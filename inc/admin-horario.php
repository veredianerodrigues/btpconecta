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
        'Horários de Ônibus',
        'Horários de Ônibus',
        'manage_options',
        'btp-horarios-onibus',
        'btp_horario_admin_page'
    );
}

// ── Helpers para diretório de backups ────────────────────────────────────────

function btp_horario_backup_dir(): string {
    return trailingslashit(wp_upload_dir()['basedir']) . 'btp-horarios';
}

function btp_horario_ensure_backup_dir(): bool {
    $dir = btp_horario_backup_dir();
    if (!is_dir($dir)) {
        wp_mkdir_p($dir);
        file_put_contents($dir . '/index.php', '<?php // Silence is golden.');
        file_put_contents($dir . '/.htaccess', 'deny from all');
    }
    return is_dir($dir) && is_writable($dir);
}

function btp_horario_list_csvs(): array {
    $dir   = btp_horario_backup_dir();
    if (!is_dir($dir)) return [];
    $files = glob($dir . '/*.csv') ?: [];
    usort($files, fn($a, $b) => filemtime($b) <=> filemtime($a));
    return $files;
}

// ── Renderiza a página de configurações ──────────────────────────────────────

function btp_horario_admin_page(): void {
    if (!current_user_can('manage_options')) {
        wp_die(__('Você não tem permissão para acessar esta página.'));
    }

    $notice = '';
    $n_type = 'updated';

    // ── 1. Upload de novo CSV ────────────────────────────────────────────────
    if (
        isset($_POST['btp_horario_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['btp_horario_nonce'])), 'btp_horario_upload')
    ) {
        if (
            isset($_FILES['btp_horario_csv']) &&
            $_FILES['btp_horario_csv']['error'] === UPLOAD_ERR_OK
        ) {
            $tmp_path  = $_FILES['btp_horario_csv']['tmp_name'];
            $orig_name = sanitize_file_name($_FILES['btp_horario_csv']['name']);
            $ext       = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

            if ($ext !== 'csv') {
                $notice = 'Arquivo inválido. Envie um arquivo .csv.';
                $n_type = 'error';
            } else {
                $tmp_dest = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'btp_' . uniqid() . '.csv';

                if (move_uploaded_file($tmp_path, $tmp_dest)) {
                    if (!function_exists('btpconecta_parse_horario_csv')) {
                        require_once get_template_directory() . '/inc/parse-horario.php';
                    }
                    $data = btpconecta_parse_horario_csv($tmp_dest);

                    if (empty($data)) {
                        @unlink($tmp_dest);
                        $notice = 'Não foi possível processar o CSV. Verifique o formato do arquivo.';
                        $n_type = 'error';
                    } else {
                        if (btp_horario_ensure_backup_dir()) {
                            $bk_name = current_time('Ymd_His') . '_' . $orig_name;
                            copy($tmp_dest, btp_horario_backup_dir() . '/' . $bk_name);
                        }
                        @unlink($tmp_dest);
                        update_option('btp_horarios_data', wp_json_encode($data, JSON_UNESCAPED_UNICODE));
                        update_option('btp_horarios_updated', current_time('mysql'));
                        $notice = 'Horários importados com sucesso!';
                    }
                } else {
                    $notice = 'Erro ao mover o arquivo enviado. Verifique as permissões do servidor.';
                    $n_type = 'error';
                }
            }
        } elseif (isset($_FILES['btp_horario_csv']) && $_FILES['btp_horario_csv']['error'] !== UPLOAD_ERR_NO_FILE) {
            $notice = 'Erro no upload (código ' . intval($_FILES['btp_horario_csv']['error']) . ').';
            $n_type = 'error';
        } else {
            $notice = 'Nenhum arquivo selecionado.';
            $n_type = 'error';
        }
    }

    // ── 2. Salvar edições manuais ────────────────────────────────────────────
    if (
        isset($_POST['btp_horario_edit_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['btp_horario_edit_nonce'])), 'btp_horario_edit')
    ) {
        $json_raw = isset($_POST['btp_horarios_json']) ? wp_unslash($_POST['btp_horarios_json']) : '';
        $data     = json_decode($json_raw, true);

        if (!is_array($data)) {
            $notice = 'Dados inválidos. Tente novamente.';
            $n_type = 'error';
        } else {
            foreach ($data as &$grupo) {
                $grupo['nome'] = sanitize_text_field($grupo['nome'] ?? '');
                foreach ($grupo['secoes'] as &$secao) {
                    $secao['nome'] = sanitize_text_field($secao['nome'] ?? '');
                    foreach ($secao['linhas'] as &$linha) {
                        $linha['horario'] = sanitize_text_field($linha['horario'] ?? '');
                        $linha['trajeto'] = sanitize_text_field($linha['trajeto'] ?? '');
                        $linha['veiculo'] = sanitize_text_field($linha['veiculo'] ?? '');
                    }
                    unset($linha);
                }
                unset($secao);
            }
            unset($grupo);

            update_option('btp_horarios_data', wp_json_encode($data, JSON_UNESCAPED_UNICODE));
            update_option('btp_horarios_updated', current_time('mysql'));
            $notice = 'Horários salvos e publicados no frontend com sucesso!';
        }
    }

    // ── 3. Restaurar a partir de CSV salvo ────────────────────────────────────
    if (
        isset($_POST['btp_horario_restore_nonce']) &&
        wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['btp_horario_restore_nonce'])), 'btp_horario_restore')
    ) {
        $filename = isset($_POST['btp_restore_file']) ? sanitize_file_name($_POST['btp_restore_file']) : '';
        $filepath = btp_horario_backup_dir() . '/' . $filename;

        if (!$filename || !file_exists($filepath)) {
            $notice = 'Arquivo não encontrado.';
            $n_type = 'error';
        } else {
            if (!function_exists('btpconecta_parse_horario_csv')) {
                require_once get_template_directory() . '/inc/parse-horario.php';
            }
            $data = btpconecta_parse_horario_csv($filepath);

            if (empty($data)) {
                $notice = 'Não foi possível processar o arquivo CSV selecionado.';
                $n_type = 'error';
            } else {
                update_option('btp_horarios_data', wp_json_encode($data, JSON_UNESCAPED_UNICODE));
                update_option('btp_horarios_updated', current_time('mysql'));
                $notice = 'Dados restaurados do arquivo: ' . $filename;
            }
        }
    }

    // ── Carrega estado atual ──────────────────────────────────────────────────
    $updated   = get_option('btp_horarios_updated', '');
    $raw_data  = get_option('btp_horarios_data', '[]');
    $grupos    = json_decode($raw_data, true);
    if (!is_array($grupos)) $grupos = [];
    $csv_files = btp_horario_list_csvs();

    ?>
    <div class="wrap">
        <h1>Horários de Ônibus</h1>

        <?php if ($notice) : ?>
        <div class="notice notice-<?php echo esc_attr($n_type); ?> is-dismissible">
            <p><?php echo esc_html($notice); ?></p>
        </div>
        <?php endif; ?>

        <!-- ══ SEÇÃO 1: UPLOAD ══════════════════════════════════════════════════ -->
        <div class="card" style="max-width:700px;margin-bottom:20px;">
            <h2 style="font-size:1rem;margin:0 0 12px;">Importar novo CSV</h2>
            <p class="description" style="margin-bottom:16px;">
                Selecione o arquivo CSV exportado da planilha de horários.<br>
                <strong>Separador:</strong> ponto-e-vírgula (<code>;</code>). A importação substitui todos os dados atuais.
            </p>
            <form method="post" enctype="multipart/form-data">
                <?php wp_nonce_field('btp_horario_upload', 'btp_horario_nonce'); ?>
                <table class="form-table" role="presentation">
                    <tr>
                        <th scope="row"><label for="btp_horario_csv">Arquivo CSV</label></th>
                        <td>
                            <input type="file" name="btp_horario_csv" id="btp_horario_csv" accept=".csv,text/csv" required>
                            <p class="description">Formatos aceitos: .csv</p>
                        </td>
                    </tr>
                </table>
                <?php submit_button('Importar Horários', 'primary', 'btp_horario_submit'); ?>
            </form>
        </div>

        <!-- ══ SEÇÃO 2: EDITOR ══════════════════════════════════════════════════ -->
        <?php if (!empty($grupos)) : ?>
        <div class="card" style="max-width:1100px;margin-bottom:20px;">
            <h2 style="font-size:1rem;margin:0 0 4px;">Editar horários</h2>
            <?php if ($updated) : ?>
            <p class="description" style="margin-bottom:16px;">
                Última atualização: <strong><?php echo esc_html($updated); ?></strong>
            </p>
            <?php endif; ?>

            <!-- Tabs de grupo (Segunda a Sexta / Sábados / Domingo) -->
            <div class="btp-tab-bar btp-group-tab-bar" role="tablist">
                <?php foreach ($grupos as $gi => $grupo) : ?>
                <button
                    type="button"
                    class="btp-tab-btn<?php echo $gi === 0 ? ' active' : ''; ?>"
                    role="tab"
                    aria-selected="<?php echo $gi === 0 ? 'true' : 'false'; ?>"
                    data-tab="btp-group-<?php echo $gi; ?>"
                ><?php echo esc_html($grupo['nome']); ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Painéis de grupo -->
            <?php foreach ($grupos as $gi => $grupo) : ?>
            <div
                id="btp-group-<?php echo $gi; ?>"
                class="btp-group-panel<?php echo $gi === 0 ? ' active' : ''; ?>"
                role="tabpanel"
                data-nome="<?php echo esc_attr($grupo['nome']); ?>"
            >
                <!-- Tabs de seção -->
                <div class="btp-tab-bar btp-section-tab-bar" role="tablist">
                    <?php foreach ($grupo['secoes'] as $si => $secao) : ?>
                    <button
                        type="button"
                        class="btp-tab-btn btp-sec-btn<?php echo $si === 0 ? ' active' : ''; ?>"
                        role="tab"
                        aria-selected="<?php echo $si === 0 ? 'true' : 'false'; ?>"
                        data-tab="btp-section-<?php echo $gi; ?>-<?php echo $si; ?>"
                    ><?php echo esc_html($secao['nome']); ?></button>
                    <?php endforeach; ?>
                </div>

                <!-- Painéis de seção -->
                <?php foreach ($grupo['secoes'] as $si => $secao) : ?>
                <div
                    id="btp-section-<?php echo $gi; ?>-<?php echo $si; ?>"
                    class="btp-section-panel<?php echo $si === 0 ? ' active' : ''; ?>"
                    data-nome="<?php echo esc_attr($secao['nome']); ?>"
                >
                    <div style="overflow-x:auto;margin-top:8px;">
                        <table class="widefat btp-edit-table">
                            <thead>
                                <tr>
                                    <th style="width:100px;">Horário</th>
                                    <th style="width:140px;">Veículo</th>
                                    <th>Trajeto</th>
                                    <th style="width:44px;"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($secao['linhas'] as $linha) : ?>
                                <tr class="btp-linha-row">
                                    <td><input type="text" class="btp-f-horario" value="<?php echo esc_attr($linha['horario']); ?>" maxlength="5" placeholder="HH:MM"></td>
                                    <td><input type="text" class="btp-f-veiculo" value="<?php echo esc_attr($linha['veiculo']); ?>"></td>
                                    <td><input type="text" class="btp-f-trajeto" value="<?php echo esc_attr($linha['trajeto']); ?>"></td>
                                    <td><button type="button" class="btp-btn-del button button-small" title="Remover">&#10005;</button></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="4" style="padding-top:8px;">
                                        <button type="button" class="btp-btn-add button button-secondary">+ Adicionar linha</button>
                                    </td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>

            </div><!-- .btp-group-panel -->
            <?php endforeach; ?>

            <!-- Formulário oculto para enviar o JSON serializado -->
            <form method="post" id="btp-edit-form" style="margin-top:20px;">
                <?php wp_nonce_field('btp_horario_edit', 'btp_horario_edit_nonce'); ?>
                <input type="hidden" name="btp_horarios_json" id="btp-horarios-json" value="">
                <p>
                    <button type="submit" class="button button-primary button-large" id="btp-save-btn">
                        Salvar e publicar no frontend
                    </button>
                    <span class="description" style="margin-left:10px;">As alterações ficam visíveis imediatamente no site.</span>
                </p>
            </form>
        </div>
        <?php endif; ?>

        <!-- ══ SEÇÃO 3: HISTÓRICO DE CSVs ════════════════════════════════════════ -->
        <div class="card" style="max-width:800px;margin-bottom:20px;">
            <h2 style="font-size:1rem;margin:0 0 8px;">Histórico de arquivos CSV</h2>
            <?php if (empty($csv_files)) : ?>
            <p class="description">
                Nenhum arquivo salvo ainda. Os arquivos são guardados automaticamente a cada importação.
            </p>
            <?php else : ?>
            <p class="description" style="margin-bottom:12px;">
                Clique em <strong>Restaurar</strong> para reverter os dados do frontend ao estado do arquivo escolhido.
            </p>
            <table class="widefat fixed striped">
                <thead>
                    <tr>
                        <th>Arquivo</th>
                        <th style="width:160px;">Data</th>
                        <th style="width:100px;"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($csv_files as $csv_path) :
                        $csv_name = basename($csv_path);
                        $csv_date = date_i18n('d/m/Y H:i', filemtime($csv_path));
                    ?>
                    <tr>
                        <td style="word-break:break-all;"><?php echo esc_html($csv_name); ?></td>
                        <td><?php echo esc_html($csv_date); ?></td>
                        <td>
                            <form method="post" style="margin:0;">
                                <?php wp_nonce_field('btp_horario_restore', 'btp_horario_restore_nonce'); ?>
                                <input type="hidden" name="btp_restore_file" value="<?php echo esc_attr($csv_name); ?>">
                                <button
                                    type="submit"
                                    class="button button-secondary button-small"
                                    onclick="return confirm('Restaurar dados do frontend a partir deste arquivo CSV?');"
                                >Restaurar</button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>

        <!-- ══ SEÇÃO 4: INSTRUÇÕES ═══════════════════════════════════════════════ -->
        <div class="card" style="max-width:700px;">
            <h2 style="font-size:1rem;margin:0 0 8px;">Formato esperado do CSV</h2>
            <ul style="list-style:disc;padding-left:20px;">
                <li>Separador: <code>;</code> (ponto-e-vírgula)</li>
                <li>Cabeçalho de seção: linha onde a segunda coluna contém <code>TRAJETO</code></li>
                <li>Linhas de dado: <code>HORÁRIO;TRAJETO;VEÍCULOS</code></li>
                <li>Linhas vazias (<code>;;</code>) são ignoradas</li>
                <li>O arquivo deve conter exatamente <strong>12 seções</strong> (3 grupos × 4 pontos de saída: Terminal, Museu Pelé, Alfândega, Armazém)</li>
            </ul>
        </div>

    </div><!-- .wrap -->

    <style>
    /* ── Tabs ─────────────────────────────────────────────── */
    .btp-tab-bar {
        display: flex;
        flex-wrap: wrap;
        gap: 4px;
        border-bottom: 2px solid #c3c4c7;
        margin-bottom: 0;
        padding-bottom: 0;
    }
    .btp-section-tab-bar {
        margin-top: 16px;
        border-bottom-color: #dcdcde;
    }
    .btp-tab-btn {
        background: #f0f0f1;
        border: 1px solid #c3c4c7;
        border-bottom: none;
        padding: 6px 14px;
        cursor: pointer;
        border-radius: 3px 3px 0 0;
        margin-bottom: -2px;
        font-size: 0.85rem;
        color: #50575e;
        transition: background 0.1s;
    }
    .btp-tab-btn:hover { background: #e0e0e0; }
    .btp-tab-btn.active {
        background: #fff;
        border-bottom: 2px solid #fff;
        font-weight: 600;
        color: #1d2327;
    }
    .btp-sec-btn { font-size: 0.8rem; padding: 4px 10px; }

    /* ── Painéis ──────────────────────────────────────────── */
    .btp-group-panel,
    .btp-section-panel { display: none; }
    .btp-group-panel.active,
    .btp-section-panel.active { display: block; }

    /* ── Tabela de edição ─────────────────────────────────── */
    .btp-edit-table td { vertical-align: middle; padding: 3px 6px; }
    .btp-edit-table .btp-f-horario { width: 88px; }
    .btp-edit-table .btp-f-veiculo { width: 128px; }
    .btp-edit-table .btp-f-trajeto { width: 100%; min-width: 200px; }
    .btp-btn-del { color: #b32d2e !important; border-color: #b32d2e !important; padding: 2px 6px !important; }
    .btp-btn-del:hover { background: #f9e2e2 !important; }
    </style>

    <script>
    (function () {
        'use strict';

        /* ── Tabs de grupo ──────────────────────────────────────── */
        var groupBtns   = document.querySelectorAll('.btp-group-tab-bar .btp-tab-btn');
        var groupPanels = document.querySelectorAll('.btp-group-panel');

        groupBtns.forEach(function (btn) {
            btn.addEventListener('click', function () {
                groupBtns.forEach(function (b) { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
                groupPanels.forEach(function (p) { p.classList.remove('active'); });
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');
                var panel = document.getElementById(this.dataset.tab);
                if (panel) panel.classList.add('active');
            });
        });

        /* ── Tabs de seção (independentes por grupo) ────────────── */
        document.querySelectorAll('.btp-section-tab-bar').forEach(function (tabBar) {
            var secBtns   = tabBar.querySelectorAll('.btp-tab-btn');
            var groupPanel = tabBar.closest('.btp-group-panel');

            secBtns.forEach(function (btn) {
                btn.addEventListener('click', function () {
                    secBtns.forEach(function (b) { b.classList.remove('active'); b.setAttribute('aria-selected', 'false'); });
                    groupPanel.querySelectorAll('.btp-section-panel').forEach(function (p) { p.classList.remove('active'); });
                    this.classList.add('active');
                    this.setAttribute('aria-selected', 'true');
                    var panel = document.getElementById(this.dataset.tab);
                    if (panel) panel.classList.add('active');
                });
            });
        });

        /* ── Máscara HH:MM nos campos de horário ───────────────── */
        function applyHorarioMask(input) {
            input.addEventListener('input', function () {
                var pos    = this.selectionStart;
                var digits = this.value.replace(/\D/g, '').slice(0, 4);
                var masked = digits.length > 2
                    ? digits.slice(0, 2) + ':' + digits.slice(2)
                    : digits;
                this.value = masked;
                // Reposiciona o cursor: se o usuário digitou no ponto do separador,
                // avança uma posição para não ficar preso antes do ":"
                if (masked.length === 3 && pos === 2) {
                    this.setSelectionRange(3, 3);
                }
            });
            // Impede colar conteúdo inválido
            input.addEventListener('paste', function (e) {
                e.preventDefault();
                var text   = (e.clipboardData || window.clipboardData).getData('text');
                var digits = text.replace(/\D/g, '').slice(0, 4);
                this.value = digits.length > 2
                    ? digits.slice(0, 2) + ':' + digits.slice(2)
                    : digits;
            });
        }
        document.querySelectorAll('.btp-f-horario').forEach(applyHorarioMask);

        /* ── Remover linha ──────────────────────────────────────── */
        function bindDelete(btn) {
            btn.addEventListener('click', function () {
                this.closest('tr').remove();
            });
        }
        document.querySelectorAll('.btp-btn-del').forEach(bindDelete);

        /* ── Adicionar linha ────────────────────────────────────── */
        document.querySelectorAll('.btp-btn-add').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var tbody = this.closest('table').querySelector('tbody');
                var tr    = document.createElement('tr');
                tr.className = 'btp-linha-row';
                tr.innerHTML =
                    '<td><input type="text" class="btp-f-horario" value="" maxlength="5" placeholder="HH:MM"></td>' +
                    '<td><input type="text" class="btp-f-veiculo" value=""></td>' +
                    '<td><input type="text" class="btp-f-trajeto" value=""></td>' +
                    '<td><button type="button" class="btp-btn-del button button-small" title="Remover">&#10005;</button></td>';
                tbody.appendChild(tr);
                bindDelete(tr.querySelector('.btp-btn-del'));
                var newHorario = tr.querySelector('.btp-f-horario');
                applyHorarioMask(newHorario);
                newHorario.focus();
            });
        });

        /* ── Serializar e salvar ────────────────────────────────── */
        document.getElementById('btp-save-btn').addEventListener('click', function (e) {
            e.preventDefault();

            var grupos = [];

            document.querySelectorAll('.btp-group-panel').forEach(function (gpanel) {
                var secoes = [];

                gpanel.querySelectorAll('.btp-section-panel').forEach(function (spanel) {
                    var linhas = [];
                    spanel.querySelectorAll('tr.btp-linha-row').forEach(function (row) {
                        var h = row.querySelector('.btp-f-horario');
                        var v = row.querySelector('.btp-f-veiculo');
                        var t = row.querySelector('.btp-f-trajeto');
                        var horario = h ? h.value.trim() : '';
                        var trajeto = t ? t.value.trim() : '';
                        if (horario || trajeto) {
                            linhas.push({
                                horario: horario,
                                veiculo: v ? v.value.trim() : '',
                                trajeto: trajeto
                            });
                        }
                    });
                    secoes.push({ nome: spanel.dataset.nome || '', linhas: linhas });
                });

                grupos.push({ nome: gpanel.dataset.nome || '', secoes: secoes });
            });

            document.getElementById('btp-horarios-json').value = JSON.stringify(grupos);
            document.getElementById('btp-edit-form').submit();
        });

    })();
    </script>
    <?php
}
