jQuery(document).ready(function(){jQuery(".et_embedded_videos iframe").each(function(e){var t=jQuery(this),n=t.attr("src");if(-1!==n.indexOf("autoplay=1")){n=n.replace(/autoplay=1/g,"");t.addClass("et_autoplay_removed").attr("src","").attr("src",n)}});jQuery("a[class*=fancybox]").fancybox({overlayOpacity:.7,overlayColor:"#000000",transitionIn:"elastic",transitionOut:"elastic",easingIn:"easeOutBack",easingOut:"easeInBack",speedIn:"700",centerOnScroll:true,onComplete:function(e,t,n){if(n.type=="image")return;var r=jQuery("#fancybox-wrap").find("iframe"),i=r.attr("src");if(jQuery(n.href).find("iframe").hasClass("et_autoplay_removed"))r.attr("src",i+"&autoplay=1")},onClosed:function(e,t,n){if(n.type=="image")return;var r=jQuery(n.href).find("iframe.et_autoplay_removed"),i;if(r.length){frame_src=r.attr("src").replace(/autoplay=1/g,"");r.attr("src","").attr("src",frame_src)}}});jQuery("a[class*='et_video_lightbox']").click(function(){var e=jQuery(this).attr("href"),t;et_vimeo=e.match(/vimeo.com\/(.*)/i);if(et_vimeo!=null)t="http://player.vimeo.com/video/"+et_vimeo[1];else{et_youtube=e.match(/watch\?v=([^&]*)/i);if(et_youtube!=null)t="http://youtube.com/embed/"+et_youtube[1]}jQuery.fancybox({overlayOpacity:.7,overlayColor:"#000000",autoScale:false,transitionIn:"elastic",transitionOut:"elastic",easingIn:"easeOutBack",easingOut:"easeInBack",type:"iframe",centerOnScroll:true,speedIn:"700",href:t});return false});var e=jQuery(".et_pt_gallery_entry");e.find(".et_pt_item_image").css("background-color","#000000");jQuery(".zoom-icon, .more-icon").css({opacity:"0",visibility:"visible"});e.hover(function(){jQuery(this).find(".et_pt_item_image").stop(true,true).animate({top:-10},500).find("img.portfolio").stop(true,true).animate({opacity:.7},500);jQuery(this).find(".zoom-icon").stop(true,true).animate({opacity:1,left:43},400);jQuery(this).find(".more-icon").stop(true,true).animate({opacity:1,left:110},400)},function(){jQuery(this).find(".zoom-icon").stop(true,true).animate({opacity:0,left:31},400);jQuery(this).find(".more-icon").stop(true,true).animate({opacity:0,left:128},400);jQuery(this).find(".et_pt_item_image").stop(true,true).animate({top:0},500).find("img.portfolio").stop(true,true).animate({opacity:1},500)});var t=jQuery("#et-contact"),n=t.find("form#et_contact_form"),r=t.find("input#et_contact_submit"),i=n.find("input[type=text],textarea"),s=/^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/,o=false,u=jQuery("#et-contact-message"),a="";i.live("focus",function(){if(jQuery(this).val()===jQuery(this).siblings("label").text())jQuery(this).val("")}).live("blur",function(){if(jQuery(this).val()==="")jQuery(this).val(jQuery(this).siblings("label").text())});n.live("submit",function(){o=false;a="<ul>";i.removeClass("et_contact_error");i.each(function(e,t){if(jQuery(t).val()===""||jQuery(t).val()===jQuery(this).siblings("label").text()){jQuery(t).addClass("et_contact_error");o=true;var n=jQuery(this).siblings("label").text();if(n=="")n=et_ptemplates_strings.captcha;a+="<li>"+et_ptemplates_strings.fill+" "+n+" "+et_ptemplates_strings.field+"</li>"}if(jQuery(t).attr("id")=="et_contact_email"&&!s.test(jQuery(t).val())){jQuery(t).removeClass("et_contact_error").addClass("et_contact_error");o=true;if(!s.test(jQuery(t).val()))a+="<li>"+et_ptemplates_strings.invalid+"</li>"}});if(!o){$href=jQuery(this).attr("action");t.fadeTo("fast",.2).load($href+" #et-contact",jQuery(this).serializeArray(),function(){t.fadeTo("fast",1)})}a+="</ul>";if(a!="<ul></ul>")u.html(a);return false});var f=jQuery("#et-searchinput");etsearchvalue=f.val();f.focus(function(){if(jQuery(this).val()===etsearchvalue)jQuery(this).val("")}).blur(function(){if(jQuery(this).val()==="")jQuery(this).val(etsearchvalue)});var l=jQuery(".et_pt_portfolio_entry");l.hover(function(){jQuery(this).find("img").fadeTo("fast",.8);jQuery(this).find(".et_portfolio_more_icon,.et_portfolio_zoom_icon").fadeTo("fast",1)},function(){jQuery(this).find("img").fadeTo("fast",1);jQuery(this).find(".et_portfolio_more_icon,.et_portfolio_zoom_icon").fadeTo("fast",0)})})