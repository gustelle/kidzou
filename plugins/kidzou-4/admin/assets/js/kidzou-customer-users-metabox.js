'use strict';

var kidzouCustomerUsersModule = function ($) {
	//havre de paix

	var customerID = document.querySelector('#post_ID') !== null ? document.querySelector('#post_ID').value : '';

	var CustomerUsersSelector = React.createClass({
		displayName: 'CustomerUsersSelector',

		getInitialState: function getInitialState() {
			return {
				values: customer_users_jsvars.customer_users
			};
		},
		render: function render() {
			var _this = this;

			return React.createElement(
				'div',
				{ className: 'kz_form' },
				React.createElement(Select.Async, {
					name: 'customer_users',
					placeholder: 'Contributeurs du client',
					noResultsText: 'Aucun resultat',
					searchingText: 'Recherche de Contributeurs...',
					multi: true,
					loadOptions: this.loadUsers,
					onChange: this.selectUser,
					value: this.state.values,
					valueKey: 'id',
					labelKey: 'title' }),
				React.createElement(HintMessage, { ref: function ref(c) {
						return _this._hintMessage = c;
					} })
			);
		},

		/**
   * Chargement en background des posts selon input
   *
   */
		loadUsers: function loadUsers(input, callback) {
			if (input.length < 3) {
				callback(null, { options: [] });
				return;
			}
			setTimeout(function () {

				$.ajax({
					url: customer_users_jsvars.api_get_userinfo,
					data: {
						term: input
					},
					error: function error() {
						self._hintMessage.onError('Impossible de trouver des Contributeurs');
						callback(null, { options: [] });
					},
					success: function success(data) {
						// console.debug('data',data);
						if (typeof data.status == 'undefined' || data.status.length == 0) {
							callback(null, { options: [] });
						} else {
							var _options = data.status.map(function (item) {
								return { id: item.data.ID, title: item.data.display_name + ' [' + item.data.user_email + '] [' + item.data.user_login + ']' };
							});
							callback(null, {
								options: _options
							});
						}
					}
				});
			}, 500);
		},

		/**
  * au choix d'un post dans la liste, enregistrement des posts sur le client
  */
		selectUser: function selectUser(_values) {
			var self = this;
			var _users = _values.map(function (item) {
				return item.id;
			});
			if (customerID !== '') {
				self.setState({ values: _values }, function () {
					$.get(customer_users_jsvars.api_base + '/api/get_nonce/?controller=clients&method=users', {}, function (n) {
						$.post(customer_users_jsvars.api_attach_users + '?nonce=' + n.nonce, {
							customer_id: customerID,
							users: _users
						}).done(function (r) {
							self._hintMessage.onSuccess('Enregistré');
						}).fail(function (err) {
							console.error('customer not saved', err);
							self._hintMessage.onError('Impossible d\'enregistrer');
						});
					});
				});
			} else {
				self._hintMessage.onError('Impossible d\'enregistrer');
			}
		}
	});

	ReactDOM.render(React.createElement(CustomerUsersSelector, null), document.querySelector('#kz_customer_users_metabox .react-content'));
}(jQuery);
