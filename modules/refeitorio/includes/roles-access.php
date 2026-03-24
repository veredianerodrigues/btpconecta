<?php
if (!defined('ABSPATH')) exit;

define('RD_ROLE_RESTAURANTE', 'restaurante');
define('RD_ROLE_EDITOR', 'editor_cardapio');
define('RD_MENU_SLUG', 'refeitorio-digital');
define('RD_PAGE_PAINEL', 'refeitorio-digital-painel');
define('RD_PAGE_CONFIG', 'refeitorio-digital');

function rd_criar_roles_customizadas() {
    if (!get_role(RD_ROLE_RESTAURANTE)) {
        add_role(
            RD_ROLE_RESTAURANTE,
            'Restaurante',
            array(
                'read' => true,
                'rd_gerenciar_retiradas' => true,
            )
        );
    }

    if (!get_role(RD_ROLE_EDITOR)) {
        add_role(
            RD_ROLE_EDITOR,
            'Editor Cardápio',
            array(
                'read' => true,
                'upload_files' => true,
                'rd_gerenciar_cardapio' => true,
            )
        );
    }
}

add_action('init', function() {
    if (get_transient('rd_roles_created')) return;
    rd_criar_roles_customizadas();
    set_transient('rd_roles_created', true, DAY_IN_SECONDS);
});

function rd_is_restricted_user() {
    if (!is_user_logged_in()) return false;
    $user = wp_get_current_user();
    $restricted_roles = array(RD_ROLE_RESTAURANTE, RD_ROLE_EDITOR);
    return (bool) array_intersect($restricted_roles, (array) $user->roles);
}

function rd_get_user_allowed_page() {
    $user = wp_get_current_user();
    $roles = (array) $user->roles;
    
    if (in_array(RD_ROLE_RESTAURANTE, $roles, true)) {
        return RD_PAGE_PAINEL;
    }
    
    if (in_array(RD_ROLE_EDITOR, $roles, true)) {
        return RD_PAGE_CONFIG;
    }
    
    return false;
}

function rd_limitar_menus_admin() {
    if (!is_admin() || !rd_is_restricted_user()) return;

    global $menu, $submenu;
    $pagina_permitida = rd_get_user_allowed_page();
    $menus_permitidos = array(RD_MENU_SLUG, 'profile.php');
    
    foreach ($menu as $index => $item) {
        $slug = isset($item[2]) ? $item[2] : '';
        if (!in_array($slug, $menus_permitidos, true)) {
            remove_menu_page($slug);
        }
    }
    
    if (isset($submenu[RD_MENU_SLUG])) {
        foreach ($submenu[RD_MENU_SLUG] as $index => $sub) {
            $sub_slug = isset($sub[2]) ? $sub[2] : '';
            if ($sub_slug !== $pagina_permitida) {
                remove_submenu_page(RD_MENU_SLUG, $sub_slug);
            }
        }
    }
    
    remove_submenu_page('profile.php', 'profile.php');
}

add_action('admin_menu', 'rd_limitar_menus_admin', 9999);

function rd_restringir_acesso_admin() {
    if (!is_admin() || !rd_is_restricted_user()) return;
    if (defined('DOING_AJAX') && DOING_AJAX) return;

    $user = wp_get_current_user();
    $roles = (array) $user->roles;

    // Editor Cardápio pode acessar páginas de upload e options.php para salvar
    if (in_array(RD_ROLE_EDITOR, $roles, true)) {
        global $pagenow;
        $paginas_permitidas = array('upload.php', 'media-new.php', 'async-upload.php', 'options.php', 'admin-ajax.php', 'admin-post.php', 'profile.php');
        if (in_array($pagenow, $paginas_permitidas, true)) return;

        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if ($pagenow === 'admin.php' && $page === RD_PAGE_CONFIG) return;
    }

    // Restaurante pode acessar seu painel e páginas do sistema
    if (in_array(RD_ROLE_RESTAURANTE, $roles, true)) {
        global $pagenow;
        $paginas_permitidas = array('admin-ajax.php', 'admin-post.php', 'profile.php');
        if (in_array($pagenow, $paginas_permitidas, true)) return;

        $page = isset($_GET['page']) ? sanitize_text_field($_GET['page']) : '';
        if ($pagenow === 'admin.php' && $page === RD_PAGE_PAINEL) return;
    }

    // Redireciona para a página permitida
    $pagina_permitida = rd_get_user_allowed_page();
    if ($pagina_permitida) {
        wp_safe_redirect(admin_url('admin.php?page=' . $pagina_permitida));
        exit;
    }
}

add_action('admin_init', 'rd_restringir_acesso_admin', 1);

function rd_customizar_admin_bar() {
    if (!rd_is_restricted_user()) return;
    
    global $wp_admin_bar;
    
    $remover = array(
        'wp-logo', 'about', 'wporg', 'documentation', 'support-forums',
        'feedback', 'site-name', 'view-site', 'updates', 'comments', 'new-content',
    );
    
    foreach ($remover as $item) {
        $wp_admin_bar->remove_node($item);
    }
    
    $pagina = rd_get_user_allowed_page();
    $wp_admin_bar->add_node(array(
        'id'    => 'rd-minha-pagina',
        'title' => 'Refeitório Digital',
        'href'  => admin_url('admin.php?page=' . $pagina),
    ));
}

add_action('wp_before_admin_bar_render', 'rd_customizar_admin_bar', 999);

function rd_esconder_avisos_admin() {
    if (!rd_is_restricted_user()) return;
    
    remove_action('admin_notices', 'update_nag', 3);
    remove_action('admin_notices', 'maintenance_nag', 10);
    
    echo '<style>
        .update-nag, .updated, .notice:not(.rd-notice),
        #wp-admin-bar-wp-logo, #wp-admin-bar-site-name,
        #wp-admin-bar-comments, #wp-admin-bar-new-content,
        #adminmenu .wp-menu-separator, #collapse-menu,
        #footer-left, #footer-upgrade { display: none !important; }
        #wpadminbar { background: #1a5c3a !important; }
        #adminmenu, #adminmenu .wp-submenu,
        #adminmenuback, #adminmenuwrap { background: #1e1e1e !important; }
        #adminmenu .wp-has-current-submenu .wp-submenu,
        #adminmenu a.wp-has-current-submenu:focus+.wp-submenu { background: #2c2c2c !important; }
    </style>';
}

add_action('admin_head', 'rd_esconder_avisos_admin');

function rd_redirect_apos_login($redirect_to, $request, $user) {
    if (!isset($user->roles) || !is_array($user->roles)) {
        return $redirect_to;
    }
    
    if (in_array(RD_ROLE_RESTAURANTE, $user->roles, true)) {
        return admin_url('admin.php?page=' . RD_PAGE_PAINEL);
    }
    
    if (in_array(RD_ROLE_EDITOR, $user->roles, true)) {
        return admin_url('admin.php?page=' . RD_PAGE_CONFIG);
    }
    
    return $redirect_to;
}

add_filter('login_redirect', 'rd_redirect_apos_login', 10, 3);

function rd_customizar_login() {
    echo '<style>
        body.login { background: #f0f0f0; }
        .login h1 a {
            background-image: url("' . RD_URL . 'assets/img/logo-btp.png") !important;
            background-size: contain !important;
            width: 200px !important;
            height: 80px !important;
        }
        .login #backtoblog, .login #nav { display: none; }
        .wp-core-ui .button-primary {
            background: #1a5c3a !important;
            border-color: #1a5c3a !important;
        }
    </style>';
}

add_action('login_head', 'rd_customizar_login');

add_filter('login_headerurl', function() {
    return home_url();
});

function rd_user_can_manage_retiradas() {
    return current_user_can('administrator') || current_user_can('rd_gerenciar_retiradas');
}

function rd_user_can_manage_cardapio() {
    return current_user_can('administrator') || current_user_can('rd_gerenciar_cardapio');
}

function rd_remover_roles_customizadas() {
    remove_role(RD_ROLE_RESTAURANTE);
    remove_role(RD_ROLE_EDITOR);
    delete_transient('rd_roles_created');
}