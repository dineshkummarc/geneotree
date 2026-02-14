<?php
require_once('config.php'); 
require_once  ("_functions.php");

sql_connect();

// Retrieve the response sent by JavaScript
$base     = isset($_GET['base'])     ? $_GET['base']     : '';
$sql_pref = isset($_GET['sql_pref']) ? $_GET['sql_pref'] : '';
$password = isset($_GET['password']) ? $_GET['password'] : '';
$hide75   = isset($_GET['hide75'])   ? $_GET['hide75']   : '';

if (!$base || !$sql_pref || $hide75 === '') 
{
  http_response_code(400);
  exit('Parameters missings.');
}

$query = "
  UPDATE `".$sql_pref."__base` 
  SET 
   password = '".$password."'
  ,hide75   = ".$hide75." 
  WHERE base = '".$base."'
  ";
sql_exec($query,0);
?>
