<?php
require_once  ("_caracteres.inc.php");
require_once  ("_stat.inc.php");

function surligne ($text, $match)
{	$ii = 0;
	while (isset($match[$ii]))
	{	$text2 = strtolower(sans_accent($text));
		$match2 = strtolower(sans_accent($match[$ii]));
		$pos = @mb_strpos(' '.$text2,$match2);
		$lg = mb_strlen($match[$ii]);
		if ($pos >= 1)
		{	$pos--;
			$text = mb_substr($text,0,$pos).'<b>'.mb_substr($text,$pos,$lg).'</b>'.mb_substr($text,$pos+$lg);
		}
		$ii++;
	}
	return $text;
}

/******************************************************* DEBUT DU SCRIPT ************************************************************/

require_once ("menu.php");
$url = url_request();

if (!isset($_REQUEST['spag'])) {$_REQUEST['spag'] = "BIRT";}
if (!isset($_REQUEST['opag'])) {$_REQUEST['opag'] = "BIRT";}
if (!isset($_REQUEST['sosa'])) {$_REQUEST['sosa'] = "Tous";}
if (!isset($_REQUEST['ibase2'])) {$_REQUEST['ibase2'] = "";}
if (!isset($_REQUEST['ldeb'])) {$_REQUEST['ldeb'] = 0;}
if (!isset($_REQUEST['sdeb'])) {$_REQUEST['sdeb'] = "";}
if (!isset($_REQUEST['sfin'])) {$_REQUEST['sfin'] = "";}
if (!isset($_REQUEST['pere'])) {$_REQUEST['pere'] = "";}
if (!isset($_REQUEST['mere'])) {$_REQUEST['mere'] = "";}
if (!isset($_REQUEST['prenom'])) {$_REQUEST['prenom'] = "";}
if (!isset($_REQUEST['lieu'])) {$_REQUEST['lieu'] = "";}
if (!isset($_REQUEST['dept'])) {$_REQUEST['dept'] = "";}
if (!isset($_REQUEST['sosa'])) {$_REQUEST['sosa'] = "";}
if (!isset($_REQUEST['iautr'])) {$_REQUEST['iautr'] = "";}
if (!isset($_REQUEST['nom_wife'])) {$_REQUEST['nom_wife'] = "";}
if (!isset($_REQUEST['scont'])) {$_REQUEST['scont'] = "";}

if ($_REQUEST["spag"] != "Sourc")
{	$_REQUEST["ftop"] = 8;
	$_REQUEST["scrolly"] = 0;
}

if ( $_REQUEST['spag'] != $_REQUEST['opag'] ) // si on change de spag, on reinitialise tous les criteres
{	$_REQUEST['scont'] = '';
	$_REQUEST['sosa'] = '';$_REQUEST['ibase2'] = '';$_REQUEST['pere'] = '';$_REQUEST['mere'] = '';$_REQUEST['sdeb'] = '';$_REQUEST['sfin'] = '';$_REQUEST['dept'] = '';$_REQUEST['lieu'] = '';
	$_REQUEST['nom_wife'] = '';
	$_REQUEST['ldeb'] = 0; $_REQUEST['lfin'] = '';
	$_REQUEST['opag'] = $_REQUEST['spag'];
}

		// estimation du nombre de pages à afficher pour eviter de faire 2 requêtes couteuse en temps de réponse
$query = 'SELECT volume FROM g__base WHERE base ="'.$_REQUEST['ibase'].'"';
$result = sql_exec($query);
$row = mysqli_fetch_row($result);
//$lpas = round ($row[0] / 13,0) + 50;
$lpas = 100;
$liste_dept[] = '';

		// recuperation des données
if ($_REQUEST['spag'] == "Sourc" or $_REQUEST['spag'] == "NOTE")
{	$_REQUEST['scont'] = mb_ereg_replace('  ',' ',$_REQUEST['scont']);	// au cas où l'utilisateur saisi plusieurs espaces
	$match = explode (' ',$_REQUEST['scont']);
	$result = recup_source($_REQUEST['ibase'], $match, $_REQUEST['ldeb'], $lpas, $_REQUEST['spag']);
} else
{
	if ($_REQUEST['spag'] == "BIRT" or $_REQUEST['spag'] == "DEAT" or $_REQUEST['spag'] == "AUTR")
	{	$result_evene = recup_source_evene($_REQUEST['ibase'], $_REQUEST['ibase2'], $_REQUEST['sdeb'], $_REQUEST['sfin'], $_REQUEST['pere'], $_REQUEST['mere'], $_REQUEST['dept'], $_REQUEST['lieu'], $_REQUEST['sosa'], $_REQUEST['spag'], $_REQUEST['iautr'], $_REQUEST['ldeb'], $lpas);
		while ($row = @mysqli_fetch_row ($result_evene[0]) )
		{	if ($row[2] != ' ') {$liste_dept[] = $row[3];}
		}
	}
		if ($_REQUEST['spag'] == "MARR")
	{ $result_evene = recup_source_marr($_REQUEST['ibase'], $_REQUEST['ibase2'], $_REQUEST['nom_wife'], $_REQUEST['dept'], $_REQUEST['lieu'], $_REQUEST['sosa'], $_REQUEST['sdeb'], $_REQUEST['sfin'], $_REQUEST['ldeb'], $lpas);
		while ($row = @mysqli_fetch_row ($result_evene[0]) )
		{	if ($row[2] != ' ') {$liste_dept[] = $row[2];}
		}
	}
	@mysqli_data_seek ($result_evene[0],0);
}
$url = url_request(); // juste après les initialiations, avant les href

echo '<table><tr><td width=925px>';   // 1ere colonne sur 3
	
echo '<table><tr>';
if ($flag_excel !== "No") 
{	echo "<td><a HREF = source_pdf.php".$url."&itype=excel title='".$got_lang['IBExc']."' target=_blank><img border=0 width=35 heigth=35 src=themes/icon-excel.png></a></td>";
}

if ($_REQUEST['spag'] != "Sourc" AND geo_pertinente($liste_dept) )  // s'il y a de dept_naiss significatif dans l'arbre, on affiche le globe. 
{	echo '<td><a href = affichage_carte.php'.$url.'&ipag='.$_REQUEST['spag'].'&carte= title="'.$got_lang["IBGeo"].'"><img width=35 heigth=35 border=0 src=themes/icon-maps-green.png></a></td>';
	echo '<td><a href = affichage_carte.php'.$url.'&ipag='.$_REQUEST['spag'].'&carte=&ifin=ge title="'.$got_lang["IBGeo"].'"><img width=35 heigth=35 border=0 src=themes/icon-maps-kml.png></a></td>';
}

echo "<td class=titre width=100%>".$got_lang['LisSo']."</td>";
echo "</tr></table>";

$rech_lib = array ($got_tag['BIRT'],$got_tag['DEAT'],$got_tag['MARR'],$got_tag['NOTE'],$got_lang['Sourc'],$got_lang['Autre']);
$rech_cod = array ('BIRT','DEAT','MARR','NOTE','Sourc','AUTR');

echo '<table width=100%>';
echo '<tr><td align="center">';

echo "<form name=rech_typ method=post>";

afficher_radio_bouton("spag", $rech_lib, $rech_cod, $_REQUEST['spag']);

echo '<input class=invisible type=text name=opag value='.$_REQUEST['spag'].'>';   // on garde l ancienne valeur de spag

if ($_REQUEST['spag'] == "AUTR")
{	$query = 'SELECT distinct type_evene 
		FROM got_'.$_REQUEST['ibase'].'_evenement
		WHERE type_evene not in ("BIRT","DEAT","MARR","FILE")
		AND id_indi != ""
		';
	$list_typev[0] = $got_lang['Tous'];
	$result = sql_exec($query,0);
	while ($row = mysqli_fetch_row($result) )
	{	$list_typev[] = $got_tag[$row[0]];
	}
	afficher_liste_deroulante("iautr", $list_typev, $_REQUEST['iautr']);
	if ($_REQUEST['iautr'] == NULL)
	{	$_REQUEST['iautr'] = $list_typev[0];
	}
}
echo '</td></tr>';
echo '</table>';

if ($_REQUEST['spag'] == "Sourc" or $_REQUEST['spag'] == "NOTE")
{	echo '<table width=100%><tr><td align="center">';	// un tableau juste pour centrer

	echo '<table width=100%>';

		echo '<tr><td>&nbsp;</td></tr>';
		echo '<tr>';
		echo "<td width=775 align=center><input type=text name=scont value='".@str_replace("+"," ",$_REQUEST['scont'])."' size=100>";
		echo "<input type=submit value=".$got_lang['Reche']."></td>";
	
		echo '<td width=150 align=right>';
		echo '<b>Page '.$result[1].' / '.$result[2].'</b>';
		echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ldeb='.$result[3].'&lfin='.$lpas.'"><img src=themes/fleche_prec.png border=0></a>';
		echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ldeb=0&lfin="><img src=themes/reset.png border=0></a>';
		echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ldeb='.$result[4].'&lfin='.$lpas.'"><img src=themes/fleche_suiv.png border=0></a>';
		echo '</td>';

	echo '</tr></table>';
	
	echo '</td></tr></table>';

echo "</form>";

			// positionnement du focus sur le formulaire
	echo '<script language="JavaScript" type="text/javascript">
		document.rech.scont.focus();
		</script>';

	echo '<table>';
	
	$id_sour_old = "";
	while ($row = mysqli_fetch_row($result[0]))  // un enregistrement par couple note, evenement
	{	if ($row[0] !== $id_sour_old)					// rupture a la note, affichage de la note
		{	echo '<br><br><p class=cell_indiv>';
			echo surligne ($row[0],$match);
			echo '</p>';
		}
		echo $got_tag[$row[6]].' ';			// affichage de tous les individus
		afficher_lien_indiv ($row[1], $url, $row[7],$row[5], $row[2],$row[3],$row[4],$row[9]);

		$id_sour_old = $row[0];
	}
	
	if ($id_sour_old == NULL)
	{	echo $got_lang['PasSo'];
	}
}

if ($_REQUEST['spag'] == "BIRT" or $_REQUEST['spag'] == "DEAT" or $_REQUEST['spag'] == "AUTR")
{	
	echo '<br><table width=100%><tr><td align="center">';	// un tableau juste pour centrer le sous-tableau

		echo '<table>';
	
			echo '<tr>';
			if (existe_sosa())
			{	echo '<td align=right>';
				afficher_radio_bouton("sosa", array ($got_lang['Tous'],"Sosa"), array ("Tous","Sosa"), $_REQUEST['sosa']);
				echo '</td>';
			} else
			{	echo '<td></td>';
			}
			echo '<td></td>';
			echo '<td colspan=3 align=center><b>'.$got_lang['Years'].' '.$got_lang['Entre'].'</b><input type=text name=sdeb value="'.$_REQUEST['sdeb'].'" size=5> <b>'.$got_lang['Et'].'</b> <input type=text name=sfin value="'.$_REQUEST['sfin'].'" size=5></td>';
			echo '<td align=center><input type=submit value='.$got_lang['Reche'].'></td>';
			echo '<td align=right><b>Page '.$result_evene[1].' / '.$result_evene[2].'</b></td>';
			echo '</tr>';

			echo '<tr><td>&nbsp;</td></tr>';

			echo '<tr>';
			echo '<td class=titre colspan=7 width=925>';
			if ($_REQUEST['spag'] != 'AUTR') {echo $got_tag[$_REQUEST['spag']];} else {echo $_REQUEST['iautr'];}
			if ($_REQUEST['sosa'] == "Sosa") {echo ' '.$_REQUEST['sosa'];}
			if ($_REQUEST['ibase2'])				 {echo ' "'.$_REQUEST['ibase2'].'"';}
			if ($_REQUEST['pere']) 					 {echo ' "'.$_REQUEST['pere'].'"';}
			if ($_REQUEST['mere']) 					 {echo ' "'.$_REQUEST['mere'].'"';}
			if ($_REQUEST['sdeb']) 					 {echo ' "'.$_REQUEST['sdeb'].'"';}
			if ($_REQUEST['sfin']) 					 {echo ' "'.$_REQUEST['sfin'].'"';}
			if ($_REQUEST['dept']) 					 {echo ' "'.$_REQUEST['dept'].'"';}
			if ($_REQUEST['lieu']) 					 {echo ' "'.$_REQUEST['lieu'].'"';}
			echo '</td>';
			echo '</tr>';

			echo '<tr>';
			echo '<td width=215><input type=text name=ibase2 value="'.@str_replace("+"," ",$_REQUEST['ibase2']).'" size=26></td>';
//			echo '<td width=100><input type=text name=prenom value="'.@str_replace("+"," ",$_REQUEST['prenom']).'" size=8></td>';
			echo '<td width=100>&nbsp;</td>';
			echo '<td width=150><input type=text name=lieu value="'.@str_replace("+"," ",$_REQUEST['lieu']).'" size=14></td>';
			echo '<td width=20><input type=text name=dept value="'.@str_replace("+"," ",$_REQUEST['dept']).'" size=1></td>';
			echo '<td width=150><input type=text name=pere value="'.@str_replace("+"," ",$_REQUEST['pere']).'" size=14></td>';
			echo '<td width=150><input type=text name=mere value="'.@str_replace("+"," ",$_REQUEST['mere']).'" size=14></td>';
			echo '<td width=140 align=right>';
			echo 			'<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ldeb='.$result_evene[3].'&lfin='.$lpas.'"><img src=themes/fleche_prec.png border=0></a>';
			echo 			'<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ldeb=0&lfin="><img src=themes/reset.png border=0></a>';
			echo 			'<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ldeb='.$result_evene[4].'&lfin='.$lpas.'"><img src=themes/fleche_suiv.png border=0></a>';
			echo '</td>';
			echo '</tr>';

		echo '</table>';

	echo '</td></tr></table>';

	echo "</form>";

//	if ($_REQUEST['ibase'] == '$club' and $_REQUEST['ibase2'] == "" and $_REQUEST['prenom'] == "" and $_REQUEST['lieu'] == "") 
//	{	echo '<p class=titre>';
//		echo $got_lang['RenCh'];
//		echo '</p>';
//	} else
	{
		echo '<table class="bord_bas bord_haut">';
			// affichage des entetes de colonnes
		echo '<tr class=ligne_tr2>';
		echo '<td class=bords_verti align=center width=215px><b>'.$got_tag['NAME'].'</b></td>';
		echo '<td class=bords_verti align=center width=100px ><b>Date</b></td>';
		echo '<td class=bords_verti align=center width=150px><b>'.$got_lang['Lieux'].'</b></td>';
		echo '<td class=bords_verti align=center width=20px ><b>Dpt</b></td>';
		echo '<td class=bords_verti align=center width=150px><b>'.$got_lang['Pere'].'</b></td>';
		echo '<td class=bords_verti align=center width=150px><b>'.$got_lang['Mere'].'</b></td>';
		echo '<td class=bords_verti align=center width=140px><b>Note</b></td>';
		echo '</tr>';
	
		if (mysqli_num_rows($result_evene[0]) !== 0)
		{	$ii = 0;
			while ($row = mysqli_fetch_row($result_evene[0]))
			{	if ($ii % 2 == 0) {echo '<tr class=ligne_tr1>';} else {echo '<tr class=ligne_tr2>';}
				echo '<td class=bords_verti>';
				afficher_lien_indiv ($row[0], $url, $row[4], $row[5], $row[6], $row[7], $row[8], $row[9], NULL, $source = $row[20], $lg=21, "NO" );
				echo '</td>';
				echo '<td class=bords_verti align=right>'.mb_substr(affichage_date ($row[1],NULL,TRUE),0,14).'</td>';
				echo '<td class=bords_verti>'.mb_substr($row[2],0,21).'</td>';
				echo '<td class=bords_verti>'.mb_substr($row[3],0,2).'</td>';
				echo '<td class=bords_verti>'.mb_substr($row[10].' '.$row[11],0,15).'</td>';
				echo '<td class=bords_verti>'.mb_substr($row[14].' '.$row[15],0,15).'</td>';
				echo '<td class=bords_verti>'.mb_substr($row[19],0,15).'</td>';
				echo '</tr>';
				
				$ii++;
			}
		}
		echo '</table>';
	}
}

if ($_REQUEST['spag'] == "MARR")
{	
	echo '<br><table width=100%><tr><td align="center">';	// un tableau juste pour centrer le sous-tableau
	
	echo '<table>';

		echo '<tr>';
		if (existe_sosa())
		{	echo '<td align=right>';
			afficher_radio_bouton("sosa", array ($got_lang['Tous'],"Sosa"), array ("Tous","Sosa"), $_REQUEST['sosa']);
			echo '</td>';
		} else
		{	echo '<td></td>';
		}
		echo '<td colspan=2 align=right><b>'.$got_lang['Years'].' '.$got_lang['Entre'].' </b><input type=text name=sdeb value="'.$_REQUEST['sdeb'].'" size=5> <b>'.$got_lang['Et'].'</b> <input type=text name=sfin value="'.$_REQUEST['sfin'].'" size=5></td>';
		echo '<td colspan=2 align=right><input type=submit value='.$got_lang['Reche'].'></td>';
		echo '<td align=right><b>Page '.$result_evene[1].' / '.$result_evene[2].'</b></td>';
		echo '</tr>';

			echo '<tr><td>&nbsp;</td></tr>';

		echo '<tr><td colspan=6 class=titre>';
		echo $got_tag[$_REQUEST['spag']];
		if ($_REQUEST['sosa'])     {echo ' '.$got_lang['De'].' '.$_REQUEST['sosa'];}
		if ($_REQUEST['ibase2'])   {echo ' "'.$_REQUEST['ibase2'].'"';}
		if ($_REQUEST['nom_wife']) {echo ' "'.$_REQUEST['nom_wife'].'"';}
		if ($_REQUEST['sdeb'])     {echo ' "'.$_REQUEST['sdeb'].'"';}
		if ($_REQUEST['sfin'])     {echo ' "'.$_REQUEST['sfin'].'"';}
		if ($_REQUEST['lieu'])     {echo ' "'.$_REQUEST['lieu'].'"';}
		if ($_REQUEST['dept'])     {echo ' "'.$_REQUEST['dept'].'"';}
		echo '</td></tr>';

		echo '<tr>';
		echo '<td width=230><input type=text name=ibase2 value="'.@str_replace("+"," ",$_REQUEST['ibase2']).'" size=25></td>';
		echo '<td width=230><input type=text name=nom_wife value="'.@str_replace("+"," ",$_REQUEST['nom_wife']).'" size=25></td>';
		echo '<td width=100>&nbsp;</td>';
		echo '<td width=165><input type=text name=lieu value="'.@str_replace("+"," ",$_REQUEST['lieu']).'" size=20></td>';
		echo '<td width=20><input type=text name=dept value="'.@str_replace("+"," ",$_REQUEST['dept']).'" size=1></td>';
		echo '<td width=180 align=right>';
		echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ldeb='.$result_evene[3].'&lfin='.$lpas.'"><img src=themes/fleche_prec.png border=0></a>';
		echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ldeb=0&lfin="><img src=themes/reset.png border=0></a>';
		echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ldeb='.$result_evene[4].'&lfin='.$lpas.'"><img src=themes/fleche_suiv.png border=0></a>';
		echo '<td>';

		echo '</td></tr>';

	echo '</table>';

	echo '</td></tr></table>';

	echo "</form>";

//	if ($_REQUEST['ibase'] == '$club' and $_REQUEST['ibase2'] == "" and $_REQUEST['nom_wife'] == "" and $_REQUEST['lieu'] == "") 
//	{	echo '<p class=titre>';
//		echo $got_lang['RenCh'];
//		echo '</p>';
//	} else
	{
		echo '<table class="bord_haut bord_bas">';
	
		echo '<tr class=ligne_tr2>';
		echo '<td class=bords_verti align="center" width=230><b>'.$got_tag['HUSB'].'</b></td>';
		echo '<td class=bords_verti align="center" width=230><b>'.$got_tag['WIFE'].'</b></td>';
		echo '<td class=bords_verti align="center" width=100><b>Date</b></td>';
		echo '<td class=bords_verti align="center" width=165><b>'.$got_lang['Lieux'].'</b></td>';
		echo '<td class=bords_verti align="center" width=20><b>Dpt</b></td>';
		echo '<td class=bords_verti align="center" width=180><b>Note</b></td>';
		echo '</tr>';
	
		if (mysqli_num_rows($result_evene[0]) !== 0)
		{	$ii = 0;
			while ($row = mysqli_fetch_row($result_evene[0]))
			{	if ($ii % 2 == 0) {echo '<tr class=ligne_tr1>';} else {echo '<tr class=ligne_tr2>';}
				echo '<td class=bords_verti>';
				afficher_lien_indiv ($row[3], $url, $row[4], $row[5], $row[6], $row[7], $row[8], $row[9], NULL, $source = $row[17], $lg=21,"NO");
				echo '</td>';
				echo '<td class=bords_verti>';
				afficher_lien_indiv ($row[10], $url, $row[11], $row[12], $row[13], $row[14], $row[15], $row[16], NULL, $source = $row[17], $lg=21,"NO");
				echo '</td>';
				echo '<td class=bords_verti align=right>'.mb_substr(affichage_date ($row[0],NULL,TRUE),0,15).'</td>';
				echo '<td class=bords_verti>'.mb_substr($row[1],0,20).'</td>';
				echo '<td class=bords_verti>'.mb_substr($row[2],0,2).'</td>';
				echo '<td class=bords_verti>'.mb_substr($row[20],0,22).'</td>';
				echo '</tr>';
				
				$ii++;
			}
		}
		echo '</table>';
	}
}

echo '</td><td width=1px></td>';  //2eme colonne vide
echo'<td width=355px>';   //3eme colonne fiche
require_once ("fiche.php");
echo '</td></tr></table>'; // on ferme tout correctement

?>