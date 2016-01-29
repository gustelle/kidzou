/**
 * Plugin: "link" (selectize.js)
 *
 * @author Guillaume Patin <guillaume@kidzou.fr>
 */

(function($){

Selectize.define('link', function(options) {

	options = $.extend({
		title:  'ouvrir dans une nouvelle fenetre'
	}, options); //console.debug('options', options);

		var singleLink = function(thisRef, options) {

			var self = thisRef;
			var html = '<a href="javascript:void(0)" title="' + options.title + '" style="position: absolute;right: 28px;top: 6px;font-size: 23px;"><i class="' + options.iconClass + '"></i></a>';

			thisRef.setup = (function() {
				var original = self.setup;
				return function() {
	
					var id = $(self.$input.context).attr('id');
					var selectizer = $('#'+id);

					var render_item = self.settings.render.item;
					self.settings.render.item = function(data) {
						return render_item.apply(thisRef, arguments) + html ;
					};
				
					original.apply(thisRef, arguments);

					// add event listener
					thisRef.$control.on('click', function(e) {
						console.debug('click on ' , thisRef.$control);
						e.preventDefault();
						if (self.isLocked) return;
						self.clear();
					});

				};
			})();
		};

		singleLink(this, options);
		return;
});

})(jQuery)