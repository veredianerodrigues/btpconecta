<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function rd_user_can_access_painel() {
    return current_user_can('manage_options') || current_user_can('rd_gerenciar_retiradas');
}

add_action( 'admin_menu', function() {
    add_submenu_page(
        'refeitorio-digital',
        'Painel de configurações',
        'Painel Refeições',
        'read',
        'refeitorio-digital-painel',
        'rd_admin_painel_page'
    );
});

add_action('admin_init', function() {
    global $pagenow;
    if ($pagenow !== 'admin.php') return;
    
    $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
    if ($page !== 'refeitorio-digital-painel') return;
    
    if (!rd_user_can_access_painel()) {
        wp_die('Você não tem permissão para acessar esta página.', 'Acesso Negado', ['response' => 403]);
    }
});

function rd_admin_painel_page() {
    if ( ! rd_user_can_access_painel() ) return;

    wp_enqueue_script( 'jquery-ui-datepicker' );
    
    wp_register_script( 'rd-admin', RD_URL . 'assets/js/rd-admin.js', [ 'jquery', 'jquery-ui-datepicker', 'wp-api-fetch' ], RD_VER, true );
    if ( ! wp_style_is( 'rd-style', 'registered' ) ) {
        wp_register_style( 'rd-style', RD_URL . 'assets/css/style.css', [], RD_VER );
    }
    wp_enqueue_style( 'wp-jquery-ui-dialog' );
    wp_enqueue_style( 'rd-style' );
    $types = function_exists('rd_refeicao_allowed_types')
        ? (array) rd_refeicao_allowed_types()
        : ['Qveggie','Qlight','Qsabor'];
    $types = array_values( array_filter( array_map( 'sanitize_text_field', $types ) ) );
    $local_opts = [];
    if ( function_exists('rd_local_retirada_options') ) {
        $src = (array) rd_local_retirada_options();
        foreach ( $src as $k => $v ) {
            $key = sanitize_key( (string) $k );
            if ( $key === '' ) continue;
            $local_opts[ $key ] = sanitize_text_field( (string) $v );
        }
        if ( ! empty( $local_opts ) ) {
            asort( $local_opts, SORT_NATURAL | SORT_FLAG_CASE );
        }
    }
    $today     = wp_date( 'Y-m-d' );
    $rest_list = add_query_arg( ['status' => 'solicitado'], rest_url( 'rd/v1/refeicoes' ) );

    $categories = function_exists('rd_get_categories_for_frontend')
        ? rd_get_categories_for_frontend()
        : [];
    $cat_labels = [];
    foreach ( $categories as $cat ) {
        $cat_labels[ $cat['code'] ] = $cat['label'];
    }

    $cfg = [
        'restList'       => esc_url_raw( $rest_list ),
        'restPatch'      => esc_url_raw( rest_url( 'rd/v1/refeicoes/' ) ),
        'restEmail'      => esc_url_raw( rest_url( 'rd/v1/relatorios/send' ) ),
        'nonce'          => wp_create_nonce( 'wp_rest' ),
        'today'          => $today,
        'status'         => 'solicitado',
        'localOptions'   => $local_opts,
        'categoryLabels' => $cat_labels,
    ];

    wp_enqueue_script( 'rd-admin' );
    
    wp_add_inline_script( 'rd-admin', 'window.RD_ADMIN = ' . wp_json_encode( $cfg ) . ';', 'before' );

    $datepicker_script = <<<'JS'
    jQuery(function($) {
        if (typeof $.fn.datepicker !== 'function') {
            return;
        }

        var datepickerInput = $('#rd-filter-data');
        if (!datepickerInput.length) {
            return;
        }

        if (datepickerInput.hasClass('hasDatepicker')) {
            return;
        }

        window.RD_IGNORE_DATE_CHANGE = false;
        var lastValue = datepickerInput.val();

        try {
            datepickerInput.datepicker({
                dateFormat: 'dd/mm/yy',
                changeMonth: true,
                changeYear: true,
                showButtonPanel: true,
                yearRange: '-1:+1',
                beforeShow: function(input, inst) {
                    $(input).attr('autocomplete', 'off');
                    lastValue = $(input).val();
                    var currentInput = input;
                    $('#ui-datepicker-div').removeClass('rd-hidden');
                    setTimeout(function() {
                        var $buttonPane = $('.ui-datepicker-buttonpane');

                        var $closeBtn = $buttonPane.find('.ui-datepicker-close');
                        $closeBtn.off('click').on('click', function(e) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            window.RD_IGNORE_DATE_CHANGE = true;
                            var $picker = $('#ui-datepicker-div');
                            $picker.hide();
                            $picker.css('display', 'none');
                            $picker.addClass('rd-hidden');
                            $(currentInput).datepicker('hide');
                            $(currentInput).blur();

                            return false;
                        });

                        var $todayBtn = $buttonPane.find('.ui-datepicker-current');
                        $todayBtn.off('click').on('click', function(e) {
                            e.preventDefault();
                            e.stopImmediatePropagation();
                            window.RD_IGNORE_DATE_CHANGE = false;
                            var today = new Date();
                            $(currentInput).datepicker('setDate', today);
                            var $picker = $('#ui-datepicker-div');
                            $picker.hide();
                            $picker.css('display', 'none');
                            $picker.addClass('rd-hidden');
                            $(currentInput).datepicker('hide');

                            return false;
                        });
                    }, 50);
                },
                onSelect: function(dateText, inst) {
                    window.RD_IGNORE_DATE_CHANGE = false;
                    lastValue = dateText;
                    var $picker = $('#ui-datepicker-div');
                    $picker.hide();
                    $picker.css('display', 'none');
                    $picker.addClass('rd-hidden');
                },
                onClose: function() {
                    if ($(this).val() === lastValue) {
                        window.RD_IGNORE_DATE_CHANGE = true;
                    }
                    $(this).blur();
                }
            });

            $(document).on('mousedown.rdDatepicker', function(e) {
                var widget = datepickerInput.datepicker('widget');
                if (!widget || !widget.is(':visible')) {
                    return;
                }

                var target = $(e.target);
                var isDatepicker = target.closest('.ui-datepicker').length > 0 ||
                                 target.closest('#ui-datepicker-div').length > 0 ||
                                 target.closest('.ui-datepicker-buttonpane').length > 0;
                var isInput = target.is('#rd-filter-data');

                if (!isDatepicker && !isInput) {
                    window.RD_IGNORE_DATE_CHANGE = true;
                    var $picker = $('#ui-datepicker-div');
                    $picker.hide();
                    $picker.css('display', 'none');
                    $picker.addClass('rd-hidden');
                    datepickerInput.datepicker('hide');
                    datepickerInput.blur();
                }
            });
        } catch (error) {}
    });
    JS;
    
    wp_add_inline_script( 'rd-admin', $datepicker_script );

    $limpar_script = <<<'JS'
    jQuery(function($) {
        $('#rd-btn-limpar').on('click', function(e) {
            e.preventDefault();
            $('#rd-filter-data').val('');
            $('#rd-filter-tipo').val('');
            $('#rd-filter-categoria').val('');
            $('#rd-filter-local').val('');
            $('#rd-filter-matricula').val('');
            $('#rd-btn-filtrar').trigger('click');
        });
    });
    JS;
    
    wp_add_inline_script( 'rd-admin', $limpar_script );

    ?>
    <style id="rd-datepicker-custom-styles">
        .rd-hidden {
            display: none !important;
            visibility: hidden !important;
            opacity: 0 !important;
            pointer-events: none !important;
        }

        .rd-datepicker-wrapper {
            background: #fff !important;
            border: 1px solid #ddd !important;
            border-radius: 8px !important;
            padding: 12px !important;
            box-shadow: 0 5px 20px rgba(0,0,0,0.25) !important;
            z-index: 99999 !important;
        }

        .rd-datepicker-wrapper .ui-datepicker-header {
            background: #256f4a !important;
            color: #fff !important;
            padding: 12px 16px !important;
            border-radius: 6px !important;
            border: none !important;
            display: flex !important;
            align-items: center !important;
            justify-content: space-between !important;
            gap: 12px !important;
            width: 100% !important;
            box-sizing: border-box !important;
        }

        .rd-datepicker-wrapper .ui-datepicker-title {
            color: #fff !important;
            display: flex !important;
            align-items: center !important;
            justify-content: center !important;
            gap: 8px !important;
            flex: 1 1 auto !important;
            margin: 0 !important;
            font-weight: 600 !important;
            line-height: 1.5 !important;
            text-align: center !important;
        }

        .rd-datepicker-wrapper .ui-datepicker-title select,
        .rd-datepicker-wrapper .ui-datepicker-month,
        .rd-datepicker-wrapper .ui-datepicker-year {
            background: rgba(255,255,255,0.3) !important;
            color: #fff !important;
            border: 1px solid rgba(255,255,255,0.4) !important;
            padding: 6px 10px !important;
            border-radius: 6px !important;
            margin: 0 4px !important;
            font-weight: 600 !important;
            min-width: 72px !important;
        }

        .rd-datepicker-wrapper .ui-datepicker-title select option {
            background: #fff !important;
            color: #333 !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-prev,
        .rd-datepicker-wrapper .ui-datepicker-next {
            background: rgba(255,255,255,0.2) !important;
            color: #fff !important;
            border: none !important;
            border-radius: 6px !important;
            cursor: pointer !important;
            text-decoration: none !important;
            display: inline-flex !important;
            align-items: center !important;
            justify-content: center !important;
            width: 32px !important;
            height: 32px !important;
            line-height: 1 !important;
            font-size: 16px !important;
            font-weight: 700 !important;
            padding: 0 !important;
            position: static !important;
            flex: 0 0 auto !important;
            transition: background 0.2s ease, background-color 0.2s ease;
        }
        .rd-datepicker-wrapper .ui-datepicker-prev:hover,
        .rd-datepicker-wrapper .ui-datepicker-next:hover {
            background: rgba(255,255,255,0.3) !important;
        }

        .rd-datepicker-wrapper .ui-datepicker-prev::before {
            content: '‹';
            font-size: 24px !important;
            font-weight: 700 !important;
            line-height: 1 !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-next::before {
            content: '›';
            font-size: 24px !important;
            font-weight: 700 !important;
            line-height: 1 !important;
        }

        .rd-datepicker-wrapper .ui-datepicker-prev span,
        .rd-datepicker-wrapper .ui-datepicker-next span {
            display: none !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-calendar {
            background: #fff !important;
            width: 100% !important;
            border-collapse: collapse !important;
        }

        .rd-datepicker-wrapper .ui-datepicker-calendar thead {
            background: #f8f9fa !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-calendar th {
            background: #f8f9fa !important;
            padding: 8px 4px !important;
            font-weight: 600 !important;
            color: #666 !important;
            text-align: center !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-calendar td {
            background: #fff !important;
            padding: 2px !important;
            text-align: center !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-calendar td a,
        .rd-datepicker-wrapper .ui-datepicker-calendar td span {
            background: #fff !important;
            display: block !important;
            padding: 8px !important;
            text-align: center !important;
            color: #333 !important;
            border-radius: 6px !important;
            text-decoration: none !important;
            border: none !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-calendar td a:hover {
            background: #e8f5ef !important;
            color: #256f4a !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-calendar td .ui-state-active {
            background: #256f4a !important;
            color: #fff !important;
            font-weight: 700 !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-calendar td .ui-state-highlight {
            background: #e8f5ef !important;
            color: #256f4a !important;
            font-weight: 600 !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-buttonpane {
            background: #fff !important;
            padding-top: 10px !important;
            border-top: 1px solid #e0e0e0 !important;
            margin-top: 10px !important;
            display: flex !important;
            justify-content: space-between !important;
            gap: 8px !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-buttonpane button {
            background: #256f4a !important;
            color: #fff !important;
            border: none !important;
            padding: 8px 16px !important;
            border-radius: 5px !important;
            cursor: pointer !important;
            font-weight: 600 !important;
        }
        .rd-datepicker-wrapper .ui-datepicker-buttonpane button:hover {
            background: #1f5b3c !important;
        }

        @media (max-width: 480px) {
            .rd-datepicker-wrapper {
                width: 95% !important;
                max-width: 320px !important;
                font-size: 14px !important;
                padding: 10px !important;
            }
            .rd-datepicker-wrapper .ui-datepicker-header {
                padding: 10px 8px !important;
            }
            .rd-datepicker-wrapper .ui-datepicker-title select,
            .rd-datepicker-wrapper .ui-datepicker-month,
            .rd-datepicker-wrapper .ui-datepicker-year {
                padding: 4px 6px !important;
                font-size: 13px !important;
                min-width: 64px !important;
            }
            .rd-datepicker-wrapper .ui-datepicker-calendar th {
                padding: 6px 2px !important;
                font-size: 11px !important;
            }
            .rd-datepicker-wrapper .ui-datepicker-calendar td a,
            .rd-datepicker-wrapper .ui-datepicker-calendar td span {
                padding: 6px 4px !important;
                font-size: 13px !important;
                min-width: 28px !important;
            }
            .rd-datepicker-wrapper .ui-datepicker-buttonpane button {
                padding: 6px 12px !important;
                font-size: 13px !important;
            }

            .rd-datepicker-wrapper .ui-datepicker-prev,
            .rd-datepicker-wrapper .ui-datepicker-next {
                width: 28px !important;
                height: 28px !important;
                font-size: 14px !important;
            }
        }
        @media (min-width: 481px) and (max-width: 768px) {
            .rd-datepicker-wrapper {
                width: auto !important;
                max-width: 340px !important;
            }
        }
    </style>
    <div class="wrap rd-painel">
        <h1><?php echo esc_html__( 'Painel de Refeições', 'refeitorio-digital' ); ?></h1>

        <div class="rd-filters">
            <div class="rd-filters-row">
                <label>
                    <?php echo esc_html__( 'Data:', 'refeitorio-digital' ); ?>
                    <input
                        type="text"
                        id="rd-filter-data"
                        value="<?php echo esc_attr( wp_date('d/m/Y') ); ?>"
                        placeholder="dd/mm/aaaa"
                        class="rd-date"
                        autocomplete="off"
                        maxlength="10"
                    />
                </label>

                <label>
                    <?php echo esc_html__( 'Refeição:', 'refeitorio-digital' ); ?>
                    <select id="rd-filter-categoria">
                        <option value=""><?php echo esc_html__( 'Todas', 'refeitorio-digital' ); ?></option>
                        <?php foreach ( $categories as $cat ) : ?>
                            <option value="<?php echo esc_attr( $cat['code'] ); ?>">
                                <?php echo esc_html( $cat['label'] ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <?php echo esc_html__( 'Tipo:', 'refeitorio-digital' ); ?>
                    <select id="rd-filter-tipo">
                        <option value=""><?php echo esc_html__( 'Todos', 'refeitorio-digital' ); ?></option>
                        <?php foreach ( $types as $t ) : ?>
                            <option value="<?php echo esc_attr( $t ); ?>">
                                <?php echo esc_html( $t ); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </label>

                <label>
                    <?php echo esc_html__( 'Local:', 'refeitorio-digital' ); ?>
                    <select id="rd-filter-local">
                        <option value=""><?php echo esc_html__( 'Todos', 'refeitorio-digital' ); ?></option>
                        <?php if ( ! empty( $local_opts ) ) : ?>
                            <?php foreach ( $local_opts as $val => $label ) : ?>
                                <option value="<?php echo esc_attr( $val ); ?>">
                                    <?php echo esc_html( $label ); ?>
                                </option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </label>

                <label>
                    <?php echo esc_html__( 'Matrícula:', 'refeitorio-digital' ); ?>
                    <input type="text" id="rd-filter-matricula" placeholder="ex.: 1234" autocomplete="off" />
                </label>
            </div>

            <div class="rd-filters-row rd-filters-actions">
                <button class="button button-primary" id="rd-btn-filtrar">
                    <?php echo esc_html__( 'Filtrar', 'refeitorio-digital' ); ?>
                </button>

                <button class="button" id="rd-btn-limpar">
                    <?php echo esc_html__( 'Limpar', 'refeitorio-digital' ); ?>
                </button>

                <button class="button" id="rd-btn-email">
                    <?php echo esc_html__( 'Enviar relatório por e-mail', 'refeitorio-digital' ); ?>
                </button>
                <span id="rd-email-msg"></span>
            </div>
        </div>
        <div class="rd-controls" id="rd-pager" style="margin:12px 0;">
            <button class="button" id="rd-prev" disabled>« <?php echo esc_html__( 'Anterior', 'refeitorio-digital' ); ?></button>
            <label style="margin:0 8px;"><?php echo esc_html__( 'Por página:', 'refeitorio-digital' ); ?>
                <select id="rd-limit">
                    <option value="10">10</option>
                    <option value="20" selected>20</option>
                    <option value="50">50</option>
                    <option value="100">100</option>
                    <option value="500">500</option>
                </select>
            </label>
            <button class="button button-primary" id="rd-next" disabled><?php echo esc_html__( 'Próximo', 'refeitorio-digital' ); ?> »</button>
            <span id="rd-meta" style="margin-left:12px; font-weight:600;">
              <?php echo esc_html__( 'Página', 'refeitorio-digital' ); ?>
              <span id="rd-page">1</span>
              <?php echo esc_html__( 'de', 'refeitorio-digital' ); ?>
              <span id="rd-pages">1</span> — <span id="rd-total">0</span> <?php echo esc_html__( 'registro(s)', 'refeitorio-digital' ); ?>
            </span>
        </div>

        <div class="rd rd-table-wrap">
            <table class="widefat fixed striped" id="rd-tabela">
                <thead>
                    <tr>
                        <th data-label="<?php echo esc_attr__( 'Matrícula', 'refeitorio-digital' ); ?>"><?php echo esc_html__( 'Matrícula', 'refeitorio-digital' ); ?></th>
                        <th data-label="<?php echo esc_attr__( 'Nome', 'refeitorio-digital' ); ?>"><?php echo esc_html__( 'Nome', 'refeitorio-digital' ); ?></th>
                        <th data-label="<?php echo esc_attr__( 'Data', 'refeitorio-digital' ); ?>"><?php echo esc_html__( 'Data', 'refeitorio-digital' ); ?></th>
                        <th data-label="<?php echo esc_attr__( 'Refeição', 'refeitorio-digital' ); ?>"><?php echo esc_html__( 'Refeição', 'refeitorio-digital' ); ?></th>
                        <th data-label="<?php echo esc_attr__( 'Cardápio', 'refeitorio-digital' ); ?>"><?php echo esc_html__( 'Cardápio', 'refeitorio-digital' ); ?></th>
                        <th data-label="<?php echo esc_attr__( 'Retirado', 'refeitorio-digital' ); ?>"><?php echo esc_html__( 'Retirado', 'refeitorio-digital' ); ?></th>
                        <?php if ( ! empty( $local_opts ) ) : ?>
                            <th data-label="<?php echo esc_attr__( 'Local', 'refeitorio-digital' ); ?>"><?php echo esc_html__( 'Local', 'refeitorio-digital' ); ?></th>
                        <?php endif; ?>
                        <th data-label="<?php echo esc_attr__( 'Ações', 'refeitorio-digital' ); ?>"><?php echo esc_html__( 'Ações', 'refeitorio-digital' ); ?></th>
                    </tr>
                </thead>
                <tbody>
                    <tr><td colspan="7"><?php echo esc_html__( 'Carregando…', 'refeitorio-digital' ); ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php
}