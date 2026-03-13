<?php
/**
* Template Name: Programas Sociais
*/

/** CHECK IF USER CAN CREATE BLOG POST
$post_create = woffice_get_settings_option('post_create');
$woffice_role_allowed = Woffice_Frontend::role_allowed($post_create, 'post');
if ($woffice_role_allowed):
	
	$hasError = Woffice_Frontend::frontend_process('post');
	
endif;
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
		      
		<?php
		
		// Checa a imagem principal
		if ( has_post_thumbnail() ) {
		 	$image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'medium' );	}
		else {
			$image[0] = get_template_directory_uri()."/images/header_padrao.jpg";
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
			<div id="content" class="bottom-less">
				
				
					<?php 
					// THE LOOP :
                    $posts_per_page = 100;

					$pagination_slug = (is_front_page()) ? 'page' : 'paged';
					$paged = (get_query_var($pagination_slug)) ? get_query_var($pagination_slug) : 1;
				
					$args = apply_filters('woffice_blog_query_args', array(
						'post_type' => 'post',
						'paged' => $paged,
						'category_name' => 'programas-sociais',
                        'posts_per_page' => $posts_per_page
					), $paged, $posts_per_page);

					$blog_query = new WP_Query($args);
					if ( $blog_query->have_posts() ) :	?>
						<?php while ( $blog_query->have_posts() ) : $blog_query->the_post(); 
								
							get_template_part( 'content', 'programas' ); ?>
							
						<?php endwhile; ?>
					<?php else : ?>
						<?php get_template_part( 'content', 'none' ); ?>
					<?php endif; ?>
								
						
								
					<?php	
				
					// PROJETOS INCENTIVADOS		
				
					$args = apply_filters('woffice_blog_query_args', array(
						'post_type' => 'post',
						'paged' => $paged,
						'category_name' => 'projetos-incentivados',
                        'posts_per_page' => $posts_per_page
					), $paged, $posts_per_page);

					$blog_query = new WP_Query($args);
					if ( $blog_query->have_posts() ) :	?>
					
					<div class="intern-padding clearfix">
					<h2 class="super-titulo-vazado">PROJETOS INCENTIVADOS</h2>
					
					<div class="texto-destacado">
					<p>A BTP investe em projetos que acredita e tem orgulho em fazer parte. Incentivar iniciativas que estejam em linha com sua Missão, Visão e Valores é uma das formas que a empresa escolheu para contribuir com a sociedade. A partir de leis de incentivo fiscal, o terminal redireciona aportes para ações de cunho social, cultural e esportivo. Conheça algumas das ações:</p>
					</div>
					</div>
						<?php while ( $blog_query->have_posts() ) : $blog_query->the_post(); 
								
							get_template_part( 'content', 'projetos' ); 
						?>
							
						<?php endwhile; ?>
					<?php endif; ?>
									
			</div>
				
		</div>
		
	</div><!-- END #left-content -->

<?php 
get_footer(); 