var url = '../admin/amazonBilling.php';

$(document).ready(function(){

	//Maintenance Mode start date picker.
	$('#billingDate').datetimepicker({
		format: 'F d, Y H:i:s',
		onClose: function(dateText, inst){
		}
	});
	
	
	$("#amazonBillingHolder").on("click", "#addMonth", function(){
		$.ajax({
			type:'post',
			url: url,
			data:{
				'mode': 'addMonth',
				'date': $('#billingDate').val()
			},
			beforeSend: function(){
				spinner('working...');
			},
			error: function(){
				spinner('error addMonth');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					$("#dateDestination").val(result.output ? result.output : 'error return addMonth');
					showMessage(message,true);
				}else{
					showMessage(message,false);
				}
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
	})

	/*
	This works great for adding a month to the date.
	$("#amazonBillingHolder").on("click", "#addMonth", function(){
		var currentDate = new Date($('#billingDate').val());
		$("#dateDestination").val(currentDate.addMonths(1));
	})
	*/
	
});