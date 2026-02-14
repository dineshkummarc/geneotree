<?php
require_once  ("config.php");
require_once  ("_functions.php");
sql_connect();

// Retrieve the response sent by JavaScript
$ibase    = isset($_GET['ibase'])    ? $_GET['ibase']    : '';
$sql_pref = isset($_GET['sql_pref']) ? $_GET['sql_pref'] : '';

$query = 'DROP TABLE IF EXISTS `'.$sql_pref.'_'.$ibase.'_individu`';
$result = sql_exec($query);
$query = 'DROP TABLE IF EXISTS `'.$sql_pref.'_'.$ibase.'_source`';
sql_exec($query);
$query = 'DROP TABLE IF EXISTS `'.$sql_pref.'_'.$ibase.'_evenement`';
sql_exec($query);
$query = 'DROP TABLE IF EXISTS `'.$sql_pref.'_'.$ibase.'_even_sour`';
sql_exec($query);
$query = 'DELETE FROM `'.$sql_pref.'__base` WHERE base = "'.$ibase.'"';
sql_exec($query);
