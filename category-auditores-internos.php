<?php
/**
 * BTP Conecta — category-auditores-internos.php
 * Template específico da categoria Auditores Internos.
 * Renderiza os posts em formato de tabela.
 */
get_header();

$archive_title = single_cat_title('', false) ?: 'Auditores Internos';
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
        <table class="posts-table">
            <thead>
                <tr>
                    <th>Título</th>
                    <th class="posts-table-date">Data</th>
                </tr>
            </thead>
            <tbody>
                <?php while (have_posts()) : the_post(); ?>
                <tr>
                    <td>
                        <a href="<?php the_permalink(); ?>" class="posts-table-link">
                            <?php the_title(); ?>
                        </a>
                        <?php
                        $excerpt = get_the_excerpt() ?: wp_trim_words(get_the_content(), 15, '…');
                        if ($excerpt) : ?>
                        <p class="posts-table-excerpt"><?php echo esc_html($excerpt); ?></p>
                        <?php endif; ?>
                    </td>
                    <td class="posts-table-date"><?php echo get_the_date('d/m/Y'); ?></td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
        <?php btpconecta_pagination(); ?>
        <?php else : ?>
        <div class="no-posts">
            <p><?php esc_html_e('Nenhuma publicação encontrada.', 'btpconecta'); ?></p>
        </div>
        <?php endif; ?>

    </div>

</div>

<?php get_footer(); ?>
