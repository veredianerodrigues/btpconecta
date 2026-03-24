<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

function rd_get_window_days(): int { return (int) get_option( RD_OPT_WINDOW_DAYS, 0 ); }
function rd_get_cutoff_hhmm(): string { return (string) get_option( RD_OPT_CUTOFF_HHMM, '' ); }
function rd_get_date_start(): string { return (string) get_option( defined('RD_OPT_DATE_START') ? RD_OPT_DATE_START : 'rd_date_start', '' ); }
function rd_get_date_end(): string { return (string) get_option( defined('RD_OPT_DATE_END') ? RD_OPT_DATE_END : 'rd_date_end', '' ); }

function rd_can_schedule_date( DateTimeImmutable $date ): bool {
    $tz = wp_timezone();
    $now = new DateTimeImmutable('now', $tz);
    $today = $now->setTime(0, 0, 0);
    $d = $date->format('Y-m-d');

    if ( $date < $today ) {
        return false;
    }

    $date_start = rd_get_date_start();
    $date_end = rd_get_date_end();

    if ( $date_start && $date_end ) {
        $start_dt = DateTimeImmutable::createFromFormat('Y-m-d', $date_start, $tz);
        $end_dt = DateTimeImmutable::createFromFormat('Y-m-d', $date_end, $tz);

        if ( ! $start_dt || ! $end_dt ) return false;

        $effective_start = ($start_dt < $today) ? $today : $start_dt;

        return ($date >= $effective_start) && ($date <= $end_dt);
    }

    $is_admin = current_user_can('manage_options');
    $window_days = $is_admin ? 30 : rd_get_window_days();

    $start = $today->format('Y-m-d');
    $end   = $today->modify('+' . $window_days . ' days')->format('Y-m-d');

    return ($d >= $start) && ($d <= $end);
}

function rd_can_cancel_until( DateTimeImmutable $date, string $categoria = '' ): bool {
    $cutoff = $categoria ? rd_get_cutoff_for_category($categoria) : rd_get_cutoff_hhmm();
    [$h, $m] = rd_parse_hhmm( $cutoff );
    $same_day = $categoria && function_exists('rd_is_same_day_category') && rd_is_same_day_category($categoria);
    $limit = $same_day ? $date->setTime($h, $m) : $date->modify('-1 day')->setTime($h, $m);
    return rd_now_wp() <= $limit;
}

function rd_can_mark_retirado( DateTimeImmutable $date ): bool {
    $now = rd_now_wp();
    return $date->format('Y-m-d') === $now->format('Y-m-d');
}

function rd_cutoff_permite_categoria( DateTimeImmutable $data, string $categoria ): bool {
    $cutoff = rd_get_cutoff_for_category($categoria);
    if ( empty($cutoff) ) return true;
    [$h, $m] = rd_parse_hhmm($cutoff);
    $same_day = $categoria && function_exists('rd_is_same_day_category') && rd_is_same_day_category($categoria);
    $limit = $same_day ? $data->setTime($h, $m) : $data->modify('-1 day')->setTime($h, $m);
    return rd_now_wp() <= $limit;
}

function rd_is_date_available_for_category( string $date_ymd, string $categoria ): bool {
    $tz = wp_timezone();
    $date = DateTimeImmutable::createFromFormat('Y-m-d', $date_ymd, $tz);
    if ( ! $date ) return false;
    $date = $date->setTime(0, 0, 0);
    if ( ! rd_can_schedule_date($date) ) return false;
    return rd_cutoff_permite_categoria($date, $categoria);
}
