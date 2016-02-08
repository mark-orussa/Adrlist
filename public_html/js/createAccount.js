$(document).ready(function(){
	$('#createFirstName').focus();

	//Create a new user.
	$(document).on('click', "#createNewUser", function(){
		var createFirstName = $('#createFirstName');
		var createFirstNameVal = createFirstName.val();
		var createLastName = $('#createLastName');
		var createLastNameVal = createLastName.val();
		var createEmail = $('#createEmail');
		var createEmailVal = createEmail.val();
		var emailCheck = emailValidate(createEmailVal); 
		var createPass1 = $('#createPass1');
		var createPass1Val = createPass1.val(); 
		var pass1Check = passwordValidate(createPass1Val);
		/*var createPass2 = $('#createPass2');
		var createPass2Val = createPass2.val();
		var recaptchaResponse = $('#recaptcha_response_field');
		var recaptchaResponseVal = recaptchaResponse.val();
		var recaptchaChallengeVal = $('#recaptcha_challenge_field').val();*/
		var invitationCode = $('#invitationCode').html();
		if(createFirstNameVal == ''){
			validationWarning('Please enter a first name.',createFirstName);
			createFirstName.focus();
		}else if(createFirstNameVal.length > 100){
			validationWarning('The first name must be less than 100 characters.',createFirstName);
			createFirstName.focus();
		}else if(createLastNameVal == ''){
			validationWarning('Please enter a last name.',createLastName);
			createLastName.focus();
		}else if(createLastNameVal.length > 100){
			validationWarning('The last name must be less than 100 characters.',createLastName);
			createLastName.focus();
		}else if(emailCheck !== true){
			validationWarning(emailCheck,createEmail);
			createEmail.focus();
		}else if(pass1Check !== true){
			validationWarning(pass1Check,createPass1);
			createPass1.focus();
		}else if(!$('#termsConfirmation').is(':checked')){
			validationWarning('You must agree to the terms to use this service.',$('#termsConfirmation'));
		}/*else if(createPass2Val != createPass1Val){
			validationWarning('Passwords don\'t match.',0);
			createPass2.focus();
		}else if(!local && recaptchaResponseVal == ''){
			showMessage('Please enter the recaptcha text.',0);
			recaptchaResponse.focus();
		}
					'recaptcha_challenge_field': recaptchaChallengeVal,
					'recaptcha_response_field': recaptchaResponseVal,
		*/else{
			if(local){
				recaptchaChallengeVal = 'local';
				recaptchaResponseVal = 'local';
			}	
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'createNewUser',
					'email': createEmailVal,
					'firstName': createFirstNameVal,
					'lastName': createLastNameVal,
					'password': createPass1Val,
					'timeZone': $('#timeZoneSelect').val(),
					'invitationCode': invitationCode
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error create');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						var returnUrl = result.returnUrl ? result.returnUrl : 'error trying to redirect;';
						var pass = result.pass ? result.pass : '';//This is the password whether new or not.
						$.ajax({
							type: 'post',
							url: url,
							data:{
								'mode': 'login',
								'email': createEmailVal,
								'password': pass
							},
							beforeSend: function(){
								spinner('working…');
							},
							error: function(){
								spinner('error attempting to login');
							},
							success: function(result2){
								result2 = charConvert(eval('(' + result2 + ')'));
								var message2 = result2.message ? result2.message : '';
								if(result2.success == true){
									var returnCode = result2.returnCode ? result2.returnCode : '';
									window.location.href = returnCode;
								}else{
									showMessage(message2,false);
								}
								if(result2.debug){
									debugElement.html(result2.debug);
								}
							}
						})
					}else{
						showMessage(message,0);
						if(!local){
							Recaptcha.create(strangeThings,"recaptchaElement",{
									theme: "clean",
									callback: Recaptcha.focus_response_field
								}
							);
						}
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
					coverMe();
				}
			})
		}
	})
	
	/*
	if($('#recaptchaElement').length){
		Recaptcha.create(strangeThings,
			"recaptchaElement",{theme: "clean",tabindex:16}
		);//callback: Recaptcha.focus_response_field
	}

	$(document).on('focus', "#recaptcha_response_field", function(){
		$(this).attr('go','createNewUser');
	})
	*/
});