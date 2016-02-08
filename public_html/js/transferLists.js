var url = '../includes/transferListsMethods.php';

$(document).ready(function(){

	//convertHistCharacters----------------------------------------------------------------------------------------------
	$(document).on('click', "input#convertHistCharacters", function(){
		$('input').attr('disabled', 'disabled');
		var responseElement = $('#topResponseElement');
		$.ajax({
			type: 'post',
			url: url,
			data:{
			'mode': 'convertHist',
			'fromTable': 'charHist',
			'toTable': 'characters',
			'id': 'charId'
			},
				beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error convertHistCharacters');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					//$('#getOldLinesHolder').html(result.returnCode);
					showMessage(message);//.delay(750).fadeOut(250);
				}else{
					showMessage(message);
				}
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
		$("input").removeAttr('disabled');
	})

	//convertHistLines----------------------------------------------------------------------------------------------
	$(document).on('click', "input#convertHistLines", function(){
		$('input').attr('disabled', 'disabled');
		var responseElement = $('#topResponseElement');
		$.ajax({
			type: 'post',
			url: url,
			data:{
			'mode': 'convertHist',
			'fromTable': 'lineHist',
			'toTable': 'lines',
			'id': 'lineId'
			},
				beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error convertHistLines');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					showMessage(message);//.delay(750).fadeOut(250);
				}else{
					showMessage(message);
				}
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
		//$("input").removeAttr('disabled');
	})

	//convertHistLists----------------------------------------------------------------------------------------------------
	$(document).on('click', "input#convertHistLines", function(){
		$('input').attr('disabled', 'disabled');
		var responseElement = $('#topResponseElement');
		$.ajax({
			type: 'post',
			url: url,
			data:{
			'mode': 'convertHist',
			'fromTable': 'listHist',
			'toTable': 'lists',
			'id': 'listId'
			},
				beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error convertHistLists');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					showMessage(message);//.delay(750).fadeOut(250);
				}else{
					showMessage(message);
				}
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
		//$("input").removeAttr('disabled');
	})

});