-- Fix resolved ads helper script
-- 1) List all anúncios com status 'resolvido'
SELECT id, usuario_id, tipo, nome_pet, cidade, estado, data_publicacao, status
FROM anuncios
WHERE status = 'resolvido'
ORDER BY data_publicacao DESC;

-- 2) Verifique o id do admin (se necessário)
SELECT id, nome, email FROM usuarios WHERE email = 'admin@cademeupet.com.br';

-- 3) REATIVAR anúncios específicos (substitua IDs)
-- Exemplo: reativar só o anúncio 1
-- UPDATE anuncios SET status = 'ativo', data_atualizacao = NOW() WHERE id = 1;

-- 4) REATIVAR todos anúncios marcados como resolvido (opcional)
-- ATENÇÃO: execute somente se tiver certeza
-- UPDATE anuncios SET status = 'ativo', data_atualizacao = NOW() WHERE status = 'resolvido';

-- 5) REATIVAR apenas os anúncios resolvidos de UM usuário (substitua USER_ID)
-- UPDATE anuncios SET status = 'ativo', data_atualizacao = NOW() WHERE status = 'resolvido' AND usuario_id = USER_ID;

-- 6) Verifique o resultado
-- SELECT id, usuario_id, status FROM anuncios WHERE id IN (1) -- ou outros IDs

-- Recomendações:
-- - Faça backup da tabela antes de executar updates em massa.
-- - Execute primeiro as queries SELECT para confirmar os registros afetados.
-- - Ajuste USER_ID e lista de IDs conforme necessário.
