<?php

include('config.php');

$response = [
    'status'  => 0,
    'message' => '',
    'data'    => ''
];

$status = 0;

$res_msg = [
    1   => 'Login efetuado com sucesso!',
    0   => 'Ocorreu um erro desconhecido durante este processo',
    -1  => 'Erro no recebimento de dados.',
    -2  => 'O servidor de login não responde.',
    -3  => 'O servidor de login não existe.',
    -4  => 'Ocorreu um erro durante a consulta ao servidor.',
    -5  => 'Usuário ou senha inválido.',
    -6  => 'Muitas tentativas. Tente novamente em 15 minutos.',
    2   => 'Sessão expirada',
    -8  => 'Dados incorretos de acesso ao BD.',
    -9  => 'Falha na conexão com o BD.',
    -10 => 'Erro na inserção de dados no BD.'
];

if (!isset($_POST['user']) || !isset($_POST['pass'])) {
    $status = -1;
}

// ── Conexão ao banco (necessária para rate limiting) ──────────────────────────
if ($status === 0) {
    if (
        !defined('DB_HOST') ||
        !defined('DB_USER') ||
        !defined('DB_PASSWORD') ||
        !defined('DB_NAME')
    ) {
        $status = -8;
    }
}

if ($status === 0) {
    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_errno) {
        error_log("Erro na conexão: " . $mysqli->connect_error);
        $status = -9;
    }
}

// ── Rate limiting: bloqueia IP após 5 tentativas falhas em 15 minutos ────────
if ($status === 0) {
    $mysqli->query("CREATE TABLE IF NOT EXISTS btpconecta_login_attempts (
        ip           VARCHAR(45)  NOT NULL,
        attempts     INT          NOT NULL DEFAULT 0,
        blocked_until DATETIME    DEFAULT NULL,
        PRIMARY KEY (ip)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $ip = $_SERVER['REMOTE_ADDR'];

    $stmt_rl = $mysqli->prepare(
        "SELECT attempts, blocked_until FROM btpconecta_login_attempts WHERE ip = ?"
    );
    if ($stmt_rl) {
        $stmt_rl->bind_param('s', $ip);
        $stmt_rl->execute();
        $rl_row = $stmt_rl->get_result()->fetch_assoc();
        $stmt_rl->close();

        if ($rl_row && $rl_row['blocked_until'] && strtotime($rl_row['blocked_until']) > time()) {
            $status = -6;
        }
    }
}

// ── Chamada ao servidor Senior ────────────────────────────────────────────────
if ($status === 0) {
    $user = trim(str_replace([';', '<', '>'], '', $_POST['user']));
    $pass = trim(str_replace([';', '<', '>'], '', $_POST['pass']));

    if (!str_contains($user, '@')) {
        $user .= '@btp.com.br';
    }

    $curlRequest = json_encode([
        'username' => $user,
        'password' => $pass
    ]);

    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL            => $loginUrlSend,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_ENCODING       => '',
        CURLOPT_MAXREDIRS      => 10,
        CURLOPT_TIMEOUT        => 120,
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
    $http_status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $jsonRes     = json_decode($jsonRes);
    $err         = curl_error($curl);
    curl_close($curl);

    if ($err) {
        error_log("Erro CURL: $err");
        $status = -4;
    } elseif ($http_status == 500) {
        $status = -2;
    } elseif ($http_status == 404 && !isset($jsonRes->reason) && !isset($jsonRes->errorCode)) {
        $status = -3;
    } elseif (isset($jsonRes->reason) || isset($jsonRes->errorCode)) {
        $status = -5;
    }

    // Registra tentativa falha (não conta erros de infraestrutura)
    if (in_array($status, [-5, -2, -3])) {
        $ip_escaped = $mysqli->real_escape_string($ip);
        $mysqli->query("INSERT INTO btpconecta_login_attempts (ip, attempts, blocked_until)
            VALUES ('$ip_escaped', 1, NULL)
            ON DUPLICATE KEY UPDATE
                attempts      = attempts + 1,
                blocked_until = IF(attempts + 1 >= 5, DATE_ADD(NOW(), INTERVAL 15 MINUTE), NULL)");
    }
}

// ── Gera token único ──────────────────────────────────────────────────────────
if ($status === 0) {
    $letters = "abcdefghijkmnopqrstuvxyz23456789";
    $token   = '';
    for ($i = 0; $i < 32; $i++) {
        $token .= $letters[random_int(0, strlen($letters) - 1)];
    }
}

// ── Inativa tokens expirados ──────────────────────────────────────────────────
if ($status === 0) {
    $queryExpire = "UPDATE btpconecta_tokens SET ativo = '0'
                    WHERE expires_at IS NOT NULL
                    AND expires_at < UTC_TIMESTAMP()
                    AND ativo = '1'";
    if (!$mysqli->query($queryExpire)) {
        error_log("Erro ao inativar tokens expirados: " . $mysqli->error);
    }
}

// ── Persiste token no banco ───────────────────────────────────────────────────
if ($status === 0) {
    $validate      = time() + 3600;
    $expires_at_db = gmdate('Y-m-d H:i:s', $validate);

    $stmt = $mysqli->prepare(
        "SELECT id FROM btpconecta_tokens
         WHERE token=? AND user=? AND ativo='1'
         AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())"
    );
    if (!$stmt) {
        error_log("Erro no prepare (SELECT): " . $mysqli->error);
        $status = -10;
    } else {
        $stmt->bind_param("ss", $token, $user);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            setcookie("btpUserName",  '', 1, '/');
            setcookie("btpUserToken", '', 1, '/');
            $status = 2;
        } else {
            $stmt = $mysqli->prepare(
                "INSERT INTO btpconecta_tokens (token, user, ip, ativo, expires_at)
                 VALUES (?, ?, ?, 1, ?)"
            );
            if (!$stmt) {
                error_log("Erro no prepare (INSERT): " . $mysqli->error);
                $status = -10;
            } else {
                $stmt->bind_param("ssss", $token, $user, $ip, $expires_at_db);
                if ($stmt->execute()) {
                    // Login bem-sucedido: limpa contador de tentativas do IP
                    $ip_escaped = $mysqli->real_escape_string($ip);
                    $mysqli->query("DELETE FROM btpconecta_login_attempts WHERE ip = '$ip_escaped'");

                    setcookie("btpUserName",  $user,  $validate, '/');
                    setcookie("btpUserToken", $token, $validate, '/');
                    $status = 1;
                } else {
                    error_log("Erro ao inserir token: " . $stmt->error);
                    $status = -10;
                }
            }
        }
    }
}

$response['status']  = $status;
$response['message'] = $res_msg[$status] ?? 'Erro desconhecido.';

header('Content-type: application/json; charset=utf-8');
echo json_encode($response);
?>
