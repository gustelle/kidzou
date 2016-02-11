'use strict';

var kidzouEventModule = function ($) {
	//havre de paix

	var postID = document.querySelector('#post_ID') !== null ? document.querySelector('#post_ID').value : '';

	var Event = React.createClass({
		displayName: 'Event',

		getDefaultProps: function getDefaultProps() {
			var wdg = {
				name: "kz_event_reccurence_repeat_weekly_items",
				items: [{
					label: "Lundi",
					value: "1",
					checked: false
				}, {
					label: "Mardi",
					value: "2",
					checked: false
				}, {
					label: "Mercredi",
					value: "3",
					checked: false
				}, {
					label: "Jeudi",
					value: "4",
					checked: false
				}, {
					label: "Vendredi",
					value: "5",
					checked: false
				}, {
					label: "Samedi",
					value: "6",
					checked: false
				}, {
					label: "Dimanche",
					value: "7",
					checked: false
				}]
			};

			if (typeof event_jsvars.recurrence !== 'undefined' && event_jsvars.recurrence.model !== 'undefined' && event_jsvars.recurrence.model == 'weekly') {

				if (Object.prototype.toString.call(event_jsvars.recurrence.repeatItems) === '[object Array]') {

					var repeatItems = event_jsvars.recurrence.repeatItems;

					//initialisation des weekDaysGroup
					repeatItems.forEach(function (item, index) {
						wdg.items.forEach(function (day, i) {
							if (item == day.value) {
								// console.debug('setting item ' + day.label + ' checked');
								wdg.items[i] = {
									label: day.label,
									value: item,
									checked: true
								};
							}
						});
					});
				}
			}

			return {
				weekDaysGroup: wdg,
				allowRecurrence: event_jsvars.allow_recurrence
			};
		},

		getInitialState: function getInitialState() {
			var self = this;

			var recurrence = event_jsvars.recurrence || {};
			var repeatEach = typeof recurrence.repeatEach !== 'undefined' ? parseInt(recurrence.repeatEach) : 1;
			var endType = recurrence.endType || 'never';
			var endValue = recurrence.endType == 'date' ? moment(recurrence.endValue).toDate() : recurrence.endValue;
			var repeatModel = recurrence.model || 'weekly';
			var repeatItems = recurrence.repeatItems || [];
			var startDate = typeof event_jsvars.start_date !== 'undefined' && event_jsvars.start_date !== '' ? moment(event_jsvars.start_date, 'YYYY-MM-DD HH:mm:ss').toDate() : null;
			var endDate = typeof event_jsvars.end_date !== 'undefined' && event_jsvars.end_date !== '' ? moment(event_jsvars.end_date, 'YYYY-MM-DD HH:mm:ss').toDate() : null;
			var pastDates = event_jsvars.past_dates || [];

			return {
				from: startDate,
				to: endDate,
				pastDates: pastDates,
				isRecurrence: Object.keys(recurrence).length > 0 ? true : false,
				repeatEach: repeatEach,
				endType: endType,
				endValue: endValue,
				repeatModel: repeatModel,
				repeatItems: repeatItems
			};
		},

		/** 
   * Utilisation du composant depuis l'exterieur au travers de la variable globale setPlace
   */
		componentWillMount: function componentWillMount() {
			var self = this;

			//envoi de dates depuis l'exterieur
			setDates = function setDates(_start_date, _end_date, _recurrence) {
				// console.debug('setDates', _start_date, _end_date);
				self.setState({
					from: moment(_start_date).toDate(),
					to: moment(_end_date).toDate(),
					isRecurrence: false
				}, function () {
					self.saveEvent();
					// console.debug('setDates end');
				});
			};
		},

		/**
   * Eviter les boucles infinies d'update causées par l'interaction avec le ChecboxGroup
   * Pas reussi à trouver le bug
   */
		shouldComponentUpdate: function shouldComponentUpdate(nextProps, nextState) {
			// You can access `this.props` and `this.state` here
			// This function should return a boolean, whether the component should re-render.
			var diff = JSON.stringify(this.state) !== JSON.stringify(nextState);
			// console.debug('shouldComponentUpdate', diff);
			return diff;
		},

		render: function render() {
			var _this = this;

			var self = this;
			var modifiers = {
				selected: function selected(day) {
					return DateUtils.isDayInRange(day, self.state);
				}
			};

			//passer les hidden pour soumission backend de la page (récupération par WP par secu)
			return React.createElement(
				'div',
				{ className: 'kz_form' },
				React.createElement(
					'ul',
					null,
					React.createElement(
						'li',
						{ className: 'kz_grid kz_grid_2_1' },
						React.createElement(DayPicker, {
							localeUtils: DayPickerLocaleUtils, locale: 'fr',
							numberOfMonths: 2,
							onDayClick: this.handleDayClick,
							modifiers: modifiers }),
						React.createElement(
							'div',
							null,
							this.state.from == null && this.state.to == null && React.createElement(
								'p',
								null,
								'Sélectionnez le ',
								React.createElement(
									'strong',
									null,
									'jour de début'
								),
								'.'
							),
							this.state.from != null && this.state.to == null && React.createElement(
								'p',
								null,
								'Sélectionnez le ',
								React.createElement(
									'strong',
									null,
									'jour de fin'
								),
								', ',
								React.createElement(
									'strong',
									null,
									'ou laissez tel quel si l\'événement se déroule sur un seul jour'
								),
								'.'
							),
							this.state.from !== null && this.state.to !== null && React.createElement(
								'p',
								null,
								'L\'événement se déroule du ',
								moment(this.state.from).format("L"),
								' au ',
								moment(this.state.to).format("L"),
								'.'
							),
							this.state.from !== null && this.state.to == null && React.createElement(
								'p',
								null,
								'L\'événement se déroule le ',
								moment(this.state.from).format("L")
							),
							this.state.from !== null && this.state.isRecurrence && React.createElement(
								'p',
								null,
								this.summary()
							),
							this.state.from !== null && React.createElement(
								'p',
								null,
								React.createElement(
									'a',
									{ style: { cursor: 'pointer' }, onClick: this.onResetDay, className: 'button' },
									React.createElement('i', { className: 'fa fa-undo' }),
									' Annuler'
								)
							),
							React.createElement(HintMessage, { ref: function ref(c) {
									return _this._hintMessage = c;
								} })
						),
						React.createElement('input', { type: 'hidden', name: 'kz_event_start_date', value: moment(this.state.from).format("YYYY-MM-DD 00:00:00") }),
						React.createElement('input', { type: 'hidden', name: 'kz_event_end_date', value: moment(this.state.to).format("YYYY-MM-DD 23:59:59") })
					),
					this.state.from !== null && this.props.allowRecurrence && React.createElement(Checkbox, { change: this.handleRecurrenceClick, isChecked: this.state.isRecurrence, label: 'Evénement récurrent :', name: 'kz_event_is_reccuring' })
				),
				this.state.isRecurrence && this.props.allowRecurrence && React.createElement(
					'ul',
					null,
					React.createElement(
						'li',
						null,
						React.createElement(
							'label',
							{ htmlFor: 'kz_event_reccurence_model' },
							'Type de récurrence:'
						),
						React.createElement(
							RadioGroup,
							{ style: { display: 'inline' }, name: 'kz_event_reccurence_model', value: this.state.repeatModel, onChange: this.onRepeatModel },
							React.createElement('input', { type: 'radio', value: 'weekly' }),
							React.createElement(
								'span',
								{ style: { marginRight: '1em' } },
								'Hebdomadaire'
							),
							React.createElement('input', { type: 'radio', value: 'monthly' }),
							React.createElement(
								'span',
								{ style: { marginRight: '1em' } },
								'Mensuelle'
							)
						)
					),
					React.createElement(
						'li',
						null,
						React.createElement(
							'label',
							{ htmlFor: 'kz_event_reccurence_repeat_select' },
							'Répéter tous les :'
						),
						React.createElement(
							'select',
							{ name: 'kz_event_reccurence_repeat_select', onChange: this.onRecurrenceFreq, value: this.state.repeatEach },
							React.createElement(
								'option',
								{ value: '1' },
								'1'
							),
							React.createElement(
								'option',
								{ value: '2' },
								'2'
							),
							React.createElement(
								'option',
								{ value: '3' },
								'3'
							),
							React.createElement(
								'option',
								{ value: '4' },
								'4'
							)
						)
					),
					this.state.repeatModel == 'weekly' && React.createElement(
						'li',
						null,
						React.createElement(
							'label',
							{ htmlFor: 'kz_event_reccurence_repeat_items' },
							'Répéter le :'
						),
						React.createElement(CheckboxGroup, {
							values: this.props.weekDaysGroup,
							name: 'kz_event_reccurence_repeat_items',
							onUpdate: this.onRepeatDays })
					),
					this.state.repeatModel == 'monthly' && React.createElement(
						'li',
						null,
						React.createElement(
							'label',
							{ htmlFor: 'kz_event_reccurence_repeat_items' },
							'Répéter le :'
						),
						React.createElement(
							RadioGroup,
							{ style: { display: 'inline' }, name: 'kz_event_reccurence_repeat_items', value: this.state.repeatItems, onChange: this.onRepeatItems },
							React.createElement('input', { type: 'radio', value: 'day_of_month' }),
							React.createElement(
								'span',
								{ style: { marginRight: '1em' } },
								'Jour du mois'
							),
							React.createElement('input', { type: 'radio', value: 'day_of_week' }),
							React.createElement(
								'span',
								{ style: { marginRight: '1em' } },
								'Jour de la semaine'
							)
						)
					),
					React.createElement(
						'li',
						null,
						React.createElement(
							'label',
							{ htmlFor: 'kz_event_reccurence_end_type' },
							'L\'événement prend fin :'
						),
						React.createElement(
							RadioGroup,
							{ style: { display: 'inline' }, name: 'kz_event_reccurence_end_type', value: this.state.endType, onChange: this.onEndType },
							React.createElement('input', { type: 'radio', value: 'never' }),
							React.createElement(
								'span',
								{ style: { marginRight: '1em' } },
								'Jamais'
							),
							React.createElement('input', { type: 'radio', value: 'occurences' }),
							React.createElement(
								'span',
								{ style: { marginRight: '1em' } },
								'Au bout d\'un nombre de fois'
							),
							React.createElement('input', { type: 'radio', value: 'date' }),
							React.createElement(
								'span',
								{ style: { marginRight: '1em' } },
								'A une date précise'
							)
						)
					),
					React.createElement(
						'li',
						null,
						this.state.endType == 'date' && React.createElement(
							'div',
							null,
							React.createElement(
								'strong',
								null,
								'Date de fin de récurrence : '
							),
							this.state.endValue !== null && React.createElement(
								'span',
								null,
								moment(this.state.endValue).format("L")
							),
							React.createElement(DayPicker, { localeUtils: DayPickerLocaleUtils, locale: 'fr', numberOfMonths: 1, onDayClick: this.onEndDateValue, modifiers: { selected: function selected(day) {
										return DateUtils.isSameDay(_this.state.endValue, day);
									} } }),
							React.createElement('input', { type: 'hidden', name: 'kz_event_reccurence_end_value', value: moment(this.state.endValue).format("YYYY-MM-DD 23:59:59") })
						),
						this.state.endType == 'occurences' && React.createElement(
							'div',
							null,
							React.createElement(
								'label',
								{ htmlFor: 'kz_event_reccurence_end_value' },
								'Nombre d\'occurences à répéter :'
							),
							React.createElement('input', { type: 'text', name: 'kz_event_reccurence_end_value', value: this.state.endValue, onChange: this.onEndValue })
						)
					)
				),
				this.state.pastDates.length > 0 && React.createElement(List, { items: this.getPastDates(), title: 'Evenements passés' })
			);
		},

		getPastDates: function getPastDates() {
			var list = [];
			this.state.pastDates.forEach(function (item, i) {
				list.push('Du ' + moment(item.start_date).format("L") + ' au ' + moment(item.end_date).format("L"));
			});
			return list;
		},

		/** 
      * Résumé de la recurrence pour aider le user à bien comprendre le formulaire
      *
      */
		summary: function summary() {
			var self = this;
			var day = '';
			var occ = '';

			if (self.state.endType == 'occurences') occ = ', ' + self.state.endValue + ' fois ';else if (self.state.endType == 'date' && moment(self.state.endValue).isValid()) occ = ', jusqu\'au ' + moment(self.state.endValue).format("DD/MM/YYYY");

			if (self.state.repeatModel == 'weekly' && Object.prototype.toString.call(self.state.repeatItems) === '[object Array]') {
				var selected = self.props.weekDaysGroup.items.filter(function (day) {
					var isSelected = false;

					self.state.repeatItems.forEach(function (item, index) {
						if (day.value == item) isSelected = true;
					});

					return isSelected;
				});

				selected.forEach(function (item, index) {
					// console.debug('selected.forEach', item);
					if (day == '') day += ', le ';else day += ' - ';
					day += item.label;
				});
				return 'Toutes les ' + (self.state.repeatEach == 1 ? 'semaines ' : self.state.repeatEach + ' semaines ') + day + occ;
			} else {

				if (self.state.repeatItems == 'day_of_month') {
					day += ', le ' + moment(self.state.from).date();
				} else if (self.state.repeatItems == 'day_of_week') {

					//obtention du numéro de semaine dans le mois
					//@see http://stackoverflow.com/questions/21737974/moment-js-how-to-get-week-of-month-google-calendar-style
					var prefixes = [1, 2, 3, 4, 5];
					var week_number = prefixes[0 | moment(self.state.from).date() / 7];
					var week_number_suffix = week_number === 1 ? 'er' : 'eme';

					//obtention du jour dans la semaine
					day += ', le ' + week_number + week_number_suffix + ' ' + moment(self.state.from).format('dddd');
				}
				return 'Tous les ' + self.state.repeatEach + ' mois ' + day + occ;
			}
		},

		/**
   * Choix du range de dates de l'evenement
   */
		handleDayClick: function handleDayClick(e, day) {
			// console.debug('day', day)
			var self = this;
			var range = DateUtils.addDayToRange(day, self.state);
			self.setState(range, function () {
				self.saveEvent();
			});
		},

		/**
   * Check de la Box "l'evenement est recurrent"
   */
		handleRecurrenceClick: function handleRecurrenceClick(data) {
			// console.debug('_checkRecurrence', data);
			this.setState({ isRecurrence: !this.state.isRecurrence }, function () {
				this.saveEvent();
			});
		},

		/** 
   * Remise à zeo du range de date
   */
		onResetDay: function onResetDay(e) {
			e.preventDefault();
			this.setState({
				from: null,
				to: null,
				isRecurrence: false

			}, function () {
				this.saveEvent();
			});
		},

		/**
   * Choix du Modèle de recurrence hebdo ou mensuelle
   */
		onRepeatModel: function onRepeatModel(value, event) {
			// remettre en cohérence les repeatItems
			this.setState({ repeatModel: value, repeatItems: 'day_of_month' }, function () {
				this.saveEvent();
			});
		},

		/** 
   * Reception des valeurs selectionnées dans le <CheckboxGroup />
   * @param values Array
   */
		onRepeatDays: function onRepeatDays(values) {
			// console.debug('onRepeatDays');
			this.setState({ repeatItems: values }, function () {
				this.saveEvent();
			});
		},

		/**
   * Pour une recurrence de type "nombre d'occurence"
   */
		onEndValue: function onEndValue(event) {
			// console.debug('onEndValue', event.target.value);
			this.setState({ endValue: event.target.value }, function () {
				this.saveEvent();
			});
		},

		/**
   * Pour une recurrence de type "date"
   */
		onEndDateValue: function onEndDateValue(event, day) {
			// console.debug('onEndValue', day);
			this.setState({ endValue: day }, function () {
				this.saveEvent();
			});
		},

		/** 
   * Choix du type de fin de recurrence
   */
		onEndType: function onEndType(value, event) {
			// console.debug('onEndType', value);
			//remettre à zero endValue qui dépend de endType
			//sinon on risque de retomber sur un formattage de date alors que endValue avait été rempli en tant que nombre d'occurences
			this.setState({ endType: value, endValue: null }, function () {
				this.saveEvent();
			});
		},

		onRecurrenceFreq: function onRecurrenceFreq(event) {
			// console.debug('onRecurrenceFreq', event.target.value);
			this.setState({ repeatEach: event.target.value }, function () {
				this.saveEvent();
			});
		},

		onRepeatItems: function onRepeatItems(value, event) {
			// console.debug('onRepeatPeriod', value);
			this.setState({ repeatItems: value }, function () {
				this.saveEvent();
			});
		},

		saveEvent: function saveEvent() {
			var self = this;

			if (self.state.from == null) {
				self._hintMessage.onError('Renseignez une date');
				return;
			} else if (self.state.isRecurrence) {
				if (self.state.repeatModel == 'weekly' && self.state.repeatItems.length == 0) {
					self._hintMessage.onError('Choisissez les jours de récurrence');
					return;
				}
				if (self.state.endType == 'date' && (self.state.endValue == '' || self.state.endValue == null)) {
					self._hintMessage.onError('Renseignez la date de fin de récurrence');
					return;
				}
			}

			self._hintMessage.onProgress('Enregistrement');
			jQuery.get(event_jsvars.api_base + '/api/get_nonce/?controller=content&method=eventData', {}, function (n) {

				jQuery.post(event_jsvars.api_save_event + '?nonce=' + n.nonce, {
					start_date: moment(self.state.from).format('YYYY-MM-DD 00:00:00'),
					end_date: moment(self.state.to).format('YYYY-MM-DD 23:59:59'),
					recurrence: self.state.isRecurrence,
					model: self.state.repeatModel,
					repeatEach: self.state.repeatEach,
					repeatItems: self.state.repeatItems,
					endType: self.state.endType,
					endValue: self.state.endType == 'date' ? moment(self.state.endValue).format('YYYY-MM-DD 23:59:59') : self.state.endValue,
					post_id: postID
				}).done(function (r) {

					if (r.status == 'ok' && typeof r.result !== 'undefined' && r.result !== null && typeof r.result.errors !== 'undefined') {
						var key = Object.keys(r.result.errors)[0];
						self._hintMessage.onError(r.result.errors[key][0]);
					} else self._hintMessage.onSuccess('Enregistré');
				}).fail(function (err) {
					console.error(err);
					self._hintMessage.onError('Enregistrement impossible');
				});
			});
		}

	});

	ReactDOM.render(React.createElement(Event, null), document.querySelector('#kz_event_metabox .react-content'));

	//global vars accessible de l'extérieur
	var setDates;

	return {
		setDates: setDates //init depuis l'exterieur
	};
}(jQuery);
