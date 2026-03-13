<?php

function update_jquery_to_latest(): void {
    if (!is_admin()) {
        wp_deregister_script('jquery');
        wp_register_script('jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js', [], '3.7.1', true);	
        wp_enqueue_script('jquery');
    }
}
add_action('wp_enqueue_scripts', 'update_jquery_to_latest');

function wpb_custom_new_menu(): void {
    register_nav_menu('custom-menu', __('Custom Menu'));
}
add_action('init', 'wpb_custom_new_menu');

function desabilitar_script_moment(): void {
    wp_dequeue_script('woffice-theme-script-moment');
    wp_deregister_script('woffice-theme-script-moment');
}
add_action('wp_enqueue_scripts', 'desabilitar_script_moment', 20);

function sdt_remove_ver_css_js(string $src): string {
    return strpos($src, 'ver=') !== false ? remove_query_arg('ver', $src) : $src;
}
add_filter('style_loader_src', 'sdt_remove_ver_css_js', 9999);
add_filter('script_loader_src', 'sdt_remove_ver_css_js', 9999);
add_filter('the_generator', '__return_empty_string');

function logged(): bool {
    if (function_exists('rd_is_authenticated')) {
        return rd_is_authenticated();
    }

    if (isset($_COOKIE['btpUserName']) && isset($_COOKIE['btpUserToken'])) {
        $userName  = htmlspecialchars($_COOKIE['btpUserName'], ENT_COMPAT, 'UTF-8', true);
        $userToken = htmlspecialchars($_COOKIE['btpUserToken'], ENT_COMPAT, 'UTF-8', true);

        $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
        if ($mysqli->connect_errno) {
            error_log('[Auth] Erro na conexão mysqli: ' . $mysqli->connect_error);
            return false;
        }

        $stmt = $mysqli->prepare("SELECT * FROM btpconecta_tokens WHERE token = ? AND user = ? AND ativo = 1 AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())");
        if (!$stmt) {
            error_log('[Auth] Erro no prepared statement: ' . $mysqli->error);
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
    return false;
}

function login_form_render(): string {
    ob_start();
    include get_stylesheet_directory() . '/templates/login-form.php';
    return ob_get_clean();
}

function woffice_child_scripts(): void {
    if (!is_admin() && !in_array($GLOBALS['pagenow'], ['wp-login.php', 'wp-register.php'], true)) {
        $theme_info = wp_get_theme();
        wp_enqueue_style(
            'woffice-child-stylesheet',
            get_stylesheet_uri(),
            [],
            $theme_info->get('Version')
        );
    }
}
add_action('wp_enqueue_scripts', 'woffice_child_scripts', 30);

add_filter('show_admin_bar', '__return_false');

function auto_redirect_after_logout(): void {
    wp_redirect(home_url());
    exit;
}
add_action('wp_logout', 'auto_redirect_after_logout');

add_action('template_redirect', function() {
    if (preg_match('/operadores/', $_SERVER['REQUEST_URI'])) {
        if (is_user_logged_in()) {
            wp_redirect(admin_url());
        } else {
            wp_redirect(wp_login_url());
        }
        exit;
    }
});

add_filter('bp_core_fetch_avatar_no_grav', '__return_true');


ini_set('upload_max_size', '64M');
ini_set('post_max_size', '64M');
ini_set('max_execution_time', '30');

function btpconecta_script(): void {
    wp_register_script(
        'btpconecta',
        get_stylesheet_directory_uri() . '/js/btpconecta.js',
        [],
        '1.0.0',
        true
    );
    wp_enqueue_script('btpconecta');
}
add_action('wp_enqueue_scripts', 'btpconecta_script');

if (!function_exists('woffice_postmetas_min')) {
    function woffice_postmetas_min(): void {
        echo '<ul class="post-metadatas list-inline">';
        echo '<li class="updated published"><i class="fa fa-clock-o"></i> ' . get_the_date() . '</li>';
        
        if (get_the_category_list() !== "") {
            echo '<li><i class="fa fa-thumb-tack"></i> ' . get_the_category_list(', ') . '</li>';
        }
        if (get_the_tag_list() !== "") {
            echo '<li class="meta-tags"><i class="fa fa-tags"></i> ' . get_the_tag_list('', ', ') . '</li>';
        }
        echo '</ul>';
    }
}

add_action('admin_menu', 'remove_plugin_update_count');
function remove_plugin_update_count(): void {
    global $menu, $submenu;
    $menu[65][0]             = 'Plugins';
    $submenu['index.php'][10][0] = 'Updates';
}

function wpdocs_custom_excerpt_length(int $length): int {
    return 10;
}
add_filter('excerpt_length', 'wpdocs_custom_excerpt_length', 999);

