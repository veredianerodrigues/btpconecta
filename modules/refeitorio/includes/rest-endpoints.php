<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function rd_is_admin_user() {
    return current_user_can( 'manage_options' );
}

function rd_can_manage_retiradas() {
    return current_user_can( 'manage_options' ) || current_user_can( 'rd_gerenciar_retiradas' );
}

function rd_can_manage_cardapio() {
    return current_user_can( 'manage_options' ) || current_user_can( 'rd_gerenciar_cardapio' );
}

function rd_can_view_reports() {
    return current_user_can( 'manage_options' ) ||
           current_user_can( 'rd_gerenciar_retiradas' ) ||
           current_user_can( 'rd_gerenciar_cardapio' );
}

function rd_can_send_reports() {
    return current_user_can( 'manage_options' ) || current_user_can( 'rd_gerenciar_retiradas' );
}

function rd_permission_send_reports() {
    if ( rd_can_send_reports() ) {
        return true;
    }
    return new WP_Error( 'rd_forbidden', 'Sem permissão para enviar relatórios por e-mail.', [ 'status' => 403 ] );
}

function rd_is_internal_user() {
    return rd_is_admin_user() || rd_can_manage_retiradas() || rd_can_manage_cardapio();
}

function rd_permission_public( WP_REST_Request $req ) {
    if ( rd_is_internal_user() ) {
        return true;
    }
    if ( function_exists('rd_require_auth') ) {
        $auth = rd_require_auth( $req );
        return is_wp_error( $auth ) ? $auth : true;
    }
    return new WP_Error( 'rd_auth_missing', 'Autenticação não configurada.', [ 'status' => 401 ] );
}

function rd_permission_admin_only() {
    if ( rd_is_admin_user() ) {
        return true;
    }
    return new WP_Error( 'rd_forbidden', 'Acesso restrito a administradores.', [ 'status' => 403 ] );
}

function rd_permission_retiradas() {
    if ( rd_can_manage_retiradas() ) {
        return true;
    }
    return new WP_Error( 'rd_forbidden', 'Sem permissão para gerenciar retiradas.', [ 'status' => 403 ] );
}

function rd_permission_reports() {
    if ( rd_can_view_reports() ) {
        return true;
    }
    return new WP_Error( 'rd_forbidden', 'Sem permissão para ver relatórios.', [ 'status' => 403 ] );
}

add_action( 'rest_api_init', function() {

    register_rest_route( 'rd/v1', '/refeicoes', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => 'rd_permission_public',
            'callback'            => 'rd_rest_list_refeicoes',
            'args' => [
                'status'         => [ 'type' => 'string', 'enum' => [ 'ativo', 'confirmado', 'cancelado', 'solicitado', 'all' ], 'required' => false ],
                'data'           => [ 'required' => false, 'type' => 'string' ],
                'tipo'           => [ 'required' => false, 'type' => 'string' ],
                'categoria'      => [ 'required' => false, 'type' => 'string' ],
                'matricula'      => [ 'required' => false, 'type' => 'string' ],
                'local_retirada' => [ 'required' => false, 'type' => 'string' ],
                'page'           => [ 'required' => false, 'type' => 'integer', 'minimum' => 1 ],
                'limit'          => [ 'required' => false, 'type' => 'integer', 'minimum' => 1, 'maximum' => 500 ],
            ],
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'permission_callback' => 'rd_permission_public',
            'callback'            => 'rd_rest_create_refeicao',
            'args' => [
                'nome_completo'  => [ 'required' => true, 'type' => 'string' ],
                'data_refeicao'  => [ 'required' => true, 'type' => 'string' ],
                'refeicao'       => [ 'required' => true, 'type' => 'string' ],
                'categoria'      => [ 'required' => true, 'type' => 'string' ],
                'local_retirada' => [ 'required' => false, 'type' => 'string' ],
            ],
        ]
    ] );

    register_rest_route( 'rd/v1', '/refeicoes/(?P<id>\d+)', [
        'methods'             => WP_REST_Server::EDITABLE,
        'permission_callback' => 'rd_permission_public',
        'callback'            => 'rd_rest_update_refeicao',
        'args' => [
            'id' => [ 'required' => true, 'type' => 'integer' ],
        ],
    ] );

    register_rest_route( 'rd/v1', '/relatorios', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => 'rd_permission_reports',
            'callback'            => 'rd_rest_relatorios_csv',
        ],
    ] );

    register_rest_route( 'rd/v1', '/relatorios/send', [
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'permission_callback' => 'rd_permission_send_reports',
            'callback'            => 'rd_rest_send_report',
            'args' => [
                'data'     => [ 'required' => false, 'type' => 'string' ],
                'tipo'     => [ 'required' => false, 'type' => 'string' ],
                'status'   => [ 'required' => false, 'type' => 'string', 'enum' => [ 'all','ativo','confirmado','cancelado','solicitado' ] ],
                'emails'   => [ 'required' => false, 'type' => 'string' ],
                'matricula'=> [ 'required' => false, 'type' => 'string' ],
            ],
        ],
    ] );

    register_rest_route( 'rd/v1', '/relatorios/email', [
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'permission_callback' => 'rd_permission_send_reports',
            'callback'            => 'rd_rest_send_report',
            'args' => [
                'data'     => [ 'required' => false, 'type' => 'string' ],
                'tipo'     => [ 'required' => false, 'type' => 'string' ],
                'status'   => [ 'required' => false, 'type' => 'string', 'enum' => [ 'all','ativo','confirmado','cancelado','solicitado' ] ],
                'emails'   => [ 'required' => false, 'type' => 'string' ],
                'matricula'=> [ 'required' => false, 'type' => 'string' ],
            ],
        ],
    ] );

    register_rest_route( 'rd/v1', '/meal-types', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => 'rd_permission_public',
            'callback'            => 'rd_rest_get_meal_types',
        ],
    ] );

    register_rest_route( 'rd/v1', '/meal-categories', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => 'rd_permission_public',
            'callback'            => 'rd_rest_get_meal_categories',
        ],
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'permission_callback' => 'rd_permission_admin_only',
            'callback'            => 'rd_rest_create_meal_category',
            'args' => [
                'code'        => [ 'required' => true, 'type' => 'string' ],
                'label'       => [ 'required' => true, 'type' => 'string' ],
                'cutoff_hhmm' => [ 'required' => false, 'type' => 'string' ],
                'active'      => [ 'required' => false, 'type' => 'boolean' ],
                'sort_order'  => [ 'required' => false, 'type' => 'integer' ],
            ],
        ],
    ] );

    register_rest_route( 'rd/v1', '/meal-categories/(?P<id>\d+)', [
        [
            'methods'             => WP_REST_Server::EDITABLE,
            'permission_callback' => 'rd_permission_admin_only',
            'callback'            => 'rd_rest_update_meal_category',
            'args' => [
                'id'          => [ 'required' => true, 'type' => 'integer' ],
                'label'       => [ 'required' => false, 'type' => 'string' ],
                'cutoff_hhmm' => [ 'required' => false, 'type' => 'string' ],
                'active'      => [ 'required' => false, 'type' => 'boolean' ],
                'sort_order'  => [ 'required' => false, 'type' => 'integer' ],
            ],
        ],
        [
            'methods'             => WP_REST_Server::DELETABLE,
            'permission_callback' => 'rd_permission_admin_only',
            'callback'            => 'rd_rest_delete_meal_category',
            'args' => [
                'id' => [ 'required' => true, 'type' => 'integer' ],
            ],
        ],
    ] );

    register_rest_route( 'rd/v1', '/meal-categories/cutoffs', [
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'permission_callback' => 'rd_permission_admin_only',
            'callback'            => 'rd_rest_update_category_cutoffs',
            'args' => [
                'categories' => [ 'required' => true, 'type' => 'array' ],
            ],
        ],
    ] );

    register_rest_route( 'rd/v1', '/refeicao/form-data', [
        [
            'methods'             => WP_REST_Server::READABLE,
            'permission_callback' => 'rd_permission_public',
            'callback'            => 'rd_rest_get_form_data',
        ],
    ] );

    register_rest_route( 'rd/v1', '/retiradas/marcar', [
        [
            'methods'             => WP_REST_Server::CREATABLE,
            'permission_callback' => 'rd_permission_retiradas',
            'callback'            => 'rd_rest_marcar_retirada',
            'args' => [
                'id' => [ 'required' => true, 'type' => 'integer' ],
            ],
        ],
    ] );

});

function rd_rest_list_refeicoes( WP_REST_Request $req ) {
    $valid_status = [ 'ativo', 'confirmado', 'cancelado', 'solicitado', 'all' ];
    $status       = sanitize_key( (string) $req->get_param('status') );
    $is_internal  = rd_is_internal_user();

    if ( ! in_array( $status, $valid_status, true ) ) {
        $status = $is_internal ? '' : 'solicitado';
    }

    if ( $is_internal && $status === 'all' ) {
        $status = '';
    }

    $data      = sanitize_text_field( (string) $req->get_param('data') );
    $tipo      = sanitize_text_field( (string) $req->get_param('tipo') );
    $categoria = sanitize_key( (string) $req->get_param('categoria') );
    $matricula = sanitize_text_field( (string) $req->get_param('matricula') );

    $local_param = $req->get_param('local_retirada');
    $local = null;
    if ( $local_param !== null ) {
        $local = function_exists('rd_local_retirada_sanitize')
            ? rd_local_retirada_sanitize( $local_param )
            : sanitize_key( (string) $local_param );
    }

    if ( $is_internal ) {
        $page   = max( 1, (int) $req->get_param('page') );
        $limit  = min( 500, max( 1, (int) $req->get_param('limit') ?: 20 ) );
        $offset = ($page - 1) * $limit;

        if ( $req->get_param('page') !== null || $req->get_param('limit') !== null ) {
            $args = [
                'data'           => $data,
                'tipo'           => $tipo,
                'categoria'      => $categoria,
                'matricula'      => $matricula,
                'status'         => $status,
                'local_retirada' => $local,
                'limit'          => $limit,
                'offset'         => $offset,
            ];

            $rows  = rd_refeicao_list_admin( $args );
            $total = function_exists('rd_refeicao_count_admin')
                ? (int) rd_refeicao_count_admin( array_diff_key($args, ['limit'=>0,'offset'=>0]) )
                : count( (array) $rows );

            return rest_ensure_response([
                'data' => $rows,
                'meta' => [
                    'total'    => $total,
                    'page'     => $page,
                    'per_page' => $limit,
                    'pages'    => max( 1, (int) ceil( $total / $limit ) ),
                ],
            ]);
        }

        return rest_ensure_response(
            rd_refeicao_list_admin([
                'data'           => $data,
                'tipo'           => $tipo,
                'categoria'      => $categoria,
                'matricula'      => $matricula,
                'status'         => $status,
                'local_retirada' => $local,
            ])
        );
    }

    $mat = function_exists('rd_current_matricula') ? rd_current_matricula() : '';
    if ( ! $mat ) {
        return new WP_Error( 'rd_cookie', 'Matrícula ausente', [ 'status' => 401 ] );
    }

    return rest_ensure_response(
        rd_refeicao_list_user( $mat, [
            'data'      => $data,
            'status'    => $status ?: 'solicitado',
            'matricula' => $mat,
        ])
    );
}

function rd_rest_create_refeicao( WP_REST_Request $req ) {
    $mat = function_exists('rd_current_matricula') ? rd_current_matricula() : '';
    if ( ! $mat ) {
        return new WP_Error('rd_cookie', 'Matrícula ausente', ['status'=>401]);
    }

    $nome = sanitize_text_field( (string) $req->get_param( 'nome_completo' ) );
    $data_raw = $req->get_param( 'data_refeicao' );
    $data = function_exists('rd_sanitize_ymd')
        ? rd_sanitize_ymd( $data_raw )
        : ( preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$data_raw) ? (string)$data_raw : '' );
    $tipo = sanitize_text_field( (string) $req->get_param( 'refeicao' ) );
    $categoria = sanitize_key( (string) $req->get_param( 'categoria' ) );

    $local_param = $req->get_param( 'local_retirada' );
    $local = null;
    if ( $local_param !== null ) {
        $local = function_exists('rd_local_retirada_sanitize')
            ? rd_local_retirada_sanitize( $local_param )
            : sanitize_key( (string) $local_param );
    }

    if ( $nome === '' || $nome === '—' || $nome === '-' ) {
        return new WP_Error( 'rd_nome_obrigatorio', 'Informe seu nome completo', ['status'=>422] );
    }
    if ( ! $data ) {
        return new WP_Error( 'rd_invalid_date', 'Data inválida. Use YYYY-MM-DD.', ['status'=>422] );
    }
    if ( ! $categoria ) {
        return new WP_Error( 'rd_categoria_obrigatoria', 'Selecione uma refeição (Almoço, Jantar ou Ceia).', ['status'=>422] );
    }
    if ( function_exists('rd_category_exists') && ! rd_category_exists($categoria) ) {
        return new WP_Error( 'rd_categoria_invalida', 'Refeição inválida.', ['status'=>422] );
    }

    $allowed = function_exists('rd_refeicao_allowed_types')
        ? rd_refeicao_allowed_types()
        : [ 'Qveggie','Qlight','Qsabor' ];

    if ( ! in_array( $tipo, $allowed, true ) ) {
        return new WP_Error( 'rd_invalid_enum', 'Tipo de refeição inválido.', ['status'=>422] );
    }

    if ( function_exists('rd_is_meal_type_allowed_for_date') && ! rd_is_meal_type_allowed_for_date( $tipo, $data ) ) {
        return new WP_Error( 'rd_tipo_dia_invalido', 'Este cardápio não está disponível para o dia selecionado.', ['status'=>422] );
    }

    if ( function_exists('rd_is_date_available_for_category') && ! rd_is_date_available_for_category($data, $categoria) ) {
        return new WP_Error( 'rd_cutoff_categoria', 'Horário limite para esta refeição já passou.', ['status'=>422] );
    }

    $id = rd_refeicao_create( $mat, $nome, $data, $tipo, $local, $categoria );

    if ( is_wp_error( $id ) ) return $id;

    return rest_ensure_response([ 'id' => (int) $id ]);
}

function rd_rest_update_refeicao( WP_REST_Request $req ) {
    global $wpdb;

    $id   = (int) $req['id'];
    $body = $req->get_json_params();
    if ( ! is_array( $body ) ) $body = [];
    $action = isset( $body['action'] ) ? sanitize_key( $body['action'] ) : '';

    $table = rd_db_table_full();
    $row   = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$table} WHERE id=%d", $id), ARRAY_A );
    $row_before = $row;

    if ( ! $row ) {
        return new WP_Error('rd_not_found', 'Registro não encontrado.', ['status' => 404]);
    }

    $is_internal = rd_is_internal_user();
    $matricula   = function_exists('rd_current_matricula') ? rd_current_matricula() : '';

    if ( $action === 'editar' ) {
        if ( ! $is_internal ) {
            if ( ! function_exists('rd_is_authenticated') || ! rd_is_authenticated() ) {
                return new WP_Error('rd_unauthorized', 'Não autenticado.', ['status' => 401]);
            }
            if ( $row['matricula'] !== $matricula ) {
                return new WP_Error('rd_forbidden', 'Sem permissão para editar este registro.', ['status' => 403]);
            }
        }

        if ( (int) $row['retirado'] === 1 || strtolower((string)$row['status']) !== 'ativo' ) {
            return new WP_Error('rd_unprocessable', 'Só é permitido editar pedidos ativos e não retirados.', ['status' => 422]);
        }

        $new_tipo = array_key_exists('refeicao', $body) ? sanitize_text_field($body['refeicao']) : $row['refeicao'];
        $new_data_raw = array_key_exists('data_refeicao', $body) ? $body['data_refeicao'] : $row['data_refeicao'];
        $new_data = function_exists('rd_sanitize_ymd') 
            ? rd_sanitize_ymd( $new_data_raw )
            : ( preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$new_data_raw) ? (string)$new_data_raw : '' );

        $allowed = function_exists('rd_refeicao_allowed_types')
            ? rd_refeicao_allowed_types()
            : ['Qveggie','Qlight','Qsabor'];

        if ( ! in_array( $new_tipo, $allowed, true ) ) {
            return new WP_Error('rd_invalid_enum', 'Tipo de refeição inválido.', ['status' => 422]);
        }
        if ( ! $new_data ) {
            return new WP_Error('rd_invalid_date', 'Data inválida. Use YYYY-MM-DD.', ['status' => 422]);
        }

        if ( function_exists('rd_is_meal_type_allowed_for_date') && ! rd_is_meal_type_allowed_for_date( $new_tipo, $new_data ) ) {
            return new WP_Error('rd_tipo_dia_invalido', 'Este cardápio não está disponível para o dia selecionado.', ['status' => 422]);
        }

        $tz       = wp_timezone();
        $hoje     = new DateTimeImmutable('today', $tz);
        $d        = DateTimeImmutable::createFromFormat('Y-m-d', $new_data, $tz);
        $max_dias = (int) get_option( defined('RD_OPT_WINDOW_DAYS') ? RD_OPT_WINDOW_DAYS : 'rd_window_days', 0 );

        if ( $d < $hoje ) {
            return new WP_Error('rd_past_date', 'Não é permitido alterar para data passada.', ['status' => 422]);
        }
        if ( $d > $hoje->modify("+{$max_dias} days") ) {
            return new WP_Error('rd_range', 'Data fora da janela permitida.', ['status' => 422]);
        }
        $categoria = $row['categoria'] ?? '';
        if ( ! $is_internal && function_exists('rd_cutoff_permite_categoria') && ! rd_cutoff_permite_categoria($d, $categoria) ) {
            return new WP_Error('rd_cutoff', 'Fora do horário permitido para alteração.', ['status' => 422]);
        }

        $dup = (int) $wpdb->get_var( $wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE matricula=%s AND data_refeicao=%s AND categoria=%s AND id<>%d",
            $row['matricula'], $new_data, $categoria, $id
        ));
        if ( $dup ) {
            return new WP_Error('rd_conflict', 'Já existe um pedido para esta data e categoria.', ['status' => 409]);
        }

        $ok = $wpdb->update(
            $table,
            [
                'refeicao'      => $new_tipo,
                'data_refeicao' => $new_data,
                'updated_at'    => current_time('mysql'),
            ],
            ['id' => $id],
            ['%s','%s','%s'],
            ['%d']
        );

        if ( $ok === false ) {
            if ( function_exists('rd_log') ) rd_log('DB error editar: ' . $wpdb->last_error);
            return new WP_Error('rd_db', 'Falha ao atualizar.', ['status' => 500]);
        }

        $out = $wpdb->get_row( $wpdb->prepare("SELECT * FROM {$table} WHERE id=%d", $id), ARRAY_A );

        if ( function_exists('rd_refeicoes_log_write') ) {
            rd_refeicoes_log_write( $out ?: $row_before, 'edit', [
                'before' => [ 'data_refeicao' => $row_before['data_refeicao'], 'refeicao' => $row_before['refeicao'] ],
                'after'  => [ 'data_refeicao' => $new_data, 'refeicao' => $new_tipo ]
            ]);
        }

        return new WP_REST_Response( $out, 200 );
    }

    if ( $action === 'cancelar' ) {
        if ( function_exists('rd_require_auth') ) {
            $auth = rd_require_auth( $req );
            if ( is_wp_error( $auth ) ) return $auth;
        }
        $mat = function_exists('rd_current_matricula') ? rd_current_matricula() : '';
        if ( ! $mat ) {
            return new WP_Error('rd_cookie', 'Matrícula ausente', ['status'=>401]);
        }

        $res = rd_refeicao_cancel( $id, $mat );
        if ( is_wp_error( $res ) ) return $res;

        return rest_ensure_response( [ 'deleted' => true, 'id' => $id ] );
    }

    if ( $action === 'retirado' ) {
        if ( ! rd_can_manage_retiradas() ) {
            return new WP_Error( 'rd_forbidden', 'Permissão negada. Apenas o restaurante pode marcar retiradas.', [ 'status' => 403 ] );
        }
        
        $res = rd_refeicao_mark_retirado( $id );
        if ( is_wp_error( $res ) ) return $res;
        
        return rest_ensure_response([ 'ok' => true, 'id' => $id ]);
    }

    return new WP_Error('rd_invalid_action', 'Ação não reconhecida.', ['status' => 400]);
}

function rd_rest_marcar_retirada( WP_REST_Request $req ) {
    $id = (int) $req->get_param('id');
    
    if ( ! $id ) {
        return new WP_Error('rd_invalid_id', 'ID inválido.', ['status' => 422]);
    }
    
    $res = rd_refeicao_mark_retirado( $id );
    if ( is_wp_error( $res ) ) return $res;
    
    return rest_ensure_response([ 
        'ok'      => true, 
        'id'      => $id,
        'message' => 'Retirada registrada com sucesso.'
    ]);
}

function rd_rest_get_meal_types() {
    if ( function_exists('rd_get_meal_types_enum') ) {
        return rest_ensure_response([ 'items' => rd_get_meal_types_enum() ]);
    }

    if ( function_exists('rd_get_meal_types_codes') ) {
        return rest_ensure_response([ 'items' => rd_get_meal_types_codes() ]);
    }

    return rest_ensure_response([ 'items' => ['QVeggie','QLight','QSabor'] ]);
}

function rd_rest_get_form_data( WP_REST_Request $req ) {
    $mat = function_exists('rd_current_matricula') ? rd_current_matricula() : '';
    if ( ! $mat ) {
        return new WP_Error('rd_cookie', 'Matrícula ausente', ['status'=>401]);
    }

    $nome = function_exists('rd_user_known_name') ? rd_user_known_name( $mat ) : '';

    $types = function_exists('rd_get_meal_types_codes')
        ? rd_get_meal_types_codes()
        : ['QVeggie','QLight','QSabor'];

    $local_opts = function_exists('rd_local_retirada_options') ? rd_local_retirada_options() : [];
    $default_local = '';
    
    if ( ! empty($local_opts) && function_exists('rd_db_table_full') ) {
        global $wpdb;
        $table = rd_db_table_full();
        if ( $table ) {
            $last = $wpdb->get_var( $wpdb->prepare(
                "SELECT local_retirada FROM {$table} WHERE matricula = %s AND local_retirada <> '' ORDER BY updated_at DESC, id DESC LIMIT 1",
                $mat
            ) );
            $last = function_exists('rd_local_retirada_sanitize') 
                ? rd_local_retirada_sanitize( $last ) 
                : sanitize_key( (string) $last );
            if ( $last && isset($local_opts[$last]) ) {
                $default_local = $last;
            }
        }
    }

    $categories = function_exists('rd_get_categories_for_frontend')
        ? rd_get_categories_for_frontend()
        : [];

    return rest_ensure_response([
        'matricula'              => $mat,
        'nome_completo'          => $nome,
        'meal_types'             => $types,
        'meal_categories'        => $categories,
        'local_retirada_options' => $local_opts,
        'local_retirada'         => $default_local,
    ]);
}

function rd_rest_send_report( WP_REST_Request $req ) {
    $data      = sanitize_text_field( (string) $req->get_param('data') );
    $tipo      = sanitize_text_field( (string) $req->get_param('tipo') );
    $status    = sanitize_key( (string) ( $req->get_param('status') ?: 'solicitado' ) );
    $matricula = sanitize_text_field( (string) $req->get_param('matricula') );
    $emails    = $req->get_param('emails');
    $emails    = is_string($emails) ? sanitize_text_field($emails) : null;

    $res = rd_send_report_now( $data, $tipo ?: null, $status, $emails, $matricula ?: null );
    if ( is_wp_error($res) ) return $res;
    
    return rest_ensure_response( $res );
}

if ( ! function_exists('rd_sanitize_ymd') ) {
    function rd_sanitize_ymd( $v ) {
        $s = trim( (string) $v );
        if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ) return $s;
        if ( preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m) ) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        return '';
    }
}

if ( ! function_exists('rd_cutoff_permite') ) {
    function rd_cutoff_permite( DateTimeImmutable $data ) {
        $tz  = wp_timezone();
        $cut = function_exists('rd_get_cutoff_hhmm')
            ? rd_get_cutoff_hhmm()
            : get_option( defined('RD_OPT_CUTOFF_HHMM') ? RD_OPT_CUTOFF_HHMM : 'rd_cutoff_hhmm', '' );
        $now    = new DateTimeImmutable('now', $tz);
        $cut_dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $data->format('Y-m-d') . ' ' . $cut, $tz);
        if ( $cut_dt === false ) return false;
        return $now <= $cut_dt;
    }
}

function rd_rest_get_meal_categories() {
    if ( function_exists('rd_get_categories_for_frontend') ) {
        return rest_ensure_response([ 'items' => rd_get_categories_for_frontend() ]);
    }
    return rest_ensure_response([ 'items' => [] ]);
}

function rd_rest_create_meal_category( WP_REST_Request $req ) {
    if ( ! function_exists('rd_insert_category') ) {
        return new WP_Error('rd_not_available', 'Funcionalidade não disponível.', ['status' => 500]);
    }
    $result = rd_insert_category([
        'code'        => $req->get_param('code'),
        'label'       => $req->get_param('label'),
        'cutoff_hhmm' => $req->get_param('cutoff_hhmm') ?: '',
        'active'      => $req->get_param('active') !== false,
        'sort_order'  => (int) $req->get_param('sort_order'),
    ]);
    if ( is_wp_error($result) ) return $result;
    return rest_ensure_response([ 'id' => $result ]);
}

function rd_rest_update_meal_category( WP_REST_Request $req ) {
    if ( ! function_exists('rd_update_category') ) {
        return new WP_Error('rd_not_available', 'Funcionalidade não disponível.', ['status' => 500]);
    }
    $id = (int) $req['id'];
    $data = [];
    if ( $req->get_param('label') !== null ) $data['label'] = $req->get_param('label');
    if ( $req->get_param('cutoff_hhmm') !== null ) $data['cutoff_hhmm'] = $req->get_param('cutoff_hhmm');
    if ( $req->get_param('active') !== null ) $data['active'] = $req->get_param('active');
    if ( $req->get_param('sort_order') !== null ) $data['sort_order'] = $req->get_param('sort_order');

    $result = rd_update_category($id, $data);
    if ( is_wp_error($result) ) return $result;
    return rest_ensure_response([ 'ok' => $result ]);
}

function rd_rest_delete_meal_category( WP_REST_Request $req ) {
    if ( ! function_exists('rd_delete_category') ) {
        return new WP_Error('rd_not_available', 'Funcionalidade não disponível.', ['status' => 500]);
    }
    $id = (int) $req['id'];
    $ok = rd_delete_category($id);
    return rest_ensure_response([ 'deleted' => $ok ]);
}

function rd_rest_update_category_cutoffs( WP_REST_Request $req ) {
    if ( ! function_exists('rd_update_category') ) {
        return new WP_Error('rd_not_available', 'Funcionalidade não disponível.', ['status' => 500]);
    }

    $categories = $req->get_param('categories');
    if ( ! is_array($categories) ) {
        return new WP_Error('rd_invalid_data', 'Dados inválidos.', ['status' => 422]);
    }

    $updated = 0;
    $errors = [];

    foreach ( $categories as $cat ) {
        if ( ! isset($cat['id']) ) continue;

        $id = (int) $cat['id'];
        $cutoff = isset($cat['cutoff_hhmm']) ? trim((string) $cat['cutoff_hhmm']) : '';

        if ( $cutoff !== '' && ! preg_match('/^\d{2}:\d{2}$/', $cutoff) ) {
            $errors[] = "ID {$id}: formato inválido";
            continue;
        }

        $result = rd_update_category($id, ['cutoff_hhmm' => $cutoff]);
        if ( is_wp_error($result) ) {
            $errors[] = "ID {$id}: " . $result->get_error_message();
        } else {
            $updated++;
        }
    }

    if ( ! empty($errors) ) {
        return new WP_Error('rd_partial_error', implode('; ', $errors), ['status' => 422]);
    }

    return rest_ensure_response(['ok' => true, 'updated' => $updated]);
}