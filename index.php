<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — index.php
 * Home page: hero/banner + feed Acontece na BTP + grid de atalhos.
 */
get_header();

// ── 1. HERO: sticky post ou post mais recente com thumbnail ───────────────────
$sticky_ids = get_option('sticky_posts', []);
$hero_args  = ['numberposts' => 1, 'meta_key' => '_thumbnail_id'];
if (!empty($sticky_ids)) {
    $hero_args['post__in']            = $sticky_ids;
    $hero_args['ignore_sticky_posts'] = 0;
}
$hero_posts = get_posts($hero_args);
$hero_post  = $hero_posts ? $hero_posts[0] : null;
$hero_img   = $hero_post ? get_the_post_thumbnail_url($hero_post->ID, 'full') : '';

// ── 2. FEED: últimos 4 posts da categoria "acontece-na-btp" ──────────────────
$news_cat   = get_category_by_slug('acontece-na-btp');
$news_posts = $news_cat ? get_posts([
    'numberposts' => 4,
    'category'    => $news_cat->term_id,
]) : [];

// ── 3. ATALHOS HOME: itens do menu "home-shortcuts" com imagem do destino ─────
$shortcuts      = [];
$menu_locations = get_nav_menu_locations();
if (!empty($menu_locations['home-shortcuts'])) {
    $raw = wp_get_nav_menu_items($menu_locations['home-shortcuts']);
    if ($raw) {
        foreach ($raw as $item) {
            // Imagem destacada do post/page vinculado; fallback: sem imagem
            $img_url = '';
            if (in_array($item->object, ['post', 'page'], true) && $item->object_id) {
                $img_url = get_the_post_thumbnail_url((int) $item->object_id, 'medium_large') ?: '';
            }
            $shortcuts[] = [
                'title' => $item->title,
                'url'   => $item->url,
                'img'   => $img_url,
            ];
        }
    }
}
?>

<div class="content-area">

    <!-- Superheader -->
    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>

    <!-- ── BLOCO SUPERIOR: hero + feed de notícias ───────────── -->
    <div class="home-top">

        <!-- Hero/Banner -->
        <?php if ($hero_post && $hero_img) : ?>
        <div class="home-hero" style="background-image: url(<?php echo esc_url($hero_img); ?>);">
            <div class="home-hero-overlay">
                <div class="home-hero-inner">
                    <a href="<?php echo esc_url(get_permalink($hero_post->ID)); ?>" class="home-hero-link">
                        <?php echo esc_html($hero_post->post_title); ?>
                    </a>
                </div>
            </div>
        </div>
        <?php else : ?>
        <div class="home-hero home-hero--empty"></div>
        <?php endif; ?>

        <!-- Feed Acontece na BTP -->
        <?php if ($news_posts) : ?>
        <aside class="home-news">
            <ul class="home-news-list">
                <?php foreach ($news_posts as $np) :
                    $excerpt = $np->post_excerpt
                        ? wp_trim_words($np->post_excerpt, 15, '…')
                        : wp_trim_words($np->post_content, 15, '…');
                ?>
                <li class="home-news-item">
                    <a href="<?php echo esc_url(get_permalink($np->ID)); ?>" class="home-news-title">
                        <?php echo esc_html($np->post_title); ?>
                    </a>
                    <?php if ($excerpt) : ?>
                    <p class="home-news-excerpt"><?php echo esc_html($excerpt); ?></p>
                    <?php endif; ?>
                </li>
                <?php endforeach; ?>
            </ul>
            <?php if ($news_cat) : ?>
            <a href="<?php echo esc_url(get_category_link($news_cat->term_id)); ?>" class="home-news-all">
                + Leia tudo o que acontece na BTP
            </a>
            <?php endif; ?>
        </aside>
        <?php endif; ?>

    </div><!-- /.home-top -->

    <!-- ── GRID DE ATALHOS ───────────────────────────────────── -->
    <?php if ($shortcuts) : ?>
    <div class="home-shortcuts">
        <?php foreach ($shortcuts as $s) : ?>
        <a href="<?php echo esc_url($s['url']); ?>" class="shortcut-tile"
           <?php if ($s['img']) echo 'style="background-image: url(' . esc_url($s['img']) . ');"'; ?>>
            <div class="shortcut-overlay">
                <span class="shortcut-label"><?php echo esc_html($s['title']); ?></span>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

</div><!-- /.content-area -->

<?php get_footer(); ?>
