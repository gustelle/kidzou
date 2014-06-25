
//ne pas tracker en dev et ne pas tracker les admins
var _do_track = !kidzou_commons_jsvars.is_admin && location.hostname==='www.kidzou.fr';


if (_do_track) {

	//google analytics
	(function (i,s,o,g,r,a,m) {i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
	(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
	m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
	})(window,document,'script','//www.google-analytics.com/analytics.js','ga');

	ga('create', 'UA-23017523-1', 'kidzou.fr');
	ga('send', 'pageview');
}

var kidzouTracker = (function() {

		function trackEvent(context, action, title, loadtime) {
			if (_do_track)
				ga('send', 'event', context, action, title, loadtime);
	        else
	        	console.debug("trackEvent(" + context + ", " + action + ", " + title + ", " + loadtime + ")");
	  	}

	  	return {
	  		trackEvent : trackEvent
	  	};

}());