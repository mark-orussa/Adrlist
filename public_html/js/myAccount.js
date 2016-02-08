var $ = jQuery.noConflict();
$(document).ready(function(){
	//Billing----------------------------------------------------------------------------------------------------
		$("#buildBillingHolder").on('click', "#addCredits", function(){
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'addCredits'
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					coverMe('error add credits');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.output ? result.output : '');
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
	
		$(".floater").on('click', ".purchasePlan", function(){
			//Select a plan.
			fadeBilling(this)
		})
		
		//Fade billing offers.
		function fadeBilling(elementId){
			$('.column, .purchasePlan, .price').fadeTo(0,1);//Set all columns and buttons to full opacity.
			var billingOfferId = $(elementId).attr('billingOfferId');//
			var currentColumn = $(elementId).attr('column');//Which column did we click?
			var regexColumns = new RegExp(currentColumn, 'g' );//This makes a new regex object.
			$('.column').each(function(){//Loop through the columns.
				var colorMe = true;
				var classes = $(this).attr('class').split(' ');//Gather both classes of the current column.
				for(var i=0; i<classes.length; i++){
					colorMe = classes[i].match(regexColumns) ? false : colorMe;//If this is the current column, don't fade it.
				}
				if(colorMe){
					$(this).fadeTo(400,.5);		
				}
			})
			$('.purchasePlan').each(function(){//Loop through the plans.
				if($(this).attr('billingOfferId') != billingOfferId){
					$(this).fadeTo(400,.4);
				}
			})
			$('.price').each(function(){
				if($(this).attr('billingOfferId') != billingOfferId){
					$(this).fadeTo(400,.4);
				}
			})
		}

		$("#buildBillingHolder").on("click", ".buildBillingHistory", function (){
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'buildBillingHistory'
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error buildBillingHistory');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.output ? result.output : '');
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

		uiContent.on("click", ".changePlan", function (){
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'changePlan'
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					coverMe('error change plans');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.output ? result.output : '');
						var selectedPlan = result.billingOfferId;
						//fadeBilling($(".purchasePlan[billingOfferId='" + selectedPlan + "']")[0]);//[0] returns a javascript object instead of a jquery object.
						//var parentColumn = $(".purchasePlan[billingOfferId='" + selectedPlan + "']").attr('column');
						$(".purchasePlan[billingOfferId='" + selectedPlan + "']").removeClass("purchasePlan").css('opacity','.2');
						$(".price[billingOfferId='" + selectedPlan + "']").css('background-color', '#FF9591');
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

		uiPage.on('click', ".purchasePlanAmazon", function(){
			var billingOfferId = new Array();
			$('.purchasePlan').each(function(){
				if($(this).css('opacity') == 1 && $(this).is(':visible')){
					billingOfferId.push($(this).attr('billingOfferId'));
				}
			})
			console.log(billingOfferId);
			if(billingOfferId.length > 1){
				showMessage('You must select only one plan.',true);
			}else{
				var billingOfferId = billingOfferId[0];
				//console.log('selected billingOfferId: ' + billingOfferId);
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'purchasePlanAmazon',
						'billingOfferId': billingOfferId
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error purchase plan amazon');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							if(result.url){
								coverMe('You are being redirected to Amazon to authorize payment...');
								window.location.href = result.url;
							}
							showMessage(message,true);
						}else{
							showMessage(message,false);
						}
						if(result.debug){
							debugElement.html(result.debug);
						}
					}
				})
			}
		})

	
	//Settings-------------------------------------------------------------------------
		uiContent.on('click', "#saveMyInformation", function(){
			//Save my information. 
			//The term new is used for variable names because all of the values are potentially new.
			var firstName = $('#myInformationFirstName');
			var firstNameVal = firstName.val();
			var lastName = $('#myInformationLastName');
			var lastNameVal = lastName.val();
			
			var primaryEmail = $('#primaryEmail');
			var primaryEmailVal = primaryEmail.val();
			var primaryEmailCheck = emailValidate(primaryEmailVal); 
			
			var primaryEmailRetype = $('#primaryEmailRetype');
			var primaryEmailRetypeVal = primaryEmailRetype.val();
	
			var secondaryEmail = $('#secondaryEmail');
			var secondaryEmailVal = secondaryEmail.val();
			var secondaryEmailCheck = emailValidate(secondaryEmailVal); 
			
			var secondaryEmailRetype = $('#secondaryEmailRetype');
			var secondaryEmailRetypeVal = secondaryEmailRetype.val();
	
			var currentPassword = $('#currentPassword');
			var currentPasswordVal = currentPassword.val(); 
			var currentPasswordCheck = passwordValidate(currentPasswordVal);
			
			var newPassword = $('#newPassword');
			var newPasswordVal = newPassword.val(); 
			var newPasswordCheck = passwordValidate(newPasswordVal);
			var newPasswordRetype = $('#newPasswordRetype');
			var newPasswordRetypeVal = newPasswordRetype.val(); 
			if(firstNameVal == ''){
				validationWarning('Please enter a first name.',firstName);
				firstName.focus();
			}else if(firstNameVal.length > 255){
				validationWarning('The first name must be less than 255 characters.',firstName);
				firstName.focus();
			}else if(lastNameVal == ''){
				validationWarning('Please enter a last name.',lastName);
				lastName.focus();
			}else if(lastNameVal.length > 255){
				validationWarning('The last name must be less than 255 characters.',lastName);
				lastName.focus();
			}else if(primaryEmailCheck != true){
				validationWarning(primaryEmailCheck,primaryEmail);
				primaryEmail.focus();
			}else if(primaryEmailVal != primaryEmailRetypeVal){
				validationWarning('Please verify the primary email addresses match.',primaryEmailRetype);
				primaryEmailRetype.focus();
			}else if(secondaryEmailVal != '' && secondaryEmailCheck != true){
				validationWarning(secondaryEmailCheck,secondaryEmail);
				secondaryEmail.focus();
			}else if(secondaryEmailVal != secondaryEmailRetypeVal){
				validationWarning('Please verify the secondary email addresses match.',secondaryEmailRetype);
				secondaryEmailRetype.focus();
			}else if(primaryEmailVal == secondaryEmailVal){
				validationWarning('The Primary and Secondary email addresses must be different.',secondaryEmail);
				secondaryEmail.focus();
			}else if(currentPasswordVal == ''){
				validationWarning('Please enter your current password.',currentPassword);
				currentPassword.focus();
			}else if(currentPasswordCheck != true){
				validationWarning(currentPasswordCheck,currentPassword);
				currentPassword.focus();
			}else if(newPasswordVal != '' && newPasswordCheck != true){
				validationWarning(newPasswordCheck,newPassword);
				newPassword.focus();
			}else if(newPasswordRetypeVal != newPasswordVal){
				validationWarning("Passwords don't match.",newPasswordRetype);
				newPasswordRetype.focus();
			}else{
				var data = 'mode=saveMyInformation' + 
				'&firstName=' + firstNameVal +
				'&lastName=' + lastNameVal +
				'&primaryEmail=' + primaryEmailVal +
				'&primaryEmailRetype=' + primaryEmailRetypeVal +
				'&secondaryEmail=' + secondaryEmailVal +
				'&secondaryEmailRetype=' + secondaryEmailRetypeVal +
				'&currentPassword=' + currentPasswordVal +
				'&newPassword=' + newPasswordVal +
				'&newPasswordRetype=' + newPasswordRetypeVal;
				$.ajax({
					type: 'post',
					url: url,
					data:data,
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error myInformationSave');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '&nbsp;';
						currentPassword.val('');
						if(result.success == true){
							validationWarning(message,$("#saveMyInformation"));
							newPassword.val('');
							newPasswordRetype.val('');
						}else{
							validationWarning(message,$("#saveMyInformation"));
						}
						if(result.debug){
							debugElement.html(result.debug);
						}
					}
				})
			}
			coverMe();
		})

		uiContent.on('click', "#saveSettings", function(){
			//Save user settings.
			var timeZone = $('#timeZoneSelect').val();
			var dateFormat = $('#dateFormatSelect').val();
			var viewListOnLogin = $('#viewListOnLogin').is(':checked');
			var defaultShowCharacterColors = $('#defaultShowCharacterColors').is(':checked');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'saveSettings',
					'timeZone': timeZone,
					'dateFormat': dateFormat,
					'viewListOnLogin': viewListOnLogin,
					'defaultShowCharacterColors': defaultShowCharacterColors
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					coverMe('error save settings',false);
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe();
						validationWarning(message,$("#saveSettings"));
					}else{
						showMessage(message,false);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		})
});