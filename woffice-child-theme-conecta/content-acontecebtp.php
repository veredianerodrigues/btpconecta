<?php
/**
 * Conteúdo de Acontece na BTP
 */
?>
<?php 
// CUSTOM CLASSES ADDED BY THE THEME
$post_classes = array('box','content', 'entry-content');
$blog_listing_content = woffice_get_settings_option('blog_listing_content','excerpt');
$hide_image_single_post = woffice_get_settings_option('hide_image_single_post', 'nope');
$hide_author_box = woffice_get_settings_option('hide_author_box_single_post', 'nope');
$hide_like_counter = woffice_get_settings_option('hide_like_counter_inside_author_box', 'nope');
$hide_learndash_meta = woffice_get_settings_option('hide_learndash_meta', 'nope');

if(get_post_status() == 'draft')
    array_push($post_classes, 'is-draft');
?>

	<article id="post-<?php the_ID(); ?>" <?php post_class($post_classes); ?>>
		<?php if (has_post_thumbnail() && !is_single()) : ?>
			<!-- THUMBNAIL IMAGE -->
			<?php /*GETTING THE POST THUMBNAIL URL*/
            Woffice_Frontend::render_featured_image_single_post($post->ID, 400);
            ?>

		<?php endif; 
        
        // Checa a imagem principal
		if ( has_post_thumbnail() ) {			
		 	$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium' );
			$imagem_base = "<img src='".$image[0]."' class='img-single-left' align='left' />"; 
		}
		 if (strpos(get_post_type(), 'sfwd') === FALSE || is_search()) : ?>
		
		
		<div class="intern-padding heading-container">
			<?php if (!is_single()): ?>
				<?php // THE TITLE
					the_title( '<div class="heading"><h3><a href="' . esc_url( get_permalink() ) . '" rel="bookmark">', '</a></h3></div>' ); 
			 else : ?>
								
				<?php // THE TITLE
				the_title( '<div class="heading titulo-defesa"><h2>', '</h2></div>' ); ?>
			<?php endif; ?>
		</div>
        <?php endif; ?>
        			
		
	</article>
