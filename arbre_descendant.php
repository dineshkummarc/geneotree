<?php
error_reporting(E_ALL & ~E_NOTICE);	// compliqué dans l'algo de dessin des traits
require_once ("_recup_descendance.inc.php");
require_once ("_boites.inc.php");
require_once ("_caracteres.inc.php");
//require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	


/*************************************** RECUPERATION DES DONNEES *********************************************/

//$titre_page = "GeneoTree v".$got_lang['Relea']." - ".$got_lang['ArDes'];
require_once ("menu.php");

		// alimentation de nb_generation_desc indispensable pour l'appel de recup_descendance
if (!isset($_REQUEST['nb_gen_desc'])) {$_REQUEST['nb_gen_desc'] = 5;}

$nb_generations_desc = $_REQUEST['nb_gen_desc'];
$descendants = array();
$descendants ['id_indi'] [0] = $_REQUEST['id'];
$cpt_generations = 0;
recup_descendance (0,0,0,'ME_G','MARR');
array_multisort (
$descendants['indice']
,$descendants['id_indi']
,$descendants['niveau']
,$descendants['nom']
,$descendants['prenom1']
,$descendants['prenom2']
,$descendants['prenom3']
,$descendants['sexe']
,$descendants['profession']
,$descendants['date_naiss']
,$descendants['lieu_naiss']
,$descendants['dept_naiss']
,$descendants['date_deces']
,$descendants['lieu_deces']
,$descendants['dept_deces']
,$descendants['id_parent']
,$descendants['sosa_dyn']
,$descendants['id_conj']   
,$descendants['date_maria']
,$descendants['lieu_maria']
,$descendants['dept_maria']
,$descendants['nom_conj']  
,$descendants['pre1_conj'] 
,$descendants['pre2_conj'] 
,$descendants['pre3_conj'] 
,$descendants['sexe_conj'] 
,$descendants['sosa_conj'] 
);	
$cles = array_keys($descendants);
//afficher_descendance();

$nb_descendants = count ($descendants['id_indi']);
			// préparation des traits verticaux intermédiaire

			// pour 1 caractère de l'indice, trouver le dernier 2 caractères dépendants  A -> AF
$iniv=1;
$niv_old = "";
for ($ii = 0; $ii < $nb_descendants; $ii++)
{	if (mb_strlen($descendants['indice'][$ii]) == 1 and $descendants['indice'][$ii] != $niv_old)
	{	$niv1["pere"][$iniv] = mb_substr($descendants['indice'][$ii],0,1); // on stocke le père sans le dernier fils
	}
	if (mb_strlen($descendants['indice'][$ii]) == 2) {$nivplus1_old = $descendants['indice'][$ii];}
}
//if (mb_substr($niv_old,0,1) == $niv2["pere"][$iniv]) {$niv1["fils"][$iniv] = $nivplus1_old;$nivplus1_old="";} // on stocke le dernier fils
if (mb_substr($niv_old,0,1) == $niv2["pere"][$iniv]) {$niv1["fils"][$iniv] = $nivplus1_old;$nivplus1_old="";} // on stocke le dernier fils correction niv2 en niv1
//print_r($niv1);



			// preparation generation des intervalles de traits verticaux pour dessiner l'arbre (algo HYPER COMPLEXE)

for ($igen = 1; $igen < $_REQUEST["nb_gen_desc"]; $igen++)
{	$iniv=1;	// 2eme indice du tableau : l'occurence de l'intervalle concerné par la génération
	for ($ii = 0; $ii < $nb_descendants; $ii++)
	{	if (mb_strlen($descendants['indice'][$ii]) == $igen and $descendants['indice'][$ii] != $niv_old)
		{	$nivgen["pere"][$igen][$iniv] = mb_substr($descendants['indice'][$ii],0,$igen); // on stocke le père sans le dernier fils
			$inivmoins1 = $iniv - 1;
			if (mb_substr($niv_old,0,$igen) == $nivgen["pere"][$igen][$inivmoins1]) {$nivgen["fils"][$igen][$inivmoins1] = $nivplus1_old;$nivplus1_old="";} // on stocke le dernier fils
	
			$niv_old = $descendants['indice'][$ii];
			$iniv++;
		}
		if (mb_strlen($descendants['indice'][$ii]) == $igen + 1) {$nivplus1_old = $descendants['indice'][$ii];}
	}
	$iniv--;
	if (mb_substr($niv_old,0,$igen) == $nivgen["pere"][$igen][$iniv]) {$nivgen["fils"][$igen][$iniv] = $nivplus1_old;$nivplus1_old="";} // on stocke le dernier fils
}

/************************************* AFFICHAGE ENTETE ****************************************/

echo '<table><tr><td width=925px>';   // 1ere colonne sur 3

$url = url_request();

echo '<table><tr>';
echo "<td><a HREF = arbre_descendant_pdf.php".$url."&ipag=AD&itype title='".$got_lang['IBPdf']."' target=_blank><img border=0 width=35 heigth=35 src=themes/icon-print.png></a></td>";
if ($flag_excel !== "No")
{	echo "<td><a HREF = arbre_descendant_pdf.php".$url."&ipag=AD&itype=excel title='".$got_lang['IBExc']."'><img border=0 width=35 heigth=35 src=themes/icon-excel.png></a></td>";
}
echo "<td class=titre width=100%>".$got_lang['ArDes']." ".$descendants['prenom1'][0]." ".$descendants['nom'][0]."</td>";
echo '</tr></table>';

echo "<table><tr>";
echo "<td><a href = fiche_pdf.php".$url."&ipag=AD title='".$got_lang['IBFic']."' target=_blank><img width=35 heigth=35 border=0 src=themes/icon-folder-grey.png></a></td>";

if (geo_pertinente($descendants['dept_naiss']))
{	echo '<td><a href = affichage_carte.php'.$url.'&ipag=AD&carte= title="'.$got_lang["IBGeo"].'"><img width=35 heigth=35 border=0 src=themes/icon-maps-green.png></a></td>';
	echo '<td><a href = affichage_carte.php'.$url.'&ipag=AD&carte=&ifin=ge title="'.$got_lang["IBGeo"].'"><img width=35 heigth=35 border=0 src=themes/icon-maps-kml.png></a></td>';
} 

$nb_gen_desc = array (1,2,3,4,5,6);
echo '<form method=post><td><b>';
echo str_repeat ("&nbsp;",20);
echo $got_lang['NbGen'].' : </b>';
afficher_radio_bouton("nb_gen_desc",$nb_gen_desc,$nb_gen_desc,$_REQUEST['nb_gen_desc'],"YES");
echo '</form>';
echo "</td></tr></table>";

/************************************* AFFICHAGE ARBRE ****************************************/

echo '<table>';

for ($ii = 0; $ii < $nb_descendants; $ii++)
{	if ($descendants['niveau'][$ii] > $nb_col )
	{	$nb_col = $descendants['niveau'][$ii];
	}
}
$nb_col++;
$tota_desce = 0;
$xx = 50;
$yy = 160;
$niv_old = 0;
$decal = 110;   //decalage des cellules en pixel
 
for ($ii = 0; $ii < $nb_descendants; $ii++)
{	
	if ($descendants['niveau'][$ii] !== $niv_old)
	{	if ($descendants['niveau'][$ii] > $niv_old)
		{	$xx = $xx + $decal;
		} else
		{	$xx = $xx - $decal * ($niv_old - $descendants['niveau'][$ii] );
		}
	}
	$yy = $yy + 31;
	$xbulle = $xx + 220;   // ce parametre doit être identique a la largeur prévue dans la fonction afficher_cellule

	$idbulle = 'A'.$descendants['id_indi'][$ii];
	for ($ik = 0; $ik < count ($cles); $ik++)  {	$tab_indiv[$cles[$ik]] = $descendants[$cles[$ik]][$ii];	}
	afficher_cellule ($tab_indiv, $idbulle, $xx, $yy, "H2");

				// dessin des traits horizontaux sortants gauche (facile)
	if ($ii != 0)
	{	$coor_trait[0] = $xx - $decal/2; 
		$coor_trait[1] = $yy + 15;
		$coor_trait[2] = $decal/2;
		afficher_trait_horizontal($coor_trait);
	}

				// dessin des traits verticaux dernière génération (assez facile)
	if (mb_strlen($descendants['indice'][$ii]) == $_REQUEST["nb_gen_desc"] + 1 )
	{	$coor_trait[0] = $xx - $decal/2;
		$coor_trait[1] = $yy - 15;
		$coor_trait[2] = 30;
		afficher_trait_vertical($coor_trait);
	}
				// dessin des traits verticaux dernière génération (TRES difficile)
	for ($igen = 1; $igen < $_REQUEST["nb_gen_desc"]; $igen++)
	{	for ($iniv = 0; $iniv <= count($nivgen["pere"][$igen]); $iniv++)
		{	if ($descendants['indice'][$ii] > $nivgen["pere"][$igen][$iniv] and $descendants['indice'][$ii] <= $nivgen["fils"][$igen][$iniv])
			{	$coor_trait[0] = -5 + ($decal * $igen);
				$coor_trait[1] = $yy - 15;
				$coor_trait[2] = 31;
				afficher_trait_vertical($coor_trait);//echo '<br>'.$descendants['indice'][$ii].'/'.$xx.'/'.$yy;
			}
		}
	}

			// affichage des conjoints
//	if (isset($descendants['id_conj'][$ii]))
//	{
//		echo ', '.$got_lang['Marie'];
//		if ($got_lang['Langu'] == 'fr' and $descendants['sexe'][$ii] == 'F') {echo 'e';}
//		if ($descendants['date_maria'][$ii] != "")
//		{	echo " <b> ".affichage_date($descendants['date_maria'][$ii]);
//			echo "</b>";
//		}
//		if ($descendants['lieu_maria'][$ii] != "")
//		{	echo ' '.$got_lang['Situa'].' '.$descendants['lieu_maria'][$ii]." ";
//			if ($descendants['dept_maria'][$ii] != "")
//			{	echo '('.$descendants['dept_maria'][$ii].')';
//			}
//		}
//		if ($descendants['nom_conj'][$ii] != '' or $descendants['pre1_conj'][$ii] != '')
//		{	echo $got_lang['Avec'];
//			afficher_lien_indiv ($descendants['id_conj'][$ii], $url, $descendants['sosa_conj'][$ii], $descendants['nom_conj'][$ii],$descendants['pre1_conj'][$ii],$descendants['pre2_conj'][$ii],$descendants['pre3_conj'][$ii],$descendants['sexe_conj'][$ii]);
//		}
//		$tota_desce++;
//	}
	
//	afficher_bulle ($idbulle, $descendants['id_indi'][$ii], 220, -15);
//	echo "</div>";  // fermeture de la ligne en boucle
	
	
	$niv_old = $descendants['niveau'][$ii];
}
$ii--;
$total = $ii + $tota_desce;

//echo "<tr><td>&nbsp;</td></tr>";   // une ligne blanche pour separer l'affichage des totaux
//
//if ($ii % 2 == 0) 
//{	echo '<tr class="ligne_tr1">';
//} else 
//{	echo '<tr class="ligne_tr2">';
//}
//echo '<td></td><td colspan='.$nb_col.'>'.$ii.' '.$got_tag['DESC'].' directs</td>';
//echo '</tr>';

//echo '<td></td><td colspan='.$nb_col.'>'.$total.' '.$got_tag['DESC'].' '.$got_lang['Avec'].' '.$got_lang['NomCo'].'</td>';
echo '</tr>';  // fin affichage principal

echo '</table>';  // fin affichage principal

echo '</td><td width=1px></td>';  //2eme colonne vide
echo'<td width=355px>';   //3eme colonne fiche
require_once ("fiche.php");
echo '</td></tr></table>'; // on ferme tout correctement

$query = 'DROP TABLE got_'.$ADDRC.'_desc_cles';
sql_exec($query,2);
?>
</BODY>
</HTML>


