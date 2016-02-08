var kidzouCustomerModule = (function($) { //havre de paix

	var postID = (document.querySelector('#post_ID')!==null ? document.querySelector('#post_ID').value : '');

	var CustomerSelector = React.createClass({
		getInitialState: function() {
			return {
				customerId : parseInt(client_jsvars.customer_id),
				options: client_jsvars.clients_list,
				hint : {
					valid : false,
					show : false,
					icon : '',
					message : ''
				},
				customerPosts : client_jsvars.customer_posts,
				displayForm: false,
				newCustomerName : ''
			};
	    },

	    /** 
	     * Utilisation du composant depuis l'exterieur au travers de la variable globale setPlace
	     */
	    componentWillMount: function() {
	      var self = this;

	      //envoi d'une adresse client depuis l'exterieur
	      setPlace = (_place) => {
	    
	      	self.savePlace(_place, 
	      		function(){
	      			self._hintMessage.onProgress('Enregistrement de l\'adresse');
	      		},
	      		function(){
	      			self._hintMessage.onSuccess('Adresse enregistrée');
	      		},
	      		function(){
	      			self._hintMessage.onError('Impossible d\'enregistrer l\'adresse');
	      		});
	      };
	    },
	    
	    /**
	     * à l'init proposer le lieu du client en adresse du post
	     * Attendre que le DOM soit chargé pour être sur que window.kidzouPlaceModule est connu 
	     */
	    componentDidMount : function() {
	    	var self = this;
	    	document.addEventListener("DOMContentLoaded", function(event) {
	    		// console.debug('DOMContentLoaded');
	    		if (self.state.customerId>0) {
					self.proposeCustomerPlace(self.state.customerId);
				}
	    	});
			
	    },
	    renderOption: function(option) {
			return (
				<span>
					{option.name}<br/>
					{ (typeof option.location!=='undefined' && typeof option.location.location_address!=='undefined') &&
						<em style={{fontSize:'smaller'}}>{option.location.location_address}</em>
					}
				</span>
			);
		},
		render: function() {

			return (
				<div>
				    <Select
					    name="customer_select"
					    className="kz_form"
					    placeholder="Choisissez un client dans cette liste"
					    value={this.state.customerId}
					    valueKey="id"
					    labelKey="name"
					    options={this.state.options}
					    optionRenderer={this.renderOption}
					    onChange={this.saveCustomer} />
					<HintMessage ref={(c) => this._hintMessage = c} />
					{ (this.state.customerId!=='' && parseInt(this.state.customerId)>0) &&
						<p>
							<a onClick={this.onEditCustomer} style={{cursor:'pointer'}} className="button"><i className="fa fa-external-link"></i>&nbsp;Editer ce client</a>
						</p>
					}
					<Posts list={this.state.customerPosts} />
					
					{ this.state.displayForm &&
						<form className="kz_form" onSubmit={this.onCreateCustomer}>
						<hr/>
						<p>Si vous cr&eacute;ez un nouveau client avec ce formulaire, il sera automatiquement associ&eacute; &agrave; cet article. Vous pourrez ensuite renseigner l&apos;adresse du client ci-dessous ou dans l&apos;&eacute;cran d&apos;&eacute;dition du client.</p>
							<ul>
								<li>
									<label className="editableLabel">Nom du client:</label>
					            	<input type="text" onChange={this.setCustomerName} value={this.state.newCustomerName} placeholder="Nom du client a creer..."/>
					            	<HintMessage ref={(c) => this._createCustomerHintMessage = c} />
								</li>
								<li>
						            <button type="submit" id="createCustomerButton" className="button"><i className="fa fa-floppy-o"></i>&nbsp;Cr&eacute;er le nouveau client</button>
								</li>
							</ul>
						</form>
						
		            }
		            { !this.state.displayForm &&
						<p>
							<a onClick={this.onDisplayForm} style={{cursor:'pointer'}} className="button"><i className="fa fa-plus-square-o"></i>&nbsp;Cr&eacute;er un nouveau client</a>
						</p>
					}
		            
				</div>
			);
		},

		/**
		 * User clicks on "editer ce client"
		 * Ouverture d'une fenetre pour editer le client
		 */
		onEditCustomer: function() {
			window.open(client_jsvars.admin_url + 'post.php?post=' + this.state.customerId +'&action=edit' ,'_blank')
		},

		setCustomerName: function(e) {
			this.setState({newCustomerName: e.target.value});
		},

		/**
		 * Création d'un nouveau client
		 *
		 */
		 onDisplayForm : function() {
		 	this.setState({displayForm:true}, function(){
		 		document.querySelector('#createCustomerButton').removeAttribute('disabled');
		 	});
		 },

		 /**
		 * Création d'un nouveau client
		 *
		 */
		 onCreateCustomer : function(e) {

		 	e.preventDefault(); //stopper la validation de la page;
		 	if (this.state.newCustomerName=='') return;

		 	document.querySelector('#createCustomerButton').setAttribute('disabled','disabled');

		 	var self= this;
		 	self._createCustomerHintMessage.onSuccess('Création client');

		 	$.get(client_jsvars.api_base + '/api/get_nonce/?controller=posts&method=create_post', {}, function (n) {

              $.post(client_jsvars.api_create_post, {
              	nonce: n.nonce, 
              	status:'publish',
              	title: self.state.newCustomerName, 
              	type: 'customer'
              }).done(function (r) {
                // console.debug('customer saved', r);

                if (r.status=='ok' && r.post!==null && typeof r.post!=='undefined') {
                	//todo : selectionner ce nouveau client dans la selectbox
	                self._createCustomerHintMessage.onSuccess('Enregistré');
	                var options = self.state.options;
	                var newOption = {id: r.post.id, name: r.post.title, location: {location_address:''}};
	                options.push(newOption);
	                setTimeout(function(){
	                	self.setState({
		                	newCustomerName: '',
		                	displayForm:false,
		                	options : options,
		                	customerId : r.post.id
		                },function(){//console.debug('new options',options);
		                	self.saveCustomer(newOption);
		                	// self._hintMessage.onSuccess('Client mis à jour');
		            	});
	                }, 500);
                } else {
                	self._createCustomerHintMessage.onError('Impossible d\'enregistrer');
                }

              }).fail(function (err) {
                console.error('customer not saved', err);
                self._createCustomerHintMessage.onError('Impossible d\'enregistrer');
              });

            });
		 },

		/**
		 * Changement du client pour un post
		 *
		 */
		saveCustomer: function(option) { 
			var self = this;
		  	if (option!==null && typeof option.id!=='undefined'){
		  		
		  		self.setState({
			  		customerId : option.id
			  	}, function(){

			  		//save customer 
			  		if (postID!=='') {
			  			$.get(client_jsvars.api_base + '/api/get_nonce/?controller=clients&method=posts', {}, function (n) {

			              $.post(client_jsvars.api_attach_posts + '?nonce=' + n.nonce, {
			                customer_id : option.id,
			                posts : [postID]
			              }).done(function (r) {
			                // console.debug('customer saved', r);
			                self._hintMessage.onSuccess('Enregistré');

			              }).fail(function (err) {
			                console.error('customer not saved', err);
			                self._hintMessage.onError('Impossible d\'enregistrer');
			              });

			            });
			  		}

			  		self.proposeCustomerPlace(option.id);

			  	}); //setState
		  	}
		},

		proposeCustomerPlace: function(customerId) {
			// console.debug('window.kidzouPlaceModule ', window.kidzouPlaceModule);
			if (window.kidzouPlaceModule) {

				// console.debug('proposeCustomerPlace for customer ', customerId);

				$.get(client_jsvars.api_getCustomerPlace, { 
						id 	: customerId
				}).done(function(data) {
					// console.log('saveCustomer',data);
					if (data.status==='ok' && data.location.location_name!='') {
						kidzouPlaceModule.proposePlace('Adresse Client', {
								name 		: data.location.location_name,
			        			address 	: data.location.location_address,
			        			website 	: data.location.location_website, //website
			        			phone_number: data.location.location_phone_number, //phone
			        			city 		: data.location.location_city,
			        			latitude	: data.location.location_latitude,
			        			longitude 	: data.location.location_longitude,
			        			opening_hours : '' //opening hours
							});
					} 
				});
			}

		},

		/** 
		 * Enregistrement d'une adresse pour un client
		 *
		 */
		savePlace: function(_place, progressCallback, successCallback, errorCallback) {
			var self = this;
			if (typeof progressCallback === "function")
			  progressCallback();

			$.get(client_jsvars.api_base + '/api/get_nonce/?controller=content&method=place', {}, function (n) {

			    $.post(client_jsvars.api_save_place + '?nonce=' + n.nonce, {
			      contact : {
			        tel : _place.phone_number,
			        web : _place.website
			      },
			      location : {
			        name : _place.name,
			        address : _place.address ,
			        city : _place.city,
			        lat : _place.latitude,
			        lng : _place.longitude,
			        country : 'FR'
			      },
			      post_id: self.state.customerId
			    }).done(function (r) {
			      
			      if (r.status=='ok' && typeof r.result!=='undefined' && r.result!==null && typeof r.result.errors!=='undefined' && typeof errorCallback === "function")
			        errorCallback(r);

			      else if (typeof successCallback === "function")
			        successCallback(r);
			    }).fail(function (err) {
			      console.error(err);
			      if (typeof errorCallback === "function")
			          errorCallback(err);
			    });
			});
		},
	});

	var Post = React.createClass({
		render: function() {
		  return (
		    <span className="linked-item">
		      <a onClick={this._onClick} style={{cursor:'pointer'}}><i className="fa fa-external-link"></i>&nbsp;{this.props.data.title}</a>
		    </span>
		  );
		},
		_onClick: function(){
		  window.open(client_jsvars.admin_url + 'post.php?post=' + this.props.data.id +'&action=edit' ,'_blank')
		}
	});

	var Posts = React.createClass({
		render: function() {
		  var rows = [];
		  this.props.list.forEach(function(post, index) {
		    var key = 'post_' + index;
		    rows.push(<Post data={post} index={index} key={key} />);
		  });
		  return (
		    <div>
		      { this.props.list.length>0 &&
		        <div>
		          <h4>Autres articles pour ce client : </h4>
		          <p>{rows}</p>
		        </div>
		      }
		    </div>
		  );
		}
	});

	ReactDOM.render(
		<CustomerSelector />, 
		document.querySelector('#kz_client_metabox .react-content')
	);

	 //global vars accessible de l'extérieur
	var setPlace;

	return {
		setPlace : setPlace //Mise à jour de l'adresse client depuis l'exterieur
	};


}(jQuery));