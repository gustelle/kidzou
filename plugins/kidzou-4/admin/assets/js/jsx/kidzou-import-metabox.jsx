
var applyChange = function(value, token, progressCallback, successCallback, errorCallback) {

	var patt = /https?:\/\/www.facebook.com\/events\/([0-9]*)\/?/i;
	var matches = patt.exec(value);

	if (matches!=null) {

		if (typeof progressCallback === "function") progressCallback();

	    FB.api("/" + matches[1] + '?access_token=' + token + '&fields=cover,name,description,place,end_time,start_time', function (response) {

		    if (response && !response.error && typeof response.start_time!='undefined') {

		    	//import en background par ajax
		    	if (import_jsvars.background_import) {

		    		var startend_time = (typeof response.end_time=='undefined' ? response.start_time : response.end_time);
		    		var place = response.place || {};
		    		var location = place.location || {};

		    		//source : 'api' inique que cette source de données est fiable, pas besoin de redressement
					var apiInfo = {
						titre :   response.name,
						description :  response.description,
						images : {
						  medium : response.cover.source
						},
						contact : {
						},
						adresse : {
						  name : place.name,
						  street : location.street,
						  city : location.city,
						  zip : location.zip,
						  lat : location.latitude,
						  lng : location.longitude,
						  country : (location.country.toLowerCase()=='france' ? 'FR' : 'US'), //sert de marqueur de redressement d'adresse sur le backend
						  adresseComplete : ''
						},
						dates : {
						  start_date : ((response.start_time.split('T'))[0] + ' 00:00:00') ,
						  end_date : ((startend_time.split('T'))[0] + ' 23:59:59')
						},
						source:  'api',
					};

					var author_id = import_jsvars.author_id || -1;

					jQuery.get( import_jsvars.api_base + '/api/get_nonce/?controller=content&method=create_post', {}, function(n) {
					  
					  	jQuery.post(import_jsvars.api_base + '/api/content/create_post/', {
					  		data : apiInfo,
					  		nonce: n.nonce, 
					  		location: location,
					  		author_id : author_id
						}).done(function(r) {

							jQuery.post(import_jsvars.api_addMediaFromURL,{
								url : response.cover.source,
								title : response.name,
								post_id : r.post_id //get back post id
							}).done(function(resp) {
								if (typeof resp.status!=='undefined' && resp.status=='error' && typeof errorCallback === "function")
									errorCallback(resp);
								else if (typeof successCallback === "function")
									successCallback( r );
							}).fail(function(err) {
								// console.error('erreur lors de l\'import de la photo', err);
								if (typeof errorCallback === "function")
									errorCallback( err );
							});

						}).fail(function(err) {
							if (typeof errorCallback === "function")
								errorCallback(err);
						});

					});

		    	//sinon pré-remplissage des champs sur le post
		    	} else if (!import_jsvars.background_import) {
		
					if (window.kidzouEventsModule) {
		    			var startend_time = (typeof response.end_time=='undefined' ? response.start_time : response.end_time);
				    	kidzouEventsModule.model.initDates(
							moment( response.start_time ).startOf("day").format("YYYY-MM-DD HH:mm:ss"),
							moment( startend_time ).endOf("day").format("YYYY-MM-DD HH:mm:ss"), 
							[]); //pas de récurrence
					}

			    	//on est dans l'édition d'un post
			    	if (document.querySelector('input[name="post_title"]')!==null)
		        		document.querySelector('input[name="post_title"]').value  = response.name;
		        
			        //remplacer les CR LF par des <br>
			        if (typeof response.description!='undefined' && window.tinyMCE) {
			        	var content = response.description.replace(/(\r\n|\n|\r)/gm,"<br/>");
			        	//le contenu de Facebook est ajouté à la fin du contenu pré-existant
			        	//il faut donc récupérer le contenu existant 
			        	var previousContent = tinyMCE.activeEditor.getContent({format : 'raw'});

			       		tinyMCE.execCommand('mceSetContent', false, previousContent+content); 
			        }
		        
			        //fixer le contenu dans l'editor
			        if (window.kidzouPlaceModule && typeof response.place!=='undefined' ) {

			        	var _locationName 	= (typeof response.place.name!=='undefined' ? response.place.name : '');
			        	var _address 	= (typeof response.place.location!=='undefined' && typeof response.place.location.street!=='undefined' ? response.place.location.street : '');
			        	var _phone 		= (typeof response.place.location!=='undefined' && typeof response.place.location.phone!=='undefined' ? response.place.location.phone : '');
			        	var _city 		= (typeof response.place.location!=='undefined' && typeof response.place.location.city!=='undefined' ? response.place.location.city : '');
			        	var _latitude 	= (typeof response.place.location!=='undefined' && typeof response.place.location.latitude!=='undefined' ? response.place.location.latitude : '');
			        	var _longitude 	= (typeof response.place.location!=='undefined' && typeof response.place.location.longitude!=='undefined' ? response.place.location.longitude : '');

			        	kidzouPlaceModule.model.proposePlace('facebook', {
			        			name 	: _locationName,
			        			address : _address,
			        			website : value, //website
			        			phone	: _phone, //phone
			        			city 	: _city,
			        			latitude	: _latitude,
			        			longitude 	: _longitude,
			        			opening_hours : '' //opening hours
			        		});
			        }

			        //inserer le cover comme featured image
			        if (typeof response.cover!='undefined') {

			        	jQuery.post(import_jsvars.api_addMediaFromURL,{
							url : response.cover.source,
							title : response.name,
							post_id : document.querySelector('#post_ID').value //c'est un champ caché de la page
						}).done(function(resp) {

							//on est dans un écran type wp-admin/post.php?post=xxx&action=edit
							if (document.querySelector('#postimagediv')!==null && !import_jsvars.background_import) {
								//il existe déjà une image, il faut la remplacer
								if (document.querySelector('#postimagediv img')) {
							  		document.querySelector('#postimagediv img').src = resp.src;
							  	} else {
							  		//sinon, il faut la créer
							  		var node = document.createElement("IMG");    
							  		node.setAttribute('src', resp.src);             
									document.querySelector("#set-post-thumbnail").appendChild(node);  
							  	} 
							}
							if (typeof successCallback === "function")
								successCallback(resp);

						}).fail(function(err) {
							// console.error('erreur lors de l\'import de la photo', err);
							if (typeof errorCallback === "function")
								errorCallback(err);
						});

			        }  else {
			        	//pas d'import de photo (cover)
			        	if (typeof successCallback === "function")
								successCallback(response);
			        }

		    	}

			} else {
				if (typeof errorCallback === "function")
					errorCallback(response);
			}
		
		}); //FB.api
	} 
};

/**
 *
 * Création du champ Input via ReactJS
 */

var ImportForm = React.createClass({
	getInitialState: function() {
		return {
			statusClass: '', 
			statusMessage : '',
			inputClass : '',
			hintStyle : {
				display : 'none'
			},
			content_edit_url : ''
		};
	},
	getToken : function() {
		var self = this;
		var token_url = "https://graph.facebook.com/oauth/access_token?" +
				          "client_id=" + import_jsvars.facebook_appId +
				          "&client_secret=" + import_jsvars.facebook_appSecret +
				          "&grant_type=client_credentials";

        //avant d'appeler l'API facebook, il faut un access token
      	jQuery.ajax({
            url: token_url,
            error: function() {
                console.error('impossible de recuperer un token facebook');
            },
            success: function(data) {
            	var patt = /access_token=(.+)/;
		        var matches = patt.exec(data);
		        self.setState({token: matches[1]});
            }
        });
	},
	handleChange: function(e) {

		var self = this;

		self.setState({
			statusClass: '', 
			statusMessage : '',
			inputClass : '',
			hintStyle : {
				display : 'none'
			},
			content_edit_url : ''
		});

		////////////////////////////////////////////
		applyChange(
			e.target.value, 
			self.state.token, 
			function(response){
				//progress
				self.setState({
					statusClass: 'fa fa-spinner fa-spin', 
					statusMessage : 'Import en cours...',
					inputClass : 'valid',
					hintStyle : {
						display : 'inline'
					},
					content_edit_url : ''
				});
			},
			function(response){

				console.debug('received', response);
				//success
				self.setState({
					statusClass: 'fa fa-check', 
					statusMessage : 'Import terminé',
					inputClass : 'valid',
					hintStyle : {
						display : 'inline',
					},
					content_edit_url : response.post_edit_url 
				});
			}, 
			function(response){
				console.error('received', response);
				//error
				self.setState({
					statusClass: 'fa fa-exclamation-circle', 
					statusMessage: 'L\'import a échoué',
					inputClass : 'invalid',
					hintStyle : {
						display: 'inline',
					},
					content_edit_url : ''
				});
			});	
	},
	render: function() {
		return (
			<div className="kz_form" id="import_form">
			  	<h4>Importer un &eacute;v&eacute;nement Facebook</h4>
			  	<p>
			  		Pour importer un &eacute;v&eacute;nement Facebook, c&apos;est tr&egrave;s simple ! Copiez-collez l&apos;URL de l&apos;&eacute;v&eacute;nement dans le champ ci-dessous.<br/>
			  		Cette URL <strong>doit ressembler à cela</strong> : <em>https://www.facebook.com/events/1728989597320835/</em>
			  	</p>
			  	<ul>
			  		<li>
			  		<label htmlFor="facebook_url">Copiez-collez l&apos;URL ici :</label>
			  		<input 
			  			type="text" 
			  			name="facebook_url"
			  			placeholder="Ex : https://www.facebook.com/events/1028586230505678/"
			  			onChange={this.handleChange}
			  			onFocus={this.getToken}
			  			className={this.state.inputClass} /> 
			  		<span className="form_hint" style={this.state.hintStyle}>
			  			<i className={this.state.statusClass}></i>{this.state.statusMessage}
			  		</span>
			  		</li>
			  	</ul>
			  	{ this.state.content_edit_url &&
			       <p><a href={this.state.content_edit_url} target="_blank">Vous pouvez continuer l&apos;&eacute;dition du contenu import&eacute; ici </a></p>
			    }
			</div>
		);
	}
});

React.render(
    <ImportForm />,
    document.querySelector(import_jsvars.import_form_parent)
);

//initialisation des scripts facebook pour import d'événement par le Graph
window.fbAsyncInit = function() {
	FB.init({
	  appId      : import_jsvars.facebook_appId,
	  xfbml      : true,
	  version    : 'v2.4'
	});
};

(function(d, s, id){
	var js, fjs = d.getElementsByTagName(s)[0];
	if (d.getElementById(id)) {return;}
	js = d.createElement(s); js.id = id;
	js.src = '//connect.facebook.net/en_US/sdk.js';
	fjs.parentNode.insertBefore(js, fjs);
}(document, 'script', 'facebook-jssdk'));




