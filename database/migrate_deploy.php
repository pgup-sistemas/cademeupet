<?php
/**
 * Script de migration para deploy em produção.
 *
 * Cria/atualiza tabelas, colunas e a view que o código já espera mas que
 * ainda não existem no banco (idempotente — pode rodar quantas vezes
 * quiser, só faz o que ainda falta).
 *
 * Como usar após subir os arquivos via FileZila:
 *   - Via navegador: acesse /database/migrate_deploy.php logado como admin
 *   - Via SSH/terminal (se tiver acesso): php database/migrate_deploy.php
 *
 * Depois de confirmar que rodou com sucesso, pode apagar este arquivo do
 * servidor (ou deixar — ele não faz nada destrutivo e é protegido por login
 * de admin quando acessado pelo navegador).
 */

require_once __DIR__ . '/../config.php';

$isCli = (php_sapi_name() === 'cli');

if (!$isCli) {
    // Acesso via navegador precisa estar logado como admin
    requireAdmin();
    header('Content-Type: text/plain; charset=utf-8');
}

function out(string $msg): void {
    echo $msg . "\n";
}

function tableExists(Database $db, string $table): bool {
    $row = $db->fetchOne(
        "SELECT COUNT(*) AS n FROM information_schema.tables
         WHERE table_schema = DATABASE() AND table_name = ?",
        [$table]
    );
    return (int)($row['n'] ?? 0) > 0;
}

function columnExists(Database $db, string $table, string $column): bool {
    $row = $db->fetchOne(
        "SELECT COUNT(*) AS n FROM information_schema.columns
         WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ?",
        [$table, $column]
    );
    return (int)($row['n'] ?? 0) > 0;
}

function viewExists(Database $db, string $view): bool {
    $row = $db->fetchOne(
        "SELECT COUNT(*) AS n FROM information_schema.views
         WHERE table_schema = DATABASE() AND table_name = ?",
        [$view]
    );
    return (int)($row['n'] ?? 0) > 0;
}

/**
 * Cria a tabela se ainda não existir. $sql deve ser só o corpo
 * "CREATE TABLE `nome` (...) ENGINE=..." completo.
 */
function ensureTable(Database $db, string $table, string $createSql): void {
    if (tableExists($db, $table)) {
        out("  `$table` já existe, nada a fazer.");
        return;
    }
    $db->query($createSql);
    out("  `$table` criada com sucesso.");
}

out("=== Migration de deploy — Cadê Meu Pet? ===");
out("Iniciado em " . date('Y-m-d H:i:s'));
out("");

try {
    $db = getDB();
    $passo = 0;
    $totalPassos = 38;

    // ── 1. cancelamentos_log ────────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela cancelamentos_log");
    ensureTable($db, 'cancelamentos_log', "
        CREATE TABLE `cancelamentos_log` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `usuario_id` int(11) DEFAULT NULL,
          `doacao_id` int(11) DEFAULT NULL,
          `tipo` enum('assinatura_parceiro','doacao_recorrente') NOT NULL,
          `motivo` text DEFAULT NULL,
          `gateway_response` varchar(50) DEFAULT NULL,
          `responsavel` enum('usuario','admin','sistema') NOT NULL DEFAULT 'usuario',
          `ip_address` varchar(45) DEFAULT NULL,
          `user_agent` text DEFAULT NULL,
          `data_cancelamento` datetime NOT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_usuario_id` (`usuario_id`),
          KEY `idx_doacao_id` (`doacao_id`),
          KEY `idx_tipo` (`tipo`),
          KEY `idx_data_cancelamento` (`data_cancelamento`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 2. colunas de cancelamento em parceiro_assinaturas e doacoes ────
    out("[" . (++$passo) . "/$totalPassos] Colunas de cancelamento");
    $colunasCancelamento = [
        'cancelada_em'         => "datetime DEFAULT NULL",
        'motivo_cancelamento'  => "text DEFAULT NULL",
        'cancelamento_gateway' => "varchar(20) DEFAULT NULL COMMENT 'sucesso, manual, falha'",
    ];
    foreach (['parceiro_assinaturas', 'doacoes'] as $tabela) {
        if (!tableExists($db, $tabela)) {
            out("  ! tabela `$tabela` não existe — pulando (verifique o schema base).");
            continue;
        }
        foreach ($colunasCancelamento as $coluna => $definicao) {
            if (columnExists($db, $tabela, $coluna)) {
                out("  $tabela.$coluna já existe.");
                continue;
            }
            $db->query("ALTER TABLE `$tabela` ADD COLUMN `$coluna` $definicao");
            out("  $tabela.$coluna adicionada.");
        }
    }
    out("");

    // ── 3. configuracoes (chave/valor do painel admin) ──────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela configuracoes");
    ensureTable($db, 'configuracoes', "
        CREATE TABLE `configuracoes` (
          `chave` varchar(100) NOT NULL,
          `valor` text NOT NULL DEFAULT '',
          `descricao` varchar(255) DEFAULT NULL,
          `atualizado_em` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`chave`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    out("");

    // ── 4. efi_transfers (log de repasses do gateway EFI) ───────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela efi_transfers");
    ensureTable($db, 'efi_transfers', "
        CREATE TABLE `efi_transfers` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `txid` varchar(100) NOT NULL,
          `payload` text DEFAULT NULL,
          `status` varchar(50) DEFAULT NULL,
          `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_txid` (`txid`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 5. parceiro_contratos_aceites (aceite de contrato do parceiro) ──
    out("[" . (++$passo) . "/$totalPassos] Tabela parceiro_contratos_aceites");
    ensureTable($db, 'parceiro_contratos_aceites', "
        CREATE TABLE `parceiro_contratos_aceites` (
          `id` int(11) NOT NULL AUTO_INCREMENT,
          `usuario_id` int(11) NOT NULL,
          `versao_contrato` varchar(20) NOT NULL DEFAULT '1.0',
          `plano` enum('basico','destaque') NOT NULL,
          `periodicidade` enum('mensal','anual') NOT NULL,
          `valor_mensal` decimal(10,2) NOT NULL,
          `ip_aceite` varchar(45) DEFAULT NULL,
          `user_agent` text DEFAULT NULL,
          `hash_contrato` char(64) DEFAULT NULL COMMENT 'SHA-256 do conteudo do contrato',
          `aceito_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_usuario` (`usuario_id`),
          KEY `idx_aceito_em` (`aceito_em`),
          CONSTRAINT `parceiro_contratos_aceites_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci
    ");
    out("");

    // ── 6. petlove_pets (precisa vir antes de fotos/interesses — FK) ────
    out("[" . (++$passo) . "/$totalPassos] Tabela petlove_pets");
    ensureTable($db, 'petlove_pets', "
        CREATE TABLE `petlove_pets` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `usuario_id` int(11) NOT NULL,
          `nome` varchar(100) NOT NULL,
          `especie` enum('cachorro','gato','outro') NOT NULL DEFAULT 'cachorro',
          `raca` varchar(100) NOT NULL,
          `porte` enum('mini','pequeno','medio','grande','gigante') NOT NULL,
          `sexo` enum('macho','femea') NOT NULL,
          `idade_meses` smallint(5) unsigned NOT NULL,
          `cor` varchar(50) DEFAULT NULL,
          `peso_kg` decimal(5,2) DEFAULT NULL,
          `tem_pedigree` tinyint(1) NOT NULL DEFAULT 0,
          `pedigree_num` varchar(60) DEFAULT NULL,
          `vacinado` tinyint(1) NOT NULL DEFAULT 0,
          `vermifugado` tinyint(1) NOT NULL DEFAULT 0,
          `castrado` tinyint(1) NOT NULL DEFAULT 0,
          `descricao` text DEFAULT NULL,
          `objetivo` enum('cruzamento','pedigree','companhia') NOT NULL DEFAULT 'cruzamento',
          `latitude` decimal(10,8) DEFAULT NULL,
          `longitude` decimal(11,8) DEFAULT NULL,
          `cidade` varchar(100) DEFAULT NULL,
          `estado` char(2) DEFAULT NULL,
          `disponivel` tinyint(1) NOT NULL DEFAULT 1,
          `criacao_responsavel` tinyint(1) NOT NULL DEFAULT 0,
          `status` enum('ativo','pausado','removido') NOT NULL DEFAULT 'ativo',
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_especie_raca_porte` (`especie`,`raca`(50),`porte`),
          KEY `idx_sexo` (`sexo`),
          KEY `idx_status_disponivel` (`status`,`disponivel`),
          KEY `idx_geo` (`latitude`,`longitude`),
          KEY `idx_usuario` (`usuario_id`),
          CONSTRAINT `fk_petlove_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 7. petlove_fotos ─────────────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela petlove_fotos");
    ensureTable($db, 'petlove_fotos', "
        CREATE TABLE `petlove_fotos` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `petlove_id` int(10) unsigned NOT NULL,
          `caminho` varchar(255) NOT NULL,
          `principal` tinyint(1) NOT NULL DEFAULT 0,
          `ordem` tinyint(3) unsigned NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`),
          KEY `idx_petlove` (`petlove_id`),
          CONSTRAINT `fk_petlove_fotos` FOREIGN KEY (`petlove_id`) REFERENCES `petlove_pets` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 8. petlove_interesses ────────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela petlove_interesses");
    ensureTable($db, 'petlove_interesses', "
        CREATE TABLE `petlove_interesses` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `petlove_id` int(10) unsigned NOT NULL,
          `interessado_id` int(11) NOT NULL,
          `pet_interessado_id` int(10) unsigned DEFAULT NULL,
          `mensagem` text DEFAULT NULL,
          `status` enum('pendente','aceito','recusado') NOT NULL DEFAULT 'pendente',
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_interesse` (`petlove_id`,`interessado_id`),
          KEY `idx_interessado` (`interessado_id`),
          KEY `idx_status` (`status`),
          KEY `fk_interesse_pet` (`pet_interessado_id`),
          CONSTRAINT `fk_interesse_pet` FOREIGN KEY (`pet_interessado_id`) REFERENCES `petlove_pets` (`id`) ON DELETE SET NULL,
          CONSTRAINT `fk_interesse_petlove` FOREIGN KEY (`petlove_id`) REFERENCES `petlove_pets` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_interesse_usuario` FOREIGN KEY (`interessado_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 9. conversas (chat interno — anúncios e Pet Love) ────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela conversas");
    ensureTable($db, 'conversas', "
        CREATE TABLE `conversas` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `tipo` enum('anuncio','petlove') NOT NULL DEFAULT 'anuncio',
          `referencia_id` int(10) unsigned NOT NULL,
          `usuario_dono_id` int(11) NOT NULL,
          `usuario_interessado_id` int(11) NOT NULL,
          `status` enum('aberta','resolvida','arquivada') NOT NULL DEFAULT 'aberta',
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `ultima_mensagem_em` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uniq_conversa` (`tipo`,`referencia_id`,`usuario_interessado_id`),
          KEY `idx_dono` (`usuario_dono_id`),
          KEY `idx_interessado` (`usuario_interessado_id`),
          KEY `idx_referencia` (`tipo`,`referencia_id`),
          CONSTRAINT `fk_conversa_dono` FOREIGN KEY (`usuario_dono_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_conversa_interessado` FOREIGN KEY (`usuario_interessado_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 10. conversa_mensagens ────────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela conversa_mensagens");
    ensureTable($db, 'conversa_mensagens', "
        CREATE TABLE `conversa_mensagens` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `conversa_id` int(10) unsigned NOT NULL,
          `remetente_id` int(11) NOT NULL,
          `mensagem` text NOT NULL,
          `lida` tinyint(1) NOT NULL DEFAULT 0,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_conversa_data` (`conversa_id`,`criado_em`),
          KEY `idx_remetente` (`remetente_id`),
          CONSTRAINT `fk_mensagem_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `conversas` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_mensagem_remetente` FOREIGN KEY (`remetente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 11. depoimentos (histórias de reencontro/adoção — prova social) ──
    out("[" . (++$passo) . "/$totalPassos] Tabela depoimentos");
    ensureTable($db, 'depoimentos', "
        CREATE TABLE `depoimentos` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `conversa_id` int(10) unsigned DEFAULT NULL,
          `usuario_id` int(11) NOT NULL,
          `anuncio_id` int(11) DEFAULT NULL,
          `texto` text NOT NULL,
          `aprovado` tinyint(1) NOT NULL DEFAULT 0,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_aprovado` (`aprovado`),
          KEY `idx_anuncio` (`anuncio_id`),
          CONSTRAINT `fk_depoimento_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_depoimento_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `conversas` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 12. conversa_mensagens: tipo (texto/imagem/localizacao) ─────────
    out("[" . (++$passo) . "/$totalPassos] Colunas de mídia em conversa_mensagens");
    $colunasMensagem = [
        'tipo'      => "enum('texto','imagem','localizacao') NOT NULL DEFAULT 'texto'",
        'arquivo'   => "varchar(255) DEFAULT NULL",
        'latitude'  => "decimal(10,8) DEFAULT NULL",
        'longitude' => "decimal(11,8) DEFAULT NULL",
    ];
    foreach ($colunasMensagem as $coluna => $definicao) {
        if (columnExists($db, 'conversa_mensagens', $coluna)) {
            out("  conversa_mensagens.$coluna já existe.");
            continue;
        }
        $db->query("ALTER TABLE `conversa_mensagens` ADD COLUMN `$coluna` $definicao");
        out("  conversa_mensagens.$coluna adicionada.");
    }
    out("");

    // ── 13. view_estatisticas (usada no dashboard admin e na home) ───────
    // CREATE OR REPLACE (idempotente) para que deploys já existentes também
    // recebam colunas novas, como petlove_matches.
    out("[" . (++$passo) . "/$totalPassos] View view_estatisticas");
    // Sem DEFINER explícito de propósito: em hospedagem compartilhada o
    // usuário do banco raramente tem privilégio para definir outro
    // DEFINER, então deixamos o MySQL usar o usuário que está criando.
    $db->query("
        CREATE OR REPLACE VIEW `view_estatisticas` AS
        SELECT
          (SELECT COUNT(*) FROM usuarios WHERE ativo = 1) AS usuarios_ativos,
          (SELECT COUNT(*) FROM anuncios WHERE status = 'ativo') AS anuncios_ativos,
          (SELECT COUNT(*) FROM anuncios WHERE tipo = 'perdido' AND status = 'ativo') AS perdidos_ativos,
          (SELECT COUNT(*) FROM anuncios WHERE tipo = 'encontrado' AND status = 'ativo') AS encontrados_ativos,
          (SELECT COUNT(*) FROM anuncios WHERE tipo = 'doacao' AND status = 'ativo') AS doacoes_ativas,
          (SELECT COUNT(*) FROM anuncios WHERE status = 'resolvido') AS casos_resolvidos,
          (SELECT COUNT(*) FROM petlove_interesses WHERE status = 'aceito') AS petlove_matches,
          (SELECT COALESCE(SUM(valor), 0) FROM doacoes WHERE status = 'aprovada') AS total_doacoes,
          (SELECT COALESCE(SUM(valor), 0) FROM doacoes WHERE status = 'aprovada'
             AND MONTH(data_doacao) = MONTH(CURDATE()) AND YEAR(data_doacao) = YEAR(CURDATE())) AS doacoes_mes_atual,
          (SELECT COUNT(*) FROM doacoes WHERE status = 'aprovada') AS total_doacoes_count,
          (SELECT COUNT(*) FROM doacoes WHERE status = 'pendente') AS doacoes_pendentes
    ");
    out("  criada/atualizada com sucesso.");
    out("");

    // ── 14. triagem_locais_publicos (clínicas municipais/institucionais) ─
    out("[" . (++$passo) . "/$totalPassos] Tabela triagem_locais_publicos");
    ensureTable($db, 'triagem_locais_publicos', "
        CREATE TABLE `triagem_locais_publicos` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `nome` varchar(150) NOT NULL,
          `cidade` varchar(100) NOT NULL,
          `estado` char(2) NOT NULL,
          `endereco` varchar(255) DEFAULT NULL,
          `latitude` decimal(10,7) DEFAULT NULL,
          `longitude` decimal(10,7) DEFAULT NULL,
          `horario_funcionamento` varchar(255) DEFAULT NULL,
          `como_funciona_fila` text DEFAULT NULL,
          `requisitos` varchar(500) DEFAULT NULL,
          `telefone` varchar(20) DEFAULT NULL,
          `ativo` tinyint(1) NOT NULL DEFAULT 1,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_cidade_estado` (`cidade`,`estado`),
          KEY `idx_ativo` (`ativo`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 15. triagem_solicitacoes ─────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela triagem_solicitacoes");
    ensureTable($db, 'triagem_solicitacoes', "
        CREATE TABLE `triagem_solicitacoes` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `usuario_id` int(11) DEFAULT NULL,
          `nome_contato` varchar(150) DEFAULT NULL,
          `telefone_contato` varchar(20) DEFAULT NULL,
          `especie` varchar(30) NOT NULL,
          `sintomas` text NOT NULL COMMENT 'JSON: respostas estruturadas do formulario',
          `nivel_urgencia` enum('baixa','moderada','alta','critica') NOT NULL,
          `renda_baixa_declarada` tinyint(1) DEFAULT NULL,
          `cidade` varchar(100) DEFAULT NULL,
          `estado` char(2) DEFAULT NULL,
          `latitude` decimal(10,7) DEFAULT NULL,
          `longitude` decimal(10,7) DEFAULT NULL,
          `direcionamento_sugerido` enum('publico','parceiro_privado','ambos','emergencia_imediata') NOT NULL,
          `triagem_locais_publicos_id` int(10) unsigned DEFAULT NULL,
          `parceiro_perfil_id` int(11) DEFAULT NULL,
          `conversa_id` int(10) unsigned DEFAULT NULL,
          `status` enum('orientado','em_contato','encerrado','abandonado') NOT NULL DEFAULT 'orientado',
          `disclaimer_aceito` tinyint(1) NOT NULL DEFAULT 0,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_usuario` (`usuario_id`),
          KEY `idx_parceiro_status` (`parceiro_perfil_id`,`status`),
          KEY `idx_urgencia_data` (`nivel_urgencia`,`criado_em`),
          CONSTRAINT `fk_triagem_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
          CONSTRAINT `fk_triagem_local_publico` FOREIGN KEY (`triagem_locais_publicos_id`) REFERENCES `triagem_locais_publicos` (`id`) ON DELETE SET NULL,
          CONSTRAINT `fk_triagem_parceiro` FOREIGN KEY (`parceiro_perfil_id`) REFERENCES `parceiro_perfis` (`id`) ON DELETE SET NULL,
          CONSTRAINT `fk_triagem_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `conversas` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 16. triagem_arrecadacao_futura (reservada, sem uso no MVP) ──────
    out("[" . (++$passo) . "/$totalPassos] Tabela triagem_arrecadacao_futura");
    ensureTable($db, 'triagem_arrecadacao_futura', "
        CREATE TABLE `triagem_arrecadacao_futura` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `triagem_solicitacao_id` int(10) unsigned NOT NULL,
          `doacao_id` int(11) DEFAULT NULL,
          `valor_estimado_necessario` decimal(10,2) DEFAULT NULL,
          `status` enum('nao_iniciado','arrecadando','concluido') NOT NULL DEFAULT 'nao_iniciado',
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_solicitacao` (`triagem_solicitacao_id`),
          CONSTRAINT `fk_arrecadacao_solicitacao` FOREIGN KEY (`triagem_solicitacao_id`) REFERENCES `triagem_solicitacoes` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_arrecadacao_doacao` FOREIGN KEY (`doacao_id`) REFERENCES `doacoes` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 17. conversas.tipo precisa aceitar 'triagem' ────────────────────
    out("[" . (++$passo) . "/$totalPassos] conversas.tipo aceita 'triagem'");
    $db->query("ALTER TABLE `conversas` MODIFY COLUMN `tipo` enum('anuncio','petlove','triagem') NOT NULL DEFAULT 'anuncio'");
    out("  conversas.tipo atualizado.");
    out("");

    // ── Seed: Clínica de Bem-Estar Animal Municipal (Porto Velho/RO) ────
    out("Seed: Clínica de Bem-Estar Animal Municipal (Porto Velho/RO)");
    $existe = $db->fetchOne(
        "SELECT id FROM triagem_locais_publicos WHERE nome = ? AND cidade = ?",
        ['Clínica de Bem-Estar Animal Municipal', 'Porto Velho']
    );
    if ($existe) {
        out("  já cadastrada.");
    } else {
        $db->insert('triagem_locais_publicos', [
            'nome' => 'Clínica de Bem-Estar Animal Municipal',
            'cidade' => 'Porto Velho',
            'estado' => 'RO',
            'endereco' => 'Av. Mamoré, nº 1120, Lagoinha (junto ao CCZ)',
            'horario_funcionamento' => 'Segunda a sexta-feira, das 8h às 17h',
            'como_funciona_fila' => 'Distribuição de senhas por volta das 7h30 da manhã, em ordem de chegada. Quantidade diária limitada — recomendado chegar cedo.',
            'requisitos' => 'Famílias de baixa renda (CadÚnico), protetores de animais e pets de rua',
            'ativo' => 1,
        ]);
        out("  cadastrada com sucesso.");
    }
    out("");

    // ── 18. foto_embeddings (assinatura visual das fotos p/ matching) ───
    out("[" . (++$passo) . "/$totalPassos] Tabela foto_embeddings");
    ensureTable($db, 'foto_embeddings', "
        CREATE TABLE `foto_embeddings` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `foto_id` int(11) NOT NULL,
          `anuncio_id` int(11) NOT NULL,
          `provedor` varchar(30) NOT NULL DEFAULT 'phash_local',
          `status` enum('pendente','processado','falha') NOT NULL DEFAULT 'pendente',
          `hash_perceptual` varchar(64) DEFAULT NULL,
          `vetor` longtext DEFAULT NULL COMMENT 'Reservado para provedor pago futuro (embedding vetorial em JSON)',
          `tentativas` tinyint(3) unsigned NOT NULL DEFAULT 0,
          `erro_mensagem` varchar(500) DEFAULT NULL,
          `processado_em` datetime DEFAULT NULL,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_status_tentativas` (`status`,`tentativas`),
          KEY `idx_anuncio` (`anuncio_id`),
          CONSTRAINT `fk_embedding_foto` FOREIGN KEY (`foto_id`) REFERENCES `fotos_anuncios` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_embedding_anuncio` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 19. anuncio_matches ──────────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela anuncio_matches");
    ensureTable($db, 'anuncio_matches', "
        CREATE TABLE `anuncio_matches` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `anuncio_perdido_id` int(11) NOT NULL,
          `anuncio_achado_id` int(11) NOT NULL,
          `score_total` decimal(5,2) NOT NULL,
          `score_visual` decimal(5,2) DEFAULT NULL,
          `score_geo` decimal(5,2) DEFAULT NULL,
          `score_atributos` decimal(5,2) DEFAULT NULL,
          `score_tempo` decimal(5,2) DEFAULT NULL,
          `distancia_km` decimal(8,2) DEFAULT NULL,
          `dias_diferenca` int(11) DEFAULT NULL,
          `status` enum('pendente','notificado','confirmado','rejeitado','expirado') NOT NULL DEFAULT 'pendente',
          `conversa_id` int(10) unsigned DEFAULT NULL,
          `confirmado_por_usuario_id` int(11) DEFAULT NULL,
          `notificado_em` datetime DEFAULT NULL,
          `resolvido_em` datetime DEFAULT NULL,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_par` (`anuncio_perdido_id`,`anuncio_achado_id`),
          KEY `idx_status` (`status`),
          KEY `idx_perdido` (`anuncio_perdido_id`),
          KEY `idx_achado` (`anuncio_achado_id`),
          CONSTRAINT `fk_match_perdido` FOREIGN KEY (`anuncio_perdido_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_match_achado` FOREIGN KEY (`anuncio_achado_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_match_conversa` FOREIGN KEY (`conversa_id`) REFERENCES `conversas` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 20. anuncios.matching_processado_em + índice ────────────────────
    out("[" . (++$passo) . "/$totalPassos] Coluna anuncios.matching_processado_em");
    if (columnExists($db, 'anuncios', 'matching_processado_em')) {
        out("  anuncios.matching_processado_em já existe.");
    } else {
        $db->query("ALTER TABLE `anuncios` ADD COLUMN `matching_processado_em` DATETIME NULL DEFAULT NULL AFTER `moderacao_status`");
        out("  anuncios.matching_processado_em adicionada.");
    }
    $idxExiste = $db->fetchOne(
        "SELECT COUNT(*) AS n FROM information_schema.statistics
         WHERE table_schema = DATABASE() AND table_name = 'anuncios' AND index_name = 'idx_anuncios_matching'",
        []
    );
    if ((int)($idxExiste['n'] ?? 0) > 0) {
        out("  índice idx_anuncios_matching já existe.");
    } else {
        $db->query("CREATE INDEX `idx_anuncios_matching` ON `anuncios` (`tipo`, `status`, `matching_processado_em`)");
        out("  índice idx_anuncios_matching criado.");
    }
    out("");

    // ── 21. conversas.tipo precisa aceitar 'match' ───────────────────────
    out("[" . (++$passo) . "/$totalPassos] conversas.tipo aceita 'match'");
    $db->query("ALTER TABLE `conversas` MODIFY COLUMN `tipo` enum('anuncio','petlove','triagem','match') NOT NULL DEFAULT 'anuncio'");
    out("  conversas.tipo atualizado.");
    out("");

    // ── 22. api_consumidores ─────────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela api_consumidores");
    ensureTable($db, 'api_consumidores', "
        CREATE TABLE `api_consumidores` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `nome` varchar(150) NOT NULL,
          `email_contato` varchar(150) DEFAULT NULL,
          `descricao` varchar(500) DEFAULT NULL,
          `ativo` tinyint(1) NOT NULL DEFAULT 1,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 23. api_keys ─────────────────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela api_keys");
    ensureTable($db, 'api_keys', "
        CREATE TABLE `api_keys` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `consumidor_id` int(10) unsigned NOT NULL,
          `chave_hash` char(64) NOT NULL,
          `prefixo` varchar(12) NOT NULL,
          `escopos` set('anuncios_leitura','parceiros_leitura','ingestao_denuncias','ingestao_animais') NOT NULL DEFAULT 'anuncios_leitura,parceiros_leitura',
          `rate_limit_por_minuto` int(10) unsigned NOT NULL DEFAULT 60,
          `ativo` tinyint(1) NOT NULL DEFAULT 1,
          `ultimo_uso_em` datetime DEFAULT NULL,
          `expira_em` datetime DEFAULT NULL,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `revogada_em` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_hash` (`chave_hash`),
          KEY `idx_prefixo` (`prefixo`),
          CONSTRAINT `fk_apikey_consumidor` FOREIGN KEY (`consumidor_id`) REFERENCES `api_consumidores` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 24. api_rate_limit_janelas ───────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela api_rate_limit_janelas");
    ensureTable($db, 'api_rate_limit_janelas', "
        CREATE TABLE `api_rate_limit_janelas` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `api_key_id` int(10) unsigned NOT NULL,
          `janela_inicio` datetime NOT NULL,
          `contador` int(10) unsigned NOT NULL DEFAULT 0,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_key_janela` (`api_key_id`,`janela_inicio`),
          CONSTRAINT `fk_ratelimit_apikey` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 25. api_requisicoes_log ──────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela api_requisicoes_log");
    ensureTable($db, 'api_requisicoes_log', "
        CREATE TABLE `api_requisicoes_log` (
          `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
          `api_key_id` int(10) unsigned DEFAULT NULL,
          `endpoint` varchar(150) NOT NULL,
          `metodo` varchar(10) NOT NULL,
          `status_http` smallint(5) unsigned NOT NULL,
          `ip` varchar(45) DEFAULT NULL,
          `tempo_ms` int(10) unsigned DEFAULT NULL,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_key_data` (`api_key_id`,`criado_em`),
          KEY `idx_data` (`criado_em`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 26. api_ingestao_animais ─────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela api_ingestao_animais");
    ensureTable($db, 'api_ingestao_animais', "
        CREATE TABLE `api_ingestao_animais` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `api_key_id` int(10) unsigned NOT NULL,
          `payload_json` longtext NOT NULL,
          `status` enum('pendente_revisao','aprovado','rejeitado') NOT NULL DEFAULT 'pendente_revisao',
          `anuncio_id` int(11) DEFAULT NULL,
          `revisado_por` int(11) DEFAULT NULL,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_status` (`status`),
          CONSTRAINT `fk_ingestao_apikey` FOREIGN KEY (`api_key_id`) REFERENCES `api_keys` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_ingestao_anuncio` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 27. Feature flag do endpoint de ingestão (desabilitado por padrão) ─
    out("[" . (++$passo) . "/$totalPassos] Config api_ingestao_animais_ativa");
    $configExiste = $db->fetchOne("SELECT chave FROM configuracoes WHERE chave = ?", ['api_ingestao_animais_ativa']);
    if ($configExiste) {
        out("  já existe.");
    } else {
        $db->insert('configuracoes', [
            'chave' => 'api_ingestao_animais_ativa',
            'valor' => '0',
            'descricao' => 'Habilita o endpoint POST /api/v1/ingestao/animais (ingestão de terceiros, ex. prefeitura/CCZ)',
        ]);
        out("  criada com valor padrão '0' (desabilitado).");
    }
    out("");

    // ── 28. pets (ficha permanente — fundação do módulo veterinário) ────
    out("[" . (++$passo) . "/$totalPassos] Tabela pets");
    ensureTable($db, 'pets', "
        CREATE TABLE `pets` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `tutor_usuario_id` int(11) NOT NULL,
          `nome` varchar(100) NOT NULL,
          `especie` varchar(30) NOT NULL,
          `raca` varchar(100) DEFAULT NULL,
          `sexo` enum('macho','femea') DEFAULT NULL,
          `data_nascimento` date DEFAULT NULL,
          `idade_aproximada_meses` smallint(5) unsigned DEFAULT NULL,
          `cor` varchar(50) DEFAULT NULL,
          `foto` varchar(255) DEFAULT NULL,
          `microchip_numero` varchar(50) DEFAULT NULL,
          `origem_anuncio_id` int(11) DEFAULT NULL,
          `ativo` tinyint(1) NOT NULL DEFAULT 1,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `atualizado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_tutor` (`tutor_usuario_id`),
          CONSTRAINT `fk_pet_tutor` FOREIGN KEY (`tutor_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_pet_origem_anuncio` FOREIGN KEY (`origem_anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 29. documentos (núcleo genérico de documento assinável) ─────────
    out("[" . (++$passo) . "/$totalPassos] Tabela documentos");
    ensureTable($db, 'documentos', "
        CREATE TABLE `documentos` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `tipo` enum('laudo','atestado','receituario','termo_adocao','termo_responsabilidade') NOT NULL,
          `referencia_tipo` enum('atendimento','anuncio') NOT NULL,
          `referencia_id` int(10) unsigned NOT NULL,
          `conteudo_html` longtext NOT NULL,
          `pdf_path` varchar(255) DEFAULT NULL,
          `hash_conteudo` char(64) NOT NULL,
          `status` enum('rascunho','aguardando_assinaturas','assinado','revogado') NOT NULL DEFAULT 'rascunho',
          `codigo_verificacao` varchar(20) NOT NULL,
          `criado_por_usuario_id` int(11) NOT NULL,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `revogado_em` datetime DEFAULT NULL,
          `motivo_revogacao` varchar(500) DEFAULT NULL,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_codigo_verificacao` (`codigo_verificacao`),
          KEY `idx_referencia` (`referencia_tipo`,`referencia_id`),
          KEY `idx_tipo_status` (`tipo`,`status`),
          CONSTRAINT `fk_documento_criador` FOREIGN KEY (`criado_por_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 30. documento_assinaturas (trilha de auditoria multi-signatário) ─
    out("[" . (++$passo) . "/$totalPassos] Tabela documento_assinaturas");
    ensureTable($db, 'documento_assinaturas', "
        CREATE TABLE `documento_assinaturas` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `documento_id` int(10) unsigned NOT NULL,
          `usuario_id` int(11) NOT NULL,
          `papel` enum('veterinario_autor','adotante_responsavel','doador','testemunha_parceiro') NOT NULL,
          `identificacao_extra` varchar(100) DEFAULT NULL,
          `hash_no_momento` char(64) NOT NULL,
          `ip_address` varchar(45) DEFAULT NULL,
          `user_agent` varchar(255) DEFAULT NULL,
          `assinado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_documento` (`documento_id`),
          CONSTRAINT `fk_assinatura_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_assinatura_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 31. termos_adocao ────────────────────────────────────────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela termos_adocao");
    ensureTable($db, 'termos_adocao', "
        CREATE TABLE `termos_adocao` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `anuncio_id` int(11) NOT NULL,
          `pet_id` int(10) unsigned DEFAULT NULL,
          `documento_id` int(10) unsigned NOT NULL,
          `doador_usuario_id` int(11) NOT NULL,
          `adotante_usuario_id` int(11) DEFAULT NULL,
          `adotante_nome_informado` varchar(150) DEFAULT NULL,
          `adotante_telefone_informado` varchar(20) DEFAULT NULL,
          `parceiro_testemunha_id` int(11) DEFAULT NULL,
          `status` enum('aguardando_adotante','assinado','recusado','expirado') NOT NULL DEFAULT 'aguardando_adotante',
          `expira_em` datetime DEFAULT NULL,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          KEY `idx_anuncio` (`anuncio_id`),
          KEY `idx_adotante` (`adotante_usuario_id`),
          KEY `idx_status` (`status`),
          CONSTRAINT `fk_termo_anuncio` FOREIGN KEY (`anuncio_id`) REFERENCES `anuncios` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_termo_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE SET NULL,
          CONSTRAINT `fk_termo_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_termo_doador` FOREIGN KEY (`doador_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_termo_adotante` FOREIGN KEY (`adotante_usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
          CONSTRAINT `fk_termo_parceiro` FOREIGN KEY (`parceiro_testemunha_id`) REFERENCES `parceiro_perfis` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 32. parceiro_veterinarios (CRMV validado por profissional) ──────
    out("[" . (++$passo) . "/$totalPassos] Tabela parceiro_veterinarios");
    ensureTable($db, 'parceiro_veterinarios', "
        CREATE TABLE `parceiro_veterinarios` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `parceiro_perfil_id` int(11) NOT NULL,
          `usuario_id` int(11) NOT NULL,
          `nome_completo` varchar(150) NOT NULL,
          `crmv_numero` varchar(20) NOT NULL,
          `crmv_uf` char(2) NOT NULL,
          `status` enum('pendente_validacao','aprovado','rejeitado','suspenso') NOT NULL DEFAULT 'pendente_validacao',
          `validado_por` int(11) DEFAULT NULL,
          `validado_em` datetime DEFAULT NULL,
          `motivo_rejeicao` varchar(500) DEFAULT NULL,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_crmv` (`crmv_numero`,`crmv_uf`),
          KEY `idx_status` (`status`),
          KEY `idx_parceiro` (`parceiro_perfil_id`),
          CONSTRAINT `fk_vet_parceiro` FOREIGN KEY (`parceiro_perfil_id`) REFERENCES `parceiro_perfis` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_vet_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_vet_validado_por` FOREIGN KEY (`validado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 33. atendimentos (consulta veterinária presencial) ──────────────
    out("[" . (++$passo) . "/$totalPassos] Tabela atendimentos");
    ensureTable($db, 'atendimentos', "
        CREATE TABLE `atendimentos` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `pet_id` int(10) unsigned NOT NULL,
          `parceiro_perfil_id` int(11) NOT NULL,
          `veterinario_id` int(10) unsigned NOT NULL,
          `triagem_solicitacao_id` int(10) unsigned DEFAULT NULL,
          `motivo_consulta` varchar(255) NOT NULL,
          `anamnese` text DEFAULT NULL,
          `peso_kg` decimal(5,2) DEFAULT NULL,
          `temperatura_c` decimal(4,1) DEFAULT NULL,
          `frequencia_cardiaca_bpm` smallint(5) unsigned DEFAULT NULL,
          `frequencia_respiratoria_mpm` smallint(5) unsigned DEFAULT NULL,
          `mucosas` varchar(100) DEFAULT NULL,
          `grau_hidratacao` varchar(50) DEFAULT NULL,
          `exame_fisico` text DEFAULT NULL,
          `diagnostico` text DEFAULT NULL,
          `conduta` text DEFAULT NULL,
          `vacinas_aplicadas` text DEFAULT NULL,
          `medicamentos_prescritos` text DEFAULT NULL,
          `proxima_consulta_recomendada` date DEFAULT NULL,
          `status` enum('em_andamento','finalizado','cancelado') NOT NULL DEFAULT 'em_andamento',
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          `finalizado_em` datetime DEFAULT NULL,
          PRIMARY KEY (`id`),
          KEY `idx_pet` (`pet_id`),
          KEY `idx_parceiro` (`parceiro_perfil_id`),
          KEY `idx_veterinario` (`veterinario_id`),
          CONSTRAINT `fk_atendimento_pet` FOREIGN KEY (`pet_id`) REFERENCES `pets` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_atendimento_parceiro` FOREIGN KEY (`parceiro_perfil_id`) REFERENCES `parceiro_perfis` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_atendimento_veterinario` FOREIGN KEY (`veterinario_id`) REFERENCES `parceiro_veterinarios` (`id`) ON DELETE RESTRICT,
          CONSTRAINT `fk_atendimento_triagem` FOREIGN KEY (`triagem_solicitacao_id`) REFERENCES `triagem_solicitacoes` (`id`) ON DELETE SET NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 34. laudos (vínculo entre atendimento e documento assinável) ────
    out("[" . (++$passo) . "/$totalPassos] Tabela laudos");
    ensureTable($db, 'laudos', "
        CREATE TABLE `laudos` (
          `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
          `atendimento_id` int(10) unsigned NOT NULL,
          `documento_id` int(10) unsigned NOT NULL,
          `criado_em` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
          PRIMARY KEY (`id`),
          UNIQUE KEY `uq_atendimento_documento` (`atendimento_id`,`documento_id`),
          KEY `idx_atendimento` (`atendimento_id`),
          CONSTRAINT `fk_laudo_atendimento` FOREIGN KEY (`atendimento_id`) REFERENCES `atendimentos` (`id`) ON DELETE CASCADE,
          CONSTRAINT `fk_laudo_documento` FOREIGN KEY (`documento_id`) REFERENCES `documentos` (`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    out("");

    // ── 35. documentos.retifica_documento_id (correção de documento assinado) ─
    out("[" . (++$passo) . "/$totalPassos] Coluna documentos.retifica_documento_id");
    if (columnExists($db, 'documentos', 'retifica_documento_id')) {
        out("  documentos.retifica_documento_id já existe.");
    } else {
        $db->query("ALTER TABLE `documentos` ADD COLUMN `retifica_documento_id` int(10) unsigned NULL DEFAULT NULL AFTER `criado_por_usuario_id`");
        out("  documentos.retifica_documento_id adicionada.");
    }
    $fkExiste = $db->fetchOne(
        "SELECT COUNT(*) AS n FROM information_schema.table_constraints
         WHERE table_schema = DATABASE() AND table_name = 'documentos' AND constraint_name = 'fk_documento_retifica'"
    );
    if ((int)($fkExiste['n'] ?? 0) > 0) {
        out("  fk_documento_retifica já existe.");
    } else {
        $db->query("ALTER TABLE `documentos` ADD CONSTRAINT `fk_documento_retifica` FOREIGN KEY (`retifica_documento_id`) REFERENCES `documentos` (`id`) ON DELETE SET NULL");
        out("  fk_documento_retifica criada.");
    }
    out("");

    // ── 36. atendimentos.exames_solicitados (solicitação de exames) ─────
    out("[" . (++$passo) . "/$totalPassos] Coluna atendimentos.exames_solicitados");
    if (columnExists($db, 'atendimentos', 'exames_solicitados')) {
        out("  atendimentos.exames_solicitados já existe.");
    } else {
        $db->query("ALTER TABLE `atendimentos` ADD COLUMN `exames_solicitados` text NULL DEFAULT NULL AFTER `medicamentos_prescritos`");
        out("  atendimentos.exames_solicitados adicionada.");
    }
    out("");

    // ── 37. documentos.tipo aceita 'pedido_exame' ───────────────────────
    out("[" . (++$passo) . "/$totalPassos] documentos.tipo aceita 'pedido_exame'");
    $db->query("ALTER TABLE `documentos` MODIFY COLUMN `tipo` enum('laudo','atestado','receituario','pedido_exame','termo_adocao','termo_responsabilidade') NOT NULL");
    out("  documentos.tipo atualizado.");
    out("");

    // ── 38. atendimentos.tipo_atendimento (consulta/vacinacao/exame/retorno) ─
    out("[" . (++$passo) . "/$totalPassos] Coluna atendimentos.tipo_atendimento");
    if (columnExists($db, 'atendimentos', 'tipo_atendimento')) {
        out("  atendimentos.tipo_atendimento já existe.");
    } else {
        $db->query("ALTER TABLE `atendimentos` ADD COLUMN `tipo_atendimento` enum('consulta','vacinacao','exame','retorno') NOT NULL DEFAULT 'consulta' AFTER `motivo_consulta`");
        out("  atendimentos.tipo_atendimento adicionada.");
    }
    out("");

    out("=== Migration concluída com sucesso. ===");
} catch (Throwable $e) {
    out("");
    out("!!! ERRO: " . $e->getMessage());
    out("Nenhuma alteração parcial é destrutiva — pode corrigir o problema e rodar de novo.");
    exit(1);
}
