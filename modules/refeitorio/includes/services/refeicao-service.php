<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! function_exists('rd_refeicao_allowed_types') ) {
function rd_refeicao_allowed_types(): array {
    if ( function_exists('rd_get_meal_types_codes') ) {
        return rd_get_meal_types_codes();
    }
    if ( function_exists('rd_get_meal_types_opt') ) {
        return rd_get_meal_types_opt();
    }
    return ['QVeggie','QLight','QSabor'];
}}

if ( ! function_exists('rd_refeicao_normalize_row') ) {
function rd_refeicao_normalize_row(array $r): array {
    $r['id']              = isset($r['id']) ? (int) $r['id'] : null;
    $r['retirado']        = (int) ($r['retirado'] ?? 0);
    $r['status']          = trim((string)($r['status'] ?? ''));
    $r['matricula']       = (string)($r['matricula'] ?? '');
    $r['nome_completo']   = (string)($r['nome_completo'] ?? '');
    $r['data_refeicao']   = (string)($r['data_refeicao'] ?? '');
    $r['refeicao']        = (string)($r['refeicao'] ?? '');
    $r['categoria']       = (string)($r['categoria'] ?? '');
    $r['local_retirada']  = (string)($r['local_retirada'] ?? '');
    return $r;
}}

if ( ! function_exists('rd_row_can_edit') ) {
function rd_row_can_edit(array $r): bool {
    $r = rd_refeicao_normalize_row($r);
    if ( strtolower($r['status']) !== 'ativo' ) return false;
    if ( !empty($r['retirado']) ) return false;

    $tz = wp_timezone();
    $d  = DateTimeImmutable::createFromFormat('Y-m-d', $r['data_refeicao'], $tz);
    if ( ! $d ) return false;

    if ( current_user_can('manage_options') || current_user_can('rd_gerenciar_retiradas') ) return true;

    $categoria = $r['categoria'] ?? '';
    return rd_cutoff_permite_categoria($d, $categoria);
}}

if ( ! function_exists('rd_refeicao_create') ) {
function rd_refeicao_create(
    string $matricula,
    string $nome_completo,
    string $data_refeicao,
    string $refeicao,
    ?string $local_retirada = null,
    string $categoria = ''
) {
    global $wpdb;
    $table = rd_db_table_full();

    $tz = wp_timezone();
    $d  = DateTimeImmutable::createFromFormat('Y-m-d', $data_refeicao, $tz);
    if ( ! $d ) return new WP_Error( 'rd_data_invalida', 'Data inválida' );

    $allowed = function_exists('rd_refeicao_allowed_types')
        ? rd_refeicao_allowed_types()
        : ['Qveggie','Qlight','Qsabor'];
    if ( ! in_array( $refeicao, $allowed, true ) ) {
        return new WP_Error( 'rd_tipo_invalido', 'Tipo inválido' );
    }

    if ( function_exists('rd_is_meal_type_allowed_for_date') && ! rd_is_meal_type_allowed_for_date( $refeicao, $data_refeicao ) ) {
        return new WP_Error( 'rd_tipo_dia_invalido', 'Este cardápio não está disponível para o dia selecionado' );
    }

    if ( ! rd_can_schedule_date( $d ) ) {
        return new WP_Error( 'rd_fora_janela', 'Data fora da janela de agendamento' );
    }

    $categoria = sanitize_key($categoria);

    $now = rd_now_wp()->format('Y-m-d H:i:s');

    $local_sanit = '';
    if ( $local_retirada !== null ) {
        $local_sanit = function_exists('rd_local_retirada_sanitize')
            ? rd_local_retirada_sanitize($local_retirada)
            : sanitize_key((string)$local_retirada);
    }

    $row_exist = $wpdb->get_row( $wpdb->prepare(
        "SELECT id, status FROM `$table` WHERE matricula=%s AND data_refeicao=%s AND categoria=%s LIMIT 1",
        $matricula, $data_refeicao, $categoria
    ), ARRAY_A );

    if ( $row_exist ) {
        if ( isset($row_exist['status']) && strtolower($row_exist['status']) === 'cancelado' ) {
            $update = [
                'nome_completo' => $nome_completo,
                'refeicao'      => $refeicao,
                'categoria'     => $categoria,
                'status'        => 'ativo',
                'retirado'      => 0,
                'updated_at'    => $now,
            ];
            $fmts = ['%s','%s','%s','%s','%d','%s'];

            if ( $local_retirada !== null ) {
                $update['local_retirada'] = $local_sanit;
                $fmts[] = '%s';
            }

            $ok_upd = $wpdb->update(
                $table,
                $update,
                [ 'id' => (int)$row_exist['id'] ],
                $fmts,
                [ '%d' ]
            );
            if ( $ok_upd === false ) {
                return new WP_Error( 'rd_db_error', 'Falha ao reativar solicitação cancelada' );
            }

            if ( function_exists('rd_refeicoes_log_write') ) {
                $row_after = $wpdb->get_row( $wpdb->prepare(
                    "SELECT * FROM `$table` WHERE id=%d", (int)$row_exist['id']
                ), ARRAY_A );
                rd_refeicoes_log_write( $row_after ?: [], 'reactivate_from_cancel' );
            }

            return (int) $row_exist['id'];
        }

        return new WP_Error( 'rd_ja_existe', 'Já existe solicitação para esta data e categoria' );
    }

    $ok = $wpdb->insert( $table, [
        'matricula'      => $matricula,
        'nome_completo'  => $nome_completo,
        'data_refeicao'  => $data_refeicao,
        'refeicao'       => $refeicao,
        'categoria'      => $categoria,
        'retirado'       => 0,
        'status'         => 'ativo',
        'local_retirada' => $local_sanit,
        'created_at'     => $now,
        'updated_at'     => $now,
    ], [ '%s','%s','%s','%s','%s','%d','%s','%s','%s','%s' ] );

    if ( ! $ok ) {
        $db_error = $wpdb->last_error;
        if ( function_exists('rd_log') ) rd_log( 'Erro ao inserir', [ 'db' => $db_error ] );

        if ( strpos($db_error, 'Duplicate') !== false ) {
            return new WP_Error( 'rd_ja_existe', 'Já existe solicitação para esta data e categoria' );
        }

        return new WP_Error( 'rd_db_error', 'Falha ao salvar. ' . $db_error );
    }

    $new_id = (int) $wpdb->insert_id;

    if ( function_exists('rd_refeicoes_log_write') ) {
        $row_new = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM `$table` WHERE id=%d", $new_id
        ), ARRAY_A );
        if ( $row_new ) rd_refeicoes_log_write( $row_new, 'create' );
    }

    return $new_id;
}}

if ( ! function_exists('rd_refeicao_cancel') ) {
function rd_refeicao_cancel( int $id, string $matricula ) {
    global $wpdb;
    $table = rd_db_table_full();

    $row = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `$table` WHERE id=%d", $id) );
    if ( ! $row ) return new WP_Error( 'rd_not_found', 'Registro não encontrado' );

    $is_admin = current_user_can('manage_options');
    if ( ! $is_admin && $row->matricula !== $matricula ) {
        return new WP_Error( 'rd_forbidden', 'Sem permissão' );
    }
    if ( (int)$row->retirado === 1 ) {
        return new WP_Error( 'rd_unprocessable', 'Não é possível cancelar/excluir um pedido já retirado' );
    }

    $d = DateTimeImmutable::createFromFormat('Y-m-d', (string)$row->data_refeicao, wp_timezone());
    if ( ! $d ) return new WP_Error( 'rd_data_invalida', 'Data inválida' );
    $categoria = $row->categoria ?? '';
    if ( ! $is_admin && ! rd_can_cancel_until( $d, $categoria ) ) {
        return new WP_Error( 'rd_prazo_expirado', 'Prazo de cancelamento expirado' );
    }

    if ( function_exists('rd_refeicoes_log_write') ) {
        rd_refeicoes_log_write( $row, $is_admin ? 'delete_admin' : 'delete_user' );
    }

    $where = $is_admin ? [ 'id' => $id ] : [ 'id' => $id, 'matricula' => $matricula ];
    $ok = $wpdb->delete( $table, $where, $is_admin ? [ '%d' ] : [ '%d','%s' ] );
    if ( $ok === false ) return new WP_Error( 'rd_db_error', 'Falha ao excluir' );

    return true;
}}

if ( ! function_exists('rd_refeicao_mark_retirado') ) {
function rd_refeicao_mark_retirado( int $id ) {
    global $wpdb;
    $table = rd_db_table_full();

    $row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `$table` WHERE id=%d", $id ) );
    if ( ! $row ) return new WP_Error( 'rd_not_found', 'Registro não encontrado' );

    $tz  = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone( get_option('timezone_string') ?: 'UTC' );
    $raw = (string) $row->data_refeicao;

    $d = DateTimeImmutable::createFromFormat('Y-m-d', $raw, $tz);
    if ( ! $d ) { $d = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $raw, $tz); }
    if ( ! $d ) { return new WP_Error( 'rd_invalid_date', 'Data da refeição inválida' ); }

    if ( ! rd_can_mark_retirado( $d ) ) {
        return new WP_Error( 'rd_fora_dia', 'Marcação de retirada apenas no dia' );
    }

    $ok = $wpdb->update(
        $table,
        [
            'retirado'   => 1,
            'status'     => 'confirmado',
            'updated_at' => rd_now_wp()->format('Y-m-d H:i:s'),
        ],
        [ 'id' => $id ],
        [ '%d','%s','%s' ],
        [ '%d' ]
    );
    if ( $ok === false ) return new WP_Error( 'rd_db_error', 'Falha ao marcar retirada' );

    if ( function_exists('rd_refeicoes_log_write') ) {
        $row_after = $wpdb->get_row( $wpdb->prepare("SELECT * FROM `$table` WHERE id=%d", $id), ARRAY_A );
        rd_refeicoes_log_write(
            $row_after ?: (array) $row,
            'retirado',
            [ 'reason' => 'marcado via painel/admin' ]
        );
    }

    return true;
}}

if ( ! function_exists("rd_refeicao_list_user") ) {
function rd_refeicao_list_user( string $matricula, array $args = [] ): array {
    global $wpdb; $table = rd_db_table_full();

    $where  = 'WHERE matricula = %s';
    $params = [ $matricula ];

    if ( ! empty( $args['data'] ) ) { $where .= ' AND data_refeicao = %s'; $params[] = $args['data']; }
    if ( ! empty( $args['tipo'] ) ) { $where .= ' AND refeicao = %s';      $params[] = $args['tipo']; }

    if ( ! empty( $args['status'] ) ) {
        $st = strtolower( (string) $args['status'] );
        if ( in_array( $st, ['ativo','confirmado','cancelado'], true ) ) {
            $where .= ' AND status = %s'; $params[] = $st;
        } elseif ( $st === 'solicitado' ) {
            $where .= " AND status <> 'cancelado'";
        }
    }

    $sql  = $wpdb->prepare( "SELECT * FROM `$table` $where ORDER BY data_refeicao DESC, id DESC", ...$params );
    $rows = $wpdb->get_results( $sql, ARRAY_A ) ?: [];
    foreach ($rows as &$r) { $r = rd_refeicao_normalize_row($r); $r['can_edit'] = rd_row_can_edit($r); }
    unset($r);
    return $rows;
}}

if ( ! function_exists('rd_refeicao_list_admin') ) {
function rd_refeicao_list_admin( array $args = [] ): array {
    global $wpdb; $table = rd_db_table_full();

    $where  = 'WHERE 1=1';
    $params = [];

    if ( ! empty( $args['data'] ) ) { $where .= ' AND data_refeicao = %s'; $params[] = $args['data']; }
    if ( ! empty( $args['tipo'] ) ) { $where .= ' AND refeicao = %s';      $params[] = $args['tipo']; }

    if ( ! empty( $args['matricula'] ) ) {
        $mat = preg_replace('/@btp\.com\.br$/i', '', (string)$args['matricula']);
        $where .= ' AND (matricula = %s OR matricula = %s)';
        $params[] = $mat;
        $params[] = $mat . '@btp.com.br';
    }

    if ( isset($args['local_retirada']) && $args['local_retirada'] !== null && $args['local_retirada'] !== '' ) {
        $where .= ' AND local_retirada = %s';
        $params[] = function_exists('rd_local_retirada_sanitize')
            ? rd_local_retirada_sanitize($args['local_retirada'])
            : sanitize_key((string)$args['local_retirada']);
    }

    if ( ! empty( $args['categoria'] ) ) {
        $where .= ' AND categoria = %s';
        $params[] = sanitize_key((string)$args['categoria']);
    }

    if ( ! empty( $args['status'] ) ) {
        $st = strtolower( (string) $args['status'] );
        if ( in_array( $st, ['ativo','confirmado','cancelado'], true ) ) {
            $where .= ' AND status = %s'; $params[] = $st;
        } elseif ( $st === 'solicitado' ) {
            $where .= " AND status <> 'cancelado'";
        }
    }

    $orderby = " ORDER BY data_refeicao DESC, matricula ASC";

    $sql = "SELECT * FROM `$table` $where" . $orderby;

    if ( isset($args['limit']) ) {
        $limit  = max(1, (int) $args['limit']);
        if ($limit > 500) { $limit = 500; }
        $offset = max(0, (int) ($args['offset'] ?? 0));
        $sql .= " LIMIT %d OFFSET %d";
        $params[] = $limit;
        $params[] = $offset;
    }

    $sql  = $wpdb->prepare($sql, ...$params);
    $rows = $wpdb->get_results( $sql, ARRAY_A ) ?: [];
    foreach ($rows as &$r) { $r = rd_refeicao_normalize_row($r); $r['can_edit'] = rd_row_can_edit($r); }
    unset($r);
    return $rows;
}}

if ( ! function_exists('rd_user_known_name') ) {
function rd_user_known_name( string $matricula ): ?string {
    global $wpdb;
    $table = rd_db_table_full();

    $name = $wpdb->get_var( $wpdb->prepare(
        "SELECT nome_completo FROM `$table`
         WHERE matricula=%s AND nome_completo<>''
         ORDER BY updated_at DESC, id DESC LIMIT 1",
        $matricula
    ) );

    if ( $name ) {
        return $name;
    }

    if ( function_exists('rd_db_log_table_full') ) {
        $log_table = rd_db_log_table_full();
        $name = $wpdb->get_var( $wpdb->prepare(
            "SELECT nome_completo FROM `$log_table`
             WHERE matricula=%s AND nome_completo<>''
             ORDER BY occurred_at DESC, id DESC LIMIT 1",
            $matricula
        ) );
    }

    return $name ?: null;
}}

if ( ! function_exists('rd_refeicoes_log_write') ) {
function rd_refeicoes_log_write( $row, string $action, array $extra = [] ): void {
    global $wpdb;
    if ( ! function_exists('rd_db_log_table_full') ) return; 
    $log_table = rd_db_log_table_full();

    $r = is_array($row) ? $row : (array) $row;

    if ( current_user_can('manage_options') ) {
        $u = wp_get_current_user();
        $actor = 'admin:' . ( $u && $u->user_login ? $u->user_login : 'unknown' );
    } else {
        $actor = 'user:' . ( function_exists('rd_current_matricula') ? (string) rd_current_matricula() : '' );
    }

    $wpdb->insert( $log_table, [
        'refeicao_id'   => (int)($r['id'] ?? 0),
        'matricula'     => (string)($r['matricula'] ?? ''),
        'nome_completo' => (string)($r['nome_completo'] ?? ''),
        'data_refeicao' => (string)($r['data_refeicao'] ?? ''),
        'refeicao'      => (string)($r['refeicao'] ?? ''),
        'status'        => (string)($r['status'] ?? ''),
        'retirado'      => (int)($r['retirado'] ?? 0),
        'action'        => $action,
        'actor'         => $actor,
        'occurred_at'   => rd_now_wp()->format('Y-m-d H:i:s'),
        'extra'         => empty($extra) ? null : wp_json_encode($extra, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES),
    ], [ '%d','%s','%s','%s','%s','%s','%d','%s','%s','%s','%s' ] );
}}

if ( ! function_exists('rd_refeicao_count_admin') ) {
function rd_refeicao_count_admin( array $args = [] ): int {
    global $wpdb; $table = rd_db_table_full();
    $where  = 'WHERE 1=1';
    $params = [];

    if ( ! empty( $args['data'] ) ) { $where .= ' AND data_refeicao = %s'; $params[] = $args['data']; }
    if ( ! empty( $args['tipo'] ) ) { $where .= ' AND refeicao = %s';      $params[] = $args['tipo']; }

    if ( ! empty( $args['matricula'] ) ) {
        $mat = preg_replace('/@btp\.com\.br$/i', '', (string)$args['matricula']);
        $where .= ' AND (matricula = %s OR matricula = %s)';
        $params[] = $mat;
        $params[] = $mat . '@btp.com.br';
    }

    if ( isset($args['local_retirada']) && $args['local_retirada'] !== null && $args['local_retirada'] !== '' ) {
        $where .= ' AND local_retirada = %s';
        $params[] = function_exists('rd_local_retirada_sanitize')
            ? rd_local_retirada_sanitize($args['local_retirada'])
            : sanitize_key((string)$args['local_retirada']);
    }

    if ( ! empty( $args['categoria'] ) ) {
        $where .= ' AND categoria = %s';
        $params[] = sanitize_key((string)$args['categoria']);
    }

    if ( ! empty( $args['status'] ) ) {
        $st = strtolower( (string) $args['status'] );
        if ( in_array( $st, ['ativo','confirmado','cancelado'], true ) ) {
            $where .= ' AND status = %s'; $params[] = $st;
        } elseif ( $st === 'solicitado' ) {
            $where .= " AND status <> 'cancelado'";
        }
    }

    $sql   = $wpdb->prepare( "SELECT COUNT(*) FROM `$table` $where", ...$params );
    $total = (int) $wpdb->get_var( $sql );
    return $total;
}}
