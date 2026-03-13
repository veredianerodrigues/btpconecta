<?php
/**
 * BTP Conecta — header.php
 * Inclui verificação de autenticação customizada antes de qualquer output HTML.
 */

// Carrega as constantes do WordPress se ainda não carregadas (para chamadas diretas a login.php)
if (!defined('ABSPATH')) {
    require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
}

// Verifica autenticação — mostra tela de login se não autenticado
if (!btpconecta_logged()) {
    echo btpconecta_login_form_render();
    exit;
}

// Nome do usuário logado via cookie
$btpUserName = '';
if (isset($_COOKIE['btpUserName'])) {
    $btpUserName = htmlspecialchars($_COOKIE['btpUserName'], ENT_COMPAT, 'UTF-8', true);
    // Exibe apenas a parte antes do @
    $btpUserName = explode('@', $btpUserName)[0];
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

<div id="page-wrapper">

    <!-- ── NAVIGATION WRAPPER (sidebar + navbar) ─────────────── -->
    <div id="navigation-wrapper">

        <!-- Navbar superior dentro da sidebar -->
        <nav id="navbar">
            <div id="nav-left">
                <div id="nav-logo">
                    <a href="<?php echo esc_url(home_url('/')); ?>">
                        <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/logo_btp.png" alt="BTP Conecta">
                    </a>
                </div>
                <div id="nav-user">
                    <strong>
                        <span>Bem-vindo(a)<?php echo $btpUserName ? ', ' . esc_html($btpUserName) : ''; ?>!</span>
                        <a href="<?php echo esc_url($logout_url); ?>" id="nav-logout">(SAIR)</a>
                    </strong>
                </div>
            </div>
            <div id="nav-buttons">
                <a href="javascript:void(0)" id="nav-sidebar-trigger" title="Expandir/Recolher menu">
                    <span class="hamburger-icon">&#9776;</span>
                </a>
                <a href="javascript:void(0)" id="search-trigger" title="Buscar">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                </a>
            </div>
        </nav>

        <!-- Menu lateral / navigation -->
        <nav id="navigation">
            <?php
            wp_nav_menu([
                'theme_location' => 'primary',
                'menu_class'     => 'nav-menu',
                'container'      => false,
                'fallback_cb'    => false,
            ]);
            ?>

            <!-- Sidebar widgets (ex: calendário) -->
            <div id="secondary" class="sidebar-container" role="complementary">
                <a href="<?php echo esc_url(home_url('/calendario')); ?>" class="btn-calendario-hidden">Calendário</a>
                <div class="widget-area">
                    <?php dynamic_sidebar('sidebar-menu'); ?>
                </div>
            </div>
        </nav>

    </div><!-- /#navigation-wrapper -->

    <!-- ── HEADER / BARRA DE BUSCA ───────────────────────────── -->
    <header id="main-header">
        <div id="main-search">
            <div class="search-container">
                <?php get_search_form(); ?>
                <a href="javascript:void(0)" id="close-search-trigger" title="Fechar busca">&times;</a>
            </div>
        </div>
    </header>

    <!-- ── CONTEÚDO PRINCIPAL ─────────────────────────────────── -->
    <section id="main-content">
