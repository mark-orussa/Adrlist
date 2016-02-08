var url = '../faq/index.php';

$(document).ready(function(){

	//Toggle topics.---------------------------------------------------------------------------------------------
	$(uiPage).on("click", "#faqHideAll", function(){
		console.log('hide all');
		$(".faqAnswer").hide(200);
	})
	
	$(uiPage).on("click", "#faqShowAll", function(){
		console.log('show all');
		$(".faqAnswer").show(200);
	})
});