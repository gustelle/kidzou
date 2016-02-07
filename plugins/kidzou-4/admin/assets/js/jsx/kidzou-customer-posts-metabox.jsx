var kidzouCustomerPostsModule = (function($) { //havre de paix

	var customerID = (document.querySelector('#post_ID')!==null ? document.querySelector('#post_ID').value : '');

	var CustomerPostsSelector = React.createClass({
		getInitialState: function() {
			return {
				values : customer_posts_jsvars.customer_posts
			};
	    },
		render: function() {
			return (
				<div className="kz_form">
				    <Select.Async
					    name="customer_posts"
					    placeholder="Articles du client"
					    noResultsText="Aucun resultat"
					    searchingText="Recherche d'articles..."
					    multi={true}
					    loadOptions={this.loadPosts}
					    onChange={this.selectPost} 
					    value={this.state.values} 
					    valueKey="id" 
					    labelKey="title" />		
					<HintMessage ref={(c) => this._hintMessage = c} />	
				</div>
			);
		},


		/**
		 * Chargement en background des posts selon input
		 *
		 */
		 loadPosts : function(input, callback) {
		 	if (input.length<3) {
		 		callback(null,{options:[]});
		 		return;
		 	}
		 	setTimeout(function() {

		 		$.ajax({
		            url: customer_posts_jsvars.api_queryAttachablePosts ,
		            data: {
		                term: input,
		            },
		            error: function() {
		            	self._hintMessage.onError('Impossible de trouver les articles');
		                callback(null,{options:[]});
		            },
		            success: function(data) { 
		            	// console.debug('data',data);
		            	if (typeof data.posts=='undefined' || data.posts.length==0) {
		            		callback(null,{options:[]});
		            	} else {
		            		var _options = data.posts.map(function(item){
		            			return {id: item.id, title: item.title};
		            		});
		            		callback(null, {
					            options: _options,
					        });
		            	} 
		            }
		        });
		    }, 500);
		 },

		/**
		* au choix d'un post dans la liste, enregistrement des posts sur le client
		*/
		selectPost: function(_values) {
			var self = this;
			var _posts = _values.map(function(item){
				return item.id;
			});
			if (customerID!=='') {
			  	self.setState({values: _values}, function(){
			  		$.get(customer_posts_jsvars.api_base + '/api/get_nonce/?controller=clients&method=posts', {}, function (n) {
						$.post(customer_posts_jsvars.api_attach_posts + '?nonce=' + n.nonce, {
							customer_id: customerID,
							posts: _posts
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

	ReactDOM.render(
		<CustomerPostsSelector />, 
		document.querySelector('#kz_customer_posts_metabox .react-content')
	);

}(jQuery));