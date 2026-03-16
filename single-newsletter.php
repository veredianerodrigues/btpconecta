<?php
/**
 * BTP Conecta — single-newsletter.php
 * Post individual de Newsletter — layout full-width para o Elementor.
 */
get_header();

while (have_posts()) : the_post();

    $default_img = get_template_directory_uri() . '/images/header_padrao.jpg';
    if (has_post_thumbnail()) {
        $img_url = get_the_post_thumbnail_url(get_the_ID(), 'full');
    } else {
        $img_url = $default_img;
    }
    $hero_style = 'style="background-image: url(' . esc_url($img_url) . ');"';

    $cat     = get_category_by_slug('newsletter');
    $cat_url = $cat ? get_category_link($cat->term_id) : home_url('/');
?>

<div class="content-area">

    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <span class="sep"> / </span>
            <a href="<?php echo esc_url($cat_url); ?>">Newsletter</a>
            <span class="sep"> / </span>
            <span class="current"><?php the_title(); ?></span>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>

    <div class="post-hero has-thumbnail" <?php echo $hero_style; ?>>
        <div class="post-hero-overlay">
            <div class="post-hero-inner">
                <span class="newsletter-edition-label">Newsletter</span>
                <h1 class="post-hero-title"><?php the_title(); ?></h1>
                <div class="post-hero-meta">
                    <span class="post-date">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                        <?php echo get_the_date('F \d\e Y'); ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <div class="newsletter-content">
        <?php the_content(); ?>
    </div>

    <div style="padding: 0 32px;">
        <?php get_template_part('template-parts/share', 'buttons'); ?>
    </div>

    <div class="newsletter-back">
        <a href="<?php echo esc_url($cat_url); ?>" class="btn-back">&larr; Ver todas as edições</a>
    </div>

</div>

<?php
endwhile;
get_footer();
?>
