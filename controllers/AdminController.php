<?php

class AdminController
{
    private $db;

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    // ─────────────────────────────────────────────
    // DASHBOARD
    // ─────────────────────────────────────────────

    public function getDashboardData(): array
    {
        $stats = $this->db->fetchOne('SELECT * FROM view_estatisticas') ?: [];

        $hoje = $this->db->fetchOne("
            SELECT
                (SELECT COUNT(*) FROM usuarios WHERE DATE(data_cadastro)   = CURDATE()) AS usuarios_hoje,
                (SELECT COUNT(*) FROM anuncios WHERE DATE(data_publicacao) = CURDATE()) AS anuncios_hoje,
                (SELECT COUNT(*) FROM anuncios WHERE DATE(resolvido_em)    = CURDATE()) AS reunioes_hoje,
                (SELECT COUNT(*) FROM anuncios WHERE moderacao_status = 'pendente')     AS pendentes_moderacao
        ") ?: [];

        $mes = $this->db->fetchOne("
            SELECT
                (SELECT COUNT(*) FROM usuarios
                  WHERE MONTH(data_cadastro) = MONTH(NOW()) AND YEAR(data_cadastro) = YEAR(NOW())) AS usuarios_mes,
                (SELECT COUNT(*) FROM anuncios
                  WHERE status = 'resolvido'
                    AND MONTH(resolvido_em) = MONTH(NOW()) AND YEAR(resolvido_em) = YEAR(NOW()))    AS reunioes_mes
        ") ?: [];

        $ultimosUsuarios = $this->db->fetchAll("
            SELECT id, nome, email, data_cadastro, ativo
            FROM usuarios ORDER BY data_cadastro DESC LIMIT 5
        ");

        $ultimosAnuncios = $this->db->fetchAll("
            SELECT a.id, a.nome_pet, a.especie, a.tipo, a.status, a.cidade, a.estado,
                   a.moderacao_status, a.data_publicacao, u.nome AS autor
            FROM anuncios a
            JOIN usuarios u ON a.usuario_id = u.id
            ORDER BY a.data_publicacao DESC LIMIT 8
        ");

        $ultimasDoacoes = $this->db->fetchAll("
            SELECT d.id, d.valor, d.status, d.data_doacao,
                   COALESCE(NULLIF(TRIM(d.nome_doador),''), u.nome, 'Anônimo') AS doador,
                   d.email_doador,
                   d.metodo_pagamento
            FROM doacoes d
            LEFT JOIN usuarios u ON d.usuario_id = u.id
            ORDER BY d.data_doacao DESC LIMIT 8
        ");

        return compact('stats', 'hoje', 'mes', 'ultimosUsuarios', 'ultimosAnuncios', 'ultimasDoacoes');
    }

    // ─────────────────────────────────────────────
    // ANÚNCIOS
    // ─────────────────────────────────────────────

    public function listarAnuncios(array $filtros, int $pagina = 1, int $porPagina = 25): array
    {
        $where  = [];
        $params = [];

        $filtroStatus = $filtros['status'] ?? 'todos';
        $filtroTipo   = $filtros['tipo']   ?? '';
        $filtroBusca  = trim($filtros['busca'] ?? '');

        if ($filtroStatus !== 'todos') {
            $where[]  = 'a.status = ?';
            $params[] = $filtroStatus;
        }
        if ($filtroTipo !== '') {
            $where[]  = 'a.tipo = ?';
            $params[] = $filtroTipo;
        }
        if ($filtroBusca !== '') {
            $where[]  = '(a.nome_pet LIKE ? OR a.cidade LIKE ? OR u.nome LIKE ? OR u.email LIKE ?)';
            $like     = '%' . $filtroBusca . '%';
            $params   = array_merge($params, [$like, $like, $like, $like]);
        }

        $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';
        $offset   = ($pagina - 1) * $porPagina;

        $total = (int)($this->db->fetchOne("
            SELECT COUNT(*) AS total FROM anuncios a JOIN usuarios u ON a.usuario_id = u.id $whereSQL
        ", $params)['total'] ?? 0);

        $anuncios = $this->db->fetchAll("
            SELECT a.id, a.nome_pet, a.tipo, a.especie, a.status, a.moderacao_status,
                   a.cidade, a.estado, a.data_publicacao, a.data_expiracao, a.visualizacoes,
                   u.nome AS autor_nome, u.email AS autor_email,
                   (SELECT nome_arquivo FROM fotos_anuncios WHERE anuncio_id = a.id ORDER BY ordem LIMIT 1) AS foto
            FROM anuncios a
            JOIN usuarios u ON a.usuario_id = u.id
            $whereSQL
            ORDER BY a.data_publicacao DESC
            LIMIT $porPagina OFFSET $offset
        ", $params);

        $contagens = $this->db->fetchOne("
            SELECT COUNT(*)              AS total,
                   SUM(status='ativo')   AS ativos,
                   SUM(status='inativo') AS inativos,
                   SUM(status='resolvido') AS resolvidos,
                   SUM(status='expirado')  AS expirados
            FROM anuncios
        ") ?: [];

        $totalPaginas = max(1, (int)ceil($total / $porPagina));

        return compact('anuncios', 'total', 'contagens', 'totalPaginas');
    }

    public function processarAcaoAnuncio(int $anuncioId, string $acao): void
    {
        $dadosAntes = $this->db->fetchOne('SELECT * FROM anuncios WHERE id = ?', [$anuncioId]) ?: [];

        if ($acao === 'desativar') {
            $this->db->update('anuncios', ['status' => STATUS_INATIVO], 'id = ?', [$anuncioId]);
            auditLog('desativar_anuncio', 'anuncios', $anuncioId, ['status' => $dadosAntes['status'] ?? null], ['status' => STATUS_INATIVO]);
            setFlashMessage('Anúncio desativado com sucesso.', MSG_SUCCESS);
        } elseif ($acao === 'ativar') {
            $this->db->update('anuncios', ['status' => STATUS_ATIVO], 'id = ?', [$anuncioId]);
            auditLog('ativar_anuncio', 'anuncios', $anuncioId, ['status' => $dadosAntes['status'] ?? null], ['status' => STATUS_ATIVO]);
            setFlashMessage('Anúncio reativado com sucesso.', MSG_SUCCESS);
        } elseif ($acao === 'excluir') {
            auditLog('excluir_anuncio', 'anuncios', $anuncioId, $dadosAntes, null);
            $fotos = $this->db->fetchAll(
                'SELECT nome_arquivo FROM fotos_anuncios WHERE anuncio_id = ?', [$anuncioId]
            );
            foreach ($fotos as $foto) {
                $caminho = UPLOAD_PATH . '/anuncios/' . $foto['nome_arquivo'];
                if (file_exists($caminho)) @unlink($caminho);
            }
            $this->db->delete('fotos_anuncios', 'anuncio_id = ?', [$anuncioId]);
            $this->db->delete('anuncios',       'id = ?',         [$anuncioId]);
            setFlashMessage('Anúncio excluído permanentemente.', MSG_SUCCESS);
        }
    }

    // ─────────────────────────────────────────────
    // MODERAÇÃO
    // ─────────────────────────────────────────────

    public function listarFilaModeracaoAnuncios(string $filtro = 'pendente'): array
    {
        $filtrosValidos = ['pendente', 'aprovado', 'rejeitado'];
        if (!in_array($filtro, $filtrosValidos, true)) $filtro = 'pendente';

        $anuncios = $this->db->fetchAll("
            SELECT a.*, u.nome AS autor_nome, u.email AS autor_email,
                   (SELECT nome_arquivo FROM fotos_anuncios WHERE anuncio_id = a.id ORDER BY ordem LIMIT 1) AS foto
            FROM anuncios a JOIN usuarios u ON a.usuario_id = u.id
            WHERE a.moderacao_status = ?
            ORDER BY a.data_publicacao DESC LIMIT 100
        ", [$filtro]);

        $contagens = $this->db->fetchOne("
            SELECT SUM(moderacao_status='pendente')  AS pendentes,
                   SUM(moderacao_status='aprovado')  AS aprovados,
                   SUM(moderacao_status='rejeitado') AS rejeitados
            FROM anuncios
        ") ?: [];

        return compact('anuncios', 'contagens');
    }

    public function processarModeracaoAnuncio(int $anuncioId, string $acao, string $motivo = ''): void
    {
        if (!in_array($acao, ['aprovar', 'rejeitar'], true) || $anuncioId <= 0) return;

        $dadosAntes = $this->db->fetchOne(
            'SELECT moderacao_status, status FROM anuncios WHERE id = ?', [$anuncioId]
        ) ?: [];

        $novoStatus = $acao === 'aprovar' ? 'aprovado' : 'rejeitado';
        $this->db->update('anuncios', [
            'moderacao_status' => $novoStatus,
            'moderacao_motivo' => $motivo ?: null,
            'status'           => $acao === 'rejeitar' ? STATUS_INATIVO : STATUS_ATIVO,
        ], 'id = ?', [$anuncioId]);
        auditLog("moderacao_{$acao}", 'anuncios', $anuncioId, $dadosAntes, ['moderacao_status' => $novoStatus, 'motivo' => $motivo]);

        $anuncio = $this->db->fetchOne(
            'SELECT a.*, u.nome AS autor_nome, u.email AS autor_email
             FROM anuncios a JOIN usuarios u ON a.usuario_id = u.id WHERE a.id = ?',
            [$anuncioId]
        );

        if ($anuncio && !empty($anuncio['autor_email'])) {
            $nomePet = sanitize($anuncio['nome_pet'] ?: ucfirst($anuncio['especie']));
            if ($acao === 'aprovar') {
                $assunto = "Seu anuncio de {$nomePet} foi aprovado!";
                $corpo   = "<p>Ola, {$anuncio['autor_nome']}!</p><p>Seu anuncio de <strong>{$nomePet}</strong> foi aprovado e ja esta visivel no Cade Meu Pet?.</p><p><a href='" . BASE_URL . "/anuncio/{$anuncioId}/'>Ver anuncio</a></p>";
            } else {
                $assunto = "Seu anuncio de {$nomePet} nao foi aprovado";
                $corpo   = "<p>Ola, {$anuncio['autor_nome']}!</p><p>Infelizmente seu anuncio de <strong>{$nomePet}</strong> nao foi aprovado." . ($motivo ? " Motivo: {$motivo}." : '') . "</p><p>Se tiver duvidas, entre em contato conosco.</p>";
            }
            sendEmail($anuncio['autor_email'], $assunto, $corpo);
        }

        setFlashMessage('Anúncio ' . ($acao === 'aprovar' ? 'aprovado' : 'rejeitado') . ' com sucesso.', MSG_SUCCESS);
    }

    // ─────────────────────────────────────────────
    // CONFIGURAÇÕES
    // ─────────────────────────────────────────────

    private function getCamposConfig(): array
    {
        return [
            'site_nome'                   => ['label' => 'Nome do site',              'type' => 'text',  'max' => 100],
            'site_descricao'              => ['label' => 'Descrição do site',         'type' => 'text',  'max' => 300],
            'max_anuncios'                => ['label' => 'Máx. anúncios por usuário', 'type' => 'int',   'min' => 1, 'max_val' => 50],
            'min_intervalo'               => ['label' => 'Intervalo mínimo (horas)',  'type' => 'int',   'min' => 0, 'max_val' => 168],
            'expiracao_dias'              => ['label' => 'Expiração (dias)',          'type' => 'int',   'min' => 1, 'max_val' => 365],
            'max_fotos'                   => ['label' => 'Máx. fotos por anúncio',   'type' => 'int',   'min' => 1, 'max_val' => 20],
            'moderacao_ativa'             => ['label' => 'Moderação ativa',          'type' => 'bool'],
            'email_contato'               => ['label' => 'E-mail de contato',        'type' => 'email', 'max' => 150],
            'meta_keywords'               => ['label' => 'Meta keywords',            'type' => 'text',  'max' => 300],
            'rodape_texto'                => ['label' => 'Texto do rodapé',          'type' => 'text',  'max' => 300],
            'parceiro_plano_basico_mensal'   => ['label' => 'Plano Básico (R$/mês)',    'type' => 'float', 'min' => 0.01],
            'parceiro_plano_destaque_mensal' => ['label' => 'Plano Destaque (R$/mês)',  'type' => 'float', 'min' => 0.01],
        ];
    }

    public function getConfiguracoes(): array
    {
        $rows = $this->db->fetchAll('SELECT chave, valor FROM configuracoes');
        $config = [];
        foreach ($rows as $row) {
            $config[$row['chave']] = $row['valor'];
        }
        return $config;
    }

    public function salvarConfiguracoes(array $post): array
    {
        $campos  = $this->getCamposConfig();
        $erros   = [];
        $valores = [];

        foreach ($campos as $chave => $cfg) {
            if ($cfg['type'] === 'bool') {
                $valores[$chave] = isset($post[$chave]) ? '1' : '0';
            } elseif ($cfg['type'] === 'int') {
                $v = (int)($post[$chave] ?? 0);
                if (isset($cfg['min']) && $v < $cfg['min']) {
                    $erros[] = $cfg['label'] . " deve ser pelo menos {$cfg['min']}.";
                    continue;
                }
                if (isset($cfg['max_val']) && $v > $cfg['max_val']) {
                    $erros[] = $cfg['label'] . " não pode exceder {$cfg['max_val']}.";
                    continue;
                }
                $valores[$chave] = (string)$v;
            } elseif ($cfg['type'] === 'float') {
                $v = (float)str_replace(',', '.', (string)($post[$chave] ?? '0'));
                if (isset($cfg['min']) && $v < $cfg['min']) {
                    $erros[] = $cfg['label'] . " deve ser maior que zero.";
                    continue;
                }
                $valores[$chave] = number_format($v, 2, '.', '');
            } elseif ($cfg['type'] === 'email') {
                $v = trim($post[$chave] ?? '');
                if ($v !== '' && !filter_var($v, FILTER_VALIDATE_EMAIL)) {
                    $erros[] = $cfg['label'] . ' deve ser um e-mail válido.';
                    continue;
                }
                $valores[$chave] = $v;
            } else {
                $v = trim($post[$chave] ?? '');
                if (isset($cfg['max']) && mb_strlen($v) > $cfg['max']) {
                    $v = mb_substr($v, 0, $cfg['max']);
                }
                $valores[$chave] = $v;
            }
        }

        if (!$erros) {
            foreach ($valores as $chave => $valor) {
                $exists = $this->db->fetchOne('SELECT chave FROM configuracoes WHERE chave = ?', [$chave]);
                if ($exists) {
                    $this->db->update('configuracoes', ['valor' => $valor], 'chave = ?', [$chave]);
                } else {
                    $this->db->insert('configuracoes', ['chave' => $chave, 'valor' => $valor]);
                }
            }
        }

        return $erros;
    }
}
