-- Cria ou recria a view de estatísticas usada pela home e pelo painel admin.
-- Seguro de rodar múltiplas vezes (CREATE OR REPLACE).
CREATE OR REPLACE VIEW `view_estatisticas` AS
SELECT
  (SELECT COUNT(*) FROM usuarios WHERE ativo = 1)                             AS usuarios_ativos,
  (SELECT COUNT(*) FROM anuncios WHERE status = 'ativo')                      AS anuncios_ativos,
  (SELECT COUNT(*) FROM anuncios WHERE tipo = 'perdido'   AND status = 'ativo') AS perdidos_ativos,
  (SELECT COUNT(*) FROM anuncios WHERE tipo = 'encontrado' AND status = 'ativo') AS encontrados_ativos,
  (SELECT COUNT(*) FROM anuncios WHERE tipo = 'doacao'    AND status = 'ativo') AS doacoes_ativas,
  (SELECT COUNT(*) FROM anuncios WHERE status = 'resolvido')                  AS casos_resolvidos,
  (SELECT COALESCE(SUM(valor), 0) FROM doacoes WHERE status = 'aprovada')    AS total_doacoes,
  (SELECT COALESCE(SUM(valor), 0) FROM doacoes
   WHERE status = 'aprovada' AND MONTH(data_doacao) = MONTH(CURDATE()))      AS doacoes_mes_atual;
