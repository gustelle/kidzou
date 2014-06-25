var kidzouMessage = (function() {

	function MessageModel() {

		// logger.debug("MessageModel initialisé");
		var self = this;
		self.messageClass 		= ko.observable('');
		self.messageContent 	= ko.observable('');

		self.addMessage	= function(_cls, _msg) {

			// console.log('addMessage ' + _msg);
			self.messageClass(_cls);
			self.messageContent(_msg);

			//je ne parviens pas à utiliser proprement la propriété isVisible()
			//j'ai donc positionné un "display:none" en css et j'utilise jQuery en solution de secours
			jQuery("#messageBox").show();
		};

		self.removeMessage = function() {
			self.messageContent('');
			jQuery("#messageBox").hide();
		};
	}

	return {
		message : MessageModel
	};

}());