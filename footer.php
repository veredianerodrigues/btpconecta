<?php
/**
 * BTP Conecta — footer.php
 */
?>
    </section><!-- /#main-content -->

</div><!-- /#page-wrapper -->

<div class="border-bottom-btp"></div>

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
