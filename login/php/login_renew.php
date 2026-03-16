<?php
/**
 * BTP Conecta — login_renew.php
 * Verifica periodicamente se o token ainda é válido na plataforma Senior.
 */

include('config.php');

$response             = [];
$response['status']   = 0;
$response['message']  = '';
$response['data']     = '';

$date_limit = 240; // segundos (4 minutos)

$status = 0;

$res_msg = [
     1  => 'Login verificado com sucesso!',
     0  => 'Ocorreu um erro desconhecido durante este processo',
    -1  => 'O usuário não está conectado.',
    -2  => 'Ocorreu um erro durante o acesso ao BD.',
    -3  => 'A conexão do usuário está dentro do período de validade.',
];

if (!(isset($_COOKIE['btpUserName'])) || !(isset($_COOKIE['btpUserToken']))) {
    $status = -1;
}

function btpconecta_loggin_check(string $user, string $pass): bool {
    $user = trim($user);
    $pass = trim($pass);

    if ($user === '' || $pass === '') {
        return false;
    }

    if (!str_contains($user, '@')) {
        $user .= '@btp.com.br';
    }

    $curlRequest = json_encode(['username' => $user, 'password' => $pass]);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => "https://platform.senior.com.br/t/senior.com.br/bridge/1.0/rest/platform/authentication/actions/login",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_CUSTOMREQUEST  => "POST",
        CURLOPT_POSTFIELDS     => $curlRequest,
        CURLOPT_HTTPHEADER     => [
            "Accept: */*",
            "Content-Type: application/json",
            "User-Agent: PHP"
        ],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);

    $jsonRes     = curl_exec($curl);
    $jsonRes     = json_decode($jsonRes);
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $err         = curl_error($curl);
    curl_close($curl);

    if ($err || $http_status === 500 || $http_status === 404) {
        return false;
    }

    if (isset($jsonRes->reason) || isset($jsonRes->errorCode)) {
        return false;
    }

    return true;
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
            "SELECT *, UNIX_TIMESTAMP(date_update) AS date_timestamp
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
                $user     = $row['user'];
                $pass_raw = $row['pass'];
                $pass     = base64_decode($pass_raw);

                $res = btpconecta_loggin_check($user, $pass);

                if (!$res) {
                    setcookie("btpUserName",  '', 0, '/');
                    setcookie("btpUserToken", '', 0, '/');
                } else {
                    $upd = $mysqli->prepare("UPDATE btpconecta_tokens SET date_update = NOW() WHERE user = ? AND token = ?");
                    $upd->bind_param('ss', $userName, $userToken);
                    $upd->execute();
                }

                $status = 1;
            } else {
                $status = -3;
            }
        } else {
            setcookie("btpUserName",  '', 0, '/');
            setcookie("btpUserToken", '', 0, '/');
            $status = -1;
        }
    }
}

$response['status']  = $status;
$response['message'] = $res_msg[$status] ?? 'Erro desconhecido.';

header('Content-type: application/json; charset=utf-8');
echo json_encode($response);
?>
