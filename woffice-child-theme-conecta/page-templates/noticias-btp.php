<?php
/**
* Template Name: Acontece na BTP
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
		      
		<header id="featuredbox" class="centered ">
		<div class="pagetitle animate-me fadeIn"><h1 class="entry-title">Acontece na BTP</h1></div>
		<!-- .pagetitle -->
		<div class="featured-background" style="background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/images/header_padrao.jpg)" ;="">
		<!--<div class="featured-layer"></div>-->
		</div>
		</header>

		<!-- START THE CONTENT CONTAINER -->
		<div id="content-container">

			<!-- START CONTENT -->
			<div id="content" class="acontece-btp">
				
				<?php // We check for the layout 
				$blog_layout = "masonry";
				$masonry_columns = 4;
				$masonry_columns_class = 'masonry-layout--'.$masonry_columns.'-columns';

				echo ($blog_layout == "masonry") ? '<div id="directory" class="masonry-layout '.$masonry_columns_class.'">' : ''; ?>
				
					<?php 
					// THE LOOP :
                    $posts_per_page = woffice_get_settings_option('blog_number');

					$pagination_slug = (is_front_page()) ? 'page' : 'paged';
					$paged = (get_query_var($pagination_slug)) ? get_query_var($pagination_slug) : 1;

                    /**
                     * Filter args of the blog posts query
                     *
                     * @param array $args
                     * @param int $paged
                     * @param int $posts_per_page
                     */
					$args = apply_filters('woffice_blog_query_args', array(
						'post_type' => 'post',
						'paged' => $paged,
						'category_name' => 'acontece-na-btp',
                        'posts_per_page' => $posts_per_page
					), $paged, $posts_per_page);

					$blog_query = new WP_Query($args);
					if ( $blog_query->have_posts() ) :	?>
						<?php while ( $blog_query->have_posts() ) : $blog_query->the_post(); ?>
							<?php // We check for the role : 
							if (woffice_is_user_allowed()) { 								
									get_template_part( 'content', 'acontecebtp' );
							 } ?>
						<?php endwhile; ?>
					<?php else : ?>
						<?php get_template_part( 'content', 'none' ); ?>
					<?php endif; ?>
					
				<?php echo ($blog_layout == "masonry") ? '</div>' : ''; ?>

				<!-- THE NAVIGATION --> 
				<?php woffice_paging_nav($blog_query); ?>				
				
			</div>
				
		</div><!-- END #content-container -->

		<?php woffice_scroll_top(); ?>
		
	</div><!-- END #left-content -->

<?php 
get_footer(); 