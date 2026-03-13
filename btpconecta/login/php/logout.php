<?php
include('config.php');

if (isset($_COOKIE['btpUserName']) && isset($_COOKIE['btpUserToken'])) {
    $userName  = htmlspecialchars($_COOKIE['btpUserName'],  ENT_COMPAT, 'UTF-8', true);
    $userToken = htmlspecialchars($_COOKIE['btpUserToken'], ENT_COMPAT, 'UTF-8', true);

    try {
        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);

        if ($mysqli->connect_errno) {
            throw new Exception("Erro na conexão com o banco de dados");
        }

        $stmt = $mysqli->prepare("UPDATE btpconecta_tokens SET ativo = '0' WHERE token = ? AND user = ? AND ativo = '1'");
        $stmt->bind_param("ss", $userToken, $userName);
        $stmt->execute();

    } catch (Exception $e) {
        error_log("Erro durante logout: " . $e->getMessage());
    }
}

setcookie("btpUserName",  '', time() - 3600, '/');
setcookie("btpUserToken", '', time() - 3600, '/');

header("Location: /");
exit;
