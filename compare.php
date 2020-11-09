<?php
require_once ("_recup_ascendance.inc.php");
require_once ("_sql.inc.php");
if (!isset($_REQUEST['lang'])) {	$_REQUEST['lang'] = "en";}
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
require_once ("_boites.inc.php");

function soundex_fr ($sound)
{	$sound = trim($sound);
	$sound = strtoupper($sound);
	$premiere_lettre = mb_substr($sound,0,1);
	$sound = rtrim(mb_substr($sound,1,100));
	
	$voyelles = array ('A','E','I','O','U','Y','H','W');
	$sound = mb_ereg_replace($voyelles, ' ', $sound); // suppression des voyelles et des lettres peu significatives
	$sound = trim($sound);
	
	$position_fin = mb_strlen($sound);
	$sound = mb_substr($sound,0,$position_fin - 1);			//suppression du dernier caractère (copyright Damien !)
	
	$avant = array('B','P','C','K','Q','D','T','L','M','N','R','G','J','X','Z','S','F','V');
	$apres = array('1','1','2','2','2','3','3','4','5','5','6','7','7','8','8','8','9','9');
	$sound = mb_ereg_replace($avant,$apres,$sound); // attribution des numeros soundex francais
	$sound = trim($sound);
	
	$i = 1;
	$old_chiffre = mb_substr($sound,0,1);
	while (mb_substr($sound,$i,1) != '')
	{	if (mb_substr($sound,$i,1) == $old_chiffre or mb_substr($sound,$i,1) == ' ')
		{	$sound = mb_substr($sound,0,$i).mb_substr($sound, $i+1, mb_strlen($sound) );
		}
		else
		{	$old_chiffre = mb_substr($sound,$i,1);
			$i = $i + 1;
		}
	}
	if (mb_strlen($sound) > 3) {$sound = mb_substr($sound,0,3);}
	if (mb_strlen($sound) == 2) {$sound = $sound.' ';}
	if (mb_strlen($sound) == 1) {$sound = $sound.'  ';}
	
	$sound = $premiere_lettre.$sound;
	return $sound;
	
/*	
anglais
    1       B, F, P, V
    2       C, G, J, K, Q, S, X, Z
    3       D, T
    4       L
    5       M, N    
    6       R

pour le français: 
    1       B, P
    2       C, K, Q
    3       D, T
    4       L
    5       M, N
    6       R
    7       G, J
    8       X, Z, S
    9       F, V

Voici un résumé des différentes étapes de l'algorithme:

* supprimer les éventuels 'espace' initiaux
* mettre le mot en majuscule
* garder la première lettre
* supprimer les lettres A, E, I, O, U, Y, H et W
* remplacer les lettres restantes par le chiffre associé dans la table
* supprimer les chiffres répétés (garder une occurence)
* si le code obtenu contient moins de 4 éléments, compléter à droite par des espaces
  si le code obtenu contient plus de 4 éléments, conserver les 4 éléments les plus à gauche
  
B626 est le code pour BeauReGaRd, BeRGeR, BeRGeRon, BouRCieR et BRaSsaRd 
J630 est le code pour JaRreT 
*/ 	
}

function sosa_souche($sosa_d)
{	global $souches;
	$result = "KO";
	$z = 0;
	while ($souches ['sosa1_d'][$z] != '' and $result == "KO")
	{	$sosa_w = $sosa_d;
		while ($sosa_w > $souches ['sosa1_d'][$z])
		{	$sosa_w = floor ($sosa_w / 2);
		}
		if ($sosa_w == $souches ['sosa1_d'][$z]) {$result = "OK";}
		$z = $z + 1;
	}
	return $result;
}

function soundex_got($text,$fcont)
{	if ($fcont == 'Francais')
	{	$text = soundex_fr($text);
	} else
	{	$text = soundex($text);
	}
	return $text;
}

/*************************************** DEBUT DU SCRIPT *********************************************/

//$titre_page = "GeneoTree v".$got_lang['Relea']." - ".$got_lang['EtCom'];
require_once ("menu.php");
//if (!isset($_REQUEST["theme"])) {$_REQUEST["theme"] = "wikipedia";}
echo '<table><tr><td width=925>';   // cadre principal

$lib_langu = Array ('English','Francais');
if ($_REQUEST['ibase'] and !$_REQUEST['ibase2'])
{	

				// affichage message pour expliquer qu'on cherche les cousins du personnage principal DUPONT
	$query = 'SELECT concat(prenom1," ",prenom2," ",prenom3," ",nom) FROM got_'.$_REQUEST['ibase'].'_individu
			WHERE id_indi='.$_REQUEST['id'];
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);
	echo "<p class=titre>".$got_lang['Supe1']." ".$row[0]." ".$got_lang['Supe2']."</p>";
	echo "<br><p class=titre>".$got_lang['ChoB2']."</p>";

			// affichage des bases à cousiner
	$query = 'SELECT base,sosa_principal,commentaire FROM g__base WHERE base != "'.$_REQUEST['ibase'].'" ORDER BY base';
	$result = sql_exec($query);

	echo '<table class="bord_haut bord_bas">';
	echo '<tr class=ligne_tr2>';
	echo '<td align=center class=bords_verti><b>'.$got_lang['Bases'].'</b></td>';
	echo '<td align=center class=bords_verti><b>'.$got_lang['PriCo'].'</b></td>';
	echo '</tr>';

	$anc_base = $_REQUEST['ibase'];
	$ii = 0;
	while ($row = mysqli_fetch_row($result) )
	{	$row_id = recup_identite($row[1],$row[0]);
		if ($ii % 2 == 0)	{echo '<tr class=ligne_tr1>';} else {echo '<tr class=ligne_tr2>';} 
		echo '<td class=bords_verti><a href=compare.php?ibase='.$anc_base.'&ibase2='.$row[0].'&id='.$_REQUEST['id'].' title="'.$got_lang['IBCom'].'"><b>'.$row_id[1].' '.$row_id[0].'</b> (Base '.$row[0].')</td>';
		echo '<td class=bords_verti>'.mb_substr($row[2],0,100).'</font></td>';
		echo '</tr>';
		$ii++;
	}
	echo '</table>';

} elseif ($_REQUEST['ibase'] and $_REQUEST['ibase2'] and !$_REQUEST['fcont']) 
{
		echo "<br><br><br><br>";
		echo '<p class=titre style="width:600px;">'.$got_lang["LanSo"].'</p>';
		echo '<br><form method=post><p align=center style="width:600px;">';
		afficher_radio_bouton ("fcont",$lib_langu,$lib_langu,"English",YES);  // par definition, une boite de confirmation n'est pas submittée
		echo '</form></p>	';
	
} elseif ($_REQUEST['ibase'] and $_REQUEST['ibase2'] and $_REQUEST['fcont'] )
{
//	$isosa = recup_sosa_principal ($_REQUEST['ibase']);
	$isosa = $_REQUEST['id'];
	$isosa2 = recup_sosa_principal ($_REQUEST['ibase2']);

	$time = time();

	$ancetres['id_indi'] [0] = $isosa;
	recup_ascendance ($ancetres,0,40,'ME_P');
	$ancetres1 = $ancetres;								// $ancetres1 contient les ascendants de la 1ère base

	$ancetres = '';
	$ancetres['id_indi'] [0] = $isosa2;
	$ibase_old	= $_REQUEST['ibase'];
	$_REQUEST['ibase'] = $_REQUEST['ibase2'];
	recup_ascendance ($ancetres,0,40,'ME_P');
	$_REQUEST['ibase'] = $ibase_old;							// $ancetres contient les ascendants de la 2ème base

			/************* pré-calcul des soundex pour raison de performance 1 lecture par individu ****/

	$i = 1;
	while ($ancetres1 ['nom'][$i] != '')
	{	$ancetres1 ['sound'][$i] = soundex_got($ancetres1 ['nom'][$i],$_REQUEST['fcont']);
		$i++;
	}
	$i = 1;
	while ($ancetres ['nom'][$i] != '')
	{	$ancetres ['sound'][$i] = soundex_got($ancetres ['nom'][$i],$_REQUEST['fcont']);
		$i++;
	}

			/************************ Algorithme principal *********************************************
			
			Principes de bases : 
			- On stocke les 2 ascendances dynamiques dans $ancetres1 (base 1) et $ancetres (base 2)
			- On parcours les 2 tableaux en produit cartésien.
				 Une ascendance dépassant rarement les 1000 individus distincts, on fait au maximum 1 million de boucles.
			- Le 1er test effectué est celui des soundexs sur les noms, du sexe et des dates incompatibles,
				 cela élimine 99,9% des combinaisons avec 1 seul if.
			- La souche est declaree "commune" si le soundex des conjoints ou de la mere est identique.
			- La souche est declaree "a verifier" si on a aucune infos sur les conjoints et meres,
				mais les noms et prénoms sont identiques ET les dates sont inconnues
				ou bien les noms et prenoms sont approchants ET les dates sont connues et approchantes

			Performances : 
			- le calcul des 2 ascendances dynamiques prend à lui seul entre 50% et 80% du temps total.
			- on pourrait optimiser un peu le produit cartésien en triant les tableaux, gain prévisible 10% environ
			**********************************************************************************************/

	$i = 1;$cpt_semblables= 0; $cpt_souches = 0;
	while ($ancetres1['id_indi'][$i] != '') 
	{	if (sosa_souche ($ancetres1 ['sosa_d'][$i]) == "KO") // si l'individu est ancetre d'une souche deja trouvee, on passe
		{	$j = 1;
			while ($ancetres['id_indi'][$j] != '')
			{	$flag_prenom_semblable = "KO";		// les 2 prenoms principaux sont proches
				$flag_prenom2_semblable = "KO";  // le 2eme prenom est proche du 1er prenom de l'autre base
				$flag_naiss_semblable = "KO";  // les dates de naissance sont proches
				$flag_semblable = "KO";		// flag synthese des 3 precedents -> rapprochement fait sans confirmation. A verifier par l'utilisateur.
				$flag_souche = "KO";		// flag rapprochement effectué à 100%
				if ($ancetres1 ['sound'][$i] == $ancetres ['sound'][$j] // soundex identiques
					and $ancetres1 ['sexe'][$i] == $ancetres ['sexe'][$j] // sexes identiques
					and !($ancetres1 ['tri'][$i] != '' and $ancetres ['tri'][$j] != '' and ($ancetres1 ['tri'][$i] < $ancetres ['tri'][$j]-10 or $ancetres1 ['tri'][$i] > $ancetres ['tri'][$j]+10 ) ) // dates compatibles
					) 
				{	if ($ancetres1 ['prenom1'][$i] == $ancetres ['prenom1'][$j] and $ancetres1 ['prenom1'][$i] != '')
					{	$flag_prenom_semblable = "OK";
					}
					if ( ($ancetres1 ['prenom1'][$i] == $ancetres ['prenom2'][$j] and $ancetres1 ['prenom1'][$i] != ''and $ancetres1 ['prenom2'][$i] != '')
						or ($ancetres1 ['prenom2'][$i] == $ancetres ['prenom1'][$j] and $ancetres ['prenom1'][$j] != '' and $ancetres ['prenom2'][$j] != '') )
					{	$flag_prenom2_semblable = "OK";
					}
					if ( $ancetres1 ['tri'][$i] != '' and $ancetres ['tri'][$j] != '' and $ancetres1 ['tri'][$i] >= $ancetres ['tri'][$j]-10 and $ancetres1 ['tri'][$i] <= $ancetres ['tri'][$j]+10)
					{	$flag_naiss_semblable = "OK";
					}
						/* si les dates de naissance sont lisibles, on teste les noms & prenoms assez souples */
					if (($flag_prenom_semblable == "OK" or $flag_prenom2_semblable == "OK") and $flag_naiss_semblable == "OK" )
					{	$flag_semblable = "OK";
					}
						/* si les dates de naissance sont mal renseignées, on teste les noms & prenoms assez précis (identité nom et 1er prénom) sinon on sort toute la base */
					if ( ($ancetres1 ['tri'][$i] == '' or $ancetres ['tri'][$j] == '') and $ancetres1 ['nom'][$i] == $ancetres ['nom'][$j] and $ancetres1 ['prenom1'][$i] == $ancetres ['prenom1'][$j] )
					{	$flag_semblable = "OK";
					}
					if ($flag_semblable == "OK")
					{		/* on teste le soundex des conjoints*/
						if ($ancetres1 ['sexe'][$i] == 'M') {$sosa1_conj = $ancetres1['sosa_d'][$i] + 1;} else {$sosa1_conj = $ancetres1['sosa_d'][$i] - 1;}
						$z = 0;
						while ($ancetres1['sosa_d'][$z] != $sosa1_conj and $ancetres1['id_indi'][$z] != '')
						{	$z = $z + 1;}
						$ancetres1['nom_conj'][$i] = $ancetres1['nom'][$z];

						if ($ancetres ['sexe'][$j] == 'M') {$sosa2_conj = $ancetres['sosa_d'][$j] + 1;} else {$sosa2_conj = $ancetres['sosa_d'][$j] - 1;}
						$z = 0;
						while ($ancetres['sosa_d'][$z] != $sosa2_conj and $ancetres['id_indi'][$z] != '')
						{	$z = $z + 1;}
						$ancetres['nom_conj'][$j] = $ancetres['nom'][$z];

						if (soundex_got($ancetres ['nom_conj'][$j],$_REQUEST['fcont']) == soundex_got($ancetres1 ['nom_conj'][$i],$_REQUEST['fcont']) and $ancetres1 ['nom_conj'][$i] != '')   // alors on est certain de faire la correlation
						{	$flag_souche = "OK";
						} else
							/* sinon, on teste le soundex des mères des 2 individus */
						{	$sosa1_mere = $ancetres1['sosa_d'][$i] * 2+ 1;
							$z = 0;
							while ($ancetres1['sosa_d'][$z] != $sosa1_mere and $ancetres1['id_indi'][$z] != '')
							{	$z = $z + 1;}
							$ancetres1['nom_mere'][$i] = $ancetres1['nom'][$z];
							
							$sosa2_mere = $ancetres['sosa_d'][$j] * 2 + 1;
							$z = 0;
							while ($ancetres['sosa_d'][$z] != $sosa2_mere and $ancetres['id_indi'][$z] != '')
							{	$z = $z + 1;}
							$ancetres['nom_mere'][$j] = $ancetres['nom'][$z];
							
							if (soundex_got($ancetres ['nom_mere'][$j],$_REQUEST['fcont']) == soundex_got($ancetres1 ['nom_mere'][$i],$_REQUEST['fcont']) and $ancetres1 ['nom_mere'][$i] != '') 
							{	$flag_souche = "OK";
							} else 
								if ( (soundex_got($ancetres ['nom_mere'][$j],$_REQUEST['fcont']) != soundex_got($ancetres1 ['nom_mere'][$i],$_REQUEST['fcont']) and $ancetres1 ['nom_mere'][$i] != '' and $ancetres ['nom_mere'][$j] != '') 
									or (soundex_got($ancetres ['nom_conj'][$j],$_REQUEST['fcont']) != soundex_got($ancetres1 ['nom_conj'][$i],$_REQUEST['fcont']) and $ancetres1 ['nom_conj'][$i] != '' and $ancetres ['nom_conj'][$j] != '') )
								{	$flag_semblable = "KO";
								}
						}
					}
				}
				if ($flag_souche == "OK") 
				{	$souches['id_base1'][$cpt_souches] = $i;
					$souches['id_base2'][$cpt_souches] = $j;
					$souches['sosa1_d'][$cpt_souches] = $ancetres1['sosa_d'][$i];
					$cpt_souches = $cpt_souches + 1;
				}
				if ($flag_semblable == "OK" and $flag_souche == "KO") 
				{	$semblables['id_base1'][$cpt_semblables] = $i;
					$semblables['id_base2'][$cpt_semblables] = $j;
					$cpt_semblables = $cpt_semblables + 1;
				}
				$j = $j + 1;
			}
		}
		$i = $i + 1;
	}

			/************************ AFFICHAGE FINAL **********************************/

	if ($souches['id_base1'][0] != '' or $semblables['id_base1'][0] != '')
	{	echo '<table class="bord_haut bord_bas">';

		echo "<tr class=ligne_tr2>";
		echo "<td class=cell_indiv colspan='9' align='center'><b>".$got_lang['Ancet']." ".$got_lang['Commu']."</b></td>";
		echo "</tr>";
	
		echo '<tr><td colspan="9">&nbsp;</td></tr>';

		echo "<tr>";
		echo "<td class=cell_indiv colspan='4' align='center'><b>".$ancetres1 ['prenom1'][0]." ".$ancetres1 ['nom'][0]."</b> Base (".$_REQUEST['ibase'].")</td>";
		echo '<td></td>';
		echo "<td class=cell_indiv colspan='4' align='center'><b>".$ancetres ['prenom1'][0]." ".$ancetres ['nom'][0]."</b> Base (".$_REQUEST['ibase2'].")</td>";
		echo "</tr>";
	
		echo "<tr>";
		echo "<td class=bords_verti align='center'></td>";
		echo "<td class=bords_verti align='center'><b>Date</b></td>";
		echo "<td align='center'><b>".$got_lang['NomCo']."</b></td>";
		echo "<td class=bords_verti align='center'><b>".$got_lang['NomMe']."</b></td>";
		echo '<td class=bords_verti></td>';
		echo "<td class=bords_verti align='center'></td>";
		echo "<td class=bords_verti align='center'><b>Date</b></td>";
		echo "<td align='center'><b>".$got_lang['NomCo']."</b></td>";
		echo "<td class=bords_verti align='center'><b>".$got_lang['NomMe']."</b></td>";
		echo "</tr>";

		$url = url_request();

		$z = 0;
		while ($souches['id_base1'][$z] != '')
		{	$i = $souches['id_base1'][$z];
			$j = $souches['id_base2'][$z];
			if ($z % 2 == 0) {echo "<tr class=ligne_tr1>";} else {echo "<tr class=ligne_tr2>";}
			echo "<td class=bords_verti>";
			afficher_lien_indiv ($ancetres1['id_indi'][$i], $url, $ancetres1 ['sosa_d'][$i],$ancetres1 ['nom'][$i],$ancetres1 ['prenom1'][$i],$ancetres1 ['prenom2'][$i],$ancetres1 ['prenom3'][$i],$ancetres1 ['sexe'][$i],$_REQUEST['ibase']);
			echo "</td>";
			echo "<td class=bords_verti>".$ancetres1 ['tri'][$i]."</td>";
			echo "<td class=bords_verti>".$ancetres1 ['nom_conj'][$i]."</td>";
			echo "<td class=bords_verti>".$ancetres1 ['nom_mere'][$i]."</td>";
			echo '<td>&nbsp;=&nbsp;</td>';
			echo "<td class=bords_verti>";
			afficher_lien_indiv ($ancetres['id_indi'][$j], $url, $ancetres ['sosa_d'][$j], $ancetres ['nom'][$j],$ancetres ['prenom1'][$j],$ancetres ['prenom2'][$j],$ancetres ['prenom3'][$j],$ancetres ['sexe'][$j],$_REQUEST['ibase2']);
			echo "</td>";
			echo "<td class=bords_verti>".$ancetres ['tri'][$j]."</td>";
			echo "<td class=bords_verti>".$ancetres ['nom_conj'][$j]."</td>";
			echo "<td class=bords_verti>".$ancetres ['nom_mere'][$j]."</td>";
			echo "</tr>";
			$z = $z + 1;
		}
		echo '</table><br>';
	
//		echo '<tr><td class=bords_verti colspan="9">&nbsp;</td></tr>';
		
		$z = 0;
		if ($semblables['id_base1'][$z] != '')  // s'il y a des semblables sans verification possible, on les affiche pour info
		{	
			echo '<table class="bord_haut bord_bas">';
			
			echo "<tr class=ligne_tr2>";
			echo "<td class=cell_indiv colspan='9' align='center'><b>".$got_lang['Ancet']." ".$got_lang['AVeri']."</b></td>";
			echo "</tr>";

			echo '<tr><td colspan="9">&nbsp;</td></tr>';

			echo "<tr>";
			echo "<td class=cell_indiv colspan='4' align='center'><b>".$ancetres1 ['prenom1'][0]." ".$ancetres1 ['nom'][0]."</b> Base (".$_REQUEST['ibase'].")</td>";
			echo '<td></td>';
		echo "<td class=cell_indiv colspan='4' align='center'><b>".$ancetres ['prenom1'][0]." ".$ancetres ['nom'][0]."</b> Base (".$_REQUEST['ibase2'].")</td>";
			echo "</tr>";

			echo "<tr>";
			echo "<td class=bords_verti align='center'></td>";
			echo "<td class=bords_verti align='center'>Date</td>";
			echo "<td class=bords_verti align='center'><b>".$got_lang['NomCo']."</b></td>";
			echo "<td class=bords_verti align='center'><b>".$got_lang['NomMe']."</b></td>";
			echo '<td class=bords_verti></td>';
			echo "<td class=bords_verti align='center'></td>";
			echo "<td class=bords_verti align='center'>Date</td>";
			echo "<td class=bords_verti align='center'><b>".$got_lang['NomCo']."</b></td>";
			echo "<td class=bords_verti align='center'><b>".$got_lang['NomMe']."</b></td>";
			echo "</tr>";
		
			while ($semblables['id_base1'][$z] != '')
			{	$i = $semblables['id_base1'][$z];
				$j = $semblables['id_base2'][$z];
				echo "<tr>";
				echo "<td class=bords_verti>";
				afficher_lien_indiv ($ancetres1['id_indi'][$i], $url, $ancetres1 ['sosa_d'][$i],$ancetres1 ['nom'][$i],$ancetres1 ['prenom1'][$i],$ancetres1 ['prenom2'][$i],$ancetres1 ['prenom3'][$i],$ancetres1 ['sexe'][$i],$_REQUEST['ibase']);
				echo "</td>";
				echo "<td class=bords_verti>".$ancetres1 ['tri'][$i]."</td>";
				echo "<td class=bords_verti>".$ancetres1 ['nom_conj'][$i]."</td>";
				echo "<td class=bords_verti>".$ancetres1 ['nom_mere'][$i]."</td>";
				echo '<td>&nbsp;=&nbsp;</td>';
				echo "<td class=bords_verti>";
				afficher_lien_indiv ($ancetres['id_indi'][$j], $url, $ancetres ['sosa_d'][$j],$ancetres ['nom'][$j],$ancetres ['prenom1'][$j],$ancetres ['prenom2'][$j],$ancetres ['prenom3'][$j],$ancetres ['sexe'][$j],$_REQUEST['ibase2']);
				echo "</td>";
				echo "<td class=bords_verti>".$ancetres ['tri'][$j]."</td>";
				echo "<td class=bords_verti>".$ancetres ['nom_conj'][$j]."</td>";
				echo "<td class=bords_verti>".$ancetres ['nom_mere'][$j]."</td>";
				echo "</tr>";
				$z = $z + 1;
			}
		}
		echo "</table><br>";
	
		echo '</td><td width=24%>';  // grand cadre
		require_once ("fiche.php");
		echo '</td></tr></table>';

	} else
	{	echo '<p class=titre>'.$got_lang["PasCo"].'</p>';
	}
}
?>
</BODY>
</HTML>


