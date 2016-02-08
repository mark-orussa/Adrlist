var limitTimer = null;
var url = '../errors/report.php';

$(document).ready(function(){
	//Show or hide reporting fields.-----------------------------------------------------------------------------------------
	$(document).on('click', "[id^='errorTrigger']", function(){
		var errorId = $(this).attr('errorid');
		console.log(errorId);
		$("[id$='ShowHide" + errorId + "']").toggle();
	});
	
	$(document).on('click', "#dateRangeGo", function(){
		var startDate = $('#startDate').val();
		var endDate = $('#endDate').val();
		$.ajax({
			type: 'post',
			url: url,
			data:{
				'mode': 'buildDailyDigest',
				'startDate': startDate,
				'endDate': endDate
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error set date range');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					$('#' + result.container).html(result.output ? result.output : 'error return set date range');
					coverMe();
					showMessage(message,true);
				}else{
					showMessage(message,false);
				}
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
	});

	//Maintenance Mode start date picker.
	$('#startDate').datetimepicker({
		format: 'F d, Y H:i:s',
		minDate:0,
		onClose: function(dateText, inst){
			var endDateTextBox = $('#endDate');
			if(endDateTextBox.val() != ''){
				var testStartDate = new Date(dateText);
				var testEndDate = new Date(endDateTextBox.val());
				if(testStartDate > testEndDate){
					//The end date is earlier than the start date. Change it to one hour after.
					testEndDate = new Date(testStartDate.setHours(testStartDate.getHours()+1));
					endDateTextBox.val(testEndDate.dateFormat('F d, Y H:i:s'));
				}
			}else{
				endDateTextBox.val(dateText);
			}
		}
	});
	
	//Maintenance Mode end date picker.
	$('#endDate').datetimepicker({
		format: 'F d, Y H:i:s',
		onClose: function(dateText, inst){
			var startDateTextBox = $('#startDate');
			if(startDateTextBox.val() != ''){
				var testStartDate = new Date(startDateTextBox.val());
				var testEndDate = new Date(dateText);
			}else{
				startDateTextBox.val(dateText);
			}
		},
		onShow:function(dateText){
			var startTime = new Date($('#startDate').val());
			this.setOptions({
				minDate: $('#startDate').val(),
				minTime: (startTime.getHours()+1) + ':00'
			})
		}
	});
	
});