var kidzouEventsModule = (function() { //havre de paix

	var windowURL = window.URL || window.webkitURL;
	
	//voir http://jsfiddle.net/3LT9d/
	ko.extenders.debug = function(target, option) {
	    target.subscribe(function(newValue) {
	       console.debug(option + ": " + newValue);
	    });
	    return target;
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


	ko.bindingHandlers.datepicker = {
	    init: function(element, valueAccessor, allBindingsAccessor) {
	        var $el = jQuery(element);
	        
	        //initialize datepicker with some optional options
	        var options = allBindingsAccessor().datepickerOptions || {};
	        $el.datepicker(options);
	        jQuery.datepicker.setDefaults(jQuery.datepicker.regional.fr);

	        //handle the field changing
	        ko.utils.registerEventHandler(element, "change", function() {
	            var observable = valueAccessor();
	            observable($el.datepicker("getDate"));
	            jQuery(element).blur();
	        });

	        //handle disposal (if KO removes by the template binding)
	        ko.utils.domNodeDisposal.addDisposeCallback(element, function() {
	            $el.datepicker("destroy");
	        });

	    },
	    update: function(element, valueAccessor) {
	        var value = ko.utils.unwrapObservable(valueAccessor()),
	            $el = jQuery(element),
	            current = $el.datepicker("getDate");
	        // console.log("current " + current);
	        if (value - current !== 0) {
	            $el.datepicker("setDate", value);   
	        }
	    }
	};

	//verification qu'une date est postérieure à une autre
	//extension ko-validation
	ko.validation.rules.dateAfter = {
	    validator: function (val, other) {
	    	
	    	//prevenir les champs vides ou invalides
	    	if (!moment(val).isValid() || !moment(val).isValid(other))
	    		return true;

	        return moment(val).isAfter(moment(other)); // true
	    },
	    message: 'La date doit être postérieure à la date de début'
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


	var kidzouEventsEditor = function() { 

		var model = new EventsEditorModel();


		function EventModel() {

			var self = this;

			// self.place 				= ko.observable(new Place());
			
			//les dates sont des JS dates
		    self.start_date 	 	= ko.observable("");//= ko.observable(moment().startOf("day").toDate());
		    self.end_date 			= ko.observable("");//= ko.observable(moment().endOf("day").toDate()); //controler que n'est pas inférieure à eventStartDate 
   		    
		    self.formattedStartDate 	= ko.computed({
		    	read: function() {
		    		if ( moment( self.start_date() ).isValid() )
		    			return moment(self.start_date()).startOf("day").format("YYYY-MM-DD HH:mm:ss");
		    		return '';
		    	},
		    	write: function(value) {
		    		if ( moment(value).isValid() ) {
						self.start_date(moment(value).startOf("day").format("YYYY-MM-DD HH:mm:ss"));
		    			self.start_date.notifySubscribers();
					} else {
						self.start_date("");
					}
		    	},
		    	owner:self
			});
			self.formattedStartDate.extend({ required: false, notify: 'always'}); 

		    self.formattedEndDate 	= ko.computed({
		    	read: function() {
		    		if ( moment( self.end_date() ).isValid() )
		    			return moment(self.end_date()).endOf('day').format("YYYY-MM-DD HH:mm:ss");
		    		return '';
		    	},
		    	write: function(value) {
		    		if ( moment(value).isValid() ) {
		    			self.end_date(moment(value).endOf('day').format("YYYY-MM-DD HH:mm:ss"));
		    			self.end_date.notifySubscribers();
		    		} else {
						self.end_date("");
					}
		    	},
		    	owner:self
		    });
		    self.formattedEndDate.extend({ required: false, dateAfter : self.formattedStartDate, notify: 'always' });

		    self.eventDuration = ko.computed(function() {

		    	var start = moment(self.formattedStartDate(), "YYYY-MM-DD HH:mm:ss");
		    	var end = moment(self.formattedEndDate(), "YYYY-MM-DD HH:mm:ss");
		    	// console.log("start " + start);
		    	// console.log("end " + end);
		    	var diff = end.diff(start, 'hours');
		    	if (moment.duration(diff, "hours")>0)
		    		return moment.duration(diff, "hours").humanize();
		    	return "";
		    });

		    
		}


		function EventsEditorModel() {


		    var self = this;

		    self.eventData 			= ko.observable(new EventModel());

		   

			//recuperation au format 2014-12-03 23:59:59 et mise au format JS date
			self.initDates = function(start, end ) {
				
				var start_mom, end_mom;

				if (start!=='' && moment(start).isValid()) {
					start_mom = moment(start);
					self.eventData().start_date(start_mom.toDate());
				}

				if (end!=='' && moment(end).isValid()) {
					end_mom = moment(end);
					self.eventData().end_date(end_mom.toDate());
				}
				
			};



			//utilisé pour le formattage des evenements dans la liste eventsList()
			self.eventDatesFormatter = function(ev) {
				var start_moment	= moment(ev.start_date(),"YYYY-MM-DD HH:mm:ss");
				var end_moment		= moment(ev.end_date(), "YYYY-MM-DD HH:mm:ss");
				var format_start = start_moment.format("DD/MM");
				var format_end 	= end_moment.format("DD/MM");
				if (format_start!== format_end)
					return "Du " + format_start + " au " + format_end;
				else
					return "Le " + format_start;
			};

			
		} //EventsEditorModel

		return { 
			model 		: model //EventsEditorModel
		};

	}();  //kidzouEventsEditor

	jQuery(document).ready(function() { 
		ko.applyBindings( kidzouEventsEditor.model, document.getElementById("event_form") ); //retourne un EventsEditorModel
	});

	return {
		model : kidzouEventsEditor.model //necessaire de fournir un acces pour interaction avec Google Maps ??
	};

}());  //kidzouEventsModule

