<?php //This document will only allow access with siteAdmin credentials. When this document is included you do not need to include config.php or force SSL, as they are included below through auth.php.
require_once('../../includes/auth.php');
if($_SESSION['siteRoleId'] != 5){
	header('Location:' . LINKLOGIN);
}