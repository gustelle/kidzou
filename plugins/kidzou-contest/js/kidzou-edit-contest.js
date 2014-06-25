var kidzouContestModule = (function() { //havre de paix

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


	var kidzouContestEditor = function() { 

		var model = new ContestEditorModel();

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


		function ContestModel() {

			var self = this;
			
			//les dates sont des JS dates
		    self.end_date 			= ko.observable(moment().endOf("day").toDate()); //controler que n'est pas inférieure à eventStartDate 
   		    
		    self.formattedEndDate 	= ko.computed({
		    	read: function() {
		    		return moment(self.end_date()).endOf('day').format("YYYY-MM-DD HH:mm:ss");
		    	},
		    	write: function(value) {
		    		self.end_date(moment(value).endOf('day').format("YYYY-MM-DD HH:mm:ss"));
		    		self.end_date.notifySubscribers();
		    	},
		    	owner:self
		    });
		    self.formattedEndDate.extend({ required: true, dateAfter : self.formattedStartDate, notify: 'always' });
		    
		}


		function ContestEditorModel() {


		    var self = this;

		    self.contestData 			= ko.observable(new ContestModel());
		    //liste des gagnants du concours
			self.selectedWinners = ko.observable(""); 

			//recuperation au format 2014-12-03 23:59:59 et mise au format JS date
			self.initDates = function( end ) {
				
				var end_mom;

				if (end==='')
					end_mom = moment();
				else
					end_mom = moment(end);

				self.contestData().end_date(end_mom.toDate());
			};

			self.selectedWinnerId = function(e) {
				var resp = e.data.ID+":"+e.data.user_login+":"+e.data.user_email; 
		    	return resp;
		    };

		    self.formatWinner = function(user) { 
		    	var resp = user.data.user_login; 
		    	if (user.data.user_email)
		    		resp += ' <em>(' + user.data.user_email + ')</em>';
		    	return resp;
		    };

		    self.initSelectedWinners = function (element, callback) {
				var data = [];
				if(self.selectedWinners().trim()!='')
				{
					ko.utils.arrayForEach(self.selectedWinners().split(","), function(item) {
						var pieces = item.split(":");
						data.push({data:{ID: pieces[0], user_login: pieces[1], user_email: pieces[2]}});
					});
			        callback(data);
				}
		    };

		    self.queryWinners = function (query) {
		        jQuery.get(kidzou_contest_jsvars.api_contest_get_participants, { term: query.term, post_id : jQuery('#post_ID').val() }, function(data) {
	    			query.callback({
	                    results: data.results
	                });
	    		});
		    };

			
		} //ContestEditorModel

		return { 
			model 		: model //ContestEditorModel
		};

	}();  //kidzouContestEditor

	jQuery(document).ready(function() { 
		ko.applyBindings( kidzouContestEditor.model, document.getElementById("contest_form") ); //retourne un EventsEditorModel
	});

	return {
		model : kidzouContestEditor.model //necessaire de fournir un acces pour interaction avec Google Maps ??
	};

}());  //kidzouContestModule

