<?php
/**
 * Cadê Meu Pet? - Wrapper para SDK EFI
 * Integração com Efi\EfiPay para cobranças PIX, Cartão e Assinaturas
 * 
 * IMPORTANTE: Este arquivo é um adapter para a SDK oficial do EFI
 * A SDK deve ser instalada via composer: composer require efipay/sdk-php-apis-efi
 * 
 * VERSÃO: 2.0 - Atualizado em 2026-01-12
 * MÉTODOS: pixCreateImmediateCharge, pixGenerateQRCode, pixDetailCharge,
 *          createOneStepLink, createPlan, createOneStepSubscriptionLink
 */

use Efi\EfiPay;
use Efi\Exception as EfiException;

/**
 * Wrapper da SDK EFI para facilitar uso no Cadê Meu Pet?
 */
class Efi
{
    private $efiPay;
    private $clientId;
    private $clientSecret;
    private $certificatePath;
    private $sandbox;
    private $pixKey;

    /**
     * Construtor
     * 
     * @param array $options Configurações:
     *   - client_id: ID do cliente EFI
     *   - client_secret: Secret do cliente EFI
     *   - certificate: Caminho do certificado SSL/TLS
     *   - sandbox: bool - modo sandbox
     *   - pixKey: Chave PIX para receber
     *   - base_url: URL base da API (opcional, usa padrão do SDK)
     */
    public function __construct(array $options = [])
    {
        $this->clientId = (string)($options['client_id'] ?? '');
        $this->clientSecret = (string)($options['client_secret'] ?? '');
        $this->certificatePath = (string)($options['certificate'] ?? '');
        $this->sandbox = (bool)($options['sandbox'] ?? false);
        $this->pixKey = (string)($options['pixKey'] ?? $options['pix_key'] ?? '');

        $this->validateCredentials();
        $this->initializeEfiPay();
    }

    /**
     * Valida credenciais necessárias
     */
    private function validateCredentials()
    {
        if (empty($this->clientId) || empty($this->clientSecret)) {
            throw new Exception('Credenciais EFI não configuradas (client_id/client_secret).');
        }

        if (empty($this->certificatePath) || !file_exists($this->certificatePath)) {
            throw new Exception('Certificado EFI não encontrado em: ' . $this->certificatePath);
        }
    }

    /**
     * Inicializa a SDK EfiPay com certificado em PEM
     * 
     * IMPORTANTE: Conforme documentação oficial EFI, o certificado deve estar em formato PEM
     * Se tiver um arquivo .p12, execute:
     *   openssl pkcs12 -in arquivo.p12 -out arquivo.pem -nodes -password pass:""
     * 
     * URL: https://dev.efipay.com.br/docs/api-pix/credenciais/
     */
    private function initializeEfiPay()
    {
        if (!class_exists('Efi\EfiPay')) {
            throw new Exception('SDK Efi\EfiPay não encontrada. Execute: composer require efipay/sdk-php-apis-efi');
        }

        // Garantir que o certificado está em PEM
        $cert_path = $this->certificatePath;
        
        // Se o caminho é .p12, tentar .pem
        if (strpos($cert_path, '.p12') !== false) {
            $pem_path = str_replace('.p12', '.pem', $cert_path);
            if (file_exists($pem_path)) {
                $cert_path = $pem_path;
            }
        }

        $config = [
            'client_id' => $this->clientId,
            'client_secret' => $this->clientSecret,
            'certificate' => $cert_path,           // Path do certificado em PEM
            'sandbox' => $this->sandbox,
        ];

        $this->efiPay = new EfiPay($config);
    }

    /**
     * Cria cobrança PIX imediata
     * 
     * @param array $params Parâmetros (geralmente vazio para este método)
     * @param array $body Corpo da requisição com dados da cobrança
     * @return array Resposta da API com txid, valor, etc.
     */
    public function pixCreateImmediateCharge(array $params = [], array $body = []): array
    {
        try {
            $response = $this->efiPay->pixCreateImmediateCharge($params, $body);
            
            // Garantir que a resposta tem a estrutura esperada
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI');
            }

            return $response;
        } catch (EfiException $e) {
            throw new Exception('Erro da API EFI ao criar cobrança PIX: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Gera QR Code para cobrança PIX
     * 
     * @param array $params Parâmetros com 'id' (location ID)
     * @return array Resposta com qrcode (imagem em base64)
     */
    public function pixGenerateQRCode(array $params = []): array
    {
        try {
            $response = $this->efiPay->pixGenerateQRCode($params);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao gerar QR Code');
            }

            return $response;
        } catch (EfiException $e) {
            throw new Exception('Erro da API EFI ao gerar QR Code: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Detalha cobrança PIX
     * 
     * @param array $params Parâmetros com 'txid'
     * @return array Detalhes da cobrança
     */
    public function pixDetailCharge(array $params = []): array
    {
        try {
            $response = $this->efiPay->pixDetailCharge($params);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao detalhar cobrança');
            }

            return $response;
        } catch (EfiException $e) {
            throw new Exception('Erro da API EFI ao detalhar cobrança PIX: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Cria link de pagamento único (boleto, cartão, etc.)
     * 
     * @param array $params Parâmetros
     * @param array $body Corpo com itens e configurações
     * @return array Resposta com payment_url
     */
    public function createOneStepLink(array $params = [], array $body = []): array
    {
        try {
            $response = $this->efiPay->createOneStepLink($params, $body);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao criar link de pagamento');
            }

            return $response;
        } catch (EfiException $e) {
            throw new Exception('Erro da API EFI ao criar link de pagamento: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Cria plano de assinatura
     * 
     * @param array $params Parâmetros
     * @param array $body Corpo com dados do plano
     * @return array Resposta com plan_id
     */
    public function createPlan(array $params = [], array $body = []): array
    {
        try {
            $response = $this->efiPay->createPlan($params, $body);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao criar plano');
            }

            return $response;
        } catch (EfiException $e) {
            throw new Exception('Erro da API EFI ao criar plano: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Cria link de assinatura em uma etapa
     * 
     * @param array $params Parâmetros com 'id' (plan_id)
     * @param array $body Corpo com dados de pagamento
     * @return array Resposta com payment_url e subscription_id
     */
    public function createOneStepSubscriptionLink(array $params = [], array $body = []): array
    {
        try {
            $response = $this->efiPay->createOneStepSubscriptionLink($params, $body);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao criar link de assinatura');
            }

            return $response;
        } catch (EfiException $e) {
            throw new Exception('Erro da API EFI ao criar link de assinatura: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Obtém notificação de cobrança/webhook
     * 
     * @param array $params Parâmetros com 'token' de notificação
     * @param array $body Corpo (geralmente vazio)
     * @return array Detalhes da notificação
     */
    public function getNotification(array $params = [], array $body = []): array
    {
        try {
            $response = $this->efiPay->getNotification($params, $body);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao obter notificação');
            }

            return $response;
        } catch (EfiException $e) {
            throw new Exception('Erro da API EFI ao obter notificação: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Cancela assinatura
     * 
     * @param array $params Parâmetros com 'id' (subscription_id)
     * @return array Resposta do cancelamento
     */
    public function cancelSubscription(array $params = []): array
    {
        try {
            $response = $this->efiPay->cancelSubscription($params);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao cancelar assinatura');
            }

            return $response;
        } catch (EfiException $e) {
            throw new Exception('Erro da API EFI ao cancelar assinatura: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Detém assinatura
     * 
     * @param array $params Parâmetros com 'id' (subscription_id)
     * @return array Detalhes da assinatura
     */
    public function detailSubscription(array $params = []): array
    {
        try {
            $response = $this->efiPay->detailSubscription($params);
            
            if (!is_array($response)) {
                throw new Exception('Resposta inválida da API EFI ao detalhar assinatura');
            }

            return $response;
        } catch (EfiException $e) {
            throw new Exception('Erro da API EFI ao detalhar assinatura: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Registra (ou atualiza) a URL de webhook PIX para uma chave.
     *
     * @param array $params ['chave' => '<sua-chave-pix>']
     * @param array $body   ['webhookUrl' => 'https://...']
     */
    public function pixConfigWebhook(array $params = [], array $body = []): array
    {
        try {
            $response = $this->efiPay->pixConfigWebhook($params, $body);
            return is_array($response) ? $response : [];
        } catch (EfiException $e) {
            throw new Exception('Erro ao registrar webhook PIX: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Consulta o webhook registrado para uma chave PIX.
     *
     * @param array $params ['chave' => '<sua-chave-pix>']
     */
    public function pixDetailWebhook(array $params = []): array
    {
        try {
            $response = $this->efiPay->pixDetailWebhook($params);
            return is_array($response) ? $response : [];
        } catch (EfiException $e) {
            throw new Exception('Erro ao consultar webhook PIX: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Remove o webhook registrado para uma chave PIX.
     *
     * @param array $params ['chave' => '<sua-chave-pix>']
     */
    public function pixDeleteWebhook(array $params = []): array
    {
        try {
            $response = $this->efiPay->pixDeleteWebhook($params);
            return is_array($response) ? $response : [];
        } catch (EfiException $e) {
            throw new Exception('Erro ao remover webhook PIX: ' . $e->getMessage(), (int)$e->getCode(), $e);
        }
    }

    /**
     * Acesso direto à API EfiPay para chamadas customizadas
     *
     * @return EfiPay Instância da SDK
     */
    public function getEfiPay(): EfiPay
    {
        return $this->efiPay;
    }

    /**
     * Retorna a chave PIX configurada
     *
     * @return string Chave PIX
     */
    public function getPixKey(): string
    {
        return $this->pixKey;
    }
}

?>
