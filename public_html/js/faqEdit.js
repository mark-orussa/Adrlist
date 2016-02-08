$(document).ready(function(){
	var url = '../admin/faqEdit.php';

	//Add FAQ.-------------------------------------------------------------------------------------------------------------
	$(document).on('click', "#addFaqButton", function(){
		var chooseTopicDropDownVal = $('#chooseTopicDropDown').val();
		var addQ = $('#addQ');
		var addQVal = addQ.val();
		var addA = $('#addA');
		var addAVal = addA.val();
		if(addQVal != '' && addAVal != ''){
			$.ajax({
				type: 'post',
				url: url,
				data:{"mode": 'addFaq', "addQVal": addQVal, "addAVal": addAVal, "chooseTopicDropDownVal": chooseTopicDropDownVal},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error add a faq');
				},
				success: function(result){//If data is retrieved, store it in result.
					result = $.parseJSON(result);
					var message = result.message ? result.message : '&nbsp;';
					if(result.success == true){
						var returnCode = result.returnCode ? result.returnCode : '';
						$('#buildFaqs').html(returnCode);
						addQ.val('');
						addA.val('');
						coverMe();
						showMessage(message,1);
					}else{
						showMessage(message,0);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		}else{
			showMessage('Please enter a question and answer.',0);
		}
	})

	//Add topic.-----------------------------------------------------------------------------------------------------------
	$(document).on('click', "#addTopicButton", function(){
		var addTopic = $('#addTopic');
		var addTopicVal = addTopic.val();
		if(addTopicVal != ''){
			$.ajax({
				type: 'post',
				url: url,
				data:{
					"mode": 'addTopic',
					"addTopicVal": addTopicVal
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error add topic');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '&nbsp;';
					if(result.success == true){
						$('#buildFaqs').html(result.buildFaqs ? result.buildFaqs : 'error');
						coverMe();
						showMessage(message,1);
					}else{
						showMessage(message,0);	
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		}else{
			showMessage('Please enter a topic.',0);
		}
	})
	
	//Delete FAQ.----------------------------------------------------------------------------------------------
	$(document).on('click', "[id*='deleteFaq']", function(){
		var agree = confirm('Are you sure you want to permanently delete this FAQ?');
		if(agree){
			var faqId = $(this);
			var faqId = this.id.split('deleteFaq');
			faqId = faqId[1];//The second value of the array holds the id number.
			$.ajax({
				type: 'post',
				url: url,
				data:{"mode": 'deleteFaq', "faqId": faqId},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error delete faq');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '&nbsp;';
					if(result.success == true){
						$('#buildFaqs').html(result.buildFaqs ? result.buildFaqs : 'error');
						coverMe();
						showMessage(message,1);
					}else{
						showMessage(message,0);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		}
	})

	//Delete topic.---------------------------------------------------------------------------------------------
	$(document).on('click', "#deleteTopicButton", function(){
		var agree = confirm('Are you sure you want to permanently delete this topic?');
		if(agree){
			var topicId = $('#deleteTopicDropDown').val();
			$.ajax({
				type: 'post',
				url: url,
				data:{"mode": 'deleteTopic', "topicId": topicId},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error delete topic');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '&nbsp;';
					if(result.success == true){
						$('#buildFaqs').html(result.buildFaqs ? result.buildFaqs : 'error');
						coverMe();
						showMessage(message,1);
					}else{
						showMessage(message,0);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		}
	})

	//Modify a FAQ.----------------------------------------------------------------------------------------
	$(document).on('click', "[id*='modifyFaq']", function(){
		var faqId = $(this).attr('faqid');
		var faqQ = $('#q' + faqId);
		var faqQVal = faqQ.val();
		var faqA = $('#a' + faqId);
		var faqAVal = faqA.val();
		var seeAlso = null;
		$.ajax({
			type: 'post',
			url: url,
			data:{
				'mode': 'modifyFaq',
				'faqId': faqId,
				'faqQ': faqQVal,
				'faqA': faqAVal,
				'seeAlso': seeAlso
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error modify faq');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					faqQ.val(result.returnQ);
					faqA.val(result.returnA);
					coverMe();
					showMessage(message,1);
				}else{
					showMessage(message,0);
				}
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
	})
	
	//Modify a topic.---------------------------------------------------------------------------------------------------------
	$(document).on('click', "[id*='topicId']", function(){
		var topicId = $(this).attr('topicid');
		$.ajax({
			type: 'post',
			url: url,
			data:{
				"mode": 'modifyTopic',
				"topicId": topicId,
				"topic": $('#topic' + topicId).val()
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error modify topic');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					var returnCode = result.returnCode ? result.returnCode : '&nbsp;';
					$('#buildFaqs').html(result.buildFaqs ? result.buildFaqs : 'error');
					coverMe();
					showMessage(message,1);
				}else{
					showMessage(message,0);
				}
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
	})
	
	//Move to topic.
	$(document).on('change', "select[id*='topicDropDown']", function(){
		var faqId = $(this);
		var newTopicId = faqId.val();//The mysql id of the topic.
		faqId = this.id.split('topicDropDown');
		faqId = faqId[1];
		$.ajax({
			type: 'post',
			url: url,
			data:{
				'mode': 'changeFaqTopic',
				'faqId': faqId,
				'newTopicId': newTopicId
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				coverMe('error change faq topic',0);
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					var returnCode = result.returnCode ? result.returnCode : '&nbsp;';
					$('#buildFaqs').html(returnCode);
					coverMe();
					showMessage(message,1);
				}else{
					showMessage(message,0);
				}
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
	})

});