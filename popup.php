<!DOCTYPE html>
<HTML>
<HEAD>
	<TITLE>GeneoTree - Image Viewer</TITLE>
	<LINK rel="shortcut icon" href="themes/geneotre.ico">
  <META http-equiv="Content-type" content="text/html; charset=utf-8" name="author" content="Damien Poulain">
</HEAD>
<BODY>
	
<?php
$url = str_replace('+',' ',$_REQUEST['pict']);
echo '<img src="'.$url.'">';
?>

</BODY> 
</HTML>
  