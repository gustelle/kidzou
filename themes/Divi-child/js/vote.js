"use strict";

var Vote = React.createClass({
	displayName: "Vote",

	render: function render() {
		console.debug('render');
		return React.createElement(
			"span",
			{ "class": "vote %s", "data-bind": "event: { click: $data.doUpOrDown, mouseover: $data.activateDown, mouseout: $data.deactivateDown }" },
			React.createElement("i", { className: this.props.iconClass }),
			React.createElement(
				"span",
				null,
				this.props.count
			)
		);
	}

});

console.time('render');
var nodes = document.querySelectorAll('.type-post');
[].forEach.call(nodes, function (node) {
	ReactDOM.render(React.createElement(Vote, { count: "4", iconClass: "fa fa-heart" }), node);
});
console.timeEnd('render');
