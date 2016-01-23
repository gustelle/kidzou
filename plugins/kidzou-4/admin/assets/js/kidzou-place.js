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

				// console.debug('completePlace', result);

				self.placeData().place(new Place(
						result.name, 
						result.address, 
						result.website, 
						result.phone, 
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
				
				//blocage de la surcharge d'adresse dans proposePlace
				//si on reprend une adresse et qu'on en propose une autre
				//Ex : 	j'utilise une adresse, le client n'est pas sélectionné
				//		je reprend plus tard cette adresse, je sélectionne le client mais garde l'ancienne adresse..
				self.isPlaceComplete(true);
				
				if (name!=='' || address!=='' || website!=='' || phone_number!=='' || city!=='' || lat!=='' || lng!=='')
					self.customPlace(true); //l'adresse a commencé à etre renseignée

			};

			//Ajouter une adresse à la liste des propositions
			self.proposePlace = function(type, place) {
				//si aucune place n'a été renseignée on peut directement mapper les champs
				if (!self.isPlaceComplete()) {
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
			model 		: model //EventsEditorModel
		};

	}();  //kidzouEventsEditor

	jQuery(document).ready(function() {
		ko.applyBindings( kidzouPlaceEditor.model, document.querySelector("#place_form") ); //retourne un EventsEditorModel
		
		//maintenant que le binding est fait, faire apparaitre le form
		setTimeout(function(){
			document.querySelector("#place_form").classList.remove('hide');
			document.querySelector("#place_form").classList.add('pop-in');
		}, 300);
	});

	return {
		model : kidzouPlaceEditor.model //necessaire de fournir un acces pour interaction avec Google Maps ??
	};

}());  //kidzouPlaceModule

