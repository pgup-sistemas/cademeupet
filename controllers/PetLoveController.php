<?php

class PetLoveController {

    private $model;

    public function __construct() {
        $this->model = new PetLove();
    }

    // ─────────────────────────────────────────────
    // VITRINE — GET /petlove
    // ─────────────────────────────────────────────

    public function vitrine(): array {
        $filtros = [
            'especie'      => $_GET['especie']      ?? '',
            'sexo'         => $_GET['sexo']         ?? '',
            'porte'        => $_GET['porte']         ?? '',
            'raca'         => $_GET['raca']          ?? '',
            'cidade'       => $_GET['cidade']        ?? '',
            'estado'       => $_GET['estado']        ?? '',
            'tem_pedigree' => $_GET['tem_pedigree']  ?? '',
            'objetivo'     => $_GET['objetivo']      ?? '',
        ];
        // Limpar filtros vazios
        $filtros = array_filter($filtros, fn($v) => $v !== '');
        $pagina  = max(1, (int)($_GET['pagina'] ?? 1));
        return $this->model->listarVitrine($filtros, $pagina);
    }

    // ─────────────────────────────────────────────
    // DETALHE — GET /petlove/{id}
    // ─────────────────────────────────────────────

    public function detalhe(int $id): ?array {
        $pet = $this->model->buscarPorId($id);
        if (!$pet || $pet['status'] === 'removido') return null;
        $pet['matches']     = $this->model->buscarCompativeis($id);
        $pet['ja_interesse'] = false;
        if (isLoggedIn()) {
            $db = getDB();
            $existe = $db->fetchOne(
                "SELECT id FROM petlove_interesses WHERE petlove_id = ? AND interessado_id = ?",
                [$id, getUserId()]
            );
            $pet['ja_interesse'] = (bool)$existe;
        }
        return $pet;
    }

    // ─────────────────────────────────────────────
    // CADASTRO — POST /petlove/novo
    // ─────────────────────────────────────────────

    public function processar(array $post, array $files): array {
        requireLogin();
        if (!validateCSRFToken($post['csrf_token'] ?? '')) {
            return ['ok' => false, 'erros' => ['Token de segurança inválido.']];
        }

        $erros = $this->validar($post);
        if ($erros) return ['ok' => false, 'erros' => $erros];

        $dados = array_merge($post, ['usuario_id' => getUserId()]);
        $id    = $this->model->criar($dados);

        // Upload de fotos
        if (!empty($files['fotos']['name'][0])) {
            $dir = UPLOAD_PATH . '/petlove';
            if (!is_dir($dir)) mkdir($dir, 0755, true);
            $i = 0;
            foreach ($files['fotos']['tmp_name'] as $k => $tmp) {
                if ($files['fotos']['error'][$k] !== UPLOAD_ERR_OK) continue;
                $ext      = strtolower(pathinfo($files['fotos']['name'][$k], PATHINFO_EXTENSION));
                $permitido = in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
                $mime     = mime_content_type($tmp);
                $mimeOk   = in_array($mime, ['image/jpeg', 'image/png', 'image/webp'], true);
                if (!$permitido || !$mimeOk) continue;
                if ($files['fotos']['size'][$k] > 2 * 1024 * 1024) continue;
                $nome = uniqid('pl_', true) . '.' . $ext;
                if (move_uploaded_file($tmp, $dir . '/' . $nome)) {
                    $this->model->adicionarFoto($id, $nome, $i === 0);
                    $i++;
                }
            }
        }

        return ['ok' => true, 'id' => $id];
    }

    // ─────────────────────────────────────────────
    // MINHA CONTA — meus pets + interesses
    // ─────────────────────────────────────────────

    public function meusPets(): array {
        requireLogin();
        $ic = new PetLoveInteresseController();
        return [
            'pets'             => $this->model->buscarPorUsuario(getUserId()),
            'interesses_receb' => $ic->listarRecebidos(getUserId()),
            'interesses_env'   => $ic->listarEnviados(getUserId()),
        ];
    }

    public function alterarStatus(int $id, string $status): void {
        requireLogin();
        if (!$this->model->pertenceAoUsuario($id, getUserId()) && !isAdmin()) {
            setFlashMessage('Sem permissão.', MSG_ERROR);
            redirect('/minha-conta/petlove');
        }
        $this->model->alterarStatus($id, $status);
        $msgs = ['ativo' => 'Pet reativado.', 'pausado' => 'Pet pausado.', 'removido' => 'Pet removido.'];
        setFlashMessage($msgs[$status] ?? 'Atualizado.', MSG_SUCCESS);
        redirect('/minha-conta/petlove');
    }

    // ─────────────────────────────────────────────
    // VALIDAÇÃO
    // ─────────────────────────────────────────────

    private function validar(array $d): array {
        $erros = [];
        if (empty(trim($d['nome'] ?? '')))    $erros[] = 'Nome do pet é obrigatório.';
        if (empty($d['especie']))              $erros[] = 'Espécie é obrigatória.';
        if (empty(trim($d['raca'] ?? '')))    $erros[] = 'Raça é obrigatória.';
        if (empty($d['porte']))               $erros[] = 'Porte é obrigatório.';
        if (empty($d['sexo']))                $erros[] = 'Sexo é obrigatório.';
        if (!isset($d['idade_meses']) || (int)$d['idade_meses'] < 1) {
            $erros[] = 'Idade inválida.';
        }
        if (empty($d['criacao_responsavel'])) {
            $erros[] = 'É necessário confirmar a criação responsável.';
        }
        return $erros;
    }
}
