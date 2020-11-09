<?php
require_once  ("_recup_ascendance.inc.php");
require_once  ("_recup_descendance.inc.php");
require_once  ("_sql.inc.php");
require_once  ("_caracteres.inc.php");
require_once  ("_recup_cousin_inc.php");
require_once  ("tfpdf/tfpdf.php");
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
$pool = sql_connect();

class PDF extends TFPDF
{

function init_pdf()
{	global $entete;
	global $row;
	global $got_lang;

	$entete = $got_lang['ImpFi'];

	$this->SetTitle($row[4].' '.$row[5].' '.$row[6].' '.$row[3].' ');
	$this->SetCreator('GeneoTree');
	$this->SetAuthor('GeneoTree');
	$this->SetMargins(18,20);
	$this->SetAutoPageBreak(TRUE, 20);
	$this->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
}

function editer_cellule($text,$retour,$font,$style = NULL)
{	global $dim_page;

	if ($font == "F") {$this->SetTextColor (172,2,83); $font = '';}
	else if ($font == "M") {$this->SetTextColor (0,0,128); $font = '';}
	else {$this->SetTextColor (0,0,0);}

	$this->SetFont('DejaVu','',8);
	$this->SetFillColor(255);

	$retour_av = array('RET','CONT','SOUS');
	$retour_ap = array(1,0,2);
	$retour = str_replace($retour_av,$retour_ap,$retour);

	$larg_cell = largeur_cellule($text,$font);
	if ($style == 'NOTE')
	{	
		$car_chariot_in = array (chr(13));
		$car_chariot_out = array ('
');
		$text = str_replace($car_chariot_in,$car_chariot_out,$text);
		$this->MultiCell(138,3,$text,0,"L",1);
	} elseif ($style == 'STITRE')
	{	
		$this->SetFont('DejaVu','',10);
		$this->Cell($larg_cell,3,strtoupper($text),0,$retour,"L",1);
	} else
	{	if ($larg_cell + $this->GetX() > $dim_page[0] - 53)	// gestion du changement de ligne
		{	$this->cell(1,3,'',0,1,"L",1);			// deplacement curseur a la ligne suivante
			$this->SetX(80);					// deplacement curseur milieu de ligne
		}
		$this->Cell($larg_cell,3,$text,0,$retour,"L",1);
	}
}

function editer_individu($row,$principal = NULL)
{	global $got_lang;
	
	if ($principal == NULL)
	{	if ($row[5] == "F") {$font = 'F';}
		else if ($row[5] == "M") {$font = 'M';}
		else {$row[5] = '';}
		$this->editer_cellule ($row[2].' '.$row[3].' '.$row[4].' '.$row[1],'CONT',$font,'');
	} else
	{	$this->SetFont('DejaVu','',12);
		$this->SetFillColor(255);
		$this->cell(110,4,$row[2].' '.$row[3].' '.$row[4].' '.$row[1],0,2,"L",1);
	}

	if ($row[6] != NULL) {$this->editer_cellule (', '.$row[6],'CONT','','');}	//profession

	if ($row[7] != NULL)
	{	if ($got_lang['Langu'] == 'fr' and $row[5] == 'F') {$suf_naiss = 'e';} else {$suf_naiss = "";}
		$this->editer_cellule (', '.$got_lang['Ne'].$suf_naiss.' '.affichage_date($row[7]),'CONT','b','');
	}
	if ($row[8] != NULL) 
	{	$this->editer_cellule ($got_lang['Situa'].' '.$row[8].'('.$row[9].')','CONT','','');
	}

	if ($row[10] != NULL)	// date de deces
	{	$this->editer_cellule (', + '.affichage_date($row[10]),'CONT','','');
	}
	if ($row[11] != NULL)	// lieu de deces
	{	$this->editer_cellule ($got_lang['Situa'].' '.$row[11].'('.$row[12].')','CONT','','');
	}
	$this->editer_cellule (affichage_age($row[7],$row[10]),'CONT','','');

	$this->editer_cellule ('','RET','','');
}

function editer_cousin ($fid,$nb_generations,$nb_generations_desc,$relation)
{	
	$cousins = recup_cousin ($fid,$nb_generations,$nb_generations_desc,$relation);

	if (@count($cousins['id_indi']) != 0)
	{	$this->editer_cellule ('','RET','','');
		$this->editer_cellule (@count($cousins['id_indi']).' '.$relation,'RET','b','STITRE');
	}
	
	$i = 0;
	for ($i = 0; $i < @count($cousins['id_indi']); $i++)
//	while ($cousins['id_indi'][$i] != '')
	{	$row[0] = $cousins['id_indi'][$i];
		$row[1] = $cousins['nom'][$i];
		$row[2] = $cousins['prenom1'][$i];
		$row[3] = $cousins['prenom2'][$i];
		$row[4] = $cousins['prenom3'][$i];
		$row[5] = $cousins['sexe'][$i];
		$row[6] = $cousins['profession'][$i];
		$row[7] = $cousins['date_naiss'][$i];
		$row[8] = $cousins['lieu_naiss'][$i];
		$row[9] = $cousins['dept_naiss'][$i];
		$row[10] = $cousins['date_deces'][$i];
		$row[11] = $cousins['lieu_deces'][$i];
		$row[12] = $cousins['dept_deces'][$i];
		$this->editer_individu($row);
//		$i++;
	}
}

function editer_fiche($id_indi)
{	global $got_lang;
	global $got_tag;
	global $entete;
	global $dim_page;

	$this->AddPage();

					// affichage photo
	$query = 'SELECT note_evene FROM got_'.$_REQUEST['ibase'].'_evenement WHERE type_evene = "FILE" and id_indi = '.$id_indi;
	$result1 = sql_exec($query);
	$row1 = mysqli_fetch_row($result1);
	$taille = @getimagesize('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row1[0]);
	if ($taille[0] !== NULL)
	{	$this->image('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row1[0],$dim_page[0] - 53,16,36,0);
	}


					// affichage info individu principal
	$query = 'SELECT * FROM got_'.$_REQUEST['ibase'].'_individu WHERE id_indi = '.$id_indi;
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);
	$sexe = $row[5];
	$pere=$row[14];
	$mere=$row[15];
	
	$this->SetX(47);
	$this->editer_individu($row,'PRINCIP');
	$this->editer_cellule('','RET','');


					// v3.11 edition des autres medias du personnage principal
	$ii = 0;
	$Y_encours = $this->GetY() - 20;
	$nb_ligne = 0;
	while ($row1 = mysqli_fetch_row($result1))
	{	$taille = @getimagesize($row1[0]);
		if ($taille[0] !== NULL)
		{	if ($ii % 4 == 0)
			{	$Y_encours = $Y_encours + 20;
				$nb_ligne++;
			}
			$this->image($row1[0],$this->GetX() + ($ii % 4) * 30,$Y_encours,0,20);
			$ii++;
		}

	}
	$this->SetY($this->GetY() + $nb_ligne * 20);		// on repositionne le position Y pour démarrer correctement l'edition des textes

					// affichage de la note individuelle

	if ($row[13] !== NULL) {$this->editer_cellule ($row[13],'RET','i','NOTE');}
	
					// affichage des évènements associés à l'individu principal : notes, sources et témoins 
	$query = 'SELECT a.type_evene,a.date_evene,a.lieu_evene,a.note_evene, b.id_sour, b.type_sourc,b.attr_sourc
		FROM (got_'.$_REQUEST['ibase'].'_evenement a
		LEFT OUTER JOIN got_'.$_REQUEST['ibase'].'_even_sour b 
		ON a.id_indi = b.id_indi and a.id_husb = b.id_husb and a.id_wife = b.id_wife 
		and a.type_evene = b.type_evene and a.date_evene = b.date_evene and a.dept_evene = b.dept_evene and a.lieu_evene = b.lieu_evene)
		WHERE a.type_evene != "FILE" 
		and a.id_indi = '.$id_indi.'
		ORDER BY 1,2,3';
	$result3 = sql_exec($query,0);
	$old = NULL;
	if (mysqli_num_rows($result3) !== 0)
	{	while ($row3 = mysqli_fetch_row($result3))
		{	if ($row3[0] !== "MARR")			// on ne traite pas les mariages tout de suite (voir plus loin)
			{	if ($row3[0] !== $old and ($row3[3] != NULL or $row3[4] != NULL or $row3[6] != NULL) )		// on affiche l'acte s'il y a une note ou un temoin
				{	$this->editer_cellule($got_tag[$row3[0]],'CONT','u');
					if ($row3[0] != 'BIRT' and $row3[0] != 'DEAT')			// on n'affiche pas la date et le lieu si naiss ou deces car deja affiche
					{	$this->editer_cellule(affichage_date($row3[1]).' '.$row3[2],'RET','i');
					}
					$this->editer_cellule("     ",'CONT','');
					$this->editer_cellule($row3[3],'RET','i');
				}
				if ($row3[5] == "RELA")
				{	$query = 'SELECT * FROM got_'.$_REQUEST['ibase'].'_individu WHERE id_indi = "'.$row3[4].'"';
					$result1 = sql_exec($query);
					$identite = mysqli_fetch_row($result1);
					$this->editer_cellule("     ",'CONT','');
					$this->editer_cellule($got_tag[$row3[6]],'CONT','u');
					$this->editer_cellule(' : ','CONT','');
					$this->editer_individu($identite);
				}
				if ($row3[5] == "SOUR")
				{	$query = 'SELECT note_source 
						FROM got_'.$_REQUEST['ibase'].'_source
						WHERE id_sour = '.$row3[4];
					$result2 = sql_exec($query);
					$source = mysqli_fetch_row($result2);
					$this->editer_cellule('Source : ','CONT','');
					$this->editer_cellule($source[0],'RET','i','NOTE');
				}
				if ($row3[5] == "FILE")		// v3.11 edition des medias des evenements grace à suppression clause where "a.type_evene not like 'MAR%'" and result3
				{	$taille = @getimagesize('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row3[6]);
					if ($taille[0] !== NULL)
					{	$this->image('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row3[6],$this->GetX(),$this->GetY(),0,20);
						$this->SetY($this->GetY() +  20);		// on repositionne le position Y pour démarrer correctement l'edition des textes
					}
				}
			}
			$old = $row3[0];
		}
	}

						// affichage du père
	
	$this->editer_cellule('','RET','','');
	$this->editer_cellule($got_lang['Pere'],'RET','b','STITRE');
	$query = 'SELECT * FROM got_'.$_REQUEST['ibase'].'_individu WHERE id_indi = "'.$pere.'"';
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);
	if ($row[0] != NULL) 
	{	$this->editer_individu($row);
	}

	
				// affichage de la mère
	$this->editer_cellule('','RET','','');
	$this->editer_cellule($got_lang['Mere'],'RET','b','STITRE');
	$query = 'SELECT * FROM got_'.$_REQUEST['ibase'].'_individu WHERE id_indi = "'.$mere.'"';
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);
	if ($row[0] != NULL) {$this->editer_individu($row);}

	
					// affichage des unions
	$this->editer_cellule('','RET','','');
	$query = 'SELECT b.id_indi,b.nom,b.prenom1,b.prenom2,b.prenom3,b.sexe,b.profession,
			a.date_evene,a.lieu_evene,dept_evene,note_evene,b.sosa_dyn,b.sexe,a.id_husb,a.id_wife,a.type_evene
			FROM got_'.$_REQUEST['ibase'].'_evenement a, got_'.$_REQUEST['ibase'].'_individu b 
			WHERE a.type_evene = "MARR" and ';
	if ($sexe == 'M')
	{	$query = $query .' (a.id_wife = b.id_indi) and a.id_husb = '.$id_indi;}
	else
	{	$query = $query .' (a.id_husb = b.id_indi) and a.id_wife = '.$id_indi;}
	$result = sql_exec($query);

	if (mysqli_num_rows($result) != 0) {$this->editer_cellule($got_lang['Union'],'RET','b','STITRE');}

	while ($row = mysqli_fetch_row($result))
	{	if ($row[5] == "F") {$font = 'F';}
		else if ($row[5] == "M") {$font = 'M';}
		else {$row[5] = '';}
		$this->editer_cellule($got_lang['Avec'],'CONT','','');
		$this->editer_cellule($row[2].' '.$row[3].' '.$row[4].' '.$row[1],'CONT',$font,'');
		if ($row[6] != '') {$this->editer_cellule(', '.$row[6],'CONT','','');}
		if ($row[7] != '' or $row[8] != '')
		{	$this->editer_cellule(', '.affichage_date($row[7]),'CONT','b','');
			$this->editer_cellule($got_lang['Situa'].' '.$row[8].'('.$row[9].')','CONT','','');
		}
		$this->editer_cellule('','RET','','');
		if ($row[10] != '')
		{	$this->editer_cellule($row[10],'RET','i','NOTE');			// note du mariage
			$this->editer_cellule('','RET','','');
		}

						// edition des témoins du mariage

		$query = 'SELECT a.id_sour,b.sosa_dyn,b.nom,b.prenom1,b.sexe,a.attr_sourc
		FROM got_'.$_REQUEST['ibase'].'_even_sour a
		INNER JOIN got_'.$_REQUEST['ibase'].'_individu b ON a.id_sour = b.id_indi and a.type_sourc = "RELA"
		WHERE a.id_husb = "'.$row[13].'" and a.id_wife = "'.$row[14].'" 
		and a.type_evene = "'.$row[15].'" and a.date_evene ="'.$row[7].'" and a.dept_evene = "'.$row[9].'" and a.lieu_evene = "'.$row[8].'"
		';
		$result2 = sql_exec($query,0);
		while ($row2 = mysqli_fetch_row($result2))
		{	$query = 'SELECT * FROM got_'.$_REQUEST['ibase'].'_individu WHERE id_indi = "'.$row2[0].'"';
			$result3 = sql_exec($query,0);
			$identite = mysqli_fetch_row($result3);
			$this->editer_cellule("       ","CONT","");
			$this->editer_cellule($got_tag[$row2[5]],'CONT','u');
			$this->editer_cellule(" : ","CONT","");
			$this->editer_individu($identite);
		}

		$id_conjoint = $row[0];
		$query = 'SELECT b.note_source,a.type_evene
				FROM (got_'.$_REQUEST['ibase'].'_even_sour a LEFT OUTER JOIN got_'.$_REQUEST['ibase'].'_source b ON a.id_sour = b.id_sour)
				where ';
		if ($sexe == 'M')
		{	$query = $query .' a.id_husb = '.$id_indi.' and a.id_wife = '.$id_conjoint;}
		else
		{	$query = $query .' a.id_husb = '.$id_conjoint.' and a.id_wife = '.$id_indi;}
		$result2 = sql_exec ($query);
		$row2 = mysqli_fetch_row($result2);
		if ($row2[0] != '')
		{	$this->editer_cellule('Source '.$got_tag[$row2[1]],'RET','u','');
			$this->editer_cellule($row2[0],'RET','i','NOTE');
			$this->editer_cellule('','RET','','');
		}
	}

					// v3.11 on edite les medias du mariage
	if (mysqli_num_rows($result3) !== 0)
	{	mysqli_data_seek($result3,0);
		while ($row3 = mysqli_fetch_row($result3))
		{	if ($row3[0] == "MARR")			// on traite les medias des mariages
			{	if ($row3[5] == "FILE")
				{	$taille = @getimagesize('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row3[6]);
					if ($taille[0] !== NULL)
					{	$this->image('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row3[6],$this->GetX(),$this->GetY(),0,20);
						$this->SetY($this->GetY() +  20);		// on repositionne le position Y pour démarrer correctement l'edition des textes
					}
				}
			}
		}
	}

	if (mysqli_num_rows($result) != 0) {$this->editer_cellule('','RET','','');}

					// affichage des frères et soeurs
	if ($pere == 0) {$pere = 99999999;}	// astuce pour tromper mysql
	if ($mere == 0) {$mere = 99999999;}	// astuce pour tromper mysql
	$query = 'SELECT distinct id_indi,nom,prenom1,prenom2,prenom3,sexe,profession,
		date_naiss,lieu_naiss,dept_naiss,date_deces,lieu_deces,dept_deces,note_indi
		FROM got_'.$_REQUEST['ibase'].'_individu
		WHERE (id_pere = "'.$pere.'" or id_mere = "'.$mere.'")
		and id_indi != '.$id_indi.'
		ORDER BY tri';
	$result = sql_exec($query);
	if (mysqli_num_rows($result) != 0)
	{	$this->editer_cellule(mysqli_num_rows($result).' '.$got_lang['Frere'],'RET','b','STITRE');
	}
	while ($row = mysqli_fetch_row($result))
	{	$this->editer_individu($row);
	}
	if (mysqli_num_rows($result) != 0) {$this->editer_cellule('','RET','','');}

					// affichage des enfants
	$query = 'SELECT id_indi,nom,prenom1,prenom2,prenom3,sexe,profession,
			date_naiss,lieu_naiss,dept_naiss,date_deces,lieu_deces,dept_deces,note_indi
			FROM got_'.$_REQUEST['ibase'].'_individu
		WHERE ';
		if ($sexe == 'M') {$query = $query.' id_pere = ';} else {$query = $query.' id_mere = ';}
		$query = $query.$id_indi.'
		ORDER BY tri';
	$result = sql_exec($query);
	if (mysqli_num_rows($result) != 0)
	{	$this->editer_cellule(mysqli_num_rows($result).' '.$got_lang['Enfan'],'RET','b','STITRE');
	}
	while ($row = mysqli_fetch_row($result))
	{	$this->editer_individu($row);
	}
	if (mysqli_num_rows($result) != 0) {$this->editer_cellule('','RET','','');}

					// affichage des cousins
	$ancetres='';$descendants='';$cpt_generations='';
	$nb_generations = 0;$nb_generations_desc = 2;$relation = $got_lang['PetEf'];
	$this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

	$ancetres='';$descendants='';$cpt_generations='';
	$nb_generations = 2;$nb_generations_desc = 1;$relation = $got_lang['Oncle'];
	$this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

	$ancetres='';$descendants='';$cpt_generations='';
	$nb_generations = 1;$nb_generations_desc = 2;$relation = $got_lang['Neveu'];
	$this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

	$ancetres='';$descendants='';$cpt_generations='';
	$nb_generations = 2;$nb_generations_desc = 2;$relation = $got_lang['Germa'];
	$this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

	$ancetres='';$descendants='';$cpt_generations='';
	$nb_generations = 3;$nb_generations_desc = 1;$relation = $got_lang['OncGr'];
	$this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

	$ancetres='';$descendants='';$cpt_generations='';
	$nb_generations = 3;$nb_generations_desc = 2;$relation = $got_lang['CouGr'];
	$this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

	$ancetres='';$descendants='';$cpt_generations='';
	$nb_generations = 3;$nb_generations_desc = 3;$relation = $got_lang['CouIs'];
	$this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);
}

}

/*************************************** DEBUT DU SCRIPT *********************************************/
$dim_page = recup_dim_page();
$orientation = "P";		// portrait
//echo "pass".$_POST['continu'].'/'.$_POST['fconfirm'].'/'.$_REQUEST['ipag'];

// fiche.php est appele depuis fiche.php, arbre_ascendant.php et arbre_descendant.php. REQUEST[ipag] qui permet de determiner cette orginine.
// car PHP_SELF retourne toujours fiche.php. 

// pointeur de page pour l'annulation de l'edition. Pas terrible, on est incapable de savoir d'ou on vient. A ameliorer.
if ($_REQUEST['ipag'] !== "AD")
{	$page = "arbre_ascendant.php";
} else 
{	$page = "arbre_descendant.php";
}
{
	$timeout = recup_timeout();

	if ($_REQUEST['ipag'] == NULL)
	{			$pdf = new PDF($orientation,'mm',$_REQUEST['forma']);
		$pdf->init_pdf();
		$pdf->editer_fiche($_REQUEST['fid']);
		$pdf->Output();
	} else if ($_REQUEST['ipag'] == 'AA') 
	{	$T_OLD = time();
		$temps_out = FALSE;
		$ancetres[][] = ''; $cpt_generations = 0;
		$ancetres['id_indi'][0] = $_REQUEST['id'];
		recup_ascendance ($ancetres,0,15,'ME_P');	
		if (count($ancetres['id_indi']) <= 50 or ($_POST['continu'] == "OK" and $_POST['fconfirm'] == "OK") )
		{	$pdf = new PDF($orientation,'mm',$_REQUEST['forma']);
			$pdf->init_pdf();
			$temp = $ancetres;			// on est oblige de transferer le tableau, car $ancetres est utilise dans la fonction editer_fiche
			array_multisort ($temp['sosa_d'],$temp['id_indi'],$temp['generation']);
			$ii = 0;
			$pdf->editer_fiche($temp['id_indi'][$ii]);
			while ($temp['id_indi'][$ii] != '' and $temps_out == FALSE) 
			{	$pdf->editer_fiche($temp['id_indi'][$ii]);
				if ($timeout[0] == 'KO' and time() - $T_OLD >= $timeout[1] - 4) {$temps_out = TRUE;}
				$ii++;
			}
			$pdf->Output();
		} else
		{	require_once("menu.php");
			echo '<FORM>';
			echo '<div style="position:absolute; left: 180px; top:200px;">';	// div pour positionner
			echo '<p align=center>'.$got_lang['NbFic'];

			echo 	'<br><input type="submit" name="continu" value="OK">';
			echo	'&nbsp;&nbsp;&nbsp;&nbsp;';
		  echo '<input type="button" value="Annul" onclick="window.location=&quot;arbre_ascendant.php?ibase='.$_REQUEST["ibase"].'&id='.$_REQUEST["id"].'&theme='.$_REQUEST["theme"].'&format='.$_REQUEST['forma'].'&lang='.$_REQUEST["lang"].'&quot;">';
			echo '</p></FORM>';
			echo '</div>';
		}
	} else if ($_REQUEST['ipag'] == 'AD') 
	{	$T_OLD = time();
		$temps_out = FALSE;
		$descendants = '';
		$descendants ['id_indi'][0] = $_REQUEST['id'];
		$cpt_generations_desc = 0;
		$nb_generations_desc = $_REQUEST['nb_gen_desc'];
		recup_descendance ($descendants,0,3,'ME_P',''); // le parametre 3 variable locale n'est pas interprétée. C'est la variable globale $nb_generations_desc qui prend le dessus.

		if (count($descendants['id_indi']) <= 50 or ($_POST['continu'] == "OK" and $_POST['fconfirm'] == "OK"))
		{	$pdf = new PDF($orientation,'mm',$_REQUEST['forma']);
			$pdf->init_pdf();
			$temp = $descendants;	// on est oblige de transferer le tableau, car $descendants est utilise dans la fonction editer_fiche
			array_multisort ($temp['indice'],$temp['id_indi']);
			$i = 0;
			$nb_temp = count($temp['id_indi']);
			while ($i < $nb_temp and $temps_out == FALSE) 
			{	$pdf->editer_fiche($temp['id_indi'][$i]);
				if ($timeout[0] == 'KO' and time() - $T_OLD >= $timeout[1] - 4) {$temps_out = TRUE;}
				$i++; 
			}
			$pdf->Output();
		} else
		{	require_once("menu.php");
			echo '<FORM>';
			echo '<div style="position:absolute; left: 180px; top:200px;">';	// div pour positionner
			echo '<p align=center>'.$got_lang['NbFic'];

			echo 	'<br><input type="submit" name="continu" value="OK">';
			echo	'&nbsp;&nbsp;&nbsp;&nbsp;';
		  echo '<input type="button" value="Annul" onclick="window.location=&quot;arbre_descendant.php?ibase='.$_REQUEST["ibase"].'&id='.$_REQUEST["id"].'&theme='.$_REQUEST["theme"].'&format='.$_REQUEST['forma'].'&lang='.$_REQUEST["lang"].'&quot;">';
			echo '</p></FORM>';
			echo '</div>';
		}
	}
}
?>
