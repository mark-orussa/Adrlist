<?php require_once ('../../includes/config.php');//Place at top of all pages before all other includes.
require_once('errorReportingMethods.php');
header("HTTP/1.1 403 Forbidden");
$fileInfo = array('title' => '403 Error', 'fileName' => 'errors/403.php');
$debug->newFile($fileInfo['fileName']);
//errorReporting(403);
die('Forbidden');?>