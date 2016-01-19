//version vanilla de custom.js

//@see https://gist.github.com/liamcurry/2597326
//@see http://gomakethings.com/climbing-up-and-down-the-dom-tree-with-vanilla-javascript/
//@see https://gist.github.com/smutnyleszek/9853194
//@see http://callmenick.com/post/jquery-functions-javascript-equivalents
//@see http://codeblog.cz/vanilla/style.html
//@see https://plainjs.com/javascript/traversing/get-siblings-of-an-element-40/

(function($){

	// Vanilla version of jquery.extend()
	// see http://gomakethings.com/vanilla-javascript-version-of-jquery-extend/
	var extend = function () {

	    // Variables
	    var extended = {};
	    var deep = false;
	    var i = 0;
	    var length = arguments.length;

	    // Check if a deep merge
	    if ( Object.prototype.toString.call( arguments[0] ) === '[object Boolean]' ) {
	        deep = arguments[0];
	        i++;
	    }

	    // Merge the object into the extended object
	    var merge = function (obj) {
	        for ( var prop in obj ) {
	            if ( Object.prototype.hasOwnProperty.call( obj, prop ) ) {
	                // If deep merge and property is an object, merge properties
	                if ( deep && Object.prototype.toString.call(obj[prop]) === '[object Object]' ) {
	                    extended[prop] = extend( true, extended[prop], obj[prop] );
	                } else {
	                    extended[prop] = obj[prop];
	                }
	            }
	        }
	    };

	    // Loop through each object and conduct a merge
	    for ( ; i < length; i++ ) {
	        var obj = arguments[i];
	        merge(obj);
	    }

	    return extended;
	};

	//vanilla version de height() et width()
	var width = function() {
		var  e=document.documentElement,
		g=document.getElementsByTagName('body')[0],
		x=window.innerWidth||e.clientWidth||g.clientWidth;
		return x;
	};

	var height = function() {
		var  e=document.documentElement,
		g=document.getElementsByTagName('body')[0],
		y=window.innerHeight||e.clientHeight||g.clientHeight;
		return y;
	};

	/**
	 * Get closest DOM element up the tree that contains a class, ID, or data attribute
	 * @param  {Node} elem The base element
	 * @param  {String} selector The class, id, data attribute, or tag to look for
	 * @return {Node} Null if no match
	 */
	var getClosest = function (elem, selector) {

	    var firstChar = selector.charAt(0);

	    // Get closest match
	    for ( ; elem && elem !== document; elem = elem.parentNode ) {

	        // If selector is a class
	        if ( firstChar === '.' ) {
	            if ( elem.classList.contains( selector.substr(1) ) ) {
	                return elem;
	            }
	        }

	        // If selector is an ID
	        if ( firstChar === '#' ) {
	            if ( elem.id === selector.substr(1) ) {
	                return elem;
	            }
	        } 

	        // If selector is a data attribute
	        if ( firstChar === '[' ) {
	            if ( elem.hasAttribute( selector.substr(1, selector.length - 2) ) ) {
	                return elem;
	            }
	        }

	        // If selector is a tag
	        if ( elem.tagName.toLowerCase() === selector ) {
	            return elem;
	        }

	    }

	    return false;
	};

	var getParents = function(el, selector) {
		var parents = [];
		var parent = element;
		while ( parent = getClosest(parent.parentElement, selector) )
    		parents.push(parent);

    	return parents;
	};
	

	var getSiblings = function(el, filter) {
	    var siblings = [];
	    el = el.parentNode.firstChild;
	    do { if (!filter || filter(el)) siblings.push(el); } while (el = el.nextSibling);
	    return siblings;
	};

	//https://davidwalsh.name/element-matches-selector
	var selectorMatches = function(el, selector) {
		var p = Element.prototype;
		var f = p.matches || p.webkitMatchesSelector || p.mozMatchesSelector || p.msMatchesSelector || function(s) {
			return [].indexOf.call(document.querySelectorAll(s), this) !== -1;
		};
		return f.call(el, selector);
	}	

	var nextUntil = function(el, selector) {
		var next = []; 
		var last = el;
		while (last.nextElementSibling && !selectorMatches(last.nextElementSibling,selector))
		    next.push(last = last.nextElementSibling);
		return next;
	};

	var prevUntil = function(el, selector) {
		var prev = []; 
		var last = el;
		while (last.previousElementSibling && !selectorMatches(last.previousElementSibling,selector))
		    prev.push(last = last.previousElementSibling);
	};

	// fade an element from the current state to full opacity in "duration" ms
	var fadeOut = function(el, duration) {
	    var s = el.style, step = 25/(duration || 300);
	    s.opacity = s.opacity || 1;
	    (function fade() { (s.opacity -= step) < 0 ? s.display = "none" : setTimeout(fade, 25); })();
	};

	// fade out an element from the current state to full transparency in "duration" ms
	// display is the display style the element is assigned after the animation is done
	var fadeIn = function(el, duration, display, onComplete) {
	    var s = el.style, step = 25/(duration || 300);
	    s.opacity = s.opacity || 0;
	    s.display = display || "block";
	    (function fade() { (s.opacity = parseFloat(s.opacity)+step) > 1 ? s.opacity = 1 : setTimeout(fade, 25); })();
		if (typeof onComplete!=='undefined' && onComplete instanceof Function) onComplete();
	};

	var slideLeft = function(el, onComplete) {
		var beg = parseInt(getComputedStyle(el).right);
		var end = beg - parseInt(getComputedStyle(el).width);
		function goLeft () {
			var cur = parseInt(getComputedStyle(el).right);
			setTimeout(function () {      
				if (cur > end) {
					el.style.right = cur - 4 + 'px';
					goLeft();
				}
			}, 5);
		}
		goLeft();
		if (typeof onComplete!=='undefined' && onComplete instanceof Function) onComplete();
	};

	// jQuery.fn.reverse = [].reverse;
	var et_pb_simple_slider = function(el, options) {
		var settings = extend( {
			slide         			: '.et-slide',				 	// slide class
			arrows					: '.et-pb-slider-arrows',		// arrows container class
			prev_arrow				: '.et-pb-arrow-prev',			// left arrow class
			next_arrow				: '.et-pb-arrow-next',			// right arrow class
			controls 				: '.et-pb-controllers a',		// control selector
			control_active_class	: 'et-pb-active-control',		// active control class name
			previous_text			: 'Previous',					// previous arrow text
			next_text				: 'Next',						// next arrow text
			fade_speed				: 500,							// fade effect speed
			use_arrows				: true,							// use arrows?
			use_controls			: true,							// use controls?
			manual_arrows			: '',							// html code for custom arrows
			append_controls_to		: '',							// controls are appended to the slider element by default, here you can specify the element it should append to
			controls_class			: 'et-pb-controllers',				// controls container class name
			slideshow				: false,						// automattic animation?
			slideshow_speed			: 7000,							// automattic animation speed
			show_progress_bar		: false,							// show progress bar if automattic animation is active
			tabs_animation			: false
		}, options );

		var $et_slider 			= document.querySelector(el),//$(el),
			$et_slide			= $et_slider.querySelectorAll(settings.slide),//$et_slider.find( settings.slide ),
			et_slides_number	= $et_slide.length,
			et_fade_speed		= settings.fade_speed,
			et_active_slide		= 0,
			$et_slider_arrows,
			$et_slider_prev,
			$et_slider_next,
			$et_slider_controls,
			et_slider_timer,
			controls_html = '',
			$progress_bar = null,
			progress_timer_count = 0,
			$et_pb_container = $et_slider.querySelector( '.et_pb_container' ),
			et_pb_container_width = $et_pb_container.offsetWidth;

			//@see http://stackoverflow.com/questions/11286661/set-data-attribute-using-javascript
			$et_slider.dataset.et_animation_running = false;

			// $.data(el, "et_pb_simple_slider", $et_slider);
			var data = new WeakMap();
			data.set("et_pb_simple_slider", $et_slider);
			// data.set('et_animation_running', false)

			// $et_slide.eq(0).classList.add( 'et-pb-active-slide' );
			$et_slide[0].classList.add( 'et-pb-active-slide' );

			if ( ! settings.tabs_animation ) {
				if ( !$et_slider.classList.contains('et_pb_bg_layout_dark') && !$et_slider.classList.contains('et_pb_bg_layout_light') ){
					$et_slider.classList.add( et_get_bg_layout_color( $et_slide[0] ) );
				}
			}

			if ( settings.use_arrows && et_slides_number > 1 ) {
				if ( settings.manual_arrows === '' ) {
					// $et_slider.append( '<div class="et-pb-slider-arrows"><a class="et-pb-arrow-prev" href="#">' + '<span>' +settings.previous_text + '</span>' + '</a><a class="et-pb-arrow-next" href="#">' + '<span>' + settings.next_text + '</span>' + '</a></div>' );
					$et_slider.insertAdjacentHTML('beforeEnd', '<div class="et-pb-slider-arrows"><a class="et-pb-arrow-prev" href="#">' + '<span>' +settings.previous_text + '</span>' + '</a><a class="et-pb-arrow-next" href="#">' + '<span>' + settings.next_text + '</span>' + '</a></div>');

				} else {
					$et_slider.insertAdjacentHTML( 'beforeEnd', settings.manual_arrows );
				}

				$et_slider_arrows 	= document.querySelector( settings.arrows );
				$et_slider_prev 	= $et_slider.querySelector( settings.prev_arrow );
				$et_slider_next 	= $et_slider.querySelector( settings.next_arrow );

				$et_slider_next.addEventListener( 'click', function () {
			       if ( $et_slider.dataset.et_animation_running )	return false;

					et_slider_move_to( $et_slider, 'next' );

					return false;
			    }, false );

				$et_slider_prev.addEventListener( 'click', function () {
			       if ( $et_slider.dataset.et_animation_running )	return false;

					et_slider_move_to( $et_slider, 'previous' );

					return false;
			    }, false );
			}

			if ( settings.use_controls && et_slides_number > 1 ) {
				for ( var i = 1; i <= et_slides_number; i++ ) {
					controls_html += '<a href="#"' + ( i == 1 ? ' class="' + settings.control_active_class + '"' : '' ) + '>' + i + '</a>';
				}

				controls_html =
					'<div class="' + settings.controls_class + '">' +
						controls_html +
					'</div>';

				if ( settings.append_controls_to === '' )
					$et_slider.insertAdjacentHTML('beforeEnd', controls_html );
				else
					document.querySelector( settings.append_controls_to ).insertAdjacentHTML('beforeEnd', controls_html );

				$et_slider_controls	= $et_slider.querySelectorAll( settings.controls );


				[].slice.call($et_slider_controls).forEach(function(el,i){
				    el.addEventListener( 'click', function () {
				       if ( $et_slider.daet_animation_running )	return false;

						et_slider_move_to( $et_slider, i );

						return false;
				    }, false );
				});


			}

			if ( settings.slideshow && et_slides_number > 1 ) {
				$et_slider.addEventListener('mouseover', function() {
				    // mouse is hovering over this element
				    et_slider.classList.add( 'et_slider_hovered' );
				    if ( typeof et_slider_timer != 'undefined' ) {
						clearInterval( et_slider_timer );
					}
				});

				$et_slider.addEventListener('mouseout', function() {
				    // mouse was hovering over this element, but no longer is
				    $et_slider.classList.remove( 'et_slider_hovered' );
					et_slider_auto_rotate();
				});
			}

			et_slider_auto_rotate();

			function et_slider_auto_rotate(){
				if ( settings.slideshow && et_slides_number > 1 && ! $et_slider.classList.contains( 'et_slider_hovered' ) ) {
					et_slider_timer = setTimeout( function() {
						et_slider_move_to( $et_slider, 'next' );
					}, settings.slideshow_speed );
				}
			}

			function et_fix_slider_content_images() {
				var $this_slider           = $et_slider;
				var $slide_image_container = $this_slider.querySelector( '.et-pb-active-slide .et_pb_slide_image' );
				var $slide                 = getClosest($slide_image_container,'.et_pb_slide');//$slide_image_container.closest( '.et_pb_slide' );
				var $slider                = getClosest($slide,'.et_pb_slider');//$slide.closest( '.et_pb_slider' );
				var slide_height           = $slider.clientHeight;
				var image_height           = parseInt( slide_height * 0.8 );

				[].slice.call($slide_image_container.querySelectorAll( 'img' )).forEach(function(el,i){
				    el.style.maxHeight = image_height + 'px' ;
				});

				if ( $slide.classList.contains( 'et_pb_media_alignment_center' ) ) {
					$slide_image_container.style.marginTop =  '-' + parseInt( parseInt(getComputedStyle($slide_image_container).height)  / 2 ) + 'px' ;
				}
			}

			function et_get_bg_layout_color( $slide ) {
				if ( $slide.classList.contains( 'et_pb_bg_layout_dark' ) ) {
					return 'et_pb_bg_layout_dark';
				}
				return 'et_pb_bg_layout_light';
			}

			$et_window.addEventListener('load', function() {
			    // page is fully rendered
			    et_fix_slider_content_images();
			});
			$et_window.addEventListener('resize', function() {
				if ( et_pb_container_width !== parseInt(getComputedStyle($et_pb_container).width) ) {
					et_pb_container_width = parseInt(getComputedStyle($et_pb_container).width);

					et_fix_slider_content_images();
				}
			});

			et_slider_move_to = function ( el, direction ) {
				var $active_slide = $et_slide[et_active_slide],
					$next_slide;

				$et_slider.dataset.et_animation_running = true;

				if ( direction == 'next' || direction == 'previous' ){

					if ( direction == 'next' )
						et_active_slide = ( et_active_slide + 1 ) < et_slides_number ? et_active_slide + 1 : 0;
					else
						et_active_slide = ( et_active_slide - 1 ) >= 0 ? et_active_slide - 1 : et_slides_number - 1;

				} else {

					if ( et_active_slide == direction ) {
						$et_slider.dataset.et_animation_running = false;
						return;
					}

					et_active_slide = direction;

				}

				if ( typeof et_slider_timer != 'undefined' )
					clearInterval( et_slider_timer );

				$next_slide	= $et_slide[et_active_slide];

				[].slice.call($et_slide).forEach(function(el,i){
				    el.style.zIndex = 1 ;
				});

				$active_slide.style.zIndex = 2;
				$active_slide.classList.remove( 'et-pb-active-slide' );

				$next_slide.style.display = 'block';
				$next_slide.style.opacity = 0;
				$next_slide.classList.add( 'et-pb-active-slide' );

				et_fix_slider_content_images();

				if ( settings.use_controls )
					$et_slider_controls.classList.remove( settings.control_active_class ).eq( et_active_slide ).classList.add( settings.control_active_class );

				if ( ! settings.tabs_animation ) {
					$next_slide.animate( { opacity : 1 }, et_fade_speed );
					$active_slide.classList.add( 'et_slide_transition' ).css( { 'display' : 'list-item', 'opacity' : 1 } ).animate( { opacity : 0 }, et_fade_speed, function(){
						var active_slide_layout_bg_color = et_get_bg_layout_color( $active_slide ),
							next_slide_layout_bg_color = et_get_bg_layout_color( $next_slide );

						$(this).css('display', 'none').classList.remove( 'et_slide_transition' );

						$et_slider
							.classList.remove( active_slide_layout_bg_color )
							.classList.add( next_slide_layout_bg_color );

						$et_slider.et_animation_running = false;
					} );
				} else {
					$next_slide.css( { 'display' : 'none', opacity : 0 } );

					$active_slide.classList.add( 'et_slide_transition' ).css( { 'display' : 'block', 'opacity' : 1 } ).animate( { opacity : 0 }, et_fade_speed, function(){
						$(this).css('display', 'none').classList.remove( 'et_slide_transition' );

						$next_slide.css( { 'display' : 'block', 'opacity' : 0 } ).animate( { opacity : 1 }, et_fade_speed, function() {
							$et_slider.et_animation_running = false;
						} );
					} );
				}

				et_slider_auto_rotate();
			};
	};

	// $.fn.et_pb_simple_slider = function( options ) {
	// 	return this.each(function() {
	// 		new $.et_pb_simple_slider(this, options);
	// 	});
	// };

	var et_hash_module_seperator = '||',
		et_hash_module_param_seperator = '|';

	function process_et_hashchange( hash ) {
		var module_params ;
		var element;
		if ( ( hash.indexOf( et_hash_module_seperator, 0 ) ) !== -1 ) {
			modules = hash.split( et_hash_module_seperator );
			for ( var i = 0; i < modules.length; i++ ) {
				module_params = modules[i].split( et_hash_module_param_seperator );
				element = module_params[0];
				module_params.shift();
				// if ( $('#' + element ).length ) {
				if ( document.querySelector('#' + element )!==null ) {
					document.querySelector('#' + element).dispatchEvent(new CustomEvent('et_hashchange', module_params));
				}
			}
		} else {
			module_params = hash.split( et_hash_module_param_seperator );
			element = module_params[0];
			module_params.shift();
			if ( document.querySelector('#' + element )!==null ) {
				document.querySelector('#' + element).dispatchEvent(new CustomEvent('et_hashchange', module_params));
			}
		}
	}

	function et_set_hash( module_state_hash ) {
		module_id = module_state_hash.split( et_hash_module_param_seperator )[0];
		if ( document.querySelector('#' + module_id )==null) {
			return;
		}

		var hash; var new_hash;

		if ( window.location.hash ) {
			hash = window.location.hash.substring(1); //Puts hash in variable, and removes the # character
			new_hash = [];

			var element;

			if( ( hash.indexOf( et_hash_module_seperator, 0 ) ) !== -1 ) {
				modules = hash.split( et_hash_module_seperator );
				var in_hash = false;
				for ( var i = 0; i < modules.length; i++ ) {
					element = modules[i].split( et_hash_module_param_seperator )[0];
					if( element === module_id ) {
						new_hash.push( module_state_hash );
						in_hash = true;
					} else {
						new_hash.push( modules[i] );
					}
				}
				if ( !in_hash ) {
					new_hash.push( module_state_hash );
				}
			} else {
				module_params = hash.split( et_hash_module_param_seperator );
				element = module_params[0];
				if ( element !== module_id ) {
					new_hash.push( hash );
				}
				new_hash.push( module_state_hash );
			}

			hash = new_hash.join( et_hash_module_seperator );
		} else {
			hash = module_state_hash;
		}

		var yScroll = document.body.scrollTop;
		window.location.hash = hash;
		document.body.scrollTop = yScroll;
	}

	var $et_pb_slider  				= document.querySelectorAll( '.et_pb_slider' ),
		$et_pb_tabs   				= document.querySelector( '.et_pb_tabs' ),
		$et_pb_tabs_li 				= $et_pb_tabs!==null ? $et_pb_tabs.querySelectorAll( '.et_pb_tabs_controls li' ) : null,
		$et_pb_video_section 		= document.querySelector('.et_pb_section_video_bg'),
		$et_pb_newsletter_button 	= document.querySelector( '.et_pb_newsletter_button' ),
		$et_pb_filterable_portfolio = document.querySelectorAll( '.et_pb_filterable_portfolio:not(.kz_pb_filterable_portfolio)' ),
		$kz_pb_filterable_portfolio = document.querySelectorAll( '.kz_pb_filterable_portfolio' ),
		$et_pb_fullwidth_portfolio 	= document.querySelectorAll( '.et_pb_fullwidth_portfolio' ),
		$et_pb_gallery 				= document.querySelectorAll( '.et_pb_gallery' ),
		$et_pb_countdown_timer 		= document.querySelectorAll( '.et_pb_countdown_timer' ),
		$et_post_gallery 			= document.querySelector( '.et_post_gallery' ),
		$et_lightbox_image 			= document.querySelector( '.et_pb_lightbox_image'),
		$et_pb_map    				= document.querySelectorAll( '.et_pb_map_container' ),
		$et_pb_circle_counter 		= document.querySelectorAll( '.et_pb_circle_counter' ),
		$et_pb_number_counter 		= document.querySelectorAll( '.et_pb_number_counter' ),
		$et_pb_parallax 			= document.querySelectorAll( '.et_parallax_bg' ),
		et_is_mobile_device 		= navigator.userAgent.match( /Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/ ),
		et_is_ipad 					= navigator.userAgent.match( /iPad/ ),
		$et_container 				= document.querySelector( '.container' ),
		et_container_width 			= parseInt(getComputedStyle($et_container).width),//$et_container.width(),
		et_is_fixed_nav 			= document.querySelector( 'body' ).classList.contains( 'et_fixed_nav' ),
									$main_container_wrapper = document.querySelector( '#page-container' ),
									$et_window = window,
									etRecalculateOffset = false,
									et_header_height,
									et_header_modifier,
									et_header_offset,
									et_primary_header_top;

	// $(document).ready( function(){
	document.addEventListener('DOMContentLoaded', function() {

		var $et_top_menu 	= document.querySelector( 'ul.nav' ),
			$et_search_icon = document.querySelector( '#et_search_icon' );

			[].slice.call($et_top_menu.querySelectorAll( 'li' )).forEach(function(el,i){
				    el.addEventListener('mouseover', function() {
						if ( getClosest(el, 'li.mega-menu')==null || el.classList.contains( 'mega-menu' ) ) {
							el.classList.add( 'et-show-dropdown' );
							el.classList.remove( 'et-hover' );
							el.classList.add( 'et-hover' );
						}
					});
					el.addEventListener('mouseout', function() {
						el.classList.remove( 'et-show-dropdown' );
						setTimeout( function() {
							if ( ! el.classList.contains( 'et-show-dropdown' ) ) {
								el.classList.remove( 'et-hover' );
							}
						}, 200 );
					} );
			});

	
		if ( document.querySelector('ul.et_disable_top_tier')!==null ) {
			var prevs = getSiblings(document.querySelector("ul.et_disable_top_tier > li > ul"), function(el) {
				return elem.nodeName.toLowerCase() == 'a';
			});
			prevs.forEach(function(element) {
			    element.setAttribute('href','#');
			});
		}

		if ( et_is_mobile_device ) {
			[].slice.call(document.querySelectorAll('.et_pb_section_video_bg')).forEach(function(el,i){
				el.style.visibility = 'hidden' ;
				getClosest(el, '.et_pb_preload' ).classList.remove( 'et_pb_preload' );
			});
			document.querySelector( 'body' ).classList.add( 'et_mobile_device' );
			if ( ! et_is_ipad ) {
				document.querySelector( 'body' ).classList.add( 'et_mobile_device_not_ipad' );
			}
		}

		$et_search_icon.addEventListener('click', function() {

			$form = getSiblings($et_search_icon, function(el) {
				return elem.classList.contains('et-search-form');
			}); 
			if ( $form.classList.contains( 'et-hidden' ) ) {
				$form.style.display = 'block';
				$form.style.opacity = 0;
				$form.style.zIndex = 1000;
				fadeIn($form, 500);
			} else {
				// $form.style.display = 'none';
				// $form.style.opacity = 0;
				// $form.style.zIndex = 1000;
				fadeOut($form, 500);
			}
			$form.classList.toggle( 'et-hidden' );
		} );



		if ( $et_pb_video_section!==null ){

			[].slice.call($et_pb_video_section.querySelectorAll( 'video' )).forEach(function(el,i){
				var player = new MediaElementPlayer('#' + el.id, {
				    pauseOtherPlayers: false,
					success : function( mediaElement, domObject ) {
						mediaElement.addEventListener( 'canplay', function() {
							et_pb_resize_section_video_bg( document.querySelector(domObject) );
							et_pb_center_video( document.querySelector(domObject) );
						});
					}
				});
			});

		}

		if ( $et_post_gallery!==null && jQuery && jQuery.fn.magnificPopup) {
			jQuery('.et_post_gallery').magnificPopup( {
				delegate: 'a',
				type: 'image',
				removalDelay: 500,
				gallery: {
					enabled: true,
					navigateByImgClick: true
				},
				mainClass: 'mfp-fade',
				zoom: {
					enabled: true,
					duration: 500,
					opener: function(element) {
						return element.find('img');
					}
				}
			} );
		}

		if ( $et_lightbox_image!==null && jQuery && jQuery.fn.magnificPopup ) {
			jQuery('.et_pb_lightbox_image').magnificPopup( {
				type: 'image',
				removalDelay: 500,
				mainClass: 'mfp-fade',
				zoom: {
					enabled: true,
					duration: 500,
					opener: function(element) {
						return element.find('img');
					}
				}
			} );
		}

		if ( $et_pb_slider!==null ){
			[].slice.call($et_pb_slider).forEach(function(el,i){
			// $et_pb_slider.each( function() {
				// var $this_slider = $(this),
					et_slider_settings = {
						fade_speed 		: 700,
						slide			: '.et_pb_slide'
					};

				if ( el.classList.contains('et_pb_slider_no_arrows') )
					et_slider_settings.use_arrows = false;

				if ( el.classList.contains('et_pb_slider_no_pagination') )
					et_slider_settings.use_controls = false;

				if ( el.classList.contains('et_slider_auto') ) {
					var et_slider_autospeed_class_value = /et_slider_speed_(\d+)/g;

					et_slider_settings.slideshow = true;

					et_slider_autospeed = et_slider_autospeed_class_value.exec( $this_slider.getAttribute('class') );

					et_slider_settings.slideshow_speed = et_slider_autospeed[1];
				}

				et_pb_simple_slider( el, et_slider_settings );

				[].slice.call(el.querySelectorAll( '.et_pb_slide_video' )).forEach(function(elem,j){
				// el.querySelectorAll( '.et_pb_slide_video' ).each( function() {
					var $this_el = elem.querySelector( 'iframe' ),
						src_attr = $this_el.getAttribute('src'),
						wmode_character = src_attr.indexOf( '?' ) == -1 ? '?' : '&amp;',
						this_src = src_attr + wmode_character + 'wmode=opaque';

					$this_el.setAttribute('src', this_src);
				} );
			} );
		}

		var set_fullwidth_portfolio_columns;
		var et_carousel_auto_rotate;

		if ( $et_pb_fullwidth_portfolio!==null  ) {

			set_fullwidth_portfolio_columns = function ( $the_portfolio, carousel_mode ) {
				var columns,
					$portfolio_items = $the_portfolio.querySelector('.et_pb_portfolio_items'),
					portfolio_items_width = parseInt(getComputedStyle($portfolio_items).width),//$portfolio_items.width(),
					$the_portfolio_items = $portfolio_items.querySelectorAll('.et_pb_portfolio_item'),
					portfolio_item_count = $the_portfolio_items.length;

				// calculate column breakpoints
				if ( portfolio_items_width >= 1600 ) {
					columns = 5;
				} else if ( portfolio_items_width >= 1024 ) {
					columns = 4;
				} else if ( portfolio_items_width >= 768 ) {
					columns = 3;
				} else if ( portfolio_items_width >= 480 ) {
					columns = 2;
				} else {
					columns = 1;
				}

				// set height of items
				portfolio_item_width = portfolio_items_width / columns;
				portfolio_item_height = portfolio_item_width * 0.75;

				if ( carousel_mode ) {
					$portfolio_items.style.height = portfolio_item_height + 'px';
				}

				// $the_portfolio_items.css({ 'height' : portfolio_item_height });
				[].slice.call($the_portfolio_items).forEach(function(element,i){
				// $the_portfolio_items.forEach(function(element) {
				    element.style.height = portfolio_item_height + 'px';
				});

				if ( columns === parseInt($portfolio_items.dataset.columns) ) {
					return;
				}

				if ( $the_portfolio.dataset.columns_setting_up ) {
					return;
				}

				$the_portfolio.dataset.columns_setting_up = ''; //true

				var portfolio_item_width_percentage = ( 100 / columns ) + '%';
				[].slice.call($the_portfolio_items).forEach(function(item,k){
					item.style.width = portfolio_item_width_percentage ;
				});
				
				// store last setup column
				$portfolio_items.classList.remove('columns-' + $portfolio_items.dataset.columns );
				$portfolio_items.classList.add('columns-' + columns );
				$portfolio_items.dataset.columns = columns ;

				if ( !carousel_mode ) {
					delete $the_portfolio.dataset.columns_setting_up ;
					return;
				}

				// kill all previous groups to get ready to re-group
				if ( $portfolio_items.querySelector('.et_pb_carousel_group')!==null ) {
					$the_portfolio_items.insertAdjacentHTML( 'beforeEnd',$portfolio_items.parentNode.innerHTML );
					var elem = $portfolio_items.querySelector('.et_pb_carousel_group');
					elem.parentNode.removeChild(elem);
				}

				// setup the grouping
				var the_portfolio_items = $portfolio_items.dataset.items,
					$carousel_group = $portfolio_items.insertAdjacentHTML('beforeEnd', '<div class="et_pb_carousel_group active"></div>');

				[].slice.call($the_portfolio_items).forEach(function(item,k){
					item.dataset.position = '';
				});
				
				if ( the_portfolio_items.length <= columns ) {
					$portfolio_items.querySelector('.et-pb-slider-arrows').style.display = 'none';//hide();
				} else {
					$portfolio_items.querySelector('.et-pb-slider-arrows').style.display = 'block';//show();
				}

				for ( position = 1, x=0 ;x < the_portfolio_items.length; x++, position++ ) {
					if ( x < columns ) {
						document.querySelector( the_portfolio_items[x] ).style.display = 'block';
						$carousel_group.insertAdjacentHTML('beforeEnd', document.querySelector( the_portfolio_items[x] ).parentNode.innerHTML ) ;
						document.querySelector( the_portfolio_items[x] ).dataset.position = position ;
						document.querySelector( the_portfolio_items[x] ).classList.add('position_' + position );
					} else {
						position = document.querySelector( the_portfolio_items[x] ).dataset.position;
						document.querySelector( the_portfolio_items[x] ).classList.remove('position_' + position );
						document.querySelector( the_portfolio_items[x] ).dataset.position = '' ;
						document.querySelector( the_portfolio_items[x] ).style.display = 'none';
					}
				}

				delete $the_portfolio.dataset.columns_setting_up;

			};

			et_carousel_auto_rotate = function ( $carousel ) {
				if ( 'on' === $carousel.dataset.autoRotate && $carousel.querySelectorAll('.et_pb_portfolio_item').length > $carousel.querySelectorAll('.et_pb_carousel_group .et_pb_portfolio_item').length && ! $carousel.classList.contains( 'et_carousel_hovered' ) ) {

					et_carousel_timer = setTimeout( function() {
						var evt = new MouseEvent("click", {
						    view: window,
						    bubbles: true,
						    cancelable: true,
						    clientX: 20,
						    /* whatever properties you want to give it */
						});
						$carousel.querySelector('.et-pb-arrow-next').dispatchEvent(evt);
						// $carousel.querySelector('.et-pb-arrow-next').click();
					}, parseInt($carousel.dataset.autoRotateSpeed) );

					$carousel.dataset.et_carousel_timer = et_carousel_timer;
				}
			};

			//todo
			[].slice.call($et_pb_fullwidth_portfolio).forEach(function(el,i){
				
			// });
			// $et_pb_fullwidth_portfolio.each(function(){
				var $the_portfolio = el,
					$portfolio_items = el.querySelector('.et_pb_portfolio_items');

				var items_array = [];
				[].slice.call($portfolio_items).forEach(function(item,index){
					items_array.push(item);
				});
				
				$portfolio_items.dataset.items = items_array;
				delete $the_portfolio.dataset.columns_setting_up;

				if ( $the_portfolio.classList.contains('et_pb_fullwidth_portfolio_carousel') ){
					// add left and right arrows
					// $portfolio_items.prepend('<div class="et-pb-slider-arrows"><a class="et-pb-arrow-prev" href="#">' + '<span>Previous</span>' + '</a><a class="et-pb-arrow-next" href="#">' + '<span>Next</span>' + '</a></div>');
					$portfolio_items.insertAdjacentHTML('afterBegin','<div class="et-pb-slider-arrows"><a class="et-pb-arrow-prev" href="#">' + '<span>Previous</span>' + '</a><a class="et-pb-arrow-next" href="#">' + '<span>Next</span>' + '</a></div>');

					set_fullwidth_portfolio_columns( $the_portfolio, true );

					et_carousel_auto_rotate( $the_portfolio );

					$the_portfolio.addEventListener('mouseover', function() {
					   	el.classList.add('et_carousel_hovered');
						if ( typeof el.dataset.et_carousel_timer != 'undefined' ) {
							clearInterval( el.dataset.et_carousel_timer );
						}
					});
					$the_portfolio.addEventListener('mouseout', function() {
					   	el.classList.remove('et_carousel_hovered');
						et_carousel_auto_rotate( el );
					});

					delete $the_portfolio.dataset.carouseling; 

					[].slice.call($the_portfolio.querySelectorAll('.et-pb-slider-arrows a')).forEach(function(clickable,j){
					
					// $the_portfolio.on('click', '.et-pb-slider-arrows a', function(e){
					clickable.addEventListener('click', function(e) {
						
						var $the_portfolio = getParents(clickable, '.et_pb_fullwidth_portfolio')[0], //clicked.parents(),
							$portfolio_items = $the_portfolio.querySelector('.et_pb_portfolio_items'),
							$the_portfolio_items = $portfolio_items.querySelectorAll('.et_pb_portfolio_item'),
							$active_carousel_group = $portfolio_items.querySelector('.et_pb_carousel_group.active'),
							slide_duration = 700,
							items = $portfolio_items.dataset.items,
							columns = parseInt($portfolio_items.dataset.columns),
							item_width = $active_carousel_group.clientWidth / columns, //$active_carousel_group.children().first().innerWidth(),
							original_item_width = ( 100 / columns ) + '%';

							e.preventDefault(); //stop the click, do not follow the link

							if ( typeof $the_portfolio.dataset.carouseling!='undefined' && $the_portfolio.dataset.carouseling!==null ) {
								return;
							}

							$the_portfolio.dataset.carouseling = ''; //eq true

							// $active_carousel_group.children().each(function(){
							[].slice.call($active_carousel_group.children).forEach(function(child,k){
								child.style.width = child.clientWidth + 1 + 'px';
								child.style.position = 'absolute';
								child.style.left = ( child.clientWidth * ( parseInt(child.dataset.position) - 1 ) ) + 'px';
							});

							//todo
							// console.debug('active_carousel_group children[0]', $active_carousel_group.children[0]);
							if ( clickable.classList.contains('et-pb-arrow-next') ) {
								var $next_carousel_group,
									current_position = 1,
									next_position = 1,
									active_items_start = items.indexOf( ($active_carousel_group.children[0])[0] ),
									active_items_end = active_items_start + columns,
									next_items_start = active_items_end,
									next_items_end = next_items_start + columns;

								var div = document.createElement('div');
								div.classList.add('et_pb_carousel_group');
								div.classList.add('next');
								div.style.display = 'none';
								div.style.left = '100%';
								div.style.position = 'absolute';
								div.style.top = '0';

								$next_carousel_group = $active_carousel_group.after(div);
								$next_carousel_group.style.width = $active_carousel_group.clientWidth;
								$next_carousel_group.style.display = 'block';

								// this is an endless loop, so it can decide internally when to break out, so that next_position
								// can get filled up, even to the extent of an element having both and current_ and next_ position
								for( x = 0, total = 0 ; ; x++, total++ ) {
									if ( total >= active_items_start && total < active_items_end ) {
										var cstring = 'changing_position current_position current_position_' + current_position;
										var classes = cstring.split(' ');
										classes.forEach(function(c){
											items[x].classList.add( c );
										})
										items[x].dataset.current_position = current_position ;
										current_position++;
									}

									if ( total >= next_items_start && total < next_items_end ) {
										items[x].dataset.next_position =  next_position ;
										
										var cstring = 'changing_position next_position next_position_' + next_position;
										var classes = cstring.split(' ');
										classes.forEach(function(c){
											items[x].classList.add( c );
										})
										if ( !items[x].classList.contains( 'current_position' ) ) {
											items[x].classList.add('container_append');
										} else {
											var node = items[x].cloneNode(true);
											$active_carousel_group.append(node);
											node.style.display = 'none';
											node.classList.add('delayed_container_append_dup');
											node.setAttribute('id', $( items[x] ).attr('id') + '-dup');
											// items[x].cloneNode(true).appendTo( $active_carousel_group ).hide().classList.add('delayed_container_append_dup').attr('id', $( items[x] ).attr('id') + '-dup' );
											items[x].classList.add('delayed_container_append');
										}

										next_position++;
									}

									if ( next_position > columns ) {
										break;
									}

									if ( x >= ( items.length -1 )) {
										x = -1;
									}
								}

								var sorted = [];
								[].slice.call($portfolio_items.querySelectorAll('.container_append, .delayed_container_append_dup')).forEach(function(unsorted,l){
									sorted.push(unsorted);
								});
								sorted.sort(function(a,b){
									var el_a_position = parseInt( a.dataset.next_position );
									var el_b_position = parseInt( b.dataset.next_position );
									return ( el_a_position < el_b_position ) ? -1 : ( el_a_position > el_b_position ) ? 1 : 0;
								});
								// sorted = $portfolio_items.querySelectorAll('.container_append, .delayed_container_append_dup').sort(function (a, b) {
								// 	var el_a_position = parseInt( $(a).data('next_position') );
								// 	var el_b_position = parseInt( $(b).data('next_position') );
								// 	return ( el_a_position < el_b_position ) ? -1 : ( el_a_position > el_b_position ) ? 1 : 0;
								// });
								sorted.forEach(function(s,m){
									s.style.display = 'block';
									$next_carousel_group.append(s);
								});

								// $( sorted ).show().appendTo( $next_carousel_group );
								[].slice.call($next_carousel_group.children).forEach(function(child,k){
								// $next_carousel_group.children().each(function(){
									child.style.width = item_width + 'px';
									child.style.position = 'absolute';
									child.style.left = item_width * ( parseInt(child.dataset.next_position) - 1 ) + 'px'; //) });
								});

								slideLeft($active_carousel_group, function(){

									[].slice.call($portfolio_items.querySelectorAll('.delayed_container_append')).forEach(function(delayed,l){
									// $portfolio_items.find('.delayed_container_append').each(function(){
										delayed.style.width = item_width + 'px';
										delayed.style.position = 'absolute';
										delayed.style.left = ( item_width * ( parseInt(delayed.dataset.next_position) - 1 ) )  + 'px';
										$next_carousel_group.append( delayed );
									});

									$active_carousel_group.classList.remove('active');
									[].slice.call($active_carousel_group.children).forEach(function(child,k){
									// $active_carousel_group.children().each(function(){
										position = child.dataset.position;
										current_position = child.dataset.current_position;
										
										var cstring = 'position_' + position + ' ' + 'changing_position current_position current_position_' + current_position ;
										var classes = cstring.split(' ');
										classes.forEach(function(c){
											child.classList.remove(c);
										})
										child.dataset.position = '';
										child.dataset.current_position = '';
										child.style.display = 'none';//hide();
										child.style.position = '';
										child.style.width = '';
										child.style.left = '';
										$portfolio_items.append( child );
									});

									$active_carousel_group.parentNode.removeChild($active_carousel_group);
									//$active_carousel_group.remove();

									et_carousel_auto_rotate( $the_portfolio );
								});
								

								$next_carousel_group.classList.add('active');
								$next_carousel_group.style.position = 'absolute';
								$next_carousel_group.style.top = 0;
								$next_carousel_group.style.left = '100%';

								// $next_carousel_group.animate({
									// left: '0%'
								// }, {
									// duration: slide_duration,
									// complete: function(){
										setTimeout(function(){
											$next_carousel_group.classList.remove('next');
											$next_carousel_group.classList.add('active');
											$next_carousel_group.style.position = '';
											$next_carousel_group.style.width = '';
											$next_carousel_group.style.top = '';
											$next_carousel_group.style.left = '';

											$next_carousel_group.querySelector('.delayed_container_append_dup').parentNode.removeChild($next_carousel_group.querySelector('.delayed_container_append_dup'));

											[].slice.call($next_carousel_group.querySelectorAll('.changing_position')).forEach(function(changing,m){
											// .each(function( index ){
												position = changing.dataset.position;
												current_position = changing.dataset.current_position;
												next_position = changing.dataset.next_position;
												var cstring = 'container_append delayed_container_append position_' + position + ' ' + 'changing_position current_position current_position_' + current_position + ' next_position next_position_' + next_position;
												var classes = cstring.split(' ');
												classes.forEach(function(c){
													changing.classList.remove(c);
												})
												changing.dataset.current_position = '';
												changing.dataset.next_position = '';
												changing.dataset.position = ( m + 1 );
											});

											[].slice.call($next_carousel_group.children).forEach(function(c,n){
												c.style.position = '';
												c.style.width = original_item_width + 'px';
												c.style.left = '';
											});

											delete $the_portfolio.dataset.carouseling; //false
										}, 100 );
									// }
								// } );
							} else {
								var $prev_carousel_group,
									current_position = columns,
									prev_position = columns,
									columns_span = columns - 1,
									active_items_start = items.indexOf( $active_carousel_group.children().last()[0] ),
									active_items_end = active_items_start - columns_span,
									prev_items_start = active_items_end - 1,
									prev_items_end = prev_items_start - columns_span;

								// $prev_carousel_group = $('<div class="et_pb_carousel_group prev" style="display: none;left: 100%;position: absolute;top: 0;">').insertBefore( $active_carousel_group );
								$active_carousel_group.insertAdjacentHTML('beforeBegin','<div class="et_pb_carousel_group prev" style="display: none;left: 100%;position: absolute;top: 0;"></div>'); //.insertBefore( $active_carousel_group );
								document.querySelector('.et_pb_carousel_group.prev').style.left =  '-' + $active_carousel_group.clientWidth + 'px';
								document.querySelector('.et_pb_carousel_group.prev').style.width = $active_carousel_group.clientWidth + 'px';
								document.querySelector('.et_pb_carousel_group.prev').style.display = 'block';//.show();

								// this is an endless loop, so it can decide internally when to break out, so that next_position
								// can get filled up, even to the extent of an element having both and current_ and next_ position
								for( x = ( items.length - 1 ), total = ( items.length - 1 ) ; ; x--, total-- ) {

									if ( total <= active_items_start && total >= active_items_end ) {

										var cstring =  'changing_position current_position current_position_' + current_position ;
										var classes = cstring.split(' ');
										classes.forEach(function(c){
											items[x].classList.add(c);
										})
										
										items[x].dataset.current_position = current_position ;
										current_position--;
									}

									if ( total <= prev_items_start && total >= prev_items_end ) {
										items[x].dataset.prev_position = prev_position ;
										var cstring =  'changing_position prev_position prev_position_' + prev_position  ;
										var classes = cstring.split(' ');
										classes.forEach(function(c){
											items[x].classList.add(c);
										})

										if ( !items[x].classList.contains( 'current_position' ) ) {
											items[x].classList.add('container_append');
										} else {
											var newItem = items[x].cloneNode(true);
											$active_carousel_group.append(newItem);
											newItem.classList.add('delayed_container_append_dup').setAttribute('id', items[x].getAttribute('id') + '-dup' );
											items[x].classList.add('delayed_container_append');
										}

										prev_position--;
									}

									if ( prev_position <= 0 ) {
										break;
									}

									if ( x === 0 ) {
										x = items.length;
									}
								}

								var sorted = [];
								[].slice.call($portfolio_items.querySelectorAll('.container_append, .delayed_container_append_dup')).forEach(function(unsorted,l){
									sorted.push(unsorted);
								});
								sorted.sort(function(a,b){
									var el_a_position = parseInt( a.dataset.prev_position );
									var el_b_position = parseInt( b.dataset.prev_position );
									return ( el_a_position < el_b_position ) ? -1 : ( el_a_position > el_b_position ) ? 1 : 0;
								});

								sorted.forEach(function(s,m){
									s.style.display = 'block';
									$prev_carousel_group.append(s);
								});

								// $( sorted ).show().appendTo( $prev_carousel_group );

								// $prev_carousel_group.children().each(function(){
								// 	$(this).css({'width': item_width, 'position':'absolute', 'left': ( item_width * ( $(this).data('prev_position') - 1 ) ) });
								// });
								[].slice.call($prev_carousel_group.children).forEach(function(child,k){
								// $next_carousel_group.children().each(function(){
									child.style.width = item_width + 'px';
									child.style.position = 'absolute';
									child.style.left = item_width * ( parseInt(child.dataset.prev_position) - 1 ) + 'px'; //) });
								});
								////////////

								slideLeft($active_carousel_group, function(){

									[].slice.call($portfolio_items.querySelectorAll('.delayed_container_append')).forEach(function(delayed,l){
									// $portfolio_items.find('.delayed_container_append').each(function(){
										delayed.style.width = item_width + 'px';
										delayed.style.position = 'absolute';
										delayed.style.left = ( item_width * ( parseInt(delayed.dataset.next_position) - 1 ) ) + 'px' ;
										$prev_carousel_group.append( delayed );
									});

									$active_carousel_group.classList.remove('active');
									[].slice.call($active_carousel_group.children).forEach(function(child,k){
									// $active_carousel_group.children().each(function(){
										position = child.dataset.position;
										current_position = child.dataset.current_position;
										
										var cstring = 'position_' + position + ' ' + 'changing_position current_position current_position_' + current_position ;
										var classes = cstring.split(' ');
										classes.forEach(function(c){
											child.classList.remove(c);
										})
										child.dataset.position = '';
										child.dataset.current_position = '';
										child.style.display = 'none';//hide();
										child.style.position = '';
										child.style.width = '';
										child.style.left = '';
										$portfolio_items.append( child );
									});

									$active_carousel_group.parentNode.removeChild($active_carousel_group);
									//$active_carousel_group.remove();

									// et_carousel_auto_rotate( $the_portfolio );
								});

								///////////
								// $active_carousel_group.animate({
								// 	left: '100%'
								// }, {
								// 	duration: slide_duration,
								// 	complete: function() {
								// 		// $portfolio_items.find('.delayed_container_append').reverse().each(function(){
								// 		// 	$(this).css({'width': item_width, 'position':'absolute', 'left': ( item_width * ( $(this).data('prev_position') - 1 ) ) });
								// 		// 	$(this).prependTo( $prev_carousel_group );
								// 		// });

								// 		// $active_carousel_group.classList.remove('active');
								// 		// $active_carousel_group.children().each(function(){
								// 		// 	position = $(this).data('position');
								// 		// 	current_position = $(this).data('current_position');
								// 		// 	$(this).classList.remove('position_' + position + ' ' + 'changing_position current_position current_position_' + current_position );
								// 		// 	$(this).data('position', '');
								// 		// 	$(this).data('current_position', '');
								// 		// 	$(this).hide();
								// 		// 	$(this).css({'position': '', 'width': '', 'left': ''});
								// 		// 	$(this).appendTo( $portfolio_items );
								// 		// });

								// 		$active_carousel_group.remove();
								// 	}
								// } );

								$prev_carousel_group.classList.add('active')
								$prev_carousel_group.style.position = 'absolute';
								$prev_carousel_group.style.top = 0;
								$prev_carousel_group.style.left ='-100%';

								// $prev_carousel_group.animate({
								// 	left: '0%'
								// }, {
								// 	duration: slide_duration,
								// 	complete: function(){
										setTimeout(function(){
											$prev_carousel_group.classList.remove('prev');
											$prev_carousel_group.classList.add('active');
											$prev_carousel_group.style.position = '';
											$prev_carousel_group.style.width = '';
											$prev_carousel_group.style.top = '';
											$prev_carousel_group.style.left = '';

											var rem = $prev_carousel_group.querySelectorAll('.delayed_container_append_dup');
											[].slice.call(rem).forEach(function(r,k){
												r.parentNode.removeChild(r);
											});
											// $active_carousel_group.parentNode.removeChild($active_carousel_group);
											// $prev_carousel_group.find('.delayed_container_append_dup').remove();

											[].slice.call($prev_carousel_group.find('.changing_position')).forEach(function(item,k){
											// $prev_carousel_group.find('.changing_position').each(function( index ){
												position = item.dataset.position;
												current_position = item.dataset.current_position;
												prev_position = item.dataset.prev_position;

												var cstring = 'container_append delayed_container_append position_' + position + ' ' + 'changing_position current_position current_position_' + current_position + ' prev_position prev_position_' + prev_position ;
												var classes = cstring.split(' ');
												classes.forEach(function(c){
													item.classList.remove(c);
												});

												// item.classList.remove('container_append delayed_container_append position_' + position + ' ' + 'changing_position current_position current_position_' + current_position + ' prev_position prev_position_' + prev_position );
												delete item.dataset.current_position;
												delete item.dataset.prev_position;//', '');
												position = k + 1;
												item.dataset.position = position ;
												item.classList.add('position_' + position );
											});

											// $prev_carousel_group.children().css({'position': '', 'width': original_item_width, 'left': ''});
											[].slice.call($prev_carousel_group.children).forEach(function(child,l){
												child.style.position = '';
												child.style.width = original_item_width + 'px';
												child.style.left = '';
											});
											delete $the_portfolio.dataset.carouseling;//', false);
										}, 100 );
									// }
								// } );
							} //else

						}); //onclick

					}); //forEach 

				} else {
					// setup fullwidth portfolio grid
					set_fullwidth_portfolio_columns( $the_portfolio, false );
				}

			});
		}

		var set_filterable_grid_items;
		var set_filterable_grid_pages;
		var set_filterable_portfolio_hash;

		if ( $et_pb_filterable_portfolio!==null ) { 

			// $(window).load(function(){
				[].slice.call($et_pb_filterable_portfolio).forEach(function(the_portfolio,k){
				// $et_pb_filterable_portfolio.each(function(){
					var $the_portfolio = $(the_portfolio),
					$the_portfolio_items = $the_portfolio.find('.et_pb_portfolio_items');

					//j'ai cronstruit un portfolio avec des filtres débrayables
					//donc on peut avoir un $et_pb_filterable_portfolio vide (voir le shortcode kz_pb_portfolio)
					if ($the_portfolio_items.length) {

						$the_portfolio_items.imagesLoaded( function() {

							$('.waiting').hide();

							$the_portfolio.show(); //after all the content is loaded we can show the portfolio

							$the_portfolio_items.masonry({
								itemSelector : '.et_pb_portfolio_item',
								columnWidth : $the_portfolio.find('.column_width').innerWidth(),
								gutter : $the_portfolio.find('.gutter_width').innerWidth(),
								transitionDuration: 0
							});

							set_filterable_grid_items( $the_portfolio );

						});

						$the_portfolio.on('click', '.et_pb_portfolio_filter a', function(e){
							e.preventDefault();
							var category_slug = $(this).data('category-slug');
							$the_portfolio_items = $(this).parents('.et_pb_filterable_portfolio').find('.et_pb_portfolio_items');

							if ( 'all' == category_slug ) {
								$the_portfolio.find('.et_pb_portfolio_filter a').classList.remove('active');
								$the_portfolio.find('.et_pb_portfolio_filter_all a').classList.add('active');
								$the_portfolio.find('.et_pb_portfolio_item').show();
							} else {
								$the_portfolio.find('.et_pb_portfolio_filter_all').classList.remove('active');
								$the_portfolio.find('.et_pb_portfolio_filter a').classList.remove('active');
								$the_portfolio.find('.et_pb_portfolio_filter_all a').classList.remove('active');
								$(this).classList.add('active');

								$the_portfolio_items.find('.et_pb_portfolio_item').hide();
								$the_portfolio_items.find('.et_pb_portfolio_item.project_category_' + $(this).data('category-slug') ).show();
							}

							set_filterable_grid_items( $the_portfolio );
							setTimeout(function(){
								set_filterable_portfolio_hash( $the_portfolio );
							}, 500 );
						});

						$(this).on('et_hashchange', function( event ){
							var params = event.params;
							$the_portfolio = $( '#' + event.target.id );

							if ( !$the_portfolio.find('.et_pb_portfolio_filter a[data-category-slug="' + params[0] + '"]').classList.contains('active') ){
								$the_portfolio.find('.et_pb_portfolio_filter a[data-category-slug="' + params[0] + '"]').click();
							}

							if ( params[1] ) {
								setTimeout(function(){
									if ( !$the_portfolio.find('.et_pb_portofolio_pagination a.page-' + params[1]).classList.contains('active') ) {
										$the_portfolio.find('.et_pb_portofolio_pagination a.page-' + params[1]).classList.add('active').click();
									}
								}, 300 );
							}
						});

					}

				});

			// }); // End $(window).load()

			set_filterable_grid_items = function( $the_portfolio ) {

				var min_height = 0,
					$the_portfolio_items = $the_portfolio.find('.et_pb_portfolio_items'),
					active_category = $the_portfolio.find('.et_pb_portfolio_filter > a.active').data('category-slug'),
					masonry_data = Masonry.data( $the_portfolio_items[0] );

				$the_portfolio_items.masonry('option', {
					'columnWidth': $the_portfolio.find('.column_width').innerWidth(),
					'gutter': $the_portfolio.find('.gutter_width').innerWidth()
				});

				if ( !$the_portfolio.classList.contains('et_pb_filterable_portfolio_fullwidth') ) {
					$the_portfolio.find( '.et_pb_portfolio_item' ).css({ minHeight : '', height : '' });
					$the_portfolio_items.masonry();
					if ( masonry_data.cols > 1 ) {
						$the_portfolio.find( '.et_pb_portfolio_item' ).css({ minHeight : '', height : '' });
						$the_portfolio.find( '.et_pb_portfolio_item' ).each( function() {
							if ( $(this).outerHeight() > min_height )
								min_height = parseInt( $(this).outerHeight() ) + parseInt( $(this).css('marginBottom').slice(0, -2) ) + parseInt( $(this).css('marginTop').slice(0, -2) );
						} );
						$the_portfolio.find( '.et_pb_portfolio_item' ).css({ height : min_height, minHeight : min_height });
					}
				}

				if( 'all' === active_category ) {
					$the_portfolio_visible_items = $the_portfolio.find('.et_pb_portfolio_item');
				} else {
					$the_portfolio_visible_items = $the_portfolio.find('.et_pb_portfolio_item.project_category_' + active_category);
				}

				var visible_grid_items = $the_portfolio_visible_items.length,
					posts_number = $the_portfolio.data('posts-number'),
					pages = Math.ceil( visible_grid_items / posts_number );

				set_filterable_grid_pages( $the_portfolio, pages );

				var visible_grid_items = 0;
				var _page = 1;
				$the_portfolio.find('.et_pb_portfolio_item').data('page', '');
				$the_portfolio_visible_items.each(function(i){
					visible_grid_items++;
					if ( 0 === parseInt( visible_grid_items % posts_number ) ) {
						$(this).data('page', _page);
						_page++;
					} else {
						$(this).data('page', _page);
					}
				});

				$the_portfolio_visible_items.filter(function() {
					return $(this).data('page') == 1;
				}).show();

				$the_portfolio_visible_items.filter(function() {
					return $(this).data('page') != 1;
				}).hide();

				$the_portfolio_items.masonry();
			};

			set_filterable_grid_pages = function ( $the_portfolio, pages ) {
				$pagination = $the_portfolio.find('.et_pb_portofolio_pagination');

				if ( !$pagination.length ){
					return;
				}

				$pagination.html('<ul></ul>');
				if ( pages <= 1 ) {
					return;
				}

				$pagination_list = $pagination.children('ul');
				$pagination_list.append('<li class="prev" style="display:none;"><a href="#" data-page="prev" class="page-prev">Prev</a></li>');
				for( var page = 1; page <= pages; page++ ) {
					var first_page_class = page === 1 ? ' active' : '',
						last_page_class = page === pages ? ' last-page' : '',
						hidden_page_class = page >= 5 ? ' style="display:none;"' : '';
					$pagination_list.append('<li' + hidden_page_class + ' class="page page-' + page + '"><a href="#" data-page="' + page + '" class="page-' + page + first_page_class + last_page_class + '">' + page + '</a></li>');
				}
				$pagination_list.append('<li class="next"><a href="#" data-page="next" class="page-next">Next</a></li>');
			};

			// $et_pb_filterable_portfolio.on('click', '.et_pb_portofolio_pagination a', function(e){
			var pagers = document.querySelectorAll('.et_pb_portofolio_pagination a');
			if (pagers!==null) {

				[].slice.call(pagers).forEach(function(pager,i){
					pager.addEventListener('click', function(e) {
				
					e.preventDefault();

					var to_page = pager.dataset.page,
						$the_portfolio = getParents(pager, '.et_pb_filterable_portfolio'),//$(pager).parents(),
						$the_portfolio_items = $the_portfolio.querySelector('.et_pb_portfolio_items');

					var uls = getParents(pager,'ul');
					var active_ul;
					[].slice.call(uls).forEach(function(the_ul,i){
						if (ul.querySelector('a.active')!==null) {
							active_ul = ul.querySelector('a.active');
							// break;
						}
					});

					var to_page;
					if ( pager.classList.contains('page-prev') ) {
						to_page = parseInt(active_ul.dataset.page)  - 1;
					} else if( pager.classList.contains('page-next') ) {
						to_page = parseInt( active_ul.dataset.page ) + 1;
					}

					var current_index =0;
					var total_pages = 0;
					[].slice.call(uls).forEach(function(the_ul,i){
						[].slice.call(the_ul.querySelectorAll('a')).forEach(function(the_a,j){
							the_a.classList.remove('active');
						});
						[].slice.call(the_ul.querySelectorAll('a.page-' + to_page)).forEach(function(the_apage,j){
							the_apage.classList.remove('active');
							current_index = [].slice.call(the_apage.parentNode.parentNode.children).indexOf(the_apage.parentNode);
						});
						total_pages += the_ul.querySelectorAll('li.page').length;

					});
					// $(this).parents('ul').find('a').classList.remove('active');
					// $(this).parents('ul').find('a.page-' + to_page ).classList.add('active');

					// var current_index = $(this).parents('ul').find('a.page-' + to_page ).parent().index(),
						// total_pages = $(this).parents('ul').find('li.page').length;

					var next = nextUntil(pager.parentNode, '.page-' + ( current_index + 3 ) );
					[].slice.call(next).forEach(function(n,i){
						n.style.display = 'block';
					});
					
					var prev = prevUntil(pager.parentNode, '.page-' + ( current_index - 3 ) );
					[].slice.call(prev).forEach(function(n,i){
						n.style.display = 'block';
					});

					[].slice.call(uls).forEach(function(the_ul,i){
						[].slice.call(the_ul.querySelectorAll('li.page')).forEach(function(the_li,j){
							if ( !the_li.classList.contains('prev') && !the_li.classList.contains('next') ) {
								if ( i < ( current_index - 3 ) ) {
									the_li.style.display = 'none';//.hide();
								} else if( i > ( current_index + 1 ) ) {
									the_li.style.display = 'none';//$(this).hide();
								} else {
									the_li.style.display = 'block';//$(this).show();
								}

								if ( total_pages - current_index <= 2 && total_pages - i <= 5 ) {
									the_li.style.display = 'block';//$(this).show();
								} else if( current_index <= 3 && i <= 4 ) {
									the_li.style.display = 'block';//$(this).show();
								}
							}
						});
					});
					// $(this).parents('ul').find('li.page').each(function(i){
					// 	if ( !$(this).classList.contains('prev') && !$(this).classList.contains('next') ) {
					// 		if ( i < ( current_index - 3 ) ) {
					// 			$(this).hide();
					// 		} else if( i > ( current_index + 1 ) ) {
					// 			$(this).hide();
					// 		} else {
					// 			$(this).show();
					// 		}

					// 		if ( total_pages - current_index <= 2 && total_pages - i <= 5 ) {
					// 			$(this).show();
					// 		} else if( current_index <= 3 && i <= 4 ) {
					// 			$(this).show();
					// 		}

					// 	}
					// });

					if ( to_page > 1 ) {
						// $(this).parents('ul').find('li.prev').show();
						[].slice.call(uls).forEach(function(the_ul,i){
							[].slice.call(the_ul.querySelectorAll('li.prev')).forEach(function(the_li,j){
								the_li.style.display = 'block';
							});
						});
					} else {
						// $(this).parents('ul').find('li.prev').hide();
						[].slice.call(uls).forEach(function(the_ul,i){
							[].slice.call(the_ul.querySelectorAll('li.prev')).forEach(function(the_li,j){
								the_li.style.display = 'none';
							});
						});
					}


					//if ( $(this).parents('ul').find('a.active').classList.contains('last-page') ) {
					if ( active_ul.classList.contains('last-page') ){
						// $(this).parents('ul').find('li.next').hide();
						[].slice.call(uls).forEach(function(the_ul,i){
							[].slice.call(the_ul.querySelectorAll('li.next')).forEach(function(the_li,j){
								the_li.style.display = 'none';
							});
						});
					} else {
						// $(this).parents('ul').find('li.next').show();
						[].slice.call(uls).forEach(function(the_ul,i){
							[].slice.call(the_ul.querySelectorAll('li.next')).forEach(function(the_li,j){
								the_li.style.display = 'block';
							});
						});
					}

					// $the_portfolio.find('.et_pb_portfolio_item').hide();
					[].slice.call($the_portfolio.querySelectorAll('.et_pb_portfolio_item')).forEach(function(item,i){
						// [].slice.call(the_ul.querySelectorAll('li.next')).forEach(function(the_li,j){
							item.style.display = 'none';
						// });
					});

					var found = $the_portfolio.querySelectorAll('.et_pb_portfolio_item').filter(function(item){
						return item.dataset.page === to_page;
					});
					[].slice.call(found).forEach(function(item,i){
							item.style.display = 'block';
					});

					$the_portfolio_items.masonry();

					setTimeout(function(){
						set_filterable_portfolio_hash( $the_portfolio );
					}, 500 );
					});
				});

			}

			set_filterable_portfolio_hash = function ( $the_portfolio ) {

				if ( !$the_portfolio.attr('id') ) {
					return;
				}

				var this_portfolio_state = [];
				this_portfolio_state.push( $the_portfolio.attr('id') );
				this_portfolio_state.push( $the_portfolio.find('.et_pb_portfolio_filter > a.active').data('category-slug') );

				if( $the_portfolio.find('.et_pb_portofolio_pagination a.active').length ) {
					this_portfolio_state.push( $the_portfolio.find('.et_pb_portofolio_pagination a.active').data('page') );
				} else {
					this_portfolio_state.push( 1 );
				}

				this_portfolio_state = this_portfolio_state.join( et_hash_module_param_seperator );

				et_set_hash( this_portfolio_state );
			};
		} /*  end if ( $et_pb_filterable_portfolio.length ) */

		/* Portfolio Kidzou */
		if ( $('.kz_pb_filterable_portfolio').length ) {
	

			var $kz_portfolio = $('.kz_pb_filterable_portfolio');

			var isotope_filter = "*"; //initialisé pour un filtrage sur "all" (toutes les categories)

			$kz_portfolio.show("slow");

			$kz_portfolio.on( "click", ".et_pb_portfolio_filter a", function(e) {

				e.preventDefault();
				$(".et_pb_portfolio_filter a").classList.remove('active');
				$(this).classList.add("active");

				var category_slug = $(this).data('category-slug');
				if ( 'all' !== category_slug )
					isotope_filter = ".category-" + category_slug;
				else
					isotope_filter = "*";

				console.debug("isotope_filter " + isotope_filter);
				
				$(".et_pb_blog_grid").isotope({ filter: isotope_filter, layoutMode: 'masonry' });
			});
								
		}

		var set_gallery_grid_items;
		var set_gallery_grid_pages;
		var set_gallery_hash;

		if ( $et_pb_gallery.length ) {

			set_gallery_grid_items = function ( $the_gallery ) {
				var $the_gallery_items_container = $the_gallery.find('.et_pb_gallery_items'),
					$the_gallery_items = $the_gallery_items_container.find('.et_pb_gallery_item');

				var total_grid_items = $the_gallery_items.length,
					posts_number = $the_gallery_items_container.data('per_page'), // TODO: make this depend on col size and how many fit across at a given screenwidth & col size
					pages = Math.ceil( total_grid_items / posts_number );

				$the_gallery_items_container.masonry('option', {
					'columnWidth': $the_gallery.find('.column_width').innerWidth(),
					'gutter': $the_gallery.find('.gutter_width').innerWidth()
				});

				set_gallery_grid_pages( $the_gallery, pages );

				var total_grid_items = 0;
				var _page = 1;
				$the_gallery_items.data('page', '');
				$the_gallery_items.each(function(i){
					total_grid_items++;
					if ( 0 === parseInt( total_grid_items % posts_number ) ) {
						$(this).data('page', _page);
						_page++;
					} else {
						$(this).data('page', _page);
					}

				});

				var visible_items = $the_gallery_items.filter(function() {
					return $(this).data('page') == 1;
				}).show();

				$the_gallery_items.filter(function() {
					return $(this).data('page') != 1;
				}).hide();

				$the_gallery_items_container.masonry();
			};

			set_gallery_grid_pages = function ( $the_gallery, pages ) {
				$pagination = $the_gallery.find('.et_pb_gallery_pagination');

				if ( !$pagination.length ){
					return;
				}

				$pagination.html('<ul></ul>');
				if ( pages <= 1 ) {
					$pagination.hide();
					return;
				}

				$pagination_list = $pagination.children('ul');
				$pagination_list.append('<li class="prev" style="display:none;"><a href="#" data-page="prev" class="page-prev">Prev</a></li>');
				for( var page = 1; page <= pages; page++ ) {
					var first_page_class = page === 1 ? ' active' : '',
						last_page_class = page === pages ? ' last-page' : '',
						hidden_page_class = page >= 5 ? ' style="display:none;"' : '';
					$pagination_list.append('<li' + hidden_page_class + ' class="page page-' + page + '"><a href="#" data-page="' + page + '" class="page-' + page + first_page_class + last_page_class + '">' + page + '</a></li>');
				}
				$pagination_list.append('<li class="next"><a href="#" data-page="next" class="page-next">Next</a></li>');
			};

			set_gallery_hash = function ( $the_gallery ) {

				if ( !$the_gallery.attr('id') ) {
					return;
				}

				var this_gallery_state = [];
				this_gallery_state.push( $the_gallery.attr('id') );

				if( $the_gallery.find('.et_pb_gallery_pagination a.active').length ) {
					this_gallery_state.push( $the_gallery.find('.et_pb_gallery_pagination a.active').data('page') );
				} else {
					this_gallery_state.push( 1 );
				}

				this_gallery_state = this_gallery_state.join( et_hash_module_param_seperator );

				et_set_hash( this_gallery_state );
			};

			$et_pb_gallery.each(function(){
				var $the_gallery = $(this);

				if ( $the_gallery.classList.contains( 'et_pb_gallery_grid' ) ) {
					$the_gallery.imagesLoaded( function() {

						$the_gallery.show(); //after all the content is loaded we can show the gallery

						$the_gallery.find('.et_pb_gallery_items').masonry({
							itemSelector : '.et_pb_gallery_item',
							columnWidth : $the_gallery.find('.column_width').innerWidth(),
							gutter : $the_gallery.find('.gutter_width').innerWidth(),
							transitionDuration: 0
						});

						set_gallery_grid_items( $the_gallery );
					});

					$the_gallery.on('et_hashchange', function( event ){
						var params = event.params;
						$the_gallery = $( '#' + event.target.id );

						if ( page_to == params[0] ) {
							setTimeout(function(){
								if ( !$the_gallery.find('.et_pb_gallery_pagination a.page-' + page_to ).classList.contains('active') ) {
									$the_gallery.find('.et_pb_gallery_pagination a.page-' + page_to ).classList.add('active').click();
								}
							}, 300 );
						}
					});
				} else {
					$the_gallery.et_pb_simple_slider({
						fade_speed 		: 700,
						slide			: '.et_pb_gallery_item'
					});
				}

			});

			$et_pb_gallery.data('paginating', false );
			$et_pb_gallery.on('click', '.et_pb_gallery_pagination a', function(e){
				e.preventDefault();

				var to_page = $(this).data('page'),
					$the_gallery = $(this).parents('.et_pb_gallery'),
					$the_gallery_items_container = $the_gallery.find('.et_pb_gallery_items'),
					$the_gallery_items = $the_gallery_items_container.find('.et_pb_gallery_item');

				if ( $the_gallery.data('paginating') ) {
					return;
				}

				$the_gallery.data('paginating', true );

				if ( $(this).classList.contains('page-prev') ) {
					to_page = parseInt( $(this).parents('ul').find('a.active').data('page') ) - 1;
				} else if( $(this).classList.contains('page-next') ) {
					to_page = parseInt( $(this).parents('ul').find('a.active').data('page') ) + 1;
				}

				$(this).parents('ul').find('a').classList.remove('active');
				$(this).parents('ul').find('a.page-' + to_page ).classList.add('active');

				var current_index = $(this).parents('ul').find('a.page-' + to_page ).parent().index(),
					total_pages = $(this).parents('ul').find('li.page').length;

				$(this).parent().nextUntil('.page-' + ( current_index + 3 ) ).show();
				$(this).parent().prevUntil('.page-' + ( current_index - 3 ) ).show();

				$(this).parents('ul').find('li.page').each(function(i){
					if ( !$(this).classList.contains('prev') && !$(this).classList.contains('next') ) {
						if ( i < ( current_index - 3 ) ) {
							$(this).hide();
						} else if( i > ( current_index + 1 ) ) {
							$(this).hide();
						} else {
							$(this).show();
						}

						if ( total_pages - current_index <= 2 && total_pages - i <= 5 ) {
							$(this).show();
						} else if( current_index <= 3 && i <= 4 ) {
							$(this).show();
						}

					}
				});

				if ( to_page > 1 ) {
					$(this).parents('ul').find('li.prev').show();
				} else {
					$(this).parents('ul').find('li.prev').hide();
				}

				if ( $(this).parents('ul').find('a.active').classList.contains('last-page') ) {
					$(this).parents('ul').find('li.next').hide();
				} else {
					$(this).parents('ul').find('li.next').show();
				}

				$the_gallery_items.hide();
				var visible_items = $the_gallery_items.filter(function( index ) {
					return $(this).data('page') === to_page;
				}).show();

				$the_gallery_items_container.masonry();
				$the_gallery.data('paginating', false );

				setTimeout(function(){
					set_gallery_hash( $the_gallery );
				}, 100 );
			});

		} /*  end if ( $et_pb_gallery.length ) */

		function et_countdown_timer( timer ) {
			var gmt_offset = ( timer.data('gmt-offset') * 100 ).toString();
			if ( gmt_offset.indexOf('-1') ) {
				gmt_offset = -1 * gmt_offset;
				gmt_offset = ( '0' + gmt_offset ).slice(-4);
				gmt_offset = '-' + gmt_offset;
			} else {
				gmt_offset = ( '0' + gmt_offset ).slice(-4);
			}

			var end_date = new Date( timer.data('end-date') + ' GMT' + gmt_offset ).getTime();

			var current_date = new Date(),
				month_names  = [ "January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December" ];
			current_date = ( month_names[current_date.getMonth()] ) + ' ' + current_date.getDate() + ' ' + current_date.getFullYear() + ' ' + current_date.getHours() + ':' + current_date.getMinutes() + ':' + current_date.getSeconds() + ' GMT' + gmt_offset;
			current_date = new Date( current_date ).getTime();

			var seconds_left = ( end_date - current_date ) / 1000;

			days = parseInt(seconds_left / 86400);
			seconds_left = seconds_left % 86400;

			hours = parseInt(seconds_left / 3600);
			hours = hours > 0 ? hours : 0;

			seconds_left = seconds_left % 3600;

			minutes = parseInt(seconds_left / 60);
			minutes = minutes > 0 ? minutes : 0;

			seconds = parseInt(seconds_left % 60);
			seconds = seconds > 0 ? seconds : 0;

			if( days === 0 ) {
				if ( !timer.find('.days > .value').parent('.section').classList.contains('zero') ) {
					timer.find('.days > .value').html( '000' ).parent('.section').classList.add('zero').next().classList.add('zero');
				}
			} else {
				timer.find('.days > .value').html( ('000' + days).slice(-3) );
			}

			if( days === 0 && hours === 0 ) {
				if ( !timer.find('.hours > .value').parent('.section').classList.contains('zero') ) {
					timer.find('.hours > .value').html('00').parent('.section').classList.add('zero').next().classList.add('zero');
				}
			} else {
				timer.find('.hours > .value').html( ( '0' + hours ).slice(-2) );
			}

			if( days === 0 && hours === 0 && minutes === 0 ) {
				if ( !timer.find('.minutes > .value').parent('.section').classList.contains('zero') ) {
					timer.find('.minutes > .value').html('00').parent('.section').classList.add('zero').next().classList.add('zero');
				}
			} else {
				timer.find('.minutes > .value').html( ( '0' + minutes ).slice(-2) );
			}

			if ( days === 0 && hours === 0 && minutes === 0 && seconds === 0 ) {
				if ( !timer.find('.seconds > .value').parent('.section').classList.contains('zero') ) {
					timer.find('.seconds > .value').html('00').parent('.section').classList.add('zero');
				}
			} else {
				timer.find('.seconds > .value').html( ( '0' + seconds ).slice(-2) );
			}
		}

		function et_countdown_timer_labels( timer ) {
			if ( timer.closest( '.et_pb_column_3_8' ).length || timer.children('.et_pb_countdown_timer_container').width() <= 250 ) {
				timer.find('.hours .label').html( timer.find('.hours').data('short') );
				timer.find('.minutes .label').html( timer.find('.minutes').data('short') );
				timer.find('.seconds .label').html( timer.find('.seconds').data('short') );
			}
		}

		if ( $et_pb_countdown_timer.length ) {
			$et_pb_countdown_timer.each(function(){
				var timer = $(this);
				et_countdown_timer_labels( timer );
				et_countdown_timer( timer );
				setInterval(function(){
					et_countdown_timer( timer );
				}, 1000);
			});

		}

		if ( $et_pb_tabs.length ) {
			$et_pb_tabs.et_pb_simple_slider( {
				use_controls   : false,
				use_arrows     : false,
				slide          : '.et_pb_all_tabs > div',
				tabs_animation : true
			} ).on('et_hashchange', function( event ){
				var params = event.params;
				var $the_tabs = $( '#' + event.target.id );
				var active_tab = params[0];
				if ( !$the_tabs.find( '.et_pb_tabs_controls li' ).eq( active_tab ).classList.contains('et_pb_tab_active') ) {
					$the_tabs.find( '.et_pb_tabs_controls li' ).eq( active_tab ).click();
				}
			});

			$et_pb_tabs_li.click( function() {
				var $this_el        = $(this),
					$tabs_container = $this_el.closest( '.et_pb_tabs' ).data('et_pb_simple_slider');

				if ( $tabs_container.et_animation_running ) return false;

				$this_el.classList.add( 'et_pb_tab_active' ).siblings().classList.remove( 'et_pb_tab_active' );

				$tabs_container.data('et_pb_simple_slider').et_slider_move_to( $this_el.index() );

				if ( $this_el.closest( '.et_pb_tabs' ).attr('id') ) {
					var tab_state = [];
					tab_state.push( $this_el.closest( '.et_pb_tabs' ).attr('id') );
					tab_state.push( $this_el.index() );
					tab_state = tab_state.join( et_hash_module_param_seperator );
					et_set_hash( tab_state );
				}

				return false;
			} );
		}

		if ( $et_pb_map.length ) {
			google.maps.event.addDomListener(window, 'load', function() {
				$et_pb_map.each(function(){
					var $this_map_container = $(this);
					var $this_map = $this_map_container.children('.et_pb_map');

						$this_map_container.data('map', new google.maps.Map( $this_map[0], {
							zoom: parseInt( $this_map.data('zoom') ),
							center: new google.maps.LatLng( parseFloat( $this_map.data('center-lat') ) , parseFloat( $this_map.data('center-lng') )),
							mapTypeId: google.maps.MapTypeId.ROADMAP
						}));

						$this_map_container.data('bounds', new google.maps.LatLngBounds() );
						$this_map_container.find('.et_pb_map_pin').each(function(){
							var $this_marker = $(this),
								position = new google.maps.LatLng( parseFloat( $this_marker.data('lat') ) , parseFloat( $this_marker.data('lng') ) );

							$this_map_container.data('bounds').extend( position );

							var marker = new google.maps.Marker({
								position: position,
								map: $this_map_container.data('map'),
								title: $this_marker.data('title'),
								icon: { url: et_custom.images_uri + '/marker.png', size: new google.maps.Size( 46, 43 ), anchor: new google.maps.Point( 16, 43 ) },
								shape: { coord: [1, 1, 46, 43], type: 'rect' }
							});

							if ( $this_marker.find('.infowindow').length ) {
								var infowindow = new google.maps.InfoWindow({
									content: $this_marker.html()
								});

								google.maps.event.addListener(marker, 'click', function() {
									infowindow.open( $this_map_container.data('map'), marker );
								});
							}
						});

						setTimeout(function(){
							if (typeof $this_map_container.data('map').getBounds()!=="undefined") {
								if ( !$this_map_container.data('map').getBounds().contains( $this_map_container.data('bounds').getNorthEast() ) || !$this_map_container.data('map').getBounds().contains( $this_map_container.data('bounds').getSouthWest() ) ) {
									$this_map_container.data('map').fitBounds( $this_map_container.data('bounds') );
								}
							}
						}, 200 );
				});
			} );
		}

		var et_pb_circle_counter_init;
		if ( $et_pb_circle_counter.length ){

			et_pb_circle_counter_init = function($the_counter, animate) {
				$the_counter.easyPieChart({
					easing: 'easeInOutCirc',
					animate: {
						duration: 1800,
						enabled: true
					},
					size: $the_counter.width(),
					barColor: $the_counter.data('bar-bg-color'),
					trackColor: '#000000',
					trackAlpha: 0.1,
					scaleColor: false,
					lineWidth: 5,
					onStart: function() {
						$(this.el).find('.percent p').css({ 'visibility' : 'visible' });
					},
					onStep: function(from, to, percent) {
						$(this.el).find('.percent-value').text( Math.round( percent ) );
					},
					onStop: function(from, to) {
						$(this.el).find('.percent-value').text( $(this.el).data('number-value') );
					}
				});

				$the_counter.data('easyPieChart').update( $the_counter.data('number-value') );
			};

			$et_pb_circle_counter.each(function(){
				var $the_counter = $(this);
				et_pb_circle_counter_init($the_counter, false);

				$the_counter.on('containerWidthChanged', function( event ){
					$the_counter = $( event.target );
					$the_counter.find('canvas').remove();
					$the_counter.removeData('easyPieChart' );
					et_pb_circle_counter_init($the_counter, true);
				});

			});
		}

		if ( $et_pb_number_counter.length ){
			$et_pb_number_counter.each(function(){
				var $this_counter = $(this);
				$this_counter.easyPieChart({
					easing: 'easeInOutCirc',
					animate: {
						duration: 1800,
						enabled: true
					},
					size: 0,
					trackColor: false,
					scaleColor: false,
					lineWidth: 0,
					onStart: function() {
						$(this.el).find('.percent p').css({ 'visibility' : 'visible' });
					},
					onStep: function(from, to, percent) {
						$(this.el).find('.percent-value').text( Math.round( percent ) );
					},
					onStop: function(from, to) {
						$(this.el).find('.percent-value').text( $(this.el).data('number-value') );
					}
				});
			});
		}

		function et_apply_parallax() {
			var $this = $(this),
				element_top = $this.offset().top,
				window_top = $et_window.scrollTop(),
				y_pos = ( ( ( window_top + $et_window.height() ) - element_top ) * 0.3 ),
				main_position;

			main_position = 'translate3d(0, ' + y_pos + 'px, 0px)';

			$this.find('.et_parallax_bg').css( {
				'-webkit-transform' : main_position,
				'-moz-transform'    : main_position,
				'-ms-transform'     : main_position,
				'transform'         : main_position
			} );
		}

		function et_parallax_set_height() {
			var $this = $(this),
				bg_height;

			bg_height = ( $et_window.height() * 0.3 + $this.innerHeight() );

			$this.find('.et_parallax_bg').css( { 'height' : bg_height } );
		}

		$('.et_pb_toggle_title').click( function(){
			var $this_heading = $(this),
				$module = $this_heading.closest('.et_pb_toggle'),
				$content = $module.find('.et_pb_toggle_content'),
				is_accordion = $module.closest( '.et_pb_accordion' ).length,
				$accordion_active_toggle;

			if ( is_accordion ) {
				if ( $module.classList.contains('et_pb_toggle_open') ) {
					return false;
				}

				$accordion_active_toggle = $module.siblings('.et_pb_toggle_open');
			}

			$content.slideToggle( 700, function() {
				if ( $module.classList.contains('et_pb_toggle_close') )
					$module.classList.remove('et_pb_toggle_close').classList.add('et_pb_toggle_open');
				else
					$module.classList.remove('et_pb_toggle_open').classList.add('et_pb_toggle_close');
			} );

			if ( is_accordion ) {
				$accordion_active_toggle.find('.et_pb_toggle_content').slideToggle( 700, function() {
					$accordion_active_toggle.classList.remove( 'et_pb_toggle_open' ).classList.add('et_pb_toggle_close');
				} );
			}
		} );

		var $et_contact_container = $('.et_pb_contact_form_container');

		if ( $et_contact_container.length ) {
			var $et_contact_form = $et_contact_container.find( 'form' ),
				$et_contact_submit = $et_contact_container.find( 'input.et_pb_contact_submit' ),
				$et_inputs = $et_contact_form.find('input[type=text],textarea'),
				et_email_reg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/,
				et_contact_error = false,
				$et_contact_message = $et_contact_container.find('.et-pb-contact-message'),
				et_message = '';

			$et_inputs.live('focus', function(){
				if ( $(this).val() === $(this).siblings('label').text() ) $(this).val("");
			}).live('blur', function(){
				if ($(this).val() === "") $(this).val( $(this).siblings('label').text() );
			});

			$et_contact_form.on('submit', function(event) {
				et_contact_error = false;
				et_message = '<ul>';

				$et_inputs.classList.remove('et_contact_error');

				$et_inputs.each(function(index, domEle){
					if ( $(domEle).val() === '' || $(domEle).val() === $(this).siblings('label').text() ) {
						$(domEle).classList.add('et_contact_error');
						et_contact_error = true;

						var default_value = $(this).siblings('label').text();
						if ( default_value === '' ) default_value = et_custom.captcha;

						et_message += '<li>' + et_custom.fill + ' ' + default_value + ' ' + et_custom.field + '</li>';
					}
					if ( ($(domEle).attr('id') == 'et_contact_email') && !et_email_reg.test($(domEle).val()) ) {
						$(domEle).classList.remove('et_contact_error').classList.add('et_contact_error');
						et_contact_error = true;

						if ( !et_email_reg.test($(domEle).val()) ) et_message += '<li>' + et_custom.invalid + '</li>';
					}
				});

				if ( !et_contact_error ) {
					$href = $(this).attr('action');

					$et_contact_container.fadeTo('fast',0.2).load($href + ' #' + $et_contact_form.closest('.et_pb_contact_form_container').attr('id'), $(this).serializeArray(), function() {
						$et_contact_container.fadeTo('fast',1);
					});
				}

				et_message += '</ul>';

				if ( et_message != '<ul></ul>' )
					$et_contact_message.html(et_message);

				event.preventDefault();
			});
		}

		if ( jQuery && jQuery.fn.fitVids ) {
			jQuery( '.et_pb_slide_video' ).fitVids();

			jQuery( '#main-content' ).fitVids();
		}


		function et_pb_resize_section_video_bg( $video ) {
			$element = typeof $video !== 'undefined' ? $video.closest( '.et_pb_section_video_bg' ) : $( '.et_pb_section_video_bg' );

			$element.each( function() {
				var $this_el = $(this),
					ratio = ( typeof $this_el.attr( 'data-ratio' ) !== 'undefined' ) ? $this_el.attr( 'data-ratio' ) : $this_el.find('video').attr( 'width' ) / $this_el.find('video').attr( 'height' ),
					$video_elements = $this_el.find( '.mejs-video, video, object' ).css( 'margin', 0 ),
					$container = $this_el.closest( '.et_pb_section' ).length ? $this_el.closest( '.et_pb_section' ) : $this_el.closest( '.et_pb_slides' ),
					body_width = $container.width(),
					container_height = $container.innerHeight(),
					width, height;

				if ( typeof $this_el.attr( 'data-ratio' ) == 'undefined' )
					$this_el.attr( 'data-ratio', ratio );

				if ( body_width / container_height < ratio ) {
					width = container_height * ratio;
					height = container_height;
				} else {
					width = body_width;
					height = body_width / ratio;
				}

				$video_elements.width( width ).height( height );
			} );
		}

		function et_pb_center_video( $video ) {
			$element = typeof $video !== 'undefined' ? $video : $( '.et_pb_section_video_bg .mejs-video' );

			$element.each( function() {
				var $video_width = $(this).width() / 2;
				var $video_width_negative = 0 - $video_width;
				$(this).css("margin-left",$video_width_negative );

				if ( typeof $video !== 'undefined' ) {
					if ( $video.closest( '.et_pb_slider' ).length && ! $video.closest( '.et_pb_first_video' ).length )
						return false;

					setTimeout( function() {
						$( this ).closest( '.et_pb_preload' ).classList.remove( 'et_pb_preload' );
					}, 500 );
				}
			} );
		}

		function et_calculate_header_values() {
			var $top_header = $( '#top-header' ),
				secondary_nav_height = $top_header.length && $top_header.is( ':visible' ) ? $top_header.innerHeight() : 0,
				admin_bar_height     = $( '#wpadminbar' ).length ? $( '#wpadminbar' ).innerHeight() : 0;

			et_header_height      = $( '#main-header' ).innerHeight() + secondary_nav_height - 1;
			et_header_modifier    = et_header_height <= 90 ? et_header_height - 29 : et_header_height - 56;
			//et_header_offset      ;

			et_header_offset = et_header_modifier + admin_bar_height;
			et_primary_header_top = secondary_nav_height + admin_bar_height;
	

		}

		function et_fix_slider_height() {
			if ( ! $et_pb_slider.length ) return;

			$et_pb_slider.each( function() {
				var $slide = $(this).find( '.et_pb_slide' ),
					$slide_container = $slide.find( '.et_pb_container' ),
					max_height = 0;

				$slide_container.css( 'min-height', 0 );

				$slide.each( function() {
					var $this_el = $(this),
						height = $this_el.innerHeight();

					if ( max_height < height )
						max_height = height;
				} );

				$slide_container.css( 'min-height', max_height );
			} );
		}
		et_fix_slider_height();

		var $comment_form = $('#commentform');

		et_pb_form_placeholders_init( $comment_form );
		et_pb_form_placeholders_init( $( '.et_pb_newsletter_form' ) );

		$comment_form.submit(function(){
			et_pb_remove_placeholder_text( $comment_form );
		});

		function et_pb_form_placeholders_init( $form ) {
			$form.find('input:text, textarea').each(function(index,domEle){
				var $et_current_input = jQuery(domEle),
					$et_comment_label = $et_current_input.siblings('label'),
					et_comment_label_value = $et_current_input.siblings('label').text();
				if ( $et_comment_label.length ) {
					$et_comment_label.hide();
					if ( $et_current_input.siblings('span.required') ) {
						et_comment_label_value += $et_current_input.siblings('span.required').text();
						$et_current_input.siblings('span.required').hide();
					}
					$et_current_input.val(et_comment_label_value);
				}
			}).bind('focus',function(){
				var et_label_text = jQuery(this).siblings('label').text();
				if ( jQuery(this).siblings('span.required').length ) et_label_text += jQuery(this).siblings('span.required').text();
				if (jQuery(this).val() === et_label_text) jQuery(this).val("");
			}).bind('blur',function(){
				var et_label_text = jQuery(this).siblings('label').text();
				if ( jQuery(this).siblings('span.required').length ) et_label_text += jQuery(this).siblings('span.required').text();
				if (jQuery(this).val() === "") jQuery(this).val( et_label_text );
			});
		}

		// remove placeholder text before form submission
		function et_pb_remove_placeholder_text( $form ) {
			$form.find('input:text, textarea').each(function(index,domEle){
				var $et_current_input = jQuery(domEle),
					$et_label = $et_current_input.siblings('label'),
					et_label_value = $et_current_input.siblings('label').text();

				if ( $et_label.length && $et_label.is(':hidden') ) {
					if ( $et_label.text() == $et_current_input.val() )
						$et_current_input.val( '' );
				}
			});
		}

		// et_duplicate_menu( $('#et-top-navigation ul.nav'), $('#et-top-navigation .mobile_nav'), 'mobile_menu', 'et_mobile_menu' );

		// et_duplicate_menu( $('.et_pb_fullwidth_menu ul.nav'), $('.et_pb_fullwidth_menu .mobile_nav'), 'mobile_menu', 'et_mobile_menu' );

		et_duplicate_menu( 
			document.querySelector('#et-top-navigation ul.nav'), 
			document.querySelector('#et-top-navigation .mobile_nav'), 'mobile_menu', 'et_mobile_menu' );

		et_duplicate_menu( 
			document.querySelector('.et_pb_fullwidth_menu ul.nav'), 
			document.querySelector('.et_pb_fullwidth_menu .mobile_nav'), 'mobile_menu', 'et_mobile_menu' );

		function et_duplicate_menu( menu, append_to, menu_id, menu_class ){
			// var $cloned_nav;

			// menu.clone().attr('id',menu_id).classList.remove().attr('class',menu_class).appendTo( append_to );

			if (menu!==null) {

				var node = menu.cloneNode(true);
				node.setAttribute('id',menu_id);
				node.classList.add(menu_class);
				append_to.appendChild(node);

				// $cloned_nav = append_to.find('> ul');
				// $cloned_nav.find('.menu_slide').remove();
				var child = document.querySelector('#' + menu_id + ' .menu_slide');
				if (child!==null)
					node.removeChild(child);

				// $cloned_nav.find('li:first').classList.add('et_first_mobile_item');
				// console.info(document.querySelector('#' + menu_id));
				document.querySelector('#' + menu_id + ' li:first-child').classList.add('et_first_mobile_item');

				append_to.addEventListener('click', function(e) {
					// if ( $(this).classList.contains('closed') ){
					// 	$(this).classList.remove( 'closed' ).classList.add( 'opened' );
					// 	$cloned_nav.slideDown( 500 );
					// } else {
					// 	$(this).classList.remove( 'opened' ).classList.add( 'closed' );
					// 	$cloned_nav.slideUp( 500 );
					// }
					if (append_to.classList.contains('closed')) {
						append_to.classList.add('opened');
						append_to.classList.remove('closed');
						fadeIn(node);
					} else {
						append_to.classList.add('closed');
						append_to.classList.remove('opened');
						fadeOut(node);
					}
					
					// console.info(e);
					// return false;
				}, false);
			}
			

			// append_to.click( function(e){
			// 	if ( $(this).classList.contains('closed') ){
			// 		$(this).classList.remove( 'closed' ).classList.add( 'opened' );
			// 		$cloned_nav.slideDown( 500 );
			// 	} else {
			// 		$(this).classList.remove( 'opened' ).classList.add( 'closed' );
			// 		$cloned_nav.slideUp( 500 );
			// 	}
			// 	console.info(e);
			// 	return false;
			// } );

			// append_to.find('a').click( function(event){
			// 	event.stopPropagation();
			// } );
		}

		if ( $( '#et-secondary-nav' ).length ) {
			$('#et-top-navigation #mobile_menu').append( $( '#et-secondary-nav' ).clone().html() );
		}

		$et_pb_newsletter_button.click( function( event ) {
			if ( $(this).closest( '.et_pb_login_form' ).length || $(this).closest( '.et_pb_feedburner_form' ).length ) {
				return;
			}

			event.preventDefault();

			var $newsletter_container = $(this).closest( '.et_pb_newsletter' ),
				$firstname = $newsletter_container.find( 'input[name="et_pb_signup_firstname"]' ),
				$lastname = $newsletter_container.find( 'input[name="et_pb_signup_lastname"]' ),
				$email = $newsletter_container.find( 'input[name="et_pb_signup_email"]' ),
				$zipcode = $newsletter_container.find( 'input[name="et_pb_signup_zipcode"]' ),
				list_id = $newsletter_container.find( 'input[name="et_pb_signup_list_id"]' ).val(),
				$result = $newsletter_container.find( '.et_pb_newsletter_result' ).hide(),
				service = $(this).closest( '.et_pb_newsletter_form' ).data( 'service' ) || 'mailchimp';

			$firstname.classList.remove( 'et_pb_signup_error' );
			$lastname.classList.remove( 'et_pb_signup_error' );
			$email.classList.remove( 'et_pb_signup_error' );
			$zipcode.classList.remove( 'et_pb_signup_error' );

			et_pb_remove_placeholder_text( $(this).closest( '.et_pb_newsletter_form' ) );

			if ( $firstname.val() === '' || $email.val() === '' || list_id === '' || $zipcode.val() === '' ) {
				if ( $firstname.val() === '' ) $firstname.classList.add( 'et_pb_signup_error' );

				if ( $email.val() === '' ) $email.classList.add( 'et_pb_signup_error' );

				if ( $zipcode.val() === '' ) $zipcode.classList.add( 'et_pb_signup_error' );

				if ( $firstname.val() === '' )
					$firstname.val( $firstname.siblings( '.et_pb_contact_form_label' ).text() );

				if ( $lastname.val() === '' )
					$lastname.val( $lastname.siblings( '.et_pb_contact_form_label' ).text() );

				if ( $email.val() === '' )
					$email.val( $email.siblings( '.et_pb_contact_form_label' ).text() );

				if ( $zipcode.val() === '' )
					$zipcode.val( $zipcode.siblings( '.et_pb_contact_form_label' ).text() );


				return;
			}

			$.ajax( {
				type: "POST",
				url: et_custom.ajaxurl,
				data:
				{
					action : 'et_pb_submit_subscribe_form',
					et_load_nonce : et_custom.et_load_nonce,
					et_list_id : list_id,
					et_firstname : $firstname.val(),
					et_lastname : $lastname.val(),
					et_email : $email.val(),
					kz_zipcode : $zipcode.val(),
					et_service : service
				},
				success: function( data ){
					if ( data ) {
						var obj = JSON.parse(data);
						var message = obj.error || obj.success;
						$newsletter_container.find( '.et_pb_newsletter_form > p' ).hide();
						$result.html( message ).show();
					} else {
						$result.html( et_custom.subscription_failed ).show();
					}
				}
			} );
		} );

		function et_change_primary_nav_position() {
			var $body = $('body');

			if ( ! $body.classList.contains( 'et_vertical_nav' ) && ( $body.classList.contains( 'et_fixed_nav' ) ) ) {
				$('#main-header').css( 'top', et_primary_header_top );
			}
		}

		window.addEventListener('resize', function(){
			var containerWidthChanged = et_container_width !== $et_container.width(),
				window_width = $et_window.width();

			et_pb_resize_section_video_bg();
			et_pb_center_video();

			et_fix_slider_height();

			if ( $( '.et_pb_blog_grid' ).length )
				$( '.et_pb_blog_grid' ).masonry();

			if ( et_is_fixed_nav && containerWidthChanged ) {
				setTimeout( function() {
					var $top_header = $( '#top-header' ),
						secondary_nav_height = $top_header.length && $top_header.is( ':visible' ) ? $top_header.innerHeight() : 0;

					$main_container_wrapper.css( 'paddingTop', $( '#main-header' ).innerHeight() + secondary_nav_height - 1 );

					et_change_primary_nav_position();

				}, 200 );
			}

			if ( $( '#wpadminbar' ).length && et_is_fixed_nav && window_width >= 740 && window_width <= 782 ) {
				et_calculate_header_values();

				et_change_primary_nav_position();
			}

			$et_pb_fullwidth_portfolio.each(function(){
				set_container_height = $(this).classList.contains('et_pb_fullwidth_portfolio_carousel') ? true : false;
				set_fullwidth_portfolio_columns( $(this), set_container_height );
			});

			if ( containerWidthChanged ) {
				$('.container-width-change-notify').trigger('containerWidthChanged');

				setTimeout( function() {
					$et_pb_filterable_portfolio.each(function(){
						set_filterable_grid_items( $(this) );
					});
					$et_pb_gallery.each(function(){
						if ( $(this).classList.contains( 'et_pb_gallery_grid' ) ) {
							set_gallery_grid_items( $(this) );
						}
					});
				}, 100 );

				et_container_width = $et_container.width();

				etRecalculateOffset = true;
			}
		}, false );

		// document.addEventListener('DOMContentLoaded', function() {
			if ( et_is_fixed_nav ) {
				et_calculate_header_values();

				$main_container_wrapper.css( 'paddingTop', et_header_height - 1 );

				et_change_primary_nav_position();
			}

			if ( jQuery && jQuery( '.et_pb_blog_grid' ).length && jQuery.fn.hashchange) {
				jQuery( '.et_pb_blog_grid' ).masonry( {
					itemSelector : '.et_pb_post'
				} );
			}

			setTimeout( function() {
				document.querySelector( '.et_pb_preload' ).classList.remove( 'et_pb_preload' );
			}, 500 );

			if ( jQuery && jQuery.fn.hashchange ) {
				jQuery(window).hashchange( function(){
					var hash = window.location.hash.substring(1);
					process_et_hashchange( hash );
				});
				jQuery(window).hashchange();
			}

			if ( jQuery && jQuery.fn.waypoint ) {
				jQuery( '.et_pb_counter_container, .et-waypoint' ).waypoint( {
					offset: '75%',
					handler: function() {
						jQuery(this).classList.add( 'et-animated' );
					}
				} );

				if ( jQuery( '.et_pb_circle_counter' ).length ){
					jQuery( '.et_pb_circle_counter' ).each(function(){
						var $this_counter = jQuery(this);
						$this_counter.waypoint({
							offset: '65%',
							handler: function() {
								$this_counter.data('easyPieChart').update( $this_counter.data('number-value') );
							}
						});
					});
				}

				if ( jQuery( '.et_pb_number_counter' ).length ){
					jQuery( '.et_pb_number_counter' ).each(function(){
						var $this_counter = jQuery(this);
						$this_counter.waypoint({
							offset: '75%',
							handler: function() {
								$this_counter.data('easyPieChart').update( $this_counter.data('number-value') );
							}
						});
					});
				}

				if ( et_is_fixed_nav ) {
					jQuery('#main-content').waypoint( {
						offset: function() {
							if ( etRecalculateOffset ) {
								et_calculate_header_values();

								etRecalculateOffset = false;
							}

							return et_header_offset;
						},
						handler : function( direction ) {
							if ( direction === 'down' ) {
								jQuery('#main-header').classList.add( 'et-fixed-header' );
							} else {
								jQuery('#main-header').classList.remove( 'et-fixed-header' );
							}
						}
					} );
				}
			}

			if ( $et_pb_parallax!==null && !et_is_mobile_device ) {
				$et_pb_parallax.each(function(){
					if ( $(this).classList.contains('et_pb_parallax_css') ) {
						return;
					}

					var $this_parent = $(this).parent();

					$.proxy( et_parallax_set_height, $this_parent )();

					$.proxy( et_apply_parallax, $this_parent )();

					$et_window.on( 'scroll', $.proxy( et_apply_parallax, $this_parent ) );

					$et_window.on( 'resize', $.proxy( et_parallax_set_height, $this_parent ) );
					$et_window.on( 'resize', $.proxy( et_apply_parallax, $this_parent ) );
				});
			}

			if ( $('.et_pb_audio_module .mejs-audio').length || $( '.et_audio_content .mejs-audio' ).length ){
				$('.et_pb_audio_module .mejs-audio, .et_audio_content .mejs-audio').each(function(){
					$count_timer = $(this).find('div.mejs-currenttime-container').classList.add('custom');
					$(this).find('.mejs-controls div.mejs-duration-container').replaceWith($count_timer);
				});
			}

		// }, false );
	}, false );

	//searchbox
	// $(document).ready(function() {
	document.addEventListener('DOMContentLoaded', function() {

		//sur la home page, le filtre de recherche des sorties
		if ($(".kz_searchbox").length) {
			
			var options, a;
			options = { 
				source: et_custom.terms_list ,
				messages: {
			        noResults: et_custom.no_results,
			        results: function() {return et_custom.results;}
			    },
			    minLength: 1,
			    delay : 100,
			    select: function(event, ui) {
			    	
			    	kidzouTracker.trackEvent("Filtre Home", "Categorie", ui.item.id, 0);
			    	$("#kz_searchinput").val( ui.item.label );
			    	$('#kz_searchbutton').html('<i class="fa fa-circle-o-notch fa-spin"></i> Recherche');

			    	// console.debug(kidzou_suggest.site_url + "/" + ui.item.id);
			    	
	                window.location.href = et_custom.site_url + "/" + ui.item.id;
	            },
	            //si le user lance une recherche sans selectionner d'item, et sans valider le formulaire
	            search: function( event, ui ) {
	            	$('#kz_searchbutton').attr('href', et_custom.site_url + "/?s=" + $("#kz_searchinput").val());
	            }
			
			};
			if ($.fn.autocomplete) {

				//fix sur l'autocomplete..
				$.ui.autocomplete.prototype._renderItem = function (ul, item) {

			      // Escape any regex syntax inside this.term
			      var cleanTerm = this.term.replace(/[-\/\\^$*+?.()|[\]{}]/g, '\\$&');

			      // Build pipe separated string of terms to highlight
			      var keywords = $.trim(cleanTerm).replace('  ', ' ').split(' ').join('|');

			      // Get the new label text to use with matched terms wrapped
			      // in a span tag with a class to do the highlighting
			      var re = new RegExp("(" + keywords + ")", "gi");
			      var output = item.label.replace(re,  
			         '$1');

			      return $("<li>")
			         .append($("<a class='ui-corner-all'>").html(output))
			         .appendTo(ul);
			   };

				$('.kz_searchbox input').autocomplete(options).data('ui-autocomplete')._renderMenu = function( ul, items ) {
				  var that = this;
				  $.each( items, function( index, item ) {
				    that._renderItemData( ul, item );
				  });
				  $(ul).prepend("<h4>" + et_custom.suggest_title + "</h4>");
				  
				};
			}
			

			//submission du formulaire
			jQuery(".kz_searchbox").submit(function(){
				kidzouTracker.trackEvent("Filtre Home", "Recherche", $("#kz_searchinput").val(), 0);
			});	
			
		}
	}, false);


	
})(jQuery);