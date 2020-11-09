<?php
require_once ("_recup_descendance.inc.php");
require_once ("_caracteres.inc.php");
require_once ("tfpdf/tfpdf.php");
require_once ("_sql.inc.php");
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
$pool = sql_connect();

class PDF extends TFPDF
{

function edition_cellule ($cell,$font)
{			// B : noir gras, F : rose normal, M : bleu normal, '' : noir normal
	global $dim_page;

	if ($font == "F") {$this->SetTextColor (172,2,83);$font = '';}
	else if ($font == "M") {$this->SetTextColor (0,0,128);$font = '';}
	else if ($font == "B" or $font == "") {$this->SetTextColor (0,0,0);}
	else {$this->SetTextColor (0,0,0);$font = '';}
	$this->SetFont('DejaVu','',8);
	$this->SetFillColor (255,255,255);

	$larg_cell = largeur_cellule($cell,$font);
	if ($larg_cell + $this->GetX() > $dim_page[0] - 15)	// gestion du changement de ligne
	{	$this->cell (0,3,'',0,1);			// deplacement curseur debut ligne suivante
		$this->edition_traits_verticaux();
		$this->SetX(90);					// deplacement curseur milieu de ligne
	}
	$this->cell ($larg_cell,3,$cell,0,0,"L",1);		// edition finale de la cellule
}

function edition_traits_verticaux ()
{	global $descendants;
	global $ii;

	$posX = $this->GetX();
	$posY = $this->GetY();		// stockage position no generation
	if ($descendants['niveau'][$ii] != 0)
	{	$indice = 0;
		while ($indice < $descendants['niveau'][$ii])
		{	$this->line ($posX + 6 * $indice,$posY - 2,$posX + 6 * $indice,$posY + 1);		// traits verticaux
			$indice++;
		}
	}
	return;
}

}

/*************************************** DEBUT DU SCRIPT *********************************************/
error_reporting(E_ALL & ~E_NOTICE);    // pas reussi a l'enlever Erreur offset vide dans recup_descendance
$dim_page = recup_dim_page();
$orientation = "P";		// portrait

$OLD_TIME = time();

if ($_REQUEST['itype'] == NULL)
{	
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
//	afficher_descendance();
	
	$entete = $got_lang['ArDes'];
	$entete1 = $descendants['prenom1'][0].' '.$descendants['prenom2'][0].' '.$descendants['prenom3'][0].' '.$descendants['nom'][0];
	
	$pdf = new PDF('P','mm',$_REQUEST['forma']);
	$pdf->SetTitle($descendants['prenom1'][0].' '.$descendants['prenom2'][0].' '.$descendants['prenom3'][0].' '.$descendants['nom'][0].' '.$got_lang['ArDes']);
	$pdf->SetCreator('GeneoTree');
	$pdf->SetAuthor('GeneoTree');
	$pdf->SetMargins(18,20);
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
	$pdf->AddPage();
	$pdf->SetAutoPageBreak(TRUE, 20);

	$nb_descendants = count ($descendants['id_indi']);
	$tota_desce = 0;
	for ($ii = 0; $ii < $nb_descendants; $ii++)
	{
						// affichage indentation
	
		$pdf->SetTextColor (0,0,0);
		$pdf->SetFont('Arial','',8);
	
		$pdf->edition_traits_verticaux();
	
		$posX = $pdf->GetX();
		$posY = $pdf->GetY();					// stockage position no generation
		if ($descendants['niveau'][$ii] != 0)
		{	$pdf->line ($posX + 6 * ($descendants['niveau'][$ii] - 1),$posY + 1,$posX + 6 * ($descendants['niveau'][$ii] - 1) + 3,$posY + 1);			// trait horizontal
		}
		$pdf->cell (($descendants['niveau'][$ii] * 6) + 0.1,3,$descendants['niveau'][$ii] + 1,0,0,"R");		// no generation cadre a droite
	
	
					// affichage nom prenom
		$cell = $descendants['nom'][$ii].' '.$descendants['prenom1'][$ii].' '.$descendants['prenom2'][$ii].' '.$descendants['prenom3'][$ii];
	
		$pdf->edition_cellule ($cell,$descendants['sexe'][$ii]);
					// affichage de la naissance
		if ($descendants['date_naiss'][$ii] != '')
		{	$cell = $got_lang['Ne'];
			if ($got_lang['Langu'] == 'fr' and $descendants['sexe'][$ii] == 'F') {$cell = $cell.'e';}
			$pdf->edition_cellule ($cell,'');

			$cell = affichage_date($descendants['date_naiss'][$ii]);
			$pdf->edition_cellule ($cell,'B');
		}
		if ($descendants['lieu_naiss'][$ii] != "")
		{	$cell = $got_lang['Situa'].' '.$descendants['lieu_naiss'][$ii];
			if ($descendants['dept_naiss'][$ii] != "")
			{	$cell = $cell.' ('.$descendants['dept_naiss'][$ii].')';
			}
			$pdf->edition_cellule ($cell,'');
		}
				// affichage des conjoints
		if (isset($descendants['id_conj'][$ii])) 
		{	$tota_desce++;
			$cell = ', '.$got_lang['Marie'];
			if ($got_lang['Langu'] == 'fr' and $descendants['sexe_conj'][$ii] == 'F') {$cell = $cell.'e';}
			$pdf->edition_cellule ($cell,'');
			if ($descendants['date_maria'][$ii] != "")
			{	$cell = affichage_date($descendants['date_maria'][$ii]);
				$pdf->edition_cellule ($cell,'B');
			}
			if ($descendants['lieu_maria'][$ii] != "")
			{	$cell = $got_lang['Situa'].' '.$descendants['lieu_maria'][$ii]." ";
				if ($descendants['dept_maria'][$ii] != "")
				{	$cell = $cell.'('.$descendants['dept_maria'][$ii].')';
				}
				$pdf->edition_cellule ($cell,'');
			}
			if ($descendants['nom'][$ii] != '' or $descendants['pre1_conj'][$ii] != '')
			{	$cell = $got_lang['Avec'];
				$pdf->edition_cellule ($cell,'');
				$cell = $descendants['nom_conj'][$ii].' '.$descendants['pre1_conj'][$ii].' '.$descendants['pre2_conj'][$ii].' '.$descendants['pre3_conj'][$ii];
				$pdf->edition_cellule ($cell,$descendants['sexe_conj'][$ii]);
			}
		}
				// affichage deces
		if ($descendants['date_deces'][$ii] != "")
		{	$cell = ', '.$got_lang['Deced'];
			if ($got_lang['Langu'] == 'fr' and $descendants['sexe'][$ii] == 'F') {$cell = $cell.'e';}
			$pdf->edition_cellule ($cell,'');
			if ($descendants['date_deces'][$ii] != "")
			{	$cell = affichage_date($descendants['date_deces'][$ii]);
				$pdf->edition_cellule ($cell,'B');
			}
			if ($descendants['lieu_deces'][$ii] != "")
			{	$cell = $got_lang['Situa'].' '.$descendants['lieu_deces'][$ii]." ";
				if ($descendants['dept_deces'][$ii] != "")
				{	$cell = $cell.' ('.$descendants['dept_deces'][$ii].')';
				}
				$pdf->edition_cellule ($cell,'');
			}
			$cell = affichage_age($descendants['date_naiss'][$ii],$descendants['date_deces'][$ii]);
			$pdf->edition_cellule ($cell,'');
		}
	
		$pdf->cell (0,3,'',0,1);			// deplacement du curseur en début de ligne suivante
	}
	$ii = $ii - 1;
	
	$pdf->cell (0,3,'',0,1);			// deplacement du curseur en début de ligne suivante
	$pdf->SetFont('Arial','',8);
	$pdf->cell (largeur_cellule($ii,''),3,$ii,0,0,"L",1);

	$cell = $got_tag['DESC'];
	$pdf->edition_cellule($cell,'');

	$pdf->cell (0,3,'',0,1);			// deplacement du curseur en début de ligne suivante

	$pdf->SetFont('Arial','',8);
	$pdf->cell (largeur_cellule($ii + $tota_desce,''),3,$ii + $tota_desce,0,0,"L",1);

	$cell = $got_tag['DESC'].' '.$got_lang['Avec'].' '.$got_lang['NomCo'].'s';
	$pdf->edition_cellule($cell,'');

	$pdf->Output();
}
elseif ($_REQUEST['itype'] == "excel")
{
	header('Content-type:application/vnd.ms-excel');
	header('Content-Transfer-Encoding: binary');
	header('Content-Disposition: attachment; filename="GeneoTree_Descendancy_'.$_REQUEST['ibase'].'.csv"');
	
	$nb_generations_desc = 40;
	$descendants = '';
	$descendants ['id_indi'] [0] = $_REQUEST['id'];
	$cpt_generations = 0;
	recup_descendance (0,0,0,'ME_G','MARR');	
	
	afficher_descendance(YES);
}
$query = 'DROP TABLE got_'.$ADDRC.'_desc_cles';
sql_exec($query);

?>