// Path será definido inline no template HTML
// loginUrlSend será definido via data attribute ou variável global

// (function() {
// 	loginRenew();
// 	})();

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
ACTIONS
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
$(document).on("click","#btn-login",function()
	{
		loginFormCheck();	
	});
$(document).on("click","#esqueci-senha-link",function()
{
	$('#esqueci-senha-box').removeClass('hide');
});
$(document).on("click","#esqueci-senha-fechar",function()
{
	$('#esqueci-senha-box').addClass('hide');
});

/* ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~
FUNCTIONS
~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~ */
function loginLoadingShow()
	{
		$("#login-form").css('display','none');
		$("#login-form-loading").css('display','block');
	}

function loginLoadingHide()
	{
		$("#login-form").css('display','block');
		$("#login-form-loading").css('display','none');
	}

function loginErrorShow(text)
	{
		$("#login-erro").text(text);
		$("#login-erro").css('display','block');
	}

function loginErrorHide()
	{
		$("#login-erro").css('display','none');
	}

function loginFormCheck()
	{
		var user = $("#cp-login-username").val();
		var pass = $("#cp-login-pass").val();

		if(user==''){
			loginErrorShow('Preencha corretamente seu login.');
			return false;
			}

		if(pass==''){
			loginErrorShow('Preencha corretamente sua senha.');
			return false;
			}

		loginErrorHide();
		loginExec(user,pass);
	}

function loginExec(user,pass)
	{

		loginLoadingShow();

		var form = new FormData();
		form.append("user", user);
		form.append("pass", pass);
		
		var settings = {
		  "async": true,
		  "crossDomain": true,
		  "crossOrigin": 'anonymous',
		  "url": loginUrlSend,
		  "method": "POST",
		  "processData": false,
		  "contentType": false,
		  "mimeType": "multipart/form-data",
		  "data": form
		}
		
		$.ajax(settings).done(function (response) 
			{
		
				var res_json = JSON.parse(response);
				if(res_json.status<1)
					{
						loginLoadingHide();
						loginErrorShow(res_json.message);
					}
				if(res_json.status==1)
					{
						location.reload();
					}
			});
	}

// function loginRenew()
// 	{
// 		$.getJSON(loginUrlRenew,function(response)
// 			{
// 				console.log('Renew response: ',response);
// 			})
// 	}