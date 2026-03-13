<?php
/* Template Name: Aniversariantes do Mês*/ 

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
			
			
				<ul id="lista-usuarios">
				<?php if ( bp_has_members() ) : ?>
					<?php while ( bp_members() ) : bp_the_member(); 

					$aniversario = xprofile_get_field_data( 'Birthday', bp_get_member_user_id() );
					$aniversario_date = date_create($aniversario);
					$mes_aniversario =  date("M",strtotime($aniversario));
					$mes = date("M");
					
					if($mes_aniversario == $mes ){
					?>
					
					 <li class="col-md-3 col-sm-4 col-xs-12">
					  <div class="item-avatar">
						 <a href="<?php bp_member_permalink(); ?>" style="text-align: center"><?php bp_member_avatar(); ?></a>
					  </div>

					  <div class="item">
						<div class="item-title">
						   <a href="<?php bp_member_permalink(); ?>"><?php bp_member_name(); ?></a>

						   <p class="data_aniversario">Aniversário: <strong><?php echo $aniversario_date->format('d/m');; ?></strong></p>

					   </div>

					   <!--<div class="item-meta"><span class="activity"><?php// bp_member_last_active(); ?></span></div>-->
					   
					  
				   </li>
					
					<?php }
					endwhile;			

					endif;
					?>
				</ul>
			</div>
				
		</div><!-- END #content-container -->
		
		<?php woffice_scroll_top(); ?>

	</div><!-- END #left-content -->

<?php // END THE LOOP 
endwhile; ?>

<?php 
get_footer();
