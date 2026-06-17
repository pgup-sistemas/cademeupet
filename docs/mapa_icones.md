# Mapa de Ícones — Emojis Substituídos por Font Awesome 6
> Gerado em: 2026-06-17

## Font Awesome carregado
`https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css` — em `includes/header.php`

## Tabela de substituições

| Emoji | Ícone FA inserido | Arquivo(s) |
|---|---|---|
| 🐾 | `<i class="fa-solid fa-paw"></i>` | header.php, home.php, login.php, cadastro.php, emails/alerta_resumo.php |
| 🔴 (badge tipo) | `<i class="fa-solid fa-circle text-danger"></i>` | anuncio-detalhes.php, favoritos.php, home.php |
| 🟢 (badge tipo) | `<i class="fa-solid fa-circle text-success"></i>` | anuncio-detalhes.php, favoritos.php, home.php |
| 💙 (badge tipo) | `<i class="fa-solid fa-circle text-primary"></i>` | anuncio-detalhes.php, favoritos.php, home.php |
| 🔴 (ícone tipo) | `<i class="fa-solid fa-triangle-exclamation text-danger"></i>` | novo-anuncio.php |
| 🟢 (ícone tipo) | `<i class="fa-solid fa-circle-check text-success"></i>` | novo-anuncio.php |
| 💙 (ícone doação) | `<i class="fa-solid fa-hand-holding-heart text-primary"></i>` | novo-anuncio.php |
| 🐕 | Texto simples "Cachorro/Cachorros" | busca.php, novo-anuncio.php, home.php |
| 🐈 | Texto simples "Gato/Gatos" | busca.php, novo-anuncio.php, home.php |
| 🦜 | Texto simples "Ave" | busca.php, novo-anuncio.php |
| 📍 | `<i class="fa-solid fa-location-dot"></i>` | home.php |
| 📷 | `<i class="fa-solid fa-camera"></i>` | home.php |
| 📸 | `<i class="fa-solid fa-camera"></i>` | novo-anuncio.php |
| 🔍 / 🔎 | `<i class="fa-solid fa-magnifying-glass"></i>` | home.php |
| ❤️ | `<i class="fa-solid fa-heart text-danger"></i>` | home.php, doar.php |
| 💚 | `<i class="fa-solid fa-heart text-success"></i>` | home.php, doar.php, transparencia.php |
| 🕒 | `<i class="fa-regular fa-clock"></i>` | home.php |
| ⚡ | `<i class="fa-solid fa-bolt"></i>` | home.php |
| 📝 | `<i class="fa-solid fa-file-pen"></i>` | home.php |
| 📢 | `<i class="fa-solid fa-bullhorn"></i>` | novo-anuncio.php |
| 📊 | `<i class="fa-solid fa-chart-bar"></i>` | transparencia.php |
| ✅ | `<i class="fa-solid fa-circle-check text-success"></i>` | transparencia.php, assinaturas.php |
| ❌ | `<i class="fa-solid fa-circle-xmark text-danger"></i>` | assinaturas.php |
| ⏸️ | `<i class="fa-solid fa-pause"></i>` | assinaturas.php |
| 💳 | `<i class="fa-solid fa-credit-card"></i>` | assinaturas.php |
| 📄 | `<i class="fa-solid fa-file"></i>` | assinaturas.php |
| 🔑 | `<i class="fa-solid fa-key"></i>` | recuperar-senha.php |
| 📧 | `<i class="fa-solid fa-envelope"></i>` | reenviar-confirmacao.php |
| 🔐 | `<i class="fa-solid fa-lock"></i>` | resetar-senha.php |
| 📌 | `<i class="fa-solid fa-thumbtack"></i>` | transparencia.php |
| 🗺️ | `<i class="fa-solid fa-map"></i>` | transparencia.php |
| 🎯 | `<i class="fa-solid fa-bullseye"></i>` | emails/alerta_resumo.php |

## Emojis em selects (removidos — textos simples)

Emojis em `<option>` de selects foram removidos e o texto mantido puro:
- `🔴 Perdidos` → `Perdidos`
- `🟢 Encontrados` → `Encontrados`
- `💙 Adoção` → `Adoção`
- `🐕 Cachorro` → `Cachorro`
- `🐈 Gato` → `Gato`
- `🦜 Ave` → `Ave`
- `🐾 Outro` → `Outro`

## Emojis em comentários de código (mantidos — permitido pelo documento)

- `═` em separadores de seção em `functions.php`, `main.js`, `style.css` — mantidos como decoração de comentário, não são UI-facing.

## Resultado final

- **75 arquivos** verificados
- **ZERO emojis de interface** encontrados após substituição
