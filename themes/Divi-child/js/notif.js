'use strict';

//merci http://www.abeautifulsite.net/detecting-mobile-devices-with-javascript/
var isMobile = {
	Android: function Android() {
		return navigator.userAgent.match(/Android/i);
	},
	BlackBerry: function BlackBerry() {
		return navigator.userAgent.match(/BlackBerry/i);
	},
	iOS: function iOS() {
		return navigator.userAgent.match(/iPhone|iPad|iPod/i);
	},
	Opera: function Opera() {
		return navigator.userAgent.match(/Opera Mini/i);
	},
	Windows: function Windows() {
		return navigator.userAgent.match(/IEMobile/i);
	},
	any: function any() {
		return isMobile.Android() || isMobile.BlackBerry() || isMobile.iOS() || isMobile.Opera() || isMobile.Windows();
	}
};

/**
 * Composant de Simplifié de Vote
 *
 */
var VoteNotification = React.createClass({
	displayName: 'VoteNotification',

	getInitialState: function getInitialState() {
		return {
			voted: false };
	},

	//le user a t il voté ce post ?
	handleVoteAction: function handleVoteAction(e, x) {

		e.preventDefault(); //stopper le click

		var self = this;
		self.setState({
			voted: true
		}, function () {

			//deleguer le vote au composant principal sur la page
			var pageVoter = kidzouVoteModule.getComponents()[0];
			pageVoter.voteUpOrDown('Notification');

			setTimeout(function () {
				kidzouNotifier.close();
			}, 200);
		});
	},

	render: function render() {

		var self = this;

		var votedClass = classNames('popMe', {
			'fa fa-heart': self.state.voted,
			'fa fa-heart-o': !self.state.voted
		});

		var spanClass = classNames('voteBlock fa-3x', {});

		return React.createElement(
			'span',
			{ style: { display: 'inline' }, className: spanClass, onClick: self.handleVoteAction },
			React.createElement(
				'span',
				{ className: 'vote' },
				React.createElement('i', { className: votedClass })
			)
		);
	}

});

var kidzouNotifier = function () {

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
		notificationsRead = storageSupport.fromLocalData('messages') || [];
		[].forEach.call(notificationsRead, function (n) {
			if (n.context == pageId) {
				thisContextNotifications = n;
			}
		});

		if (thisContextNotifications == null) {
			thisContextNotifications = { context: pageId, messages: [] };
		}

		// ko.utils.arrayForEach(kidzou_notif.messages.content, function(m) {
		[].forEach.call(kidzou_notif.messages.content, function (m) {

			var amess = new Message(m.id, m.title, m.body, m.target, m.icon);

			//si le post est déjà voté, on écarte le message d'incitation au vote
			//de même si le post à recommander est déjà le post sur lequel on se trouve
			if (_is_page_voted && m.id == 'vote' || _current_page_id == m.id) amess.readMe();

			// ko.utils.arrayForEach(thisContextNotifications.messages, function(alreadyRead) {
			[].forEach.call(thisContextNotifications.messages, function (alreadyRead) {

				//gestion du legacy
				//les messages newsletter n'entrent pas dans la logique "lu/pas lu"
				//leur fréquence d'affichage est gérée séparément, cependant auparavant ce n'était pas le cas
				if (m.id != 'newsletter' && alreadyRead == m.id) amess.readMe();
			});

			if (!amess.isRead()) messages.push(amess);
		});

		return messages;
	}

	/**
  * Modele Objet d'un message de notification
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

		self.readMe = function () {
			self.readFlag = true;
		};

		self.isRead = function () {
			return self.readFlag;
		};
	}

	/**
  *
  * lorsqu'un message est lu, il est flaggué pour ne plus le représenter au user
  **/
	function setMessageRead(m) {

		//les newsletter n'entrent pas dans cette logique
		//leur fréquence d'affichage est gérée séparémment
		if (m.id != 'newsletter') {
			m.readMe();

			// thisContextMessages.;
			thisContextNotifications.messages.push(m.id);

			var exist = false;

			// ko.utils.arrayForEach(notificationsRead, function(n) {
			[].forEach.call(notificationsRead, function (n) {

				if (n.context == pageId) {
					//remplacer l'existant
					n = thisContextNotifications;
					exist = true;
				}
			});

			if (!exist) notificationsRead.push(thisContextNotifications);

			//sur chaque page ou tous les mois
			var expiration = 30;

			if (pageId == 'daily') expiration = 1;else if (pageId == 'weekly') expiration = 7;

			storageSupport.toLocalData('messages', notificationsRead, expiration);
		}
	}

	/**
  * choix du message a afficher
  */
	function chooseMessage(messages) {

		var unread = messages.filter(function (m) {
			return !m.readFlag;
		});

		// exclusion du form newsletter si l'option'newsletter_once' est passée et que le user a déjà vu le formulaure
		//
		// var newsletter_already_seen = storageSupport.getLocal('newsletter_form');
		// var newsletter_once = (kidzou_notif.newsletter_once && newsletter_already_seen);
		var newsletter_context = parseInt(kidzou_notif.newsletter_context) + 1; //les pages viewed commencent à 1
		var pages_viewed = parseInt(storageSupport.getLocal('pages_viewed')) || 1;

		//le formulaire newsletter est-il potentiellement affichable ?
		//si le cookie n'existe pas encore ou positionné à 0 ou égal a la fréquence spécifiée dnas les reglages
		var newsletter_candidate = pages_viewed == 1 || pages_viewed >= newsletter_context;

		// si le user a souscri a la newsletter, un cookie a été positionné
		// cela éviter de resolliciter le user si l'option 'newsletter_once' n'a pas été selectionnée
		// ou si le cookie 'newsletter_form' a expiré
		var newsletter_subscribe = storageSupport.getLocal('newsletter_subscribe');

		// si le user a déjà vu le formulaire et a demandé à ne plus le voir
		var newsletter_refuse = storageSupport.getLocal('newsletter_refuse');

		//ce n'est pas un bug, mais pour être clean, on supprime le compteur de pages vues si le user refuse les newsletter
		if (newsletter_refuse) {
			storageSupport.removeLocal('pages_viewed');
		}

		//pas d'affiche du formulaire newsletter sur mobile
		//la UX n'est pas bonne sur ces terminaux
		//	1- quand on se met dans un champ input, on se retrouve tt en bas de la page, hors du form !!
		//	2- dans un mode paysage, le formualire est tronqué sans possibilité de fermer la popup
		var exclude_mobile = kidzou_notif.newsletter_nomobile && isMobile.any();

		//on affiche le formulaire newsletter si ce n'est pas un mobile, si le form newsletter est candidate en termes de fréquence d'affichage, et si le user n'a pas souscit a la newsletter
		if (!exclude_mobile && newsletter_candidate && !newsletter_subscribe && !newsletter_refuse) {

			var chosen = unread[0];

			//si le formulaire newsletter est choisi, remettre à 0 les pages viewed
			// console.info(chosen);
			if (typeof chosen !== 'undefined' && chosen.id == 'newsletter' && pages_viewed == newsletter_context) {
				storageSupport.setLocal('pages_viewed', 1);
			}

			return chosen;
		} else {
			// console.info('exclusion du formulaire newsletter');
			// return ko.utils.arrayFirst(unread, function(item) {
			var found = unread.filter(function (item) {
				return item.id != 'newsletter';
			}).shift();
			return found;
		}
	}

	function displayMessage(m) {

		var boxcontent = '';

		//l'id du post wordpress pris dans <article id="post-xxx">
		var current_page_id = document.querySelector('article').getAttribute('id').split('-')[1]; //kidzouModule.getCurrentPageId();

		//le votable est récupéré du model, on peut donc actionner les actions dessus
		//Attention, le votable est le modele objet du coeur toute en haut de la page
		// var votable = kidzouModule.getVotesModel().getVotableItem( current_page_id );
		//le contenu de la boite de notif dépend si c'est un vote ou non
		var is_vote = m.id == 'vote';
		var is_newsletter = m.id == 'newsletter';

		var href = is_vote ? "" : 'href="' + m.target + '"';
		var classes = is_vote ? "votable_notification" : "notification";

		if (!is_vote && !is_newsletter) {
			var excerpt = m.body.length > 200 ? m.body.substring(0, 200) + '...' : m.body;
			boxcontent += '<h3>' + kidzou_notif.message_title + '</h3>';
			boxcontent += '<i class="fa fa-close close"></i><a ' + href + '" class="' + classes + '">' + m.icon + '<h4>' + m.title + '</h4><span>' + excerpt + '</span></a>';
		} else if (is_vote) {
			boxcontent += '<i class="fa fa-close close"></i><span class="vote_container"></span><h4>' + m.title + '</h4><span>' + m.body + '</span>';
		} else {
			boxcontent += '<i class="fa fa-close close"></i>' + m.icon + '<h3>' + m.title + '</h3>' + m.body;
		}

		if (jQuery.fn.endpage_box) {

			jQuery("#endpage-box").endpage_box({
				animation: "flyInDown", // There are several animations available: fade, slide, flyInLeft, flyInRight, flyInUp, flyInDown, or false if you don't want it to animate. The default value is fade.
				from: "2%", // This option allows you to define where on the page will the box start to appear. You can either send in the percentage of the page, or the exact pixels (without the px). The default value is 50%.
				to: "80%", // This option lets you define where on the page will the box start to disappear. You can either send in the percentage of the page, or the exact pixels (without the px). The default value is 110% (the extra 10% is to support the over scrolling effect you get from OSX's Chrome and Safari)
				content: boxcontent // The plugin will automatically create a container if it doesn't exist. This option will allow you to define the content of the container. This field also supports HTML.
			});

			if (is_vote) {
				ReactDOM.render(React.createElement(VoteNotification, {
					ID: current_page_id,
					apis: kidzou_notif.vote_apis,
					currentUserId: kidzou_notif.current_user_id,
					slug: kidzou_notif.slug }), document.querySelector('.vote_container'));
			} else if (is_newsletter) {

				var link = document.querySelector('#newsletter_refuse');
				if (link != null) {
					link.addEventListener("click", function (e) {

						kidzouTracker.trackEvent("Notification", "Newsletter", 'Refus', 0);

						storageSupport.setLocal("newsletter_refuse", true);

						setTimeout(function () {
							closeFlyIn();
						}, 700);
					}, false);
				}

				document.addEventListener("newsletter_subscribed", function (e) {

					if (e.detail.status == 'ok' && e.detail.result != 'error') {

						//positionner un cookie pour ne pas re-solliciter le user sur une inscription newsletter alors qu'il s'est déjà inscrit
						storageSupport.setLocal("newsletter_subscribe", true);

						//Attendre un peu avant de supprimer le message...
						//histoire que le user ait le temps de lire le messaye de confirmation de souscription
						setTimeout(function () {
							closeFlyIn();
						}, 1500);
					}
				}, false);
			}
		}

		setMessageRead(m);

		//suivi des clicks sur les suivis de lien
		if (document.querySelector(".notification") !== null) {
			document.querySelector(".notification").addEventListener("click", function () {
				kidzouTracker.trackEvent("Notification", "Suivi de suggestion", m.target, 0);
			});
		}

		//gestion de la fermeture de la boite de notif
		if (document.querySelector(".close") !== null) {
			document.querySelector(".close").addEventListener("click", function () {
				closeFlyIn();
			});
		}
	}

	function closeFlyIn() {
		jQuery("#endpage-box").fadeOut("slow", function () {});
		jQuery(document).unbind('scroll');
	}

	document.addEventListener('DOMContentLoaded', function () {
		if (kidzou_notif.activate && kidzou_notif.messages.content.length) {

			//mettre a jour le cookie  qui trace le nombre de pages vues,
			//utilisé dans les regles d'affichage du formulaire newsletter
			var pages_viewed = parseInt(storageSupport.getLocal('pages_viewed')) || 0;
			pages_viewed += 1;
			storageSupport.setLocal('pages_viewed', pages_viewed);

			//besoin de faire un update des votes
			//pour savoir si le user a déjà recommandé la page ou non
			//s'il a déjà recommandé la sortie, on ne lui affiche pas la notif de vote
			jQuery(window).load(function () {

				var current_page_id = document.querySelector('article').getAttribute('id').split('-')[1]; //kidzouModule.getCurrentPageId();

				// kidzouModule.afterVoteUpdate(function(result) {
				jQuery.get(kidzou_notif.api_voted_by_user, {
					post_id: current_page_id
				}, function (data) {
					var voted = data.voted;
					var messages = getUnreadMessages(voted, current_page_id);
					var message = chooseMessage(messages);
					if (message != null && typeof message != 'undefined') displayMessage(message);
				});
			});
		}
	});

	return {
		close: closeFlyIn
	};
}();
