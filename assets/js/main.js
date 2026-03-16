/**
 * BTP Conecta — main.js
 * Toggle do menu lateral, busca e interações gerais.
 */
(function ($) {
    'use strict';

    // ── Mobile: abrir/fechar sidebar com overlay ─────────────────
    function openSidebar() {
        $('#navigation-wrapper').addClass('sidebar-open');
        $('#sidebar-overlay').addClass('active');
        $('#mobile-menu-trigger').attr('aria-expanded', 'true');
        $('body').css('overflow', 'hidden');
    }
    function closeSidebar() {
        $('#navigation-wrapper').removeClass('sidebar-open');
        $('#sidebar-overlay').removeClass('active');
        $('#mobile-menu-trigger').attr('aria-expanded', 'false');
        $('body').css('overflow', '');
    }

    $('#mobile-menu-trigger').on('click', function () {
        if ($('#navigation-wrapper').hasClass('sidebar-open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    // Fechar ao clicar no overlay
    $('#sidebar-overlay').on('click', closeSidebar);

    // Busca mobile usa o mesmo trigger do desktop
    $('#mobile-search-trigger').on('click', function () {
        closeSidebar();
        $('#main-header').addClass('search-open');
        setTimeout(function () { $('#main-header input[type="search"]').focus(); }, 200);
    });

    // ── Toggle da busca ──────────────────────────────────────────
    $('#search-trigger').on('click', function () {
        $('#main-header').toggleClass('search-open');
        if ($('#main-header').hasClass('search-open')) {
            setTimeout(function () {
                $('#main-header input[type="search"]').focus();
            }, 200);
        }
    });

    $('#close-search-trigger').on('click', function () {
        $('#main-header').removeClass('search-open');
    });

    // Fechar busca com Escape
    $(document).on('keyup', function (e) {
        if (e.key === 'Escape') {
            $('#main-header').removeClass('search-open');
        }
    });

    // ── Sub-menus: toggle em qualquer nível de profundidade ──────
    // Adiciona seta em TODOS os itens que têm sub-menu (qualquer nível)
    $('.nav-menu li').each(function () {
        if ($(this).find('> .sub-menu').length) {
            $(this).find('> a').append('<span class="menu-arrow">&#9660;</span>');
        }
    });

    // Abre automaticamente os ancestrais do item ativo no carregamento
    $('.nav-menu .current-menu-item, .nav-menu .current-menu-ancestor, .nav-menu .current-menu-parent')
        .parents('li').addClass('menu-open');

    // Clique em qualquer item com sub-menu
    $('.nav-menu li > a').on('click', function (e) {
        var $li = $(this).parent();
        if (!$li.find('> .sub-menu').length) { return; } // sem sub-menu: segue o link

        e.preventDefault();

        var isOpen = $li.hasClass('menu-open');

        // Fecha todos os irmãos (mesmo nível) e seus descendentes
        $li.siblings().removeClass('menu-open')
           .find('.menu-open').removeClass('menu-open');

        // Toggle do item clicado
        $li.toggleClass('menu-open', !isOpen);
    });

})(jQuery);
