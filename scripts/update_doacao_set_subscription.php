<?php
require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Doacao.php';

$doacaoModel = new Doacao();
$doacaoId = 34;
$subscriptionId = '1407944';

$doacaoModel->update((int)$doacaoId, ['efi_subscription_id' => $subscriptionId]);

echo "Doação $doacaoId atualizada com subscription_id $subscriptionId\n";
