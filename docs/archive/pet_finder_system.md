# 🐾 PetFinder - Documentação Técnica Completa

## 📋 Índice
1. [Visão Geral](#visao-geral)
2. [Requisitos do Sistema](#requisitos)
3. [Arquitetura](#arquitetura)
4. [Funcionalidades](#funcionalidades)
5. [Estrutura do Banco de Dados](#banco-dados)
6. [Estrutura de Arquivos](#estrutura-arquivos)
7. [Segurança](#seguranca)
8. [Otimizações de Performance](#performance)
9. [API e Integrações](#api)
10. [Instalação](#instalacao)

---

## 🎯 Visão Geral

O **PetFinder** é uma plataforma web responsiva desenvolvida em PHP para conectar pessoas que perderam ou encontraram animais de estimação. O sistema foi projetado para suportar alto volume de acessos simultâneos e ser escalável.

### Objetivos do Projeto
- Facilitar a reunião de pets perdidos com seus donos
- Interface intuitiva e responsiva para todos os dispositivos
- Sistema robusto preparado para tráfego intenso
- Geolocalização para buscas por proximidade
- Notificações em tempo real

---

## ⚙️ Requisitos do Sistema

### Servidor
- **PHP**: 8.0 ou superior
- **MySQL**: 8.0 ou superior (ou MariaDB 10.5+)
- **Apache/Nginx**: com mod_rewrite habilitado
- **SSL/TLS**: Certificado válido (Let's Encrypt)
- **Memória**: Mínimo 2GB RAM (recomendado 4GB+)
- **Armazenamento**: SSD com mínimo 20GB

### Extensões PHP Necessárias
```
- pdo_mysql
- gd ou imagick (manipulação de imagens)
- mbstring
- json
- session
- curl
- fileinfo
- zip
```

### Recomendações de Servidor
- **Compartilhado**: Hostinger, HostGator (plano Business+)
- **VPS**: DigitalOcean, Linode, AWS EC2
- **Otimização**: Redis/Memcached para cache
- **CDN**: Cloudflare para assets estáticos

---

## 🏗️ Arquitetura

### Padrão MVC (Model-View-Controller)
```
┌─────────────────────────────────────────┐
│           CAMADA DE APRESENTAÇÃO         │
│  (Views - HTML/CSS/JS Responsivo)       │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│         CAMADA DE CONTROLE               │
│  (Controllers - Lógica de Aplicação)    │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│          CAMADA DE MODELO                │
│  (Models - Acesso a Dados)              │
└──────────────┬──────────────────────────┘
               │
┌──────────────▼──────────────────────────┐
│        BANCO DE DADOS MySQL              │
└──────────────────────────────────────────┘
```

### Componentes Principais
1. **Frontend**: HTML5, CSS3 (Bootstrap 5), JavaScript (vanilla + jQuery)
2. **Backend**: PHP 8+ com PDO
3. **Banco de Dados**: MySQL com índices otimizados
4. **Cache**: Sistema de cache de consultas frequentes
5. **Upload**: Sistema de upload com validação e redimensionamento

---

## 🎨 Funcionalidades

### 1. Sistema de Usuários
- ✅ Cadastro com validação de email
- ✅ Login/Logout seguro (sessões com token)
- ✅ Recuperação de senha via email
- ✅ Perfil editável com foto
- ✅ Histórico de anúncios publicados
- ✅ Painel de controle pessoal

### 2. Gestão de Anúncios
- ✅ Publicar anúncio (Perdido ou Encontrado)
- ✅ Upload de até 2 fotos por anúncio
- ✅ Formulário completo:
  - Nome do pet
  - Espécie (cachorro, gato, ave, outro)
  - Raça e cor
  - Tamanho (pequeno, médio, grande)
  - Descrição detalhada
  - Data da ocorrência
  - Endereço completo
  - Bairro e cidade
  - Ponto de referência
  - Contatos (telefone, WhatsApp, email)
  - Recompensa (opcional)
- ✅ Editar/Excluir próprios anúncios
- ✅ Marcar como "Encontrado/Resolvido"

### 3. Busca e Filtros
- ✅ Busca por palavra-chave
- ✅ Filtro por tipo (perdido/encontrado)
- ✅ Filtro por espécie
- ✅ Filtro por localização (cidade/bairro)
- ✅ Filtro por data
- ✅ Ordenação (recentes, antigos, proximidade)
- ✅ Busca com geolocalização (raio em km)

### 4. Recursos Avançados
- ✅ Mapa interativo com marcadores
- ✅ Sistema de favoritos
- ✅ Compartilhamento em redes sociais
- ✅ Notificações por email (novos anúncios na área)
- ✅ Estatísticas (taxa de sucesso, pets reunidos)
- ✅ Modo escuro/claro
- ✅ PWA (Progressive Web App)

### 5. Sistema de Doações (Monetização)
- ✅ **Botão de doação visível** em todas as páginas
- ✅ **Modal de doação** com valores sugeridos
- ✅ Integração com gateways de pagamento:
  - Mercado Pago (PIX, cartão, boleto)
  - PagSeguro
  - PayPal (internacional)
  - PIX direto (QR Code)
- ✅ **Doações recorrentes** (mensais)
- ✅ **Badge de apoiador** no perfil
- ✅ **Mural de doadores** (com permissão)
- ✅ **Transparência financeira** (custos mensais)
- ✅ **Histórico de doações** do usuário
- ✅ **Certificado de doação** (para declaração IR)
- ✅ **Metas de arrecadação** visíveis
- ✅ **Histórias de sucesso** para engajamento

### 6. Administração
- ✅ Painel administrativo
- ✅ Moderação de anúncios
- ✅ Gestão de usuários
- ✅ **Dashboard de doações**
- ✅ Estatísticas e relatórios
- ✅ Backup automático

---

## 🗄️ Estrutura do Banco de Dados

### Tabela: `usuarios`
```sql
id (PK)              INT AUTO_INCREMENT
nome                 VARCHAR(100)
email                VARCHAR(100) UNIQUE
telefone             VARCHAR(20)
senha                VARCHAR(255) - hash bcrypt
foto_perfil          VARCHAR(255)
cidade               VARCHAR(100)
estado               VARCHAR(2)
notificacoes_email   BOOLEAN
data_cadastro        TIMESTAMP
ultimo_acesso        TIMESTAMP
ativo                BOOLEAN
INDEX: email, cidade
```

### Tabela: `anuncios`
```sql
id (PK)              INT AUTO_INCREMENT
usuario_id (FK)      INT
tipo                 ENUM('perdido', 'encontrado')
nome_pet             VARCHAR(100)
especie              ENUM('cachorro', 'gato', 'ave', 'outro')
raca                 VARCHAR(100)
cor                  VARCHAR(100)
tamanho              ENUM('pequeno', 'medio', 'grande')
descricao            TEXT
data_ocorrido        DATE
endereco_completo    VARCHAR(255)
bairro               VARCHAR(100)
cidade               VARCHAR(100)
estado               VARCHAR(2)
ponto_referencia     VARCHAR(255)
latitude             DECIMAL(10, 8)
longitude            DECIMAL(11, 8)
telefone_contato     VARCHAR(20)
whatsapp             VARCHAR(20)
email_contato        VARCHAR(100)
recompensa           VARCHAR(100)
status               ENUM('ativo', 'resolvido', 'inativo')
visualizacoes        INT DEFAULT 0
data_publicacao      TIMESTAMP
data_atualizacao     TIMESTAMP
INDEX: tipo, especie, cidade, status, data_publicacao
INDEX: latitude, longitude (para buscas geográficas)
```

### Tabela: `fotos_anuncios`
```sql
id (PK)              INT AUTO_INCREMENT
anuncio_id (FK)      INT
nome_arquivo         VARCHAR(255)
ordem                TINYINT
data_upload          TIMESTAMP
INDEX: anuncio_id
```

### Tabela: `doacoes`
```sql
id (PK)              INT AUTO_INCREMENT
usuario_id (FK)      INT (NULL para doações anônimas)
valor                DECIMAL(10, 2)
tipo                 ENUM('unica', 'mensal')
metodo_pagamento     VARCHAR(50)
gateway              VARCHAR(50)
transaction_id       VARCHAR(255)
status               ENUM('pendente', 'aprovada', 'cancelada')
nome_doador          VARCHAR(100) (para anônimos)
mensagem             TEXT
exibir_mural         BOOLEAN
data_doacao          TIMESTAMP
proxima_cobranca     DATE (para recorrentes)
INDEX: usuario_id, status, data_doacao
```

### Tabela: `metas_financeiras`
```sql
id (PK)              INT AUTO_INCREMENT
mes_referencia       DATE
valor_meta           DECIMAL(10, 2)
valor_arrecadado     DECIMAL(10, 2)
custos_servidor      DECIMAL(10, 2)
custos_manutencao    DECIMAL(10, 2)
descricao            TEXT
ativo                BOOLEAN
data_criacao         TIMESTAMP
```

### Tabela: `favoritos`
```sql
id (PK)              INT AUTO_INCREMENT
usuario_id (FK)      INT
anuncio_id (FK)      INT
data_favoritado      TIMESTAMP
UNIQUE: usuario_id + anuncio_id
INDEX: usuario_id, anuncio_id
```

### Tabela: `alertas`
```sql
id (PK)              INT AUTO_INCREMENT
usuario_id (FK)      INT
especie              VARCHAR(50)
cidade               VARCHAR(100)
raio_km              INT
ativo                BOOLEAN
data_criacao         TIMESTAMP
INDEX: usuario_id, ativo
```

---

## 📁 Estrutura de Arquivos

```
pet-finder/
│
├── index.php                    # Ponto de entrada
├── config.php                   # Configurações globais
├── .htaccess                    # Rewrite rules
│
├── assets/
│   ├── css/
│   │   ├── style.css           # Estilos principais
│   │   └── responsive.css      # Media queries
│   ├── js/
│   │   ├── main.js             # JavaScript principal
│   │   ├── map.js              # Integração com mapas
│   │   └── upload.js           # Preview de imagens
│   ├── img/
│   │   └── logo.png
│   └── fonts/
│
├── uploads/
│   ├── perfil/                 # Fotos de perfil
│   ├── anuncios/               # Fotos de anúncios
│   └── .htaccess               # Proteção de diretório
│
├── includes/
│   ├── db.php                  # Conexão com banco
│   ├── functions.php           # Funções auxiliares
│   ├── auth.php                # Autenticação
│   └── header.php              # Header global
│   └── footer.php              # Footer global
│
├── controllers/
│   ├── UsuarioController.php
│   ├── AnuncioController.php
│   ├── BuscaController.php
│   └── AdminController.php
│
├── models/
│   ├── Usuario.php
│   ├── Anuncio.php
│   ├── Foto.php
│   └── Alerta.php
│
├── views/
│   ├── home.php                # Página inicial
│   ├── cadastro.php
│   ├── login.php
│   ├── perfil.php
│   ├── novo-anuncio.php
│   ├── editar-anuncio.php
│   ├── anuncio-detalhes.php
│   ├── busca.php
│   ├── meus-anuncios.php
│   ├── favoritos.php
│   ├── doar.php                # Página de doações
│   ├── transparencia.php       # Relatório financeiro
│   ├── apoiadores.php          # Mural de doadores
│   └── admin/
│       ├── dashboard.php
│       ├── moderacao.php
│       └── doacoes.php         # Gestão de doações
│
└── api/
    ├── geocode.php             # API de geolocalização
    ├── notificacoes.php        # Sistema de alertas
    └── webhook-payment.php     # Webhook pagamentos
```

---

## 🔒 Segurança

### Medidas Implementadas

1. **Senhas**
   - Hash com `password_hash()` (bcrypt)
   - Mínimo 8 caracteres
   - Validação de força

2. **SQL Injection**
   - Prepared Statements (PDO)
   - Validação de entrada
   - Sanitização de dados

3. **XSS (Cross-Site Scripting)**
   - `htmlspecialchars()` em todas as saídas
   - Content Security Policy headers

4. **CSRF (Cross-Site Request Forgery)**
   - Tokens únicos por formulário
   - Validação no servidor

5. **Upload de Arquivos**
   - Validação de tipo MIME
   - Limite de tamanho (2MB por foto)
   - Renomeação com hash único
   - Armazenamento fora da raiz web

6. **Sessões**
   - Session hijacking prevention
   - Regeneração de ID de sessão
   - Timeout configurável

7. **Rate Limiting**
   - Limite de tentativas de login
   - Proteção contra brute force
   - Captcha após 3 tentativas

---

## ⚡ Otimizações de Performance

### Banco de Dados
- Índices em colunas de busca frequente
- Query optimization (EXPLAIN)
- Connection pooling
- Prepared statements cache

### Cache
```php
// Sistema de cache de consultas
- Cache de página (5 minutos)
- Cache de resultados de busca (10 minutos)
- Cache de contadores (1 hora)
- Invalidação inteligente
```

### Imagens
- Redimensionamento automático (thumb 300x300, medium 800x600)
- Compressão com qualidade 85%
- Lazy loading
- WebP quando suportado
- Sprites para ícones

### Frontend
- Minificação de CSS/JS
- Combine de arquivos
- Gzip compression
- Browser caching (expires headers)
- CDN para bibliotecas (Bootstrap, jQuery)

### Servidor
```apache
# .htaccess otimizado
- Gzip compression
- Browser caching
- ETags
- Keep-Alive
```

---

## 🔌 API e Integrações

### APIs de Pagamento
1. **Mercado Pago** (Recomendado para Brasil)
   - PIX instantâneo
   - Cartão de crédito/débito
   - Boleto bancário
   - Assinaturas recorrentes
   - Taxa: 4,99% + R$ 0,40

2. **PagSeguro**
   - Múltiplos métodos
   - PIX
   - Taxa: 4,99%

3. **PayPal**
   - Doações internacionais
   - Taxa: 4,4% + US$ 0,30

4. **PIX Direto**
   - QR Code estático
   - 0% de taxa
   - Confirmação manual

### APIs Externas
1. **Google Maps API**
   - Geocodificação de endereços
   - Mapa interativo com marcadores
   - Cálculo de distância

2. **ViaCEP**
   - Preenchimento automático de endereço

3. **WhatsApp API**
   - Botão de contato direto

### Endpoints Internos (REST)
```
GET  /api/anuncios.php?cidade=porto-velho&tipo=perdido
POST /api/anuncios.php (criar anúncio)
PUT  /api/anuncios.php?id=123 (atualizar)
GET  /api/busca.php?q=labrador&lat=-8.76&lng=-63.90&raio=10
```

---

## 📦 Instalação

### Passo 1: Requisitos
Verifique se o servidor atende aos requisitos mínimos.

### Passo 2: Download
```bash
git clone https://github.com/seu-usuario/pet-finder.git
cd pet-finder
```

### Passo 3: Configuração
1. Copie `config.sample.php` para `config.php`
2. Configure as credenciais do banco de dados
3. Configure chaves de API (Google Maps)

### Passo 4: Banco de Dados
```bash
mysql -u root -p < database/schema.sql
mysql -u root -p < database/initial_data.sql
```

### Passo 5: Permissões
```bash
chmod 755 uploads/
chmod 755 uploads/perfil/
chmod 755 uploads/anuncios/
```

### Passo 6: Virtual Host (Apache)
```apache
<VirtualHost *:80>
    ServerName petfinder.local
    DocumentRoot /var/www/pet-finder
    
    <Directory /var/www/pet-finder>
        AllowOverride All
        Require all granted
    </Directory>
</VirtualHost>
```

### Passo 7: Acesso
- URL: http://localhost/pet-finder
- Admin padrão: admin@petfinder.com / Admin@123

---

## 📊 Métricas e Monitoramento

### KPIs Importantes
- Taxa de reunião (pets devolvidos aos donos)
- Tempo médio até resolução
- Usuários ativos mensais
- Anúncios publicados por dia
- Taxa de conversão (visualizações → contatos)
- **Taxa de conversão de doações**
- **Ticket médio de doação**
- **Doadores recorrentes vs únicos**
- **Custo por pet reunido**

### Transparência Financeira (Público)
```
Custos Mensais Estimados:
- Servidor VPS: R$ 80-150/mês
- Domínio: R$ 40/ano
- SSL: Grátis (Let's Encrypt)
- Email (SendGrid): R$ 0-50/mês
- Backup: R$ 30/mês
- APIs (Google Maps): R$ 0-100/mês
Total: R$ 150-330/mês

Meta Mensal: R$ 500
- 70% Infraestrutura
- 20% Melhorias
- 10% Reserva emergência
```

### Ferramentas Recomendadas
- Google Analytics
- Hotjar (heatmaps)
- Sentry (error tracking)
- New Relic (performance)

---

## 🚀 Roadmap Futuro

### Fase 2
- [ ] App mobile (React Native)
- [ ] Reconhecimento de imagem (TensorFlow)
- [ ] Chat interno entre usuários
- [ ] Sistema de pontos e gamificação
- [ ] **Programa de embaixadores**
- [ ] **Campanhas de doação temáticas**

### Fase 3
- [ ] IA para matching automático (pet perdido × encontrado)
- [ ] Integração com clínicas veterinárias
- [ ] Microchip database
- [ ] Parcerias com ONGs
- [ ] **Crowdfunding para casos especiais**

---

## 💰 Estratégias de Sustentabilidade

### Modelo de Doações
O PetFinder é 100% gratuito e sem anúncios, mantido por doações da comunidade.

### Técnicas de Engajamento
1. **Prova Social**
   - Contador de doadores
   - Últimas doações (com permissão)
   - Histórias de sucesso

2. **Urgência e Escassez**
   - Barra de progresso da meta mensal
   - "Faltam R$ 200 para nossa meta"
   - Contador regressivo de campanhas

3. **Reciprocidade**
   - Badge especial para apoiadores
   - Menção no mural de agradecimentos
   - Acesso antecipado a novos recursos

4. **Transparência Total**
   - Relatório mensal público
   - Custos detalhados
   - Prestação de contas

5. **Facilidade**
   - PIX em 1 clique
   - Valores sugeridos (R$ 5, 10, 20, 50)
   - "Doar o equivalente a 1 café"

### Valores Sugeridos
```
☕ R$ 5,00  - Café da Causa
🍕 R$ 10,00 - Pizza Solidária
🎬 R$ 20,00 - Cinema do Bem
🛒 R$ 50,00 - Feira do Mês
💚 Outro valor
```

### Gatilhos Psicológicos
- "Seu café de hoje pode reunir um pet com sua família"
- "99% dos usuários ainda não doaram. Seja diferente!"
- "João doou R$ 10 há 2 minutos e ajudou a manter o site no ar"
- "Com R$ 5/mês você garante 1 ano de servidor"

---

## 📈 Posicionamento no Modal de Doação

### Timing Ideal para Mostrar Modal
1. Após sucesso em anúncio (pet encontrado)
2. Após 5 minutos de navegação
3. Ao salvar um anúncio nos favoritos
4. Uma vez por semana para usuários recorrentes
5. Nunca no primeiro acesso (evitar friction)

### Estrutura do Modal
```
┌─────────────────────────────────────────┐
│  ❤️ Ajude a Manter o PetFinder Gratuito │
│                                         │
│  Reunimos 1.247 pets com suas famílias │
│  graças ao apoio de pessoas como você! │
│                                         │
│  [Barra de Progresso] 68% da meta     │
│  R$ 340 de R$ 500 este mês             │
│                                         │
│  Escolha um valor:                      │
│  [R$ 5] [R$ 10] [R$ 20] [R$ 50] [___]  │
│                                         │
│  💳 [PIX] [Cartão] [Boleto]            │
│                                         │
│  □ Quero doar mensalmente               │
│  □ Exibir meu nome no mural             │
│                                         │
│  [💚 Doar Agora]  [Talvez depois]      │
│                                         │
│  🔒 Pagamento seguro via Mercado Pago  │
└─────────────────────────────────────────┘
```

---

## 📞 Suporte

- **Email**: suporte@petfinder.com
- **Documentação**: https://docs.petfinder.com
- **GitHub Issues**: https://github.com/seu-usuario/pet-finder/issues

---

## 📄 Licença

MIT License - Uso livre para fins comerciais e não comerciais.

---

**Desenvolvido com ❤️ para reunir pets com suas famílias**