<?php
/**
 * Cadê Meu Pet? - Configurações Globais
 * Arquivo principal de configuração do sistema
 */

// ═══════════════════════════════════════════════
// DETECÇÃO DE AMBIENTE (deve vir primeiro)
// ═══════════════════════════════════════════════
$_httpHost = $_SERVER['HTTP_HOST'] ?? '';
$_isLocal  = (php_sapi_name() === 'cli')
    || in_array($_httpHost, ['localhost', '127.0.0.1', '::1',
                              'localhost:8080', 'localhost:8083', 'localhost:8090',
                              '127.0.0.1:8083', '127.0.0.1:8090', 'cademeupet.local'])
    || (isset($_SERVER['SERVER_ADDR']) && in_array($_SERVER['SERVER_ADDR'], ['127.0.0.1', '::1']));
define('IS_LOCAL', $_isLocal);

// Encoding UTF-8 em toda a aplicação
ini_set('default_charset', 'UTF-8');
mb_internal_encoding('UTF-8');
mb_http_output('UTF-8');
if (!headers_sent()) {
    header('Content-Type: text/html; charset=UTF-8');
}

// Iniciar sessão com cookies seguros (apenas em contexto web)
if (php_sapi_name() !== 'cli' && session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 0,            // expira ao fechar o browser (timeout via last_activity)
        'path'     => '/',
        'domain'   => '',
        'secure'   => !$_isLocal,   // HTTPS only em produção
        'httponly' => true,         // bloqueia acesso via JavaScript
        'samesite' => 'Lax',       // protege contra CSRF cross-site
    ]);
    session_start();
}

// Configurações de erro por ambiente
if ($_isLocal) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    ini_set('display_startup_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_DEPRECATED);
}

// Timezone
date_default_timezone_set('America/Porto_Velho');

// ═══════════════════════════════════════════════
// LEITOR DE VARIÁVEIS DE AMBIENTE (.env)
// Definido aqui para estar disponível em todas as seções abaixo.
// ═══════════════════════════════════════════════
if (!function_exists('envValue')) {
    function envValue(string $key, $default = '') {
        static $vars = null;
        if ($vars === null) {
            $vars = [];
            $envFile = __DIR__ . '/.env';
            if (file_exists($envFile) && is_readable($envFile)) {
                foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
                    [$k, $v] = array_map('trim', explode('=', $line, 2));
                    $v = preg_replace('/^"(.*)"$/s', '$1', $v);
                    $v = preg_replace("/^'(.*)'$/s", '$1', $v);
                    $vars[$k] = $v;
                    @putenv("$k=$v");
                    $_ENV[$k] = $v;
                }
            }
        }
        $fromEnv = getenv($key);
        if ($fromEnv !== false) return $fromEnv;
        return $vars[$key] ?? $default;
    }
}

// ═══════════════════════════════════════════════
// BANCO DE DADOS
// ═══════════════════════════════════════════════
define('DB_HOST',    IS_LOCAL ? 'localhost'    : envValue('DB_HOST',    ''));
define('DB_NAME',    IS_LOCAL ? 'cademeupet'   : envValue('DB_NAME',    'cademeupet'));
define('DB_USER',    IS_LOCAL ? 'root'         : envValue('DB_USER',    ''));
define('DB_PASS',    IS_LOCAL ? ''             : envValue('DB_PASS',    ''));
define('DB_CHARSET', 'utf8mb4');
unset($_isLocal, $_httpHost);

// ═══════════════════════════════════════════════
// MODO SANDBOX vs PRODUÇÃO
// ═══════════════════════════════════════════════
define('EFI_SANDBOX', false); // true = Testes (Homologação), false = Produção

// ═══════════════════════════════════════════════
// CAMINHOS DO SISTEMA
// ═══════════════════════════════════════════════
define('BASE_PATH', __DIR__);
define('BASE_URL', IS_LOCAL ? 'http://localhost/cademeupet' : 'https://cademeupet.pageup.net.br');

// Informações de SEO do site
define('SITE_NAME', 'Cadê Meu Pet?');
define('SITE_DESCRIPTION', 'Perdeu ou encontrou um pet? Cadê Meu Pet? conecta tutores no Brasil.');
define('UPLOAD_PATH', BASE_PATH . '/uploads');
define('ASSETS_URL', BASE_URL . '/assets');

// ═══════════════════════════════════════════════
// UPLOAD DE ARQUIVOS
// ═══════════════════════════════════════════════
define('MAX_FILE_SIZE', 2 * 1024 * 1024); // 2MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'webp']);
define('MAX_PHOTOS_PER_AD', 2);

// ═══════════════════════════════════════════════
// LIMITES DO SISTEMA
// ═══════════════════════════════════════════════
define('MAX_ACTIVE_ADS_PER_USER', 10);
define('MIN_PUBLISH_INTERVAL', 300); // 5 minutos em segundos
define('AD_EXPIRATION_DAYS', 180); // 6 meses
define('MAX_LOGIN_ATTEMPTS', 3);
define('MAX_ALERTS_PER_USER', 5);
define('RESULTS_PER_PAGE', 20);
define('ALERT_MIN_INTERVAL_SECONDS', 3600); // 1 hora entre disparos do mesmo alerta
define('ALERT_EMAIL_MAX_RESULTS', 5);
define('MAX_PETLOVE_PETS_PER_USER', 5);
define('MAX_PETLOVE_INTERESTS_PER_USER', 20);

// Motor de match automático (perdido ↔ achado) — ver models/MatchEngine.php
define('MATCHING_JANELA_DIAS', 60);     // dias após o "perdido" em que um "achado" ainda é candidato
define('MATCHING_RAIO_KM', 50);         // raio padrão de busca de candidatos por geolocalização
define('MATCHING_SCORE_MINIMO', 40);    // score mínimo (0-100) para notificar o usuário

// ═══════════════════════════════════════════════
// DOAÇÕES
// ═══════════════════════════════════════════════
define('MIN_DONATION_AMOUNT', 2.00);

// ═══════════════════════════════════════════════
// CONFIGURAÇÕES EFI BANK - PRODUÇÃO (ROTACIONADAS)
// As chaves de produção foram removidas deste arquivo para segurança.
// Defina as credenciais via variáveis de ambiente (ex.: .env) ou um secrets manager.
// ═══════════════════════════════════════════════
define('EFI_CLIENT_ID', envValue('EFI_CLIENT_ID', ''));
define('EFI_CLIENT_SECRET', envValue('EFI_CLIENT_SECRET', ''));
define('EFI_PIX_KEY', envValue('EFI_PIX_KEY', ''));
define('EFI_WEBHOOK_TOKEN', envValue('EFI_WEBHOOK_TOKEN', ''));

// CERTIFICADO em secrets/ (protegido por .htaccess, ignorado pelo git).
// Nota: envValue retorna '' quando a chave existe mas está vazia no .env,
// por isso checamos explicitamente e aplicamos o default manual.
$__efiCertPath = trim((string)envValue('EFI_CERTIFICATE_PATH', ''));
if ($__efiCertPath === '') {
    $__efiCertPath = __DIR__ . DIRECTORY_SEPARATOR . 'secrets' . DIRECTORY_SEPARATOR . 'producao-cademeupet.pem';
}

// Normalizar caminho do certificado: se veio um path antigo/inválido, buscar na raiz do projeto.
if ($__efiCertPath !== '' && !file_exists($__efiCertPath)) {
    $baseName = basename(str_replace('\\', '/', $__efiCertPath));
    $rootCandidate = rtrim(__DIR__, '/\\') . DIRECTORY_SEPARATOR . $baseName;
    if (file_exists($rootCandidate)) {
        $__efiCertPath = $rootCandidate;
    }
}

// Preferir PEM se existir um equivalente ao P12
if ($__efiCertPath !== '' && str_ends_with(strtolower($__efiCertPath), '.p12')) {
    $pemCandidate = preg_replace('/\.p12$/i', '.pem', $__efiCertPath);
    if (is_string($pemCandidate) && file_exists($pemCandidate)) {
        $__efiCertPath = $pemCandidate;
    } else {
        $pemInRoot = rtrim(__DIR__, '/\\') . DIRECTORY_SEPARATOR . basename($pemCandidate);
        if (is_string($pemInRoot) && file_exists($pemInRoot)) {
            $__efiCertPath = $pemInRoot;
        }
    }
}

// Se for PEM e não existir, tentar PEM na raiz
if ($__efiCertPath !== '' && str_ends_with(strtolower($__efiCertPath), '.pem') && !file_exists($__efiCertPath)) {
    $pemInRoot = rtrim(__DIR__, '/\\') . DIRECTORY_SEPARATOR . basename($__efiCertPath);
    if (file_exists($pemInRoot)) {
        $__efiCertPath = $pemInRoot;
    }
}

define('EFI_CERTIFICATE_PATH', $__efiCertPath);
define('EFI_CERTIFICATE_PASSWORD', envValue('EFI_CERTIFICATE_PASSWORD', ''));

// === SPLIT PAYMENTS (Opcional) ===
// Habilite e forneça regras em JSON via .env ou variáveis de ambiente.
// Exemplo de JSON: [{"recipient_id":"12345","percentage":50},{"recipient_id":"67890","percentage":50}]
define('EFI_SPLIT_ENABLED', (bool)envValue('EFI_SPLIT_ENABLED', false));
define('EFI_SPLIT_RULES_JSON', envValue('EFI_SPLIT_RULES_JSON', ''));

// URLs Base conforme documentação oficial
define('EFI_BASE_URL', EFI_SANDBOX === true 
    ? 'https://pix-h.api.efipay.com.br'      // Homologação (testes)
    : 'https://pix.api.efipay.com.br'         // Produção
);

define('EFI_PIX_DESCRIPTION', envValue('EFI_PIX_DESCRIPTION', 'Doação para Cadê Meu Pet?'));
// URL do webhook PIX: lida do .env, com fallback para BASE_URL + caminho padrão
define('EFI_PIX_NOTIFICATION_URL', (function() {
    $fromEnv = trim((string)envValue('EFI_PIX_NOTIFICATION_URL', ''));
    if ($fromEnv !== '') return $fromEnv;
    return rtrim(IS_LOCAL ? 'https://cademeupet.pageup.net.br' : BASE_URL, '/') . '/api/efi-webhook';
})());

define('DONATION_MODAL_TITLE', 'Ajude a manter o Cadê Meu Pet? ativo!');
define('DONATION_MODAL_TEXT', 'Seja um apoiador e ajude a manter o Cadê Meu Pet? ativo!');

// ═══════════════════════════════════════════════
// EMAIL (Resend.com)
// ═══════════════════════════════════════════════
define('RESEND_API_KEY',  envValue('RESEND_API_KEY',  ''));
define('EMAIL_FROM',      envValue('EMAIL_FROM',      'noreply@cademeupet.pageup.net.br'));
define('EMAIL_FROM_NAME', envValue('EMAIL_FROM_NAME', 'Cadê Meu Pet?'));

// ═══════════════════════════════════════════════
// GOOGLE MAPS API
// ═══════════════════════════════════════════════
define('GOOGLE_MAPS_API_KEY', envValue('GOOGLE_MAPS_API_KEY', ''));

// ═══════════════════════════════════════════════
// CACHE
// ═══════════════════════════════════════════════
define('CACHE_ENABLED', true);
define('CACHE_TIME_HOME', 300); // 5 minutos
define('CACHE_TIME_SEARCH', 600); // 10 minutos

// ═══════════════════════════════════════════════
// SEGURANÇA
// ═══════════════════════════════════════════════
define('PASSWORD_MIN_LENGTH', 8);
define('SESSION_TIMEOUT', 86400); // 24 horas
define('CSRF_TOKEN_NAME', 'csrf_token');

// ═══════════════════════════════════════════════
// AUTOLOAD DE CLASSES
// ═══════════════════════════════════════════════
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/models/' . $class . '.php',
        BASE_PATH . '/controllers/' . $class . '.php',
        BASE_PATH . '/services/' . $class . '.php',
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// ═══════════════════════════════════════════════
// FUNÇÕES AUXILIARES
// ═══════════════════════════════════════════════
require_once BASE_PATH . '/includes/functions.php';
require_once BASE_PATH . '/includes/db.php';
// Tentar carregar autoload do Composer (se existir) para disponibilizar SDKs
if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}
// Carrega SDK EFI local como fallback (se composer não estiver instalado em produção)
if (file_exists(BASE_PATH . '/includes/efi.php')) {
    require_once BASE_PATH . '/includes/efi.php';
}

// ═══════════════════════════════════════════════
// CONSTANTES DE STATUS
// ═══════════════════════════════════════════════
define('STATUS_ATIVO', 'ativo');
define('STATUS_RESOLVIDO', 'resolvido');
define('STATUS_INATIVO', 'inativo');
define('STATUS_BLOQUEADO', 'bloqueado');
define('STATUS_EXPIRADO', 'expirado');

define('TIPO_PERDIDO', 'perdido');
define('TIPO_ENCONTRADO', 'encontrado');
define('TIPO_DOACAO', 'doacao');

define('ESPECIE_CACHORRO', 'cachorro');
define('ESPECIE_GATO', 'gato');
define('ESPECIE_AVE', 'ave');
define('ESPECIE_OUTRO', 'outro');

define('TAMANHO_PEQUENO', 'pequeno');
define('TAMANHO_MEDIO', 'medio');
define('TAMANHO_GRANDE', 'grande');

// ═══════════════════════════════════════════════
// MENSAGENS DO SISTEMA
// ═══════════════════════════════════════════════
define('MSG_SUCCESS', 'success');
define('MSG_ERROR', 'error');
define('MSG_WARNING', 'warning');
define('MSG_INFO', 'info');

// ═══════════════════════════════════════════════
// VERIFICAÇÃO DE AMBIENTE
// ═══════════════════════════════════════════════
if (!is_writable(UPLOAD_PATH)) {
    die('ERRO: O diretório de uploads não tem permissão de escrita!');
}

// ═══════════════════════════════════════════════
// HEADER SECURITY
// ═══════════════════════════════════════════════
if (php_sapi_name() !== 'cli') {
    header('X-Frame-Options: SAMEORIGIN');
    header('X-Content-Type-Options: nosniff');
    header('X-XSS-Protection: 1; mode=block');
}

?>