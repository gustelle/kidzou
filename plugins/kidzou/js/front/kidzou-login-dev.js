var kidzouLogin = (function() {

	/////////////// Login ////////////////
	//////////////////////////////////////

	jQuery(".login").click(function(){
		openLoginDialog();
	});


	var openLoginDialog = function (msg) 
	{
		var self = this;
		var social_login_msg = 
			'<a class="social google" href="http://www.kidzou.fr/wp-login.php?loginGoogle=1" rel="nofollow" ><div class="new-google-btn new-google-13"><div class="new-google-13-1"></div></div>&nbsp;&nbsp;Identifiez vous avec Google</a><br/>' + 
			'<a class="social facebook" href="http://www.kidzou.fr/wp-login.php?loginFacebook=1" rel="nofollow"><div class="new-fb-btn new-fb-13"><div class="new-fb-13-1"></div></div>&nbsp;&nbsp;Identifiez vous avec Facebook</a>';
		
		if (typeof msg!=="undefined")
			msg = '<br/><span class="error">' + msg + '</span>';
		else
			msg = '';

		vex.defaultOptions.contentClassName 	= 'login';
		vex.defaultOptions.className 			= 'vex-theme-top';
		vex.defaultOptions.overlayClosesOnClick = true;

		//Recupérer le modèle message du ViewModel de ko
		self.message = ko.dataFor( document.getElementById('messageBox') ).message;

		vex.dialog.open({
            message: '' +
            	'Vous pouvez vous connecter par <em>Google</em>, <em>Facebook</em>, ou en entrant vos <em>identifiants Kidzou </em>:<br/>' +
            	social_login_msg +
            	msg + 
            '',
            input: '' +
                '<input name="username" type="text" placeholder="Identifiant" required />' +
                '<input name="password" type="password" placeholder="Mot de passe" required />' +
                '<p class="forgetmenot"><label for="rememberme"><input name="rememberme" type="checkbox" id="rememberme" value="1"  /> Se souvenir de moi</label></p> ' +
                '<p><br/><a href="'+ kidzou_commons_jsvars.cfg_lost_password_url +'">Mot de passe oublié</a> | <a href="' + kidzou_commons_jsvars.cfg_signup_url + '">Cr&eacute;er un compte</a></p>' +
            '',
            buttons: [
                jQuery.extend({}, vex.dialog.buttons.YES, { text: 'Valider' }),
                jQuery.extend({}, vex.dialog.buttons.NO, { text: 'Abandonner' })
            ],
            callback: function (data) {
            	if (data) { //si on clique sur "Abandonner", data=false
            		
            		kidzouTracker.trackEvent("Connexion", "Valider", "", 0);
            		self.message.addMessage('info', kidzou_commons_jsvars.msg_auth_onprogress);
	                
	                jQuery.getJSON(kidzou_commons_jsvars.api_get_nonce,{controller: 'auth',	method: 'generate_auth_cookie'})
						.done(function (d) {
							if (d!==null) {
					           //authenticate with the nonce
					           jQuery.getJSON(kidzou_commons_jsvars.api_generate_auth_cookie, {
										username: data.username, 
										password: data.password,
										rememberme : data.rememberme,
										nonce: d.nonce,
										referer : location.href
									}, function(d2) {
										
										if (d2!==null && d2.status==="ok") {
											self.message.addMessage("info",kidzou_commons_jsvars.msg_auth_success);

											//redirection guidée par le role du user
											//la page de redirection est renvoyée par l'API
											window.location.href = d2.redirect;
										} else {
											self.message.addMessage("error",kidzou_commons_jsvars.msg_auth_failed);
											kidzouLogin.openLoginDialog(d2.error);
											self.message.removeMessage();
										}
									}
								); 
					        }
				        });

            	}
            	else
            		kidzouTracker.trackEvent("Connexion", "Abandonner", "", 0);
            }
        });
	};

	return {
		openLoginDialog : openLoginDialog
	};
	
}());