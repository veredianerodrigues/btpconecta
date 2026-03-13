<?php
/* Template Name: Modelo Sem Cabeçalho */ 
get_header(); 
?>



	<div id="left-content">
	
		<div id="superheader">
		
		<div id="breadcrumbs" typeof="BreadcrumbList" vocab="http://schema.org/">
			<?php if(function_exists('bcn_display'))
			{
				bcn_display();
			}?>
		</div>	
		
		<a class="voltar" href="<?php echo get_home_url(); ?>" ><i class="fa fa-reply"></i> <span class="texto-home">Home</span></a>
		<div class="clearfix"></div>
		</div>
		
		
		<?php // Start the Loop.
		while ( have_posts() ) : the_post(); 
		
		// Checa a imagem principal
		if ( has_post_thumbnail() ) {
		 	$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );	}
		else {
			$image[0] = get_stylesheet_directory_uri()."/images/header_padrao.jpg";
		}
		?>	

		<!-- START THE CONTENT CONTAINER -->
		<div id="content-container">
			<div class="clearfix" style="padding-top: 40px !important;"></div>

			<!-- START CONTENT -->
			<div id="content" class="padrao">
				<?php 
				if (woffice_is_user_allowed()) {
					get_template_part( 'content', 'page' );
					
					$page_comments = woffice_get_settings_option('page_comments');
					// If comments are open or we have at least one comment, load up the comment template.
					if ( $page_comments == "show"){
						if ( comments_open() || get_comments_number()) {
							comments_template();
						}
					}
				}
				else { 
					get_template_part( 'content', 'private' );
				}
				?>
			</div>
				
		</div><!-- END #content-container -->
		
		<?php woffice_scroll_top(); ?>

	</div><!-- END #left-content -->

<?php // END THE LOOP 
endwhile; ?>

<?php 
get_footer();
