<?php
/**
 * BTP Conecta — header.php
 * Inclui verificação de autenticação customizada antes de qualquer output HTML.
 */


if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
}

$is_elementor = isset($_GET['elementor-preview']) || isset($_GET['elementor_library']);
$is_wp_admin  = is_user_logged_in() && current_user_can('edit_posts');

if (!$is_elementor && !$is_wp_admin && !btpconecta_logged()) {
    echo btpconecta_login_form_render();
    exit;
}

$btpMatricula = '';
if (isset($_COOKIE['btpUserName'])) {
    $raw          = htmlspecialchars($_COOKIE['btpUserName'], ENT_COMPAT, 'UTF-8', true);
    $btpMatricula = explode('@', $raw)[0];
}

$logout_url = get_template_directory_uri() . '/login/php/logout.php';
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo('charset'); ?>">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="yes">
    <meta name="apple-mobile-web-app-title" content="BTPConecta">
    <link rel="manifest" href="/manifest.json">
    <?php wp_head(); ?>
    <script async src="https://www.googletagmanager.com/gtag/js?id=G-H6PE36L56F"></script>
    <script>
    window.dataLayer = window.dataLayer || [];
    function gtag(){dataLayer.push(arguments);}
    gtag('js', new Date());
    gtag('config', 'G-H6PE36L56F');
    </script>
</head>
<body <?php body_class('btpconecta-theme'); ?>>

<div class="border-top-btp"></div>

<div id="mobile-topbar">
    <button id="mobile-menu-trigger" aria-label="Abrir menu" aria-expanded="false">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5">
            <line x1="3" y1="6"  x2="21" y2="6"/>
            <line x1="3" y1="12" x2="21" y2="12"/>
            <line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
    </button>
    <a href="<?php echo esc_url(home_url('/')); ?>" id="mobile-logo">
        <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/logo_btp.png" alt="BTP Conecta">
    </a>
    <a href="javascript:void(0)" id="mobile-search-trigger" aria-label="Buscar">
        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
    </a>
</div>

<div id="sidebar-overlay"></div>

<div id="page-wrapper">

    <div id="navigation-wrapper">

        <div id="nav-user">
            <div id="nav-user-info">
                <span id="nav-username">Bem-vindo(a)!</span>
            </div>
            <div id="nav-user-icons">
                <a href="javascript:void(0)" id="search-trigger" title="Buscar" class="nav-icon-btn">⌕</a>
                <a href="<?php echo esc_url($logout_url); ?>" id="nav-logout" title="Sair" class="nav-icon-btn nav-icon-logout">
                    <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                        <polyline points="16 17 21 12 16 7"/>
                        <line x1="21" y1="12" x2="9" y2="12"/>
                    </svg>
                    <span>Sair</span>
                </a>
            </div>
        </div>

        <div id="nav-logo">
            <a href="<?php echo esc_url(home_url('/')); ?>">
                <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/logo_btp.png" alt="BTP Conecta">
            </a>
        </div>

        <nav id="navigation">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'menu_class'     => 'nav-menu',
                'container'      => false,
                'fallback_cb'    => false,
            ]);
            ?>
        </nav>

        <div id="btp-proximo-onibus" style="display:none">
            <div class="btp-onibus-label">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                Próxima saída Ônibus BTP:
            </div>
            <div id="btp-onibus-content">—</div>
        </div>

        <div id="nav-footer">BTP Conecta &copy; 2026</div>

    </div>

    <section id="main-content">

    <header id="main-header">
        <div id="main-search">
            <div class="search-container">
                <?php get_search_form(); ?>
                <a href="javascript:void(0)" id="close-search-trigger" title="Fechar busca">&times;</a>
            </div>
        </div>
    </header>

