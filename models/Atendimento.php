<?php
/**
 * Cadê Meu Pet? - Atendimento veterinário presencial.
 * Ver docs/modulo-atendimento-veterinario-laudo.md (Fase 2)
 */
class Atendimento
{
    private $db;

    private const CAMPOS_CLINICOS = [
        'motivo_consulta', 'anamnese', 'peso_kg', 'temperatura_c',
        'frequencia_cardiaca_bpm', 'frequencia_respiratoria_mpm',
        'mucosas', 'grau_hidratacao', 'exame_fisico', 'diagnostico',
        'conduta', 'vacinas_aplicadas', 'medicamentos_prescritos',
        'proxima_consulta_recomendada',
    ];

    public function __construct($db = null)
    {
        $this->db = $db ?: getDB();
    }

    public function abrir(array $dados): int
    {
        return (int)$this->db->insert('atendimentos', [
            'pet_id'                 => $dados['pet_id'],
            'parceiro_perfil_id'     => $dados['parceiro_perfil_id'],
            'veterinario_id'         => $dados['veterinario_id'],
            'triagem_solicitacao_id' => $dados['triagem_solicitacao_id'] ?? null,
            'motivo_consulta'        => $dados['motivo_consulta'],
            'status'                 => 'em_andamento',
        ]);
    }

    public function buscarPorId(int $id): ?array
    {
        $row = $this->db->fetchOne(
            'SELECT a.*, p.nome AS pet_nome, p.especie AS pet_especie, p.tutor_usuario_id,
                    v.nome_completo AS veterinario_nome, v.crmv_numero, v.crmv_uf
             FROM atendimentos a
             JOIN pets p ON p.id = a.pet_id
             JOIN parceiro_veterinarios v ON v.id = a.veterinario_id
             WHERE a.id = ?',
            [$id]
        );
        return $row ?: null;
    }

    /** Atualiza os campos clínicos preenchidos durante o atendimento (rascunho, ainda em_andamento). */
    public function atualizarCampos(int $id, array $dados): bool
    {
        $campos = [];
        foreach (self::CAMPOS_CLINICOS as $campo) {
            if (array_key_exists($campo, $dados)) {
                $campos[$campo] = $dados[$campo] !== '' ? $dados[$campo] : null;
            }
        }
        if (empty($campos)) {
            return false;
        }
        return $this->db->update('atendimentos', $campos, "id = ? AND status = 'em_andamento'", [$id]) !== false;
    }

    public function finalizar(int $id): bool
    {
        return $this->db->update(
            'atendimentos',
            ['status' => 'finalizado', 'finalizado_em' => date('Y-m-d H:i:s')],
            "id = ? AND status = 'em_andamento'",
            [$id]
        ) !== false;
    }

    public function cancelar(int $id): bool
    {
        return $this->db->update(
            'atendimentos',
            ['status' => 'cancelado'],
            "id = ? AND status = 'em_andamento'",
            [$id]
        ) !== false;
    }

    public function pertenceAoVeterinario(int $id, int $veterinarioId): bool
    {
        $row = $this->db->fetchOne('SELECT id FROM atendimentos WHERE id = ? AND veterinario_id = ?', [$id, $veterinarioId]);
        return (bool)$row;
    }

    public function historicoDoPet(int $petId): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, v.nome_completo AS veterinario_nome, pp.nome_fantasia AS clinica_nome
             FROM atendimentos a
             JOIN parceiro_veterinarios v ON v.id = a.veterinario_id
             JOIN parceiro_perfis pp ON pp.id = a.parceiro_perfil_id
             WHERE a.pet_id = ? AND a.status != 'cancelado'
             ORDER BY a.criado_em DESC",
            [$petId]
        ) ?: [];
    }

    public function listarPorClinica(int $parceiroPerfilId, int $limit = 50): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, p.nome AS pet_nome, v.nome_completo AS veterinario_nome
             FROM atendimentos a
             JOIN pets p ON p.id = a.pet_id
             JOIN parceiro_veterinarios v ON v.id = a.veterinario_id
             WHERE a.parceiro_perfil_id = ?
             ORDER BY a.criado_em DESC
             LIMIT ?",
            [$parceiroPerfilId, $limit]
        ) ?: [];
    }

    /** Decodifica o JSON de vacinas_aplicadas ([{nome, data, lote}]) com segurança. */
    public static function decodificarVacinas(?string $json): array
    {
        if (empty($json)) {
            return [];
        }
        $arr = json_decode($json, true);
        return is_array($arr) ? $arr : [];
    }

    /** Carteira de vacinação consolidada do pet — agrega vacinas de todos os atendimentos finalizados. */
    public function carteiraDeVacinacao(int $petId): array
    {
        $rows = $this->db->fetchAll(
            "SELECT a.vacinas_aplicadas, a.criado_em, v.nome_completo AS veterinario_nome, pp.nome_fantasia AS clinica_nome
             FROM atendimentos a
             JOIN parceiro_veterinarios v ON v.id = a.veterinario_id
             JOIN parceiro_perfis pp ON pp.id = a.parceiro_perfil_id
             WHERE a.pet_id = ? AND a.status = 'finalizado' AND a.vacinas_aplicadas IS NOT NULL
             ORDER BY a.criado_em DESC",
            [$petId]
        ) ?: [];

        $vacinas = [];
        foreach ($rows as $row) {
            foreach (self::decodificarVacinas($row['vacinas_aplicadas']) as $v) {
                if (empty($v['nome'])) {
                    continue;
                }
                $vacinas[] = [
                    'nome'    => $v['nome'],
                    'data'    => $v['data'] ?? null,
                    'lote'    => $v['lote'] ?? null,
                    'clinica' => $row['clinica_nome'],
                    'veterinario' => $row['veterinario_nome'],
                ];
            }
        }

        usort($vacinas, function ($a, $b) {
            return strcmp((string)($b['data'] ?? ''), (string)($a['data'] ?? ''));
        });

        return $vacinas;
    }

    public function listarEmAndamentoPorVeterinario(int $veterinarioId): array
    {
        return $this->db->fetchAll(
            "SELECT a.*, p.nome AS pet_nome
             FROM atendimentos a
             JOIN pets p ON p.id = a.pet_id
             WHERE a.veterinario_id = ? AND a.status = 'em_andamento'
             ORDER BY a.criado_em DESC",
            [$veterinarioId]
        ) ?: [];
    }
}
