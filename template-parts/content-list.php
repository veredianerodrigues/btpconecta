<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — template-parts/content-list.php
 * Item de lista para archive.php (imagem à esquerda, conteúdo à direita).
 */

$categories  = get_the_category();
$primary_cat = !empty($categories) ? $categories[0] : null;
$cat_color   = $primary_cat ? btpconecta_category_color($primary_cat->slug) : '#214549';
$cat_url     = $primary_cat ? get_category_link($primary_cat->term_id) : '';
$cat_name    = $primary_cat ? $primary_cat->name : '';

$excerpt = get_the_excerpt();
if (empty($excerpt)) {
    $content = wp_strip_all_tags(strip_shortcodes(get_the_content()));
    $excerpt = wp_trim_words($content, 25, '…');
}

$logo_url  = esc_url(get_template_directory_uri() . '/images/logo_btp.png');
$has_thumb = has_post_thumbnail();
$first_img = !$has_thumb ? btpconecta_first_content_image(get_the_ID()) : '';
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('post-list-item'); ?>>

    <a href="<?php the_permalink(); ?>" class="post-list-image" tabindex="-1" aria-hidden="true">
        <?php if ($has_thumb) : ?>
            <?php the_post_thumbnail('medium_large', ['alt' => esc_attr(get_the_title())]); ?>
        <?php elseif ($first_img) : ?>
            <img src="<?php echo esc_url($first_img); ?>" alt="<?php echo esc_attr(get_the_title()); ?>">
        <?php else : ?>
            <div class="post-list-logo">
                <img src="<?php echo $logo_url; ?>" alt="BTP Conecta">
            </div>
        <?php endif; ?>
    </a>

    <div class="post-list-body">

        <?php if ($cat_name) : ?>
        <a href="<?php echo esc_url($cat_url); ?>"
           class="card-category"
           style="color: <?php echo esc_attr($cat_color); ?>; border-color: <?php echo esc_attr($cat_color); ?>;">
            <?php echo esc_html($cat_name); ?>
        </a>
        <?php endif; ?>

        <h2 class="post-list-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>

        <?php if ($excerpt) : ?>
        <p class="post-list-excerpt"><?php echo esc_html($excerpt); ?></p>
        <?php endif; ?>

        <div class="card-footer">
            <span class="card-date">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?php echo get_the_date('d/m/Y'); ?>
            </span>
            <span class="card-views">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                <?php echo btpconecta_get_post_views(get_the_ID()); ?>
            </span>
            <a href="<?php the_permalink(); ?>" class="card-readmore">Leia mais &rarr;</a>
        </div>

    </div><!-- /.post-list-body -->

</article><!-- /.post-list-item -->
