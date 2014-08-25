var router = (function() {

	var current = 'listEvents';
	changeTab(current);

	function setCurrent(current) {
		this.current = current;
	}

	function getCurrent() {
		return current;
	}

	function nextScreen(target) {
		//console.log("current " + current + " / target " + target);
		switch(current)
		{
		case 'createCustomer':
			changeTab('listUsers');
			return ('listUsers');
		case 'editCustomer':
			changeTab('listEvents');
			return ('listEvents');
		default:
		  	changeTab(target);
		  	return (target);
		}
	}

	function changeTab(_label) {

		jQuery(".nav-tab").removeClass("nav-tab-active");
		jQuery(".nav-tab").each(function() {
			var mylab = jQuery(this).data( "label" );
			if (mylab === _label)
				jQuery(this).addClass("nav-tab-active");
		});
	}

	return {
		context : setCurrent,
		next : nextScreen,
		getContext : getCurrent
	};
})();

var kidzouAdmin = (function() { //havre de paix

	var model = new ClientsViewModel();

	ko.bindingHandlers.select2 = {
	    init: function(element, valueAccessor, allBindingsAccessor) {
	        var obj = valueAccessor(),
	            allBindings = allBindingsAccessor(),
	            lookupKey = allBindings.lookupKey;
	        // console.log(obj);
	        jQuery(element).select2(obj);
	        if (lookupKey) {
	            var value = ko.utils.unwrapObservable(allBindings.value);
	            jQuery(element).select2('data', ko.utils.arrayFirst(obj.data.results, function(item) {
	                return item[lookupKey] === value;
	            }));
	        }

	        ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
	            jQuery(element).select2('destroy');
	        });
	    },
	    update: function(element) {
	        jQuery(element).trigger('change');
	    }
	};

	ko.bindingHandlers.date = {
        update: function (element, valueAccessor, allBindingsAccessor, viewModel) {
            var value = valueAccessor();
            var formString = allBindingsAccessor().stringFormat;
            var dateFormat = allBindingsAccessor().dateFormat;
            var mom = moment( value, dateFormat);
            jQuery(element).text(mom.format(formString));
       }
  	};

  	ko.bindingHandlers.initCheckBox = {
	    init: function(element, valueAccessor, allBindingsAccessor, context) {
	        var value = valueAccessor();
	        if (value)
	        	jQuery(element).attr('checked','checked');
	        else
	        	jQuery(element).removeAttr('checked');
	        
	        //run the real checked binding's init function
	        ko.bindingHandlers.checked.init(element, valueAccessor, allBindingsAccessor, context);
	    }      
	};

	function Message(_cls, _msg) {
			
		var self = this;

		self.messageClass = "messageBox " + _cls;
		self.messageContent = _msg;

	}

	function Client() {

		var self = this;

		self.name = ko.observable(""); //interaction dans le formulaire d'édition client
		self.id = ko.observable(0);
		self.connections_id = ko.observable(0);
		self.connections_slug = ko.observable("");
		self.users = ko.observableArray();
		self.secondusers = ko.observableArray();

	}


	function ClientsViewModel() {

	    var self = this;

	    //temp data pour selectBox (id:name)
	    self.selectedClient = ko.observable(""); 
	    self.selectedConnection = ko.observable(""); 
	    self.selectedUsers = ko.observable(""); 
	    self.selectedSecondUsers = ko.observable(""); 

	    self.clients = []; //liste des clients
	    self.chosenClientData = ko.observable(); //le client selectionné [objet Client()]
    	self.chosenClientEvents = ko.observableArray(); 

	    self.message = ko.observable();
	    self.releaseSubmitButton = ko.observable(true);
	    self.releaseAttachEventButton = ko.observable(true);
	    self.releaseUpdateEventsButton = ko.observable(true);
	    self.editMode = ko.observable(false);

	    self.attachedEvents = ko.observable("");
		//self.theEvent = {};
		self.filtering = ko.observable(false);
		self.filters = {year:-1, month:-1};
		self.currentPage = ko.observable(0);
		self.pages = ko.observable(0);
		self.isMoreEvents = ko.computed(function(){
			//plus d'une page et on n'est pas sur la dernière page
			return self.pages()>1 && (self.currentPage()+1) <=  self.pages();
		});

		function resetClient() {
			self.chosenClientData(null);
	    	self.chosenClientEvents.removeAll();
	    	self.selectedClient("");
	    	self.selectedUsers("");
	    	self.selectedConnection("");
	    	self.selectedSecondUsers("");
		}

		function resetFilters() {
			self.currentPage(0);
			self.filters = {year:-1, month:-1};
		}

		//gestion des tabs
		self.currentTab = ko.observable('listEvents');
		self.tabs = function( data, ev ) {
			var label = jQuery(ev.target).data( "label" );
			self.currentTab(router.next(label)); //prepare data for the view
		};
	    
	    self.addMessage = function(_cls, _msg) {
	    	var m = new Message(_cls, _msg);
	    	self.message(m);
	    	setTimeout(function(){
				m = null;
				self.message(m);
			},1500);
	    };

	    self.doEdit = function() {
	    	self.editMode(true);
	    	router.context("editCustomer");
	    };

	    self.initSelectedClient = function (element, callback) {
    		var pieces = [];
    		var data = {id:0, text:""};
    		if (self.selectedClient()!=="") {
    			pieces = self.selectedClient().split(":");
    			data = {id: pieces[0], text: pieces[1]};
    		}	
	        callback(data);
	    };

	    self.selectedClientId = function(e) { return e.id+":"+e.text;  };

	    self.deleteClient = function() {

	    	var id = self.chosenClientData().id();
	    	//console.log(self.chosenClientData());
	    	
	  //   	vex.defaultOptions.className 			= 'vex-theme-default';
			// vex.dialog.buttons.YES.text 			= 'Confirmer';
			// vex.dialog.buttons.NO.text 				= 'Annuler';

			// vex.dialog.confirm({
	  //           message: "<strong>Certain de vouloir supprimer ce client ? </strong><br/>Les &eacute;v&egrave;nements du client seront attribu&eacute;s &agrave; l&apos;administrateur !",
	  //           callback: function (data) {
	  //           	if (data) {
	            		jQuery.get(kidzou_jsvars.api_deleteClient, { 
			   				id 	: id
		   				}, function(data) {
		   					if (data.status==="ok"){

		   						//updater la liste des clients de la selectBox de filtrage (opérée par select2)
		   						self.clients = ko.utils.arrayFilter(self.clients, function(item) {
		   							return item.id !== id; 
		   						});
		    					self.addMessage("warning", "Suppression effectu&eacute;e");
		    					resetClient();
		    					self.currentTab(router.next("editCustomer"));
		   					} else 
		   						self.addMessage("error", "La Suppression n&apos;a pas pu &ecirc;tre effectu&eacute;e");
		    			});
	        //     	}

	        //     }
	        // });
		
	    };

	    self.doSaveUsers = function(form) {

	    	self.addMessage("warning", "Enregistrement ...");
	    	self.releaseSubmitButton(false);
	    	var isError = false;

	    	//autorisons pour l'instant qu'il n'y ait pas de user primaire
	    	//car pour l'instant pas de distingo entre primaire et secondaire
	    	//le role edit_other_events ne convient pas (il permet à tout utilisateur disposant de cette capabilité d'éditer les évenements de n'importe quel client)
	    	if (self.selectedUsers()==="") {
	    		// self.addMessage("error", "Au moins un utilisateur doit pouvoir &eacute;diter les &eacute;v&egrave;nements du client");
	    		// isError = true;
	    	}
	   		
	   		if (!isError) {

	   			var users = [];
	   			jQuery(self.selectedUsers().split(",")).each(function(){
	   				users.push(this.split(":")[0]);
	   			});

	   			var secondusers = [];
	   			jQuery(self.selectedSecondUsers().split(",")).each(function(){
	   				secondusers.push(this.split(":")[0]);
	   			});

	   			var id = self.chosenClientData().id();
	   			
	   			jQuery.get(kidzou_jsvars.api_saveUsers, { 
		   				id 	: id,
		   				users : users,
		   				secondusers : secondusers
	   				}).done(function(data) {
	   					if (data.status==="ok") {
	   						self.addMessage("warning", "Modifications enregistr&eacute;es");
	    					self.releaseSubmitButton(true);
	    					//self.currentTab(router.next("editCustomer"));
	   					} else {
	   						self.addMessage("error", "Erreur : " + data.error);
	   					}
	    			});

	   		} else 
	   			self.releaseSubmitButton(true);
	    };

	    self.doSaveClient = function(form) {

	    	self.addMessage("warning", "Enregistrement ...");
	    	self.releaseSubmitButton(false);
	    	var isError = false;

	   		if (self.chosenClientData().name()==="") {
	    		self.addMessage("error", "Le nom du client ne peut pas &ecirc;tre vide");
	   			isError = true;
	   		}
	   		
	   		if (!isError) {

	   			jQuery.get(kidzou_jsvars.api_saveClient, { 
		   				id 	: self.chosenClientData().id(),
		   				name: self.chosenClientData().name(),
		   				connections_id : self.selectedConnection().split(":")[0]
	   				}).done(function(data) {

					    //s'il s'agit au contraire d'un nouveau client
					    if ( data.status==="ok") {

					    	if (parseInt(self.chosenClientData().id())!==parseInt(data.id)) {
					        	//updater egalement l'id du formulaire pour éviter un nouvel ajout en cas
					        	//de second click sur dans le formulaire
					        	self.chosenClientData().id(data.id);

					        	//updater le filtre de selection client 
					        	self.selectedClient(self.chosenClientData().id() + ":" + self.chosenClientData().name());

					        	//ajouter le client à la liste des clients de la selectBox de filtrage (opérée par select2)
					        	self.clients.push({id : self.chosenClientData().id(), text: self.chosenClientData().name()});
					        	
					        	//continuer la creation du client par le renseignement des users
					        	self.currentTab(router.next("listUsers"));
				        	} else {
					    		self.currentTab(router.next("editCustomer"));
					    	}
					    	self.addMessage("warning", "Modifications enregistr&eacute;es");
					   
					    } else if (data.status==="error") {
					    	self.addMessage("error", "Une erreur est survenue : " + data.error);
					    } 

	    				self.releaseSubmitButton(true);

	    			});

	   		} else 
	   			self.releaseSubmitButton(true);
	    };

	    self.doNewClient = function(form) {
	    	resetClient();
	    	self.chosenClientData(new Client());
	    	self.editMode(true);
	    	self.currentTab(router.next("editCustomer"));
	    };

	    self.resetSelectedClient = function () {
	    	//console.log("resetSelectedClient");
	    	resetClient();
	    };

    	self.selectClient = function( d, ev ) { 

    		if (typeof ev.currentTarget.attributes.value !== "undefined" && 
    			typeof ev.currentTarget.attributes.value.value === "string") {

    			self.addMessage("warning", "Patience...");

	    		self.selectedClient(ev.currentTarget.attributes.value.value);
	    		var id = self.selectedClient().split(":")[0];
	    		
	    		if (id!=="") {

	    			self.editMode(false);

		    		jQuery.get(kidzou_jsvars.api_getClientByID, { id: id }, function(data) {

		    			self.addMessage("warning", "Donnees client recupérées...");

		    			self.chosenClientData (ko.mapping.fromJS(data.client));
		    			self.selectedConnection (data.client.connections_id + ":" + (data.client.connections_slug===null ? "" : data.client.connections_slug));

		    			//console.log("loading selectedConnection : " + self.selectedConnection());
		    			
		    			var tmpUsers = "";
		    			ko.utils.arrayForEach(data.client.users, function(item) {
		    				if (tmpUsers!=="")
		    					tmpUsers +=",";
		    				tmpUsers += item.id + ":" + item.user_login;
		    			});
		    			self.selectedUsers (tmpUsers);
		    			//console.log(self.selectedUsers());

		    			tmpUsers = "";
		    			ko.utils.arrayForEach(data.client.secondusers, function(item) {
		    				if (tmpUsers!=="")
		    					tmpUsers +=",";
		    				tmpUsers += item.id + ":" + item.user_login;
		    			});
		    			self.selectedSecondUsers (tmpUsers);
		    			
		    			//console.log("self.selectedConnection " + self.selectedConnection());
		    			//second temps : récup des events du client
		    			self.addMessage("warning", "Chargement des événements...");
						self.getAllEvents();

			    		self.currentTab(router.next("listEvents"));

		    		});

	    		} else {
	    			resetClient();
	    		}

    		}

    	};

    	self.queryUsers = function (query) {
	        jQuery.get(kidzou_jsvars.api_get_userinfo, { term: query.term, term_field: 'user_login' }, function(data) {
	        	var filteredResults = ko.utils.arrayFilter(data.status, function(user) {
	        			//console.log("filtering " + isNaN(parseInt(user.customer_id)) );
                    	return parseInt(user.customer_id) === 0 || isNaN(parseInt(user.customer_id));//on filtre la liste des users disponibles pour ne renvoyer que les users qui ne sont pas déjà attachés à un client
                    });
    			query.callback({
                    results: filteredResults
                });
    		});
	    };
	    self.formatUser = function(user) { return user.user_login; };
	    self.initSelectedUsers = function (element, callback) {
			var data = [];
			ko.utils.arrayForEach(self.selectedUsers().split(","), function(item) {
				var pieces = item.split(":");
				data.push({id: pieces[0], user_login: pieces[1]});
			});
	        callback(data);
	    };
	    self.initSelectedSecondUsers = function (element, callback) {
	    	var data = [];
			ko.utils.arrayForEach(self.selectedSecondUsers().split(","), function(item) {
				var pieces = item.split(":");
				data.push({id: pieces[0], user_login: pieces[1]});
			});
	        callback(data);
	    };
	    self.selectedUserId = function(e) {
	    	return e.id+":"+e.user_login; 
	    };

	    self.selectConnection = function (d, ev) {
	    	if (typeof ev.currentTarget.attributes.value !== "undefined" && 
    			typeof ev.currentTarget.attributes.value.value === "string") 
	    		self.selectedConnection(ev.currentTarget.attributes.value.value);
	    };

	    self.resetSelectedConnection = function (d, ev) {
	    	self.selectedConnection("");
	    };

	    self.queryConnections = function (query) {
	        jQuery.get(kidzou_jsvars.api_get_fiche_by_slug, { term: query.term }, function(data) {
    			query.callback({
                    results: data.fiches
                });
    		});
	    };
	    self.initSelectedConnection = function (element, callback) {
    		var data = {id:0, slug:""};
    		if (self.selectedConnection()!=="") {
    			var pieces = self.selectedConnection().split(":");
    			data = {id : pieces[0], slug : pieces[1]};
    		}	
	        callback(data);
	    };
	   	self.formatConnection = function(conn) { return conn.slug; };
	    self.selectedConnectionId = function(e) { 
	    	return e.id+":"+e.slug;
	    };

	    //events
	    self.queryEvents = function (query) {
	        jQuery.get(kidzou_jsvars.api_queryAttachableEvents, { term: query.term }, function(data) {
	        	// console.debug(data);
    			query.callback({
                    results: data.posts
                });
    		});
	    };
	    	
	    //remarque :
	    //pour les events on utilise le pipe (|) et non : comme séparateur de données
	    //car les données contiennes des ":" (start_date et end_date sont au format YYYY-MM-DD HH:mm:ss)
	    self.formatEvent = function(ev) {  
	    	var status	 	= ev.status; 
	   		var start  		= moment(ev.custom_fields.kz_event_start_date, "YYYY-MM-DD HH:mm:ss");
	   		var end 		= moment(ev.custom_fields.kz_event_end_date, "YYYY-MM-DD HH:mm:ss");
	   		var diff 		= start.diff(end, 'hours'); // 1
	   		return "<span class='" + (status==="publish" ? "validated" : "draft") + "'>" + ev.title + " <span class='date'>(" + start.format("DD MMM YYYY") + ", " + moment.duration(diff, "hours").humanize() + ")</span></span>"; 
	    };
	    self.selectedEventId = function(e) { 
	    	return e.id+"|"+e.title+"|"+e.status+"|"+e.custom_fields.kz_event_start_date+"|"+e.custom_fields.kz_event_end_date;  
	    };
	    self.initSelectedEvents = function (element, callback) {
	    	var data = [];
			ko.utils.arrayForEach(self.attachedEvents().split(","), function(item) {
				var pieces = item.split("|");
				data.push({id: pieces[0], title: pieces[1], status: pieces[2], kz_event_start_date:pieces[3], kz_event_end_date: pieces[4]});
			});
	        callback(data);
	    };
	    self.doAttachEvents = function(form) {

	    	if (self.chosenClientData().id()===0) {
	    		self.addMessage("error", "Cr&eacute;ez d&apos;abord le client !");
	    		return false;
	    	} else {

	    		self.addMessage("warning", "Ajout en cours...");
		    	self.releaseAttachEventButton(false);

	   			var events = [];
	   			jQuery(self.attachedEvents().split(",")).each(function(){
	   				// console.log("attachedEvents():" + self.attachedEvents());
	   				var pieces = this.split("|");
	   				events.push(pieces[0]);
	   			});

	   			var id = self.chosenClientData().id();
	   			//console.log(events);
	   			jQuery.get(kidzou_jsvars.api_attachToClient, { 
		   				id 	: id,
		   				events: events
	   				}).done(function(data) {
	   					// console.log("api_attachToClient : ");
	   					// console.log(data);
	   					if (data.status==="ok") {
	   						self.addMessage('warning','Ajout&eacute; !');
		   					self.attachedEvents("");
		    				self.releaseAttachEventButton(true);
		    				//RAZ des filtres
		   					self.getAllEvents();
	   					} else {
	   						self.addMessage('error','Erreur: ' + data.error);
	   					}
	    			});
	    	}
		};

		self.detachEvent = function(data) {
			self.addMessage('warning','Suppression en cours...');
			var event_id = data.id;
			jQuery.get(kidzou_jsvars.api_detachFromClient, { 
	   				event_id 	: event_id
   				}).done(function(data) {
   					self.addMessage('warning','Supprim&eacute; !');
    				//RAZ des filtres
   					self.getAllEvents();

    			});
		};

		self.eventsYears = ko.computed(function() {
			var categories = ko.utils.arrayMap(self.chosenClientEvents(), function(item) {
				if (item.custom_fields.kz_event_end_date) {
					var mom = moment(item.custom_fields.kz_event_end_date(), "YYYY-MM-DD HH:mm:ss", 'fr', true);
		        	return mom.year();
				}
		    });
		    return ko.utils.arrayGetDistinctValues(categories).sort();
		}, self);

		self.eventsMonths = ko.computed(function() {
			var categories = ko.utils.arrayMap(self.chosenClientEvents(), function(item) {
				if (item.custom_fields.kz_event_end_date) {
					var mom = moment(item.custom_fields.kz_event_end_date(), "YYYY-MM-DD HH:mm:ss", 'fr', true);
		        	return mom.format('MMM'); 
		        }
		    });
		    return ko.utils.arrayGetDistinctValues(categories).sort();
		}, self);

		self.eventDetailsLink = function(id) {
			return "post.php?action=edit&post=" + id;
		};

		self.filterEventsByYear = function(year) {
			// console.log(year);
			var evs = ko.utils.arrayFilter( self.chosenClientEvents(), function(item) {
				// console.log(item);
				if (item.custom_fields.kz_event_end_date) {
					var mom = moment(item.custom_fields.kz_event_end_date(), "YYYY-MM-DD HH:mm:ss", 'fr', true);
					self.filters.year = year;
	            	return (mom.year() == year);
				}

				//on filtre les posts qui ne sont pas de type event
				return false;
				
	        });

	        self.chosenClientEvents(evs);
	        self.filtering(true);
		};

		self.filterEventsByMonth = function(month) {
			var evs = ko.utils.arrayFilter( self.chosenClientEvents(), function(item) {
				if (item.custom_fields.kz_event_end_date) {
					var itemMoment = moment(item.custom_fields.kz_event_end_date(), "YYYY-MM-DD HH:mm:ss", 'fr', true);
					var _parsedMonth = itemMoment.format('MMM'); 
					self.filters.month = month;
		            return (_parsedMonth == month);
		        }

		        //on filtre les posts qui ne sont pas de type event
				return false;
	        });

	        self.chosenClientEvents(evs);
	        self.filtering(true);
		};

		self.getAllEvents = function() {

			var client = self.chosenClientData();
			self.chosenClientEvents.removeAll();
			resetFilters();

			jQuery.getJSON(kidzou_jsvars.api_getContentsByClientID, { 
					id : client.id()
				}, function (data) {

					self.addMessage("warning", "Evénements chargés...");

					if (data.posts) {
						ko.utils.arrayForEach(data.posts, function(item) {
							var ev = ko.mapping.fromJS(item);
							self.chosenClientEvents.push(ev);
						});
					}
					
					self.filtering(false);
			});
		};

		self.checkUncheckEvent = function(data) {
			// console.log(data.status());
			if (data.status()==="requested") {
				self.addMessage("warning", "Enregistrement...");
				//faire un save dans bouton
				jQuery.getJSON(kidzou_jsvars.api_publishEvent, { id: data.id()}, function (res) {
					if (res.status==="ok") {
						data.status("approved");
						self.addMessage("warning", "Bien enregistr&eacute; !");
					} else {
						data.status("requested");
						self.addMessage("error", data.error);
					}
				});
			} else {

				self.addMessage("warning", "Enregistrement...");
				//faire un save dans bouton
				jQuery.getJSON(kidzou_jsvars.api_unpublishEvent, { id: data.id()}, function (res) {
					if (res.status==="ok") {
						data.status("requested");
						self.addMessage("warning", "Bien enregistr&eacute; !");
					} else {
						data.status("approved");
						self.addMessage("error", data.error);
					}
				});
			}
			return true;
		};

			
	}

	jQuery.getJSON(kidzou_jsvars.api_getClients)
		.done(function (d) {

			ko.utils.arrayMap(d.clients, function(item) {
				model.clients.push({id : item.id, text: item.name});
			});

			ko.applyBindings(model);

			//pret à intéragir avec le user
			jQuery(".metabox-holder").show("slow");

	});

}());  //kidzouAdmin

Array.prototype.remove = function() {
    var what, a = arguments, L = a.length, ax;
    while (L && this.length) {
        what = a[--L];
        while ((ax = this.indexOf(what)) !== -1) {
            this.splice(ax, 1);
        }
    }
    return this;
};