<?php
require_once ("tfpdf/tfpdf.php");
require_once ("_caracteres.inc.php");
require_once ("_sql.inc.php");
require_once ("_stat.inc.php");
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
$pool = sql_connect();


class PDF extends TFPDF
{
function saut_de_ligne($text,$larg_max)
{	global $dim_page;

	if (largeur_cellule($text,'') + $this->GetX() > $dim_page[0] - 20)	// gestion du changement de ligne
	{	$this->cell (1,3,'',0,1);			// deplacement curseur debut ligne suivante
		$this->SetX($larg_max + 30);		// deplacement curseur milieu de ligne
	}
}

function editer_liste_det($res,$res1,$larg_max)
{	global $got_lang;
/* ATTENTION LARG_MAX faux pour les li A FAIRE */
	$this->SetFillColor(255);
	$i = 0;
	$ii = "";
	while ($i < count($res['col1']) )
	{	$this->Cell (1,3,'',0,1);
		$this->SetFont('Arial','B',8);
		$this->Cell (6,3,$i+1,0,0,"R",1);
		$this->SetTextColor (0,0,128);
		$temp = $res['col1'][$i];
			// afficher les départements uniquement pour li ( alimenter correctement $res)
		if (!isset($res1['dept_naiss'][$ii])) {$res1['dept_naiss'][$ii] = "";}
		if ($res1['dept_naiss'][$ii] != "" and $_REQUEST['ipag'] == "li")
		{	$temp = $temp.'['.$res1['dept_naiss'][$ii].']';
		}
		$this->SetFont('DejaVu','',8);
		$this->Cell ($larg_max,3,$temp,0,0,"L",1);
		$this->SetTextColor (0,0,0);
		$this->SetFont('Arial','B',8);
		$this->Cell (6,3,'['.$res['nb'][$i].']',0,0,"R",1);
		$this->SetFont('DejaVu','',8);
		
		$ii = 0;
		while ($ii < count($res1['col1']) )				// REPORTING < au lieu de <=
		{	
			if ($res1['col1'][$ii] == $res['col1'][$i])
			{	//if ($res1['col2'][$ii] == NULL) {$res1['col2'][$ii] = $got_lang['NonAf'];}
				if ($res1['col2'][$ii] != NULL)
				{	$temp = $res1['col2'][$ii];
					$this->saut_de_ligne($temp,$larg_max);
					$this->Cell (largeur_cellule($temp,''),3,$temp,0,0,"L",1);
						// afficher les départements uniquement pour no et pr
					if ($res1['dept_naiss'][$ii] != NULL and $_REQUEST['ipag'] != "li")
					{	$temp = "(".$res1['dept_naiss'][$ii].")";
						$this->saut_de_ligne($temp,$larg_max);
						$this->Cell (largeur_cellule($temp,''),3,$temp,0,0,"L",1);
					}

					$this->SetFont('Arial','B',8);
					$temp = "[".$res1['nb'][$ii]."]";
					$this->saut_de_ligne($temp,$larg_max);
					$this->Cell (largeur_cellule($temp,''),3,$temp,0,0,"L",1);
					$this->SetFont('DejaVu','',8);

//					if ($res1['anne_debut'][$ii] != NULL or $res1['anne_fin'][$ii] != NULL)
//					{	$temp = $res1['anne_debut'][$ii]."-".$res1['anne_fin'][$ii].",";
//						$this->saut_de_ligne($temp,$larg_max);
//						$this->Cell (largeur_cellule($temp,''),3,$temp,0,0,"L",1);
//					} else
//					{	$this->Cell (1,3,',',0,0);	// afficher la virgule
//					}
				}
			}
			$ii++;
		}
		$i = $i + 1;
	}
}

function editer_liste()
{	global $got_lang;
	global $entete;
	global $entete1;

	switch ($_REQUEST['ipag'])
	{	case 'no' : $entete = $got_lang['LisNo'];
					$colonne = "nom";
					$colonne2 = "lieu_evene";
					break;
		case 'li' : $entete = $got_lang['LisLi'];
					$colonne = "lieu_evene";
					$colonne2 = "nom";
					break;
		case 'de' : $entete = $got_lang['Depar'];
					$colonne = "dept_evene";
					$colonne2 = "nom";
					break;
		case 'pr' : $entete = $got_lang['LisPr'];
					$colonne = "prenom1";
					$colonne2 = "lieu_evene";
					break;
	}
	$entete1 = $got_lang['LisPa'].' Base '.$_REQUEST['ibase'];

	$this->SetTitle($entete);
	$this->SetCreator('GeneoTree');
	$this->SetAuthor('GeneoTree');
	$this->SetMargins(18,20);
	$this->SetAutoPageBreak(TRUE, 20);
	$this->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);

			// calcul de la  largeur max de la 1ere colonne
			//				astuce : on profite de la requête de comptage des 1eres colonnes
	$result = recup_occurrences($_REQUEST['ibase'], $_REQUEST['ipag'],$colonne);

	$larg_max ="";
	while ($row = mysqli_fetch_row($result))
	{	$larg = largeur_cellule($row[0],'');
		if ($larg > $larg_max)
		{	$larg_max = $larg;
		}
		$res['col1'][] = $row[0];
		$res['nb'][] = $row[1];
	}

	$result = recup_eclair($_REQUEST['ibase'], $_REQUEST['ipag'], "", html_entity_decode ('&#65436;', ENT_COMPAT, "UTF-8"),$colonne,$colonne2);	
	while ($row = mysqli_fetch_row($result))
	{	if (!isset($row[3])) {$row[3] = "";}
		$res1['col1'][] = $row[0];
		$res1['col2'][] = $row[1];
		$res1['nb'][] = $row[2];
		$res1['dept_naiss'][] = $row[3];
	}
	array_multisort ($res['nb'], SORT_DESC, $res['col1']);

	$entete1 = $got_lang['LisAl'].' Base '.$_REQUEST['ibase'];
	$this->AddPage();
	$this->SetX (6);
	$this->SetTextColor (0,0,0);
	$this->SetFont('DejaVu','',8);
	// res deja trie par nb desc
	$this->editer_liste_det ($res,$res1,$larg_max);
	
	array_multisort ($res['col1'],$res['nb']);
//	$this->editer_liste_det ($res,$res1,$larg_max);

	$this->Output();
}

}

/*************************************** DEBUT DU SCRIPT *********************************************/
$pool = sql_connect();
$dim_page = recup_dim_page();
$orientation = "P";		// portrait
// on sort systematiquement la liste des individus (nom, prenom, lieu naiss, deces).
if ($_REQUEST['itype'] == NULL)
{	@set_time_limit(120);
	$pdf = new PDF('P','mm',$_REQUEST['forma']);
	$pdf->editer_liste();
}
elseif ($_REQUEST['itype'] == "excel")
{
	header('Content-type:application/vnd.ms-excel');
	header('Content-Transfer-Encoding: binary');
	header('Content-Disposition: attachment; filename="GeneoTree_List_'.$_REQUEST['ibase'].'.csv"');
	
	$query = 'SELECT * FROM got_'.$_REQUEST['ibase'].'_individu';
	$result = sql_exec($query,0);

	$ligne =
	 "id_indi"
	.chr(9)."nom"
	.chr(9)."prenom1"
	.chr(9)."prenom2"
	.chr(9)."prenom3"
	.chr(9)."sexe"
	.chr(9)."occupation"
	.chr(9)."date_birth"
	.chr(9)."lieu_birth"
	.chr(9)."dept_birth"
	.chr(9)."date_death"
	.chr(9)."lieu_death"
	.chr(9)."dept_death"
	.chr(9)."note"
	.chr(10);
	echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');

	while ($row = mysqli_fetch_row($result) )
	{
		$ligne = 
		$row[0]
		.chr(9).$row[1]
		.chr(9).$row[2]
		.chr(9).$row[3]
		.chr(9).$row[4]
		.chr(9).$row[5]
		.chr(9).$row[6]
		.chr(9).$row[7]
		.chr(9).$row[8]
		.chr(9).$row[9]
		.chr(9).$row[10]
		.chr(9).$row[11]
		.chr(9).$row[12]
		.chr(9).$row[13]
		.chr(10);
		echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');
	}
}
?>