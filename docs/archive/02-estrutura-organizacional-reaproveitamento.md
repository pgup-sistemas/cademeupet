# Prompt: Auditoria de Estrutura Organizacional e Reaproveitamento de Código

## Contexto do projeto
Sistema web em **PHP puro (vanilla)**, sem framework de backend definido (a confirmar via investigação).
Já existe uso parcial de **includes/partials** (ex.: `header.php`, e também footer, sidebar etc.), mas não há certeza sobre:
- Quão consistente é esse padrão em todo o projeto
- Se há lógica de negócio misturada com HTML dentro dos mesmos arquivos
- Se há duplicação de código (funções, queries, validações) repetida em várias páginas
- Se existe um padrão real de pastas ou se cresceu de forma orgânica/ad-hoc

⚠️ Este é um pedido de **auditoria geral** — ainda não há um problema específico identificado. O objetivo é mapear o estado atual e só então decidir prioridades.

## Etapa 0 — Investigação obrigatória (fazer antes de propor qualquer mudança)
1. Mapear a árvore de diretórios do projeto inteiro (até 2-3 níveis), identificando padrões e exceções.
2. Identificar onde estão: configurações (`config.php` e similares), funções utilitárias/helpers, includes/partials de layout, lógica de acesso a dados (queries SQL diretas? alguma camada de abstração?), arquivos de cada "página"/rota.
3. Verificar se há separação entre lógica (PHP/backend) e apresentação (HTML/view), ou se estão misturados no mesmo arquivo.
4. Procurar por código duplicado: funções reescritas em múltiplos arquivos, blocos HTML repetidos sem include, queries SQL repetidas, validações repetidas.
5. Verificar como autenticação/sessão é tratada (ex.: `isLoggedIn()`, `isAdmin()`, `$_SESSION`) e se esse padrão é consistente em todo o sistema.
6. Verificar se há algum tipo de roteamento (arquivo único `index.php` com switch/router, ou cada página é um arquivo `.php` acessado diretamente).
7. Checar se há testes automatizados, versionamento (git), `.env` ou configs sensíveis expostas incorretamente.

## Saída da Etapa 0 (obrigatória antes de prosseguir)
Um relatório resumido contendo:
- Estrutura de pastas atual (representada em árvore).
- Padrões identificados (o que é consistente).
- Inconsistências identificadas (o que varia sem motivo aparente).
- Lista de duplicações de código encontradas, com localização (arquivo + trecho).
- Riscos encontrados (segurança, manutenibilidade, configs expostas).
- Uma nota de **complexidade/esforço estimado** para cada possível melhoria, sem ainda implementar nada.

Esse relatório deve ser apresentado para eu validar prioridades antes de qualquer refatoração.

## Objetivo (após validação do relatório)
1. Reduzir duplicação de código (extrair para funções/helpers/includes reutilizáveis).
2. Organizar estrutura de pastas de forma mais clara, se necessário (sem migrar para um framework, a menos que seja decisão explícita futura).
3. Padronizar nomenclatura de arquivos e funções.
4. Separar, na medida do possível sem reescrever tudo, lógica de dados/negócio da camada de apresentação.
5. Aumentar reaproveitamento de componentes de view (partials) onde fizer sentido.

## Regras de trabalho
- **Nada de reescrita completa ou migração de framework** nesta fase. O foco é organizar e reaproveitar o que já existe.
- Mudanças estruturais devem ser feitas em pequenos lotes, testáveis, com explicação clara do que mudou e por quê.
- Qualquer refatoração que toque em autenticação, sessão, pagamentos ou dados sensíveis deve ser sinalizada com destaque e só aplicada após confirmação explícita minha.
- Preferir extrair duplicações para funções/includes existentes antes de criar novos padrões do zero.
- Documentar (mesmo que de forma simples, em comentários ou num `ESTRUTURA.md`) as decisões de organização tomadas, para que fique claro o "porquê" de cada pasta/arquivo.

## Processo esperado
1. Investigação completa (Etapa 0) → relatório.
2. Eu valido prioridades com base no relatório.
3. Plano de refatoração dividido em fases pequenas e independentes.
4. Implementação fase a fase, com confirmação minha entre cada fase.
5. Para cada fase: resumo do que mudou, arquivos afetados, e o que ficou mais fácil de manter como resultado.

## Restrições
- Não alterar comportamento funcional do sistema durante a reorganização (refactor, não rewrite).
- Não introduzir dependências/bibliotecas novas sem necessidade clara e aprovação.
- Não remover código "não óbvio" sem confirmar que realmente não é usado em nenhum lugar (buscar referências no projeto inteiro antes de excluir).
