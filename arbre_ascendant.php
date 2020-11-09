<?php
require_once ("_recup_ascendance.inc.php");
require_once ("_caracteres.inc.php");
//require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	

function recup_hh($ii)
{	global $y;
	return $y[$ii] * 3.5 + 60;
}

function recup_ll($ii)
{	global $x;
	switch ($ii)
	{	case ($ii >= 16) : $x[$ii] = 751; break;
		case ($ii >= 8  and $ii < 16) : $x[$ii] = 510; break;
		case ($ii >= 4  and $ii < 8) : $x[$ii] = 269; break;
		case ($ii >= 2  and $ii < 4) : $x[$ii] = 28; break;
		default :  $x[$ii] = 14;
	}
	return $x[$ii];
}

/*************************************** DEBUT DU SCRIPT *********************************************/
//$titre_page = "GeneoTree v".$got_lang['Relea']." - ".$got_lang['ArAsc'];
require_once ("menu.php");

$url = url_request();
$ancetres[][] = '';$communs[][] = '';$cpt_generations = 0;
$ancetres['id_indi'][0] = $_REQUEST['id'];
recup_ascendance ($ancetres,0,4,'ME_G');
integrer_implexe(4,'ME_G');
// afficher_ascendance();
$cles = array_keys($ancetres);
if (!isset($_REQUEST["orient"]))	{$_REQUEST["orient"] = "P";}

echo '<table><tr><td width=925px>';   // 1ere colonne sur 3

echo "<table><tr>";
echo '<td><a href = arbre_ascendant_pdf.php'.$url.'&itype=arbre&continu&nbgen=8&implex&opti_reche&orient=L title="'.$got_lang['IBPdf'].'" target=_blank><img border=0 width=35 heigth=35 src=themes/icon-print.png></a></td>';
echo '<td><a href = arbre_ascendant_pdf.php'.$url.'&itype=liste&continu&nbgen=8&implex&opti_reche title="'.$got_lang["IBPdL"].'" target=_blank><img border=0 width=35 heigth=35 src=themes/liste.png></a></td>';
if ($flag_excel !== "No")
{	echo "<td><a HREF = arbre_ascendant_pdf.php".$url."&itype=excel&continu&nbgen=25 title='".$got_lang['IBExc']."'><img border=0 width=35 heigth=35 src=themes/icon-excel.png></a></td>";
}
echo "<td class=titre width=100%>".$got_lang['ArAsc']." ".$ancetres['prenom1'][0]." ".$ancetres['nom'][0]."</td>";
echo "</tr></table>";

echo '<table><tr>';

echo "<td><a href = fiche_pdf.php".$url."&ipag=AA title='".$got_lang['IBFic']."' target=_blank><img width=35 heigth=35 border=0 src=themes/icon-folder-grey.png></a></td>";
if (geo_pertinente($ancetres['dept_naiss']))  // s'il y a de dept_naiss significatif dans l'arbre, on affiche le globe. 
{	echo '<td><a href = affichage_carte.php'.$url.'&ipag=AA&carte= title="'.$got_lang["IBGeo"].'"><img width=35 heigth=35 border=0 src=themes/icon-maps-green.png></a></td>';
	echo '<td><a href = affichage_carte.php'.$url.'&ipag=AA&carte=&ifin=ge title="'.$got_lang["IBGeo"].'"><img width=35 heigth=35 border=0 src=themes/icon-maps-kml.png></a></td>';
} 
echo '<td>'.str_repeat ("&nbsp;",10).'</td>';

echo '<td class_menu_td>';
if (!isset($_REQUEST['iprof'])) {$_REQUEST['iprof'] = "Age";} // par défaut, affichage des professions. plus sympa je trouve

echo '<form method=post><b>'.$got_lang['DeuLi'].' : </b> ';
afficher_radio_bouton("iprof", array("Age", $got_tag['DEAT'], "Profession"), array("Age", "Deces", "Profession"), $_REQUEST['iprof'], "YES");
echo '</form>';
echo '</td>';
echo "</tr></table>";

		// on reprend les coordonnees absolues de l'arbre pdf ($x et $y dans _recup_ascendance)
recup_pts_asc($_REQUEST['forma'],$_REQUEST["orient"]); 

		// les fonctions recup_ll et recup_hh adapte les positions pdf aux positions html

		// on calcule les traits entre les boites pour dessiner un arbre
			// traits horizontaux sortants
for ($ii = 2; $ii <= 15; $ii++)
{	$coor_trait[0] = recup_ll($ii)*.95 + 215; // on ajoute la largeur de la cellule
	$coor_trait[1] = recup_hh($ii) + 27;
	$coor_trait[2] = 15;
	afficher_trait_horizontal($coor_trait);
}
			// traits horizontaux entrants
for ($ii = 2; $ii <= 31; $ii++)
{	$coor_trait[0] = recup_ll($ii)*.95 ;
	$coor_trait[1] = recup_hh($ii) + 27;
	$coor_trait[2] = 15;
	afficher_trait_horizontal($coor_trait);
}
			// traits verticaux
for ($ii = 2; $ii <= 31; $ii = $ii + 2)
//{	$coor_trait[0] = recup_ll($ii) + 8;
{	$coor_trait[0] = recup_ll($ii)*.95;
	$coor_trait[1] = recup_hh($ii) + 27;
	if ($ii == 2)	{$coor_trait[2] = 199;}
	if ($ii >= 4 and $ii <= 6)	{$coor_trait[2] = 237;}
	if ($ii >= 8 and $ii <= 14)	{$coor_trait[2] = 120;}
	if ($ii >= 16 and $ii <= 30){$coor_trait[2] = 60;}
	afficher_trait_vertical($coor_trait);
}
$coor_trait[0] = recup_ll(3)*.95 ;
$coor_trait[1] = recup_hh(3) - 190;
$coor_trait[2] = 218;
afficher_trait_vertical($coor_trait);

$i = 0;
while ($ancetres['id_indi'][$i] != NULL)
{
		// affichage des cellules
	$coor_cellu[0] = recup_ll($ancetres['sosa_d'][$i])*.95 + 15;
	$coor_cellu[1] = recup_hh($ancetres['sosa_d'][$i]);
	$coor_cellu[2] = 200;
	$coor_cellu[3] = 50;
	$idbulle = 'A'.$ancetres['id_indi'][$i];

					// affichage cellule principale
	for ($ii = 0; $ii < count ($cles); $ii++)  {	$tab_indiv[$cles[$ii]] = $ancetres[$cles[$ii]][$i];	}
	afficher_cellule ($tab_indiv, $idbulle, $coor_cellu[0], $coor_cellu[1], "H3");
	$yk = $coor_cellu[1] + 50;

					// affichage des  frères et soeurs 
	$sosa_f = 0;
	$temp = "";

	$sosa_d = $ancetres_fs ['sosa_d'][ $ancetres['id_indi'][$i] ][$sosa_f] - 1;
	echo '<div style="position: absolute; left: '.$coor_cellu[0].'px; top: '.$yk.'px; width: '.$coor_cellu[2].'px;">'; // grande case
	if ($sosa_d < 15)
	{	while ($ancetres_fs ['id_indi'][ $ancetres['id_indi'][$i] ][$sosa_f] != '')
		{	$temp = $temp.'<a href = arbre_ascendant.php'.$url.'&fid='.$ancetres_fs ['id_fs'][ $ancetres['id_indi'][$i] ][$sosa_f].' title="'.$got_lang['IBFih'].'"><color="'.recup_color_sexe($ancetres_fs ['sexe'][ $ancetres['id_indi'][$i] ][$sosa_f]).'">'.$ancetres_fs ['prenom1'][ $ancetres['id_indi'][$i] ][$sosa_f].',</a>';
			if (fmod ($sosa_f+1, 4) == 0) {$temp = $temp.'<br>';}
			$sosa_f = $sosa_f + 1;
		}
		if ($sosa_f > 0) 
		{	$temp = ''.$sosa_f.' '.$got_lang['F&S'].': '.$temp;
		} 
		echo $temp;
	}	

					// récupération du(es) conjoint(s) de l'individu central dans $conjoint 

	if ($ancetres['sosa_d'][$i] == 1)
	{	if ($ancetres ['sexe'][0] == 'M')
		{	$query = "SELECT c.id_indi,c.prenom1, c.nom, c.sexe
				FROM got_".$_REQUEST['ibase']."_individu a, got_".$_REQUEST['ibase']."_evenement b, got_".$_REQUEST['ibase']."_individu c 
				where (a.id_indi = b.id_husb) and (b.id_wife = c.id_indi) 
				and b.type_evene = 'MARR'
				and a.id_indi=".$_REQUEST['id'];
		}
		else 
		{	$query = "SELECT c.id_indi,c.prenom1, c.nom, c.sexe 
				FROM got_".$_REQUEST['ibase']."_individu a, got_".$_REQUEST['ibase']."_evenement b, got_".$_REQUEST['ibase']."_individu c 
				where (a.id_indi = b.id_wife) and (b.id_husb = c.id_indi) 
				and b.type_evene = 'MARR'
				and a.id_indi=".$_REQUEST['id'];
		}
		
		$result = sql_exec($query,0);
		while ($row = mysqli_fetch_row($result))
		{	
			echo '<br>&nbsp;&nbsp;&nbsp;x <a href = arbre_ascendant.php'.$url.'&id='.$row[0].' title="'.$got_lang['ArAsc'].'"><color="'.recup_color_sexe($row[3]).'"><b>'.$row[2].' '.$row[1].'</b></a>,';
		}

					// récupération des enfants de l'individu central dans $enfants 
		$query = "SELECT id_indi,prenom1,sexe,tri
			FROM got_".$_REQUEST['ibase']."_individu
			WHERE ";
		if ($ancetres['sexe'][0] == 'M') {$query = $query.' id_pere = ';} else {$query = $query.' id_mere = ';}
			$query = $query.$_REQUEST['id']."
			ORDER BY tri";
		$result = sql_exec($query);
		$row = mysqli_fetch_row($result);
		if ($row[0] != '')
		{	echo '<br>&nbsp;&nbsp;&nbsp;Enf: ';
		}
		$sosa_e = 1;
		while ($row[0] != '')
		{	echo '<a href = arbre_ascendant.php'.$url.'&id='.$row[0].' title="'.$got_lang['ArAsc'].'"><color="'.recup_color_sexe($row[2]).'">'.$row[1].'</a>,';
			if (fmod ($sosa_e, 4) == 0)
			{	echo '<br>&nbsp;&nbsp;';
			}
			$row = mysqli_fetch_row($result);
			$sosa_e = $sosa_e + 1;
		}
	}
	echo '</div>';

	$i++;
}
			// gestion des cases vides
					// on ne sait pas à l'avance où sont les cases vides. Nul en perf mais pour 32 boucles, c'est pas grave
for ($ii = 2; $ii < 32; $ii++)
{	if (array_search ($ii,$ancetres['sosa_d']) == NULL)
	{	$coor_cellu[1] = recup_hh($ii);
		$coor_cellu[0] = recup_ll($ii)*.95 + 15;
		$coor_cellu[2] = 200;
		$coor_cellu[3] = 50;
		echo '<div class="cell_indiv" style="position: absolute; left:'.$coor_cellu[0].'px; top:'.$coor_cellu[1].'px; width: '.$coor_cellu[2].'px; height: '.$coor_cellu[3].'px;">';
		echo '</div>';
	}
}
echo '</td><td width=1px></td>';  //2eme colonne vide
echo'<td width=355px>';   //3eme colonne fiche
require_once ("fiche.php");
echo '</td></tr></table>'; // on ferme tout correctement
?>
