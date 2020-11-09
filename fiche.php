<?php
require_once ("_recup_ascendance.inc.php");
require_once ("_recup_descendance.inc.php");
require_once ("_boites.inc.php");
require_once ("_caracteres.inc.php");
require_once ("_recup_cousin_inc.php");
//require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	

function affichage_cousin ($fid, $url, $nb_generations,$nb_generations_desc,$relation)
{
	$cousins = recup_cousin ($fid,$nb_generations,$nb_generations_desc,$relation);

	if ($cousins['id_indi'] != 0)
	{	echo '<p class=titre>'.count($cousins['id_indi']).' '.$relation.'</p>';
	
	echo '<table class="bord_haut bord_bas">';
		$i = 0;
		while ($cousins['id_indi'][$i] != '')
		{	if ($i % 2 == 0) {echo '<tr class=ligne_tr1>';} else {echo '<tr class=ligne_tr2>';}
			echo '<td class=bords_verti width=215>';
			afficher_lien_indiv ($cousins['id_indi'][$i], $url, $cousins['sosa_dyn'][$i], $cousins['nom'][$i],$cousins['prenom1'][$i],"","",$cousins['sexe'][$i],NULL,NULL,23);
			echo '</td>';
			echo '<td class=bords_verti width=15>&nbsp;'.affichage_date($cousins["date_naiss"][$i],"YES").'</td>';
		 	echo '<td class=bords_verti width=70>&nbsp;'.mb_substr($cousins["lieu_naiss"][$i],0,7).'</td>';
			echo '</tr>';
			$i++;
		}
	echo '</table>';	
	}
}

echo '<SCRIPT language="javascript">';
echo '	window.onload = function()';
echo '	{	';
echo '		window.scrollTo(0,'.$_REQUEST["scrolly"].');';
echo '	}';
echo '</SCRIPT>';
/*************************************** DEBUT DU SCRIPT *********************************************/

if ($_REQUEST['cujd'] == "OK" and $_REQUEST['fcuj'] !== "OK")		// affichage de la demande de confirmation du changement de de-cujus
{	$row = recup_identite($_REQUEST['fid'], $_REQUEST['ibase']);
	echo "<br><br><br><br>";
	echo $got_lang['MesCu'];
	echo "<br>";
	echo "<p align=center>".$row[1]." ".$row[0]." ".affichage_date($row[2],"YES")." ".$row[3]."</p>";

		//Boite de confirmation specifique car on ne veut pas la positionner au centre de la page
	echo '<FORM method="POST" name="boiteconf" class="cell_indiv">';
	echo '<table>';	// tableau pour faire la bordure
	echo '<tr><td>'.str_repeat("&nbsp;",25).'</td>';
	echo '<td align=center>';
	echo 	'<input type="submit" name="fcuj" value="OK">&nbsp;&nbsp;&nbsp;&nbsp;';
	echo '<input type="button" value="Annul" onclick="window.location=&quot;'.basename($_SERVER["PHP_SELF"]).$url.'&fcuj=KO&cujd=KO&quot;"></td>';
	echo '</td></tr></table>';
	echo '</FORM>';
} else
{	if ($_POST['fcuj'] == "OK")
	{	maj_cujus ($_REQUEST['ibase'],$_REQUEST['fid'],'USER');
	}		// mise a jour du de cujus

			/* Rappel du personnage principal */

	echo '<div id=div_flottante class="maxwidth trait_double" style="position:absolute; left:937px; top:'.$_REQUEST["ftop"].'px; width=355px;">';

	echo "<table width=355px><tr>";
	echo "<td><a HREF = fiche_pdf.php".$url."&ipag= title='".$got_lang['IBPdf']."' target=_blank><img border=0 width=35 heigth=35 src=themes/icon-print.png></a></td>";
	echo '<td></td>';

	echo '<td class=menu_td><a class=menu_td href='.basename($_SERVER["PHP_SELF"]).$url.'&icousin=KO title="'.$got_lang['IBFih'].'">Index</a></td>';
	echo '<td>&nbsp;&nbsp;</td>';
	echo '<td class=menu_td><a class=menu_td href='.basename($_SERVER["PHP_SELF"]).$url.'&icousin=OK title="'.$got_lang['IBCou'].'">'.$got_lang['Cousi'].'</a></td>';
	echo '<td>&nbsp;&nbsp;</td>';
	echo '<td class=menu_td><a class=menu_td href='.basename($_SERVER["PHP_SELF"]).$url.'&cujd=OK&fcuj=KO title="'.$got_lang['IBCuj'].'">'.$got_lang['ChgIn'].'</a></td>';
	echo "</tr></table>";
					
					// affichage info individu principal
	$query = 'SELECT * 
		FROM got_'.$_REQUEST['ibase'].'_individu 
		WHERE id_indi = '.$_REQUEST['fid'];
	$result = sql_exec($query);
	$indi = mysqli_fetch_row($result);

	$idbulle = 'C'.$_REQUEST['fid'];
	$top_cellule = $_REQUEST["ftop"] + 50;
	echo '<div class="cell_indiv" align=center valign=middle style="position:absolute; left:3px; top:48px; height:100px;" OnMouseOver=afficher_bulle("'.$idbulle.'") OnMouseOut=desafficher_bulle("'.$idbulle.'")>';
		echo '<p style="width:355px;">';
		echo '<font class=titre color='.recup_color_sexe($indi[5]).'>'.$indi[2].' '.$indi[3].' '.$indi[4].' '.$indi[1].'</font>';
		if ($indi[6] != NULL) {echo '<br><b>'.$indi[6].'</b>';} // profession
			echo '<br>';
		if ($indi[7] != NULL) {echo $got_lang['Ne'].' <b>'.affichage_date($indi[7])."</b>";}
		if ($indi[8] != NULL) {echo ' '.$got_lang['Situa'].' '.$indi[8].'('.$indi[9].')';}
		if ($indi[10] != NULL) {echo '<br>'.html_entity_decode ("&#134", ENT_COMPAT, "UTF-8").' <b>'.affichage_date($indi[10])."</b>"; echo ' (<b>'.affichage_age ($indi[7],$indi[10], "YES").'</b>)';}
		if ($indi[11] != NULL) {echo ' '.$got_lang['Situa'].' '.$indi[11].'('.$indi[12].')';}
		afficher_bulle ($idbulle, $indi[0], 340, 5);   // impossible de placer precisement la bulle car la div mere s ajuste en fonction du contenu !
		echo '</p>';
	echo '</div>';
	
	echo '<div style="position:absolute; left:3px; top:150px;")>';

					// affichage du sosa
	if ($indi[17] != 0)	{echo '<p align=center style="font-size:1.6em; font-weight:bold; color:red;"><img height=15 src="themes/fleche_losa.png" border=0 title=Source> n'.utf8_encode('°').$indi[17].'</p>';}

					// affichage de la photo
	$query = 'SELECT note_evene 
		FROM got_'.$_REQUEST['ibase'].'_evenement 
		WHERE type_evene = "FILE" 
		and id_indi = '.$_REQUEST['fid'];
	$result1 = sql_exec($query);
	$row1 = mysqli_fetch_row($result1);	// on considère que la première ligne trouvée, c'est la photo principale
	if ($row1[0] != '')
	{	$size_image = @getimagesize('picture/'.$_REQUEST['ibase'].'/'.$row1[0]);
		if ($size_image[0] < $size_image[1]) {$orient_image = "height";} else {$orient_image = "width";} //i.e largeur inferieur a hauteur => portrait
    echo '<p align="center"><a href="javascript:PopupPic(&quot;'.str_replace(' ','+','picture/'.$_REQUEST['ibase'].'/'.$row1[0]).'&quot;,'.$size_image[0].','.$size_image[1].')"><img src="picture/'.$_REQUEST['ibase'].'/'.$row1[0].'" '.$orient_image.'="240"></a></p> ';
	}

							// affichage de la note principale
	if ($indi[13] != NULL) {echo '<br><b>Note : </b><i>'.$indi[13].'</i>';}			//13 -> note_indi

					// si club, affichage de la base source
	if ($_REQUEST['ibase'] == '$club')
	{	$query = 'SELECT base FROM g__club WHERE indi_min <= "'.$_REQUEST['fid'].'" and indi_max >= "'.$_REQUEST['fid'].'"';
		$result = sql_exec($query,0);
		$base_club = mysqli_fetch_row($result);
		echo '<br>(Base '.$base_club[0].')';
	}

					// v3.11 affichage des autres medias
	$ii = 0;
	while ($row1 = mysqli_fetch_row($result1))
	{	if ($ii % 5 == 0)
		{	echo '<br>';
		}
		$size_image = @getimagesize('picture/'.$_REQUEST['ibase'].'/'.$row1[0]);
		if ($size_image[0] < $size_image[1]) {$orient_image = "height";} else {$orient_image = "width";} //i.e largeur inferieur a hauteur => portrait
//		echo '<img src="picture/'.$_REQUEST['ibase'].'/'.$row1[0].'" '.$orient_image.'="50">';
		echo '<a href="javascript:PopupPic(&quot;'.str_replace(' ','+','picture/'.$_REQUEST['ibase'].'/'.$row1[0]).'&quot;,'.$size_image[0].','.$size_image[1].')"><img src="picture/'.$_REQUEST['ibase'].'/thumbs/'.$row1[0].'"></a>&nbsp;';
		$ii++;
	}

					// affichage des évènements associés à l'individu principal autre que mariage : notes, sources et témoins 
	$query = 'SELECT a.type_evene,a.date_evene,a.lieu_evene,a.note_evene, b.id_sour, b.type_sourc,b.attr_sourc
		FROM (got_'.$_REQUEST['ibase'].'_evenement a
		LEFT OUTER JOIN got_'.$_REQUEST['ibase'].'_even_sour b 
		ON a.id_indi = b.id_indi and a.id_husb = b.id_husb and a.id_wife = b.id_wife 
		and a.type_evene = b.type_evene and a.date_evene = b.date_evene and a.dept_evene = b.dept_evene and a.lieu_evene = b.lieu_evene)
		WHERE a.id_indi = '.$_REQUEST['fid'].'
		ORDER BY 1,2,3,6';
	$result3 = sql_exec($query,0);
	$old = NULL;
	if (mysqli_num_rows($result3) !== 0)
	{	while ($row3 = mysqli_fetch_row($result3))
		{	if ($row3[0] !== $old and ($row3[3] != NULL or $row3[4] != NULL or $row3[6] != NULL) )		// on affiche l'acte s'il y a une note ou un temoin
			{	echo '<table><tr><td class="trait_simple justif">';
				echo '<b><u>'.$got_tag[$row3[0]].'</u></b> : ';  // affichage du titre de l'acte : bapteme, enterrement, diplome, ....
				if ($row3[0] != 'BIRT' and $row3[0] != 'DEAT')			// on n'affiche pas la date et le lieu si naiss ou deces car deja affiche
				{	echo '<i>'.affichage_date($row3[1]).' '.$row3[2].' </i>';   // affichage de la date de l'acte
				}
				echo '<i>'.mb_substr(mb_ereg_replace('picture/'.$_REQUEST['ibase'].'/thumbs/','',$row3[3]),0,45).'</i>';  // affichage du libellé de l'acte en supprimant le chemin si besoin
				if ($row3[5] == "RELA")			// affichage des temoins de l'acte
				{	$identite = recup_identite($row3[4],$_REQUEST['ibase']);
					echo '<br>&nbsp;&nbsp;&nbsp;<u>'.$got_tag[$row3[6]].' :</u> ';
					afficher_lien_indiv ($row3[4], $url, 0,$identite[0],$identite[1],"","",$identite[4]);
				}
				if ($row3[5] == "SOUR")		// affichage des sources de l'acte
				{	$query = 'SELECT note_source 
						FROM got_'.$_REQUEST['ibase'].'_source
						WHERE id_sour = '.$row3[4];
					$result2 = sql_exec($query);
					$source = mysqli_fetch_row($result2);
					echo '<br>&nbsp;&nbsp;&nbsp;'.$source[0];
				}
				if ($row3[5] == "FILE")		// affichage des fichiers associés à l'acte
				{	$size_image = @getimagesize('picture/'.$_REQUEST['ibase'].'/'.$row3[6]);
					if ($size_image[0] < $size_image[1]) {$orient_image = "height";} else {$orient_image = "width";} //i.e largeur inferieur a hauteur => portrait
			    echo '<a href="javascript:PopupPic(&quot;'.str_replace(' ','+','picture/'.$_REQUEST['ibase'].'/'.$row3[6]).'&quot;,'.$size_image[0].','.$size_image[1].')"><img src="picture/'.$_REQUEST['ibase'].'/thumbs/'.$row3[6].'"></a><br> ';
				}
				echo '</td></tr></table>';
			}
			$old = $row3[0];
		}
	}

	if ($_REQUEST['icousin'] != 'OK')
	{					// affichage du père
		echo '<p class=titre>'.$got_lang['Pere'].'</p>';
		if ($indi[14] != NULL)
		{	$query = 'SELECT id_indi,nom,prenom1,TRI,lieu_naiss,sosa_dyn,sexe FROM got_'.$_REQUEST['ibase'].'_individu WHERE id_indi = '.$indi[14];
			$result = sql_exec($query);
			afficher_liste_individu($result);
		}

					// affichage de la mère
		echo '<p class=titre>'.$got_lang['Mere'].'</p>';
		if ($indi[15] != NULL)
		{	$query = 'SELECT id_indi,nom,prenom1,TRI,lieu_naiss,sosa_dyn,sexe FROM got_'.$_REQUEST['ibase'].'_individu WHERE id_indi = '.$indi[15];
			$result = sql_exec($query);
			afficher_liste_individu($result);
		}
		;
					// affichage des unions
		echo '<p class=titre>'.$got_lang['Union'].'</p>';
	
		$query = 'SELECT b.id_indi,b.nom,b.prenom1,b.prenom2,b.prenom3,b.sexe,b.profession,
				a.date_evene,a.lieu_evene,dept_evene,note_evene,b.sosa_dyn,b.sexe,a.id_husb,a.id_wife,a.type_evene
				FROM got_'.$_REQUEST['ibase'].'_evenement a, got_'.$_REQUEST['ibase'].'_individu b 
				WHERE a.type_evene = "MARR" and ';
		if ($indi[5] == 'M')
		{	$query = $query .' (a.id_wife = b.id_indi) and a.id_husb = '.$_REQUEST['fid'];}
		else
		{	$query = $query .' (a.id_husb = b.id_indi) and a.id_wife = '.$_REQUEST['fid'];}
		$result = sql_exec($query,0);

		echo '<table width=100%>';
		while ($row = mysqli_fetch_row($result))
		{	
						// affichage du conjoint
			
			echo '<tr><td class=trait_simple>';
			echo $got_lang['Avec'];
			afficher_lien_indiv ($row[0], $url, $row[11], $row[1],$row[2],$row[3],$row[4],$row[12]);
			echo '</td></tr>';
			if ($row[6] != '') {echo  '<tr><td> '.$row[6].'</td></tr>';}  // affichage profession
			if ($row[7] != '' or $row[6] != '') {echo '<tr><td> <b>'.affichage_date($row[7])."</b>".' '.$got_lang['Situa'].' '.$row[8].'('.$row[9].')'.'</td></tr>';}
			if ($row[10] != '') {echo '<tr><td class="trait_simple justif"><i>'.$row[10].'</i>'.'</td></tr>';} // affichage note du mariage

						// affichage des témoins du mariage

			$query = 'SELECT a.id_sour,b.sosa_dyn,b.nom,b.prenom1,b.sexe,a.attr_sourc
			FROM got_'.$_REQUEST['ibase'].'_even_sour a
			INNER JOIN got_'.$_REQUEST['ibase'].'_individu b ON a.id_sour = b.id_indi and a.type_sourc = "RELA"
			WHERE a.id_husb = "'.$row[13].'" and a.id_wife = "'.$row[14].'" 
			and a.type_evene = "'.$row[15].'" and a.date_evene ="'.$row[7].'" and a.dept_evene = "'.$row[9].'" and a.lieu_evene = "'.$row[8].'"
			';
			$result2 = sql_exec($query,0);
			while ($row2 = mysqli_fetch_row($result2))
			{	echo '<tr><td><u>'.$got_tag[$row2[5]].' :</u> ';
				afficher_lien_indiv ($row2[0], $url, $row2[1], $row2[2],$row2[3],"","",$row2[4]);
				echo '</td></tr>';
			}

						// affichage de la source du mariage   BOGUE quand existe source, requete executée 2 fois Pierre Lebarillier Victoire Noel

			$id_conjoint = $row[0];
			$query = 'SELECT b.note_source, a.type_evene, a.type_sourc, a.attr_sourc
					FROM (got_'.$_REQUEST['ibase'].'_even_sour a LEFT OUTER JOIN got_'.$_REQUEST['ibase'].'_source b ON a.id_sour = b.id_sour)
					where ';
			if ($indi[5] == 'M')
			{	$query = $query .' a.id_husb = '.$_REQUEST['fid'].' and a.id_wife = '.$id_conjoint;}
			else
			{	$query = $query .' a.id_husb = '.$id_conjoint.' and a.id_wife = '.$_REQUEST['fid'];}
			$result2 = sql_exec ($query,0);
			while ($row2 = mysqli_fetch_row($result2))
			{	echo '<tr><td class="trait_simple justif">';
				if ($row2[2] == 'SOUR')
				{	echo '<u>Source '.$got_tag[$row2[1]].'</u> : <i>'.$row2[0].'</i>';
				}
				if ($row2[2] == 'FILE')
				{	$size_image = @getimagesize('picture/'.$_REQUEST['ibase'].'/'.$row2[3]);
				if ($size_image[0] < $size_image[1]) {$orient_image = "height";} else {$orient_image = "width";} //i.e largeur inferieur a hauteur => portrait
			  echo '&nbsp;<a href="javascript:PopupPic(&quot;'.str_replace(' ','+','picture/'.$_REQUEST['ibase'].'/'.$row2[3]).'&quot;,'.$size_image[0].','.$size_image[1].')"><img src="picture/'.$_REQUEST['ibase'].'/thumbs/'.$row2[3].'"></a><br> ';
				}
				echo '</td></tr>';
			}
		}
		echo '</table>';

						// affichage des frères et soeurs
		if ($indi[14] == NULL) {$indi[14] = "NULL";}	// astuce pour tromper mysql
		if ($indi[15] == NULL) {$indi[15] = "NULL";}	// astuce pour tromper mysql
		if ($indi[14] == 0) {$indi[14] = 99999999;}	// astuce pour tromper mysql
		if ($indi[15] == 0) {$indi[15] = 99999999;}	// astuce pour tromper mysql
		$query = 'SELECT distinct id_indi,nom,prenom1,TRI,lieu_naiss,sosa_dyn,sexe
			FROM got_'.$_REQUEST['ibase'].'_individu 
			WHERE (id_pere = '.$indi[14].' or id_mere = '.$indi[15].')
			and id_indi != '.$_REQUEST['fid'].'
			ORDER BY tri';
		$result = sql_exec($query);

		echo '<p class=titre>'.mysqli_num_rows($result).' '.$got_lang['Frere'].'</p>';
		afficher_liste_individu($result);

					// affichage des enfants
		$query = 'SELECT id_indi,nom,prenom1,TRI,lieu_naiss,sosa_dyn,sexe
			FROM got_'.$_REQUEST['ibase'].'_individu  
			WHERE ';
		if ($indi[5] == 'M') {$query = $query.' id_pere = ';} else {$query = $query.' id_mere = ';}
		$query = $query	.$_REQUEST['fid'].'
			ORDER BY tri';
		$result = sql_exec($query,0);

		echo '<p class=titre>'.mysqli_num_rows($result).' '.$got_lang['Enfan'].'</p>';
		afficher_liste_individu($result);
	} 
	else				// affichage des cousins
	{	$ancetres='';$descendants='';$cpt_generations='';
		$nb_generations = 0;$nb_generations_desc = 2;$relation = $got_lang['PetEf'];
		affichage_cousin ($_REQUEST['fid'], $url, $nb_generations,$nb_generations_desc,$relation);
	
		$ancetres='';$descendants='';$cpt_generations='';
		$nb_generations = 2;$nb_generations_desc = 1;$relation = $got_lang['Oncle'];
		affichage_cousin ($_REQUEST['fid'],$url, $nb_generations,$nb_generations_desc,$relation);
		
		$ancetres='';$descendants='';$cpt_generations='';
		$nb_generations = 1;$nb_generations_desc = 2;$relation = $got_lang['Neveu'];
		affichage_cousin ($_REQUEST['fid'],$url, $nb_generations,$nb_generations_desc,$relation);
		
		$ancetres='';$descendants='';$cpt_generations='';
		$nb_generations = 2;$nb_generations_desc = 2;$relation = $got_lang['Germa'];
		affichage_cousin ($_REQUEST['fid'],$url, $nb_generations,$nb_generations_desc,$relation);
		
		$ancetres='';$descendants='';$cpt_generations='';
		$nb_generations = 3;$nb_generations_desc = 1;$relation = $got_lang['OncGr'];
		affichage_cousin ($_REQUEST['fid'],$url, $nb_generations,$nb_generations_desc,$relation);
		
		$ancetres='';$descendants='';$cpt_generations='';
		$nb_generations = 3;$nb_generations_desc = 2;$relation = $got_lang['CouGr'];
		affichage_cousin ($_REQUEST['fid'],$url, $nb_generations,$nb_generations_desc,$relation);
		
		$ancetres='';$descendants='';$cpt_generations='';
		$nb_generations = 3;$nb_generations_desc = 3;$relation = $got_lang['CouIs'];
		affichage_cousin ($_REQUEST['fid'],$url, $nb_generations,$nb_generations_desc,$relation);
	}	

	echo '</div>';  // fermeture de la div de positionnement generale

/*ob_start();
var_dump(get_defined_vars());
$size=(int)(ob_get_length()/1024);
ob_end_clean();
echo 'Mémoire utilisée par les variables = ' . $size .' Ko';

function sizeofvar($var) 
{

  $start_memory = memory_get_usage();   
  $temp =unserialize(serialize($var ));   
  $taille = memory_get_usage() - $start_memory;
  return $taille ;
}

function aff_variables() 
{
   echo '<br/>';
   global $datas ;
   foreach($GLOBALS as $Key => $Val)
   {
      if ($Key != 'GLOBALS') 
      { $toto['NomVar'][] = $Key;
		$toto['SizVar'][] = sizeofvar( $Val );
      }
   }
    echo' <br/>';

	array_multisort($toto['SizVar'], SORT_DESC, $toto['NomVar']);

	echo '<table>';
	$ii = 0;
	while (isset($toto['NomVar'][$ii]))
	{	echo '<tr><td>'.$toto['NomVar'][$ii].'</td><td align=right>'.$toto['SizVar'][$ii].'</td></tr>';
		$ii++;
	}
	echo '</table>';
}

function memory_stat()
{
   echo  'Mémoire -- Utilisé : '. memory_get_usage(false) .
   ' || Alloué : '.
   memory_get_usage(true) .
   ' || MAX Utilisé  : '.
   memory_get_peak_usage(false).
   ' || MAX Alloué  : '.
   memory_get_peak_usage(true).
   ' || MAX autorisé : '.
   ini_get('memory_limit') ;  ;
}

aff_variables();
*/
}
?>
