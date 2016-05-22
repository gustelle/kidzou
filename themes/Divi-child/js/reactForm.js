'use strict';

/**
 * Un <HintMessage /> est un message diffusé a coté d'un champ mis à jour en Ajax
 * Il peut etre appelé de 3 manieres : 
 * * onProgress
 * * onSuccess (effacé au bout de 1,5s)
 * * onError (effacé au bout de 1,5s)
 *
 */
var HintMessage = React.createClass({
  displayName: 'HintMessage',

  getDefaultProps: function getDefaultProps() {
    return {
      // _isMounted : false
    };
  },

  getInitialState: function getInitialState() {
    return {
      valid: false,
      show: false,
      iconClass: '',
      message: '',
      _isMounted: false
    };
  },

  //pour éviter erreur setState(...): Can only update a mounted or mounting component. This usually means you called setState() on an unmounted component. This is a no-op. Please check the code for the HintMessage component
  componentDidMount: function componentDidMount() {
    this.setState({ _isMounted: true });
  },
  componentWillUnmount: function componentWillUnmount() {
    this.setState({ _isMounted: false });
  },

  render: function render() {
    var validClass = this.state.valid ? 'form_hint valid' : 'form_hint invalid';
    var displayStyle = this.state.show ? { display: 'block' } : { display: 'none' };
    // console.debug('HintMessage', this.state.message);
    return React.createElement(
      'div',
      { className: validClass, style: displayStyle },
      React.createElement('i', { className: this.state.iconClass }),
      this.state.message
    );
  },
  onProgress: function onProgress(_message, autoFadeOut) {
    var self = this;
    if (self.state._isMounted) {
      self.setState({
        valid: true,
        show: true,
        iconClass: 'fa fa-spinner fa-spin',
        message: _message
      });
    }
  },
  onSuccess: function onSuccess(_message, autoFadeOut) {
    var self = this;
    if (self.state._isMounted) {
      self.setState({
        valid: true,
        show: true,
        iconClass: 'fa fa-check',
        message: _message
      });
      if (autoFadeOut) {
        setTimeout(function () {
          self.setState({
            valid: false,
            show: false,
            iconClass: '',
            message: ''
          });
        }, 1500);
      }
    }
  },
  onError: function onError(_message, autoFadeOut) {
    var self = this;
    if (self.state._isMounted) {
      self.setState({
        valid: false,
        show: true,
        iconClass: 'fa fa-exclamation-circle',
        message: _message
      });
      if (autoFadeOut) {
        setTimeout(function () {
          self.setState({
            valid: false,
            show: false,
            iconClass: '',
            message: ''
          });
        }, 1500);
      }
    }
  }
});

/**
 * Input qui embarque les events de mise à jour
 */
var TextField = React.createClass({
  displayName: 'TextField',

  getDefaultProps: function getDefaultProps() {
    return {
      type: 'text',
      rows: 8,
      mandatory: false
    };
  },

  propTypes: {
    name: React.PropTypes.string.isRequired,
    label: React.PropTypes.string,
    placeholder: React.PropTypes.string,
    rows: React.PropTypes.number,
    mandatory: React.PropTypes.bool
  },

  getInitialState: function getInitialState() {
    return {
      value: ''
    };
  },

  validate: function validate() {
    if (this.props.mandatory && this.state.value.trim() == '') {
      this._hint.onError('Ce champ est obligatoire');
      return false;
    }
    return true;
  },

  /**
  * mise à jour depuis l'exterieur du composant
  */
  setValue: function setValue(v) {
    this.setState({
      value: v
    });
  },

  /**
   *
   * On rend également un champ <input type="hidden"> pour enregistrement dans WP à la validation de la page
   * ce n'est pas nécessaire mais bon...par sécu / cohérence
   */
  render: function render() {
    var _this = this;

    return React.createElement(
      'div',
      null,
      this.props.type == 'text' && React.createElement(
        'div',
        { className: 'kz_form_field' },
        React.createElement(
          'label',
          { htmlFor: this.props.name },
          this.props.label,
          ' :'
        ),
        this.props.mandatory && React.createElement(
          'p',
          null,
          React.createElement('i', { className: 'fa fa-asterisk mandatory' }),
          'ce champ est obligatoire'
        ),
        React.createElement(HintMessage, { ref: function ref(c) {
            return _this._hint = c;
          } }),
        React.createElement('input', {
          type: 'text',
          name: this.props.name,
          value: this.state.value,
          onChange: this.onChange,
          placeholder: this.props.placeholder })
      ),
      this.props.type == 'textarea' && React.createElement(
        'div',
        { className: 'kz_form_field' },
        React.createElement(
          'label',
          { htmlFor: this.props.name },
          this.props.label,
          ' :'
        ),
        this.props.mandatory && React.createElement(
          'p',
          null,
          React.createElement('i', { className: 'fa fa-asterisk mandatory' }),
          'ce champ est obligatoire'
        ),
        React.createElement(HintMessage, { ref: function ref(c) {
            return _this._hint = c;
          } }),
        React.createElement('textarea', {
          name: this.props.name,
          value: this.state.value,
          onChange: this.onChange,
          placeholder: this.props.placeholder,
          rows: this.props.rows })
      )
    );
  },

  onChange: function onChange(e) {
    this.setState({ value: e.target.value });
  }
});

/**
 * Une <input type="checkbox"> wrappé avec Label + <li></li> 
 * Adaptée aux forms Kidzou inclus dans un <ul>
 */
var Checkbox = React.createClass({
  displayName: 'Checkbox',

  getInitialState: function getInitialState() {
    return {
      isChecked: this.props.isChecked || false
    };
  },

  /**
   *
   * On rend également un champ <input type="hidden"> pour enregistrement dans WP à la validation de la page
   * ce n'est pas nécessaire mais bon...par sécu / cohérence
   */
  render: function render() {
    var _this2 = this;

    return React.createElement(
      'li',
      null,
      React.createElement(
        'label',
        { htmlFor: this.props.name },
        this.props.label
      ),
      React.createElement('input', {
        type: 'checkbox',
        name: this.props.name,
        checked: this.state.isChecked,
        onChange: this.onChange }),
      React.createElement(HintMessage, { ref: function ref(c) {
          return _this2._hintMessage = c;
        } })
    );
  },

  onChange: function onChange() {
    var self = this;
    self.setState({ isChecked: !self.state.isChecked });
    self.props.change.call(self, self.state.isChecked, function (res) {
      var msg = res ? res.msg || 'Enregistrement' : 'Enregistrement';
      self._hintMessage.onProgress(msg);
    }, function (data) {
      var msg = data ? data.msg || 'Enregistré' : 'Enregistré';
      self._hintMessage.onSuccess(msg);
    }, function (err) {
      var msg = err ? err.msg || 'Impossible d\'enregistrer' : 'Impossible d\'enregistrer';
      self._hintMessage.onError(msg);
    });
  }
});
/**
 * Une <input type="checkbox"> managé dans un Group <CheckboxGroup/>
 *
 */
var CheckBoxItem = React.createClass({
  displayName: 'CheckBoxItem',

  propTypes: {
    value: React.PropTypes.string.isRequired,
    name: React.PropTypes.string.isRequired
  },
  getInitialState: function getInitialState() {
    return {
      checked: this.props.checked || false
    };
  },

  render: function render() {
    return React.createElement(
      'label',
      { htlmFor: this.props.id },
      React.createElement('input', { type: 'checkbox',
        name: this.props.name,
        value: this.props.value,
        checked: this.state.checked,
        onClick: this.handleClick }),
      this.props.label,
      ' ',
      React.createElement('br', null)
    );
  },

  handleClick: function handleClick(event) {
    // Should use this to set parent's state via a callback func.  Then the
    // change to the parent's state will generate new props to be passed down
    // to the children in the render().
    event.stopPropagation();
    var self = this;
    self.setState({ checked: !self.state.checked }, function () {
      self.props.callBackOnChange();
    });
  }
});

/**
 * Un ensemble de <CheckBoxItem />
 *
 */
var CheckboxGroup = React.createClass({
  displayName: 'CheckboxGroup',

  propTypes: {
    values: React.PropTypes.object.isRequired
  },
  _checkBoxItems: [],
  getInitialState: function getInitialState() {
    return {
      values: this.props.values
    };
  },
  render: function render() {
    var rows = [];
    var self = this;
    self.state.values.items.forEach(function (item, index) {
      var key = '_CheckBoxItem_' + index;
      rows.push(React.createElement(CheckBoxItem, {
        name: self.props.name,
        value: item.value,
        label: item.label,
        checked: item.checked,
        index: index,
        key: key,
        callBackOnChange: self.handleClick.bind(self, index),
        ref: function ref(c) {
          return self._checkBoxItems[index] = c;
        } })); //callBackOnChange={self.handleChange}
    });
    return React.createElement(
      'div',
      null,
      this.state.values.items.length > 0 && React.createElement(
        'div',
        null,
        rows
      )
    );
  },

  /**
   * Déclenchée à l'update d'un CheckboxItem par la prop 'callBackOnChange'
   */
  handleClick: function handleClick(i) {

    var self = this;
    var newValues = self.state.values;
    newValues.items[i].checked = self._checkBoxItems[i].state.checked;

    function getCheckedValues(_items) {
      // Get an array of state objects that are checked
      var checkedObjArray = [];
      checkedObjArray = _.filter(_items, function (obj) {
        return obj.checked;
      });
      // Get an array of value properties for the checked objects
      var checkedArray = _.map(checkedObjArray, function (obj) {
        return obj.value;
      });
      return checkedArray;
    }

    self.setState({ values: newValues }, function () {
      self.props.onUpdate(getCheckedValues(self.state.values.items));
    });
  }
});

/**
 * Une liste toute simple <ul>
 */
var List = React.createClass({
  displayName: 'List',

  render: function render() {
    var rows = [];
    this.props.items.forEach(function (it, index) {
      var key = 'item_' + index;
      rows.push(React.createElement(ListItem, { label: it, index: index, key: key }));
    });
    return React.createElement(
      'ul',
      null,
      this.props.items.length > 0 && React.createElement(
        'div',
        null,
        React.createElement(
          'h4',
          null,
          this.props.title
        ),
        React.createElement(
          'p',
          null,
          rows
        )
      )
    );
  }
});

/** 
 * Un element <li>
 */
var ListItem = React.createClass({
  displayName: 'ListItem',

  render: function render() {
    return React.createElement(
      'li',
      null,
      this.props.label
    );
  }
});

/**
 *
 * Composant de saisie de dates
 */
var EventDates = React.createClass({
  displayName: 'EventDates',

  getDefaultProps: function getDefaultProps() {
    return {};
  },

  getInitialState: function getInitialState() {
    var self = this;
    return {
      from: null,
      to: null
    };
  },

  render: function render() {

    var self = this;
    var modifiers = {
      selected: function selected(day) {
        return DateUtils.isDayInRange(day, self.state);
      }
    };

    //passer les hidden pour soumission backend de la page (récupération par WP par secu)
    return React.createElement(
      'div',
      null,
      this.state.from == null && this.state.to == null && React.createElement(
        'p',
        null,
        'Sélectionnez le ',
        React.createElement(
          'strong',
          null,
          'jour de début'
        ),
        '.'
      ),
      this.state.from != null && this.state.to == null && React.createElement(
        'p',
        null,
        'Sélectionnez le ',
        React.createElement(
          'strong',
          null,
          'jour de fin'
        ),
        ', ',
        React.createElement(
          'strong',
          null,
          'ou laissez tel quel si l\'événement se déroule sur un seul jour'
        ),
        '.'
      ),
      React.createElement(
        'div',
        { className: 'kz_form_field' },
        React.createElement(DayPicker, {
          localeUtils: DayPickerLocaleUtils,
          locale: 'fr',
          numberOfMonths: 2,
          onDayClick: this.handleDayClick,
          modifiers: modifiers })
      ),
      React.createElement(
        'div',
        { className: 'kz_form_field' },
        this.state.from !== null && this.state.to !== null && !DateUtils.isSameDay(this.state.from, this.state.to) && React.createElement(
          'div',
          { onClick: this.onResetDay, className: 'kz_box' },
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
                'L\'événement se déroule'
              ),
              React.createElement('br', null),
              React.createElement(
                'span',
                null,
                'du ',
                moment(this.state.from).format("L"),
                ' au ',
                moment(this.state.to).format("L")
              )
            )
          )
        ),
        this.state.from !== null && this.state.to == null || this.state.from !== null && this.state.to !== null && DateUtils.isSameDay(this.state.from, this.state.to) && React.createElement(
          'div',
          { onClick: this.onResetDay, className: 'kz_box' },
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
                'L\'événement se déroule'
              ),
              React.createElement('br', null),
              React.createElement(
                'span',
                null,
                'le ',
                moment(this.state.from).format("L"),
                ' '
              )
            )
          )
        )
      )
    );
  },

  /**
   * Choix du range de dates de l'evenement
   */
  handleDayClick: function handleDayClick(e, day) {
    var self = this;
    var range = DateUtils.addDayToRange(day, self.state);
    self.setState({
      from: range.from,
      to: range.to
    }, function () {
      // self.saveEvent();
    });
  },

  /** 
   * Remise à zeo du range de date
   */
  onResetDay: function onResetDay(e) {
    e.preventDefault();
    this.setState({
      from: null,
      to: null

    }, function () {
      // this.saveEvent();
    });
  }

});
