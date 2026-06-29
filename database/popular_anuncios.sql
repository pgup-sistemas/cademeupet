-- Descobrir o id do admin
SELECT id FROM usuarios WHERE email = 'admin@cademeupet.com.br';

-- Supondo que o id retornado seja 1. Se for diferente, troque nas linhas abaixo.

INSERT INTO anuncios (
  usuario_id, tipo, nome_pet, especie, raca, cor, tamanho, castrado,
  necessita_termo_responsabilidade, descricao, data_ocorrido, endereco_completo, bairro,
  cidade, estado, cep, ponto_referencia, latitude, longitude, telefone_contato, whatsapp,
  email_contato, recompensa, status, visualizacoes, data_expiracao
) VALUES
-- Perdidos

(1, 'perdido', 'Rex', 'cachorro', 'SRD', 'preto', 'medio', 1, 0, 'Cão perdido no bairro', '2026-01-10', 'Rua A, 123', 'Centro', 'Porto Velho', 'RO', '76800-000', 'Próximo à praça', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'perdido', 'Mimi', 'gato', 'Persa', 'branco', 'pequeno', 1, 0, 'Gata sumiu de casa', '2026-01-11', 'Rua B, 456', 'Nova Porto', 'Porto Velho', 'RO', '76800-001', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'perdido', 'Tico', 'ave', 'Calopsita', 'cinza', 'pequeno', 0, 0, 'Ave voou pela janela', '2026-01-12', 'Rua C, 789', 'Industrial', 'Porto Velho', 'RO', '76800-002', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'perdido', 'Luna', 'cachorro', 'Labrador', 'amarelo', 'grande', 1, 0, 'Sumiu no parque', '2026-01-13', 'Rua D, 101', 'Floresta', 'Porto Velho', 'RO', '76800-003', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'perdido', 'Bolinha', 'gato', 'Siamês', 'cinza', 'pequeno', 1, 0, 'Desapareceu no condomínio', '2026-01-14', 'Rua E, 202', 'Liberdade', 'Porto Velho', 'RO', '76800-004', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),

-- Encontrados

(1, 'encontrado', 'Desconhecido', 'cachorro', 'SRD', 'marrom', 'medio', 0, 0, 'Cão encontrado na rua', '2026-01-10', 'Rua F, 303', 'Centro', 'Porto Velho', 'RO', '76800-005', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'encontrado', 'Desconhecido', 'gato', 'SRD', 'preto', 'pequeno', 0, 0, 'Gato apareceu no quintal', '2026-01-11', 'Rua G, 404', 'Nova Porto', 'Porto Velho', 'RO', '76800-006', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'encontrado', 'Desconhecido', 'ave', 'Papagaio', 'verde', 'pequeno', 0, 0, 'Ave encontrada no telhado', '2026-01-12', 'Rua H, 505', 'Industrial', 'Porto Velho', 'RO', '76800-007', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'encontrado', 'Desconhecido', 'cachorro', 'Poodle', 'branco', 'pequeno', 0, 0, 'Cão perdido encontrado', '2026-01-13', 'Rua I, 606', 'Floresta', 'Porto Velho', 'RO', '76800-008', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'encontrado', 'Desconhecido', 'gato', 'Angorá', 'laranja', 'medio', 0, 0, 'Gato encontrado no estacionamento', '2026-01-14', 'Rua J, 707', 'Liberdade', 'Porto Velho', 'RO', '76800-009', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),

-- Doação

(1, 'doacao', 'Mel', 'cachorro', 'SRD', 'caramelo', 'medio', 1, 1, 'Cão dócil para adoção', '2026-01-10', 'Rua K, 808', 'Centro', 'Porto Velho', 'RO', '76800-010', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'doacao', 'Nina', 'gato', 'SRD', 'preto', 'pequeno', 1, 1, 'Gata carinhosa para adoção', '2026-01-11', 'Rua L, 909', 'Nova Porto', 'Porto Velho', 'RO', '76800-011', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'doacao', 'Pipoca', 'cachorro', 'SRD', 'branco', 'pequeno', 1, 1, 'Cão brincalhão para adoção', '2026-01-12', 'Rua M, 1010', 'Industrial', 'Porto Velho', 'RO', '76800-012', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'doacao', 'Frajola', 'gato', 'SRD', 'cinza', 'medio', 1, 1, 'Gato esperto para adoção', '2026-01-13', 'Rua N, 1111', 'Floresta', 'Porto Velho', 'RO', '76800-013', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL),
(1, 'doacao', 'Bidu', 'cachorro', 'SRD', 'azul', 'grande', 1, 1, 'Cão forte para adoção', '2026-01-14', 'Rua O, 1212', 'Liberdade', 'Porto Velho', 'RO', '76800-014', '', NULL, NULL, '69999999999', '69999999999', 'admin@cademeupet.com.br', '', 'ativo', 0, NULL);
