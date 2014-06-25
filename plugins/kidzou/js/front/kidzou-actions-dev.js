var kidzouActions = (function() {

		//binding des elements initialement présents dans le HTML
		/////////////// SEARCH ////////////////
		///////////////////////////////////////

		jQuery("#searchform").submit(function(){
			// kidzouMessage.addMessage('info', kidzou_commons_jsvars.msg_wait);
			kidzouTracker.trackEvent("Recherche", "Submit", jQuery("#searchinput").val(), 0);
		});

		/////////////// Tracking du comportement ////////////////
		//////////////////////////////////////////////////////

		jQuery(".slide_wrap a").click(function(){
			kidzouTracker.trackEvent("Featured Slider", "Click", jQuery(this).attr("href"), 0);
		});

		jQuery("#menu-menu-principal li a").click(function(){
			kidzouTracker.trackEvent("Navigation", "Menu Desktop", jQuery(this).find(".main_text").text(), 0);
		});

		jQuery("#mobile_menu li a").click(function(){
			kidzouTracker.trackEvent("Navigation", "Menu Mobile", jQuery(this).find("span").text(), 0);
		});

		jQuery("#menu-menu-principal li .dropdown_5columns .col_5 article").click(function(){
			kidzouTracker.trackEvent("Navigation", "MegaDropDown Article", jQuery(this).find(".entry-title a").text(), 0);
		});

		jQuery("#menu-menu-principal li .dropdown_5columns .col_3 li a").click(function(){
			kidzouTracker.trackEvent("Navigation", "MegaDropDown Categorie", jQuery(this).text(), 0);
		});

		jQuery(".meta a").click(function(){
			kidzouTracker.trackEvent("Navigation", "Meta", jQuery(this).text(), 0);
		});

		jQuery(".social.google").click(function(){
			kidzouTracker.trackEvent("Connexion", "Google", 'LoginDialog', 0);
		});

		jQuery(".social.facebook").click(function(){
			kidzouTracker.trackEvent("Connexion", "Facebook", 'LoginDialog', 0);
		});

		jQuery(".catad").click(function(){
			kidzouTracker.trackEvent("Publicite", "Categorie", jQuery(this).attr('src'), 0);
		});

		//top panel
		jQuery("#mc-embedded-subscribe-form").submit(function() {
			kidzouTracker.trackEvent("Newsletter", "Inscription", '', 0);
		});

		/////////////// MEGADROPDOWN ////////////////
		///////////////////////////////////////
		jQuery(".rubriques > ul.nav > li").hover(
			function() {
				jQuery(this).children().show();
			}, function() {
				jQuery(this).children(".dropdown_5columns").hide(); //pas le <a> qui contient l'element de nav principal
			}	
		);

	}());
