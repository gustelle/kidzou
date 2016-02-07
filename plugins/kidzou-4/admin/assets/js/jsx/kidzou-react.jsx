
var HintMessage = React.createClass({
    getInitialState: function() {
      return {
        valid : false,
        show  : false,
        iconClass  : '',
        message : '',
      }
    },
    render: function() {
      var validClass = (this.state.valid ? 'form_hint valid' : 'form_hint invalid');
      var displayStyle = (this.state.show ? {display:'inline'} : {display:'none'});
      return (
        <span className={validClass} style={displayStyle}>
          <i className={this.state.iconClass}></i>{this.state.message}
        </span>
      );
    },
    onProgress: function(_message) {
      var self = this;
      self.setState({
          valid : true,
          show  : true,
          iconClass  : 'fa fa-spinner fa-spin',
          message : _message,
        });
    },
    onSuccess: function(_message) {
      var self = this;
      self.setState({
          valid : true,
          show  : true,
          iconClass  : 'fa fa-check',
          message : _message,
        });
        setTimeout(function(){
          self.setState({
            valid : false,
            show  : false,
            iconClass  : '',
            message : ''
          });
        }, 1500);
    },
    onError: function(_message) {
      var self = this;
      self.setState({
          valid : false,
          show  : true,
          iconClass  : 'fa fa-exclamation-circle',
          message : _message,
        });
        setTimeout(function(){
          self.setState({
            valid : false,
            show  : false,
            iconClass  : '',
            message : ''
          });
        }, 1500);
    }
});

/**
 * Une <input type="text"> wrappé avec Label + <li></li> 
 * Adaptée aux forms Kidzou inclus dans un <ul>
 */
var Field = React.createClass({

    getInitialState: function() {
      return {
        valid : true,
        show  : false,
        icon  : '',
        message : '',
        inputValue : this.props.text,
        inputPrefix : (this.props.inputPrefix || '')
      }
    },

    /**
     *
     * On rend également un champ <input type="hidden"> pour enregistrement dans WP à la validation de la page
     * ce n'est pas nécessaire mais bon...par sécu / cohérence
     */
    render: function() {
      var tabIndex = (this.props.tabIndex || 0);
      var inputName = this.state.inputPrefix + this.props.updateParam;
      var text = '' + this.props.text; //forcer le type string 
      return (
        <li>
           <span className="editableLabel">{this.props.label}</span>
            <InlineEdit
                activeClassName="editing"
                validate={this.customValidate}
                text={text}
                paramName={this.props.updateParam}
                change={this.onEdit}
                className="editable"
                staticElement="div"
                tabIndex={tabIndex} />
                <HintMessage ref={(c) => this._hintMessage = c} />
            <input type="hidden" value={this.state.inputValue} name={inputName} /> 
          </li>
      );
    },

    /**
     * Appelle la fonction passée dans les props (this.props)
     * Rien à voir avec le param "change" de <InlineEdit>
     */
    onEdit : function(_data) {

      var self = this;
      self.props.change.call(this, _data, 
        function(res){
          var msg = (res ? res.msg || 'Enregistrement' : 'Enregistrement');
          self._hintMessage.onProgress(msg);
        },
        function(data){
          var msg = (data ? data.msg || 'Enregistré' : 'Enregistré');
          self.setState({
            inputValue : _data[self.props.updateParam]
          });
          self._hintMessage.onSuccess(msg);
        },
        function(err){
          var msg  = (err ? err.msg || 'Impossible d\'enregistrer' : 'Impossible d\'enregistrer');
          self._hintMessage.onError(msg);
        });
    },

    /**
     * validation des données du champ
     */
    customValidate : function(_text) {

      var self = this;
      if (typeof self.props.validate=='undefined') return true;
      return self.props.validate.call(this, _text);
    }
});

/**
 * Une <input type="checkbox"> wrappé avec Label + <li></li> 
 * Adaptée aux forms Kidzou inclus dans un <ul>
 */
var Checkbox = React.createClass({

    getInitialState: function() {
      return {
        isChecked : (this.props.isChecked  || false)
      }
    },

    /**
     *
     * On rend également un champ <input type="hidden"> pour enregistrement dans WP à la validation de la page
     * ce n'est pas nécessaire mais bon...par sécu / cohérence
     */
    render: function() {
      return (
        <li>
          <label htmlFor={this.props.name}>{this.props.label}</label>
          <input 
              type="checkbox" 
              name={this.props.name} 
              checked={this.state.isChecked}
              onChange={this.onChange}/>
          <HintMessage ref={(c) => this._hintMessage = c} />
        </li>
      );
    },

    onChange : function() {
      var self = this;
      self.setState({isChecked: !self.state.isChecked});
      self.props.change.call(self, self.state.isChecked,
        function(res){
          var msg = (res ? res.msg || 'Enregistrement' : 'Enregistrement');
          self._hintMessage.onProgress(msg);
        },
        function(data){
          var msg = (data ? data.msg || 'Enregistré' : 'Enregistré');
          self._hintMessage.onSuccess(msg);
        },
        function(err){
          var msg  = (err ? err.msg || 'Impossible d\'enregistrer' : 'Impossible d\'enregistrer');
          self._hintMessage.onError(msg);
        });
    }
});

/**
 * Une <input type="checkbox"> managé dans un Group <CheckboxGroup/>
 *
 */
var CheckBoxItem = React.createClass({
  
    render: function() {
      // console.debug('SimpleCheckBox', this.props);
        return (
            <label htlmFor={this.props.id}>
                <input type="checkbox"
                    name={this.props.name}
                    id={this.props.id}
                    value={this.props.value}
                    checked={this.props.checked}
                    onChange={this.handleChange} />
                {this.props.label} <br />
            </label>
        );
    },

    handleChange: function(event) {
        // Should use this to set parent's state via a callback func.  Then the
        // change to the parent's state will generate new props to be passed down
        // to the children in the render().
        event.stopPropagation();
        this.props.callBackOnChange(this, event.target.checked);
    }  
});

/**
 * Un ensemble de <SimpleCheckBox />
 *
 */
var CheckboxGroup = React.createClass({
    propTypes: {
        values: React.PropTypes.object.isRequired,
    },
    getInitialState: function () {
      return {
        values : this.props.values
      };
    },
    render: function () {
        var rows = [];
        var self = this;
        self.props.values.items.forEach(function(item, index) {
          var key = '_CheckBoxItem_' + index;
          rows.push(<CheckBoxItem 
                      name={self.props.name} 
                      value={item.value} 
                      label={item.label} 
                      checked={item.checked} 
                      index={index} 
                      key={key} 
                      callBackOnChange={self.handleChange} />);
        });
        return (
            <div>
                { this.props.values.items.length>0 &&
                  <div>
                    {rows}
                  </div>
                }
            </div>
        );
    },
    /**
     * Déclenchée à l'update d'un CheckboxItem
     * Le CheckboxItem est passé en tant que 'componentChanged'
     * @param newState : boolean (checked)
     */
    handleChange: function(componentChanged, newState) {
        // Callback function passed from CheckboxFieldGroup (this component) to each of the
        // CheckboxField child components.  (See renderChoices func).
        var idx = -1;
        var stateMemberToChange = _.find(this.state.values.items, function(obj, num) {
            idx = num;
            return obj.value === componentChanged.props.value;
        });

        // Threw an error when I tried to update and indiviudal member of the state array/object.  So, take a copy
        // of the state, update the copy and do a setState() on the whole thing.  Using setState() rather than
        // replaceState() should be more efficient here.
        var newStateValuesArray = this.state.values.items;
        newStateValuesArray[idx].checked = newState;
        this.setState({
          values: {
            name : this.state.values.name,
            items : newStateValuesArray
          }
        });  // Automatically triggers render() !!
    },
    getCheckedValues: function() {
        // Get an array of state objects that are checked
        var checkedObjArray = [];
        checkedObjArray = _.filter(this.state.values.items, function(obj){
            return obj.checked;
        });

        // Get an array of value properties for the checked objects
        var checkedArray = _.map(checkedObjArray, function(obj){
            return obj.value;
        });
        return checkedArray;
        // console.log("SimpleCheckBox.getCheckedValues() = " + checkedArray);
    },
    componentDidMount: function() {
        // this.getCheckedValues();
    },
    componentWillUpdate: function(nextProps, nextState) {
      // console.debug(JSON.stringify(this.state.values.items), JSON.stringify(nextState.values.items));
    },
    componentDidUpdate: function() {
        if (typeof this.props.onUpdate==='function') {
          this.props.onUpdate(this.getCheckedValues());
        }
    }
});

/**
 * Une liste toute simple <ul>
 */
var List = React.createClass({
    render: function() {
      var rows = [];
      this.props.items.forEach(function(it, index) {
        // console.debug('proposal', proposal);
        var key = 'item_' + index;
        rows.push(<ListItem label={it} index={index} key={key} />);
      });
      // var isProposal = (this.props.proposals.length>0);
      return (
        <ul>
          { this.props.items.length>0 &&
            <div>
              <h4>{this.props.title}</h4>
              <p>{rows}</p>
            </div>
          }
        </ul>
      );
    }
});

/** 
 * Un element <li>
 */
var ListItem = React.createClass({
    render: function() {
      return (
        <li>
          {this.props.label}
        </li>
      );
    }
});


