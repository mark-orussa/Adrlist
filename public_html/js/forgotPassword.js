$(document).ready(function(){
	var url = '../forgotPassword/index.php';
	var email = $('#emailReset');
	var pass1 = $('#pass1');
	if($('#emailReset').length > 0){
		$('#emailReset').focus();
	}
	if($("#pass1:visible").length > 0){
		$("#pass1:visible").focus();
	}
	//Reset password 1.--------------------------------------------------------------------------------------------------------
	uiPage.on('click', "#resetPasswordStep1", function(){
		var emailVal = email.val();
		var emailCheck = emailValidate(emailVal);
		if(emailCheck === true){
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'resetPasswordStep1',
					'email': emailVal
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error resetPasswordStep1');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '&nbsp;';
					if(result.success == true){
						$('#resetHolder').html(result.buildReset ? result.buildReset : 'error return reset password step 1');
						$('#resetHolder').trigger("create");//Initialize jquery mobile.
						showMessage(message,0);
					}else{
						showMessage(message,0);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		}else{
			email.focus();
			showMessage(emailCheck,0);
		}
		coverMe();
	})
	
	//Reset password 2.--------------------------------------------------------------------------------------------------------
	uiPage.on('click', "#resetPasswordStep2", function(){
		var pass1Val = pass1.val();
		var pass2 = $('input#pass2');
		var pass2Val = pass2.val();
		var passwordCheck = passwordValidate(pass1Val);
		var codeVal = $(this).attr('resetcode');
		if(passwordCheck != true){
			validationWarning(passwordCheck,pass1);
			document.getElementById('pass1').focus();
		}else if(pass1Val != pass2Val){
			validationWarning("Passwords don't match.",pass2);
			pass2.focus();
		}else{
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'resetPasswordStep2',
					'pass': pass1Val,
					'resetCode': codeVal
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error reset password step 2');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						showMessage(message,0);
						if(result.url){
							window.location.href = result.url;
						}else{
							message = message + 'error return reset password step 1';
						}
					}else{
						showMessage(message,0);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
					coverMe();
				}
			})
		}
	})

	//Detect enter key.-------------------------------------------------------------------------------------------------------
	/*
	DEPRECATED
	$(window).keypress(function(e) {
		if(e.keyCode == 13) {
			$('#resetPassword1').click();
			$('#resetPassword2').click();
		}
	});
	*/
});