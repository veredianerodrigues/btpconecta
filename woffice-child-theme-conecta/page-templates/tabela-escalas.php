<?php
/**
 * Template Name: Turnos de Trabalho
 */

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
		 	$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium' );	}
		else {
			$image[0] = get_stylesheet_directory_uri()."/images/header_padrao.jpg";
		}
		?>		  
		  
		<header id="featuredbox" class="centered ">
		<div class="pagetitle animate-me fadeIn"><h1 class="entry-title"><?php echo get_the_title(); ?></h1></div>
		<!-- .pagetitle -->
		<div class="featured-background" style="background-image: url(<?php echo $image[0]; ?>)" ;="">
		<!--<div class="featured-layer"></div>-->
		</div>
		</header>

		<!-- START THE CONTENT CONTAINER -->
		<div id="content-container">

			<!-- START CONTENT -->
			<div id="content">
				<div class="tabela_escalas">
				
				<?php 				
					get_template_part( 'content', 'page' );
				?>
				
				</div>
			</div>
				
		</div><!-- END #content-container -->

	</div><!-- END #left-content -->

<?php // END THE LOOP 
endwhile; ?>

<?php 
get_footer();