function buildCharactersList(callback) {
	//Build the complex characters list.
	$('html, body').scrollTop(0);
	coverMe();
	var data = 'mode=buildCharactersList';
	if ($('#selectedCharactersHolder').html() != '') {
		//Loop through the currently selected characters to build a list of charaters to exclude.
		var x = 0;
		$('.selectThisCharacter').each(function () {
			if ($(this).attr("class").indexOf('fa-check-square-o') != -1) {
				var charId = $(this).attr('charId');
				data = data + '&char' + x + '=' + charId;//Add the charId of the selected character(s).
				x++;
			}
		});
	}
	$.ajax({
		type: 'post',
		url: url,
		data: data,
		beforeSend: function () {
			spinner('working…');
		},
		error: function () {
			spinner('error build complex characters list');
		},
		success: function (result) {
			result = $.parseJSON(result);
			var message = result.message ? result.message : '';
			coverMe();
			if (result.success == true) {
				coverMe(result.buildCharactersList ? result.buildCharactersList : 'error return build complex characters list');
				if (callback != null) {
					callback();
				}
			} else {
				showMessage(message, false);
			}
			if (result.debug) {
				debugElement.html(result.debug);
			}
		}
	})
}

function buildLines(lines) {
	$("#buildLinesHolder").html(lines).trigger("create");
}

function undeleteCharacter(charId) {
	$.ajax({
		type: 'post',
		url: url,
		data: {
			'mode': 'undeleteCharacter',
			'charId': charId
		},
		beforeSend: function () {
			spinner('working…');
		},
		error: function () {
			spinner('error add character');
		},
		success: function (result) {
			result = $.parseJSON(result);
			var message = result.message ? result.message : '';
			if (result.success == true) {
				$('#createNewCharacterName').val('');
				showMessage(message, true);
				buildLines(result.buildLines ? result.buildLines : 'error return undelete character');
				buildCharactersList(function () {
					$("#selectCharacter" + charId).click();
				})
			} else {
				showMessage(message, false);
			}
			if (result.debug) {
				debugElement.html(result.debug);
			}
		}
	})
}

$(document).ready(function () {
	var addReel = $('#addReel');

	uiPage.click(function (event) {
		//alert($(this).html());
	});

	uiPage.on("click", ".copyValue", function () {
		//Grey arrow copy value.
		var field = $(this).attr('field');
		field = brRemove(field);
		var value = $(this).attr('value');
		value = brRemove(value);
		if (field == 'addCharacter') {
			//$("#addCharacter option:contains('" + value + "')").attr('selected', 'selected');
			buildCharactersList(function () {
				$("#selectCharacter" + value).click();
				$('.selectCharactersDone').click();
			});
		} else {
			$('#' + field).val(value);
		}
	});

	/*jQuery.extend(jQuery.expr[':'], {
	 focus: function(element) {
	 return element == document.activeElement;
	 }
	 })*/

	//Characters---------------------------------------------------------------------------------------------------
	//Add a new character.

	//Click on Select Characters.
	uiPage.on("click", '.buildCharactersList', function () {
		buildCharactersList(null);
	});

	uiPage.on("click", ".createNewCharacterStep1", function () {
		//Build the create new character dialogue.
		$('#createNewCharacterName').removeAttr("data-role");
		coverMe($('#createNewCharacterHolder').html());
		$('#createNewCharacterName').focus();
	});

	uiPage.on("click", "#createNewCharacterCancelButton", function () {
		//Cancel when creating a new character.
		buildCharactersList(null);
	});

	uiPage.on("click", ".selectThisCharacter", function () {
		//Select a character in the complex characters list when clicking on the span tag.
		//Toggle the checked image and css background-color.
		var charId = $(this).attr('charId');
		var container = $('#selectCharacterContainer' + charId);
		var i = $("#selectCharacter" + charId);
		var containerColor;
		var nameColor;
		i.toggleClass("fa-square-o fa-check-square-o");
		container.toggleClass;
		if ($(this).attr("class").indexOf('fa-check-square-o') != -1) {
			containerColor = container.attr('defaultcolor');
			nameColor = $(this).attr('defaultcolor');
		} else {
			containerColor = container.attr('charcolor');
			nameColor = '#000';
		}
		if (container.parent().attr('id') == 'selectedCharactersHolder') {
			container.remove();
		}
	});

	uiPage.on("click", ".selectCharactersDone", function () {
		//Done selecting characters.
		//Loop through the character check boxes and select the ones that are checked.
		$('.selectThisCharacter').each(function (index) {
			if ($(this).attr("class").indexOf('fa-check-square-o') != -1) {
				var charId = $(this).attr('charId');
				$('#selectCharacterContainer' + charId).appendTo($('#selectedCharactersHolder'));
			}
		});
		coverMe();
	});

	uiPage.on("click", ".deselectAllCharacters", function () {
		//Deselect all characters.
		//Clear the selectedCharactersHolder and reset the buildCharactersList.
		$('#selectedCharactersHolder').html('');
		buildCharactersList(null);
	});

	uiPage.on("click", "#createNewCharacterButton", function () {
		//Save to create a new character.
		var createNewCharacterNameVal = $('#createNewCharacterName').val();
		if (createNewCharacterNameVal == '') {
			validationWarning('Please enter a character name.', $("#createNewCharacterName"));
			$('#createNewCharacterName').focus();
		} else {
			$.ajax({
				type: 'post',
				url: url,
				data: {
					'mode': 'createNewCharacter',
					'createNewCharacterName': createNewCharacterNameVal
				},
				beforeSend: function () {
					spinner('working…');
				},
				error: function () {
					spinner('error add character');
				},
				success: function (result) {
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if (result.success == true) {
						$('#createNewCharacterName').val('');
						buildCharactersList(function () {
							validationWarning(message, $("#charactersValidationWarning"));
						});
						console.log(message);
					} else if (result.success == 2) {
						showMessage(message);
					} else {
						showMessage(message, false);
					}
					if (result.debug) {
						debugElement.html(result.debug);
					}
				}
			})
		}
	});

	uiPage.on("click", "#addForSure", function () {
		//When clicking on 'No, add this new character.'
		var createNewCharacterNameVal = $('#createNewCharacterName').val();
		if (createNewCharacterNameVal == '') {
			showMessage('Please enter a first name.');
		} else {
			$.ajax({
				type: 'post',
				url: url,
				data: {
					'mode': 'createNewCharacter',
					'createNewCharacterName': createNewCharacterNameVal,
					'addForSure': true
				},
				beforeSend: function () {
					spinner('working…');
				},
				error: function () {
					spinner('error add for sure character');
				},
				success: function (result) {
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if (result.success == true) {
						showMessage(message, true);
						$('#createNewCharacterName').val('');
						if (result.newCharId != null) {
							console.log("#addForSure 2");
							buildCharactersList(function () {
								$(".selectThisCharacter[charId='" + result.newCharId + "']").click();
							})
						} else {
							console.log(".deselectAllCharacters 2");
							buildCharactersList(null);
						}
					} else {
						showMessage(message, false);
					}
					if (result.debug) {
						debugElement.html(result.debug);
					}
				}
			})
		}
	});

	uiPage.on("click", "[id^='potential']", function () {
		//Select a character in the characterCheck list that isn't marked as deleted. It will select the existing character in the complex character list.
		var charId = $(this).attr('charId');
		$('#createNewCharacterName').val('');
		console.log("[id^='potential']");
		buildCharactersList(function () {
			if ($('#selectCharacter' + charId).attr("class").indexOf('fa-square-o') != -1) {
				$("#selectCharacter" + charId).click();
			}
		});
		showMessage();
	});

	uiPage.on("click", ".mustBeUndeleted", function () {
		//Select a character in the characterCheck list that is marked as deleted.
		var charId = $(this).attr('charId');
		undeleteCharacter(charId);
	});

	uiPage.on("click", ".editCharacterButton", function () {
		//Edit character button.
		var charId = $(this).attr('charId');
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'editCharacterPart1',
				'charId': charId
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error edit character part 1');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					coverMe(result.returnCode ? result.returnCode : 'error return edit character part 1');
					var myPicker = new jscolor.color(document.getElementById('editCharacterColor'), {});
					uiPage.keyup(function (event) {
						var k = event.keyCode;
						if (k == 27) {
							myPicker.hidePicker();
						}
					});
					//myPicker.fromString('99FF33')
					$('#editCharacterName').focus();
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
			}
		})
	});

	uiPage.on("click", "#editCharacterCancel", function () {
		//Cancel when editing character.
		buildCharactersList();
	});

	uiPage.on("click", "#editCharacterSave", function () {
		//Edit character save button.
		var editCharacterName = $('#editCharacterName');
		var editCharacterNameVal = editCharacterName.val();
		var charId = editCharacterName.attr('charId');
		var editCharacterColorVal = $('#editCharacterColor').val();
		if (editCharacterNameVal == '') {
			showMessage('Please enter a first name.');
		} else {
			$.ajax({
				type: 'post',
				url: url,
				data: {
					'mode': 'editCharacterPart2',
					'charId': charId,
					'editCharacterName': editCharacterNameVal,
					'editCharacterColor': editCharacterColorVal
				},
				beforeSend: function () {
					spinner('working…');
				},
				error: function () {
					spinner('error edit character part 2');
				},
				success: function (result) {
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if (result.success == true) {
						buildLines(result.buildLines ? result.buildLines : 'error return edit character part 2');
						editCharacterName.val('');
						$('#editCharacterColor').val('');
						buildCharactersList(function () {
							validationWarning(message, $("#charactersValidationWarning"))
						});
					} else {
						showMessage(message, false);
					}
					if (result.debug) {
						debugElement.html(result.debug);
					}
				}
			})
		}
	});

	uiPage.on("click", "#editCharacterCancel", function () {
		//Edit character cancel button.
		console.log("#editCharacterCancel");
		buildCharactersList(null);
	});

	uiPage.on("click", "#selectCharacter", function () {
		//Select a character.
		coverMe($('#addCharacter').html())
	});

	uiPage.on("click", "div[id^='editCharactersPotential']", function () {
		//Select a character in the characterCheck list.
		var charId = $(this).attr('charId');
		$('#addCharacter').val(charId);
		editCharacterName.val('');
		$('#editCharacterColor').val('');
		addReel.focus();
	});

	uiPage.on("click", ".deleteCharacter", function () {
		//Delete character button.
		var charId = $(this).attr('charId');
		if (confirm('Are you sure you want to delete this character? All lines associated with this character will be deleted.')) {
			$.ajax({
				type: 'post',
				url: url,
				data: {
					'mode': 'deleteCharacter',
					'charId': charId
				},
				beforeSend: function () {
					spinner('working…');
				},
				error: function () {
					spinner('error delete character');
				},
				success: function (result) {
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if (result.success == true) {
						buildLines(result.buildLines ? result.buildLines : 'error return delete character');
						buildCharactersList(function () {
							validationWarning(message, $("#charactersValidationWarning"));
						})
					} else {
						showMessage(message, false);
					}
					if (result.debug) {
						debugElement.html(result.debug);
					}
				}
			})
		}
	});

	//Comments.---------------------------------------------------------------------------------------------------
	/*	
	 //Hover to display "Show Comments"
	 uiPage.on('mouseenter',"[id^='lineHolder']",function(){
	 var lineId = $(this).attr('lineid');
	 $('#commentsHolder' + lineId).fadeIn(250);
	 },
	 'mouseleave', "[id^='lineHolder']", function(){
	 var lineId = $(this).attr('lineid');
	 var commentsHolder = $('#commentsHolder' + lineId);
	 if(/View Comments/i.test(commentsHolder.html())){
	 commentsHolder.slideUp(100);
	 }
	 }
	 })

	 //Previous hover.
	 uiPage.on('mouseeneter', "[id^='lineHolder']", function(){
	 $(this).first().children('[id^=commentsHolder]').slideDown(250);
	 },
	 'mouseleave', "[id^='lineHolder']",function(){
	 var commentsHolder = $(this).children('[id^=commentsHolder]');
	 if(/View Comments/i.test(commentsHolder.html())){
	 commentsHolder.slideUp(100);
	 }
	 }
	 })*/

	//Show Comments.
	uiPage.on("click", ".commentsToggle", function () {
		var lineId = $(this).attr('lineId');
		var commentsHolder = $('#commentsHolder' + lineId);
		//Close all other comments.
		//$('[id^=commentsHolder]').hide();
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'buildComments',
				'lineId': lineId
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error comments show');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					commentsHolder.html(result.buildComments ? result.buildComments : 'error return comments show');
					triggerThis(commentsHolder);
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
				coverMe();
			}
		})
	});

	//Clear the new comment area upon focus.
	uiPage.on('focus', "[id^='newComment']", function () {
		if ($(this).val() == 'Type comment here...') {
			$(this).val('');
		}
		var lineId = $(this).attr('lineId');
		$(".addComment[lineId='" + lineId + "']").show();
	});

	//Add comment.
	uiPage.on("click", ".addComment", function () {
		var lineId = $(this).attr('lineId');
		var commentVal = $('#newComment' + lineId).val();
		var commentsHolder = $('#commentsHolder' + lineId);
		if (commentVal != '') {
			$.ajax({
				type: 'post',
				url: url,
				data: {
					'mode': 'addComment',
					'lineId': lineId,
					'newComment': commentVal
				},
				beforeSend: function () {
					spinner('working…');
				},
				error: function () {
					spinner('error add comment');
				},
				success: function (result) {
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if (result.success == true) {
						commentsHolder.html(result.buildComments ? result.buildComments : 'error return add comment');
						triggerThis(commentsHolder);
						$("#commentMessageHolder" + lineId).html(message);
					} else {
						showMessage(message, false);
					}
					if (result.debug) {
						debugElement.html(result.debug);
					}
					coverMe();
				}
			})
		}
	});

	//Delete comment.
	uiPage.on("click", ".deleteComment", function () {
		var commentId = $(this).attr('commentId');
		var lineId = $(this).attr('lineId');
		var commentsHolder = $("#commentsHolder" + lineId);
		if (confirm('Delete this comment?')) {
			$.ajax({
				type: 'post',
				url: url,
				data: {
					'mode': 'deleteComment',
					'commentId': commentId,
					'lineId': lineId
				},
				beforeSend: function () {
					spinner('working…');
				},
				error: function () {
					spinner('error delete comment');
				},
				success: function (result) {
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if (result.success == true) {
						commentsHolder.html(result.buildComments ? result.buildComments : 'error return delete comment');
						triggerThis(commentsHolder);
					} else {
						showMessage(message, false);
					}
					if (result.debug) {
						debugElement.html(result.debug);
					}
					coverMe();
				}
			})
		}
	});

	//Lines-----------------------------------------------------------------------------------------------
	//Add a new line.
	uiPage.on("click", "#addLineButton", function () {
		var selectedCharactersHolder = $('#selectedCharactersHolder');
		var addTcOut = $('#addTcOut');
		var addTcOutVal = addTcOut.val();
		if (selectedCharactersHolder.html() == '') {
			validationWarning('Please select a character.', $(".buildCharactersListValidationWarning"));
		} else if ($('#addLine').val() == '') {
			validationWarning('Enter a line.', $("#addLine"));
			$('#addLine').focus();
		} else {
			var data = 'mode=addLine' + '&reel=' + $('#addReel').val() + '&scene=' + $('#addScene').val() + '&tcIn=' + $('#addTcIn').val() + '&tcOut=' + $('#addTcOut').val() + '&line=' + $('#addLine').val() + '&notes=' + $('#addNotes').val();
			//Loop through all of the selected characters.
			var x = 0;
			$('.selectThisCharacter').each(function () {
				if ($(this).attr("class").indexOf('fa-check-square-o') != -1) {
					data = data + '&char' + x + '=' + $(this).attr('charId');//Add the charId of the selected character(s).
					x++;
				}
			});
			$.ajax({
				type: 'post',
				url: url,
				data: data,
				beforeSend: function () {
					spinner('working…');
				},
				error: function () {
					spinner('error add line');
				},
				success: function (result) {
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if (result.success == true) {
						$('#addLine').val('');
						$('#addNotes').val('');
						$('#addTcIn').val(addTcOutVal);
						addTcOut.val('');
						$('#addLine').focus();
						buildLines(result.buildLines ? result.buildLines : 'error return add line');
						showMessage(message, true);
					} else {
						showMessage(message, false);
					}
					if (result.debug) {
						debugElement.html(result.debug);
					}
					coverMe();
				}
			})
		}
	});

	//Reset add line fields.
	uiPage.on("click", "#addLineClear", function () {
		$('#addReel').val('');
		$('#addScene').val('');
		$('#addTcIn').val('');
		$('#addTcOut').val('');
		$('#addLine').val('');
		$('#addNotes').val('');
	});

	//Cancel Edit Line Part 1.
	uiPage.on("click", "#cancelEditLine", function () {
		$('#editLineHolder').hide();
	});

	//Mark line as recorded.
	uiPage.on("click", ".unrecorded", function () {
		$('#editLineHolder').hide();
		var lineId = $(this).attr('lineId');
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'markRecorded',
				'lineId': lineId
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error mark line recorded');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					buildLines(result.buildLines ? result.buildLines : 'error return mark line recorded');
					coverMe();
					validationWarning(message, $("#validationWarning" + lineId));
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
			}
		})
	});

	//Mark line as unrecorded.
	uiPage.on("click", ".recorded", function () {
		$('#editLineHolder').hide();
		var lineId = $(this).attr('lineId');
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'markUnrecorded',
				'lineId': lineId
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error mark line unrecorded');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					buildLines(result.buildLines ? result.buildLines : 'error return mark line unrecorded');
					coverMe();
					validationWarning(message, $("#validationWarning" + lineId));
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
			}
		})
	});

	//Delete line.
	uiPage.on("click", ".deleteLineButton", function () {
		if (confirm('Are you sure you want mark this line as deleted?')) {
			$('#editLineHolder').hide();
			var lineId = $(this).attr('lineId');
			$.ajax({
				type: 'post',
				url: url,
				data: {
					'mode': 'deleteLine',
					'lineId': lineId
				},
				beforeSend: function () {
					spinner('working…');
				},
				error: function () {
					spinner('error delete line');
				},
				success: function (result) {
					result = $.parseJSON(result);
					var message = result.message ? result.message : '';
					if (result.success == true) {
						var returnCode = result.returnCode ? result.returnCode : '';
						buildLines(result.buildLines ? result.buildLines : 'error return delete line');
						coverMe();
						validationWarning(message, $("#validationWarning" + lineId));
					} else {
						showMessage(message, false);
					}
					if (result.debug) {
						debugElement.html(result.debug);
					}
				}
			})
		} else {
			coverMe();
		}
	});

	//Edit Line Part 1.
	uiPage.on("click", ".editLineButton", function () {
		var lineId = $(this).attr('lineId');
		$('html, body').scrollTop($('#lineHolder' + lineId).offset().top - 10);
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'editLinePart1',
				'lineId': lineId
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error edit line part 1');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					var returnEditLinePart1 = result.returnEditLinePart1 ? result.returnEditLinePart1 : 'error return edit line part 1';
					document.getElementById('editLineHolder').style.display = 'block';
					$('#editLineHolder').insertAfter($('#editLineHolderAfterThis' + lineId)).html(returnEditLinePart1).trigger("create");
					$('#editThisLine').html(lineId);
					$('#editLine').focus();
					coverMe();
					showMessage(message);
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
			}
		})
	});

	//Edit Line Part 2 - save.
	uiPage.on("click", "#saveLineButton", function () {
		var lineId = $(this).attr('lineId');
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'editLinePart2',
				'lineId': lineId,
				'charId': $('select#editLineCharacter').val(),
				'reel': $('#editReel').val(),
				'scene': $('#editScene').val(),
				'tcIn': $('#editTcIn').val(),
				'tcOut': $('#editTcOut').val(),
				'line': $('textarea#editLine').val(),
				'notes': $('textarea#editNotes').val()
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error edit line part 2');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					document.getElementById('editLineHolder').style.display = 'block';
					buildLines(result.buildLines ? result.buildLines : 'error return edit line part 2');
					$('html, body').scrollTop($('#lineHolder' + lineId).offset().top - 50);
					validationWarning(message, $("#validationWarning" + lineId));
					console.log("Warning at lineId" + lineId + ": " + message);
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
				coverMe();
			}
		})
	});

	//Undelete line.
	uiPage.on("click", ".undeleteLineButton", function () {
		var lineId = $(this).attr('lineId');
		var charId = $('#charId').html();
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'undeleteLine',
				'lineId': lineId,
				'charId': charId
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error undelete line');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					buildLines(result.buildLines ? result.buildLines : 'error return undelete line');
					coverMe();
					validationWarning(message, $("#validationWarning" + lineId));
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
			}
		})
	});

	//Export-----------------------------------------------------------------------------------------------	
	uiPage.on("click", ".export", function () {
		/*
		 There are a few options here:
		 export for engineer vs talent
		 showcommments (boolean)
		 */
		var exportFor = '?exportFor=' + $(this).attr('exportFor');
		var showComments = '&showComments=' + getCheckboxState(document.getElementById('exportShowComments'));
		window.open(url + exportFor + showComments, '_self');
	});

	//Report-----------------------------------------------------------------------------------------------
	uiPage.on("click", "[id^='report']", function () {
		window.open('report.php');
	});

	//Advanced view options.-----------------------------------------------------------------------------------------------	
	uiPage.on("click", "#buildAdvancedViewOptions", function () {
		//Show advanced options.
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'buildViewOptions'
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error build advanced view options');
			},
			success: function (result) {
				//result = $.parseJSON(result);
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					coverMe(result.buildViewOptions ? result.buildViewOptions : 'error return build advanced view options');
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
			}
		})
	});

	uiPage.on("click", "#saveAdvancedViewOptions", function () {
		//Save advanced view options.
		//Order by options.
		var orderBy = '';
		var beenHere = false;
		$('.orderByOption').each(function () {
			if (beenHere) {
				orderBy = orderBy + ' ' + $(this).attr('value');
			} else {
				orderBy = $(this).attr('value');
			}
			beenHere = true;
		});
		//console.log('orderBy: ' + orderBy);
		//Order direction.
		//console.log('Made it to order direction.');
		if (getCheckboxState(document.getElementById("orderDirectionAscending"))) {
			var orderDirection = 'ASC';
		} else {
			var orderDirection = 'DESC';
		}
		//console.log("orderDirection: " + orderDirection);
		//View reels.
		//console.log('Made it to view reels.');
		var viewReels = '';
		beenHere = false;
		$("input[viewReelsValue]").each(function () {
			if (getCheckboxState(this)) {
				var reelValue = $(this).attr("viewReelsValue");
				if (reelValue == 'viewAll') {
					viewReels = 'viewAll';
					return false;
				} else if (beenHere) {
					viewReels = viewReels + ' ' + reelValue;
				} else {
					viewReels = reelValue;
				}
				beenHere = true;
			}
		});
		//console.log('viewReels: ' + viewReels);
		//View characters. This can be empty '', which means view all characters.
		var viewCharacters = '';
		$("input[viewCharactersValue]").each(function () {
			if (getCheckboxState(this)) {
				var characterValue = $(this).attr("viewCharactersValue");
				if (characterValue == 'viewAll') {
					viewCharacters = '';
					return false;
				} else if (beenHere) {
					viewCharacters = viewCharacters + ' ' + characterValue;
				} else {
					viewCharacters = characterValue;
				}
				beenHere = true;
			}
		});
		//console.log('viewCharacters: ' + viewCharacters);
		var showCharacterColors = getCheckboxState($('#advancedShowCharacterColors').get(0)) ? 'true' : 'false';
		var showDeletedLines = getCheckboxState($('#advancedShowDeletedLines').get(0)) ? 'true' : 'false';
		var showRecordedLines = getCheckboxState($('#advancedShowRecordedLines').get(0)) ? 'true' : 'false';
		//var showRecordedLines = getCheckboxState($('#advancedShowRecordedLines').children('img:first').get(0));
		//console.log('showCharacterColors: ' + showCharacterColors + '\nshowDeletedLines: ' + showDeletedLines + '\nshowRecordedLines: ' + showRecordedLines);
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'saveViewOptions',
				'orderBy': orderBy,
				'orderDirection': orderDirection,
				'viewReels': viewReels,
				'viewCharacters': viewCharacters,
				'showCharacterColors': showCharacterColors,
				'showDeletedLines': showDeletedLines,
				'showRecordedLines': showRecordedLines
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error save view options');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					buildLines(result.buildLines ? result.buildLines : 'error return save view options');
					showMessage(message, true);
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
				coverMe();
			}
		})
	});

	uiPage.on("click", "#refreshAdvancedViewOptions", function () {
		//Refresh advanced view options.
		$('#buildAdvancedViewOptions').click();
	});

	uiPage.on("click", "#resetViewOptionsToDefault", function () {
		//Reset view options to default.
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'saveViewOptions'
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error reset view options to default');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					buildLines(result.buildLines ? result.buildLines : 'error return reset view options to default');
					coverMe();
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
			}
		})
	});

	/*
	 <div>
	 <div><img top><img up one></div>
	 <div>orderByOption</div>
	 <div><img down one><img bottom></div>
	 </div>
	 */

	uiPage.on("click", "img.arrowTop", function () {
		//Move order by option to the top.
		var sourceDiv = $(this).parent().next(".orderByOption");
		//var sourceDiv = this.parentNode.nextSibling;//The order by option td for this arrow.
		moveToTop(sourceDiv);
	});

	function moveToTop(sourceDiv) {
		var newSourceDiv = moveUpOne(sourceDiv);
		if (newSourceDiv.parentNode.id !== 'orderByOptionsFirstRow') {
			moveToTop(newSourceDiv);
		} else {
			return true;
		}
	}

	uiPage.on("click", "img.arrowUpOne", function () {
		//Move order by option up one.
		var sourceDiv = $(this).parent().next(".orderByOption");
		//var sourceDiv = this.parentNode.nextSibling;//The order by option td for this arrow.
		moveUpOne(sourceDiv);
	});

	function moveUpOne(sourceDiv) {
		/*
		 Swaps the order by option with the one above it.
		 sourceDiv = (string - javascript object) the element to move up.
		 Returns the newly moved element as a javascript object.
		 */
		var sourceHtml = $(sourceDiv).html();//The displayed text of the source div.
		var sourceVal = $(sourceDiv).attr('value');//The value of the source div.
		var destinationDiv = $(sourceDiv).parent().prev('div').children('div.orderByOption:first');//The next order by option div.
		var destinationHtml = destinationDiv.html();
		var destinationVal = destinationDiv.attr('value');
		//Change the html and values.
		destinationDiv.html(sourceHtml);
		destinationDiv.attr('value', sourceVal);
		$(sourceDiv).html(destinationHtml);
		$(sourceDiv).attr('value', destinationVal);
		return (destinationDiv.get(0));//Convert to a javascript object.
	}

	uiPage.on("click", "img.arrowDownOne", function () {
		//Move order by option down one.
		var sourceDiv = $(this).parent().prev(".orderByOption");
		//var sourceDiv = this.parentNode.previousSibling;//The order by option div for this arrow.
		moveDownOne(sourceDiv);
	});

	function moveDownOne(sourceDiv) {
		/*
		 Swaps the order by option with the one below it.
		 sourceDiv = (string - javascript object) the element to move down.
		 Returns the newly moved element as a javascript object.
		 */
		var sourceHtml = $(sourceDiv).html();//The displayed text of the source td.
		var sourceVal = $(sourceDiv).attr('value');//The value of the source td.
		var destinationDiv = $(sourceDiv).parent().next('div').children('div.orderByOption:first');//The next order by option div.
		var destinationHtml = destinationDiv.html();
		var destinationVal = destinationDiv.attr('value');
		//Change the html and values.
		destinationDiv.html(sourceHtml);
		destinationDiv.attr('value', sourceVal);
		$(sourceDiv).html(destinationHtml);
		$(sourceDiv).attr('value', destinationVal);
		return (destinationDiv.get(0));//Convert to a javascript object.
	}

	uiPage.on("click", "img.arrowBottom", function () {
		//Move order by option to the bottom.
		var sourceDiv = $(this).parent().prev(".orderByOption");
		//var sourceDiv = this.parentNode.previousSibling;//The order by option div for this arrow.
		moveToBottom(sourceDiv);
	});

	function moveToBottom(sourceDiv) {
		/*
		 sourceDiv (javascript object)
		 */
		var newSourceDiv = moveDownOne(sourceDiv);
		if (newSourceDiv.parentNode.id !== 'orderByOptionsLastRow') {
			moveToBottom(newSourceDiv);
		} else {
			return true;
		}
	}

	function findParentElement(startingElement, nameOfTag) {
		/*
		 Traverses up the DOM tree and returns the parent table element as a javascript object.
		 startingElement = (string - javascript object) the starting DOM element.
		 nameOfTag = (string - javascript object) the name of the parent tag to find.
		 */
		var element = startingElement.parentNode;
		var re = new RegExp(nameOfTag, 'i');
		if (element == null || element == 'undefined') {
			return false;
		} else if (element.tagName.match(re)) {
			return element;
		} else {
			//alert(startingElement.tagName);
			findParentElement(element, nameOfTag);
		}
	}

	//Time Code.-----------------------------------------------------------------------------------------------
	function convertTc(tcInput) {
		//Convert integers between 5 and 8 characters in length to a timecode formatted of HH:MM:SS:FF.
		var output = tcInput;
		var framesZero = false;
		var x;
		if (!isNaN(parseFloat(tcInput)) && isFinite(tcInput)) {
			if (tcInput.length == 5) {
				//For 5 characters we will assume a single-digit hour and the frames are 00. Ex: 02:12:34:00 entered as 21234.
				framesZero = true;
				output = '0' + tcInput.substring(0, 1);
				x = 1;
			} else if (tcInput.length == 6) {
				//For 6 characters we will assume the frames are 00. Ex: 02:12:34:00 entered as 021234.
				framesZero = true;
				output = tcInput.substring(0, 2);
				x = 2;
			} else if (tcInput.length == 7) {
				//A tc number with a single-digit hour. Ex: 02:12:34:00 entered as 2123400.
				output = '0' + tcInput.substring(0, 1);
				x = 1;
			} else if (tcInput.length == 8) {
				//A tc number with a double-digit hour. Ex: 02:12:34:00 entered as 02123400.
				output = tcInput.substring(0, 2);
				x = 2;
			}
			if (x) {
				output += ':';
				output += tcInput.substring(x, x + 2) + ':';
				x += 2;
				output += tcInput.substring(x, x + 2) + ':';
				if (framesZero) {
					output += '00';
				} else {
					x += 2;
					output += tcInput.substring(x, x + 2);
				}
			}
		}
		return output;
	}

	uiPage.on('focus', ".tcValidate", function () {
		//Validate the timecode upon timeout.
		var element = $(this);
		if (element.val() != '') {
			tcValidate(element);
		}
	});

	uiPage.on('keyup', ".tcValidate", function () {
		//Validate the timecode upon timeout.
		var element = $(this);
		if (tcValidateTimer != null) {
			clearTimeout(tcValidateTimer);
			tcValidateTimer = null;
		}
		tcValidateTimer = setTimeout(function () {
			tcValidate(element);
		}, 500);
	});

	uiPage.on('blur', ".tcValidate", function () {
		//Convert TC fields when leaving the input.
		var element = $(this);
		element.val(convertTc($(this).val()));
		tcValidate(element);
	});

	uiPage.on("click", "[id^='tcValidateSave']", function () {
		//Save the tc values when checking.
		var lineId = $(this).attr('lineid');
		var tcValidateIn = $('#tcValidateIn' + lineId);
		var tcValidateOut = $('#tcValidateOut' + lineId);
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'tcValidateSave',
				'lineId': lineId,
				'tcValidateIn': tcValidateIn.val(),
				'tcValidateOut': tcValidateOut.val()
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error validate all timecode save');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					showMessage(false);
					var tcValidateAll = result.tcValidateAll ? result.tcValidateAll : 'error return tc validate save';
					buildLines(result.buildLines ? result.buildLines : 'error return tc validate save build lines');
					coverMe(tcValidateAll);
					showMessage(message, true);
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
			}
		})
	});

	uiPage.on("click", "#tcValidateAll", function () {
		//Validate the tc values of all lines.
		$.ajax({
			type: 'post',
			url: url,
			data: {
				'mode': 'tcValidateAll'
			},
			beforeSend: function () {
				spinner('working…');
			},
			error: function () {
				spinner('error validate all timecode');
			},
			success: function (result) {
				result = $.parseJSON(result);
				var message = result.message ? result.message : '';
				if (result.success == true) {
					showMessage(false);
					var tcValidateAll = result.tcValidateAll ? result.tcValidateAll : 'error return validate all timecode';
					coverMe(tcValidateAll);
				} else {
					showMessage(message, false);
				}
				if (result.debug) {
					debugElement.html(result.debug);
				}
			}
		})
	});


	function tcValidate(element) {
		/*
		 For validating single timecode field values. Returns true upon the following:
		 1. The timecode is valid in relation to the list framerate.
		 2. The hours match the reel, if reel is set.
		 Otherwise false is returned. Changes color of the input to green or red accordingly.

		 element = (jquery object) the timecode input field.
		 */
		var tcBad = false;
		var tcVal = element.val();
		if (tcVal.match(/^\d{2}:\d{2}:\d{2}:\d{2}$/)) {
			var entry = element.attr('entry');
			var parts = tcVal.split(':');
			var hours = parseInt(parts[0]);
			var minutes = parseInt(parts[1]);
			var seconds = parseInt(parts[2]);
			var frames = parseInt(parts[3]);
			var framerate = element.attr('framerate');
			var reel = $('#' + entry + 'Reel').val();
			reel = isNumeric(parseInt(reel)) ? parseInt(reel) : '';
			var totalSeconds = (hours * 60 * 60) + (minutes * 60) + seconds;
			//console.log('parts: ' + parts + '\nframerate: ' + framerate + '\nframes: ' + frames + '\nreel: ' + reel + '\nhours: ' + hours);
			if (isNumeric(framerate) && isNumeric(frames)) {
				if (frames * 1000 > framerate * 1000) {//Javascript doesn't like comparing floats.
					tcBad = true;
					validationWarning('Frames not valid for ' + element.attr('framerate') + ' framerate.', element);
				}
			}
			if (isNumeric(reel) && isNumeric(hours)) {
				//Hours
				if (hours != reel) {
					tcBad = true;
					validationWarning('Hour does not match reel.', element);
				}
			}
			if (!isNumeric(minutes) || minutes > 59) {
				//Minutes
				tcBad = true;
				validationWarning('Minutes not valid.', element);
			}
			if (!isNumeric(seconds) || seconds > 59) {
				//Seconds
				tcBad = true;
				validationWarning('Seconds not valid.', element);
			}
		} else {
			tcBad = true;
		}
		//Compare against other timecode field.
		var thisField = element.attr('id').indexOf('In') == -1 ? 'Out' : 'In';
		var otherField = $('#' + element.attr('otherfield'));
		otherFieldParts = otherField.val().split(':');
		otherFieldTotalSeconds = (parseInt(otherFieldParts[0]) * 60 * 60) + (parseInt(otherFieldParts[1]) * 60) + parseInt(otherFieldParts[2]);
		if (thisField == 'In') {
			if (totalSeconds > otherFieldTotalSeconds) {
				tcBad = true;
				validationWarning('TC In must be less than or equal to TC Out.', element);
			} else if (totalSeconds == otherFieldTotalSeconds && frames > parseInt(otherFieldParts[3])) {
				tcBad = true;
				validationWarning('TC In must be less than or equal to TC Out.', element);
			}
		} else {
			if (totalSeconds < otherFieldTotalSeconds) {
				tcBad = true;
				validationWarning('TC Out must be greater than or equal to TC In.', element);
			} else if (totalSeconds == otherFieldTotalSeconds && frames < parseInt(otherFieldParts[3])) {
				tcBad = true;
				validationWarning('TC Out must be greater than or equal to TC In.', element);
			}
		}
		if (tcBad) {
			element.css('background-color', '#FFACAC');
			otherField.css('background-color', '#FFACAC');
			return false;
		} else {
			element.css('background-color', '#B1FF99');
			otherField.css('background-color', '#B1FF99');
			validationWarning('', element);
			return true;
		}
	}

	//Swap timecode fields.---------------------------------------------------------------------------------------
	uiPage.on("click", ".swapTc", function () {
		console.log("swap");
		var entry = $(this).attr('entry');
		var tcIn = $('#' + entry + 'TcIn');
		var tcOut = $('#' + entry + 'TcOut');
		var tcInValue = tcIn.val();
		var tcOutValue = tcOut.val();
		tcIn.val(tcOutValue);
		tcOut.val(tcInValue);
		/*
		 DEPRECATED

		 if($(this).attr('lineid')){
		 var lineId = $(this).attr('lineid');
		 }else{
		 var lineId = '';
		 }
		 var tcIn = this.id.split('swap');
		 tcIn = $('#'+ tcIn[1] + 'TcIn' + lineId);
		 var tcOut = this.id.split('swap');
		 tcOut = $('#' + tcOut[1] + 'TcOut' + lineId);
		 var tcInVal = tcIn.val();
		 var tcOutVal = tcOut.val();
		 tcIn.val(tcOutVal);
		 tcOut.val(tcInVal);
		 tcValidate(tcIn.val(),tcIn);
		 tcValidate(tcOut.val(),tcOut);
		 */
	})

});