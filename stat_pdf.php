<?php
require_once ("tfpdf/tfpdf.php");
require_once ("_caracteres.inc.php");
require_once ("_sql.inc.php");
require_once ("_stat.inc.php");
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
$pool = sql_connect();

class PDF extends TFPDF
{

function editer_stat($result,$X,$Y,$titre,$format = "age")
{	global $got_lang;
	global $entete;
	global $entete1;
	global $theme;

	$this->SetXY($X,$Y);
	$titre1 = mb_substr($titre,0, mb_strpos($titre.' ',' '));
	$titre2 = mb_substr($titre.' ', mb_strpos($titre.' ',' ') - mb_strlen($titre.' ') );
	$this->SetTextColor (0);
	$this->SetFillColor (255);
	$this->SetFont('DejaVu','',8);
	if (trim($titre2) !== NULL)	{$retour = 0;} else {$retour = 2;}
	$this->Cell(largeur_cellule(strtoupper($titre1).' ',''),3,strtoupper($titre1),0,$retour,"L",1);
	if (trim($titre2) !== NULL)	{$this->Cell(0,3,$titre2,0,2,"L",1);}
	$this->SetFont('DejaVu','',8);

	$row = @mysqli_fetch_row($result);
	if (isset($row[0]))	
	{	mysqli_data_seek($result,0);		// remet le pointeur au début pour le fetch
	
		$larg_max = "";
		while ($row = mysqli_fetch_row($result))
		{	$larg = largeur_cellule($row[2].' '.$row[3],'B');
			if ($larg > $larg_max)
			{	$larg_max = $larg;
			}
		}
		mysqli_data_seek($result,0);		// remet le pointeur au début pour le fetch

		$ii = 1;
		while ($row = mysqli_fetch_row($result))
		{	if ($row[6] == "F") {$this->SetTextColor (172,2,83);}
			else if ($row[6] == "M") {$this->SetTextColor (0,0,128);}
			else {$this->SetTextColor (0,0,0);}
	
			$this->SetX($X);
			$this->SetFont('Arial','',8);
			$this->Cell(4,3,$ii,0,0,"R",1);
			$this->SetFont('DejaVu','',8);
			$this->Cell($larg_max,3,$row[2].' '.$row[3],0,0,"L",1);
			$this->SetFont('Arial','',8);
			$this->Cell(9,3,affichage_date($row[4],"ANNEE"),0,0,"L",1);
			if ($format == "age")
			{	$age = nbj2age($row[5]);
				$this->Cell(0,3,$age[0].' '.$got_lang['Annee'].' '.$age[1].' '.$got_lang['Mois'],0,1,"L",1);
			} else 
			{	$this->Cell(0,3,$row[5],0,1,"L",1);
			}
			$ii++;
		}
	}
}

}

/*************************************** DEBUT DU SCRIPT *********************************************/

{	$dim_page = recup_dim_page();
	$orientation = "P";		// paysage

	if ($_REQUEST['isex'] == NULL) {$_REQUEST['isex'] = "_";}
	$date_systeme = getdate();
	$debfin[0] = "";   // REPORTING "" au lieur de 1578 ?
	$debfin[1] = $date_systeme['year'];
	
	$pdf = new PDF($orientation,'mm',$_REQUEST['forma']);
	$entete = $got_lang['PalNo'].' base '.$_REQUEST['ibase'];
	if ($_REQUEST['isex'] == "F")	{$entete1 = $got_lang['Femme'];}
	if ($_REQUEST['isex'] == "M") {$entete1 = $got_lang['Homme'];}
	
	$pdf->SetTitle($entete);
	$pdf->SetCreator('GeneoTree');
	$pdf->SetAuthor('GeneoTree');
	$pdf->SetMargins(18,20);
	$pdf->SetAutoPageBreak(TRUE, 20);
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
	
	$pdf->Addpage();
	$pdf->SetFont('DejaVu','',8);
	$pdf->SetFillColor(255);

	$haut_page = $dim_page[1] - 40;

	switch ($_REQUEST['palma'])
	{case	 15:	$nb_page = 1; $haut_table = floor($haut_page / 3 / 5) - 2; break;
	case	 30:	$nb_page = 2; $haut_table = floor($haut_page / 3 / 4) - 2; break;
	case	 45:	$nb_page = 3; $haut_table = floor($haut_page / 3 / 3) - 2; break;
	case	 75:	$nb_page = 4; $haut_table = floor($haut_page / 3 / 2) - 2; break;
	case	100:	$nb_page = 5; $haut_table = floor($haut_page / 3 / 1) - 2; break;
	}

	for ($ii = 0; $ii < 5; $ii++)
	{	$temp = ($ii / (6 - $nb_page)) * $haut_page + 20;
		if ($temp < $haut_page)
		{	$Y[] = $temp;
		} elseif ($temp < 2 * $haut_page)
		{	$Y[] = $temp - $haut_page;
		} elseif ($temp < 3 * $haut_page)
		{	$Y[] = $temp - 2 * $haut_page;
		} elseif ($temp < 4 * $haut_page)
		{	$Y[] = $temp - 3 * $haut_page;
		} else
		{	$Y[] = $temp - 4 * $haut_page;
		}
	}
//echo $haut_table;
//for ($ii = 0; $ii < 5; $ii++)
//{	echo $ii.': '.$Y[$ii].'<br>';
//}
	$result = recup_deces ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'], $haut_table);
	$pdf->editer_stat ($result,18,$Y[0],$got_lang['StLon']);
	$result = recup_jumeaux($_REQUEST['ibase'], $debfin[0], $debfin[1], $_REQUEST['isex'], $haut_table);
	$pdf->editer_stat ($result,100,$Y[0],$got_lang['Jumea'],"nb");
	
	if ($nb_page == 5 or $nb_page == 5)
	{$pdf->Addpage();}
	
	$result = recup_maries ($_REQUEST['ibase'], $debfin[0], $debfin[1], "asc", $_REQUEST['isex'], $haut_table);
	$pdf->editer_stat ($result,18,$Y[1],$got_lang['StMar'].' '.$got_lang['+jeun']);
	$result = recup_maries ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'], $haut_table);
	$pdf->editer_stat ($result,100,$Y[1],$got_lang['StMar'].' '.$got_lang['+ages']);
	
	if ($nb_page == 4 or $nb_page == 5)
	{$pdf->Addpage();}
	
	$result = recup_noces ($_REQUEST['ibase'], $debfin[0], $debfin[1], "asc", $_REQUEST['isex'], $haut_table);
	$pdf->editer_stat ($result,18,$Y[2],$got_lang['StMar'].' '.$got_lang['MoiLo']);
	$result = recup_noces ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'], $haut_table);
	$pdf->editer_stat ($result,100,$Y[2],$got_lang['StMar'].' '.$got_lang['PluLo']);
	
	if ($nb_page == 3 or $nb_page == 5)
	{$pdf->Addpage();}
	
	$result = recup_parents ($_REQUEST['ibase'], $debfin[0], $debfin[1], "asc", $_REQUEST['isex'], "age", $haut_table);
	$pdf->editer_stat ($result,18,$Y[3],$got_lang['StPar'].' '.$got_lang['+jeun']);
	$result = recup_parents ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'], "age", $haut_table);
	$pdf->editer_stat ($result,100,$Y[3],$got_lang['StPar'].' '.$got_lang['+ages']);
	
	if ($nb_page == 2 or $nb_page == 4 or $nb_page == 5)
	{$pdf->Addpage();}
	
	$result = recup_parents ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'], "nb", $haut_table);
	$pdf->editer_stat ($result,18,$Y[4],$got_lang['StFam'].' '.$got_lang['NbEnf'],"nb");
	$result = recup_parents ($_REQUEST['ibase'], $debfin[0], $debfin[1], "desc", $_REQUEST['isex'], "ecart", $haut_table);
	$pdf->editer_stat ($result,100,$Y[4],$got_lang['StFam'].' '.$got_lang['EcaFS']);
	
	$pdf->Output();
}
?>