<?php
require_once __DIR__ . '/config.php';
requireLogin();

$id     = (int)($_GET['id']     ?? 0);
$status = $_GET['status'] ?? '';

$ctrl = new PetLoveController();
$ctrl->alterarStatus($id, $status);
