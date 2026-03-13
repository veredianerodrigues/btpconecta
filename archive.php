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
?>

<div class="content-area">

    <!-- Superheader / breadcrumb -->
    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <span class="sep"> / </span>
            <span class="current"><?php echo esc_html($archive_title); ?></span>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>

    <!-- Hero / featuredbox -->
    <div class="featuredbox">
        <div class="featuredbox-inner">
            <h1 class="archive-title"><?php echo esc_html($archive_title); ?></h1>
        </div>
    </div>

    <div class="main-container">

        <?php if (is_category()) : ?>
        <!-- Filtro de categorias irmãs -->
        <div class="category-filter">
            <?php
            $current_cat = get_queried_object();
            $sibling_cats = get_categories([
                'parent'  => $current_cat->parent,
                'exclude' => $current_cat->term_id,
                'hide_empty' => true,
            ]);
            if ($sibling_cats) :
            ?>
            <span class="filter-label">Categorias:</span>
            <a href="<?php echo esc_url(get_category_link($current_cat->term_id)); ?>"
               class="filter-pill active"><?php echo esc_html($current_cat->name); ?></a>
            <?php foreach ($sibling_cats as $cat) : ?>
                <a href="<?php echo esc_url(get_category_link($cat->term_id)); ?>"
                   class="filter-pill"><?php echo esc_html($cat->name); ?></a>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <?php endif; ?>

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
