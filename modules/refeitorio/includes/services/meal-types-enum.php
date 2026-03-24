<?php
if ( ! defined('ABSPATH') ) exit;

if ( ! function_exists('rd_get_meal_types_enum') ) {
function rd_get_meal_types_enum(): array {
    static $cache = null;
    if ( $cache !== null ) return $cache;

    $cache = [
        [
            'code'  => 'QSabor',
            'label' => 'QSabor',
            'days'  => [5],
        ],
        [
            'code'  => 'QVeggie',
            'label' => 'QVeggie',
            'days'  => [1, 2, 3, 4, 5],
        ],
        [
            'code'  => 'QLightCarne',
            'label' => 'QLight Carne',
            'days'  => [0, 1, 2, 3, 4, 5, 6],
        ],
        [
            'code'  => 'QLightFrango',
            'label' => 'QLight Frango',
            'days'  => [0, 1, 2, 3, 4, 5, 6],
        ],
    ];

    return $cache;
}}

if ( ! function_exists('rd_is_meal_type_allowed_for_date') ) {
function rd_is_meal_type_allowed_for_date( string $tipo, string $data_ymd ): bool {
    $tipo = sanitize_text_field( $tipo );
    $data_ymd = sanitize_text_field( $data_ymd );

    if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_ymd) ) {
        return false;
    }

    $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('America/Sao_Paulo');
    $date = DateTimeImmutable::createFromFormat( 'Y-m-d', $data_ymd, $tz );

    if ( ! $date ) {
        return false;
    }

    $day_of_week = (int) $date->format( 'w' );
    $enum = rd_get_meal_types_enum();

    foreach ( $enum as $item ) {
        if ( $item['code'] === $tipo ) {
            return in_array( $day_of_week, $item['days'], true );
        }
    }

    return false;
}}

if ( ! function_exists('rd_get_meal_types_codes') ) {
function rd_get_meal_types_codes(): array {
    return array_column( rd_get_meal_types_enum(), 'code' );
}}

if ( ! function_exists('rd_get_meal_types_for_date') ) {
function rd_get_meal_types_for_date( string $data_ymd ): array {
    $data_ymd = sanitize_text_field( $data_ymd );

    if ( ! preg_match('/^\d{4}-\d{2}-\d{2}$/', $data_ymd) ) {
        return [];
    }

    $tz = function_exists('wp_timezone') ? wp_timezone() : new DateTimeZone('America/Sao_Paulo');
    $date = DateTimeImmutable::createFromFormat( 'Y-m-d', $data_ymd, $tz );

    if ( ! $date ) {
        return [];
    }

    $day_of_week = (int) $date->format( 'w' );
    $enum = rd_get_meal_types_enum();

    return array_values( array_filter( $enum, fn( $item ) => in_array( $day_of_week, $item['days'], true ) ) );
}}
