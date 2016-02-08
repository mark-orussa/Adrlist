var url = '../admin/adminTools.php';

$(document).ready(function(){

	//Set debug state.---------------------------------------------------------------------------------------------------------------
	$(document).on("click", "#debugButton", function(){
		spinner('working…');
		console.log('debugButton');;
		var debug = $("#debugButton").html();
		console.log('debug: ' + debug);
		//setCookie(name, value, expires, path, domain).
		if(debug.indexOf('Off') != -1){
			console.log('tried to delete the debug cookie');
			setCookie('DEBUG', false, -1, cookiepath, cookiedomain);
		}else{
			console.log('tried to create the debug cookie');
			setCookie('DEBUG', true, 365, cookiepath, cookiedomain);
		}
		location.reload(1);
	})

	//Set Maintenance Mode.-----------------------------------------------------------------------------------------------------------
	$(document).on('click', "#maintModeSave", function(){
		$.ajax({
			type: 'post',
			url: url,
			data:{
				'mode': 'setMaintMode',
				'maintModeStartTime': $('#maintModeStartTime').val(),
				'maintModeEndTime': $('#maintModeEndTime').val()
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error set maint mode');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '&nbsp;';	
				if(result.success == true){
					coverMe();
					location.reload(1);
				}else{
					showMessage(message,0);
				}
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
	})
	
	//Maintenance Mode start date picker.
	$('#maintModeStartTime').datetimepicker({
		format: 'F d, Y H:i:s',
		minDate:0,
		onClose: function(dateText, inst){
			var endDateTextBox = $('#maintModeEndTime');
			if(endDateTextBox.val() != ''){
				var testStartDate = new Date(dateText);
				var testEndDate = new Date(endDateTextBox.val());
				if(testStartDate > testEndDate){
					//The end date is earlier than the start date. Change it to one hour after.
					var testEndDate = new Date(testStartDate.setHours(testStartDate.getHours()+1));
					console.log('testEndDate: ' + testEndDate);
					endDateTextBox.val(testEndDate.dateFormat('F d, Y H:i:s'));
					//$('#maintModeEndTime').datetimepicker('option', 'minDate', testEndDate);
				}
			}else{
				endDateTextBox.val(dateText);
			}
		}
	});
	
	//Maintenance Mode end date picker.
	$('#maintModeEndTime').datetimepicker({
		format: 'F d, Y H:i:s',
		onClose: function(dateText, inst){
			var startDateTextBox = $('#maintModeStartTime');
			if(startDateTextBox.val() != ''){
				var testStartDate = new Date(startDateTextBox.val());
				var testEndDate = new Date(dateText);
			}else{
				startDateTextBox.val(dateText);
			}
		},
		onShow:function(dateText){
			var startTime = new Date($('#maintModeStartTime').val());
			this.setOptions({
				minDate: $('#maintModeStartTime').val(),
				minTime: (startTime.getHours()+1) + ':00'
			})
		}
	});
	
	//Clear maint mode end time.
	$(document).on('click', "[id^='clearMaintMode']", function(){
		$(this).prev($('[id^=maintMode]')).val('');
	})

	$(document).on('click', ".listMaintTrigger", function(){
		$('#' + $(this).attr('triggerthis')).toggle();
	})
	
});