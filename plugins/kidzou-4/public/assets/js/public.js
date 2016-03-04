//support de la console
window.console = typeof window.console === 'undefined'
    ? {log:function(str){alert(str)}}
    : window.console;
    
//thanks http://www.chrisbuttery.com/articles/fade-in-fade-out-with-javascript/
// fade out
function fadeOut(el){
  el.style.opacity = 1;
  (function fade() {
    if ((el.style.opacity -= .1) < 0) {
      el.style.display = "none";
    } else {
      requestAnimationFrame(fade);
    }
  })();
}

// fade in
function fadeIn(el, display){
  el.style.opacity = 0;
  el.style.display = display || "block";
  (function fade() {
    var val = parseFloat(el.style.opacity);
    if (!((val += .1) > 1)) {
      el.style.opacity = val;
      requestAnimationFrame(fade);
    }
  })();
}

var kidzouTracker = (function() {

	//ne pas tracker en dev et ne pas tracker les admins
	var _do_track = kidzou_commons_jsvars.analytics_activate;

	function trackEvent(context, action, title, loadtime) {
		if (_do_track) {
			ga('send', 'event', context, action, title, loadtime);
		}
        else {
        	console.debug("trackEvent(" + context + ", " + action + ", " + title + ", " + loadtime + ")");
        }
  	}

  	return {
  		trackEvent : trackEvent
  	};

}());

var kidzouNewsletter = (function() {

	function subscribe(form) {

		jQuery.ajax({

			type: "POST",
			url: kidzou_commons_jsvars.api_newsletter_url,
			data:
			{
				nonce 		: kidzou_commons_jsvars.api_newsletter_nonce,
				firstname 	: (kidzou_commons_jsvars.newsletter_fields.firstname=='1' && form.querySelector('[name="firstname"]') ? form.querySelector('[name="firstname"]').value : ''),
				lastname 	: (kidzou_commons_jsvars.newsletter_fields.lastname=='1' && form.querySelector('[name="lastname"]') ? form.querySelector('[name="lastname"]').value : ''),
				email 		: form.querySelector('[name="email"]').value,
				zipcode 	: (kidzou_commons_jsvars.newsletter_fields.zipcode=='1' && form.querySelector('[name="zipcode"]') ? form.querySelector('[name="zipcode"]').value : ''),
				key 		: kidzou_commons_jsvars.mailchimp_key,
				list_id 	: kidzou_commons_jsvars.mailchimp_list
			},
			beforeSend : function() {

				//afficher un message de patience
				document.querySelector('#newsletter_form button').disabled = true;
				document.querySelector('#newsletter_form_error_message').innerHTML = '';

				document.querySelector('#newsletter_form_error_message').innerHTML = kidzou_commons_jsvars.form_wait_message;

				var myEvent = new CustomEvent("newsletter_subscribing", {
					detail: {}
				});

				// Trigger it!
				document.dispatchEvent(myEvent);

			},
			success: function( data ){

				document.querySelector('#newsletter_form_error_message').innerHTML = '';
				document.querySelector('#newsletter_form input').classList.remove('error');

				//pas d'erreur dans l'API
				if (data.status=='ok') {

					//erreur fonctionnelle de valdation
					if (data.result == 'error') {

						//re-afficher le bouton de soumission du formulaire
						document.querySelector('#newsletter_form button').disabled = false;
						var fields = data.fields ;
						for (x in fields) {
							// console.debug(x);
							var field = fields[x];
						    document.querySelector('#newsletter_form_error_message').innerHTML += field.message;
						    document.querySelector('#newsletter_form input[name="' + x + '"]').classList.toggle('error');
						}
					
					} else {

						document.querySelector('#newsletter_form_error_message').innerHTML = data.message;

						kidzouTracker.trackEvent("Newsletter", 'subscribe', '', 0);
					}
					
				//erreur technique dans l'API
				} else {
					document.querySelector('#newsletter_form button').disabled = false;
					document.querySelector('#newsletter_form_error_message').innerHTML = kidzou_commons_jsvars.form_error_message ;

				}

				var myEvent = new CustomEvent("newsletter_subscribed", {
					detail: {status: data.status, result:data.result}
				});

				// Trigger it!
				document.dispatchEvent(myEvent);

			}

		} );

		//ne pas oublier d'envoyer l'event à Google Analytics
		if (typeof kidzouTracker !== 'undefined') {
			kidzouTracker.trackEvent("Notification", "Newsletter", 'Subscribe' , 0);
		}
	
		//soumission ajax, on reste sur la page
		return false;
	}


	return {
		subscribe : subscribe
	}			
	
}());









