<?php
require_once ("_sql.inc.php");
require_once ("tfpdf/tfpdf.php");
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
$pool = sql_connect();

class PDF extends TFPDF {

function Circle($x,$y,$r,$style='')
{
	$this->Ellipse($x,$y,$r,$r,$style);
}

function Ellipse($x,$y,$rx,$ry,$style='D')
{
	if($style=='F')
		$op='f';
	elseif($style=='FD' or $style=='DF')
		$op='B';
	else
		$op='S';
	$lx=4/3*(M_SQRT2-1)*$rx;
	$ly=4/3*(M_SQRT2-1)*$ry;
	$k=$this->k;
	$h=$this->h;
	$this->_out(sprintf('%.2f %.2f m %.2f %.2f %.2f %.2f %.2f %.2f c',
		($x+$rx)*$k,($h-$y)*$k,
		($x+$rx)*$k,($h-($y-$ly))*$k,
		($x+$lx)*$k,($h-($y-$ry))*$k,
		$x*$k,($h-($y-$ry))*$k));
	$this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
		($x-$lx)*$k,($h-($y-$ry))*$k,
		($x-$rx)*$k,($h-($y-$ly))*$k,
		($x-$rx)*$k,($h-$y)*$k));
	$this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c',
		($x-$rx)*$k,($h-($y+$ly))*$k,
		($x-$lx)*$k,($h-($y+$ry))*$k,
		$x*$k,($h-($y+$ry))*$k));
	$this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c %s',
		($x+$lx)*$k,($h-($y+$ry))*$k,
		($x+$rx)*$k,($h-($y+$ly))*$k,
		($x+$rx)*$k,($h-$y)*$k,
		$op));
}
}

/********************************* DEBUT DU SCRIPT *******************************/

$query = 'SELECT commune, dept, nb, longitude, latitude, x, y, x_mm, y_mm 
		FROM got_'.$_REQUEST["addrc"].'_commcarte 
		ORDER BY nb DESC';
$result = sql_exec($query,0);

$_REQUEST['ititre'] = @mb_ereg_replace('_',' ',$_REQUEST['ititre']);

$pdf=new PDF('P','mm',$_REQUEST['forma']);
//$pdf->Open();
$pdf->SetTitle($got_lang['CarTi'].' - '.$_REQUEST['ititre']);
$pdf->SetCreator('GeneoTree');
$pdf->SetAuthor('GeneoTree');
$pdf->SetMargins(18,20);
$pdf->SetAutoPageBreak(TRUE, 20);
$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);

					// Affichage carte
$entete = $_REQUEST["ititre"];
$entete1 = $got_lang["Carte"];
$pdf->AddPage();
$pdf->SetFont('Arial','',7);
$pdf->MultiCell (0,3,"Detail page 2",0,"C");
$pdf->Image('geo/'.$_REQUEST['carte'].'.jpg',14, 26);
$pdf->SetFillColor(0,0,0);
while ($row = mysqli_fetch_row($result))
{	if      ($row[2] >= 1 and $row[2] <= 10) {$taille = 1;}
	else if ($row[2] >= 11 and $row[2] <= 20) {$taille = 2;}
	else if ($row[2] >= 21 and $row[2] <= 30) {$taille = 3;}
	else if ($row[2] >= 31 and $row[2] <= 40) {$taille = 4;}
	else if ($row[2] >= 41 and $row[2] <= 50) {$taille = 5;}
	else if ($row[2] >= 51 and $row[2] <= 60) {$taille = 6;}
	else if ($row[2] >= 61) {$taille = 7;}
//	$pdf->Circle($row[7]+14,$row[8]+26,$taille,"F");
	$pdf->Circle($row[7],$row[8],$taille,"F");  // correction pas normale. La librairie fpdf n'a pas l'air très précise (les coordonnées sont correctes en javascript).
}

					// Affichage liste par fréquence

$entete1 = $got_lang['TriFr'];
$pdf->AddPage();
$pdf->SetFont('DejaVu','',10);
$pdf->Cell(70,5,$got_lang["Ville"],0,0,"C");
$pdf->SetFont('Times','',10);
$pdf->Cell(20,5,$got_lang["Nombr"],0,0,"C");
$pdf->Cell(20,5,$got_lang["Longi"],0,0,"C");
$pdf->Cell(20,5,$got_lang["Latit"],0,0,"C");
$pdf->Ln();
$pdf->SetFont('Times','',10);

mysqli_data_seek($result,0);		// remet le pointeur au début pour le fetch

while (	$row = mysqli_fetch_row($result) )
{	$pdf->SetFont('DejaVu','',10);
	$pdf->Cell(70,3,$row [0].' ('.$row [1].')');
	$pdf->SetFont('Times','',10);
	$pdf->Cell(20,3,$row [2],0,0,"R");
	$pdf->Cell(20,3,round($row [3],2),0,0,"R");
	$pdf->Cell(20,3,round($row [4],2),0,0,"R");
	$pdf->Ln();
}

					// Affichage liste alphabétique

$query = 'SELECT  commune, dept, nb, longitude, latitude, x, y, x_mm, y_mm 
					FROM got_'.$_REQUEST['addrc'].'_commcarte 
					ORDER BY commune';
$result = sql_exec($query);

$entete1 = $got_lang['TriAl'];
$pdf->AddPage();
$pdf->SetFont('DejaVu','',10);
$pdf->Cell(70,5,$got_lang["Ville"],0,0,"C");
$pdf->SetFont('Times','',10);
$pdf->Cell(20,5,$got_lang["Nombr"],0,0,"C");
$pdf->Cell(20,5,$got_lang["Longi"],0,0,"C");
$pdf->Cell(20,5,$got_lang["Latit"],0,0,"C");
$pdf->Ln();
$pdf->SetFont('Times','',10);

while (	$row = mysqli_fetch_row($result) )
{	$pdf->SetFont('DejaVu','',10);
	$pdf->Cell(70,3,$row [0].' ('.$row [1].')');
	$pdf->SetFont('Times','',10);
	$pdf->Cell(20,3,$row [2],0,0,"R");
	$pdf->Cell(20,3,round($row [3],2),0,0,"R");
	$pdf->Cell(20,3,round($row [4],2),0,0,"R");
	$pdf->Ln();
}


$query = 'DROP TABLE got_'.$_REQUEST['addrc'].'_commcarte';
sql_exec($query);

$pdf->Output();

?>
