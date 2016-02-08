$(document).ready(function(){
	var responseElement = $('#responseElement');
	
	$(document).on('click', "#goButton", function(){
		$.ajax({
			type: 'post',
			url: '../test/index.php',
			data:{
				'mode': 'testDb',
				'email': 'phij@markproaudio.com',
				'firstName': 'Phil',
				'lastName': 'Andrade',
				'password': '12341234',
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error test join');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '&nbsp;';
				if(result.success == true){
					var returnUrl = result.returnUrl ? result.returnUrl : 'error trying to redirect;';
					console.log(returnUrl + '?message=' + message);
					//window.location.href = returnUrl + '?message=' + message;
				}
				$('#responseElement').show().html(message);
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
	})
});