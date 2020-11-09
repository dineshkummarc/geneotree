<?php
require_once ("_boites.inc.php");
require_once ('_graphes.inc.php');
require_once ("_stat.inc.php");
require_once ("_caracteres.inc.php");

	
/********************************************** DEBUT DU SCRIPT ******************************************************/
require_once ("menu.php");
echo '<script src="chartjs/Chart.min.js"></script>';   // appel apres le menu pour que le header force bien le charset ISO
//	echo '<script src="chartjs/Chart.HorizontalBar.js"></script>'; // script pas stable. Non deployé.

if (!isset($_REQUEST['ipag'])) {$_REQUEST['ipag'] = "no";}
if (!isset($_REQUEST['sosa'])) {$_REQUEST['sosa'] = "Tous";}
if (!isset($_REQUEST['ideb'])) {$_REQUEST['ideb'] = "";}
if (!isset($_REQUEST['sens'])) {$_REQUEST['sens'] = "";}

$url = url_request();
$titre = "";
$titre2 = "";

if ($_REQUEST['ipag'] !== 'no' and $_REQUEST['ipag'] !== 'li' and $_REQUEST['ipag'] !== 'pr' and $_REQUEST['ipag'] !== 'de') {$_REQUEST['ipag'] = "no";}

switch ($_REQUEST['ipag'])
{	case 'no' : $titre = $got_lang['Noms']; break;
	case 'li' : $titre = $got_lang['Lieux']; break;
	case 'pr' : $titre = $got_lang['Preno']; break;
	case 'de' : $titre = $got_lang['Depar']; break;
}

$debfin = recup_intervalle($_REQUEST['ibase'], $_REQUEST['ipag'], $_REQUEST['ideb'], $_REQUEST['intervalle'], $_REQUEST['sens']); // donne ideb et ifin en sortie

if ($debfin[0] !== "") {	$titre2 = $debfin[0].' '.$got_lang['Et'].' '.$debfin[1];}

echo '<table><tr><td width=925px>';   // 1ere colonne sur 3

echo "<table width=100%><tr>";
//	echo "<td><a HREF = graphes_pdf.php".$url." title='".$got_lang['IBPdf']."'><img border=0 width=35 heigth=35 src=themes/icon-print.png></a></td>";  //temporairement desactivé.
echo "<td class=titre width=100%>".$got_lang['MenGr']."</td>";
echo "</tr></table>";

afficher_filtres($_REQUEST['ibase'], $_REQUEST['ipag'], "", '%', $debfin[0], $_REQUEST['intervalle'], $_REQUEST['palma'], $url);

echo '<br><p class=titre  style="background-color:white;">';
echo $_REQUEST['palma'].' '.$got_lang["Premi"].' ';
echo $titre;
if ($_REQUEST['sosa'] == "Sosa") {echo ' '.$_REQUEST['sosa'];}
if ($titre2) {echo ' '.$got_lang['Entre'].' '.$titre2;}
echo '</p>';

if (version_gd() == TRUE)
{	$res = recup_palmares($_REQUEST['ibase'], $_REQUEST['ipag'], $debfin[0], $debfin[1], $_REQUEST["sosa"]);
	$nom_graph = bar_horiz ($res['nom'],$res['nb'],'Base '.$_REQUEST['ibase'].' - Top '.$_REQUEST['palma'],$titre.' '.$titre2,'aagraph',$_REQUEST['palma']);
	echo '<p align=center>';
	echo '<img border="0" src="'.$nom_graph.'.png">';
	echo '</p>';
} else
{	echo $got_lang['MesGd'];
}
//	echo '</td><td></td>';	// tableau uniquement pour limiter la taille

echo '</td><td width=1px></td>';  //2eme colonne vide
echo'<td width=1px>';   //3eme colonne vide
echo '</td></tr></table>'; // on ferme tout correctement
?>