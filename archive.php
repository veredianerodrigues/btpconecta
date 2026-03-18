<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — archive.php
 * Listagem de posts/categorias em layout de lista.
 */
get_header();

// Título do arquivo
$archive_title = '';
if (is_category()) {
    $archive_title = single_cat_title('', false);
} elseif (is_tag()) {
    $archive_title = single_tag_title('', false);
} elseif (is_date()) {
    $archive_title = get_the_date('F Y');
} else {
    $archive_title = get_the_archive_title();
}

$default_img = get_template_directory_uri() . '/images/header_padrao.jpg';
$hero_style  = 'style="background-image: url(' . esc_url($default_img) . ');"';
?>

<div class="content-area">

    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <span class="sep"> / </span>
            <span class="current"><?php echo esc_html($archive_title); ?></span>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>

    <div class="post-hero has-thumbnail" <?php echo $hero_style; ?>>
        <div class="post-hero-overlay">
            <div class="post-hero-inner">
                <h1 class="post-hero-title"><?php echo esc_html($archive_title); ?></h1>
            </div>
        </div>
    </div>

    <div class="main-container">

        <?php if (have_posts()) : ?>
            <div class="posts-list">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('template-parts/content', 'list'); ?>
                <?php endwhile; ?>
            </div>
            <?php btpconecta_pagination(); ?>
        <?php else : ?>
            <div class="no-posts">
                <p><?php esc_html_e('Nenhuma publicação encontrada nesta categoria.', 'btpconecta'); ?></p>
            </div>
        <?php endif; ?>

    </div><!-- /.main-container -->
</div><!-- /.content-area -->

<?php get_footer(); ?>
