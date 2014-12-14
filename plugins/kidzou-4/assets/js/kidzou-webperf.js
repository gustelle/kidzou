


var asyncLoader = (function(){

	//merci
	//http://phpjs.org/functions/in_array/
	function in_array(needle, haystack, argStrict) {
	  //  discuss at: http://phpjs.org/functions/in_array/
	  // original by: Kevin van Zonneveld (http://kevin.vanzonneveld.net)
	  // improved by: vlado houba
	  // improved by: Jonas Sciangula Street (Joni2Back)
	  //    input by: Billy
	  // bugfixed by: Brett Zamir (http://brett-zamir.me)
	  //   example 1: in_array('van', ['Kevin', 'van', 'Zonneveld']);
	  //   returns 1: true
	  //   example 2: in_array('vlado', {0: 'Kevin', vlado: 'van', 1: 'Zonneveld'});
	  //   returns 2: false
	  //   example 3: in_array(1, ['1', '2', '3']);
	  //   example 3: in_array(1, ['1', '2', '3'], false);
	  //   returns 3: true
	  //   returns 3: true
	  //   example 4: in_array(1, ['1', '2', '3'], true);
	  //   returns 4: false

	  var key = '',
	    strict = !! argStrict;

	  //we prevent the double check (strict && arr[key] === ndl) || (!strict && arr[key] == ndl)
	  //in just one for, in order to improve the performance 
	  //deciding wich type of comparation will do before walk array
	  if (strict) {
	    for (key in haystack) {
	      if (haystack[key] === needle) {
	        return true;
	      }
	    }
	  } else {
	    for (key in haystack) {
	      if (haystack[key] == needle) {
	        return true;
	      }
	    }
	  }

	  return false;
	}
	

	///gerer les deps ici 

	if(window.jQuery && window.ko){

		var loaded = ['jquery-core', 'ko', 'jquery', 'kidzou-storage', 'kizou-plugin-script']
		, 	toBeLoaded = kidzou_webperf.js;


		function downloadJSAtOnload() {

			var i = toBeLoaded.length;
			var count = 1;

			// console.debug(  " toBeLoaded : " + i + " scripts");

			while (i--) {
				if (toBeLoaded[i])
				{	
					// console.debug('Loading ' + toBeLoaded[i].handle);
					var myDeps = toBeLoaded[i].deps;
					var j = myDeps.length;
					var doLoad = true;
					while(j--)
					{ 
						if (!in_array( myDeps[j], loaded )) {
							console.debug(' x Requires ' + myDeps[j] );
							doLoad = false;
							break;
						}
					}
					if (doLoad && toBeLoaded[i].src) {
						// console.debug( count + " ... " + toBeLoaded[i].handle );
						var element = document.createElement("script");
						element.src = toBeLoaded[i].src + '?ver=' + kidzou_webperf.version;
						document.body.appendChild(element);

						loaded.push(toBeLoaded[i].handle);

						//et mettre à jour le tableau des JS à chjarger
						toBeLoaded.splice(i, 1);
						i = toBeLoaded.length;
						count++;
					}	

				}

			}
		}
		if (window.addEventListener)
			window.addEventListener("load", downloadJSAtOnload, false);
		else if (window.attachEvent)
			window.attachEvent("onload", downloadJSAtOnload);
		else window.onload = downloadJSAtOnload;
	}


})();

