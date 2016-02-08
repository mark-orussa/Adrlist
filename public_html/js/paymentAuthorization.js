var url = '../myAccount/paymentAuthorization.php';

$(document).ready(function(){
	$.ajax({
		type: 'post',
		url: url,
		data:{
			'mode': 'parseReturnUrl',
			'returnUrl': location.href
		},
		beforeSend: function(){
			spinner('working…');
		},
		error: function(){
			spinner('error parseReturnUrl');
		},
		success: function(result){
			result = $.parseJSON(result);
			var message = result.message ? result.message : '';
			if(result.success == true){
				spinnerHolder.hide();
				if(result.successUrl){
					var count=5;
					function timer(){
						console.log('started timer');
						count=count-1;
						$("#countDown").html('<div class="bold textLarge">Transaction Complete.<br><br>Returning to My Account.</div><div id="countDown">' + count + '</div>');
						if(count <= 0){
							console.log('timer done');
							clearInterval(counter);
							window.location.href = result.successUrl;
						}
					}
					var counter=setInterval(timer, 1000); //1000 will  run it every 1 second
				}
			}else{
				showMessage(message,false);
			}
			if(result.debug){
				debugElement.html(result.debug);
			}
		}
	})
})