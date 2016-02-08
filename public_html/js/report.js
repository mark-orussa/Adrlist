var url = '../includes/reportMethods.php';	

$(document).ready(function(){

	$(document).on('click', "[id^='viewLinkedUserListRole']", function(){
		var linkedUserId = this.id.split('viewLinkedUserListRole');
		linkedUserId = linkedUserId[1];
		var responseElement = $('#responseElement' + linkedUserId);
		var trigger = $('span#viewLinkedUserListRole' + linkedUserId);
		if($('#linkedUserListRole' + linkedUserId).css('display') == 'none'){
			trigger.html('Hide List Role');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'viewLinkedUserListRole',
					'linkedUserId': linkedUserId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error view linked user list role');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '&nbsp;';
					if(result.success == true){
						var returnListRoles = result.returnListRoles;
						$('#linkedUserListRole' + linkedUserId).show().html(returnListRoles);
						showMessage(message).delay(1000).fadeOut(250);
					}else{
						showMessage(message);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		}else{
			$('#linkedUserListRole' + linkedUserId).hide();
			trigger.html('Show List Role');
		}
	})

});