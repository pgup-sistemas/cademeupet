<?php
require_once __DIR__ . '/config.php';
error_reporting(E_ALL);
echo 'defined: ' . (defined('EFI_PIX_KEY') ? 'SIM' : 'NAO') . PHP_EOL;
echo 'valor: ' . (defined('EFI_PIX_KEY') ? EFI_PIX_KEY : '---') . PHP_EOL;