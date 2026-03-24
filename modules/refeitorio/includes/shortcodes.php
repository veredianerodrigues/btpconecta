<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }


if ( ! function_exists('rd_translate_status') ) {
  function rd_translate_status( $status ) {
    $map = [
      'ativo'      => 'Solicitado',
      'confirmado' => 'Retirado',
      'cancelado'  => 'Cancelado',
    ];
    $key = strtolower( trim( (string) $status ) );
    return isset( $map[$key] ) ? $map[$key] : ucfirst( $status );
  }
}

if ( ! function_exists('rd_sc_window_days_safe') ) {
  function rd_sc_window_days_safe() {
    if ( function_exists('rd_get_window_days') ) {
      return (int) rd_get_window_days();
    }
    return 0;
  }
}

if ( ! function_exists('rd_sc_cutoff_safe') ) {
  function rd_sc_cutoff_safe() {
    if ( function_exists('rd_get_cutoff_hhmm') ) {
      return rd_get_cutoff_hhmm();
    }
    return '';
  }
}

if ( ! function_exists('rd_sc_get_categories_safe') ) {
  function rd_sc_get_categories_safe() {
    if ( function_exists('rd_get_categories_for_frontend') ) {
      return rd_get_categories_for_frontend();
    }
    return [];
  }
}

if ( ! wp_script_is( 'rd-xlsx', 'registered' ) ) {
  wp_register_script(
    'rd-xlsx',
    'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js',
    [],
    '0.18.5',
    true
  );
}
wp_enqueue_script( 'rd-xlsx' );

if ( ! function_exists('rd_sc_form') ) {
  function rd_sc_form() {
    $form_image_id = (int) get_option( defined('RD_OPT_FORM_IMAGE') ? RD_OPT_FORM_IMAGE : 'rd_form_image', 0 );
    $form_file_id = (int) get_option( defined('RD_OPT_FORM_FILE') ? RD_OPT_FORM_FILE : 'rd_form_file', 0 );

    ob_start();

    if ( $form_image_id ) {
      $img = wp_get_attachment_image( $form_image_id, 'large', false, ['class' => 'rd-form-header-image', 'style' => 'max-width:100%;height:auto;margin-bottom:20px;'] );
      if ( $img ) {
        echo '<div class="rd-form-image-wrapper">' . $img . '</div>';
      }
    }

    if ( $form_file_id ) {
      $file_url = wp_get_attachment_url( $form_file_id );
      $file_name = basename( get_attached_file( $form_file_id ) );
      $file_ext = strtolower( pathinfo( $file_name, PATHINFO_EXTENSION ) );

      if ( $file_url ) {
        echo '<div class="rd-form-file-viewer" style="margin-bottom:30px;">';

        echo '<div class="rd-file-content">';

        if ( $file_ext === 'pdf' ) {
          echo '<iframe src="' . esc_url( $file_url ) . '#toolbar=0&navpanes=0" ';
          echo 'style="width:100%;height:600px;border:none;" ';
          echo 'title="' . esc_attr( $file_name ) . '"></iframe>';

                } else if ( in_array( $file_ext, ['xlsx', 'xls'], true ) ) {
          $date_start = function_exists('rd_get_date_start') ? rd_get_date_start() : '';
          $date_end = function_exists('rd_get_date_end') ? rd_get_date_end() : '';

          echo '<div id="rd-excel-viewer" ';
          echo 'data-file-url="' . esc_url( $file_url ) . '" ';
          echo 'data-date-start="' . esc_attr( $date_start ) . '" ';
          echo 'data-date-end="' . esc_attr( $date_end ) . '" ';
          echo 'style="padding:20px;overflow-x:auto;min-height:400px;">';
          echo '<div class="rd-loading" style="text-align:center;padding:40px;color:#666;">';
          echo '<span class="dashicons dashicons-update" style="font-size:32px;width:32px;height:32px;animation:rd-spin 1s linear infinite;"></span>';
          echo '<p style="margin-top:12px;">Carregando planilha...</p>';
          echo '</div>';
          echo '</div>';

          $base = defined('RD_FILE') ? RD_FILE : __FILE__;


          if ( ! wp_script_is( 'rd-xlsx', 'registered' ) ) {
				wp_register_script(
				  'rd-xlsx',
				  'https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js',
				  [],
				  '0.18.5-v2',
				  true
				);
          }


                    wp_enqueue_script( 'rd-xlsx' );

                    
          $inline = "
(function() {
  function initExcelViewer() {
    var container = document.getElementById('rd-excel-viewer');
    if (!container) return;
    
    if (typeof XLSX === 'undefined') {
      console.log('Aguardando XLSX...');
      setTimeout(initExcelViewer, 100);
      return;
    }

    var fileUrl = container.getAttribute('data-file-url');
    var dateStart = container.getAttribute('data-date-start');
    var dateEnd = container.getAttribute('data-date-end');

    function formatDate(isoDate) {
      if (!isoDate || !isoDate.match(/^\d{4}-\d{2}-\d{2}$/)) return '';
      var parts = isoDate.split('-');
      return parts[2] + '/' + parts[1] + '/' + parts[0];
    }

    fetch(fileUrl)
      .then(function(response) { return response.arrayBuffer(); })
      .then(function(data) {
        if (typeof XLSX === 'undefined') {
          container.innerHTML = '<p style=\"color:#d63638;padding:20px;text-align:center;\">Erro: Biblioteca não carregada.</p>';
          return;
        }

        var workbook = XLSX.read(data, { type: 'array' });
        var html = '';

        if (dateStart && dateEnd) {
          var startFormatted = formatDate(dateStart);
          var endFormatted = formatDate(dateEnd);
          html += '<div class=\"rd-week-header\" style=\"margin-bottom:15px;padding:12px 16px;background:#f0f0f1;border-left:4px solid #ff6600;\">';
          html += '<strong style=\"font-size:15px;color:#1d2327;\">Semana de ' + startFormatted + ' a ' + endFormatted + '</strong>';
          html += '</div>';
        }

        workbook.SheetNames.forEach(function(sheetName, index) {
          var worksheet = workbook.Sheets[sheetName];
          var displayStyle = index === 0 ? 'block' : 'none';
          html += '<div class=\"rd-sheet-content\" data-sheet=\"' + index + '\" style=\"display:' + displayStyle + ';overflow-x:auto;\">';
          html += '<table class=\"rd-excel-table\" style=\"width:100%;border-collapse:collapse;font-size:13px;font-family:Arial,sans-serif;\">';

          var range = XLSX.utils.decode_range(worksheet['!ref']);
          var totalCols = range.e.c - range.s.c + 1;
          var MAX_ROWS = 10;
          var totalRows = range.e.r - range.s.r + 1;
          var lastRow = Math.min(range.e.r, range.s.r + MAX_ROWS - 1);

          for (var R = range.s.r; R <= lastRow; ++R) {
            html += '<tr>';
            var firstColAddress = XLSX.utils.encode_cell({ r: R, c: 0 });
            var firstColCell = worksheet[firstColAddress];
            var firstColValue = firstColCell ? XLSX.utils.format_cell(firstColCell) : '';

            var secondColAddress = XLSX.utils.encode_cell({ r: R, c: 1 });
            var secondColCell = worksheet[secondColAddress];
            var secondColValue = secondColCell ? XLSX.utils.format_cell(secondColCell) : '';

            var isMergedRow = (
              String(firstColValue).toLowerCase().indexOf('qlight') !== -1 &&
              String(secondColValue).length > 40 &&
              (String(secondColValue).toLowerCase().indexOf('antecedência') !== -1 ||
               String(secondColValue).toLowerCase().indexOf('antecedencia') !== -1 ||
               String(secondColValue).toLowerCase().indexOf('opção') !== -1 ||
               String(secondColValue).toLowerCase().indexOf('opcao') !== -1 ||
               String(secondColValue).toLowerCase().indexOf('grelhado') !== -1)
            );

            if (isMergedRow) {
              html += '<td style=\"padding:8px 10px;border:1px solid #592236;background-color:#ffffff;color:#592236;font-weight:bold;text-align:left;vertical-align:middle;\">';
              html += firstColValue;
              html += '</td>';
              var colspan = totalCols - 1;
              html += '<td colspan=\"' + colspan + '\" style=\"padding:8px 10px;border:1px solid #592236;background-color:#ffffff;color:#592236;text-align:center;vertical-align:middle;font-size:12px;font-style:italic;\">';
              html += secondColValue;
              html += '</td>';
            } else {
              for (var C = range.s.c; C <= range.e.c; ++C) {
                var cellAddress = XLSX.utils.encode_cell({ r: R, c: C });
                var cell = worksheet[cellAddress];
                var cellValue = cell ? XLSX.utils.format_cell(cell) : '';
                var bgColor = '#ffffff';
                var textColor = '#000000';
                var fontWeight = 'normal';
                var textAlign = 'center';
                var borderColor = '#592236';

                if (R === 0) {
                  bgColor = '#F5B233';
                  textColor = '#592236';
                  fontWeight = 'bold';
                } else if (C === 0) {
                  if (String(cellValue).toLowerCase().indexOf('veggie') !== -1 || String(cellValue).toLowerCase().indexOf('vegetariano') !== -1) {
                    bgColor = '#ffff00';
                    textColor = '#592236';
                  } else {
                    bgColor = '#ffffff';
                    textColor = '#592236';
                  }
                  fontWeight = 'bold';
                  textAlign = 'left';
                } else {
                  if (String(firstColValue).toLowerCase().indexOf('veggie') !== -1 || String(firstColValue).toLowerCase().indexOf('vegetariano') !== -1) {
                    bgColor = '#ffff00';
                  } else {
                    bgColor = '#ffffff';
                  }
                  textAlign = 'center';
                }

                var style = 'padding:8px 10px;border:1px solid ' + borderColor + ';';
                style += 'background-color:' + bgColor + ';';
                style += 'color:' + textColor + ';';
                style += 'font-weight:' + fontWeight + ';';
                style += 'text-align:' + textAlign + ';';
                style += 'vertical-align:middle;';

                html += '<td style=\"' + style + '\">' + cellValue + '</td>';
              }
            }
            html += '</tr>';
          }
          html += '</table></div>';
        });

        container.innerHTML = html;
      })
      .catch(function(error) {
        container.innerHTML = '<p style=\"color:#d63638;padding:20px;text-align:center;\">Erro ao carregar planilha: ' + error.message + '</p>';
      });
  }
  
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initExcelViewer);
  } else {
    initExcelViewer();
  }
})();
";

          wp_add_inline_script( 'rd-xlsx', $inline, 'after' );

          echo '<style>
          @keyframes rd-spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
          }
          .rd-excel-table {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
          }
          @media print {
            .rd-excel-table {
              font-size: 11px;
            }
            .rd-excel-table td,
            .rd-excel-table th {
              padding: 6px 8px !important;
            }
          }
          @media (max-width: 768px) {
            .rd-excel-table {
              font-size: 11px;
            }
            .rd-excel-table td {
              padding: 6px 4px !important;
            }
          }
          </style>';
        }
        echo '</div>';
        echo '</div>';
      }
    }
    ?>
<div class="rd rd-form">
  <h2 class="rd-title">Solicitar Refeição</h2>
  <p class="rd-intro">Aqui você poderá solicitar sua refeição, atenção para o comunicado abaixo.</p>

  <div class="rd-comunicado">
    <h4 class="rd-comunicado-title">Comunicado – Prazos para Solicitação de Refeições</h4>
    <p class="rd-comunicado-text">Para garantir o bom planejamento e a qualidade no atendimento, informamos que os pedidos de refeições devem ser feitos com, no mínimo, 24 horas de antecedência.</p>
    <div class="rd-comunicado-cols">
      <div class="rd-comunicado-col">
        <p class="rd-comunicado-subtitle">Horários-limite para solicitações:</p>
        <ul class="rd-comunicado-list">
          <li><span class="rd-comunicado-check">✓</span> <strong>Almoço:</strong> até às 15h00 do dia anterior</li>
          <li><span class="rd-comunicado-check">✓</span> <strong>Jantar:</strong> até às 21h30 do dia anterior</li>
          <li><span class="rd-comunicado-check">✓</span> <strong>Ceia:</strong> até às 04h30 do dia anterior</li>
        </ul>
      </div>
      <div class="rd-comunicado-col">
        <p class="rd-comunicado-subtitle">Disponibilidade dos cardápios:</p>
        <ul class="rd-comunicado-list">
          <li><span class="rd-comunicado-check">✓</span> <strong>QLight</strong> – todos os dias</li>
          <li><span class="rd-comunicado-check">✓</span> <strong>QVeggie</strong> – segunda a sexta</li>
          <li><span class="rd-comunicado-check">✓</span> <strong>QSabor</strong> – somente às sextas-feiras</li>
        </ul>
      </div>
    </div>
  </div>

  <div class="rd-row">
    <div class="rd-col">
      <label class="rd-label">Nome:</label>
      <div class="rd-readonly" id="rd-form-nome"></div>
      <input type="text" id="rd-form-nome-input" class="rd-input" placeholder="Informe seu nome completo" style="display:none;" maxlength="200" autocomplete="name" />
    </div>
    <div class="rd-col">
      <label class="rd-label">Matrícula:</label>
      <div class="rd-readonly" id="rd-form-matricula"></div>
    </div>
  </div>

  <div class="rd-row">
    <div class="rd-col">
      <label class="rd-label">Refeição</label>
      <select id="rd-categoria" class="rd-input" required>
        <option value="">Selecione</option>
      </select>
    </div>
    <div class="rd-col">
      <label class="rd-label">Data da Refeição</label>
      <div class="rd-date-selects" id="rd-date-selects">
        <select id="rd-dia" aria-label="Dia"></select>
        <select id="rd-mes" aria-label="Mês"></select>
        <select id="rd-ano" aria-label="Ano"></select>
      </div>
    </div>
  </div>

  <div class="rd-row">
    <div class="rd-col">
      <label class="rd-label">Cardápio</label>
      <select id="rd-tipo" class="rd-input">
        <option value="">Selecione</option>
      </select>
    </div>
    <div class="rd-col">
      <label for="rd-local-retirada" class="rd-label">Local</label>
      <select id="rd-local-retirada" name="local_retirada" class="rd-input" required>
        <option value=""><?php echo esc_html__('Selecione', 'refeitorio-digital'); ?></option>
      </select>
    </div>
  </div>

  <button id="rd-enviar" class="rd-btn rd-primary">Solicitar</button>
  <div id="rd-msg" class="rd-msg" role="alert" aria-live="polite"></div>
</div>
<?php
    $date_start = function_exists('rd_get_date_start') ? rd_get_date_start() : '';
    $date_end = function_exists('rd_get_date_end') ? rd_get_date_end() : '';

    $ui = [
      'nonce'          => wp_create_nonce('wp_rest'),
      'apiBase'        => rest_url('rd/v1'),
      'today'          => current_time('Y-m-d'),
      'windowDays'     => rd_sc_window_days_safe(),
      'cutoff'         => rd_sc_cutoff_safe(),
      'mealCategories' => rd_sc_get_categories_safe(),
      'dateStart'      => $date_start,
      'dateEnd'        => $date_end,
      'localFieldId'   => 'rd-local-retirada',
      'localParam'     => 'local_retirada',
      'localRequired'  => true,
    ];

    $base = defined('RD_FILE') ? RD_FILE : __FILE__;

    wp_register_style('rd-style', plugins_url('assets/css/rd.css', $base), [], RD_VER);
    wp_enqueue_style('rd-style');

    wp_register_script('rd-form', plugins_url('assets/js/rd-form.js', $base), ['jquery'], RD_VER, true);
    wp_enqueue_script('rd-form');

    wp_add_inline_script('rd-form', 'window.RD_FORM = ' . wp_json_encode($ui) . ';', 'before');

    return ob_get_clean();
  }
  add_shortcode('rd_form', 'rd_sc_form');
}

if ( ! function_exists('rd_sc_minhas_refeicoes') ) {
  function rd_sc_minhas_refeicoes( $atts ) {
    $a = shortcode_atts( [ 'view' => 'edit' ], $atts, 'rd_minhas_refeicoes' );
    $view = strtolower( $a['view'] ) === 'cards' ? 'cards' : 'edit';

    ob_start(); ?>
    <div class="rd rd-list" data-view="<?php echo esc_attr( $view ); ?>">
      <?php if ( $view === 'edit' ) : ?>
        <div class="rd-titlebar">
          <h2 class="rd-title">Editar minhas solicitações</h2>
          <button type="button"
                  class="rd-icon rd-icon--primary rd-refresh"
                  title="Atualizar lista"
                  aria-label="Atualizar lista">
            <svg width="18" height="18" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
              <path fill="currentColor"
                    d="M17.65 6.35A7.95 7.95 0 0 0 12 4a8 8 0 1 0 8 8h-2a6 6 0 1 1-6-6c1.66 0 3.14.69 4.22 1.78L13 11h7V4l-2.35 2.35Z"/>
            </svg>
          </button>
        </div>
        <div class="rd-list-header">
          <span class="rd-th">Data</span>
          <span class="rd-th">Categoria</span>
          <span class="rd-th">Cardápio</span>
          <span class="rd-th">Local</span>
          <span class="rd-th rd-th-actions">Ações</span>
        </div>

        <div id="rd-list-container" class="rd-list-container"></div>

      <?php else : ?>
        <h2 class="rd-title">Minhas Solicitações</h2>
        <p class="rd-sub">Abaixo as suas solicitações.</p>
        <div id="rd-cards" class="rd-grid-3"></div>
      <?php endif; ?>

      <div id="rd-list-msg" class="rd-msg" role="alert" aria-live="polite"></div>
    </div>
    <?php

    $cfg = [
      'nonce'        => wp_create_nonce( 'wp_rest' ),
      'apiBase'      => rest_url( 'rd/v1' ),
      'restFormData' => esc_url_raw( rest_url( 'rd/v1/refeicao/form-data' ) ),
    ];

    $base = defined('RD_FILE') ? RD_FILE : __FILE__;

    if ( ! wp_style_is( 'rd-style', 'registered' ) ) {
      wp_register_style( 'rd-style', plugins_url( 'assets/css/rd.css', $base ), [], RD_VER );
    }
    if ( ! wp_script_is( 'rd-list', 'registered' ) ) {
      wp_register_script( 'rd-list', plugins_url( 'assets/js/rd-list.js', $base ), [ 'jquery' ], RD_VER, true );
    }

    wp_enqueue_style( 'rd-style' );
    wp_enqueue_script( 'rd-list' );
    wp_add_inline_script( 'rd-list', 'window.RD_LIST = ' . wp_json_encode( $cfg ) . ';', 'before' );

    return ob_get_clean();
  }
  add_shortcode( 'rd_minhas_refeicoes', 'rd_sc_minhas_refeicoes' );
}

if ( ! function_exists('rd_sc_minhas_confirmadas') ) {
  function rd_sc_minhas_confirmadas( $atts = [] ) {
    $a = shortcode_atts( [
      'status'  => 'confirmado',
      'variant' => 'cards',
    ], $atts, 'rd_minhas_confirmadas' );

    $status_attr = trim( (string) $a['status'] );
    $variant     = in_array( strtolower($a['variant']), ['cards','agenda'], true ) ? strtolower($a['variant']) : 'cards';

    ob_start(); ?>
    <div class="rd rd-list" data-view="cards">
      <h2 class="rd-title">Minhas Solicitações</h2>
      <div id="rd-list-msg" class="rd-msg" role="alert" aria-live="polite"></div>
      <div id="rd-cards" class="<?php echo esc_attr($variant === 'agenda' ? 'rd-agenda-wrap' : 'rd-grid-3'); ?>"></div>      
    </div>
    <?php

    $cfg = [
      'nonce'   => wp_create_nonce( 'wp_rest' ),
      'apiBase' => rest_url( 'rd/v1' ),
      'status'  => $status_attr,
      'variant' => $variant,
    ];

    $base = defined('RD_FILE') ? RD_FILE : __FILE__;
    
    if ( ! wp_style_is( 'rd-style', 'registered' ) ) {
      wp_register_style( 'rd-style', plugins_url( 'assets/css/rd.css', $base ), [], RD_VER );
    }

    if ( ! wp_script_is( 'rd-cards', 'registered' ) ) {
      wp_register_script( 'rd-cards', plugins_url( 'assets/js/rd-cards.js', $base ), ['jquery'], RD_VER, true );
    }

    wp_enqueue_style( 'rd-style' );
    wp_enqueue_script( 'rd-cards' );
    wp_add_inline_script( 'rd-cards', 'window.RD_CARDS = ' . wp_json_encode( $cfg ) . ';', 'before' );

    return ob_get_clean();
  }
  add_shortcode( 'rd_minhas_confirmadas', 'rd_sc_minhas_confirmadas' );
}

if ( ! function_exists('rd_sc_image') ) {
  function rd_sc_image( $atts = [] ) {
    $a = shortcode_atts( [
      'id'        => '',
      'page_id'   => '',
      'slug'      => '',
      'post_type' => 'page',
      'url'       => '',
      'size'      => 'large',
      'alt'       => '',
      'class'     => 'rd-img',
      'link'      => '',
      'target'    => '',
      'width'     => '',
      'height'    => '',
      'decoding'  => 'async',
      'loading'   => 'lazy',
      'debug'     => '0',
    ], $atts, 'rd_image' );

    $dbg = (string)$a['debug'] === '1';
    $echo_dbg = function($msg) use ($dbg) { return $dbg ? "\n<!-- rd_image: {$msg} -->\n" : ''; };

  
    $class_attr = implode(' ', array_filter(array_map('sanitize_html_class', preg_split('/\s+/', (string)$a['class']))));

    $img_html = '';
    $img_url  = '';
    $alt_attr = trim( wp_strip_all_tags( $a['alt'] ) );
    $page_link = '';

    if ( $a['id'] ) {
      $id = absint( $a['id'] );
      if ( $id ) {
        $attr = [
          'class'    => $class_attr,
          'decoding' => $a['decoding'],
          'loading'  => $a['loading'],
        ];
        if ( $alt_attr !== '' ) { $attr['alt'] = $alt_attr; }
        if ( $a['width']  !== '' )  { $attr['width']  = (int) $a['width']; }
        if ( $a['height'] !== '' )  { $attr['height'] = (int) $a['height']; }

        $img_html = wp_get_attachment_image( $id, $a['size'], false, $attr );
        $img_url  = wp_get_attachment_image_url( $id, 'full' );
        if ( ! $img_url ) {
          $src = wp_get_attachment_image_src( $id, 'full' );
          $img_url = is_array($src) ? $src[0] : '';
        }
        if ( $img_html ) return $echo_dbg('usando attachment id') . $img_html;
      }
    }

    if ( ! $img_html && ( $a['page_id'] || $a['slug'] ) ) {
      $post = null;

      if ( $a['page_id'] ) {
        $post = get_post( absint( $a['page_id'] ) );
      } else {
        $path = ltrim( (string) $a['slug'], '/' );
        $pts = array_filter(array_map('trim', explode(',', (string)$a['post_type'] ?: 'page')));
        $post = get_page_by_path( $path, OBJECT, $pts );

        if ( ! $post ) {
          $base = sanitize_title( basename( $path ) );
          $q = new WP_Query([
            'name'           => $base,
            'post_type'      => $pts,
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'no_found_rows'  => true,
          ]);
          if ( $q->have_posts() ) {
            $post = $q->posts[0];
          }
          wp_reset_postdata();
        }
      }

      if ( $post ) {
        $page_link = get_permalink( $post );
        if ( has_post_thumbnail( $post ) ) {
          $thumb_id = get_post_thumbnail_id( $post );
          $attr = [
            'class'    => $class_attr,
            'decoding' => $a['decoding'],
            'loading'  => $a['loading'],
          ];
          if ( $alt_attr !== '' ) { $attr['alt'] = $alt_attr; }
          if ( $a['width']  !== '' )  { $attr['width']  = (int) $a['width']; }
          if ( $a['height'] !== '' )  { $attr['height'] = (int) $a['height']; }

          $img_html = wp_get_attachment_image( $thumb_id, $a['size'], false, $attr );
          $img_url  = wp_get_attachment_image_url( $thumb_id, 'full' );
          if ( ! $img_url ) {
            $src = wp_get_attachment_image_src( $thumb_id, 'full' );
            $img_url = is_array($src) ? $src[0] : '';
          }
        } else { 
          $img_html = '';
        }
      }

      if ( $img_html ) {
        if ( strtolower($a['link']) === 'page' && $page_link ) {
          $a['link'] = $page_link;
        }
        return $echo_dbg('usando featured image por page_id/slug') . $img_html;
      }
    }

    if ( ! $img_html && $a['url'] ) {
      $src = esc_url( $a['url'] );
      $alt = esc_attr( $alt_attr );
      $wh  = '';
      if ( $a['width']  !== '' )  { $wh .= ' width="'.(int)$a['width'].'"'; }
      if ( $a['height'] !== '' )  { $wh .= ' height="'.(int)$a['height'].'"'; }

      $img_html = sprintf(
        '<img src="%s" class="%s" alt="%s" decoding="%s" loading="%s"%s/>',
        $src, esc_attr($class_attr), $alt, esc_attr($a['decoding']), esc_attr($a['loading']), $wh
      );
      $img_url = $src;

      return $echo_dbg('usando url direta') . $img_html;
    }

    if ( ! $img_html ) {
      return $echo_dbg('nenhuma imagem encontrada') . '<!-- rd_image: nenhuma imagem encontrada -->';
    }

    $link = trim( (string)$a['link'] );
    if ( $link ) {
      $href = '';
      if ( $link === 'file' ) {
        $href = $img_url ?: '';
      } elseif ( filter_var( $link, FILTER_VALIDATE_URL ) ) {
        $href = $link;
      } elseif ( $link === 'page' && $page_link ) {
        $href = $page_link;
      }
      
      if ( $href ) {
        $target = $a['target'] ? ' target="'.esc_attr($a['target']).'" rel="noopener"' : '';
        $img_html = '<a href="'.esc_url($href).'"'.$target.'>'.$img_html.'</a>';
      }
    }

    return $img_html;
  }
  add_shortcode( 'rd_image', 'rd_sc_image' );
}
