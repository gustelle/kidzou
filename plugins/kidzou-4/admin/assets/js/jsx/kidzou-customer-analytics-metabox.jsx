var kidzouCustomerAnalyticsModule = (function($) { //havre de paix

	var customerID = (document.querySelector('#post_ID')!==null ? document.querySelector('#post_ID').value : '');

	var CustomerAnalytics = React.createClass({
		getInitialState: function() {
			return {
				isChecked : customer_analytics_jsvars.is_analytics
			};
	    },
		render: function() {
			
			return (
				<div className="kz_form">
					<ul>
						<Checkbox change={this._onChange} isChecked={this.state.isChecked} label="Autoriser les utilisateurs du client à voir les analytics :" name="kz_customer_analytics" />
					</ul>
				</div>
			);
		},

		_onChange: function(data, progress, success, error) {
			var self = this;
			self.setState({isChecked: !self.state.isChecked}, function(){
				console.debug('_onChange', self.state);

				if (typeof progress==='function') progress();

		      	jQuery.get(customer_analytics_jsvars.api_base + '/api/get_nonce/?controller=clients&method=analytics', {}, function (n) {

		            jQuery.post(customer_analytics_jsvars.api_save_analytics + '?nonce=' + n.nonce, {
						customer_id : customerID,
						analytics : self.state.isChecked 
					}).done(function (r) {
						if (typeof success==='function') success(r);
					}).fail(function (err) {
	                	if (typeof error === "function") error(err);
	              	});
				});
			});
			
		}


	});

	ReactDOM.render(
		<CustomerAnalytics />, 
		document.querySelector('#kz_customer_analytics_metabox .react-content')
	);


}(jQuery));