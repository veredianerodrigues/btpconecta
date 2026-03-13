<?php /* Template Name: HomePage*/ 

get_header(); 

?>

	<div id="left-content">
		
		<?php // Start the Loop.
		while ( have_posts() ) : the_post(); 
		
		// Carrega os customs fields
		$custom = get_post_custom();
		
		// Link do banner
		if(isset($custom['link_banner'])) {
			$link_banner = $custom['link_banner'][0];
		} else {
			$link_banner = $link_noticias;
		}
		
		// Link da segurança
		if(isset($custom['link_seguranca'])) {
			$link_seguranca = $custom['link_seguranca'][0];
		} else {
			$link_seguranca = "#";
		}
		
		$class_banner = "";
		$texto_banner = "";
		// Texto do banner
		if(isset($custom['texto_banner'])) {
			$texto_banner = $custom['texto_banner'][0];
		}

		if($texto_banner == "") {
			$class_banner = "hide";
		}
		

		// Dias sem acidente
		if(isset($custom['data_calculo_acidente'])) {
			$dia1=strtotime( $custom['data_calculo_acidente'][0] )+97140;
			$dia2=strtotime( date('d-m-Y H:i', time()) );
			$dias_sem_acidente = (($dia2 - $dia1) / 86400);
		} else {
			$dias_sem_acidente = 0;
		}
		   
		// Checa a imagem principal
		if ( has_post_thumbnail() ) {
		 $image = wp_get_attachment_image_src( get_post_thumbnail_id( $post->ID ), 'full' );		
		  ?>
	<div id="content-top">
		<div class="col-md-8 col-sm-12 defesa-banner-home">
			<div class="banner-home">
				<a href="<?php echo $link_banner; ?>" class="banner-home-img left-banner" style="background-image: url(<?php echo $image[0]; ?>); ">
					<span class="conteudo-texto <?php echo $class_banner; ?>">
						<?php echo $texto_banner; ?>
					</span>
				</a>
			</div>
		</div>
		
		<?php } 
			 // END THE LOOP 
			endwhile; 
			wp_reset_query(); 
		?>

		<div class="col-md-4 col-sm-12 noticias-destaque-home">
			<?php query_posts('cat=24&showposts=3');?>
			<ul>
			<?php while ( have_posts() ) : the_post(); ?>   
			
			  <li>
				<h4><a href="<?php the_permalink();?>"><?php the_title();?></h4></a>
				<p><a href="<?php the_permalink();?>"><?php the_excerpt();?></a></p>
				<a class="leia-plus" href="<?php the_permalink();?>"><i class="fa fa-plus"></i></a>
			  </li>
			
			<?php endwhile;
			wp_reset_query(); ?>			
			</ul>
			<?php 	$link_noticias = get_home_url() . '/acontece-na-btp';
					$link_noticias_setor = get_home_url() . '/noticias-do-setor';
					$link_risco = 'https://linktr.ee/safetybtp';
					$link_universidade = 'https://universidadebtp.edusense.app/#/'; ?>
			
			<a href="<?php echo $link_noticias; ?>" class="link-leia-home">+ <strong>Leia tudo</strong> o que Acontece na BTP</a>
		</div>
		</div>	
		<div class="clearfix"></div>

		<!-- START THE CONTENT CONTAINER -->
		<div id="content-container">

			<!-- START CONTENT -->
			<div id="content">
				
				<!-- CONTEÚDO HOME -->
				
				<div id="botoes_home">
					
				  	<div class="flex-grid">
				  	
				  	   <div class="col">
					  	<a  href="<?php echo get_home_url() . '/cardapio'; ?>">
						<img src="<?php echo get_stylesheet_directory_uri();?>/images/botao_home_cardapio.jpg" alt="Cardápio"/>
						</a>
					  </div>
					  
					  <div class="col">
					  	<a  href="<?php echo $link_universidade; ?>" target="_blank">
						<img src="<?php echo get_stylesheet_directory_uri();?>/images/faculdade_btp-b.png" alt="Universidade BTP"/>
						</a>
					  </div>
					  
					  <div class="col">
					  	<a  href="https://portaldocliente.btp.com.br/">
						<img src="<?php echo get_stylesheet_directory_uri();?>/images/portaldocliente.png" alt="Portal do Cliente"/>
						</a>
					  </div>
					</div>
					
					<div class="flex-grid">
					  
					  <div class="col">
					  	<a  href="<?php echo get_home_url() . '/propor'; ?>">
						<img src="<?php echo get_stylesheet_directory_uri();?>/images/botao_home_propor.jpg" alt="Propor"/>
						</a>
					  </div>
					  
					  <div class="col">
					  	<a  href="<?php echo $link_risco;?>" target="_blank">
					  	<img src="<?php echo get_stylesheet_directory_uri();?>/images/botao_home_risco.png" alt="#DeOlhoNoRisco"/>
						</a>
					  </div>					  
					  
					  <div class="col">
					  	<div class="info seguranca linkSeguranca" data-link="<?php echo $link_seguranca;?>">
				  		<span class="cont-dias"><span>Estamos há</span> <span class="contagemDias"><?php echo $dias_sem_acidente; ?></span></span>
					  	<span class="texto-dias"></br> dias sem acidente com afastamento</span> 
					  	<img src="<?php echo get_stylesheet_directory_uri();?>/images/botao_home_seguranca.jpg" alt="Segurança"/>						  
						</div>
					  </div>
					  
					</div>

					
				</div>
		
				<!-- CONTEÚDO HOME -->				
				
				
			</div>
				
		</div><!-- END #content-container -->

	</div><!-- END #left-content -->
	<?php 
get_footer();
