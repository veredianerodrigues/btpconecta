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

    // ── Sub-menus: toggle no clique (mobile) ─────────────────────
    if ($(window).width() <= 768) {
        $('.nav-menu > li > a').on('click', function (e) {
            var $li = $(this).parent();
            if ($li.find('.sub-menu').length) {
                e.preventDefault();
                $li.toggleClass('sfHover');
            }
        });
    }

})(jQuery);
