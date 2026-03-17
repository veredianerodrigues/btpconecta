<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — search.php
 */
get_header();
$search_query = get_search_query();
?>
<div class="content-area">
    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <span class="sep"> / </span>
            <span class="current">Busca: <?php echo esc_html($search_query); ?></span>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>

    <?php $default_img = get_template_directory_uri() . '/images/header_padrao.jpg'; ?>
    <div class="post-hero has-thumbnail" style="background-image: url(<?php echo esc_url($default_img); ?>);">
        <div class="post-hero-overlay">
            <div class="post-hero-inner">
                <h1 class="post-hero-title">Resultados para: <em><?php echo esc_html($search_query); ?></em></h1>
            </div>
        </div>
    </div>

    <div class="main-container">
        <?php if (have_posts()) : global $wp_query; ?>
            <p class="search-count"><?php printf('%d resultado(s) encontrado(s)', $wp_query->found_posts); ?></p>
            <div class="posts-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('template-parts/content', 'card'); ?>
                <?php endwhile; ?>
            </div>
            <?php btpconecta_pagination(); ?>
        <?php else : ?>
            <div class="no-posts">
                <p>Nenhum resultado encontrado para <strong><?php echo esc_html($search_query); ?></strong>.</p>
                <?php get_search_form(); ?>
            </div>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?>
