'use strict';

function _objectWithoutProperties(obj, keys) { var target = {}; for (var i in obj) { if (keys.indexOf(i) >= 0) continue; if (!Object.prototype.hasOwnProperty.call(obj, i)) continue; target[i] = obj[i]; } return target; }

var STATE = {
  LOADING: 'loading',
  DISABLED: 'disabled',
  SUCCESS: 'success',
  ERROR: 'error',
  NOTHING: ''
};

var ProgressButton = React.createClass({
  displayName: 'ProgressButton',

  propTypes: {
    classNamespace: React.PropTypes.string,
    durationError: React.PropTypes.number,
    durationSuccess: React.PropTypes.number,
    form: React.PropTypes.string,
    onClick: React.PropTypes.func,
    onError: React.PropTypes.func,
    onSuccess: React.PropTypes.func,
    state: React.PropTypes.oneOf(Object.keys(STATE).map(function (k) {
      return STATE[k];
    })),
    type: React.PropTypes.string,
    shouldAllowClickOnLoading: React.PropTypes.bool
  },

  getDefaultProps: function getDefaultProps() {
    return {
      classNamespace: 'pb-',
      durationError: 1200,
      durationSuccess: 500,
      onClick: function onClick() {},
      onError: function onError() {},
      onSuccess: function onSuccess() {},

      shouldAllowClickOnLoading: false
    };
  },

  getInitialState: function getInitialState() {
    return {
      currentState: this.props.state || STATE.NOTHING
    };
  },

  componentWillReceiveProps: function componentWillReceiveProps(nextProps) {
    if (nextProps.state === this.props.state) {
      return;
    }
    switch (nextProps.state) {
      case STATE.SUCCESS:
        this.success();
        return;
      case STATE.ERROR:
        this.error();
        return;
      case STATE.LOADING:
        this.loading();
        return;
      case STATE.DISABLED:
        this.disable();
        return;
      case STATE.NOTHING:
        this.notLoading();
        return;
      default:
        return;
    }
  },

  componentWillUnmount: function componentWillUnmount() {
    clearTimeout(this._timeout);
  },

  render: function render() {
    var _props = this.props;
    var className = _props.className;
    var classNamespace = _props.classNamespace;
    var children = _props.children;
    var type = _props.type;
    var form = _props.form;
    var durationError = _props.durationError;
    var durationSuccess = _props.durationSuccess;
    var onClick = _props.onClick;
    var onError = _props.onError;
    var state = _props.state;
    var shouldAllowClickOnLoading = _props.shouldAllowClickOnLoading;

    var containerProps = _objectWithoutProperties(_props, ['className', 'classNamespace', 'children', 'type', 'form', 'durationError', 'durationSuccess', 'onClick', 'onError', 'state', 'shouldAllowClickOnLoading']);

    containerProps.className = classNamespace + 'container ' + this.state.currentState + ' ' + className;
    containerProps.onClick = this.handleClick;
    return React.createElement(
      'div',
      containerProps,
      React.createElement(
        'button',
        { type: type, form: form, className: classNamespace + 'button' },
        React.createElement(
          'span',
          null,
          children
        ),
        React.createElement(
          'svg',
          { className: classNamespace + 'progress-circle', viewBox: '0 0 41 41' },
          React.createElement('path', { d: 'M38,20.5 C38,30.1685093 30.1685093,38 20.5,38' })
        ),
        React.createElement(
          'svg',
          { className: classNamespace + 'checkmark', viewBox: '0 0 70 70' },
          React.createElement('path', { d: 'm31.5,46.5l15.3,-23.2' }),
          React.createElement('path', { d: 'm31.5,46.5l-8.5,-7.1' })
        ),
        React.createElement(
          'svg',
          { className: classNamespace + 'cross', viewBox: '0 0 70 70' },
          React.createElement('path', { d: 'm35,35l-9.3,-9.3' }),
          React.createElement('path', { d: 'm35,35l9.3,9.3' }),
          React.createElement('path', { d: 'm35,35l-9.3,9.3' }),
          React.createElement('path', { d: 'm35,35l9.3,-9.3' })
        )
      )
    );
  },

  handleClick: function handleClick(e) {
    if ((this.props.shouldAllowClickOnLoading || this.state.currentState !== 'loading') && this.state.currentState !== 'disabled') {
      var ret = this.props.onClick(e);
      this.loading(ret);
    } else {
      e.preventDefault();
    }
  },

  loading: function loading(promise) {
    var _this = this;

    this.setState({ currentState: 'loading' });
    if (promise && promise.then && promise.catch) {
      promise.then(function () {
        _this.success();
      }).catch(function () {
        _this.error();
      });
    }
  },

  notLoading: function notLoading() {
    this.setState({ currentState: STATE.NOTHING });
  },

  enable: function enable() {
    this.setState({ currentState: STATE.NOTHING });
  },

  disable: function disable() {
    this.setState({ currentState: STATE.DISABLED });
  },

  success: function success(callback, dontRemove) {
    var _this2 = this;

    this.setState({ currentState: STATE.SUCCESS });
    this._timeout = setTimeout(function () {
      callback = callback || _this2.props.onSuccess;
      if (typeof callback === 'function') {
        callback();
      }
      if (dontRemove === true) {
        return;
      }
      _this2.setState({ currentState: STATE.NOTHING });
    }, this.props.durationSuccess);
  },

  error: function error(callback) {
    var _this3 = this;

    this.setState({ currentState: STATE.ERROR });
    this._timeout = setTimeout(function () {
      callback = callback || _this3.props.onError;
      if (typeof callback === 'function') {
        callback();
      }
      _this3.setState({ currentState: STATE.NOTHING });
    }, this.props.durationError);
  }
});
