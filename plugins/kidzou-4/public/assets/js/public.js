

var kidzouModule = (function() { //havre de paix

	var kidzou;
	var logger;

	jQuery(document).ready(function() {

		String.prototype.toBoolean = function()
		{switch(this.toLowerCase()){case "true": case "yes": case "1": return true;case "false": case "no": case "0": case null: return false;default: return Boolean(this);}};


		logger = function() {

			var logLevel 		= false;

			function setLogging(_bool) {var bool;if(typeof _bool=='undefined' || !_bool || _bool==''){bool="false";}else{bool=_bool; console.debug("Logging actif");}; logLevel = bool.toBoolean();}
			function debug(msg) {if (logLevel) console.debug(msg);}
			function info(msg) {if (logLevel) console.log(msg);}

			return {
				setLogging : setLogging,
				debug : debug,
				info : info
			};
		}();


		kidzou = function() {

			// var message			= new kidzouMessage.message();
			var votesModel 		= new VotesModel(); 

			//initialement (permettre le vote même si le user n'accepte pas la geoloc)
			feedViewModel();

			function mapVotedToVotables(_voted) {
				// logger.debug("mapVotedToVotables " + ko.toJSON(_voted));
				// debug("votesModel.votableItems " + ko.toJSON(votesModel.votableItems));
				ko.utils.arrayForEach(_voted, function(item) {
					ko.utils.arrayFirst(votesModel.votableItems, function(i) {
			            if ( i.id == item.id) i.voted(true);    
			        });
				});
			}

			/**
			* rafraichissement du nombre votes pour les éléments votables 
			* 
			*/
			function refreshVotesCount() {
				jQuery.getJSON(kidzou_commons_jsvars.api_get_votes_status, {
						posts_in: ko.toJSON(votesModel.votableIds)
					},
					function(data) {
						setVotesCount(data.status);
			        }
			    );
			}

			function setVotesCount(votes) {
				ko.utils.arrayMap(votes, function(item) {
					var matchedItem = ko.utils.arrayFirst(votesModel.votableItems, function(i) {
			            if (i.id == item.id) return i;
			        });
			        if (matchedItem!==null)
			        	matchedItem.votes( item.votes );
				});
			}

			/**
			* si le localx n'est pas supporté, les votes ne sont pas stockés en local
			* dans ce cas on rafraichit systématiquement les données en provenance du serveur
			*
			* Lié avec la fonction d'écriture des votes lorsque le user recommande/ne recommande pas un article
			* @see VotableItem.prototype
			*/
			function refreshUserVotes() {

		        var localVotes 			 = JSON.parse(storageSupport.getLocal("voted"));
		        var user_hash 			 = getUserHash();

		        if (localVotes===null || localVotes.length===0) 
				{
					logger.debug("localVotes null pour user_hash " + user_hash);

					//assurer de ne pas passer la valeur "null" dans la requete
					//renvoyer dans ce cas une chaine vide
					//cela peut arriver à cause du legacy ou lorsque le user est identifié
					if (user_hash===null || user_hash==="undefined" ) {
						user_hash="";
					}

					jQuery.getJSON(kidzou_commons_jsvars.api_get_votes_user, { user_hash: getUserHash() })
					.done(function(d) {
						
						logger.debug("storeLocalVotes " + ko.toJSON(d));
						
						//cas des users loggués : le user_hash n'est pas renvoyé
						if (d!==null && d.user_hash!==null && d.user_hash!=="undefined")
							setUserHash(d.user_hash); //pour réutilisation ultérieure
						
						if (d!==null && d.voted!==null && d.voted.length > 0) { 
							// logger.debug('before local data storage ' );
							storageSupport.toLocalData("voted", d.voted);
							mapVotedToVotables(d.voted);
						}
			        });
				}
				else
					mapVotedToVotables(localVotes);
				
			} 

			/**
			* permet d'identifier un user anonyme
			* le hash est fourni par le serveur, voir hash_anonymous() dans kidzou_utils
			**/
			function setUserHash (hash) {

				if (hash===null || hash==="" || hash==="undefined") //prevention des cas ou le user est identifié : son user_hash est null
					return;

				if (getUserHash()===null || getUserHash()==="" || getUserHash()==="undefined") {
					logger.debug("setUserHash : " + hash);
					storageSupport.setLocal("user_hash", hash);
				}
			}

			/**
			* permet d'identifier un user anonyme
			* le hash est fourni par le serveur, voir hash_anonymous() dans kidzou_utils
			**/
			function getUserHash ( ) {

				if (storageSupport.getLocal("user_hash")==="undefined") { //pour le legacy
					logger.debug("user_hash undefined" );
					storageSupport.removeLocal("user_hash");
				}

				return storageSupport.getLocal("user_hash");
			}


			function feedViewModel() {
		
				ko.utils.arrayMap(jQuery('.votable'), function(item) {
					votesModel.votableIds.push( jQuery(item).data('post') );
				    votesModel.votableItems.push(new VotableItem ( jQuery(item).data('post'), 0, false, jQuery(item).data('slug')) );
				}); 

				refreshVotesCount();  //cached by server
				refreshUserVotes(); //cached with local storage if supported or cookie if not
			}

			///////////////////////////////////////////////////////////////////////////////////////////////////////////////
			////////////////////////// Modele objet exposé publiquement, utilisé par le UI ////////////////////////////////
			///////////////////////////////////////////////////////////////////////////////////////////////////////////////

			function VotesModel() {

				var self 			= this;
				self.votableIds 	= []; //only for JSON request
				self.votableItems 	= [];

				self.getVotableItem = function (_id) {
					return ko.utils.arrayFirst(self.votableItems, function(item) {
			  		    if (item.id == _id) return item;
			    	});
				};

			}

			function VotableItem ( _id, _votes, _voted, _slug) {

				var self 		= this;

				self.id 		= _id;
				self.slug 		= _slug;
				self.votes 		= ko.observable(_votes);
				self.voted 		= ko.observable(_voted);
				self.downActivated = ko.observable(false); 

				self.countText 	= ko.computed(function() {
					if (self.downActivated())
						return kidzou_commons_jsvars.votable_countText_down;
					else
						return kidzou_commons_jsvars.votable_countText; 
				});

				self.iconClass = ko.computed(function() {
					return self.voted() ? 'fa fa-heart' : 'fa fa-heart-o';
				});


				//si le user a deja voté et qu'il survole le lien de recommandation
				//on met à jour le lien pour qu'il devienne une action de retrait du vote
				//ce changement d'action est appelé par le texte 'stop' visible à coté du texte 'recommandation'
				//lorsque le user a voté
				self.activateDown = function() {
					if (self.voted()) {
						self.downActivated(true);
					}
				};

				self.doUpOrDown = function() {

					var upOrdown = '+1';

					if (self.voted())
						upOrdown = '-1';

					kidzouTracker.trackEvent("Recommandation", upOrdown, this.slug , kidzou_commons_jsvars.current_user_id);

					//console.dir(this);
					if (self.voted()) 
						self.doWithdraw();
					else
						self.doVote();
				};

				//quoiqu'il arrive, le countText est reinitialisé lorsque la souris est éloignée de l'item
				self.deactivateDown = function() {
					self.downActivated(false);
				};

				self.doVote = function() {

					// logger.debug("doVote");

					if (this.voted()) return;

					var _id = this.id;

					//update the UI immediatly and proceed to the vote in back-office
					var count = parseInt(this.votes())+1;
					this.voted(true);
					this.votes(count);

					// logger.info("doVote " + _id + "(+1)");

					//get nonce for voting and proceed to vote
					jQuery.getJSON(kidzou_commons_jsvars.api_get_nonce,{controller: 'vote',	method: 'up'})
						.done(function (data) {
							// logger.debug("doVote " + ko.toJSON(data));
							if (data!==null) {
					           var nonce =  data.nonce;
					           //vote with the nonce
					           jQuery.getJSON(kidzou_commons_jsvars.api_vote_up, {
										post_id: _id, 
										nonce: nonce,
										user_hash : getUserHash()
									}, function(data) {
										//cas des users loggués, le user_hash n'est aps renvoyé
										if (data.user_hash!==null && data.user_hash!=="undefined")
											setUserHash(data.user_hash); //pour reuntilisation ultérieure
										
										storageSupport.removeLocalData("voted"); //pour rafraichissement à la prochaine requete
										logger.debug("doVote executed");
									}
								); 
					        }
				        });
				};

				//retrait du vote ('Je ne recommande plus')
				self.doWithdraw = function() {

					if (!this.voted()) return;

					var _id = this.id;

					//update the UI immediatly and proceed to the withdraw in back-office
					var count = parseInt(this.votes())-1;
					this.voted(false);
					this.votes(count);

					// logger.info("doWithdraw " + _id + "(-1)");

					//get nonce for voting and proceed to vote
					jQuery.getJSON(kidzou_commons_jsvars.api_get_nonce,{controller: 'vote',	method: 'down'})
						.done(function (data) {
				           var nonce =  data.nonce;
				           //vote with the nonce
				           jQuery.getJSON(kidzou_commons_jsvars.api_vote_down, {
									post_id: _id, 
									nonce: nonce,
									user_hash : getUserHash()
								}, function(data) {
									//cas des users loggués, le user_hash n'est aps renvoyé
									if (data.user_hash!==null && data.user_hash!=="undefined")
										setUserHash(data.user_hash); //pour reuntilisation ultérieure
									
									storageSupport.removeLocalData("voted"); //pour rafraichissement à la prochaine requete
									logger.debug("doWithdraw executed");
								}
							); 
				        });
				};
			}

			

			function bindView() {
				ko.applyBindings( viewModel() ); 
			}

			function viewModel() {
				return {
					// message : message,
					votes 	: votesModel
				};
			}

			return { 
				bindView 		: bindView,
			};

		}();


		logger.setLogging(kidzou_commons_jsvars.cfg_debug_mode); 

		kidzou.bindView();

	}); // jQuery(document).ready(function() {


	function afterVoteUpdate(callback) {

		var current_page_id = jQuery('.votable').first().data('post');
		console.debug('current_page_id ' + current_page_id);

		jQuery.getJSON(kidzou_commons_jsvars.api_voted_by_user, {
				post_id: current_page_id
			},
			function(data) {
				var voted = data.voted;
				return callback(voted);
	        }
	    );

	}

	return {
		afterVoteUpdate : afterVoteUpdate
	}

}());  //kidzouModule


//ne pas tracker en dev et ne pas tracker les admins
var _do_track = !kidzou_commons_jsvars.is_admin && location.hostname==='www.kidzou.fr';

if (_do_track) {

	//google analytics
	(function (i,s,o,g,r,a,m) {i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	ga('create', 'UA-23017523-1', 'kidzou.fr');
	ga('send', 'pageview');
}

var kidzouTracker = (function() {

		function trackEvent(context, action, title, loadtime) {
			if (_do_track)
				ga('send', 'event', context, action, title, loadtime);
	        else
	        	console.debug("trackEvent(" + context + ", " + action + ", " + title + ", " + loadtime + ")");
	  	}

	  	return {
	  		trackEvent : trackEvent
	  	};

}());

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

	//les messages pour ce contexte 
	// var thisContextMessages =  null;
		

	//les messages qui font sens pour cette page
	//c'est à dire les messages qui n'ont pas encore été lus 
	function getUnreadMessages(_is_page_voted) {

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
			console.debug("_is_page_voted " + _is_page_voted);
			if (_is_page_voted && m.id=='vote') {
				console.debug("Message de vote écarté");
				amess.readMe();
			}
				
			
			ko.utils.arrayForEach(thisContextNotifications.messages, function(alreadyRead) {

			    if ( alreadyRead == m.id  ) { 
			    	amess.readMe();
			    }

			});

			if (!amess.isRead()) 
				messages.push(amess);
		});

		return messages;
	
	}
	

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

        // var nextMessage = unread[Math.floor(Math.random()*unread.length)];
      	// return nextMessage;

      	//ke premier de la liste
      	return unread[0];

	}

	function displayMessage(m) {

		var boxcontent = '';

		console.debug("displayMessage : " + m.id);

		if (m.id!='vote')
			boxcontent += '<h3>Nous vous recommandons : </h3>';

		boxcontent += '<i class="fa fa-close close"></i><a href="' + m.target + '" class="notification">' + m.icon + '<h4>' + m.title + '</h4><span>' + m.body + '</span></a>';
		
		jQuery("#endpage-box").endpage_box({
		    animation: "flyInDown",  // There are several animations available: fade, slide, flyInLeft, flyInRight, flyInUp, flyInDown, or false if you don't want it to animate. The default value is fade.
		    from: "1%",  // This option allows you to define where on the page will the box start to appear. You can either send in the percentage of the page, or the exact pixels (without the px). The default value is 50%.
		    to: "50%", // This option lets you define where on the page will the box start to disappear. You can either send in the percentage of the page, or the exact pixels (without the px). The default value is 110% (the extra 10% is to support the over scrolling effect you get from OSX's Chrome and Safari)
		    content: boxcontent  // The plugin will automatically create a container if it doesn't exist. This option will allow you to define the content of the container. This field also supports HTML.
		  });

		jQuery('.notification').click(function() {
			kidzouTracker.trackEvent("Notification", "Click", m.target , 0);
		});

		jQuery('.close').click(function() {
			jQuery("#endpage-box").css('display', 'none');
			jQuery(document).unbind('scroll');
		});

		setMessageRead(m);

	}

	// console.debug(kidzou_notif.messages.content.length);
	jQuery(document).ready(function() {

		if (kidzou_notif.activate && kidzou_notif.messages.content.length) {

			jQuery(window).load( function() {

				kidzouModule.afterVoteUpdate(function(result) {

					var messages = getUnreadMessages(result);
					var message = chooseMessage(messages) ;

					if (message !=null && (typeof message!='undefined') )
						displayMessage(message);
					else
						console.debug('aucun message à afficher');

				});
		
			});

		} 
	
		
	});
	

})();






