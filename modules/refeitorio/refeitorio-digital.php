<?php
/**
 * Plugin Name: Refeitório Digital
 * Description: Fluxo digital para solicitações do refeitório: agendamento, controle de retirada e relatórios.
 * Version:     2.0.1
 * Author:      Verediane Rodrigues
 * License:     GPLv2 or later
 * Text Domain: refeitorio-digital
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'RD_FILE', __FILE__ );
define( 'RD_DIR', plugin_dir_path( __FILE__ ) );
define( 'RD_URL', plugin_dir_url( __FILE__ ) );
define( 'RD_VER', '2.1.0' );
define( 'RD_DB_TABLE', 'rm_refeicoes' );

if ( ! defined( 'RD_TOKENS_TABLE' ) ) {
    define( 'RD_TOKENS_TABLE', 'btpconecta_tokens' );
}
if ( ! defined( 'RD_COOKIE_TOKEN' ) ) define( 'RD_COOKIE_TOKEN', 'btpUserToken' );
if ( ! defined( 'RD_COOKIE_MATRICULA' ) ) define( 'RD_COOKIE_MATRICULA', 'btpUserName' );

define( 'RD_OPT_WINDOW_DAYS', 'rd_window_days' );
define( 'RD_OPT_CUTOFF_HHMM', 'rd_cutoff_hhmm' );
define( 'RD_OPT_REPORT_EMAILS', 'rd_report_emails' );

require_once RD_DIR . 'includes/utils.php';
require_once RD_DIR . 'includes/refeicoes-db.php';
require_once RD_DIR . 'includes/rules.php';
require_once RD_DIR . 'includes/auth-senior.php';
require_once RD_DIR . 'includes/security.php';
require_once RD_DIR . 'includes/performance.php';

require_once RD_DIR . 'includes/services/meal-types-enum.php';
require_once RD_DIR . 'includes/services/meal-categories.php';
require_once RD_DIR . 'includes/services/refeicao-service.php';
require_once RD_DIR . 'includes/services/relatorio-service.php';

require_once RD_DIR . 'includes/roles-access.php';
require_once RD_DIR . 'includes/rest-endpoints.php';
require_once RD_DIR . 'includes/admin/settings-page.php';
require_once RD_DIR . 'includes/admin/painel.php';
require_once RD_DIR . 'includes/shortcodes.php';
require_once RD_DIR . 'includes/wp-cli.php';

if ( ! defined('RD_CRON_HOOK') ) {
    define('RD_CRON_HOOK', 'rd_daily_report_event');
}
if ( ! defined('RD_CACHE_CRON_HOOK') ) {
    define('RD_CACHE_CRON_HOOK', 'rd_cache_cleanup_event');
}

if ( ! defined('RD_CRON_ALMOCO') ) {
    define('RD_CRON_ALMOCO', 'rd_category_report_almoco');
}
if ( ! defined('RD_CRON_JANTAR') ) {
    define('RD_CRON_JANTAR', 'rd_category_report_jantar');
}
if ( ! defined('RD_CRON_CEIA') ) {
    define('RD_CRON_CEIA', 'rd_category_report_ceia');
}

add_action( RD_CRON_HOOK, 'rd_send_daily_report' );

add_action( RD_CRON_ALMOCO, function() { rd_send_category_report('almoco'); } );
add_action( RD_CRON_JANTAR, function() { rd_send_category_report('jantar'); } );
add_action( RD_CRON_CEIA,   function() { rd_send_category_report('ceia'); } );

add_action( RD_CACHE_CRON_HOOK, function() {
    if (class_exists('RD_Performance')) {
        RD_Performance::get_instance()->clear_all_cache();
    }
});

register_activation_hook( __FILE__, function() {
    rd_log( 'Ativação do plugin' );
    rd_install_tables();
    rd_criar_roles_customizadas();

    $tz  = wp_timezone();
    $now = new DateTimeImmutable('now', $tz);

    if ( ! wp_next_scheduled( RD_CRON_HOOK ) ) {
        $run  = $now->setTime(6, 5);
        if ( $run <= $now ) { $run = $run->modify('+1 day'); }
        wp_schedule_event( $run->getTimestamp(), 'daily', RD_CRON_HOOK );
    }

    if ( ! wp_next_scheduled( RD_CACHE_CRON_HOOK ) ) {
        $next_week = strtotime('+7 days');
        wp_schedule_event( $next_week, 'weekly', RD_CACHE_CRON_HOOK );
    }

    // Relatório Almoço: 15:10
    if ( ! wp_next_scheduled( RD_CRON_ALMOCO ) ) {
        $run = $now->setTime(15, 10);
        if ( $run <= $now ) { $run = $run->modify('+1 day'); }
        wp_schedule_event( $run->getTimestamp(), 'daily', RD_CRON_ALMOCO );
    }

    // Relatório Jantar: 21:40
    if ( ! wp_next_scheduled( RD_CRON_JANTAR ) ) {
        $run = $now->setTime(21, 40);
        if ( $run <= $now ) { $run = $run->modify('+1 day'); }
        wp_schedule_event( $run->getTimestamp(), 'daily', RD_CRON_JANTAR );
    }

    // Relatório Ceia: 04:40
    if ( ! wp_next_scheduled( RD_CRON_CEIA ) ) {
        $run = $now->setTime(4, 40);
        if ( $run <= $now ) { $run = $run->modify('+1 day'); }
        wp_schedule_event( $run->getTimestamp(), 'daily', RD_CRON_CEIA );
    }
});

register_deactivation_hook( __FILE__, function(){
    wp_clear_scheduled_hook( RD_CRON_HOOK );
    wp_clear_scheduled_hook( RD_CACHE_CRON_HOOK );
    wp_clear_scheduled_hook( RD_CRON_ALMOCO );
    wp_clear_scheduled_hook( RD_CRON_JANTAR );
    wp_clear_scheduled_hook( RD_CRON_CEIA );
    rd_remover_roles_customizadas();
});

add_action( 'init', function() {
    wp_register_style( 'rd-style', RD_URL . 'assets/css/style.css', [], RD_VER );
    wp_register_script( 'rd-form',  RD_URL . 'assets/js/rd-form.js',  [ 'wp-api-fetch' ], RD_VER, true );
    wp_register_script( 'rd-admin', RD_URL . 'assets/js/rd-admin.js', [ 'wp-api-fetch' ], RD_VER, true );
    wp_register_script( 'rd-list',  RD_URL . 'assets/js/rd-list.js', [], RD_VER, true );
    wp_register_script( 'rd-cards', RD_URL . 'assets/js/rd-cards.js', [], RD_VER, true );
});

rd_log( 'Plugin carregado' );