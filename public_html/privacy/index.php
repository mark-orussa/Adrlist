<?php require_once('../../includes/config.php');
$fileInfo = array('title' => 'Privacy Policy', 'fileName' => 'privacy/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
echo $buildPage->output(), '
This describes how ' . THENAMEOFTHESITE . ' handles information you provide.
<div class="sectionTitle">I. Information We Collect</div>
You are asked to provide your name and email address when joining this site. ' . THENAMEOFTHESITE . ' uses Google Anaylytics, a service that aggregates non-personal information such as browser type, referring site, time spent on pages, and ip address.
<div class="sectionTitle">II. How We Use Information We Collect</div>
Your personal information is used only to enable collaboration with other users. Other users can see your name in relation to lists they have access to. Non-personal statistical information is used to improve operation, ease-of-use, and access to users. It exists only temporarily and is not disclosed to third parties.
<div class="sectionTitle">III. Email Addresses</div>
If you provide your email address it will only be used to send you emails related to the operation of this site. Your email address is not disclosed to third parties.
<div class="sectionTitle">IV. Leaving the Service</div>
You may close your account and leave the service at any time. Please <a href="' . LINKCONTACT . '" data-ajax="false">contact</a> an administrator.
<div class="sectionTitle">V. Changes to this Policy</div>
Any changes to this policy will be displayed here.
', $buildPage->buildFooter();?>