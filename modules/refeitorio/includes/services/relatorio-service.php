<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

if ( ! defined('FPDF_FONTPATH') ) {
    define('FPDF_FONTPATH', RD_DIR . 'includes/lib/font/');
}
if ( ! class_exists('FPDF') ) {
    require_once RD_DIR . 'includes/lib/fpdf.php';
}
if ( ! class_exists('RD_PDF') && class_exists('FPDF') ) {
    class RD_PDF extends FPDF {
        public $brand = 'BTP Conecta';
        public $generatedAt = '';
        
        function Header() {
            $this->SetFont('helvetica','B',12);
            $this->Cell(190, 6, utf8_decode($this->brand), 0, 1, 'L');
            $this->SetDrawColor(200,200,200);
            $y = $this->GetY();
            $this->Line(10, $y, 200, $y);
            $this->Ln(2);
        }
        
        function Footer() {
            $this->SetY(-15);
            $this->SetFont('helvetica','',9);
            $txt = 'Página ' . $this->PageNo() . '/{nb}' . ' • gerado em ' . $this->generatedAt;
            $this->Cell(0, 10, utf8_decode($txt), 0, 0, 'R');
        }
    }
}
if ( ! function_exists('rd_tempnam') ) {
    function rd_tempnam( string $suggest = 'rd-relatorio.csv' ) {
        if ( ! function_exists('wp_tempnam') ) {
            @require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        if ( function_exists('wp_tempnam') ) {
            return wp_tempnam( $suggest );
        }
        $dir = function_exists('get_temp_dir') ? get_temp_dir() : ( function_exists('sys_get_temp_dir') ? sys_get_temp_dir() : ABSPATH );
        $tmp = @tempnam( $dir, 'rd-' );
        if ( ! $tmp ) return false;
        $ext = pathinfo( $suggest, PATHINFO_EXTENSION );
        if ( $ext ) {
            $new = $tmp . '.' . $ext;
            @rename( $tmp, $new );
            return $new;
        }
        return $tmp;
    }
}

if ( ! function_exists('rd_relatorio_parse_date') ) {
    function rd_relatorio_parse_date( $raw ) : string {
        $v = is_string($raw) ? trim($raw) : '';
        if ( $v === '' ) return '';
        if ( preg_match('~^(\d{2})/(\d{2})/(\d{4})$~', $v, $m) ) {
            return sprintf('%04d-%02d-%02d', (int)$m[3], (int)$m[2], (int)$m[1]);
        }
        if ( preg_match('~^\d{4}-\d{2}-\d{2}$~', $v) ) {
            return $v;
        }
        return '';
    }
}
if ( ! function_exists('rd_relatorio_normalize_params') ) {
    function rd_relatorio_normalize_params( $data, $tipo, $status = 'all' ) : array {
        $tz = wp_timezone();

        $raw = is_string($data) ? trim($data) : '';
        if ($raw === '') {
            $d = function_exists('rd_now_wp')
                ? rd_now_wp()->format('Y-m-d')
                : (new DateTimeImmutable('now', $tz))->format('Y-m-d');
        } else {
            $d = rd_relatorio_parse_date( $raw );
            if ( ! $d ) {
                $d = function_exists('rd_now_wp')
                    ? rd_now_wp()->format('Y-m-d')
                    : (new DateTimeImmutable('now', $tz))->format('Y-m-d');
            }
        }

        $allowed = function_exists('rd_refeicao_allowed_types')
            ? rd_refeicao_allowed_types()
            : ['Qveggie', 'Qlight', 'Qsabor'];
        $t = ( is_string($tipo) && in_array( $tipo, $allowed, true ) ) ? $tipo : null;

        $valid_status = ['all','ativo','confirmado','cancelado','solicitado'];
        $s = is_string($status) ? strtolower($status) : 'all';
        if ( ! in_array( $s, $valid_status, true ) ) $s = 'all';

        return [ $d, $t, $s ];
    }
}

if ( ! function_exists('rd_relatorio_rows') ) {
    function rd_relatorio_rows( string $data, ?string $tipo = null, string $status = 'all', ?string $matricula = null ): array {
        list($data, $tipo, $status) = rd_relatorio_normalize_params( $data, $tipo, $status );

        $args = [
            'data' => $data,
        ];
        if ( $matricula ) $args['matricula'] = $matricula;
        if ( $tipo )   $args['tipo']   = $tipo;
        if ( $status !== 'all' ) $args['status'] = $status;

        $rows = function_exists('rd_refeicao_list_admin') ? rd_refeicao_list_admin( $args ) : [];
        if ( ! is_array($rows) ) $rows = [];

        $out   = [];
        $out[] = [ 'Matrícula', 'Nome', 'Data', 'Tipo', 'Retirado', 'Status' ];

        foreach ( $rows as $r ) {
            $matricula     = isset($r['matricula'])     ? (string)$r['matricula']     : '';
            $nome          = isset($r['nome_completo']) ? (string)$r['nome_completo'] : '';
            $data_refeicao = isset($r['data_refeicao']) ? (string)$r['data_refeicao'] : '';
            $tipo_ref      = isset($r['refeicao'])      ? (string)$r['refeicao']      : '';
            $ret           = !empty($r['retirado']) ? 'Sim' : 'Não';
            $st            = isset($r['status'])        ? (string)$r['status']        : '';

            $out[] = [ $matricula, $nome, $data_refeicao, $tipo_ref, $ret, $st ];
        }

        return apply_filters( 'rd_relatorio_rows', $out, $data, $tipo, $status );
    }
}

if ( ! function_exists('rd_relatorio_csv') ) {
    function rd_relatorio_csv( string $data, ?string $tipo = null, string $status = 'all', ?string $matricula = null ): string {
        list($data, $tipo, $status) = rd_relatorio_normalize_params( $data, $tipo, $status );
        $rows = rd_relatorio_rows( $data, $tipo, $status, $matricula );

        if ( function_exists('rd_csv_output') ) {
            return rd_csv_output( $rows );
        }
        $fp = @fopen('php://temp', 'r+');
        if ( ! $fp ) {
            $buf = '';
            foreach ( $rows as $r ) {
                $buf .= implode(';', array_map(static function($v){
                    $v = (string)$v;                    
                    $v = str_replace('"', '""', $v);
                    return '"' . $v . '"';
                }, $r)) . "\n";
            }
            return $buf;
        }

        foreach ( $rows as $r ) {
            fputcsv( $fp, $r, ';' );
        }
        rewind( $fp );
        $csv = stream_get_contents( $fp );
        fclose( $fp );
        return (string) $csv;
    }
}

if ( ! function_exists('rd_relatorio_filename') ) {
    function rd_relatorio_filename( string $data, ?string $tipo = null, string $status = 'all' ): string {
        list($data, $tipo, $status) = rd_relatorio_normalize_params( $data, $tipo, $status );
        $suf = $tipo ? "-{$tipo}" : '';
        if ( $status && $status !== 'all' ) $suf .= "-{$status}";
        return "refeitorio-{$data}{$suf}.csv";
    }
}


if ( ! function_exists('rd_relatorio_pdf') ) {
    function rd_relatorio_pdf( string $data, ?string $tipo = null, string $status = 'all', ?string $matricula = null ) {
        list($data, $tipo, $status) = rd_relatorio_normalize_params($data, $tipo, $status);

        if ( ! defined('FPDF_FONTPATH') ) {
            define('FPDF_FONTPATH', RD_DIR . 'includes/lib/font/');
        }
        if ( ! class_exists('FPDF') ) {
            require_once RD_DIR . 'includes/lib/fpdf.php';
        }
        $rows = rd_relatorio_rows($data, $tipo, $status, $matricula);
        if (empty($rows)) {
            $rows = [['Sem dados']];
        }

        $pdf = new RD_PDF('L','mm','A4');
        $pdf->AliasNbPages();
        $pdf->SetAutoPageBreak(true, 15);
        $pdf->generatedAt = (function_exists('rd_now_wp') ? rd_now_wp()->format('H:i') : (new DateTimeImmutable('now', wp_timezone()))->format('H:i'));
        $pdf->brand = 'BTP Conecta';
        $pdf->AddPage();
        $pdf->SetFont('helvetica','B',12);
        $hData = ($data !== '' ? $data : 'Todas as datas');
        $titleParts = [ 'Relatorio do Refeitorio', $hData ];
        if ($tipo)   $titleParts[] = $tipo;
        if ($status && $status !== 'all') $titleParts[] = $status;
        if ($matricula) $titleParts[] = 'Matricula: ' . $matricula;
        $titulo = implode(' - ', $titleParts);
        $pdf->Cell(0, 8, utf8_decode($titulo), 0, 1, 'L');
        $pdf->Ln(2);

        $pdf->SetFont('helvetica','B',10);
        $header = ['Nº', 'Matrícula', 'Nome', 'Data', 'Tipo', 'Retirado', 'Status'];
        $w = [15, 50, 120, 25, 30, 20, 17];

        $pdf->SetFillColor(235,235,235);
        foreach ($header as $i => $h) {
            $pdf->Cell($w[$i], 8, utf8_decode($h), 1, 0, 'C', true);
        }
        $pdf->Ln();

        $pdf->SetFont('helvetica','',9); 
        $pdf->SetFillColor(245,245,245);
        $fill = false;
        $rowHeight = 7;
        $toBR = function($ymd) {
            if (preg_match('~^(\d{4})-(\d{2})-(\d{2})$~', (string)$ymd, $m)) {
                return $m[3].'/'.$m[2].'/'.$m[1];
            }
            return (string)$ymd;
        };

        $count = 0;
        for ($i=1; $i<count($rows); $i++) {
            $cols = $rows[$i];
            $mat = isset($cols[0]) ? (string)$cols[0] : '';
            $nome= isset($cols[1]) ? (string)$cols[1] : '';
            $dat = isset($cols[2]) ? $toBR($cols[2]) : '';
            $tip = isset($cols[3]) ? (string)$cols[3] : '';
            $ret = isset($cols[4]) ? (string)$cols[4] : '';
            $sta = isset($cols[5]) ? (string)$cols[5] : '';

            $pdf->SetFont('helvetica','',9);
            $nomeWidth = $w[2];
            
            $nomeQuebrado = wordwrap(utf8_decode($nome), 30, "\n", true);
            $nomeLines = count(explode("\n", $nomeQuebrado));
            $lineH = max($rowHeight, $nomeLines * $rowHeight);

            $pdf->Cell($w[0], $lineH, $i, 1, 0, 'C', $fill);
            
            $pdf->Cell($w[1], $lineH, utf8_decode($mat), 1, 0, 'L', $fill);
            
            $x = $pdf->GetX();
            $y = $pdf->GetY();
            $pdf->MultiCell($w[2], $rowHeight, $nomeQuebrado, 1, 'L', $fill);
            $maxY = $pdf->GetY();
            $pdf->SetXY($x + $w[2], $y);
            
            $pdf->Cell($w[3], $lineH, utf8_decode($dat), 1, 0, 'C', $fill);
            
            $pdf->Cell($w[4], $lineH, utf8_decode($tip), 1, 0, 'L', $fill);
            
            $pdf->Cell($w[5], $lineH, utf8_decode($ret), 1, 0, 'C', $fill);
            
            $pdf->Cell($w[6], $lineH, utf8_decode($sta), 1, 0, 'C', $fill);
            
            $pdf->SetXY(10, $maxY);
            $fill = !$fill;
            $count++;
        }

        if ($count === 0) {
            $pdf->Cell(0, 8, utf8_decode('Sem dados.'), 1, 1, 'L');
        }

        $tmp = function_exists('wp_tempnam')
            ? wp_tempnam('rd-relatorio')
            : tempnam(sys_get_temp_dir(), 'rd-relatorio');
        if (!$tmp) {
            return new WP_Error('rd_tmp', 'Falha ao criar arquivo temporário.');
        }
        $pdfPath = preg_replace('/\.tmp$/i', '', $tmp) . '.pdf';

        $pdf->Output('F', $pdfPath);

        return $pdfPath;
    }
}

function rd_rest_relatorios_csv( WP_REST_Request $req ) {
    if ( ! current_user_can( 'manage_options' ) ) {
        return new WP_Error( 'rd_forbidden', 'Permissão negada', [ 'status' => 403 ] );
    }

    $data   = sanitize_text_field( $req->get_param('data') );
    $tipo   = sanitize_text_field( $req->get_param('tipo') );
    $status = sanitize_key(       $req->get_param('status') );
    $format = strtolower( (string) $req->get_param('format') );
    $matricula = sanitize_text_field( (string) $req->get_param('matricula') );

    list($data, $tipo, $status) = rd_relatorio_normalize_params( $data, $tipo, $status );

    if ( $format === 'pdf' ) {
        $pdf = rd_relatorio_pdf( $data, $tipo, $status, $matricula );
        if ( is_wp_error($pdf) ) return $pdf;

        $filename = preg_replace('/\.csv$/i', '.pdf', rd_relatorio_filename($data, $tipo, $status));
        $bytes = @file_get_contents($pdf);
        @unlink($pdf);

        return new WP_REST_Response( $bytes, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
        ] );
    }

    $csv      = rd_relatorio_csv( $data, $tipo, $status, $matricula ?: null );
    $filename = rd_relatorio_filename( $data, $tipo, $status );
    return new WP_REST_Response( $csv, 200, [
        'Content-Type'        => 'text/csv; charset=UTF-8',
        'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        'Cache-Control'       => 'no-store, no-cache, must-revalidate, max-age=0',
    ] );
}

if ( ! function_exists('rd_send_daily_report') ) {
    function rd_send_daily_report(): void {
        add_action('wp_mail_failed', function($wp_error){
            if ( function_exists('rd_log') ) rd_log('wp_mail_failed', [
                'error' => is_wp_error($wp_error) ? $wp_error->get_error_message() : $wp_error
            ]);
        });

        $emails_str = (string) get_option( RD_OPT_REPORT_EMAILS, '' );
        $emails_raw = array_map('trim', explode(',', $emails_str));
        $emails = array_values(array_filter($emails_raw, 'is_email'));

        $emails = apply_filters('rd_report_emails', $emails);

        if ( empty($emails) ) {
            if ( function_exists('rd_log') ) rd_log('Sem e-mails válidos para relatório diário', ['orig'=>$emails_raw]);
            return;
        }

        $tz   = wp_timezone();
        $data = function_exists('rd_now_wp')
            ? rd_now_wp()->modify('-1 day')->format('Y-m-d')
            : (new DateTimeImmutable('now', $tz))->modify('-1 day')->format('Y-m-d');

        $status = apply_filters('rd_report_daily_status', 'all');

        $rows = rd_relatorio_rows( $data, null, $status );
        if ( count($rows) <= 1 ) {
            if ( function_exists('rd_log') ) rd_log('Relatório diário não enviado: sem registros', ['data'=>$data,'status'=>$status]);
            return;
        }

        $csv = "\xEF\xBB\xBF" . rd_relatorio_csv( $data, null, $status );

        if ( ! function_exists('wp_tempnam') ) {
            @require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $tmp = function_exists('wp_tempnam') ? wp_tempnam('rd-relatorio.csv') : tempnam(sys_get_temp_dir(), 'rd-relatorio');
        if ( ! $tmp ) {
            if ( function_exists('rd_log') ) rd_log('Falha ao criar arquivo temporário para relatório diário');
            return;
        }
        $csvPath = preg_match('~\.csv$~i', $tmp) ? $tmp : ($tmp . '.csv');
        if ($csvPath !== $tmp) {
            @rename($tmp, $csvPath);
        }

        if ( file_put_contents($csvPath, $csv) === false ) {
            if ( function_exists('rd_log') ) rd_log('Falha ao escrever arquivo temporário', ['path'=>$csvPath]);
            @unlink($csvPath);
            return;
        }

        $subject = apply_filters('rd_report_subject', 'Relatório diário do refeitório — ' . $data, $data, null, $status);
        $body    = apply_filters('rd_report_body',    'Segue em anexo o relatório CSV do dia.', $data, null, $status);

        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        if ( function_exists('rd_log') ) rd_log('Debug pré-envio diário', [
            'file' => $csvPath,
            'exists' => file_exists($csvPath),
            'size' => @filesize($csvPath),
            'emails' => $emails
        ]);

        $sent = 0; $fail = 0;
        foreach ($emails as $to) {
            if ( wp_mail($to, $subject, $body, $headers, [$csvPath]) ) {
                $sent++;
            } else {
                $fail++;
                if ( function_exists('rd_log') ) rd_log('Falha no envio diário', ['email'=>$to]);
            }
        }

        @unlink($csvPath);

        if ( function_exists('rd_log') ) rd_log('Relatório diário concluído', [
            'data' => $data, 'status' => $status, 'sent' => $sent, 'fail' => $fail
        ]);
    }
}

if ( ! function_exists('rd_send_category_report') ) {
    /**
     * Envia relatório de refeições por categoria para o dia seguinte.
     * Chamado via CRON nos horários específicos de cada categoria.
     *
     * @param string $categoria Código da categoria (almoco, jantar, ceia)
     */
    function rd_send_category_report( string $categoria ): void {
        add_action('wp_mail_failed', function($wp_error){
            if ( function_exists('rd_log') ) rd_log('wp_mail_failed (category)', [
                'error' => is_wp_error($wp_error) ? $wp_error->get_error_message() : $wp_error
            ]);
        });

        $cat = function_exists('rd_get_category_by_code') ? rd_get_category_by_code($categoria) : null;
        if ( ! $cat ) {
            if ( function_exists('rd_log') ) rd_log('Categoria não encontrada para relatório', ['categoria' => $categoria]);
            return;
        }
        $cat_label = $cat['label'] ?? $categoria;

        $emails_str = (string) get_option( RD_OPT_REPORT_EMAILS, '' );
        $emails_raw = array_map('trim', explode(',', $emails_str));
        $emails = array_values(array_filter($emails_raw, 'is_email'));

        $emails = apply_filters('rd_category_report_emails', $emails, $categoria);

        if ( empty($emails) ) {
            if ( function_exists('rd_log') ) rd_log('Sem e-mails válidos para relatório de categoria', ['categoria' => $categoria, 'orig' => $emails_raw]);
            return;
        }

        $tz   = wp_timezone();
        $data = function_exists('rd_now_wp')
            ? rd_now_wp()->modify('+1 day')->format('Y-m-d')
            : (new DateTimeImmutable('now', $tz))->modify('+1 day')->format('Y-m-d');

        $args = [
            'data'      => $data,
            'categoria' => $categoria,
            'status'    => 'ativo',
        ];
        $rows_raw = function_exists('rd_refeicao_list_admin') ? rd_refeicao_list_admin($args) : [];
        if ( ! is_array($rows_raw) ) $rows_raw = [];

        $rows = [];
        $rows[] = [ 'Matrícula', 'Nome', 'Data', 'Categoria', 'Tipo', 'Retirado', 'Status' ];

        foreach ( $rows_raw as $r ) {
            $matricula     = isset($r['matricula'])     ? (string)$r['matricula']     : '';
            $nome          = isset($r['nome_completo']) ? (string)$r['nome_completo'] : '';
            $data_refeicao = isset($r['data_refeicao']) ? (string)$r['data_refeicao'] : '';
            $cat_code      = isset($r['categoria'])     ? (string)$r['categoria']     : '';
            $tipo_ref      = isset($r['refeicao'])      ? (string)$r['refeicao']      : '';
            $ret           = !empty($r['retirado']) ? 'Sim' : 'Não';
            $st            = isset($r['status'])        ? (string)$r['status']        : '';

            $rows[] = [ $matricula, $nome, $data_refeicao, $cat_code, $tipo_ref, $ret, $st ];
        }

        if ( count($rows) <= 1 ) {
            if ( function_exists('rd_log') ) rd_log('Relatório de categoria não enviado: sem registros', ['categoria' => $categoria, 'data' => $data]);
            return;
        }

        $fp = @fopen('php://temp', 'r+');
        $csv_content = '';
        if ( $fp ) {
            foreach ( $rows as $r ) {
                fputcsv( $fp, $r, ';' );
            }
            rewind( $fp );
            $csv_content = stream_get_contents( $fp );
            fclose( $fp );
        }
        $csv = "\xEF\xBB\xBF" . $csv_content;

        if ( ! function_exists('wp_tempnam') ) {
            @require_once ABSPATH . 'wp-admin/includes/file.php';
        }
        $tmp = function_exists('wp_tempnam') ? wp_tempnam('rd-categoria.csv') : tempnam(sys_get_temp_dir(), 'rd-categoria');
        if ( ! $tmp ) {
            if ( function_exists('rd_log') ) rd_log('Falha ao criar arquivo temporário para relatório de categoria', ['categoria' => $categoria]);
            return;
        }
        $csvPath = preg_match('~\.csv$~i', $tmp) ? $tmp : ($tmp . '.csv');
        if ($csvPath !== $tmp) {
            @rename($tmp, $csvPath);
        }

        if ( file_put_contents($csvPath, $csv) === false ) {
            if ( function_exists('rd_log') ) rd_log('Falha ao escrever arquivo temporário de categoria', ['path' => $csvPath]);
            @unlink($csvPath);
            return;
        }

        $data_br = preg_match('~^(\d{4})-(\d{2})-(\d{2})$~', $data, $m) ? "{$m[3]}/{$m[2]}/{$m[1]}" : $data;
        $subject = apply_filters('rd_category_report_subject', "Relatório {$cat_label} — {$data_br}", $categoria, $data);
        $body    = apply_filters('rd_category_report_body', "Segue em anexo o relatório CSV de {$cat_label} para o dia {$data_br}.", $categoria, $data);

        $headers = ['Content-Type: text/plain; charset=UTF-8'];

        if ( function_exists('rd_log') ) rd_log('Debug pré-envio categoria', [
            'categoria' => $categoria,
            'file'   => $csvPath,
            'exists' => file_exists($csvPath),
            'size'   => @filesize($csvPath),
            'emails' => $emails,
            'data'   => $data,
            'total'  => count($rows) - 1
        ]);

        $sent = 0; $fail = 0;
        foreach ($emails as $to) {
            if ( wp_mail($to, $subject, $body, $headers, [$csvPath]) ) {
                $sent++;
            } else {
                $fail++;
                if ( function_exists('rd_log') ) rd_log('Falha no envio de categoria', ['email' => $to, 'categoria' => $categoria]);
            }
        }

        @unlink($csvPath);

        if ( function_exists('rd_log') ) rd_log('Relatório de categoria concluído', [
            'categoria' => $categoria,
            'data'      => $data,
            'sent'      => $sent,
            'fail'      => $fail,
            'total'     => count($rows) - 1
        ]);
    }
}

if ( ! function_exists( 'rd_send_report_now' ) ) {

    function rd_send_report_now( string $data, ?string $tipo = null, string $status = 'all', ?string $emails_str = null, ?string $matricula = null ) {
        list( $data, $tipo, $status ) = rd_relatorio_normalize_params( $data, $tipo, $status );

        if ( $emails_str === null ) {
            $emails_str = (string) get_option( RD_OPT_REPORT_EMAILS, '' );
        }
        $emails = array_values( array_filter(
            array_map( 'trim', explode( ',', $emails_str ) ),
            'is_email'
        ) );
        if ( empty( $emails ) ) {
            return new WP_Error( 'rd_no_emails', 'Nenhum e-mail válido configurado.' );
        }

        $pdf = rd_relatorio_pdf( $data, $tipo, $status, $matricula );
        if ( is_wp_error( $pdf ) ) {
            return $pdf;
        }

        $assunto_tip = $tipo ? " — {$tipo}" : '';
        $assunto_sta = ( $status && $status !== 'all' ) ? " — {$status}" : '';
        $subject = apply_filters( 'rd_report_subject', "Relatório do refeitório — {$data}{$assunto_tip}{$assunto_sta}", $data, $tipo, $status );
        $body    = apply_filters( 'rd_report_body',    "Segue em anexo o relatório PDF.\nData: {$data}{$assunto_tip}{$assunto_sta}", $data, $tipo, $status );

        $sent = 0;
        foreach ( $emails as $to ) {
            if ( wp_mail( $to, $subject, $body, [], [ $pdf ] ) ) {
                $sent++;
            }
        }
        @unlink( $pdf );

        return [
            'sent'   => $sent,
            'emails' => $emails,
            'date'   => $data,
            'tipo'   => $tipo,
            'status' => $status,
            'format' => 'pdf',
            'matricula' => $matricula,
        ];
    }
}