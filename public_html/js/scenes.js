var $ = jQuery.noConflict();
var url = '../scenes/index.php';

$(document).ready(function(){
	
	//Add the new scene information to the database.
	$(document).on('click', "#addScene", function(){
		var responseElement = $('#addSceneResponse');
		var newScene = $('#newScene');
		var newSceneVal = newScene.val();
		var newTakes = $('#newTakes');
		var newTakesVal = newTakes.val();
		var newDate = $('#newDate');
		var newDateVal = newDate.val();
		var newCircleTake = $('#newCircleTake');
		var newCircleTakeVal = newCircleTake.val();
		var newNotes = $('#newNotes');
		var newNotesVal = newNotes.val();
		if(newSceneVal == '' || /^\W*$/.test(newSceneVal)){
			showMessage('Please enter a scene number.');
			newScene.focus();
		}else if(/\D+/.test(newTakesVal)){//Search for any non-numeric characters.
			showMessage('The number of takes is numeric only.');
			newTakes.focus();
		}else if(/\D+/.test(newCircleTakeVal)){//Search for any non-numeric characters.
			showMessage('The circle take is numeric only.');
			newCircleTake.focus();
		}else{
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'addScene',
					'scene': newSceneVal,
					'takes': newTakesVal,
					'date': newDateVal,
					'circleTake': newCircleTakeVal,
					'notes': newNotesVal
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error addScene');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '&nbsp;';
					myInformationCurrentPassword.val('');
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		}
	})

});