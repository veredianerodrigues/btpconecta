<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( defined('WP_CLI') && WP_CLI ) {
    WP_CLI::add_command( 'rd backfill-nomes', function( $args, $assoc_args ) {
        if ( empty( $assoc_args['csv'] ) ) {
            WP_CLI::error( 'Informe --csv=/caminho/arquivo.csv' );
        }
        $csvPath = $assoc_args['csv'];
        if ( ! file_exists( $csvPath ) ) WP_CLI::error( 'Arquivo não encontrado' );

        $fh = fopen( $csvPath, 'r' );
        $count = 0; $skip = 0;
        $header = fgetcsv( $fh, 0, ';' );
        while ( ($row = fgetcsv( $fh, 0, ';' )) !== false ) {
            [$mat, $nome] = array_pad( $row, 2, '' );
            $mat = trim($mat); $nome = trim($nome);
            if ( $mat === '' || $nome === '' ) { $skip++; continue; }
            global $wpdb; $table = rd_db_table_full();
            $wpdb->query( $wpdb->prepare( "UPDATE `$table` SET nome_completo=%s WHERE matricula=%s", $nome, $mat ) );
            $count += $wpdb->rows_affected;
        }
        fclose( $fh );
        WP_CLI::success( sprintf( 'Atualizados: %d | Ignorados: %d', $count, $skip ) );
    } );
}
