<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — single.php
 * Template do post individual com suporte completo ao Gutenberg.
 */
get_header();

while (have_posts()) : the_post();

    // Categoria principal
    $categories = get_the_category();
    $primary_cat = !empty($categories) ? $categories[0] : null;
    $cat_color   = $primary_cat ? btpconecta_category_color($primary_cat->slug) : '#214549';
    $cat_url     = $primary_cat ? get_category_link($primary_cat->term_id) : home_url('/');
    $cat_name    = $primary_cat ? $primary_cat->name : '';

    // URL da imagem de capa para o hero
    $hero_style = '';
    if (has_post_thumbnail()) {
        $img_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
        $hero_style = 'style="background-image: url(' . esc_url($img_url) . ');"';
    }
?>

<div class="content-area">

    <!-- Superheader / breadcrumb -->
    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <?php if ($primary_cat) : ?>
                <span class="sep"> / </span>
                <a href="<?php echo esc_url($cat_url); ?>"><?php echo esc_html($cat_name); ?></a>
            <?php endif; ?>
            <span class="sep"> / </span>
            <span class="current"><?php the_title(); ?></span>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>

    <!-- Hero com imagem de capa e título sobreposto -->
    <div class="post-hero <?php echo has_post_thumbnail() ? 'has-thumbnail' : 'no-thumbnail'; ?>" <?php echo $hero_style; ?>>
        <div class="post-hero-overlay">
            <div class="post-hero-inner">
                <?php if ($cat_name) : ?>
                    <a href="<?php echo esc_url($cat_url); ?>"
                       class="post-category-badge"
                       style="background-color: <?php echo esc_attr($cat_color); ?>">
                        <?php echo esc_html($cat_name); ?>
                    </a>
                <?php endif; ?>
                <h1 class="post-hero-title"><?php the_title(); ?></h1>
                <div class="post-hero-meta">
                    <span class="post-date">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?php echo get_the_date('d/m/Y'); ?>
                    </span>
                    <span class="post-views">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        <?php echo btpconecta_get_post_views(get_the_ID()); ?> visualizações
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Área de conteúdo -->
    <div class="main-container">
        <article id="post-<?php the_ID(); ?>" <?php post_class('single-post-content'); ?>>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>

            <div class="post-pub-date">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                Publicado em <?php echo get_the_date('d \d\e F \d\e Y'); ?>
            </div>

            <?php get_template_part('template-parts/share', 'buttons'); ?>

            <!-- Botão voltar para a categoria -->
            <?php if ($primary_cat) : ?>
            <div class="post-back">
                <a href="<?php echo esc_url($cat_url); ?>" class="btn-back"
                   style="border-color: <?php echo esc_attr($cat_color); ?>; color: <?php echo esc_attr($cat_color); ?>;">
                    &larr; Voltar para <?php echo esc_html($cat_name); ?>
                </a>
            </div>
            <?php endif; ?>

        </article>
    </div><!-- /.main-container -->

</div><!-- /.content-area -->

<?php
endwhile;
get_footer();
?>
