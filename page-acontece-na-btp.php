<?php
/**
 * BTP Conecta — page-acontece-na-btp.php
 * Redireciona para o archive da categoria homônima.
 */
$category = get_category_by_slug('acontece-na-btp');
if ($category) {
    wp_redirect(get_category_link($category->term_id), 301);
    exit;
}
wp_redirect(home_url('/'), 302);
exit;
