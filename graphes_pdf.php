<?php
require_once ("fpdf/fpdf.php");
require_once ("_caracteres.inc.php");
require_once ('_graphes.inc.php');
require_once ("_sql.inc.php");
require_once ("_stat.inc.php");
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
$pool = sql_connect();


class PDF extends FPDF
{	function saut_de_ligne($text,$larg_max)
	{	if (largeur_cellule($text,'') + $this->GetX() > 200)	// gestion du changement de ligne
		{	$this->cell (0,3,'',0,1);			// deplacement curseur debut ligne suivante
			$this->SetX($larg_max + 25);		// deplacement curseur milieu de ligne
		}
	}
}

/*************************************** DEBUT DU SCRIPT *********************************************/
$dim_page = recup_dim_page();
$orientation = "P";		// portrait

{	
	$pdf = new PDF('P','mm',$_REQUEST['forma']);
	$colonne = 'nom';
	
	switch ($_REQUEST['ipag'])
	{	case 'no' : $entete = $got_lang['Noms']; break;
		case 'li' : $entete = $got_lang['Lieux']; break;
		case 'pr' : $entete = $got_lang['Preno']; break;
		case 'de' : $entete = $got_lang['Depar']; break;
	}
	$entete = $entete.' - Base '.$_REQUEST['ibase'];
	
	$pdf->SetTitle($entete);
	$pdf->SetCreator('GeneoTree');
	$pdf->SetAuthor('GeneoTree');
	$pdf->SetMargins(18,20);
	$pdf->SetAutoPageBreak(TRUE, 20);

	$pdf->AddPage();

  $debfin = recup_intervalle($_REQUEST['ibase'], $_REQUEST['ipag'], $_REQUEST['ideb'], $_REQUEST['intervalle'], $_REQUEST['sens']); // donne ideb et ifin en sortie

	$res = recup_palmares($_REQUEST['ibase'], $_REQUEST['ipag'], $debfin[0], $debfin[1]);

	bar_horiz ($res['nom'],$res['nb'],$entete,$entete1,'temp',$_REQUEST['palma'],YES);

	$pdf->Image('temp'.'.png',18,20);
	unlink('temp'.'.png');

	$dfin = $debfin[1];
	$ii = 0;
	while ($debfin[0] < $dfin)
	{	$debfin = recup_intervalle($_REQUEST['ibase'], $_REQUEST['ipag'], $debfin[0], $_REQUEST['intervalle'], "plus"); // donne ideb et ifin en sortie
		$res = recup_palmares($_REQUEST['ibase'], $_REQUEST['ipag'], $debfin[0], $debfin[1]);

		$entete1 = $debfin[0]." - ".$debfin[1];

		if ($debfin[0] == '""')	
		{	$suf = '000';
		} else 
		{	$suf = $debfin[0];
		}	// gestion du changement du contenu de ideb par recup_palmares

		bar_horiz ($res['nom'],$res['nb'],$entete,$entete1,'temp'.$suf,$_REQUEST['palma'],YES);
	
		if ($_REQUEST['palma'] == 15)
		{	if ($ii % 3 == 0)
			{	$pdf->AddPage();
				$Y = 25;
			} elseif ($ii % 3 == 1)
			{	$Y = 105;
			} else
			{	$Y = 185;
			}
		} else
		{	$pdf->AddPage();
			$Y = 25;
		}
		$pdf->Image('temp'.$suf.'.png',18,$Y);
		unlink('temp'.$suf.'.png');

		$debfin[0] = $debfin[1];
		$ii++;
	}
	$pdf->Output();
}
?>