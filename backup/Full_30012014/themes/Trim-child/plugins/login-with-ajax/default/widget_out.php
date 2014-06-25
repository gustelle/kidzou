<?php 
/*
 * This is the page users will see logged out. 
 * You can edit this, but for upgrade safety you should copy and modify this file into your template folder.
 * The location from within your template folder is plugins/login-with-ajax/ (create these directories if they don't exist)
*/
?>

	<a href="#" id="LoginWithAjax_Links_Login"><?php _e('Log In') ?></a>

	<div id="LoginWithAjax_Modal" style="display:none;" class="shadow-light">
	        <!--span class="close">X</span-->
	        <form class="lwa-form" action="<?php echo esc_attr(LoginWithAjax::$url_login); ?>" method="post">
	        	<?php if (function_exists('kz_social_login')) {echo kz_social_login();} ?>
	        	<!-- <p>
					<label for="user_login">Identifiant<br />
					<input type="text" name="log" id="user_login" class="radius-light" value="" /></label>
				</p>
				<p>
					<label for="user_pass">Mot de passe<br />
					<input type="password" name="pwd" id="user_pass" class="radius-light" value="" /></label>
				</p> -->
				
				<!-- <p class="forgetmenot"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="forever"  /> Se souvenir de moi</label></p> -->
				<p class="submit">
	                <input type="hidden" name="lwa_profile_link" value="<?php echo esc_attr($lwa_data['profile_link']); ?>" />
	                <input type="hidden" name="login-with-ajax" value="login" />
					<!-- <input type="submit" name="lwa_wp-submit" id="lwa_wp-submit" class="kz-button kz-bg-blue radius-light" value="Se connecter" /> -->
				</p>
				<p style="clear:both;"></p>
	        </form>
	        <p id="LoginWithAjax_Links">
				<a id="LoginWithAjax_Links_Remember" href="<?php echo site_url('wp-login.php?action=lostpassword', 'login') ?>"><?php _e('Lost your password?') ?></a><?php
                    //Signup Links
                    if ( get_option('users_can_register') ) {
                        if ( function_exists('bp_get_signup_page') ) { //Buddypress
                        	$register_link = bp_get_signup_page();
                        }elseif ( file_exists( ABSPATH."/wp-signup.php" ) ) { //MU + WP3
                            $register_link = site_url('wp-signup.php', 'login');
                        } else {
                            $register_link = site_url('wp-login.php?action=register', 'login');
                        }
                        ?>&nbsp;|&nbsp;<a href="<?php echo $register_link ?>" id="LoginWithAjax_Links_Register"><?php _e('Register') ?></a><?php
                    }
                ?>
			</p>
	        <form class="lwa-remember" action="<?php echo esc_attr(LoginWithAjax::$url_remember); ?>" method="post" style="display:none;">
	        	<p>
					<label for="lwa_user_remember" >Identifiant ou adresse de messagerie&nbsp;:<br />
					<input type="text" name="user_login" id="lwa_user_remember" class="radius-light" value="<?php echo $msg ?>" onfocus="if(this.value == '<?php echo $msg ?>'){this.value = '';}" onblur="if(this.value == ''){this.value = '<?php echo $msg ?>'}"/></label>
				</p>
				<p class="submit">
					<input type="hidden" name="login-with-ajax" value="remember" />   
					<input type="submit" name="wp-submit-remember" id="wp-submit-remember" class="kz-button kz-bg-blue radius-light" value="Générer un mot de passe" />
				</p>
				<a href="#" id="LoginWithAjax_Links_Remember_Cancel"><?php _e("Cancel"); ?></a>
	        </form>
	       
	        <?php 
			//Taken from wp-login.php
			?>
	        <?php if ( get_option('users_can_register') && $lwa_data['registration'] == true ) : ?>

				<form class="lwa-register-form" action="<?php echo esc_attr(LoginWithAjax::$url_register); ?>" method="post" style="display:none;">
					<p>
						<label for="user_id">Identifiant<br />
						<input type="text" name="user_login" id="user_id" class="radius-light" value="" /></label>
					</p>
					<p>
						<label for="user_email">E-mail<br />
						<input type="text" name="user_email" id="user_email" class="radius-light"  value="" /></label>
					</p>
					
					<?php do_action('register_form'); ?>
					<?php do_action('lwa_register_form'); ?>
					<p id="reg_passmail">Un mot de passe vous sera envoyé sur votre adresse de messagerie.</p>
					<p class="submit">
						<input type="hidden" name="login-with-ajax" value="register" />
						<input type="submit" name="wp-submit-register" id="wp-submit-register" class="kz-button kz-bg-blue radius-light" value="Inscription" />
					</p>
					<a href="#" id="LoginWithAjax_Links_Register_Cancel"><?php _e("Cancel"); ?></a>
				</form>

			<?php endif; ?>	
	</div>
