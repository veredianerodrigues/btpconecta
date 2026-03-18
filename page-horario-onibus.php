<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — page-horario-onibus.php
 * Template Name: Horário de Ônibus
 *
 * Exibe os horários de ônibus organizados por grupo (dia da semana) e ponto de saída.
 *
 * @package btpconecta
 */

get_header();

// ── Hero: usa imagem destacada da página ou fallback padrão ──────────────────
$default_img = get_template_directory_uri() . '/images/header_padrao.jpg';
if (has_post_thumbnail()) {
    $img_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
} else {
    $img_url = $default_img;
}
$hero_style = 'style="background-image: url(' . esc_url($img_url) . ');"';

// ── Carrega dados dos horários do wp_options ──────────────────────────────────
$grupos = json_decode(get_option('btp_horarios_data', '[]'), true);
if (!is_array($grupos)) {
    $grupos = [];
}

// ── Helper: aplica highlight ao texto do TRAJETO ─────────────────────────────
function btp_horario_render_trajeto(string $trajeto): string {
    $html = esc_html($trajeto);

    // Badge "SOMENTE SEXTA-FEIRA"
    if (stripos($trajeto, 'SOMENTE SEXTA-FEIRA') !== false) {
        $html .= ' <span class="horario-badge horario-badge-sexta">Somente Sexta-Feira</span>';
    }

    // Badge "DE SEGUNDA A QUINTA"
    if (stripos($trajeto, 'DE SEGUNDA A QUINTA') !== false) {
        $html .= ' <span class="horario-badge horario-badge-semana">Seg a Qui</span>';
    }

    // Highlight SUPERVISOR → vermelho
    if (stripos($trajeto, 'SUPERVISOR') !== false) {
        return '<span class="horario-highlight-supervisor">' . $html . '</span>';
    }

    // Highlight TURNO _ → verde
    if (preg_match('/TURNO\s+_/i', $trajeto)) {
        return '<span class="horario-highlight-turno">' . $html . '</span>';
    }

    return $html;
}
?>

<div class="content-area">

    <!-- Breadcrumb + superheader -->
    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <span class="sep"> / </span>
            <span class="current">Horário de Ônibus</span>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>

    <!-- Hero -->
    <div class="post-hero has-thumbnail" <?php echo $hero_style; ?>>
        <div class="post-hero-overlay">
            <div class="post-hero-inner">
                <h1 class="post-hero-title">Horário de Ônibus</h1>
            </div>
        </div>
    </div>

    <div class="main-container">

        <?php if (empty($grupos)) : ?>
        <div class="horario-empty">
            <p>Os horários ainda não foram configurados. Entre em contato com o administrador do sistema.</p>
        </div>
        <?php else : ?>

        <!-- Legenda de veículos -->
        <div class="horario-legend">
            <strong>Legenda de veículos:</strong>
            <ul class="horario-legend-list">
                <li><span class="horario-legend-icon horario-legend-micro">M</span> <strong>Micro-ônibus</strong> — 26 assentos</li>
                <li><span class="horario-legend-icon horario-legend-onibus">Ô</span> <strong>Ônibus</strong> — 46 assentos</li>
                <li><span class="horario-legend-icon horario-legend-van">V</span> <strong>Van</strong> — 15 assentos</li>
            </ul>
        </div>

        <!-- Wrapper principal -->
        <div class="horario-wrapper">

            <!-- Filtros de grupo (Segunda a Sexta / Sábados e Feriados / Domingo) -->
            <div class="horario-filters horario-group-filters" role="group" aria-label="Filtro por dia da semana">
                <?php foreach ($grupos as $gi => $grupo) : ?>
                <button
                    type="button"
                    class="horario-filter-btn horario-group-btn<?php echo $gi === 0 ? ' active' : ''; ?>"
                    data-group="<?php echo esc_attr($gi); ?>"
                    aria-pressed="<?php echo $gi === 0 ? 'true' : 'false'; ?>"
                ><?php echo esc_html($grupo['nome']); ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Filtros de ponto de saída -->
            <div class="horario-filters horario-section-filters" role="group" aria-label="Filtro por ponto de saída">
                <?php
                // Os nomes das seções são iguais em todos os grupos; usa o primeiro
                $primeiro_grupo = $grupos[0] ?? ['secoes' => []];
                foreach ($primeiro_grupo['secoes'] as $si => $secao) : ?>
                <button
                    type="button"
                    class="horario-filter-btn horario-section-btn<?php echo $si === 0 ? ' active' : ''; ?>"
                    data-section="<?php echo esc_attr($si); ?>"
                    aria-pressed="<?php echo $si === 0 ? 'true' : 'false'; ?>"
                ><?php echo esc_html($secao['nome']); ?></button>
                <?php endforeach; ?>
            </div>

            <!-- Campo de busca -->
            <div class="horario-search-wrap">
                <h3>Buscar horário:</h3>
                <input
                    type="search"
                    id="horario-search"
                    class="horario-search"
                    placeholder="Digite aqui o horário desejado…"
                    aria-label="Buscar horários"
                >
            </div>

            <!-- Contador de resultados -->
            <div class="horario-count" id="horario-count" aria-live="polite"></div>

            <!-- Tabelas: uma por grupo × seção -->
            <?php foreach ($grupos as $gi => $grupo) : ?>
                <?php foreach ($grupo['secoes'] as $si => $secao) : ?>
                <div
                    class="horario-table-wrap"
                    data-group="<?php echo esc_attr($gi); ?>"
                    data-section="<?php echo esc_attr($si); ?>"
                    <?php echo ($gi !== 0 || $si !== 0) ? 'style="display:none;"' : ''; ?>
                >
                    <div class="horario-table-scroll">
                        <table class="horario-table">
                            <caption class="horario-table-caption">
                                <?php echo esc_html($grupo['nome']); ?> — <?php echo esc_html($secao['nome']); ?>
                            </caption>
                            <thead>
                                <tr>
                                    <th scope="col" class="col-horario">Horário</th>
                                    <th scope="col" class="col-veiculo">Veículo</th>
                                    <th scope="col" class="col-trajeto">Trajeto</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($secao['linhas'])) : ?>
                                <tr class="horario-no-data">
                                    <td colspan="3">Nenhum horário disponível para este período.</td>
                                </tr>
                                <?php else : ?>
                                    <?php foreach ($secao['linhas'] as $linha) : ?>
                                    <tr class="horario-row">
                                        <td class="col-horario"><?php echo esc_html($linha['horario']); ?></td>
                                        <td class="col-veiculo"><?php echo esc_html($linha['veiculo']); ?></td>
                                        <td class="col-trajeto"><?php echo btp_horario_render_trajeto($linha['trajeto']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endforeach; ?>

        </div><!-- .horario-wrapper -->

        <?php endif; ?>

    </div><!-- .main-container -->

</div><!-- .content-area -->

<?php get_footer(); ?>
