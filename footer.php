<?php
/**
 * BTP Conecta — footer.php
 */
?>
    </section><!-- /#main-content -->

</div><!-- /#page-wrapper -->

<div class="border-bottom-btp"></div>

<button id="btp-install-btn" style="display:none;" aria-label="Instalar app">
    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/><polyline points="7 10 12 15 17 10"/><line x1="12" y1="15" x2="12" y2="3"/></svg>
    Instalar app
</button>

<?php if (is_single()) : ?>
<div id="share-email-modal" class="share-modal" aria-hidden="true">
    <div class="share-modal-box">
        <button class="share-modal-close" id="share-modal-close" aria-label="Fechar">&times;</button>
        <h3>Compartilhar por e-mail</h3>
        <p>Informe o e-mail do destinatário:</p>
        <input type="email" id="share-email-to" placeholder="destinatario@email.com" autocomplete="email">
        <div id="share-email-msg"></div>
        <div class="share-modal-actions">
            <button id="share-email-send" class="share-modal-btn">Enviar</button>
        </div>
    </div>
</div>
<?php endif; ?>

<?php wp_footer(); ?>
</body>
</html>
