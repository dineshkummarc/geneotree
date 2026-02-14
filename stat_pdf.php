<?php
require_once ("tfpdf/tfpdf.php");
require_once ("_sql_requests.php");
require_once  ("_functions.php");
require_once ("languages/".$_REQUEST['lang'].".php");    
$pool = sql_connect();

class PDF extends TFPDF
{
  function editer_stat($result,$X,$Y,$titre,$format = "age")
  { global $got_lang;
    global $entete;
    global $entete1;
    global $theme;

    $this->SetXY($X,$Y);
    $titre1 = mb_substr($titre,0, mb_strpos($titre.' ',' '));
    $titre2 = mb_substr($titre.' ', mb_strpos($titre.' ',' ') - mb_strlen($titre.' '));
    $this->SetTextColor (0);
    $this->SetFillColor (255);
    $this->SetFont('DejaVu','',8);
    if (trim($titre2) !== NULL)    {$retour = 0;} else {$retour = 2;}
    $this->SetFont('DejaVu','B',9);

    $this->Cell(0,3,$titre1.$titre2,0,2);

    $this->SetFont('DejaVu','',8);

    $row = @mysqli_fetch_row($result);
    if (isset($row[0]))     //id_indi
    {    mysqli_data_seek($result,0);        // remet le pointeur au début pour le fetch
    
        $larg_max = "";
        while ($row = mysqli_fetch_row($result))
        {    $larg = $this->GetStringWidth($row[5]); // nom prénom
            if ($larg > $larg_max)
            {    $larg_max = $larg;
            }
        }
        mysqli_data_seek($result,0);        // remet le pointeur au début pour le fetch

        $ii = 1;
        while ($row = mysqli_fetch_row($result))
        {   

            // sex color
            if ($row[2])
            { $SexColor = recup_color_sexe_decimal($row[2]);
              $this->SetTextColor ($SexColor[0],$SexColor[1],$SexColor[2]);
            } else
              $this->SetTextColor (0,0,0);
    
            $this->SetX($X);
            $this->SetFont('Arial','',8);
            $this->Cell(4,3,$ii,0,0,"R",1);
            $this->SetFont('DejaVu','B',8);
            $this->Cell($larg_max,3,$row[5],0,0,"L",1);
            $this->SetFont('Arial','',8);
            $this->Cell(9,3,displayDate($row[6],"ANNEE"),0,0,"L",1); // tri, année
            if ($format == "age")
            {    $age = nbj2age($row[7]); // calcul
                $this->Cell(0,3,$age[0].' '.$got_lang['Annee'].' '.$age[1].' '.$got_lang['Mois'],0,1,"L",1);
            } else 
            {    $this->Cell(0,3,$row[7],0,1,"L",1);
            }
            $ii++;
        }
    }
  }
}

/*************************************** DEBUT DU SCRIPT *********************************************/

{   $orientation = "P";        // paysage

    if ($_REQUEST['sex'] == NULL) {$_REQUEST['sex'] = "_";}
    $date_systeme = getdate();
    $debfin[0] = "";   // REPORTING "" au lieu de 1578 ?
    $debfin[1] = $date_systeme['year'];
    
    $pdf = new PDF($orientation,'mm',recup_format());
    $entete  = $got_lang['PalNo'];
    $entete1 = ' base '.$_REQUEST['ibase'];
    
    $pdf->SetTitle($entete.$entete1);
    $pdf->SetCreator('GeneoTree');
    $pdf->SetAuthor('GeneoTree');
    $pdf->SetMargins(18,20);
    $pdf->SetAutoPageBreak(TRUE, 20);
    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    $pdf->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);
    
    $pdf->Addpage();
    $pdf->SetFont('DejaVu','',8);
    $pdf->SetFillColor(255);

    $haut_page = $pdf->GetPageHeight() - 40;

    $_REQUEST['palma'] = 15;
    switch ($_REQUEST['palma'])
    {
	case     15:    $nb_page = 1; $haut_table = floor($haut_page / 3 / 5) - 2; break;
    case     30:    $nb_page = 2; $haut_table = floor($haut_page / 3 / 4) - 2; break;
    case     45:    $nb_page = 3; $haut_table = floor($haut_page / 3 / 3) - 2; break;
    case     75:    $nb_page = 4; $haut_table = floor($haut_page / 3 / 2) - 2; break;
    case    100:    $nb_page = 5; $haut_table = floor($haut_page / 3 / 1) - 2; break;
    }

    for ($ii = 0; $ii < 5; $ii++)
    {   $temp = ($ii / (6 - $nb_page)) * $haut_page + 20;

        if ($temp < $haut_page)
        {    $Y[] = $temp;
        } elseif ($temp < 2 * $haut_page)
        {    $Y[] = $temp - $haut_page;
        } elseif ($temp < 3 * $haut_page)
        {    $Y[] = $temp - 2 * $haut_page;
        } elseif ($temp < 4 * $haut_page)
        {    $Y[] = $temp - 3 * $haut_page;
        } else
        {    $Y[] = $temp - 4 * $haut_page;
        }
    }

    $result = recup_deces ($debfin[0], $debfin[1]);
    $pdf->editer_stat ($result,18,$Y[0],$got_lang['StLon']);

    $result = recup_jumeaux($debfin[0], $debfin[1]);
    $pdf->editer_stat ($result,100,$Y[0],$got_lang['Jumea'],"nb");

    if ($nb_page == 5 or $nb_page == 5)
    {$pdf->Addpage();}
    
    $result = recup_maries ($debfin[0], $debfin[1], FALSE, "asc");
    $pdf->editer_stat ($result,18,$Y[1],$got_lang['StMar'].' '.$got_lang['+jeun']);
    $result = recup_maries ($debfin[0], $debfin[1], FALSE, "desc");
    $pdf->editer_stat ($result,100,$Y[1],$got_lang['StMar'].' '.$got_lang['+ages']);

    if ($nb_page == 4 or $nb_page == 5)
    {$pdf->Addpage();}
    
    $result = recup_noces ($debfin[0], $debfin[1], FALSE, "asc");
    $pdf->editer_stat ($result,18,$Y[2],$got_lang['StMar'].' '.$got_lang['MoiLo']);

    $result = recup_noces ($debfin[0], $debfin[1], FALSE, "desc");
    $pdf->editer_stat ($result,100,$Y[2],$got_lang['StMar'].' '.$got_lang['PluLo']);

    if ($nb_page == 3 or $nb_page == 5)
    {$pdf->Addpage();}
    
    $result = recup_parents ($debfin[0], $debfin[1], FALSE, "asc", "age");
    $pdf->editer_stat ($result,18,$Y[3],$got_lang['StPar'].' '.$got_lang['+jeun']);

    $result = recup_parents ($debfin[0], $debfin[1], FALSE, "desc", "age");
    $pdf->editer_stat ($result,100,$Y[3],$got_lang['StPar'].' '.$got_lang['+ages']);

    if ($nb_page == 2 or $nb_page == 4 or $nb_page == 5)
    {$pdf->Addpage();}
    
    $result = recup_parents ($debfin[0], $debfin[1], FALSE, "desc", "nb");
    $pdf->editer_stat ($result,18,$Y[4],$got_lang['StFam'].' '.$got_lang['NbEnf'],"nb");
    $result = recup_parents ($debfin[0], $debfin[1], FALSE, "desc", "ecart");
    $pdf->editer_stat ($result,100,$Y[4],$got_lang['StFam'].' '.$got_lang['EcaFS']);
    
    $pdf->Output();
}