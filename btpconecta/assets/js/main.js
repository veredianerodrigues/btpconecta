/**
 * BTP Conecta — main.js
 * Toggle do menu lateral, busca e interações gerais.
 */
(function ($) {
    'use strict';

    // ── Toggle da sidebar (mobile) ────────────────────────────────
    $('#nav-sidebar-trigger').on('click', function () {
        $('#navigation-wrapper').toggleClass('sidebar-open');
    });

    // Fechar sidebar ao clicar fora (mobile)
    $(document).on('click', function (e) {
        if ($(window).width() <= 768) {
            if (!$(e.target).closest('#navigation-wrapper, #nav-sidebar-trigger').length) {
                $('#navigation-wrapper').removeClass('sidebar-open');
            }
        }
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
