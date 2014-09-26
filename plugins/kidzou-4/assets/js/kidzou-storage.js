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