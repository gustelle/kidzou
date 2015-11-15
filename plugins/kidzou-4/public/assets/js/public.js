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



var kidzouModule = (function() { //havre de paix

	var kidzou;
	var logger;

	// jQuery(document).ready(function() {
	document.addEventListener('DOMContentLoaded', function(event) {

		//assurer que les dépendances sont là...
		// if (window.jQuery && window.ko && window.storageSupport) {

			String.prototype.toBoolean = function()
			{switch(this.toLowerCase()){case "true": case "yes": case "1": return true;case "false": case "no": case "0": case null: return false;default: return Boolean(this);}};


			logger = function() {

				var logLevel 		= false;

				function setLogging(_bool) {var bool;if(typeof _bool=='undefined' || !_bool || _bool==''){bool="false";}else{bool=_bool; console.debug("Logging actif");}; logLevel = bool.toBoolean();}
				function debug(msg) {if (logLevel) console.debug(msg);}
				function info(msg) {if (logLevel) console.log(msg);}
				function warn(msg) {if (logLevel) console.warn(msg);}
				function error(msg) {if (logLevel) console.error(msg);}

				return {
					setLogging : setLogging,
					debug : debug,
					info : info,
					warn : warn,
					error : error
				};
			}();


			kidzou = function() {

				var votesModel 		= new VotesModel(); 

				//initialement (permettre le vote même si le user n'accepte pas la geoloc)
				feedViewModel();

				/**
				 * Les éléments "déjà votés" (issus du localstorage ou d'une requete ajax)
				 * vont être marqués voted(true), de sorte 
				 * - dans le UI on pourra indiquer par un marqueur que ce votable a déjà été voté
				 * - lorsqu'on cliquera sur l'élément de vote, on supprimera le vote (via la fonction doUpOrDown())
				 */
				function mapVotedToVotables(_voted) {
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
					// console.debug('refreshVotesCount', ko.toJSON(votesModel.votableIds));
					jQuery.getJSON(kidzou_commons_jsvars.api_get_votes_status, {
							posts_in: votesModel.votableIds
						},
						function(data) {
							setVotesCount(data.status);
				        }
				    );
				}

				/**
				 * Mise à jour du nombre de votes dans le modèle JS 
				 * ensuite on peut s'en servir pour l'afficher dans le UI
				 * 
				 * Uniquement appelé en cas de rafraichissement via refreshVotesCount() 
				 */
				function setVotesCount(votes) {

					ko.utils.arrayMap(votes, function(item) {
						var matchedItem = ko.utils.arrayFirst(votesModel.votableItems, function(i) {
				            if (i.id == item.id) return i;
				        });
				        if (matchedItem!==null) 
				        	matchedItem.votes( item.votes );
					});


					var els = document.querySelectorAll('.votable');
					[].forEach.call(els, function(el) {
					  fadeIn(el);
					});
					
				}

				/**
				* si le localStorage n'est pas supporté, les votes ne sont pas stockés en local
				* dans ce cas on rafraichit systématiquement les données en provenance du serveur
				*
				* Lié avec la fonction d'écriture des votes lorsque le user recommande/ne recommande pas un article
				* @see VotableItem.prototype
				*/
				function refreshUserVotes() {

					var votedPosts 	= storageSupport.getLocal("voted");
					var localVotes 	= {};
					var user_hash 	= getUserHash();

					if (votedPosts!=='') {
						localVotes 	= JSON.parse(storageSupport.getLocal("voted"));
					}

			        if (localVotes===null || localVotes.length===0) 
					{
						// logger.warn("Rafraichissement des votes, aucun vote local trouvé pour " + user_hash);

						//assurer de ne pas passer la valeur "null" dans la requete
						//renvoyer dans ce cas une chaine vide
						//cela peut arriver à cause du legacy ou lorsque le user est identifié
						if (user_hash===null || user_hash==="undefined" ) {
							user_hash="";
						}

						jQuery.getJSON(kidzou_commons_jsvars.api_get_votes_user, { user_hash: getUserHash() })
						.done(function(d) {
							
							// logger.debug("storeLocalVotes " + ko.toJSON(d));
							
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
						// logger.debug("setUserHash : " + hash);
						storageSupport.setLocal("user_hash", hash);
					}
				}

				/**
				* permet d'identifier un user anonyme
				* le hash est fourni par le serveur, voir hash_anonymous() dans kidzou_utils
				**/
				function getUserHash ( ) {

					if (storageSupport.getLocal("user_hash")==="undefined") { //pour le legacy
						// logger.debug("user_hash undefined" );
						storageSupport.removeLocal("user_hash");
					}

					return storageSupport.getLocal("user_hash");
				}

				/**
				 * quels sont les elements de la page qui peuvent etre votés
				 */
				function feedViewModel() {
					ko.utils.arrayMap(document.querySelectorAll('.votable'), function(item) {
						var id 		=  item.getAttribute('data-post');	
						var slug 	=  item.getAttribute('data-slug');		
						votesModel.votableIds.push( id );
					    votesModel.votableItems.push( new VotableItem ( id, 0, false, slug) );
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
						// console.debug("votableItems", self.votableItems);
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
					bindView 	: bindView,
					votable     : VotableItem ,
					votesModel : votesModel ,
					feedViewModel : feedViewModel
				};

			}();


			logger.setLogging(kidzou_commons_jsvars.cfg_debug_mode); 

			kidzou.bindView();
		// }

	}, false); // jQuery(document).ready(function() {


	function afterVoteUpdate(callback) {

		// var current_page_id = jQuery('.votable').first().data('post');
		var current_page_id = document.querySelector('.votable').getAttribute('data-post');

		jQuery.getJSON(kidzou_commons_jsvars.api_voted_by_user, {
				post_id: current_page_id
			},
			function(data) {
				var voted = data.voted;
				return callback(voted);
	        }
	    );

	}

	function getCurrentPageId( ) {
		// var current_page_id = jQuery('.votable').first().data('post');
		var current_page_id = document.querySelector('.votable').getAttribute('data-post');
		return current_page_id;

	}


	function getVotesModel() {
		return kidzou.votesModel;
	}

	//parfois du contenu est rechargé en ajax
	//il faut recharger les votes pour rafraichir les données sur les posts chargés en ajax
	function refresh() {
		kidzou.feedViewModel();
		kidzou.bindView();
	}


	return {
		afterVoteUpdate : afterVoteUpdate,
		getCurrentPageId : getCurrentPageId,
		getVotesModel : getVotesModel,
		refresh : refresh
	}

}());  //kidzouModule


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









