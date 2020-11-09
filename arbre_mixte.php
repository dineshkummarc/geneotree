<?php
require_once ("_recup_ascendance.inc.php");
require_once ("_recup_descendance.inc.php");
require_once ("_boites.inc.php");
require_once ("_caracteres.inc.php");


/*************************************** DEBUT DU SCRIPT *********************************************/
//$titre_page = "GeneoTree v".$got_lang['Relea']." - ".$got_lang['ArMix'];

require_once ("menu.php");

echo '<table><tr><td width=925px>';   // 1ere colonne sur 3

$ancetres['id_indi'] [0] = $_REQUEST['id'];
$cpt_generations = 0;
recup_ascendance ($ancetres,0,2,'ME_G');
$cles = array_keys($ancetres);
$url = url_request();
//afficher_ascendance();

echo '<table><tr>';
echo '<td><a HREF=arbre_mixte_pdf.php'.$url.' title="'.$got_lang['IBPdf'].'" target=_blank><img border=0 width=35 heigth=35 src=themes/icon-print.png></a></td>';
echo "<td class=titre width=100%>".$got_lang['ArMix']." ".$ancetres['prenom1'][0]." ".$ancetres['nom'][0]."</td>";
echo '</tr></table>';

							// affichage ascendance
for ($ii = 0; $ii < count($ancetres["sosa_d"]); $ii++)
{	$sosa_d = $ancetres["sosa_d"][$ii];
	
	$coor_cellu[0] = recup_pts_mix ("pa",$sosa_d);
	
	if ($sosa_d != 1) {$coor_cellu[0] = $coor_cellu[0] * 3.1;} else {$coor_cellu[0]= 422;}

	if ($sosa_d < 2)	{$coor_cellu[1] = 480;}
	elseif ($sosa_d < 4) {$coor_cellu[1] = 300;}
	else {$coor_cellu[1] = 120;}

	if ($sosa_d != 1)	{	$coor_cellu[2] = 220;} else {$coor_cellu[2] = 125;}

	$coor_cellu[3] = 158;
	$idbulle = 'A1'.$sosa_d;
	
//	$i = array_search($ii,$ancetres['sosa_d']);   // ????
	for ($ik = 0; $ik < count ($cles); $ik++)  {	$tab_indiv[$cles[$ik]] = $ancetres[$cles[$ik]][$ii];	}
	afficher_cellule ($tab_indiv, $idbulle, $coor_cellu[0], $coor_cellu[1], "V2");

	if ($sosa_d !== 1)
	{	if ($sosa_d % 2 == 0)
		{	$coor_trait[0] = $coor_cellu[0] + $coor_cellu[2] / 2;
		} else
		{	if ($sosa_d > 3)
			{	$coor_trait[0] = $coor_cellu[0] - 43;
			} else
			{	$coor_trait[0] = $coor_cellu[0] - 147;
			}
		}
		$coor_trait[1] = $coor_cellu[1] + 170;
		if ($sosa_d > 3)
		{	$coor_trait[2] = 156;
		} else
		{	$coor_trait[2] = 260;
		}
		afficher_trait_horizontal($coor_trait);

		// traits verticaux entrants
		$coor_trait[0] = $coor_cellu[0] + $coor_cellu[2] / 2;
		$coor_trait[1] = $coor_cellu[1] + 163;
		$coor_trait[2] = 10;
		afficher_trait_vertical($coor_trait);
	} else
	{	$coor_trait[0] = $coor_cellu[0] + 60;
		$coor_trait[1] = $coor_cellu[1] - 10;
		$coor_trait[2] = 14;
		afficher_trait_vertical($coor_trait);
		$stoc_xxcentral = $coor_trait[0];
		$stoc_yycentral = $coor_trait[1];
	}
}

		// affichage des enfants
$descendants = array();
$descendants ['id_indi'] [0] = $_REQUEST['id'];
$cpt_generations = 0;
recup_descendance (0,0,1,'ME_G','');
$cles = array_keys($descendants);

$nb_enfants = count($descendants['id_indi']) - 1;	// on enlève le personnage central
if ($nb_enfants <= 11)	{$max_enfants = $nb_enfants;} else {$max_enfants = 11;}
for ($ii = 1; $ii <= $max_enfants; $ii++)
{	$coor_cellu[0] = recup_pts_mix("pe",$ii,$nb_enfants);
	$coor_cellu[0] = $coor_cellu[0] * 2.8;
	$coor_cellu[1] = 660;
	$coor_cellu[2] = 80;
	$coor_cellu[3] = 158;
	$idbulle = 'A3'.$descendants['id_indi'][$ii];

	for ($ik = 0; $ik < count ($cles); $ik++)  {	$tab_indiv[$cles[$ik]] = $descendants[$cles[$ik]][$ii];	}
	afficher_cellule ($tab_indiv, $idbulle, $coor_cellu[0], $coor_cellu[1], "V1");

	$coor_trait[0] = $coor_cellu[0] + $coor_cellu[2] / 2;
	if ($ii == 1)	{$stoc_xxdeb = $coor_trait[0];}
	$coor_trait[1] = $coor_cellu[1] - 10;
	$coor_trait[2] = 14;
	afficher_trait_vertical($coor_trait);
}

if ($nb_enfants !== 0)
{	$coor_trait[2] = $coor_trait[0] - $stoc_xxdeb;
	$coor_trait[0] = $stoc_xxdeb;
	afficher_trait_horizontal($coor_trait);

	$coor_trait[0] = $stoc_xxcentral;
	$coor_trait[1] = $stoc_yycentral + 170;
	$coor_trait[2] = 10;
	afficher_trait_vertical($coor_trait);
}

						// affichage des frères et soeurs
$i = 0;
while ($ancetres_fs['id_indi'][ $_REQUEST['id'] ][$i] == $_REQUEST['id'] and $ancetres_fs['id_indi'][ $_REQUEST['id'] ][$i] != '')
{	$i++;
}
$nb_freres = $i;

if ($nb_freres <= 10)	{$max_freres = $nb_freres;} else {$max_freres = 10;}

for ($ii = 0; $ii < $max_freres; $ii++)
{	$coor_cellu[0] = recup_pts_mix("pf",$ii,$max_freres);
	$coor_cellu[0] = $coor_cellu[0] * 3.25;
	$coor_cellu[1] = 480;
	$coor_cellu[2] = 90;
	$coor_cellu[3] = 158;
	$idbulle = 'A2'.$ancetres_fs['id_fs'][ $_REQUEST['id'] ][$ii];

	$query = 'SELECT nom,prenom1,profession,date_naiss,lieu_naiss,sosa_dyn,sexe,date_deces,lieu_deces
		FROM got_'.$_REQUEST['ibase'].'_individu
		WHERE id_indi = '.$ancetres_fs['id_fs'][ $_REQUEST['id'] ][$ii];
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);

	$cles = NULL;
	$cles["id_indi"] = $ancetres_fs['id_fs'][ $_REQUEST['id'] ][$ii];
	$cles["nom"] = $row[0];
	$cles["prenom1"] = $row[1];
	$cles["profession"] = $row[2];
	$cles["date_naiss"] = $row[3];
	$cles["lieu_naiss"] = $row[4];
	$cles["sosa_dyn"] = $row[5];
	$cles["sexe"] = $row[6];
	$cles["date_deces"] = $row[7];
	$cles["lieu_deces"] = $row[8];

	afficher_cellule ($cles, $idbulle, $coor_cellu[0], $coor_cellu[1], "V1");
}

echo '</td><td width=1px></td>';  //2eme colonne vide
echo'<td width=355px>';   //3eme colonne fiche
require_once ("fiche.php");
echo '</td></tr></table>'; // on ferme tout correctement
?>
