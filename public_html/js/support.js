$(document).ready(function(){
	$('#supportName').focus();
	/*$(document).on('focus', "#recaptcha_response_field", function(){
		$(this).attr('go','supportSend');
	})
	
	if($('#recaptchaElement').length){
		Recaptcha.create(strangeThings,
			"recaptchaElement",{theme: "clean",tabindex:4}//callback: Recaptcha.focus_response_field
		);
	}
	*/

	uiContent.on('click', "#supportSend", function(){
		var supportName = $('#supportName');
		var supportEmail = $('#supportEmail');
		var supportEmailVal = supportEmail.val();
		var emailCheck = emailValidate(supportEmailVal); 
		var supportMessage = $('#supportMessage');
		var recaptchaResponse = $('#recaptcha_response_field');
		var recaptchaResponseVal = recaptchaResponse.val();
		var recaptchaChallengeVal = $('#recaptcha_challenge_field').val();
		if(supportName.val() == ''){
			validationWarning('Please provide your name.',supportName);
			supportName.focus();
		}else if(emailCheck !== true){
			validationWarning(emailCheck,supportEmail);
			supportEmail.focus();
		}else if(supportMessage.val() == ''){
			validationWarning('Please enter a message.',supportMessage);
			supportMessage.focus();
		}/*else if(!local && recaptchaResponseVal == ''){
			validationWarning('Please enter the recaptcha text.',recaptchaResponse);
			recaptchaResponse.focus();
		}
					'recaptcha_challenge_field': recaptchaChallengeVal,
					'recaptcha_response_field': recaptchaResponseVal
		*/else{
			/*if(local){
				recaptchaChallengeVal = 'local';
				recaptchaResponseVal = 'local';
			}*/
			console.log('url: ' + url);	
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'supportSend',
					'supportName': supportName.val(),
					'supportEmail': supportEmailVal,
					'supportMessage': supportMessage.val()
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error support send');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						showMessage(message,0);
					}else{
						showMessage(message,0);
						/*if(!local){
							Recaptcha.create(strangeThings,"recaptchaElement",{
									theme: "clean",
									callback: Recaptcha.focus_response_field
								}
							);
						}*/
						
					}
					coverMe();
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		}
	})
});