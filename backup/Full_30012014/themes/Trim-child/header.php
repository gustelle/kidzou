<!doctype html>
<!--[if IE 6]>
<html id="ie6" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 7]>
<html id="ie7" <?php language_attributes(); ?>>
<![endif]-->
<!--[if IE 8]>
<html id="ie8" <?php language_attributes(); ?>>
<![endif]-->
<!--[if !(IE 6) | !(IE 7) | !(IE 8)  ]><!-->
<html <?php language_attributes(); ?>>
<!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta http-equiv="content-type" content="<?php bloginfo('html_type'); ?>; charset=<?php bloginfo('charset'); ?>" />
	<title><?php elegant_titles(); ?></title>
	<?php elegant_description(); ?>
	<?php elegant_keywords(); ?>
	<?php elegant_canonical(); ?>

	<?php do_action('et_head_meta'); ?>

	<link rel="stylesheet" href="<?php bloginfo('stylesheet_url'); ?>" type="text/css" media="screen" />
	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

	<!--[if lt IE 7]>
		<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/ie6style.css" />
		<script type="text/javascript" src="<?php echo get_template_directory_uri(); ?>/js/DD_belatedPNG_0.0.8a-min.js"></script>
		<script type="text/javascript">DD_belatedPNG.fix('img#logo, span.overlay, a.zoom-icon, a.more-icon, #menu, #menu-right, #menu-content, ul#top-menu ul, #menu-bar, .footer-widget ul li, span.post-overlay, #content-area, .avatar-overlay, .comment-arrow, .testimonials-item-bottom, #quote, #bottom-shadow, #quote .container');</script>
	<![endif]-->
	<!--[if IE 7]>
		<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/ie7style.css" />
	<![endif]-->
	<!--[if IE 8]>
		<link rel="stylesheet" type="text/css" href="<?php echo get_template_directory_uri(); ?>/css/ie8style.css" />
	<![endif]-->
	<!--[if lt IE 8]>
		<script src="<?php echo plugins_url(); ?>/kidzou/js/json2.js" type="text/javascript"></script>
	<![endif]-->
	<!--[if lt IE 9]>
		<script src="<?php echo get_template_directory_uri(); ?>/js/html5.js" type="text/javascript"></script>
	<![endif]-->

	<!--script type="text/javascript">
		document.documentElement.className = 'js';
	</script-->

	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?> <?php if (function_exists('kz_webpage_microdata')) { kz_webpage_microdata();} ?> >

	<noscript>
		<p class="warning">
			Javascript est d&eacute;sactiv&eacute; sur ce navigateur, en cons&eacute;quence le site Kidzou aura quelques limitations. En particulier :<br/>
		<p>
		<ul class="warning">
			<li>Vous ne pourrez pas recommander des articles</li>
			<li>La page "Agenda" ne fonctionne pas</li>
			<li>Vous ne pourrez pas visualiser le d&eacute;tail des activit&eacute;s propos&eacute;es sur la Home Page et dans les articles</li>
			<li>Le menu de navigation ne fonctionnera pas correctement</li>
			<li>Vous ne pourrez pas commenter les articles</li>
			<li>L&apos;affichage du site sera d&eacute;grad&eacute;</li>
			<li>Vous ne pourrez pas vous identifier sur cette page. Pour vous identifier, consultez la <a href="http://www.kidzou.fr/wp-login.php" title="page d'identification">Page d&apos;identification</a></li>
		</ul>
	</noscript>

	<script type="text/html" id="vote-template">
	    <a href="#" data-bind="event: { click: $data.doUpOrDown, mouseover: $data.activateDown, mouseout: $data.deactivateDown }">
			<i 		data-bind="css : $data.iconClass"></i>
			<span 	data-bind="text: $data.votes"></span>
			<span 	data-bind="html: $data.countText"></span>
	    </a>
	</script>

	<?php if (function_exists('kz_top_panel')) { kz_top_panel();} ?>
	<div id="container">
		<div id="wrapper">
			<header id="main-header" class="clearfix" itemscope itemtype="http://schema.org/WPHeader">
				<div id="top-area">

					<div id="messageBox" data-bind="with: message" class="radius-light warning">
						<span data-bind="html: messageContent"></span>
					</div>

					<?php do_action('et_header_top'); ?>

					<div class="top">

						<!--span class="kz-button"-->
							<a rel="author"		href="<?php echo get_option('trim_facebook_url') ?>" 			title="Kidzou sur Facebook">
								<img class="fbicon" alt="retrouvez-nous sur Facebook" src="<?php echo get_stylesheet_directory_uri() ?>/images/FB-f-Logo__blue_29.png"/>
							Retrouvez-nous sur Facebook
							</a>
							<span class="booticon-bookmark"></span>
							<a href="#" class="share">Restez inform&eacute;s</a>&nbsp;|&nbsp;
						<!--/span-->

						<?php if (is_user_logged_in()) { 
							global $current_user;
     						get_currentuserinfo();
							echo "Bienvenue ".$current_user->display_name; ?>&nbsp;|&nbsp;<a href="<?php echo wp_logout_url( get_permalink() ); ?>" title="Logout">D&eacute;connexion</a>
						<?php } else { ?>
							<!--span class="kz-button"-->
								<span class="booticon-login"></span><a href="#" class="login">Connexion</a>
							<!--/span-->
						<?php } ?>

						<div class="search">
							<form method="get" id="searchform" action="<?php echo esc_url( home_url( '/' ) ); ?>/">
								<!-- <label for="searchinput">Recherchez sur Kidzou :</label> -->
								<input type="search" placeholder="<?php esc_attr_e('On va o&ugrave; &agrave; Lille ?', 'Trim'); ?>" name="s" id="searchinput" title="Entrez des mots tels que week-end ou vacances"   /> 
								<!--input type="image" src="< ?php echo esc_url( get_template_directory_uri() . '/images/search_btn.png' ); ?>" id="searchsubmit" alt="Cliquez ici pour lancer la recherche"/-->
							</form>
						</div> <!-- end .search -->

					</div> <!-- end .top -->

					

					<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php $logo = (get_option('trim_logo') <> '') ? esc_attr(get_option('trim_logo')) : get_template_directory_uri() . '/images/logo.png'; ?>
						<img src="<?php echo esc_attr( $logo ); ?>" alt="<?php echo esc_attr(get_bloginfo('name')); ?>" id="logo"/>
					</a>
					
				</div> <!-- end #top-area -->

				<div id="menu" class="menu clearfix">
					<?php do_action('et_header_menu'); ?>

					<nav id="main-menu" class="rubriques" itemscope itemtype="http://schema.org/SiteNavigationElement">
						<meta itemprop="headline" 		content="<?php echo get_the_title(); ?>">
						<?php 
							if (is_single()) { 
								$thumb = wp_get_attachment_image_src( get_post_thumbnail_id($post->ID), 'thumbnail' );
								$url = $thumb['0'];
								echo '<meta itemprop="thumbnailUrl" 	content="'.$url.'">';
							}  
							$menuClass = 'nav';
							if ( get_option('trim_disable_toptier') == 'on' ) $menuClass .= ' et_disable_top_tier';
							$primaryNav = '';
							if (function_exists('wp_nav_menu')) {
								$primaryNav = wp_nav_menu( array( 'theme_location' => 'primary-menu', 'container' => '', 'fallback_cb' => '', 'menu_class' => $menuClass, 'echo' => false, 'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>', 'walker' => new kz_walker_nav() ) );
							}
							if ($primaryNav == '') { ?>

								<ul class="<?php echo esc_attr( $menuClass ); ?>">
									<?php if (get_option('trim_home_link') == 'on') { ?>
										<li <?php if (is_home()) echo('class="current_page_item"') ?>><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e('Home','Trim') ?></a></li>
									<?php }; ?>

									<?php show_page_menu($menuClass,false,false); ?>
									<?php show_categories_menu($menuClass,false); ?>
								</ul>
							<?php }
							else echo($primaryNav);
						?>
					</nav>
					<nav class="transverse">
						<?php wp_nav_menu( array( 'menu' => 'Menu Transverse', 'container' => '', 'fallback_cb' => '', 'menu_class' => $menuClass, 'echo' => true, 'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s</ul>' ) ); ?>
					</nav>

				</div> <!-- end #menu -->
			
			</header> <!-- end #main-header -->

			<?php
				if ( 'on' == get_option('trim_featured') && is_home() ) get_template_part( 'includes/featured', 'home' );
				else echo '<div id="content">';
			?>

			<?php if ( is_home() ) get_template_part( 'events', 'home' );?>

