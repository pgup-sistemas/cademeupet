<?php
/**
 * Cadê Meu Pet? - Funções Auxiliares
 * Funções utilitárias usadas em todo o sistema
 */

// ═══════════════════════════════════════════════
// SEGURANÇA
// ═══════════════════════════════════════════════

/**
 * Sanitiza entrada de dados
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Valida email
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Valida telefone brasileiro
 */
function isValidPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    return strlen($phone) >= 10 && strlen($phone) <= 11;
}

/**
 * Formata telefone brasileiro
 */
function formatPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    if (strlen($phone) == 11) {
        return '(' . substr($phone, 0, 2) . ') ' . substr($phone, 2, 5) . '-' . substr($phone, 7);
    }
    return $phone;
}

/**
 * Gera token CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

function generateDeterministicToken($seed)
{
    return hash('sha256', (string)$seed);
}

/**
 * Valida token CSRF
 */
function validateCSRFToken($token) {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        return false;
    }
    return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * Hash de senha
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
}

/**
 * Verifica senha
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Valida força da senha
 */
function isStrongPassword($password) {
    if (strlen($password) < PASSWORD_MIN_LENGTH) {
        return false;
    }
    // Deve ter letras e números
    return preg_match('/[A-Za-z]/', $password) && preg_match('/[0-9]/', $password);
}

// ═══════════════════════════════════════════════
// SESSÃO E AUTENTICAÇÃO
// ═══════════════════════════════════════════════

/**
 * Verifica se usuário está logado
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Verifica se é admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == 1;
}

/**
 * Pega ID do usuário logado
 */
function getUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Requer login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('Você precisa estar logado para acessar esta página.', MSG_WARNING);
        redirect('/login.php');
    }
}

/**
 * Requer admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('Acesso negado. Área restrita a administradores.', MSG_ERROR);
        redirect('/');
    }
}

/**
 * Faz logout
 */
function logout() {
    session_destroy();
    redirect('/');
}

function resetLoginAttempts($userId)
{
    $db = getDB();
    $db->update('usuarios', ['tentativas_login' => 0, 'bloqueado_ate' => null], 'id = ?', [$userId]);
}

// ═══════════════════════════════════════════════
// MENSAGENS FLASH
// ═══════════════════════════════════════════════

/**
 * Define mensagem flash
 */
function setFlashMessage($message, $type = MSG_INFO) {
    $_SESSION['flash_message'] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * Pega e remove mensagem flash
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $flash = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $flash;
    }
    return null;
}

/**
 * Exibe mensagem flash
 */
function displayFlashMessage() {
    $flash = getFlashMessage();
    if ($flash) {
        $alertClass = [
            MSG_SUCCESS => 'alert-success',
            MSG_ERROR => 'alert-danger',
            MSG_WARNING => 'alert-warning',
            MSG_INFO => 'alert-info'
        ][$flash['type']] ?? 'alert-info';
        
        echo "<div class='alert {$alertClass} alert-dismissible fade show' role='alert'>";
        echo sanitize($flash['message']);
        echo "<button type='button' class='btn-close' data-bs-dismiss='alert'></button>";
        echo "</div>";
    }
}

// ═══════════════════════════════════════════════
// REDIRECIONAMENTO E URL
// ═══════════════════════════════════════════════

/**
 * Redireciona para URL
 */
function redirect($url) {
    $target = (string)$url;
    if (!preg_match('#^https?://#i', $target)) {
        $target = BASE_URL . $target;
    }

    if (!headers_sent()) {
        header('Location: ' . $target);
        exit;
    } else {
        echo "<script>window.location.href='" . $target . "';</script>";
        exit;
    }
}

/**
 * Pega URL atual
 */
function currentURL() {
    return $_SERVER['REQUEST_URI'];
}

/**
 * Gera URL completa
 */
function url($path = '') {
    return BASE_URL . '/' . ltrim($path, '/');
}

// ═══════════════════════════════════════════════
// UPLOAD DE ARQUIVOS
// ═══════════════════════════════════════════════

/**
 * Valida upload de imagem
 */
function validateImageUpload($file) {
    $errors = [];
    
    // Verifica se foi enviado
    if ($file['error'] === UPLOAD_ERR_NO_FILE) {
        return ['valid' => false, 'errors' => ['Nenhum arquivo foi enviado.']];
    }
    
    // Verifica erros de upload
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['valid' => false, 'errors' => ['Erro no upload do arquivo.']];
    }

    // Verifica se arquivo temporário existe (pode vir de cache no servidor)
    if (empty($file['tmp_name']) || !is_string($file['tmp_name']) || !file_exists($file['tmp_name'])) {
        return ['valid' => false, 'errors' => ['Arquivo temporário não encontrado. Reenvie a imagem.']];
    }
    
    // Verifica tamanho
    if ($file['size'] > MAX_FILE_SIZE) {
        $maxMB = MAX_FILE_SIZE / (1024 * 1024);
        $errors[] = "Arquivo muito grande. Máximo: {$maxMB}MB";
    }
    
    // Verifica extensão
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ALLOWED_EXTENSIONS)) {
        $errors[] = "Formato inválido. Permitidos: " . implode(', ', ALLOWED_EXTENSIONS);
    }
    
    // Verifica MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if ($finfo === false) {
        return ['valid' => false, 'errors' => ['Não foi possível validar o tipo do arquivo.']];
    }

    clearstatcache(true, $file['tmp_name']);
    if (!file_exists($file['tmp_name'])) {
        finfo_close($finfo);
        return ['valid' => false, 'errors' => ['Arquivo temporário não encontrado. Reenvie a imagem.']];
    }

    $mime = @finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if ($mime === false) {
        return ['valid' => false, 'errors' => ['Não foi possível identificar o tipo do arquivo.']];
    }
    
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];
    if (!in_array($mime, $allowedMimes)) {
        $errors[] = "Tipo de arquivo inválido.";
    }
    
    return [
        'valid' => empty($errors),
        'errors' => $errors,
        'extension' => $ext
    ];
}

/**
 * Faz upload de imagem
 */
function uploadImage($file, $targetDir) {
    $validation = validateImageUpload($file);
    
    if (!$validation['valid']) {
        return ['success' => false, 'errors' => $validation['errors']];
    }
    
    // Gera nome único
    $newName = uniqid('img_', true) . '.' . $validation['extension'];
    $targetPath = $targetDir . '/' . $newName;
    
    // Move arquivo
    $moved = false;
    if (isset($file['tmp_name']) && is_string($file['tmp_name'])) {
        $moved = move_uploaded_file($file['tmp_name'], $targetPath);

        // Se não for um upload "direto" (ex.: arquivo temporário salvo no servidor), tenta rename/copy.
        if (!$moved && file_exists($file['tmp_name'])) {
            $moved = @rename($file['tmp_name'], $targetPath);
            if (!$moved) {
                $moved = @copy($file['tmp_name'], $targetPath);
                if ($moved) {
                    @unlink($file['tmp_name']);
                }
            }
        }
    }

    if ($moved) {
        // Redimensiona se necessário (somente se a extensão GD estiver disponível)
        if (function_exists('imagecreatefromjpeg') && function_exists('imagecreatetruecolor')) {
            resizeImage($targetPath, 1200, 1200);
        }
        
        return [
            'success' => true,
            'filename' => $newName,
            'path' => $targetPath
        ];
    }
    
    return ['success' => false, 'errors' => ['Erro ao salvar arquivo.']];
}

/**
 * Redimensiona imagem mantendo proporção
 */
function resizeImage($filePath, $maxWidth, $maxHeight) {
    if (!function_exists('imagecreatetruecolor')) {
        return false;
    }

    $info = getimagesize($filePath);
    $mime = $info['mime'];
    
    switch ($mime) {
        case 'image/jpeg':
            if (!function_exists('imagecreatefromjpeg')) {
                return false;
            }
            $image = imagecreatefromjpeg($filePath);
            break;
        case 'image/png':
            if (!function_exists('imagecreatefrompng')) {
                return false;
            }
            $image = imagecreatefrompng($filePath);
            break;
        case 'image/webp':
            if (!function_exists('imagecreatefromwebp')) {
                return false;
            }
            $image = imagecreatefromwebp($filePath);
            break;
        default:
            return false;
    }
    
    $width = imagesx($image);
    $height = imagesy($image);
    
    // Calcula novas dimensões mantendo proporção
    if ($width > $maxWidth || $height > $maxHeight) {
        $ratio = min($maxWidth / $width, $maxHeight / $height);
        $newWidth = $width * $ratio;
        $newHeight = $height * $ratio;
        
        $newImage = imagecreatetruecolor($newWidth, $newHeight);
        
        // Mantém transparência para PNG
        if ($mime === 'image/png') {
            imagealphablending($newImage, false);
            imagesavealpha($newImage, true);
        }
        
        imagecopyresampled($newImage, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
        
        // Salva imagem redimensionada
        switch ($mime) {
            case 'image/jpeg':
                imagejpeg($newImage, $filePath, 85);
                break;
            case 'image/png':
                imagepng($newImage, $filePath, 8);
                break;
            case 'image/webp':
                imagewebp($newImage, $filePath, 85);
                break;
        }
        
        imagedestroy($newImage);
    }
    
    imagedestroy($image);
    return true;
}

// ═══════════════════════════════════════════════
// FORMATAÇÃO E EXIBIÇÃO
// ═══════════════════════════════════════════════

/**
 * Formata data brasileira
 */
function formatDateBR($date) {
    if (empty($date)) return '';
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp);
}

/**
 * Formata data/hora brasileira
 */
function formatDateTimeBR($datetime) {
    if (empty($datetime)) return '';
    $timestamp = strtotime($datetime);
    return date('d/m/Y H:i', $timestamp);
}

/**
 * Tempo relativo (ex: "2 horas atrás")
 */
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $diff = time() - $timestamp;
    
    if ($diff < 60) return 'agora mesmo';
    if ($diff < 3600) return floor($diff / 60) . ' min atrás';
    if ($diff < 86400) return floor($diff / 3600) . 'h atrás';
    if ($diff < 2592000) return floor($diff / 86400) . ' dias atrás';
    if ($diff < 31536000) return floor($diff / 2592000) . ' meses atrás';
    return floor($diff / 31536000) . ' anos atrás';
}

/**
 * Formata dinheiro brasileiro
 */
function formatMoney($value) {
    return 'R$ ' . number_format($value, 2, ',', '.');
}

/**
 * Trunca texto
 */
function truncate($text, $length = 100, $suffix = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . $suffix;
}

// ═══════════════════════════════════════════════
// GEOLOCALIZAÇÃO
// ═══════════════════════════════════════════════

/**
 * Calcula distância entre dois pontos (Haversine)
 */
function calculateDistance($lat1, $lon1, $lat2, $lon2) {
    $earthRadius = 6371; // km
    
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    
    $a = sin($dLat/2) * sin($dLat/2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon/2) * sin($dLon/2);
    
    $c = 2 * atan2(sqrt($a), sqrt(1-$a));
    
    return $earthRadius * $c;
}

/**
 * Busca endereço por CEP (ViaCEP)
 */
function getAddressByCEP($cep) {
    $cep = preg_replace('/[^0-9]/', '', $cep);
    
    if (strlen($cep) != 8) {
        return ['error' => 'CEP inválido'];
    }
    
    $url = "https://viacep.com.br/ws/{$cep}/json/";
    $response = @file_get_contents($url);
    
    if ($response === false) {
        return ['error' => 'Erro ao consultar CEP'];
    }
    
    $data = json_decode($response, true);
    
    if (isset($data['erro'])) {
        return ['error' => 'CEP não encontrado'];
    }
    
    return $data;
}

// ═══════════════════════════════════════════════
// EMAIL
// ═══════════════════════════════════════════════

/**
 * Envia email via Resend API (https://resend.com).
 * Em localhost apenas loga — não tenta envio real.
 */
function sendEmail(string $to, string $subject, string $message, string $from = EMAIL_FROM): bool
{
    if (defined('IS_LOCAL') && IS_LOCAL) {
        error_log("[DEV EMAIL] Para: {$to} | Assunto: {$subject}");
        return true;
    }

    $apiKey = defined('RESEND_API_KEY') ? RESEND_API_KEY : '';
    if ($apiKey === '') {
        error_log('[Email] RESEND_API_KEY não configurado em .env');
        return false;
    }

    $fromName    = defined('EMAIL_FROM_NAME') ? EMAIL_FROM_NAME : 'Cadê Meu Pet?';
    $fromAddress = ($from !== '' && $from !== EMAIL_FROM) ? $from : EMAIL_FROM;

    $payload = json_encode([
        'from'    => "{$fromName} <{$fromAddress}>",
        'to'      => [$to],
        'subject' => $subject,
        'html'    => $message,
    ], JSON_UNESCAPED_UNICODE);

    $ch = curl_init('https://api.resend.com/emails');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => $payload,
        CURLOPT_HTTPHEADER     => [
            'Authorization: Bearer ' . $apiKey,
            'Content-Type: application/json',
        ],
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlErr  = curl_error($ch);
    curl_close($ch);

    if ($curlErr !== '') {
        error_log('[Email] cURL error enviando para ' . $to . ': ' . $curlErr);
        return false;
    }

    if ($httpCode !== 200 && $httpCode !== 201) {
        error_log('[Email] Resend HTTP ' . $httpCode . ' para ' . $to . ': ' . $response);
        return false;
    }

    return true;
}

// ═══════════════════════════════════════════════
// CONFIGURAÇÕES DO BANCO
// ═══════════════════════════════════════════════

/**
 * Lê um valor da tabela `configuracoes` com cache estático por request.
 */
function getConfig(string $key, string $default = ''): string
{
    static $cache = null;
    if ($cache === null) {
        try {
            $rows  = getDB()->fetchAll('SELECT chave, valor FROM configuracoes');
            $cache = [];
            foreach ($rows as $row) {
                $cache[$row['chave']] = $row['valor'];
            }
        } catch (Throwable $e) {
            $cache = [];
        }
    }
    return isset($cache[$key]) ? (string)$cache[$key] : $default;
}

// ═══════════════════════════════════════════════
// UTILIDADES
// ═══════════════════════════════════════════════

/**
 * Debug helper
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Pega IP do visitante
 */
function getClientIP() {
    $keys = ['HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            return $_SERVER[$key];
        }
    }
    return '0.0.0.0';
}

/**
 * Gera slug amigável
 */
function generateSlug($text) {
    $text = preg_replace('/[áàâãäå]/u', 'a', $text);
    $text = preg_replace('/[éèêë]/u', 'e', $text);
    $text = preg_replace('/[íìîï]/u', 'i', $text);
    $text = preg_replace('/[óòôõö]/u', 'o', $text);
    $text = preg_replace('/[úùûü]/u', 'u', $text);
    $text = preg_replace('/[ç]/u', 'c', $text);
    $text = strtolower($text);
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    $text = trim($text, '-');
    return $text;
}

?>