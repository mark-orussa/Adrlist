<?php
$fileInfo = array('fileName' => 'includes/indexMethods.php');
$debug->newFile($fileInfo['fileName']);
//Array format: title, content, image, anchor name, font awesome icon name.
$textArray = array(
	array(
		'Speed and Efficiency',
		'<p>Designed specifically for editing ADR Lists, there are many time-saving features.</p>
<p>The AJAX interface makes everything load fast and update immediately. Timecode numbers automatically conform.</p>',
		'timeMoney.png',
		'speed',
		'fa-clock-o'
	),
	array(
		'Lists without Boundaries',
		'<p>The internet is great, isn\'t it? Why not take advantage of it?</p>
<p>Mobile, tablet, or desktop - all of your ADR lists available through any web browser 24/7.</p>',
		'accessAnytime.png',
		'access',
		'fa-cloud'
	),
	array(
		'Easy to Share',
		'<p>10 producers, 40 actors, 10 editors...</p>
<p>Everyone can take a look with simple sharing options.</p>',
		'worldshare.png',
		'share',
		'fa-users'
	),
	array(
		'In Control',
		'<p>With so many people involved in the process it\'s easy to become confused as to which version is current.</p>
<p>With a centralized, shared list you can make sure everyone is on the same page.</p>',
		'worldshare.png',
		'control',
		'fa-sitemap'
	),
	array(
		'Organized Viewing',
		'<p>Want to view just George\'s lines from reel two? No problem! How about those same lines ordered by scene in descending order. Done!</p>
<p>Just about any organizational view option is at your fingertips.</p>
',
		'reorder.png',
		'organized',
		'fa-list'
	),
	array(
		'Tailored Print Views',
		'<p>Actors need their lines with familiar script-styled text and without all of the technical numbers. Recordists need those numbers.</p>
<p>Get what you need by using the great organizational tools to produce role-specific lists.</p>',
		'printer.png',
		'print',
		'fa-print'
	),
);
//<p>It\'s not just for dialogue. List all those car sound effects to give your editor a great guide. Got sound design ideas that re-occur throughout the movie? Add them to the list. Want to view all the lines that occur during flashback scenes? Toss them into a line group and view them separately from other lines.</p>
function buildSlides(){
	global $debug;
	$output = '<div class="relative" style="width:100%;margin-top:-1em">
	<div class="noise">
	</div>
	<div class="center relative textCenter" style="padding:1em">
		<div class="inline-block" style="padding:1em">
			<p class="textSuperLarge" style="color:#555;text-shadow: 1px 2px 0px #EEEEEE, 2px 4px 0px #FFFFFF;margin:0">ADR Lists</p>
			<p class="textLarge">Simplified</p>
			<p class="textLarge">Organized</p>
			<p class="textLarge">Flexible</p>
		</div>
		<!-- 680 x 365 -->
		<div class="inline-block relative" style="top:1em;padding-bottom:2em">
			<img src="' . LINKIMAGES . '/carousel/pic2.png" />
			<div class="fadein" style="left:0;position:absolute;top:0">
				<img src="' . LINKIMAGES . '/carousel/pic1.png" style="position:absolute;" />
				<img src="' . LINKIMAGES . '/carousel/pic2.png" style="position:absolute;" />
				<img src="' . LINKIMAGES . '/carousel/pic3.png" style="position:absolute;" />
				<img src="' . LINKIMAGES . '/carousel/pic4.png" style="position:absolute;" />
			<div>
		</div>
	</div>
</div>';
	return $output;
	/*
	<div class="blueberry" style="display:inline-block">
			<ul class="slides">
				<li><img src="' . LINKIMAGES . '/carousel/pic1.png" /></li>
				<li><img src="' . LINKIMAGES . '/carousel/pic2.png" /></li>
				<li><img src="' . LINKIMAGES . '/carousel/pic3.png" /></li>
				<li><img src="' . LINKIMAGES . '/carousel/pic4.png" /></li>
			</ul>
		</div>
		
	*/
}

function buildFeatures(){
	global $debug, $message, $textArray;
	$dataBoxes = '';
	foreach($textArray as $key => $value){
		$dataBoxes .= '<div class="dataBox">
	<a class="anchorHelper" name="' . $value['3'] . '"></a>
	<div class="bold textLeft" style="padding-bottom:5px">
		<i class="fa ' . $value[4] . ' fa-2x" style="color:#333;padding-right:.5em"></i>' . $value[0] . '
	</div>
	<div class="hr1"></div>
	<div class="textLeft" style="color:#444">
		' . $value[1] . '
	</div>
</div>';
	}
	//Use class="anchorHelper" on the anchor when you need to change the position of the anchor.
	$output = '<div class="textCenter">
	<a id="features"></a>
	' . $dataBoxes . '
	<div style="padding:2em">
	<a data-ajax="false" href="' . LINKCREATEACCOUNT . '?invitationCode=NA" class="ui-btn ui-btn-inline ui-shadow ui-corner-all ui-icon-arrow-r ui-btn-icon-right">Try it Now</a>
	</div>
</div>';
	return $output;
}