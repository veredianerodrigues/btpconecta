<?php
/**
 * The Template for displaying all single posts
 */

global $post;
$current_user_is_admin = woffice_current_is_admin();
$edit_allowed = (Woffice_Frontend::edit_allowed('post') == true) ? true : false;
$delete_allowed = (Woffice_Frontend::edit_allowed('post', 'delete') == true) ? true : false;
if ($edit_allowed) {

	$process_result = Woffice_Frontend::frontend_process('post', $post->ID);

}

get_header(); ?>

	<?php // Start the Loop.
	while ( have_posts() ) : the_post(); ?>

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
		
			<!-- START THE CONTENT CONTAINER -->
			<div id="content-container">

				<!-- START CONTENT -->
				<div id="content">
					<?php // We check for the role : 
					if (woffice_is_user_allowed()) { ?>
						
						<?php // Include the page content template.
						get_template_part( 'content');
						?>
						
						<?php
						/*
						 * FRONT END EDIT
						if ($edit_allowed || $delete_allowed) { ?>
							
							<div class="frontend-wrapper box">
								<div class="intern-padding">
							
									<div class="center" id="blog-bottom">
									
										<?php if($edit_allowed) : ?>
                                            <a href="javascript:void(0)" class="btn btn-default" id="show-blog-edit"><i class="fa fa-pencil-square-o"></i> <?php _e("Edit Post", "woffice"); ?></a>
                                        <?php endif; ?>

										<?php
										if($delete_allowed) {
                                            echo '<a onclick="return confirm(\'' . __('Are you sure you wish to delete article :', 'woffice') . ' ' . get_the_title() . ' ?\')" href="' . get_delete_post_link(get_the_ID(), '') . '" class="btn btn-default">
												<i class="fa fa-trash-o"></i> ' . __("Delete", "woffice") . '
											</a>';
                                        }
										//}
										 ?>

									</div>

                                    <?php if($edit_allowed) : ?>
                                        <?php Woffice_Frontend::frontend_render('post', $process_result, get_the_ID()); ?>
                                    <?php endif; ?>
									
								</div>
							</div>
						 
						
						<?php  } */ ?>
					
					
						<?php
						// If comments are open or we have at least one comment, load up the comment template.
						if ( comments_open() || get_comments_number() ) {
							//comments_template();
						}
						?>
					
					<?php } else { 
						get_template_part( 'content', 'private' );
					}  ?>

				</div>
					
			</div><!-- END #content-container -->
		
			<?php woffice_scroll_top(); ?>

		</div><!-- END #left-content -->
		
	<?php // END THE LOOP 
	endwhile; ?>

<?php 
get_footer();