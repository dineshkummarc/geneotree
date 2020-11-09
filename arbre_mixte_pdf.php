<?php
require_once ("_recup_ascendance.inc.php");
require_once ("_recup_descendance.inc.php");
require_once ("_caracteres.inc.php");
require_once ("tfpdf/tfpdf.php");
require_once ("_sql.inc.php");
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
$pool = sql_connect();

class PDF extends TFPDF
{

function editer_cellule ($id_indi,$nom,$prenom1,$prenom2,$prenom3,$sexe,$profession,$date_naiss,$lieu_naiss,$date_deces,$lieu_deces,$x,$y,$format)
{	global $got_lang;

	$this->SetLineWidth(0.3);

				// recherche photo
	$query = 'SELECT note_evene 
		FROM got_'.$_REQUEST['ibase'].'_evenement
		WHERE id_indi = '.$id_indi.' and type_evene = "FILE"';
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);

	if ($sexe == "F") {$this->SetTextColor (172,2,83);}
	else if ($sexe == "M") {$this->SetTextColor (0,0,128);}
	else {$this->SetTextColor (0,0,0);}

	$this->SetXY ($x,$y);
	$this->SetFont('DejaVu','',8);
	$this->SetFillColor (255);

	if ($format == 'G')
	{	$largeur_cellule = 40;
		$ligne1 = $nom.' '.$prenom1;
		$ligne2 = $prenom2.' '.$prenom3;
		$ligne3 = $profession;
		$this->SetFont('DejaVu','',8);
		$this->Cell ($largeur_cellule,3,$ligne1,"LTR",2,"C",1);
		$this->SetFont('DejaVu','',8);
		$this->Cell ($largeur_cellule,3,$ligne2,"LR",2,"C",1);
		$this->Cell ($largeur_cellule,3,$ligne3,"LR",2,"C",1);
	} else
	{	$largeur_cellule = 24;
		$ligne1 = $nom;
		$ligne2 = $prenom1;
		$ligne3 = $profession;
		$this->SetFont('DejaVu','',8);
		$this->Cell ($largeur_cellule,3,$ligne1,"LTR",2,"C",1);
		$this->Cell ($largeur_cellule,3,$ligne2,"LR",2,"C",1);
		$this->SetFont('DejaVu','',8);
		$this->Cell ($largeur_cellule,3,$ligne3,"LR",2,"C",1);
	}

	$x = $this->GetX();
	$y = $this->GetY();

	if ($row[0] != '') 
	{	$this->Cell ($largeur_cellule,20,'',"LR",2,"C",1);

		$F = @fopen('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0],"r");
		if ($F != FALSE)
		{			// on bloque la hauteur finale à 20 millimètres
					// on calcule la largeur par rapport aux proportions de l'image
			$caract_image = @getimagesize('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0]);
			$larg_image = $caract_image[0] / $caract_image[1] * 20;
			if ($format == 'G')
			{	$this->Image ('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0], $x + (40 - $larg_image) / 2 , $y, 0,20);
			} else
			{	$this->Image ('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0], $x + (24 - $larg_image) / 2, $y, 0,20);
			}
		}
		if ($date_naiss != NULL)	{$ligne1 = $got_lang['Ne'].' '.affichage_date($date_naiss,"YES");} else {$ligne1 = '';}
		if ($lieu_naiss != NULL)	{$ligne2 = $got_lang['Situa'].' '.$lieu_naiss;} else {$ligne2 = '';}
		$this->Cell ($largeur_cellule,3,$ligne1,"LR",2,"C",1);
		$this->Cell ($largeur_cellule,3,$ligne2,"LBR",2,"C",1);
	} else
	{	if ($date_naiss != NULL)	{$ligne1 = $got_lang['Ne'].' '.affichage_date($date_naiss);} else {$ligne1 = '';}
		if ($lieu_naiss != NULL)	{$ligne2 = $got_lang['Situa'].' '.$lieu_naiss;} else {$ligne2 = '';}
		if ($date_deces != NULL)	{$ligne3 = '+ '.affichage_date($date_deces);} else {$ligne3 = '';}
		if ($lieu_deces != NULL)	{$ligne4 = $got_lang['Situa'].' '.$lieu_deces;} else {$ligne4 = '';}
		$this->Cell ($largeur_cellule,4,'',"LR",2,"C",1);
		$this->Cell ($largeur_cellule,3,$ligne1,"LR",2,"C",1);
		$this->Cell ($largeur_cellule,3,$ligne2,"LR",2,"C",1);
		$this->Cell ($largeur_cellule,3,$ligne3,"LR",2,"C",1);
		$this->Cell ($largeur_cellule,3,$ligne4,"LR",2,"C",1);
		$this->Cell ($largeur_cellule,4,'',"LR",2,"C",1);
		$this->Cell ($largeur_cellule,6,'',"LBR",2,"C",1);
	}


/*	if ($row[0] != '')		// si photo existe, on affiche un résumé pour ne pas prendre trop de place
	{	$cell = $nom.' '.$prenom1.' - '.$profession.
		"\n\n\n\n\n\n\n\n\n";
		if ($date_naiss != '') {$cell = $cell.$got_lang['Ne'].' '.affichage_date($date_naiss);} else {$cell=$cell."\n";}
		if ($lieu_naiss != '') {$cell = $cell.' à '.$lieu_naiss;} else {$cell=$cell."\n\n";}
	} else
	{	$cell = $nom."\n".$prenom1.' '.$prenom2.' '.$prenom3.
		"\n".$profession;
		if ($date_naiss != '') {$cell=$cell."\n\n".$got_lang['Ne']." ".affichage_date($date_naiss);} else {$cell=$cell."\n";}
		if ($lieu_naiss != '') {$cell=$cell."\nà ".$lieu_naiss.')';} else {$cell=$cell."\n\n";}
		if ($date_deces != '') {$cell=$cell."\n\n+ '.affichage_date($date_deces);} else {$cell=$cell."\n";}
		if ($lieu_deces != '') {$cell=$cell."\nà ".$lieu_deces.')';} else {$cell=$cell."\n\n";}
	}*/

}

function pa ($sosa_d)
{	global $ancetres;
	global $got_lang;
	global $dim_page;

	$this->SetLineWidth(0.6);

	$i = array_search($sosa_d,$ancetres['sosa_d']);
	if ($i !== FALSE) 			// si ancetre trouvé
	{	//$this->line(
		$x = recup_pts_mix("pa",$sosa_d);
		if ($sosa_d == 1)
		{	$y = 109;
		}
		if ($sosa_d >= 2 and $sosa_d < 4)
		{	$y = 66;
			$this->line($dim_page[1]/2,105,$dim_page[1]/2,109);
			$this->line($dim_page[1]/2,105,$x + 20,105);
			$this->line($x + 20,105,$x + 20,101);
		}
		if ($sosa_d >= 4 and $sosa_d < 8)
		{	$y = 23;
			if ($sosa_d <=5)	{$xdepart = $dim_page[1]/2 - $dim_page[1]/4;}
			else 	{$xdepart = $dim_page[1]/2 + $dim_page[1]/4;}
			$this->line($x + 20,62,$x + 20,57);		// vert haut
			$this->line($xdepart,62,$x + 20,62);	// horiz
			$this->line($xdepart,62,$xdepart,66);	// vert bas
		}

		$this->editer_cellule ($ancetres['id_indi'][$i],$ancetres['nom'][$i],$ancetres['prenom1'][$i],$ancetres['prenom2'][$i],$ancetres['prenom3'][$i],$ancetres['sexe'][$i],$ancetres['profession'][$i],$ancetres['date_naiss'][$i],$ancetres['lieu_naiss'][$i],$ancetres['date_deces'][$i],$ancetres['lieu_deces'][$i],$x,$y,'G');
	}
}

function pf ($id_fs, $nb_freres, $ii)
{	global $dim_page;

	$this->SetLineWidth(0.6);

	$query = 'SELECT nom,prenom1,sexe,profession,date_naiss,lieu_naiss,dept_naiss,date_deces,lieu_deces,dept_deces
		FROM got_'.$_REQUEST['ibase'].'_individu
		WHERE id_indi = '.$id_fs;
	$result = sql_exec($query,0);
	$row = mysqli_fetch_row($result);

	$y = 109;
	$x = recup_pts_mix("pf",$ii,$nb_freres);

	if ($row[0] != '')
	{	$this->editer_cellule ($id_fs,$row[0],$row[1],'','',$row[2],$row[3],$row[4],$row[5],$row[7],$row[8],$x,$y,'P');
	}
}

function pe ($nb_enfants, $ii)
{	global $descendants;
	global $dim_page;

	$this->SetLineWidth(0.6);

	$y = 152;
	$x = recup_pts_mix ("pe",$ii,$nb_enfants);
	$this->editer_cellule ($descendants['id_indi'][$ii],$descendants['nom'][$ii],$descendants['prenom1'][$ii],$descendants['prenom2'][$ii],$descendants['prenom3'][$ii],$descendants['sexe'][$ii],$descendants['profession'][$ii],$descendants['date_naiss'][$ii],$descendants['lieu_naiss'][$ii],$descendants['date_deces'][$ii],$descendants['lieu_deces'][$ii],$x,$y,'P');

	$this->line($dim_page[1]/2,144.5,$dim_page[1]/2,148);
	$this->line($dim_page[1]/2,148,$x + 12.5,148);
	$this->line($x + 12.5,148,$x + 12.5,151.5);
}

}

/*************************************** DEBUT DU SCRIPT *********************************************/
$dim_page = recup_dim_page();
$orientation = "L";		// paysage

{	
	$ancetres['id_indi'] [0] = $_REQUEST['id'];
	$cpt_generations = 0;
	recup_ascendance ($ancetres,0,2,'ME_G');
//afficher_ascendance();
	$entete = $got_lang['ArMix'];
	$entete1 = $ancetres['prenom1'][0]." ".$ancetres['nom'][0];

	$pdf = new PDF($orientation,'mm',$_REQUEST['forma']);
	$pdf->SetTitle($entete1.' '.$got_lang['ArMix']);
	$pdf->SetCreator('GeneoTree');
	$pdf->SetAuthor('GeneoTree');
	$pdf->SetMargins(18,20);
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
	$pdf->AddPage();

						// edition grands-parents
	$pdf->pa(4);
	$pdf->pa(5);
	$pdf->pa(6);
	$pdf->pa(7);

						// edition parents
	$pdf->pa(2);
	$pdf->pa(3);

						// edition frères et soeurs	
	// les premieres lignes de $ancetre_fs sont par definition celles du personnage central
	// un premier passage pour detecter le nb de freres et soeurs, nécessaire au calcul des emplacements

	if (@array_key_exists($_REQUEST['id'], $ancetres_fs['id_indi']))
	{	$nb_freres = count($ancetres_fs['id_indi'][ $_REQUEST['id'] ]);  // pb si pas de freres et soeurs 
	} else
	{ $nb_freres = 0;
	}

	if ($nb_freres <= 10)	{$max_freres = $nb_freres;} else {$max_freres = 10;}
	for ($ii = 0; $ii < $max_freres; $ii++)
	{	$pdf->pf($ancetres_fs['id_fs'][ $_REQUEST['id'] ][$ii],$max_freres,$ii);
	}
	if ($nb_freres > 10)
	{	$pdf->SetTextColor(0);
		$pdf->SetXY($dim_page[1] - 20,100);
		$pdf->SetFont("Symbol","B","24");
		$pdf->Cell(4,3,chr(222));
	}
						//edition du personnage central
	$pdf->pa(1);

						// edition des enfants
	$descendants = array();
	$descendants ['id_indi'] [0] = $_REQUEST['id'];
	$cpt_generations = 0;
	recup_descendance (0,0,1,'ME_G','');

	$nb_enfants = count($descendants['id_indi']) - 1;	// on enlève le personnage central
	if ($nb_enfants <= 11)	{$max_enfants = $nb_enfants;} else {$max_enfants = 11;}
	for ($ii = 1; $ii <= $max_enfants; $ii++)
	{	$pdf->pe($max_enfants,$ii);
	}
	if ($nb_enfants > 11)
	{	$pdf->SetTextColor(0);
		$pdf->SetXY($dim_page[1] - 20,140);
		$pdf->SetFont("Symbol","B","24");
		$pdf->Cell(4,3,chr(222));
	}
	$pdf->Output();
}
?>