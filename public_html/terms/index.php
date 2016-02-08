<?php require_once('../../includes/config.php');
$fileInfo = array('title' => 'Terms and Conditions', 'fileName' => 'legal/index.php');
$debug->newFile($fileInfo['fileName']);
$buildPage = new Adrlist_BuildPage();
echo $buildPage->output(), '
<div style="padding:0 0px 10px 0px">
	By using this web site you agree to the following terms and conditions:
</div>
<div class="sectionTitle bold"
	>' . THENAMEOFTHESITE . ' is available "AS-IS"
</div>
<div class="indent">
	Although we are happy to make the services of this site available to you, use of the site is AS-IS and at your own risk. No warranty is expressed or implied. Features of the service may change or be removed without notice. Not all features are guaranteed to be fully functional.
</div>
<div class="sectionTitle bold">
	Your use of the service
</div>
<div class="indent">
	You will not attempt to hack, damage, deface, reverse-engineer, or cause interference to the site or it\'s services. You will not interfere with other users\' use of this site. You, and not Mark Pro Audio Inc, are responsible for maintaining and protecting all of your information. We will not be liable for any loss or corruption of your information, or for any costs or expenses associated with backing up or restoring any of your information.
</div>
<div class="indent">
	If your contact information, or other information related to your account, changes, you must notify us promptly and keep your information current. The services are not intended for use by you if you are under 13 years of age. By agreeing to these terms, you are representing to us that you are over 13.
</div>
<div class="sectionTitle bold">
	Your account and security
</div>
<div class="indent">
	It is your responsibility to ensure the security of your account. You agree not to disclose your password to any third party. You are responsible for any activity on your account whether you provide authorization or not. You should immediately <a href="' . LINKCONTACT . '">notify us</a> if you detect any unauthorized access.
</div>
<div class="sectionTitle bold">
	Property and suggestions
</div>
<div class="indent">
	You are not given any right, title, or hold on any services or features of the site. You have no right to use any trademarked images or features without implicit permission. We are happy to accept comments and suggestions on how to improve the site. We reserve the right to implement improvements without obligation to you.
</div>
<div class="sectionTitle bold">
	Termination
</div>
<div class="indent">
	You may stop using this site and it\'s services at any time. You are not eligible for any reimbursement or payment for unused time or services. We reserve the right to suspend or end the services at any time, with or without cause, and with or without notice. For example, we may suspend or terminate your use if you are not complying with these terms, or use the services in any way that would cause us legal liability or disrupt others\' use of the services. If we suspend or terminate your use, we will try to let you know in advance and help you retrieve data, though there may be some cases (for example, repeatedly or flagrantly violating these terms, a court order, or danger to other users) where we may suspend immediately.
</div>
<div class="sectionTitle bold">
	Limitation of Liability
</div>
<div class="indent">
	TO THE FULLEST EXTENT PERMITTED BY LAW, IN NO EVENT WILL MARK PRO AUDIO INC, ITS AFFILIATES, OFFICERS, EMPLOYEES, AGENTS, SUPPLIERS OR LICENSORS BE LIABLE FOR (A) ANY INDIRECT, SPECIAL, INCIDENTAL, PUNITIVE, EXEMPLARY OR CONSEQUENTIAL (INCLUDING LOSS OF USE, DATA, BUSINESS, OR PROFITS) DAMAGES, REGARDLESS OF LEGAL THEORY, WHETHER OR NOT MARK PRO AUDIO INC HAS BEEN WARNED OF THE POSSIBILITY OF SUCH DAMAGES, AND EVEN IF A REMEDY FAILS OF ITS ESSENTIAL PURPOSE; (B) AGGREGATE LIABILITY FOR ALL CLAIMS RELATING TO THE SERVICES MORE THAN THE GREATER OF $20 OR THE AMOUNTS PAID BY YOU TO MARK PRO AUDIO INC FOR THE PAST THREE MONTHS OF THE SERVICES IN QUESTION. Some states do not allow the types of limitations in this paragraph, so they may not apply to you.
</div>',
$buildPage->buildFooter();