<?php

/**
 * Cadê Meu Pet? - Controller de Anúncios
 * Responsável por orquestrar a criação, leitura e atualização de anúncios,
 * aplicando as regras de negócio descritas nas documentações.
 */
class AnuncioController
{
    private $anuncioModel;
    private $usuarioModel;
    private $db;
    private $userIdResolver;
    private $isAdminResolver;

    public function __construct($anuncioModel = null, $usuarioModel = null, $db = null, callable $userIdResolver = null, callable $isAdminResolver = null)
    {
        $this->anuncioModel = $anuncioModel ?: new Anuncio();
        $this->usuarioModel = $usuarioModel ?: new Usuario();
        $this->db = $db ?: getDB();
        $this->userIdResolver = $userIdResolver ?: function () {
            return getUserId();
        };
        $this->isAdminResolver = $isAdminResolver ?: function () {
            return isAdmin();
        };
    }

    public function getAnuncioModel()
    {
        return $this->anuncioModel;
    }

    /**
     * Cria um anúncio novo a partir dos dados fornecidos pelo formulário multi-step.
     */
    public function create(array $data, array $files = [])
    {
        $userId = call_user_func($this->userIdResolver);

        if (!$userId) {
            return ['success' => false, 'errors' => ['Sessão expirada. Faça login novamente.']];
        }

        if (IS_LOCAL) {
            error_log('Dados recebidos no método create: ' . print_r($data, true));
            error_log('Tipo de anúncio recebido: ' . ($data['tipo'] ?? 'não definido'));
        }

        $data = sanitize($data);
        $errors = $this->validateCreateData($data, $files, $userId);

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $payload = $this->buildInsertPayload($data, $userId);

        try {
            $this->db->beginTransaction();

            $anuncioId = $this->anuncioModel->create($payload);

            if (!empty($files['fotos'])) {
                $this->storePhotos($anuncioId, $files['fotos']);
            }

            $this->db->commit();

            cacheClear(null);

            $emModeracao = getConfig('moderacao_ativa') === '1';
            return ['success' => true, 'id' => $anuncioId, 'em_moderacao' => $emModeracao];
        } catch (Throwable $e) {
            $this->db->rollback();
            error_log('[AnuncioController] Falha ao criar anúncio: ' . $e->getMessage());
            return ['success' => false, 'errors' => ['Erro inesperado ao salvar o anúncio. Tente novamente.']];
        }
    }

    /**
     * Retorna dados completos de um anúncio, incluindo fotos e autor.
     */
    public function getDetalhes(int $id)
    {
        $anuncio = $this->anuncioModel->findByIdAnyStatus($id);

        if (!$anuncio) {
            return null;
        }

        $userId = call_user_func($this->userIdResolver);
        $isAdmin = call_user_func($this->isAdminResolver);
        $isOwner = $userId && (int)$anuncio['usuario_id'] === (int)$userId;

        if (in_array($anuncio['status'], [STATUS_INATIVO, STATUS_BLOQUEADO], true) && !$isOwner && !$isAdmin) {
            return null;
        }

        $anuncio['fotos'] = $this->db->fetchAll(
            'SELECT id, nome_arquivo FROM fotos_anuncios WHERE anuncio_id = ? ORDER BY ordem',
            [$id]
        );

        return $anuncio;
    }

    /**
     * Busca anúncios aplicando filtros provenientes da interface.
     */
    public function search(array $params, int $page = 1)
    {
        $page = max(1, (int)$page);
        $offset = ($page - 1) * RESULTS_PER_PAGE;

        $filters = $this->normalizeSearchFilters($params);
        $searchResult = $this->anuncioModel->search($filters, RESULTS_PER_PAGE, $offset);

        return [
            'results' => $searchResult['results'],
            'total'   => $searchResult['total'],
            'filters' => $filters,
            'page'    => $page,
        ];
    }

    /**
     * Marca anúncio como resolvido (somente dono ou admin).
     * Aceita historia_reuniao opcional e envia email de parabéns ao tutor.
     */
    public function marcarComoResolvido(int $id, int $usuarioId, string $historia = '')
    {
        $anuncio = $this->anuncioModel->findByIdAnyStatus($id);

        if (!$anuncio) {
            return ['success' => false, 'error' => 'Anúncio não encontrado.'];
        }

        $isAdmin = call_user_func($this->isAdminResolver);
        if ($anuncio['usuario_id'] != $usuarioId && !$isAdmin) {
            return ['success' => false, 'error' => 'Você não tem permissão para alterar este anúncio.'];
        }

        $this->anuncioModel->markAsResolved($id, $historia);

        // Encerra as conversas abertas sobre este anúncio — o caso foi resolvido.
        (new Conversa($this->db))->encerrarPorReferencia('anuncio', $id);

        // Depoimento (história de reencontro/adoção) — moderado antes de aparecer em público.
        if (trim($historia) !== '') {
            $this->db->insert('depoimentos', [
                'usuario_id' => $anuncio['usuario_id'],
                'anuncio_id' => $id,
                'texto'      => trim($historia),
                'aprovado'   => 0,
            ]);
        }

        // Email de parabéns ao tutor
        $db = getDB();
        $usuario = $db->fetchOne('SELECT nome, email FROM usuarios WHERE id = ?', [$anuncio['usuario_id']]);
        if ($usuario && !empty($usuario['email'])) {
            $nomePet = sanitize($anuncio['nome_pet'] ?: ucfirst($anuncio['especie']));
            $nomeUsuario = sanitize($usuario['nome']);
            $urlAnuncio = BASE_URL . '/anuncio/' . $id . '/';
            $assunto = "Que alegria! {$nomePet} foi reencontrado(a)!";
            $corpo = "
<p>Ola, {$nomeUsuario}!</p>
<p>Parabens! Voce marcou o anuncio <strong>{$nomePet}</strong> como resolvido.</p>
<p>Obrigado por usar o Cade Meu Pet? e por compartilhar essa historia de reencontro com a comunidade.</p>
" . ($historia ? "<p><em>Sua historia: " . htmlspecialchars($historia, ENT_QUOTES, 'UTF-8') . "</em></p>" : '') . "
<p>O anuncio ficara visivel por mais 30 dias inspirando outras pessoas.</p>
<p><a href='{$urlAnuncio}'>Ver anuncio</a></p>
<p>Equipe Cade Meu Pet?</p>
";
            sendEmail($usuario['email'], $assunto, $corpo);
        }

        return ['success' => true, 'showDonation' => true];
    }

    /**
     * Reativa anúncio (marca como ativo). Somente dono ou admin.
     */
    public function marcarComoAtivo(int $id, int $usuarioId)
    {
        $anuncio = $this->anuncioModel->findByIdAnyStatus($id);

        if (!$anuncio) {
            return ['success' => false, 'error' => 'Anúncio não encontrado.'];
        }

        $isAdmin = call_user_func($this->isAdminResolver);
        if ($anuncio['usuario_id'] != $usuarioId && !$isAdmin) {
            return ['success' => false, 'error' => 'Você não tem permissão para alterar este anúncio.'];
        }

        $this->anuncioModel->markAsActive($id);
        return ['success' => true];
    }

    /**
     * Renova um anúncio expirado, reiniciando o prazo de expiração.
     */
    public function renovar(int $id, int $usuarioId)
    {
        if (isRateLimited('renovar_' . $usuarioId, 5, 3600)) {
            return ['success' => false, 'error' => 'Muitas renovações em pouco tempo. Aguarde 1 hora e tente novamente.'];
        }

        $anuncio = $this->anuncioModel->findByIdAnyStatus($id);
        if (!$anuncio) {
            return ['success' => false, 'error' => 'Anúncio não encontrado.'];
        }
        if ((int)$anuncio['usuario_id'] !== $usuarioId) {
            return ['success' => false, 'error' => 'Você não tem permissão para renovar este anúncio.'];
        }
        if ($anuncio['status'] !== STATUS_EXPIRADO) {
            return ['success' => false, 'error' => 'Apenas anúncios expirados podem ser renovados.'];
        }

        $maxAtivos = (int)getConfig('max_anuncios', (string)MAX_ACTIVE_ADS_PER_USER);
        $ativos    = $this->anuncioModel->countActiveByUser($usuarioId);
        if ($ativos >= $maxAtivos) {
            return ['success' => false, 'error' => 'Você atingiu o limite de ' . $maxAtivos . ' anúncios ativos.'];
        }

        $novaExpiracao = date('Y-m-d', strtotime('+' . AD_EXPIRATION_DAYS . ' days'));
        $this->anuncioModel->update($id, [
            'status'          => STATUS_ATIVO,
            'data_publicacao' => date('Y-m-d H:i:s'),
            'data_expiracao'  => $novaExpiracao,
            'data_atualizacao' => date('Y-m-d H:i:s'),
        ]);
        cacheClear(null);
        return ['success' => true];
    }

    /**
     * Soft delete do anúncio (dono ou admin).
     */
    public function excluir(int $id)
    {
        $userId = call_user_func($this->userIdResolver);
        if (!$userId) {
            return ['success' => false, 'error' => 'Sessão expirada. Faça login novamente.'];
        }

        $anuncio = $this->anuncioModel->findByIdAnyStatus($id);
        if (!$anuncio) {
            return ['success' => false, 'error' => 'Anúncio não encontrado.'];
        }

        $isAdmin = call_user_func($this->isAdminResolver);
        if ((int)$anuncio['usuario_id'] !== (int)$userId && !$isAdmin) {
            return ['success' => false, 'error' => 'Você não tem permissão para excluir este anúncio.'];
        }

        if ($isAdmin && (int)$anuncio['usuario_id'] !== (int)$userId) {
            $this->anuncioModel->softDeleteAsAdmin($id);
        } else {
            $this->anuncioModel->softDelete($id, (int)$anuncio['usuario_id']);
        }

        // Remover arquivos de foto do disco (o registro é mantido para auditoria)
        $db    = getDB();
        $fotos = $db->fetchAll('SELECT nome_arquivo FROM fotos_anuncios WHERE anuncio_id = ?', [$id]);
        foreach ($fotos as $foto) {
            $caminho = UPLOAD_PATH . '/anuncios/' . $foto['nome_arquivo'];
            if (file_exists($caminho)) {
                @unlink($caminho);
            }
        }

        cacheClear(null);

        return ['success' => true];
    }

    /**
     * Incrementa visualizações respeitando limite diário por IP.
     */
    public function registrarVisualizacao(int $id)
    {
        $clientIp = getClientIP();
        $this->anuncioModel->incrementViews($id, $clientIp);
    }

    /**
     * Atualiza anúncio existente (somente dono ou admin).
     */
    public function update(int $id, array $data)
    {
        $userId = call_user_func($this->userIdResolver);
        if (!$userId) {
            return ['success' => false, 'errors' => ['Sessão expirada. Faça login novamente.']];
        }

        $anuncio = $this->anuncioModel->findById($id);
        if (!$anuncio) {
            return ['success' => false, 'errors' => ['Anúncio não encontrado.']];
        }

        $isAdmin = call_user_func($this->isAdminResolver);
        if ((int)$anuncio['usuario_id'] !== (int)$userId && !$isAdmin) {
            return ['success' => false, 'errors' => ['Você não tem permissão para editar este anúncio.']];
        }

        $data = sanitize($data);

        $latitude = null;
        if (array_key_exists('latitude', $data)) {
            $latitude = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
            $latitude = ($latitude === false) ? null : (float)$latitude;
        }

        $longitude = null;
        if (array_key_exists('longitude', $data)) {
            $longitude = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);
            $longitude = ($longitude === false) ? null : (float)$longitude;
        }

        $payload = [
            'tipo' => $data['tipo'] ?? $anuncio['tipo'],
            'especie' => $data['especie'] ?? $anuncio['especie'],
            'nome_pet' => $data['nome_pet'] ?? null,
            'tamanho' => $data['tamanho'] ?? $anuncio['tamanho'],
            'raca' => $data['raca'] ?? null,
            'cor' => $data['cor'] ?? null,
            'idade' => array_key_exists('idade', $data) && $data['idade'] !== '' ? (int)$data['idade'] : null,
            'vacinas' => array_key_exists('vacinas', $data) ? ($data['vacinas'] !== '' ? $data['vacinas'] : null) : null,
            'castrado' => array_key_exists('castrado', $data) && $data['castrado'] !== '' ? (int)($data['castrado'] === '1' || $data['castrado'] === 1 || $data['castrado'] === true) : null,
            'necessita_termo_responsabilidade' => !empty($data['necessita_termo_responsabilidade']) ? 1 : 0,
            'descricao' => $data['descricao'] ?? null,
            'data_ocorrido' => $data['data_ocorrido'] ?? $anuncio['data_ocorrido'],
            'endereco_completo' => $data['endereco_completo'] ?? $anuncio['endereco_completo'],
            'bairro' => $data['bairro'] ?? $anuncio['bairro'],
            'cidade' => $data['cidade'] ?? $anuncio['cidade'],
            'estado' => isset($data['estado']) ? strtoupper($data['estado']) : $anuncio['estado'],
            'cep' => $data['cep'] ?? null,
            'ponto_referencia' => $data['ponto_referencia'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'whatsapp' => $data['whatsapp'] ?? $anuncio['whatsapp'],
            'telefone_contato' => $data['telefone_contato'] ?? null,
            'email_contato' => $data['email_contato'] ?? null,
            'recompensa' => $data['recompensa'] ?? null,
            'data_atualizacao' => date('Y-m-d H:i:s')
        ];

        $errors = [];

        if (!in_array($payload['tipo'], [TIPO_PERDIDO, TIPO_ENCONTRADO, TIPO_DOACAO], true)) {
            $errors[] = 'Informe se o pet está perdido ou encontrado.';
        }

        $especies = [ESPECIE_CACHORRO, ESPECIE_GATO, ESPECIE_AVE, ESPECIE_OUTRO];
        if (empty($payload['especie']) || !in_array($payload['especie'], $especies, true)) {
            $errors[] = 'Selecione a espécie do animal.';
        }

        $tamanhos = [TAMANHO_PEQUENO, TAMANHO_MEDIO, TAMANHO_GRANDE];
        if (empty($payload['tamanho']) || !in_array($payload['tamanho'], $tamanhos, true)) {
            $errors[] = 'Selecione o tamanho do animal.';
        }

        if (empty($payload['descricao']) || strlen(trim($payload['descricao'])) < 20) {
            $errors[] = 'Descrição deve possuir pelo menos 20 caracteres.';
        }

        if (empty($payload['data_ocorrido'])) {
            $errors[] = 'Informe a data do ocorrido.';
        }

        foreach (['endereco_completo', 'bairro', 'cidade', 'estado'] as $campo) {
            if (empty($payload[$campo])) {
                $errors[] = 'Preencha o campo ' . str_replace('_', ' ', $campo) . '.';
            }
        }

        if (!empty($payload['estado']) && strlen($payload['estado']) !== 2) {
            $errors[] = 'Informe a sigla do estado (ex: RO).';
        }

        if (empty($payload['whatsapp']) || !isValidPhone($payload['whatsapp'])) {
            $errors[] = 'Informe um número de WhatsApp válido.';
        }

        if (!empty($payload['telefone_contato']) && !isValidPhone($payload['telefone_contato'])) {
            $errors[] = 'Telefone fixo informado é inválido.';
        }

        if (!empty($payload['email_contato']) && !isValidEmail($payload['email_contato'])) {
            $errors[] = 'Email de contato inválido.';
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => array_values(array_unique($errors))];
        }

        // Se moderação ativa, anúncio editado volta para fila de revisão
        if (getConfig('moderacao_ativa') === '1') {
            $payload['moderacao_status'] = 'pendente';
            $payload['status']           = STATUS_INATIVO;
        }

        $updated = $this->anuncioModel->update($id, $payload);
        if (!$updated) {
            return ['success' => false, 'errors' => ['Não foi possível atualizar o anúncio.']];
        }

        cacheClear(null);

        $emModeracao = getConfig('moderacao_ativa') === '1';
        return ['success' => true, 'em_moderacao' => $emModeracao];
    }

    /**
     * Aplica regras de negócio e validações no momento da criação.
     */
    private function validateCreateData(array $data, array $files, int $userId): array
    {
        $errors = [];

        $usuario = $this->usuarioModel->findById($userId);

        if (!$usuario) {
            $errors[] = 'Usuário não encontrado.';
        } else {
            if (empty($usuario['email_confirmado'])) {
                $errors[] = 'Confirme seu email antes de publicar anúncios.';
            }

            $maxAtivos = (int)getConfig('max_anuncios', (string)MAX_ACTIVE_ADS_PER_USER);
            $ativos    = $this->anuncioModel->countActiveByUser($userId);
            if ($ativos >= $maxAtivos) {
                $errors[] = 'Você atingiu o limite de ' . $maxAtivos . ' anúncios ativos.';
            }

            if (!$this->anuncioModel->canPublishNewAd($userId)) {
                $errors[] = 'Espere 5 minutos entre cada publicação.';
            }
        }

        $tipo = $data['tipo'] ?? '';
        if (!in_array($tipo, [TIPO_PERDIDO, TIPO_ENCONTRADO, TIPO_DOACAO], true)) {
            $errors[] = 'Informe se o pet está perdido ou encontrado.';
        }

        $especies = [ESPECIE_CACHORRO, ESPECIE_GATO, ESPECIE_AVE, ESPECIE_OUTRO];
        if (empty($data['especie']) || !in_array($data['especie'], $especies, true)) {
            $errors[] = 'Selecione a espécie do animal.';
        }

        $tamanhos = [TAMANHO_PEQUENO, TAMANHO_MEDIO, TAMANHO_GRANDE];
        if (empty($data['tamanho']) || !in_array($data['tamanho'], $tamanhos, true)) {
            $errors[] = 'Selecione o tamanho do animal.';
        }

        $descricao = $data['descricao'] ?? '';
        if (strlen(trim($descricao)) < 20) {
            $errors[] = 'Descrição deve possuir pelo menos 20 caracteres.';
        }
        if (strlen($descricao) > 1000) {
            $errors[] = 'Descrição não pode ultrapassar 1000 caracteres.';
        }

        $dataOcorrido = $data['data_ocorrido'] ?? '';
        if (empty($dataOcorrido)) {
            $errors[] = 'Informe a data do ocorrido.';
        } else {
            $timestamp = strtotime($dataOcorrido);
            if ($timestamp === false || $timestamp > time()) {
                $errors[] = 'Data do ocorrido não pode ser no futuro.';
            }
            $tresAnosAtras = strtotime('-3 years');
            if ($timestamp < $tresAnosAtras) {
                $errors[] = 'Data do ocorrido deve estar dentro dos últimos 3 anos.';
            }
        }

        if ($tipo === TIPO_DOACAO) {
            $idade = $data['idade'] ?? null;
            if ($idade !== null && $idade !== '') {
                $idadeInt = filter_var($idade, FILTER_VALIDATE_INT);
                if ($idadeInt === false || $idadeInt < 0 || $idadeInt > 60) {
                    $errors[] = 'Idade inválida. Informe a idade em anos (0 a 60).';
                }
            }
        }

        $enderecosObrigatorios = ['endereco_completo', 'bairro', 'cidade', 'estado'];
        foreach ($enderecosObrigatorios as $campo) {
            if (empty($data[$campo])) {
                $errors[] = 'Preencha o campo ' . str_replace('_', ' ', $campo) . '.';
            }
        }

        if (!empty($data['estado']) && strlen($data['estado']) !== 2) {
            $errors[] = 'Informe a sigla do estado (ex: RO).';
        }

        // Contatos: é obrigatório pelo menos WhatsApp, os demais opcionais.
        if (empty($data['whatsapp']) || !isValidPhone($data['whatsapp'])) {
            $errors[] = 'Informe um número de WhatsApp válido.';
        }

        if (!empty($data['telefone_contato']) && !isValidPhone($data['telefone_contato'])) {
            $errors[] = 'Telefone fixo informado é inválido.';
        }

        if (!empty($data['email_contato']) && !isValidEmail($data['email_contato'])) {
            $errors[] = 'Email de contato inválido.';
        }

        // Validação das fotos
        if (empty($files['fotos']) || !is_array($files['fotos']) || !is_array($files['fotos']['name'] ?? null)) {
            $errors[] = 'Adicione pelo menos uma foto para destacar seu anúncio.';
        } else {
            $totalFotos = $this->countValidFiles($files['fotos']);
            if ($totalFotos > MAX_PHOTOS_PER_AD) {
                $errors[] = 'Envie no máximo ' . MAX_PHOTOS_PER_AD . ' fotos.';
            }

            $validCount = 0;
            foreach ($this->iterateFiles($files['fotos']) as $file) {
                if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                    continue;
                }

                $validation = validateImageUpload($file);
                if (!$validation['valid']) {
                    $errors = array_merge($errors, $validation['errors']);
                } else {
                    $validCount++;
                }
            }

            if ($validCount === 0) {
                $errors[] = 'Adicione pelo menos uma foto para destacar seu anúncio.';
            }
        }

        return array_unique($errors);
    }

    private function buildInsertPayload(array $data, int $userId): array
    {
        $latitude = null;
        if (array_key_exists('latitude', $data)) {
            $latitude = filter_var($data['latitude'], FILTER_VALIDATE_FLOAT);
            $latitude = ($latitude === false) ? null : (float)$latitude;
        }

        $longitude = null;
        if (array_key_exists('longitude', $data)) {
            $longitude = filter_var($data['longitude'], FILTER_VALIDATE_FLOAT);
            $longitude = ($longitude === false) ? null : (float)$longitude;
        }

        return [
            'usuario_id' => $userId,
            'tipo' => $data['tipo'],
            'nome_pet' => $data['nome_pet'] ?? null,
            'especie' => $data['especie'],
            'raca' => $data['raca'] ?? null,
            'cor' => $data['cor'] ?? null,
            'tamanho' => $data['tamanho'],
            'idade' => isset($data['idade']) && $data['idade'] !== '' ? (int)$data['idade'] : null,
            'vacinas' => $data['vacinas'] ?? null,
            'castrado' => array_key_exists('castrado', $data) ? (int)($data['castrado'] === '1' || $data['castrado'] === 1 || $data['castrado'] === true) : null,
            'necessita_termo_responsabilidade' => !empty($data['necessita_termo_responsabilidade']) ? 1 : 0,
            'descricao' => $data['descricao'],
            'data_ocorrido' => $data['data_ocorrido'],
            'endereco_completo' => $data['endereco_completo'],
            'bairro' => $data['bairro'],
            'cidade' => $data['cidade'],
            'estado' => strtoupper($data['estado']),
            'cep' => $data['cep'] ?? null,
            'ponto_referencia' => $data['ponto_referencia'] ?? null,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'telefone_contato' => !empty($data['telefone_contato']) ? preg_replace('/[^0-9]/', '', $data['telefone_contato']) : null,
            'whatsapp' => preg_replace('/[^0-9]/', '', $data['whatsapp']),
            'email_contato' => $data['email_contato'] ?? null,
            'recompensa' => $data['recompensa'] ?? null,
            'status'           => STATUS_ATIVO,
            'moderacao_status' => getConfig('moderacao_ativa') === '1' ? 'pendente' : 'aprovado',
            'data_publicacao'  => date('Y-m-d H:i:s')
        ];
    }

    private function storePhotos(int $anuncioId, array $files)
    {
        $uploadDir = UPLOAD_PATH . '/anuncios';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $ordem = 1;
        foreach ($this->iterateFiles($files) as $file) {
            if ($ordem > MAX_PHOTOS_PER_AD) {
                break;
            }

            if ($file['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result = uploadImage($file, $uploadDir);
            if (!$result['success']) {
                throw new RuntimeException(implode(', ', $result['errors']));
            }

            $this->db->insert('fotos_anuncios', [
                'anuncio_id' => $anuncioId,
                'nome_arquivo' => $result['filename'],
                'ordem' => $ordem,
                'data_upload' => date('Y-m-d H:i:s')
            ]);

            $ordem++;
        }
    }

    private function normalizeSearchFilters(array $params): array
    {
        $filters = [];

        if (!empty($params['q'])) {
            $filters['q'] = trim($params['q']);
        }

        if (!empty($params['tipo']) && in_array($params['tipo'], [TIPO_PERDIDO, TIPO_ENCONTRADO, TIPO_DOACAO], true)) {
            $filters['tipo'] = $params['tipo'];
        }

        if (!empty($params['especie'])) {
            $filters['especie'] = $params['especie'];
        }

        if (!empty($params['cidade'])) {
            $filters['cidade'] = $params['cidade'];
        }

        if (!empty($params['estado'])) {
            $filters['estado'] = strtoupper($params['estado']);
        }

        if (!empty($params['bairro'])) {
            $filters['bairro'] = $params['bairro'];
        }

        if (!empty($params['status'])) {
            $filters['status'] = $params['status'];
        }

        if (!empty($params['ordenacao'])) {
            $filters['ordenacao'] = $params['ordenacao'];
        }

        if (!empty($params['has_photo'])) {
            $filters['has_photo'] = true;
        }

        $raio = isset($params['raio']) ? (int)$params['raio'] : null;
        if ($raio) {
            $filters['raio'] = $raio;
        }

        if (!empty($params['lat']) && !empty($params['lng'])) {
            $filters['lat'] = (float)$params['lat'];
            $filters['lng'] = (float)$params['lng'];
        }

        if (!empty($params['data_desde'])) {
            $filters['data_desde'] = $params['data_desde'];
        }

        if (!empty($params['data_ate'])) {
            $filters['data_ate'] = $params['data_ate'];
        }

        $validTamanhos = ['pequeno', 'medio', 'grande', 'gigante'];
        if (!empty($params['tamanho']) && in_array($params['tamanho'], $validTamanhos, true)) {
            $filters['tamanho'] = $params['tamanho'];
        }

        return $filters;
    }

    private function countValidFiles(array $files): int
    {
        $count = 0;
        foreach ($this->iterateFiles($files) as $file) {
            if ($file['error'] !== UPLOAD_ERR_NO_FILE) {
                $count++;
            }
        }
        return $count;
    }

    private function iterateFiles(array $files): Generator
    {
        $fileCount = count($files['name']);
        for ($i = 0; $i < $fileCount; $i++) {
            yield [
                'name' => $files['name'][$i],
                'type' => $files['type'][$i],
                'tmp_name' => $files['tmp_name'][$i],
                'error' => $files['error'][$i],
                'size' => $files['size'][$i],
            ];
        }
    }
}

