<?php
/**
 * BTP Conecta — functions.php
 */

// ─── Suporte ao tema ──────────────────────────────────────────────────────────
function btpconecta_setup(): void {
    add_theme_support('post-thumbnails');
    add_theme_support('title-tag');
    add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption']);
    add_theme_support('editor-styles');
    add_theme_support('align-wide');
    add_theme_support('responsive-embeds');
    add_theme_support('wp-block-styles');
    add_theme_support('automatic-feed-links');
    register_nav_menus([
        'primary'           => __('Menu Principal', 'btpconecta'),
        'left-mobile-menu'  => __('Left Mobile Menu', 'btpconecta'),
        'right-mobile-menu' => __('Right Mobile Menu', 'btpconecta'),
    ]);
}
add_action('after_setup_theme', 'btpconecta_setup');

function btpconecta_content_width(): void {
    $GLOBALS['content_width'] = 780;
}
add_action('after_setup_theme', 'btpconecta_content_width', 0);

// ─── Enqueue de estilos e scripts ─────────────────────────────────────────────
function btpconecta_scripts(): void {
    // Google Fonts
    wp_enqueue_style(
        'btpconecta-google-fonts',
        'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=PT+Sans:wght@400;700&display=swap',
        [],
        null
    );
    // CSS principal
    wp_enqueue_style(
        'btpconecta-style',
        get_template_directory_uri() . '/assets/css/style.css',
        ['btpconecta-google-fonts'],
        '1.0.0'
    );
    // jQuery via CDN
    wp_deregister_script('jquery');
    wp_register_script('jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js', [], '3.7.1', true);
    wp_enqueue_script('jquery');
    // JS principal
    wp_enqueue_script(
        'btpconecta-main',
        get_template_directory_uri() . '/assets/js/main.js',
        ['jquery'],
        '1.0.0',
        true
    );
}
add_action('wp_enqueue_scripts', 'btpconecta_scripts');

// ─── Segurança ────────────────────────────────────────────────────────────────
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');
add_filter('show_admin_bar', '__return_false');

function btpconecta_remove_ver(string $src): string {
    return strpos($src, 'ver=') !== false ? remove_query_arg('ver', $src) : $src;
}
add_filter('style_loader_src',  'btpconecta_remove_ver', 9999);
add_filter('script_loader_src', 'btpconecta_remove_ver', 9999);

// ─── Autenticação customizada ─────────────────────────────────────────────────
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

// ─── Excerpt ──────────────────────────────────────────────────────────────────
function btpconecta_excerpt_length(): int {
    return 20;
}
add_filter('excerpt_length', 'btpconecta_excerpt_length', 999);

function btpconecta_excerpt_more(): string {
    return '…';
}
add_filter('excerpt_more', 'btpconecta_excerpt_more');

// ─── Widgets ──────────────────────────────────────────────────────────────────
function btpconecta_widgets_init(): void {
    register_sidebar([
        'name'          => __('Sidebar do Menu', 'btpconecta'),
        'id'            => 'sidebar-menu',
        'before_widget' => '<div class="widget %2$s">',
        'after_widget'  => '</div>',
        'before_title'  => '<h3 class="widget-title">',
        'after_title'   => '</h3>',
    ]);
}
add_action('widgets_init', 'btpconecta_widgets_init');

// ─── Paginação ────────────────────────────────────────────────────────────────
function btpconecta_pagination(): void {
    the_posts_pagination([
        'mid_size'           => 2,
        'prev_text'          => '&laquo; Anterior',
        'next_text'          => 'Próxima &raquo;',
        'before_page_number' => '',
    ]);
}

// ─── Cor da categoria (para os badges) ───────────────────────────────────────
function btpconecta_category_color(int $cat_id = 0): string {
    $colors = [
        90  => '#214549',
        87  => '#3AAA35',
        89  => '#E2AB3B',
        220 => '#1C6C7F',
    ];
    return $colors[$cat_id] ?? '#E94E1B';
}
