'use strict';

var _typeof = typeof Symbol === "function" && typeof Symbol.iterator === "symbol" ? function (obj) { return typeof obj; } : function (obj) { return obj && typeof Symbol === "function" && obj.constructor === Symbol ? "symbol" : typeof obj; };

var _extends = Object.assign || function (target) { for (var i = 1; i < arguments.length; i++) { var source = arguments[i]; for (var key in source) { if (Object.prototype.hasOwnProperty.call(source, key)) { target[key] = source[key]; } } } return target; };

var _createClass = function () { function defineProperties(target, props) { for (var i = 0; i < props.length; i++) { var descriptor = props[i]; descriptor.enumerable = descriptor.enumerable || false; descriptor.configurable = true; if ("value" in descriptor) descriptor.writable = true; Object.defineProperty(target, descriptor.key, descriptor); } } return function (Constructor, protoProps, staticProps) { if (protoProps) defineProperties(Constructor.prototype, protoProps); if (staticProps) defineProperties(Constructor, staticProps); return Constructor; }; }();

function _objectWithoutProperties(obj, keys) { var target = {}; for (var i in obj) { if (keys.indexOf(i) >= 0) continue; if (!Object.prototype.hasOwnProperty.call(obj, i)) continue; target[i] = obj[i]; } return target; }

function _classCallCheck(instance, Constructor) { if (!(instance instanceof Constructor)) { throw new TypeError("Cannot call a class as a function"); } }

function _possibleConstructorReturn(self, call) { if (!self) { throw new ReferenceError("this hasn't been initialised - super() hasn't been called"); } return call && (typeof call === "object" || typeof call === "function") ? call : self; }

function _inherits(subClass, superClass) { if (typeof superClass !== "function" && superClass !== null) { throw new TypeError("Super expression must either be null or a function, not " + typeof superClass); } subClass.prototype = Object.create(superClass && superClass.prototype, { constructor: { value: subClass, enumerable: false, writable: true, configurable: true } }); if (superClass) Object.setPrototypeOf ? Object.setPrototypeOf(subClass, superClass) : subClass.__proto__ = superClass; }

// import accepts from 'attr-accept';
// import React from 'react';

var supportMultiple = typeof document !== 'undefined' && document && document.createElement ? 'multiple' in document.createElement('input') : true;

var Dropzone = function (_React$Component) {
  _inherits(Dropzone, _React$Component);

  function Dropzone(props, context) {
    _classCallCheck(this, Dropzone);

    var _this = _possibleConstructorReturn(this, Object.getPrototypeOf(Dropzone).call(this, props, context));

    _this.onClick = _this.onClick.bind(_this);
    _this.onDragEnter = _this.onDragEnter.bind(_this);
    _this.onDragLeave = _this.onDragLeave.bind(_this);
    _this.onDragOver = _this.onDragOver.bind(_this);
    _this.onDrop = _this.onDrop.bind(_this);

    _this.state = {
      isDragActive: false
    };
    return _this;
  }

  _createClass(Dropzone, [{
    key: 'componentDidMount',
    value: function componentDidMount() {
      this.enterCounter = 0;
    }
  }, {
    key: 'onDragEnter',
    value: function onDragEnter(e) {
      e.preventDefault();

      // Count the dropzone and any children that are entered.
      ++this.enterCounter;

      // This is tricky. During the drag even the dataTransfer.files is null
      // But Chrome implements some drag store, which is accesible via dataTransfer.items
      var dataTransferItems = e.dataTransfer && e.dataTransfer.items ? e.dataTransfer.items : [];

      // Now we need to convert the DataTransferList to Array
      var allFilesAccepted = this.allFilesAccepted(Array.prototype.slice.call(dataTransferItems));

      this.setState({
        isDragActive: allFilesAccepted,
        isDragReject: !allFilesAccepted
      });

      if (this.props.onDragEnter) {
        this.props.onDragEnter.call(this, e);
      }
    }
  }, {
    key: 'onDragOver',
    value: function onDragOver(e) {
      e.preventDefault();
      e.stopPropagation();
      return false;
    }
  }, {
    key: 'onDragLeave',
    value: function onDragLeave(e) {
      e.preventDefault();

      // Only deactivate once the dropzone and all children was left.
      if (--this.enterCounter > 0) {
        return;
      }

      this.setState({
        isDragActive: false,
        isDragReject: false
      });

      if (this.props.onDragLeave) {
        this.props.onDragLeave.call(this, e);
      }
    }
  }, {
    key: 'onDrop',
    value: function onDrop(e) {
      e.preventDefault();

      // Reset the counter along with the drag on a drop.
      this.enterCounter = 0;

      this.setState({
        isDragActive: false,
        isDragReject: false
      });

      var droppedFiles = e.dataTransfer ? e.dataTransfer.files : e.target.files;
      var max = this.props.multiple ? droppedFiles.length : Math.min(droppedFiles.length, 1);
      var files = [];

      for (var i = 0; i < max; i++) {
        var file = droppedFiles[i];
        // We might want to disable the preview creation to support big files
        if (!this.props.disablePreview) {
          file.preview = window.URL.createObjectURL(file);
        }
        files.push(file);
      }

      if (this.props.onDrop) {
        this.props.onDrop.call(this, files, e);
      }

      if (this.allFilesAccepted(files)) {
        if (this.props.onDropAccepted) {
          this.props.onDropAccepted.call(this, files, e);
        }
      } else {
        if (this.props.onDropRejected) {
          this.props.onDropRejected.call(this, files, e);
        }
      }
    }
  }, {
    key: 'onClick',
    value: function onClick() {
      if (!this.props.disableClick) {
        this.open();
      }
    }
  }, {
    key: 'allFilesAccepted',
    value: function allFilesAccepted(files) {
      var _this2 = this;

      return files.every(function (file) {
        return accepts(file, _this2.props.accept);
      });
    }
  }, {
    key: 'open',
    value: function open() {
      this.fileInputEl.value = null;
      this.fileInputEl.click();
    }
  }, {
    key: 'render',
    value: function render() {
      var _this3 = this;

      var _props = this.props;
      var accept = _props.accept;
      var activeClassName = _props.activeClassName;
      var inputProps = _props.inputProps;
      var multiple = _props.multiple;
      var name = _props.name;
      var rejectClassName = _props.rejectClassName;

      var rest = _objectWithoutProperties(_props, ['accept', 'activeClassName', 'inputProps', 'multiple', 'name', 'rejectClassName']);

      var _rest = // eslint-disable-line prefer-const
      rest;
      var activeStyle = _rest.activeStyle;
      var className = _rest.className;
      var rejectStyle = _rest.rejectStyle;
      var style = _rest.style;

      var props = _objectWithoutProperties(_rest, ['activeStyle', 'className', 'rejectStyle', 'style']);

      var _state = this.state;
      var isDragActive = _state.isDragActive;
      var isDragReject = _state.isDragReject;

      className = className || '';

      if (isDragActive && activeClassName) {
        className += ' ' + activeClassName;
      }
      if (isDragReject && rejectClassName) {
        className += ' ' + rejectClassName;
      }

      if (!className && !style && !activeStyle && !rejectStyle) {
        style = {
          width: 200,
          height: 200,
          borderWidth: 2,
          borderColor: '#666',
          borderStyle: 'dashed',
          borderRadius: 5
        };
        activeStyle = {
          borderStyle: 'solid',
          backgroundColor: '#eee'
        };
        rejectStyle = {
          borderStyle: 'solid',
          backgroundColor: '#ffdddd'
        };
      }

      var appliedStyle = undefined;
      if (activeStyle && isDragActive) {
        appliedStyle = _extends({}, style, activeStyle);
      } else if (rejectStyle && isDragReject) {
        appliedStyle = _extends({}, style, rejectStyle);
      } else {
        appliedStyle = _extends({}, style);
      }

      var inputAttributes = {
        accept: accept,
        type: 'file',
        style: { display: 'none' },
        multiple: supportMultiple && multiple,
        ref: function ref(el) {
          return _this3.fileInputEl = el;
        },
        onChange: this.onDrop
      };

      if (name && name.length) {
        inputAttributes.name = name;
      }

      return React.createElement(
        'div',
        _extends({
          className: className,
          style: appliedStyle
        }, props /* expand user provided props first so event handlers are never overridden */, {
          onClick: this.onClick,
          onDragEnter: this.onDragEnter,
          onDragOver: this.onDragOver,
          onDragLeave: this.onDragLeave,
          onDrop: this.onDrop
        }),
        this.props.children,
        React.createElement('input', _extends({}, inputProps /* expand user provided inputProps first so inputAttributes override them */, inputAttributes))
      );
    }
  }]);

  return Dropzone;
}(React.Component);

Dropzone.defaultProps = {
  disablePreview: false,
  disableClick: false,
  multiple: true
};

Dropzone.propTypes = {
  onDrop: React.PropTypes.func,
  onDropAccepted: React.PropTypes.func,
  onDropRejected: React.PropTypes.func,
  onDragEnter: React.PropTypes.func,
  onDragLeave: React.PropTypes.func,

  children: React.PropTypes.node,
  style: React.PropTypes.object,
  activeStyle: React.PropTypes.object,
  rejectStyle: React.PropTypes.object,
  className: React.PropTypes.string,
  activeClassName: React.PropTypes.string,
  rejectClassName: React.PropTypes.string,

  disablePreview: React.PropTypes.bool,
  disableClick: React.PropTypes.bool,

  inputProps: React.PropTypes.object,
  multiple: React.PropTypes.bool,
  accept: React.PropTypes.string,
  name: React.PropTypes.string
};

function accepts(file, acceptedFiles) {
  if (file && acceptedFiles) {
    var _ret = function () {
      var acceptedFilesArray = acceptedFiles.split(',');
      var fileName = file.name || '';
      var mimeType = file.type || '';
      var baseMimeType = mimeType.replace(/\/.*$/, '');

      return {
        v: acceptedFilesArray.some(function (type) {
          var validType = type.trim();
          if (validType.charAt(0) === '.') {
            return fileName.toLowerCase().endsWith(validType.toLowerCase());
          } else if (/\/\*$/.test(validType)) {
            // This is something like a image/* mime type
            return baseMimeType === validType.replace(/\/.*$/, '');
          }
          return mimeType === validType;
        })
      };
    }();

    if ((typeof _ret === 'undefined' ? 'undefined' : _typeof(_ret)) === "object") return _ret.v;
  }
  return true;
}

// export default Dropzone;
