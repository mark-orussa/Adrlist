//Refresh the buildListsHolder content.
function rebuildContent(content){
	$('#buildListsHolder').html(content ? content : 'error build lists').trigger("create");
}

$(document).ready(function(){
	//Lists----------------------------------------------------------------------------------------------------
		/* Test
		uiPage.on("click", function(){
			coverMe($("#filler").html());
		})
		*/
		
		uiPage.on("click", ".createFolderStep1", function(){
			//Create folder step 1.
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'createFolderStep1'
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error create folder ');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.createFolderStep1 ? result.createFolderStep1 : '');
						showMessage(message,true);
						$('#createFolderName').focus();
					}else{
						showMessage(message,false);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		})
		
		contentFloater.on("click", "#createFolderStep2", function(){
			//Create folder step 2.
			var createFolderName = $('#createFolderName');
			createFolderNameVal = createFolderName.val();
			if(createFolderNameVal == ''){
				validationWarning('Please enter a name for the folder.',createFolderName);
				createFolderName.focus();
			}else{
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'createFolderStep2',
						'createFolderName': createFolderNameVal
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error create folder step 2');
					},
					success: function(result){
						coverMe();
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							rebuildContent(result.buildLists);
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

		uiPage.on("click", ".createListStep1", function(){
			//Create list step 1.
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'createListStep1'
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					showMessage.html('error create list step 1',false);
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.createListStep1 ? result.createListStep1 : '');
						showMessage(message,true);
						$('#createListName').focus();
					}else{
						showMessage(message,false);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		})

		contentFloater.on('change', "#createListIntoFolder", function(){
			if($(this).val() != ''){
				$('#hideDistributeOption').show();
			}else{
				$('#hideDistributeOption').hide();
			}
		})
		
		contentFloater.on("click", "#createListStep2", function(){
			//Create list step 2, optionally to the selected folder.
			var folderId = typeof $('#createListIntoFolder').html() !== 'undefined' ? $('#createListIntoFolder').val() : 0;
			var createListName = $('#createListName').val();
			var createListFramerate = $('#createListFramerate').val();
			if($('#hideDistributeOption').css("display") !== 'none'){
				var distributeFolderRoles = $('#createListDistributeRoles').is(':checked')
			}else{
				var distributeFolderRoles = '';
			}
			if(createListName == ''){
				validationWarning('Please enter a name for the list.',$("#createListName"));
				$("#createListName").focus();
			}else{
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'createListStep2',
						'folderId': folderId,
						'createListName': createListName,
						'createListFramerate': createListFramerate,
						'distributeFolderRoles': distributeFolderRoles
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error create list step 2');
					},
					success: function(result){
						try{
							coverMe();
							result = $.parseJSON(result);
							var message = result.message ? result.message : '';
							if(result.success == true){
								rebuildContent(result.buildLists);
								showMessage(message,result.locked);
							}else{
								showMessage(message,false);
							}
							if(result.debug){
								debugElement.html(result.debug);
							}
						}catch(err){
							errorReporter('',err);
						}
					}
				})
			}
		})

		//Build folder users.
		uiPage.on("click", ".buildFolderUsers", function(){
			var folderId = $(this).attr('folderId');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'buildFolderUsers',
					'folderId': folderId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error build folder users');
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

		//Build list users.
		uiContent.on("click", ".buildListUsers", function(){
			var listId = $(this).attr('listId');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'buildListUsers',
					'listId': listId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error build list users');
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
		
		//Change a user's folder role.
		contentFloater.on('change', ".changeFolderRole", function(){
			if(confirm("If you are reducing a user's role below Manager, all pending shares they started will be deleted.\n\nAre you sure you want to change the user's role?")){
				var userId = $(this).attr('userId');
				var folderId = $(this).attr('folderId');
				var newRoleId = $(this).val();
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'updateFolderRole',
						'userId': userId,
						'folderId': folderId,
						'newRoleId': newRoleId
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error update folder user role');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							coverMe(result.buildFolderUsers ? result.buildFolderUsers : '');
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
	
		//Change a user's list role.
		contentFloater.on('change', ".changeListRole", function(){
			if(confirm("If you are reducing a user's role below Manager, all pending shares they started will be deleted.\n\nAre you sure you want to change the user's role?")){
				var userId = $(this).attr('userId');
				var listId = $(this).attr('listId');
				var newRoleId = $(this).val();
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'updateListRole',
						'userId': userId,
						'listId': listId,
						'newRoleId': newRoleId
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error update list user role');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							coverMe(result.buildListUsers ? result.buildListUsers : '');
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
	
		//Change a pending user's role.
		contentFloater.on('change', ".changePendingRole", function(){
			var invitationId = $(this).attr('invitationId');
			var type = $(this).attr('adrType');
			var typeId = $(this).attr('typeId');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'updatePendingRole',
					'invitationId': invitationId,
					'type': type,
					'typeId': typeId,
					'newRoleId': $(this).val()
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error update pending user role');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.buildUsers ? result.buildUsers : '');
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

		//Delete folder.
		uiContent.on("click", ".deleteFolder", function(){
			var folderId = $(this).attr('folderId');
			var folderName = $(this).attr('folderName');
			if(confirm('Are you sure you want to delete the folder "' + folderName + '"?')){
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'deleteFolder',
						'folderId': folderId
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error delete folder');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							rebuildContent(result.buildLists);
							showMessage(message,true);
						}else{
							showMessage(message,false);
						}
						if(result.debug){
							debugElement.html(result.debug);
						}
						coverMe();
					}
				})
			}else{
				coverMe();
			}
		})
			
		//Delete list step 1.
		uiContent.on("click", ".deleteListStep1", function(){
			var listId = $(this).attr('listId');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'deleteListStep1',
					'listId': listId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error delete list step 1');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.deleteListStep1 ? result.deleteListStep1 : '');
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
		
		//Delete list step 2.
		contentFloater.on("click", "#deleteListStep2", function(){
			var listId = $(this).attr('listId');
			if($('#deleteListCheckbox').is(':checked')){
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'deleteListStep2',
						'listId': listId
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error delete list step 2');
					},
					success: function(result){
						coverMe();
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							rebuildContent(result.buildLists);
							showMessage(message,true);
						}else{
							showMessage(message,false);
						}
						if(result.debug){
							debugElement.html(result.debug);
						}
					}
				})
			}else{
				validationWarning('You must check the box to confirm deletion.',$("#deleteListCheckbox"));
			}
		})

		//Edit the selected list.
		uiContent.on("click", ".editList", function(){
			var listId = $(this).attr('listId');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'editList',
					'listId': listId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error edit list');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '&nbsp;';
					if(result.success == true){
						var returnCode = result.returnCode ? result.returnCode : '';
						window.location.href = returnCode;
					}else{
						showMessage(message,false);	
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		})
	
		//List properties step 1.
		uiContent.on("click", ".listPropertiesStep1",function(){
			var listId = $(this).attr('listId');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'listPropertiesStep1',
					'listId': listId,
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error list properties step 1');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.listPropertiesStep1 ? result.listPropertiesStep1 : '');
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

		//List properties step 2.
		contentFloater.on("click", "#listPropertiesStep2", function(){
			var listId = $(this).attr('listId');
			var newListName = $('#listPropertyName').val();
			var newFolderId = typeof $("#newFolderId").val() !== 'undefined' ? $('#newFolderId').val() : 0;
			var newListFramerate = $('#listPropertyFramerate').val();
			var data = 'mode=listPropertiesStep2' +
			'&listId=' + listId +
			'&newListName=' + newListName +
			'&newFolderId=' + newFolderId +
			'&newListFramerate=' + newListFramerate;			
			$.ajax({
				type: 'post',
				url: url,
				data:data,
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error list properties step 2');
				},
				success: function(result){
					try{
						var result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							rebuildContent(result.buildLists);
							showMessage(message,true);
						}else{
							showMessage(message,false);
						}
						if(result.debug){
							debugElement.html(result.debug);
						}
					}catch(err){
						errorReporter('',err);
					}
					coverMe();
				}
			})
		})		

		//Lock list.
		uiContent.on("click", ".lockList", function(){
			var listId = $(this).attr("listId");
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'lockList',
					'listId': listId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error lock list');
				},
				success: function(result){
					spinner();
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						rebuildContent(result.buildLists);
						showMessage(message,!result.locked);
					}else{
						showMessage(message,false);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		})

		//Remove a pending share/invitation.
		contentFloater.on("click", ".removeInvitation", function(){
			var invitationId = $(this).attr('invitationId');
			var typeId = $(this).attr('typeId');
			var type = $(this).attr('adrType');
			if(confirm('Are you sure you want to remove the pending user from this ' + type + '?')){
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'removeInvitation',
						'invitationId': invitationId,
						'type': type,
						'typeId': typeId
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error remove invitation');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							coverMe();
							if(type == 'folder'){
								coverMe(result.buildFolderUsers ? result.buildFolderUsers : '');
							}else{
								coverMe(result.buildListUsers ? result.buildListUsers : '');
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

		//Remove yourself from a folder.
		uiContent.on("click", ".removeFolder", function(){
			var folderId = $(this).attr('folderId');
			var folderName = $(this).attr('folderName');
			if(confirm('Are you sure you want to remove yourself from the folder "' + folderName + '"?\n\nYou will also forfeit your role for all of the lists in this folder.')){
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'removeFolder',
						'folderId': folderId
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error remove yourself from a folder');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							coverMe();
							rebuildContent(result.buildLists);
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
			coverMe();
		})

		//Remove user from a folder. This is not the same as removing yourself from a folder.
		uiPage.on("click", ".removeUserFromFolder", function(){
			var userId = $(this).attr('userId');
			var folderId = $(this).attr('folderId');
			var folderName = $(this).attr('folderName');
			if(confirm('Are you sure you want to remove this user from the folder "' + folderName + '"? They will have no access to the folder or it\'s lists. All Pending Shares they created will be deleted.')){
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'removeUserFromFolder',
						'userId': userId,
						'folderId': folderId
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error remove user from folder');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							coverMe(result.buildFolderUsers ? result.buildFolderUsers : '');
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

		//Remove yourself from a list.
		uiContent.on("click", ".removeList", function(){
			var listId = $(this).attr('listId');
			var listName = $(this).attr('listName');
			if(confirm('Are you sure you want to remove yourself from the list "' + listName + '"?\n\nThis will also remove pending shares you started.')){
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'removeList',
						'listId': listId
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error remove yourself from a list');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							coverMe();
							rebuildContent(result.buildLists);
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
			coverMe();
		})

		//Remove user from a list. This is not the same as removing yourself from a list.
		contentFloater.on("click", ".removeUserFromList", function(){
			var userId = $(this).attr('userId');
			var listId = $(this).attr('listId');
			var listName = $(this).attr('listName');
			if(confirm('Are you sure you want to remove this user from the list "' + listName + '"? All Pending Shares they created will be deleted.')){
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'removeUserFromList',
						'userId': userId,
						'listId': listId
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error remove user from list');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							coverMe(result.buildListUsers ? result.buildListUsers : '');
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

		//Rename folder step 1.
		uiContent.on("click", ".folderPropertiesStep1", function(){
			var folderId = $(this).attr('folderId');
			var folderName = $(this).attr('folderName');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'folderPropertiesStep1',
					'folderId': folderId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error folder properties step 1');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.folderPropertiesStep1 ? result.folderPropertiesStep1 : '');
						showMessage(message,true);
						$('#renameFolderInput').focus().val(folderName);
					}else{
						showMessage(message,false);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})
		})

		//Rename folder step 2.
		contentFloater.on("click", "#folderPropertiesStep2", function(){
			var folderId = $(this).attr('folderId');
			var renameFolderInput = $('#renameFolderInput');
			if(renameFolderInput.val() == ''){
				validationWarning('Please enter a name.',renameFolderInput);
			}else{
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'folderPropertiesStep2',
						'folderId': folderId,
						'folderName': renameFolderInput.val()
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error folder properties step 2');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							rebuildContent(result.buildLists);
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
			}
		})

		//Share a folder step 1. Build the share folder section.
		uiContent.on("click", ".shareFolderStep1", function(){
			var folderId = $(this).attr('folderId');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'shareFolderStep1',
					'folderId': folderId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error share folder step 1');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.output ? result.output : '');
						$("#shareFolderInput").focus();
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

		//Share a folder step 2 = add a user to a folder.
		contentFloater.on("click", "#shareFolderStep2", function(){
			var folderId = $(this).attr('folderId');
			var shareFolderInput = $("#shareFolderInput");
			validateResponse = emailValidate(shareFolderInput.val());
			if(validateResponse !== true){
				vaidationWarning(validateResponse,shareFolderInput);
			}else{
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'shareFolderStep2',
						'folderId': folderId,
						'email': shareFolderInput.val()
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error share folder step 2');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							showMessage(message,true);
						}else{
							showMessage(message,false);
						}
						if(result.debug){
							debugElement.html(result.debug);
						}
						coverMe();
					}
				})
			}
		})

		//Share list build form.
		uiContent.on("click", ".shareListStep1", function(){
			var listId = $(this).attr('listId');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'shareListStep1',
					'listId': listId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error share list step 1');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.output ? result.output : '');
						$("#shareListInput").focus();
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

		//Share a list = add a user to a list.
		contentFloater.on("click", "#shareListStep2", function(){
			var listId = $(this).attr('listId');
			var shareListInput = $('#shareListInput');
			validateResponse = emailValidate(shareListInput.val());
			if(validateResponse !== true){
				validationWarning(validateResponse,shareListInput);
				$("#shareListInput").focus();
			}else{
				$.ajax({
					type: 'post',
					url: url,
					data:{
						'mode': 'shareListStep2',
						'listId': listId,
						'email': shareListInput.val()
					},
					beforeSend: function(){
						spinner('working…');
					},
					error: function(){
						spinner('error share list step 2');
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							showMessage(message,true);
						}else{
							showMessage(message,false);
						}
						if(result.debug){
							debugElement.html(result.debug);
						}
						coverMe();
					}
				})
			}
		})

		uiPage.on("click", "[toggleFolders]", function(){//DEPRECATED
			/*
			Toggle folders using arrows to show lists. This is separate from the general arrow function so that folders are left open when viewing the list actions.
			There should be a containing element around the arrow img. This container needs a custom attribute of "toggleFolder" that specifies the ID of the element to toggle.
			*/
			var toggleMe = $(this).attr('toggleFolders');
			//Hide all toggle elements.
			if(cover.css('display') == 'block'){
				//Close only elements in the coverFloater.
				coverFloater.find($("[toggleFolders]")).each(function (){
					$("#" + $(this).attr("toggleFolders")).slideUp(200);
					var img = $(this).find("img[src*='Down']");
					if(typeof img.attr('src') !== 'undefined'){
						var src = img.attr('src').replace("Down","Right");
						img.attr("src", src);
					}
				});
			}else{
				$("[toggleFolders]").each(function (){
					$("#" + $(this).attr("toggleFolders")).slideUp(200);
					var img = $(this).find("img[src*='Down']");
					if(typeof img.attr('src') !== 'undefined'){
						var src = img.attr('src').replace("Down","Right");
						img.attr("src", src);
					}
				});
			}
			var arrow = $(this).children("img[src*='Right']");
			//Toggle the stuff.
			if($('#' + toggleMe).css('display') == 'none'){
				if(typeof arrow.attr('src') !== 'undefined'){
					var src = arrow.attr('src').replace("Right","Down");
				}
				$('#' + toggleMe).slideDown(200);
			}else{
				if(typeof arrow.attr('src') !== 'undefined'){
					var src = arrow.attr('src').replace("Down","Right");
				}
				$('#' + toggleMe).slideUp(200);
			}
			arrow.attr("src", src);
		})

		//Transfer a list step 1.
		uiContent.on("click", ".transferListStep1", function(){
			var listId = $(this).attr('listId');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'transferListStep1',
					'listId': listId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error transfer list step 1');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe(result.transferListStep1 ? result.transferListStep1 : '');
						showMessage(message,true);
						$('#intendedEmail').focus();
					}else{
						showMessage(message,false);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})			
		})

		//Transfer a list step 2.
		uiPage.on("click", "#transferListStep2", function(){
			var listId = $(this).attr('listId');
			var intendedEmail = $('#intendedEmail');
			var intendedEmailRetype = $('#intendedEmailRetype');
			var emailCheck = emailValidate(intendedEmail.val());
			if(emailCheck !== true){
				validationWarning(emailCheck,intendedEmail);
				$('#intendedEmail').focus();
			}else{
				if(intendedEmail.val() != intendedEmailRetype.val()){
					validationWarning('The email addresses do not match.',intendedEmailRetype);
					$('#intendedEmailRetype').focus();
				}else{
					$.ajax({
						type: 'post',
						url: url,
						data:{
							'mode': 'transferListStep2',
							'listId': listId,
							'intendedEmail': intendedEmail.val(),
							'intendedEmailRetype': intendedEmailRetype.val()
						},
						beforeSend: function(){
							spinner('working…');
						},
						error: function(){
							spinner('error transfer list step 2');
						},
						success: function(result){
							result = $.parseJSON(result);
							var message = result.message ? result.message : '';
							if(result.success == true){
								coverMe();
								showMessage(message,false);
							}else{
								showMessage(message,false);
							}
							if(result.debug){
								debugElement.html(result.debug);
							}
						}
					})	
				}
			}
		})

		//Transfer list stop.
		uiPage.on("click", "#transferListStop", function(){
			var listId = $(this).attr('listId');
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'transferListStop',
					'listId': listId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error transfer list stop');
				},
				success: function(result){
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						coverMe();
						showMessage(message,false);
					}else{
						showMessage(message,false);
					}
					if(result.debug){
						debugElement.html(result.debug);
					}
				}
			})	
		})

		//Unlock list.
		uiContent.on("click", ".unlockList", function(){
			var listId = $(this).attr("listId");
			$.ajax({
				type: 'post',
				url: url,
				data:{
					'mode': 'unlockList',
					'listId': listId
				},
				beforeSend: function(){
					spinner('working…');
				},
				error: function(){
					spinner('error unlock list');
				},
				success: function(result){
					spinner();
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if(result.success == true){
						rebuildContent(result.buildLists);
						showMessage(message,!result.locked);
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