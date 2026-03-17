<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — inc/admin-horario.php
 * Página de configurações para upload e gestão do CSV de horários de ônibus.
 *
 * Menu: Configurações > Horários de Ônibus
 *
 * @package btpconecta
 */

// ── Registra o item de menu no painel administrativo ─────────────────────────

add_action('admin_menu', 'btp_horario_admin_menu');

function btp_horario_admin_menu(): void {
    add_options_page(
        'Horários de Ônibus',          // <title>
        'Horários de Ônibus',          // label no menu
        'manage_options',              // capability mínima
        'btp-horarios-onibus',         // slug único
        'btp_horario_admin_page'       // callback de render
    );
}

// ── Renderiza a página de configurações ──────────────────────────────────────

function btp_horario_admin_page(): void {
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para acessar esta página.'));
    }

    $notice  = '';
    $n_type  = 'updated'; // 'updated' = verde, 'error' = vermelho

    // ── Processa o formulário de upload ──────────────────────────
    if (
        isset($_POST['btp_horario_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['btp_horario_nonce'])), 'btp_horario_upload')
    ) {
        if (
            isset($_FILES['btp_horario_csv']) &&
            $_FILES['btp_horario_csv']['error'] === UPLOAD_ERR_OK
        ) {
            $tmp_path = $_FILES['btp_horario_csv']['tmp_name'];
            $orig_name = sanitize_file_name($_FILES['btp_horario_csv']['name']);

            // Valida extensão
            $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
            if ($ext !== 'csv') {
                $notice = 'Arquivo inválido. Envie um arquivo .csv.';
                $n_type = 'error';
            } else {
                // Usa diretório temporário do sistema (sempre disponível)
                $dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . '_horario_tmp_' . uniqid() . '.csv';

                if (move_uploaded_file($tmp_path, $dest)) {
                    // Garante que a função de parse está disponível
                    if (!function_exists('btpconecta_parse_horario_csv')) {
                        require_once get_template_directory() . '/inc/parse-horario.php';
                    }

                    $data = btpconecta_parse_horario_csv($dest);

                    // Remove arquivo temporário imediatamente após o parse
                    @unlink($dest);

                    if (empty($data)) {
                        $notice = 'Não foi possível processar o CSV. Verifique o formato do arquivo.';
                        $n_type = 'error';
                    } else {
                        update_option('btp_horarios_data',    wp_json_encode($data, JSON_UNESCAPED_UNICODE));
                        update_option('btp_horarios_updated', current_time('mysql'));
                        $notice = 'Horários importados com sucesso!';
                    }
                } else {
                    $notice = 'Erro ao mover o arquivo enviado. Verifique as permissões do servidor.';
                    $n_type = 'error';
                }
            }
        } elseif (isset($_FILES['btp_horario_csv']) && $_FILES['btp_horario_csv']['error'] !== UPLOAD_ERR_NO_FILE) {
            $notice = 'Erro no upload do arquivo (código ' . intval($_FILES['btp_horario_csv']['error']) . ').';
            $n_type = 'error';
        } else {
            $notice = 'Nenhum arquivo selecionado.';
            $n_type = 'error';
        }
    }

    // ── Carrega dados atuais ──────────────────────────────────────
    $updated  = get_option('btp_horarios_updated', '');
    $raw_data = get_option('btp_horarios_data', '[]');
    $grupos   = json_decode($raw_data, true);
    if (!is_array($grupos)) {
        $grupos = [];
    }

    ?>
    <div class="wrap">
        <h1>Horários de Ônibus</h1>

        <?php if ($notice) : ?>
        <div class="notice notice-<?php echo esc_attr($n_type); ?> is-dismissible">
            <p><?php echo esc_html($notice); ?></p>
        </div>
        <?php endif; ?>

        <!-- Informações sobre os dados atuais -->
        <?php if ($updated) : ?>
        <div class="card" style="max-width:600px;margin-bottom:20px;">
            <h2 style="font-size:1rem;margin:0 0 8px;">Dados carregados</h2>
            <p><strong>Última atualização:</strong> <?php echo esc_html($updated); ?></p>
            <?php if (!empty($grupos)) : ?>
            <table class="widefat fixed striped" style="width:auto;min-width:400px;margin-top:8px;">
                <thead>
                    <tr>
                        <th>Grupo</th>
                        <th>Seção</th>
                        <th>Linhas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($grupos as $grupo) : ?>
                        <?php foreach ($grupo['secoes'] as $secao) : ?>
                        <tr>
                            <td><?php echo esc_html($grupo['nome']); ?></td>
                            <td><?php echo esc_html($secao['nome']); ?></td>
                            <td><?php echo count($secao['linhas']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
        <?php else : ?>
        <p class="description">Nenhum dado importado ainda.</p>
        <?php endif; ?>

        <!-- Formulário de upload -->
        <div class="card" style="max-width:600px;">
            <h2 style="font-size:1rem;margin:0 0 12px;">Importar novo CSV</h2>
            <p class="description" style="margin-bottom:16px;">
                Selecione o arquivo CSV exportado da planilha de horários.<br>
                <strong>Separador:</strong> ponto-e-vírgula (<code>;</code>).<br>
                A importação substitui completamente os dados anteriores.
            </p>

            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('btp_horario_upload', 'btp_horario_nonce'); ?>

                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row">
                            <label for="btp_horario_csv">Arquivo CSV</label>
                        </th>
                        <td>
                            <input
                                type="file"
                                name="btp_horario_csv"
                                id="btp_horario_csv"
                                accept=".csv,text/csv"
                                required
                            >
                            <p class="description">Formatos aceitos: .csv</p>
                        </td>
                    </tr>
                </table>

                <?php submit_button('Importar Horários', 'primary', 'btp_horario_submit'); ?>
            </form>
        </div>

        <!-- Instruções -->
        <div class="card" style="max-width:600px;margin-top:20px;">
            <h2 style="font-size:1rem;margin:0 0 8px;">Formato esperado do CSV</h2>
            <ul style="list-style:disc;padding-left:20px;">
                <li>Separador: <code>;</code> (ponto-e-vírgula)</li>
                <li>Cabeçalho de seção: linha onde a segunda coluna contém <code>TRAJETO</code></li>
                <li>Linhas de dado: <code>HORÁRIO;TRAJETO;VEÍCULOS</code></li>
                <li>Linhas vazias (<code>;;</code>) são ignoradas</li>
                <li>O arquivo deve conter exatamente <strong>9 seções</strong> (3 grupos × 3 pontos de saída)</li>
            </ul>
        </div>
    </div>
    <?php
}
