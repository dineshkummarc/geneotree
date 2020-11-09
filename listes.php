<?php
header('Content-type: text/html; charset=utf-8'); 
require_once ("_boites.inc.php");
require_once ("_stat.inc.php");
require_once ("_caracteres.inc.php");

function afficher_eclair($url, $aff)
{	global $old_colonne;
	global $old_colonne2;
	global $row;		// contient une ligne avec col1, col2, nb et dpt
	global $ii;
	global $result1; // liste des intervalle des lettres
	global $nb_car;
	
	if ($row[0] != $old_colonne) // rupture col1, on vient de finir de lire une occurrence de la colonne principale 
	{	echo '</td></tr>';
		if ($ii % 2 == 0) {echo '<tr class=ligne_tr1>';} else {echo '<tr class=ligne_tr2>';}
		echo '<td class=bords_verti width=230>';
				// affichage colonne principale
		if ( ($_REQUEST['ipag'] == "li" or $_REQUEST['ipag'] == "de") and $row[3] != '')	{$dept = '('.$row[3].')';} else {$dept = '';}	// si ville, on affiche le departement
		echo '<a href ="listes.php'.$url.'&iaff='.$aff.'&lcont='.mb_ereg_replace(' ','_',$row[0]).'"><b>'.mb_substr($row[0].$dept,0,26).'</b></a>';
//		echo '<a href ="listes.php'.$url.'&iaff='.$aff.'&icont2='.@mb_ereg_replace(' ','_',$row[0]).'"><b>'.$row[0].$dept.'</b></a>';

		if (mysqli_num_rows($result1) !== 0)
		{	// affichage nb occurrence de $colonne
			mysqli_data_seek($result1,0);	//reinitialisation pointeur  result colonne
			while ($row1 = mysqli_fetch_row($result1) and $row[0] !== $row1[0]) {} // parcours result colonne pour recuperer le nb
// BOGUE : ne fonctionne pas pour les departements
			echo '</td><td class=bords_verti width=20 align=center><b>'.$row1[1].'</b>'; 

			echo "</td><td class=bords_verti width=675>";
			if ($row[1] != '')	{echo $row[1];$nb_car = mb_strlen($row[1]);}		// affichage de la ville pour les noms/prenoms, du nom pour les villes
			if ($row[3] != '' and $_REQUEST['ipag'] != 'li')	{echo '('.$row[3].')';$nb_car = $nb_car + mb_strlen($row[3]) + 2;}		// affichage departement pour les noms/prenoms uniquement
			if ($row[1] != '') {echo '[<b>'.$row[2].'</b>] ';$nb_car = $nb_car + mb_strlen($row[2]) + 3;}		// affichage nb d'actes
		}
		$ii++;
	} elseif ($row[1] != $old_colonne2 and $nb_car <= 60) // boucle principale d'affichage de la col2
	{	if ($row[1] != '')	{echo $row[1];$nb_car = $nb_car + mb_strlen($row[1]);}		// affichage de la ville pour les noms/prenoms, du nom pour les villes
		if ($row[3] != '' and ($_REQUEST['ipag'] == "no" or $_REQUEST['ipag'] == "pr"))	{echo '('.$row[3].')';$nb_car = $nb_car + mb_strlen($row[3]) + 2;}		// affichage departement pour les noms/prenoms uniquement
		if ($row[1] != '') {echo '[<b>'.$row[2].'</b>] ';$nb_car = $nb_car + mb_strlen($row[2]) + 3;}		// affichage nb d'actes
//echo '/'.$nb_car;
	}
}

/********************************************** DEBUT DU SCRIPT ******************************************************/
$_REQUEST["ftop"] = 8;
$_REQUEST["scrolly"] = 0;

//$titre_page = "GeneoTree v".$got_lang['Relea']." - ".$got_lang['MenLi'];
require_once ("menu.php");

if (!isset($_REQUEST['ipag'])) {$_REQUEST['ipag'] = "no";}
if ($_REQUEST['ipag'] !== "no" and $_REQUEST['ipag'] !== "pr" and $_REQUEST['ipag'] !== "li" and $_REQUEST['ipag'] !== "de") {$_REQUEST['ipag'] = "no";}

switch ($_REQUEST['ipag'])
{	case "no" : $colonne = "nom";   		$colonne2 = "lieu_evene";	$menu = $got_lang['Noms'];break;
	case "pr" : $colonne = "prenom1";		$colonne2 = "lieu_evene";	$menu = $got_lang['Preno'];break;
	case "li" : $colonne = "lieu_evene";$colonne2 = "nom";				$menu = $got_lang['Lieux'];break;
	case "de" : $colonne = "dept_evene";$colonne2 = "lieu_evene";	$menu = $got_lang['Depar'];break;
}

if (!isset($_REQUEST['lcont'])) { $_REQUEST['lcont'] = "";}

			// detection des lettres par groupe de 35 
			// 		-> en avance de phase pour recuperation des lettres pour le titre
$result1 = recup_occurrences($_REQUEST['ibase'], $_REQUEST['ipag'], $colonne);	
$lettre = recup_lettres ($result1);

if (!isset($_REQUEST['adeb'])) { $_REQUEST['adeb'] = $lettre['deb'][0];}  // initialisation pour l'arrivee 
if (!isset($_REQUEST['afin'])) { $_REQUEST['afin'] = $lettre['fin'][0];}
if (!isset($_REQUEST['iaff'])) { $_REQUEST['iaff'] = $lettre['aff'][0];}

$menu = $menu.'&nbsp;'.$_REQUEST['adeb'].' - '.$_REQUEST['iaff'];

if ($_REQUEST['lcont'] == "")  //i.e 3eme volet inactif
{	echo '<table><tr><td width=925px>';   // 1ere colonne sur 3
} else
{	echo '<table><tr><td width=570px>';   // 1ere colonne sur 3
}

$url = url_request();

echo "<table><tr>";
echo "<td><a HREF = listes_pdf.php".$url."&itype title='".$got_lang['IBPdf']."' target=_blank><img border=0 width=35 heigth=35 src=themes/icon-print.png></a></td>";
if ($flag_excel !== "No")	
{	echo "<td><a HREF = listes_pdf.php".$url."&itype=excel title='".$got_lang['IBExc']."'><img border=0 width=35 heigth=35 src=themes/icon-excel.png></a></td>";
}
echo '<td class=titre width=510>'.$got_lang['MenLi'].'</td>';
echo '<td></td></tr>';
echo '<tr>';
//	if ( ($_REQUEST['ipag'] == "no" or $_REQUEST['ipag'] == "pr") /*and geo_pertinente($dept_naiss)*/ )
//	{	echo '<td>&nbsp;<a HREF=affichage_carte.php'.$url.'&icont2='.$_REQUEST['lcont'].'&carte= title="'.$got_lang['IBGeo'].'"><img width=35 heigth=35 border=0 src=themes/icon-maps-green.png></a></td>';
//		echo '<td>&nbsp;<a HREF=affichage_carte.php'.$url.'&icont2=&ifin=ge'.$_REQUEST['lcont'].'&carte= title="'.$got_lang['IBGeo'].'"><img width=35 heigth=35 border=0 src=themes/icon-maps-kml.png></a></td>';
//	}
echo '<td align=center colspan=3 width=570px>';

		// affichage du choix noms, departement, lieux, prenoms
echo '<form method=post>';
afficher_radio_bouton("ipag",array($got_lang['Noms'],$got_lang['Preno'],$got_lang['Depar'],$got_lang['Lieux']),array("no","pr","de","li"),$_REQUEST['ipag'],"YES");
echo '</td><td>';
echo '</form>';

echo "</td></tr></table>";

		// affichage des intervalles
echo '<br>';
echo '<p align=center style="background-color:white;">';
for ($ii = 0; $ii < count($lettre['deb']); $ii++)
{ echo ' <a class=menu_td 
		href=listes.php'.$url.'&adeb='.$lettre['deb'][$ii].'&afin='.$lettre['fin'][$ii].'&iaff='.$lettre['aff'][$ii].'>'.$lettre['deb'][$ii].'-'.$lettre['aff'][$ii].'</a>';
}
echo '</p>';

echo '<br><p class=titre  style="background-color:white;" >'.$menu.'</p>';

$result = recup_eclair($_REQUEST['ibase'], $_REQUEST['ipag'], $_REQUEST['adeb'],  $_REQUEST['afin'], $colonne, $colonne2);	

echo '<table class="bord_bas bord_haut">';
echo '<tr>';
echo '<td colspan=3>';	// TD bidon pour demarrer la rupture sur old_colonne

if (mysqli_num_rows($result) != 0)	// protection, car la boucle while n'est pas test裠sur fetch_row
{	$ii = 0;
	if ($_REQUEST['ipag'] !== "de")
	{	$row = mysqli_fetch_row($result);
		while(strtoupper(sans_accent($row[0])) < $_REQUEST['adeb'])	{$row = mysqli_fetch_row($result);}
		while(strtoupper(sans_accent($row[0])) < $_REQUEST['afin'] and $row[0] != '')
		{	afficher_eclair($url, $_REQUEST['iaff']); // fonction afficher_eclair dans cette page

			$old_colonne = $row[0];
			$old_colonne2 = $row[1];
			$row = mysqli_fetch_row($result);
		}
	} else
	{	while ($row = mysqli_fetch_row($result))
		{	afficher_eclair($url, $_REQUEST['iaff']);

			$old_colonne = $row[0];
			$old_colonne2 = $row[1];
		}
	}
}
echo '</td></tr>';
echo '<tr><td colspan=3</td></tr>';
echo '</table>'; // fin tableau liste

echo '</td>';  // fin 1籥 colonne



/************************************ affichage du 3eme volet liste des individus ***********************/

if ($_REQUEST['lcont'] != NULL )
{	echo '<td width=355px>'; //bordure gauche pour separateur
			// icont devient icont2 dans le contexte carte

	$dept_naiss = array();
	$result = recup_eclair2($_REQUEST['ibase'], $_REQUEST['ipag'], $_REQUEST['lcont']);
	while ($row = @mysqli_fetch_row($result))
	{	$dept_naiss[] = $row[9];
	}
	mysqli_data_seek($result,0);	//reinitialisation pointeur  result 

	echo '<p class=titre><b>'.mb_substr($_REQUEST['lcont'],0,22).'</b></p>';
	afficher_liste_individu ($result);

	echo '</td>';  // fin colonne volet liste individu
}

/************************************ affichage du 2eme volet fiche ***********************/

echo'<td width=355px>';   
require_once ("fiche.php");
echo '</td>';


echo '</tr></table>'; // on ferme tout correctement


?>