# Módulo: Atendimento Veterinário, Laudo Assinado e Termo de Adoção

**Status:** Modelagem aprovada para implementação. Este documento é a fonte de verdade do módulo — qualquer mudança de escopo deve ser refletida aqui antes de mexer no código.

**Decisões já validadas com o usuário:**
- Ferramenta gratuita para o parceiro usar (incluída na assinatura já existente, não cobra a mais).
- Assinatura eletrônica **simples e auditável** (hash + timestamp + CRMV), sem validade jurídica ICP-Brasil por enquanto.
- Só parceiros categoria `clinica` com veterinário de CRMV validado podem abrir atendimento/emitir laudo.
- Atendimento presencial (o pet está fisicamente na clínica) — sem telemedicina nesta fase.
- Adicionado ao escopo: quando um pet é doado/adotado, gerar um **Termo de Responsabilidade de Adoção** assinado pelo adotante, com opção de validação testemunhal por um parceiro (petshop/veterinário).

---

## 1. Visão geral do módulo

Hoje o sistema não tem um "pet" persistente — só `anuncios` (temporários: perdido/achado/doação) e `petlove_pets` (cruzamento). Este módulo introduz a primeira entidade de pet permanente (`pets`) e constrói em cima dela:

1. **Prontuário/Atendimento** — veterinário parceiro registra consultas ao longo do tempo.
2. **Laudo assinado** — documento clínico (laudo, atestado, receituário) gerado a partir de um atendimento, com assinatura eletrônica simples do veterinário.
3. **Termo de Adoção assinado** — quando um anúncio de doação é resolvido (pet adotado), o adotante assina um termo de responsabilidade, opcionalmente testemunhado por um parceiro.

Os itens 2 e 3 compartilham a mesma infraestrutura de **documento assinável com múltiplos signatários** (`documentos` + `documento_assinaturas`), para não duplicar lógica de hash/auditoria — é o núcleo reutilizável do módulo.

```
                    ┌─────────────────────────┐
                    │   documentos (genérico) │
                    │  + documento_assinaturas│
                    └───────────┬─────────────┘
                    ┌────────────┴────────────┐
                    │                         │
            ┌───────▼────────┐       ┌────────▼─────────┐
            │    laudos       │       │  termos_adocao    │
            │ (via atendimento)│       │ (via anúncio doação)│
            └───────┬─────────┘       └───────────────────┘
                    │
            ┌───────▼─────────┐
            │  atendimentos    │
            └───────┬──────────┘
                    │
            ┌───────▼──────────┐      ┌──────────────────────┐
            │      pets         │◄─────┤ parceiro_veterinarios │
            │ (ficha permanente)│      │  (CRMV validado)      │
            └───────────────────┘      └──────────────────────┘
```

---

## 2. Perfis envolvidos e seus papéis

| Perfil | O que faz neste módulo |
|---|---|
| **Tutor** | Cadastra a ficha do pet, acompanha histórico de atendimentos, baixa laudos, assina o termo de adoção quando adota um pet |
| **Doador** (quem cadastrou o anúncio de doação) | Ao marcar o anúncio como resolvido, inicia o termo de adoção informando quem adotou |
| **Veterinário** (vinculado a um parceiro clínica, CRMV validado) | Abre atendimentos, registra prontuário, gera e assina laudos/atestados/receituários |
| **Parceiro/Clínica** (dono da conta parceiro) | Cadastra e gerencia veterinários da clínica, acompanha atendimentos realizados |
| **Admin da plataforma** | Valida manualmente o CRMV de cada veterinário cadastrado, audita documentos emitidos, modera denúncias de laudo/termo fraudulento |

---

## 3. Modelagem de dados

### 3.1 `pets` — ficha permanente do animal (fundação nova)

```sql
CREATE TABLE pets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tutor_usuario_id INT NOT NULL,
    nome VARCHAR(100) NOT NULL,
    especie VARCHAR(30) NOT NULL,
    raca VARCHAR(100) DEFAULT NULL,
    sexo ENUM('macho','femea') DEFAULT NULL,
    data_nascimento DATE DEFAULT NULL,
    idade_aproximada_meses SMALLINT UNSIGNED DEFAULT NULL, -- usado quando não se sabe a data exata
    cor VARCHAR(50) DEFAULT NULL,
    foto VARCHAR(255) DEFAULT NULL,
    microchip_numero VARCHAR(50) DEFAULT NULL,
    origem_anuncio_id INT DEFAULT NULL,      -- se veio de um anúncio de doação resolvido
    ativo TINYINT(1) NOT NULL DEFAULT 1,     -- soft delete (pet falecido/removido)
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (tutor_usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (origem_anuncio_id) REFERENCES anuncios(id) ON DELETE SET NULL,
    INDEX idx_tutor (tutor_usuario_id)
);
```

### 3.2 `parceiro_veterinarios` — profissionais habilitados por clínica

```sql
CREATE TABLE parceiro_veterinarios (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    parceiro_perfil_id INT NOT NULL,          -- clínica à qual pertence
    usuario_id INT NOT NULL,                  -- conta de usuário do veterinário (login próprio)
    nome_completo VARCHAR(150) NOT NULL,
    crmv_numero VARCHAR(20) NOT NULL,
    crmv_uf CHAR(2) NOT NULL,
    status ENUM('pendente_validacao','aprovado','rejeitado','suspenso') NOT NULL DEFAULT 'pendente_validacao',
    validado_por INT DEFAULT NULL,            -- admin que aprovou
    validado_em DATETIME DEFAULT NULL,
    motivo_rejeicao VARCHAR(500) DEFAULT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_crmv (crmv_numero, crmv_uf),
    FOREIGN KEY (parceiro_perfil_id) REFERENCES parceiro_perfis(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    INDEX idx_status (status)
);
```
> Nota: `UNIQUE(crmv_numero, crmv_uf)` impede duplo cadastro do mesmo CRMV em clínicas diferentes — se acontecer, é sinal de fraude ou de o vet ter trocado de clínica (fluxo de transferência tratado na Fase 2).

### 3.3 `atendimentos` — sessão de consulta

```sql
CREATE TABLE atendimentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pet_id INT UNSIGNED NOT NULL,
    parceiro_perfil_id INT NOT NULL,
    veterinario_id INT UNSIGNED NOT NULL,
    triagem_solicitacao_id INT UNSIGNED DEFAULT NULL,  -- fecha o ciclo com a Feature C, se veio de lá
    motivo_consulta VARCHAR(255) NOT NULL,
    anamnese TEXT DEFAULT NULL,
    peso_kg DECIMAL(5,2) DEFAULT NULL,
    temperatura_c DECIMAL(4,1) DEFAULT NULL,
    exame_fisico TEXT DEFAULT NULL,
    diagnostico TEXT DEFAULT NULL,
    conduta TEXT DEFAULT NULL,
    status ENUM('em_andamento','finalizado','cancelado') NOT NULL DEFAULT 'em_andamento',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    finalizado_em DATETIME DEFAULT NULL,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE CASCADE,
    FOREIGN KEY (parceiro_perfil_id) REFERENCES parceiro_perfis(id) ON DELETE CASCADE,
    FOREIGN KEY (veterinario_id) REFERENCES parceiro_veterinarios(id) ON DELETE RESTRICT,
    FOREIGN KEY (triagem_solicitacao_id) REFERENCES triagem_solicitacoes(id) ON DELETE SET NULL,
    INDEX idx_pet (pet_id),
    INDEX idx_parceiro (parceiro_perfil_id)
);
```

### 3.4 `documentos` — núcleo genérico de documento assinável (reutilizado por laudo e termo de adoção)

```sql
CREATE TABLE documentos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tipo ENUM('laudo','atestado','receituario','termo_adocao','termo_responsabilidade') NOT NULL,
    referencia_tipo ENUM('atendimento','anuncio') NOT NULL,  -- de onde este documento nasceu
    referencia_id INT UNSIGNED NOT NULL,
    conteudo_html LONGTEXT NOT NULL,           -- conteúdo renderizado (fonte da verdade textual)
    pdf_path VARCHAR(255) DEFAULT NULL,        -- gerado via Dompdf após todas as assinaturas necessárias
    hash_conteudo CHAR(64) NOT NULL,           -- SHA-256 do conteudo_html no momento da criação
    status ENUM('rascunho','aguardando_assinaturas','assinado','revogado') NOT NULL DEFAULT 'rascunho',
    codigo_verificacao VARCHAR(20) NOT NULL,   -- código curto p/ QR/consulta pública de autenticidade
    criado_por_usuario_id INT NOT NULL,
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    revogado_em DATETIME DEFAULT NULL,
    motivo_revogacao VARCHAR(500) DEFAULT NULL,
    UNIQUE KEY uq_codigo_verificacao (codigo_verificacao),
    FOREIGN KEY (criado_por_usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_referencia (referencia_tipo, referencia_id),
    INDEX idx_tipo_status (tipo, status)
);
```

### 3.5 `documento_assinaturas` — trilha de auditoria de assinatura (multi-signatário)

```sql
CREATE TABLE documento_assinaturas (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    documento_id INT UNSIGNED NOT NULL,
    usuario_id INT NOT NULL,
    papel ENUM('veterinario_autor','adotante_responsavel','doador','testemunha_parceiro') NOT NULL,
    identificacao_extra VARCHAR(100) DEFAULT NULL,  -- ex: "CRMV 12345-RO" no momento da assinatura
    hash_no_momento CHAR(64) NOT NULL,              -- SHA-256 do conteúdo no instante desta assinatura específica
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    assinado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
    FOREIGN KEY (usuario_id) REFERENCES usuarios(id) ON DELETE RESTRICT,
    INDEX idx_documento (documento_id)
);
```
> **Por que hash "no momento" em cada assinatura, além do hash no `documentos`?** Porque um termo de adoção pode ter 2-3 signatários assinando em momentos diferentes (adotante hoje, testemunha do parceiro depois) — registrar o hash em cada assinatura prova que cada um assinou exatamente aquele conteúdo, mesmo que o documento tenha `status='aguardando_assinaturas'` entre uma assinatura e outra. Depois da última assinatura necessária, o documento vira `assinado` e o PDF final é gerado.

### 3.6 `laudos` — vínculo fino entre atendimento e o documento genérico

```sql
CREATE TABLE laudos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    atendimento_id INT UNSIGNED NOT NULL,
    documento_id INT UNSIGNED NOT NULL,
    UNIQUE KEY uq_atendimento_documento (atendimento_id, documento_id),
    FOREIGN KEY (atendimento_id) REFERENCES atendimentos(id) ON DELETE CASCADE,
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE
);
```

### 3.7 `termos_adocao` — vínculo fino entre anúncio de doação e o documento genérico

```sql
CREATE TABLE termos_adocao (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    anuncio_id INT NOT NULL,                        -- o anúncio de doação resolvido
    pet_id INT UNSIGNED DEFAULT NULL,               -- preenchido se/quando a ficha do pet for criada
    documento_id INT UNSIGNED NOT NULL,
    doador_usuario_id INT NOT NULL,
    adotante_usuario_id INT DEFAULT NULL,           -- pode ser NULL se o adotante ainda não tem conta (fluxo convite)
    adotante_nome_informado VARCHAR(150) DEFAULT NULL,
    adotante_telefone_informado VARCHAR(20) DEFAULT NULL,
    parceiro_testemunha_id INT DEFAULT NULL,        -- petshop/clínica que presenciou a entrega, se houver
    status ENUM('aguardando_adotante','assinado','recusado','expirado') NOT NULL DEFAULT 'aguardando_adotante',
    criado_em DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (anuncio_id) REFERENCES anuncios(id) ON DELETE CASCADE,
    FOREIGN KEY (pet_id) REFERENCES pets(id) ON DELETE SET NULL,
    FOREIGN KEY (documento_id) REFERENCES documentos(id) ON DELETE CASCADE,
    FOREIGN KEY (doador_usuario_id) REFERENCES usuarios(id) ON DELETE CASCADE,
    FOREIGN KEY (adotante_usuario_id) REFERENCES usuarios(id) ON DELETE SET NULL,
    FOREIGN KEY (parceiro_testemunha_id) REFERENCES parceiro_perfis(id) ON DELETE SET NULL,
    INDEX idx_anuncio (anuncio_id)
);
```

---

## 4. Fluxos detalhados por perfil

### 4.1 Admin — validar CRMV de um veterinário
```gherkin
Cenário: Parceiro clínica cadastra um veterinário
  Dado que o parceiro tem categoria='clinica' e assinatura ativa
  Quando cadastra nome + CRMV + UF de um veterinário
  Então o registro fica com status 'pendente_validacao'
  E o admin vê a fila em /admin/veterinarios

Cenário: Admin aprova
  Quando o admin confere manualmente o CRMV (site do CRMV da UF, já que
    não há API pública unificada) e aprova
  Então status vira 'aprovado' e o veterinário pode logar e abrir atendimentos

Cenário: Admin rejeita
  Quando o CRMV não confere ou está irregular
  Então status vira 'rejeitado' com motivo, e o veterinário é notificado por e-mail

Cenário: CRMV duplicado
  Quando alguém tenta cadastrar um CRMV já existente em outra clínica
  Então o sistema bloqueia o cadastro e sinaliza para o admin revisar
    (pode ser o mesmo profissional mudando de clínica — fluxo de
    transferência entra na Fase 2, não no MVP)
```

### 4.2 Veterinário — atendimento e laudo
```gherkin
Cenário: Abrir atendimento presencial
  Dado que o veterinário está aprovado e logado
  Quando busca o pet (por tutor/telefone, ou o tutor mostra o código do
    pet direto da conta dele) e não encontra
  Então pode criar a ficha do pet ali mesmo (vinculado ao tutor se tiver
    conta, ou "sem tutor" até alguém reivindicar)
  Quando encontra o pet
  Então abre um novo atendimento com status 'em_andamento'

Cenário: Preencher e finalizar
  Quando o veterinário registra anamnese, exame físico, diagnóstico, conduta
  E clica em "Finalizar atendimento"
  Então o atendimento vira 'finalizado' e fica disponível pra gerar laudo

Cenário: Gerar e assinar laudo
  Quando o veterinário escolhe o tipo (laudo/atestado/receituário) e
    preenche o conteúdo final
  Então o sistema cria um registro em `documentos` (status 'rascunho')
    com o hash do conteúdo
  Quando o veterinário confirma "Assinar" (reautenticação por senha,
    não só sessão — para reforçar intenção deliberada de assinatura)
  Então grava uma linha em `documento_assinaturas` (papel
    'veterinario_autor', hash no momento, IP, timestamp)
  E o documento vira 'assinado', gera o PDF final com Dompdf
    (timbre da clínica + nome/CRMV do veterinário + QR do código de
    verificação) e vincula em `laudos`

Cenário: Tentativa de alterar laudo já assinado
  Quando alguém tenta editar o conteúdo de um documento 'assinado'
  Então o sistema bloqueia — laudo assinado é imutável; correções geram
    um novo documento referenciando o anterior como retificação
```

### 4.3 Tutor — ficha do pet e histórico
```gherkin
Cenário: Cadastrar pet pela primeira vez
  Dado que o tutor está logado
  Quando acessa "Meus Pets" → "Adicionar pet"
  Então preenche nome, espécie, raça, sexo, nascimento/idade, foto

Cenário: Ver histórico
  Quando o tutor abre a ficha de um pet
  Então vê a timeline de atendimentos (data, clínica, motivo) e pode
    baixar cada laudo assinado em PDF, com o código de verificação visível

Cenário: Pet resultante de anúncio de doação
  Quando um anúncio tipo='doacao' é marcado como resolvido
  Então o sistema oferece ao doador iniciar o Termo de Adoção
    (ver fluxo 4.4) e, se o adotante tiver conta, sugere criar a ficha
    do pet já vinculada a ele
```

### 4.4 Doador/Adotante — termo de responsabilidade de adoção
```gherkin
Cenário: Doador marca anúncio como resolvido e inicia o termo
  Dado que o doador marcou o anúncio de doação como 'resolvido'
  Quando informa quem adotou (usuário cadastrado, ou nome+telefone se
    a pessoa ainda não tem conta)
  Então o sistema cria um `documentos` (tipo='termo_adocao') com o
    conteúdo padrão (identificação do pet, do doador, do adotante,
    cláusulas de responsabilidade: alimentação, saúde, não abandono,
    ciência de que abandono é crime) e um `termos_adocao` vinculado

Cenário: Adotante com conta assina
  Dado que o adotante tem conta e recebeu o link/notificação
  Quando lê o termo e clica em "Assinar e me responsabilizar"
    (com checkbox de ciência explícita, igual ao disclaimer da triagem)
  Então grava assinatura (papel 'adotante_responsavel') e o status do
    termo avança

Cenário: Adotante sem conta ainda
  Quando a pessoa que adotou não tem conta na plataforma
  Então o termo fica 'aguardando_adotante' com um link de convite por
    e-mail/WhatsApp; ao criar conta e confirmar identidade (nome/telefone
    batendo), pode assinar

Cenário: Validação testemunhal por um parceiro (opcional)
  Dado que a adoção aconteceu presencialmente numa clínica/petshop parceiro
    (ex: pet veio de uma campanha de adoção no local)
  Quando o parceiro confirma que presenciou a entrega
  Então grava assinatura adicional (papel 'testemunha_parceiro'),
    reforçando a validade do termo

Cenário: Termo concluído
  Quando todas as assinaturas obrigatórias existem (doador sempre é
    o criador; adotante é sempre obrigatório; testemunha é opcional)
  Então o documento vira 'assinado', gera PDF, e se ainda não existe
    ficha de pet, oferece criá-la vinculada ao adotante automaticamente

Cenário de borda: Adotante recusa assinar
  Quando a pessoa se recusa ou o prazo expira (ex: 30 dias)
  Então o termo vira 'recusado'/'expirado' e fica registrado — não
    bloqueia o anúncio (que já está resolvido), mas fica sinalizado
    no histórico como "responsabilidade não formalizada"
```

### 4.5 Parceiro/Clínica — gestão de veterinários e atendimentos
```gherkin
Cenário: Gerenciar equipe
  Quando o parceiro (categoria clinica) acessa o painel
  Então vê lista de veterinários cadastrados com status, pode adicionar
    novos (entram como 'pendente_validacao') e ver o histórico de
    atendimentos realizados pela clínica (não o conteúdo clínico
    detalhado de cada um, por sigilo — só metadados: data, pet, veterinário)
```

---

## 5. Roadmap de implementação (fases)

### Fase 0 — Fundação (pré-requisito de tudo)
1. Migration: tabela `pets`.
2. Tela "Meus Pets" (CRUD simples) para o tutor.
3. Migration: tabelas `documentos` + `documento_assinaturas` (núcleo genérico, ainda sem nenhum caso de uso ligado).
4. Testar o núcleo de documento/assinatura isoladamente (script CLI): criar documento, assinar, verificar hash, bloquear edição pós-assinatura.

**Critério de saída da Fase 0:** um tutor consegue cadastrar um pet; o núcleo de documento assinável funciona isolado e testado, sem nenhuma tela ainda.

### Fase 1 — Termo de Adoção (menor risco clínico, maior valor imediato contra abandono)
5. Migration: `termos_adocao`.
6. Fluxo: marcar anúncio de doação como resolvido → iniciar termo → adotante assina (com/sem conta) → parceiro testemunha (opcional).
7. Geração de PDF do termo com Dompdf + código de verificação.
8. Tela do tutor/doador para ver termos pendentes/assinados.

**Critério de saída da Fase 1:** termo de adoção funcionando ponta a ponta, testado com os 2 cenários de borda (adotante sem conta, recusa/expiração).

### Fase 2 — Veterinários e Atendimentos (abre o módulo clínico)
9. Migration: `parceiro_veterinarios`.
10. Fluxo de cadastro de veterinário + fila de validação manual do admin (`/admin/veterinarios`).
11. Migration: `atendimentos`.
12. Tela do veterinário: buscar/criar pet, abrir e preencher atendimento, finalizar.

**Critério de saída da Fase 2:** veterinário aprovado consegue abrir e finalizar um atendimento completo (sem laudo ainda).

### Fase 3 — Laudo assinado (reaproveita o núcleo da Fase 0)
13. Migration: `laudos`.
14. Tela de geração de laudo/atestado/receituário a partir de um atendimento finalizado.
15. Fluxo de assinatura (reautenticação por senha) + geração de PDF com timbre/QR.
16. Regra de imutabilidade pós-assinatura + fluxo de retificação (novo documento referenciando o anterior).
17. Tela do tutor: histórico de laudos do pet, download em PDF.

**Critério de saída da Fase 3:** ciclo completo testado — atendimento → laudo → assinatura → PDF → download pelo tutor → tentativa de editar laudo assinado é bloqueada.

### Fase 4 — Enriquecimento (não bloqueador, evolutivo)
- Página pública de verificação de autenticidade por `codigo_verificacao` (QR aponta pra ela).
- Compartilhar laudo/termo por WhatsApp.
- Vacinas/pesagens como sub-registros estruturados do prontuário (hoje ficam em campo livre `exame_fisico`/`anamnese`).
- Avaliar assinatura ICP-Brasil real se houver demanda/exigência legal futura.

---

## 6. Riscos e decisões que precisam de atenção contínua

| Risco | Mitigação adotada |
|---|---|
| Petshop sem veterinário emitindo laudo médico | Só `parceiro_veterinarios` aprovado pode assinar `laudos`; petshop puro (sem categoria clínica) nunca tem acesso ao módulo |
| CRMV falso/não validável automaticamente | Validação manual do admin na Fase 2; documentado como processo humano recorrente, não só código |
| Assinatura sem validade jurídica ICP-Brasil | Aviso explícito na tela de assinatura sobre a natureza da assinatura (auditável, não cartorial); reavaliar se surgir exigência legal |
| Laudo alterado após assinado | Documento assinado é imutável; correções só via novo documento vinculado como retificação |
| Termo de adoção sem adotante confirmar identidade | Se tiver conta, confere; se não tiver, fica pendente até criar conta — nunca assina "no nome de alguém" sem ação própria da pessoa |
| Dado de saúde do pet ligado ao tutor (pessoa física) | Revisar política de privacidade/LGPD para cobrir prontuário — dado sensível por associação, mesmo não sendo dado de saúde humana |

---

## 7. Reaproveitamento explícito do que já existe
- `parceiro_perfis.categoria='clinica'` já filtra quem pode ter veterinários.
- Dompdf (`composer.json`) já usado no contrato de parceiro — mesmo padrão para laudo/termo.
- `auditLog()` já existente registra toda ação administrativa (aprovação de CRMV, revogação de laudo).
- `Conversa` (chat interno) pode ser reaproveitado para o doador/parceiro conversar com o adotante durante o processo do termo.
- `Triagem` já linka a clínica parceira — o atendimento pode nascer diretamente de uma triagem anterior (`triagem_solicitacao_id` em `atendimentos`), fechando o ciclo triagem → atendimento → laudo.
