<?php
header('Content-type: text/html; charset=utf-8');
require_once ("_sql_requests.php");
require_once ("_functions.php");

include ("config.php");
sql_connect();
// get min and max years to initialize date slider before write div tags
$query = '
  SELECT MIN(substring(date_evene,1,4)), MAX(substring(date_evene,1,4))
  FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement`
  WHERE date_evene > "0000"
';
$result = sql_exec($query,0);
$row = mysqli_fetch_row($result);
$minValue = $row[0];
$maxValue = $row[1];

include ("menu.php");

$url = url_request(); // get ibase and lang
$Href = 'listes_pdf.php'.$url.'&pag='.$_REQUEST["pag"];
?>

<script>
DivIcons ("DivIcon1", "themes/icon-print.png", "listes_pdf.php" + "?" + HrefBase + "&exp=pdf");
DivIcons ("DivIcon2", "themes/icon-excel.png", "listes_pdf.php" + "?" + HrefBase + "&exp=excel");
if (flagMaps == 1) 
  dataJson = `[{"Code":"nom", "Nb":0},{"Code":"lieu_evene", "Nb":0},{"Code":"dept_evene", "Nb":0},{"Code":"region_evene", "Nb":0},{"Code":"country_evene", "Nb":0},{"Code":"prenom1", "Nb":0}]`;
else 
  dataJson = `[{"Code":"nom", "Nb":0},{"Code":"lieu_evene", "Nb":0},{"Code":"dept_evene", "Nb":0},{"Code":"prenom1", "Nb":0}]`;
SubMenuJson(dataJson);
SubMenuSex ();
SubMenuSosa ();
DivSearch ();
window.onload = initializeSlider(); // call pagination via date slider
</script>
