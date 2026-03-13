<?php
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

    // Locais de menu registrados
    // 'primary'           → menu lateral principal (sidebar desktop)
    // 'left-mobile-menu'  → reservado para uso futuro no mobile
    // 'right-mobile-menu' → reservado para uso futuro no mobile
    register_nav_menus([
        'primary'           => __('Menu Principal', 'btpconecta'),
        'left-mobile-menu'  => __('Left Mobile Menu', 'btpconecta'),
        'right-mobile-menu' => __('Right Mobile Menu', 'btpconecta'),
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
        '1.0.0'
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
        '1.0.0',
        true // footer = true
    );
}
add_action('wp_enqueue_scripts', 'btpconecta_scripts');

// ─── Segurança ────────────────────────────────────────────────────────────────

// Remove a meta tag <?php esc_html_e('WordPress Generator', 'btpconecta'); ?> que expõe a versão do WP
remove_action('wp_head', 'wp_generator');
add_filter('the_generator', '__return_empty_string');

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

// ─── Widgets ──────────────────────────────────────────────────────────────────

/**
 * Registra as áreas de widget do tema.
 *
 * 'sidebar-menu' → área exibida abaixo do menu lateral (ex: widget de calendário)
 */
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

// ─── Cor da categoria (para badges e marcadores) ─────────────────────────────

/**
 * Retorna a cor hexadecimal associada a uma categoria pelo seu term_id.
 *
 * Mapeamento baseado nos IDs de categoria do ambiente de produção.
 * Caso o ID não esteja mapeado, retorna a cor padrão (laranja/vermelho).
 *
 * @param int $cat_id  term_id da categoria WordPress
 * @return string      Cor em formato hex, ex: '#214549'
 */
function btpconecta_category_color(int $cat_id = 0): string {
    // IDs de categoria → cor (mesma paleta dos marcadores do menu lateral)
    $colors = [
        90  => '#214549', // Institucional
        87  => '#3AAA35', // RH para você
        89  => '#E2AB3B', // Performance e Processos
        220 => '#1C6C7F', // Notícias
    ];
    return $colors[$cat_id] ?? '#E94E1B'; // fallback: Central de Serviços
}
