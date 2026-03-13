<?php
/* Template Name: Imagem Única */ 
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
		?>		  
		  

		<!-- START THE CONTENT CONTAINER -->
		<div id="content-container">

			<!-- START CONTENT -->
			<div id="content" class="imagem-unica">
				<?php 
				if (woffice_is_user_allowed()) {
					get_template_part( 'content', 'page' );					
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
