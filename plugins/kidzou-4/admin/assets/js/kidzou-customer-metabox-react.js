var kidzouCustomerModule = function ($) {
	//havre de paix

	var postID = document.querySelector('#post_ID') !== null ? document.querySelector('#post_ID').value : '';

	var CustomerSelector = React.createClass({
		displayName: 'CustomerSelector',

		getInitialState: function () {
			return {
				customerId: parseInt(client_jsvars.customer_id),
				hint: {
					valid: false,
					show: false,
					icon: '',
					message: ''
				},
				customerPosts: client_jsvars.customer_posts
			};
		},
		componentWillMount: function () {
			var self = this;

			//envoi d'une adresse client depuis l'exterieur
			setPlace = _place => {
				self.savePlace(_place, function () {
					self._hintMessage.onProgress('Enregistrement de l\'adresse');
				}, function () {
					self._hintMessage.onSuccess('Adresse enregistrée');
				}, function () {
					self._hintMessage.onError('Impossible d\'enregistrer l\'adresse');
				});
			};
		},
		renderOption: function (option) {
			return React.createElement(
				'span',
				null,
				option.name,
				React.createElement('br', null),
				React.createElement(
					'em',
					{ style: { fontSize: 'smaller' } },
					option.location.location_address
				)
			);
		},
		render: function () {
			var options = client_jsvars.clients_list;
		},
		saveCustomer: function (option) {},
		savePlace: function (_place, progressCallback, successCallback, errorCallback) {}
	});

	var Post = React.createClass({
		displayName: 'Post'
	});

	var HintMessage = React.createClass({
		displayName: 'HintMessage'
	});

	var Posts = React.createClass({
		displayName: 'Posts'
	});

	ReactDOM.render(React.createElement(CustomerSelector, null), document.querySelector('#kz_client_metabox .inside'));

	//global vars accessible de l'extérieur
	var setPlace;

	return {
		setPlace: setPlace //Mise à jour de l'adresse client depuis l'exterieur
	};
}(jQuery);