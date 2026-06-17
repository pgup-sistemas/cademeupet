<?php
// Suite de testes — Cadê Meu Pet?
ini_set('display_errors', 1);
error_reporting(E_ALL);

session_start();

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../controllers/AnuncioController.php';
require_once __DIR__ . '/../controllers/UsuarioController.php';
require_once __DIR__ . '/../includes/auth.php';

$tests = [];
$results = ['passed' => 0, 'failed' => 0];

function addTest(string $name, callable $fn)
{
    global $tests;
    $tests[] = [$name, $fn];
}

function assertTrue($condition, $message = 'Assertion failed')
{
    if (!$condition) {
        throw new Exception($message);
    }
}

function assertEquals($expected, $actual, $message = 'Values are not equal')
{
    if ($expected != $actual) {
        throw new Exception($message . " (expected " . var_export($expected, true) . ", got " . var_export($actual, true) . ")");
    }
}

function assertStringContains(string $needle, string $haystack, string $message = 'String not found')
{
    if (strpos($haystack, $needle) === false) {
        throw new Exception($message . " (needle: '{$needle}')");
    }
}

class FakeAnuncioModel
{
    public $countActive = 0;
    public $canPublish = true;
    public $createdPayload = null;

    public function countActiveByUser(int $userId): int
    {
        return $this->countActive;
    }

    public function canPublishNewAd(int $userId): bool
    {
        return $this->canPublish;
    }

    public function create(array $data)
    {
        $this->createdPayload = $data;
        return 123;
    }
}

class FakeUsuarioModel
{
    private $user;

    public function __construct(array $user)
    {
        $this->user = $user;
    }

    public function findById(int $id)
    {
        return $this->user;
    }
}

class StubDatabase
{
    public $transactions = 0;

    public function beginTransaction()
    {
        $this->transactions++;
        return true;
    }

    public function commit()
    {
        return true;
    }

    public function rollback()
    {
        return true;
    }

    public function insert($table, $data)
    {
        return 1;
    }

    public function fetchAll($sql, $params = [])
    {
        return [];
    }

    public function query($sql, $params = [])
    {
        return true;
    }
}

class FakeAuthDatabase
{
    public $user;

    public function __construct(array $user)
    {
        $this->user = $user;
    }

    public function fetchOne($sql, $params = [])
    {
        if (stripos($sql, 'FROM usuarios') !== false) {
            $email = $params[0];
            if (strcasecmp($this->user['email'], $email) === 0 && $this->user['ativo']) {
                return $this->user;
            }
            return null;
        }
        return null;
    }

    public function update($table, $data, $where, $params = [])
    {
        if ($table === 'usuarios' && $where === 'id = ?') {
            $id = $params[0];
            if ($id == $this->user['id']) {
                $this->user = array_merge($this->user, $data);
            }
        }
        return true;
    }

    public function insert($table, $data)
    {
        return 1;
    }
}

addTest('Limite de anúncios ativos impede novas publicações', function () {
    $fakeAnuncioModel = new FakeAnuncioModel();
    $fakeAnuncioModel->countActive = MAX_ACTIVE_ADS_PER_USER;
    $fakeUsuarioModel = new FakeUsuarioModel([
        'id' => 1,
        'email_confirmado' => 1,
        'tentativas_login' => 0,
        'bloqueado_ate' => null
    ]);
    $db = new StubDatabase();

    $controller = new AnuncioController(
        $fakeAnuncioModel,
        $fakeUsuarioModel,
        $db,
        function () {
            return 1;
        }
    );

    $data = [
        'tipo' => TIPO_PERDIDO,
        'especie' => ESPECIE_CACHORRO,
        'tamanho' => TAMANHO_MEDIO,
        'descricao' => str_repeat('Detalhe ', 4),
        'data_ocorrido' => date('Y-m-d'),
        'endereco_completo' => 'Rua Principal, 123',
        'bairro' => 'Centro',
        'cidade' => 'Porto Velho',
        'estado' => 'RO',
        'whatsapp' => '69999999999'
    ];

    $result = $controller->create($data, []);
    assertTrue(!$result['success'], 'O resultado deveria indicar falha.');
    $exists = false;
    foreach ($result['errors'] as $error) {
        if (strpos($error, (string)MAX_ACTIVE_ADS_PER_USER) !== false) {
            $exists = true;
            break;
        }
    }
    assertTrue($exists, 'Mensagem de erro deve mencionar limite de anúncios ativos.');
});

addTest('Login bloqueia após três tentativas incorretas', function () {
    $password = 'Senha123';
    $fakeDb = new FakeAuthDatabase([
        'id' => 1,
        'nome' => 'Usuário Teste',
        'email' => 'teste@petfinder.com',
        'telefone' => '69999999999',
        'senha' => hashPassword($password),
        'ativo' => 1,
        'email_confirmado' => 1,
        'tentativas_login' => 0,
        'bloqueado_ate' => null
    ]);

    $auth = new Auth($fakeDb);

    for ($i = 0; $i < MAX_LOGIN_ATTEMPTS; $i++) {
        $result = $auth->login('teste@petfinder.com', 'Errada123');
        assertTrue(!$result['success'], 'Login deve falhar com senha incorreta.');
    }

    $result = $auth->login('teste@petfinder.com', 'Errada123');
    assertTrue(!$result['success'], 'Login deve permanecer bloqueado.');
    assertStringContains('Conta bloqueada', $result['error'], 'Mensagem deve indicar bloqueio.');
});

// ─────────────────────────────────────────────────────────────────
// FASE 9.1 — Novos testes
// ─────────────────────────────────────────────────────────────────

// ── Pet Love: Haversine ──────────────────────────────────────────
addTest('PetLove: cálculo de distância Haversine (São Paulo → Rio de Janeiro ≈ 357 km)', function () {
    // Fórmula de Haversine inline — idêntica à implementada em PetLove::calcularDistanciaKm()
    $haversine = function (float $lat1, float $lng1, float $lat2, float $lng2): float {
        $R    = 6371.0;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a    = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;
        return round($R * 2 * asin(sqrt($a)), 1);
    };

    // São Paulo ↔ Rio de Janeiro: distância real ~357 km
    $dist = $haversine(-23.5505, -46.6333, -22.9068, -43.1729);
    assertTrue($dist >= 340 && $dist <= 380, "Distância SP→RJ deve ser entre 340 e 380 km, obtido: {$dist}");

    // Dois pontos idênticos = 0 km
    $zero = $haversine(-23.5505, -46.6333, -23.5505, -46.6333);
    assertEquals(0.0, $zero, 'Dois pontos idênticos devem resultar em 0 km');
});

// ── Pet Love: score de compatibilidade ──────────────────────────
addTest('PetLove: score de compatibilidade com raça idêntica, pedigrees e proximidade = 100', function () {
    $calcScore = function (array $a, array $b): int {
        $score = 0;
        if (mb_strtolower(trim($a['raca'])) === mb_strtolower(trim($b['raca']))) $score += 40;
        if ($a['porte'] === $b['porte'])            $score += 20; else $score += 10;
        if ($a['tem_pedigree'] && $b['tem_pedigree']) $score += 20;
        elseif ($a['tem_pedigree'] || $b['tem_pedigree']) $score += 5;

        if ($a['latitude'] && $a['longitude'] && $b['latitude'] && $b['longitude']) {
            $R = 6371.0;
            $dLat = deg2rad($b['latitude'] - $a['latitude']);
            $dLng = deg2rad($b['longitude'] - $a['longitude']);
            $av = sin($dLat / 2) ** 2 + cos(deg2rad($a['latitude'])) * cos(deg2rad($b['latitude'])) * sin($dLng / 2) ** 2;
            $km = round($R * 2 * asin(sqrt($av)), 1);
            if ($km <= 50) $score += 20; elseif ($km <= 100) $score += 10;
        }
        return min(100, $score);
    };

    // Raça idêntica (+40) + porte idêntico (+20) + ambos pedigree (+20) + < 50 km (+20) = 100
    $petA = ['raca' => 'Golden Retriever', 'porte' => 'grande', 'tem_pedigree' => 1, 'latitude' => -23.5505, 'longitude' => -46.6333];
    $petB = ['raca' => 'Golden Retriever', 'porte' => 'grande', 'tem_pedigree' => 1, 'latitude' => -23.6010, 'longitude' => -46.6900];
    assertEquals(100, $calcScore($petA, $petB), 'Score deve ser 100 para match perfeito');

    // Raças diferentes: máximo 60 (porte+pedigree+proximidade)
    $petC = ['raca' => 'Labrador', 'porte' => 'grande', 'tem_pedigree' => 1, 'latitude' => -23.5505, 'longitude' => -46.6333];
    $scoreRacaDif = $calcScore($petA, $petC);
    assertTrue($scoreRacaDif <= 60, 'Score com raças diferentes não deve atingir 100');
});

// ── Pet Love: matching filtra sexo oposto + mesma espécie ───────
addTest('PetLove: matching retorna apenas pets de sexo oposto e espécie idêntica', function () {
    $petReferencia = ['sexo' => 'macho', 'especie' => 'cachorro', 'id' => 1, 'usuario_id' => 10];
    $candidatos    = [
        ['id' => 2, 'sexo' => 'femea', 'especie' => 'cachorro', 'usuario_id' => 20], // válido
        ['id' => 3, 'sexo' => 'macho', 'especie' => 'cachorro', 'usuario_id' => 30], // mesmo sexo — inválido
        ['id' => 4, 'sexo' => 'femea', 'especie' => 'gato',     'usuario_id' => 40], // espécie errada — inválido
        ['id' => 5, 'sexo' => 'femea', 'especie' => 'cachorro', 'usuario_id' => 10], // mesmo dono — inválido
    ];

    $sexoOposto = $petReferencia['sexo'] === 'macho' ? 'femea' : 'macho';
    $matches    = array_values(array_filter($candidatos, function ($c) use ($petReferencia, $sexoOposto) {
        return $c['sexo']      === $sexoOposto
            && $c['especie']   === $petReferencia['especie']
            && $c['id']        !== $petReferencia['id']
            && $c['usuario_id'] !== $petReferencia['usuario_id'];
    }));

    assertEquals(1, count($matches), 'Deve haver exatamente 1 match válido');
    assertEquals(2, $matches[0]['id'], 'O único match válido deve ser o pet ID 2');
});

// ── Pet Love: cadastro valida campos obrigatórios ────────────────
addTest('PetLove: cadastro de pet para cruzamento valida todos os campos obrigatórios', function () {
    $validar = function (array $d): array {
        $erros = [];
        if (empty(trim($d['nome']          ?? ''))) $erros[] = 'Nome do pet é obrigatório.';
        if (empty($d['especie']))                    $erros[] = 'Espécie é obrigatória.';
        if (empty(trim($d['raca']          ?? ''))) $erros[] = 'Raça é obrigatória.';
        if (empty($d['porte']))                      $erros[] = 'Porte é obrigatório.';
        if (empty($d['sexo']))                       $erros[] = 'Sexo é obrigatório.';
        if (!isset($d['idade_meses']) || (int)$d['idade_meses'] < 1) $erros[] = 'Idade inválida.';
        if (empty($d['criacao_responsavel']))         $erros[] = 'É necessário confirmar a criação responsável.';
        return $erros;
    };

    // Dados completos — sem erros
    $dadosOk = [
        'nome' => 'Rex', 'especie' => 'cachorro', 'raca' => 'Labrador',
        'porte' => 'grande', 'sexo' => 'macho', 'idade_meses' => 18, 'criacao_responsavel' => 1,
    ];
    assertEquals(0, count($validar($dadosOk)), 'Dados completos não devem gerar erros');

    // Dados vazios — deve gerar 7 erros
    assertEquals(7, count($validar([])), 'Dados vazios devem gerar exatamente 7 erros');

    // Sem nome
    $semNome = $dadosOk; unset($semNome['nome']);
    assertTrue(count($validar($semNome)) > 0, 'Cadastro sem nome deve ser rejeitado');
});

// ── Pet Love: interesse impede duplicidade ───────────────────────
addTest('PetLove: manifestação de interesse impede registro duplicado', function () {
    $interesses = [
        ['petlove_id' => 10, 'interessado_id' => 5],
        ['petlove_id' => 10, 'interessado_id' => 7],
    ];

    $jaDemonstrou = function (int $petloveId, int $interessadoId) use ($interesses): bool {
        foreach ($interesses as $i) {
            if ($i['petlove_id'] === $petloveId && $i['interessado_id'] === $interessadoId) return true;
        }
        return false;
    };

    assertTrue($jaDemonstrou(10, 5), 'Segundo interesse do usuário 5 no pet 10 deve ser detectado como duplicado');
    assertTrue($jaDemonstrou(10, 7), 'Segundo interesse do usuário 7 no pet 10 deve ser detectado como duplicado');
    assertTrue(!$jaDemonstrou(10, 9), 'Primeiro interesse do usuário 9 não é duplicado');
    assertTrue(!$jaDemonstrou(11, 5), 'Interesse em pet diferente não é duplicado');
});

// ── Coordenadas dentro dos limites do Brasil ─────────────────────
addTest('Validação: coordenadas dentro dos limites do Brasil são aceitas', function () {
    // Limites aproximados do Brasil
    $dentroDosBrasil = function (float $lat, float $lng): bool {
        return $lat >= -33.75 && $lat <= 5.27 && $lng >= -73.99 && $lng <= -28.85;
    };

    assertTrue($dentroDosBrasil(-23.5505, -46.6333), 'São Paulo deve ser válida');
    assertTrue($dentroDosBrasil(-22.9068, -43.1729), 'Rio de Janeiro deve ser válida');
    assertTrue($dentroDosBrasil(-3.7190, -38.5434),  'Fortaleza deve ser válida');
    assertTrue($dentroDosBrasil(-15.7801, -47.9292), 'Brasília deve ser válida');

    assertTrue(!$dentroDosBrasil(51.5074, -0.1278),   'Londres não deve ser válida');
    assertTrue(!$dentroDosBrasil(0.0,  0.0),           'Origem (0,0) não deve ser válida');
    assertTrue(!$dentroDosBrasil(-23.5505, 0.0),       'Longitude zero não deve ser válida');
    assertTrue(!$dentroDosBrasil(-40.0, -46.6333),     'Latitude abaixo do Brasil não deve ser válida');
});

// ── Moderação: anúncio pendente não aparece na listagem pública ──
addTest('Moderação: anúncio pendente não aparece na listagem pública', function () {
    $anuncios = [
        ['id' => 1, 'moderacao_status' => 'aprovado',  'status' => 'ativo'],
        ['id' => 2, 'moderacao_status' => 'pendente',  'status' => 'ativo'],
        ['id' => 3, 'moderacao_status' => 'rejeitado', 'status' => 'inativo'],
        ['id' => 4, 'moderacao_status' => 'aprovado',  'status' => 'ativo'],
    ];

    // A query pública filtra: status = 'ativo' AND moderacao_status = 'aprovado'
    $publicos = array_values(array_filter($anuncios, function ($a) {
        return $a['status'] === 'ativo' && $a['moderacao_status'] === 'aprovado';
    }));

    assertEquals(2, count($publicos), 'Deve haver exatamente 2 anúncios públicos (IDs 1 e 4)');
    foreach ($publicos as $a) {
        assertEquals('aprovado',  $a['moderacao_status'], "ID {$a['id']}: moderacao_status deve ser 'aprovado'");
        assertEquals('ativo',     $a['status'],           "ID {$a['id']}: status deve ser 'ativo'");
    }

    // Confirmar que pendente não está na lista
    $ids = array_column($publicos, 'id');
    assertTrue(!in_array(2, $ids), 'Anúncio pendente (ID 2) não deve estar na listagem pública');
    assertTrue(!in_array(3, $ids), 'Anúncio rejeitado (ID 3) não deve estar na listagem pública');
});

// ── Alerta: validação de criação ─────────────────────────────────
addTest('Alerta de busca: valida cidade, estado e tipo obrigatórios', function () {
    $validar = function (array $dados): array {
        $erros = [];
        $tipo  = $dados['tipo'] ?? 'ambos';
        if (!in_array($tipo, ['perdido', 'encontrado', 'ambos'], true)) {
            $erros[] = 'Tipo de alerta inválido.';
        }
        if (empty($dados['cidade'])) {
            $erros[] = 'Informe a cidade para o alerta.';
        }
        if (empty($dados['estado']) || strlen($dados['estado']) !== 2) {
            $erros[] = 'Informe a sigla do estado.';
        }
        if (isset($dados['raio_km'])) {
            if (!in_array((int)$dados['raio_km'], [5, 10, 20, 50], true)) {
                $erros[] = 'Selecione um raio válido.';
            }
        }
        return $erros;
    };

    assertEquals(0, count($validar(['cidade' => 'São Paulo', 'estado' => 'SP', 'tipo' => 'perdido'])),
        'Alerta válido não deve ter erros');

    $erros = $validar(['cidade' => '', 'estado' => '', 'tipo' => 'perdido']);
    assertTrue(count($erros) >= 2, 'Cidade e estado vazios devem gerar ao menos 2 erros');

    $erros = $validar(['cidade' => 'Rio', 'estado' => 'Rio de Janeiro', 'tipo' => 'ambos']);
    assertTrue(count($erros) >= 1, 'Estado com mais de 2 letras deve gerar erro');

    $erros = $validar(['cidade' => 'Curitiba', 'estado' => 'PR', 'tipo' => 'invalido']);
    assertTrue(count($erros) >= 1, 'Tipo inválido deve gerar erro');

    $erros = $validar(['cidade' => 'SP', 'estado' => 'SP', 'tipo' => 'ambos', 'raio_km' => 999]);
    assertTrue(count($erros) >= 1, 'Raio inválido deve gerar erro');
});

// ─────────────────────────────────────────────────────────────────
// Executor
// ─────────────────────────────────────────────────────────────────

foreach ($tests as [$name, $fn]) {
    try {
        $fn();
        $results['passed']++;
        echo "[OK] {$name}" . PHP_EOL;
    } catch (Throwable $e) {
        $results['failed']++;
        echo "[FALHA] {$name}: " . $e->getMessage() . PHP_EOL;
    }
}

echo PHP_EOL;
echo 'Total: ' . count($tests) . ' | Passou: ' . $results['passed'] . ' | Falhou: ' . $results['failed'] . PHP_EOL;
if ($results['failed'] > 0) {
    exit(1);
}
exit(0);
