<?php
/**
* Template Name: Blog
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
		      
		<header id="featuredbox" class="centered ">
		<div class="pagetitle animate-me fadeIn"><h1 class="entry-title" style="text-indent: -9999px">Notícias do Setor</h1></div>
		<!-- .pagetitle -->
		<div class="featured-background" style="background-image: url(<?php echo get_stylesheet_directory_uri(); ?>/images/header_clipping.jpg)" ;="">
		<!--<div class="featured-layer"></div>-->
		</div>
		</header>

		<!-- START THE CONTENT CONTAINER -->
		<div id="content-container">

			<!-- START CONTENT -->
			<div id="content">
				
				<?php // We check for the layout 
				$blog_layout = woffice_get_settings_option('blog_layout');
				$masonry_columns = woffice_get_settings_option('masonry_columns');
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
						'category_name' => 'noticias-do-setor',
                        'posts_per_page' => $posts_per_page
					), $paged, $posts_per_page);

					$blog_query = new WP_Query($args);
					if ( $blog_query->have_posts() ) :	?>
						<?php while ( $blog_query->have_posts() ) : $blog_query->the_post(); ?>
							<?php // We check for the role : 
							if (woffice_is_user_allowed()) { 								
									get_template_part( 'content' );
							 } ?>
						<?php endwhile; ?>
					<?php else : ?>
						<?php get_template_part( 'content', 'none' ); ?>
					<?php endif; ?>
					
				<?php echo ($blog_layout == "masonry") ? '</div>' : ''; ?>

				<!-- THE NAVIGATION --> 
				<?php woffice_paging_nav($blog_query); ?>
				
				<?php
				/*
				 * FRONT END CREATION
				 */ 
				// CHECK IF USER CAN CREATE BLOG POST
				$post_create = woffice_get_settings_option('post_create'); 
				if ($woffice_role_allowed): ?>
					
					<div class="frontend-wrapper box">
						<div class="intern-padding">
					
							<div class="center" id="blog-bottom">

								<?php
                                /**
                                 * Filter the text of the button "New Blog Article"
                                 *
                                 * @param string
                                 */
                                $new_blog_button_text = apply_filters('woffice_new_blog_article_button_text', __("New Blog Article", "woffice")); ?>
								<?php echo'<a href="javascript:void(0)" class="btn btn-default" id="show-blog-create"><i class="fa fa-plus-square"></i> '. $new_blog_button_text .'</a>'; ?>
								
							</div>
							
							<?php Woffice_Frontend::frontend_render('post',$hasError); ?>
														
						</div>
					</div>
				
				<?php endif; ?>
			</div>
				
		</div><!-- END #content-container -->

		<?php woffice_scroll_top(); ?>
		
	</div><!-- END #left-content -->

<?php 
get_footer(); 