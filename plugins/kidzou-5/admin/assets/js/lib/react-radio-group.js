// /******/ (function(modules) { // webpackBootstrap
// /******/ 	// The module cache
// /******/ 	var installedModules = {};

// /******/ 	// The require function
// /******/ 	function __webpack_require__(moduleId) {

// /******/ 		// Check if module is in cache
// /******/ 		if(installedModules[moduleId])
// /******/ 			return installedModules[moduleId].exports;

// /******/ 		// Create a new module (and put it into the cache)
// /******/ 		var module = installedModules[moduleId] = {
// /******/ 			exports: {},
// /******/ 			id: moduleId,
// /******/ 			loaded: false
// /******/ 		};

// /******/ 		// Execute the module function
// /******/ 		modules[moduleId].call(module.exports, module, module.exports, __webpack_require__);

// /******/ 		// Flag the module as loaded
// /******/ 		module.loaded = true;

// /******/ 		// Return the exports of the module
// /******/ 		return module.exports;
// /******/ 	}


// /******/ 	// expose the modules object (__webpack_modules__)
// /******/ 	__webpack_require__.m = modules;

// /******/ 	// expose the module cache
// /******/ 	__webpack_require__.c = installedModules;

// /******/ 	// __webpack_public_path__
// /******/ 	__webpack_require__.p = "";

// /******/ 	// Load entry module and return exports
// /******/ 	return __webpack_require__(0);
// /******/ })
// /************************************************************************/
// /******/ ([
/* 0 */
// /***/ function(module, exports, __webpack_require__) {

// 	/** @jsx React.DOM */'use strict';

// 	__webpack_require__(2)

// 	var React      = __webpack_require__(1)
// 	var RadioGroup = __webpack_require__(4)

// 	var fruits = [
// 	    'apple',
// 	    'watermelon',
// 	    'orange',
// 	    'banana'
// 	]

// 	var SELECTED_FRUIT

// 	var colors = [
// 	    {
// 	        value: 'red',
// 	        style: { color: 'red' }
// 	    },
// 	    {
// 	        value: 'blue',
// 	        style: { color: 'blue'}
// 	    },
// 	    {
// 	        value: 'magenta',
// 	        style: {color: 'magenta'}
// 	    }
// 	]

// 	var SELECTED_COLOR = 'blue'

// 	var App = React.createClass({

// 	    displayName: 'App',

// 	    render: function(){

// 	        return React.createElement("div", null, 
// 	            React.createElement("h1", null, "React Radio Group"), 


// 	            React.createElement("code", null, "npm install --save react-radio"), 
// 	            React.createElement("p", null, "Github: ", React.createElement("a", {target: "_blank", href: "https://github.com/zippyui/react-radio"}, "github.com/zippyui/react-radio")), 

// 	            React.createElement("h3", null, "Fruits"), 
// 	            React.createElement("p", null, "You chose ", React.createElement("b", null, SELECTED_FRUIT || 'none')), 
// 	            React.createElement(RadioGroup, {
// 	                items: fruits, 
// 	                labelStyle: {display: 'block', padding: 10}, 
// 	                inputStyle: {marginRight: 10}, 
// 	                onChange: this.onFruitChange, 
// 	                defaultValue: SELECTED_FRUIT
// 	            }
// 	            ), 

// 	            React.createElement("p", {style: {marginBottom: 10, color: 'gray', border: '1px solid gray', padding: 20, display: 'inline-block'}}, 
// 	            "Hey! Just in case you are interested in a DataGrid, check out ", React.createElement("a", {href: "http://zippyui.github.io/react-datagrid", target: "_blank"}, "zippyui.github.io/react-datagrid")
// 	            ), 

// 	            React.createElement("h3", null, "Colors"), 
// 	            React.createElement("p", null, "You chose ", React.createElement("b", null, SELECTED_COLOR || 'none')), 
// 	            React.createElement(RadioGroup, {
// 	                items: colors, 
// 	                style: {border: '1px solid gray'}, 
// 	                onChange: this.onColorChange, 
// 	                labelStyle: {display: 'block', padding: 10}, 
// 	                inputStyle: {marginRight: 5}, 
// 	                defaultValue: SELECTED_COLOR
// 	            }
// 	            )
// 	        )
// 	    },
// 	    onColorChange: function(value) {
// 	        SELECTED_COLOR = value
// 	        this.setState({})
// 	    },
// 	    onFruitChange: function(value) {
// 	        SELECTED_FRUIT = value
// 	        this.setState({})
// 	    }
// 	})

// 	React.render(React.createElement(App, null), document.getElementById('content'))

// /***/ },
// /* 1 */
// /***/ function(module, exports, __webpack_require__) {

// 	module.exports = React;

// /***/ },
// /* 2 */
// /***/ function(module, exports, __webpack_require__) {

// 	// style-loader: Adds some css to the DOM by adding a <style> tag

// 	// load the styles
// 	var content = __webpack_require__(3);
// 	if(typeof content === 'string') content = [[module.id, content, '']];
// 	// add the styles to the DOM
// 	var update = __webpack_require__(5)(content, {});
// 	// Hot Module Replacement
// 	if(false) {
// 		// When the styles change, update the <style> tags
// 		module.hot.accept("!!/home/radu/pages/react-radio-www/node_modules/css-loader/index.js!/home/radu/pages/react-radio-www/node_modules/stylus-loader/index.js!/home/radu/pages/react-radio-www/index.styl", function() {
// 			var newContent = require("!!/home/radu/pages/react-radio-www/node_modules/css-loader/index.js!/home/radu/pages/react-radio-www/node_modules/stylus-loader/index.js!/home/radu/pages/react-radio-www/index.styl");
// 			if(typeof newContent === 'string') newContent = [[module.id, newContent, '']];
// 			update(newContent);
// 		});
// 		// When the module is disposed, remove the <style> tags
// 		module.hot.dispose(function() { update(); });
// 	}

// /***/ },
// /* 3 */
// /***/ function(module, exports, __webpack_require__) {

// 	exports = module.exports = __webpack_require__(6)();
// 	exports.push([module.id, "body {\n  margin: 20px;\n}\nbody .date-picker {\n  margin-top: 20px;\n}\n", ""]);

// /***/ },
/* 4 */
// /***/ function(module, exports, __webpack_require__) {

	'use strict';

	// var React = __webpack_require__(1)
	// var assign = __webpack_require__(7)

	// function emptyFn(){}

	// module.exports = 
	var RadioGroup = React.createClass({

	    displayName: 'ReactRadioGroup',

	    propTypes: {
	        name: React.PropTypes.string.isRequired,
	        items: function(props, name){
	            if (!props.children && !props.items){
	                return new Error('Your component has no children. In this case, you should specify an items array.')
	            }
	        }
	    },

	    componentDidUpdate: function() {
	        if (!this.shouldGenerateChildren()){
	            this.setRadioNames()
	            this.setCheckedRadio()
	        }
	    },

	    componentDidMount: function() {
	        if (!this.shouldGenerateChildren()){
	            this.setRadioNames()
	            this.setCheckedRadio()
	        }
	    },

	    setCheckedRadio: function(){
	        var value = this.props.value != null?
	                        this.props.value:
	                        this.state.defaultValue

	        this.someRadio(function(radio){
	            if (radio.value == value){
	                radio.checked = true
	                return true
	            }
	        })
	    },

	    setRadioNames: function() {
	        this.forEachRadio(function(radio){
	            radio.setAttribute('name', this.props.name)
	        })
	    },

	    someRadio: function(fn){
	        var $radios = this.getRadios()

	        return [].some.call($radios, fn, this)
	    },

	    forEachRadio: function(fn) {
	        var $radios = this.getRadios()

	        return [].forEach.call($radios, fn, this)
	    },

	    getRadios: function() {
	        return this.getDOMNode().querySelectorAll('input[type="radio"]')
	    },

	    getDefaultProps: function() {
	        return {
	            defaultLabelStyle: {
	                cursor: 'pointer'
	            },
	            defaultInputStyle: {
	                cursor: 'pointer'
	            }
	        }
	    },

	    getInitialState: function() {
	        return {
	            defaultValue: this.props.defaultValue
	        }
	    },

	    render: function(){
	        var props = this.prepareProps(this.props, this.state)

	        return React.createElement("div", React.__spread({},  props))
	    },

	    getValue: function() {
	        if (this.value == undefined){
	            this.value = this.state.defaultValue
	        }

	        return this.value
	    },

	    handleChange: function(event) {
	        var target = event.target
	        var fn     = this.props.onChange || emptyFn
	        var value  = this.value = target.value

	        fn(value, event)

	        if (this.props.value == null){
	            this.setState({
	                defaultValue: value
	            })
	        }
	    },

	    shouldGenerateChildren: function() {
	        return !this.props.children
	    },

	    prepareProps: function(thisProps, state) {

	        var props = {}

	        assign(props, thisProps)

	        if (this.shouldGenerateChildren()){
	            props.labelStyle = this.prepareLabelStyle(props, state)
	            props.inputStyle = this.prepareInputStyle(props, state)
	            props.children   = this.prepareChildren(props, state)
	        }

	        props.onChange = this.handleChange

	        return props
	    },

	    prepareLabelStyle: function(props) {
	        return assign({}, props.defaultLabelStyle, props.labelStyle)
	    },

	    prepareInputStyle: function(props) {
	        return assign({}, props.defaultInputStyle, props.inputStyle)
	    },

	    prepareChildren: function(props, state) {

	        var checkedValue = props.value != null?
	                            props.value:
	                            state.defaultValue

	        return (props.items || []).map(function(item, index, arr){

	            var inputStyle = assign({}, props.inputStyle)
	            var labelStyle = assign({}, props.labelStyle)
	            var checked
	            var value
	            var children

	            if (typeof item === 'string'){
	                value    = item
	                checked  = checkedValue == value
	                children = item
	            } else {
	                value    = item.value
	                children = item.label || item.value || item.children
	                checked  = checkedValue == value

	                if (item.inputStyle){
	                    assign(inputStyle, item.inputStyle)
	                }
	                if (item.style){
	                    assign(labelStyle, item.style)
	                }
	            }

	            if (checked){
	                assign(inputStyle, props.checkedInputStyle)
	                assign(labelStyle, props.checkedLabelStyle)

	                if (checked && item && item.checkedStyle){
	                    assign(labelStyle, item.checkedStyle)
	                }
	            }

	            var inputProps = {
	                checked : checked,
	                value   : value,
	                name    : props.name,
	                type    : 'radio',
	                style   : inputStyle,
	                onChange: emptyFn
	            }

	            var radioProps = {
	                index     : index,
	                value     : value,
	                style     : labelStyle,
	                inputProps: inputProps,
	                children  : [
	                    React.createElement("input", React.__spread({},  inputProps)),
	                    {children:children}
	                ]
	            }

	            var renderFn = props.renderRadio
	            var result

	            if (renderFn){
	                result = renderFn(radioProps, index, arr)
	            }

	            if (result === undefined){
	                result = React.createElement("label", React.__spread({},  radioProps))
	            }

	            return result
	        }, this)
	    }
	});

// /***/ },
/* 5 */
// /***/ function(module, exports, __webpack_require__) {

// 	/*
// 		MIT License http://www.opensource.org/licenses/mit-license.php
// 		Author Tobias Koppers @sokra
// 	*/
// 	var stylesInDom = {},
// 		memoize = function(fn) {
// 			var memo;
// 			return function () {
// 				if (typeof memo === "undefined") memo = fn.apply(this, arguments);
// 				return memo;
// 			};
// 		},
// 		isIE9 = memoize(function() {
// 			return /msie 9\b/.test(window.navigator.userAgent.toLowerCase());
// 		}),
// 		getHeadElement = memoize(function () {
// 			return document.head || document.getElementsByTagName("head")[0];
// 		}),
// 		singletonElement = null,
// 		singletonCounter = 0;

// 	module.exports = function(list, options) {
// 		if(false) {
// 			if(typeof document !== "object") throw new Error("The style-loader cannot be used in a non-browser environment");
// 		}

// 		options = options || {};
// 		// Force single-tag solution on IE9, which has a hard limit on the # of <style>
// 		// tags it will allow on a page
// 		if (typeof options.singleton === "undefined") options.singleton = isIE9();

// 		var styles = listToStyles(list);
// 		addStylesToDom(styles, options);

// 		return function update(newList) {
// 			var mayRemove = [];
// 			for(var i = 0; i < styles.length; i++) {
// 				var item = styles[i];
// 				var domStyle = stylesInDom[item.id];
// 				domStyle.refs--;
// 				mayRemove.push(domStyle);
// 			}
// 			if(newList) {
// 				var newStyles = listToStyles(newList);
// 				addStylesToDom(newStyles, options);
// 			}
// 			for(var i = 0; i < mayRemove.length; i++) {
// 				var domStyle = mayRemove[i];
// 				if(domStyle.refs === 0) {
// 					for(var j = 0; j < domStyle.parts.length; j++)
// 						domStyle.parts[j]();
// 					delete stylesInDom[domStyle.id];
// 				}
// 			}
// 		};
// 	}

// 	function addStylesToDom(styles, options) {
// 		for(var i = 0; i < styles.length; i++) {
// 			var item = styles[i];
// 			var domStyle = stylesInDom[item.id];
// 			if(domStyle) {
// 				domStyle.refs++;
// 				for(var j = 0; j < domStyle.parts.length; j++) {
// 					domStyle.parts[j](item.parts[j]);
// 				}
// 				for(; j < item.parts.length; j++) {
// 					domStyle.parts.push(addStyle(item.parts[j], options));
// 				}
// 			} else {
// 				var parts = [];
// 				for(var j = 0; j < item.parts.length; j++) {
// 					parts.push(addStyle(item.parts[j], options));
// 				}
// 				stylesInDom[item.id] = {id: item.id, refs: 1, parts: parts};
// 			}
// 		}
// 	}

// 	function listToStyles(list) {
// 		var styles = [];
// 		var newStyles = {};
// 		for(var i = 0; i < list.length; i++) {
// 			var item = list[i];
// 			var id = item[0];
// 			var css = item[1];
// 			var media = item[2];
// 			var sourceMap = item[3];
// 			var part = {css: css, media: media, sourceMap: sourceMap};
// 			if(!newStyles[id])
// 				styles.push(newStyles[id] = {id: id, parts: [part]});
// 			else
// 				newStyles[id].parts.push(part);
// 		}
// 		return styles;
// 	}

// 	function createStyleElement() {
// 		var styleElement = document.createElement("style");
// 		var head = getHeadElement();
// 		styleElement.type = "text/css";
// 		head.appendChild(styleElement);
// 		return styleElement;
// 	}

// 	function addStyle(obj, options) {
// 		var styleElement, update, remove;

// 		if (options.singleton) {
// 			var styleIndex = singletonCounter++;
// 			styleElement = singletonElement || (singletonElement = createStyleElement());
// 			update = applyToSingletonTag.bind(null, styleElement, styleIndex, false);
// 			remove = applyToSingletonTag.bind(null, styleElement, styleIndex, true);
// 		} else {
// 			styleElement = createStyleElement();
// 			update = applyToTag.bind(null, styleElement);
// 			remove = function () {
// 				styleElement.parentNode.removeChild(styleElement);
// 			};
// 		}

// 		update(obj);

// 		return function updateStyle(newObj) {
// 			if(newObj) {
// 				if(newObj.css === obj.css && newObj.media === obj.media && newObj.sourceMap === obj.sourceMap)
// 					return;
// 				update(obj = newObj);
// 			} else {
// 				remove();
// 			}
// 		};
// 	}

// 	function replaceText(source, id, replacement) {
// 		var boundaries = ["/** >>" + id + " **/", "/** " + id + "<< **/"];
// 		var start = source.lastIndexOf(boundaries[0]);
// 		var wrappedReplacement = replacement
// 			? (boundaries[0] + replacement + boundaries[1])
// 			: "";
// 		if (source.lastIndexOf(boundaries[0]) >= 0) {
// 			var end = source.lastIndexOf(boundaries[1]) + boundaries[1].length;
// 			return source.slice(0, start) + wrappedReplacement + source.slice(end);
// 		} else {
// 			return source + wrappedReplacement;
// 		}
// 	}

// 	function applyToSingletonTag(styleElement, index, remove, obj) {
// 		var css = remove ? "" : obj.css;

// 		if(styleElement.styleSheet) {
// 			styleElement.styleSheet.cssText = replaceText(styleElement.styleSheet.cssText, index, css);
// 		} else {
// 			var cssNode = document.createTextNode(css);
// 			var childNodes = styleElement.childNodes;
// 			if (childNodes[index]) styleElement.removeChild(childNodes[index]);
// 			if (childNodes.length) {
// 				styleElement.insertBefore(cssNode, childNodes[index]);
// 			} else {
// 				styleElement.appendChild(cssNode);
// 			}
// 		}
// 	}

// 	function applyToTag(styleElement, obj) {
// 		var css = obj.css;
// 		var media = obj.media;
// 		var sourceMap = obj.sourceMap;

// 		if(sourceMap && typeof btoa === "function") {
// 			try {
// 				css += "\n/*# sourceMappingURL=data:application/json;base64," + btoa(JSON.stringify(sourceMap)) + " */";
// 				css = "@import url(\"data:text/css;base64," + btoa(css) + "\")";
// 			} catch(e) {}
// 		}

// 		if(media) {
// 			styleElement.setAttribute("media", media)
// 		}

// 		if(styleElement.styleSheet) {
// 			styleElement.styleSheet.cssText = css;
// 		} else {
// 			while(styleElement.firstChild) {
// 				styleElement.removeChild(styleElement.firstChild);
// 			}
// 			styleElement.appendChild(document.createTextNode(css));
// 		}
// 	}


// /***/ },
// /* 6 */
// /***/ function(module, exports, __webpack_require__) {

// 	module.exports = function() {
// 		var list = [];
// 		list.toString = function toString() {
// 			var result = [];
// 			for(var i = 0; i < this.length; i++) {
// 				var item = this[i];
// 				if(item[2]) {
// 					result.push("@media " + item[2] + "{" + item[1] + "}");
// 				} else {
// 					result.push(item[1]);
// 				}
// 			}
// 			return result.join("");
// 		};
// 		return list;
// 	}

// /***/ },
// /* 7 */
// /***/ function(module, exports, __webpack_require__) {

// 	'use strict';

	function ToObject(val) {
		if (val == null) {
			throw new TypeError('Object.assign cannot be called with null or undefined');
		}

		return Object(val);
	}

	// module.exports = Object.assign || 
	function assign (target, source) {
		var from;
		var keys;
		var to = ToObject(target);

		for (var s = 1; s < arguments.length; s++) {
			from = arguments[s];
			keys = Object.keys(Object(from));

			for (var i = 0; i < keys.length; i++) {
				to[keys[i]] = from[keys[i]];
			}
		}

		return to;
	}


// /***/ }
// /******/ ]);