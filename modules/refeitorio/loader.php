<?php
/**
 * BTP Conecta — módulo Refeitório Digital
 * Carregado pelo tema via functions.php (não é plugin WordPress).
 *
 * Equivalente ao refeitorio-digital.php do plugin, adaptado para:
 *  - Paths relativos ao tema (get_template_directory)
 *  - Hooks de ativação substituídos por after_switch_theme / init
 *  - Crons reagendados automaticamente se ausentes
 */
if ( ! defined( 'ABSPATH' ) ) { exit; }

// ── Constantes ────────────────────────────────────────────────────────────────
if ( ! defined('RD_FILE') )    define( 'RD_FILE', __FILE__ );
if ( ! defined('RD_DIR') )     define( 'RD_DIR',  get_template_directory() . '/modules/refeitorio/' );
if ( ! defined('RD_URL') )     define( 'RD_URL',  get_template_directory_uri() . '/modules/refeitorio/' );
if ( ! defined('RD_VER') )     define( 'RD_VER',  '2.1.0' );
if ( ! defined('RD_DB_TABLE') ) define( 'RD_DB_TABLE', 'rm_refeicoes' );

if ( ! defined('RD_TOKENS_TABLE') )    define( 'RD_TOKENS_TABLE',    'btpconecta_tokens' );
if ( ! defined('RD_COOKIE_TOKEN') )    define( 'RD_COOKIE_TOKEN',    'btpUserToken' );
if ( ! defined('RD_COOKIE_MATRICULA') ) define( 'RD_COOKIE_MATRICULA', 'btpUserName' );

if ( ! defined('RD_OPT_WINDOW_DAYS') )  define( 'RD_OPT_WINDOW_DAYS',  'rd_window_days' );
if ( ! defined('RD_OPT_CUTOFF_HHMM') )  define( 'RD_OPT_CUTOFF_HHMM',  'rd_cutoff_hhmm' );
if ( ! defined('RD_OPT_REPORT_EMAILS') ) define( 'RD_OPT_REPORT_EMAILS', 'rd_report_emails' );

if ( ! defined('RD_CRON_HOOK') )       define( 'RD_CRON_HOOK',       'rd_daily_report_event' );
if ( ! defined('RD_CACHE_CRON_HOOK') ) define( 'RD_CACHE_CRON_HOOK', 'rd_cache_cleanup_event' );
if ( ! defined('RD_CRON_ALMOCO') )     define( 'RD_CRON_ALMOCO',     'rd_category_report_almoco' );
if ( ! defined('RD_CRON_JANTAR') )     define( 'RD_CRON_JANTAR',     'rd_category_report_jantar' );
if ( ! defined('RD_CRON_CEIA') )       define( 'RD_CRON_CEIA',       'rd_category_report_ceia' );

// ── Includes ──────────────────────────────────────────────────────────────────
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

// ── Cron handlers ─────────────────────────────────────────────────────────────
add_action( RD_CRON_HOOK,  'rd_send_daily_report' );
add_action( RD_CRON_ALMOCO, function() { rd_send_category_report('almoco'); } );
add_action( RD_CRON_JANTAR, function() { rd_send_category_report('jantar'); } );
add_action( RD_CRON_CEIA,   function() { rd_send_category_report('ceia'); } );
add_action( RD_CACHE_CRON_HOOK, function() {
    if ( class_exists('RD_Performance') ) {
        RD_Performance::get_instance()->clear_all_cache();
    }
});

// ── Inicialização: tabelas + roles + crons ────────────────────────────────────
// Substitui register_activation_hook — roda na troca de tema e garante
// que tabelas e crons existam sempre, mesmo em deploy direto de arquivos.
function rd_module_setup(): void {
    rd_install_tables();
    rd_criar_roles_customizadas();
    rd_module_schedule_crons();
}
add_action( 'after_switch_theme', 'rd_module_setup' );

// Garante crons mesmo sem trocar de tema (ex.: deploy direto)
add_action( 'init', function(): void {
    if ( ! wp_next_scheduled( RD_CRON_HOOK ) ||
         ! wp_next_scheduled( RD_CRON_ALMOCO ) ||
         ! wp_next_scheduled( RD_CRON_JANTAR ) ||
         ! wp_next_scheduled( RD_CRON_CEIA ) ) {
        rd_module_schedule_crons();
    }
}, 5 );

function rd_module_schedule_crons(): void {
    $tz  = wp_timezone();
    $now = new DateTimeImmutable( 'now', $tz );

    $schedules = [
        RD_CRON_HOOK   => [6,  5],
        RD_CRON_ALMOCO => [15, 10],
        RD_CRON_JANTAR => [21, 40],
        RD_CRON_CEIA   => [4,  40],
    ];

    foreach ( $schedules as $hook => [$h, $m] ) {
        if ( ! wp_next_scheduled( $hook ) ) {
            $run = $now->setTime( $h, $m );
            if ( $run <= $now ) { $run = $run->modify('+1 day'); }
            wp_schedule_event( $run->getTimestamp(), 'daily', $hook );
        }
    }

    if ( ! wp_next_scheduled( RD_CACHE_CRON_HOOK ) ) {
        wp_schedule_event( strtotime('+7 days'), 'weekly', RD_CACHE_CRON_HOOK );
    }
}

// ── Limpeza ao trocar de tema ─────────────────────────────────────────────────
// Substitui register_deactivation_hook
add_action( 'switch_theme', function(): void {
    wp_clear_scheduled_hook( RD_CRON_HOOK );
    wp_clear_scheduled_hook( RD_CACHE_CRON_HOOK );
    wp_clear_scheduled_hook( RD_CRON_ALMOCO );
    wp_clear_scheduled_hook( RD_CRON_JANTAR );
    wp_clear_scheduled_hook( RD_CRON_CEIA );
    rd_remover_roles_customizadas();
});

// ── Assets ────────────────────────────────────────────────────────────────────
add_action( 'init', function(): void {
    wp_register_style(  'rd-style',  RD_URL . 'assets/css/style.css',   [], RD_VER );
    wp_register_script( 'rd-form',   RD_URL . 'assets/js/rd-form.js',   ['wp-api-fetch'], RD_VER, true );
    wp_register_script( 'rd-admin',  RD_URL . 'assets/js/rd-admin.js',  ['wp-api-fetch'], RD_VER, true );
    wp_register_script( 'rd-list',   RD_URL . 'assets/js/rd-list.js',   [], RD_VER, true );
    wp_register_script( 'rd-cards',  RD_URL . 'assets/js/rd-cards.js',  [], RD_VER, true );
});

rd_log( 'Módulo Refeitório carregado' );
