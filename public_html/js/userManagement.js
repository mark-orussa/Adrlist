var url = '../admin/userManagement.php';

function buildBlockUsers(){
	var searchVal = $('#searchBlockUser').val() !== $('#searchBlockUser').attr('default') ? $('#searchBlockUser').val() : '';
	$.ajax({
		type: 'post',
		url: url,
		data:{
			'mode': 'buildBlockUsers',
			'searchVal': searchVal
		},
		beforeSend: function(){
			spinner('working…');
		},
		error: function(){
			spinner('error build block users');
		},
		success: function(result){
			result = $.parseJSON(result);
			var message = result.message ? result.message : '&nbsp;';	
			if(result.success == true){
				coverMe();
				$('#blockUsersHolder').html(result.buildBlockUsers ? result.buildBlockUsers : 'error return build block users');
			}else{
				showMessage(message,0);
			}
			if(result.debug){
				debugElement.html(result.debug);
			}
		}
	})	
}

$(document).ready(function(){
	$(document).on('click', ".listMaintTrigger", function(){
		$('#' + $(this).attr('triggerthis')).toggle();
	})

	//buildLists
	$(document).on('click', "[id^='buildListsLetters']", function(){
		var letters = $(this).attr('letters');
		$.ajax({
			type: 'post',
			url: url,
			data:{
				'mode': 'buildUMLists',
				'letters': letters
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error buildLists');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					$('#buildUMListsHolder').html(result.buildUMLists ? result.buildUMLists : '&nbsp;');
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

	//Build User Info. Select a range of letters.
	$(document).on('click', "[id^='userLetters']", function(){
		var letters = this.id.split('userLetters');
		letters = letters[1];
		$.ajax({
			type: 'post',
			url: url,
			data:{
				'mode': 'buildUserInfo',
				'letters': letters
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error build user info');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					var buildUserInfo = result.buildUserInfo ? result.buildUserInfo : '&nbsp;';
					$('#userInfoHolder').html(buildUserInfo);
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
	
	//Delete user step 1.
	$(document).on('click', "[id^='deleteUserStep1']", function(){
		var userId = this.id.split('deleteUserStep1');
		userId = userId[1];
		$(this).attr('id', 'deleteUserStep2' + userId).html('Click Again To Verify');
	})
	
	//Delete user step 2.
	$(document).on('click', "[id^='deleteUserStep2']", function(){
		var userId = this.id.split('deleteUserStep2');
		userId = userId[1];
		var makeSure = confirm('* WARNING *\nAll user information, preferences, and ability to view associated lists will be deleted.\nAll line, character, and list creation and modification information associated with this user will become anonymous.\n\nAre you sure you want to delete this user?');
		if(makeSure){$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'deleteUser',
					'userId': userId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error update list role');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						var returnCode = result.returnCode ? result.returnCode : '&nbsp;';
						$('#userInfoHolder').html(returnCode);
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
	
	//Edit folder information.
	//Toggle folder info.
	$(document).on('click', "[id^='viewFolderInfo']", function(){
		var folderId = this.id.split('viewFolderInfo');
		folderId = folderId[1];
		$('#buildListsHolder' + folderId).toggle();
	})
	
	//Update folder info.
	$(document).on('click', "[id^='updateFolderInfo']", function(){
		var folderId = this.id.split('updateFolderInfo');
		folderId = folderId[1];
		var responseElement = $('#message' + folderId);
		var folderName = $('#editFolderName' + folderId);
		var folderNameVal = folderName.val();
		var folderNameResponse = $('#folderNameResponse' + folderId);
		$('[id$="Response'+ folderId + '"]').html('');//Clear all elements ending with Response.
		if(firstNameVal == ''){
			folderNameResponse.html('Please enter a first name.');
			folderName.focus();
		}else if(folderNameResponse.length > 50){
			firstNameResponse.html('The first name must be less than 25 characters.');
			firstName.focus();
		}else{
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'updateUserInfo',
					'folderId': folderId,
					'firstName': firstNameVal,
					'lastName': lastNameVal,
					'email': emailVal
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error update user info');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						var returnCode = result.returnCode ? result.returnCode : '&nbsp;';
						$('#userInfoHolder').html(returnCode);
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

	//Edit user information.
	//Toggle user info.
	$(document).on('click', "[id^='viewUserInfo']", function(){
		var userId = this.id.split('viewUserInfo');
		userId = userId[1];
		$('#userInfoHolder' + userId).toggle();
		if($('#userInfoHolder' + userId).css('display') == 'block'){
			$(this).html('Hide Info');
		}else{
			$(this).html('Edit Info');
		}
	})

	//Row actions.
	$(document).on('click', "[id^='rowActionsButton']", function(){
		var uniqueId = $(this).attr('uniqueId');
		var holder = $('#rowActionsHolder' + uniqueId);
		//Detect is the selected row action menu is displayed.
		if(holder.css('display') == 'none'){
			var show = true;
		}
		if(cover.css('display') == 'block'){
			//Close only floaterContainer children elements.
			fc.find($('[id^=rowActionsHolder]')).slideUp(200);
			fc.find($('[id^=rowActionsButton]')).each(function (){
				var img = $(this).find('img');
				var src = img.attr('src').replace("Down","Right");
				img.attr("src", src);
			});
		}else{
			//Hide all row action menus.
			$('[id^=rowActionsHolder]').slideUp(200);
			$('[id^=rowActionsButton]').each(function (){
				var img = $(this).find('img');
				var src = img.attr('src').replace("Down","Right");
				img.attr("src", src);
			});
		}
		var arrow = $("img", this);
		if(show){
			//Show the selected row action menu.
			holder.slideDown(200);
			var src = arrow.attr("src").replace('Right','Down');
			arrow.attr("src", src);
		}else{
			//Hide the selected row action menu.
			$('#rowActionsHolder' + uniqueId).slideUp(200);
			var src = arrow.attr("src").replace("Down","Right");
			arrow.attr("src", src);
		}
	})

	//Toggle folder lists.
	$(document).on('click', "[id^='toggleFolder']", function(){
		var folderId = $(this).attr('folderid');
		var folderHolder = $('#folderListsHolder' + folderId);
		var imgElement = $('#folderListsImg' + folderId);
		if(folderHolder.css('display') == 'none'){
			var src = imgElement.attr("src").replace('Right','Down');
			imgElement.attr("src", src);
		}else{
			var src = imgElement.attr("src").replace("Down","Right");
			imgElement.attr("src", src);
		}
		folderHolder.toggle("blind", {}, 200);
	});

	//Update user info.
	$(document).on('click', "[id^='updateUserInfo']", function(){
		var userId = this.id.split('updateUserInfo');
		userId = userId[1];
		var responseElement = $('#message' + userId);
		var firstName = $('#editUserFirstName' + userId);
		var firstNameVal = firstName.val();
		var firstNameResponse = $('#firstNameResponse' + userId);
		var lastName = $('#editUserLastName' + userId);
		var lastNameVal = lastName.val();
		var lastNameResponse = $('#lastNameResponse' + userId);
		var email = $('#editUserEmail' + userId);
		var emailVal = email.val();
		var emailResponse = $('#emailResponse' + userId);
		var emailCheck = emailValidate(emailVal);
		$('[id$="Response'+ userId + '"]').html('');//Clear all elements ending with Response.
		if(firstNameVal == ''){
			firstNameResponse.html('Please enter a first name.');
			firstName.focus();
		}else if(firstNameVal.length > 25){
			firstNameResponse.html('The first name must be less than 25 characters.');
			firstName.focus();
		}else if(lastNameVal == ''){
			lastNameResponse.html('Please enter a last name.');
			lastName.focus();
		}else if(lastNameVal.length > 25){
			lastNameResponse.html('The last name must be less than 25 characters.');
			lastName.focus();
		}else if(emailCheck != true){
			emailResponse.html(emailCheck);
			email.focus();
		}else{
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'updateUserInfo',
					'userId': userId,
					'firstName': firstNameVal,
					'lastName': lastNameVal,
					'email': emailVal
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error update user info');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						var returnCode = result.returnCode ? result.returnCode : '&nbsp;';
						$('#userInfoHolder').html(returnCode);
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

	//Update folder role.
	$(document).on('click', "[id^='updateFolderRoleUser']", function(){
		var userId = this.id.split('updateFolderRoleUser');
		userId = userId[1].split('folderId');
		folderId = userId[1];
		userId = userId[0];
		var responseElement = $('#message' + userId);
		var dropDown = $('[id^="folderRoleUser' + userId + 'folderId' + folderId + '"]');
		var oldRole = dropDown.attr('id').split('folderRoleId');
		oldRole = oldRole[1];
		var newRole = dropDown.val();
		$.ajax({
			type: 'post',
			url: url,
			data:{
				'mode': 'updateFolderRole',
				'userId': userId,
				'folderId': folderId,
				'oldRole': oldRole,
				'newRole': newRole
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error update Folder role');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					dropDown.attr('id', 'folderRoleUser' + userId + 'folderId' + folderId + 'Role' + newRole);
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

	//Update list role.
	$(document).on('click', "[id^='updateListRole']", function(){
		var userId = this.id.split('updateListRole');
		userId = userId[1];
		var responseElement = $('#message' + userId);
		var dropDown = $(this).prev('[id^="user' + userId + 'List"]');
		var newRoleId = dropDown.val();
		var listId = dropDown.attr('id').split('List');
		listId = listId[1];
		listId = listId.split('Role');
		var oldRoleId = listId[1];
		listId = listId[0];
		$.ajax({
			type: 'post',
			url: url,
			data:{
				'mode': 'updateListRole',
				'userId': userId,
				'listId': listId,
				'oldRoleId': oldRoleId,
				'newRoleId': newRoleId
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error update list role');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					dropDown.attr('id', 'user' + userId + 'List' + listId + 'Role' + newRoleId);
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
	
	//Update site role.
	$(document).on('click', "[id*='updateSiteRole']", function(){
		var userId = this.id.split('updateSiteRole');
		userId = userId[1];
		var newRole = $("input[name='role" + userId + "']:checked").val(); 
		var responseElement = $('#message' + userId);
		$.ajax({
				type: 'post',
				url: url,
				data:{'mode': 'updateSiteRole',
				'userId': userId,
				'newRole': newRole
			},
			beforeSend: function(){
				spinner('working…');
			},
			error: function(){
				spinner('error update site role');
			},
			success: function(result){
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if(result.success == true){
					$('#message' + userId).show().html(message).delay(750).fadeOut(250);
				}else{
					$('#message' + userId).show().html(message);
				}
				if(result.debug){
					debugElement.html(result.debug);
				}
			}
		})
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
					var message = result.message ? result.message : '';
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