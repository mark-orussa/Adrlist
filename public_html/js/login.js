$(document).ready(function(){
	var email = $('#loginEmail');
	var pass = $('#loginPassword');
	if(email.val() == ''){
		email.focus();
	}else if(pass.val() == ''){
		//pass.focus();
	}
	
	/*
	$(document).on('focus', "#recaptcha_response_field", function(){
		$(this).attr('go','createNewUser');
	})
		if($('#recaptchaElement').length){
		Recaptcha.create("6LfemMMSAAAAAC5ILUq2wR_Pye4DuiiCqZHJaAD_",
			"recaptchaElement",{theme: "clean",tabindex:17}
		);//callback: Recaptcha.focus_response_field 
	}
	*/
	
	//Login.-------------------------------------------------------------------------------------------------------------------
	uiContent.on('click', "#loginButton", function(){
		var emailVal = email.val();
		var emailCheck = emailValidate(emailVal);
		if(emailCheck !== true){
			validationWarning(emailCheck,email);
			email.focus();
		}else{
			var passVal = pass.val();
			var passwordCheck = passwordValidate(passVal);
			if(passwordCheck !== true){
				validationWarning(passwordCheck,pass);
				pass.focus();
			}else{
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'login',
						'email': emailVal,
						'password': passVal,
						'rememberMe': $('#rememberMe').is(':checked')
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error login');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							var returnUrl = (result.returnUrl ? result.returnUrl : 'error return login');
							window.location.href = returnUrl;
						}else{
							$(".validationWarningPlaceholder").html('<div class="validationWarning">' + message + '</div>');
							pass.val('').focus();
							coverMe();
						}
						if(result.debug){
							debugElement.html(result.debug);
						}
					}
				})
			}
		}
	})
});