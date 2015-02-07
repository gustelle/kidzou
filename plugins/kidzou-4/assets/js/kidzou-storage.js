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
Storage.prototype.setCacheItem = function (key, value, exp) { //console.debug('setCacheItem ' + key );
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

var storageSupport = (function () {


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
			
			if (!supports_html5_storage()  )
				return null;

			var localData = localStorage.getCacheItem(key);

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
		function toLocalData (key, obj, expiration) {
			
			if (!supports_html5_storage() )
				return;

			if (obj===null || key===null || key==="")
				return;

			var exp = expiration || 30; //days

			localStorage.setCacheItem(key, 
							ko.toJSON(
								ko.mapping.toJS(obj)
							), 
							{ days: exp }
						);

		}

		function removeLocalData(key) {

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

			return false;
			
		}

		//Stockage local 
		//fallback en cookie si le stockage local n'est pas supporté
		//pour stocker un object utiliser toLocalData 
		function setLocal(key, value, expiration) {

			var exp = expiration || 30; //days

			if (supports_html5_storage() ) {
				console.debug('setLocal ' + key );
				localStorage.setCacheItem(
						key, 
						value, 
						{ days: exp });
			}
			else {
				console.debug('setLocal ' + key + ' / fallback vers les cookies');
				setCookie(key, value, exp);
			}
				
		}

		//Recup à partir du Stockage local 
		//fallback en cookie si le stockage local n'est pas supporté
		function getLocal(key) {
			if (supports_html5_storage() ) {
				return localStorage.getCacheItem(key);
			}
			else
				getCookie(key);
		}

		function setCookie(key, value, expiration) {

			var exp = expiration || 180; //days
			cookie(key , value, { path: '/', expires:exp } );
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
			// setLocalSupport : setLocalSupport,
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