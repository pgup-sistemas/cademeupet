# 🐾 PROMPT MASTER — Claude Code | Projeto "Cadê Meu Pet?"
> **Versão:** 1.0 | **Base:** PetFinder (PHP/MySQL/Bootstrap 5)
> **Executor:** Claude Code (seguir este documento de forma estrita e sequencial)

---

## ⚠️ REGRAS ABSOLUTAS DE EXECUÇÃO

Antes de qualquer ação, leia e internalize estas regras. **Não há exceções.**

1. **Nunca pule etapas.** Execute cada fase na ordem exata definida neste documento.
2. **Nunca assuma.** Se um arquivo não existir onde esperado, reporte antes de continuar.
3. **Nunca quebre o que funciona.** Antes de alterar qualquer arquivo, faça backup local (`*.bak`).
4. **Nunca use emojis em código-fonte.** Emojis são permitidos apenas em comentários de documentação.
5. **Sempre confirme ao final de cada fase** com um relatório resumido do que foi feito.
6. **Mantenha o português brasileiro** em todos os textos de interface, mensagens e comentários.
7. **Todo ícone de UI usa Font Awesome 6 Free** — nenhum emoji no frontend.
8. **Commits atômicos:** um commit por tarefa concluída, com mensagem descritiva em português.

---

## 📋 FASE 0 — SINCRONIZAÇÃO E INVENTÁRIO COMPLETO

### 0.1 — Atualizar o repositório local

```bash
git fetch origin
git status
git pull origin main
```

> Se houver conflitos, liste-os e interrompa. Não resolva conflitos automaticamente.

### 0.2 — Inventário de arquivos

Percorra toda a estrutura do projeto e gere um relatório com:

- Lista de todos os arquivos `.php`, `.sql`, `.js`, `.css`, `.json`
- Tamanho e data de última modificação de cada arquivo
- Identificação de arquivos órfãos (não referenciados em nenhum outro arquivo)
- Identificação de arquivos duplicados por nome

Salve o relatório em: `docs/inventario_inicial.md`

### 0.3 — Verificação de dependências externas

Verifique se os seguintes recursos estão corretamente referenciados no projeto:

- [ ] Bootstrap 5 (versão exata usada)
- [ ] jQuery (versão exata usada)
- [ ] Font Awesome (versão exata — se não estiver, registre como pendência)
- [ ] Google Maps / Leaflet.js (para mapas de localização)
- [ ] Qualquer outra biblioteca JS/CSS

Salve em: `docs/dependencias.md`

---

## 📋 FASE 1 — AUDITORIA TÉCNICA COMPLETA

### 1.1 — Verificação de segurança básica

Para cada arquivo PHP, verifique e documente:

- [ ] **SQL Injection:** Todas as queries usam PDO com prepared statements?
- [ ] **XSS:** Toda saída de dados do usuário usa `htmlspecialchars()` ou equivalente?
- [ ] **CSRF:** Formulários críticos têm token CSRF implementado?
- [ ] **Upload de arquivos:** Há validação de tipo MIME e extensão nos uploads de imagem?
- [ ] **Senhas:** O sistema usa `password_hash()` e `password_verify()`?
- [ ] **Sessões:** As sessões são regeneradas após login (`session_regenerate_id(true)`)?
- [ ] **Variáveis de ambiente:** Credenciais estão em `config.php` fora do versionamento?

Gere relatório em: `docs/auditoria_seguranca.md`

### 1.2 — Verificação de integridade do banco de dados

Analise o arquivo `database/schema.sql` e verifique:

- [ ] Todas as tabelas têm chave primária definida
- [ ] Foreign keys estão corretamente declaradas com `ON DELETE` e `ON UPDATE`
- [ ] Índices existem para colunas usadas em `WHERE` e `JOIN` frequentes
- [ ] Charset e collation consistentes (`utf8mb4` / `utf8mb4_unicode_ci`)
- [ ] Campos de data/hora usam `DATETIME` ou `TIMESTAMP` adequadamente

Gere relatório em: `docs/auditoria_banco.md`

### 1.3 — Verificação de rotas e controllers

Mapeie todas as rotas/páginas existentes:

| Arquivo/Rota | Controller | Model | View | Status |
|---|---|---|---|---|
| (preencher automaticamente) | | | | ✅ OK / ⚠️ Parcial / ❌ Quebrado |

Verifique se cada rota:
- Tem autenticação quando necessário
- Trata erros com páginas adequadas (404, 403, 500)
- Redireciona corretamente após ações (POST → redirect → GET)

Gere relatório em: `docs/mapa_rotas.md`

### 1.4 — Execução dos testes existentes

```bash
php tests/test_runner.php
```

Documente cada teste: nome, resultado (passou/falhou), mensagem de erro se houver.

Gere relatório em: `docs/resultado_testes.md`

---

## 📋 FASE 2 — RENOMEAÇÃO COMPLETA: PetFinder → "Cadê Meu Pet?"

### 2.1 — Mapeamento de ocorrências antes de alterar

Antes de qualquer modificação, gere uma lista exaustiva de todas as ocorrências de:

```
petfinder | PetFinder | PETFINDER | pet_finder | pet-finder
admin@petfinder.com
```

Em arquivos: `.php`, `.sql`, `.js`, `.css`, `.json`, `.md`, `.htaccess`, `.env`

Salve em: `docs/ocorrencias_renomeacao.md`

### 2.2 — Substituições de texto e identidade

Aplique as seguintes substituições **de forma global e controlada**:

| De | Para |
|---|---|
| `PetFinder` | `Cadê Meu Pet?` |
| `petfinder` (título/label) | `cademeupet` (slugs/rotas) ou `Cadê Meu Pet?` (display) |
| `admin@petfinder.com` | `admin@cademeupet.com.br` |
| Constante `DB_NAME` no schema | `cademeupet` |
| Variável `BASE_URL` | Atualizar conforme ambiente |
| `<title>PetFinder</title>` | `<title>Cadê Meu Pet?</title>` |
| Meta description | `Perdeu ou encontrou um pet? Cadê Meu Pet? conecta tutores no Brasil.` |

### 2.3 — Identidade visual básica

Atualize as variáveis CSS/Bootstrap para refletir a nova marca:

```css
:root {
  --cmp-primary: #FF6B35;      /* laranja caloroso — cor principal */
  --cmp-secondary: #2D6A4F;    /* verde natureza — cor secundária */
  --cmp-accent: #FFD166;       /* amarelo suave — destaque */
  --cmp-dark: #1A1A2E;         /* quase-preto — textos */
  --cmp-light: #F8F9FA;        /* fundo claro */
  --cmp-danger: #EF233C;       /* vermelho alertas */
  --cmp-success: #06D6A0;      /* verde confirmações */
}
```

Crie ou atualize: `assets/css/cademeupet.css`

### 2.4 — Atualizar schema do banco

No arquivo `database/schema.sql`, altere:
- Nome do banco para `cademeupet`
- Comentários internos das tabelas

Crie também `database/rename_migration.sql` com:
```sql
RENAME DATABASE `petfinder` TO `cademeupet`;
-- (ou instruções equivalentes compatíveis com MySQL 8)
```

---

## 📋 FASE 3 — SUBSTITUIÇÃO DE EMOJIS POR FONT AWESOME 6

### 3.1 — Garantir que Font Awesome 6 Free está carregado

Em todos os arquivos de layout/header (`views/layouts/` ou equivalente), verifique se existe:

```html
<link rel="stylesheet"
      href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css"
      integrity="sha512-..." crossorigin="anonymous" referrerpolicy="no-referrer" />
```

Se não existir, adicione. Use a versão **6.5.0** (ou a mais recente disponível no cdnjs).

### 3.2 — Mapeamento de emojis para ícones FA

Localize todos os emojis usados no frontend e substitua conforme tabela:

| Emoji | Contexto provável | Ícone Font Awesome |
|---|---|---|
| 🐾 | Marca / patas | `<i class="fa-solid fa-paw"></i>` |
| 🐕 | Cachorro / perdido | `<i class="fa-solid fa-dog"></i>` |
| 🐈 | Gato / encontrado | `<i class="fa-solid fa-cat"></i>` |
| 📍 | Localização | `<i class="fa-solid fa-location-dot"></i>` |
| 📷 | Foto / upload | `<i class="fa-solid fa-camera"></i>` |
| ❤️ | Favorito / curtida | `<i class="fa-solid fa-heart"></i>` |
| 🔔 | Notificação / alerta | `<i class="fa-solid fa-bell"></i>` |
| 🔍 | Busca | `<i class="fa-solid fa-magnifying-glass"></i>` |
| ✅ | Confirmação | `<i class="fa-solid fa-circle-check"></i>` |
| ⚠️ | Aviso | `<i class="fa-solid fa-triangle-exclamation"></i>` |
| ❌ | Erro / fechar | `<i class="fa-solid fa-circle-xmark"></i>` |
| 🏠 | Home / início | `<i class="fa-solid fa-house"></i>` |
| 👤 | Perfil / usuário | `<i class="fa-solid fa-user"></i>` |
| 💬 | Mensagem / contato | `<i class="fa-solid fa-comment"></i>` |
| 💰 / 💵 | Doação | `<i class="fa-solid fa-hand-holding-heart"></i>` |
| 📋 | Anúncio / listagem | `<i class="fa-solid fa-list"></i>` |
| 🗺️ | Mapa | `<i class="fa-solid fa-map"></i>` |
| 🔗 | Compartilhar | `<i class="fa-solid fa-share-nodes"></i>` |
| 📱 | WhatsApp / contato | `<i class="fa-brands fa-whatsapp"></i>` |
| 🎉 | Sucesso / reunião | `<i class="fa-solid fa-party-horn"></i>` |

> **Para qualquer emoji não listado acima:** consulte https://fontawesome.com/search e escolha o ícone mais semanticamente próximo. Documente a escolha em `docs/mapa_icones.md`.

### 3.3 — Varredura e substituição

Percorra todos os arquivos `.php`, `.html`, `.js` e substitua cada emoji por seu ícone FA correspondente.

Após a substituição, gere `docs/mapa_icones.md` com:
- Arquivo | Linha | Emoji removido | Ícone FA inserido

---

## 📋 FASE 4 — FRONTEND PROFISSIONAL

### 4.1 — Navbar

A navbar deve conter:

```
[<i fa-paw>] Cadê Meu Pet?    [Buscar]  [Perdidos] [Encontrados] [Mapa] [Login/Avatar▼]
```

Requisitos:
- Logo com ícone `fa-paw` + nome do sistema em fonte bold
- Fundo `var(--cmp-primary)` com texto branco
- Dropdown para usuário logado com: Meu Perfil, Meus Anúncios, Meus Favoritos, Sair
- Botão de CTA "Publicar Anúncio" com `fa-plus` em destaque
- Responsivo com hamburger menu no mobile

### 4.2 — Cards de anúncio

Cada card de pet deve ter:

```
┌─────────────────────────────┐
│  [FOTO DO PET]              │
│  Badge: PERDIDO / ENCONTRADO│
├─────────────────────────────┤
│  Nome do Pet                │
│  🗓 Data  📍 Cidade, UF     │  ← substituir emojis por FA
│  ─────────────────────────  │
│  [❤ Favoritar] [Ver Mais →]│
└─────────────────────────────┘
```

- Badge colorido: `PERDIDO` em vermelho (`--cmp-danger`) | `ENCONTRADO` em verde (`--cmp-success`)
- Hover com sombra suave e leve elevação (transform translateY)
- Foto com `object-fit: cover` e proporção 16:9

### 4.3 — Página de detalhes do anúncio

Layout em duas colunas (desktop) / coluna única (mobile):

**Coluna esquerda (60%):**
- Galeria de fotos (carousel Bootstrap)
- Descrição completa
- Descrição completa do pet (espécie, raça, características)

**Coluna direita (40%):**
- Card fixo (sticky) com:
  - Status do anúncio (badge)
  - Mapa de localização exata (ver Fase 6)
  - Botão "Entrar em Contato" com `fa-comment`
  - Botão "Compartilhar no WhatsApp" com `fa-whatsapp`
  - Botão "Favoritar" com `fa-heart`
  - Data de publicação e visualizações

### 4.4 — Página inicial (home)

Estrutura:

1. **Hero Section:** headline impactante + campo de busca rápida centralizado
2. **Stats Bar:** total de pets perdidos | encontrados | reuniões confirmadas
3. **Últimos anúncios:** grid de cards (3 colunas desktop / 1 mobile)
4. **CTA "Publicar Anúncio":** seção destacada com fundo `--cmp-secondary`
5. **Mapa geral:** mapa interativo com pins dos últimos anúncios (ver Fase 6)
6. **Footer:** links úteis + redes sociais + créditos

### 4.5 — Formulário de publicação de anúncio

Fluxo em etapas (stepper visual):

```
[1. Tipo] → [2. Sobre o Pet] → [3. Localização] → [4. Fotos] → [5. Contato] → [6. Revisar]
```

Cada etapa com:
- Indicador de progresso visual no topo
- Botões "Voltar" e "Continuar" / "Publicar"
- Validação inline em tempo real

---

## 📋 FASE 5 — FUNCIONALIDADE: PET LOVE (CRUZAMENTO / ACASALAMENTO DE PETS)

### 5.1 — Conceito

**Pet Love** é o módulo onde o tutor disponibiliza seu pet para **cruzamento (acasalamento)** com outro pet compatível. O sistema conecta tutores de pets da mesma espécie, raça e porte, com base em critérios como pedigree, idade, sexo e localização.

> **IMPORTANTE:** Este módulo é **separado** do módulo de pets perdidos/encontrados. São dois fluxos distintos no mesmo sistema:
> - **Anúncios** (Perdidos/Encontrados) — já existente
> - **Pet Love** (Cruzamento) — novo módulo
>
> Um pet cadastrado no Pet Love NÃO é um pet perdido. Mantenha as tabelas e telas separadas.

### 5.2 — Diretrizes éticas obrigatórias

O módulo deve obrigatoriamente:
- Exibir aviso de criação responsável e contra a superpopulação animal
- Exigir confirmação de que o pet é saudável, vacinado e vermifugado
- Recomendar acompanhamento veterinário
- Permitir denúncia de anúncios de cruzamento (criadouros irregulares / maus-tratos)
- Incluir checkbox obrigatório: "Declaro que pratico a criação responsável e que meu pet está saudável"

Exiba um banner fixo no topo do módulo:
```html
<div class="alert alert-info">
  <i class="fa-solid fa-circle-info"></i>
  O Cadê Meu Pet? incentiva a <strong>criação responsável</strong>.
  Consulte sempre um veterinário antes do cruzamento.
</div>
```

### 5.3 — Banco de dados

Crie a tabela de perfis de cruzamento. Adicione ao `database/schema.sql` e crie `database/migrations/003_pet_love.sql`:

```sql
CREATE TABLE IF NOT EXISTS `petlove_pets` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`         INT UNSIGNED NOT NULL,
  `nome`            VARCHAR(100) NOT NULL,
  `especie`         ENUM('cao','gato','outro') NOT NULL DEFAULT 'cao',
  `raca`            VARCHAR(100) NOT NULL,
  `porte`           ENUM('mini','pequeno','medio','grande','gigante') NOT NULL,
  `sexo`            ENUM('macho','femea') NOT NULL,
  `idade_meses`     SMALLINT UNSIGNED NOT NULL,
  `cor`             VARCHAR(50) NULL,
  `peso_kg`         DECIMAL(5,2) NULL,
  `tem_pedigree`    TINYINT(1) NOT NULL DEFAULT 0,
  `pedigree_num`    VARCHAR(60) NULL,
  `vacinado`        TINYINT(1) NOT NULL DEFAULT 0,
  `vermifugado`     TINYINT(1) NOT NULL DEFAULT 0,
  `castrado`        TINYINT(1) NOT NULL DEFAULT 0,
  `descricao`       TEXT NULL,
  `objetivo`        ENUM('cruzamento','pedigree','companhia') NOT NULL DEFAULT 'cruzamento',
  `latitude`        DECIMAL(10,8) NULL,
  `longitude`       DECIMAL(11,8) NULL,
  `cidade`          VARCHAR(100) NULL,
  `estado`          CHAR(2) NULL,
  `disponivel`      TINYINT(1) NOT NULL DEFAULT 1,
  `criacao_responsavel` TINYINT(1) NOT NULL DEFAULT 0,
  `status`          ENUM('ativo','pausado','removido') NOT NULL DEFAULT 'ativo',
  `criado_em`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `atualizado_em`   DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_especie_raca_porte` (`especie`, `raca`, `porte`),
  INDEX `idx_sexo` (`sexo`),
  INDEX `idx_geo` (`latitude`, `longitude`),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `petlove_fotos` (
  `id`          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `petlove_id`  INT UNSIGNED NOT NULL,
  `caminho`     VARCHAR(255) NOT NULL,
  `principal`   TINYINT(1) NOT NULL DEFAULT 0,
  `ordem`       TINYINT UNSIGNED NOT NULL DEFAULT 0,
  FOREIGN KEY (`petlove_id`) REFERENCES `petlove_pets`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Manifestações de interesse entre tutores
CREATE TABLE IF NOT EXISTS `petlove_interesses` (
  `id`              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `petlove_id`      INT UNSIGNED NOT NULL,  -- pet de destino (alvo do interesse)
  `interessado_id`  INT UNSIGNED NOT NULL,  -- user que demonstrou interesse
  `pet_interessado_id` INT UNSIGNED NULL,   -- opcional: o pet do interessado
  `mensagem`        TEXT NULL,
  `status`          ENUM('pendente','aceito','recusado') NOT NULL DEFAULT 'pendente',
  `criado_em`       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uniq_interesse` (`petlove_id`, `interessado_id`),
  FOREIGN KEY (`petlove_id`)     REFERENCES `petlove_pets`(`id`) ON DELETE CASCADE,
  FOREIGN KEY (`interessado_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

### 5.4 — Lógica de compatibilidade (matching)

Crie `models/PetLove.php` com método de busca de matches compatíveis. Um pet é compatível com outro quando:

| Critério | Regra |
|---|---|
| Espécie | Deve ser **idêntica** (obrigatório) |
| Raça | Idêntica (filtro padrão) — permitir "raças semelhantes" como opção |
| Porte | Idêntico ou adjacente (ex.: médio combina com pequeno/grande) |
| Sexo | **Oposto** (macho busca fêmea e vice-versa) — obrigatório |
| Idade | Dentro de faixa reprodutiva saudável da espécie |
| Distância | Ordenar por proximidade geográfica (usar lat/lng) |
| Disponibilidade | Apenas pets com `disponivel = 1` e `status = 'ativo'` |

Métodos a implementar:
- `buscarCompativeis(int $petloveId, array $filtros = []): array`
- `calcularDistanciaKm(float $lat1, float $lng1, float $lat2, float $lng2): float` (fórmula de Haversine)
- `pontuacaoCompatibilidade(array $petA, array $petB): int` (score 0–100 baseado em raça, porte, pedigree, distância)

### 5.5 — Telas do módulo Pet Love

| Tela | Rota | Descrição |
|---|---|---|
| Vitrine Pet Love | `/petlove` | Grid de pets disponíveis para cruzamento, com filtros |
| Detalhe do pet | `/petlove/{id}` | Perfil completo: fotos, dados, pedigree, score de match |
| Matches sugeridos | `/petlove/{id}/matches` | Pets compatíveis ordenados por compatibilidade |
| Cadastrar pet | `/petlove/novo` | Formulário em stepper para registrar pet no Pet Love |
| Meus pets (Pet Love) | `/minha-conta/petlove` | Gerenciar pets cadastrados + interesses recebidos |

### 5.6 — Filtros da vitrine

A vitrine `/petlove` deve ter filtros:
- Espécie (cão / gato / outro)
- Raça (autocomplete)
- Porte (mini / pequeno / médio / grande / gigante)
- Sexo (macho / fêmea)
- Com pedigree (sim / não)
- Cidade / raio de distância (km)
- Faixa de idade

### 5.7 — Card de pet no Pet Love

```html
<div class="card petlove-card">
  <img src="{foto_principal}" class="card-img-top" alt="{nome}">
  <span class="badge badge-sexo">
    <i class="fa-solid {fa-mars|fa-venus}"></i> {Macho|Fêmea}
  </span>
  <div class="card-body">
    <h5 class="card-title">{nome}</h5>
    <p class="card-text">
      <i class="fa-solid fa-dog"></i> {raca} ·
      <i class="fa-solid fa-ruler"></i> {porte}<br>
      <i class="fa-solid fa-cake-candles"></i> {idade} ·
      <i class="fa-solid fa-location-dot"></i> {cidade}, {uf}
    </p>
    <div class="petlove-badges">
      <span class="badge bg-success" title="Pedigree"><i class="fa-solid fa-award"></i> Pedigree</span>
      <span class="badge bg-info" title="Vacinado"><i class="fa-solid fa-syringe"></i> Vacinado</span>
    </div>
    <a href="/petlove/{id}" class="btn btn-primary btn-sm w-100 mt-2">
      <i class="fa-solid fa-heart"></i> Ver perfil
    </a>
  </div>
</div>
```

### 5.8 — Demonstrar interesse (match)

Na página de detalhe, o tutor logado pode:
- Botão **"Tenho interesse no cruzamento"** (`fa-heart`) → abre modal
- Modal: selecionar qual dos seus pets (do mesmo perfil compatível) + mensagem opcional
- Ao enviar → cria registro em `petlove_interesses` + notifica o dono por email
- O dono do pet vê os interesses recebidos em `/minha-conta/petlove` e pode **aceitar** ou **recusar**
- Ao aceitar → libera os contatos entre os dois tutores

Crie:
- `controllers/PetLoveController.php` (vitrine, detalhe, cadastro, edição)
- `controllers/PetLoveInteresseController.php` (manifestar/aceitar/recusar interesse)
- `assets/js/petlove.js` (filtros AJAX, modal de interesse, ordenação por match)

### 5.9 — Integração com a navbar e home

- Adicionar item **"Pet Love"** na navbar com ícone `fa-heart`
- Adicionar uma seção na home: "Encontre um par para o seu pet" com botão para `/petlove`

---

## 📋 FASE 6 — FUNCIONALIDADE: LOCALIZAÇÃO EXATA DOS ANÚNCIOS

### 6.1 — Estratégia de mapa

Use **Leaflet.js** (open source, sem necessidade de API key para mapas básicos) com tiles do OpenStreetMap.

```html
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
```

> Alternativa com Google Maps: requer `GOOGLE_MAPS_API_KEY` em `config.php`. Implemente suporte a ambos com fallback.

### 6.2 — Banco de dados — adicionar localização

Verifique se a tabela `pets` (ou `anuncios`) já tem campos de localização. Se não, crie a migration:

```sql
-- database/migrations/004_localizacao_pets.sql
ALTER TABLE `pets`
  ADD COLUMN `latitude`       DECIMAL(10, 8)  NULL AFTER `cidade`,
  ADD COLUMN `longitude`      DECIMAL(11, 8)  NULL AFTER `latitude`,
  ADD COLUMN `endereco_exato` VARCHAR(255)    NULL AFTER `longitude`,
  ADD COLUMN `bairro`         VARCHAR(100)    NULL AFTER `endereco_exato`,
  ADD COLUMN `cep`            VARCHAR(9)      NULL AFTER `bairro`,
  ADD INDEX  `idx_lat_lng`    (`latitude`, `longitude`);
```

### 6.3 — Formulário de localização (Etapa 3 do stepper)

A etapa de localização no formulário deve oferecer três formas de entrada:

**Opção A — Mapa interativo (clicar no mapa):**
```
[Mapa Leaflet clicável — usuário arrasta pin para o local exato]
Lat: _______ | Lng: _______   (preenchidos automaticamente)
```

**Opção B — Usar minha localização atual:**
```
[Botão: <i fa-location-crosshairs> Usar minha localização]
```

**Opção C — Busca por endereço (geocoding reverso):**
```
[Campo de texto: "Digite o endereço ou ponto de referência"]
[Botão: Buscar no Mapa]
```

> Use a API Nominatim (OpenStreetMap) para geocoding — gratuita, sem key.
> URL: `https://nominatim.openstreetmap.org/search?q={endereco}&format=json&limit=1`

Campos obrigatórios do formulário:
- `latitude` (hidden, preenchido pelo mapa)
- `longitude` (hidden, preenchido pelo mapa)
- `cidade` (text)
- `estado` (select com UFs do Brasil)
- `bairro` (text, opcional)
- `endereco_exato` (text — "Próximo ao Parque X", "Rua Y, esquina com Z")

### 6.4 — Exibição de localização nos anúncios

**No card de listagem:**
```html
<small class="text-muted">
  <i class="fa-solid fa-location-dot"></i> Bairro, Cidade – UF
</small>
```

**Na página de detalhes:**
- Mapa Leaflet com pin na localização exata (readonly)
- Popup no pin: nome do pet + data do anúncio
- Botão "Abrir no Google Maps" com link `https://maps.google.com/?q={lat},{lng}`
- Exibir endereço textual abaixo do mapa

### 6.5 — Mapa geral na home

Na home page, exiba um mapa com todos os anúncios ativos:

```php
// controllers/MapController.php
// GET /api/mapa/pins
// Retorna JSON: [ { id, nome_pet, tipo, lat, lng, foto_thumb, cidade } ]
// Limite: últimos 200 anúncios ativos, com lat/lng definidos
```

No mapa:
- Pin vermelho = pet perdido
- Pin verde = pet encontrado
- Clique no pin abre popup com miniatura e link para o anúncio
- Clustering automático para muitos pins (use plugin Leaflet.markercluster)

---

## 📋 FASE 7 — FUNCIONALIDADES ADICIONAIS RECOMENDADAS

*Com base na análise do projeto, as seguintes funcionalidades são recomendadas para implementação:*

### 7.1 — Sistema de "Pet Encontrado!" (Reunião Confirmada)

Quando o tutor confirma que o pet foi encontrado/reunido:

1. Status do anúncio muda para `RESOLVIDO`
2. Badge especial aparece no anúncio: `<i class="fa-solid fa-heart-circle-check"></i> Reunido!`
3. Contador de "reuniões confirmadas" na home é incrementado
4. Email automático de parabenização é enviado ao tutor
5. Anúncio fica visível por mais 30 dias como "caso resolvido" (inspire outros)

**Banco:**
```sql
ALTER TABLE `pets`
  ADD COLUMN `status`       ENUM('ativo','resolvido','expirado','removido') NOT NULL DEFAULT 'ativo',
  ADD COLUMN `resolvido_em` DATETIME NULL,
  ADD COLUMN `historia_reuniao` TEXT NULL;
```

### 7.2 — Alertas de Busca por Área

Usuário cadastra: espécie + cor + raça + cidade/raio de km. Sistema notifica por email quando um anúncio correspondente é publicado.

**Banco:**
```sql
CREATE TABLE `alertas_busca` (
  `id`        INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  `user_id`   INT UNSIGNED NOT NULL,
  `especie`   VARCHAR(50) NULL,
  `raca`      VARCHAR(100) NULL,
  `cor`       VARCHAR(50) NULL,
  `latitude`  DECIMAL(10,8) NULL,
  `longitude` DECIMAL(11,8) NULL,
  `raio_km`   TINYINT UNSIGNED NOT NULL DEFAULT 20,
  `ativo`     TINYINT(1) NOT NULL DEFAULT 1,
  `criado_em` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

### 7.3 — Compartilhamento Social Simplificado

Em cada anúncio, botões de compartilhamento one-click:

```html
<!-- WhatsApp -->
<a href="https://wa.me/?text=Ajude+a+encontrar+[NOME]+%F0%9F%90%BE+{URL}"
   target="_blank" class="btn btn-success btn-sm">
  <i class="fa-brands fa-whatsapp"></i> WhatsApp
</a>

<!-- Facebook -->
<a href="https://www.facebook.com/sharer/sharer.php?u={URL}"
   target="_blank" class="btn btn-primary btn-sm">
  <i class="fa-brands fa-facebook"></i> Facebook
</a>

<!-- Copiar link -->
<button class="btn btn-outline-secondary btn-sm" onclick="copyLink('{URL}')">
  <i class="fa-solid fa-link"></i> Copiar Link
</button>
```

### 7.4 — Painel do Usuário (Dashboard)

Página `/minha-conta` com abas:

| Aba | Ícone | Conteúdo |
|---|---|---|
| Meus Anúncios | `fa-list` | Anúncios ativos, resolvidos e expirados |
| Favoritos | `fa-heart` | Pets favoritados |
| Alertas | `fa-bell` | Alertas de busca configurados |
| Configurações | `fa-gear` | Foto de perfil, senha, notificações |

### 7.5 — SEO e Metadados Dinâmicos

Para cada anúncio, gerar metadados Open Graph:

```php
// Em views/pets/show.php
echo '<meta property="og:title" content="' . htmlspecialchars($pet['nome']) . ' — Cadê Meu Pet?" />';
echo '<meta property="og:description" content="' . htmlspecialchars(substr($pet['descricao'], 0, 160)) . '" />';
echo '<meta property="og:image" content="' . $pet['foto_principal_url'] . '" />';
echo '<meta property="og:url" content="' . BASE_URL . '/pet/' . $pet['slug'] . '" />';
echo '<meta property="og:type" content="website" />';
```

Adicione campo `slug` na tabela `pets` para URLs amigáveis (`/pet/rex-perdido-porto-velho`).

### 7.6 — Moderação Básica de Anúncios

Adicione flag `moderacao_status` na tabela `pets`:
- `pendente`: aguardando revisão (opcional para novos usuários)
- `aprovado`: visível publicamente
- `rejeitado`: removido com motivo

Painel admin com fila de moderação acessível em `/admin/moderacao`.

---

## 📋 FASE 8 — PAINEL ADMINISTRATIVO

### 8.1 — Rotas do painel

Todas as rotas `/admin/*` devem verificar role `admin` na sessão. Redirecionar para home se não autorizado.

### 8.2 — Seções do painel admin

| Seção | Rota | Descrição |
|---|---|---|
| Dashboard | `/admin` | KPIs: total usuários, anúncios, reuniões, doações |
| Usuários | `/admin/usuarios` | Listar, bloquear, desbloquear, promover a admin |
| Anúncios | `/admin/anuncios` | Listar todos, filtrar por status, remover |
| Moderação | `/admin/moderacao` | Fila de anúncios pendentes de aprovação |
| Doações | `/admin/doacoes` | Histórico e mural de doações |
| Configurações | `/admin/config` | Textos do site, limites, chaves de API |

### 8.3 — KPIs no dashboard admin

```
┌──────────────┐ ┌──────────────┐ ┌──────────────┐ ┌──────────────┐
│ 👥 Usuários  │ │ 📋 Anúncios  │ │ ❤️ Reuniões  │ │ 💰 Doações   │
│    1.234     │ │     456      │ │     89       │ │  R$ 2.340    │
│  +12 hoje    │ │  +5 hoje     │ │  este mês    │ │  este mês    │
└──────────────┘ └──────────────┘ └──────────────┘ └──────────────┘
```

---

## 📋 FASE 9 — TESTES E VALIDAÇÃO

### 9.1 — Atualizar suite de testes

No arquivo `tests/test_runner.php`, adicione testes para:

- [ ] Pet Love: cadastro de pet para cruzamento salva todos os campos
- [ ] Pet Love: matching retorna apenas pets de sexo oposto e espécie idêntica
- [ ] Pet Love: cálculo de distância (Haversine) e score de compatibilidade
- [ ] Pet Love: manifestação de interesse cria registro e impede duplicidade
- [ ] Validação de coordenadas (lat/lng dentro dos limites do Brasil)
- [ ] Geocoding reverso retorna resultado válido
- [ ] Limite de anúncios por usuário continua funcionando
- [ ] Moderação: anúncio pendente não aparece na listagem pública
- [ ] Alerta de busca dispara corretamente

### 9.2 — Checklist de validação manual

Após implementar cada fase, valide manualmente:

- [ ] **Mobile first:** Todas as páginas funcionam em 375px de largura?
- [ ] **Sem emojis:** Nenhum emoji visível no frontend (buscar com grep)?
- [ ] **Font Awesome carregando:** Todos os ícones renderizam corretamente?
- [ ] **Mapa funciona:** Pin pode ser posicionado, coordenadas são salvas?
- [ ] **Pet Love funciona:** Cadastro de cruzamento, busca de matches e manifestação de interesse funcionam?
- [ ] **Compartilhamento:** Links de WhatsApp e Facebook geram URL correta?
- [ ] **Login admin:** `admin@cademeupet.com.br` / `Admin@123` funciona?
- [ ] **Busca avançada:** Filtros por espécie, cidade, data funcionam?

---

## 📋 FASE 10 — DOCUMENTAÇÃO E COMMIT FINAL

### 10.1 — Atualizar README.md

Reescreva o `README.md` com:

- Nome: **Cadê Meu Pet?**
- Descrição atualizada
- Stack tecnológica completa (incluindo Leaflet.js, Font Awesome 6)
- Instruções de instalação atualizadas (banco `cademeupet`)
- Credenciais padrão atualizadas
- Lista de funcionalidades implementadas (incluindo Pet Love e Localização)
- Variáveis de ambiente necessárias

### 10.2 — Criar CHANGELOG.md

```markdown
# Changelog — Cadê Meu Pet?

## [2.0.0] — (data da implementação)
### Renomeado
- PetFinder → Cadê Meu Pet?

### Adicionado
- Módulo Pet Love (cruzamento/acasalamento de pets com matching por raça, porte, pedigree e localização)
- Localização exata com mapa Leaflet.js
- Mapa geral na home page
- Ícones Font Awesome 6 (remoção total de emojis)
- Sistema de reunião confirmada
- Alertas de busca por área
- Compartilhamento social (WhatsApp, Facebook)
- Painel do usuário com abas
- Metadados Open Graph por anúncio
- Moderação básica de anúncios
- Painel administrativo completo

### Melhorado
- Frontend profissional com identidade visual própria
- Formulário de anúncio em stepper com validação inline
- Cards de anúncio com hover e animações
- Segurança: CSRF tokens, sanitização de saída

### Banco de dados
- Migration 003: tabelas `petlove_pets`, `petlove_fotos`, `petlove_interesses` (módulo de cruzamento)
- Migration 004: colunas de localização na tabela `pets`
- Migration 005: tabela `alertas_busca`
- Migration 006: colunas `status`, `resolvido_em`, `historia_reuniao`, `slug` na tabela `pets`
```

### 10.3 — Commits finais

```bash
git add -A
git commit -m "feat: renomeação completa PetFinder → Cadê Meu Pet? + identidade visual"
git commit -m "feat: substitui emojis por ícones Font Awesome 6 em todo o frontend"
git commit -m "feat: implementa Pet Love (cruzamento de pets com matching por raça/porte/pedigree)"
git commit -m "feat: implementa localização exata com mapa Leaflet.js"
git commit -m "feat: adiciona funcionalidades complementares (alertas, compartilhamento, dashboard)"
git commit -m "feat: painel administrativo completo"
git commit -m "docs: atualiza README e CHANGELOG para v2.0.0"
git push origin main
```

---

## 📊 RELATÓRIO FINAL OBRIGATÓRIO

Ao concluir todas as fases, gere o arquivo `docs/relatorio_implementacao.md` contendo:

1. **Resumo executivo:** o que foi feito em cada fase
2. **Problemas encontrados:** lista de bugs, inconsistências ou pontos de atenção
3. **O que ficou pendente:** itens não implementados e por quê
4. **Próximos passos sugeridos:** melhorias futuras identificadas durante a análise
5. **Tempo estimado por fase:** quanto cada fase levou (aproximado)
6. **Estrutura de arquivos atualizada:** árvore completa do projeto após as alterações

---

## 🔒 VERIFICAÇÕES DE SEGURANÇA PRÉ-DEPLOY

Antes de qualquer deploy em produção, confirme:

- [ ] `config.php` está no `.gitignore`
- [ ] Não há credenciais hardcoded em nenhum arquivo versionado
- [ ] `display_errors` está `Off` para produção
- [ ] Pasta `uploads/` tem permissão de escrita mas não de execução PHP
- [ ] `.htaccess` bloqueia acesso direto a `/database/`, `/tests/`, `/docs/`
- [ ] Rate limiting implementado nas rotas de login e API do Pet Love

---

*Este documento deve ser tratado como especificação técnica imutável durante a execução.*
*Qualquer desvio deve ser documentado e justificado antes de ser aplicado.*

**Boa implementação, Claude Code! 🐾**
