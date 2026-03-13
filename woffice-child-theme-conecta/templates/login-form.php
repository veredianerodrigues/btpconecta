<!DOCTYPE HTML>
<html lang="pt-br">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
    <meta name="description" content="" />
    <meta name="author" content="BTP" />

    <script type="text/javascript" src="/wp-content/themes/woffice-child-theme-conecta/templates/assets/jquery.min.js"></script>
    <script type="text/javascript">
        var loginUrlSend = "/wp-content/themes/woffice-child-theme-conecta/login/php/login.php";
    </script>
    <script type="text/javascript" src="/wp-content/themes/woffice-child-theme-conecta/login/js/login.js"></script>
    <link rel="stylesheet" type="text/css" href="/wp-content/themes/woffice-child-theme-conecta/login/css/login.css">

    <link rel="manifest" href="/../manifest.json?__v201910171615">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="yes">
    <meta name="apple-mobile-web-app-title" content="BTPConecta">

    <link rel="apple-touch-icon" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/touch-icon-iphone.png?__v201910171615">
    <link rel="apple-touch-icon" sizes="152x152" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/touch-icon-ipad.png?__v201910171615">
    <link rel="apple-touch-icon" sizes="180x180" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/touch-icon-iphone-retina.png?__v201910171615">
    <link rel="apple-touch-icon" sizes="167x167" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/touch-icon-ipad-retina.png?__v201910171615">
    <link rel="apple-touch-icon-precomposed" sizes="152x152" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-icon-152x152-precomposed.png?__v201910171615">
    <link rel="apple-touch-icon-precomposed" sizes="144x144" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-icon-144x144-precomposed.png?__v201910171615">
    <link rel="apple-touch-icon-precomposed" sizes="120x120" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-icon-120x120-precomposed.png?__v201910171615">
    <link rel="apple-touch-icon-precomposed" sizes="114x114" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-icon-114x114-precomposed.png?__v201910171615">
    <link rel="apple-touch-icon-precomposed" sizes="76x76" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-icon-76x76-precomposed.png?__v201910171615">
    <link rel="apple-touch-icon-precomposed" sizes="72x72" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-icon-72x72-precomposed.png?__v201910171615">
    <link rel="apple-touch-icon-precomposed" href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-icon-precomposed.png?__v201910171615">

    <link href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-startup-image-320x460.png?__v201910171615"
          media="(device-width: 320px)" rel="apple-touch-startup-image">
    <link href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-startup-image-640x920.png?__v201910171615"
          media="(device-width: 320px) and (device-height: 460px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
    <link href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-startup-image-640x1096.png?__v201910171615"
          media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
    <link href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-startup-image-768x1004.png?__v201910171615"
          media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:portrait)" rel="apple-touch-startup-image" />
    <link href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-startup-image-1024x748.png?__v201910171615"
          media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:landscape)" rel="apple-touch-startup-image" />
    <link href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-startup-image-1536x2008.png?__v201910171615"
          media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:portrait) and (-webkit-min-device-pixel-ratio: 2)"
          rel="apple-touch-startup-image" />
    <link href="/wp-content/themes/woffice-child-theme-conecta/images/icon/apple-touch-startup-image-2048x1496.png?__v201910171615"
          media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:landscape) and (-webkit-min-device-pixel-ratio: 2)"
          rel="apple-touch-startup-image" />

    <link rel="stylesheet" type="text/css" href="/wp-content/themes/woffice-child-theme-conecta/templates/assets/semantic.min.css">

    <title>BTP Conecta - Login</title>

    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag("js", new Date());
    gtag("config", "G-H6PE36L56F");
    </script>

</head>
<body>
    <div id="login">
        <div id="login-form-loading"><img src="/wp-content/themes/woffice-child-theme-conecta/images/carregando.gif" loading="eager"></div>
        <div id="login-form">
            <div id="login-logo"><img src="/wp-content/themes/woffice-child-theme-conecta/images/logo_btp.png" loading="eager"></div>
            <h1>Conecte-se</h1>
            <div style="color: #933;margin-bottom: 20px;">
              <strong>Atenção:</strong><br>Login/Senha são os mesmos dados de acesso à plataforma de gestão de pessoas 'Senior'
            </div>
            <div id="login-erro">Erro no seu cadastro</div>
            <div class="ui form">
                <div class="field">
                    <label>LOGIN (matrícula)</label>
                    <input id="cp-login-username" type="text">
                </div>
                <div class="field">
                    <label>SENHA (a mesma cadastrada na Senior)</label>
                    <input id="cp-login-pass" type="password" autocomplete="off">
                </div>
                <div>
                  <a href="#" id="esqueci-senha-link">Esqueci minha senha</a>
                </div>
				<div class="field">
					<div style="display: flex; justify-content: flex-end;">
						<button id="btn-login" class="ui button olive" style="display: flex;gap: 8px;">
							Entrar
							<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor"
								stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
								<path d="M9 6l6 6-6 6"></path>
							</svg>
						</button>
					</div>
				</div>
            </div>
        </div>

        <div id="esqueci-senha-box" class="hide">
            <div class="ui icon button olive" id="esqueci-senha-fechar"
                 style="position: absolute; top: -10px; right: -13px;">
               <i class="x icon"></i>
            </div>
            <h1>Esqueci minha senha</h1>

            <div>
                <p>
                    Em caso de esquecimento de senha, siga todas as instruções para a recuperação de senha diretamente na
                    plataforma de gestão de pessoas "Senior".
                    <br>
                </p>
                <a href="https://platform.senior.com.br/login/forgot.html?redirectTo=https%3A%2F%2Fplatform.senior.com.br%2Fsenior-x%2F&tenant=btp.com.br" target="_blank">
                    <div class="ui button olive" style="">
                        Clique aqui e acesse a Plataforma Senior
                    </div>
                </a>
                <p>
                    <strong>Atenção:</strong> Para troca de senha na "Senior", utilize seu número de matrícula seguido de @btp.com.br<br>
                    <i>Exemplo: <span style="color: #32636a;">0000@btp.com.br</span></i>
                </p>
            </div>
        </div>

    </div>
</body>
</html>
