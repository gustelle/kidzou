

var kidzouAdmin = (function() { //havre de paix

	var model = new PublishRequestsModel();

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

	};

	
	function PublishRequestsModel() {

	    var self = this;

	    //temp data pour selectBox (id:name)
	    //self.selectedClient = ko.observable(""); 
	    //self.selectedConnection = ko.observable(""); 
	    //self.selectedUsers = ko.observable(""); 
	    //self.selectedSecondUsers = ko.observable(""); 

	    //self.clients = []; //liste des clients
	    //self.chosenClientData = ko.observable(); //le client selectionné [objet Client()]
    	self.requests = ko.observableArray(); 

	    self.message = ko.observable();
	    //self.releaseSubmitButton = ko.observable(true);
	    //self.releaseAttachEventButton = ko.observable(true);
	    //self.releaseUpdateEventsButton = ko.observable(true);
	    //self.editMode = ko.observable(false);

	    //self.attachedEvents = ko.observable("");
		//self.theEvent = {};
		self.filtering = ko.observable(false);
		self.filters = {year:-1, month:-1};
		self.currentPage = ko.observable(0);
		self.pages = ko.observable(0);
		self.isMoreEvents = ko.computed(function(){
			//plus d'une page et on n'est pas sur la dernière page
			return self.pages()>1 && (self.currentPage()+1) <=  self.pages();
		});


		function resetFilters() {
			self.currentPage(0);
			self.filters = {year:-1, month:-1};
		}
	    
	    self.addMessage = function(_cls, _msg) {
	    	var m = new Message(_cls, _msg);
	    	self.message(m);
	    	setTimeout(function(){
				m = null;
				self.message(m);
			},1500);
	    };

    	self.moreEvents = function() {
    		// console.log(self.chosenClientEvents().length);
    		self.currentPage (self.currentPage() + 1);
    		getMoreEvents();
    	};

		self.eventsYears = ko.computed(function() {
			var categories = ko.utils.arrayMap(self.requests(), function(item) {
				var mom = moment(item.start_date(), "YYYY-MM-DD HH:mm:ss", 'fr', true);
		        return mom.year();
		    });
		    return ko.utils.arrayGetDistinctValues(categories).sort();
		}, self);

		self.eventsMonths = ko.computed(function() {
			var categories = ko.utils.arrayMap(self.requests(), function(item) {
				var mom = moment(item.start_date(), "YYYY-MM-DD HH:mm:ss", 'fr', true);
		        return mom.format('MMM'); 
		    });
		    return ko.utils.arrayGetDistinctValues(categories).sort();
		}, self);

		self.eventDetailsLink = function(id) {
			return "admin.php?page=hc_rse_add_event&edit_id=" + id;
		}

		self.filterEventsByYear = function(year) {
			var evs = ko.utils.arrayFilter( self.requests(), function(item) {
				var mom = moment(item.start_date(), "YYYY-MM-DD HH:mm:ss", 'fr', true);
				self.filters.year = year;
	            return (mom.year() == year);
	        });
	        self.requests(evs);
	        self.filtering(true);
		};

		self.filterEventsByMonth = function(month) {
			var evs = ko.utils.arrayFilter( self.requests(), function(item) {
				var itemMoment = moment(item.start_date(), "YYYY-MM-DD HH:mm:ss", 'fr', true);
				var _parsedMonth = moment(month, 'MMM');
				self.filters.month = _parsedMonth.month();
	            return (_parsedMonth.month() == itemMoment.month());
	        });
	        self.requests(evs);
	        self.filtering(true);
		};

		self.getAllRequests = function() {

			//var client = self.chosenClientData();
			//self.chosenClientEvents.removeAll();
			resetFilters();

			jQuery.getJSON(kidzou_jsvars.api_publishRequests, function (data) {

				if (data.events) {
					ko.utils.arrayForEach(data.events, function(item) {
						var ev = ko.mapping.fromJS(item);
						self.requests.push(ev);
					});
				}
				if (data.count) {
					self.pages(Math.ceil(parseInt(data.count) / 10)); 
				}
				
				self.filtering(false);
			});
		};


		function getMoreEvents () {

			self.addMessage('warning', 'Chargement en cours...');
			//var client = self.chosenClientData();
			//self.chosenClientEvents.removeAll();
			// console.log(self.filters);
			jQuery.getJSON(kidzou_jsvars.api_publishRequests, { page : self.currentPage(), filters: ko.toJS(self.filters), index:self.requests().length  }, function (data) {

				if (data.events) {
					ko.utils.arrayForEach(data.events, function(item) {
						var ev = ko.mapping.fromJS(item);
						self.requests.push(ev);
					});
				}
				if (data.count) {
					self.pages(Math.ceil(parseInt(data.count) / 10)); 
				}
			});
		};

		self.checkUncheckEvent = function(data) {
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

	jQuery.getJSON(kidzou_jsvars.api_publishRequests)
		.done(function (data) {

			// console.log(data);

			//jQuery.getJSON("/api/events/publishRequests/", function (data) {
				//console.log(data.events.length);
				if (data.events) {
					ko.utils.arrayForEach(data.events, function(item) {
						var ev = ko.mapping.fromJS(item);
						model.requests.push(ev);
					});
				}
				if (data.count) {
					model.pages(Math.ceil(parseInt(data.count) / 10)); 
				}
				
				model.filtering(false);
			//});

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