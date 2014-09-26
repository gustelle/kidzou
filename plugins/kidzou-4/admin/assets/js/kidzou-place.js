var kidzouPlaceModule = (function() { //havre de paix

	var windowURL = window.URL || window.webkitURL;
	
	//voir http://jsfiddle.net/3LT9d/
	ko.extenders.debug = function(target, option) {
	    target.subscribe(function(newValue) {
	       console.debug(option + ": " + newValue);
	    });
	    return target;
	};

	ko.extenders.updateVilleTaxonomy = function(target, option) {
	    target.subscribe(function(newValue) {

	       //la ville est stockée dans newValue
	       //Faire un MapQuest avec la ville et appeler 

	       //1er webservice pour recuperer lat et lng
	       kidzouGeoContent.getLatLng(newValue, function(pos) {

	       		//2eme webservice pour la metropole
				kidzouGeoContent.getMetropole(pos.lat,pos.lng, function(metropole) {
					updateVilleTaxonomy(null, metropole);
				});


	       });


	    });
	    return target;
	};


	ko.bindingHandlers.placecomplete = {
	    init: function(element, valueAccessor, allBindingsAccessor) {
	        var obj = valueAccessor(),
	            allBindings = allBindingsAccessor(),
	            lookupKey = allBindings.lookupKey;
	        jQuery(element).placecomplete(obj);
	        //console.log(obj);
	        if (lookupKey) {
	            var value = ko.utils.unwrapObservable(allBindings.value);
	            jQuery(element).placecomplete('data', ko.utils.arrayFirst(obj.data.results, function(item) {
	                return item[lookupKey] === value;
	            }));
	        }

	        ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
	            jQuery(element).placecomplete('destroy');
	        });
	    },
	    update: function(element) {
	        jQuery(element).trigger('change');
	    }
	};


	ko.validation.registerExtenders();
	ko.validation.init({ 
		insertMessages : true,
		decorateElement : true,
		errorsAsTitle : false,
		parseInputAttributes : true,
	    errorMessageClass : 'form_hint',
	    errorElementClass : 'input_hint',
	    messagesOnModified:true, //verifier tout le temps, meme au chargement 
	    grouping : { deep: true, observable: true },
	    decorateElementOnModified : false,
	});

	function updateVilleTaxonomy(post_id, value) {

		if(value!==null && "undefined"!==value && jQuery('#kz_event_metropole_' + value.toLowerCase() ).length) {
			jQuery('#kz_event_metropole_' + value.toLowerCase() ).attr('checked','checked');
		}
	}

	var kidzouPlaceEditor = function() { 

		var model = new PlaceEditorModel();

		function Place(_venue, _address, _website, _phone_number, _city, lat, lng, opening_hours) {
			this.venue 			= ko.observable(_venue).extend({ required: { message: 'Il faut nous indiquer où ca se passe !' }, notify: 'always' });
			this.address 		= ko.observable(_address).extend({ required: { message: 'Et ca se trouve ou ?' }, notify: 'always' });
			this.website 		= ko.observable(_website).extend({ notify: 'always', pattern: "(http|ftp|https)://[a-z0-9\-_]+(\.[a-z0-9\-_]+)+([a-z0-9\-\.,@\?^=%&;:/~\+#]*[a-z0-9\-@\?^=%&;/~\+#])?" }); //http://stackoverflow.com/questions/8188645/javascript-regex-to-match-a-url-in-a-field-of-text
			this.phone_number 	= ko.observable(_phone_number).extend({ notify: 'always', pattern: "^0[1-9]([-. ]?[0-9]{2}){4}$" }); //http://fr.openclassrooms.com/forum/sujet/mettre-une-regex-de-numero-de-telephone-en-javascript-93444
			this.city 			= ko.observable(_city).extend({ required: { message: 'Precisez le quartier ou la ville, cela facilitera la lecture rapide de cet evenement !' }, updateVilleTaxonomy: {}, notify: 'always' }); //quartier ou ville, rapidement identifiable par l'internaute
			this.lat 			= ko.observable(lat);
			this.lng 			= ko.observable(lng);
			this.opening_hours 	= ko.observableArray(opening_hours);
		}

		function PlaceModel() {

			var self = this;

			self.place 				= ko.observable(new Place());	
		    
		}


		function PlaceEditorModel() {


		    var self = this;

		    self.placeData 			= ko.observable(new PlaceModel());

		    //si l'utilisateur ne trouve pas son bonheur dans Google Places
		    //ce flag permet d'afficher le formulaire d'adresse custom
		    self.customPlace 		= ko.observable(false);

		    //https://developers.google.com/places/documentation/details
			self.completePlace = function(d,ev, result) {
				// console.log(result);
				//si non existant, le champ ville reprend le display_text 
				// var city = result.address_components[1] ? result.address_components[1].long_name : result.display_text;

				var city = result.display_text;

				//tentative de retrouver la ville de manière plus précise
				//voir https://developers.google.com/maps/documentation/geocoding/?hl=FR#Types
				result.address_components.forEach(function(entry) {
				    if (entry.types[0]=='locality') {
				    	city = entry.long_name;
				    	// console.log('found ' + city);
				    }
				});

				var opening_hours = result.opening_hours ? result.opening_hours.periods : [];

				self.placeData().place(new Place(
					result.name, 
					result.formatted_address, 
					result.website, 
					result.formatted_phone_number, 
					city, //city 
					result.geometry.location.lat(), //latitude
					result.geometry.location.lng(), //longitude
					opening_hours
					)
				); 

				kidzouGeoContent.getMetropole(
					result.geometry.location.lat(), 
					result.geometry.location.lng(), function(metropole) {
													updateVilleTaxonomy(jQuery("#post_ID").val(),metropole);
												}); 

				self.customPlace(true); //make custom place from google place
			};


			self.initPlace = function(name, address, website, phone_number, city, lat, lng, opening_hours) {
				self.placeData().place(new Place(name, address, website, phone_number, city, lat, lng, opening_hours));

				if (name!=='' || address!=='' || website!=='' || phone_number!=='' || city!=='' || lat!=='' || lng!=='')
					self.customPlace(true); //l'adresse a commencé à etre renseignée
			};

			

			self.displayCustomPlaceForm = function() {
				self.eventData().place(new Place()); //remise à zero si éventuellement existante
				self.customPlace(true);
			};

			self.displayGooglePlaceForm = function() {
				self.customPlace(false);
			};


			
		} //EventsEditorModel

		return { 
			model 		: model //EventsEditorModel
		};

	}();  //kidzouEventsEditor

	jQuery(document).ready(function() {
		ko.applyBindings( kidzouPlaceEditor.model, document.getElementById("place_form") ); //retourne un EventsEditorModel
	});

	return {
		model : kidzouPlaceEditor.model //necessaire de fournir un acces pour interaction avec Google Maps ??
	};

}());  //kidzouPlaceModule

