var kidzouAdmin = (function() { //havre de paix

	ko.bindingHandlers.select2 = {
	    init: function(element, valueAccessor) {
	        jQuery(element).select2(valueAccessor());

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

	function Message(_cls, _msg) {
			
		var self = this;

		//console.log("addMessage[" + _cls + "] : " + _msg );
		self.messageClass = "messageBox " + _cls;
		self.messageContent = _msg;

	};

	function Client(_id, _name, _conn_id, _conn_slug) {

		var self = this;
		self.name = ko.observable(_name);
		self.id = _id;
		self.connections_id = _conn_id;
		self.connections_slug = _conn_slug;

	}

	function Event(_id, _title, _valid, _customer_id) {

		var self = this;
		self.customer_id = ko.observable();
		self.title = _title;
		self.validated = ko.observable();
		self.start_date = "";
		self.end_date = "";
		self.id = _id;
		self.checked = ko.computed({
	        read: function () {
	        	return self.validated()=="1" ? true: false;
	        },
	        write: function (value) {
	            if (value)
	            	self.validated("1");
	            else
	            	self.validated("0");
	        },
	        owner: self
	    });


		self.startYear = "";
		self.startMonth = "";


		self.fromJS = function(object) {
			
			self.start_date = object.start_date;
			self.end_date = object.end_date;
			self.id = object.id;
			self.title = object.title;
			self.customer_id(object.customer_id);
			self.validated(object.validated);

			var mom = moment(self.start_date, "YYYY-MM-DD HH:mm:ss", 'fr', true);
			//console.log(mom);
			self.startYear = mom.year();
			self.startMonth = mom.month() + 1; //why ???
		};

	}

	function ClientsViewModel() {

	    var self = this;
	    self.clients = ko.observableArray();
	    self.publishRequests = ko.observable(false);

	    self.message = ko.observable();
	    self.addMessage = function(_cls, _msg) {
	    	var m = new Message(_cls, _msg);
	    	self.message(m);
	    	setTimeout(function(){
				m = null;
				self.message(m);
			},1500);
	    };
	    
	    self.releaseSubmitButton = ko.observable(true);
	    self.releaseAttachEventButton = ko.observable(true);
	    self.releaseUpdateEventsButton = ko.observable(true);
	    self.editMode = ko.observable(false);

	    self.doEdit = function() {
	    	self.editMode(true);
	    };

	    self.deleteClient = function() {

	    	var id = self.chosenClientData().client.id;
	    	
	    	vex.defaultOptions.className 			= 'vex-theme-default';
			vex.dialog.buttons.YES.text 			= 'Confirmer';
			vex.dialog.buttons.NO.text 				= 'Annuler';

			vex.dialog.confirm({
	            message: "<strong>Certain de vouloir supprimer ce client ? </strong><br/>Les &eacute;v&egrave;nements du client seront attribu&eacute;s &agrave; l&apos;administrateur !",
	            callback: function (data) {

	            	if (data) {

	            		jQuery.get('/api/clients/deleteClient', { 
			   				id 	: id
		   				}, function(data) {
		   					if (data.status==="ok"){
		    					self.addMessage("warning", "Suppression effectu&eacute;e");

		    					//rafraichir le modele
		    					self.chosenClientData(null);
		    					self.clients.remove(function(client){
		    						return client.id === id
		    					});


		   					} else {
		   						self.addMessage("error", "La Suppression n&apos;a pas pu &ecirc;tre effectu&eacute;e");
		   					}
		    			});
	            	}

	            }
	        });
		
	    };

	    self.doSaveClient = function(form) {

	    	self.addMessage("warning", "Enregistrement ...");
	    	self.releaseSubmitButton(false);
	    	var isError = false;

	    	if (self.chosenClientUsers()==="") {
	    		self.addMessage("error", "Au moins un utilisateur doit pouvoir &eacute;diter les &eacute;v&egrave;nements du client");
	    		isError = true;
	    	}
	   		if (self.chosenClientData().client.name==="") {
	    		self.addMessage("error", "Le nom du client ne peut pas &ecirc;tre vide");
	   			isError = true;
	   		}
	   		
	   		if (!isError) {

	   			var users = [];
	   			jQuery(self.chosenClientUsers().split(",")).each(function(){
	   				var pieces = this.split(":");
	   				users.push(pieces[0]);
	   			});

	   			var connections_id = 0;
	   			var connections_slug = "";
	   			if (self.chosenClientConnection()!=="") {
	   				var pieces = self.chosenClientConnection().split(":");
	   				connections_id = pieces[0];
	   				connections_slug = pieces[1];
	   			}
	   			
	   			var id = self.chosenClientData().client.id;
	   			var name = self.chosenClientData().client.name;
	   			jQuery.get('/api/clients/saveClient/', { 
		   				id 	: id,
		   				name: name,
		   				connections_id : connections_id,
		   				users : users
	   				}).done(function(data) {
	   					
	    				//mettre à jour la liste des clients pour rafraichissement du UI
	    				ko.utils.arrayForEach(self.clients(), function(client) {
					        //le client est retrouvé, il s'agit d'une mise à jour
					        if (client.id===id) {
					        	client.name(name);
					        	client.connections_id = connections_id;
					        	client.connections_slug = connections_slug;
					        }
					    });

					    //s'il s'agit au contraire d'un nouveau client
					    if ( parseInt(id)!==parseInt(data.id) ) {
				        	var newclient = new Client();
				        	newclient.id = data.id;
				        	newclient.name(name);
				        	newclient.connections_id = connections_id;
				        	newclient.connections_slug = connections_slug;
				        	self.clients.push(newclient);

				        	//updater egalement l'id du formulaire pour éviter un nouvel ajout en cas
				        	//de second click sur dans le formulaire
				        	self.chosenClientData().client.id = data.id;
					    };

					    self.addMessage("warning", "Modifications enregistr&eacute;es");
	    				self.releaseSubmitButton(true);

	    			});

	   		} else 
	   			self.releaseSubmitButton(true);
	    };

	    self.doNewClient = function(form) {

	    	self.chosenClientData({client:new Client(0,"",0,"")});
	    	self.chosenClientUsers("");
	    	self.chosenClientConnection("");
	    	self.chosenClientEvents.removeAll();

	    	self.editMode(true);

	    	var msnry = new Masonry( document.querySelector(".metabox-holder"), {
			  columnWidth: 500,
			  itemSelector: '.postbox-container',
			  "gutter": 10
			});

			msnry.layout();
	    };

	    self.addClient = function(client) {
	    	self.clients.push(client);
	    };

    	self.selectClient = function(client) { 


    		self.chosenClientUsers("");
    		self.chosenClientEvents.removeAll();
    		self.chosenClientConnection(client.connections_id + ":" + client.connections_slug);
    		self.editMode(false);

    		jQuery.get('/api/clients/getClientByID/', { id: client.id }, function(data) {

    			self.chosenClientData(data);
    			for (var i = data.client.users.length - 1; i >= 0; i--) {
    				var user = data.client.users[i];
    				var val = self.chosenClientUsers();
    				if (val!="") val += ",";
    				self.chosenClientUsers(val + user.id + ":" + user.user_login);
    			};
    			//forcer au cas ou
    			self.chosenClientConnection(data.client.connections_id + ":" + (data.client.connections_slug===null ? "" : data.client.connections_slug));
    			
    			//second temps : récup des events du client
				self.getAllEvents();

				var msnry = new Masonry( document.querySelector(".metabox-holder"), {
				  columnWidth: 500,
				  itemSelector: '.postbox-container',
				  "gutter": 10
				});

				msnry.layout();
    		});

    	};

    	self.chosenClientData = ko.observable();
    	self.chosenClientUsers = ko.observable("");
    	self.chosenClientConnection = ko.observable("");
    	self.chosenClientEvents = ko.observableArray();

    	self.queryUsers = function (query) {
	        jQuery.get('/api/users/get_userinfo/', { term: query.term, term_field: 'user_login' }, function(data) {
    			query.callback({
                    results: data.status
                });
    		});
	    };
	    self.formatUserResult = function(user) { return user.user_login; };
	    self.formatUserSelection = function(user) {  return user.user_login; } ;
	    self.initSelectedUsers = function (element, callback) {
	    	var data = [];
	    	jQuery(self.chosenClientUsers().split(",")).each(function () {
	    		var pieces = this.split(":");
	    		if (pieces[0]!=="undefined" && pieces[0]>0)
	            	data.push({id: pieces[0], user_login: pieces[1]});
	        });
	        callback(data);
	    };
	    self.selectedUserId = function(e) { return e.id+":"+e.user_login; };

	    self.queryConnections = function (query) {
	        jQuery.get('/api/connections/get_fiche_by_slug/', { term: query.term }, function(data) {
    			query.callback({
                    results: data.fiches
                });
    		});
	    };
	    self.initSelectedConnection = function (element, callback) {
    		var pieces = [];
    		var data = {id:0, slug:""};
    		//console.log("initSelectedConnection " + self.chosenClientConnection());
    		if (self.chosenClientConnection()!=="") {
    			pieces = self.chosenClientConnection().split(":");
    			data = {id: pieces[0], slug: pieces[1]};
    		}	
	        callback(data);
	    };
	   	self.formatConnectionResult = function(conn) { return conn.slug; };
	    self.formatConnectionSelection = function(conn) {  return conn.slug; } ;
	    self.selectedConnectionId = function(e) { return e.id+":"+e.slug;  };

	    //events
	    self.attachedEvents = ko.observable("");
	    self.queryEvents = function (query) {
	        jQuery.get('/api/events/queryAttachableEvents/', { term: query.term }, function(data) {
    			query.callback({
                    results: data.events
                });
    		});
	    };
	    
	   	self.formatEventResult = function(ev) { 
	   		return self.formatEventSelection(ev); 
	   	};
	    self.formatEventSelection = function(ev) {  
	    	var validated = ev.validated; 
	   		var start = moment(ev.start_date, "YYYY-MM-DD HH:mm:ss");
	   		var end = moment(ev.end_date, "YYYY-MM-DD HH:mm:ss");
	   		var diff = start.diff(end, 'hours') // 1
	   		return "<span class='" + (ev.validated==="1" ? "validated" : "draft") + "'>" + ev.title + " <span class='date'>(" + start.format("DD MMM YYYY") + ", " + moment.duration(diff, "hours").humanize() + ")</span></span>"; 
	    };
	    self.selectedEventId = function(e) { return e.id+"|"+e.title+"|"+e.validated+"|"+e.start_date+"|"+e.end_date;  };
	    self.doAttachEvents = function(form) {

	    	self.addMessage("warning", "Ajout en cours...");
	    	self.releaseAttachEventButton(false);

   			var events = [];

   			jQuery(self.attachedEvents().split(",")).each(function(){
   				var pieces = this.split("|");
   				events.push(pieces[0]);

   			});

   			var id = self.chosenClientData().client.id;
   			jQuery.get('/api/events/attachToClient/', { 
	   				id 	: id,
	   				events: events
   				}).done(function(data) {
   					
   					self.addMessage('warning','Ajout&eacute; !');
   					self.attachedEvents("");
    				self.releaseAttachEventButton(true);

    				//RAZ des filtres
   					self.getAllEvents();;

    			});
		};

		self.detachEvent = function(data) {
			self.addMessage('warning','Suppression en cours...');
			var customer_id = self.chosenClientData().client.id;
			var event_id = data.id;
			jQuery.get('/api/events/detachFromClient/', { 
	   				customer_id : customer_id,
	   				event_id 	: event_id
   				}).done(function(data) {
   					self.addMessage('warning','Supprim&eacute; !');
    				//RAZ des filtres
   					self.getAllEvents();;

    			});
		};

		self.showEventDetails = function(data) {
			self.addMessage('warning','Chargement...');
			console.log(data);

			vex.defaultOptions.className 			= 'vex-theme-default';
			vex.dialog.buttons.YES.text 			= 'Publier cet &eacute;v&egrave;nement';
			vex.dialog.buttons.NO.text 				= 'Fermer';

			vex.dialog.confirm({
	            message: jQuery('#event-details').html(),
	            callback: function (confirm) {

	            	if (confirm) {

	            		console.log("confirmation demandee");
	            	}

	            }
	        });
		}

		self.filtering = ko.observable(false);

		self.eventsYears = ko.dependentObservable(function() {
			var categories = ko.utils.arrayMap(self.chosenClientEvents(), function(item) {
		        return item.startYear;
		    });
		    return ko.utils.arrayGetDistinctValues(categories).sort();
		}, self);

		self.eventsMonths = ko.dependentObservable(function() {
			var categories = ko.utils.arrayMap(self.chosenClientEvents(), function(item) {
		        return item.startMonth;
		    });
		    return ko.utils.arrayGetDistinctValues(categories).sort();
		}, self);

		self.filterEventsByYear = function(year) {

			var evs = ko.utils.arrayFilter( self.chosenClientEvents(), function(item) {
	            return (item.startYear == year);
	        });
	        self.chosenClientEvents(evs);
	        self.filtering(true);
		};

		self.filterEventsByMonth = function(month) {
			var evs = ko.utils.arrayFilter( self.chosenClientEvents(), function(item) {
	            return (item.startMonth == month);
	        });
	        self.chosenClientEvents(evs);
		};

		self.getAllEvents = function() {
			var client = self.chosenClientData().client;
			self.chosenClientEvents.removeAll();
			//self.getEvents(client, 'all');
			jQuery.getJSON("/api/clients/getEventsByClientID/", { id: client.id, filter:"all" }, function (data) {
				self.mapEvents(client, data.events);
				self.filtering(false);
			});
		};

		self.mapEvents = function(client,list) {

			for (var j = list.length - 1; j >= 0; j--) {
				var event = new Event();
				event.fromJS(list[j]);
				event.customer_id(client.id);
				self.chosenClientEvents.push(event);
			}
		};

		self.doUpdateEvents = function() {

			self.addMessage("warning", "Enregistrement en cours...");
			self.releaseUpdateEventsButton(false);
			var validated = ko.utils.arrayFilter(self.chosenClientEvents(), function(item) {
	            return item.checked();
	        });
	        var vid = ko.utils.arrayMap(validated, function(item) {
		    	return item.id;
		    });
	        var unvalidated = ko.utils.arrayFilter(self.chosenClientEvents(), function(item) {
	            return !item.checked();
	        });
	        var unvid = ko.utils.arrayMap(unvalidated, function(item) {
		    	return item.id;
		    });
			
			jQuery.getJSON("/api/events/saveEvents/", { validated: vid, unvalidated : unvid}, function (data) {
				//console.log(data);
				self.addMessage("warning", "Bien enregistr&eacute; !");
				self.releaseUpdateEventsButton(true);
			});
			
		};
			
	}

	jQuery.getJSON("/api/clients/getClients/")
		.done(function (data) {

		var model = new ClientsViewModel();
		
		for (var i = data.clients.length - 1; i >= 0; i--) {
			var client = new Client();
			client.id = data.clients[i].id;
			client.name(data.clients[i].name);
			client.connections_id = data.clients[i].connections_id;
			client.connections_slug = (data.clients[i].connections_slug===null ? "" : data.clients[i].connections_slug);
			model.addClient(client);
		}

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