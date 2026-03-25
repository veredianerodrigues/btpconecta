<?php
/*
Plugin Name: BTP Secure Uploads
Description: Armazena uploads em E:\uploads\btp e protege o acesso via rota REST validando cookies btpUserToken/btpUserName. Também valida e entrega arquivos antigos em wp-content/uploads.
Version:     2.1.0
Author:      Verediane Rodrigues
License:     GPLv2 or later
*/

if (!defined('ABSPATH')) {
    exit;
}

const BTP_UPLOAD_DIR     = 'E:/uploads/btp';
const BTP_WP_UPLOAD_DIR  = 'wp-content/uploads';
const BTP_TOKENS_TBL     = 'btpconecta_tokens';
const BTP_TTL_HOURS      = 12;

define('BTP_DEBUG', defined('WP_DEBUG') && WP_DEBUG);

if (BTP_DEBUG) {
    ini_set('display_errors', 1);
    ini_set('display_startup_errors', 1);
    error_reporting(E_ALL & ~E_NOTICE & ~E_USER_NOTICE & ~E_DEPRECATED & ~E_USER_DEPRECATED);
}

function btp_log($message) {
    if (BTP_DEBUG) {
        error_log('[BTP DEBUG] ' . $message);
    }
}

add_action('template_redirect', function () {
    $uri = $_SERVER['REQUEST_URI'] ?? '';
    $excluded = apply_filters('btp_secure_uploads_exclude_dirs', ['photo-gallery']);
    $match = false;
    foreach ($excluded as $dir) {
        if (strpos($uri, '/wp-content/uploads/' . $dir . '/') !== false) {
            $match = true;
            break;
        }
    }
    if (!$match) return;
    if (btp_token_ok()) return;
    status_header(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Não autorizado']);
    exit;
}, 0);

add_filter('upload_dir', function ($dirs) {
    $sub = $dirs['subdir'];
    $dirs['basedir'] = BTP_UPLOAD_DIR;
    $dirs['path']    = trailingslashit(BTP_UPLOAD_DIR) . ltrim($sub, '/');
    $dirs['baseurl'] = home_url('/' . BTP_WP_UPLOAD_DIR);
    $dirs['url']     = $dirs['baseurl'] . $sub;
    return $dirs;
});

function btp_error_response($code, $message) {
    return new WP_REST_Response([
        'error'   => $code,
        'message' => $message,
    ], $code === 'invalid_token' ? 403 : 401);
}

function btp_token_ok(bool $allow_wp_users = true): bool {
    if ($allow_wp_users && is_user_logged_in()) {
        $caps = apply_filters('btp_secure_uploads_bypass_caps', [
            'manage_options',
            'edit_others_posts',
            'upload_files',
        ]);
        foreach ($caps as $cap) {
            if (current_user_can($cap)) {
                btp_log('Bypass: usuário WP autenticado com permissão.');
                return true;
            }
        }
    }

    $token = isset($_COOKIE['btpUserToken']) ? sanitize_text_field($_COOKIE['btpUserToken']) : '';
    $user  = isset($_COOKIE['btpUserName']) ? sanitize_email(urldecode($_COOKIE['btpUserName'])) : '';
    if ($token === '' || $user === '') {
        btp_log("Acesso negado - token ou usuário não fornecido");
        return false;
    }

    global $wpdb;
    $tbl = esc_sql(BTP_TOKENS_TBL);
    $row = $wpdb->get_row($wpdb->prepare(
        "SELECT id, token, user, expires_at, ativo
         FROM {$tbl}
         WHERE token = %s
           AND LOWER(user) = LOWER(%s)
           AND ativo = 1
           AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())
         LIMIT 1",
        $token, $user
    ));
    if (!$row) {
        btp_log("Token não encontrado/inativo/expirado");
        return false;
    }
    $nowUtcTs = current_time('timestamp', true);
    return (int)$row->ativo === 1 &&
           (is_null($row->expires_at) || strtotime($row->expires_at) > $nowUtcTs);
}

add_action('rest_api_init', function () {
    register_rest_route('btp/v1', '/download/(?P<file>.+)', [
        'methods'             => 'GET',
        'args'                => ['file' => ['required' => true]],
        'permission_callback' => '__return_true',
        'callback'            => 'btp_rest_download',
    ]);
});

function btp_rest_download(WP_REST_Request $req) {
    if (!btp_token_ok()) {
        status_header(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['error' => 'Não autorizado']);
        exit;
    }

    $rel = ltrim((string) urldecode($req['file']), '/');
    $rel = wp_normalize_path($rel);

    foreach (explode('/', $rel) as $segment) {
        if ($segment === '..' || $segment === '.') {
            wp_die('Invalid path', '', ['response' => 400]);
        }
    }

    $cands = [
        wp_normalize_path(trailingslashit(ABSPATH . BTP_WP_UPLOAD_DIR) . $rel),
        wp_normalize_path(trailingslashit(BTP_UPLOAD_DIR) . $rel),
    ];

    $allowedRoots = [
        realpath(ABSPATH . BTP_WP_UPLOAD_DIR),
        realpath(BTP_UPLOAD_DIR),
    ];

    foreach ($cands as $p) {
        if (is_link($p)) {
            btp_log("Symlink rejeitado: {$p}");
            continue;
        }

        $real = realpath($p);
        if (!$real || !is_file($real) || !is_readable($real)) {
            continue;
        }

        $inside = false;
        foreach ($allowedRoots as $root) {
            if ($root && strpos($real, rtrim($root, '/') . '/') === 0) {
                $inside = true;
                break;
            }
        }
        if (!$inside) {
            continue;
        }

        $ft   = wp_check_filetype($real);
        $mime = $ft['type'] ?: 'application/octet-stream';

        if (ob_get_level()) {
            @ob_end_clean();
        }

        header("Content-Type: {$mime}");
        header("Content-Length: " . filesize($real));
        $safe_name = preg_replace('/[\r\n"\\\\]/', '_', basename($real));
        header('Content-Disposition: inline; filename="' . $safe_name . '"');
        header('Cache-Control: private, max-age=3600');
        header('Vary: Cookie');
        header('X-Content-Type-Options: nosniff');

        readfile($real);
        exit;
    }

    wp_die('Arquivo não encontrado', '', ['response' => 404]);
}

function btp_activate() {
    if (!file_exists(BTP_UPLOAD_DIR)) {
        if (!wp_mkdir_p(BTP_UPLOAD_DIR)) {
            error_log('Falha ao criar diretório seguro: ' . BTP_UPLOAD_DIR);
            return false;
        }
    }

    $secure_htaccess = <<<HTACCESS
<IfModule mod_authz_core.c>
    Require all denied
</IfModule>
<IfModule !mod_authz_core.c>
    Order deny,allow
    Deny from all
</IfModule>
<FilesMatch "\.(jpe?g|png|gif|webp|avif|svg|pdf|docx|xlsx|zip|mp4|mp3)$">
    Require all denied
</FilesMatch>
HTACCESS;

    if (@file_put_contents(BTP_UPLOAD_DIR . '/.htaccess', $secure_htaccess) === false) {
        error_log('Falha ao criar .htaccess na pasta segura');
    }

    $excluded = apply_filters('btp_secure_uploads_exclude_dirs', [
        'photo-gallery',
    ]);
    $rules = ['<IfModule mod_rewrite.c>', 'RewriteEngine On'];
    foreach ($excluded as $dir) {
        $rules[] = 'RewriteCond %{REQUEST_URI} !^/wp-content/uploads/' . preg_quote($dir, null) . '/';
    }
    $rules = array_merge($rules, [
        'RewriteRule ^wp-content/uploads/(.+)$ index.php?rest_route=/btp/v1/download/$1 [QSA,L]',
        '</IfModule>',
        '<IfModule mod_headers.c>',
        'Header set Vary "Cookie"',
        '</IfModule>',
    ]);

    if (!insert_with_markers(ABSPATH . '.htaccess', 'BTP_SECURE_UPLOADS', $rules)) {
        error_log('Falha ao atualizar .htaccess principal');
    }

    update_option('upload_path', BTP_UPLOAD_DIR);
    flush_rewrite_rules();

    if (function_exists('flush_rewrite_rules_hard')) {
        flush_rewrite_rules_hard();
    }
}
register_activation_hook(__FILE__, 'btp_activate');

register_deactivation_hook(__FILE__, function () {
    insert_with_markers(ABSPATH . '.htaccess', 'BTP_SECURE_UPLOADS', []);
    update_option('upload_path', '');
    flush_rewrite_rules();
    @unlink(BTP_UPLOAD_DIR . '/.htaccess');
});
