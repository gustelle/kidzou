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

  	ko.bindingHandlers.logger = {
        update: function(element, valueAccessor, allBindings) {
            //store a counter with this element
            var count = ko.utils.domData.get(element, "_ko_logger") || 0,
                data = ko.toJS(valueAccessor() || allBindings());

            ko.utils.domData.set(element, "_ko_logger", ++count);

            if (window.console && console.log) {
                console.log(count, element, data);
            }
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

		function RecurrenceModel() {

			var self = this;
			
			//utilisé également à l'init des RepeatOptions
			self.days = [{ label:'Lundi', value: 1}, {label:'Mardi', value:2}, {label:'Mercredi', value:3}, {label:'Jeudi', value:4}, {label:'Vendredi', value:5}, {label:'Samedi', value:6}, {label:'Dimanche', value:7}];
			self.day_of_month = {label:'Jour du mois', value: 'day_of_month'};
			self.day_of_week = {label:'Jour de la semaine', value : 'day_of_week'};

			self.isReccuring = ko.observable(false);

			function RepeatOption( label, value, repeatEach, multipleChoice) {

				this.label = label;
				this.value = value;

				this.repeatEvery = [1,2,3,4];  //les options possibles
				this.selectedRepeatEvery = ko.observable(1);  //l'option choisie

				this.repeatEach = repeatEach;  // les options possibles
				this.selectedRepeatEachItems = ko.observableArray();  // les options sélectionnées

				//est-ce qu'on peut sélectionner dans le UI plusieurs "repeatEach"
				//Ex : répéter le lundi, le mardi
				this.multipleChoice = multipleChoice; 
			}

			self.weeklyModel = new RepeatOption('Toutes les semaines','weekly', self.days, true);
			self.monthlyModel = new RepeatOption('Tous les mois' , 'monthly', [self.day_of_month, self.day_of_week], false) ;
			
			//quelles sont les spossibilités de récurrence ?
			self.repeatOptions = [
			 	self.weeklyModel,
			 	self.monthlyModel
			];

			//et celle choisie par le User?
			self.selectedRepeat = ko.observable(self.weeklyModel);
			
			//la valeur qui est transmise au serveur
			//pas utilisée dans le UI
			self.repeatItemsValue = ko.computed(function() {
				var _r = '';
				if (self.selectedRepeat().value=='weekly') {
					var _o = [];
					ko.utils.arrayForEach(self.selectedRepeat().selectedRepeatEachItems(), function(item) {
				        _o.push(item.value);
				    });
					_r = ko.toJSON(_o);
				} else {
					_r = self.selectedRepeat().selectedRepeatEachItems().value;
				}
				return _r;
			});


			//la selection du modele de repetition est-elle visible ?
			self.showSelectRepeat = ko.observable(true);

			self.endType = ko.observable('never');  

			//si la recurrence se termine au bout d'un certain nombre de fois
			self.occurencesNumber = ko.observable(0).extend({ number: true });

			//si la recurrence se termine à une date donnée
			self.reccurenceEndDate = ko.observable("");

			//utilisée pour renvoyer au serveur uniquement, pas dans le UI
			self.formattedReccurenceEndDate = ko.computed({
		    	read: function() {
		    		if ( moment( self.reccurenceEndDate() ).isValid() ) {
		    			self.endType('date');
		    			return moment(self.reccurenceEndDate()).endOf("day").format("YYYY-MM-DD HH:mm:ss");
		    		}
		    		//si la date n'est pas valide et que le endType est positionné sur date
		    		//on force le repositionnement à never
		    		//paer contre si le endType est déjà sur "occurences", on n'y touche pas
		    		if (self.endType()=='date') self.endType('never');
		    		return '';
		    	},
		    	write: function(value) {
		    		if ( moment(value).isValid() ) {
						self.reccurenceEndDate(moment(value).endOf("day").format("YYYY-MM-DD HH:mm:ss"));
		    			self.reccurenceEndDate.notifySubscribers();
					} else {
						self.reccurenceEndDate("");
					}
		    	},
		    	owner:self
			});
			

			//résumé présenté au user 
			//c'est purement du display
			self.recurrenceSummary = ko.computed(function() {
				
				var day= '';
				var occ = ''; 

				if (self.endType() == 'occurences') 
					occ = ', ' + self.occurencesNumber() + ' fois ';
				else if (self.endType() == 'date' &&  moment( self.reccurenceEndDate() ).isValid())
					occ = ', jusqu\'au ' + moment(self.reccurenceEndDate()).format("DD/MM/YYYY");
				
				if (self.selectedRepeat().value=='weekly') {
					ko.utils.arrayForEach(self.selectedRepeat().selectedRepeatEachItems(), function(item) {
				        if (day=='') day += ', le ';
				        else day+= ' - '
				        day += item.label ;
				    });
					return 'Toutes les ' + ( self.selectedRepeat().selectedRepeatEvery() == 1 ? 'semaines ' :  self.selectedRepeat().selectedRepeatEvery() + ' semaines ' )  + day + occ;
				} else {
					if (self.selectedRepeat().selectedRepeatEachItems().value=='day_of_month') {
						day += ', le ' + moment(model.eventData().start_date()).date();
					} else if (self.selectedRepeat().selectedRepeatEachItems().value=='day_of_week') {

						//obtention du numéro de semaine dans le mois
						//@see http://stackoverflow.com/questions/21737974/moment-js-how-to-get-week-of-month-google-calendar-style
						var prefixes = [1,2,3,4,5];
    					var week_number = prefixes[0 | moment(model.eventData().start_date()).date() / 7] ;
    					var week_number_suffix = (week_number===1 ? 'er' : 'eme') ;

						//obtention du jour dans la semaine
						day += ', le ' + week_number + week_number_suffix + ' ' + moment(model.eventData().start_date()).format('dddd');
					}
					return 'Tous les ' + ( self.selectedRepeat().selectedRepeatEvery() == 1 ? 'mois ' :  self.selectedRepeat().selectedRepeatEvery() + ' mois ' ) + day + occ ;
				}
		        	
		    }, self);
			
		}

		
		function EventModel() {

			var self = this;
			
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
		    	var diff = end.diff(start, 'hours');
		    	if (moment.duration(diff, "hours")>0) {

		    		//si la durée n'est pas de 1 jour, on force le modele de recurrence mensuel
		    		//le modele de recurrence hebdo n'a pas de sens
		    		if (moment.duration(diff, "hours").days()>=1)  {
		    			self.recurrenceModel().selectedRepeat(self.recurrenceModel().monthlyModel);
		    			self.recurrenceModel().showSelectRepeat(false);
		    		} else {
		    			self.recurrenceModel().showSelectRepeat(true);
		    		}

		    		return moment.duration(diff, "hours").humanize();
		    	}
		 
		    	return "";
		    });

		    //recurrence d'événement
		    self.recurrenceModel = ko.observable(new RecurrenceModel());

		    //seulement si les dates sont renseignées
		    self.isReccurenceEnabled = ko.computed(function() {
		    	if (self.formattedStartDate()=='' || self.formattedEndDate()=='')
		    	{
		    		//désactivation des récurrences
		    		self.recurrenceModel().isReccuring(false);
		    		return false;
		    	}
				return true;
			});

			//si l'URL d'un evenement facebook est renseignée, on déclenche la mise à jour des autres champs
			// self.facebookImportMessage 	= ko.observable("");
			    
		}


		function EventsEditorModel() {


		    var self = this;

		    self.eventData 			= ko.observable(new EventModel());


			//recuperation au format 2014-12-03 23:59:59 et mise au format JS date
			self.initDates = function(start, end, reccurenceData ) {

				console.debug('initDates', start, end, reccurenceData);

				var start_mom, end_mom;

				if (start!=='' && moment(start).isValid()) {
					start_mom = moment(start);
					self.eventData().start_date(start_mom.toDate());
				}

				if (end!=='' && moment(end).isValid()) {
					end_mom = moment(end);
					self.eventData().end_date(end_mom.toDate());
				}

				//si l'objet est correctement formatté et pas vide
				if (typeof reccurenceData!='undefined' && typeof reccurenceData.model!='undefined') { //&& reccurenceData.hasOwnProperty(0)

					//c'est plus simple a manipuler
					// var data = reccurenceData[0]; 
					// console.debug(reccurenceData);

					//activation de la checkbox
					self.eventData().recurrenceModel().isReccuring(true);

					//activation de la selectbox
					if (reccurenceData.model=='weekly') {

						self.eventData().recurrenceModel().selectedRepeat(self.eventData().recurrenceModel().weeklyModel);
						ko.utils.arrayForEach(reccurenceData.repeatItems, function(item) {
					        var v = parseInt(item);
					        if (!isNaN(v)) {
					            self.eventData().recurrenceModel().selectedRepeat().selectedRepeatEachItems.push(self.eventData().recurrenceModel().days[v-1]);
					        }
					    });
					} else {

						//modele monthly
						self.eventData().recurrenceModel().selectedRepeat(self.eventData().recurrenceModel().monthlyModel);
						if (reccurenceData.repeatItems=='day_of_month')
							self.eventData().recurrenceModel().selectedRepeat().selectedRepeatEachItems(self.eventData().recurrenceModel().day_of_month);
						else
							self.eventData().recurrenceModel().selectedRepeat().selectedRepeatEachItems(self.eventData().recurrenceModel().day_of_week);
					}	

					//l'evenement se repete tous les ...
					self.eventData().recurrenceModel().selectedRepeat().selectedRepeatEvery(reccurenceData.repeatEach);

					//et se termine : à une date fixe ? au bout d'un nombre de repetitions ? jamais ?
					self.eventData().recurrenceModel().endType(reccurenceData.endType);

					if (reccurenceData.endType=='date')
						self.eventData().recurrenceModel().reccurenceEndDate(reccurenceData.endValue);
					else if (reccurenceData.endType=='occurences')
						self.eventData().recurrenceModel().occurencesNumber(reccurenceData.endValue);
						
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
		ko.applyBindings( kidzouEventsEditor.model, document.querySelector("#event_form") ); //retourne un EventsEditorModel
	
		//maintenant que le binding est fait, faire apparaitre le form
		setTimeout(function(){
			document.querySelector("#event_form").classList.remove('hide');
			document.querySelector("#event_form").classList.add('pop-in');
		}, 300);
	});

	return {
		model : kidzouEventsEditor.model //necessaire de fournir un acces pour interaction avec Google Maps ??
	};

}());  //kidzouEventsModule

(function($){

	$(document).ready(function() {

		kidzouEventsModule.model.initDates(
			events_jsvars.start_date,
			events_jsvars.end_date, 
			events_jsvars.recurrence);

	});

})(jQuery);


