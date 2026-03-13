<?php
/**
 * BTP Conecta — template-parts/content-single.php
 * Conteúdo do post individual (usado por single.php).
 */
?>
<div class="entry-content">
    <?php
    the_content(
        sprintf(
            '<span class="screen-reader-text">%s</span>',
            esc_html__('Continuar lendo', 'btpconecta')
        )
    );

    wp_link_pages([
        'before' => '<div class="page-links"><span>' . __('Páginas:', 'btpconecta') . '</span>',
        'after'  => '</div>',
    ]);
    ?>
</div><!-- /.entry-content -->
