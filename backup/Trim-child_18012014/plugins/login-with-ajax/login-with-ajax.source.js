/* Customize from here downwards */
jQuery(document).ready( function($) {	

	// no ajax for form.lwa-form, redirect done by s2member
	$('form.lwa-form').submit(function (event) {
		loginStatus("warning","Vous allez être redirig&eacute;, merci de patienter");
	});
	// no ajax for form.lwa-form, redirect done by s2member
	$('form.lwa-remember, form.lwa-register-form').submit(function(event){
		
		event.preventDefault();
		loginStatus("warning","Merci de patienter...");
		var form = $(this);
		// var url = form.attr('action');
		var ajaxFlag = form.find('.lwa-ajax');
 		if( ajaxFlag.length === 0 ){
 			ajaxFlag = $('<input class="lwa-ajax" name="lwa" type="hidden" value="1" />');
 			form.prepend(ajaxFlag);
 		}
		//Make Ajax Call
		$.ajax({
			type: "POST",
			url: form.attr('action'),
			data: form.serialize(),
			dataType: "jsonp",
			success: function(data){
				lwaAjax( data ); 
				// $(document).trigger('lwa_' + data.action, [data, form]);
			}
		});
	});	
 	

	//Handle a AJAX call for Login, RememberMe or Registration
	function lwaAjax( data ){
		// console.log('data.result ' + data.result);
		if( data!==null && data.result!==null && (data.result === true || data.result === false) ){
			if(data.result === true){
				loginStatus('success',data.message);
			}else{
				loginStatus('warning',data.error);
				//We assume a link in the status message is for a forgotten password
				$('#LoginWithAjax_Status').click(function(event){
					event.preventDefault();
					showRememberForm();
				});
			}
		}else{	
			loginStatus('error','An error has occured. Please try again.');
		}
	}
	

	function loginStatus(type,status) {
		removeLoginStatus();
		jQuery(".close").after('<span id="LoginWithAjax_Status" style="display:none;">' + status + '</span>');	
		jQuery("#LoginWithAjax_Status")
							.removeClass()
							.addClass("radius-light");
		switch (type) {
		case "info":
		 	jQuery("#LoginWithAjax_Status").addClass("info");
		 	break;
		case "warning":
			jQuery("#LoginWithAjax_Status").addClass("warning");
		 	break;
		case "error":
			jQuery("#LoginWithAjax_Status").addClass("error");
		 	break;
		case "success":
			jQuery("#LoginWithAjax_Status").addClass("success");
		 	break;
		default: 
			jQuery("#LoginWithAjax_Status").addClass("info");
			break;
		}
		jQuery("#LoginWithAjax_Status").show();
	}

	function removeLoginStatus() {
		if (jQuery("#LoginWithAjax_Status").length)
		{
			jQuery("#LoginWithAjax_Status").hide();
			jQuery("#LoginWithAjax_Status").remove();
		}
	}

	jQuery("#LoginWithAjax_Links_Remember").click(function(event){
		event.preventDefault();
		showRememberForm();
	});
	jQuery("#LoginWithAjax_Links_Remember_Cancel").click(function(event){
		event.preventDefault();
		resetLoginForm();
	});
	jQuery('#LoginWithAjax_Links_Register').click(function(event){
		event.preventDefault();
		showRegisterForm();
	});
	jQuery('#LoginWithAjax_Links_Register_Cancel').click(function(event){
		event.preventDefault();
		resetLoginForm();
	});
	// jQuery("#LoginWithAjax_Links_Login").click(function(event){
	// 	event.preventDefault();
	// 	jQuery("#LoginWithAjax_Modal").show();
	// 	return false;
	// });

	// jQuery("#LoginWithAjax_Modal span.close").click(function(event){
	// 	event.preventDefault();
	// 	jQuery('#LoginWithAjax_Modal').hide();
	// 	return false;
	// });

	function showRememberForm() 
	{
		jQuery('.lwa-form').hide();
		jQuery('.lwa-register-form').hide();
		jQuery('.lwa-remember').show();
		jQuery('#LoginWithAjax_Links').hide();
	}
	
	function showRegisterForm()
	{
		jQuery('.lwa-remember').hide();
		jQuery(".lwa-form").hide();
		jQuery('.lwa-register-form').show();
		jQuery('#LoginWithAjax_Links').hide();
	}

	function resetLoginForm()
	{
		jQuery(".lwa-form").show();
		jQuery('.lwa-register-form').hide();
		jQuery('.lwa-remember').hide();
		jQuery('#LoginWithAjax_Links').show();
		removeLoginStatus();
	}

});

	
