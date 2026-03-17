<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — front-page.php
 * Usado quando WordPress tem "página estática" definida em Configurações → Leitura.
 * Home page: hero/banner + feed Acontece na BTP + grid de atalhos.
 */
get_header();

$img_base = get_template_directory_uri() . '/assets/images/';

// ── 1. HERO: imagem destacada + custom fields da página estática definida como home ──
$home_page_id = get_option('page_on_front');
$link_banner  = $home_page_id ? get_post_meta($home_page_id, 'link_banner', true) : '';
$texto_banner = $home_page_id ? get_post_meta($home_page_id, 'texto_banner', true) : '';
$link_seg     = $home_page_id ? get_post_meta($home_page_id, 'link_seguranca', true) : '#';
$data_acid    = $home_page_id ? get_post_meta($home_page_id, 'data_calculo_acidente', true) : '';
$hero_img     = $home_page_id ? get_the_post_thumbnail_url($home_page_id, 'full') : '';

// Fallback do link do banner para a home
if (empty($link_banner)) {
    $link_banner = home_url('/');
}

// Contador de dias sem acidente
$dias_sem_acidente = 0;
if ($data_acid) {
    $dia1              = strtotime($data_acid) + 97140;
    $dia2              = strtotime(date('d-m-Y H:i', time()));
    $dias_sem_acidente = max(0, (int) (($dia2 - $dia1) / 86400));
}

// ── 2. FEED: últimos 3 posts da categoria "destaques-da-home-page" ───────────
$news_cat      = get_category_by_slug('destaques-da-home-page');
$news_cat_link = get_category_by_slug('acontece-na-btp');
$news_posts    = $news_cat ? get_posts([
    'numberposts' => 3,
    'category'    => $news_cat->term_id,
]) : [];

// ── 3. GRID 3×2: hardcoded com imagens do tema ───────────────────────────────
$link_universidade = 'https://universidadebtp.edusense.app/#/';
$link_risco        = 'https://linktr.ee/safetybtp';
$link_portal       = 'https://portaldocliente.btp.com.br/';
?>

<div class="content-area">

    <div class="home-top">

        <?php if ($hero_img) : ?>
        <div class="home-hero" style="background-image: url(<?php echo esc_url($hero_img); ?>);">
            <a href="<?php echo esc_url($link_banner); ?>" class="home-hero-link">
                <?php if ($texto_banner) : ?>
                <span class="home-hero-text"><?php echo esc_html($texto_banner); ?></span>
                <?php endif; ?>
            </a>
        </div>
        <?php else : ?>
        <div class="home-hero home-hero--empty"></div>
        <?php endif; ?>

        <?php if ($news_posts) : ?>
        <aside class="home-news">
            <ul class="home-news-list">
                <?php foreach ($news_posts as $np) :
                    $excerpt = $np->post_excerpt
                        ? wp_trim_words($np->post_excerpt, 15, '…')
                        : wp_trim_words($np->post_content, 15, '…');
                ?>
                <li class="home-news-item">
                    <div class="home-news-item-body">
                        <span class="home-news-label">Destaques</span>
                        <a href="<?php echo esc_url(get_permalink($np->ID)); ?>" class="home-news-title">
                            <?php echo esc_html($np->post_title); ?>
                        </a>
                        <?php if ($excerpt) : ?>
                        <p class="home-news-excerpt"><?php echo esc_html($excerpt); ?></p>
                        <?php endif; ?>
                    </div>
                    <a href="<?php echo esc_url(get_permalink($np->ID)); ?>" class="home-news-plus" title="Leia mais">+</a>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($news_cat_link) : ?>
            <a href="<?php echo esc_url(get_category_link($news_cat_link->term_id)); ?>" class="home-news-all">
                <span class="home-news-all-plus">+</span>
                <span><strong>LEIA TUDO</strong>&nbsp;O QUE ACONTECE NA BTP</span>
            </a>
            <?php endif; ?>
        </aside>
        <?php endif; ?>

    </div>

    <div class="home-shortcuts">

        <a href="<?php echo esc_url(home_url('/cardapio/')); ?>" class="shortcut-tile">
            <img src="<?php echo esc_url($img_base . 'botao_home_cardapio.jpg'); ?>" alt="Cardápio do Dia"/>
        </a>

        <a href="<?php echo esc_url($link_universidade); ?>" class="shortcut-tile" target="_blank" rel="noopener">
            <img src="<?php echo esc_url($img_base . 'faculdade_btp-b.png'); ?>" alt="Universidade BTP"/>
        </a>

        <a href="<?php echo esc_url($link_portal); ?>" class="shortcut-tile" target="_blank" rel="noopener">
            <img src="<?php echo esc_url($img_base . 'portaldocliente.png'); ?>" alt="Portal do Cliente"/>
        </a>

        <a href="<?php echo esc_url(home_url('/propor/')); ?>" class="shortcut-tile">
            <img src="<?php echo esc_url($img_base . 'botao_home_propor.jpg'); ?>" alt="Propor"/>
        </a>

        <a href="<?php echo esc_url($link_risco); ?>" class="shortcut-tile" target="_blank" rel="noopener">
            <img src="<?php echo esc_url($img_base . 'botao_home_risco.png'); ?>" alt="#DeOlhoNoRisco"/>
        </a>

        <div class="shortcut-tile shortcut-tile--seguranca linkSeguranca"
             data-link="<?php echo esc_attr($link_seg ?: '#'); ?>"
             role="link" tabindex="0">
            <img src="<?php echo esc_url($img_base . 'botao_home_seguranca.jpg'); ?>" alt="Segurança"/>
            <div class="seguranca-counter">
                <span class="cont-dias">ESTAMOS HÁ <span class="contagemDias"><?php echo esc_html($dias_sem_acidente); ?></span></span>
                <span class="texto-dias">DIAS SEM ACIDENTE COM AFASTAMENTO</span>
            </div>
        </div>

    </div>

</div>

<?php get_footer(); ?>
