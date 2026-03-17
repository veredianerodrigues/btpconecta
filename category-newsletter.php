<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — category-newsletter.php
 * Listagem de edições da Newsletter BTP Conecta.
 */
get_header();

$archive_title = 'Newsletter BTP Conecta';
$default_img   = get_template_directory_uri() . '/images/header_padrao.jpg';
$hero_style    = 'style="background-image: url(' . esc_url($default_img) . ');"';
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
        <div class="newsletter-grid">
            <?php while (have_posts()) : the_post(); ?>
            <a href="<?php the_permalink(); ?>" class="newsletter-card">
                <?php if (has_post_thumbnail()) : ?>
                    <?php the_post_thumbnail('medium_large', ['class' => 'newsletter-card-img']); ?>
                <?php else : ?>
                    <div class="newsletter-card-placeholder">
                        <img src="<?php echo esc_url(get_template_directory_uri() . '/images/logo_btp.png'); ?>" alt="BTP Conecta">
                    </div>
                <?php endif; ?>
                <div class="newsletter-card-body">
                    <span class="newsletter-card-date"><?php echo get_the_date('M/Y'); ?></span>
                    <h2 class="newsletter-card-title"><?php the_title(); ?></h2>
                    <?php
                    $excerpt = get_the_excerpt() ?: wp_trim_words(get_the_content(), 15, '…');
                    if ($excerpt) : ?>
                    <p class="newsletter-card-excerpt"><?php echo esc_html($excerpt); ?></p>
                    <?php endif; ?>
                    <span class="newsletter-card-read">Ler edição &rarr;</span>
                </div>
            </a>
            <?php endwhile; ?>
        </div>
        <?php btpconecta_pagination(); ?>
        <?php else : ?>
        <div class="no-posts">
            <p><?php esc_html_e('Nenhuma edição publicada ainda.', 'btpconecta'); ?></p>
        </div>
        <?php endif; ?>

    </div>

</div>

<?php get_footer(); ?>
