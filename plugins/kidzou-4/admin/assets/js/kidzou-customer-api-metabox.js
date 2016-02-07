'use strict';

var kidzouCustomerAPIModule = function ($) {
	//havre de paix

	var customerID = document.querySelector('#post_ID') !== null ? document.querySelector('#post_ID').value : '';

	var CustomerAPI = React.createClass({
		displayName: 'CustomerAPI',

		getInitialState: function getInitialState() {
			return {
				apis: {
					quota: customer_api_jsvars.quota, //c'est un objet {api_name:quota}
					key: customer_api_jsvars.key,
					usage: customer_api_jsvars.usage
				}
			};
		},
		render: function render() {
			var name = Object.keys(this.state.apis.quota);
			var _q = this.state.apis.quota[name];
			return React.createElement(
				'div',
				null,
				React.createElement(
					'p',
					null,
					'API d\'accès au résumé des contenus'
				),
				React.createElement(
					'ul',
					null,
					React.createElement(
						'li',
						null,
						React.createElement(
							'label',
							null,
							'Clé de sécurité:'
						),
						React.createElement(
							'span',
							null,
							this.state.apis.key
						)
					),
					React.createElement(Field, { inputPrefix: 'kz_', validate: this.validateInt, tabIndex: 0, change: this.onEdit, label: 'Quota quotidien d\'appel aux API:', text: _q, updateParam: 'quota' }),
					React.createElement(Field, { inputPrefix: 'kz_', validate: this.validateInt, tabIndex: 0, change: this.onEdit, label: 'Utilisation sur la période:', text: this.state.apis.usage, updateParam: 'usage' })
				)
			);
		},

		/**
      * When user edits inline
      * @param  {Object} edited data
      */
		onEdit: function onEdit(data, progress, success, error) {

			var self = this;
			var keys = Object.keys(data);
			var _apis = self.state.apis;

			if (keys[0] == 'quota') {
				var _quotaKey = Object.keys(_apis['quota'])[0];
				_apis.quota[_quotaKey] = data[keys[0]];
			} else {
				_apis[keys[0]] = data[keys[0]];
			}

			// console.debug('apis',_apis);

			self.setState({ apis: _apis });

			if (typeof progress === 'function') progress();

			jQuery.get(customer_api_jsvars.api_base + '/api/get_nonce/?controller=clients&method=quota', {}, function (n) {

				jQuery.post(customer_api_jsvars.api_save_quota + '?nonce=' + n.nonce, {
					customer_id: customerID,
					quota: _apis.quota //c'est un objet
				}).done(function (r) {
					if (typeof success === 'function') success(r);
				}).fail(function (err) {
					if (typeof error === "function") error(err);
				});
			});
		},

		/**
   * validation of input field as positive int value
   */
		validateInt: function validateInt(value) {
			var patt = /^\d+$/;
			var matches = patt.exec(value); //return (typeof n == 'number' && /^-?\d+$/.test(n+''));
			return matches != null;
		}

	});

	ReactDOM.render(React.createElement(CustomerAPI, null), document.querySelector('#kz_customer_apis .react-content'));
}(jQuery);
