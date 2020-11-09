<?php

function afficher_bulle($idbulle, $idindi, $xcoindroit, $ydecalagecoindroit)
{	global $url;
	global $got_lang;
				// la div sous dessous doit obligatoirement etre une fille d'une div (sous div) pour que la position absolue parte de la div appellante
				// ex div mère -> <div class="cell_indiv"  OnMouseOver=afficher_bulle("'.$idbulle.'") OnMouseOut=desafficher_bulle("'.$idbulle.'")>
	$xcoindroit = $xcoindroit - 80;
	$ydecalagecoindroit = $ydecalagecoindroit + 10;
	echo '<div id="'.$idbulle.'" class=invisible align=left style="position: absolute; left: '.$xcoindroit.'px; top: '.$ydecalagecoindroit.'px; width: 160px;" >';
	echo '<a HREF = arbre_ascendant.php'.$url.'&id='.$idindi.' title="'.$got_lang['ArAsc'].'"><img width=18 height=18 src="themes/arrow-icon-top.png" border=0>'.$got_lang['ArAsc'].'</a>';
	echo '<br><a HREF = arbre_mixte.php'.$url.'&id='.$idindi.' title="'.$got_lang['ArMix'].'"><img width=18 height=18 src="themes/arrow-icon-middle.png" border=0>'.$got_lang['ArMix'].'</a>';
	echo '<br><a HREF = arbre_descendant.php'.$url.'&id='.$idindi.' title="'.$got_lang['ArDes'].'"><img width=18 height=18 src="themes/arrow-icon-bottom.png" border=0>'.$got_lang['ArDes'].'</a>';
	echo '</div>';
}

function afficher_cellule($tab_indi, $idbulle, $xx, $yy, $format)   
{	/* tab_indi : préparer un tableau de keys avec "$cles = array_keys($toto);" 
		 et une boucle "for ($ik = 0; $ik < count ($cles); $ik++)  {	$tab_indiv[$cles[$ik]] = $toto[$cles[$ik]][$ii];	}"
		 idbulle : préparer les identifiants de div des bulles avec des codifications uniques pour éviter les conflits
		 l'appel de idbulle se fait dans la div mère -> <div class="cell_indiv"  OnMouseOver=afficher_bulle("'.$idbulle.'") OnMouseOut=desafficher_bulle("'.$idbulle.'")>
		 la div bulle est une fille d'une div mère pour que la position absolue parte de la div appellante
		 5 formats : H2, H3, V1, V2, V3 Horizontal 2 et 3 lignes, Vertical petite, moy et grande largeur
		
		FONCTIONNEMENT DE getElementId.offsetTop
		dans afficher_lien_indiv, il y a un appel à la fonction javascript  afficher_fiche (getElementId.offsetTop)
		Cette fonction est sensible : elle retourne 0 si elle appelle sa propre div et que la div est encapsulée par une div en position absolue ou un tableau
		Elle fonctionne soit seule, soit encapsulée par une div non positionnée, soit en appelant l'identifiant de la div mère
*/				
	global $url;
	global $got_lang;
	switch ($format)
	{	case "H3": $wi=200; $he= 50; 	$ydecalagecoindroit = -15; break;
		case "H2": $wi=222; $he= 30; 	$ydecalagecoindroit = -26; break;
		case "V1": $wi= 80; $he=160; 	$ydecalagecoindroit = 40; break;
		case "V2": $wi=125; $he=160; 	$ydecalagecoindroit = 40; break;
	}

	echo '<div id=CEL'.$tab_indi['id_indi'].' class="cell_indiv" style="position:absolute; left: '.$xx.'px; top: '.$yy.'px; width: '.$wi.'px; height: '.$he.'px;"  OnMouseOver=afficher_bulle("'.$idbulle.'") OnMouseOut=desafficher_bulle("'.$idbulle.'")>'; 
	afficher_lien_indiv ($tab_indi['id_indi'],$url,$tab_indi['sosa_dyn'],$tab_indi['nom'],$tab_indi['prenom1'],"","",$tab_indi['sexe'],NULL,NULL,21, "NOFLECH");

					// naissance
	$ligne = "";
	if ($tab_indi['date_naiss'] != '') 
	{	if ($got_lang['Langu'] == "fr" and $tab_indi['sexe'] == 'F') 
		{	$pre_date_naiss = $got_lang['Ne'].'e ';
		} else 
		{	$pre_date_naiss = $got_lang['Ne'].' ';
		}
		$ligne = $pre_date_naiss.'<b>'.affichage_date($tab_indi['date_naiss'],"YES").'</b>';

	}
	if ($tab_indi['lieu_naiss'] != '')
	{	if (mb_strlen($tab_indi['dept_naiss']) == 2)
		{	$post_lieu_naiss = '('.$tab_indi['dept_naiss'].')';
		} else
		{	$post_lieu_naiss = "";
		}
		$ligne = $ligne.' '.$got_lang['Situa'].' '.mb_substr($tab_indi['lieu_naiss'],0,17).$post_lieu_naiss;		// lieu_naissance
	} 
	
//	echo mb_substr($ligne,0,43);		// bloque les dépassements pour éviter le retour à la ligne.
	echo '<br>'.$ligne;

	if ($format == "H3")
	{				// deces ou profession
		if ($_REQUEST['iprof'] == "Deces")
		{	if ($tab_indi['date_deces'] != '') // date et lieu_deces
			{	echo '<br>'.html_entity_decode ("&#134", ENT_COMPAT, "UTF-8").' <b>'.affichage_date($tab_indi['date_deces'],"YES").'</b> '.$got_lang['Situa'].' '.mb_substr($tab_indi['lieu_deces'],0,20);
			} 
		}	elseif ($_REQUEST['iprof'] == "Age") 
		{	echo '<br>';
			if ($tab_indi['date_deces'] != '') {echo html_entity_decode ("&#134", ENT_COMPAT, "UTF-8").' ';}
			echo ' <b>'.affichage_age($tab_indi['date_naiss'], $tab_indi['date_deces']).'</b>';
		}	else
		{	echo '<br><b>'.mb_substr($tab_indi['profession'],0,30).'</b>';
		}
	}

				// la div bulle est une fille de la div principale pour hériter de sa position absolue 
	afficher_bulle($idbulle, $tab_indi['id_indi'], $wi, $ydecalagecoindroit);

	echo '</div>';

			// affichage div photo
	$query = 'SELECT note_evene 
			FROM got_'.$_REQUEST['ibase'].'_evenement
			WHERE id_indi = '.$tab_indi['id_indi'].' and type_evene = "FILE"';
	$result = sql_exec($query,0);
	$row = mysqli_fetch_row($result);
	if ($row[0] != NULL)
	{	if (mb_substr($format,0,1) == "H") 
		{	$xphoto = $xx - $he*.75;  					//decalage a gauche en fonction de la hauteur de la cellule
			$yphoto = $yy;
			$hphoto = $he;										// hauteur photo identique hauteur cellule
		} else 
		{	$xphoto = $xx + $wi*0.15;								// affichage photo dans la cellule
			$hphoto = $wi*.8;									// hauteur photo adaptée à la taille de la cellule
			$yphoto = $yy + $he - $hphoto - 5; // photo au bord du bottom cellule
		}
		
		echo '<div style="position: absolute; left: '.$xphoto.'px; top: '.$yphoto.'px;">';
		echo '<a href='.$_SERVER["PHP_SELF"].$url.'&fid='.$tab_indi["id_indi"].' title="'.$got_lang["IBFih"].'">
		<img src="picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0].'" height="'.$hphoto.'"></a>';
		echo '</div>';
	}

}

function afficher_filtres($ibase, $ipag, $spag, $isex, $ideb, $intervalle, $palma, $url)
{	global $got_lang;

			// liste deroulante des intervalles et affichage/saisie intervalle d'année
	$taille_palm = array(15,30,45);
	$liste_interv = array(250,200,150,100,50);

	echo '<table>';
	echo '<form method=post>';

	if ($ipag !== "st") // i.e graphe.php
	{	if ($ipag == NULL)	{$ipag = "no";}
		echo '<tr><td align=right width=925px>';
		afficher_radio_bouton ("ipag",array($got_lang['Noms'],$got_lang['Preno'],$got_lang['Depar'],$got_lang['Lieux']),array("no","pr","de","li"),$ipag,"YES");
		echo '</td>';
//		echo '<tr><td align=center>';
	} else			// i.e stat.php
	{	if ($_REQUEST['isex'] == NULL)	{$_REQUEST['isex'] = "_";}
		echo '<tr><td align=right width=925px>';
		afficher_radio_bouton("spag",array($got_lang['StLon'],$got_lang['StMar'],$got_lang['StPar'],$got_lang['StFam']),array("lon","mar","par","fam"),$spag,"YES");
		echo '</td>';
	}		

	if ($ideb == NULL)
	{	$debut = $ideb;
	} else 
	{	$debut = $ideb + $intervalle;
	}
	echo '<td align=right width=550px>';
	echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ideb='.$ideb.'&intervalle='.$intervalle.'&sens=moins"><img src=themes/fleche_prec.png border=0></a>';
	echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ideb=&sens=" title="Reset"><img src=themes/reset.png border=0></a>';
	echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&ideb='.$debut.'&intervalle='.$intervalle.'&sens=plus"><img src=themes/fleche_suiv.png border=0></a>';
	echo '</td></tr>';

	echo '<tr><td align=center><br>';
	if ($ipag == "st")
	{	afficher_radio_bouton ("isex",array($got_lang['Tous'],$got_lang['Femme'],$got_lang['Homme']),array("_","F","M"),$isex,"YES");
	}

	echo '<b>&nbsp;&nbsp;&nbsp;&nbsp;'.$got_lang['NbPal'].'</b>&nbsp;';
	afficher_radio_bouton ("palma",$taille_palm,$taille_palm, $palma);

	if (existe_sosa())
	{	
		echo '&nbsp;&nbsp;&nbsp;&nbsp;<b>Sosa </b>';
		afficher_radio_bouton("sosa", array ($got_lang['Tous'],"Sosa"), array ("Tous","Sosa"), $_REQUEST['sosa']);
	}

	echo '</td>';
	echo '<td><br><b>&nbsp;&nbsp;&nbsp;&nbsp;'.$got_lang['Inter'].'</b>&nbsp;';
	afficher_radio_bouton ("intervalle",$liste_interv,$liste_interv,$intervalle);

	echo "</form>";
	echo '</td></tr></table>';
}

function afficher_lien_indiv ($id_indi, $url, $sosa, $nom, $prenom1, $prenom2 = NULL, $prenom3 = NULL, $sexe, $base = NULL, $source = NULL, $lg = 100, $nofleches = NULL)
{	global $got_lang;

// affichage du lien de l'individu
	echo '<div id=CEL'.$id_indi.'>';

	// affichage du C vert existence consanguinité
	if ($_REQUEST['csg'] == 'O')
	{	echo '<a href = consanguinite.php'.$url.'&fid='.$id_indi.' title="'.$got_lang['EtCon'].'"><img src="themes/consang.png" border=0></a>&nbsp;';
	}

	// affichage du logo S vert existence source
	if ($source)
	{	echo '<img src="themes/source.png" border=0 title=Source></a>&nbsp;';
	} 

	echo '<a href='.basename($_SERVER["PHP_SELF"]).$url;
	echo '&fid='.$id_indi;
	echo ' title="'.$got_lang['IBFih'].'"';
	echo ' onclick="javascript:afficher_fiche(&quot;CEL'.$id_indi.'&quot;);return false;"';   // syntaxe javascript infernale, ca m'enerve
	echo '><font color='.recup_color_sexe($sexe).' face="Arial" size="2"><b>'.mb_substr($nom.' '.$prenom1.' '.$prenom2.' '.$prenom3,0,$lg).'</b></font></a>';

	// affichage du losange sosa
	if ($sosa != 0)
	{	echo '<img src="themes/fleche_losa.png" border=0 title=Sosa&nbsp;'.$sosa.'>&nbsp;';
	}

	echo '</div>';
}

function afficher_liste_individu ($result)
{	// sql attendu 		SELECT id_indi,nom,prenom1,TRI,lieu_naiss,sosa_dyn,sexe
	
	global $url;
	echo '<table class="bord_haut bord_bas">';
	$ii = 0;
	while ($row = mysqli_fetch_row($result))
	{	if ($ii % 2 == 0) {echo '<tr class=ligne_tr1>';} else {echo '<tr class=ligne_tr2>';}
		echo '<td class=bords_verti width=215>';
		if ($_REQUEST['ipag'] == "pr") 
		{	afficher_lien_indiv ($row[0], $url, $row[5], $row[1],'',"","",$row[6],NULL,NULL,19);
		} else if ($_REQUEST['ipag'] == "no")
		{	afficher_lien_indiv ($row[0], $url, $row[5], '',$row[2],$row[7],$row[8],$row[6],NULL,NULL,23);
		} else
		  afficher_lien_indiv ($row[0], $url, $row[5], $row[1],$row[2],"","",$row[6],NULL,NULL,23);
		echo '</td>';
		echo '<td class=bords_verti width=15>&nbsp;'.affichage_date($row[3],"YES").'</font></td>';
	 	echo '<td class=bords_verti width=70>&nbsp;'.mb_substr($row[4],0,7).'</td>';
		echo '</tr>';
		$ii++;
	}
	echo '</table>';	
}

function afficher_liste_deroulante($nom_liste, $cont_liste, $select, $flag_submi = "YES")
{
	echo '<select name='.$nom_liste;
	if ($flag_submi == "YES")	{echo ' onchange="submit();"';}
	echo '>';
	$count_liste = count($cont_liste);echo $count_liste;
	for ($ii = 0; $ii < $count_liste; $ii++)
	{	echo ' <option class=ligne_tr2 ';
		if ($select == $cont_liste[$ii]) {echo ' SELECTED';}
		echo ' >'.$cont_liste[$ii].'</option>';
	}
	echo '</select>';
}

function afficher_radio_bouton($nom_liste, $cont_lib, $cont_code, $select, $flag_submit = 'YES')
{	for ($ii = 0; $ii < count($cont_code); $ii++) 
	{	echo '<input class=invisible type=radio name='.$nom_liste.' value='.$cont_code[$ii].' id='.$nom_liste.$ii;
		if ($flag_submit == 'YES')	{ echo ' onclick="submit();"';}
		if ($cont_code[$ii] == $select) {echo ' checked="checked"'; $style="filtre_actif";} else {$style="filtre_inactif";}
		echo '>';
		echo '<LABEL class="'.$style.' decale" for="'.$nom_liste.$ii.'">'.$cont_lib[$ii].'</LABEL>';
	}
}

function afficher_trait_horizontal($coor_trait)
{	echo '<div style="position:absolute; left:'.$coor_trait[0].'px; top: '.$coor_trait[1].'px; width: '.$coor_trait[2].'px;" class=trait_arbre_horiz></div>';
}

function afficher_trait_vertical($coor_trait)
{	echo '<div style="position:absolute; left:'.$coor_trait[0].'px; top: '.$coor_trait[1].'px; height: '.$coor_trait[2].'px;" class=trait_arbre_verti></div>';
}
?>