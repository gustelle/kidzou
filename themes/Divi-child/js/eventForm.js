'use strict';

/**
 *
 * Formulaire de saisie d'événement
 */

var EventForm = React.createClass({
  displayName: 'EventForm',

  getInitialState: function getInitialState() {
    return {
      importButtonState: '',
      submitButtonState: '',
      submitButtonLabel: 'J\'ai terminé, pre-visualiser mon événement',
      postCreated: false,
      facebookUrl: '',
      uploadedMedia: [],
      skipPlaceSuggest: false,
      adresse: {
        suggested: false,
        imported: false,
        country: 'FR'
      },
      contact: {},
      urlMedia: [],
      tooBigMedia: []
    };
  },

  changeFacebookUrl: function changeFacebookUrl(event) {
    this.setState({ facebookUrl: event.target.value });
  },

  importFacebook: function importFacebook(e) {

    //block refresh of page
    e.preventDefault();

    var self = this;
    var value = self.state.facebookUrl;

    var fetchData = function fetchData(token, progressCallback, successCallback, errorCallback) {

      var patt = /https?:\/\/www.facebook.com\/events\/([0-9]*)\/?/i;
      var matches = patt.exec(value);

      if (matches != null) {

        progressCallback();

        FB.api("/" + matches[1] + '?access_token=' + token + '&fields=cover,name,description,place,end_time,start_time', function (response) {

          if (response && !response.error && typeof response.start_time != 'undefined') {

            var startend_time = typeof response.end_time == 'undefined' ? response.start_time : response.end_time;
            var place = response.place || {};
            var location = place.location || {};
            var country_long = location.country || 'france'; //si aucun pays spécifié on considère que c'est en france

            if (country_long.toLowerCase() != 'france' && country_long.toLowerCase() != 'belgium') {
              errorCallback(response);
            } else {

              var street = location.street || location.city || place.name;
              self.setState({
                // title :   response.name,
                urlMedia: [response.cover.source],
                adresse: {
                  name: place.name,
                  street: street,
                  city: location.city,
                  zip: location.zip,
                  lat: location.latitude,
                  lng: location.longitude,
                  imported: true,
                  country: 'FR'
                }
              });

              //mise à jour des composants
              self.title.setValue(response.name);
              self.otherDesc.setValue(response.description);

              self.dates.setState({
                from: new Date(response.start_time.split('T')[0] + ' 00:00:00'), //(() ,
                to: new Date(startend_time.split('T')[0] + ' 23:59:59') //(()
              });

              successCallback();
            }
          } else {
            errorCallback(response);
          }
        }); //FB.api
      } else {
          errorCallback({ msg: 'cela ne correspond pas à l\'URL d\'un événement Facebook' });
        }
    };

    var token_url = "https://graph.facebook.com/oauth/access_token?" + "client_id=" + import_jsvars.facebook_appId + "&client_secret=" + import_jsvars.facebook_appSecret + "&grant_type=client_credentials";

    //avant d'appeler l'API facebook, il faut un access token
    jQuery.ajax({
      url: token_url,
      error: function error() {
        self.setState({ importButtonState: 'error' });
        self._importHint.onError('Oops...cette URL n\'a rien retourne !');
      },
      success: function success(data) {
        var patt = /access_token=(.+)/;
        var matches = patt.exec(data);

        fetchData(matches[1], function (response) {
          //progress
          self.setState({ importButtonState: 'loading' });
          self._importHint.onProgress('Nous appelons facebook pour remplir l\'evenement...');
        }, function (response) {
          //success
          self.setState({ importButtonState: 'success' });
          self._importHint.onSuccess('Nous avons rempli le formulaire avec les donnees de Facebook, il vous reste a verifier et valider !');
        }, function (response) {
          //error
          self.setState({ importButtonState: 'error' });
          self._importHint.onError('Oops...apparemment il y a un souci Houston. Votre evenement est-il public ? se passe-t-il en france ou en belgique ?');
        });
      }
    });
  },

  //
  onFormSubmit: function onFormSubmit(e) {

    e.preventDefault();
    var self = this;

    var checkForm = function checkForm(successCallback, errorCallback) {

      var isError = false;

      if (!self.title.validate()) {
        isError = true;
      }

      var desc = self.publicDesc.state.value.trim() + self.shortDesc.state.value.trim() + self.datesDesc.state.value.trim() + self.tarifsDesc.state.value.trim() + self.resaDesc.state.value.trim() + self.otherDesc.state.value.trim();
      if (desc.trim() == '') {
        self._descHint.onError('Au moins un des champs ci-dessous doit etre rempli !');
        isError = true;
      }

      //en cas d'import facebook our de suggestion par GeoSuggest on ne vérifie pas l'adresse
      if (!self.state.adresse.suggested && !self.state.adresse.imported) {
        if (!self._suggest || self._suggest.state.userInput == '' || self.adresseStreet.state.value.trim() == '') {
          self._placeHint.onError('Merci d\'indiquer ou cela se passe');
          isError = true;
        }
      }

      //dates
      if (self.dates.state.from == null || self.dates.state.to == null) {
        self._datesHint.onError('Merci d\'indiquer la date de debut et de fin');
        isError = true;
      }

      if (self.state.uploadedMedia.length == 0 && self.state.urlMedia.length == 0) {
        self._mediaHint.onError('Au moins une image est necessaire');
        isError = true;
      }

      //uploadedMedia
      if (isError) {
        errorCallback();
      } else {
        successCallback();
      }
    };

    var sendData = function sendData() {

      jQuery.ajax({
        url: import_jsvars.api_create_post_nonce,
        error: function error() {
          console.log('get_nonce, error');
        },
        success: function success(data) {

          if (!data.nonce) {
            console.log('get_nonce, error');
          } else {

            var formattedDesc = '<ul><li><strong>Public :</strong>' + self.publicDesc.state.value + ' </li><li><strong>Description :</strong>' + self.shortDesc.state.value + '</li><li><strong>Date :</strong>' + self.datesDesc.state.value + '</li><li><strong>Tarifs :</strong>' + self.tarifsDesc.state.value + '</li><li><strong>R&eacute;servation :</strong>' + self.resaDesc.state.value + '</li></ul><p><strong>Le mot de l&apos;organisateur :</strong><br/>' + self.otherDesc.state.value + '</p>';

            //si l'adresse n'est pas reprise de google suggest mais reprise manuellement
            //on met en cohérence l'objet adresse envoyé au server
            if (!self.state.adresse.suggested && !self.state.adresse.imported) {
              self.setState({
                adresse: {
                  country: 'FR',
                  name: self._suggest.state.userInput,
                  street: self.adresseStreet.state.value
                }
              });
            }

            var sendit = {
              data: {
                titre: self.title.state.value,
                description: formattedDesc,
                contact: {},
                adresse: self.state.adresse,
                dates: {
                  start_date: moment(self.dates.state.from).format('YYYY-MM-DD HH:mm:ss'),
                  end_date: moment(self.dates.state.to).format('YYYY-MM-DD HH:mm:ss')
                },
                urlMedia: self.state.urlMedia,
                uploadedMedia: self.state.uploadedMedia
              }
            };

            jQuery.post(import_jsvars.api_create_post + '?nonce=' + data.nonce + '&key=' + import_jsvars.api_key, sendit).done(function (resp) {
              if (!resp.status || resp.status == 'error') {
                console.log('error', resp);
                self.setState({ submitButtonState: 'error' });
                self.submitMessage.onError('Une erreur s\'est produite !');
              }

              //ce marker va bloquer la revalidation du form
              self.setState({ submitButtonState: 'disabled' });
              self.submitMessage.onProgress('Votre evenement a ete cree, vous allez etre redirige vers sa pre-visualisation');
              self.setState({ postCreated: true });
              // self.submitButton.disable();
              //rediriger vers un preview du post
              window.location = resp.post_preview_url;
            }).fail(function (e) {
              console.log('fail', e);
              self.setState({ submitButtonState: 'error' });
              self.submitMessage.onError('Une erreur s\'est produite !');
            });
          }
        }
      });
    };

    //
    if (!self.state.postCreated) {

      self.setState({ submitButtonState: 'loading' });
      checkForm(function () {
        //on success
        sendData();
      }, function () {
        //on failure
        self.setState({ submitButtonState: 'error' });
      });
    }
  },

  /**
   * When user clicks on the input field
   * @param  {Object} suggest The suggest
   */
  onSuggestFocus: function onSuggestFocus() {
    var self = this;
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
          adresse: {
            name: placeResult.name,
            street: placeResult.formatted_address,
            city: city,
            zip: '',
            lat: placeResult.geometry.location.lat(), //latitude
            lng: placeResult.geometry.location.lng(), //longitude
            opening_hours: placeResult.opening_hours ? placeResult.opening_hours.periods : [],
            suggested: true,
            country: 'FR'
          },
          contact: {
            website: placeResult.website || '',
            phone_number: placeResult.formatted_phone_number || ''
          }
        });

        //mise à jour pour cohérence
        //self.adresseStreet.setValue(placeResult.formatted_address);
      }
    });
  },

  /**
   * When a user wants to skip the suggestions
   */
  onSkipSuggest: function onSkipSuggest(e) {
    e.preventDefault();
    // console.debug('skipSuggest')
    this.setState({
      skipPlaceSuggest: true
    });
  },

  /**
   * When a user wants to activate suggestions after having skipped them
   */
  onActivateSuggest: function onActivateSuggest(e) {
    e.preventDefault();
    // console.debug('activateSuggest')
    this.setState({
      skipPlaceSuggest: false
    });
  },

  /**
   * When a user gets out of the place suggest field
   */
  onSuggestBlur: function onSuggestBlur() {
    // console.debug('onSuggestBlur')
  },

  /**
   * When a wants to reset the address (selected or not)
   */
  onResetPlace: function onResetPlace() {
    var self = this;
    self.setState({ adresse: {} });
  },

  /**
   * When user drops a media [image, audio, pdf] 
   * - Check de la taille du media (<2M)
   * - Stockage dans le navigateur sous forme Base64
   */
  onDrop: function onDrop(files) {
    var self = this;
    var media = self.state.uploadedMedia;
    var errors = self.state.tooBigMedia;
    files.map(function (file, index) {
      // console.log('file size is ', file.size/1024/1024);
      if (file.size / 1024 / 1024 < 2) {
        var reader = new FileReader();
        reader.onload = function () {
          media.push({
            name: file.name,
            data: reader.result
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
  removeMedia: function removeMedia(index) {
    var self = this;
    var media = self.state.uploadedMedia;
    var removed = media.splice(index, 1);
    self.setState({
      uploadedMedia: media
    });
  },

  render: function render() {
    var _this = this;

    var self = this;

    //le browser supporte nos scripts
    var supportFileUpload = window.File && window.FileReader && window.FileList && window.Blob;

    //les fichiers téléchargés
    var filesList = self.state.uploadedMedia.map(function (file, i) {
      if (file.data.startsWith('data:image')) {
        return React.createElement(
          'div',
          { className: 'previewBlock', key: 'div' + i },
          React.createElement('img', { src: file.data }),
          React.createElement(
            'div',
            null,
            file.name
          ),
          React.createElement(
            'a',
            { style: { cursor: 'pointer' }, onClick: self.removeMedia.bind(self, i) },
            'Supprimer'
          )
        );
      } else if (file.data.startsWith('data:audio')) {
        return React.createElement(
          'div',
          { className: 'noPreview', key: 'div' + i },
          React.createElement('i', { className: 'fa fa-volume-up fa-5x' }),
          React.createElement(
            'div',
            null,
            file.name
          ),
          React.createElement(
            'a',
            { style: { cursor: 'pointer' }, onClick: self.removeMedia.bind(self, i) },
            'Supprimer'
          )
        );
      } else if (file.data.startsWith('data:application/pdf')) {
        return React.createElement(
          'div',
          { className: 'noPreview', key: 'div' + i },
          React.createElement('i', { className: 'fa fa-file-pdf-o fa-5x' }),
          React.createElement(
            'div',
            null,
            file.name
          ),
          React.createElement(
            'a',
            { style: { cursor: 'pointer' }, onClick: self.removeMedia.bind(self, i) },
            'Supprimer'
          )
        );
      } else {
        return React.createElement(
          'div',
          { className: 'incorrectType', key: 'div' + i },
          React.createElement('i', { className: 'fa fa-exclamation fa-5x' }),
          React.createElement(
            'div',
            null,
            file.name
          ),
          React.createElement('br', null),
          'Ce format n\'est pas supporté, il ne sera pas importé'
        );
      }
    });

    var uploadErrors = self.state.tooBigMedia.map(function (file, i) {
      return React.createElement(
        'div',
        { className: 'incorrectType', key: 'div' + i },
        React.createElement('i', { className: 'fa fa-exclamation fa-5x' }),
        React.createElement(
          'div',
          null,
          file.name
        ),
        React.createElement('br', null),
        'Ce fichier est trop volumineux (maximum : 2 MB)'
      );
    });

    filesList = filesList.concat(uploadErrors);

    var suggestTypes = ['establishment'];

    return React.createElement(
      'form',
      { className: 'kz_form' },
      React.createElement(
        'div',
        { className: 'et_pb_promo et_pb_bg_layout_light et_pb_text_align_left', style: { backgroundColor: "#94e0fc" } },
        React.createElement(
          'div',
          { className: 'et_pb_promo_description' },
          React.createElement(
            'h2',
            null,
            'Pas de stress !'
          ),
          React.createElement(
            'p',
            null,
            'Créez votre événement en 1 click à partir de Facebook !'
          ),
          React.createElement(
            'div',
            { className: 'kz_form_field' },
            React.createElement(
              'div',
              { className: 'inline' },
              React.createElement('i', { className: 'fa fa-3x fa-facebook-official' }),
              React.createElement(
                'label',
                { htmlFor: 'facebook_url' },
                'Page Facebook de l\'événement : '
              ),
              React.createElement('input', {
                type: 'text',
                name: 'facebook_url',
                placeholder: 'https://www.facebook.com/events/1028586230505678/',
                onChange: this.changeFacebookUrl,
                value: this.state.facebookUrl }),
              React.createElement(
                ProgressButton,
                { onClick: this.importFacebook, state: this.state.importButtonState },
                React.createElement('i', { className: 'fa fa-magic' }),
                'Remplir'
              )
            )
          )
        )
      ),
      React.createElement(HintMessage, { ref: function ref(c) {
          return _this._importHint = c;
        } }),
      React.createElement(TextField, {
        name: 'event_title',
        label: 'Nom de l\'événement',
        placeholder: 'Nom de votre événement',
        mandatory: true,
        ref: function ref(c) {
          return _this.title = c;
        } }),
      React.createElement(
        'div',
        { className: 'kz_form_field_group' },
        React.createElement(
          'h2',
          null,
          'Lieu de l\'événement : '
        ),
        React.createElement(
          'p',
          null,
          React.createElement('i', { className: 'fa fa-asterisk mandatory' }),
          'ce champ est obligatoire'
        ),
        React.createElement(HintMessage, { ref: function ref(c) {
            return _this._placeHint = c;
          } }),
        React.createElement('div', { id: 'place_results', style: { display: 'none' } }),
        this.state.adresse.name && this.state.adresse.street && React.createElement(
          'div',
          { onClick: this.onResetPlace, className: 'kz_box' },
          React.createElement(
            'div',
            { style: { cursor: 'pointer' } },
            React.createElement(
              'div',
              { style: { width: 'auto', verticalAlign: 'middle', float: 'left', padding: '10px' } },
              React.createElement('i', { className: 'fa fa-check fa-2x' })
            ),
            React.createElement(
              'div',
              { style: { width: 'auto', verticalAlign: 'middle', padding: '10px', backgroundColor: '#94e0fc' } },
              React.createElement(
                'span',
                { style: { fontSize: '1.2em' } },
                this.state.adresse.name,
                React.createElement('br', null)
              ),
              React.createElement(
                'span',
                { style: { fontSize: '1em' } },
                this.state.adresse.street,
                ' ',
                this.state.adresse.zip,
                ' ',
                this.state.adresse.city
              )
            )
          )
        ),
        (!this.state.adresse.name || !this.state.adresse.street) && React.createElement(
          'div',
          null,
          React.createElement(
            'div',
            { className: 'kz_form_field' },
            !this.state.skipPlaceSuggest && React.createElement(
              'div',
              null,
              React.createElement(
                'p',
                null,
                'Kidzou vous propose les adresses connues par ',
                React.createElement(
                  'a',
                  { href: 'https://www.google.com/business/' },
                  'Google'
                ),
                ' lors de votre saisie de l\'adresse'
              ),
              React.createElement(
                'a',
                { onClick: this.onSkipSuggest, style: { cursor: 'pointer' } },
                React.createElement('i', { className: 'fa fa-magic' }),
                'Les suggestions ne sont pas satisfaisantes : désactiver les suggestions'
              )
            ),
            this.state.skipPlaceSuggest && React.createElement(
              'div',
              null,
              React.createElement(
                'p',
                null,
                'La suggestion des lieux connus par Google est désactivée'
              ),
              React.createElement(
                'a',
                { onClick: this.onActivateSuggest, style: { cursor: 'pointer' } },
                React.createElement('i', { className: 'fa fa-magic' }),
                'Activer les suggestions d\'adresse'
              )
            ),
            React.createElement(
              'label',
              { htmlFor: 'event_venue' },
              'Nom du lieu : '
            ),
            React.createElement(Geosuggest, {
              placeholder: 'Ex : Musée de plein air',
              onSuggestSelect: this.onSuggestSelect,
              types: suggestTypes,
              autoActivateFirstSuggest: 'true',
              onFocus: this.onSuggestFocus,
              skipSuggest: function skipSuggest() {
                return _this.state.skipPlaceSuggest;
              },
              onBlur: this.onSuggestBlur,
              ref: function ref(c) {
                return _this._suggest = c;
              } })
          ),
          React.createElement(TextField, {
            name: 'event_address',
            label: 'Adresse',
            placeholder: 'Ex : 135 avenue de Bretagne 59000 Lille',
            ref: function ref(c) {
              return _this.adresseStreet = c;
            } })
        )
      ),
      React.createElement(
        'div',
        { className: 'kz_form_field_group' },
        React.createElement(
          'h2',
          null,
          'Description : '
        ),
        React.createElement(
          'p',
          null,
          React.createElement('i', { className: 'fa fa-asterisk mandatory' }),
          'Au moins l\'un des champs ci-dessous est obligatoire'
        ),
        React.createElement(HintMessage, { ref: function ref(c) {
            return _this._descHint = c;
          } }),
        React.createElement(TextField, {
          name: 'event_publicDesc',
          label: 'Public',
          placeholder: 'Ex : A partir de x ans',
          ref: function ref(c) {
            return _this.publicDesc = c;
          } }),
        React.createElement(TextField, {
          name: 'event_shortDesc',
          label: 'Description courte',
          placeholder: 'Ex : Atelier cuisine Parents / enfants',
          ref: function ref(c) {
            return _this.shortDesc = c;
          } }),
        React.createElement(TextField, {
          name: 'event_dates',
          label: 'Dates et heures',
          placeholder: 'Ex : La samedi de 10h à 18h et le dimanche de 10h à 12h',
          ref: function ref(c) {
            return _this.datesDesc = c;
          } }),
        React.createElement(TextField, {
          name: 'event_tarifs',
          label: 'Tarif',
          placeholder: 'Ex : Tarif plein / tarif réduit',
          ref: function ref(c) {
            return _this.tarifsDesc = c;
          } }),
        React.createElement(TextField, {
          name: 'event_resa',
          label: 'Réservation',
          placeholder: 'Ex : Contact mail , téléphone ou site web',
          ref: function ref(c) {
            return _this.resaDesc = c;
          } }),
        React.createElement(TextField, {
          type: 'textarea',
          name: 'event_autre',
          label: 'Le mot de l\'organisateur',
          placeholder: 'Tous les autres détails concernant votre événement',
          ref: function ref(c) {
            return _this.otherDesc = c;
          } })
      ),
      React.createElement(
        'div',
        { className: 'kz_form_field_group' },
        React.createElement(
          'h2',
          null,
          'Dates : '
        ),
        React.createElement(
          'p',
          null,
          React.createElement('i', { className: 'fa fa-asterisk mandatory' }),
          'ce champ est obligatoire'
        ),
        React.createElement(HintMessage, { ref: function ref(c) {
            return _this._datesHint = c;
          } }),
        React.createElement(EventDates, { ref: function ref(c) {
            return _this.dates = c;
          } })
      ),
      React.createElement(
        'div',
        { className: 'kz_form_field_group' },
        React.createElement(
          'h2',
          null,
          'Media : '
        ),
        React.createElement(
          'p',
          null,
          React.createElement('i', { className: 'fa fa-asterisk mandatory' }),
          'Au moins une image est requise'
        ),
        React.createElement(HintMessage, { ref: function ref(c) {
            return _this._mediaHint = c;
          } }),
        React.createElement(
          'div',
          { className: 'mediaPreview' },
          supportFileUpload && React.createElement(
            'div',
            { style: { display: 'inline-block', verticalAlign: 'top' } },
            React.createElement(
              Dropzone,
              { ref: 'dropzone', onDrop: this.onDrop, className: 'mediaDropZone' },
              React.createElement('i', { className: 'fa fa-3x fa-plus-circle', style: { color: '#1ea4e8' } }),
              'Glissez-Déposez des fichiers (Images, audio, video) ou cliquez pour ouvrir votre explorateur de fichiers.'
            )
          ),
          !supportFileUpload && React.createElement(
            'div',
            null,
            React.createElement(
              'span',
              null,
              'Votre navigateur n\'pas suffisamment récent pour supporter notre application de téléchargement d\'images'
            )
          ),
          this.state.urlMedia.length > 0 && this.state.urlMedia[0] !== '' && React.createElement(
            'div',
            { className: 'previewBlock' },
            React.createElement('img', { src: this.state.urlMedia[0] })
          ),
          filesList
        )
      ),
      React.createElement(
        'div',
        { className: 'et_pb_promo et_pb_bg_layout_light et_pb_text_align_center' },
        React.createElement(HintMessage, { ref: function ref(c) {
            return _this.submitMessage = c;
          } }),
        !this.state.postCreated && React.createElement(
          ProgressButton,
          { type: 'submit',
            durationSuccess: 2000,
            onClick: this.onFormSubmit,
            state: this.state.submitButtonState,
            ref: function ref(c) {
              return _this.submitButton = c;
            } },
          React.createElement('i', { className: 'fa fa-send' }),
          React.createElement(
            'span',
            null,
            this.state.submitButtonLabel
          )
        )
      )
    );
  }

});
