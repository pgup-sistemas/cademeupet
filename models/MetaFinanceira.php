<?php
/**
 * Cadê Meu Pet? - Modelo de Metas Financeiras
 * Gerencia metas de arrecadação e custos operacionais
 */

class MetaFinanceira {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    /**
     * Busca todas as metas
     */
    public function findAll() {
        return $this->db->fetchAll("
            SELECT * FROM metas_financeiras 
            ORDER BY mes_referencia DESC
        ");
    }
    
    /**
     * Busca meta por ID
     */
    public function findById($id) {
        return $this->db->fetchOne("
            SELECT * FROM metas_financeiras 
            WHERE id = ?
        ", [$id]);
    }
    
    /**
     * Busca meta do mês atual
     */
    public function findCurrentMonth() {
        return $this->db->fetchOne("
            SELECT * FROM metas_financeiras 
            WHERE MONTH(mes_referencia) = MONTH(CURDATE()) 
            AND YEAR(mes_referencia) = YEAR(CURDATE())
            AND ativo = 1
        ");
    }
    
    /**
     * Busca metas por período
     */
    public function findByPeriod($startDate, $endDate) {
        return $this->db->fetchAll("
            SELECT * FROM metas_financeiras 
            WHERE mes_referencia BETWEEN ? AND ?
            ORDER BY mes_referencia ASC
        ", [$startDate, $endDate]);
    }
    
    /**
     * Cria nova meta
     */
    public function create($data) {
        return $this->db->insert('metas_financeiras', [
            'mes_referencia' => $data['mes_referencia'],
            'valor_meta' => $data['valor_meta'],
            'valor_arrecadado' => $data['valor_arrecadado'] ?? 0.00,
            'custos_servidor' => $data['custos_servidor'] ?? 0.00,
            'custos_manutencao' => $data['custos_manutencao'] ?? 0.00,
            'custos_outros' => $data['custos_outros'] ?? 0.00,
            'descricao' => $data['descricao'] ?? null,
            'ativo' => $data['ativo'] ?? 1
        ]);
    }
    
    /**
     * Atualiza meta
     */
    public function update($id, $data) {
        return $this->db->update('metas_financeiras', $data, 'id = ?', [$id]);
    }
    
    /**
     * Atualiza valor arrecadado
     */
    public function updateArrecadado($id, $valor) {
        return $this->db->update('metas_financeiras', 
            ['valor_arrecadado' => $valor], 
            'id = ?', 
            [$id]
        );
    }
    
    /**
     * Ativa/Desativa meta
     */
    public function toggleActive($id, $active) {
        return $this->db->update('metas_financeiras', 
            ['ativo' => $active], 
            'id = ?', 
            [$id]
        );
    }
    
    /**
     * Exclui meta
     */
    public function delete($id) {
        return $this->db->delete('metas_financeiras', 'id = ?', [$id]);
    }
    
    /**
     * Busca estatísticas de metas
     */
    public function getStatistics() {
        return $this->db->fetchOne("
            SELECT 
                COUNT(*) as total_metas,
                SUM(valor_meta) as total_metas_valor,
                SUM(valor_arrecadado) as total_arrecadado,
                SUM(custos_servidor + custos_manutencao + custos_outros) as total_custos,
                AVG(valor_arrecadado / valor_meta * 100) as percentual_medio
            FROM metas_financeiras 
            WHERE ativo = 1
        ");
    }
    
    /**
     * Busca metas dos últimos 12 meses
     */
    public function getLast12Months() {
        return $this->db->fetchAll("
            SELECT 
                DATE_FORMAT(mes_referencia, '%m/%Y') as mes_ano,
                valor_meta,
                valor_arrecadado,
                (valor_arrecadado / valor_meta * 100) as percentual_alcancado,
                (custos_servidor + custos_manutencao + custos_outros) as custos_totais
            FROM metas_financeiras 
            WHERE mes_referencia >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
            ORDER BY mes_referencia ASC
        ");
    }
}
?>
