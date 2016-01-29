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
	       kidzouAdminGeo.getLatLng(newValue, function(pos) {

	       		//2eme webservice pour la metropole
				kidzouAdminGeo.getMetropole(pos.lat,pos.lng, function(metropole) {
					updateVilleTaxonomy(null, metropole);
				});


	       });


	    });
	    return target;
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

			this.isEmpty = function() {
				// console.debug('isEmpty', (typeof this.venue()), this.venue()=='undefined');
				var empty = (	(this.venue()=='' || this.venue()=='undefined' || typeof this.venue()=='undefined')  && 
								(this.address() == ''|| this.address()=='undefined' || typeof this.address()=='undefined' ) &&
								(this.website() == ''|| this.website()=='undefined' || typeof this.website()=='undefined') &&
								(this.phone_number() == '' || this.phone_number()=='undefined' || typeof this.phone_number()=='undefined') &&
								(this.city() == '' || this.city()=='undefined' || typeof this.city()=='undefined') &&
								(this.lat() == '' || this.lat()=='undefined' || typeof this.lat()=='undefined') &&
								(this.lng() == '' || this.lng()=='undefined' || typeof this.lng()=='undefined')
							);
				return empty;
			};

			//fonction de comparaison de places
			this.equals = function(_venue, _address, _website, _phone_number, _city, _lat, _lng, _opening_hours) {

				//redressement du telephone
				var phoneEquals = (_phone_number==this.phone_number());
				if (!phoneEquals) {
					if (typeof _phone_number!=='undefined') {
						if (typeof this.phone_number()!=='undefined')
							phoneEquals = ( this.phone_number().replace(/\s/gi, "") == _phone_number.replace(/\s/gi, "") );
					}
				}

				var eq = (	this.venue() == _venue && 
							this.address() == _address &&
							this.website() == _website &&
							phoneEquals &&
							this.city() == _city &&
							this.lat() == _lat &&
							this.lng() == _lng //&&
						);
				return eq;
			};
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

		    //les propositions faites au user, en provenance de l'adresse client, de l'import facebook
		    self.placeProposals 	= ko.observableArray([]);
		    //une proposition a-t-elle été faite au user ?
		    self.isProposal 		= ko.observable(false);

		    //marker : les champs sont ils remplis ?
		    //si oui : on propose des places; on ne remplit plus les champs si une nouvelle adresse arrive
		    //si non : on peut remplir les champs
		    self.isPlaceComplete 	= ko.observable(false);

		    //Resultats en provenance de Google PlaceComplete
		    //https://developers.google.com/places/documentation/details
			self.completePlace = function(result) {

				self.placeData().place(new Place(
						result.name, 
						result.address, 
						result.website, 
						result.phone_number, 
						result.city, //city 
						result.latitude, //latitude
						result.longitude, //longitude
						result.opening_hours
					)
				); 

				kidzouAdminGeo.getMetropole(
					result.latitude, 
					result.longitude, function(metropole) {
												updateVilleTaxonomy(jQuery("#post_ID").val(),metropole);
											}); 

				self.customPlace(true); //make custom place from google place
				self.isPlaceComplete(true);
			};

			//reprise d'une adresse qui a commencé a étre renseignée
			//ou d'une adresse précédemment complètement renseignée et reprise au chargement de l'écran
			self.initPlace = function(name, address, website, phone_number, city, lat, lng, opening_hours) {

				self.placeData().place(new Place(name, address, website, phone_number, city, lat, lng, opening_hours));
				
				//ici on bloque la surcharge d'adresse dans proposePlace() si on reprend une adresse et qu'on en propose une autre
				//Ex : 	je selectionne une place dans l'édition d'un post, je ne sélectionne pas de client
				//		je reprend plus tard cette adresse, je sélectionne le client mais souhaite garder l'ancienne adresse..
				//		cas par exemple des evenements qui ne se déroulent pas à l'adresse du client

				self.isPlaceComplete(true);
				
				if (name!=='' || address!=='' || website!=='' || phone_number!=='' || city!=='' || lat!=='' || lng!=='')
					self.customPlace(true); //l'adresse a commencé à etre renseignée

			};

			//Ajouter une adresse à la liste des propositions
			self.proposePlace = function(type, place) {

				console.debug('proposePlace', type);

				//dans le case ou il n'y avait aucune donnée renseignée, on l'impose
				var wasEmpty 	= self.placeData().place().isEmpty();
	
				//sinon on fait un diff avant d'imposer
				var wasEqual	= self.placeData().place().equals(place.name,place.address,place.website, place.phone_number, place.city, place.latitude, place.longitude, place.opening_hours);
			
				//si aucune place n'a été renseignée on peut directement mapper les champs
				if (!self.isPlaceComplete() || wasEmpty || wasEqual) {
					self.completePlace(place);
				} else {
					self.isProposal(true);
					self.placeProposals.push({type : type, place : place});
				}
			};

			//utiliser une proposition d'adresse selectionnée par le user
			self.useAddress = function() {
				self.completePlace(this.place);
				//plus besoin de cette adresse dans la liste des propositions
				self.placeProposals.remove(this);
			};

			self.displayCustomPlaceForm = function() {
				self.placeData().place(new Place()); //remise à zero si éventuellement existante
				self.customPlace(true);
				self.isPlaceComplete(false);
			};

			self.displayGooglePlaceForm = function() {
				self.customPlace(false);
				self.isPlaceComplete(false);
			};
			
		} //EventsEditorModel

		return { 
			model 		: model, //EventsEditorModel
		};

	}();  //kidzouEventsEditor

	return {
		model : kidzouPlaceEditor.model, //necessaire de fournir un acces pour interaction avec Google Maps ??
	};
}());  //kidzouPlaceModule

(function($){
	
	$(document).ready(function() {

		/**
		 * Amorcage du formulaire de place
		 *
		 */
		if (document.querySelector("#place_form")!==null) {

			ko.applyBindings( kidzouPlaceModule.model, document.querySelector("#place_form") ); //retourne un EventsEditorModel
		
			//maintenant que le binding est fait, faire apparaitre le form
			setTimeout(function(){
				document.querySelector("#place_form").classList.remove('hide');
				document.querySelector("#place_form").classList.add('pop-in');
			}, 300);

			// console.debug(place_jsvars.customer_location_name)

			if (place_jsvars.location_name!='' && typeof place_jsvars.location_name!='undefined') {
				kidzouPlaceModule.model.proposePlace('Adresse par defaut',{
					name 	: place_jsvars.location_name ,
					address : place_jsvars.location_address,
					website : place_jsvars.location_website,
					phone_number : place_jsvars.location_phone_number,
					city 		: place_jsvars.location_city,
					latitude 	: place_jsvars.location_latitude,
					longitude 	: place_jsvars.location_longitude,
					opening_hours : []
				});
			}

			if (place_jsvars.customer_location_name!='' && typeof place_jsvars.customer_location_name!='undefined') {
				// console.debug('quoi ?')
				kidzouPlaceModule.model.proposePlace('Adresse client',{
					name 	: place_jsvars.customer_location_name ,
					address : place_jsvars.customer_location_address,
					website : place_jsvars.customer_location_website,
					phone_number : place_jsvars.customer_location_phone_number,
					city 		: place_jsvars.customer_location_city,
					latitude 	: place_jsvars.customer_location_latitude,
					longitude 	: place_jsvars.customer_location_longitude,
					opening_hours : []
				});
			}
		}

		/**
		 * Boite de selection de la place, utilisation de Selectize PlaceComplete
		 * Tous le monde ne voit pas forcément cette boite, d'ou l'interet de verifier le selecteur
		 *
		 */
		if (document.querySelector("select[name='place']")!==null) {

			//selection GooglePlace/PlaceComplete depuis selectize
			$("select[name='place']").selectize({
			  mode: "single",
			  openOnFocus: false,
			  delimiter: null,
			  plugins: {
			    'placecomplete': {
			      selectDetails: function(placeResult) { 
		
			      	var city = placeResult.display_text;
					//tentative de retrouver la ville de manière plus précise
					//voir https://developers.google.com/maps/documentation/geocoding/?hl=FR#Types
					placeResult.address_components.forEach(function(entry) {
					    if (entry.types[0]=='locality') {
					    	city = entry.long_name;
					    }
					});
			      	//Alimenter les champs Kidzou
			        kidzouPlaceModule.model.proposePlace('Autre adresse', {
			        		name 	: placeResult.name, 
							address : placeResult.formatted_address, 
							website : placeResult.website, 
							phone_number 	: placeResult.formatted_phone_number, 
							city 	: city,
							latitude 	: placeResult.geometry.location.lat(), //latitude
							longitude 	: placeResult.geometry.location.lng(), //longitude
							opening_hours : (placeResult.opening_hours ? placeResult.opening_hours.periods : [])
			        });

			        // la valeur que prend le <select>
			        return placeResult.name + ", " + placeResult.formatted_address;
			      }
			    }
			  }
			});
		}
		
	});

})(jQuery);

