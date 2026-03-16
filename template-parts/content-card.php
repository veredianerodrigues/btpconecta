<?php
/**
 * BTP Conecta — template-parts/content-card.php
 * Card de notícia para a listagem (archive.php, index.php).
 */

$categories  = get_the_category();
$primary_cat = !empty($categories) ? $categories[0] : null;
$cat_color   = $primary_cat ? btpconecta_category_color($primary_cat->slug) : '#214549';
$cat_url     = $primary_cat ? get_category_link($primary_cat->term_id) : '';
$cat_name    = $primary_cat ? $primary_cat->name : '';

// Excerpt com fallback
$excerpt = get_the_excerpt();
if (empty($excerpt)) {
    $excerpt = wp_trim_words(get_the_content(), 20, '…');
}

// Logo para placeholder
$logo_url = esc_url(get_template_directory_uri() . '/images/logo_btp.png');
?>

<article id="post-<?php the_ID(); ?>" <?php post_class('news-card'); ?>>

    <a href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
        <?php if (has_post_thumbnail()) : ?>
            <?php the_post_thumbnail('medium_large', ['class' => 'card-image', 'alt' => esc_attr(get_the_title())]); ?>
        <?php else : ?>
            <div class="card-image-placeholder">
                <img src="<?php echo $logo_url; ?>" alt="BTP Conecta">
            </div>
        <?php endif; ?>
    </a>

    <div class="card-body">

        <?php if ($cat_name) : ?>
        <a href="<?php echo esc_url($cat_url); ?>"
           class="card-category"
           style="color: <?php echo esc_attr($cat_color); ?>; border-color: <?php echo esc_attr($cat_color); ?>;">
            <?php echo esc_html($cat_name); ?>
        </a>
        <?php endif; ?>

        <h2 class="card-title">
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
        </h2>

        <?php if ($excerpt) : ?>
        <p class="card-excerpt"><?php echo esc_html($excerpt); ?></p>
        <?php endif; ?>

        <div class="card-footer">
            <span class="card-date">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                <?php echo get_the_date('d/m/Y'); ?>
            </span>
            <a href="<?php the_permalink(); ?>" class="card-readmore">Leia mais &rarr;</a>
        </div>

    </div><!-- /.card-body -->

</article><!-- /.news-card -->
