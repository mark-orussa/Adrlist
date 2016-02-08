var timeZones = null;
function determineTimezone(url,callback){
	//Attempt to determine the user's timezone.
	var now = new Date();
	var timestampMilliseconds = now.getTime();
	var offsetMinutes = now.getTimezoneOffset();
	//console.log('The client side time is '+now.getHours()+':'+now.getMinutes() + '\ntimestampMilliseconds: ' + timestampMilliseconds + '\noffsetMinutes: ' + offsetMinutes);
	$.ajax({
		type: 'post',
		url: url,
		data:{
			'mode': 'determineTimeZone',
			'timestampMilliseconds': timestampMilliseconds,
			'offsetMinutes': offsetMinutes
		},
		beforeSend: function(){
			spinner('working…');
		},
		error: function(){
			spinner('error determine time zone',0);
		},
		success: function(result){
			result = $.parseJSON(result);
			var message = result.message ? result.message : '';
			if(result.success == true){
				coverMe();
				timeZones = result.timeZones ? result.timeZones : '';
				if(callback != null){
					callback();
				}
			}else{
				console.log('Did not get the timeZones.');
				showMessage(message,0);
			}
			if(result.debug){
				debugElement.html(result.debug);
			}
		}
	})
}