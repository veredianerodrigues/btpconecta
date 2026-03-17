<?php
if (!defined('ABSPATH')) {
    exit;
}
/**
 * BTP Conecta — template-parts/share-buttons.php
 * Botões de compartilhamento: WhatsApp, E-mail e Copiar URL.
 */
$share_url   = urlencode(get_permalink());
$share_title = urlencode(get_the_title());
$wa_url      = 'https://wa.me/?text=' . $share_title . '%20' . $share_url;
?>
<div class="share-bar" data-post-id="<?php the_ID(); ?>">
    <span class="share-label">Compartilhar:</span>

    <a href="<?php echo esc_url($wa_url); ?>"
       class="share-btn share-btn--whatsapp"
       target="_blank" rel="noopener"
       title="Compartilhar no WhatsApp">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="currentColor">
            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z"/>
            <path d="M12 0C5.373 0 0 5.373 0 12c0 2.125.557 4.126 1.532 5.862L.054 23.454a.5.5 0 0 0 .492.592.499.499 0 0 0 .136-.019l5.7-1.534A11.943 11.943 0 0 0 12 24c6.627 0 12-5.373 12-12S18.627 0 12 0zm0 21.9a9.886 9.886 0 0 1-5.031-1.373l-.361-.214-3.737 1.006 1.006-3.669-.235-.374A9.862 9.862 0 0 1 2.1 12C2.1 6.533 6.533 2.1 12 2.1c5.468 0 9.9 4.433 9.9 9.9 0 5.468-4.432 9.9-9.9 9.9z"/>
        </svg>
        WhatsApp
    </a>

    <button class="share-btn share-btn--email" id="share-email-trigger" title="Compartilhar por e-mail">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"/>
            <polyline points="22,6 12,13 2,6"/>
        </svg>
        E-mail
    </button>

    <button class="share-btn share-btn--copy" id="share-copy-url"
            data-url="<?php echo esc_attr(get_permalink()); ?>"
            title="Copiar link">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <rect x="9" y="9" width="13" height="13" rx="2" ry="2"/>
            <path d="M5 15H4a2 2 0 0 1-2-2V4a2 2 0 0 1 2-2h9a2 2 0 0 1 2 2v1"/>
        </svg>
        Copiar link
    </button>
</div>
