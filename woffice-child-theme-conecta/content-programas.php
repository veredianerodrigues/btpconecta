<?php
/**
 * Conteúdo de Programas sociais
 */
?>
<?php 
// CUSTOM CLASSES ADDED BY THE THEME
$post_classes = array('box','content', 'entry-content');

?>

	<article id="post-<?php the_ID(); ?>" <?php post_class($post_classes); ?>>
		
		<div class="intern-padding clearfix linha-topo">
			
			<?php // Start the Loop.
		// Checa a imagem principal
		if ( has_post_thumbnail() ) {
		 	$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium' );	
		?>		
			<div class="col-md-3 col-sm-4 col-xs-12 programas-img">
			<img src="<?php echo $image[0]; ?>" alt="<?php echo the_title(''); ?>" />
            </div>

		<?php } ?>
			
			
			
			<?php // THE EXCERRPT ?>
			<div class="blog-sum-up texto-destacado col-md-9 col-sm-8 col-xs-12">
				<?php the_content(''); ?>
			</div>
				
		</div>
	</article>
