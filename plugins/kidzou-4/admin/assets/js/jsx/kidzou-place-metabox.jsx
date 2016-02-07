
var kidzouPlaceModule = (function() { //havre de paix

  function Place(_venue, _address, _website, _phone_number, _city, _lat, _lng, _opening_hours) {
      
    this.venue      = _venue;
    this.address    = _address;
    this.website    = _website; 
    this.phone_number   = _phone_number; 
    this.city       = _city; //quartier ou ville, rapidement identifiable par l'internaute
    this.lat      = _lat;
    this.lng      = _lng;
    this.opening_hours  = _opening_hours;

    this.isEmpty = function() {
      return (  (this.venue=='' || typeof this.venue=='undefined') && 
                (this.address == '' || typeof this.address=='undefined') && 
                (this.website == '' || typeof this.website=='undefined') && 
                (this.phone_number == '' || typeof this.phone_number=='undefined')  && 
                (this.city == '' || typeof this.city=='undefined') && 
                (this.lat == '' || typeof this.lat=='undefined') && 
                (this.lng==''  || typeof this.lng=='undefined') );
    };

    this.isValid = function() {
      return ( this.venue!==''  && this.address !== '' && this.city !== '' );
    };

    //fonction de comparaison de places
    this.equals = function(__venue, __address, __website, __phone_number, __city, __lat, __lng, __opening_hours) {

      //redressement du telephone
      var phoneEquals = (__phone_number==this.phone_number);
      if (!phoneEquals) {
        if (typeof _phone_number!=='undefined') {
          if (typeof this.phone_number!=='undefined')
            phoneEquals = ( this.phone_number.replace(/\s/gi, "") == __phone_number.replace(/\s/gi, "") );
        }
      }

      var eq = (  this.venue == __venue && 
            this.address == __address &&
            this.website == __website &&
            phoneEquals &&
            this.city == __city &&
            this.lat == __lat &&
            this.lng == __lng //&&
          );
      return eq;
    };
  }

  var kidzouPlaceEditor = function() { 

    var editorModel = new PlaceEditorModel();

    //sur l'écran d'édition d'un post, ce champ est toujours rempli
    var postID = (document.querySelector('#post_ID')!==null ? document.querySelector('#post_ID').value : '');

    function PlaceEditorModel() {

      var self = this;

      self.placeData      = new Place();

      /**
       * Mise à jour de la Metabox Taxonomy 'ville' avec la valeur de la ville 
       *
       */
      self.updateVilleTaxonomy = function(post_id, ville, progressCallback, successCallback, errorCallback) {

        //mise à jour de la ville
        jQuery.get(place_jsvars.api_base + '/api/taxonomy/getTermBy/', {
          field : 'name',
          taxonomy : 'ville',
          value : ville
        }, function (n) {

          var term_id = n.term.term_id;
          var node = document.querySelector( '#in-ville-' + term_id );
        
          if(node!==null ) {
            node.setAttribute('checked','checked');

            jQuery.get(place_jsvars.api_base + '/api/get_nonce/?controller=taxonomy&method=setPostTerms', {}, function (n) {

              jQuery.post(place_jsvars.api_set_post_terms + '?nonce=' + n.nonce, {
                post_id : postID,
                taxonomy : 'ville',
                terms : [term_id]
              }).done(function (r) {
                if (r.status=='ok' && typeof r.result!=='undefined' && r.result!==null && (typeof r.result.errors!=='undefined' || r.result=='false') && typeof errorCallback === "function")
                  errorCallback(r);

                else if (typeof successCallback === "function")
                  successCallback(r);
              }).fail(function (err) {
                if (typeof errorCallback === "function")
                    errorCallback(err);
              });

            });
          }

        });
      }

      //Resultats en provenance de Google PlaceComplete
      //https://developers.google.com/places/documentation/details
      self.completePlace = function(result, progress, success, error) {

        self.placeData = new Place(
            result.name, 
            result.address, 
            result.website, 
            result.phone_number, 
            result.city, //city 
            result.latitude, //latitude
            result.longitude, //longitude
            result.opening_hours
          ); 

        //check de la ville
        if (typeof result.city!=='undefined' && result.city!=='')
          self.updateVilleTaxonomy(postID, result.city, 
              function(){if (typeof progress==='function') progress({msg:'Enregistrement...'});}, 
              function(){if (typeof success==='function') success({msg:'Ville mise a jour'});}, 
              function(){if (typeof error==='function') error({msg:'Erreur de mise a jour de la Taxonomie \'ville\''});}
            );

        if (window.kidzouAdminGeo && postID!=='') {

          if (typeof progress==='function') progress({msg:'Détermination de la métropole'});

          kidzouAdminGeo.getMetropole(
            result.latitude, 
            result.longitude, 
            function(metropole) {
              //check de la metropole
              self.updateVilleTaxonomy(postID, metropole, 
                  function(){if (typeof progress==='function') progress({msg:'Enregistrement...'});}, 
                  function(){if (typeof success==='function') success({msg:'Metropole mise a jour'});}, 
                  function(){if (typeof error==='function') error({msg:'Erreur de mise a jour de la Metropole'});}
                );
            }); 
        }


        if (self.placeData.isValid()){
          //sauvegarder les data
          self.savePlace(progress, success, error);
        } else {
          if (typeof error === "function")
              error({msg: 'Continuez a remplir les champs, la sauvegarde sera automatique lorsque Nom, adresse et ville seront remplis'});
        }      
      };


      self.savePlace = function(progressCallback, successCallback, errorCallback) {
        if (postID=='') {
          if (typeof errorCallback === "function")
              errorCallback();
          return;
        }

        if (typeof progressCallback === "function")
          progressCallback();

        jQuery.get(place_jsvars.api_base + '/api/get_nonce/?controller=content&method=place', {}, function (n) {

            jQuery.post(place_jsvars.api_save_place + '?nonce=' + n.nonce, {
              contact : {
                tel : self.placeData.phone_number,
                web : self.placeData.website
              },
              location : {
                name : self.placeData.venue,
                address : self.placeData.address ,
                city : self.placeData.city,
                lat : self.placeData.lat,
                lng : self.placeData.lng,
                country : 'FR'
              },
              post_id: postID
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

      };
    } //PlaceEditorModel

    return { 
      model     : editorModel, //PlaceEditorModel
    };

  }();  //kidzouPlaceEditor

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  /////////////// React UI

  var PlaceEditor = React.createClass({

    getInitialState: function() {
      return {
        manualMode : false, //saisie manuelle d'une adresse c'est à dire sans Google PlacesService
        place : {
          name     : place_jsvars.location_name, 
          address  : place_jsvars.location_address, 
          website  : place_jsvars.location_website, 
          phone_number  : place_jsvars.location_phone_number, 
          city          : place_jsvars.location_city,
          latitude      : place_jsvars.location_latitude, //latitude
          longitude     : place_jsvars.location_longitude, //longitude
          opening_hours : []
        },
        resultsStyle: {display : 'none'},
        placesProposals : [],
        hint : {
          select : {
            valid : false,
            show : false,
            icon : '',
            message : ''
          }
        }
      };
    },
    componentWillMount: function() {
      var self = this;
      //remplissage automatique si aucun lieu n'est renseigné, sinon enrichissement des propositiosn
      proposePlace = (type, _place) => {
          var maPlace = new Place(this.state.place.name, this.state.place.address, this.state.place.website, this.state.place.phone_number, this.state.place.city, this.state.place.latitude, this.state.place.longitude, this.state.place.opening_hours);
          // console.debug('proposePlace', maPlace, maPlace.isEmpty());
          if (maPlace.isEmpty()){
            self.setState({
              place : _place
            })

          } else if (!maPlace.equals(_place.name, _place.address, _place.website, _place.phone_number, _place.city, _place.latitude, _place.longitude, _place.opening_hours) ) {

            var proposals = this.state.placesProposals;
            proposals.push(_place);
            self.setState({
              placesProposals : proposals
            }); 
          }      
      };

      //choix explicite d'une place depuis l'exterieur
      changePlace = (_place, _index) => {
        var proposals = this.state.placesProposals;
        proposals.splice(_index, 1);
        self.setState({
          place : _place,
          placesProposals : proposals
        }, function(){
          self.savePlace();
          self._suggest.clear();
        });
      };
      
    },
    
    /**
     * Render the example app
     */
    render: function() {

      var types       = ['establishment'];
      var displayForm = (this.state.place.name!=='' || this.state.place.address!=='' || this.state.place.city!=='' || this.state.place.latitude!=='' || this.state.place.longitude!=='' || this.state.place.website!=='' || this.state.place.phone_number!=='');
      var pointer     = {cursor:'pointer'};

      return (
        <div>
          <p>Commencez &agrave; taper quelques lettres et des propositions apparaitront. Les propositions sont issues de <strong>Google Maps</strong></p>
          <div>
            <Geosuggest
              className="kz_form"
              placeholder="Nom d'un lieu, d'une ville..."
              onSuggestSelect={this.onSuggestSelect}
              types={types}
              autoActivateFirstSuggest='true'
              onFocus={this.onFocus} 
              ref={(c) => this._suggest = c}  />
              <HintMessage ref={(c) => this._hintMessage = c} />
          </div>


            { (!this.state.manualMode && !displayForm) &&
              <p><a onClick={this.onManualMode} style={{cursor:'pointer'}} className="button">Saisir une adresse manuellement</a></p>
            }

            { (this.state.manualMode || displayForm) &&
              <div>
                <p>Vous pouvez modifier l&apos;adresse a tout moment, pour cela <strong>cliquez sur le champ &agrave; modifier</strong>. Par exemple, pour corriger le num&eacute;ro de t&eacute;l&eacute;phone, survolez le champ t&eacute;l&eacute;phone et cliquez dessus</p>
                <ul>
                  <Field tabIndex={0} inputPrefix="kz_location_" change={this.onEdit} label="Nom du lieu:"  text={this.state.place.name}    updateParam="name" />
                  <Field tabIndex={1} inputPrefix="kz_location_" change={this.onEdit} label="Adresse:"      text={this.state.place.address} updateParam="address" />
                  <Field tabIndex={2} inputPrefix="kz_location_" change={this.onEdit} label="Quartier / Ville:" text={this.state.place.city} updateParam="city" />
                  <Field tabIndex={3} inputPrefix="kz_location_" validate={this.validateFloat} change={this.onEdit} label="Latitude:" text={this.state.place.latitude} updateParam="latitude" />
                  <Field tabIndex={4} inputPrefix="kz_location_" validate={this.validateFloat} change={this.onEdit} label="Longitude:" text={this.state.place.longitude} updateParam="longitude" />
                  <Field tabIndex={5} inputPrefix="kz_location_" validate={this.validateURL} change={this.onEdit} label="Site Web:" text={this.state.place.website} updateParam="website" />
                  <Field tabIndex={6} inputPrefix="kz_location_" validate={this.validatePhone} change={this.onEdit} label="T&eacute;l&eacute;phone:" text={this.state.place.phone_number} updateParam="phone_number" />
                </ul>
              </div>
            }

            { displayForm &&
              <p>
                <a onClick={this.onResetForm} style={{cursor:'pointer'}} className="button"><i className="fa fa-eraser"></i>&nbsp;Remettre &agrave; zero</a>
                <a onClick={this.onUseForCustomer} style={{cursor:'pointer', marginLeft:'0.4em'}} className="button"><i className="fa fa-mail-forward"></i>&nbsp;Utiliser cette adresse pour le client</a>
              </p>
            }
            
            <PlacesProposals proposals={this.state.placesProposals} />
            <div id="place_results" style={this.state.resultsStyle}></div>
        </div>
      )
    },

    /**
     * validation of input field as float value
     */
    validateFloat: function(value) {
      var patt = /^[+-]?((\.\d+)|(\d+(\.\d+)?))$/;
      var matches = patt.exec(value);
      return matches!=null;
    },

    /**
     * validation of input field as URL
     */
    validateURL: function(value) {
      var patt = /^(https?:\/\/)?((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|((\d{1,3}\.){3}\d{1,3}))(\:\d+)?(\/[-a-z\d%_.~+]*)*(\?[;&a-z\d%_.~+=-]*)?(\#[-a-z\d_]*)?$/i;
      var matches = patt.exec(value);
      return matches!=null;
    },

    /**
     * validation of input field as Phone format
     */
    validatePhone: function(value) {
      var patt = /^(?:(?:\(?(?:00|\+)([1-4]\d\d|[1-9]\d?)\)?)?[\-\.\ \\\/]?)?((?:\(?\d{1,}\)?[\-\.\ \\\/]?){0,})(?:[\-\.\ \\\/]?(?:#|ext\.?|extension|x)[\-\.\ \\\/]?(\d+))?$/;
      var matches = patt.exec(value);
      return matches!=null;
    },


    /**
     * When user clicks on the input field
     * @param  {Object} suggest The suggest
     */
    onFocus: function() {
      var self = this;
      // console.debug('state', self._suggest);
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
            place: {
              name     : placeResult.name, 
              address  : placeResult.formatted_address, 
              website  : placeResult.website, 
              phone_number  : placeResult.formatted_phone_number, 
              city          : city,
              latitude      : placeResult.geometry.location.lat(), //latitude
              longitude     : placeResult.geometry.location.lng(), //longitude
              opening_hours : (placeResult.opening_hours ? placeResult.opening_hours.periods : [])
            }
          });

          //save address
          self.savePlace();

        }
      });
    },

    /**
     * Sauvegarde de l'adresse et messages de confirmation
     *
     */
    savePlace: function() {
      var self = this;
      kidzouPlaceEditor.model.completePlace(self.state.place, 
        function(data){
          var msg = (data ? data.msg || 'Enregistrement' : 'Enregistrement');
          self._hintMessage.onProgress(msg);
        }, //progress
        function(data){
          var msg = (data ? data.msg || 'Enregistré' : 'Enregistré');
          self._hintMessage.onSuccess(msg);
        }, //success
        function(err){
          var msg  = (err ? err.msg || 'Impossible d\'enregistrer' : 'Impossible d\'enregistrer');
          self._hintMessage.onError(msg);
        }  //error
      );
    },

    /**
     * When user clicks on Manual mode
     */
    onManualMode: function() {
      this.setState({manualMode:true});
    },

    /**
     * When user resets the form
     */
    onResetForm: function() {
      this.setState({
        place : {
          name     : '', 
          address  : '', 
          website  : '', 
          phone_number  : '', 
          city          : '',
          latitude      : '', //latitude
          longitude     : '', //longitude
          opening_hours : []
        },
        manualMode:true
      });
      this._suggest.clear();
    },

    /**
     * To use this adress for the customer
     */
    onUseForCustomer: function() {
      if (window.kidzouCustomerModule) {
        kidzouCustomerModule.setPlace(this.state.place);
      }
    },

    /**
     * When user edits inline
     * @param  {Object} edited data
     */
    onEdit: function(data, progress, success, error) {
      // console.debug('onEdit', data, this.state);

      var self = this;
      var keys = Object.keys(data);
      var _place = self.state.place;
      _place[keys[0]] = data[keys[0]];
      self.setState(_place);

      kidzouPlaceEditor.model.completePlace(_place, 
        function(msg){
          if (typeof progress==='function') progress(msg);
        },
        function(data) {
          if (typeof success==='function') success(data);
        },
        function(err) {
          if (typeof error==='function') error(err);
        }
      );
    }, 

  });

  var PlacesProposals = React.createClass({
    render: function() {
      var rows = [];
      this.props.proposals.forEach(function(proposal, index) {
        // console.debug('proposal', proposal);
        var key = 'proposal_' + index;
        rows.push(<PlaceProposal place={proposal} index={index} key={key} />);
      });
      // var isProposal = (this.props.proposals.length>0);
      return (
        <div>
          { this.props.proposals.length>0 &&
            <div>
              <h4>Lieux propos&eacute;s : </h4>
              <p>{rows}</p>
            </div>
          }
        </div>
      );
    }
  });

  var PlaceProposal = React.createClass({
    render: function() {
      return (
        <span className="proposal">
          <a onClick={this.changePlace} style={{cursor:'pointer'}}><i className="fa fa-bookmark-o"></i>&nbsp;{this.props.place.name}<br/>{this.props.place.address}</a>
        </span>
      );
    },
    changePlace: function(){
      changePlace(this.props.place, this.props.index);
    }
  });

  ReactDOM.render(
    <PlaceEditor />, 
    document.querySelector('#kz_place_metabox .react-content')
  );

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////////////////////////////

  //global vars accessible de l'extérieur
  var proposePlace;
  var changePlace;
  // var removeProposal;

  return {
    model : kidzouPlaceEditor.model, //necessaire de fournir un acces pour interaction avec Google Maps ??
    proposePlace : proposePlace //propositions de places depuis l'exterieur, mis à jour lors de componentWillMount 
  };

}());  //kidzouPlaceModule

