<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function rd_is_authenticated(): bool {
    if ( empty( $_COOKIE[ RD_COOKIE_TOKEN ] ) || empty( $_COOKIE[ RD_COOKIE_MATRICULA ] ) ) {
        rd_log( 'Cookies ausentes' );
        return false;
    }
    $token     = sanitize_text_field( wp_unslash( $_COOKIE[ RD_COOKIE_TOKEN ] ) );
    $matricula = sanitize_text_field( wp_unslash( $_COOKIE[ RD_COOKIE_MATRICULA ] ) );

    global $wpdb;
    $tbl = rd_tokens_table_full();

    // Verifica se a tabela existe antes de consultar
    $table_exists = $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = %s AND TABLE_NAME = %s",
        DB_NAME, $tbl
    ) );

    if ( ! $table_exists ) {
        rd_log( 'Tabela de tokens não existe', [ 'table' => $tbl ] );
        return false;
    }

    $row = $wpdb->get_row( $wpdb->prepare(
        "SELECT * FROM `$tbl` WHERE `token` = %s LIMIT 1", $token
    ) );

    if ( ! $row ) { rd_log( 'Token não encontrado' ); return false; }

    $userCol = property_exists( $row, 'user' ) ? 'user' : ( property_exists( $row, 'matricula' ) ? 'matricula' : null );
    if ( $userCol && strval( $row->$userCol ) !== $matricula ) {
        rd_log( 'Matrícula não confere', [ 'cookie' => $matricula, 'db' => $row->$userCol ] );
        return false;
    }

    $exp = ( property_exists($row, 'expira_em') && ! empty($row->expira_em) )
        ? strtotime( (string) $row->expira_em )
        : null;

    if ( $exp !== null && time() > $exp ) {
        rd_log( 'Token expirado' );
        return false;
    }

    return true;
}

function rd_require_auth( WP_REST_Request $req ) {
    if ( ! rd_is_authenticated() ) {
        return new WP_Error( 'rd_unauthorized', 'Não autenticado', [ 'status' => 401 ] );
    }
    return true;
}

function rd_current_matricula(): ?string {
    if ( empty( $_COOKIE[ RD_COOKIE_MATRICULA ] ) ) return null;
    return sanitize_text_field( wp_unslash( $_COOKIE[ RD_COOKIE_MATRICULA ] ) );
}
