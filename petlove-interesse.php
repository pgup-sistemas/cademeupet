<?php
require_once __DIR__ . '/config.php';
requireLogin();

$ic  = new PetLoveInteresseController();
$res = $ic->manifestar($_POST);

setFlashMessage($res['msg'], $res['ok'] ? MSG_SUCCESS : MSG_ERROR);

$petId = (int)($_POST['petlove_id'] ?? 0);
redirect($petId > 0 ? '/petlove/' . $petId : '/petlove');
