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
    $totalPassos = 9;

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

    // ── 9. view_estatisticas (usada no dashboard admin e na home) ───────
    out("[" . (++$passo) . "/$totalPassos] View view_estatisticas");
    if (viewExists($db, 'view_estatisticas')) {
        out("  já existe, nada a fazer.");
    } else {
        // Sem DEFINER explícito de propósito: em hospedagem compartilhada o
        // usuário do banco raramente tem privilégio para definir outro
        // DEFINER, então deixamos o MySQL usar o usuário que está criando.
        $db->query("
            CREATE VIEW `view_estatisticas` AS
            SELECT
              (SELECT COUNT(*) FROM usuarios WHERE ativo = 1) AS usuarios_ativos,
              (SELECT COUNT(*) FROM anuncios WHERE status = 'ativo') AS anuncios_ativos,
              (SELECT COUNT(*) FROM anuncios WHERE tipo = 'perdido' AND status = 'ativo') AS perdidos_ativos,
              (SELECT COUNT(*) FROM anuncios WHERE tipo = 'encontrado' AND status = 'ativo') AS encontrados_ativos,
              (SELECT COUNT(*) FROM anuncios WHERE tipo = 'doacao' AND status = 'ativo') AS doacoes_ativas,
              (SELECT COUNT(*) FROM anuncios WHERE status = 'resolvido') AS casos_resolvidos,
              (SELECT COALESCE(SUM(valor), 0) FROM doacoes WHERE status = 'aprovada') AS total_doacoes,
              (SELECT COALESCE(SUM(valor), 0) FROM doacoes WHERE status = 'aprovada'
                 AND MONTH(data_doacao) = MONTH(CURDATE()) AND YEAR(data_doacao) = YEAR(CURDATE())) AS doacoes_mes_atual,
              (SELECT COUNT(*) FROM doacoes WHERE status = 'aprovada') AS total_doacoes_count,
              (SELECT COUNT(*) FROM doacoes WHERE status = 'pendente') AS doacoes_pendentes
        ");
        out("  criada com sucesso.");
    }
    out("");

    out("=== Migration concluída com sucesso. ===");
} catch (Throwable $e) {
    out("");
    out("!!! ERRO: " . $e->getMessage());
    out("Nenhuma alteração parcial é destrutiva — pode corrigir o problema e rodar de novo.");
    exit(1);
}
