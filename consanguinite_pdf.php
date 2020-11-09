<?php
require_once ("_recup_ascendance.inc.php");
require_once ("_caracteres.inc.php");
require_once ("tfpdf/tfpdf.php");
require_once ("_sql.inc.php");
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
$pool = sql_connect();

class PDF extends TFPDF
{

function editer_bas_rupture()
{	global $degre;
	global $degre_plus;
	global $got_lang;

	$this->SetFont('DejaVu','',8);
	$this->SetTextColor (0,0,0);
	$this->SetFillColor (255);
		
	$degre = $degre - 1;
	if ($degre == 1) {$suffixe = 'er';} else {$suffixe = 'ème';}
	$cell = $got_lang['Cousi']." ".$got_lang['Au']." ";

	if ($degre_plus !== 0)
	{	$temp = $degre - $degre_plus;
		$cell = $cell.$temp."ème ".$got_lang['Degre']." ".$got_lang['Et']." ";
	}
	$cell = $cell.$degre.$suffixe." ".$got_lang['Degre'];

	$this->Cell(0,.2,"",0,1,"L",0);
	$this->Cell(0,3,$cell,0,1,"C",1);
	$this->Cell(0,5,"",0,1,"L",0);
	$this->Line($this->GetX(),$this->GetY(),$this->GetX() + 190,$this->GetY());
	$this->Cell(0,5,"",0,1,"L",0);
}

}

/*************************************** DEBUT DU SCRIPT *********************************************/
$dim_page = recup_dim_page();
$orientation = "P";		// portrait

{	
	recup_consanguinite();
	
	$pdf = new PDF('P','mm',$_REQUEST['forma']);
	$pdf->SetTitle($got_lang['EtCon'].' - '.$res['nom1'][0].' '.$res['prenom1'][0]);
	$pdf->SetCreator('GeneoTree');
	$pdf->SetAuthor('GeneoTree');
	$pdf->SetMargins(18,20);
	$pdf->SetAutoPageBreak(TRUE, 20);
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);

	$entete = trim($got_lang['EtCon']);
	$entete1 = $res['prenom1'][0].' '.$res['nom1'][0];
	$pdf->AddPage();
	
	$degre = 0;
	$degre_plus = 0;
	$ii = 0;
	$row0_old = "";
	while ($ii < count($res['id']) )
	{	if ($res['id'][$ii] !== $row0_old)
		{	if ($row0_old !== NULL)
			{	$pdf->editer_bas_rupture();
			}
			$pdf->SetFont('DejaVu','',8);
			$pdf->SetTextColor (0);
			$pdf->SetFillColor (255);
			$pdf->SetX (95);
			$pdf->Cell(largeur_cellule("Generation ".$res['generation'][$ii],""),3,"Generation ".$res['generation'][$ii],0,1,"L",1);
			$degre = 0;
			$degre_plus = 0;
		}

		$pdf->SetFillColor (255);

		if ($res['id'][$ii] == $row0_old and $row0_old !== NULL)	// traits verticaux
		{	$pdf->Line($pdf->GetX() + 32, $pdf->GetY() , $pdf->GetX() + 32, $pdf->GetY() + 3);
			$pdf->Line($pdf->GetX() + 142, $pdf->GetY() , $pdf->GetX() + 142, $pdf->GetY() + 3);
			$pdf->cell (1,3,"",0,1,"L",1);	// insertion d'une ligne vide avec retour à la ligne
		}

			//edition nom1, prenom1
		if ($res['id'][$ii] == $row0_old and $res['generation'][$ii] !== "FIN")
		{	$pdf->SetX (25);
		} else 
		{	$pdf->SetX (55);
			$pdf->Line($pdf->GetX() - 5, $pdf->GetY() + 3 , $pdf->GetX(), $pdf->GetY() + 3);
			$pdf->Line($pdf->GetX() + 100, $pdf->GetY() + 3 , $pdf->GetX() + 105, $pdf->GetY() + 3);
			if ($res['generation'][$ii] !== "FIN")
			{	$pdf->Line($pdf->GetX() - 5, $pdf->GetY() + 3 , $pdf->GetX() - 5, $pdf->GetY() + 6);
				$pdf->Line($pdf->GetX() + 105, $pdf->GetY() + 3 , $pdf->GetX() + 105, $pdf->GetY() + 6);
			} else
			{	$pdf->Line($pdf->GetX() - 5, $pdf->GetY() + 3 , $pdf->GetX() - 5, $pdf->GetY() + 0);
				$pdf->Line($pdf->GetX() + 105, $pdf->GetY() + 3 , $pdf->GetX() + 105, $pdf->GetY() + 0);
			}
		}
		$pdf->SetFont('DejaVu','',8);
		if ($res['sexe1'][$ii] == "F") {$pdf->SetTextColor (172,2,83);}
		else if ($res['sexe1'][$ii] == "M") {$pdf->SetTextColor (0,0,128);}
		else {$pdf->SetTextColor (0,0,0);}
		$pdf->cell (50,3,$res['nom1'][$ii].' '.$res['prenom1'][$ii],"LTR",0,"C",1);
	
			//edition trait horizontal du 1er couple souche 
		if ($res['id'][$ii] == $row0_old)		
		{	$pdf->SetX($pdf->GetX() + 20);
		}
	
			//edition nom2, prenom2
		if ($res['id'][$ii] == $row0_old and $res['generation'][$ii] !== "FIN")
		{	$pdf->SetX (135);} else {	$pdf->SetX (105);
		}
		$pdf->SetFont('DejaVu','',8);
		if ($res['sexe2'][$ii] == "F") {$pdf->SetTextColor (172,2,83);}
		else if ($res['sexe2'][$ii] == "M") {$pdf->SetTextColor (0,0,128);}
		else {$pdf->SetTextColor (0,0,0);}
		$pdf->cell (50,3,$res['nom2'][$ii].' '.$res['prenom2'][$ii],"LTR",1,"C",1);
	
			//edition date1, lieu1
		if ($res['id'][$ii] == $row0_old and $res['generation'][$ii] !== "FIN")
		{	$pdf->SetX (25);} else {	$pdf->SetX (55);
		}
		$pdf->SetFont('DejaVu','',8);
		$pdf->SetTextColor (0,0,0);
		$pdf->cell (50,3,affichage_date($res['date_naiss1'][$ii]).' '.$res['lieu_naiss1'][$ii],"LBR",0,"C",1);
	
			//edition date2, lieu2
		if ($res['id'][$ii] == $row0_old and $res['generation'][$ii] !== "FIN")
		{	$pdf->SetX (135);} else {	$pdf->SetX (105);
		}
		$pdf->SetFont('DejaVu','',8);
		$pdf->SetTextColor (0,0,0);
		$pdf->cell (50,3,affichage_date($res['date_naiss2'][$ii]).' '.$res['lieu_naiss2'][$ii],"LBR",1,"C",1);
	
		$degre++;
		if ($res['nom1'][$ii] == "" and $res['prenom1'][$ii] == "")
		{	$degre_plus++;
		}

		$row0_old = $res['id'][$ii];
		$nom1 = $res['nom1'][$ii];
		$prenom1 = $res['prenom1'][$ii];
		$nom2 = $res['nom2'][$ii];
		$prenom2 = $res['prenom2'][$ii];
		
		$ii++;
	}

	if ($row0_old !== NULL)
	{	$pdf->editer_bas_rupture();
	}

	$pdf->Output();
}
?>