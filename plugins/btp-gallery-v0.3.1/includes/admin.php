<?php
defined('ABSPATH') || exit;

add_action('admin_menu', function(){
  add_menu_page('BTP Gallery','BTP Gallery','manage_options','btp-gallery','btp_gal_admin_page','dashicons-format-gallery',58);
});
add_action('admin_init', function(){
  register_setting('btp_gal','btp_gal_defaults');
  register_setting('btp_gal','btp_gal_settings');
});
add_action('wp_ajax_btp_gal_list_albums', function(){
  check_ajax_referer('btp_gal_admin_nonce','nonce');
  if(!current_user_can('manage_options')) wp_send_json_error('forbidden',403);
  $parent=isset($_POST['parent'])?sanitize_text_field(wp_unslash($_POST['parent'])):'';
  $depth =isset($_POST['depth'])?(int)$_POST['depth']:1;
  wp_send_json_success(btp_gal_list_albums($parent,$depth));
});

function btp_gal_admin_page(){
  if(!current_user_can('manage_options')) return;
  $pages=get_pages(['sort_column'=>'post_title','sort_order'=>'ASC']);
  $settings=(array)get_option('btp_gal_settings',[]);
  $base_path=isset($settings['base_path'])?$settings['base_path']:BTP_GAL_BASE_PATH;
  ?>
  <div class="wrap">
    <h1>BTP Gallery</h1>

    <h2>Configurações</h2>
    <form method="post" action="options.php">
      <?php settings_fields('btp_gal'); ?>
      <table class="form-table" role="presentation">
        <tr>
          <th scope="row"><label for="btp_base_path">Base Path (filesystem)</label></th>
          <td>
            <input id="btp_base_path" type="text" name="btp_gal_settings[base_path]" value="<?php echo esc_attr($base_path); ?>" class="regular-text" />
            <p class="description">Caminho absoluto no servidor onde estão os álbuns (ex.: <code>/var/www/html/wp-content/uploads/btp/galerias</code> ou <code>E:/uploads/btp/galerias</code>).</p>
          </td>
        </tr>
      </table>
      <?php submit_button('Salvar configurações'); ?>
    </form>

    <hr>

    <h2 class="title">Gerador de Shortcodes</h2>
    <div id="btp-gal-builder">
      <table class="form-table" role="presentation">
        <tr><th>Ano</th>
          <td><input id="bg-year" type="text" placeholder="2025">
              Depth: <input id="bg-depth" type="number" value="1" min="1" max="4" style="width:70px">
              <button type="button" class="button" id="bg-list">Listar</button>
          </td></tr>
        <tr><th>Diretório do evento</th><td><select id="bg-album"><option value="">— selecione —</option></select></td></tr>
        <tr><th>Página (Página B)</th><td>
            <select id="bg-link"><option value="">— esta página —</option>
            <?php foreach($pages as $p){ $plink=get_permalink($p->ID); echo '<option value="'.esc_attr($plink).'">'.esc_html($p->post_title).'</option>'; } ?>
            </select>
        </td></tr>
        <tr><th>Colunas</th><td><input id="bg-columns" type="number" min="2" max="6" value="4"></td></tr>
        <tr><th>Per page</th><td><input id="bg-per_page" type="number" min="1" value="24"></td></tr>
        <tr><th>Recursive</th><td><select id="bg-recursive"><option value="false">false</option><option value="true">true</option></select></td></tr>
        <tr><th>Título</th><td><select id="bg-title"><option value="human">human</option><option value="raw">raw</option></select></td></tr>
      </table>

      <h3>Árvore</h3>
      <table class="form-table" role="presentation">
        <tr><th>open</th><td><input id="bg-open" type="text" placeholder="2025/CopaBTP2025"></td></tr>
        <tr><th>back_label</th><td><input id="bg-back" type="text" value="← Voltar"></td></tr>
        <tr><th>sep</th><td><input id="bg-sep" type="text" value=" / "></td></tr>
        <tr><th>root_label</th><td><input id="bg-rootlabel" type="text" placeholder="2025"></td></tr>
      </table>

      <p><strong>Shortcode (Galeria):</strong></p>
      <textarea id="bg-code-fixed" class="large-text code" rows="2" readonly></textarea>
      <p><strong>Shortcode (Índice):</strong></p>
      <textarea id="bg-code-index" class="large-text code" rows="2" readonly></textarea>
      <p><strong>Shortcode (Árvore):</strong></p>
      <textarea id="bg-code-tree" class="large-text code" rows="2" readonly></textarea>
    </div>
  </div>

<script>
var btpGalAdminNonce = '<?php echo esc_js(wp_create_nonce('btp_gal_admin_nonce')); ?>';
(function($){
  function build(){
    var y=$('#bg-year').val().trim();
    var d=parseInt($('#bg-depth').val()||'1',10);
    var a=$('#bg-album').val();
    var c=$('#bg-columns').val();
    var pp=$('#bg-per_page').val();
    var r=$('#bg-recursive').val();
    var l=$('#bg-link').val();
    var t=$('#bg-title').val();
    var open=$('#bg-open').val();
    var back=$('#bg-back').val();
    var sep=$('#bg-sep').val();
    var root=$('#bg-rootlabel').val();

    $('#bg-code-fixed').val('[btp_gallery album="'+a+'" columns="'+c+'" per_page="'+pp+'" recursive="'+r+'" download="true"]');
    $('#bg-code-index').val('[btp_gallery_index year="'+y+'" link="'+l+'" columns="'+c+'" depth="'+d+'" title="'+t+'"]');

    var tree='[btp_gallery_tree year="'+y+'" link="'+l+'" columns="'+c+'" title="'+t+'"';
    if(open) tree+=' open="'+open.replace(/"/g,'&quot;')+'"';
    if(back) tree+=' back_label="'+back.replace(/"/g,'&quot;')+'"';
    if(sep) tree+=' sep="'+sep.replace(/"/g,'&quot;')+'"';
    if(root) tree+=' root_label="'+root.replace(/"/g,'&quot;')+'"';
    tree+=']';
    $('#bg-code-tree').val(tree);
  }

  $(document).on('click','#bg-list',function(){
    var y=$('#bg-year').val().trim();
    var d=parseInt($('#bg-depth').val()||'1',10);
    if(!y){ alert('Informe o ano/pasta pai.'); return; }
    $('#bg-album').html('<option>Carregando...</option>');
    $.post(ajaxurl,{action:'btp_gal_list_albums',nonce:btpGalAdminNonce,parent:y,depth:d},function(resp){
      if(!resp||!resp.success){ alert('Erro ao listar.'); return; }
      var html='<option value="">— selecione —</option>';
      resp.data.forEach(function(al){ html+='<option value="'+al.album+'">'+al.album+' ('+al.count+')</option>'; });
      $('#bg-album').html(html);
      build();
    });
  });

  $('#bg-year,#bg-depth,#bg-album,#bg-columns,#bg-per_page,#bg-recursive,#bg-link,#bg-title,#bg-open,#bg-back,#bg-sep,#bg-rootlabel').on('change keyup',build);
  build();
})(jQuery);
</script>
<?php }
