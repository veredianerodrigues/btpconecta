<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0">
    <meta name="author" content="BTP">

    <title>BTP Conecta - Login</title>

    <!-- jQuery -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js"></script>

    <!-- URL do endpoint de login (novo tema) -->
    <script>
        var loginUrlSend = "/wp-content/themes/btpconecta/login/php/login.php";
    </script>

    <!-- JS e CSS de login -->
    <script src="/wp-content/themes/btpconecta/login/js/login.js" defer></script>
    <link rel="stylesheet" href="/wp-content/themes/btpconecta/login/css/login.css">

    <!-- PWA -->
    <link rel="manifest" href="/manifest.json">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="yes">
    <meta name="apple-mobile-web-app-title" content="BTPConecta">

    <!-- Google Analytics -->
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-H6PE36L56F"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-H6PE36L56F');
    </script>
</head>
<body>

<div id="login">

    <!-- Loading spinner -->
    <div id="login-form-loading">
        <svg width="48" height="48" viewBox="0 0 50 50" xmlns="http://www.w3.org/2000/svg">
            <circle cx="25" cy="25" r="20" fill="none" stroke="#bdc915" stroke-width="5"
                    stroke-dasharray="90 60" transform-origin="center">
                <animateTransform attributeName="transform" type="rotate" dur=".8s" repeatCount="indefinite" values="0;360"/>
            </circle>
        </svg>
    </div>

    <!-- Formulário de login -->
    <div id="login-form">
        <div id="login-logo">
            <img src="/wp-content/themes/btpconecta/images/logo_btp.png" alt="BTP Conecta" loading="eager">
        </div>

        <h1>Conecte-se</h1>

        <div class="login-notice">
            <strong>Atenção:</strong> Login/Senha são os mesmos dados de acesso à plataforma de gestão de pessoas <em>Senior</em>.
        </div>

        <div id="login-erro"></div>

        <div class="form-group">
            <label for="cp-login-username">LOGIN (matrícula)</label>
            <input id="cp-login-username" type="text" autocomplete="username" placeholder="Sua matrícula">
        </div>

        <div class="form-group">
            <label for="cp-login-pass">SENHA (a mesma cadastrada na Senior)</label>
            <input id="cp-login-pass" type="password" autocomplete="current-password" placeholder="Sua senha">
        </div>

        <a href="#" id="esqueci-senha-link" class="forgot-link">Esqueci minha senha</a>

        <div class="form-actions">
            <button id="btn-login" type="button">
                Entrar
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
                     stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 6l6 6-6 6"></path>
                </svg>
            </button>
        </div>
    </div><!-- /#login-form -->

    <!-- Modal: Esqueci minha senha -->
    <div id="esqueci-senha-box" class="hide">
        <button id="esqueci-senha-fechar" title="Fechar">&times;</button>
        <h1>Esqueci minha senha</h1>
        <p>
            Em caso de esquecimento de senha, siga as instruções para a recuperação diretamente na plataforma de gestão de pessoas <strong>Senior</strong>.
        </p>
        <a href="https://platform.senior.com.br/login/forgot.html?redirectTo=https%3A%2F%2Fplatform.senior.com.br%2Fsenior-x%2F&tenant=btp.com.br"
           target="_blank" rel="noopener noreferrer" class="btn-senior">
            Acessar a Plataforma Senior
        </a>
        <p>
            <strong>Atenção:</strong> Para trocar a senha na Senior, utilize seu número de matrícula seguido de <em>@btp.com.br</em><br>
            Exemplo: <strong style="color:#214549;">0000@btp.com.br</strong>
        </p>
    </div><!-- /#esqueci-senha-box -->

</div><!-- /#login -->

</body>
</html>
