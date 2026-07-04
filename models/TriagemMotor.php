<?php
/**
 * Cadê Meu Pet? - Motor de triagem veterinária.
 *
 * Regra de decisão DETERMINÍSTICA (não é IA/ML) — auditável e defensável
 * legalmente. Sintomas de risco de vida sempre geram 'emergencia_imediata',
 * independente das demais respostas do formulário. Na dúvida, a regra
 * sempre escala a urgência para cima, nunca para baixo.
 *
 * IMPORTANTE: isto NÃO é diagnóstico veterinário. É apenas uma triagem de
 * direcionamento (para onde procurar ajuda), sempre acompanhada de
 * disclaimer explícito na camada de apresentação.
 */
class TriagemMotor
{
    /** Sintomas que, sozinhos, sempre indicam emergência imediata. */
    private const SINTOMAS_CRITICOS = [
        'sangramento_intenso',
        'dificuldade_respirar',
        'convulsao',
        'trauma_grave',
        'suspeita_envenenamento',
        'inconsciente',
    ];

    /** Sintomas de urgência alta (procurar atendimento o quanto antes). */
    private const SINTOMAS_ALTA = [
        'vomito_persistente',
        'dor_intensa',
        'nao_consegue_andar',
        'inchaco_abdominal',
        'febre_alta',
    ];

    /** Sintomas de urgência moderada. */
    private const SINTOMAS_MODERADA = [
        'diarreia',
        'apatia',
        'ferida_leve',
        'coceira_intensa',
        'nao_come',
    ];

    public static function listaSintomas(): array
    {
        return [
            'sangramento_intenso'     => 'Sangramento intenso',
            'dificuldade_respirar'    => 'Dificuldade para respirar',
            'convulsao'               => 'Convulsão',
            'trauma_grave'            => 'Trauma grave (atropelamento, queda, briga)',
            'suspeita_envenenamento'  => 'Suspeita de envenenamento/intoxicação',
            'inconsciente'            => 'Desmaiado ou não responde',
            'vomito_persistente'      => 'Vômito persistente',
            'dor_intensa'             => 'Sinais claros de dor intensa',
            'nao_consegue_andar'      => 'Não consegue andar/se levantar',
            'inchaco_abdominal'       => 'Barriga inchada/estufada',
            'febre_alta'              => 'Febre alta (aparente)',
            'diarreia'                => 'Diarreia',
            'apatia'                  => 'Apatia/desânimo',
            'ferida_leve'             => 'Ferida leve/machucado superficial',
            'coceira_intensa'         => 'Coceira intensa/alergia',
            'nao_come'                => 'Não está comendo',
        ];
    }

    /**
     * Classifica o nível de urgência a partir dos sintomas marcados.
     * $sintomas: array de chaves (ver listaSintomas()).
     */
    public static function classificarUrgencia(array $sintomas): string
    {
        foreach (self::SINTOMAS_CRITICOS as $s) {
            if (in_array($s, $sintomas, true)) {
                return 'critica';
            }
        }
        foreach (self::SINTOMAS_ALTA as $s) {
            if (in_array($s, $sintomas, true)) {
                return 'alta';
            }
        }
        foreach (self::SINTOMAS_MODERADA as $s) {
            if (in_array($s, $sintomas, true)) {
                return 'moderada';
            }
        }
        return 'baixa';
    }

    /**
     * Decide o direcionamento combinando urgência + renda declarada +
     * disponibilidade de clínicas parceiras na cidade do tutor.
     *
     * @param string   $nivelUrgencia          'baixa'|'moderada'|'alta'|'critica'
     * @param bool|null $rendaBaixaDeclarada
     * @param bool     $existeClinicaParceira  se há ao menos 1 clínica parceira ativa na região
     * @param bool     $existeLocalPublico     se há local público cadastrado na região
     */
    public static function decidirDirecionamento(
        string $nivelUrgencia,
        ?bool $rendaBaixaDeclarada,
        bool $existeClinicaParceira,
        bool $existeLocalPublico
    ): string {
        // Sintoma crítico: sempre emergência, não importa mais nada.
        if ($nivelUrgencia === 'critica') {
            return 'emergencia_imediata';
        }

        if ($nivelUrgencia === 'alta') {
            // Urgência alta: prioriza atendimento mais rápido disponível.
            if ($existeClinicaParceira && $existeLocalPublico) {
                return 'ambos';
            }
            if ($existeClinicaParceira) {
                return 'parceiro_privado';
            }
            if ($existeLocalPublico) {
                return 'publico';
            }
            // Nenhuma opção cadastrada na região: ainda assim orienta a buscar atendimento urgente.
            return 'ambos';
        }

        // Urgência moderada/baixa: renda baixa declarada direciona ao público primeiro,
        // mas só se houver opção pública real na região.
        if ($rendaBaixaDeclarada === true && $existeLocalPublico) {
            return 'publico';
        }

        if ($existeClinicaParceira && $existeLocalPublico) {
            return 'ambos';
        }
        if ($existeClinicaParceira) {
            return 'parceiro_privado';
        }
        if ($existeLocalPublico) {
            return 'publico';
        }

        // Sem nenhuma opção cadastrada na região: ainda assim retorna "ambos"
        // para que a tela mostre orientação genérica de buscar um veterinário.
        return 'ambos';
    }
}
