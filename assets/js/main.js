/**
 * BTP Conecta — main.js
 * Toggle do menu lateral, busca e interações gerais.
 */

// ── Service Worker + PWA Install ─────────────────────────────────
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(function () {});
}

var _deferredInstall = null;

window.addEventListener('beforeinstallprompt', function (e) {
    e.preventDefault();
    _deferredInstall = e;
    var btn = document.getElementById('btp-install-btn');
    if (btn) btn.style.display = 'flex';
});

window.addEventListener('appinstalled', function () {
    _deferredInstall = null;
    var btn = document.getElementById('btp-install-btn');
    if (btn) btn.style.display = 'none';
});

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

        // Sem sub-menu: fecha sidebar no mobile e segue o link
        if (!$li.find('> .sub-menu').length) {
            if ($(window).width() <= 768) { closeSidebar(); }
            return;
        }

        e.preventDefault();

        var isOpen = $li.hasClass('menu-open');

        // Fecha todos os irmãos (mesmo nível) e seus descendentes
        $li.siblings().removeClass('menu-open')
           .find('.menu-open').removeClass('menu-open');

        // Toggle do item clicado
        $li.toggleClass('menu-open', !isOpen);
    });

    // ── PWA: botão instalar ───────────────────────────────────────
    $(document).on('click', '#btp-install-btn', function () {
        if (!_deferredInstall) return;
        _deferredInstall.prompt();
        _deferredInstall.userChoice.then(function (result) {
            if (result.outcome === 'accepted') {
                $('#btp-install-btn').hide();
            }
            _deferredInstall = null;
        });
    });

    // ── Toast helper ──────────────────────────────────────────────
    function btpToast(msg, type) {
        var $t = $('<div class="btp-toast btp-toast--' + (type || 'success') + '">' + msg + '</div>');
        $('body').append($t);
        setTimeout(function () { $t.addClass('btp-toast--show'); }, 10);
        setTimeout(function () {
            $t.removeClass('btp-toast--show');
            setTimeout(function () { $t.remove(); }, 400);
        }, 3000);
    }

    // ── Copiar URL ────────────────────────────────────────────────
    $('#share-copy-url').on('click', function () {
        var url = $(this).data('url');

        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(url).then(function () {
                btpToast('Link copiado para a área de transferência!', 'success');
            }).catch(function () {
                btpToast('Não foi possível copiar o link.', 'error');
            });
        } else {
            // Fallback para HTTP
            var $tmp = $('<textarea>').val(url).appendTo('body').select();
            try {
                document.execCommand('copy');
                btpToast('Link copiado para a área de transferência!', 'success');
            } catch (e) {
                btpToast('Não foi possível copiar o link.', 'error');
            }
            $tmp.remove();
        }
    });

    // ── Compartilhar por e-mail ───────────────────────────────────
    if (typeof btpShare !== 'undefined') {

        var $modal   = $('#share-email-modal');
        var $postId  = $('.share-bar').data('post-id');

        $('#share-email-trigger').on('click', function () {
            $modal.addClass('active');
            $('#share-email-to').val('').focus();
            $('#share-email-msg').text('').removeClass('success error');
        });

        $('#share-modal-close').on('click', function () {
            $modal.removeClass('active');
        });

        $modal.on('click', function (e) {
            if ($(e.target).is($modal)) { $modal.removeClass('active'); }
        });

        $('#share-email-send').on('click', function () {
            var to  = $.trim($('#share-email-to').val());
            var $msg = $('#share-email-msg');

            if (!to) {
                $msg.text('Informe um e-mail válido.').removeClass('success').addClass('error');
                return;
            }

            var $btn = $(this).prop('disabled', true).text('Enviando…');

            $.post(btpShare.ajaxurl, {
                action:  'btp_share_email',
                nonce:   btpShare.nonce,
                to:      to,
                post_id: $postId,
            })
            .done(function (res) {
                if (res.success) {
                    $msg.text(res.data.msg).removeClass('error').addClass('success');
                    setTimeout(function () { $modal.removeClass('active'); }, 2000);
                } else {
                    $msg.text(res.data.msg).removeClass('success').addClass('error');
                }
            })
            .fail(function () {
                $msg.text('Erro de conexão. Tente novamente.').removeClass('success').addClass('error');
            })
            .always(function () {
                $btn.prop('disabled', false).text('Enviar');
            });
        });

        // Enviar com Enter
        $('#share-email-to').on('keydown', function (e) {
            if (e.key === 'Enter') { $('#share-email-send').trigger('click'); }
        });
    }

    // ── Horário de Ônibus — filtros + busca ──────────────────────
    function btpHorarioInit() {
        var $wrapper      = $('.horario-wrapper');
        var $groupBtns    = $wrapper.find('.horario-group-btn');
        var $sectionBtns  = $wrapper.find('.horario-section-btn');
        var $searchInput  = $wrapper.find('#horario-search');
        var $count        = $('#horario-count');

        var activeGroup   = 0;
        var activeSection = 0;

        // Mostra apenas a tabela que corresponde ao grupo + seção ativos
        function showActiveTable() {
            $wrapper.find('.horario-table-wrap').each(function () {
                var g = parseInt($(this).data('group'), 10);
                var s = parseInt($(this).data('section'), 10);
                var visible = (g === activeGroup && s === activeSection);
                $(this).toggle(visible);
            });
            // Limpa busca ao trocar filtro
            $searchInput.val('');
            filterRows('');
        }

        // Filtra linhas da tabela visível pelo texto digitado
        function filterRows(term) {
            var $activeWrap = $wrapper.find(
                '.horario-table-wrap[data-group="' + activeGroup + '"][data-section="' + activeSection + '"]'
            );
            var $rows = $activeWrap.find('.horario-row');
            var needle = term.toLowerCase().trim();
            var visible = 0;

            $rows.each(function () {
                var text = $(this).text().toLowerCase();
                var match = (needle === '' || text.indexOf(needle) !== -1);
                $(this).prop('hidden', !match);
                if (match) { visible++; }
            });

            if (needle !== '') {
                $count.text(visible + ' horário(s) encontrado(s)');
            } else {
                $count.text('');
            }
        }

        // Clique nos botões de grupo (dia da semana)
        $groupBtns.on('click', function () {
            var $btn = $(this);
            activeGroup = parseInt($btn.data('group'), 10);
            $groupBtns.removeClass('active').attr('aria-pressed', 'false');
            $btn.addClass('active').attr('aria-pressed', 'true');
            showActiveTable();
        });

        // Clique nos botões de seção (ponto de saída)
        $sectionBtns.on('click', function () {
            var $btn = $(this);
            activeSection = parseInt($btn.data('section'), 10);
            $sectionBtns.removeClass('active').attr('aria-pressed', 'false');
            $btn.addClass('active').attr('aria-pressed', 'true');
            showActiveTable();
        });

        // Busca em tempo real
        $searchInput.on('input', function () {
            filterRows($(this).val());
        });

        // Estado inicial: mostra grupo 0, seção 0
        showActiveTable();
    }

    if ($('.horario-wrapper').length) {
        btpHorarioInit();
    }

    // ── Widget: Próxima saída de ônibus ───────────────────────────────────────
    if (typeof btpHorarios !== 'undefined' && btpHorarios.grupos && btpHorarios.grupos.length === 3) {

        function btpToMinutes(horario) {
            var parts = horario.split(':');
            return parseInt(parts[0], 10) * 60 + parseInt(parts[1], 10);
        }

        function btpNextDeparture(linhas) {
            var now     = new Date();
            var current = now.getHours() * 60 + now.getMinutes();
            var isPM    = current >= 720;

            // Ordena por minutos
            var sorted = linhas.slice().sort(function (a, b) {
                return btpToMinutes(a.horario) - btpToMinutes(b.horario);
            });

            var next;
            if (isPM) {
                // Próxima saída PM (>= hora atual e >= 12:00)
                next = sorted.find(function (l) {
                    var m = btpToMinutes(l.horario);
                    return m >= current && m >= 720;
                });
                // Se não houver mais PM, pega primeiro AM (madrugada)
                if (!next) {
                    next = sorted.find(function (l) {
                        return btpToMinutes(l.horario) < 720;
                    });
                }
            } else {
                // Próxima saída AM (>= hora atual e < 12:00)
                next = sorted.find(function (l) {
                    var m = btpToMinutes(l.horario);
                    return m >= current && m < 720;
                });
                // Se não houver mais AM, pega primeiro PM
                if (!next) {
                    next = sorted.find(function (l) {
                        return btpToMinutes(l.horario) >= 720;
                    });
                }
            }
            return next || sorted[0];
        }

        function btpGetGroupIndex() {
            var day = new Date().getDay(); // 0=Dom, 6=Sáb, 1-5=Seg-Sex
            if (day === 0) return 2;       // Domingo
            if (day === 6) return 1;       // Sábados e Feriados
            return 0;                      // Segunda a Sexta
        }

        function btpUpdateProximoOnibus() {
            var grupo   = btpHorarios.grupos[btpGetGroupIndex()];
            if (!grupo || !grupo.secoes || grupo.secoes.length < 3) return;

            var nomes = ['Terminal', 'Museu Pelé', 'Alfândega'];
            var partes = [];

            for (var i = 0; i < 3; i++) {
                var secao = grupo.secoes[i];
                if (!secao || !secao.linhas || !secao.linhas.length) continue;
                var dep = btpNextDeparture(secao.linhas);
                if (dep) {
                    partes.push('<span class="btp-onibus-ponto"><b>' + nomes[i] + '</b> ' + dep.horario + '</span>');
                }
            }

            if (partes.length) {
                $('#btp-onibus-content').html(partes.join(''));
                $('#btp-proximo-onibus').show();
            }
        }

        btpUpdateProximoOnibus();
        // Atualiza a cada minuto
        setInterval(btpUpdateProximoOnibus, 60000);
    }

})(jQuery);
