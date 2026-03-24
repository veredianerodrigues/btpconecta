<?php
if (!defined('ABSPATH')) {
    exit;
}

require_once get_template_directory() . '/modules/refeitorio/loader.php';

function btpconecta_setup(): void {
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption']);
    add_theme_support('editor-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');
    add_theme_support('automatic-feed-links');
    add_theme_support('elementor');

    register_nav_menus([
        'primary'         => __('Menu Principal', 'btpconecta'),
        'home-shortcuts'  => __('Atalhos da Home (grid 3×2)', 'btpconecta'),
    ]);
}
add_action('after_setup_theme', 'btpconecta_setup');

function btpconecta_content_width(): void {
    $GLOBALS['content_width'] = 780;
}
add_action('after_setup_theme', 'btpconecta_content_width', 0);

function btpconecta_scripts(): void {
    wp_enqueue_style(
        'btpconecta-google-fonts',
        'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=PT+Sans:wght@400;700&display=swap',
        [],
        null
    );

    wp_enqueue_style(
        'btpconecta-style',
        get_template_directory_uri() . '/assets/css/style.css',
        ['btpconecta-google-fonts'],
        '1.1.0'
    );

    wp_deregister_script('jquery');
    wp_register_script('jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js', [], '3.7.1', true);
    wp_enqueue_script('jquery');

    wp_enqueue_script(
        'btpconecta-main',
        get_template_directory_uri() . '/assets/js/main.js',
        ['jquery'],
        '1.1.0',
        true
    );

    if (is_single()) {
        wp_localize_script('btpconecta-main', 'btpShare', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('btp_share_email'),
        ]);
    }

    $horarios_raw = get_option('btp_horarios_data', '');
    if ($horarios_raw) {
        wp_localize_script('btpconecta-main', 'btpHorarios', [
            'grupos' => json_decode($horarios_raw, true),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'btpconecta_scripts');

remove_action('wp_head', 'wp_generator');
remove_filter('the_generator', 'wp_generator');

add_filter('show_admin_bar', '__return_false');

function btpconecta_remove_ver(string $src): string {
    return strpos($src, 'ver=') !== false ? remove_query_arg('ver', $src) : $src;
}
add_filter('style_loader_src',  'btpconecta_remove_ver', 9999);
add_filter('script_loader_src', 'btpconecta_remove_ver', 9999);

function btpconecta_logged(): bool {
    if (!isset($_COOKIE['btpUserName'], $_COOKIE['btpUserToken'])) {
        return false;
    }

    $userName  = htmlspecialchars($_COOKIE['btpUserName'],  ENT_COMPAT, 'UTF-8', true);
    $userToken = htmlspecialchars($_COOKIE['btpUserToken'], ENT_COMPAT, 'UTF-8', true);

    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASSWORD') || !defined('DB_NAME')) {
        return false;
    }

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_errno) {
        error_log('[BTPAuth] Erro na conexão: ' . $mysqli->connect_error);
        return false;
    }

    $stmt = $mysqli->prepare(
        "SELECT id FROM btpconecta_tokens
         WHERE token = ? AND user = ? AND ativo = 1
         AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())
         LIMIT 1"
    );
    if (!$stmt) {
        error_log('[BTPAuth] Erro no prepare: ' . $mysqli->error);
        $mysqli->close();
        return false;
    }

    $stmt->bind_param('ss', $userToken, $userName);
    $stmt->execute();
    $result = $stmt->get_result();

    $authenticated = ($result->num_rows === 1);

    $stmt->close();
    $mysqli->close();

    return $authenticated;
}

function btpconecta_login_form_render(): string {
    ob_start();
    include get_template_directory() . '/templates/login-form.php';
    return ob_get_clean();
}

function btpconecta_excerpt_length(): int {
    return 20;
}
add_filter('excerpt_length', 'btpconecta_excerpt_length', 999);

function btpconecta_excerpt_more(): string {
    return '…';
}
add_filter('excerpt_more', 'btpconecta_excerpt_more');

function btpconecta_pagination(): void {
    the_posts_pagination([
        'mid_size'           => 2,
        'prev_text'          => '&laquo; Anterior',
        'next_text'          => 'Próxima &raquo;',
        'before_page_number' => '',
    ]);
}

function btpconecta_allowed_categories(): array {
    return [
        'auditores-internos',
        'masterplan',
        'noticias',
        'acontece-na-btp',
        'noticias-do-setor',
        'newsletter',
    ];
}

add_filter('category_link', function (string $link): string {
    return str_replace('/category/', '/', $link);
}, 10, 1);

add_action('init', function (): void {
    foreach (btpconecta_allowed_categories() as $slug) {
        add_rewrite_rule(
            '^' . preg_quote($slug, '#') . '/?$',
            'index.php?category_name=' . $slug,
            'bottom'
        );
    }
});

function btpconecta_restrict_category_archives(): void {
    if ( ! is_category() ) {
        return;
    }

    $current_slug = get_queried_object()->slug ?? '';

    if ( ! in_array( $current_slug, btpconecta_allowed_categories(), true ) ) {
        global $wp_query;
        $wp_query->set_404();
        status_header( 404 );
        nocache_headers();
    }
}
add_action( 'template_redirect', 'btpconecta_restrict_category_archives' );

add_filter('pre_get_posts', function (WP_Query $query): void {
    if (!$query->is_main_query() || !$query->is_search() || is_admin()) {
        return;
    }
    $query->set('post_type', ['post', 'page']);
});

function btpconecta_first_content_image(int $post_id): string {
    $content = get_post_field('post_content', $post_id);

    $placeholder_patterns = [
        'elementor/assets/images/placeholder',
        'wp-includes/images/media/',
        'placeholder.png',
        'placeholder.jpg',
        'placeholder.svg',
    ];

    preg_match_all('/<img[^>]+src=["\']([^"\']+)["\'][^>]*>/i', $content, $matches);

    foreach ($matches[1] ?? [] as $src) {
        $is_placeholder = false;
        foreach ($placeholder_patterns as $pattern) {
            if (stripos($src, $pattern) !== false) {
                $is_placeholder = true;
                break;
            }
        }
        if (!$is_placeholder) {
            return $src;
        }
    }

    return '';
}

function btpconecta_share_email(): void {
    check_ajax_referer('btp_share_email', 'nonce');

    $to      = sanitize_email(wp_unslash($_POST['to'] ?? ''));
    $post_id = (int) ($_POST['post_id'] ?? 0);

    if (!is_email($to) || !$post_id) {
        wp_send_json_error(['msg' => 'Dados inválidos.']);
    }

    $post      = get_post($post_id);
    if (!$post) {
        wp_send_json_error(['msg' => 'Post não encontrado.']);
    }
    $title     = get_the_title($post_id);
    $url       = get_permalink($post_id);
    $excerpt   = get_the_excerpt($post_id) ?: wp_trim_words($post->post_content, 30, '…');
    $thumb_url = get_the_post_thumbnail_url($post_id, 'medium_large') ?: '';
    $logo_url  = get_template_directory_uri() . '/images/logo_btp.png';
    $from_name = get_bloginfo('name');
    $from_mail = get_option('admin_email');

    $thumb_html = $thumb_url
        ? '<img src="' . esc_url($thumb_url) . '" alt="" style="width:100%;max-width:600px;height:auto;display:block;border-radius:8px;margin-bottom:20px;">'
        : '';

    $body = '
<!DOCTYPE html>
<html lang="pt-BR">
<head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1"></head>
<body style="margin:0;padding:0;background:#f4f4f4;font-family:Arial,Helvetica,sans-serif;">
<table width="100%" cellpadding="0" cellspacing="0" style="background:#f4f4f4;padding:30px 0;">
  <tr><td align="center">
    <table width="600" cellpadding="0" cellspacing="0" style="background:#ffffff;border-radius:12px;overflow:hidden;max-width:600px;width:100%;">
      <tr><td style="background:#214549;padding:20px 30px;">
        <img src="' . esc_url($logo_url) . '" alt="BTP Conecta" style="height:40px;width:auto;">
      </td></tr>
      <tr><td style="padding:30px;">
        ' . $thumb_html . '
        <h1 style="font-size:22px;color:#214549;margin:0 0 14px;">' . esc_html($title) . '</h1>
        <p style="font-size:15px;color:#444;line-height:1.7;margin:0 0 24px;">' . esc_html($excerpt) . '</p>
        <a href="' . esc_url($url) . '" style="display:inline-block;background:#4d7c3a;color:#ffffff;text-decoration:none;padding:12px 28px;border-radius:6px;font-weight:bold;font-size:15px;">Ler conteúdo completo</a>
      </td></tr>
      <tr><td style="background:#f5f5f5;padding:16px 30px;font-size:12px;color:#888;text-align:center;">
        BTP Conecta &copy; ' . date('Y') . ' — Este e-mail foi compartilhado por um colaborador.
      </td></tr>
    </table>
  </td></tr>
</table>
</body></html>';

    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        'From: ' . $from_name . ' <' . $from_mail . '>',
    ];

    $sent = wp_mail($to, 'Compartilhado com você: ' . $title, $body, $headers);

    if ($sent) {
        wp_send_json_success(['msg' => 'E-mail enviado com sucesso!']);
    } else {
        wp_send_json_error(['msg' => 'Não foi possível enviar. Tente novamente.']);
    }
}
add_action('wp_ajax_btp_share_email',        'btpconecta_share_email');
add_action('wp_ajax_nopriv_btp_share_email', 'btpconecta_share_email');

add_filter('single_template', function (string $template): string {
    if (is_single() && in_category('newsletter')) {
        $custom = get_template_directory() . '/single-newsletter.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

function btpconecta_get_post_views(int $post_id): int {
    return (int) get_post_meta($post_id, '_btp_views', true);
}

add_action('template_redirect', function (): void {
    if (!is_single()) {
        return;
    }
    $post_id = get_queried_object_id();
    update_post_meta($post_id, '_btp_views', btpconecta_get_post_views($post_id) + 1);
});

add_filter('rest_authentication_errors', function ($result) {
    $uri = $_SERVER['REQUEST_URI']   ?? '';
    $qs  = $_SERVER['QUERY_STRING']  ?? '';

    $is_btp = strpos($uri, '/wp-json/btp/v1/download/') !== false
           || strpos($qs,  'rest_route=/btp/v1/download/') !== false;

    if (!$is_btp) return $result;

    $user_id = wp_validate_auth_cookie('', 'logged_in');
    if ($user_id) {
        wp_set_current_user($user_id);
        return null;
    }

    return $result;
}, 20);

require_once get_template_directory() . '/inc/parse-horario.php';
require_once get_template_directory() . '/inc/admin-horario.php';

add_action('add_meta_boxes', function (): void {
    add_meta_box(
        'btp_categories_meta',
        'Categorias para listar',
        'btpconecta_categories_meta_box_cb',
        'page',
        'side',
        'default'
    );
});

function btpconecta_categories_meta_box_cb(WP_Post $post): void {
    wp_nonce_field('btp_categories_save', 'btp_categories_nonce');
    $value = get_post_meta($post->ID, '_btp_categories', true);
    ?>
    <label for="btp_cat_field" style="display:block;margin-bottom:5px;font-size:12px;color:#444;">
        Slugs separados por vírgula:
    </label>
    <input
        type="text"
        id="btp_cat_field"
        name="btp_categories"
        value="<?php echo esc_attr($value); ?>"
        style="width:100%;"
        placeholder="ex: noticias, acontece-na-btp"
    >
    <p style="font-size:11px;color:#888;margin-top:6px;">
        Usado pelo template <em>Listagem de Categoria</em>.<br>
        Com múltiplos slugs, o filtro aparece automaticamente.
    </p>
    <?php
}

add_action('save_post_page', function (int $post_id): void {
    if (
        !isset($_POST['btp_categories_nonce']) ||
        !wp_verify_nonce($_POST['btp_categories_nonce'], 'btp_categories_save')
    ) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['btp_categories'])) {
        update_post_meta(
            $post_id,
            '_btp_categories',
            sanitize_text_field($_POST['btp_categories'])
        );
    }
});

function btpconecta_category_color(string $slug = ''): string {
    $colors = [
        'institucional'           => '#214549',
        'rh-para-voce'            => '#3AAA35',
        'performance-e-processos' => '#E2AB3B',
        'noticias'                => '#1C6C7F',
        'central-de-servicos'     => '#E94E1B',
    ];
    return $colors[$slug] ?? '#E94E1B';
}
