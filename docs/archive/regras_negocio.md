# 📋 Regras de Negócio - PetFinder

## 🔐 RN01 - Cadastro e Autenticação de Usuários

### RN01.1 - Cadastro de Usuário
- **Obrigatório**: nome completo, email válido, telefone, senha
- **Opcional**: foto de perfil, cidade, estado
- Email deve ser único no sistema
- Senha mínima: 8 caracteres (letras, números e símbolos)
- Telefone deve seguir formato brasileiro: (XX) XXXXX-XXXX
- Confirmação de email obrigatória antes de publicar anúncios
- Usuário inativo após 2 anos sem login (pode reativar)

### RN01.2 - Login e Sessão
- Máximo de 3 tentativas de login incorretas
- Após 3 tentativas: bloqueio temporário de 15 minutos
- Após 5 tentativas: CAPTCHA obrigatório
- Sessão expira após 24 horas de inatividade
- Permitido login simultâneo em até 3 dispositivos
- Logout automático ao trocar senha

### RN01.3 - Recuperação de Senha
- Link de recuperação válido por 1 hora
- Email enviado para endereço cadastrado
- Link pode ser usado apenas 1 vez
- Nova senha não pode ser igual às últimas 3

### RN01.4 - Perfil do Usuário
- Usuário pode editar seus dados a qualquer momento
- Alteração de email requer nova confirmação
- Exclusão de conta mantém anúncios por 30 dias (anonimizados)
- Histórico de anúncios visível apenas para o próprio usuário

---

## 📢 RN02 - Publicação de Anúncios

### RN02.1 - Criação de Anúncio
- **Obrigatório confirmar email** antes da primeira publicação
- Limite: **2 fotos por anúncio** (JPEG, PNG, WebP)
- Tamanho máximo: **2MB por foto**
- Resolução mínima: 300x300 pixels
- Dimensões recomendadas: 800x600 pixels
- Fotos impróprias são rejeitadas automaticamente (moderação)

### RN02.2 - Campos Obrigatórios do Anúncio
**Todos os anúncios devem ter:**
- Tipo: "Perdido" ou "Encontrado"
- Espécie: Cachorro, Gato, Ave, Outro
- Tamanho: Pequeno, Médio, Grande
- Data da ocorrência (não pode ser futura)
- Endereço completo
- Bairro e cidade
- Pelo menos 1 forma de contato (telefone, WhatsApp ou email)
- Descrição mínima: 20 caracteres

**Campos opcionais:**
- Nome do pet
- Raça
- Cor
- Ponto de referência
- Recompensa
- 2 fotos

### RN02.3 - Validações de Conteúdo
- Descrição máxima: 1000 caracteres
- Não permitido: palavrões, conteúdo ofensivo, spam
- Proibido: venda de animais, conteúdo comercial
- Telefone e email passam por validação de formato
- Endereço é geocodificado automaticamente (latitude/longitude)

### RN02.4 - Limites de Publicação
- Usuário pode ter no **máximo 10 anúncios ativos** simultaneamente
- Intervalo mínimo entre publicações: **5 minutos**
- Anúncios sem foto têm menor destaque nas buscas
- Anúncios idênticos são bloqueados (anti-spam)

### RN02.5 - Edição de Anúncio
- Apenas o autor pode editar seu anúncio
- Editável: descrição, fotos, contatos, status
- **Não editável**: tipo (perdido/encontrado), data da ocorrência
- Edições geram registro de auditoria
- Foto pode ser substituída, mas mantém limite de 2

### RN02.6 - Status do Anúncio
**Status possíveis:**
- **Ativo**: Visível nas buscas
- **Resolvido**: Pet encontrado/devolvido (mantém visível por 30 dias)
- **Inativo**: Oculto pelo usuário (pode reativar)
- **Bloqueado**: Removido por moderação
- **Expirado**: Sem atualização há mais de 6 meses (auto-inativado)

### RN02.7 - Expiração de Anúncios
- Anúncios sem atualização por **6 meses** são marcados como "Expirados"
- Email de aviso enviado aos **5 meses e 20 dias**
- Usuário pode renovar com 1 clique
- Anúncios expirados ficam ocultos mas não são deletados

### RN02.8 - Exclusão de Anúncio
- Apenas o autor ou admin pode excluir
- Exclusão é **soft delete** (não remove do banco)
- Mantido por **90 dias** antes da exclusão permanente
- Fotos são removidas imediatamente do servidor

---

## 🔍 RN03 - Busca e Filtros

### RN03.1 - Busca Básica
- Busca por palavra-chave em: nome, raça, cor, descrição
- Busca case-insensitive (não diferencia maiúsculas)
- Remove acentos automaticamente (Ação = acao)
- Resultados ordenados por: relevância → data (recentes primeiro)

### RN03.2 - Filtros Disponíveis
**Usuário pode filtrar por:**
- Tipo: Perdido, Encontrado, Todos
- Espécie: Cachorro, Gato, Ave, Outro
- Tamanho: Pequeno, Médio, Grande
- Localização: Estado, Cidade, Bairro
- Data: Hoje, Últimos 7 dias, Último mês, Todos
- Com foto: Sim/Não
- Status: Ativos, Resolvidos, Todos

### RN03.3 - Busca Geográfica
- Usuário pode buscar por **raio de proximidade**
- Raios disponíveis: 5km, 10km, 20km, 50km, Todo estado
- Requer geolocalização do navegador ou endereço informado
- Cálculo baseado em latitude/longitude (fórmula Haversine)
- Anúncios sem coordenadas não aparecem em busca por raio

### RN03.4 - Ordenação dos Resultados
**Opções de ordenação:**
1. Relevância (padrão com busca por palavra-chave)
2. Mais recentes (padrão sem palavra-chave)
3. Mais antigos
4. Mais próximos (apenas com geolocalização)

### RN03.5 - Paginação
- **20 resultados por página**
- Carregamento via scroll infinito (mobile)
- Paginação tradicional (desktop)
- Cache de resultados por 5 minutos

### RN03.6 - Buscas Salvas (Alertas)
- Usuário pode salvar combinação de filtros
- Notificação por email quando novo anúncio corresponde
- Máximo: **5 alertas ativos por usuário**
- Verificação: a cada 1 hora
- Alerta desativado após 90 dias sem correspondência

---

## ⭐ RN04 - Favoritos

### RN04.1 - Salvar Favoritos
- Usuário logado pode favoritar qualquer anúncio
- Sem limite de favoritos
- Acesso rápido via "Meus Favoritos"
- Notificação se anúncio favorito for marcado como resolvido

### RN04.2 - Remoção de Favoritos
- Usuário pode desfavoritar a qualquer momento
- Anúncios deletados são removidos automaticamente dos favoritos
- Favoritos não são públicos (privacidade)

---

## 📧 RN05 - Notificações

### RN05.1 - Notificações por Email
**Emails automáticos enviados quando:**
- Novo usuário: email de boas-vindas + confirmação
- Anúncio publicado: confirmação de publicação
- Anúncio prestes a expirar (5 meses e 20 dias)
- Novo anúncio correspondente a alerta salvo
- Anúncio favorito marcado como resolvido
- Mensagem recebida (se implementar chat)

### RN05.2 - Preferências de Notificação
- Usuário pode desativar notificações no perfil
- Emails importantes (confirmação, segurança) não podem ser desativados
- Frequência máxima: 1 email por alerta/dia (evitar spam)

### RN05.3 - Anti-Spam
- Máximo de **5 emails por dia** por usuário (exceto críticos)
- Link de "Descadastrar" em todos os emails promocionais
- Reclamação de spam = suspensão automática de notificações

---

## 💰 RN06 - Sistema de Doações

### RN06.1 - Valores de Doação
- Valor mínimo: **R$ 2,00**
- Sem valor máximo
- Valores sugeridos: R$ 5, 10, 20, 50
- Usuário pode digitar valor personalizado
- Aceita apenas números inteiros (sem centavos para PIX)

### RN06.2 - Métodos de Pagamento
**Aceitos:**
- PIX (instantâneo)
- Cartão de crédito (aprovação em minutos)
- Cartão de débito
- Boleto (confirmação em até 3 dias úteis)

**Taxas** (cobradas pelo gateway):
- PIX: 4,99%
- Cartão: 4,99% + R$ 0,40
- Boleto: 4,99%

### RN06.3 - Doações Recorrentes
- Usuário pode optar por doação mensal
- Cobrança automática todo dia 5 do mês
- Pode cancelar a qualquer momento
- Notificação 3 dias antes da cobrança
- Falha no pagamento: 2 tentativas antes de cancelar

### RN06.4 - Doações Anônimas
- Usuário pode doar sem fazer login
- Pode escolher se aparece no mural de doadores
- Se não quiser aparecer: doação 100% anônima
- Anônimos também recebem comprovante por email

### RN06.5 - Benefícios de Apoiadores
**Doadores ganham:**
- Badge "💚 Apoiador" no perfil (opcional)
- Aparição no mural de agradecimentos (opcional)
- Comprovante para declaração de IR
- Sensação de estar ajudando a causa 😊

**Apoiadores NÃO ganham:**
- Recursos pagos (site continua 100% gratuito)
- Prioridade em anúncios
- Remoção de qualquer limitação

### RN06.6 - Transparência Financeira
- Relatório mensal público de receitas/despesas
- Barra de progresso da meta mensal
- Histórico de todas as doações (valores, não nomes)
- Prestação de contas detalhada

### RN06.7 - Reembolsos
- Possível em até 7 dias após doação
- Solicitação via email de suporte
- Processado em até 10 dias úteis
- Doações recorrentes: reembolso apenas da última

### RN06.8 - Certificado de Doação
- Gerado automaticamente para valores acima de R$ 10
- Válido para declaração de IR (Lei 9.249/95)
- Download em PDF
- Contém: CPF/CNPJ doador, valor, data, recibo

---

## 🛡️ RN07 - Moderação e Segurança

### RN07.1 - Moderação de Conteúdo
**Anúncios são analisados automaticamente por:**
- Filtro de palavras ofensivas
- Detecção de conteúdo comercial/spam
- Validação de fotos (sem nudez, violência)

**Anúncios suspeitos:**
- Vão para fila de moderação manual
- Admin tem 24h para aprovar/rejeitar
- Usuário é notificado da decisão

### RN07.2 - Denúncias
- Qualquer usuário pode denunciar anúncio
- Motivos: Conteúdo inapropriado, Spam, Venda de animais, Golpe
- 3 denúncias = anúncio suspenso automaticamente
- Admin revisa em até 48h

### RN07.3 - Banimento de Usuário
**Usuário pode ser banido por:**
- Publicar conteúdo ofensivo repetidamente
- Venda de animais
- Spam (mais de 5 anúncios idênticos)
- Golpes ou fraudes
- Uso de múltiplas contas (fake)

**Banimento:**
- Temporário: 7, 30 ou 90 dias
- Permanente: casos graves
- Todos os anúncios são removidos
- Pode recorrer via email de suporte

### RN07.4 - Proteção de Dados (LGPD)
- Dados pessoais criptografados no banco
- Senhas com hash bcrypt (custo 12)
- Dados não são vendidos ou compartilhados
- Usuário pode solicitar exclusão total (direito ao esquecimento)
- Logs de acesso mantidos por 90 dias

### RN07.5 - Prevenção de Fraudes
- IP bloqueado após 10 cadastros/hora
- Limite de 5 anúncios/hora por IP
- CAPTCHA após 3 tentativas de login
- Verificação de email obrigatória
- Moderação de contas com padrões suspeitos

---

## 📊 RN08 - Estatísticas e Métricas

### RN08.1 - Contador de Visualizações
- Cada anúncio tem contador de visualizações
- +1 view a cada acesso único (IP) por dia
- Não conta views do próprio autor
- Exibido publicamente no anúncio

### RN08.2 - Taxa de Sucesso
- Sistema calcula % de pets reunidos
- Baseado em anúncios marcados como "Resolvido"
- Exibido na home: "Já reunimos X pets com suas famílias"
- Atualizado a cada 24h

### RN08.3 - Relatórios Admin
**Dashboard mostra:**
- Total de usuários (ativos/inativos)
- Total de anúncios (por tipo, status)
- Anúncios publicados hoje/semana/mês
- Taxa de conversão (anúncios → resolvidos)
- Principais cidades/estados
- Doações do mês (valor total, quantidade)
- Gráficos de evolução

---

## 🌍 RN09 - Geolocalização

### RN09.1 - Geocodificação de Endereços
- Todo anúncio tem endereço convertido em lat/lng
- Usa API Google Maps Geocoding (ou alternativa)
- Se falhar: anúncio publicado sem coordenadas (sem busca por raio)
- Coordenadas armazenadas com 8 casas decimais

### RN09.2 - Mapa Interativo
- Exibe marcadores de anúncios próximos
- Cores diferentes: vermelho (perdido), verde (encontrado)
- Clique no marcador: preview do anúncio
- Zoom automático para ajustar todos os marcadores
- Limite: 100 marcadores por vez (performance)

### RN09.3 - Privacidade de Localização
- Coordenadas exatas não são exibidas publicamente
- Apenas "aproximadamente em [Bairro]"
- Mapa mostra área aproximada (raio de 500m)
- Endereço completo apenas para autor do anúncio

---

## 🔔 RN10 - Modal de Doação

### RN10.1 - Quando Exibir o Modal
**Modal aparece:**
1. Quando usuário marca anúncio como "Resolvido" (sucesso!)
2. Após 5 minutos de navegação (primeira vez)
3. Ao salvar 3º anúncio nos favoritos
4. Uma vez por semana para usuários recorrentes
5. **NUNCA no primeiro acesso** (evita friction)

**Modal NÃO aparece:**
- Se usuário já doou nos últimos 30 dias
- Se usuário fechou modal há menos de 7 dias
- Se usuário clicou "Não mostrar novamente"

### RN10.2 - Comportamento do Modal
- Botão "X" para fechar (topo direito)
- Botão "Talvez depois" (não bloqueia experiência)
- Botão "Não mostrar novamente" (após 3ª exibição)
- Modal não pode ser exibido mais de 1x por sessão
- Overlay escuro semi-transparente (não bloqueia 100%)

---

## 📱 RN11 - Responsividade e Acessibilidade

### RN11.1 - Dispositivos Suportados
- Desktop: 1920px, 1366px, 1024px
- Tablet: 768px (portrait e landscape)
- Mobile: 375px, 414px, 390px (iPhone, Android)
- Funciona até 320px (mobile pequeno)

### RN11.2 - Performance Mobile
- Imagens responsivas (srcset)
- Lazy loading de imagens
- Menu hamburger em telas < 768px
- Botões grandes (mínimo 44x44px) para toque
- Formulários otimizados (input types corretos)

### RN11.3 - Acessibilidade (WCAG 2.1 Nível AA)
- Contraste mínimo 4.5:1 para textos
- Navegação por teclado (Tab, Enter, Esc)
- Alt text em todas as imagens
- Labels em todos os campos de formulário
- ARIA labels onde necessário
- Foco visível em elementos interativos

---

## ⚡ RN12 - Performance e Escalabilidade

### RN12.1 - Cache
- Página inicial: cache de 5 minutos
- Resultados de busca: cache de 10 minutos
- Perfil de usuário: sem cache (dados dinâmicos)
- Imagens: cache de 1 ano (browser)

### RN12.2 - Otimização de Consultas
- Índices em colunas de busca frequente
- Limit 20 em queries (paginação)
- Prepared statements (evita SQL injection + performance)
- Connection pooling (reutiliza conexões)

### RN12.3 - Imagens
- Redimensionamento automático:
  - Thumbnail: 300x300px
  - Medium: 800x600px
  - Original: mantido (backup)
- Compressão com qualidade 85%
- Formato WebP quando suportado
- Lazy loading nas listagens

### RN12.4 - Limites de Sistema
- Máximo de 1000 requisições/minuto por IP
- Máximo de 100 anúncios carregados por busca
- Timeout de queries: 5 segundos
- Tamanho máximo de upload: 2MB por foto
- Sessões expiram após 24h de inatividade

---

## 🚨 RN13 - Tratamento de Erros

### RN13.1 - Mensagens de Erro
- Erros exibidos de forma clara e amigável
- Sem termos técnicos para usuário final
- Sugestões de solução quando possível
- Erros críticos: email automático para admin

### RN13.2 - Erros Comuns
```
❌ "Email já cadastrado" → Sugestão: "Tente fazer login"
❌ "Formato de imagem inválido" → Aceitos: JPEG, PNG, WebP
❌ "Arquivo muito grande" → Máximo: 2MB por foto
❌ "Endereço não encontrado" → Verifique CEP ou digite manualmente
❌ "Sessão expirada" → Faça login novamente
```

### RN13.3 - Logs de Erro
- Todos os erros são registrados (error_log)
- Erros 500: notificação imediata para admin
- Logs mantidos por 30 dias
- Informações sensíveis não são logadas

---

## 🎯 RN14 - Regras de Validação Resumidas

| Campo | Validação |
|-------|-----------|
| **Nome** | 3-100 caracteres, apenas letras e espaços |
| **Email** | Formato válido, único no sistema |
| **Telefone** | (XX) XXXXX-XXXX, apenas números |
| **Senha** | Mínimo 8 caracteres, letras + números |
| **CPF** | Validação de dígitos verificadores (doações) |
| **Endereço** | Mínimo 10 caracteres |
| **CEP** | XXXXX-XXX, validado via ViaCEP |
| **Descrição** | 20-1000 caracteres |
| **Valor Doação** | Mínimo R$ 2,00, apenas números inteiros |
| **Data Ocorrido** | Não pode ser futura, máximo 3 anos atrás |

---

## 🔄 RN15 - Atualizações e Manutenção

### RN15.1 - Backup
- Backup diário automático do banco (3h da manhã)
- Backup de arquivos (fotos) semanal
- Retenção: últimos 30 dias
- Backup armazenado em local externo (S3, Dropbox)

### RN15.2 - Manutenção Programada
- Notificação com 48h de antecedência
- Preferencialmente madrugada (1h-5h)
- Página de manutenção amigável
- Tempo máximo: 2 horas

### RN15.3 - Atualizações de Código
- Deploy em ambiente de staging primeiro
- Testes automatizados antes de produção
- Rollback disponível em caso de problemas
- Changelog público para usuários

---

## ✅ Resumo das Principais Regras

1. ✅ **2 fotos máximo** por anúncio (2MB cada)
2. ✅ **10 anúncios ativos** simultâneos por usuário
3. ✅ **6 meses** sem atualização = anúncio expira
4. ✅ **Email confirmado** obrigatório para publicar
5. ✅ **Doação mínima**: R$ 2,00
6. ✅ **3 tentativas** de login antes de bloquear
7. ✅ **20 resultados** por página de busca
8. ✅ **5 alertas** salvos por usuário
9. ✅ **5 minutos** de intervalo entre publicações
10. ✅ **100% gratuito** - nenhum recurso pago

---

**Documento de Regras de Negócio v1.0**  
*Atualizado em: Dezembro 2025*  
*Próxima revisão: Trimestral*