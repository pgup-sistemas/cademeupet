# Prompt: Auditoria e Melhoria de Frontend, UX e Responsividade

## Contexto do projeto
Este é um sistema web em **PHP puro (vanilla)**, sem framework de backend, utilizando:
- Includes manuais (`require`/`include`) para layout (header, footer, sidebar etc.)
- **Bootstrap 5** como base de CSS/grid/componentes
- Ícones via **Bootstrap Icons** e **Font Awesome** (possivelmente as duas ao mesmo tempo — verificar redundância)
- Google Fonts (Poppins/Inter)
- CSS customizado em arquivos próprios (ex.: `style.css`, `cademeupet.css`) usando variáveis CSS (`--cmp-primary` etc.)
- Possível uso de bibliotecas adicionais (ex.: Leaflet para mapas) carregadas condicionalmente

⚠️ Antes de qualquer alteração, **não assuma que o projeto é 100% uniforme**. Pode haver páginas ou módulos com padrões diferentes (jQuery, JS solto, outras libs).

## Etapa 0 — Investigação obrigatória (fazer antes de qualquer mudança)
1. Mapeie todos os arquivos de view/template do projeto (estrutura de pastas, padrão de nomenclatura).
2. Liste todas as bibliotecas CSS/JS carregadas no projeto (procure por `<link>`, `<script>`, CDNs, `package.json` se existir).
3. Identifique se há mistura de paradigmas (ex.: algumas páginas com Bootstrap puro, outras com componentes customizados, jQuery vs JS vanilla).
4. Identifique os breakpoints e padrões de responsividade já usados (classes `d-none`, `d-lg-*`, media queries customizadas no CSS).
5. Apresente um resumo dessa investigação **antes de começar a editar código**, para validação.

## Objetivo
Melhorar de forma ampla:
1. **Usabilidade** — fluxos de navegação, clareza de ações, feedback visual (estados de loading, erro, sucesso), acessibilidade básica (labels, contraste, foco em teclado, `aria-*`).
2. **Layout** — consistência visual entre páginas (espaçamentos, tipografia, cores, componentes repetidos com estilos diferentes).
3. **Responsividade** — comportamento correto em mobile, tablet e desktop; eliminar overflow horizontal, elementos cortados, textos ilegíveis, botões/touch targets pequenos demais em mobile.

## Regras de trabalho
- **Não reescrever do zero.** Trabalhar em cima da estrutura existente (PHP + Bootstrap), refatorando incrementalmente.
- **Não duplicar bibliotecas de ícones.** Se Bootstrap Icons e Font Awesome estiverem ambos em uso, escolher uma (a que tiver mais uso no projeto) e migrar o restante, ou justificar a manutenção das duas se houver razão técnica clara.
- **Mobile-first**: ao revisar/criar CSS, pensar primeiro no comportamento mobile e depois expandir para desktop.
- Preservar a identidade visual já definida (cores via variáveis CSS, fontes Poppins/Inter), só ajustando consistência, não recriando a marca.
- Qualquer componente repetido em 2+ páginas (header, footer, cards de listagem, formulários) deve ser candidato a virar partial/include reutilizável — mas isso deve ser **reportado e validado**, não decidido unilateralmente caso já exista divergência de uso.
- Ao editar, ir página por página ou componente por componente — não tentar alterar o sistema inteiro de uma vez. Priorizar páginas de maior tráfego/importância (definidas em conjunto comigo).

## Processo esperado
1. Investigação (Etapa 0) e resumo do que foi encontrado.
2. Lista priorizada de problemas de UX/layout/responsividade encontrados, com prints ou descrição clara de cada um (tela, comportamento esperado vs. atual).
3. Proposta de plano de correção, dividido em fases pequenas.
4. Implementação fase a fase, com confirmação minha entre cada fase antes de seguir para a próxima.
5. Para cada mudança visual relevante, explicar o "antes/depois" em texto simples.

## Saída esperada por fase
- Lista do que foi alterado.
- Arquivos modificados.
- Pontos de atenção ou riscos (ex.: "essa mudança no header pode afetar X páginas que usam o mesmo include").
- Sugestão do próximo passo.

## Restrições
- Não remover funcionalidades existentes sem aviso explícito.
- Não trocar a stack (ex.: não introduzir um framework JS pesado como React/Vue) sem essa decisão ser tomada e validada comigo antes.
- Não commitar/alterar arquivos de configuração de produção sem confirmação.
