var url = location.href, cover, contentFloater, debugElement, messageFloater, limitTimer = null, uiPage, spinnerHolder, searchTimer = null, strangeThings = '6LccpOcSAAAAAIifB03tr5ccc7SDpmn6CrlLNN6O', tcValidateTimer = null, uiContent, res = document.location.href.match('/{2}\d\d.\d.\d.\d/'),timeZones = null;

if(document.location.href.indexOf('https') != -1){
	//We are secure
	var protocol = 'https';
}else{
	var protocol = 'http';
}

if(document.location.href.indexOf('dev') != -1 || document.location.href.match(/\/{2}\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/) != null){
	//Local
	var parts = document.location.href.split('adrlist.dev');
	var local = true;
	var autolink = parts[0]  + 'adrlist.dev';
	var cookiedomain = '.adrlist.dev';
}else{
	//Remote
	var local = false;
	var autolink = protocol + '://adrlist.com';
	var cookiedomain = '.adrlist.com';
}
var cookiepath = '/';

function brRemove(giraffe){//This converts <br>. 
	var newGiraffe = giraffe.replace(/<br>/g, '');//g is the global property that will replace all the occurances of <br>.
	return newGiraffe;
}

function charConvert(alligator){//DEPRECATED. This converts encoded new line characters back to new lines. This should be used on data after ajax has returned it. The new line data needs to be converted to %nl% in php before being handed over to ajax.
	/*
	for(i in alligator){
		alligator[i] = alligator[i].replace(/%nl%/g, "\n");//g is the global property that will replace all the occurances of %nl%.
		alligator[i] = alligator[i].replace(/%cr%/g, "\r");
		alligator[i] = alligator[i].replace(/%dq%/g, '"');
		alligator[i] = alligator[i].replace(/%sq%/g, "'");
	}
	/*else if(alligator.constructor == Array){
		alert('alligator is an array');
		for(var i=0; i<alligator.length; i++){
			alligator[i] = alligator[i].replace(/%nl%/g, "\n");//g is the global property that will replace all the occurances of %nl%.
			alligator[i] = alligator[i].replace(/%cr%/g, "\r");
			alligator[i] = alligator[i].replace(/%dq%/g, '"');
			alligator[i] = alligator[i].replace(/%sq%/g, "'");
		}
	}else{
		alert('alligator is a variable');
		alligator = alligator.replace(/%nl%/g, "\n");//g is the global property that will replace all the occurances of %nl%.
		alligator = alligator.replace(/%cr%/g, "\r");
		alligator = alligator.replace(/%dq%/g, '"');
		alligator = alligator.replace(/%sq%/g, "'");
	}*/
	return alligator;
}

//Custom checkboxes.------------------------------------------------------------------------------------------------------

	function checkAll(checkboxElement,checkedState){
		/*
		checkboxElement = (javascript object)
		checkedState = (boolean)
		The user has clicked the master checkbox. The master checkbox's id must match the slaves' "master" attribute.
		*/
		var master = $(checkboxElement).attr("id");
		$("[master='" + master + "']").each(function (){
			setCheckboxState(this,checkedState);
		});
	}
	
	function getCheckboxState(checkboxElement){
		/*
		checkboxImgElement = (javascript object) the img element to test the state of.
		Returns true if checked, false otherwise.
		*/
		if($(checkboxElement).is(':checked')){
			return true;
		}else{
			return false;
		}
	}
	
	function setCheckboxState(checkboxElement,state){
		/*
		checkbox = (javascript object)
		state = (boolean) true is checked, false is not checked.
		*/
		//if(startingElement.tagName.match(/label/i)){
		if(state){
			$(checkboxElement).prop("checked",true).checkboxradio("refresh");
		}else{
			$(checkboxElement).prop("checked",false).checkboxradio("refresh");
		}
	}
	
	function masterState(checkboxElement){
		/*
		checkboxElement (javascript element)
		The user has clicked on a view option with a master "select all" option. If any of the options are unchecked, uncheck the master "select all" checkbox. If all are checked, then the master will be checked.
		*/
		var master = $(checkboxElement).attr("master");
		var anyUnchecked = false;
		//Loop through all of the options.
		$('[master=' + master + ']').each(function(){
			var checkboxState = getCheckboxState(this);
			if(!checkboxState){
				//console.log("this is unchecked: " + $(this).attr("id"));
				anyUnchecked = true;
			}
		});
		if(anyUnchecked){
			setCheckboxState(document.getElementById(master),false);
		}else{
			setCheckboxState(document.getElementById(master),true);
		}
	}

function determineTimezone(url,label,callback){
	//Attempt to determine the user's timezone.
	var now = new Date();
	var timestampMilliseconds = now.getTime();
	var offsetMinutes = now.getTimezoneOffset();
	//console.log('The client side time is '+now.getHours()+':'+now.getMinutes() + '\ntimestampMilliseconds: ' + timestampMilliseconds + '\noffsetMinutes: ' + offsetMinutes);
	$.ajax({
		type: 'post',
		url: url,
		data:{
			'mode': 'buildTimeZones',
			'label': label,
			'timestampMilliseconds': timestampMilliseconds,
			'offsetMinutes': offsetMinutes
		},
		beforeSend: function(){
			spinner('working…');
		},
		error: function(){
			spinner('error build time zone',0);
		},
		success: function(result){
			result = $.parseJSON(result);
			var message = result.message ? result.message : '';
			if(result.success == true){
				coverMe();
				timeZones = result.timeZones ? result.timeZones : '';
				if(callback != null){
					callback();
				}
			}else{
				showMessage(message,0);
			}
			if(result.debug){
				debugElement.html('Initial debug before determineTimezone:' + debugElement.html() + result.debug);
			}
		}
	})
}

function emailValidate(email){
	/*
	DHTML email validation script. Courtesy of SmartWebby.com (http://www.smartwebby.com/dhtml/)
	email = (string) an email address.
	Returns true upon validation, otherwise a description of what is not valid. Use === or !== true to validate.
	*/
	if(email.length == 0){
		return 'Enter an email address.';
	}else{
		var at = "@";
		var dot = ".";
		var lat = email.indexOf(at);
		var lemail = email.length;
		var ldot = email.indexOf(dot);
		var re = /[a-zA-Z]{2,}$/;
		if(email.indexOf(at) == -1){
			return "Missing @ character.";
		}else if(email.indexOf(at) == 0){
			return "Missing characters before @.";
		}else if(email.indexOf(at) == lemail-1){
			return "Missing domain name.";
		}else if(email.indexOf(dot) == -1){
			return "Missing dot character.";
		}else if(email.indexOf(dot) == 0){
			return "Missing characters before dot.";
		}else if(email.indexOf(dot) == lemail-1){
			return "Missing characters after dot.";
		}else if(email.indexOf(at,(lat+1))!=-1){
			return "1.";
		}else if(email.substring(lat-1,lat) == dot || email.substring(lat+1,lat+2) == dot){
			return "Missing characters between @ and dot.";
		}else if(email.indexOf(dot,(lat+2)) == -1){
			return "3.";
		}else if(email.indexOf(" ")!=-1){//Are there spaces in email?
			return 'spaces in the email.';
		}else if(re.test(email)){
			return true;
		}else{
			return 'missing domain extension';
		}
	}
}

function errorReporter(publicMessage,err){
	//stuff here
	var publicMessage = publicMessage ? publicMessage : 'Whoops! We encountered an unexpected error. If this problem persists, please <a href="' + autolink + '/support">contact support</a>.';
	showMessage(publicMessage,false);
	if(typeof err !== 'undefined'){
		console.log(err ? err : '');
	}
}
/*----------------------------------------------------------------------
	Floater stuff. This is the spinner, cover, cover content, showMessage, etc.
*/
	function coverMe(content,triggerCreate){
		/*
		Produce the screen cover and optional content.
		showCover = (boolean).
		content = (string) html content.
		triggerCreate = (boolean) manually trigger the jqueryMobile create command.
		*/
		spinnerHolder.hide();
		triggerCreate = typeof triggerCreate == 'undefined' ? true : triggerCreate;
		if(content){
			cover.show();
			contentFloater.html('<div class="floaterContent"><a href="#" class="floaterCloseButton generalCancel ui-btn ui-corner-all ui-shadow ui-btn-a ui-icon-delete ui-btn-icon-notext ui-btn-right">Close</a>' + content + '</div>');
			if(triggerCreate){
				contentFloater.show().trigger("create");
			}
			contentFloater.css("top",$(window).scrollTop());
		}else{
			contentFloater.hide();
			cover.hide();
		}
	}
	
	function showMessage(message,fadeOut){
		/*
		Show a message at the top of the window.
		
		message = (string) The message displayed to the user. This also determines whether the message holder will show or hide.
		fadeOut = (boolean, optional) Determines whether the message holder will automatically fade to display:none or remain visible. Default is remain visible.
		retainCoverMe = (boolean, optional) False or empty will close the coverMe.
		*/
		spinnerHolder.hide();
		$("#showMessage").popup();
		if(message){
			$("#showMessage").html('<a href="#" data-rel="back" class="ui-btn ui-corner-all ui-shadow ui-btn-a ui-icon-delete ui-btn-icon-notext ui-btn-right">Close</a>' + message + '<div class="textCenter"><a style="text-decoration:none" class="ui-btn ui-btn-inline ui-corner-all ui-shadow ui-btn-icon-right ui-icon-delete ui-btn-b ui-mini" data-rel="back">Close</a></div>');
			$("#showMessage").show().popup("open").trigger("create");
			if(fadeOut){
				$("#showMessage").delay(1500).fadeOut(500,function(){
					$("#showMessage").popup("destroy");
				});
			}
		}else{
			$("#showMessage").popup("destroy").hide();
		}
	}
	
	function spinner(content){
		if(typeof content !== 'undefined'){
			cover.show();
			spinnerHolder.show().html('<div style="position:fixed;width:100%"><div class="textCenter"><a href="' + window.location.href + '"><img alt="" src="' + autolink + '/images/spinner.png" style="width:60px;height:30px"></a><div class="bold textCenter textLarge">' + content + '</div></div></div>');
		}else{
			spinnerHolder.hide();
			cover.hide();
		}
	}

//Automatically remove multiple popups created by jquery mobile.
$(document).on("tablecreate", function(event,ui){
	popupArray = new Object;
	$.each($(this).find($(".ui-popup-container")), function(){
		var tableId = $(this).attr("id");
		//console.log("tableId: " + tableId);
		if(typeof popupArray[tableId] == "undefined"){
			popupArray[tableId] = 1;
		}else{
			popupArray[tableId] = ++popupArray[tableId];
		}
	})
	for(var key in popupArray) {
		//console.log(key + ": " + popupArray[key]);
		while(popupArray[key] > 1){
			//There is more than one popup element with the same id. Delete the extras. The popup-screen will automatically close when the popup-popup is removed.
			popupArray[key] = --popupArray[key];
			$.each($("#" + key), function(){
				$(this).remove();
				//console.log("Removed "+ $(this).attr("id"));
			});
		}
	}
});

function isNumeric(n){
	return !isNaN(parseFloat(n)) && isFinite(n);
}

function passwordValidate(pass){
	var len = pass.length;
	var regEx = /^[A-Za-z\d!@]+$/;
	if(pass == ''){
		return 'Enter the password.'
	}else if(len < 6){
		return 'Password must be at least 6 characters.';
	}else if(!regEx.test(pass)){
		return 'Only a-z 0-9 ! @ are allowed.';
	/*else if(pass.indexOf('[') != -1 || pass.indexOf(']') != -1 || pass.indexOf('{') != -1 || pass.indexOf('}') != -1 || pass.indexOf('\\') != -1 || pass.indexOf('[') != -1 || pass.indexOf('#') != -1 || pass.indexOf('$') != -1 || pass.indexOf('%') != -1 || pass.indexOf('^') != -1 || pass.indexOf('&') != -1 || pass.indexOf('*') != -1 || pass.indexOf('(') != -1 || pass.indexOf(')') != -1){
		return 'Only a-z 0-9 ! and @ are allowed.'*/
	}else{
		return true;
	}
}

function redirect(address){
    window.location = address;
}

function setCookie(name, value, expires, path, domain){
	/* Set cookies
	Learn more at: http://www.w3resource.com/javascript/cookies/cookies-setting.php.
	name = (string) a single string with no commas, semicolons or whitespace characters.
	value = (string) a single string with no commas, semicolons or whitespace characters.
	expires = (int) the amount of time in days from the current time when the cookie will expire. If no value is set for expires, it will only last as long as the current session of the visitor, and will be automatically deleted when they close their browser. 
	path = (string) By default the path value is ‘/’, meaning that the cookie is visible to all paths in a given domain. It's good practice to not assume the path to the site root will be set the way you want it by default, so set this manually to '/'.
	domain - (string) If you don’t specify the domain, it will belong to the page that set the cookie. Set the domain if you are using the cookie on a subdomain, like widgets.yoursite.com, where the cookie is set on the widgets subdomain but you need it to be accessible over the whole yoursite.com domain. If a domain is specified it must begin with a ".".
	*/
	var today = new Date();
	today.setTime(today.getTime());
	if(expires){
		expires = expires * 1000 * 60 * 60 * 24;
	}
	var expires_date = new Date(today.getTime() + (expires));
	var temp = name + "=" +escape(value) +
	((expires) ? ";expires=" + expires_date.toGMTString() : "") +
	((path) ? ";path=" + path : "/") + ((domain) ? ";domain=" + domain : "");
	console.log('cookie attempt: ' + temp);
	document.cookie = temp;
}

function setPagination(offset,limit,action,uniqueId){
	var data;
	var searchfield = $(".searchfield[action=" + action + "]");
	var searchVal = searchfield.val() != searchfield.attr("default") ? searchfield.val() : '';
	var data = 'mode=' + action + '&' + uniqueId + 'Offset=' + offset + '&' + uniqueId + 'Limit=' + limit + '&searchVal=' + searchVal;
	for(x=1;typeof searchfield.attr("searchparamname" + x) !== 'undefined';x++){
		//console.log(searchfield.attr("searchparamname" + x) + ': ' + searchfield.attr("searchparamvalue" + x));
		data = data + '&' + searchfield.attr("searchparamname" + x) + '=' + searchfield.attr("searchparamvalue" + x);
	}
	//console.log('data: ' + data);
	if(cover.css('display') == 'none'){
		var doCoverMe = false;
	}else{
		var doCoverMe = true;
	}
	$.ajax({
		type: 'post',
		url: url,
		data:data,
		beforeSend: function(){
			spinner('working...');
		},
		error: function(){
			spinner('error setPagination');
		},
		success: function(result){
		spinner();
		result = $.parseJSON(result);
			var message = result.message ? result.message : '';
			if(result.success == true){
				if(doCoverMe){
					coverMe(result.output ? result.output : 'error return search');
				}else{
					$('#' + result.holder).html(result.output ? result.output : 'error return search').trigger("create");
					coverMe();
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

function triggerThis(element,callback){
	/*
	Trigger the jquery mobile create method on something.
	element (jquery element) the element to create.
	callback (javascript code) to be run before calling create.
	*/
	if(callback != null){
		callback();
	}
	element.trigger("create");
}

function validationWarning(message,element){
	/*
	message is the text to show.
	element is a jquery object below which the message will be displayed.
	*/
	//Remove existing warnings.
	$(".validationWarning").remove();
	element.parent().after('<div class="validationWarning">' + message + '</div>');
}

$(document).ready(function(){
	var email = $("#email");
	var pass = $("#password");
	debugElement = $("#debug"), cover = $(".cover"), contentFloater = $(".contentFloater"), messageFloater = $("#messageFloater"), uiPage = $(".ui-page"), spinnerHolder = $(".spinnerHolder"), uiContent = $(".ui-content");//The spinner png image holder.
	var spinnerPosition = $(window).height()/2;
	spinnerHolder.css("top",spinnerPosition + "px");//Vertically center the spinner.

	//What did I just click on?
	/*$(document).click(function(event) {
		console.log('html:' + $(event.target).html() + '\nparent:' + $(event.target).parent().attr("id") + '\nchild: ' + $(event.target).children().attr("id"));
	});*/

	//General operations.
		//Make hash anchors work. Jquery mobile messes with them.
		var anchor_id = window.location.hash;
		if(anchor_id != ""){
			var new_position = $(anchor_id).offset();
			setTimeout(function(){window.scrollTo(new_position.left,new_position.top)}, 500);
		}

		uiPage.on("click", "#noGoogle", function(){
			//Disable Google Analytics for testing.
			_gaq.push(["_setVar", "test_value"]);
		});

		$(window).bind("resize", function(){//Adjust the spinner position when the window is resized.
			spinnerHolder.css("top",$(window).height()/2 + "px");
		});
		
		//General cancel to close floaters and messages.
		uiPage.on("click", ".generalCancel", function(){
			$(this).closest(".floater").empty().hide();
			var hideCover = true;
			$(".floater").each(function(){
				if($(this).css("display") != "none"){
					hideCover = false;
				}
			})
			if(hideCover){
				cover.hide();
			}
		})
		
		$(document).keyup(function(event){
			//General cancel via escape key.
			var k = event.keyCode;
			if(k==27){
				coverMe();
			}
		});
		
		//When closing a jquery mobile popup, also hide the cover. Table column choosers are popups as well.
		$(document).on("popupafterclose", function(){
			//coverMe();
		})		

		uiPage.on("click", "[toggle]", function(){
			/*
			Toggle something.
			There should be a containing element around the arrow img. That container needs a custom attribute of "toggle"  that specifies the ID of the element to toggle.
			*/
			var toggleMe = $(this).attr('toggle');
			//Hide all toggle elements.
			if(contentFloater.css('display') == 'block'){
				//Close only elements in the contentFloater.
				contentFloater.find($("[toggle]")).each(function (){
					$("#" + $(this).attr("toggle")).slideUp(200);
					$(this).toggleClass("ui-icon-carat-r",true);
					$(this).toggleClass("ui-icon-carat-d",false);
					/*var img = $(this).find("img[src*='Down']");
					if(typeof img.attr('src') !== 'undefined'){
						var src = img.attr('src').replace("Down","Right");
						img.attr("src", src);
					}*/
				});
			}else{
				$("[toggle]").each(function (){
					if($(this).css("display") !== "none"){
						//Close all toggles outside of contentFloater.
						$("#" + $(this).attr("toggle")).slideUp(200);
						var arrow = $(this).find(".fa");
						//arrow.removeClass("fa-rotate-90");
						$(this).toggleClass("ui-icon-carat-r",true);
						$(this).toggleClass("ui-icon-carat-d",false);
					}
				});
			}
			//Toggle the stuff outside of contentFloater.
			var arrow = $(this).children(".fa");
			if($('#' + toggleMe).css('display') == 'none'){
				$('#' + toggleMe).slideDown(200);
				//arrow.toggleClass("fa-rotate-90",true);
				$(this).toggleClass("ui-icon-carat-r",false);
				$(this).toggleClass("ui-icon-carat-d",true);
			}else{
				$('#' + toggleMe).hide();
				//arrow.toggleClass("fa-rotate-90",false);
				$(this).toggleClass("ui-icon-carat-r",true);
				$(this).toggleClass("ui-icon-carat-d",false);
			}
		})
		
		//Sends the browser's time info
		if($("#timeZoneHolder").length !== 0){
			var label = $("#timeZoneHolder").attr("label") ? $("#timeZoneHolder").attr("label") : '';
			determineTimezone(url,label,function(){
				$("#timeZoneHolder").html(timeZones).trigger( "create" );
			})
		}

	//Jquery mobile checkboxes.------------------------------------------------------------------------------------------
		uiPage.on("change", ":checkbox", function(){
			/*
			Will trigger a function by adding callback="function name" in the element.
			*/
			var checkedState = getCheckboxState(this);
			var triggerfunction = $(this).attr('callback');
			if(triggerfunction != '' && triggerfunction != null && triggerfunction !== 'undefined'){
				window[triggerfunction](this,checkedState);//window["functionName"](element,checkbox state)
			}
		})
		
		//Allow clickable links in jquery mobile checkboxes.
		$(".ui-checkbox a").bind("tap click", function( event, data ){
			event.stopPropagation();
			//$.mobile.changePage($(this).attr('href'));
		});

	//General Input field operations.-----------------------------------------------------------------------
		uiPage.on("keypress",function(e){
			//Detect enter key for input fields. Trigger the goswitch.
			if(e.keyCode == 13){
				var thisIsActive = $(document.activeElement);
				//console.log('active element: ' + thisIsActive.html());
				if(thisIsActive.is("input")){
					var clickThisName = thisIsActive.attr("goswitch");
					if(typeof $("#" + clickThisName).val() !== 'undefined'){
						$("#" + clickThisName).click();
					}
				}
			}
		});	
	
		uiPage.on("focus", ":input", function(){
			//Remove the default input value and the classes.
			if($(this).attr('autoreset') == 'true'){
				$(this).removeClass('italic grey');
				if($(this).val() == $(this).attr('default')){
					$(this).val('');
				}
			}
		});	
	
		uiPage.on("blur", ":input", function(){
			//Reset the field when focus leaves the input.
			if($(this).attr('autoreset') == 'true'){
				var inputVal = $(this).val();
				if(inputVal == '' || inputVal == ' '){
					$(this).addClass('italic grey').val($(this).attr('default'));
				}
			}
		})

	//Pagination.-----------------------------------------------------------------------------------------------
		uiPage.on("click", ".goToPage", function(){
			//Set the pagination when clicking a page number.
			var offset = $(this).attr('offset');
			var limit = $(this).attr('limit');
			var action = $(this).attr('action');
			var uniqueId = $(this).attr('uniqueId');
			setPagination(offset,limit,action,uniqueId);
		})
	
		uiPage.on("click", ".setLimitButton", function(){
			//Set limit.
			var limit = $(this).prev(":input").val();
			var action = $(this).attr('action');
			var uniqueId = $(this).attr('uniqueId');
			setPagination(0,limit,action,uniqueId);
		});
		
		uiPage.on("click", ".limitShowAll", function(){
			/*
			Show all items.
			action is the function to call.
			uniqueId is an arbitrary name to avoid conflicts with other jquery mobile table functionality. Each table must have a unique id.
			*/
			var action = $(this).attr('action');
			var uniqueId = $(this).attr('uniqueId');
			setPagination(0,0,action,uniqueId);
		})
	
	//Search functions.--------------------------------------------------------------------------------------------		
		
		//Submit search.
		uiPage.on("click", ".searchButton", function(){
			var searchfield = $("[goswitch='" + $(this).attr("id") + "']");
			doSearch(searchfield,false);
		});

		//Clear the search by clicking the jquery mobile search field clear x button.
		uiPage.on("click", "[title='Clear text']", function(){
			if($(this).prev("input").attr("data-type") == 'search'){
				var searchfield = $(this).siblings("input");
				doSearch(searchfield,1);
			}
		});
	
		function doSearch(searchfield,clear){
			/*
			Start a search. This is dependent on the correct naming scheme being used between the name, search field ids, and server-side scripting. 
			searchfield - (jquery object) For the name 'buildMonkey' the input element's action attribute is also buildMonkey, the associated processing function buildMonkey(), the return holder id="buildMonkeyHolder", and return script  $returnThis['buildMonkey'] = $output;.
			clear - (boolean) true will reset the return holder to default.
			*/
			var action = searchfield.attr('action');
			var searchVal = searchfield.val();
			//Determine the return location.
			var doSearch = false;
			if(searchVal != '' && searchVal != ' ' && searchVal != '  ' && searchfield){
				doSearch = true;
			}
			if(clear){
				doSearch = true;
			}
			if(doSearch){
				var data = 'mode=' + action + '&searchVal=' + searchVal;
				//Do a loop through additional parameters here.
				for(x=1;typeof searchfield.attr("searchparamname" + x) !== 'undefined';x++){
					//console.log(searchfield.attr("searchparamname" + x) + ': ' + searchfield.attr("searchparamvalue" + x));
					data = data + '&' + searchfield.attr("searchparamname" + x) + '=' + searchfield.attr("searchparamvalue" + x);
				}
				//data = data + '&' + param1 + '=' + param1val;
				$.ajax({
					type: 'post',
					url: location.href,
					data:data,
					beforeSend: function(){
						if(clear == true){
							spinner('clearing...');
						}else{
							spinner('searching...');
						}
					},
					error: function(){
						showMessage('error search ' + action,false);
					},
					success: function(result){
						result = $.parseJSON(result);
						var message = result.message ? result.message : '';
						if(result.success == true){
							showMessage(message,true);
							if(contentFloater.css('display') == 'block'){
								coverMe(result.output ? result.output : 'error return search');
							}else{
								$('#' + result.holder).html(result.output ? result.output : 'error return search');
								coverMe();
							}
							uiPage.trigger("create");
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

});