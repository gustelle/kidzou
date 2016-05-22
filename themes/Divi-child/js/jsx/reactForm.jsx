


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
      var displayStyle = (this.state.show ? {display:'block'} : {display:'none'});
      // console.debug('HintMessage', this.state.message);
      return (
        <div className={validClass} style={displayStyle}>
          <i className={this.state.iconClass}></i>{this.state.message}
        </div>
      );
    },
    onProgress: function(_message, autoFadeOut) {
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
    onSuccess: function(_message, autoFadeOut) {
      var self = this;
      if (self.state._isMounted) {
        self.setState({
            valid : true,
            show  : true,
            iconClass  : 'fa fa-check',
            message : _message,
          });
        if (autoFadeOut) {
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
    },
    onError: function(_message, autoFadeOut) {
      var self = this;
      if (self.state._isMounted) {
        self.setState({
            valid : false,
            show  : true,
            iconClass  : 'fa fa-exclamation-circle',
            message : _message,
          });
        if (autoFadeOut) {
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
    }
});

/**
 * Input qui embarque les events de mise à jour
 */
var TextField = React.createClass({

  getDefaultProps: function() {
    return {
      type: 'text',
      rows : 8,
      mandatory : false
    };
  },

  propTypes : {
    name  : React.PropTypes.string.isRequired,
    label : React.PropTypes.string,
    placeholder : React.PropTypes.string,
    rows : React.PropTypes.number,
    mandatory : React.PropTypes.bool
  },

  getInitialState: function() {
    return {
      value : ''
    }
  },

  validate : function() {
    if (this.props.mandatory && this.state.value.trim()=='') {
      this._hint.onError('Ce champ est obligatoire'); 
      return false;
    }
    return true;
  },

  /**
  * mise à jour depuis l'exterieur du composant
  */
  setValue : function(v) {
    this.setState({
      value:v
    });
  },

  /**
   *
   * On rend également un champ <input type="hidden"> pour enregistrement dans WP à la validation de la page
   * ce n'est pas nécessaire mais bon...par sécu / cohérence
   */
  render: function() {
    return (
      <div>
      {
        (this.props.type=='text') &&
        <div className="kz_form_field">
          <label htmlFor={this.props.name}>{this.props.label} :</label>
          { this.props.mandatory &&
            <p><i className="fa fa-asterisk mandatory"></i>ce champ est obligatoire</p>
          }
          <HintMessage ref={(c) => this._hint = c} />
          <input 
              type="text"
               name={this.props.name}
               value={this.state.value}
               onChange={this.onChange}
              placeholder={this.props.placeholder} />
        </div>  
      }
      {
        (this.props.type=='textarea') &&
        <div className="kz_form_field">
          <label htmlFor={this.props.name}>{this.props.label} :</label>
          { this.props.mandatory &&
            <p><i className="fa fa-asterisk mandatory"></i>ce champ est obligatoire</p>
          }
          <HintMessage ref={(c) => this._hint = c} />
          <textarea 
                name={this.props.name}
                value={this.state.value}
                onChange={this.onChange}
                placeholder={this.props.placeholder}
                rows={this.props.rows} />
        </div>  
      }
      </div>
    );
  },

  onChange : function(e) {
    this.setState({value: e.target.value});
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

/**
 *
 * Composant de saisie de dates
 */
var EventDates = React.createClass({

    getDefaultProps: function () {
      return {
      };
    },

    getInitialState: function() {
        var self = this;
        return {
          from: null,
          to: null,
        };
      },


    render: function() {

      var self = this;
      const modifiers = {
        selected: day => DateUtils.isDayInRange(day, self.state)
      };
        
      //passer les hidden pour soumission backend de la page (récupération par WP par secu)
      return (

        <div>

          { (this.state.from==null && this.state.to==null) && 
            <p>S&eacute;lectionnez le <strong>jour de d&eacute;but</strong>.</p> 
          }
          { (this.state.from!=null && this.state.to==null) && 
            <p>S&eacute;lectionnez le <strong>jour de fin</strong>, <strong>ou laissez tel quel si l&apos;&eacute;v&eacute;nement se d&eacute;roule sur un seul jour</strong>.</p> 
          }

          <div className="kz_form_field">
              <DayPicker
                localeUtils={ DayPickerLocaleUtils } 
                locale="fr"
                numberOfMonths={ 2 }
                onDayClick={ this.handleDayClick }
                modifiers={ modifiers } />
          </div>
          
          <div className="kz_form_field">
            
            { (this.state.from!==null  && this.state.to!==null && !DateUtils.isSameDay(this.state.from, this.state.to)) &&
              
              <div onClick={this.onResetDay} className="kz_box">
                <div style={{cursor:'pointer'}}>
                  <div style={{width:'auto', verticalAlign:'middle', float:'left', padding:'10px'}}><i className="fa fa-check fa-2x"></i></div>
                  <div style={{width:'auto', verticalAlign:'middle', padding:'10px', backgroundColor:'#94e0fc'}}>
                    <span style={{fontSize:'1.2em'}}>
                      L&apos;&eacute;v&eacute;nement se d&eacute;roule 
                    </span><br/>
                    <span>du { moment(this.state.from).format("L") } au { moment(this.state.to).format("L") }</span>
                  </div>
                </div>
              </div>
            }
            { (this.state.from!==null && this.state.to==null) || (this.state.from!==null  && this.state.to!==null && DateUtils.isSameDay(this.state.from, this.state.to)) &&
              
              <div onClick={this.onResetDay} className="kz_box" >
                <div style={{cursor:'pointer'}}>
                  <div style={{width:'auto', verticalAlign:'middle', float:'left', padding:'10px'}}><i className="fa fa-check fa-2x"></i></div>
                  <div style={{width:'auto', verticalAlign:'middle', padding:'10px', backgroundColor:'#94e0fc'}}>
                    <span style={{fontSize:'1.2em'}}>
                      L&apos;&eacute;v&eacute;nement se d&eacute;roule 
                    </span><br/>
                    <span>le { moment(this.state.from).format("L") } </span>
                  </div>
                </div>
              </div>
            }   
          </div>

        </div>

      );
    },


    /**
     * Choix du range de dates de l'evenement
     */
    handleDayClick:function(e,day) {
      var self = this;
      const range = DateUtils.addDayToRange(day, self.state);
        self.setState({
          from: range.from,
          to : range.to
        }, function(){
        // self.saveEvent();
      });
    },


    /** 
     * Remise à zeo du range de date
     */
    onResetDay:function(e) {
      e.preventDefault();
        this.setState({
          from: null,
          to: null,

        }, function(){
          // this.saveEvent();
        });
    },

});