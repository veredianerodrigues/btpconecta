<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — page.php
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
?>

<div class="content-area">

    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <span class="sep"> / </span>
            <span class="current"><?php the_title(); ?></span>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>

    <div class="post-hero has-thumbnail" <?php echo $hero_style; ?>>
        <div class="post-hero-overlay">
            <div class="post-hero-inner">
                <h1 class="post-hero-title"><?php the_title(); ?></h1>
            </div>
        </div>
    </div>

    <div class="main-container">
        <article id="page-<?php the_ID(); ?>" <?php post_class('page-content'); ?>>
            <div class="entry-content">
                <?php the_content(); ?>
            </div>
        </article>
    </div>

</div>

<?php
endwhile;
get_footer();
?>
