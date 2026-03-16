<?php
/**
 * BTP Conecta — page-acontece-na-btp.php
 * Template específico para a página com slug "acontece-na-btp".
 * Renderiza os posts da categoria homônima com o mesmo layout de archive.php.
 */
get_header();

$category     = get_category_by_slug('acontece-na-btp');
$archive_title = $category ? $category->name : 'Acontece na BTP';

// Imagem de fundo do hero: post mais recente com thumbnail da categoria
$hero_image_url = '';
if ($category) {
    $latest = get_posts([
        'numberposts' => 1,
        'category'    => $category->term_id,
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
$default_img = get_template_directory_uri() . '/images/header_padrao.jpg';
$hero_bg     = $hero_image_url ?: $default_img;
$hero_style  = 'style="background-image: url(' . esc_url($hero_bg) . ');"';

// Query de posts da categoria com suporte a paginação
$paged = get_query_var('paged') ?: 1;
$query = new WP_Query([
    'cat'            => $category ? $category->term_id : 0,
    'posts_per_page' => get_option('posts_per_page'),
    'paged'          => $paged,
]);
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

        <?php if ($query->have_posts()) : ?>
            <div class="posts-grid">
                <?php while ($query->have_posts()) : $query->the_post(); ?>
                    <?php get_template_part('template-parts/content', 'card'); ?>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>
            <?php btpconecta_pagination(); ?>
        <?php else : ?>
            <div class="no-posts">
                <p><?php esc_html_e('Nenhuma publicação encontrada.', 'btpconecta'); ?></p>
            </div>
        <?php endif; ?>

    </div>

</div>

<?php get_footer(); ?>
