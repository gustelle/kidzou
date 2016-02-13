var kidzouEventModule = (function($) { //havre de paix

	var postID = (document.querySelector('#post_ID')!==null ? document.querySelector('#post_ID').value : '');

	var Event = React.createClass({

		getDefaultProps: function () {
			var  wdg = {
			    	name : "kz_event_reccurence_repeat_weekly_items",
				    items : [{
				        label : "Lundi",
				        value: "1",
				        checked : false
				    }, {
				        label : "Mardi",
				        value : "2",
				        checked : false
				    }, {
				        label : "Mercredi",
				        value : "3",
				        checked : false
				    },{
				        label : "Jeudi",
				        value : "4",
				        checked : false
				    },{
				        label : "Vendredi",
				        value : "5",
				        checked : false
				    },{
				        label : "Samedi",
				        value : "6",
				        checked : false
				    },{
				        label : "Dimanche",
				        value : "7",
				        checked : false
				    }]
			    };

			if (typeof event_jsvars.recurrence!=='undefined' && event_jsvars.recurrence.model!=='undefined' && event_jsvars.recurrence.model=='weekly') {
				
				if (Object.prototype.toString.call(event_jsvars.recurrence.repeatItems) === '[object Array]') {

					var repeatItems = event_jsvars.recurrence.repeatItems;

					repeatItems.forEach(function(item,index){
						wdg.items.forEach(function(day,i){
							if (item==day.value) {
								wdg.items[i] = {
									label : day.label,
							        value : item,
							        checked : true
								}
							}
						});
					});
				}		
			}

			return {
				weekDaysGroup : wdg,
				allowRecurrence : event_jsvars.allow_recurrence
			};
		},

		getInitialState: function() {
			var self = this;
			
			var recurrence = (event_jsvars.recurrence || {});
			var repeatEach = (typeof recurrence.repeatEach!=='undefined' ? parseInt(recurrence.repeatEach) : 1);
			var endType = (recurrence.endType || 'never');
			var endValue = (recurrence.endType=='date' ? moment(recurrence.endValue).toDate() : recurrence.endValue);
			var repeatModel = (recurrence.model || 'weekly');
			var repeatItems = (recurrence.repeatItems || []);
			var startDate = (typeof event_jsvars.start_date!=='undefined' && event_jsvars.start_date!=='' ? moment(event_jsvars.start_date, 'YYYY-MM-DD HH:mm:ss').toDate() : null); 
			var endDate = (typeof event_jsvars.end_date!=='undefined' && event_jsvars.end_date!=='' ? moment(event_jsvars.end_date, 'YYYY-MM-DD HH:mm:ss').toDate() : null); 
			var pastDates = (event_jsvars.past_dates || []);

			return {
				from: startDate,
    			to: endDate,
    			pastDates : pastDates,
    			isRecurrence : (Object.keys(recurrence).length>0 ? true : false),
    			repeatEach : repeatEach,
    			endType : endType,
    			endValue : endValue,
    			repeatModel : repeatModel,
    			repeatItems : repeatItems,
			};
	    },

	    /** 
	     * Utilisation du composant depuis l'exterieur au travers de la variable globale setPlace
	     */
	    componentWillMount: function() {
	    	var self = this;

	    	//envoi de dates depuis l'exterieur
	    	setDates = (_start_date, _end_date, _recurrence) => {
	    		// console.debug('setDates', _start_date, _end_date);
	    		self.setState({
	    			from 	: moment(_start_date).toDate(),
	    			to 		: moment(_end_date).toDate(),
	    			isRecurrence : false,
	    		}, function(){
	    			self.saveEvent();
	    			// console.debug('setDates end');
	    		});
	      	};
	    },

		render: function() {
			var self = this;
			const modifiers = {
		      selected: day => DateUtils.isDayInRange(day, self.state)
		    };
		    
		    //passer les hidden pour soumission backend de la page (récupération par WP par secu)
			return (
				<div className="kz_form">
					<ul>
						<li className="kz_grid kz_grid_2_1">
				
							<DayPicker
								localeUtils={ DayPickerLocaleUtils } locale="fr"
					          	numberOfMonths={ 2 }
					          	onDayClick={ this.handleDayClick }
					          	modifiers={ modifiers } />
					        
					        <div>
					          	{ (this.state.from==null && this.state.to==null) && 
									<p>S&eacute;lectionnez le <strong>jour de d&eacute;but</strong>.</p> 
								}
						        { (this.state.from!=null && this.state.to==null) && 
						        	<p>S&eacute;lectionnez le <strong>jour de fin</strong>, <strong>ou laissez tel quel si l&apos;&eacute;v&eacute;nement se d&eacute;roule sur un seul jour</strong>.</p> 
						        }
						        { (this.state.from!==null  && this.state.to!==null) &&
						          	<p>
						          		L&apos;&eacute;v&eacute;nement se d&eacute;roule du { moment(this.state.from).format("L") } au { moment(this.state.to).format("L") }. 
						          	</p>
						        }
						        { (this.state.from!==null && this.state.to==null) &&
						          	<p>
						          		L&apos;&eacute;v&eacute;nement se d&eacute;roule le { moment(this.state.from).format("L") }  
						          	</p>
						        }
						        { this.state.from!==null && this.state.isRecurrence &&
						          	<p>
						    			{ this.summary() }
						    		</p>
						        }
						        { this.state.from!==null  &&
						          	<p>
						          		<a style={{cursor:'pointer'}} onClick={ this.onResetDay } className="button"><i className="fa fa-undo"></i>&nbsp;Annuler</a>
						          	</p>
						        }
		
					        	<HintMessage ref={(c) => this._hintMessage = c} />
					        </div>

					        <input type="hidden" name="kz_event_start_date" value={ moment(this.state.from).format("YYYY-MM-DD 00:00:00") } />
					        <input type="hidden" name="kz_event_end_date" 	value={ moment(this.state.to).format("YYYY-MM-DD 23:59:59") } />

					    </li>

					    { this.state.from!==null  && this.props.allowRecurrence && 
					    	<Checkbox change={this.handleRecurrenceClick} isChecked={this.state.isRecurrence} label="Ev&eacute;nement r&eacute;current :" name="kz_event_is_reccuring" />
					    }

					</ul>

					{ this.state.isRecurrence && this.props.allowRecurrence &&
					<ul>
						<li>
				    		<label htmlFor="kz_event_reccurence_model">Type de r&eacute;currence:</label>
				    		<RadioGroup style={{display:'inline'}} name="kz_event_reccurence_model" value={this.state.repeatModel} onChange={this.onRepeatModel}>
							    <input type="radio" value="weekly" /><span style={{marginRight:'1em'}}>Hebdomadaire</span>
							    <input type="radio" value="monthly"  /><span style={{marginRight:'1em'}}>Mensuelle</span>
							</RadioGroup>
				    	</li>
				    	<li>
				    		<label htmlFor="kz_event_reccurence_repeat_select">R&eacute;p&eacute;ter tous les :</label>
							<select name="kz_event_reccurence_repeat_select" onChange={this.onRecurrenceFreq} value={this.state.repeatEach}><option value="1">1</option><option value="2">2</option><option value="3">3</option><option value="4">4</option></select>
				    	</li>
				    	{ this.state.repeatModel=='weekly' &&
					    	<li>
					    		<label htmlFor="kz_event_reccurence_repeat_items">R&eacute;p&eacute;ter le :</label>
					    		<CheckboxGroup 
					    			values={this.props.weekDaysGroup} 
					    			name="kz_event_reccurence_repeat_items"
					    			onUpdate={this.onRepeatDays} />
					    	</li>
				    	}
				    	{ this.state.repeatModel=='monthly' &&
					    	<li>
					    		<label htmlFor="kz_event_reccurence_repeat_items">R&eacute;p&eacute;ter le :</label>
					    		<RadioGroup style={{display:'inline'}} name="kz_event_reccurence_repeat_items" value={this.state.repeatItems} onChange={this.onRepeatItems}>
								    <input type="radio" value="day_of_month" /><span style={{marginRight:'1em'}}>Jour du mois</span>
								    <input type="radio" value="day_of_week"  /><span style={{marginRight:'1em'}}>Jour de la semaine</span>
								</RadioGroup>
					    	</li>
				    	}
				    	<li>
				    		<label htmlFor="kz_event_reccurence_end_type">L&apos;&eacute;v&eacute;nement prend fin :</label>
				    		<RadioGroup style={{display:'inline'}} name="kz_event_reccurence_end_type" value={this.state.endType} onChange={this.onEndType}>
							    <input type="radio" value="never" 		/><span style={{marginRight:'1em'}}>Jamais</span>
							    <input type="radio" value="occurences" 	/><span style={{marginRight:'1em'}}>Au bout d&apos;un nombre de fois</span>
								<input type="radio" value="date"  		/><span style={{marginRight:'1em'}}>A une date pr&eacute;cise</span>
							</RadioGroup>
				    	</li>
				    	<li>
				    		{ this.state.endType=='date' &&
				    			<div>
				    				<strong>Date de fin de r&eacute;currence : </strong>
					    			{ this.state.endValue!==null && 
						    			<span>
						    				{ moment(this.state.endValue).format("L") } 
						    			</span>
						    		}  
				    				<DayPicker localeUtils={ DayPickerLocaleUtils } locale="fr" numberOfMonths={ 1 } onDayClick={ this.onEndDateValue } modifiers={{selected: day => DateUtils.isSameDay(this.state.endValue, day)}} />
				    				<input type="hidden" name="kz_event_reccurence_end_value" 	value={ moment(this.state.endValue).format("YYYY-MM-DD 23:59:59") } />
				    			</div>
				    		}
				    		{ this.state.endType=='occurences' &&
				    			<div>
				    				<label htmlFor="kz_event_reccurence_end_value">Nombre d&apos;occurences &agrave; r&eacute;p&eacute;ter :</label>
				    				<input type="text" name="kz_event_reccurence_end_value" value={this.state.endValue} onChange={this.onEndValue} /> 
				    			</div>
				    		}
				    	</li>
					</ul>
					}
					{ this.state.pastDates.length>0 &&
						<List items={this.getPastDates()} title="Evenements pass&eacute;s" />
					}	

			    </div>
					
			);
		},

		getPastDates:function() {
			var list = [];
			this.state.pastDates.forEach(function(item,i){
				list.push('Du ' + moment(item.start_date).format("L") + ' au ' +  moment(item.end_date).format("L"));
			});
			return list;
		},

		/** 
	     * Résumé de la recurrence pour aider le user à bien comprendre le formulaire
	     *
	     */
	    summary: function() {
	    	var self = this;
			var day= '';
			var occ = ''; 

			if (self.state.endType == 'occurences') 
				occ = ', ' + self.state.endValue + ' fois ';

			else if (self.state.endType == 'date' &&  moment( self.state.endValue ).isValid())
				occ = ', jusqu\'au ' + moment(self.state.endValue).format("DD/MM/YYYY");
			
			if (self.state.repeatModel=='weekly' && Object.prototype.toString.call(self.state.repeatItems)=== '[object Array]' ) {
				var selected = self.props.weekDaysGroup.items.filter(function(day){
					var isSelected = false;
					
					self.state.repeatItems.forEach(function(item, index) {
						if (day.value==item) isSelected = true;
					});
					
					return isSelected;
				});
				
			    selected.forEach(function(item, index){
			    	// console.debug('selected.forEach', item);
			    	if (day=='') day += ', le ';
			        else day+= ' - ';
			        day += item.label ;
			    });
				return 'Toutes les ' + ( self.state.repeatEach == 1 ? 'semaines ' :  self.state.repeatEach + ' semaines ' )  + day + occ;
			
			} else {

				if (self.state.repeatItems=='day_of_month') {
					day += ', le ' + moment(self.state.from).date();
				} else if (self.state.repeatItems=='day_of_week') {

					//obtention du numéro de semaine dans le mois
					//@see http://stackoverflow.com/questions/21737974/moment-js-how-to-get-week-of-month-google-calendar-style
					var prefixes = [1,2,3,4,5];
					var week_number = prefixes[0 | moment(self.state.from).date() / 7] ;
					var week_number_suffix = (week_number===1 ? 'er' : 'eme') ;

					//obtention du jour dans la semaine
					day += ', le ' + week_number + week_number_suffix + ' ' + moment(self.state.from).format('dddd');
				}
				return 'Tous les ' + self.state.repeatEach + ' mois '  + day + occ ;
			}
	    },

		/**
		 * Choix du range de dates de l'evenement
		 */
		handleDayClick:function(e,day) {
			var self = this;
			const range = DateUtils.addDayToRange(day, self.state);
    		self.setState({
    			from: range.from,
    			to : range.to
    		}, function(){
				self.saveEvent();
			});
		},

		/**
		 * Check de la Box "l'evenement est recurrent"
		 */
		handleRecurrenceClick:function(data){
			this.setState({isRecurrence: !this.state.isRecurrence}, function(){
				this.saveEvent();
			});
		},

		/** 
		 * Remise à zeo du range de date
		 */
		onResetDay:function(e) {
			e.preventDefault();
		    this.setState({
		      from: null,
		      to: null,
		      isRecurrence : false,

		    }, function(){
		    	this.saveEvent();
		    });
		},

		/**
		 * Choix du Modèle de recurrence hebdo ou mensuelle
		 */
		onRepeatModel: function(value,event) {
			// remettre en cohérence les repeatItems 
			this.setState({repeatModel:value, repeatItems:'day_of_month'}, function(){
				this.saveEvent();
			});
		},

		/** 
		 * Reception des valeurs selectionnées dans le <CheckboxGroup />
		 * @param values Array
		 */
		onRepeatDays: function(values) {
			this.setState({repeatItems:values}, function(){
				this.saveEvent();
			});
		},

		/**
		 * Pour une recurrence de type "nombre d'occurence"
		 */
		onEndValue: function(event) {
			this.setState({endValue:event.target.value}, function(){
				this.saveEvent();
			});
		},

		/**
		 * Pour une recurrence de type "date"
		 */
		onEndDateValue: function(event, day) {
			this.setState({endValue:day}, function(){
				this.saveEvent();
			});
		},

		/** 
		 * Choix du type de fin de recurrence
		 */
		onEndType: function(value,event) {
			//remettre à zero endValue qui dépend de endType
			//sinon on risque de retomber sur un formattage de date alors que endValue avait été rempli en tant que nombre d'occurences
			this.setState({endType:value, endValue:null}, function(){
				this.saveEvent();
			});
		},

		onRecurrenceFreq: function(event) {
			this.setState({repeatEach:event.target.value}, function(){
				this.saveEvent();
			});
		},

		onRepeatItems: function(value, event) {
			this.setState({repeatItems:value}, function(){
				this.saveEvent();
			});
		},

		saveEvent: function() {
			var self = this;

			if (self.state.from==null) {
				self._hintMessage.onError('Renseignez une date');
				return;
			} else if (self.state.isRecurrence) {
				if (self.state.repeatModel=='weekly' && self.state.repeatItems.length==0){
					self._hintMessage.onError('Choisissez les jours de récurrence');
					return;
				}
				if (self.state.endType=='date' && (self.state.endValue=='' || self.state.endValue==null)) {
					self._hintMessage.onError('Renseignez la date de fin de récurrence');
					return;
				}
			}

			self._hintMessage.onProgress('Enregistrement');
			jQuery.get(event_jsvars.api_base + '/api/get_nonce/?controller=content&method=eventData', {}, function (n) {

	            jQuery.post(event_jsvars.api_save_event + '?nonce=' + n.nonce, {
	            	start_date 	: moment(self.state.from).format('YYYY-MM-DD 00:00:00'),
	            	end_date 	: (self.state.to==null ? '' : moment(self.state.to).format('YYYY-MM-DD 23:59:59')),
	              	recurrence 	: self.state.isRecurrence,
	              	model 		: self.state.repeatModel,
	              	repeatEach 	: self.state.repeatEach,
	              	repeatItems : self.state.repeatItems,
	              	endType 	: self.state.endType,
	              	endValue 	: (self.state.endType=='date' ? moment(self.state.endValue).format('YYYY-MM-DD 23:59:59') : self.state.endValue),
	              	post_id 	: postID
	            }).done(function (r) {

	              	if (r.status=='ok' && typeof r.result!=='undefined' && r.result!==null && typeof r.result.errors!=='undefined') {
	                	var key = Object.keys(r.result.errors)[0];
	                	self._hintMessage.onError(r.result.errors[key][0]);
	              	} else if (r.status=='error') {
	            		self._hintMessage.onError(r.error);
	              	} else {
	              		self._hintMessage.onSuccess('Enregistré');
	              	}
	            
	            }).fail(function (err) {
	              console.error(err);
	              self._hintMessage.onError('Enregistrement impossible');
	            });
	        });
		}

	});

	ReactDOM.render(
		<Event />, 
		document.querySelector('#kz_event_metabox .react-content')
	);

	//global vars accessible de l'extérieur
	var setDates;

	return {
		setDates : setDates //init depuis l'exterieur 
	};


}(jQuery));