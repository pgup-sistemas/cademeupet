# PetFinder - Roadmap de Desenvolvimento

## ✅ Concluído

- [x] Estrutura MVC com controllers, models e views
- [x] Sistema de autenticação (login, cadastro, recuperação de senha)
- [x] Publicação e busca de anúncios de pets perdidos/encontrados
- [x] Upload de fotos com cache temporário em multi-step
- [x] Sistema de favoritos e alertas
- [x] Layout responsivo com Bootstrap
- [x] URLs amigáveis (.htaccess)
- [x] Integração PHPMailer para envio de e-mails
- [x] Páginas: perfil, meus anúncios, favoritos, busca
- [x] Validação CSRF e sanitização de inputs
- [x] Banco de dados com schema e dados iniciais

## Em Andamento

- [ ] Testar publicação de anúncio com fotos (cache temporário)
- [ ] Testar fluxos de e-mail ponta a ponta (cadastro, recuperação)

## Próximas Implementações

### 1. Geolocalização e Mapas (Prioridade: Média)
- [x] Integrar Google Maps API ou OpenStreetMap (Leaflet)
- [x] Preencher coordenadas (lat/lng) ao criar anúncio
- [x] Geolocalização automática por CEP
- [ ] Geolocalização automática por IP
- [x] Mapa interativo na busca e detalhes do anúncio
- [x] Busca por raio com visualização no mapa
- [ ] Input de endereço com autocomplete

### 2. Sistema de Doações - Efí Bank (Prioridade: Média)
- [x] Criar `controllers/PagamentoController.php`
- [x] Model `Doacao` com status e webhook
- [x] View de doação com valores sugeridos
- [x] Integração Efí Bank (PIX)
- [x] Webhook para atualizar status após pagamento
- [x] Relatório de doações para admin
- [ ] Integração Efí Bank (cartão de crédito)
- [ ] Página de agradecimento e comprovante

### 3. Melhorias na Busca (Prioridade: Baixa)
- [ ] Busca com sugestões automáticas (AJAX)
- [ ] Filtros avançados (idade, porte, cor, data)
- [ ] Ordenação por relevância/distância/data
- [ ] Paginação infinita ou tradicional
- [ ] Resultados em modo lista/mapa

### 4. Funcionalidades Extras (Prioridade: Baixa)
- [ ] Sistema de avaliação/confiabilidade entre usuários
- [ ] Chat interno entre dono de pet e quem encontrou
- [ ] Relatórios e estatísticas para admin
- [ ] Exportar/busca em CSV/PDF
- [ ] API REST para integração com apps
- [ ] PWA (Progressive Web App)
- [ ] Notificações push para novos pets próximos

### 5. Infraestrutura (Prioridade: Baixa)
- [ ] Cache (Redis/OPcache)
- [ ] Fila de e-mails (Redis/Beanstalk)
- [ ] Logs centralizados
- [ ] Monitoramento e health checks
- [ ] Deploy automatizado (CI/CD)

## Bugs Conhecidos

- [ ] Validação de upload em multi-step pode avisar sobre arquivos temporários ausentes (já mitigado)
- [ ] Em dispositivos móveis, alguns botões podem precisar de ajuste de toque

## Sugestões de Melhoria

- Adicionar micro-interações e animações sutis
- Implementar dark mode
- Otimizar imagens com WebP
- Adicionar testes automatizados (PHPUnit)
- Melhorar SEO com metatags dinâmicas

## Roadmap de Deploy (Produção) - cPanel/HostGator

**Destino:** `public_html/petfinder` (SSL ativo)

### 1) Preparação (antes de subir)

- [ ] Definir `APP_ENV=production`
- [ ] Definir `BASE_URL=https://SEU-DOMINIO/petfinder`
- [ ] Configurar credenciais de banco (`DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`)
- [ ] Configurar SMTP (`SMTP_HOST`, `SMTP_PORT`, `SMTP_USER`, `SMTP_PASS`, `EMAIL_FROM`)
- [ ] Configurar Efí (`EFI_CLIENT_ID`, `EFI_CLIENT_SECRET`, `EFI_CERTIFICATE_PATH`, `EFI_CERTIFICATE_PASSWORD`, `EFI_PIX_KEY`, `EFI_WEBHOOK_TOKEN`)
- [ ] Garantir que **não** será usado admin padrão em produção (senha padrão / inserção automática)
- [ ] Remover/limitar logs verbosos com dados pessoais (ex.: `print_r($data)` em produção)

### 2) Apache / `.htaccess`

- [ ] Confirmar `RewriteBase /petfinder/`
- [ ] Ativar redirecionamento para HTTPS (produção)
- [ ] Proteger `uploads/` contra execução e listagem de diretório

### 3) Deploy do código

- [ ] Subir o projeto para `public_html/petfinder`
- [ ] Garantir que `vendor/` exista no servidor
  - [ ] Opção A: rodar `composer install --no-dev` no servidor
  - [ ] Opção B: enviar `vendor/` junto no upload
- [ ] Ajustar permissões
  - [ ] Pastas: `755`
  - [ ] Arquivos: `644`
  - [ ] `uploads/` gravável pelo PHP

### 4) Banco de dados

- [ ] Criar banco e usuário no cPanel
- [ ] Importar `database/schema.sql` (evitar dados padrão de admin em produção)
- [ ] Validar conexão (página inicial + login)

### 5) Cron (tarefas)

- [ ] Configurar Cron Job para `scripts/process_alerts.php`
- [ ] Validar execução e logs

### 6) Checklist pós-deploy (validação)

- [ ] Cadastro + confirmação de email
- [ ] Login/logout + bloqueio por tentativas
- [ ] Recuperação de senha
- [ ] Criar/editar/excluir anúncio (upload de fotos)
- [ ] Favoritos/alertas
- [ ] Área admin (acesso restrito)
- [ ] Doações (Pix/cartão) e webhooks Efí
- [ ] Verificar logs de erro no cPanel após navegação

---

**Nota**: Este roadmap é um guia vivo e pode ser priorizado conforme demanda dos usuários e recursos disponíveis.
