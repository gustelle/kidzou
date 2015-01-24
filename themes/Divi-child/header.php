<?php if ( ! isset( $_SESSION ) ) session_start(); ?>
<!DOCTYPE html>
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
	<title><?php elegant_titles(); ?></title>
	<?php elegant_description(); ?>
	<?php elegant_keywords(); ?>
	<?php elegant_canonical(); ?>

	<?php do_action( 'et_head_meta' ); ?>

	<link rel="pingback" href="<?php bloginfo('pingback_url'); ?>" />

	<?php $template_directory_uri = get_template_directory_uri(); ?>
	<!--[if lt IE 9]>
	<script src="<?php echo esc_url( $template_directory_uri . '/js/html5.js"' ); ?>" type="text/javascript"></script>
	<![endif]-->
	<!--[if lte IE 9]>
	<script type="text/javascript">
	    (function () {
	      function CustomEvent ( event, params ) {
	        params = params || { bubbles: false, cancelable: false, detail: undefined };
	        var evt = document.createEvent( 'CustomEvent' );
	        evt.initCustomEvent( event, params.bubbles, params.cancelable, params.detail );
	        return evt;
	      };

	      CustomEvent.prototype = window.Event.prototype;
	  window.CustomEvent = CustomEvent;
	})();
	</script>
	<![endif]-->

	<script type="text/javascript">
		document.documentElement.className = 'js';
	</script>

	<?php wp_head(); ?>

	<script type="application/ld+json">
	{
	  "@context": "http://schema.org",
	  "@type": "WebSite",
	  "url": "https://www.kidzou.fr/",
	  "potentialAction": {
	    "@type": "SearchAction",
	    "target": "http://www.kidzou.fr?s={search_term_string}",
	    "query-input": "required name=search_term_string"
	  }
	}
	</script>

</head>
<body <?php body_class(); ?>>

<?php kz_habillage() ?>

<!-- <div class="habillage"> -->

	
	<div id="page-container">
<?php
	if ( is_page_template( 'page-template-blank.php' ) ) {
		return;
	}

	$et_secondary_nav_items = et_divi_get_top_nav_items();

	$et_phone_number = $et_secondary_nav_items->phone_number;

	$et_email = $et_secondary_nav_items->email;

	$et_contact_info_defined = $et_secondary_nav_items->contact_info_defined;

	$show_header_social_icons = $et_secondary_nav_items->show_header_social_icons;

	$et_secondary_nav = $et_secondary_nav_items->secondary_nav;

	$primary_nav_class = 'et_nav_text_color_' . et_get_option( 'primary_nav_text_color', 'dark' );

	$secondary_nav_class = 'et_nav_text_color_' . et_get_option( 'secondary_nav_text_color', 'light' );

	$et_top_info_defined = $et_secondary_nav_items->top_info_defined;
?>

	<?php if ( $et_top_info_defined ) : ?>
		<div id="top-header" class="<?php echo esc_attr( $secondary_nav_class ); ?>">
			<div class="container clearfix">

				<div id="et-info">
				<?php if ( $et_contact_info_defined ) : ?>
					<?php if ( '' !== ( $et_phone_number = et_get_option( 'phone_number' ) ) ) : ?>
						<span id="et-info-phone"><?php echo esc_html( $et_phone_number ); ?></span>
					<?php endif; ?>

					<?php if ( '' !== ( $et_email = et_get_option( 'header_email' ) ) ) : ?>
						<span id="et-info-email"><?php echo esc_html( $et_email ); ?></span>
					<?php endif; ?>

					<?php
					if ( true === $show_header_social_icons ) {
						get_template_part( 'includes/social_icons', 'header' );
					} ?>
				<?php endif; // true === $et_contact_info_defined ?>
				<?php
					if ( '' !== $et_secondary_nav ) {
						echo $et_secondary_nav;
					}
				?>

				</div> <!-- #et-info -->

				<div id="kz-villes">
					<?php

						//les différentes métropoles dispo
						$active = Kidzou_Utils::get_option('geo_activate', false);
						if ($active)
						{
							$metropoles = Kidzou_GeoHelper::get_metropoles();
							$ttes_metros = '';

							if (count($metropoles)>1) 
							{
								$ttes_metros .= '<i class="fa fa-map-marker"></i>';

								$i=0;
								foreach ($metropoles as $m) {

									if ($i>0)
										$ttes_metros .= '&nbsp;|&nbsp;';

									$ttes_metros .= sprintf(
										'<a class="metropole" data-metropole="%s" href="%s" alt="%s" title="%s">%s</a>',
										$m->slug,
										site_url().'/'.$m->slug,
										$m->name,
										__( 'Changer de ville', 'kidzou' ),
										$m->name
									);

									$i++;

								}
							}

							echo $ttes_metros;	
						}
						
					?>
				</div>


				<div id="et-secondary-menu">
				<?php

					if (!is_user_logged_in()) {

						printf(
							'<a href="%1$s" class="font-bigger"><i class="fa fa-users font-bigger"></i>Connexion</a>',
							get_page_link( 
								Kidzou_Utils::get_option('login_page', '')
							)
						);	

						echo '&nbsp;|&nbsp;<a href="'.wp_registration_url().'" class="et_nav_text_color_light font-bigger">Inscription</a>';
						
					} else {

						printf(
							'<a href="%1$s" class="font-bigger"><i class="fa fa-heart"></i><span>%2$s</span></a>&nbsp;', 
							get_page_link( 
								Kidzou_Utils::get_option('user_favs_page', '')
							),
							__('Vos favoris','Divi')
						);	

						echo '&nbsp;|&nbsp;';

						printf(
							'<a href="%1$s" class="font-bigger"><i class="fa fa-pencil"></i><span>%2$s</span></a>&nbsp;', 
							get_admin_url(),
							current_user_can('edit_posts') ? 'G&eacute;rer vos articles' : 'Votre profil'
						);	

						echo '&nbsp;|&nbsp;<a class="font-bigger" href="'.wp_logout_url( get_permalink() ).'" title="'.__('Deconnexion','Divi').'">'.__('Deconnexion','Divi').'</a>';

					}

					if ( ! $et_contact_info_defined && true === $show_header_social_icons ) {
						get_template_part( 'includes/social_icons', 'header' );
					} else if ( $et_contact_info_defined && true === $show_header_social_icons ) {
						ob_start();

						get_template_part( 'includes/social_icons', 'header' );

						$duplicate_social_icons = ob_get_contents();

						ob_end_clean();

						printf(
							'<div class="et_duplicate_social_icons">
								%1$s
							</div>',
							$duplicate_social_icons
						);
					}

					

					et_show_cart_total();

					

				?>
				</div> <!-- #et-secondary-menu -->

			</div> <!-- .container -->
		</div> <!-- #top-header -->
	<?php endif; // true ==== $et_top_info_defined ?>

		<header id="main-header" class="<?php echo esc_attr( $primary_nav_class ); ?>">
			<div class="container clearfix">
			<?php
				$logo = ( $user_logo = et_get_option( 'divi_logo' ) ) && '' != $user_logo
					? $user_logo
					: $template_directory_uri . '/images/logo.png';
			?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>">
					<img src="<?php echo esc_attr( $logo ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name' ) ); ?>" id="logo" />
				</a>

				<div id="et-top-navigation">
					<nav id="top-menu-nav">
					<?php
						$menuClass = 'nav';
						if ( 'on' == et_get_option( 'divi_disable_toptier' ) ) $menuClass .= ' et_disable_top_tier';
						$primaryNav = '';

						$primaryNav = wp_nav_menu( array( 'theme_location' => 'primary-menu', 'container' => '', 'fallback_cb' => '', 'menu_class' => $menuClass, 'menu_id' => 'top-menu', 'echo' => false ) );

						if ( '' == $primaryNav ) :
					?>
						<ul id="top-menu" class="<?php echo esc_attr( $menuClass ); ?>">
							<?php if ( 'on' == et_get_option( 'divi_home_link' ) ) { ?>
								<li <?php if ( is_home() ) echo( 'class="current_page_item"' ); ?>><a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Home', 'Divi' ); ?></a></li>
							<?php }; ?>

							<?php show_page_menu( $menuClass, false, false ); ?>
							<?php show_categories_menu( $menuClass, false ); ?>
						</ul>
					<?php
						else :
							echo( $primaryNav );
						endif;
					?>
					</nav>

					<?php
					if ( ! $et_top_info_defined ) {
						et_show_cart_total( array(
							'no_text' => true,
						) );
					}
					?>

					<?php if ( false !== et_get_option( 'show_search_icon', true ) ) : ?>
					<div id="et_top_search">
						<span id="et_search_icon"></span>
						<form role="search" method="get" class="et-search-form et-hidden" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<?php
							printf( '<input type="search" class="et-search-field" placeholder="%1$s" value="%2$s" name="s" title="%3$s" />',
								esc_attr_x( 'Search &hellip;', 'placeholder', 'Divi' ),
								get_search_query(),
								esc_attr_x( 'Search for:', 'label', 'Divi' )
							);
						?>
						</form>
					</div>
					<?php endif; // true === et_get_option( 'show_search_icon', false ) ?>

					<?php do_action( 'et_header_top' ); ?>
				</div> <!-- #et-top-navigation -->
			</div> <!-- .container -->
		</header> <!-- #main-header -->

		<div id="et-main-area">