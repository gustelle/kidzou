
var kidzouMessage = (function() {

	function MessageModel() {

		// logger.debug("MessageModel initialisé");
		var self = this;
		self.messageClass 		= ko.observable('');
		self.messageContent 	= ko.observable('');

		self.addMessage	= function(_cls, _msg) {

			// console.log('addMessage ' + _msg);
			self.messageClass(_cls);
			self.messageContent(_msg);

			//je ne parviens pas à utiliser proprement la propriété isVisible()
			//j'ai donc positionné un "display:none" en css et j'utilise jQuery en solution de secours
			jQuery("#messageBox").show();
		};

		self.removeMessage = function() {
			self.messageContent('');
			jQuery("#messageBox").hide();
		};
	}

	return {
		message : MessageModel
	};

}());

var storageSupport = (function () {

		var activateSync = false;

		function setLocalSupport(bool) {
			// logger.debug("setLocalSupport " + bool);
			activateSync = bool;
		}

		/**
		 * Gets or sets cookies
		 * @see http://css-tricks.com/snippets/javascript/cookie-gettersetter/
		 * @param name
		 * @param value (null to delete or undefined to get)
		 * @param options (domain, expire (in days))
		 * @return value or true
		 */
		var cookie = function(name, value, options)
		{
			//logger.debug("cookie[" + name + "] = " + value + " (" + ko.toJSON(options) + ") ");
		    if (typeof value === "undefined") {
		        var n, v,
		            cookies = document.cookie.split(";");
		        for (var i = 0; i < cookies.length; i++) {
		            n = jQuery.trim(cookies[i].substr(0,cookies[i].indexOf("=")));
		            v = cookies[i].substr(cookies[i].indexOf("=")+1);
		            if (n === name){
		                return unescape(v);
		            }
		        }
		    } else {
		        options = options || {};
		        if (!value) {
		            value = "";
		            options.expires = -365;
		        } else {
		            value = escape(value);
		        }
		        if (options.expires) {
		            var d = new Date();
		            d.setDate(d.getDate() + options.expires);
		            value += "; expires=" + d.toUTCString();
		        }
		        if (options.domain) {
		            value += "; domain=" + options.domain;
		        }
		        if (options.path) {
		            value += "; path=" + options.path;
		        }
		        document.cookie = name + "=" + value;
		    }
		};

		//utilisation du localStorage pour stocker/récupérer des données
		//et éviter ainsi des appels JSON distants
		//le cache expirera automatiquement à l'appel de getCacheItem() selon le timing défini
		//requiert local-cache.js
		//voir https://code.google.com/p/local-cache/

		function fromLocalData (key, model) {
			
			if (!supports_html5_storage() || !activateSync )
				return null;

			var localData = localStorage.getCacheItem(key);

			// logger.debug("fromLocalData " + localData);

			if (localData===null)
				return null;

			return JSON.parse(localData);
		}


		//utilisation du localStorage pour stocker/récupérer des données
		//et éviter ainsi des appels JSON distants
		//le cache expirera automatiquement à l'appel de getCacheItem() selon le timing défini
		//requiert local-cache.js
		//voir https://code.google.com/p/local-cache/
		//
		function toLocalData (key, obj) {
			
			if (!supports_html5_storage() || !activateSync )
				return;

			if (obj===null || key===null || key==="")
				return;

			// logger.debug("toLocalData " + key);

			localStorage.setCacheItem(key, 
							ko.toJSON(
								ko.mapping.toJS(obj)
							), 
							{ days: 30 }
						);

		}

		function removeLocalData(key) {

			// logger.debug("removeLocalData " + key);

			if ( supports_html5_storage()  ) {
				//pour IE8 qui considère supporter le localStorage 
				//mais ne comprend pas les commandes ci-dessous
				try {
					localStorage.setCacheItem(key,"", {days:0}); //ecraser la date d'expiration
		    		localStorage.getCacheItem(key); //ce touch va supprimer la clé (normalement ?!)
		   			localStorage.removeItem(key);  
				} catch (e) {
					// logger.debug("removeLocalData planté : " + e);
				}
				
			} else {
				removeLocal(key);
			}
		}

		function supports_html5_storage() {
			
			if(typeof(Storage)!=="undefined") 
				return true;

			// logger.debug("localStorage not supported " );
			return false;
			
		}

		function setLocal(key, value) {
			if (supports_html5_storage() )
				localStorage.setItem(key, value);
			else
				setCookie(key, value);
		}

		function getLocal(key) {
			if (supports_html5_storage() )
				return localStorage.getItem(key);
			else
				getCookie(key);
		}

		function setCookie(key, value) {
			cookie(key , value, { path: '/', expires:180});
		}

		function getCookie(key) {
			return cookie(key);
		}


		function removeLocal(key) {
			// logger.debug("removeLocal " + key);
			if (supports_html5_storage() )
				localStorage.removeItem(key);
			else
				cookie(key, null, { path: '/', expires:-1});
		}

		return {
			setLocalSupport : setLocalSupport,
			setLocal : setLocal,
			getLocal : getLocal,
			removeLocal 	: removeLocal,
			toLocalData 	: toLocalData,
			fromLocalData 	: fromLocalData,
			removeLocalData : removeLocalData,
			setCookie 		: setCookie,
			getCookie 		: getCookie
		};
}());

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


	jQuery(document).ready(function() {

		logger.setLogging(kidzou_commons_jsvars.cfg_debug_mode); 

		kidzou.bindView();

	});


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

// local-cache, localStorage with expirations
// by Ian Davis, http://www.linkedin.com/in/ianhd and http://urlme.cc
// 
// Version 1.0
//
// Feedback?  Please submit here: http://code.google.com/p/local-cache/issues/list

function setExpiration(key, expireDate) {
    var expirations = localStorage.getItem("localStorageExpirations"); // "key1^11/18/2011 5pm|key2^3/10/2012 3pm"
    if (expirations) {
        var arr = expirations.split("|"); // ["key1^11/18/2011 5pm","key2^3/10/2012 3pm"]
        for (var i = 0; i < arr.length; i++) {
            var expiration = arr[i]; // "key1^11/18/2011 5pm"
            if (expiration.split('^')[0] == key) { // found match; update expiration
                arr.splice(i, 1); // remove, we'll add it w/ updated expiration later
                break;
            }
        } // next: key^exp pair
        arr.push(key + "^" + expireDate.toString());
        localStorage.setItem("localStorageExpirations", arr.join("|"));
    } else {
        localStorage.setItem("localStorageExpirations", key + "^" + expireDate.toString()); // "favColor^11/18/2011 5pm etc etc"
    }
}

function getExpiration(key) {
    var expirations = localStorage.getItem("localStorageExpirations"); // "key1^11/18/2011 5pm|key2^3/10/2012 3pm"
    if (expirations) {
        var arr = expirations.split("|"); // ["key1^11/18/2011 5pm","key2^3/10/2012 3pm"]
        for (var i = 0; i < arr.length; i++) {
            var expiration = arr[i]; // "key1^11/18/2011 5pm"
            var k = expiration.split('^')[0]; // key1
            var e = expiration.split('^')[1]; // 11/18/2011 5pm
            if (k == key) { // found match; return expiration and remove expiration if it's expired
                var now = new Date();
                var eDate = new Date(e);
                if (now > eDate) {
                    // remove expiration
                    arr.splice(i, 1);
                    if (arr.length > 0) {
                        localStorage.setItem("localStorageExpirations", arr.join("|"));
                    } else {
                        localStorage.removeItem("localStorageExpirations");
                    }
                }
                return new Date(e);
            }
        } // next: key^exp pair
    }
    return null;
}

// ex: localStorage.setCacheItem("favColor", "blue", { days: 1 })
Storage.prototype.setCacheItem = function (key, value, exp) {
    var val = null;
    if (typeof value == 'object') {
        // assume json
        value.isJson = true; // add this flag, so we can check it on retrieval
        val = JSON.stringify(value);
    } else {
        val = value;
    }
    localStorage.setItem(key, val);

    var now = new Date();
    var expireDate = new Date();

    if (typeof expireDate == 'undefined') {
        expireDate.setDate(now.getDate() + 1); // default to one day from now    
    } else {
        if (exp.minutes) {
            expireDate.setMinutes(now.getMinutes() + exp.minutes);
        }
        if (exp.hours) {
            expireDate.setHours(now.getHours() + exp.hours);
        }
        if (exp.days) {
            expireDate.setDate(now.getDate() + exp.days);
        }
        if (exp.months) {
            expireDate.setMonth(now.getMonth() + exp.months);
        }
        if (exp.years) {
            expireDate.setYear(now.getYear() + exp.years);
        }
    }

    setExpiration(key, expireDate);
};

Storage.prototype.getCacheItem = function (key) {
    // TODO: return JSON.parse if value is stringify'd json obj

    // first, check to see if this key is in localstorage
    if (!localStorage.getItem(key)) {
        return null;
    }

    // ex: key = "favColor"
    var now = new Date();
    var expireDate = getExpiration(key);
    if (expireDate && now <= expireDate) {
        // hasn't expired yet, so simply return
        var value = localStorage.getItem(key);
        try {
            var parsed = JSON.parse(value);
            if (parsed.isJson) {
                delete parsed.isJson; // remove the extra flag we added in setCacheItem method; clean it up
                return parsed;
            } else {
                return value; // return the string, since it could be trying to do JSON.parse("3") which will succeed and not throw an error, but "3" isn't a json obj
            }
        } catch (e) {
            // string was not json-parsable, so simply return it as-is
            return value;
        }
    }

    // made it to here? remove item
    localStorage.removeItem(key);
    return null;
};

var kidzouActions = (function() {

		//binding des elements initialement présents dans le HTML
		/////////////// SEARCH ////////////////
		///////////////////////////////////////

		jQuery("#searchform").submit(function(){
			// kidzouMessage.addMessage('info', kidzou_commons_jsvars.msg_wait);
			kidzouTracker.trackEvent("Recherche", "Submit", jQuery("#searchinput").val(), 0);
		});

		/////////////// Tracking du comportement ////////////////
		//////////////////////////////////////////////////////

		jQuery(".slide_wrap a").click(function(){
			kidzouTracker.trackEvent("Featured Slider", "Click", jQuery(this).attr("href"), 0);
		});

		jQuery("#menu-menu-principal li a").click(function(){
			kidzouTracker.trackEvent("Navigation", "Menu Desktop", jQuery(this).find(".main_text").text(), 0);
		});

		jQuery("#mobile_menu li a").click(function(){
			kidzouTracker.trackEvent("Navigation", "Menu Mobile", jQuery(this).find("span").text(), 0);
		});

		jQuery("#menu-menu-principal li .dropdown_5columns .col_5 article").click(function(){
			kidzouTracker.trackEvent("Navigation", "MegaDropDown Article", jQuery(this).find(".entry-title a").text(), 0);
		});

		jQuery("#menu-menu-principal li .dropdown_5columns .col_3 li a").click(function(){
			kidzouTracker.trackEvent("Navigation", "MegaDropDown Categorie", jQuery(this).text(), 0);
		});

		jQuery(".meta a").click(function(){
			kidzouTracker.trackEvent("Navigation", "Meta", jQuery(this).text(), 0);
		});

		jQuery(".social.google").click(function(){
			kidzouTracker.trackEvent("Connexion", "Google", 'LoginDialog', 0);
		});

		jQuery(".social.facebook").click(function(){
			kidzouTracker.trackEvent("Connexion", "Facebook", 'LoginDialog', 0);
		});

		jQuery(".catad").click(function(){
			kidzouTracker.trackEvent("Publicite", "Categorie", jQuery(this).attr('src'), 0);
		});

		//top panel
		jQuery("#mc-embedded-subscribe-form").submit(function() {
			kidzouTracker.trackEvent("Newsletter", "Inscription", '', 0);
		});

		/////////////// MEGADROPDOWN ////////////////
		///////////////////////////////////////
		jQuery(".rubriques > ul.nav > li").hover(
			function() {
				jQuery(this).children().show();
			}, function() {
				jQuery(this).children(".dropdown_5columns").hide(); //pas le <a> qui contient l'element de nav principal
			}	
		);

	}());
