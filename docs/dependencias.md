# Dependências Externas — Cadê Meu Pet?
> Verificado em: 2026-06-17

## Dependências encontradas

| Biblioteca | Versão | Fonte | Arquivo de referência | Status |
|---|---|---|---|---|
| Bootstrap CSS | **5.3.2** | jsdelivr CDN | includes/header.php:109 | OK |
| Bootstrap JS (bundle) | **5.3.2** | jsdelivr CDN | includes/footer.php:177 | OK |
| Bootstrap Icons | **1.11.3** | jsdelivr CDN | includes/header.php:110 | OK |
| Font Awesome | **6.5.1** | cdnjs CDN | includes/header.php:111 | OK |
| Leaflet.js CSS | **1.9.4** | unpkg CDN | includes/header.php:113 | OK (condicional) |
| Leaflet.js JS | **1.9.4** | unpkg CDN | includes/footer.php:180 | OK (condicional) |

## Dependências ausentes / pendências

| Biblioteca | Status | Observação |
|---|---|---|
| jQuery | **NÃO ENCONTRADO** | Não há jQuery no projeto — Bootstrap 5 não exige jQuery. Se algum código depender, deve ser migrado para vanilla JS. |
| Google Maps API | **NÃO ENCONTRADO** | Não há GOOGLE_MAPS_API_KEY configurada. Leaflet/OpenStreetMap é a estratégia principal. |
| Leaflet.markercluster | **NÃO ENCONTRADO** | Necessário para clustering de pins no mapa geral (Fase 6). Pendente de implementação. |

## Notas

- Font Awesome 6.5.1 já está carregado — atende à exigência do documento (versão 6.5.0+).
- Leaflet 1.9.4 já está presente de forma condicional em algumas páginas — deve ser padronizado.
- O projeto usa Bootstrap Icons 1.11.3 em paralelo com Font Awesome — após a Fase 3 (substituição de emojis), apenas Font Awesome deverá ser necessário para ícones. Bootstrap Icons pode ser removido se não houver dependências.
- jQuery: ausente no projeto. Confirmar que `assets/js/main.js` e demais scripts não dependem dele.
