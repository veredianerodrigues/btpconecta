<?php
if (!defined('ABSPATH')) {
    exit;
}

// ── Módulos do tema ───────────────────────────────────────────────────────────
require_once get_template_directory() . '/modules/refeitorio/loader.php';
/**
 * BTP Conecta — functions.php
 *
 * Funções e hooks do tema customizado.(standalone).
 *
 * @package btpconecta
 * @version 1.0.0
 */

// ─── Suporte ao tema ──────────────────────────────────────────────────────────

/**
 * Registra suporte a funcionalidades do WordPress e locais de menu.
 * Executado no hook 'after_setup_theme'.
 */
function btpconecta_setup(): void {
    add_theme_support('post-thumbnails');           // Imagens destacadas nos posts
    add_theme_support('title-tag');                 // <title> gerenciado pelo WP
    add_theme_support('html5', ['search-form', 'comment-form', 'gallery', 'caption']);
    add_theme_support('editor-styles');             // Estilos do tema no editor Gutenberg
    add_theme_support('align-wide');                // Suporte a blocos "wide" e "full"
    add_theme_support('responsive-embeds');         // Embeds responsivos (YouTube, etc.)
    add_theme_support('wp-block-styles');           // Estilos nativos dos blocos Gutenberg
    add_theme_support('automatic-feed-links');      // Links de feed no <head>
    add_theme_support('elementor');                 // Compatibilidade com Elementor

    register_nav_menus([
        'primary'         => __('Menu Principal', 'btpconecta'),
        'home-shortcuts'  => __('Atalhos da Home (grid 3×2)', 'btpconecta'),
    ]);
}
add_action('after_setup_theme', 'btpconecta_setup');

/**
 * Define a largura máxima do conteúdo (usada pelo Gutenberg para calcular blocos).
 * Valor em pixels, sem unidade.
 */
function btpconecta_content_width(): void {
    $GLOBALS['content_width'] = 780;
}
add_action('after_setup_theme', 'btpconecta_content_width', 0);

// ─── Enqueue de estilos e scripts ─────────────────────────────────────────────

/**
 * Registra e carrega CSS e JS do tema.
 *
 * Ordem de carregamento:
 *   1. Google Fonts (Roboto + PT Sans) via CDN
 *   2. assets/css/style.css — CSS principal do tema
 *   3. jQuery 3.7.1 via CDN (substitui a versão bundled do WP)
 *   4. assets/js/main.js — interações do tema (menu, busca, mobile)
 */
function btpconecta_scripts(): void {
    // Fontes externas — null no version para evitar query string
    wp_enqueue_style(
        'btpconecta-google-fonts',
        'https://fonts.googleapis.com/css2?family=Roboto:wght@400;700&family=PT+Sans:wght@400;700&display=swap',
        [],
        null
    );

    // CSS principal — depende das fontes para evitar FOUT
    wp_enqueue_style(
        'btpconecta-style',
        get_template_directory_uri() . '/assets/css/style.css',
        ['btpconecta-google-fonts'],
        '1.1.0'
    );

    // Substitui o jQuery bundled do WordPress por versão mais recente via CDN
    wp_deregister_script('jquery');
    wp_register_script('jquery', 'https://cdnjs.cloudflare.com/ajax/libs/jquery/3.7.1/jquery.min.js', [], '3.7.1', true);
    wp_enqueue_script('jquery');

    // JS principal — carregado no footer (true) para garantir DOM pronto
    wp_enqueue_script(
        'btpconecta-main',
        get_template_directory_uri() . '/assets/js/main.js',
        ['jquery'],
        '1.1.0',
        true
    );

    // Passa dados para o JS (nonce + ajaxurl) apenas em posts individuais
    if (is_single()) {
        wp_localize_script('btpconecta-main', 'btpShare', [
            'ajaxurl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('btp_share_email'),
        ]);
    }

    // Passa dados de horários de ônibus para o widget do header
    $horarios_raw = get_option('btp_horarios_data', '');
    if ($horarios_raw) {
        wp_localize_script('btpconecta-main', 'btpHorarios', [
            'grupos' => json_decode($horarios_raw, true),
        ]);
    }
}
add_action('wp_enqueue_scripts', 'btpconecta_scripts');

// ─── Segurança ────────────────────────────────────────────────────────────────

// Remove a meta tag WordPress Generator que expõe a versão do WP (inclui feeds RSS)
remove_action('wp_head', 'wp_generator');
remove_filter('the_generator', 'wp_generator');

// Oculta a barra de administração para todos os usuários no frontend
add_filter('show_admin_bar', '__return_false');

/**
 * Remove o parâmetro ?ver= das URLs de CSS e JS para dificultar fingerprinting.
 *
 * @param string $src URL do asset
 * @return string URL sem parâmetro de versão
 */
function btpconecta_remove_ver(string $src): string {
    return strpos($src, 'ver=') !== false ? remove_query_arg('ver', $src) : $src;
}
add_filter('style_loader_src',  'btpconecta_remove_ver', 9999);
add_filter('script_loader_src', 'btpconecta_remove_ver', 9999);

// ─── Autenticação customizada ─────────────────────────────────────────────────

/**
 * Verifica se o usuário está autenticado via sistema customizado BTP.
 *
 * Fluxo de validação:
 *   1. Verifica existência dos cookies btpUserName e btpUserToken
 *   2. Conecta ao banco via MySQLi (credenciais do wp-config.php)
 *   3. Consulta a tabela btpconecta_tokens com prepared statement
 *   4. Token deve estar ativo (ativo=1) e dentro do prazo (expires_at > UTC_TIMESTAMP)
 *
 * @return bool true se autenticado, false caso contrário
 */
function btpconecta_logged(): bool {
    // Cookies obrigatórios ausentes → não autenticado
    if (!isset($_COOKIE['btpUserName'], $_COOKIE['btpUserToken'])) {
        return false;
    }

    // Sanitiza os valores dos cookies antes de usar no banco
    $userName  = htmlspecialchars($_COOKIE['btpUserName'],  ENT_COMPAT, 'UTF-8', true);
    $userToken = htmlspecialchars($_COOKIE['btpUserToken'], ENT_COMPAT, 'UTF-8', true);

    // Constantes do banco definidas em wp-config.php
    if (!defined('DB_HOST') || !defined('DB_USER') || !defined('DB_PASSWORD') || !defined('DB_NAME')) {
        return false;
    }

    $mysqli = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
    if ($mysqli->connect_errno) {
        error_log('[BTPAuth] Erro na conexão: ' . $mysqli->connect_error);
        return false;
    }

    // Prepared statement evita SQL injection
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

    // Exatamente 1 linha = token válido e não expirado
    $authenticated = ($result->num_rows === 1);

    $stmt->close();
    $mysqli->close();

    return $authenticated;
}

/**
 * Renderiza o formulário de login customizado via output buffering.
 * Inclui templates/login-form.php e retorna o HTML como string.
 *
 * @return string HTML completo da página de login
 */
function btpconecta_login_form_render(): string {
    ob_start();
    include get_template_directory() . '/templates/login-form.php';
    return ob_get_clean();
}

// ─── Excerpt ──────────────────────────────────────────────────────────────────

/**
 * Define o tamanho do excerpt automático em palavras.
 * Usado nos cards da listagem de posts (archive.php).
 */
function btpconecta_excerpt_length(): int {
    return 20; // palavras
}
add_filter('excerpt_length', 'btpconecta_excerpt_length', 999);

/**
 * Substitui o sufixo padrão "[...]" por reticências unicode.
 */
function btpconecta_excerpt_more(): string {
    return '…';
}
add_filter('excerpt_more', 'btpconecta_excerpt_more');

// ─── Paginação ────────────────────────────────────────────────────────────────

/**
 * Renderiza a paginação numérica nas páginas de listagem.
 * Chamada diretamente nos templates archive.php, index.php, search.php.
 */
function btpconecta_pagination(): void {
    the_posts_pagination([
        'mid_size'           => 2,           // páginas ao redor da atual
        'prev_text'          => '&laquo; Anterior',
        'next_text'          => 'Próxima &raquo;',
        'before_page_number' => '',
    ]);
}

// ─── Categorias públicas permitidas ──────────────────────────────────────────

/**
 * Lista de slugs de categorias que possuem página de arquivo pública.
 * Qualquer categoria fora desta lista retorna 404 ao acessar /category/{slug}/.
 *
 * Para adicionar uma categoria, inclua seu slug neste array.
 */
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

/**
 * Remove o prefixo /category/ das URLs de categoria.
 * Ex: /category/noticias/ → /noticias/
 */
add_filter('category_link', function (string $link): string {
    return str_replace('/category/', '/', $link);
}, 10, 1);

/**
 * Registra rewrite rules para as categorias permitidas sem o prefixo /category/.
 * Deve ser seguido de flush_rewrite_rules() (feito automaticamente ao salvar
 * permalinks em Settings > Permalinks).
 */
add_action('init', function (): void {
    foreach (btpconecta_allowed_categories() as $slug) {
        add_rewrite_rule(
            '^' . preg_quote($slug, '#') . '/?$',
            'index.php?category_name=' . $slug,
            'bottom'
        );
    }
});

/**
 * Bloqueia o acesso a arquivos de categorias não permitidas.
 * Redireciona para 404 se o slug não estiver na lista de permitidos.
 * Executado no hook 'template_redirect', antes de qualquer output.
 */
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

// ─── Busca em todos os tipos de conteúdo ─────────────────────────────────────
add_filter('pre_get_posts', function (WP_Query $query): void {
    if (!$query->is_main_query() || !$query->is_search() || is_admin()) {
        return;
    }
    $query->set('post_type', ['post', 'page']);
});

// ─── Cor da categoria (para badges e marcadores) ─────────────────────────────

/**
 * Retorna a cor hexadecimal associada a uma categoria pelo seu slug.
 *
 * Usa slug em vez de term_id para funcionar em qualquer ambiente
 * (produção, QAS, local) sem depender de IDs gerados pelo banco.
 * Mesma paleta usada nos marcadores do menu lateral (nth-child no CSS).
 *
 * @param string $slug  Slug da categoria WordPress
 * @return string       Cor em formato hex, ex: '#214549'
 */
function btpconecta_first_content_image(int $post_id): string {
    $content = get_post_field('post_content', $post_id);

    // Padrões de URL que identificam imagens placeholder — nunca exibir como destaque
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

// ─── Compartilhar por e-mail (AJAX) ──────────────────────────────────────────
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

// Carrega single-newsletter.php para posts da categoria newsletter
add_filter('single_template', function (string $template): string {
    if (is_single() && in_category('newsletter')) {
        $custom = get_template_directory() . '/single-newsletter.php';
        if (file_exists($custom)) {
            return $custom;
        }
    }
    return $template;
});

// ─── Contagem de visualizações ────────────────────────────────────────────────

/**
 * Retorna o número de visualizações armazenadas no post_meta (_btp_views).
 */
function btpconecta_get_post_views(int $post_id): int {
    return (int) get_post_meta($post_id, '_btp_views', true);
}

/**
 * Incrementa o contador de visualizações a cada visita a um post individual.
 * Executado em template_redirect para garantir que o post está disponível.
 */
add_action('template_redirect', function (): void {
    if (!is_single()) {
        return;
    }
    $post_id = get_queried_object_id();
    update_post_meta($post_id, '_btp_views', btpconecta_get_post_views($post_id) + 1);
});

// ─── Força autenticação por cookie no endpoint REST do btp-upload-path ───────
// WordPress REST API (rest_cookie_check_errors, priority 10) chama
// wp_set_current_user(0) quando não há nonce, derrubando is_user_logged_in().
// Este filtro roda em priority 20 (após o reset) e re-autentica via cookie WP
// apenas para a rota btp/v1/download, restaurando o bypass do plugin.
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

// ─── Módulos de funcionalidade ────────────────────────────────────────────────

require_once get_template_directory() . '/inc/parse-horario.php';
require_once get_template_directory() . '/inc/admin-horario.php';

// ─── Meta box: categorias para o template "Listagem de Categoria" ─────────────

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
    return $colors[$slug] ?? '#E94E1B'; // fallback: laranja/vermelho
}
