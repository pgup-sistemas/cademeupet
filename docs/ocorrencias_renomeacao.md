# Ocorrências para Renomeação — PetFinder → Cadê Meu Pet?
> Mapeado em: 2026-06-17
> Total de ocorrências: 470

## Arquivos de produção afetados (código ativo)

| Arquivo | Tipo |
|---|---|
| config.php | Configuração principal |
| .htaccess | Configuração Apache |
| .env / .htaccess.env | Variáveis de ambiente |
| includes/header.php | Layout |
| includes/footer.php | Layout |
| includes/functions.php | Funções globais |
| includes/auth.php | Autenticação |
| includes/db.php | Conexão banco |
| includes/efi.php | Integração pagamentos |
| assets/css/style.css | CSS principal |
| assets/js/main.js | JS principal |
| assets/js/map.js | JS mapa |
| database/schema.sql | Schema do banco |
| database/initial_data.sql | Dados iniciais |
| controllers/* (8 arquivos) | Controllers |
| models/* (6 arquivos) | Models |
| views/* (~30 arquivos) | Views |
| api/* (2 arquivos) | APIs |

## Documentação legada (não alterar conteúdo técnico)

Vários arquivos .md na raiz são documentação de implementação anterior (EFI, PIX, cancelamentos). Serão ignorados pois não afetam o funcionamento do sistema.

## Substituições aplicadas

| De | Para | Escopo |
|---|---|---|
| `PetFinder` (display) | `Cadê Meu Pet?` | Textos de interface |
| `petfinder` (slug/db) | `cademeupet` | Constantes, banco, URLs |
| `SITE_NAME = 'PetFinder'` | `SITE_NAME = 'Cadê Meu Pet?'` | config.php |
| `DB_NAME = 'petfinder'` | `DB_NAME = 'cademeupet'` | config.php |
| `admin@petfinder.com` | `admin@cademeupet.com.br` | config.php, schema |
| `BASE_URL petfinder.pageup.net.br` | Mantido (URL de produção real) | config.php |
| `<title>PetFinder</title>` | `<title>Cadê Meu Pet?</title>` | header.php |
| Meta description | Atualizada | header.php |
