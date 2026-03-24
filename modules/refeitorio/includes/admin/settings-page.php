<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! defined('RD_OPT_DATE_START') ) define('RD_OPT_DATE_START', 'rd_date_start');
if ( ! defined('RD_OPT_DATE_END') ) define('RD_OPT_DATE_END', 'rd_date_end');
if ( ! defined('RD_OPT_FORM_IMAGE') ) define('RD_OPT_FORM_IMAGE', 'rd_form_image');
if ( ! defined('RD_OPT_FORM_FILE') ) define('RD_OPT_FORM_FILE', 'rd_form_file');

function rd_user_can_access_settings() {
    return current_user_can('manage_options') ||
           current_user_can('rd_gerenciar_cardapio');
}
add_filter('option_page_capability_rd_settings', function($capability) {
    if (current_user_can('manage_options')) {
        return 'manage_options';
    }
    if (current_user_can('rd_gerenciar_cardapio')) {
        return 'rd_gerenciar_cardapio';
    }
    return $capability;
});
add_action('admin_init', function () {
  register_setting('rd_settings', RD_OPT_WINDOW_DAYS, [
    'type'              => 'integer',
    'sanitize_callback' => 'absint',
  ]);

  register_setting('rd_settings', RD_OPT_CUTOFF_HHMM, [
    'type'    => 'string',
    'sanitize_callback' => static function($v){
      $v = preg_replace('/\s+/', '', (string)$v);
      return preg_match('/^\d{2}:\d{2}$/', $v) ? $v : null;
    },
  ]);

  register_setting('rd_settings', RD_OPT_REPORT_EMAILS, [
    'type'    => 'string',
    'default' => '',
    'sanitize_callback' => static function($v){
      $emails = array_filter(array_map('trim', preg_split('/[,;\s]+/u', (string)$v)));
      return implode(', ', $emails);
    },
  ]);

  register_setting('rd_settings', RD_OPT_DATE_START, [
    'type'    => 'string',
    'default' => '',
    'sanitize_callback' => static function($v){
      $v = trim((string)$v);
      if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ) return $v;
      if ( preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m) ) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
      }
      return '';
    },
  ]);

  register_setting('rd_settings', RD_OPT_DATE_END, [
    'type'    => 'string',
    'default' => '',
    'sanitize_callback' => static function($v){
      $v = trim((string)$v);
      if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $v) ) return $v;
      if ( preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $v, $m) ) {
        return $m[3] . '-' . $m[2] . '-' . $m[1];
      }
      return '';
    },
  ]);

  register_setting('rd_settings', RD_OPT_FORM_IMAGE, [
    'type'    => 'integer',
    'default' => 0,
    'sanitize_callback' => 'absint',
  ]);

  register_setting('rd_settings', RD_OPT_FORM_FILE, [
    'type'    => 'integer',
    'default' => 0,
    'sanitize_callback' => static function($v) {
      $id = absint($v);
      if ( ! $id ) return 0;
      $file = get_attached_file($id);
      if ( ! $file ) return 0;
      $allowed_ext = ['xlsx', 'xls', 'pdf'];
      $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
      if ( ! in_array($ext, $allowed_ext, true) ) {
        add_settings_error(
          RD_OPT_FORM_FILE,
          'invalid_extension',
          'Apenas arquivos .xlsx, .xls ou .pdf são permitidos.',
          'error'
        );
        return 0;
      }
      return $id;
    },
  ]);
});

add_action('admin_menu', function() {
  add_menu_page(
    'Refeitório Digital',
    'Quality',
    'read',
    'refeitorio-digital',
    'rd_admin_settings_page',
    'dashicons-food',
    58
  );
});

add_action('admin_init', function() {
  global $pagenow;
  if ($pagenow !== 'admin.php') return;
  
  $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
  if ($page !== 'refeitorio-digital') return;
  
  if (!rd_user_can_access_settings()) {
    wp_die('Você não tem permissão para acessar esta página.', 'Acesso Negado', ['response' => 403]);
  }
});

function rd_admin_settings_page() {
  if (!rd_user_can_access_settings()) {
    wp_die('Você não tem permissão para acessar esta página.');
  }

  $can_edit_settings = current_user_can('manage_options') || current_user_can('rd_gerenciar_cardapio');
  
  $window_days   = (int) get_option(RD_OPT_WINDOW_DAYS, 0);
  $report_emails = (string) get_option(RD_OPT_REPORT_EMAILS, '');
  $date_start    = (string) get_option(RD_OPT_DATE_START, '');
  $date_end      = (string) get_option(RD_OPT_DATE_END, '');
  $form_image_id = (int) get_option(RD_OPT_FORM_IMAGE, 0);
  $form_file_id  = (int) get_option(RD_OPT_FORM_FILE, 0);

  $date_start_br = '';
  $date_end_br = '';
  if ( $date_start && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date_start, $m) ) {
    $date_start_br = $m[3] . '/' . $m[2] . '/' . $m[1];
  }
  if ( $date_end && preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $date_end, $m) ) {
    $date_end_br = $m[3] . '/' . $m[2] . '/' . $m[1];
  }

  wp_enqueue_media();
  wp_enqueue_script( 'jquery-ui-datepicker' );
  wp_enqueue_style( 'wp-jquery-ui-dialog' );
  ?>
  <style id="rd-datepicker-settings-styles">
    #rd-datepicker-wrapper,
    #ui-datepicker-div,
    .ui-datepicker {
      background: #fff !important;
      border: 1px solid #ddd !important;
      border-radius: 8px !important;
      padding: 12px !important;
      box-shadow: 0 5px 20px rgba(0,0,0,0.25) !important;
      z-index: 99999 !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-header,
    #ui-datepicker-div .ui-datepicker-header,
    .ui-datepicker .ui-datepicker-header,
    .ui-datepicker-header {
      background: #256f4a !important;
      background-color: #256f4a !important;
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
    #rd-datepicker-wrapper .ui-datepicker-title,
    #ui-datepicker-div .ui-datepicker-title,
    .ui-datepicker .ui-datepicker-title,
    .ui-datepicker-title {
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
    #rd-datepicker-wrapper .ui-datepicker-title select,
    #ui-datepicker-div .ui-datepicker-title select,
    .ui-datepicker .ui-datepicker-title select,
    .ui-datepicker-title select,
    .ui-datepicker-month,
    .ui-datepicker-year {
      background: rgba(255,255,255,0.3) !important;
      background-color: rgba(255,255,255,0.3) !important;
      color: #fff !important;
      border: 1px solid rgba(255,255,255,0.4) !important;
      padding: 6px 10px !important;
      border-radius: 6px !important;
      margin: 0 4px !important;
      font-weight: 600 !important;
      min-width: 72px !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-title select option,
    #ui-datepicker-div .ui-datepicker-title select option,
    .ui-datepicker select option {
      background: #fff !important;
      background-color: #fff !important;
      color: #333 !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-prev,
    #rd-datepicker-wrapper .ui-datepicker-next,
    #ui-datepicker-div .ui-datepicker-prev,
    #ui-datepicker-div .ui-datepicker-next,
    .ui-datepicker .ui-datepicker-prev,
    .ui-datepicker .ui-datepicker-next,
    .ui-datepicker-prev,
    .ui-datepicker-next {
      background: rgba(255,255,255,0.2) !important;
      background-color: rgba(255,255,255,0.2) !important;
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
    #rd-datepicker-wrapper .ui-datepicker-prev:hover,
    #rd-datepicker-wrapper .ui-datepicker-next:hover,
    #ui-datepicker-div .ui-datepicker-prev:hover,
    #ui-datepicker-div .ui-datepicker-next:hover,
    .ui-datepicker .ui-datepicker-prev:hover,
    .ui-datepicker .ui-datepicker-next:hover,
    .ui-datepicker-prev:hover,
    .ui-datepicker-next:hover {
      background: rgba(255,255,255,0.3) !important;
      background-color: rgba(255,255,255,0.3) !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-prev::before,
    #ui-datepicker-div .ui-datepicker-prev::before,
    .ui-datepicker .ui-datepicker-prev::before,
    .ui-datepicker-prev::before {
      content: '<';
      font-size: 16px !important;
      font-weight: 700 !important;
      line-height: 1 !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-next::before,
    #ui-datepicker-div .ui-datepicker-next::before,
    .ui-datepicker .ui-datepicker-next::before,
    .ui-datepicker-next::before {
      content: '>';
      font-size: 16px !important;
      font-weight: 700 !important;
      line-height: 1 !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-prev span,
    #rd-datepicker-wrapper .ui-datepicker-next span,
    #ui-datepicker-div .ui-datepicker-prev span,
    #ui-datepicker-div .ui-datepicker-next span,
    .ui-datepicker .ui-datepicker-prev span,
    .ui-datepicker .ui-datepicker-next span,
    .ui-datepicker-prev span,
    .ui-datepicker-next span {
      display: none !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-calendar,
    #ui-datepicker-div .ui-datepicker-calendar {
      background: #fff !important;
      width: 100% !important;
      border-collapse: collapse !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-calendar thead,
    #ui-datepicker-div .ui-datepicker-calendar thead {
      background: #f8f9fa !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-calendar th,
    #ui-datepicker-div .ui-datepicker-calendar th {
      background: #f8f9fa !important;
      padding: 8px 4px !important;
      font-weight: 600 !important;
      color: #666 !important;
      text-align: center !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-calendar td,
    #ui-datepicker-div .ui-datepicker-calendar td {
      background: #fff !important;
      padding: 2px !important;
      text-align: center !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-calendar td a,
    #rd-datepicker-wrapper .ui-datepicker-calendar td span,
    #ui-datepicker-div .ui-datepicker-calendar td a,
    #ui-datepicker-div .ui-datepicker-calendar td span {
      background: #fff !important;
      display: block !important;
      padding: 8px !important;
      text-align: center !important;
      color: #333 !important;
      border-radius: 6px !important;
      text-decoration: none !important;
      border: none !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-calendar td a:hover,
    #ui-datepicker-div .ui-datepicker-calendar td a:hover {
      background: #e8f5ef !important;
      color: #256f4a !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-calendar td .ui-state-active,
    #ui-datepicker-div .ui-datepicker-calendar td .ui-state-active {
      background: #256f4a !important;
      color: #fff !important;
      font-weight: 700 !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-calendar td .ui-state-highlight,
    #ui-datepicker-div .ui-datepicker-calendar td .ui-state-highlight {
      background: #e8f5ef !important;
      color: #256f4a !important;
      font-weight: 600 !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-buttonpane,
    #ui-datepicker-div .ui-datepicker-buttonpane {
      background: #fff !important;
      padding-top: 10px !important;
      border-top: 1px solid #e0e0e0 !important;
      margin-top: 10px !important;
      text-align: right !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-buttonpane button,
    #ui-datepicker-div .ui-datepicker-buttonpane button {
      background: #256f4a !important;
      color: #fff !important;
      border: none !important;
      padding: 8px 16px !important;
      border-radius: 5px !important;
      cursor: pointer !important;
      font-weight: 600 !important;
      margin-left: 8px !important;
    }
    #rd-datepicker-wrapper .ui-datepicker-buttonpane button:hover,
    #ui-datepicker-div .ui-datepicker-buttonpane button:hover {
      background: #1f5b3c !important;
    }
    @media (max-width: 480px) {
      #rd-datepicker-wrapper,
      #ui-datepicker-div {
        width: 95% !important;
        max-width: 320px !important;
        font-size: 14px !important;
        padding: 10px !important;
      }
      #rd-datepicker-wrapper .ui-datepicker-header,
      #ui-datepicker-div .ui-datepicker-header {
        padding: 10px 8px !important;
      }
      #rd-datepicker-wrapper .ui-datepicker-title select,
      #ui-datepicker-div .ui-datepicker-title select {
        padding: 4px 6px !important;
        font-size: 13px !important;
      }
      #rd-datepicker-wrapper .ui-datepicker-calendar th,
      #ui-datepicker-div .ui-datepicker-calendar th {
        padding: 6px 2px !important;
        font-size: 11px !important;
      }
      #rd-datepicker-wrapper .ui-datepicker-calendar td a,
      #rd-datepicker-wrapper .ui-datepicker-calendar td span,
      #ui-datepicker-div .ui-datepicker-calendar td a,
      #ui-datepicker-div .ui-datepicker-calendar td span {
        padding: 6px 4px !important;
        font-size: 13px !important;
        min-width: 28px !important;
      }
      #rd-datepicker-wrapper .ui-datepicker-buttonpane button,
      #ui-datepicker-div .ui-datepicker-buttonpane button {
        padding: 6px 12px !important;
        font-size: 13px !important;
      }
      #rd-datepicker-wrapper .ui-datepicker-prev,
      #rd-datepicker-wrapper .ui-datepicker-next,
      #ui-datepicker-div .ui-datepicker-prev,
      #ui-datepicker-div .ui-datepicker-next {
        width: 28px !important;
        height: 28px !important;
        font-size: 14px !important;
      }
    }
    @media (min-width: 481px) and (max-width: 768px) {
      #rd-datepicker-wrapper,
      #ui-datepicker-div {
        width: auto !important;
        max-width: 340px !important;
      }
    }
  </style>
  <div class="wrap">
    <h1>Refeitório Digital — Configurações</h1>

    <?php if ($can_edit_settings) : ?>
    <form method="post" action="options.php">
      <?php settings_fields('rd_settings'); ?>

      <table class="form-table" role="presentation">
        <tr>
          <th><label for="rd_date_start">Data início (janela)</label></th>
          <td>
            <input type="text" id="rd_date_start" class="rd-date-input"
                   name="<?php echo esc_attr(RD_OPT_DATE_START); ?>"
                   value="<?php echo esc_attr($date_start_br); ?>"
                   placeholder="dd/mm/aaaa"
                   maxlength="10">
            <p class="description">Data inicial para agendamentos (DD/MM/AAAA). Deixe em branco para usar janela de dias.</p>
          </td>
        </tr>
        <tr>
          <th><label for="rd_date_end">Data fim (janela)</label></th>
          <td>
            <input type="text" id="rd_date_end" class="rd-date-input"
                   name="<?php echo esc_attr(RD_OPT_DATE_END); ?>"
                   value="<?php echo esc_attr($date_end_br); ?>"
                   placeholder="dd/mm/aaaa"
                   maxlength="10">
            <p class="description">Data final para agendamentos (DD/MM/AAAA). Deixe em branco para usar janela de dias.</p>
          </td>
        </tr>
        <tr>
          <th><label for="rd_window_days">Janela de agendamento (dias)</label></th>
          <td>
            <input type="number" id="rd_window_days" min="0"
                   name="<?php echo esc_attr(RD_OPT_WINDOW_DAYS); ?>"
                   value="<?php echo esc_attr($window_days); ?>">
			<p class="description">Número de dias à frente que usuários podem agendar e editar. Ignorado se Data Início/Fim estiverem preenchidas. Administradores sempre podem agendar até 30 dias.</p>
            
          </td>
        </tr>
        <tr>
          <th>Horário-limite por categoria</th>
          <td>
            <?php
            $categories = function_exists('rd_get_meal_categories')
                ? rd_get_meal_categories(false)
                : [];
            if ( empty($categories) ) :
            ?>
              <p class="description">Nenhuma categoria cadastrada. <a href="<?php echo esc_url(admin_url('admin.php?page=refeitorio-digital-categorias')); ?>">Adicionar categorias</a></p>
            <?php else : ?>
              <table class="widefat fixed" style="max-width:400px;">
                <thead>
                  <tr>
                    <th>Refeição</th>
                    <th style="width:120px;">Horário (HH:MM)</th>
                  </tr>
                </thead>
                <tbody>
                  <?php foreach ( $categories as $cat ) : ?>
                  <tr>
                    <td>
                      <strong><?php echo esc_html($cat['label']); ?></strong>
                      <br><small style="color:#666;"><?php echo esc_html($cat['code']); ?></small>
                    </td>
                    <td>
                      <input type="text"
                             class="rd-cutoff-input"
                             data-category-id="<?php echo esc_attr($cat['id']); ?>"
                             value="<?php echo esc_attr($cat['cutoff_hhmm'] ?? ''); ?>"
                             placeholder="HH:MM"
                             maxlength="5"
                             style="width:80px;">
                    </td>
                  </tr>
                  <?php endforeach; ?>
                </tbody>
              </table>
              <button type="button" class="button" id="rd-save-cutoffs" style="margin-top:10px;">Salvar horários</button>
              <span id="rd-cutoff-msg" style="margin-left:10px;"></span>
              <p class="description" style="margin-top:8px;">
                Horário limite para solicitação (aplica-se ao dia anterior à refeição).<br>
                Ex.: Se o Jantar tem limite 14:00, o usuário pode solicitar até 14:00 do dia anterior.
              </p>
            <?php endif; ?>
          </td>
        </tr>
        <tr>
          <th><label for="rd_report_emails">E-mails do relatório diário</label></th>
          <td>
            <input type="text" id="rd_report_emails" size="60"
                   placeholder="email1@exemplo.com, email2@exemplo.com"
                   name="<?php echo esc_attr(RD_OPT_REPORT_EMAILS); ?>"
                   value="<?php echo esc_attr($report_emails); ?>">
          </td>
        </tr>
        <tr>
          <th><label for="rd_form_image">Imagem do formulário</label></th>
          <td>
            <div class="rd-image-upload">
              <input type="hidden" id="rd_form_image" name="<?php echo esc_attr(RD_OPT_FORM_IMAGE); ?>" value="<?php echo esc_attr($form_image_id); ?>">
              <div id="rd-image-preview" style="margin-bottom: 10px;">
                <?php if ( $form_image_id ) :
                  $img = wp_get_attachment_image($form_image_id, 'medium');
                  if ( $img ) echo $img;
                endif; ?>
              </div>
              <button type="button" class="button" id="rd-upload-image-btn">
                <?php echo $form_image_id ? 'Alterar Imagem' : 'Selecionar Imagem'; ?>
              </button>
              <button type="button" class="button" id="rd-remove-image-btn" style="<?php echo $form_image_id ? '' : 'display:none;'; ?>">Remover</button>
              <p class="description">Imagem exibida acima do formulário de solicitação.</p>
            </div>
          </td>
        </tr>
        <tr>
          <th><label for="rd_form_file">Arquivo do formulário (.xlsx, .xls, .pdf)</label></th>
          <td>
            <div class="rd-file-upload">
              <input type="hidden" id="rd_form_file" name="<?php echo esc_attr(RD_OPT_FORM_FILE); ?>" value="<?php echo esc_attr($form_file_id); ?>">
              <div id="rd-file-info" style="margin-bottom: 10px;">
                <?php if ( $form_file_id ) :
                  $file_url = wp_get_attachment_url($form_file_id);
                  $file_name = basename(get_attached_file($form_file_id));
                  if ( $file_url ) :
                    echo '<a href="' . esc_url($file_url) . '" target="_blank" style="text-decoration:none;">';
                    echo '<span class="dashicons dashicons-media-spreadsheet" style="font-size:20px;"></span> ';
                    echo esc_html($file_name);
                    echo '</a>';
                  endif;
                endif; ?>
              </div>
              <button type="button" class="button" id="rd-upload-file-btn">
                <?php echo $form_file_id ? 'Alterar Arquivo' : 'Selecionar Arquivo'; ?>
              </button>
              <button type="button" class="button" id="rd-remove-file-btn" style="<?php echo $form_file_id ? '' : 'display:none;'; ?>">Remover</button>
              <p class="description">Arquivo de planilha ou PDF exibido como link de download no formulário. Apenas .xlsx, .xls ou .pdf são aceitos.</p>
            </div>
          </td>
        </tr>
      </table>

      <?php submit_button(); ?>
    </form>
    <?php endif; ?>

    <script>
    jQuery(document).ready(function($) {
      var dateInputs = $('.rd-date-input');
      dateInputs.datepicker({
        dateFormat: 'dd/mm/yy',
        changeMonth: true,
        changeYear: true,
        showButtonPanel: true,
        yearRange: '-1:+1',
        beforeShow: function(input, inst) {
          $(input).attr('autocomplete', 'off');
          var currentInput = input;
          setTimeout(function() {
            $('#ui-datepicker-div').attr('id', 'rd-datepicker-wrapper');
            $('.ui-datepicker-close').off('click').on('click', function(e) {
              e.preventDefault();
              $(currentInput).datepicker('hide');
            });
          }, 1);
        },
        onSelect: function(dateText, inst) {
          var that = this;
          setTimeout(function() {
            $(that).datepicker('hide');
          }, 0);
        },
        onClose: function() {
          $(this).blur();
        }
      });
      $(document).on('mousedown', function(e) {
        var target = $(e.target);
        var isDatepicker = target.closest('.ui-datepicker').length > 0 ||
                         target.closest('#ui-datepicker-div').length > 0 ||
                         target.closest('#rd-datepicker-wrapper').length > 0;
        var isInput = target.hasClass('rd-date-input');
        dateInputs.each(function() {
          var input = $(this);
          if (!isDatepicker && !isInput && input.datepicker('widget').is(':visible')) {
            input.datepicker('hide');
          }
        });
      });
      var mediaUploader;
      $('#rd-upload-image-btn').on('click', function(e) {
        e.preventDefault();
        if (mediaUploader) { mediaUploader.open(); return; }
        mediaUploader = wp.media({
          title: 'Selecionar Imagem do Formulário',
          button: { text: 'Usar esta imagem' },
          library: { type: 'image' },
          multiple: false
        });
        mediaUploader.on('select', function() {
          var attachment = mediaUploader.state().get('selection').first().toJSON();
          $('#rd_form_image').val(attachment.id);
          $('#rd-image-preview').html('<img src="' + attachment.url + '" style="max-width:300px;height:auto;">');
          $('#rd-upload-image-btn').text('Alterar Imagem');
          $('#rd-remove-image-btn').show();
        });
        mediaUploader.open();
      });
      $('#rd-remove-image-btn').on('click', function(e) {
        e.preventDefault();
        $('#rd_form_image').val('');
        $('#rd-image-preview').html('');
        $('#rd-upload-image-btn').text('Selecionar Imagem');
        $(this).hide();
      });
      var fileUploader;
      $('#rd-upload-file-btn').on('click', function(e) {
        e.preventDefault();
        if (fileUploader) { fileUploader.open(); return; }
        fileUploader = wp.media({
          title: 'Selecionar Arquivo (Excel ou PDF)',
          button: { text: 'Usar este arquivo' },
          library: { type: ['application/vnd.ms-excel', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', 'application/pdf'] },
          multiple: false
        });
        fileUploader.on('select', function() {
          var attachment = fileUploader.state().get('selection').first().toJSON();
          var ext = attachment.filename.split('.').pop().toLowerCase();
          if (['xlsx', 'xls', 'pdf'].indexOf(ext) === -1) {
            alert('Apenas arquivos .xlsx, .xls ou .pdf são permitidos.');
            return;
          }
          $('#rd_form_file').val(attachment.id);
          var icon = ext === 'pdf' ? 'dashicons-pdf' : 'dashicons-media-spreadsheet';
          $('#rd-file-info').html(
            '<a href="' + attachment.url + '" target="_blank" style="text-decoration:none;">' +
            '<span class="dashicons ' + icon + '" style="font-size:20px;"></span> ' +
            attachment.filename +
            '</a>'
          );
          $('#rd-upload-file-btn').text('Alterar Arquivo');
          $('#rd-remove-file-btn').show();
        });
        fileUploader.open();
      });
      $('#rd-remove-file-btn').on('click', function(e) {
        e.preventDefault();
        $('#rd_form_file').val('');
        $('#rd-file-info').html('');
        $('#rd-upload-file-btn').text('Selecionar Arquivo');
        $(this).hide();
      });

      $('.rd-cutoff-input').on('input', function() {
        var v = $(this).val().replace(/[^\d:]/g, '');
        if (v.length === 2 && v.indexOf(':') === -1) {
          v = v + ':';
        }
        $(this).val(v.substring(0, 5));
      });

      $('#rd-save-cutoffs').on('click', function() {
        var btn = $(this);
        var msg = $('#rd-cutoff-msg');
        var data = [];

        $('.rd-cutoff-input').each(function() {
          var id = $(this).data('category-id');
          var cutoff = $(this).val().trim();
          if (cutoff && !/^\d{2}:\d{2}$/.test(cutoff)) {
            msg.text('Formato inválido. Use HH:MM').css('color', '#c00');
            return false;
          }
          data.push({ id: id, cutoff_hhmm: cutoff });
        });

        if (msg.text().indexOf('inválido') > -1) return;

        btn.prop('disabled', true);
        msg.text('Salvando...').css('color', '#666');

        $.ajax({
          url: '<?php echo esc_url(rest_url('rd/v1/meal-categories/cutoffs')); ?>',
          method: 'POST',
          headers: { 'X-WP-Nonce': '<?php echo wp_create_nonce('wp_rest'); ?>' },
          contentType: 'application/json',
          data: JSON.stringify({ categories: data }),
          success: function(res) {
            msg.text('Salvo com sucesso!').css('color', '#080');
            setTimeout(function() { msg.text(''); }, 3000);
          },
          error: function(xhr) {
            var errMsg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Erro ao salvar';
            msg.text(errMsg).css('color', '#c00');
          },
          complete: function() {
            btn.prop('disabled', false);
          }
        });
      });
    });
    </script>
  </div>
  <?php
}