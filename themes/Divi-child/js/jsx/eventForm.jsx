
/**
 *
 * Formulaire de saisie d'événement
 */

var EventForm = React.createClass({
  getInitialState: function() {
    return {
      importButtonState : '',
      submitButtonState : '',
      submitButtonLabel : 'J\'ai temriné, pre-visualiser mon événement',
      postCreated : false,
      facebookUrl : '',
      uploadedMedia : [],
      adresse : {
        suggested : false,
        imported : false,
        country : 'FR'
      },
      contact : {},
      urlMedia : [],
      tooBigMedia : []
    };
  },

  changeFacebookUrl : function(event) {
    this.setState({facebookUrl: event.target.value});
  },

  importFacebook: function(e) { 

    //block refresh of page
    e.preventDefault();
  
    var self = this;
    var value = self.state.facebookUrl;

    var fetchData = function(token, progressCallback, successCallback, errorCallback) {

      var patt = /https?:\/\/www.facebook.com\/events\/([0-9]*)\/?/i;
      var matches = patt.exec(value);

      if (matches!=null) {

          progressCallback();

          FB.api("/" + matches[1] + '?access_token=' + token + '&fields=cover,name,description,place,end_time,start_time', function (response) {

            if (response && !response.error && typeof response.start_time!='undefined') {

                var startend_time = (typeof response.end_time=='undefined' ? response.start_time : response.end_time);
                var place = response.place || {};
                var location = place.location || {};
                var country_long = location.country || 'france'; //si aucun pays spécifié on considère que c'est en france
       
                if (country_long.toLowerCase()!='france' && country_long.toLowerCase()!='belgium') {
                  errorCallback(response);
                } else {

                  var street = (location.street || location.city || place.name);
                  self.setState({
                    // title :   response.name,
                    urlMedia : [response.cover.source],
                    adresse : {
                      name : place.name,
                      street : street,
                      city : location.city,
                      zip : location.zip,
                      lat : location.latitude,
                      lng : location.longitude,
                      imported : true ,
                      country : 'FR'
                    },
                  });

                  //mise à jour des composants
                  self.title.setValue(response.name);
                  self.otherDesc.setValue(response.description);
   
                  self.dates.setState({
                    from : new Date(response.start_time.split('T')[0] + ' 00:00:00'), //(() ,
                    to : new Date(startend_time.split('T')[0] + ' 23:59:59')//(()
                  });

                  successCallback();
                }

            } else {
              errorCallback(response);
            }
        
          }); //FB.api
      } else {
         errorCallback({msg:'cela ne correspond pas à l\'URL d\'un événement Facebook'});
      }
    };

    var token_url = "https://graph.facebook.com/oauth/access_token?" +
                      "client_id=" + import_jsvars.facebook_appId +
                      "&client_secret=" + import_jsvars.facebook_appSecret +
                      "&grant_type=client_credentials";

        //avant d'appeler l'API facebook, il faut un access token
    jQuery.ajax({
      url: token_url,
      error: function() {
        self.setState({importButtonState: 'error'});
        self._importHint.onError('Oops...cette URL n\'a rien retourne !');
      },
      success: function(data) {
        var patt = /access_token=(.+)/;
        var matches = patt.exec(data);

        fetchData(
            matches[1], 
            function(response){
              //progress
              self.setState({importButtonState: 'loading'});
              self._importHint.onProgress('Nous appelons facebook pour remplir l\'evenement...');
            },
            function(response){
              //success
              self.setState({importButtonState: 'success'});
              self._importHint.onSuccess('Nous avons rempli le formulaire avec les donnees de Facebook, il vous reste a verifier et valider !');
            }, 
            function(response){
              //error
              self.setState({importButtonState: 'error'});
              self._importHint.onError('Oops...apparemment il y a un souci Houston. Votre evenement est-il public ? se passe-t-il en france ou en belgique ?');
            }
        ); 
      },
      //
    });
  },

  onFormSubmit: function(e) {

    e.preventDefault();
    var self = this;

    self.setState({submitButtonState: 'loading'});

    var checkForm = function(successCallback, errorCallback) {

      var isError = false;

      if (!self.title.validate()) {
        isError = true;
      }

      var desc = self.publicDesc.state.value.trim() + self.shortDesc.state.value.trim() + self.datesDesc.state.value.trim() + self.tarifsDesc.state.value.trim() + self.resaDesc.state.value.trim() + self.otherDesc.state.value.trim();
      if (desc.trim()=='') {
        self._descHint.onError('Au moins un des champs ci-dessous doit etre rempli !');
        isError = true;
      }
      
      //en cas d'import facebook our de suggestion par GeoSuggest on ne vérifie pas l'adresse
      if (!self.state.adresse.suggested && !self.state.adresse.imported) {
        if (!self._suggest || self._suggest.state.userInput=='' || self.adresseStreet.state.value.trim()=='') {
          self._placeHint.onError('Merci d\'indiquer ou cela se passe');
          isError = true;
        }
      }
      
      //dates
      if (self.dates.state.from==null || self.dates.state.to==null) {
        self._datesHint.onError('Merci d\'indiquer la date de debut et de fin');
        isError = true;
      }

      if (self.state.uploadedMedia.length==0 && self.state.urlMedia.length==0) {
        self._mediaHint.onError('Au moins une image est necessaire');
        isError = true;
      }

      //uploadedMedia
      if (isError) 
        errorCallback();
      else
        successCallback();
    };

    var sendData = function() {

      jQuery.ajax({
        url: import_jsvars.api_create_post_nonce,
        error: function() {
          console.log('get_nonce, error');
        },
        success: function(data) {

          if ( !data.nonce ) {
            console.log('get_nonce, error');
          } else {

            var formattedDesc = ('<ul><li><strong>Public :</strong>' + self.publicDesc.state.value + ' </li><li><strong>Description :</strong>' + self.shortDesc.state.value + '</li><li><strong>Date :</strong>' + self.datesDesc.state.value + '</li><li><strong>Tarifs :</strong>' + self.tarifsDesc.state.value + '</li><li><strong>R&eacute;servation :</strong>' + self.resaDesc.state.value + '</li></ul><p><strong>Le mot de l&apos;organisateur :</strong><br/>' + self.otherDesc.state.value + '</p>');

            //si l'adresse n'est pas reprise de google suggest mais reprise manuellement
            //on met en cohérence l'objet adresse envoyé au server
            if (!self.state.adresse.suggested && !self.state.adresse.imported) {
              self.setState({
                adresse : {
                  country: 'FR',
                  name    : self._suggest.state.userInput, 
                  street  : self.adresseStreet.state.value
                }
              });
            }

            var sendit = { 
              data : {
                titre : self.title.state.value,
                description : formattedDesc,
                contact : {},
                adresse : self.state.adresse,
                dates : {
                  start_date : moment(self.dates.state.from).format('YYYY-MM-DD HH:mm:ss'),
                  end_date : moment(self.dates.state.to).format('YYYY-MM-DD HH:mm:ss')
                },
                urlMedia : self.state.urlMedia,
                uploadedMedia : self.state.uploadedMedia
              }
            };

            jQuery.post( import_jsvars.api_create_post + '?nonce=' + data.nonce + '&key=' + import_jsvars.api_key, sendit)
              .done(function(resp) {
                if (!resp.status || resp.status=='error') {
                  console.log('error', resp);
                  self.setState({submitButtonState: 'error'});
                  self.submitMessage.onError('Une erreur s\'est produite !');
                } 

                //ce marker va bloquer la revalidation du form
                self.setState({submitButtonState: 'success'});
                self.submitMessage.onProgress('Votre evenement a ete cree, vous allez etre redirige vers sa pre-visualisation');
                self.setState({postCreated:true,}); 
                // self.submitButton.disable();
                //rediriger vers un preview du post
                window.location = resp.post_preview_url;
              })
              .fail(function(e) {
                console.log('fail',e);
                self.setState({submitButtonState: 'error'});
                self.submitMessage.onError('Une erreur s\'est produite !');
              });
          }
        },
        //
      });

    };

    if (!this.state.postCreated) {
      checkForm(
        function(){
        //on success
        // console.debug('checkForm success');
        sendData();

      }, function(){
        //on failure
        // console.debug('checkForm failure');
      });
    }
    
  },

  /**
   * When user clicks on the input field
   * @param  {Object} suggest The suggest
   */
  onSuggestFocus: function() {
    var self = this;
    self._suggest.clear();
  },

  /**
   * When a suggest got selected
   * @param  {Object} suggest The suggest
   */
  onSuggestSelect: function(suggest) {
    // console.log(suggest);
    var self = this;
    
    //recuperer google maps et demander les details
    var service = new google.maps.places.PlacesService(document.querySelector('#place_results'));
    var request = {
      placeId: suggest.placeId
    };
    service.getDetails(request, function(placeResult, status){

      if (status == google.maps.places.PlacesServiceStatus.OK) {

        var city = placeResult.display_text;
        //tentative de retrouver la ville de manière plus précise
        //voir https://developers.google.com/maps/documentation/geocoding/?hl=FR#Types
        placeResult.address_components.forEach(function(entry) {
            if (entry.types[0]=='locality') {
              city = entry.long_name;
            }
        });

        self.setState({
          adresse: {
            name    : placeResult.name, 
            street  : placeResult.formatted_address, 
            city          : city,
            zip           : '',
            lat           : placeResult.geometry.location.lat(), //latitude
            lng           : placeResult.geometry.location.lng(), //longitude
            opening_hours : (placeResult.opening_hours ? placeResult.opening_hours.periods : []),
            suggested : true ,
            country : 'FR'
          },
          contact : {
            website  : (placeResult.website || ''), 
            phone_number  : (placeResult.formatted_phone_number || ''), 
          },
        });

        //mise à jour pour cohérence
        //self.adresseStreet.setValue(placeResult.formatted_address);

      }
    });
  },

  /**
   * When a wants to change the place selected
   */
  onResetPlace: function() {
    var self = this;
    self.setState({adresse:{}});
  },

  /**
   * Upload des media [image, audio, pdf] et stockage sous forme Base64
   */
  onDrop: function (files) {
    var self = this;
    var media = self.state.uploadedMedia;
    var errors = self.state.tooBigMedia;
    files.map(function(file, index){
      console.log('file size is ', file.size/1024/1024);
      if ((file.size/1024/1024) < 2) {
        var reader = new FileReader();
        reader.onload = function(){
          media.push({
              name : file.name,
              data : reader.result
          });
          self.setState({
            uploadedMedia: media
          });
        };
        reader.readAsDataURL(file);
      } else {
        errors.push(file);
        self.setState({
          tooBigMedia: errors
        });
      }
    });
  },

  /**
   * Suppression d'un media uploadé
   */
  removeMedia : function(index) {
    var self = this;
    var media = self.state.uploadedMedia;
    var removed = media.splice(index, 1);
    self.setState({
      uploadedMedia : media
    });
  },
  
  render: function() {

    var self = this;

    //le browser supporte nos scripts
    var supportFileUpload = window.File && window.FileReader && window.FileList && window.Blob;

    //les fichiers téléchargés
    var filesList = self.state.uploadedMedia.map(function(file, i){
      if (file.data.startsWith('data:image')) {
        return <div className="previewBlock" key={'div'+i}><img src={file.data}  /><div>{file.name}</div><a style={{cursor:'pointer'}} onClick={self.removeMedia.bind(self, i)}>Supprimer</a></div>
      } else if (file.data.startsWith('data:audio')) {
        return <div className="noPreview" key={'div'+i}><i className="fa fa-volume-up fa-5x" ></i><div>{file.name}</div><a style={{cursor:'pointer'}} onClick={self.removeMedia.bind(self, i)}>Supprimer</a></div>
      } else if (file.data.startsWith('data:application/pdf')) {
        return <div className="noPreview" key={'div'+i}><i className="fa fa-file-pdf-o fa-5x" ></i><div>{file.name}</div><a style={{cursor:'pointer'}} onClick={self.removeMedia.bind(self, i)}>Supprimer</a></div>
      } else {
        return <div className="incorrectType" key={'div'+i}><i className="fa fa-exclamation fa-5x" ></i><div>{file.name}</div><br />Ce format n&apos;est pas support&eacute;, il ne sera pas import&eacute;</div>
      }
    });

    var uploadErrors = self.state.tooBigMedia.map(function(file, i){
      return <div className="incorrectType" key={'div'+i}><i className="fa fa-exclamation fa-5x" ></i><div>{file.name}</div><br />Ce fichier est trop volumineux (maximum : 2 MB)</div>
    });    

    filesList = filesList.concat(uploadErrors);

    var suggestTypes = ['establishment'];

    return (
      <form className="kz_form">
        <div className="et_pb_promo et_pb_bg_layout_light et_pb_text_align_left" style={{ backgroundColor: "#94e0fc" }}>
          <div className="et_pb_promo_description">
            <h2>Pas de stress !</h2>
            <p>Cr&eacute;ez votre &eacute;v&eacute;nement en 1 click &agrave; partir de Facebook !</p>
            <div className="kz_form_field">
              <div className="inline">
                <i className="fa fa-3x fa-facebook-official"></i><label htmlFor="facebook_url">Page Facebook de l&apos;&eacute;v&eacute;nement : </label>
                <input 
                    type="text" 
                    name="facebook_url"
                    placeholder="https://www.facebook.com/events/1028586230505678/"
                    onChange={this.changeFacebookUrl}
                    value={this.state.facebookUrl} />

                <ProgressButton onClick={this.importFacebook} state={this.state.importButtonState}>
                  <i className="fa fa-magic"></i>Remplir
                </ProgressButton>
              </div>
            </div>
          </div>
        </div>

        <HintMessage ref={(c) => this._importHint = c} />

        <TextField 
          name="event_title"
          label="Nom de l&apos;&eacute;v&eacute;nement"
          placeholder="Nom de votre &eacute;v&eacute;nement" 
          mandatory={true}
          ref={(c) => this.title = c} />

        <div className="kz_form_field_group">

          <h2>Lieu de l&apos;&eacute;v&eacute;nement : </h2>
          <p><i className="fa fa-asterisk mandatory"></i>ce champ est obligatoire</p>
          <HintMessage ref={(c) => this._placeHint = c} />
          <div id="place_results" style={{display:'none'}}></div>
          
          { this.state.adresse.name && this.state.adresse.street &&
            <div onClick={this.onResetPlace} className="kz_box">
              <div style={{cursor:'pointer'}}>
                <div style={{width:'auto', verticalAlign:'middle', float:'left', padding:'10px'}}><i className="fa fa-check fa-2x"></i></div>
                <div style={{width:'auto', verticalAlign:'middle', padding:'10px', backgroundColor:'#94e0fc'}}>
                  <span style={{fontSize:'1.2em'}}>{this.state.adresse.name}<br/></span>
                  <span style={{fontSize:'1em'}}>{this.state.adresse.street} {this.state.adresse.zip} {this.state.adresse.city}</span>
                </div>
              </div>
            </div>
          }
          {
            (!this.state.adresse.name || !this.state.adresse.street) &&
            <div>
              <div className="kz_form_field">
                <label htmlFor="event_venue">Nom du lieu : </label>
                <Geosuggest
                  placeholder="Ex : Musée de plein air"
                  onSuggestSelect={this.onSuggestSelect}
                  types={suggestTypes}
                  autoActivateFirstSuggest='true'
                  onFocus={this.onSuggestFocus} 
                  ref={(c) => this._suggest = c}  />
              </div>

              <TextField 
                name="event_address"
                label="Adresse"
                placeholder="Ex : 135 avenue de Bretagne 59000 Lille" 
                ref={(c) => this.adresseStreet = c} />

            </div>
          }
         
        </div>

        <div className="kz_form_field_group">
          <h2>Description : </h2>
          <p><i className="fa fa-asterisk mandatory"></i>Au moins l&apos;un des champs ci-dessous est obligatoire</p>
          <HintMessage ref={(c) => this._descHint = c} />
          
          <TextField 
              name="event_publicDesc"
              label="Public"
              placeholder="Ex : A partir de x ans" 
              ref={(c) => this.publicDesc = c} />

          <TextField 
              name="event_shortDesc"
              label="Description courte"
              placeholder="Ex : Atelier cuisine Parents / enfants" 
              ref={(c) => this.shortDesc = c} />

          <TextField 
              name="event_dates"
              label="Dates et heures"
              placeholder="Ex : La samedi de 10h à 18h et le dimanche de 10h à 12h" 
              ref={(c) => this.datesDesc = c} />

          <TextField 
              name="event_tarifs"
              label="Tarif"
              placeholder="Ex : Tarif plein / tarif réduit" 
              ref={(c) => this.tarifsDesc = c} />

          <TextField 
              name="event_resa"
              label="R&eacute;servation"
              placeholder="Ex : Contact mail , t&eacute;l&eacute;phone ou site web" 
              ref={(c) => this.resaDesc = c} />

          <TextField 
              type="textarea"
              name="event_autre"
              label="Le mot de l&apos;organisateur"
              placeholder="Tous les autres d&eacute;tails concernant votre &eacute;v&eacute;nement" 
              ref={(c) => this.otherDesc = c} />
                     
        </div>

        <div className="kz_form_field_group">
          <h2>Dates : </h2>
          <p><i className="fa fa-asterisk mandatory"></i>ce champ est obligatoire</p>
          <HintMessage ref={(c) => this._datesHint = c} />
          <EventDates ref={(c) => this.dates = c} />        
        </div>

        <div className="kz_form_field_group">
          <h2>Media : </h2>
          <p><i className="fa fa-asterisk mandatory"></i>Au moins une image est requise</p>
          <HintMessage ref={(c) => this._mediaHint = c} />

          <div className="mediaPreview">
            {
              supportFileUpload &&
              <div style={{display:'inline-block', verticalAlign:'top'}}>
                <Dropzone ref="dropzone" onDrop={this.onDrop} className="mediaDropZone">
                  <i className="fa fa-3x fa-plus-circle" style={{color:'#1ea4e8'}}></i>
                  Glissez-D&eacute;posez des fichiers (Images, audio, video) ou cliquez pour ouvrir votre explorateur de fichiers.
                </Dropzone>
              </div>
            }
            {
              !supportFileUpload &&
              <div>
                <span>Votre navigateur n&apos;pas suffisamment r&eacute;cent pour supporter notre application de t&eacute;l&eacute;chargement d&apos;images</span>
              </div>
            } 
            {
              this.state.urlMedia.length>0 &&  this.state.urlMedia[0] !=='' &&
              <div className="previewBlock">
                <img src={this.state.urlMedia[0]} />
              </div>
            }
            {filesList}
          </div>

          
        </div>

        <div className="et_pb_promo et_pb_bg_layout_light et_pb_text_align_center">
          <HintMessage ref={(c) => this.submitMessage = c} />
          {
            !this.state.postCreated && 
            <ProgressButton type='submit' 
                          durationSuccess={2000} 
                          onClick={this.onFormSubmit} 
                          state={this.state.submitButtonState}
                          ref={(c) => this.submitButton = c} >
              <i className="fa fa-send"></i><span>{this.state.submitButtonLabel}</span>
            </ProgressButton>
          }
        </div>

      </form>
    );
  }

});




