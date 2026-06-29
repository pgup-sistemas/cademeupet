# Mapa de Rotas — Cadê Meu Pet?
> Mapeado em: 2026-06-17

## Padrão de roteamento

O projeto usa **entry points na raiz** que incluem a view correspondente. Não há roteador central (sem framework).

Padrão: `/<rota>.php` (raiz) → inclui `config.php` → inclui `views/<rota>.php`

---

## Tabela de Rotas

| Arquivo/Rota | View | Controller / Model | Auth | Status |
|---|---|---|---|---|
| `/` (index.php) | views/home.php | — | Não requer | OK |
| `/login.php` | views/login.php | UsuarioController | Não requer | OK |
| `/logout.php` | views/logout.php | UsuarioController | Não requer | OK |
| `/cadastro.php` | views/cadastro.php | UsuarioController | Não requer | OK |
| `/perfil.php` | views/perfil.php | UsuarioController | requireLogin() | OK |
| `/novo-anuncio.php` | views/novo-anuncio.php | AnuncioController | requireLogin() | OK |
| `/editar-anuncio.php` | views/editar-anuncio.php | AnuncioController | requireLogin() | OK |
| `/anuncio.php` | views/anuncio-detalhes.php | AnuncioController | Não requer | OK |
| `/busca.php` | views/busca.php | BuscaController | Não requer | OK |
| `/favoritos.php` | views/favoritos.php | FavoritoController | requireLogin() | OK |
| `/meus-anuncios.php` | views/meus-anuncios.php | AnuncioController | requireLogin() | OK |
| `/alertas.php` | views/alertas.php | AlertaController | requireLogin() | OK |
| `/admin.php` | views/admin.php | — | requireAdmin() | OK |
| `/admin-usuarios.php` | views/admin-usuarios.php | UsuarioController | requireAdmin() | OK |
| `/admin-financeiro.php` | views/admin-financeiro.php | DoacaoController | requireAdmin() | OK |
| `/transparencia.php` | views/transparencia.php | — | Não requer | OK |
| `/doar.php` | views/doar.php | DoacaoController | Não requer | OK |
| `/doacao-pix.php` | views/doacao-pix.php | DoacaoController | Não requer | OK |
| `/doacao-cancelar.php` | views/doacao-cancelar.php | DoacaoController | requireLogin() | OK |
| `/recuperar-senha.php` | views/recuperar-senha.php | UsuarioController | Não requer | OK |
| `/resetar-senha.php` | views/resetar-senha.php | UsuarioController | Não requer | OK |
| `/reenviar-confirmacao.php` | views/reenviar-confirmacao.php | UsuarioController | Não requer | OK |
| `/confirmar-email.php` | — (inline) | UsuarioController | Não requer | OK |
| `/parceiros.php` | views/parceiros.php | — | Não requer | OK |
| `/parceiros-inscricao.php` | views/parceiros-inscricao.php | — | requireLogin() | OK |
| `/parceiro-painel.php` | views/parceiro-painel.php | — | requireLogin() | OK |
| `/parceiro-perfil.php` | views/parceiro-perfil.php | ParceiroPerfil | requireLogin() | OK |
| `/parceiro-pagamento.php` | views/parceiro-pagamento.php | PagamentoController | requireLogin() | OK |
| `/parceiro-pix.php` | views/parceiro-pix.php | PagamentoController | requireLogin() | OK |
| `/parceiro-cancelar.php` | views/parceiro-cancelar.php | CancelamentoController | requireLogin() | OK |
| `/parceiro-publico.php` | views/parceiro-publico.php | — | Não requer | OK |
| `/doacao-abrir-pagamento.php` | — (inline) | DoacaoController | Não requer | OK |
| `/parceiro-abrir-pagamento.php` | — (inline) | PagamentoController | requireLogin() | OK |
| `/marcar-resolvido.php` | — (action) | AnuncioController | requireLogin() | OK |
| `/marcar-ativo.php` | — (action) | AnuncioController | requireLogin() | OK |
| `/excluir-anuncio.php` | — (action) | AnuncioController | requireLogin() | OK |
| `/favorito_toggle.php` | — (AJAX) | FavoritoController | requireLogin() | OK |
| `/sitemap.php` | — (XML) | — | Não requer | OK |

### APIs
| Rota | Descrição | Auth | Status |
|---|---|---|---|
| `/api/cep.php` | Consulta CEP via ViaCEP | Não requer | OK |
| `/api/geocode.php` | Geocoding via Nominatim | Não requer | OK |
| `/api/efi-webhook.php` | Webhook EFI pagamentos | Assinatura EFI | OK |
| `/api/efi-billing-notification.php` | Notificação de cobrança EFI | Assinatura EFI | OK |
| `/api/resync-doacao.php` | Resync doações pendentes | requireAdmin() | OK |
| `/api/status-doacao.php` | Status de doação (AJAX) | Não requer | OK |

---

## Rotas previstas nas fases (a implementar)

| Rota | Fase | Descrição |
|---|---|---|
| `/petlove.php` | Fase 5 | Vitrine Pet Love |
| `/petlove-detalhe.php` | Fase 5 | Detalhe do pet para cruzamento |
| `/petlove-novo.php` | Fase 5 | Cadastrar pet no Pet Love |
| `/minha-conta.php` | Fase 7 | Dashboard do usuário |
| `/admin-moderacao.php` | Fase 7/8 | Fila de moderação |
| `/admin-config.php` | Fase 8 | Configurações do sistema |
| `/api/mapa/pins` | Fase 6 | Pins para mapa geral |
| `/api/petlove/interesse` | Fase 5 | Manifestar interesse em cruzamento |

---

## Tratamento de erros

- Páginas 404/403/500: **não encontradas** — não há views dedicadas para erros.
- `.htaccess` redireciona erros? A ser verificado na Fase 1 (.htaccess não foi analisado em detalhes).

**Pendência:** criar `views/404.php`, `views/403.php`, `views/500.php` e configurar no `.htaccess`.
