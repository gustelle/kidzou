

//bouton de partage facebook
// function fbs_click() {u=location.href;t=document.title;window.open('http://www.facebook.com/sharer/sharer.php?u='+encodeURIComponent(u)+'&t='+encodeURIComponent(t),'Partager Kidzou sur Facebook');return false;}


var kidzouModule = (function() { //havre de paix


	String.prototype.toBoolean = function()
	{switch(this.toLowerCase()){case "true": case "yes": case "1": return true;case "false": case "no": case "0": case null: return false;default: return Boolean(this);}};


	var logger = function() {

		var logLevel 		= false;

		function setLogging(bool) {logLevel = bool.toBoolean();}
		function debug(msg) {if (logLevel) console.debug(msg);}
		function info(msg) {if (logLevel) console.log(msg);}

		return {
			setLogging : setLogging,
			debug : debug,
			info : info
		};
	}();


	var kidzou = function() {

		var message			= new kidzouMessage.message();
		var votesModel 		= new VotesModel(); 

		//initialement (permettre le vote même si le user n'accepte pas la geoloc)
		feedViewModel();

		function mapVotedToVotables(_voted) {
			logger.debug("mapVotedToVotables " + ko.toJSON(_voted));
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
		* si le localStorage n'est pas supporté, les votes ne sont pas stockés en local
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
			    votesModel.votableItems.push(new VotableItem ( jQuery(item).data('post'), 0, false) );
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

		function VotableItem ( _id, _votes, _voted) {

			var self 		= this;

			self.id 		= _id;
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
				return self.voted() ? 'icon-alreadyvoted' : 'icon-heart';
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

				logger.debug("doVote");

				if (this.voted()) return;

				var _id = this.id;

				//update the UI immediatly and proceed to the vote in back-office
				var count = parseInt(this.votes())+1;
				this.voted(true);
				this.votes(count);

				logger.info("doVote " + _id + "(+1)");

				//get nonce for voting and proceed to vote
				jQuery.getJSON(kidzou_commons_jsvars.api_get_nonce,{controller: 'vote',	method: 'up'})
					.done(function (data) {
						logger.debug("doVote " + ko.toJSON(data));
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

				logger.info("doWithdraw " + _id + "(-1)");

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
				message : message,
				votes 	: votesModel
			};
		}

		return { 
			bindView 		: bindView
		};

	}();

	var dialogs = function() {

		function openShareDialog() 
		{
			if (jQuery("#mobile_nav").css("display")==="none") {
				//console.debug("openShareDialog : #mobile_nav non affiche" );
				vex.defaultOptions.contentClassName = '';
				vex.defaultOptions.className 			= 'vex-theme-top';
				vex.defaultOptions.overlayClosesOnClick = true;
				vex.dialog.buttons.YES.text 			= 'Fermer';

				vex.dialog.alert({
		            message: jQuery('.newspan').html(),
		            callback: function (data) {
		            	//console.debug("callback, set newsletter à 1" );
		            	storageSupport.setLocal('kz_newsletter' , '1');
		            }
		        });

		    //le device est trop petit pour afficher un paneau newsletter
		    //on n'emebte pas le user
			} else {
				//console.debug("Smartphone en vue : set newsletter à 1 auto" );
		        storageSupport.setLocal('kz_newsletter' , '1');
			}
			
		}

		return { 
			openShareDialog 		: openShareDialog
		};

	}();

	
	

	/////////////// SHARE PANEL ////////////////
	////////////////////////////////////////////

	jQuery(".share").click(function(){
		dialogs.openShareDialog();
	});


	jQuery(document).ready(function() {

		logger.setLogging(kidzou_commons_jsvars.cfg_debug_mode); 

		// kidzouGeoContent.getLocation(); //Refresh du contenu

		kidzou.bindView();

	});

	return {
		dialogs : dialogs //necessaire de fournir un acces au dialog pour interaction avec Google Maps
	};

}());  //kidzouModule

