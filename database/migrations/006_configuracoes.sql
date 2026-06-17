-- Migration 006: Tabela de configurações do sistema
-- Fase 8: Painel Administrativo

CREATE TABLE IF NOT EXISTS configuracoes (
    chave        VARCHAR(100) NOT NULL PRIMARY KEY,
    valor        TEXT         NOT NULL DEFAULT '',
    descricao    VARCHAR(255) NULL,
    atualizado_em TIMESTAMP   NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Valores padrão
INSERT IGNORE INTO configuracoes (chave, valor, descricao) VALUES
    ('site_nome',       'Cadê Meu Pet?',                                     'Nome do site exibido no título e e-mails'),
    ('site_descricao',  'Plataforma de anúncios para pets perdidos e encontrados.', 'Descrição usada nas meta tags de SEO'),
    ('max_anuncios',    '5',                                                  'Máximo de anúncios ativos por usuário'),
    ('min_intervalo',   '1',                                                  'Intervalo mínimo em horas entre novos anúncios do mesmo usuário'),
    ('expiracao_dias',  '30',                                                 'Dias até um anúncio expirar automaticamente'),
    ('max_fotos',       '5',                                                  'Máximo de fotos por anúncio'),
    ('moderacao_ativa', '0',                                                  'Se 1, novos anúncios ficam pendentes até aprovação admin');
