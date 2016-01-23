(function ( $ ) {
	"use strict";

	$(function () {

		//Creation d'un client depuis une autre endroit que la fiche Customer 
		//Ex:  creation d'un client depuis un article
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

					if (self.createdCustomerId>0)
						window.open('/wp-admin/post.php?post='+ self.createdCustomerId +'&action=edit', "_blank");

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
									// console.debug('clients, pushing ', {"id":data.post.id,"text":data.post.title});
									// clients.push({"id":data.post.id,"text":data.post.title});
									// console.debug('clients', clients);
									// self.customerSelection(data.post.id + '#' + data.post.title);
									// console.debug('self.customerSelection', self.customerSelection());
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
 
		$(document).ready(function() {
			ko.applyBindings( customerEditor.model, document.querySelector("#customer_form") ); //retourne un EventsEditorModel
			
			//maintenant que le binding est fait, faire apparaitre le form
			setTimeout(function(){
				document.querySelector("#customer_form").classList.remove('hide');
				document.querySelector("#customer_form").classList.add('pop-in');
				console.debug('customer_form', document.querySelector("#customer_form").classList);
			}, 300);
		});

	});

}(jQuery));