var kidzouNotifier = (function(){

	//le système de notification est-il actif ?
	var active = kidzou_notif.activate;
	
	//tous les messages qui ont déjà été lus par le user
	var notificationsRead = null;

	//chaque post a un contexte de notification spécifique
	//de sorte que les contenus poussés sur chaque post sont différents
	var pageId = kidzou_notif.messages.context || 'daily';

	//les notifications pour cette page (ce contexte)
	//Une notification est composée d'un contexte + un ensemble de message {context: xx, messages: [xx,xx,xx]}
	var thisContextNotifications = null;
		

	//les messages qui font sens pour cette page
	//c'est à dire les messages qui n'ont pas encore été lus 
	function getUnreadMessages(_is_page_voted, _current_page_id) {

		var messages = [];

		//recupérer les notifications présentes dans le storageSupport
		notificationsRead = storageSupport.fromLocalData('messages') || [] ;

		ko.utils.arrayForEach(notificationsRead, function(n) {
			if (n.context == pageId) {
				thisContextNotifications = n;
			}
		});

		if (thisContextNotifications==null) {
			thisContextNotifications = {context: pageId, messages: []};
		}
			
		ko.utils.arrayForEach(kidzou_notif.messages.content, function(m) {

			var amess = new Message(m.id, m.title, m.body, m.target, m.icon);

			//si le post est déjà voté, on écarte le message d'incitation au vote
			//de même si le post à recommander est déjà le post sur lequel on se trouve
			if ( ( _is_page_voted && m.id=='vote' ) || ( _current_page_id == m.id ) ) amess.readMe();
			
			ko.utils.arrayForEach(thisContextNotifications.messages, function(alreadyRead) {
			    if ( alreadyRead == m.id  )  amess.readMe();
			});

			if (!amess.isRead()) messages.push(amess);
		});

		return messages;
	
	}
	
	/**
	 * chaque message de notification est un modle objet
	 * 
	 */ 
	function Message(_id, _title, _body, _target, _icon) {

		var self = this;

		//un message est identifié de manière unique par un id de sorte de pouvoir le retrouver
		self.id = _id;
		self.title = _title;
		self.body = _body;
		self.target = _target;
		self.icon = _icon;

		//le message a-t-il déjà été vu
		self.readFlag = false;

		self.readMe = function() {
			self.readFlag = true;
		};

		self.isRead = function() {
			return self.readFlag;
		}
	}

	/**
	 *
	 * lorsqu'un message est lu, il est flaggué pour ne plus le représenter au user
	 **/
	function setMessageRead(m) {
	
		m.readMe();

		// thisContextMessages.;
		thisContextNotifications.messages.push(m.id);

		var exist = false;

		ko.utils.arrayForEach(notificationsRead, function(n) {
			if (n.context == pageId) {
				//remplacer l'existant
				n = thisContextNotifications;
				exist = true;
			}
		});

		if (!exist)
			notificationsRead.push(thisContextNotifications);

		//sur chaque page ou tous les mois
		var expiration = 30;
		
		if (pageId=='daily')
			expiration = 1;
		else if (pageId=='weekly')
			expiration = 7;

		storageSupport.toLocalData('messages', notificationsRead, expiration );

	}

	/**
	 * choix du message a afficher
	 */
	function chooseMessage (messages) {
		
		var unread = ko.utils.arrayFilter(messages, function(m) {
            return !m.readFlag;
        });

      	//ke premier de la liste
      	return unread[0];

	}

	function displayMessage(m) {

		var boxcontent = '';

		//l'id du post wordpress
		var current_page_id = kidzouModule.getCurrentPageId();

		//le votable est récupéré du model, on peut donc actionner les actions dessus
		//Attention, le votable est le modele objet du coeur toute en haut de la page
		var votable = kidzouModule.getVotesModel().getVotableItem( current_page_id );

		//le contenu de la boite de notif dépend si c'est un vote ou non
		var is_vote = (m.id=='vote');
		var is_newsletter = (m.id=='newsletter');

		var href = (is_vote ? "" : 'href="' + m.target + '"');
		var classes = (is_vote ? "votable_notification" : "notification" );

		if (!is_vote && !is_newsletter) {
			boxcontent += '<h3>' + kidzou_notif.message_title + '</h3>';
		}

		if (!is_newsletter)
			boxcontent += '<i class="fa fa-close close"></i><a ' + href + '" class="'+ classes +'">' + m.icon + '<h4>' + m.title + '</h4><span>' + m.body + '</span></a>';
		else
			boxcontent += '<i class="fa fa-close close"></i>' + m.icon + '<h3>' + m.title + '</h3>' + m.body ;

		if (jQuery.fn.endpage_box) {
			jQuery("#endpage-box").endpage_box({
			    animation: "flyInDown",  // There are several animations available: fade, slide, flyInLeft, flyInRight, flyInUp, flyInDown, or false if you don't want it to animate. The default value is fade.
			    from: "5%",  // This option allows you to define where on the page will the box start to appear. You can either send in the percentage of the page, or the exact pixels (without the px). The default value is 50%.
			    to: "50%", // This option lets you define where on the page will the box start to disappear. You can either send in the percentage of the page, or the exact pixels (without the px). The default value is 110% (the extra 10% is to support the over scrolling effect you get from OSX's Chrome and Safari)
			    content: boxcontent  // The plugin will automatically create a container if it doesn't exist. This option will allow you to define the content of the container. This field also supports HTML.
			  });
		}

		//suivi des clicks sur les suivis de lien
		jQuery('.notification').click(function() {
			kidzouTracker.trackEvent("Notification", "Suivi de suggestion", m.target , 0);
		});

		//gestion de la fermeture de la boite de notif
		jQuery('.close').click(function() {
			jQuery("#endpage-box").css('display', 'none');
			jQuery(document).unbind('scroll');
		});

		//gestion du vote
		jQuery('.votable_notification').click(function() {
			
			kidzouTracker.trackEvent("Notification", "Vote", current_page_id , 0);

			//mettre en cohérence le coeur tout en haut et procéder au vote
			votable.doUpOrDown();

			//Remercier le user
			jQuery("#endpage-box").html('<i class="fa fa-close close"></i><i class="fa fa-heart fa-3x vote"></i><h4>C&apos;est bien not&eacute; !</h4>');

			//Attendre un peu avant de supprimer le message...g
			//histoire que le user voit les effets de son click
			setTimeout(function(){
				jQuery( "#endpage-box" ).fadeOut( "slow", function() { });
				jQuery(document).unbind('scroll');
			}, 700);
			

		});

		//gestion de la validation du form newsletter
		var form = document.querySelector('#notification_newsletter');
		form.addEventListener("submit", function(evt){
			evt.preventDefault();
			
			jQuery.ajax({

				type: "POST",
				url: kidzou_notif.api_newsletter_url,
				data:
				{
					nonce 		: kidzou_notif.api_newsletter_nonce,
					firstname 	: form.querySelector('[name="firstname"]').value,
					lastname 	: form.querySelector('[name="lastname"]').value,
					email 		: form.querySelector('[name="email"]').value,
					zipcode 	: form.querySelector('[name="zipcode"]').value,
					key 		: kidzou_notif.mailchimp_key,
					list_id 	: kidzou_notif.mailchimp_list
				},
				beforeSend : function() {

					//afficher un message de patience
					document.querySelector('#notification_newsletter button').disabled = true;
					document.querySelector('#notification_error_message').innerHTML = '';

					// var sp1 = document.createElement("span");
					// // sp1.classList.add('form_wait_message');
					// sp1.innerHTML = ;

					document.querySelector('#notification_error_message').innerHTML = kidzou_notif.form_wait_message;

				},
				success: function( data ){

					document.querySelector('#notification_error_message').innerHTML = '';
					document.querySelector('#notification_newsletter input').classList.remove('error');

					//pas d'erreur dans l'API
					if (data.status=='ok') {

						//erreur fonctionnelle de valdation
						if (data.result == 'error') {

							//re-afficher le bouton de soumission du formulaire
							document.querySelector('#notification_newsletter button').disabled = false;
							var fields = data.fields ;
							for (x in fields) {
								// console.debug(x);
								var field = fields[x];
							    document.querySelector('#notification_error_message').innerHTML += field.message;
							    document.querySelector('#notification_newsletter input[name="' + x + '"]').classList.toggle('error');
							}
						
						} else {

							document.querySelector('#notification_error_message').innerHTML = data.message;

							//Attendre un peu avant de supprimer le message...g
							//histoire que le user voit les effets de son click
							setTimeout(function(){
								jQuery( "#endpage-box" ).fadeOut( "slow", function() { });
								jQuery(document).unbind('scroll');
							}, 700);
						}
						
					//erreur technique dans l'API
					} else {
						document.querySelector('#notification_newsletter button').disabled = false;
						document.querySelector('#notification_error_message').innerHTML = kidzou_notif.form_error_message ;

					}

				}

			} );

			return false;
			
		});

		setMessageRead(m);

	}

	// jQuery(document).ready(function() {
	document.addEventListener('DOMContentLoaded', function() {

		// console.debug('kidzou_notif ' + kidzou_notif.activate);

		if (kidzou_notif.activate && kidzou_notif.messages.content.length) {

			jQuery(window).load( function() {

				kidzouModule.afterVoteUpdate(function(result) {

					var messages = getUnreadMessages(result, kidzouModule.getCurrentPageId() );
					var message = chooseMessage(messages) ;

					if (message !=null && (typeof message!='undefined') ) displayMessage(message);

				});
		
			});

		} 
	
		
	});
	

})();