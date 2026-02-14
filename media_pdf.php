<?php
require_once ("tfpdf/tfpdf.php");
require_once  ("_sql_requests.php");
require_once  ("_functions.php");
require_once ("languages/".$_REQUEST['lang'].".php");    
$pool = sql_connect();

class PDF extends TFPDF
{
}

/*************************************** DEBUT DU SCRIPT *********************************************/
if (!isset($_REQUEST['mdeb'])) {$_REQUEST['mdeb'] = "";}
if (!isset($_REQUEST['mfin'])) {$_REQUEST['mfin'] = "";}
if (!isset($_REQUEST['pag'])) {$_REQUEST['pag'] = "";}
if (!isset($_REQUEST['sosa'])) {$_REQUEST['sosa'] = 0;}
if (!isset($_REQUEST['sex'])) {$_REQUEST['sex'] = "_";}
if (!isset($_REQUEST['rech'])) {$_REQUEST['rech'] = "";}


$orientation = "P";        // paysage

{
    $pdf = new PDF('P','mm',recup_format());
    $pdf->SetTitle($got_lang['Media']);
    $pdf->SetCreator('GeneoTree');
    $pdf->SetAuthor('GeneoTree');
    $pdf->SetMargins(18,20);
    $pdf->SetAutoPageBreak(TRUE, 20);
    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);

    $entete = $got_lang['Media'];
    $entete1 = 'Base '.$_REQUEST['ibase'];
    $pdf->AddPage();

    $lettre['deb'][0] = "";
    $lettre['fin'][0] = html_entity_decode ('&#65436;', ENT_COMPAT, "UTF-8");    // init pour passer la clause where
    $result = recup_media($_REQUEST['ibase'],$_REQUEST['pag'],$_REQUEST['mdeb'],$_REQUEST['mfin']);

    $pdf->SetTextColor (0,0,0);
    $pdf->SetFont('DejaVu','',8);
    $pdf->SetFillColor(255);

    $larg_photo = 50;
    $larg_cellu = ($this->GetPageWidth() - 28 - $larg_photo) / 2;
    $haut_cellu = ($this->GetPageHeigth() - 45) / 13;    // 40 -> marges
    $ii= 1;
    while ($row = mysqli_fetch_row($result))
    {    
        if ($ii <= 13) {$posx = 18;} else {$posx = $this->GetPageWidth() / 2;}
        if ($ii == 14) {$pdf->SetXY($posx,24);}

                // edition des images
        $size_image[0] = "";
        $size_image[1] = "";
        $size_fichi = "";
        if (strtolower(mb_substr($row[0],-3)) == 'jpg' or strtolower(mb_substr($row[0],-3)) == 'png')
        {    $MyThumb = MyUrl('picture/'.$_REQUEST['ibase'].'/'.$row[0],FALSE);
            $MyFile = MyUrl('picture/'.$_REQUEST['ibase'].'/'.$row[0],FALSE);
            $fp = @fopen($MyFile, 'rb');
            if ($fp == TRUE)        // fichier trouve
            { $size_fichi = filesize($MyFile);
              $size_image = @getimagesize($MyThumb);
              if ($size_image[2] == 2)
              { if ($size_image[0] < $size_image[1]) //i.e largeur inferieur a hauteur => portrait
                {    $pdf->Image($MyThumb,$posx + $larg_cellu,$pdf->GetY(),$haut_cellu * $size_image[0] / $size_image[1],$haut_cellu - 1);
                } else
                {    $pdf->Image($MyThumb,$posx + $larg_cellu,$pdf->GetY(),22,22  * $size_image[1] / $size_image[0]);
                }
              }
            }
        }

        $pdf->SetFont('Arial','I',8);
        $pdf->Cell ($larg_cellu,3,'File : "'.$row[0].'"',"LTR",2,"L",1);
        $pdf->Cell ($larg_cellu,3,$size_image[0].'x'.$size_image[1].', '.$size_fichi.' octets',"LR",2,"L",1);
        $size_image = NULL;
        $pdf->SetFont('Arial','',8);

        // sex color
        if ($row[6] )
        { $SexColor = recup_color_sexe_decimal($row[6] );
          $pdf->SetTextColor ($SexColor[0],$SexColor[1],$SexColor[2]);
        } else
          $pdf->SetTextColor (0,0,0);

        if ($row[1] == 'FILE')
        {    $pref_evene = 'Fiche '.$got_lang['De'];
        } else
        {    $pref_evene = $got_tag[$row[1]].' '.$got_lang['De'];
        }

        $pdf->SetFont('DejaVu','',8);
        $pdf->Cell ($larg_cellu,3,$pref_evene.' '.$row[4].' '.$row[3],"LR",2,"L",1);
        $pdf->SetFont('Arial','',8);

        $pdf->SetTextColor (0,0,0);
        if ($row[5] != 0)        // sosa_dyn
        {    $pdf->SetFont('Arial','B',8);
            $pdf->Cell ($larg_cellu,3,$got_lang["Sosa"]." n° ".$row[5],"LR",2,"L",1);
            $pdf->SetFont('Arial','',8);
        } else
        {    $pdf->Cell ($larg_cellu,3,"","LR",2,"L",1);
        }

        $pdf->Cell ($larg_cellu,$haut_cellu - 12,"","LBR",2,"L",1);

        if ($ii >= 26)
        {    $pdf->AddPage();
            $ii = 0;
        }
        $ii++;
    }
    $pdf->Output();
}