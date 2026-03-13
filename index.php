<?php
/**
 * BTP Conecta — index.php (fallback)
 */
get_header();
?>
<div class="content-area">
    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <?php if (is_category()) : ?>
                <span class="sep"> / </span>
                <span class="current"><?php single_cat_title(); ?></span>
            <?php endif; ?>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>

    <div class="main-container">
        <?php if (have_posts()) : ?>
            <div class="posts-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('template-parts/content', 'card'); ?>
                <?php endwhile; ?>
            </div>
            <?php btpconecta_pagination(); ?>
        <?php else : ?>
            <p><?php esc_html_e('Nenhum conteúdo encontrado.', 'btpconecta'); ?></p>
        <?php endif; ?>
    </div>
</div>
<?php get_footer(); ?>
