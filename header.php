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

<!-- ── TOPBAR MOBILE (visível apenas em telas pequenas) ──── -->
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

<!-- Overlay escuro ao abrir sidebar no mobile -->
<div id="sidebar-overlay"></div>

<div id="page-wrapper">

    <!-- ── NAVIGATION WRAPPER (sidebar + navbar) ─────────────── -->
    <div id="navigation-wrapper">

        <!-- Navbar superior dentro da sidebar -->
        <nav id="navbar">
            <!-- Logo — não alterar posição/tamanho -->
            <div id="nav-logo">
                <a href="<?php echo esc_url(home_url('/')); ?>">
                    <img src="<?php echo esc_url(get_template_directory_uri()); ?>/images/logo_btp.png" alt="BTP Conecta">
                </a>
            </div>

            <!-- Barra do usuário: nome à esquerda, ações à direita — tudo em uma linha -->
            <div id="nav-user">
                <span id="nav-username">Bem-vindo(a)<?php echo $btpUserName ? ', ' . esc_html($btpUserName) : ''; ?>!</span>
                <div id="nav-actions">
                    <a href="javascript:void(0)" id="search-trigger" title="Buscar">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                    </a>
                    <a href="<?php echo esc_url($logout_url); ?>" id="nav-logout" title="Sair">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                        Logout
                    </a>
                </div>
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
