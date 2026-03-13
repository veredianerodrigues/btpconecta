<?php
/**
 * Conteúdo de Projetos incentivados
 */
?>
<?php 
// CUSTOM CLASSES ADDED BY THE THEME
$post_classes = array('box','content', 'entry-content');

?>
		
	<article id="post-<?php the_ID(); ?>" <?php post_class($post_classes); ?>>
		
		<div class="intern-padding clearfix">
		
		<h2 class="super-subtitulo"><?php echo the_title(''); ?></h2>
			
		<?php // THE EXCERRPT ?>
		<div class="blog-sum-up texto-destacado defesa-esquerda">
			<?php the_content(''); ?>
		</div>
				
		</div>
	</article>
