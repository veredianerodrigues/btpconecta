<?php
/**
 * BTP Conecta — archive.php
 * Listagem de posts/categorias com grid de cards moderno.
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

// Imagem de fundo do hero: usa a imagem destacada do post mais recente da categoria
$hero_image_url = '';
if (is_category()) {
    $latest = get_posts([
        'numberposts' => 1,
        'category'    => get_queried_object_id(),
        'meta_key'    => '_thumbnail_id',
        'post_type'   => 'post',
    ]);
    if ($latest) {
        $url = get_the_post_thumbnail_url($latest[0]->ID, 'large');
        if ($url) {
            $hero_image_url = $url;
        }
    }
}
$default_img  = get_template_directory_uri() . '/images/header_padrao.jpg';
$hero_bg      = $hero_image_url ?: $default_img;
$hero_style   = 'style="background-image: url(' . esc_url($hero_bg) . ');"';
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
            <div class="posts-grid">
                <?php while (have_posts()) : the_post(); ?>
                    <?php get_template_part('template-parts/content', 'card'); ?>
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
