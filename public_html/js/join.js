$(document).ready(function(){
	var responseElement = $('#responseElement');
	$('#joinFirstName').focus();
	$('#joinUser').show();
	
	//Join an invited user.
	$(document).on('click', "#joinInvited", function(){
		$(this).hide();
		$('[id$="Response"]').html('');//Clear all elements ending with Response.
		var joinFirstName = $('input#joinFirstName');
		var joinFirstNameVal = joinFirstName.val();
		var joinFirstNameResponse = $('#joinFirstNameResponse');
		var joinLastName = $('input#joinLastName');
		var joinLastNameVal = joinLastName.val();
		var joinLastNameResponse = $('#joinLastNameResponse');
		var joinEmail = $('input#joinEmail');
		var joinEmailVal = joinEmail.val();
		var joinEmailResponse = $('#joinEmailResponse');
		var emailCheck = emailValidate(joinEmailVal); 
		var joinPass1 = $('input#joinPass1');
		var joinPass1Val = joinPass1.val(); 
		var joinPass1Response = $('#joinPass1Reply');
		var pass1Check = passwordValidate(joinPass1Val);
		var joinPass2 = $('input#joinPass2');
		var joinPass2Val = joinPass2.val(); 
		var joinPass2Response = $('#joinPass2Response');
		var responseElement = $('#joinResponse');
		var invitationCode = $('input#invitationCode');
		var invitationCodeVal = invitationCode.val();
		if(invitationCodeVal.length != 40){
			$('#responseElement').html('There is a problem with the invitation code.  Please check that the whole code, as seen in the email, exists in the url.');
		}else if(joinFirstNameVal == ''){
			joinFirstNameResponse.html('Please enter a first name.');
			joinFirstName.focus();
		}else if(joinFirstNameVal.length > 50){
			joinFirstNameResponse.html('The first name must be less than 25 characters.');
			joinFirstName.focus();
		}else if(joinLastNameVal == ''){
			joinLastNameResponse.html('Please enter a last name.');
			joinLastName.focus();
		}else if(joinLastNameVal.length > 50){
			joinLastNameResponse.html('The last name must be less than 25 characters.');
			joinLastName.focus();
		}else if(emailCheck != true){
			joinEmailResponse.html(emailCheck);
			joinEmail.focus();
		}else if(joinPass1Val != '' && pass1Check != true){
			joinPass1Response.html(pass1Check);
			joinPass1.focus();
		}else if(joinPass2Val != joinPass1Val){
			joinPass2Response.html('Passwords don\'t match.');
			joinPass2.focus();
		}else{
			var passwordCheck = passwordValidate(joinPass1Val);
			showMessage(passwordCheck);
			if(passwordCheck == true){
				$("input").attr('disabled', 'disabled');
				$.ajax({
					type: 'post',
					url: '../join/index.php',
					data:{
						'mode': 'joinInvited',
						'email': joinEmailVal,
						'firstName': joinFirstNameVal,
						'lastName': joinLastNameVal,
						'password': joinPass1Val,
						'invitationCode': invitationCodeVal
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error join invited');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							var returnUrl = result.returnUrl ? result.returnUrl : 'error join invited return';
							window.location.href = returnUrl + '?message=' + message + '&email=' + joinEmailVal;
						}else{
							showMessage(message,true)
						}
							debugElement.html(result.debug);
						}
				})
			}else{
				joinPass1.focus();
			}
		}
	})
	
	//Join an uninvited user.
	$(document).on('click', "#joinNewUser", function(){
		responseElement.html('');
		$('[id$="Response"]').html('');//Clear all elements ending with Response.
		var joinFirstName = $('input#joinFirstName');
		var joinFirstNameVal = joinFirstName.val();
		var joinFirstNameResponse = $('#joinFirstNameResponse');
		var joinLastName = $('input#joinLastName');
		var joinLastNameVal = joinLastName.val();
		var joinLastNameResponse = $('#joinLastNameResponse');
		var joinEmail = $('input#joinEmail');
		var joinEmailVal = joinEmail.val();
		var joinEmailResponse = $('#joinEmailResponse');
		var emailCheck = emailValidate(joinEmailVal); 
		var joinPass1 = $('input#joinPass1');
		var joinPass1Val = joinPass1.val(); 
		var joinPass1Response = $('#joinPass1Reply');
		var pass1Check = passwordValidate(joinPass1Val);
		var joinPass2 = $('input#joinPass2');
		var joinPass2Val = joinPass2.val(); 
		var joinPass2Response = $('#joinPass2Response');
		var recaptchaResponse = $('#recaptcha_response_field');
		var recaptchaResponseVal = recaptchaResponse.val();
		var recaptchaResponse = $('#recaptchaResponse');
		if(joinFirstNameVal == ''){
			joinFirstNameResponse.html('Please enter a first name.');
			joinFirstName.focus();
		}else if(joinFirstNameVal.length > 50){
			joinFirstNameResponse.html('The first name must be less than 25 characters.');
			joinFirstName.focus();
		}else if(joinLastNameVal == ''){
			joinLastNameResponse.html('Please enter a last name.');
			joinLastName.focus();
		}else if(joinLastNameVal.length > 50){
			joinLastNameResponse.html('The last name must be less than 25 characters.');
			joinLastName.focus();
		}else if(emailCheck != true){
			joinEmailResponse.html(emailCheck);
			joinEmail.focus();
		}else if(joinPass1Val != '' && pass1Check != true){
			joinPass1Response.html(pass1Check);
			joinPass1.focus();
		}else if(joinPass2Val != joinPass1Val){
			joinPass2Response.html('Passwords don\'t match.');
			joinPass2.focus();
		}else if(recaptchaResponseVal == ''){
			if(document.location.href.indexOf('localhost') > 0 || document.location.href.indexOf('8888') > 0){
				recaptchaResponse.val('localhost');
			}else{
				recaptchaResponse.html('Please enter the recaptcha text.');
				recaptchaResponse.focus();
			}
		}else{
			var passwordCheck = passwordValidate(joinPass1Val);
			showMessage(passwordCheck);
			if(passwordCheck == true){
				$('#joinNewUser').hide();
				$.ajax({
					type: 'post',
					url: '../join/index.php',
					data:{
						'mode': 'joinNewUser',
						'email': joinEmailVal,
						'firstName': joinFirstNameVal,
						'lastName': joinLastNameVal,
						'password': joinPass1Val,
						'recaptcha_challenge_field': $('#recaptcha_challenge_field').val(),
						'recaptcha_response_field': recaptchaResponseVal
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error join');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '&nbsp;';
						if(result.success == true){
							var returnUrl = result.returnUrl ? result.returnUrl : 'error trying to redirect;';
							console.log(returnUrl + '?message=' + message)
							//window.location.href = returnUrl + '?message=' + message;
						}else{
							$('#responseElement').show().html(message);
							Recaptcha.create(strangeThings,"recaptchaElement",{
									theme: "clean",
									callback: Recaptcha.focus_response_field
								}
							);
							$('#joinNewUser').show();
						}
						if(result.debug){
							debugElement.html(result.debug);
						}
					}
				})
			}else{
				joinPass1.focus();
			}
		}
	})
		
	//Detect enter key.-------------------------------------------------------------------------------------------------------
	$(window).keypress(function(e) {
		if(e.keyCode == 13) {
			if(document.getElementById('invitationCode')){
				$('input#joinInvited').click();
			}else{
				$('input#joinNewUser').click();
			}
		}
	});
	
	if($('#recaptchaElement').length){
		Recaptcha.create(strangeThings,
			"recaptchaElement",{theme: "clean"/*callback: Recaptcha.focus_response_field*/}
		);
	}
});