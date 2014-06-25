var layout = (function (){

		// logger.debug("layout init");

	// head.ready(function() {
	jQuery(document).ready(function() {
		
		var $container;

		if(jQuery('#recent-work').length) 
		{
			var columns    = 3;
			setColumns = function() { columns = jQuery( window ).width() > 768 ? 3 : jQuery( window ).width() > 480 ? 2 : 1; };
			setColumns();

			jQuery( window ).resize( setColumns );

			$container = jQuery('#recent-work');
			$container.imagesLoaded(function(){
			// jQuery(function(){
			  $container.masonry({
			    itemSelector : '.compact',
			    columnWidth:  function( containerWidth ) { return containerWidth / columns; }
			  });
			});

		}
		
		/////////////////// LINKS //////////////////
		////////////////////////////////////////////
		
		if ( jQuery('#links').length )
		{
			$container = jQuery('#links');
			$container.imagesLoaded(function(){
			// jQuery(function(){
			  $container.masonry({
			    itemSelector : '.link',
			    columnWidth: function( containerWidth ) {return containerWidth / 2;	}
			  });
			});
		}


		////////////////// PORTAGE ////////////////
		if (jQuery('#ported').length) 
			jQuery('#ported').portamento({wrapper: jQuery('#kz-article'), gap:0, disableWorkaround: true});

		////////////////// NEWSLETTER ////////////////
		//logger.debug("kidzou_commons_jsvars.cfg_newsletter_auto_display : " + kidzou_commons_jsvars.cfg_newsletter_auto_display);
		if (kidzou_commons_jsvars.cfg_newsletter_auto_display)
			newsletter_auto_display();

		function newsletter_auto_display()
		{
			//console.log("*** newsletter_auto_display ***");
			if ( storageSupport.getLocal("kz_newsletter")!="1") 
			{
				setTimeout(function(){kidzouModule.dialogs.openShareDialog();}, kidzou_commons_jsvars.cfg_newsletter_delay);
				jQuery(".newsclickable").click(function(){
					storageSupport.setLocal('kz_newsletter' , '1');
				});
			}
			
		}
	});	

}()); //auto-executée