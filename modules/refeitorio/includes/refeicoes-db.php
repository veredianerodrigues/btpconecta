<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function rd_db_categories_table(): string {
    return 'rm_meal_categories';
}

function rd_db_categories_table_full(): string {
    global $wpdb;
    return $wpdb->prefix . rd_db_categories_table();
}

function rd_install_tables(): void {
    global $wpdb;
    $table     = rd_db_table_full();
    $log_table = rd_db_log_table_full();
    $cat_table = rd_db_categories_table_full();
    $charset   = $wpdb->get_charset_collate();

    $sql_categories = "CREATE TABLE IF NOT EXISTS `$cat_table` (
        `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
        `code` VARCHAR(50) NOT NULL,
        `label` VARCHAR(100) NOT NULL,
        `cutoff_hhmm` VARCHAR(5) NOT NULL DEFAULT '',
        `same_day` TINYINT(1) NOT NULL DEFAULT 0,
        `active` TINYINT(1) NOT NULL DEFAULT 1,
        `sort_order` INT NOT NULL DEFAULT 0,
        `created_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_code` (`code`)
    ) $charset;";

    $sql = "CREATE TABLE IF NOT EXISTS `$table` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `matricula` VARCHAR(50) NOT NULL,
        `nome_completo` VARCHAR(200) NOT NULL,
        `data_refeicao` DATE NOT NULL,
        `refeicao` VARCHAR(50) NOT NULL,
        `categoria` VARCHAR(50) NOT NULL DEFAULT '',
        `retirado` TINYINT(1) NOT NULL DEFAULT 0,
        `local_retirada` VARCHAR(50) NOT NULL DEFAULT '',
        `status` ENUM('ativo','confirmado','cancelado') NOT NULL DEFAULT 'ativo',
        `created_at` DATETIME NOT NULL,
        `updated_at` DATETIME NOT NULL,
        PRIMARY KEY (`id`),
        UNIQUE KEY `uq_matricula_data_cat` (`matricula`,`data_refeicao`,`categoria`),
        KEY `idx_data_tipo` (`data_refeicao`,`refeicao`),
        KEY `idx_categoria` (`categoria`)
    ) $charset;";

    $sql_log = "CREATE TABLE IF NOT EXISTS `$log_table` (
        `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
        `refeicao_id` BIGINT UNSIGNED NOT NULL DEFAULT 0,
        `matricula` VARCHAR(50) NOT NULL,
        `nome_completo` VARCHAR(200) NOT NULL DEFAULT '',
        `data_refeicao` DATE NOT NULL,
        `refeicao` VARCHAR(50) NOT NULL,
        `status` VARCHAR(20) NOT NULL DEFAULT '',
        `retirado` TINYINT(1) NOT NULL DEFAULT 0,
        `action` ENUM('create','edit','retirado','delete_user','delete_admin') NOT NULL,
        `actor` VARCHAR(120) NOT NULL DEFAULT '',
        `occurred_at` DATETIME NOT NULL,
        `extra` TEXT NULL,
        PRIMARY KEY (`id`),
        KEY `idx_data` (`data_refeicao`),
        KEY `idx_matricula` (`matricula`),
        KEY `idx_action` (`action`)
    ) $charset;";

    require_once ABSPATH . 'wp-admin/includes/upgrade.php';
    dbDelta($sql_categories);
    dbDelta($sql);
    dbDelta($sql_log);

    rd_maybe_add_categoria_column();
    rd_maybe_add_same_day_column();
    rd_seed_default_categories();

    if ( function_exists('rd_log') ) {
        rd_log('Tabelas verificadas/criadas', [ 'table' => $table, 'log' => $log_table, 'categories' => $cat_table ]);
    }
}

function rd_maybe_add_categoria_column(): void {
    global $wpdb;
    $table = rd_db_table_full();
    $col = $wpdb->get_results("SHOW COLUMNS FROM `$table` LIKE 'categoria'");
    if ( empty($col) ) {
        $wpdb->query("ALTER TABLE `$table` ADD COLUMN `categoria` VARCHAR(50) NOT NULL DEFAULT '' AFTER `refeicao`");
    }
}

function rd_maybe_add_same_day_column(): void {
    global $wpdb;
    $table = rd_db_categories_table_full();
    $col = $wpdb->get_results("SHOW COLUMNS FROM `$table` LIKE 'same_day'");
    if ( empty($col) ) {
        $wpdb->query("ALTER TABLE `$table` ADD COLUMN `same_day` TINYINT(1) NOT NULL DEFAULT 0 AFTER `cutoff_hhmm`");
        $wpdb->query("UPDATE `$table` SET same_day = 1 WHERE code = 'ceia'");
    }
}

function rd_seed_default_categories(): void {
    global $wpdb;
    $table = rd_db_categories_table_full();
    $count = (int) $wpdb->get_var("SELECT COUNT(*) FROM `$table`");
    if ( $count > 0 ) return;

    $now = current_time('mysql');
    $defaults = [
        ['code' => 'almoco', 'label' => 'Almoço', 'cutoff_hhmm' => '15:00', 'same_day' => 0, 'sort_order' => 1],
        ['code' => 'jantar', 'label' => 'Jantar', 'cutoff_hhmm' => '21:30', 'same_day' => 0, 'sort_order' => 2],
        ['code' => 'ceia',   'label' => 'Ceia',   'cutoff_hhmm' => '04:30', 'same_day' => 1, 'sort_order' => 3],
    ];
    foreach ( $defaults as $cat ) {
        $wpdb->insert($table, [
            'code'        => $cat['code'],
            'label'       => $cat['label'],
            'cutoff_hhmm' => $cat['cutoff_hhmm'],
            'same_day'    => $cat['same_day'],
            'active'      => 1,
            'sort_order'  => $cat['sort_order'],
            'created_at'  => $now,
        ]);
    }
}
