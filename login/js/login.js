// loginUrlSend é definido inline no template login-form.php

$(document).on('click', '#btn-login', function () {
    loginFormCheck();
});

$(document).on('click', '#esqueci-senha-link', function () {
    $('#esqueci-senha-box').removeClass('hide');
});

$(document).on('click', '#esqueci-senha-fechar', function () {
    $('#esqueci-senha-box').addClass('hide');
});

// Permite enviar com Enter
$(document).on('keydown', '#cp-login-username, #cp-login-pass', function (e) {
    if (e.key === 'Enter') { loginFormCheck(); }
});

function loginLoadingShow() {
    $('#login-form').hide();
    $('#login-form-loading').show();
}

function loginLoadingHide() {
    $('#login-form').show();
    $('#login-form-loading').hide();
}

function loginErrorShow(text) {
    $('#login-erro').text(text).show();
}

function loginErrorHide() {
    $('#login-erro').hide();
}

function loginFormCheck() {
    var user = $('#cp-login-username').val().trim();
    var pass = $('#cp-login-pass').val();

    if (user === '') {
        loginErrorShow('Preencha corretamente seu login.');
        return false;
    }
    if (pass === '') {
        loginErrorShow('Preencha corretamente sua senha.');
        return false;
    }

    loginErrorHide();
    loginExec(user, pass);
}

function loginExec(user, pass) {
    loginLoadingShow();

    var form = new FormData();
    form.append('user', user);
    form.append('pass', pass);

    $.ajax({
        async       : true,
        crossDomain : true,
        url         : loginUrlSend,
        method      : 'POST',
        processData : false,
        contentType : false,
        mimeType    : 'multipart/form-data',
        data        : form
    }).done(function (response) {
        var res = JSON.parse(response);
        if (res.status < 1) {
            loginLoadingHide();
            loginErrorShow(res.message);
        }
        if (res.status === 1) {
            location.reload();
        }
    }).fail(function () {
        loginLoadingHide();
        loginErrorShow('Erro de conexão. Tente novamente.');
    });
}
