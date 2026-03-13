<?php
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

    <div class="featuredbox">
        <div class="featuredbox-inner">
            <h1>Resultados para: <em><?php echo esc_html($search_query); ?></em></h1>
        </div>
    </div>

    <div class="main-container">
        <?php if (have_posts()) : ?>
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
