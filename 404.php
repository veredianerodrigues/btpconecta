<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — 404.php
 */
get_header();
?>
<div class="content-area">
    <div class="superheader">
        <span class="superheader-breadcrumb">
            <a href="<?php echo esc_url(home_url('/')); ?>">Início</a>
            <span class="sep"> / </span>
            <span class="current">Página não encontrada</span>
        </span>
        <a href="<?php echo esc_url(home_url('/')); ?>" class="superheader-home">Home</a>
    </div>
    <div class="main-container">
        <div class="error-404" style="text-align:center; padding: 60px 20px;">
            <h1 style="font-size: 6rem; color: #bdc915; margin: 0;">404</h1>
            <h2>Página não encontrada</h2>
            <p>A página que você está procurando não existe ou foi movida.</p>
            <a href="<?php echo esc_url(home_url('/')); ?>" class="btn-back">Voltar ao início</a>
        </div>
    </div>
</div>
<?php get_footer(); ?>
