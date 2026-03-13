// JavaScript Document

/* ---------------------------------
A cada acesso a aplicação faz um ping
no endereço abaixo para checar se o 
login do usuário ainda é válido na BTP. 
O php faz o controle do intervalo entre 
checagens para não pingar a todo instante 
no server da BTP
--------------------------------- */
// loginUrlRenew = "/btpconecta-dev/wp-content/themes/woffice-child-theme/login/php/login_renew.php";
// (function() {
// 	getFile();
// 	loginRenew();
// })();

// function loginRenew()
// {
// 	$.getJSON(loginUrlRenew,function(response)
// 	{
// 		console.log('Renew login: ',response);
// 	})
// }

function getFile()
{
	fetch("https://localhos/local.json",{ 
		method: 'GET',
		mode: 'cors',
		headers: {'Accept':'application/json'},
		credentials: "same-origin"
	}).then(function(data){
		console.log("ambiente local:",data);
		if( data.ok )
			localStorage.setItem("accessLocal", "true");
		else
			localStorage.setItem("accessLocal", "false");

	}).catch(function(errorObj){
		console.log("erro no ambiente local:",errorObj.message);
		localStorage.setItem("accessLocal", "false");
	});
}

/* ---------------------------------
Funções da BTP
--------------------------------- */
jQuery(document).ready(function($) {
	
	// Link do Banner com checagem se é externo
	$("a.banner-home-img").each(function() {
		  if( location.hostname === this.hostname || !this.hostname.length ) {
			  $(this).addClass("local");
		  } else {
			  $(this).addClass("janela");
		  }
	});	
	
	
	// LINKS DO MENU : ADP WEB, GED, SGC, SERVICE DESK, ITAÚ, GESTÃO À VISTA, GESTÃO À VISTA, GESTÃO À VISTA, ESCALA DE TRABALHO E EQUIPAMENTOS, LINK DO CARROSSEL, LINK GENÉRICO, LINK NOTíCIAS EXTERNAS
    $("#menu-item-102 a, #menu-item-201 a, #menu-item-206 a, #menu-item-207 a, #menu-item-208 a, #menu-item-823 a, #menu-item-824 a, #menu-item-825 a, #menu-item-312 a, .link-janela a, a.janela, .noticias-externas a, a[target='_blank'], a[title='popup']").on("click", function(e){ 
			e.preventDefault();
			var href = $(this).attr('href');
			var largura = $(window).width();
			var altura = $(window).height();
			
			var urlBlock = [
				"https://tasescala.braporto.local/",
				"https://gop.braporto.local/PainelNavio/Index",
				"https://gop.braporto.local/PainelTruckCycle/Index",
				"https://gop.braporto.local/PainelSegurancaTrabalho/Index"
			];

			if(localStorage.getItem("accessLocal") != "true" && urlBlock.includes(href))
			{
				href = "/wp-content/themes/woffice-child-theme/extern-access/index.html"
			}

			//var janela = 
			window.open(href,'PopUp','width='+largura+',height='+altura+'');

			if(localStorage.getItem("accessLocal") == "true")
				window.location.reload();
		
		//var timer = setInterval(function() {   
		//	if(janela.closed) {  
		//		clearInterval(timer);
		//		window.location.reload();
		//	}  
		//}, 500); 
		
    });
	
	// Botão fechar busca
	$('#close-search-trigger').on('click', function () {
		$("#main-search").removeClass("opened").hide();
	});
		
	// Correção de layout para o menu
	$('#main-menu ul.sub-menu').on('click mouseover', function () {
		if ($(window).width() > 992) {	
			var largura_menu = $("#main-menu ul.sub-menu.display-submenu").width();
			var style = $('<style>.main-menu ul.sub-menu.display-submenu ul.sub-menu.display-submenu { left: '+largura_menu+'px;  }</style>');
			$('html > head').append(style);	
		}
	});
	
	$(window).on('resize', function(){
					
		// Se for largura menor que 992px
		if ($(window).width() > 992) {	
			var largura_menu = $("#main-menu").width();	
			var style = $('<style>.main-menu ul.sub-menu { left: '+largura_menu+'px; } .main-menu ul.sub-menu.display-submenu, .main-menu .mega-menu.open { left: '+largura_menu+'px;  }</style>');
			$('html > head').append(style);	
			
			var bannerHeight = $('.defesa-banner-home').height(); 
    		$('.noticias-destaque-home').css('height', bannerHeight+'px');
			
			// Correção do positio do menu
			var navigationHeight = $("#navbar").height() + $("#navigation").height();	
			if ($(window).height() < navigationHeight) {	
				$('#navigation-wrapper').css('position', 'absolute');
			}else {
				$('#navigation-wrapper').css('position', 'fixed');
			}			
		}
		else
		{
			$('#navigation-wrapper').css('position', 'static');
		}
		
	});		
	
	// Se for largura menor que 992px
	if ($(window).width() > 992) {	
		var largura_menu = $("#main-menu").width();	
		var style = $('<style>.main-menu ul.sub-menu { left: '+largura_menu+'px; } .main-menu ul.sub-menu.display-submenu, .main-menu .mega-menu.open { left: '+largura_menu+'px;  }</style>');
		$('html > head').append(style);	
		
		var bannerHeight = $('.defesa-banner-home').height(); 
    	$('.noticias-destaque-home').css('height', bannerHeight+'px');
		
		
		// Correção do position do menu lateral esquerdo quando a tela for menor que o conteúdo 
	
		var navigationHeight = $("#navbar").height() + $("#navigation").height();	
		if ($(window).height() < navigationHeight) {	
			$('#navigation-wrapper').css('position', 'absolute');
		}else {
			$('#navigation-wrapper').css('position', 'fixed');
		}

		// Fim da correção
		
	}
	// FIM DA Correção de layout para o menu
	
		
	// Efeito de aumento dos dias
	$('.contagemDias').each(function () {
		$(this).prop('Counter',0).animate({
			Counter: $(this).text()
		}, {
			duration: 2500,
			easing: 'swing',
			step: function (now) {
				$(this).text(Math.ceil(now));
			}
		});
	});


	//Link da segurança
	$(".linkSeguranca").on('click', function (event) {
		event.preventDefault();
		  var href = $(this).attr('data-link');
		  var largura = $(window).width();
		  var altura = $(window).height();
		
		//var janela = 
			window.open(href,'PopUp','width='+largura+',height='+altura+'');
		
		//var timer = setInterval(function() {   
		//	if(janela.closed) {  
		//		clearInterval(timer);
		//		window.location.reload();
		//	}  
		//}, 500); 
	});
		
});

