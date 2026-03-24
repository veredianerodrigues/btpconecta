<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function rd_get_meal_categories( bool $active_only = true ): array {
    global $wpdb;
    $table = rd_db_categories_table_full();
    $where = $active_only ? 'WHERE active = 1' : '';
    $rows = $wpdb->get_results(
        "SELECT id, code, label, cutoff_hhmm, same_day, active, sort_order FROM `$table` $where ORDER BY sort_order ASC, label ASC",
        ARRAY_A
    );
    return $rows ?: [];
}

function rd_get_category_by_code( string $code ): ?array {
    global $wpdb;
    $table = rd_db_categories_table_full();
    $row = $wpdb->get_row(
        $wpdb->prepare("SELECT * FROM `$table` WHERE code = %s", $code),
        ARRAY_A
    );
    return $row ?: null;
}

function rd_get_cutoff_for_category( string $code ): string {
    $cat = rd_get_category_by_code($code);
    if ( $cat && ! empty($cat['cutoff_hhmm']) ) {
        return $cat['cutoff_hhmm'];
    }
    return rd_get_cutoff_hhmm();
}

function rd_is_same_day_category( string $code ): bool {
    $cat = rd_get_category_by_code($code);
    return $cat && ! empty($cat['same_day']);
}

function rd_category_exists( string $code ): bool {
    return rd_get_category_by_code($code) !== null;
}

function rd_get_categories_for_frontend(): array {
    $cats = rd_get_meal_categories(true);
    $result = [];
    foreach ( $cats as $cat ) {
        $result[] = [
            'code'     => $cat['code'],
            'label'    => $cat['label'],
            'cutoff'   => $cat['cutoff_hhmm'],
            'same_day' => ! empty($cat['same_day']),
        ];
    }
    return $result;
}

function rd_insert_category( array $data ): int|WP_Error {
    global $wpdb;
    $table = rd_db_categories_table_full();

    $code = sanitize_title($data['code'] ?? '');
    $label = sanitize_text_field($data['label'] ?? '');
    $cutoff = preg_match('/^\d{2}:\d{2}$/', $data['cutoff_hhmm'] ?? '') ? $data['cutoff_hhmm'] : '';
    $same_day = isset($data['same_day']) ? (int)(bool)$data['same_day'] : 0;
    $active = isset($data['active']) ? (int)(bool)$data['active'] : 1;
    $sort_order = (int)($data['sort_order'] ?? 0);

    if ( empty($code) || empty($label) ) {
        return new WP_Error('rd_invalid', 'Código e label são obrigatórios.');
    }
    if ( rd_category_exists($code) ) {
        return new WP_Error('rd_duplicate', 'Já existe uma categoria com este código.');
    }

    $inserted = $wpdb->insert($table, [
        'code'        => $code,
        'label'       => $label,
        'cutoff_hhmm' => $cutoff,
        'same_day'    => $same_day,
        'active'      => $active,
        'sort_order'  => $sort_order,
        'created_at'  => current_time('mysql'),
    ]);

    if ( ! $inserted ) {
        return new WP_Error('rd_db_error', 'Erro ao inserir categoria.');
    }
    return (int) $wpdb->insert_id;
}

function rd_update_category( int $id, array $data ): bool|WP_Error {
    global $wpdb;
    $table = rd_db_categories_table_full();

    $update = [];
    if ( isset($data['label']) ) {
        $update['label'] = sanitize_text_field($data['label']);
    }
    if ( isset($data['cutoff_hhmm']) ) {
        $update['cutoff_hhmm'] = preg_match('/^\d{2}:\d{2}$/', $data['cutoff_hhmm']) ? $data['cutoff_hhmm'] : '';
    }
    if ( isset($data['same_day']) ) {
        $update['same_day'] = (int)(bool)$data['same_day'];
    }
    if ( isset($data['active']) ) {
        $update['active'] = (int)(bool)$data['active'];
    }
    if ( isset($data['sort_order']) ) {
        $update['sort_order'] = (int)$data['sort_order'];
    }

    if ( empty($update) ) {
        return new WP_Error('rd_no_data', 'Nenhum dado para atualizar.');
    }

    $result = $wpdb->update($table, $update, ['id' => $id]);
    return $result !== false;
}

function rd_delete_category( int $id ): bool {
    global $wpdb;
    $table = rd_db_categories_table_full();
    return (bool) $wpdb->delete($table, ['id' => $id]);
}
