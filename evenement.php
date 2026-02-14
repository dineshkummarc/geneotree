<?php
header('Content-type: text/html; charset=utf-8');
require_once  ("_sql_requests.php");
require_once ("_functions.php");

/****************************** DEBUT DU SCRIPT ***********************************/
if (!isset($_REQUEST['exp'])) {$_REQUEST['exp'] = "";}

if ($_REQUEST["exp"] == "excel")
{   //require_once ("_sql.inc.php");
    require_once  ("_sql_requests.php");
    require_once ("languages/".$_REQUEST['lang'].".php");
    $pool = sql_connect();

    $result_evene = get_events();

    header('Content-type:application/vnd.ms-excel; charset=UTF-8');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="GeneoTree_Sources_'.$_REQUEST['pag'].'.csv"');

    $ligne =  
	        $got_tag["DATE"]
	.chr(9).$got_lang['Evene']
    .chr(9).$got_lang['Lieux']
    .chr(9).$got_tag['NAME']
	.chr(9).$got_tag["SEX"]
    .chr(9).$got_tag["NOTE"]
    .chr(10);
    echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');

    if ($result_evene) 
	{   // Parcourir les résultats
		$ii=0;
        while ($row = mysqli_fetch_assoc($result_evene)) 
		{  if (isset($row["note_evene"])) 
		   { $note_evene = mb_ereg_replace("\n", " ", $row["note_evene"]);
	         $note_evene = mb_ereg_replace("\r", " ", $row["note_evene"]);
	       } else 
		   { $note_evene = "";
	       }
	       $ligne = 
	               fctDisplayDateExcel($row["date_evene"])
		   .chr(9).$got_tag[$row["type_evene"]]
		   .chr(9).$row["lieu_naiss"]
		   .chr(9).$row["nom"]
		   .chr(9).$row["sexe"]
           .chr(9).$note_evene
		   .chr(10);
			;
		  echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');
        }
        // Libérer le jeu de résultats
        mysqli_free_result($result_evene);
    }
}
else
{


// require_once  ("_sql.inc.php");
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

if (is_numeric($row[0]) && is_numeric($row[1]))
{ $minValue = $row[0] - 20 + (($row[1] - $row[0]) % 20); // adjust minvalue to have a good 20 interval
  $maxValue = $row[1];
} else
{ $minValue = '0000';
  $maxValue = date('Y');
}

include ("menu.php");

if ($_REQUEST['pag'] == "%") {$_REQUEST['pag'] = "_";}

$url = url_request(); // juste après les initialiations, avant les href
?>
<script>
DivIcons ("DivIcon1", "themes/icon-excel.png", "evenement.php" + "?" + HrefBase + "&exp=excel");
SubMenuSql ("get_menu_events");
SubMenuSex ();
SubMenuSosa ();
DivSearch();
window.onload = initializeSlider(); // call pagination via date slider

// display Map
var xhr = new XMLHttpRequest();
xhr.open("GET", `get_data.php?start=0&${HrefBase}&pag=${getPag}&rech=${Rech}&sosa=${Sosa}&sex=${Sex}&fonc=get_events&p1=&p2=&p3=&p4=&sort_column=1&sort_order=ASC`, true);
xhr.onreadystatechange = function() 
{	if (xhr.readyState == 4 && xhr.status == 200) 
	{ var data = JSON.parse(xhr.responseText);
    data.sort((a, b) => a.nom.localeCompare(b.nom));
    // fctDisplayMap(data);
	} 
else 
{ //console.log(xhr.responseText);
}
};
xhr.onerror = function () 
{  console.error('Erreur lors de la requête Ajax ');
};
xhr.send();


</script>

<?php
}