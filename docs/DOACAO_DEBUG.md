# Debug das falhas de doação (cartão)

Esta pequena instrução descreve como ativar e inspecionar os logs quando ocorrer o erro genérico "Não foi possível registrar a doação" para pagamentos com cartão.

1) Ativar debug
- Defina a variável de ambiente `DEBUG_DOACAO=1` no `.env` do servidor ou via SetEnv no Apache (ou variável de ambiente do sistema). Exemplo:

    DEBUG_DOACAO=1

2) Reproduzir o erro
- Tente realizar a doação com cartão (cartão à vista ou recorrente) no ambiente onde o erro ocorre.

3) Coletar logs
- Os detalhes serão registrados em: `logs/doacao_debug.log` (um objeto JSON por linha).
- Para inspecionar os últimos 20 registros, execute:

    php scripts/debug_doacao_errors.php 20

4) Privacidade e segurança
- O payload registrado não contém dados sensíveis do cartão (números/validade/cvv). Ainda assim, mantenha o arquivo `logs/doacao_debug.log` restrito e apague entradas com dados sensíveis quando apropriado.

5) Informar o erro para suporte
- No site, quando ocorrer o erro, será retornado um código curto de suporte (ex: `doacao_20260112...`). Envie esse código para o time para que possamos localizar o registro completo no log.

---
Se precisar, posso ativar e reproduzir um teste controlado em produção (após sua confirmação explícita) para obter um caso real com dados de transação. Sempre pedirei confirmação antes de realizar testes com cobranças reais.