
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

  function updateVilleTaxonomy(post_id, value) {

    if(value!==null && "undefined"!==value && document.querySelector('#kz_event_metropole_' + value.toLowerCase() )!==null ) {
      document.querySelector('#kz_event_metropole_' + value.toLowerCase() ).setAttribute('checked','checked');

      //todo : save in ajax
    }
  }

  var kidzouPlaceEditor = function() { 

    var editorModel = new PlaceEditorModel();

    //sur l'écran d'édition d'un post, ce champ est toujours rempli
    var postID = (document.querySelector('#post_ID')!==null ? document.querySelector('#post_ID').value : '');

    function PlaceEditorModel() {

      var self = this;

      self.placeData      = new Place();

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

        if (window.kidzouAdminGeo && document.querySelector('#post_ID')!==null)
          kidzouAdminGeo.getMetropole(
            result.latitude, 
            result.longitude, function(metropole) {
                          updateVilleTaxonomy(document.querySelector('#post_ID').value,metropole);
                        }); 


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

            jQuery.post(place_jsvars.api_save_place, {
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
              nonce: n.nonce,
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
        resultsStyle: {
          display : 'none'
        },
        hintClasses :{
          name : 'form_hint',
        },
        hintStyles : {
          name : {
            display : 'none'
          }
        },
        statusClasses : {
          name : ''
        },
        statusMessages : {
          name : ''
        },
        placesProposals : []
      };
    },
    componentWillMount: function() {

      //remplissage automatique si aucun lieu n'est renseigné, sinon enrichissement des propositiosn
      proposePlace = (type, _place) => {

          var maPlace = new Place(this.state.place.name, this.state.place.address, this.state.place.website, this.state.place.phone_number, this.state.place.city, this.state.place.latitude, this.state.place.longitude, this.state.place.opening_hours);
          // console.debug('proposePlace', maPlace, maPlace.isEmpty());
          if (maPlace.isEmpty()){
            this.setState({
              place : _place
            })
          } else {
            var proposals = this.state.placesProposals;
            proposals.push(_place);
            this.setState({
              placesProposals : proposals
            }); 
          }
          
      };

      //choix explicite d'une place depuis l'exterieur
      changePlace = (_place) => {
        this.setState({
          place : _place
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
          
          <Geosuggest
            className="kz_form"
            placeholder="Nom d'un lieu, d'une ville..."
            onSuggestSelect={this.onSuggestSelect}
            types={types}
            autoActivateFirstSuggest='true'
            onFocus={this.onFocus} 
            ref={(c) => this._suggest = c}  />

            { (!this.state.manualMode && !displayForm) &&
              <a onClick={this.onManualMode} style={{cursor:'pointer'}}>Saisir une adresse moi-m&ecirc;me</a>
            }

            { (this.state.manualMode || displayForm) &&
              <div>
                <p>Vous pouvez modifier l&apos;adresse a tout moment, pour cela <strong>cliquez sur le champ &agrave; modifier</strong>. Par exemple, pour corriger le num&eacute;ro de t&eacute;l&eacute;phone, survolez le champ t&eacute;l&eacute;phone et cliquez dessus</p>
                <ul>
                  <li>
                   <span className="editableLabel">Nom du lieu:</span>
                    <InlineEdit
                        activeClassName="editing"
                        text={this.state.place.name}
                        paramName="name"
                        change={this.onEdit}
                        className="editable"
                        staticElement="div" />
                    <span className={this.state.hintClasses.name} style={this.state.hintStyles.name}>
                      <i className={this.state.statusClasses.name}></i>{this.state.statusMessages.name}
                    </span>
                  </li>
                  <li>
                   <span className="editableLabel">Adresse:</span>
                    <InlineEdit
                        activeClassName="editing"
                        text={this.state.place.address}
                        paramName="address"
                        change={this.onEdit}
                        className="editable"
                        staticElement="div" />
                    <span className={this.state.hintClasses.address} style={this.state.hintStyles.address}>
                      <i className={this.state.statusClasses.address}></i>{this.state.statusMessages.address}
                    </span>
                  </li>
                  <li>
                   <span className="editableLabel">Quartier / Ville:</span>
                    <InlineEdit
                        activeClassName="editing"
                        text={this.state.place.city}
                        paramName="city"
                        change={this.onEdit}
                        className="editable" 
                        staticElement="div" />
                    <span className={this.state.hintClasses.city} style={this.state.hintStyles.city}>
                      <i className={this.state.statusClasses.city}></i>{this.state.statusMessages.city}
                    </span>
                  </li>
                  <li>
                   <span className="editableLabel">Latitude:</span>
                    <InlineEdit
                        activeClassName="editing"
                        text={this.state.place.latitude}
                        paramName="latitude"
                        change={this.onEdit}
                        className="editable"
                        validate={this.validateFloat}
                        staticElement="div" />
                    <span className={this.state.hintClasses.latitude} style={this.state.hintStyles.latitude}>
                      <i className={this.state.statusClasses.latitude}></i>{this.state.statusMessages.latitude}
                    </span>
                  </li>
                  <li>
                   <span className="editableLabel">Longitude:</span>
                    <InlineEdit
                        activeClassName="editing"
                        text={this.state.place.longitude}
                        paramName="longitude"
                        change={this.onEdit}
                        className="editable"
                        validate={this.validateFloat} 
                        staticElement="div" />
                    <span className={this.state.hintClasses.longitude} style={this.state.hintStyles.longitude}>
                      <i className={this.state.statusClasses.longitude}></i>{this.state.statusMessages.longitude}
                    </span>
                  </li>
                  <li>
                   <span className="editableLabel">Site web:</span>
                    <InlineEdit
                        activeClassName="editing"
                        text={this.state.place.website}
                        paramName="website"
                        change={this.onEdit}
                        className="editable"
                        validate={this.validateURL} 
                        staticElement="div" />
                    <span className={this.state.hintClasses.website} style={this.state.hintStyles.website}>
                      <i className={this.state.statusClasses.website}></i>{this.state.statusMessages.website}
                    </span>
                  </li>
                  <li>
                   <span className="editableLabel">T&eacute;l&eacute;phone:</span>
                    <InlineEdit
                        activeClassName="editing"
                        text={this.state.place.phone_number}
                        paramName="phone_number"
                        change={this.onEdit}
                        className="editable"
                        validate={this.validatePhone} 
                        staticElement="div" />
                    <span className={this.state.hintClasses.phone_number} style={this.state.hintStyles.phone_number}>
                      <i className={this.state.statusClasses.phone_number}></i>{this.state.statusMessages.phone_number}
                    </span>
                  </li>
                </ul>
              </div>
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
          kidzouPlaceEditor.model.completePlace(self.state.place);
        }
      });
    },

    /**
     * When user edits inline
     * @param  {Object} edited data
     */
    onEdit: function(data) {

      var self = this;
      var keys = Object.keys(data);
      var _place = self.state.place;
      _place[keys[0]] = data[keys[0]];
      
      //save address
      self.setState({ place : _place });

      kidzouPlaceEditor.model.completePlace(this.state.place, 
        function(msg){
          //progress
          var obj = {hintClasses:{},hintStyles:{},statusClasses:{},statusMessages:{}};
          obj.hintClasses[keys[0]]      = 'form_hint valid' ;
          obj.hintStyles[keys[0]]       = { display : 'inline' } ;
          obj.statusClasses[keys[0]]    = 'fa fa-spinner fa-spin' ;
          obj.statusMessages[keys[0]]   = 'Enregistrement...';
          self.setState(obj);
        },
        function(data) {
          //success
          var obj = {hintClasses:{},hintStyles:{},statusClasses:{},statusMessages:{}};
          obj.hintClasses[keys[0]]      = 'form_hint valid' ;
          obj.statusClasses[keys[0]]    = 'fa fa-check' ;
          obj.statusMessages[keys[0]]   = 'Enregistré';
          self.setState(obj);
          
          setTimeout(function(){
            var obj = {hintClasses:{},hintStyles:{},statusClasses:{},statusMessages:{}};
            obj.hintClasses[keys[0]]      = 'form_hint' ;
            obj.hintStyles[keys[0]]       = { display : 'none' } ;
            obj.statusClasses[keys[0]]    = '' ;
            obj.statusMessages[keys[0]]   = '';
            self.setState(obj);
          }, 1500)
        },
        function(err) {
          //error
          var msg  = (err.msg || 'Impossible d\'enregistrer');
          var icon = (err.msg ? '' : 'fa fa-exclamation-circle');
          var obj = {hintClasses:{},hintStyles:{},statusClasses:{},statusMessages:{}};
          obj.hintClasses[keys[0]]      = 'form_hint invalid' ;
          obj.statusClasses[keys[0]]    = icon ;
          obj.statusMessages[keys[0]]   = msg;
          self.setState(obj);
        }
      );
    },

    /**
     * When user clicks on Manual mode
     * @param  {Object} edited data
     */
    onManualMode: function() {
      this.setState({manualMode:true});
    },
  });

  var PlacesProposals = React.createClass({
    render: function() {
      var rows = [];
      this.props.proposals.forEach(function(proposal) {
        rows.push(<PlaceProposal place={proposal} />);
      });
      // var isProposal = (this.props.proposals.length>0);
      return (
        <div>
          { this.props.proposals.length>0 &&
            <p>
              <strong>Lieux propos&eacute;s : </strong><br/>
              {rows}
            </p>
          }
        </div>
      );
    }
  });

  var PlaceProposal = React.createClass({
    render: function() {
      return (
        <span>
          <a onClick={this.changePlace} style={{cursor:'pointer'}}>{this.props.place.name}</a>&nbsp;&nbsp;&nbsp;
        </span>
      );
    },
    changePlace: function(){
      changePlace(this.props.place);
    }
  });

  ReactDOM.render(
    <PlaceEditor />, 
    document.querySelector('#kz_place_metabox .inside')
  );

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////////////////////////////

  //global vars accessible de l'extérieur
  var proposePlace;
  var changePlace;

  return {
    model : kidzouPlaceEditor.model, //necessaire de fournir un acces pour interaction avec Google Maps ??
    proposePlace : proposePlace //propositions de places depuis l'exterieur, mis à jour lors de componentWillMount 
  };

}());  //kidzouPlaceModule

