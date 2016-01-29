(function($){

	$(document).ready(function() {

		/**
		 * Boite de sélection d'un client (Selectize)
		 *
		 * Tous les users ne voient pas la metabox, seuls les auteurs+
		 * si la metabox n'est pas présente, le selecteur est vide et une erreur JS est lancée
		 * 
		 */
		if ($("select[name='customer_select']").length>0) {

			$("select[name='customer_select']").selectize({
				mode: "single",
				options : client_jsvars.clients_list,
				valueField: 'id',
				labelField: 'name',
				sortField: [
					{field: 'name', direction: 'asc'},
				],
				searchField : [
					'name'
				],
				render: {
					item: function(item, escape) {
						return '<div>' + escape(item.name) + '</div>';
					},
					option: function(item, escape) {
						if (typeof item.location=='undefined' || typeof item.location.location_address=='undefined' || item.location.location_address=='') 
							return '<div>' + escape(item.name) + '</div>';
						return '<div>' + escape(item.name) + '<br/><em style="font-size:smaller">' + escape(item.location.location_address) + ', ' + escape(item.location.location_city) + '</em></div>';
					}
				},
				onItemAdd : function(value, item) {

					// console.debug("onItemAdd", value, item);

					function addEditCustomerButton(e) {
						e.preventDefault(); //stop the event, ne pas valider cette page
						window.open("post.php?post="+value+"&action=edit", "_blank");
						return false;
					}

					if (document.querySelector("#editCustomerButton")!==null) {
						document.querySelector("#editCustomerButton").removeEventListener("click", addEditCustomerButton);
						document.querySelector("#editCustomerButton").parentNode.removeChild(document.querySelector("#editCustomerButton"));
					}
					if (document.querySelector("#customerPosts")!==null)
						document.querySelector("#customerPosts").parentNode.removeChild(document.querySelector("#customerPosts"));
					
					if (window.kidzouPlaceModule) {

						$.get(client_jsvars.api_getCustomerPlace, { 
		   					id 	: value
						}).done(function(data) {
							// console.log(data);
							if (data.status==='ok' && data.location.location_name!='') {
								kidzouPlaceModule.model.proposePlace('Adresse Client', {
										name 		: data.location.location_name,
					        			address 	: data.location.location_address,
					        			website 	: data.location.location_website, //website
					        			phone_number: data.location.location_phone_number, //phone
					        			city 		: data.location.location_city,
					        			latitude	: data.location.location_latitude,
					        			longitude 	: data.location.location_longitude,
					        			opening_hours : '' //opening hours
									});
							} 
						});
					}

					//Charger la liste des posts du même client pour permettre une navigation
					$.get(client_jsvars.api_getCustomerPosts, { 
						id 	: value
					}).done(function(data) {

						if (data.status==='ok' && data.posts!='') {
							// console.log("customer posts",data.posts);
							document.querySelector("#editCustomerButton").insertAdjacentHTML('beforeBegin', '<p id="customerPosts">Autres articles pour ce client:<br/></p>');
							var list = "";//"<br/>";
							for (i = 0; i < data.posts.length; i++) { 
							    var slug = data.posts[i].slug;
							    if (i>0)
							    	list += "&nbsp;,&nbsp;";
							    list += "<a href='post.php?post=" + data.posts[i].id + "&action=edit" + "' target='_blank'>" + data.posts[i].title + "</a>";
							}
							list += "";//"<br/>";
							document.querySelector("#customerPosts").insertAdjacentHTML('beforeEnd', list);
							document.querySelector("#editCustomerButton").addEventListener("click", addEditCustomerButton);
						} 
					});

					//afficher le bouton edition du client
					document.querySelector(".selectize-control").insertAdjacentHTML('afterEnd', '<br/><button id="editCustomerButton" class="button button-large">Editer ce client</button>');

				}
			});
					
			if (parseInt(client_jsvars.customer_id)>0)
				$("select[name='customer_select']").selectize()[0].selectize.addItem(client_jsvars.customer_id, true);
		}

		/** 
		 * Custom form de création d'un nouveau client 
		 * Ex:  creation d'un client depuis un article
		 *
		 * Tous les users ne voient pas ce form, seuls les auteurs+
		 * si la metabox n'est pas présente, le selecteur est vide et une erreur JS est lancée
		 * 
		 */
		if (document.querySelector("#customer_form")!==null) {

			var customerEditor = function() {

				var model = new CustomerModel();

				function CustomerModel () {

					var self = this;

					//ce qui sera transmis dans le formulaire après creation d'un client
					// self.customerSelection = ko.observable('');

					//si le client est en mode edition 
					self.editMode = ko.observable(false); 

					//si le process de création de client échoue, on désactive le bouton
					self.creationFailure = ko.observable(false);

					//le nom du client
					self.customerName = ko.observable('');

					//simple marker pour changer le lien du bouton de création de lcient
					self.createdCustomerId = 0;

					//statut affiché au user pendant le process de creation client
					self.creationStatus = ko.observable('Cr&eacute;er le client');

					self.displayEditCustomerForm = function() {
						self.editMode(true);
					};

					self.displayCustomerSelect = function() {
						self.editMode(false);
					};

					self.createCustomer = function() {
						
						if (self.customerName()=='' || self.creationFailure()) return;

						//on ouvre la page d'édition du client créé
						if (self.createdCustomerId>0) {
							window.open('/wp-admin/post.php?post='+ self.createdCustomerId +'&action=edit', "_blank");
							//ne pas re-créer un nouveau client, sortir du script
							return;
						}

						self.creationStatus('Cr&eacute;ation en cours...');
						$.ajax({
							// get the nonce
							url: '/api/get_nonce/?controller=posts&method=create_post',
							type: 'GET',
							success: function  (data) {
								// create the post
								$.ajax({
									url: '/api/create_post/',
									type: 'POST',
									data: {nonce: data.nonce, status:'publish',title: self.customerName(), type: 'customer'},
									success: function  (data) {
										// console.debug('customer created', data);
										$("select[name='customer_select']").selectize()[0].selectize.addOption({ id: data.post.id , name: data.post.title });
										$("select[name='customer_select']").selectize()[0].selectize.addItem( data.post.id , false);
										self.creationStatus('Continuer l\'edition du client');
										
										//permettra au prochain click de continuer l'édition dans une nouvelle fenetre
										self.createdCustomerId = data.post.id;

										//va automatiquement rendre le button "enabled"
										self.creationFailure(false); 
									},
									error: function  (data) {
										console.error('create_post', data);
										self.creationStatus('La cr&eacute;ation a &eacute;chou&eacute; :-(');
										self.creationFailure(true);
									}
								});
							},
							error: function  (data) {
								console.log('get_nonce', data);
								self.creationStatus('La cr&eacute;ation a &eacute;chou&eacute; :-(');
								self.creationFailure(true);
							}
						});
					};
				}

				return { 
					model : model 
				};
			}();
	 
			ko.applyBindings( customerEditor.model, document.querySelector("#customer_form") ); //retourne un EventsEditorModel
			
			//maintenant que le binding est fait, faire apparaitre le form
			setTimeout(function(){
				document.querySelector("#customer_form").classList.remove('hide');
				document.querySelector("#customer_form").classList.add('pop-in');
			}, 300);
		}
		
	});

})(jQuery)