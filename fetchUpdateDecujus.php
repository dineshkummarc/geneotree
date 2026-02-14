<?php
require_once  ("config.php");
require_once  ("_functions.php");
sql_connect();

// Récupérer la réponse envoyée par JavaScript
$id       = isset($_GET['id'])       ? $_GET['id']       : '';
$ibase    = isset($_GET['ibase'])    ? $_GET['ibase']    : '';
$sql_pref = isset($_GET['sql_pref']) ? $_GET['sql_pref'] : '';

$sql = 'UPDATE '.$sql_pref.'__base SET id_decujus = '.$id.' WHERE base = "'.$ibase.'"';
sql_exec($sql);

maj_cujus($ibase, $id);
