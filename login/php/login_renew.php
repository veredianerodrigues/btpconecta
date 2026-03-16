<?php
/**
 * BTP Conecta — login_renew.php
 * Verifica periodicamente se o token ainda é válido e estende a sessão.
 * A revalidação contra o servidor Senior foi removida — a senha não é mais
 * armazenada no banco. A sessão é renovada localmente enquanto o token existir.
 */

include('config.php');

$response             = [];
$response['status']   = 0;
$response['message']  = '';
$response['data']     = '';

$date_limit = 240; // segundos (4 minutos)

$status = 0;

$res_msg = [
     1  => 'Sessão renovada com sucesso!',
     0  => 'Ocorreu um erro desconhecido durante este processo',
    -1  => 'O usuário não está conectado.',
    -2  => 'Ocorreu um erro durante o acesso ao BD.',
    -3  => 'A conexão do usuário está dentro do período de validade.',
];

if (!isset($_COOKIE['btpUserName']) || !isset($_COOKIE['btpUserToken'])) {
    $status = -1;
}

if ($status === 0) {
    $userName  = htmlspecialchars($_COOKIE['btpUserName'],  ENT_COMPAT, 'UTF-8', true);
    $userToken = htmlspecialchars($_COOKIE['btpUserToken'], ENT_COMPAT, 'UTF-8', true);

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_errno) {
        $status = -2;
    }

    if ($status === 0) {
        $stmt = $mysqli->prepare(
            "SELECT UNIX_TIMESTAMP(date_update) AS date_timestamp
             FROM btpconecta_tokens
             WHERE token = ? AND user = ? AND ativo = '1'
             LIMIT 1"
        );
        $stmt->bind_param('ss', $userToken, $userName);
        $stmt->execute();
        $result = $stmt->get_result();
        $row    = $result->fetch_assoc();

        if (!empty($row)) {
            $timestamp  = (int) $row['date_timestamp'];
            $actualDate = time();

            if ($timestamp < ($actualDate - $date_limit)) {
                // Renova: estende expires_at por mais 1 hora
                $new_expires = gmdate('Y-m-d H:i:s', $actualDate + 3600);
                $upd = $mysqli->prepare(
                    "UPDATE btpconecta_tokens
                     SET date_update = NOW(), expires_at = ?
                     WHERE user = ? AND token = ?"
                );
                $upd->bind_param('sss', $new_expires, $userName, $userToken);
                $upd->execute();
                $status = 1;
            } else {
                $status = -3;
            }
        } else {
            setcookie("btpUserName",  '', 1, '/');
            setcookie("btpUserToken", '', 1, '/');
            $status = -1;
        }
    }
}

$response['status']  = $status;
$response['message'] = $res_msg[$status] ?? 'Erro desconhecido.';

header('Content-type: application/json; charset=utf-8');
echo json_encode($response);
?>
