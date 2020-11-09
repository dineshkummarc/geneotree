<?php
require_once ("_boites.inc.php");
require_once ("_caracteres.inc.php");
require_once ("_stat.inc.php");

function afficher_stat($result, $url, $format = "age")
{		// $format = age (defaut) ou nb
	global $got_lang;
	
	echo '<table class="bord_bas bord_haut">';
	$ii = 1;
	while ($row = @mysqli_fetch_row($result))   // arobase au cas ou pas de lignes, rare, base brioul
	{	if ($row[5] !== NULL)
		{	if ($ii % 2 == 0) {echo '<tr class=ligne_tr1>';} else {echo '<tr class=ligne_tr2>';}
			echo '<td class=bords_verti width=15>'.$ii.'</td>';
			echo '<td class=bords_verti width=310>';
			afficher_lien_indiv($row[0],$url, $row[1],$row[2],$row[3],"","",$row[6],NULL,NULL,32,"NO");
			echo '</td>';
			echo '<td class=bords_verti width=30>&nbsp;'.affichage_date($row[4],"YES").'&nbsp;</td>';
			if ($format == 'age')
			{	$age = nbj2age($row[5]);
				echo '<td class=bords_verti width=90 align=center><b>&nbsp;'.$age[0].' '.$got_lang['Annee'].' '.$age[1].' m</b></font></td>';
			} else
			{	echo '<td class=bords_verti width=90 align=center><b>&nbsp;'.$row[5].'</b></font></td>';
			}
			echo '</tr>';
		}
		$ii++;
	}
	echo '</table>';
}

/********************************************** DEBUT DU SCRIPT ******************************************************/
$_REQUEST["ftop"] = 8;
$_REQUEST["scrolly"] = 0;

//	$titre_page = "GeneoTree v".$got_lang['Relea']." - ".$got_lang['MenLi'];
	require_once ("menu.php");

	$_REQUEST['ipag'] = "st";
	if (!isset($_REQUEST['spag'])) {$_REQUEST['spag'] = "lon";}
	if (!isset($_REQUEST['isex'])) {$_REQUEST['isex'] = "_";}
	if (!isset($_REQUEST['ideb'])) {$_REQUEST['ideb'] = "";}
	if (!isset($_REQUEST['sens'])) {$_REQUEST['sens'] = "";}
	if (!isset($_REQUEST['sosa'])) {$_REQUEST['sosa'] = "Tous";}

//	if (existe_sosa() and $_REQUEST['sosa'] == NULL) {}
	if ($_REQUEST['spag'] !== "lon" and $_REQUEST['spag'] !== "mar" and $_REQUEST['spag'] !== "par" and $_REQUEST['spag'] !== "fam")
	{	$_REQUEST['spag'] = "lon";
	}

	$menu = "";
	switch ($_REQUEST['spag'])
	{	case 'lon' : $menu = $menu.' '.$got_lang['StLon']; break;
		case 'mar' : $menu = $menu.' '.$got_lang['StMar']; break;
		case 'par' : $menu = $menu.' '.$got_lang['StPar']; break;
		case 'fam' : $menu = $menu.' '.$got_lang['StFam']; break;
	}
	
						//preparation des titres
	
	$debfin = recup_intervalle($_REQUEST['ibase'], $_REQUEST['ipag'], $_REQUEST['ideb'], $_REQUEST['intervalle'], $_REQUEST['sens']); // donne ideb et ifin en sortie
	$titre = $got_lang['PalNo'].' '.$menu;

	$genre = "";
	if ($_REQUEST['isex'] == "F")	{$genre = $got_lang['Femme'];}
	if ($_REQUEST['isex'] == "M") {$genre = $got_lang['Homme'];}
	if ($_REQUEST['sosa'] == "Sosa") {$sosa = $_REQUEST["sosa"];} else {$sosa = "";}

	if ($debfin[0] !== "") 	{	$periode = $got_lang['Entre'].' '.$debfin[0].' '.$got_lang['Et'].' '.$debfin[1];	}
	else {$periode = "";}
	
	$url=url_request();

	echo '<table><tr><td width=925px>';   // 1ere colonne sur 3
	
	echo "<table width=100%><tr>";
	echo "<td><a HREF = stat_pdf.php".$url." title='".$got_lang['IBPdf']."' target=_blank><img border=0 width=35 heigth=35 src=themes/icon-print.png></a></td>";
	echo "<td class=titre width=100%>".$titre."</td>";
	echo "</tr></table>";

	afficher_filtres($_REQUEST['ibase'], $_REQUEST['ipag'], $_REQUEST['spag'], $_REQUEST['isex'], $debfin[0], $_REQUEST['intervalle'], $_REQUEST['palma'], $url);
	
	echo '<br><table>';
	
				// affichage sous-titre gauche et droite
	switch($_REQUEST['spag'])
	{case "lon": echo '<tr><td class=titre>'.$_REQUEST['palma'].' '.$genre.' '.$sosa.' '.$got_lang['Vecus'].' '.$periode.'</td></tr>';break;
	 case "mar": echo '<tr><td class=titre>'.$_REQUEST['palma'].' '.$genre.' '.$sosa.' '.$got_lang['StMar'].' '.$got_lang['+jeun'].' '.$periode.'</td><td>&nbsp;</td><td class=titre>'.$_REQUEST['palma'].' '.$genre.' '.$sosa.' '.$got_lang['StMar'].' '.$got_lang['+ages'].' '.$periode.'</td></tr>';break;
	 case "par": echo '<tr><td class=titre>'.$_REQUEST['palma'].' '.$genre.' '.$sosa.' '.$got_lang['StPar'].' '.$got_lang['+jeun'].' '.$periode.'</td><td>&nbsp;</td><td class=titre>'.$_REQUEST['palma'].' '.$genre.' '.$sosa.' '.$got_lang['StPar'].' '.$got_lang['+ages'].' '.$periode.'</td></tr>';break;
	 case "fam": echo '<tr><td class=titre>'.$_REQUEST['palma'].' '.$genre.' '.$sosa.' '.$got_lang['NbEnf'].' '.$periode.'</td><td>&nbsp;</td><td class=titre>'.$_REQUEST['palma'].' '.$genre.' '.$sosa.' '.$got_lang['EcaFS'].' '.$periode.'</td></tr>';break;
	}
	echo '<tr><td colspan=3>&nbsp;</td></tr>';
	
	switch($_REQUEST['spag'])
	{case "mar":
		echo '<tr><td>';
		$result = recup_maries ($_REQUEST['ibase'], $debfin[0], $debfin[1], "asc", $_REQUEST['isex'], $_REQUEST['palma'], $_REQUEST['sosa']);
		afficher_stat($result, $url);
		echo '</td><td>&nbsp;&nbsp;</td><td>';
		$result = recup_maries ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'],$_REQUEST['palma'], $_REQUEST['sosa']);
		afficher_stat($result, $url);
		echo '</td></tr>';
	
		echo '<tr><td colspan=3>&nbsp;</td></tr>';
	
		echo '<tr><td class=titre>'.$_REQUEST['palma'].' '.$genre.' '.$sosa.' '.$got_lang['StMar'].' '.$got_lang['MoiLo'].' '.$periode.'</td>';
		echo '<td>&nbsp;&nbsp;</td>';
		echo '<td class=titre>'.$_REQUEST['palma'].' '.$genre.' '.$sosa.' '.$got_lang['StMar'].' '.$got_lang['PluLo'].' '.$periode.'</td></tr>';
	
		echo '<tr><td colspan=3>&nbsp;</td></tr>';
	
		echo '<tr><td>';
		$result = recup_noces($_REQUEST['ibase'], $debfin[0], $debfin[1], "asc", $_REQUEST['isex'],$_REQUEST['palma'], $_REQUEST['sosa']);
		afficher_stat($result, $url);
		echo '</td><td>&nbsp;&nbsp;</td><td>';
		$result = recup_noces($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'],$_REQUEST['palma'], $_REQUEST['sosa']);
		afficher_stat($result, $url);
		echo '</td></tr>';
	
		break;
	case "par":
		echo '<tr><td>';
		$result = recup_parents ($_REQUEST['ibase'], $debfin[0], $debfin[1], "asc", $_REQUEST['isex'], "age", $_REQUEST['palma'], $_REQUEST['sosa']);
		afficher_stat($result, $url);
		echo '</td><td>&nbsp;&nbsp;</td><td>';
		$result = recup_parents ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'], "age", $_REQUEST['palma'], $_REQUEST['sosa']);
		afficher_stat($result, $url);
		echo '</td></tr>';
		break;
	case "lon":
		echo '<tr><td>';
		$result = recup_deces ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'],$_REQUEST['palma'], $_REQUEST['sosa']);
		afficher_stat($result, $url);
		echo '</td></tr>';
		break;
	case "fam":
		echo '<tr><td>';
		$result = recup_parents ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'], "nb", $_REQUEST['palma'], $_REQUEST['sosa']);
		afficher_stat($result, $url, "nb");
		echo '</td><td>&nbsp;&nbsp;</td><td>';
		$result = recup_parents ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'], "ecart", $_REQUEST['palma'], $_REQUEST['sosa']);
		afficher_stat($result, $url, "age");
		echo '</td></tr>';
	
		echo '<tr><td colspan=3>&nbsp;</td></tr>';
	
		echo '<tr><td class=titre>'.$_REQUEST['palma'].' '.$genre.' '.$sosa.' '.$got_lang['Jumea'].' '.$periode.'</td>';
		echo '<td>&nbsp;&nbsp;</td>';
		echo '<td></td></tr>';
	
		echo '<tr><td colspan=3>&nbsp;</td></tr>';
	
		echo '<tr><td>';
		$result = recup_jumeaux($_REQUEST['ibase'], $debfin[0], $debfin[1], $_REQUEST['isex'], $_REQUEST['palma'], $_REQUEST['sosa']);
		afficher_stat($result, $url, "nb");
		echo '</td><td>&nbsp;&nbsp;</td>';
		echo '<td></td></tr>';
	
		break;
	}
	echo '</table>';
	
echo '</td><td width=1px></td>';  //2eme colonne vide
echo'<td width=355px>';   //3eme colonne fiche
require_once ("fiche.php");	
echo '</td></tr></table>'; // on ferme tout correctement

?>