// Arquivo: database/initial_data.sql
// Conteúdo será fornecido nos artifacts anteriores
// Consulte os artifacts criados para o código completo

-- Usuário administrador padrão
INSERT INTO usuarios (nome, email, senha, telefone, ativo, email_confirmado, is_admin, data_cadastro) VALUES
('Administrador PetFinder', 'admin@petfinder.com', '$2y$12$K5b2c8vQ7Y9wZrXqJYj6Oe5x4zL1mN9pK0sR3tU7vW8yX2zC5fA6', '69999999999', 1, 1, 1, NOW());

-- Meta financeira inicial (opcional)
INSERT INTO metas_financeiras (descricao, valor_meta, data_inicio, data_fim) VALUES
('Manutenção mensal do PetFinder', 500.00, DATE_FORMAT(NOW(), '%Y-%m-01'), LAST_DAY(NOW()));