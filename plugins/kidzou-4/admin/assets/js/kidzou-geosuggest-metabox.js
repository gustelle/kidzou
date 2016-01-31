'use strict';

var kidzouPlaceModule = function () {
  //havre de paix

  function Place(_venue, _address, _website, _phone_number, _city, _lat, _lng, _opening_hours) {

    this.venue = _venue;
    this.address = _address;
    this.website = _website;
    this.phone_number = _phone_number;
    this.city = _city; //quartier ou ville, rapidement identifiable par l'internaute
    this.lat = _lat;
    this.lng = _lng;
    this.opening_hours = _opening_hours;

    this.isEmpty = function () {
      return (this.venue == '' || typeof this.venue == 'undefined') && (this.address == '' || typeof this.address == 'undefined') && (this.website == '' || typeof this.website == 'undefined') && (this.phone_number == '' || typeof this.phone_number == 'undefined') && (this.city == '' || typeof this.city == 'undefined') && (this.lat == '' || typeof this.lat == 'undefined') && (this.lng == '' || typeof this.lng == 'undefined');
    };

    this.isValid = function () {
      return this.venue !== '' && this.address !== '' && this.city !== '';
    };

    //fonction de comparaison de places
    this.equals = function (__venue, __address, __website, __phone_number, __city, __lat, __lng, __opening_hours) {

      //redressement du telephone
      var phoneEquals = __phone_number == this.phone_number;
      if (!phoneEquals) {
        if (typeof _phone_number !== 'undefined') {
          if (typeof this.phone_number !== 'undefined') phoneEquals = this.phone_number.replace(/\s/gi, "") == __phone_number.replace(/\s/gi, "");
        }
      }

      var eq = this.venue == __venue && this.address == __address && this.website == __website && phoneEquals && this.city == __city && this.lat == __lat && this.lng == __lng //&&
      ;
      return eq;
    };
  }

  function updateVilleTaxonomy(post_id, value) {

    if (value !== null && "undefined" !== value && document.querySelector('#kz_event_metropole_' + value.toLowerCase()) !== null) {
      document.querySelector('#kz_event_metropole_' + value.toLowerCase()).setAttribute('checked', 'checked');

      //todo : save in ajax
    }
  }

  var kidzouPlaceEditor = function () {

    var editorModel = new PlaceEditorModel();

    //sur l'écran d'édition d'un post, ce champ est toujours rempli
    var postID = document.querySelector('#post_ID') !== null ? document.querySelector('#post_ID').value : '';

    function PlaceEditorModel() {

      var self = this;

      self.placeData = new Place();

      //Resultats en provenance de Google PlaceComplete
      //https://developers.google.com/places/documentation/details
      self.completePlace = function (result, progress, success, error) {

        self.placeData = new Place(result.name, result.address, result.website, result.phone_number, result.city, //city
        result.latitude, //latitude
        result.longitude, //longitude
        result.opening_hours);

        if (window.kidzouAdminGeo && document.querySelector('#post_ID') !== null) kidzouAdminGeo.getMetropole(result.latitude, result.longitude, function (metropole) {
          updateVilleTaxonomy(document.querySelector('#post_ID').value, metropole);
        });

        if (self.placeData.isValid()) {
          //sauvegarder les data
          self.savePlace(progress, success, error);
        } else {
          if (typeof error === "function") error({ msg: 'Continuez a remplir les champs, la sauvegarde sera automatique lorsque Nom, adresse et ville seront remplis' });
        }
      };

      self.savePlace = function (progressCallback, successCallback, errorCallback) {
        if (postID == '') {
          if (typeof errorCallback === "function") errorCallback();
          return;
        }

        if (typeof progressCallback === "function") progressCallback();

        jQuery.get(place_jsvars.api_base + '/api/get_nonce/?controller=content&method=place', {}, function (n) {

          jQuery.post(place_jsvars.api_save_place, {
            contact: {
              tel: self.placeData.phone_number,
              web: self.placeData.website
            },
            location: {
              name: self.placeData.venue,
              address: self.placeData.address,
              city: self.placeData.city,
              lat: self.placeData.lat,
              lng: self.placeData.lng,
              country: 'FR'
            },
            nonce: n.nonce,
            post_id: postID
          }).done(function (r) {

            if (r.status == 'ok' && typeof r.result !== 'undefined' && r.result !== null && typeof r.result.errors !== 'undefined' && typeof errorCallback === "function") errorCallback(r);else if (typeof successCallback === "function") successCallback(r);
          }).fail(function (err) {
            console.error(err);
            if (typeof errorCallback === "function") errorCallback(err);
          });
        });
      };
    } //PlaceEditorModel

    return {
      model: editorModel };
  }(); //kidzouPlaceEditor

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  /////////////// React UI

  //PlaceEditorModel
  var PlaceEditor = React.createClass({
    displayName: 'PlaceEditor',

    getInitialState: function getInitialState() {
      return {
        manualMode: false, //saisie manuelle d'une adresse c'est à dire sans Google PlacesService
        place: {
          name: place_jsvars.location_name,
          address: place_jsvars.location_address,
          website: place_jsvars.location_website,
          phone_number: place_jsvars.location_phone_number,
          city: place_jsvars.location_city,
          latitude: place_jsvars.location_latitude, //latitude
          longitude: place_jsvars.location_longitude, //longitude
          opening_hours: []
        },
        resultsStyle: {
          display: 'none'
        },
        hintClasses: {
          name: 'form_hint'
        },
        hintStyles: {
          name: {
            display: 'none'
          }
        },
        statusClasses: {
          name: ''
        },
        statusMessages: {
          name: ''
        },
        placesProposals: []
      };
    },
    componentWillMount: function componentWillMount() {
      var _this = this;

      //remplissage automatique si aucun lieu n'est renseigné, sinon enrichissement des propositiosn
      proposePlace = function proposePlace(type, _place) {
        //_venue, _address, _website, _phone_number, _city, _lat, _lng, _opening_hours
        var maPlace = new Place(_this.state.place.name, _this.state.place.address, _this.state.place.website, _this.state.place.phone_number, _this.state.place.city, _this.state.place.latitude, _this.state.place.longitude, _this.state.place.opening_hours);
        console.debug('proposePlace', maPlace, maPlace.isEmpty());
        if (maPlace.isEmpty()) {
          _this.setState({
            place: _place
          });
        } else {
          var proposals = _this.state.placesProposals;
          proposals.push(_place);
          _this.setState({
            placesProposals: proposals
          });
        }
      };

      //choix explicite d'une place depuis l'exterieur
      _changePlace = function changePlace(_place) {
        _this.setState({
          place: _place
        });
      };
    },

    /**
     * Render the example app
     */
    render: function render() {
      var _this2 = this;

      var types = ['establishment'];
      var displayForm = this.state.place.name !== '' || this.state.place.address !== '' || this.state.place.city !== '' || this.state.place.latitude !== '' || this.state.place.longitude !== '' || this.state.place.website !== '' || this.state.place.phone_number !== '';
      var pointer = { cursor: 'pointer' };

      return React.createElement(
        'div',
        null,
        React.createElement(
          'p',
          null,
          'Commencez à taper quelques lettres et des propositions apparaitront. Les propositions sont issues de ',
          React.createElement(
            'strong',
            null,
            'Google Maps'
          )
        ),
        React.createElement(Geosuggest, {
          className: 'kz_form',
          placeholder: 'Nom d\'un lieu, d\'une ville...',
          onSuggestSelect: this.onSuggestSelect,
          types: types,
          autoActivateFirstSuggest: 'true',
          onFocus: this.onFocus,
          ref: function ref(c) {
            return _this2._suggest = c;
          } }),
        !this.state.manualMode && !displayForm && React.createElement(
          'a',
          { onClick: this.onManualMode, style: { cursor: 'pointer' } },
          'Saisir une adresse moi-même'
        ),
        (this.state.manualMode || displayForm) && React.createElement(
          'div',
          null,
          React.createElement(
            'p',
            null,
            'Vous pouvez modifier l\'adresse a tout moment, pour cela ',
            React.createElement(
              'strong',
              null,
              'cliquez sur le champ à modifier'
            ),
            '. Par exemple, pour corriger le numéro de téléphone, survolez le champ téléphone et cliquez dessus'
          ),
          React.createElement(
            'ul',
            null,
            React.createElement(
              'li',
              null,
              React.createElement(
                'span',
                { className: 'editableLabel' },
                'Nom du lieu:'
              ),
              React.createElement(InlineEdit, {
                activeClassName: 'editing',
                text: this.state.place.name,
                paramName: 'name',
                change: this.onEdit,
                className: 'editable',
                staticElement: 'div' }),
              React.createElement(
                'span',
                { className: this.state.hintClasses.name, style: this.state.hintStyles.name },
                React.createElement('i', { className: this.state.statusClasses.name }),
                this.state.statusMessages.name
              )
            ),
            React.createElement(
              'li',
              null,
              React.createElement(
                'span',
                { className: 'editableLabel' },
                'Adresse:'
              ),
              React.createElement(InlineEdit, {
                activeClassName: 'editing',
                text: this.state.place.address,
                paramName: 'address',
                change: this.onEdit,
                className: 'editable',
                staticElement: 'div' }),
              React.createElement(
                'span',
                { className: this.state.hintClasses.address, style: this.state.hintStyles.address },
                React.createElement('i', { className: this.state.statusClasses.address }),
                this.state.statusMessages.address
              )
            ),
            React.createElement(
              'li',
              null,
              React.createElement(
                'span',
                { className: 'editableLabel' },
                'Quartier / Ville:'
              ),
              React.createElement(InlineEdit, {
                activeClassName: 'editing',
                text: this.state.place.city,
                paramName: 'city',
                change: this.onEdit,
                className: 'editable',
                staticElement: 'div' }),
              React.createElement(
                'span',
                { className: this.state.hintClasses.city, style: this.state.hintStyles.city },
                React.createElement('i', { className: this.state.statusClasses.city }),
                this.state.statusMessages.city
              )
            ),
            React.createElement(
              'li',
              null,
              React.createElement(
                'span',
                { className: 'editableLabel' },
                'Latitude:'
              ),
              React.createElement(InlineEdit, {
                activeClassName: 'editing',
                text: this.state.place.latitude,
                paramName: 'latitude',
                change: this.onEdit,
                className: 'editable',
                validate: this.validateFloat,
                staticElement: 'div' }),
              React.createElement(
                'span',
                { className: this.state.hintClasses.latitude, style: this.state.hintStyles.latitude },
                React.createElement('i', { className: this.state.statusClasses.latitude }),
                this.state.statusMessages.latitude
              )
            ),
            React.createElement(
              'li',
              null,
              React.createElement(
                'span',
                { className: 'editableLabel' },
                'Longitude:'
              ),
              React.createElement(InlineEdit, {
                activeClassName: 'editing',
                text: this.state.place.longitude,
                paramName: 'longitude',
                change: this.onEdit,
                className: 'editable',
                validate: this.validateFloat,
                staticElement: 'div' }),
              React.createElement(
                'span',
                { className: this.state.hintClasses.longitude, style: this.state.hintStyles.longitude },
                React.createElement('i', { className: this.state.statusClasses.longitude }),
                this.state.statusMessages.longitude
              )
            ),
            React.createElement(
              'li',
              null,
              React.createElement(
                'span',
                { className: 'editableLabel' },
                'Site web:'
              ),
              React.createElement(InlineEdit, {
                activeClassName: 'editing',
                text: this.state.place.website,
                paramName: 'website',
                change: this.onEdit,
                className: 'editable',
                validate: this.validateURL,
                staticElement: 'div' }),
              React.createElement(
                'span',
                { className: this.state.hintClasses.website, style: this.state.hintStyles.website },
                React.createElement('i', { className: this.state.statusClasses.website }),
                this.state.statusMessages.website
              )
            ),
            React.createElement(
              'li',
              null,
              React.createElement(
                'span',
                { className: 'editableLabel' },
                'Téléphone:'
              ),
              React.createElement(InlineEdit, {
                activeClassName: 'editing',
                text: this.state.place.phone_number,
                paramName: 'phone_number',
                change: this.onEdit,
                className: 'editable',
                validate: this.validatePhone,
                staticElement: 'div' }),
              React.createElement(
                'span',
                { className: this.state.hintClasses.phone_number, style: this.state.hintStyles.phone_number },
                React.createElement('i', { className: this.state.statusClasses.phone_number }),
                this.state.statusMessages.phone_number
              )
            )
          )
        ),
        React.createElement(PlacesProposals, { proposals: this.state.placesProposals }),
        React.createElement('div', { id: 'place_results', style: this.state.resultsStyle })
      );
    },

    /**
     * validation of input field as float value
     */
    validateFloat: function validateFloat(value) {
      var patt = /^[+-]?((\.\d+)|(\d+(\.\d+)?))$/;
      var matches = patt.exec(value);
      return matches != null;
    },

    /**
     * validation of input field as URL
     */
    validateURL: function validateURL(value) {
      var patt = /^(https?:\/\/)?((([a-z\d]([a-z\d-]*[a-z\d])*)\.)+[a-z]{2,}|((\d{1,3}\.){3}\d{1,3}))(\:\d+)?(\/[-a-z\d%_.~+]*)*(\?[;&a-z\d%_.~+=-]*)?(\#[-a-z\d_]*)?$/i;
      var matches = patt.exec(value);
      return matches != null;
    },

    /**
     * validation of input field as Phone format
     */
    validatePhone: function validatePhone(value) {
      var patt = /^(?:(?:\(?(?:00|\+)([1-4]\d\d|[1-9]\d?)\)?)?[\-\.\ \\\/]?)?((?:\(?\d{1,}\)?[\-\.\ \\\/]?){0,})(?:[\-\.\ \\\/]?(?:#|ext\.?|extension|x)[\-\.\ \\\/]?(\d+))?$/;
      var matches = patt.exec(value);
      return matches != null;
    },

    /**
     * When user clicks on the input field
     * @param  {Object} suggest The suggest
     */
    onFocus: function onFocus() {
      var self = this;
      // console.debug('state', self._suggest);
      self._suggest.clear();
    },

    /**
     * When a suggest got selected
     * @param  {Object} suggest The suggest
     */
    onSuggestSelect: function onSuggestSelect(suggest) {
      // console.log(suggest);
      var self = this;

      //recuperer google maps et demander les details
      var service = new google.maps.places.PlacesService(document.querySelector('#place_results'));
      var request = {
        placeId: suggest.placeId
      };
      service.getDetails(request, function (placeResult, status) {
        if (status == google.maps.places.PlacesServiceStatus.OK) {

          var city = placeResult.display_text;
          //tentative de retrouver la ville de manière plus précise
          //voir https://developers.google.com/maps/documentation/geocoding/?hl=FR#Types
          placeResult.address_components.forEach(function (entry) {
            if (entry.types[0] == 'locality') {
              city = entry.long_name;
            }
          });

          self.setState({
            place: {
              name: placeResult.name,
              address: placeResult.formatted_address,
              website: placeResult.website,
              phone_number: placeResult.formatted_phone_number,
              city: city,
              latitude: placeResult.geometry.location.lat(), //latitude
              longitude: placeResult.geometry.location.lng(), //longitude
              opening_hours: placeResult.opening_hours ? placeResult.opening_hours.periods : []
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
    onEdit: function onEdit(data) {

      var self = this;
      var keys = Object.keys(data);
      var _place = self.state.place;
      _place[keys[0]] = data[keys[0]];

      //save address
      self.setState({ place: _place });

      kidzouPlaceEditor.model.completePlace(this.state.place, function (msg) {
        //progress
        var obj = { hintClasses: {}, hintStyles: {}, statusClasses: {}, statusMessages: {} };
        obj.hintClasses[keys[0]] = 'form_hint valid';
        obj.hintStyles[keys[0]] = { display: 'inline' };
        obj.statusClasses[keys[0]] = 'fa fa-spinner fa-spin';
        obj.statusMessages[keys[0]] = 'Enregistrement...';
        self.setState(obj);
      }, function (data) {
        //success
        var obj = { hintClasses: {}, hintStyles: {}, statusClasses: {}, statusMessages: {} };
        obj.hintClasses[keys[0]] = 'form_hint valid';
        obj.statusClasses[keys[0]] = 'fa fa-check';
        obj.statusMessages[keys[0]] = 'Enregistré';
        self.setState(obj);

        setTimeout(function () {
          var obj = { hintClasses: {}, hintStyles: {}, statusClasses: {}, statusMessages: {} };
          obj.hintClasses[keys[0]] = 'form_hint';
          obj.hintStyles[keys[0]] = { display: 'none' };
          obj.statusClasses[keys[0]] = '';
          obj.statusMessages[keys[0]] = '';
          self.setState(obj);
        }, 1500);
      }, function (err) {
        //error
        var msg = err.msg || 'Impossible d\'enregistrer';
        var icon = err.msg ? '' : 'fa fa-exclamation-circle';
        var obj = { hintClasses: {}, hintStyles: {}, statusClasses: {}, statusMessages: {} };
        obj.hintClasses[keys[0]] = 'form_hint invalid';
        obj.statusClasses[keys[0]] = icon;
        obj.statusMessages[keys[0]] = msg;
        self.setState(obj);
      });
    },

    /**
     * When user clicks on Manual mode
     * @param  {Object} edited data
     */
    onManualMode: function onManualMode() {
      this.setState({ manualMode: true });
    }
  });

  var PlacesProposals = React.createClass({
    displayName: 'PlacesProposals',

    render: function render() {
      var rows = [];
      this.props.proposals.forEach(function (proposal) {
        rows.push(React.createElement(PlaceProposal, { place: proposal }));
      });
      // var isProposal = (this.props.proposals.length>0);
      return React.createElement(
        'div',
        null,
        this.props.proposals.length > 0 && React.createElement(
          'p',
          null,
          React.createElement(
            'strong',
            null,
            'Lieux proposés : '
          ),
          React.createElement('br', null),
          rows
        )
      );
    }
  });

  var PlaceProposal = React.createClass({
    displayName: 'PlaceProposal',

    render: function render() {
      return React.createElement(
        'span',
        null,
        React.createElement(
          'a',
          { onClick: this.changePlace, style: { cursor: 'pointer' } },
          this.props.place.name
        ),
        '   '
      );
    },
    changePlace: function changePlace() {
      _changePlace(this.props.place);
    }
  });

  ReactDOM.render(React.createElement(PlaceEditor, null), document.querySelector('#kz_place_metabox .inside'));

  ////////////////////////////////////////////////////////////////////////////////////////////////////////
  ////////////////////////////////////////////////////////////////////////////////////////////////////////

  //global vars accessible de l'extérieur
  var proposePlace;
  var _changePlace;

  return {
    model: kidzouPlaceEditor.model, //necessaire de fournir un acces pour interaction avec Google Maps ??
    proposePlace: proposePlace //propositions de places depuis l'exterieur, mis à jour lors de componentWillMount
  };
}(); //kidzouPlaceModule
