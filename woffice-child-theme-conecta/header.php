<?php
/**
 * The Header of WOFFICE
 */

// echo '<pre>';
//     print_r($post);
// echo '</pre>';

// $post->post_content = '';
error_reporting(E_ERROR | E_PARSE);
include('/wp-content/themes/woffice-child-theme/login/php/config.php');

if(!logged())
    {
        $content = login_form_render();
        print $content;
        die();
    }

?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
	<head>
		<meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta http-equiv="X-UA-Compatible" content="IE=edge" />
		
		<meta name="viewport" content="width=device-width, initial-scale=1.0">
		<meta name="mobile-web-app-capable" content="yes">
        <meta name="mobile-web-app-capable" content="yes">

		<?php $hide_seo = woffice_get_settings_option('hide_seo'); 
		echo ($hide_seo == 'yep') ? '<meta name="robots" content="noindex">' : ''; ?>

        <link rel="manifest" href="/../manifest.json?__v201910171615">
        
        <meta name="mobile-web-app-capable" content="yes">
        <meta name="apple-mobile-web-app-status-bar-style" content="yes">
        <meta name="apple-mobile-web-app-title" content="BTPConecta">

        <link rel="apple-touch-icon" href="wp-content/themes/woffice-child-theme/images/icon/touch-icon-iphone.png?__v201910171615">
        <link rel="apple-touch-icon" sizes="152x152" href="wp-content/themes/woffice-child-theme/images/icon/touch-icon-ipad.png?__v201910171615">
        <link rel="apple-touch-icon" sizes="180x180" href="wp-content/themes/woffice-child-theme/images/icon/touch-icon-iphone-retina.png?__v201910171615">
        <link rel="apple-touch-icon" sizes="167x167" href="wp-content/themes/woffice-child-theme/images/icon/touch-icon-ipad-retina.png?__v201910171615">

        <link rel="apple-touch-icon-precomposed" sizes="152x152" href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-icon-152x152-precomposed.png?__v201910171615">
        <link rel="apple-touch-icon-precomposed" sizes="144x144" href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-icon-144x144-precomposed.png?__v201910171615">
        <link rel="apple-touch-icon-precomposed" sizes="120x120" href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-icon-120x120-precomposed.png?__v201910171615">
        <link rel="apple-touch-icon-precomposed" sizes="114x114" href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-icon-114x114-precomposed.png?__v201910171615">
        <link rel="apple-touch-icon-precomposed" sizes="76x76" href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-icon-76x76-precomposed.png?__v201910171615">
        <link rel="apple-touch-icon-precomposed" sizes="72x72" href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-icon-72x72-precomposed.png?__v201910171615">
        <link rel="apple-touch-icon-precomposed" href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-icon-precomposed.png?__v201910171615">

        <link href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-startup-image-320x460.png?__v201910171615" media="(device-width: 320px)" rel="apple-touch-startup-image">
        <link href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-startup-image-640x920.png?__v201910171615" media="(device-width: 320px) and (device-height: 460px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
        <link href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-startup-image-640x1096.png?__v201910171615" media="(device-width: 320px) and (device-height: 568px) and (-webkit-device-pixel-ratio: 2)" rel="apple-touch-startup-image">
        <link href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-startup-image-768x1004.png?__v201910171615" media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:portrait)" rel="apple-touch-startup-image" />
        <link href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-startup-image-1024x748.png?__v201910171615" media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:landscape)" rel="apple-touch-startup-image" />
        <link href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-startup-image-1536x2008.png?__v201910171615" media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:portrait) and (-webkit-min-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />
        <link href="wp-content/themes/woffice-child-theme/images/icon/apple-touch-startup-image-2048x1496.png?__v201910171615" media="screen and (min-device-width: 481px) and (max-device-width: 1024px) and (orientation:landscape) and (-webkit-min-device-pixel-ratio: 2)" rel="apple-touch-startup-image" />

		<link rel="profile" href="http://gmpg.org/xfn/11">
		<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>">

		<?php 
		woffice_favicons();
		?>
		<!-- HTML5 shim and Respond.js IE8 support of HTML5 elements and media queries -->
		<!--[if lt IE 9]>
		<script src="<?php echo get_template_directory_uri(); ?>/js/html5shiv.js"></script>
		<script src="<?php echo get_template_directory_uri(); ?>/js/respond.min.js"></script>
		<![endif]-->
		<?php wp_head(); ?>
        
        <script async src="https://www.googletagmanager.com/gtag/js?id=G-H6PE36L56F"></script>
        <script>
        window.dataLayer = window.dataLayer || [];
        function gtag(){dataLayer.push(arguments);}
        gtag('js', new Date());

        gtag('config', 'G-H6PE36L56F');
        </script>
	</head>
	
	<?php  
	$menu_layout = woffice_get_settings_option('menu_layout');
	$menu_class = ($menu_layout == "horizontal") ? "menu-is-horizontal" : "menu-is-vertical";


    $header_fixed = woffice_get_settings_option('header_fixed');
    $extra_navbar_class = ( $header_fixed == "yep" ) ? 'has_fixed_navbar' :'';

    $nav_opened_state = woffice_get_navigation_state();
    $sidebar_state = woffice_get_sidebar_state();
    $sidebar_show_class = ($sidebar_state != 'show') ? 'sidebar-hidden' : '';

	$design_update = woffice_get_settings_option('design_update');
	$design_update_class = ($design_update == "2.X") ? "woffice-2-5" : "";


    $design_update_class = apply_filters('woffice_design_version', $design_update_class);

	$is_blank_template = woffice_is_current_page_using_blank_template();
	$blank_template_class = ($is_blank_template) ? 'is-blank-template' : '';



    $hentry_class = apply_filters('woffice_hentry_class', 'hentry');
	?>


	<body <?php body_class($menu_class . ' ' . $sidebar_show_class . ' ' . $extra_navbar_class .' '.$design_update_class . ' ' . $blank_template_class); ?>>

		<?php
		if(!function_exists('fw_print')) :
			woffice_unyson_is_required();
		endif; ?>

		<?php 
        $navigation_hidden_class = woffice_get_navigation_class();
		?>
		<div class="border-top"></div>
		<div id="page-wrapper" <?php echo (!$nav_opened_state) ? 'class="menu-is-closed"':''; ?>>

			<?php

			if(!$is_blank_template): ?>

                <div id="navigation-wrapper">
                
                <nav id="navbar" class="<?php echo esc_attr($extra_navbar_class ); ?>">
                        <div id="nav-left">
                           
                            <?php 
                            
                            ?>
                            <a href="javascript:void(0)" id="nav-trigger" style="display: none"></a>
                            <?php 
                             
                            ?>
                            
							<div id="nav-logo">

								<?php

								$logo_link = apply_filters('woffice_logo_link_to', home_url( '/' ) );
								?>

								<a href="<?php echo esc_url( $logo_link ); ?>">
									<?php
									echo'<img src="'. get_stylesheet_directory_uri() .'/images/logo_btp.png" alt="BTP Conecta">';?>
								</a>
							</div>

                            <?php
                            
                            $header_user = woffice_get_settings_option('header_user');
                            ?>
                                    <div id="nav-user" class="clearfix <?php echo (function_exists('bp_is_active')) ? 'bp_is_active' : ''; ?>">
                                      
                                            <?php 

										   ?>                                            

										<?php
										if (is_user_logged_in()) : 
										$name_to_display = woffice_get_name_to_display();
										printf( _x( '<strong>%s</strong>', "Usuário", 'woffice' ), $name_to_display );
										 else : ?>
										<strong>
                                            <span>Bem-vindo(a)!</span>
                                            <a href="<?php echo get_stylesheet_directory_uri(); ?>/login/php/logout.php">(SAIR)</a>
                                        </strong>

											<?php  endif; ?> 
                                        
                                    </div>
                        </div>

                        
                        <div id="nav-buttons">

                            <?php 
                            if($sidebar_state == 'show' || $sidebar_state == 'hide') :  ?>
                                
                                <a href="javascript:void(0)" id="nav-sidebar-trigger"><i class="fa fa-long-arrow-right"></i></a>
                            <?php endif; ?>

                            <?php 
                            $header_search = woffice_get_settings_option('header_search');
                           
                            $header_search = apply_filters( 'woffice_header_search_enabled', $header_search);
                             ?>
                                
                                <a href="javascript:void(0)" id="search-trigger"><i class="fa fa-search"></i></a>
                            <?php ?>

                            <?php 

                            $minicart_header_enabled = apply_filters('woffice_show_minicart_in_header', true);

                            if (function_exists('is_woocommerce') && $minicart_header_enabled) : ?>
                                <?php 
                                if ( WC()->cart->get_cart_contents_count() > 0 ) :
                                    $cart_url_topbar = "javascript:void(0)";
                                    $cart_classes = 'active cart-content';
                                else :
                                    $cart_url_topbar = get_permalink( wc_get_page_id( 'shop' ) );
                                    $cart_classes = "";
                                endif; ?>
                                <a href="<?php echo $cart_url_topbar; ?>" id="nav-cart-trigger" title="<?php _e( 'View your shopping cart', 'woffice' ); ?>" class="<?php echo $cart_classes; ?>">
                                    <i class="fa fa-shopping-cart"></i>
                                    <?php echo (WC()->cart->get_cart_contents_count() > 0) ? WC()->cart->get_cart_subtotal() : ''; ?>
                                </a>
                            <?php endif; ?>

                            <?php 
                            if ( woffice_bp_is_active( 'notifications' ) && is_user_logged_in() ) : ?>
                                <a href="javascript:void(0)" id="nav-notification-trigger" title="<?php _e( 'View your notifications', 'woffice' ); ?>" class="<?php echo (bp_notifications_get_unread_notification_count( bp_loggedin_user_id() ) >= 1) ? "active" : "" ?>">
                                    <i class="fa fa-bell-o"></i>
                                </a>
                            <?php endif; ?>

                        </div>

                    </nav>
                
                


                <nav id="navigation" class="col-md-12 col-sm-12 <?php echo esc_attr($navigation_hidden_class); ?> mobile-hidden">
                    <?php

                    wp_nav_menu( array( 
                        'theme_location' => 'custom-menu' ) ); 

                    ?>
                
                <div class="clearfix"></div>
                
                <div id="secondary" class="sidebar-container menu-sidebar mobile-hidden" role="complementary" >
                <a href="<?php echo get_home_url() . '/calendario'; ?>" class="btn-calendario-hidden">Calendário</a>
					<div class="widget-area">
						<?php dynamic_sidebar( 'sidebar-menu' ); ?>
					</div>
				</div>
              
                </nav>

               
                </div>



                <?php 
                $header_user = woffice_get_settings_option('header_user');
                $header_user_class = ($header_user == "yep") ? 'has-user': 'user-hidden';
                ?>
                <header id="main-header" class="<?php echo esc_attr($navigation_hidden_class) . ' ' . esc_attr($header_user_class ).' '. esc_attr($sidebar_show_class); ?>">

 

                    <?php 
                    $header_user = woffice_get_settings_option('header_user');
                    if ($header_user == "yep" && function_exists('bp_is_active')) :

					  woffice_user_sidebar_min();
					
                    endif; ?>

                    <?php 
                    if (function_exists('is_woocommerce')) { Woffice_WooCommerce::print_mini_cart(); } ?>

                    <?php 
                    if ( woffice_bp_is_active( 'notifications' ) && is_user_logged_in() ) { woffice_notifications_menu(); } ?>

                    <?php 
                    $header_search = woffice_get_settings_option('header_search');
                      ?>
                        
                        <div id="main-search">
                            <div class="container">
                                <?php 
                                get_search_form(); ?>
                            
                            
                            <a href="javascript:void(0)" id="close-search-trigger"><i class="fa fa-close"></i></a>
                            </div>
                        </div>
                    <?php ?>

                    <?php

                    woffice_alerts_render(); ?>

                </header>
                


                
                <?php
                
                if ($sidebar_state == "show"){
                    $class = 'with-sidebar';
                } elseif ($sidebar_state == "hide") {
                
                    if( !isset($_COOKIE['Woffice_sidebar_position']) || ! apply_filters( 'woffice_cookie_sidebar_enabled', false ) ) {
                        $class = 'sidebar-hidden';
                    }
                    else {
                        $class = '';
                    }
                } else {
                    $class = 'full-width';
                }
                ?>

                
                <section id="main-content" class="<?php echo esc_attr($class) .' '.esc_attr($navigation_hidden_class) .' '. esc_attr($hentry_class); ?>">

                    <?php 
                    if($sidebar_state == 'show' || $sidebar_state == 'hide') :
                        get_sidebar();
                    endif; ?>

                    


    <?php else:

		echo '<section id="main-content" class="full-width navigation-hidden '. esc_attr($hentry_class) .'">';

	endif;