

var Vote = React.createClass({

	render: function() {
		console.debug('render');
		return (
			<span class="vote %s" data-bind="event: { click: $data.doUpOrDown, mouseover: $data.activateDown, mouseout: $data.deactivateDown }">
				<i className={this.props.iconClass}></i>
				<span>{this.props.count}</span>
	    	</span>
		);
	}

});

console.time('render');
var nodes = document.querySelectorAll('.type-post');
[].forEach.call(nodes, function(node) {
  ReactDOM.render(
		<Vote count="4" iconClass="fa fa-heart"/>, 
		node
	);
});
console.timeEnd('render');

