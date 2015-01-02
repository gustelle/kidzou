

var kidzouModule = (function() { //havre de paix

	var kidzou;
	var logger;

	// jQuery(document).ready(function() {
	document.addEventListener('DOMContentLoaded', function() {

		//assurer que les dépendances sont là...
		if (window.jQuery && window.ko && window.storageSupport) {

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
		}

	}); // jQuery(document).ready(function() {


	function afterVoteUpdate(callback) {

		var current_page_id = jQuery('.votable').first().data('post');
		// console.debug('current_page_id ' + current_page_id);

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
		var current_page_id = jQuery('.votable').first().data('post');
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
			if (_do_track)
				ga('send', 'event', context, action, title, loadtime);
	        else
	        	console.debug("trackEvent(" + context + ", " + action + ", " + title + ", " + loadtime + ")");
	  	}

	  	return {
	  		trackEvent : trackEvent
	  	};

}());










