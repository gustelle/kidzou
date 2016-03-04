
/**
 * Un <HintMessage /> est un message diffusé a coté d'un champ mis à jour en Ajax
 * Il peut etre appelé de 3 manieres : 
 * * onProgress
 * * onSuccess (effacé au bout de 1,5s)
 * * onError (effacé au bout de 1,5s)
 *
 */
var HintMessage = React.createClass({

    getDefaultProps: function () {
      return {
        // _isMounted : false
      };
    },

    getInitialState: function() {
      return {
        valid : false,
        show  : false,
        iconClass  : '',
        message : '',
        _isMounted : false,
      }
    },

    //pour éviter erreur setState(...): Can only update a mounted or mounting component. This usually means you called setState() on an unmounted component. This is a no-op. Please check the code for the HintMessage component
    componentDidMount: function() {
      this.setState({_isMounted:true});
    },
    componentWillUnmount: function() {
      this.setState({_isMounted:false});
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
      if (self.state._isMounted) {
        self.setState({
          valid : true,
          show  : true,
          iconClass  : 'fa fa-spinner fa-spin',
          message : _message,
        });
      }
    },
    onSuccess: function(_message) {
      var self = this;
      if (self.state._isMounted) {
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
      }
    },
    onError: function(_message) {
      var self = this;
      if (self.state._isMounted) {
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
      // console.debug('render field',this.props.updateParam, text);
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

    propTypes: {
        value : React.PropTypes.string.isRequired,
        name : React.PropTypes.string.isRequired,
    },
    getInitialState: function () {
      return {
        checked : (this.props.checked || false)
      };
    },
  
    render: function() {
        return (
            <label htlmFor={this.props.id}>
                <input type="checkbox"
                    name={this.props.name}
                    value={this.props.value}
                    checked={this.state.checked} 
                    onClick={this.handleClick} />
                {this.props.label} <br />
            </label>
        );
    },

    handleClick: function(event) {
        // Should use this to set parent's state via a callback func.  Then the
        // change to the parent's state will generate new props to be passed down
        // to the children in the render().
        event.stopPropagation();
        var self = this;
        self.setState({checked : !self.state.checked}, function(){
          self.props.callBackOnChange();
        });
    }  
});

/**
 * Un ensemble de <CheckBoxItem />
 *
 */
var CheckboxGroup = React.createClass({
    propTypes: {
        values: React.PropTypes.object.isRequired,
    },
    _checkBoxItems : [],
    getInitialState: function () {
      return {
        values : this.props.values
      };
    },
    render: function () {
        var rows = [];
        var self = this;
        self.state.values.items.forEach(function(item, index) {
          var key = '_CheckBoxItem_' + index;
          rows.push(<CheckBoxItem 
                      name={self.props.name} 
                      value={item.value} 
                      label={item.label} 
                      checked={item.checked} 
                      index={index} 
                      key={key} 
                      callBackOnChange={self.handleClick.bind(self, index)} 
                      ref={(c) => self._checkBoxItems[index] = c} />); //callBackOnChange={self.handleChange}
        });
        return (
            <div>
                { this.state.values.items.length>0 &&
                  <div>
                    {rows}
                  </div>
                }
            </div>
        );
    },

    /**
     * Déclenchée à l'update d'un CheckboxItem par la prop 'callBackOnChange'
     */
    handleClick: function(i) {
      
      var self = this;
      var newValues = self.state.values;
      newValues.items[i].checked = self._checkBoxItems[i].state.checked;

      function getCheckedValues(_items){
          // Get an array of state objects that are checked
          var checkedObjArray = [];
          checkedObjArray = _.filter(_items, function(obj){
              return obj.checked;
          });
          // Get an array of value properties for the checked objects
          var checkedArray = _.map(checkedObjArray, function(obj){
              return obj.value;
          });
          return checkedArray;
      }

      self.setState({values : newValues}, function(){
        self.props.onUpdate(getCheckedValues(self.state.values.items));
      });

    },   
});

/**
 * Une liste toute simple <ul>
 */
var List = React.createClass({
    render: function() {
      var rows = [];
      this.props.items.forEach(function(it, index) {
        var key = 'item_' + index;
        rows.push(<ListItem label={it} index={index} key={key} />);
      });
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


