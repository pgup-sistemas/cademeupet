<?php

/**
 * Cadê Meu Pet? - Controller de Cancelamentos
 * Gerencia cancelamentos de assinaturas de parceiros e doações recorrentes
 * com segurança, auditoria e validações rigorosas.
 */
class CancelamentoController
{
    private $db;
    private $pagamentoController;

    public function __construct()
    {
        $this->db = getDB();
        $this->pagamentoController = new PagamentoController();
    }

    /**
     * Cancela assinatura de parceiro com validações de segurança
     */
    public function cancelarAssinaturaParceiro(int $usuarioId, string $motivo = '', string $senha = ''): array
    {
        // Validações de segurança
        $erros = $this->validarCancelamentoAssinatura($usuarioId, $senha);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        try {
            // Iniciar transação
            $this->db->beginTransaction();

            // Buscar assinatura atual
            $assinaturaModel = new ParceiroAssinatura();
            $assinatura = $assinaturaModel->findByUserId($usuarioId);

            if (!$assinatura || $assinatura['status'] !== 'ativa') {
                throw new Exception('Assinatura não encontrada ou já cancelada.');
            }

            // Cancelar no gateway Efí se tiver subscription_id
            $gatewayCancelado = false;
            if (!empty($assinatura['efi_subscription_id'])) {
                $gatewayCancelado = $this->cancelarAssinaturaGateway($assinatura['efi_subscription_id']);
            }

            // Atualizar status da assinatura
            $assinaturaModel->updateForUser($usuarioId, [
                'status' => 'cancelada',
                'cancelada_em' => date('Y-m-d H:i:s'),
                'motivo_cancelamento' => sanitize($motivo),
                'cancelamento_gateway' => $gatewayCancelado ? 'sucesso' : 'manual'
            ]);

            // Despublicar perfil
            $perfilModel = new ParceiroPerfil();
            $perfilModel->publishForUser($usuarioId, false);

            // Registrar log de auditoria
            $this->registrarLogCancelamento([
                'usuario_id' => $usuarioId,
                'tipo' => 'assinatura_parceiro',
                'motivo' => sanitize($motivo),
                'gateway_response' => $gatewayCancelado ? 'success' : 'manual',
                'responsavel' => 'usuario',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // Enviar notificação
            $this->enviarNotificacaoCancelamento($usuarioId, 'assinatura', $motivo);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Assinatura cancelada com sucesso.',
                'data' => [
                    'cancelado_em' => date('Y-m-d H:i:s'),
                    'gateway_cancelado' => $gatewayCancelado,
                    'perfil_despublicado' => true
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('[CancelamentoController] Erro ao cancelar assinatura: ' . $e->getMessage());
            
            return [
                'success' => false, 
                'errors' => ['Erro ao processar cancelamento. Tente novamente ou contate o suporte.']
            ];
        }
    }

    /**
     * Cancela doação recorrente com validações de segurança
     */
    public function cancelarDoacaoRecorrente(int $doacaoId, int $usuarioId, string $motivo = '', string $senha = ''): array
    {
        // Validações de segurança
        $erros = $this->validarCancelamentoDoacao($doacaoId, $usuarioId, $senha);
        if (!empty($erros)) {
            return ['success' => false, 'errors' => $erros];
        }

        try {
            $this->db->beginTransaction();

            $doacaoModel = new Doacao();
            $doacao = $doacaoModel->findById($doacaoId);

            if (!$doacao || $doacao['usuario_id'] != $usuarioId || $doacao['tipo'] !== 'mensal') {
                throw new Exception('Doação não encontrada ou não é recorrente.');
            }

            // Cancelar no gateway se tiver subscription_id
            $gatewayCancelado = false;
            if (!empty($doacao['efi_subscription_id'])) {
                $gatewayCancelado = $this->cancelarAssinaturaGateway($doacao['efi_subscription_id']);
            }

            // Atualizar status da doação
            $doacaoModel->update($doacaoId, [
                'status' => 'cancelada',
                'cancelada_em' => date('Y-m-d H:i:s'),
                'motivo_cancelamento' => sanitize($motivo),
                'cancelamento_gateway' => $gatewayCancelado ? 'sucesso' : 'manual'
            ]);

            // Registrar log
            $this->registrarLogCancelamento([
                'usuario_id' => $usuarioId,
                'doacao_id' => $doacaoId,
                'tipo' => 'doacao_recorrente',
                'motivo' => sanitize($motivo),
                'gateway_response' => $gatewayCancelado ? 'success' : 'manual',
                'responsavel' => 'usuario',
                'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);

            // Enviar notificação
            $this->enviarNotificacaoCancelamento($usuarioId, 'doacao', $motivo, $doacaoId);

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Doação recorrente cancelada com sucesso.',
                'data' => [
                    'cancelado_em' => date('Y-m-d H:i:s'),
                    'gateway_cancelado' => $gatewayCancelado
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            error_log('[CancelamentoController] Erro ao cancelar doação: ' . $e->getMessage());
            
            return [
                'success' => false, 
                'errors' => ['Erro ao processar cancelamento. Tente novamente ou contate o suporte.']
            ];
        }
    }

    /**
     * Valida segurança para cancelamento de assinatura
     */
    private function validarCancelamentoAssinatura(int $usuarioId, string $senha): array
    {
        $erros = [];

        // Verificar se usuário está logado
        if (!isLoggedIn() || getUserId() != $usuarioId) {
            $erros[] = 'Usuário não autenticado.';
            return $erros;
        }

        // Validar senha (confirmação de identidade)
        if (empty($senha)) {
            $erros[] = 'Senha obrigatória para confirmar cancelamento.';
        } else {
            $usuarioModel = new Usuario();
            $usuario = $usuarioModel->findById($usuarioId);
            
            if (!$usuario || !password_verify($senha, $usuario['senha'])) {
                $erros[] = 'Senha incorreta.';
            }
        }

        // Verificar se não há cancelamento recente (anti-spam)
        $logRecente = $this->verificarCancelamentoRecente($usuarioId, 'assinatura_parceiro');
        if ($logRecente) {
            $erros[] = 'Você já solicitou um cancelamento recentemente. Aguarde 24 horas.';
        }

        return $erros;
    }

    /**
     * Valida segurança para cancelamento de doação
     */
    private function validarCancelamentoDoacao(int $doacaoId, int $usuarioId, string $senha): array
    {
        $erros = [];

        // Verificar autenticação
        if (!isLoggedIn() || getUserId() != $usuarioId) {
            $erros[] = 'Usuário não autenticado.';
            return $erros;
        }

        // Validar senha
        if (empty($senha)) {
            $erros[] = 'Senha obrigatória para confirmar cancelamento.';
        } else {
            $usuarioModel = new Usuario();
            $usuario = $usuarioModel->findById($usuarioId);
            
            if (!$usuario || !password_verify($senha, $usuario['senha'])) {
                $erros[] = 'Senha incorreta.';
            }
        }

        // Verificar se não há cancelamento recente
        $logRecente = $this->verificarCancelamentoRecente($usuarioId, 'doacao_recorrente');
        if ($logRecente) {
            $erros[] = 'Você já solicitou um cancelamento recentemente. Aguarde 24 horas.';
        }

        return $erros;
    }

    /**
     * Cancela assinatura no gateway Efí
     */
    private function cancelarAssinaturaGateway(string $subscriptionId): bool
    {
        try {
            $api = $this->pagamentoController->getApi();
            
            $params = ['id' => $subscriptionId];
            $response = $api->cancelSubscription($params);
            
            return isset($response['data']) && $response['data']['status'] === 'canceled';
            
        } catch (Exception $e) {
            error_log('[CancelamentoController] Erro ao cancelar no gateway: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Registra log de auditoria
     */
    private function registrarLogCancelamento(array $dados): void
    {
        $this->db->insert('cancelamentos_log', [
            'usuario_id' => $dados['usuario_id'] ?? null,
            'doacao_id' => $dados['doacao_id'] ?? null,
            'tipo' => $dados['tipo'],
            'motivo' => $dados['motivo'] ?? '',
            'gateway_response' => $dados['gateway_response'] ?? '',
            'responsavel' => $dados['responsavel'],
            'ip_address' => $dados['ip_address'],
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'data_cancelamento' => date('Y-m-d H:i:s')
        ]);
    }

    /**
     * Verifica se há cancelamento recente (últimas 24h)
     */
    private function verificarCancelamentoRecente(int $usuarioId, string $tipo): bool
    {
        $log = $this->db->fetchOne(
            'SELECT id FROM cancelamentos_log 
             WHERE usuario_id = ? AND tipo = ? 
             AND data_cancelamento > DATE_SUB(NOW(), INTERVAL 24 HOUR)
             LIMIT 1',
            [$usuarioId, $tipo]
        );
        
        return !empty($log);
    }

    /**
     * Envia notificação por email
     */
    private function enviarNotificacaoCancelamento(int $usuarioId, string $tipo, string $motivo, int $doacaoId = null): void
    {
        $usuarioModel = new Usuario();
        $usuario = $usuarioModel->findById($usuarioId);
        
        if (!$usuario || empty($usuario['email'])) {
            return;
        }

        $subject = $tipo === 'assinatura' 
            ? 'Assinatura Cadê Meu Pet? Cancelada' 
            : 'Doação Recorrente Cadê Meu Pet? Cancelada';

        $message = $this->gerarEmailCancelamento($usuario, $tipo, $motivo, $doacaoId);
        
        sendEmail($usuario['email'], $subject, $message);
    }

    /**
     * Gera conteúdo do email de cancelamento
     */
    private function gerarEmailCancelamento(array $usuario, string $tipo, string $motivo, ?int $doacaoId): string
    {
        $nome = sanitize($usuario['nome'] ?? '');
        $motivoFormatado = sanitize($motivo) ?: 'Não informado';
        
        if ($tipo === 'assinatura') {
            return "<html><body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color:#FF6B6B;'>Cancelamento confirmado</h2>
                    <p>Olá, <strong>{$nome}</strong>!</p>
                    <p>Sua assinatura de Parceiro Cadê Meu Pet? foi cancelada conforme solicitado.</p>
                    <p><strong>Motivo:</strong> {$motivoFormatado}</p>
                    <p>Seu perfil será despublicado em até 24 horas.</p>
                    <p>Agradecemos sua parceria e esperamos vê-lo novamente em breve!</p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                    <p style='color:#666; font-size: 12px;'>
                        Esta é uma mensagem automática. Não responda este email.
                    </p>
                </div>
            </body></html>";
        } else {
            return "<html><body style='font-family: Arial, sans-serif;'>
                <div style='max-width: 600px; margin: 0 auto; padding: 20px;'>
                    <h2 style='color:#FF6B6B;'>Doação recorrente cancelada</h2>
                    <p>Olá, <strong>{$nome}</strong>!</p>
                    <p>Sua doação recorrente Cadê Meu Pet? foi cancelada conforme solicitado.</p>
                    <p><strong>Motivo:</strong> {$motivoFormatado}</p>
                    <p>Não haverá mais cobranças futuras.</p>
                    <p>Agradecemos imensamente seu apoio à nossa causa!</p>
                    <hr style='margin: 30px 0; border: none; border-top: 1px solid #eee;'>
                    <p style='color:#666; font-size: 12px;'>
                        Esta é uma mensagem automática. Não responda este email.
                    </p>
                </div>
            </body></html>";
        }
    }

    /**
     * Lista logs de cancelamento para admin
     */
    public function listarLogsCancelamento(int $limit = 50, int $offset = 0): array
    {
        requireAdmin();
        
        return $this->db->fetchAll(
            'SELECT cl.*, u.nome as usuario_nome, u.email 
             FROM cancelamentos_log cl
             LEFT JOIN usuarios u ON u.id = cl.usuario_id
             ORDER BY cl.data_cancelamento DESC
             LIMIT ? OFFSET ?',
            [$limit, $offset]
        );
    }

    /**
     * Obtém estatísticas de cancelamentos
     */
    public function getEstatisticasCancelamento(): array
    {
        requireAdmin();
        
        return $this->db->fetchOne(
            'SELECT 
                COUNT(*) AS total_cancelamentos,
                COUNT(CASE WHEN tipo = "assinatura_parceiro" THEN 1 END) AS cancelamentos_assinaturas,
                COUNT(CASE WHEN tipo = "doacao_recorrente" THEN 1 END) AS cancelamentos_doacoes,
                COUNT(CASE WHEN data_cancelamento >= DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) AS ultimos_30_dias
             FROM cancelamentos_log'
        );
    }
}
