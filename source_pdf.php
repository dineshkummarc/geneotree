<?php
require_once ("tfpdf/tfpdf.php");
require_once ("_sql.inc.php");
require_once ("_caracteres.inc.php");
require_once  ("_stat.inc.php");
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
$pool = sql_connect();

class PDF extends TFPDF
{
}

/*************************************** DEBUT DU SCRIPT *********************************************/
$dim_page = recup_dim_page();
$orientation = "P";		// paysage

if (!isset($_REQUEST['spag'])) {$_REQUEST['spag'] = "BIRT";}
if (!isset($_REQUEST['sosa'])) {$_REQUEST['sosa'] = "Tous";}
if (!isset($_REQUEST['ibase2'])) {$_REQUEST['ibase2'] = "";}
if (!isset($_REQUEST['ldeb'])) {$_REQUEST['ldeb'] = 0;}
if (!isset($_REQUEST['sdeb'])) {$_REQUEST['sdeb'] = "";}
if (!isset($_REQUEST['sfin'])) {$_REQUEST['sfin'] = "";}
if (!isset($_REQUEST['pere'])) {$_REQUEST['pere'] = "";}
if (!isset($_REQUEST['mere'])) {$_REQUEST['mere'] = "";}
if (!isset($_REQUEST['prenom'])) {$_REQUEST['prenom'] = "";}
if (!isset($_REQUEST['lieu'])) {$_REQUEST['lieu'] = "";}
if (!isset($_REQUEST['sosa'])) {$_REQUEST['sosa'] = "";}
if (!isset($_REQUEST['iautr'])) {$_REQUEST['iautr'] = "";}
if (!isset($_REQUEST['nom_wife'])) {$_REQUEST['nom_wife'] = "";}
if (!isset($_REQUEST['pre_husb'])) {$_REQUEST['pre_husb'] = "";}
if (!isset($_REQUEST['pre_wife'])) {$_REQUEST['pre_wife'] = "";}
if (!isset($_REQUEST['scont'])) {$_REQUEST['scont'] = "";}
if (!isset($_REQUEST['ideb'])) {$_REQUEST['ideb'] = "";}
if (!isset($_REQUEST['ifin'])) {$_REQUEST['ifin'] = "";}

$match = explode (" ",$_REQUEST['scont']);

if ($_REQUEST['itype'] == 'pdf')
{
	$pdf = new PDF('P','mm',$_REQUEST['forma']);
//	$pdf->SetTitle($row[4].' '.$row[5].' '.$row[6].' '.$row[3].' '.$got_lang['ArDes']);
	$pdf->SetCreator('GeneoTree');
	$pdf->SetAuthor('GeneoTree');
	$pdf->SetMargins(18,20);
	$pdf->SetAutoPageBreak(TRUE, 20);
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);

	$entete1 = 'Base '.$_REQUEST['ibase'];
	$pdf->AddPage();
	$pdf->SetX (10);

	$result = recup_source ($_REQUEST['ibase'], $match, 0, 999999999);
	
		// preparation de l'edition
	$pdf->SetTextColor (0,0,0);
	$pdf->SetFillColor (255);
	$pdf->SetFont('Arial','',8);

	$id_sour_old	= '';
	while ($row = mysqli_fetch_row($result[0]))
	{	if ($row[0] != $id_sour_old)
		{	$pdf->Cell (0,3,'',0,1,"R","L",1);
			$pdf->MultiCell(0,3,mb_ereg_replace(chr(13), "\n",$row[0]),1,"J",1);
		}
	
		$text = $got_tag[$row[6]].' : '.$row[2].' '.$row[3].' '.$row[4].' '.$row[5];
		if ($row[9] == "F") {$pdf->SetTextColor (172,2,83);}
		else if ($row[9] == "M") {$pdf->SetTextColor (0,0,128);}
		else {$pdf->SetTextColor (0,0,0);}
		$pdf->SetFont('DejaVu','',8);
		$pdf->Cell (0,3,$text,0,1,"R","L",1);
		$pdf->SetFont('DejaVu','',8);
		$pdf->SetTextColor (0,0,0);
	
		$id_sour_old = $row[0];
	}
		// on edite
	$pdf->Output();
}
elseif ($_REQUEST['itype'] == "excel")
{
	
	header('Content-type:application/vnd.ms-excel');
	header('Content-Transfer-Encoding: binary');
	header('Content-Disposition: attachment; filename="GeneoTree_Sources_'.$_REQUEST['spag'].'.csv"');


	if ($_REQUEST['spag'] == "Sourc" or $_REQUEST['spag'] == "NOTE")
	{	
		$ligne = 
		        "note source"
		.chr(9)."type source"
		.chr(9)."name"
		.chr(9)."prenom1"
		.chr(9)."prenom2"
		.chr(9)."prenom3"
		.chr(10);
		echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');

//		$match = array('');
		$result = recup_source($_REQUEST['ibase'], $match, 0, 999999999, 'NOTE');  // on recupere toutes les sources, dans Excel c'est mieux

		while ($row = mysqli_fetch_row($result[0]) )
		{$ligne = 
			 $row[0]
			.chr(9).$row[6]
			.chr(9).$row[5]
			.chr(9).$row[2]
			.chr(9).$row[3]
			.chr(9).$row[4]
			.chr(10);
			echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');
		}

		$result = recup_source($_REQUEST['ibase'], '', 0, 999999999, 'Sourc');  // on recupere toutes les sources, dans Excel c'est mieux

		while ($row = mysqli_fetch_row($result[0]) )
		{$ligne = 
			 $row[0]
			.chr(9).$row[6]
			.chr(9).$row[5]
			.chr(9).$row[2]
			.chr(9).$row[3]
			.chr(9).$row[4]
			.chr(10);
			echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');
		}
	}

	if ($_REQUEST['spag'] != "Sourc" and $_REQUEST['spag'] != "NOTE")
	{
		$ligne =  "type_evene"
		.chr(9)."date_even"
		.chr(9)."lieu_evene"
		.chr(9)."dept_evene"
		.chr(9)."nom_indi"
		.chr(9)."prenom1_indi"
		.chr(9)."prenom2_indi"
		.chr(9)."prenom3_indi"
		.chr(9)."sexe_indi"
		.chr(9)."nom_pere"
		.chr(9)."prenom1_pere"
		.chr(9)."prenom2_pere"
		.chr(9)."prenom3_pere"
		.chr(9)."nom_mere"
		.chr(9)."prenom1_mere"
		.chr(9)."prenom2_mere"
		.chr(9)."prenom3_mere"
		.chr(9)."note_even"
		.chr(10);
		echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');

		$result_evene = recup_source_evene($_REQUEST['ibase'], $_REQUEST['ibase2'], '0', '999999', '', '', '', '', '', $_REQUEST['spag'], $_REQUEST['iautr'], 0, 999999999, 'ALL');		// alimentation de $result_evene

		while ($row = mysqli_fetch_row($result_evene[0]))
		{	$ligne =  
			$row[18]
			.chr(9).$row[1]
			.chr(9).$row[2]
			.chr(9).$row[3]
			.chr(9).$row[5]
			.chr(9).$row[6]
			.chr(9).$row[7]
			.chr(9).$row[8]
			.chr(9).$row[9]
			.chr(9).$row[10]
			.chr(9).$row[11]
			.chr(9).$row[12]
			.chr(9).$row[13]
			.chr(9).$row[14]
			.chr(9).$row[15]
			.chr(9).$row[16]
			.chr(9).$row[17]
			.chr(9).$row[19]
			.chr(10);
			echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');
		}

		$result_evene = recup_source_marr($_REQUEST['ibase'], $_REQUEST['ibase2'], '', '', '', '', '0', '999999', 0, 999999999);

		while ($row = mysqli_fetch_row($result_evene[0]))
		{	$ligne = 
			"MARR"
			.chr(9).$row[0]
			.chr(9).$row[1]
			.chr(9).$row[2]
			.chr(9).""
			.chr(9).""
			.chr(9).""
			.chr(9).""
			.chr(9).""
			.chr(9).$row[5]
			.chr(9).$row[6]
			.chr(9).$row[7]
			.chr(9).$row[8]
			.chr(9).$row[12]
			.chr(9).$row[13]
			.chr(9).$row[14]
			.chr(9).$row[15]
			.chr(9).$row[20]
			.chr(10);
			echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');
		}
	}
}
?>