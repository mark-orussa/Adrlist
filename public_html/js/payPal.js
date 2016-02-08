$(document).ready(function(){
	var url = '../admin/userManagement.php';

	//Toggle Query String.
	$(document).on('click', "[id^='viewQueryString']", function(){
		var ipnId = this.id.split('viewQueryString');
		ipnId = ipnId[1];
		var text = $('#viewQueryStringText' + ipnId);
		var holder = $('#queryStringHolder' + ipnId);
		if(holder.css('display') == 'none'){
			$('[id^="queryStringHolder"]').hide();
			$('[id^="viewQueryStringText"]').html('View Query String');
			holder.show();
			text.html('Hide Query String');
		}else{
			holder.hide();
			text.html('View Query String');
		}
	})

	//Toggle IPN request.
	$(document).on('click', "[id^='viewRequest']", function(){
		var ipnId = this.id.split('viewRequest');
		ipnId = ipnId[1];
		var text = $('#viewRequestText' + ipnId);
		var holder = $('#requestHolder' + ipnId);
		if(holder.css('display') == 'none'){
			$('[id^="requestHolder"]').hide();
			$('[id^="viewRequestText"]').html('View Request');
			holder.show();
			text.html('Hide Request');
		}else{
			holder.hide();
			text.html('View Request');
		}
	})

	//View user role.
	$(document).on('click', "span[id^='viewUserRole']", function(){
		var userId = this.id.split('viewUserRole');
		userId = userId[1]; 
		var responseElement = $('#message' + userId);
		var trigger = $('#viewUserRole' + userId);
		if($('#viewUserRoleHolder' + userId).css('display') == 'none'){
			trigger.html('Hide Role');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'viewUserRole',
					'userId': userId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error viewUserRole');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '&nbsp;';
					if(result.success == true){
						var returnCode = result.returnCode ? result.returnCode : '&nbsp;';
						$('#viewUserRoleHolder' + userId).show().html(returnCode);
						$('#message' + userId).show().html(message).delay(750).fadeOut(250);
					}else{
						$('#message' + userId).show().html(message);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		}else{
			$('#viewUserRoleHolder' + userId).hide();
			trigger.html('View Roles');
		}
	})
});