'use strict';

var kidzouFeaturedModule = function ($) {
	//havre de paix

	var postID = document.querySelector('#post_ID') !== null ? document.querySelector('#post_ID').value : '';

	var Featured = React.createClass({
		displayName: 'Featured',

		getInitialState: function getInitialState() {
			return {
				isChecked: featured_jsvars.is_featured
			};
		},
		render: function render() {

			return React.createElement(
				'div',
				{ className: 'kz_form' },
				React.createElement(
					'ul',
					null,
					React.createElement(Checkbox, { change: this._onChange, isChecked: this.state.isChecked, label: 'Mettre ce contenu en avant :', name: 'kz_featured' })
				)
			);
		},

		_onChange: function _onChange(data, progress, success, error) {
			var self = this;
			self.setState({ isChecked: !self.state.isChecked }, function () {

				if (typeof progress === 'function') progress();

				jQuery.get(featured_jsvars.api_base + '/api/get_nonce/?controller=content&method=featured', {}, function (n) {

					jQuery.post(featured_jsvars.api_save_featured + '?nonce=' + n.nonce, {
						post_id: postID,
						featured: self.state.isChecked
					}).done(function (r) {
						if (typeof r.status !== 'undefined' && r.status !== 'error') {
							if (typeof success === 'function') success(r);
						} else {
							if (typeof error === "function") error(err);
						}
					}).fail(function (err) {
						if (typeof error === "function") error(err);
					});
				});
			});
		}

	});

	//tous les users ne voient pas cette metabox, dans ce cas l'élément DOM n'existe pas
	if (document.querySelector('#kz_featured_metabox .react-content') !== null) {
		ReactDOM.render(React.createElement(Featured, null), document.querySelector('#kz_featured_metabox .react-content'));
	}
}(jQuery);
