# Resultado dos Testes — Cadê Meu Pet?
> Executado em: 2026-06-17
> Comando: `php tests/test_runner.php`

## Resumo

| Total | Passou | Falhou |
|---|---|---|
| 2 | 1 | 1 |

---

## Detalhes dos testes

### [OK] Limite de anúncios ativos impede novas publicações
- **Status:** PASSOU
- **Descrição:** Ao tentar criar um anúncio quando o usuário já atingiu o limite de 10 anúncios ativos, o sistema retorna os erros esperados: "Você atingiu o limite de 10 anúncios ativos." e "Adicione pelo menos uma foto para destacar seu anúncio."

### [FALHA] Login bloqueia após três tentativas incorretas
- **Status:** FALHOU
- **Mensagem:** `Mensagem deve indicar bloqueio. (needle: 'Conta bloqueada')`
- **Causa:** O sistema efetua 4 tentativas de login com e-mail `teste@petfinder.com` e verifica se a mensagem contém 'Conta bloqueada'. Porém a mensagem retornada não contém esse texto exato. A lógica de bloqueio existe (`MAX_LOGIN_ATTEMPTS = 3`), mas a mensagem pode ter sido alterada ou o usuário de teste não existe no banco de dados de teste.

---

## Observações

- A suite de testes é mínima (apenas 2 testes). Será expandida significativamente nas Fases 5, 6 e 9.
- O teste de bloqueio de login falha provavelmente por ausência do usuário `teste@petfinder.com` no banco de testes ou por mudança no texto da mensagem de bloqueio.
- Não há testes para: reações, mapa, Pet Love, CSRF, upload, moderação, alertas.

## Próximos testes a implementar (Fase 9)

- Pet Love: cadastro salva todos os campos
- Pet Love: matching retorna apenas pets de sexo oposto e espécie idêntica
- Pet Love: cálculo de distância (Haversine)
- Pet Love: score de compatibilidade
- Pet Love: manifestação de interesse sem duplicidade
- Validação de coordenadas (lat/lng dentro do Brasil)
- Geocoding reverso retorna resultado válido
- Moderação: anúncio pendente não aparece na listagem pública
- Alerta de busca dispara corretamente
