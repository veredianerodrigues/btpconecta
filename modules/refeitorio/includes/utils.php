<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Utils globais do Refeitório Digital
 * - Helpers de log/tempo/BD
 * - Sanitização de dados (data YMD, matrícula, tipos)
 * - Suporte a CSV e PDF (tempnam com extensão, conversão de charset)
 *
 * Obs.: todas as funções são protegidas com function_exists para não colidir.
 */

/* ------------------------------------------------------------------ */
/* Log                                                                */
/* ------------------------------------------------------------------ */
if ( ! function_exists('rd_log') ) {
function rd_log( string $msg, array $context = [] ): void {
    if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
        $prefix = '[RM] ';
        if ( ! empty( $context ) ) {
            $msg .= ' | ' . wp_json_encode( $context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
        }
        error_log( $prefix . $msg );
    }
}}

/* ------------------------------------------------------------------ */
/* DB helpers                                                         */
/* ------------------------------------------------------------------ */
if ( ! function_exists('rd_db_table_full') ) {
function rd_db_table_full(): string {
    global $wpdb;
    return $wpdb->prefix . RD_DB_TABLE; 
}}

if ( ! function_exists('rd_db_log_table_full') ) {
function rd_db_log_table_full(): string {
    global $wpdb;
    return $wpdb->prefix . RD_DB_TABLE . '_log'; 
}}

if ( ! function_exists('rd_tokens_table_full') ) {
function rd_tokens_table_full(): string {
    global $wpdb;

    $prefixed = $wpdb->prefix . RD_TOKENS_TABLE; 
    $schema = DB_NAME;
    $exists = (int) $wpdb->get_var( $wpdb->prepare(
        "SELECT COUNT(*) FROM information_schema.TABLES
         WHERE TABLE_SCHEMA=%s AND TABLE_NAME=%s",
        $schema, $prefixed
    ) );
    if ( $exists > 0 ) return $prefixed;

    return RD_TOKENS_TABLE; 
}}

/* ------------------------------------------------------------------ */
/* Tempo / datas                                                      */
/* ------------------------------------------------------------------ */
if ( ! function_exists('rd_now_wp') ) {
function rd_now_wp(): DateTimeImmutable {
    return new DateTimeImmutable( 'now', wp_timezone() );
}}

if ( ! function_exists('rd_parse_hhmm') ) {
function rd_parse_hhmm( string $hhmm ): array {
    [$h, $m] = array_pad( array_map( 'intval', explode( ':', $hhmm ) ), 2, 0 );
    return [$h, $m];
}}

if ( ! function_exists('rd_next_time_today') ) {
function rd_next_time_today( int $hour, int $minute ): int {
    $now = rd_now_wp();
    $target = $now->setTime( $hour, $minute );
    if ( $target <= $now ) { $target = $target->modify( '+1 day' ); }
    return $target->getTimestamp();
}}

/**
 * Normaliza uma data para YYYY-MM-DD.
 * Aceita "YYYY-MM-DD" (mantém) ou "DD/MM/YYYY" (converte). Retorna null se inválida.
 */
if ( ! function_exists('rd_sanitize_ymd') ) {
function rd_sanitize_ymd( $v ): ?string {
    $s = is_string($v) ? trim($v) : '';
    if ( $s === '' ) return null;
    if ( preg_match('/^\d{4}-\d{2}-\d{2}$/', $s) ) return $s;
    if ( preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $s, $m) ) {
        return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
    }
    return null;
}}

/* ------------------------------------------------------------------ */
/* Sanitização de matrícula e tipos                                   */
/* ------------------------------------------------------------------ */
if ( ! function_exists('rd_sanitize_matricula') ) {
function rd_sanitize_matricula( $v ): string {
    $s = sanitize_text_field( (string) $v );
    if ( strpos($s, '@') !== false ) {
        $s = strstr($s, '@', true);
    }
    return preg_replace('/\s+/', '', $s);
}}

/* ------------------------------------------------------------------ */
/* CSV                                                                */
/* ------------------------------------------------------------------ */
if ( ! function_exists('rd_csv_output') ) {
function rd_csv_output( array $rows ): string {
    $fh = fopen('php://temp', 'w+'); if (!$fh) return '';
    foreach ($rows as $row) { fputcsv($fh, $row, ';', '"', '\\'); }
    rewind($fh);
    $csv = stream_get_contents($fh);
    fclose($fh);
    return $csv ?: '';
}}

/* ------------------------------------------------------------------ */
/* PDF helpers                                                        */
/* ------------------------------------------------------------------ */
/**
 * Cria arquivo temporário garantindo a extensão (ex.: .pdf).
 * Retorna caminho completo ou false em erro.
 */
if ( ! function_exists('rd_tempnam') ) {
function rd_tempnam( string $suggest = 'rd-relatorio.pdf' ) {
    if ( ! function_exists('wp_tempnam') ) {
        @require_once ABSPATH . 'wp-admin/includes/file.php';
    }
    $tmp = function_exists('wp_tempnam') ? wp_tempnam( $suggest )
                                         : @tempnam( sys_get_temp_dir(), 'rd-' );
    if ( ! $tmp ) return false;

    $ext  = pathinfo($suggest, PATHINFO_EXTENSION);
    $base = pathinfo($suggest, PATHINFO_FILENAME);
    if ($ext) {
        $dir   = dirname($tmp);
        $name  = $base . '-' . substr(md5(uniqid('', true)), 0, 6) . '.' . $ext;
        $final = $dir . DIRECTORY_SEPARATOR . $name;
        @rename($tmp, $final);
        return $final;
    }
    return $tmp;
}}

/**
 * Converte texto UTF-8 para ISO-8859-1 (compatível com FPDF).
 * Se a conversão falhar, retorna o original.
 */
if ( ! function_exists('rd_pdf_txt') ) {
function rd_pdf_txt( string $s ): string {
    if (function_exists('mb_convert_encoding')) {
        $t = @mb_convert_encoding($s, 'ISO-8859-1', 'UTF-8');
        return $t !== false ? $t : $s;
    }
    $t = @iconv('UTF-8', 'ISO-8859-1//TRANSLIT', $s);
    return $t !== false ? $t : $s;
}}

if ( ! function_exists('rd_local_retirada_options') ) {
  function rd_local_retirada_options(): array {
    $opt = get_option('rd_local_retirada_options', []);
    if ( is_array($opt) && $opt ) {
      $out = [];
      foreach ($opt as $k => $v) {
        $out[ sanitize_key((string)$k) ] = sanitize_text_field((string)$v);
      }
      return $out;
    }
    return [
      'restaurante_adm' => 'Restaurante 1º Andar',
      'restaurante_operacional' => 'Restaurante Térreo',
    ];
  }
}
if ( ! function_exists('rd_local_retirada_sanitize') ) {
  function rd_local_retirada_sanitize( $v ): string {
    $v = sanitize_key( (string) $v );
    $opts = rd_local_retirada_options();
    return array_key_exists($v, $opts) ? $v : '';
  }
}
