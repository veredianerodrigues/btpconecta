<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * Template Name: Listagem de Categoria
 *
 * BTP Conecta — template-category-listing.php
 * Page Template para listagem de posts por categoria(s).
 *
 * Configuração no WP Admin:
 *   - Template: "Listagem de Categoria"
 *   - Campo lateral "_btp_categories": slugs separados por vírgula
 *     Ex: "noticias, acontece-na-btp"
 *
 * Comportamento:
 *   - 1 categoria  → lista os posts diretamente
 *   - 2+ categorias → exibe filtro acima da listagem (URL ?cat=slug)
 */

get_header();

// ── Categorias configuradas via meta da página ────────────────────────────────
$page_id   = get_the_ID();
$raw       = get_post_meta($page_id, '_btp_categories', true);
$cat_slugs = array_values(array_filter(array_map('trim', explode(',', $raw ?: ''))));

// ── Filtro ativo via ?cat=slug ────────────────────────────────────────────────
$selected = isset($_GET['cat']) ? sanitize_key($_GET['cat']) : '';
if ($selected && !in_array($selected, $cat_slugs, true)) {
    $selected = ''; // descarta valor não configurado
}

// ── Categorias a consultar ────────────────────────────────────────────────────
$query_cats = (count($cat_slugs) > 1 && $selected) ? [$selected] : $cat_slugs;

// ── WP_Query ──────────────────────────────────────────────────────────────────
$paged = max(1, (int)(get_query_var('paged') ?: ($_GET['paged'] ?? 1)));

$args = [
    'post_type'      => 'post',
    'post_status'    => 'publish',
    'paged'          => $paged,
    'posts_per_page' => (int) get_option('posts_per_page'),
];

if (!empty($query_cats)) {
    $args['tax_query'] = [[
        'taxonomy' => 'category',
        'field'    => 'slug',
        'terms'    => $query_cats,
        'operator' => 'IN',
    ]];
}

$listing_query = new WP_Query($args);

// ── Dados da página ───────────────────────────────────────────────────────────
$page_title  = get_the_title();
$default_img = get_template_directory_uri() . '/images/header_padrao.jpg';
$hero_style  = 'style="background-image: url(' . esc_url($default_img) . ');"';

// ── Monta lista de categorias para o filtro ───────────────────────────────────
$filter_cats = [];
foreach ($cat_slugs as $slug) {
    $term = get_category_by_slug($slug);
    if ($term) {
        $filter_cats[] = [
            'slug' => $slug,
            'name' => $term->name,
        ];
    }
}
?>

<div class="content-area">

    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <span class="sep"> / </span>
            <span class="current"><?php echo esc_html($page_title); ?></span>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>

    <div class="post-hero has-thumbnail" <?php echo $hero_style; ?>>
        <div class="post-hero-overlay">
            <div class="post-hero-inner">
                <h1 class="post-hero-title"><?php echo esc_html($page_title); ?></h1>
            </div>
        </div>
    </div>

    <div class="main-container">

        <?php if (count($filter_cats) > 1) : ?>
        <nav class="category-filter" aria-label="Filtro de categorias">
            <span class="filter-label">Filtrar:</span>
            <a href="<?php echo esc_url(get_permalink($page_id)); ?>"
               class="filter-pill<?php echo !$selected ? ' active' : ''; ?>">
                Todos
            </a>
            <?php foreach ($filter_cats as $fc) : ?>
            <a href="<?php echo esc_url(add_query_arg('cat', $fc['slug'], get_permalink($page_id))); ?>"
               class="filter-pill<?php echo $selected === $fc['slug'] ? ' active' : ''; ?>">
                <?php echo esc_html($fc['name']); ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <?php endif; ?>

        <?php if ($listing_query->have_posts()) : ?>
            <div class="posts-grid">
                <?php while ($listing_query->have_posts()) : $listing_query->the_post(); ?>
                    <?php get_template_part('template-parts/content', 'card'); ?>
                <?php endwhile; ?>
                <?php wp_reset_postdata(); ?>
            </div>

            <?php
            $big = 999999999;
            $paginate_base = $selected
                ? add_query_arg('cat', $selected, str_replace($big, '%#%', esc_url(get_pagenum_link($big))))
                : str_replace($big, '%#%', esc_url(get_pagenum_link($big)));

            $links = paginate_links([
                'base'      => $paginate_base,
                'format'    => '?paged=%#%',
                'current'   => $paged,
                'total'     => $listing_query->max_num_pages,
                'prev_text' => '&laquo; Anterior',
                'next_text' => 'Próxima &raquo;',
            ]);
            if ($links) {
                echo '<nav class="navigation pagination"><div class="nav-links">' . $links . '</div></nav>';
            }
            ?>
        <?php else : ?>
            <div class="no-posts">
                <p><?php esc_html_e('Nenhuma publicação encontrada.', 'btpconecta'); ?></p>
            </div>
        <?php endif; ?>

    </div><!-- /.main-container -->
</div><!-- /.content-area -->

<?php get_footer(); ?>
