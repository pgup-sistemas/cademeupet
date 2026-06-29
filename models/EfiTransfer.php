<?php
class EfiTransfer
{
    private $db;

    public function __construct()
    {
        $this->db = getDB();
    }

    public function create(string $txid, array $payload, ?string $status = null)
    {
        return $this->db->insert('efi_transfers', [
            'txid' => $txid,
            'payload' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'status' => $status,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function findByTxid(string $txid)
    {
        return $this->db->fetchOne('SELECT * FROM efi_transfers WHERE txid = ? ORDER BY created_at DESC LIMIT 1', [$txid]);
    }
}
