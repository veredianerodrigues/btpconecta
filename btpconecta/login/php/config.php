<?php
// Carrega as credenciais do WordPress
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-config.php');

// URL de login da API externa (Senior)
$loginUrlSend = "https://platform.senior.com.br/t/senior.com.br/bridge/1.0/rest/platform/authentication/actions/login";

// Intervalos de tempo de login
$loginIntervalMaxKeepConected = 1;   // em dias
$loginIntervalCheckCredentials = 60; // em minutos
?>
