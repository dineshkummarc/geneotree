<?php
mb_internal_encoding('UTF-8');
require_once ("_get_ascendancy.php");
require_once ("tfpdf/tfpdf.php");
require_once  ("_functions.php");
require_once ("languages/".$_REQUEST['lang'].".php");
$pool = sql_connect();

class PDF extends TFPDF
{

function editer_bas_rupture()
{ global $degre;
  global $degre_plus;
  global $got_lang;
  global $got_tag;

  $this->SetFont('DejaVu','',8);
  $this->SetTextColor (0,0,0);
  $this->SetFillColor (255);

  if ($degre !=0)        
  {  $degre = $degre - 1;

    if ($got_lang['Langu'] == "en" AND $degre == 2) {$got_lang['DegrS']="nd";}
    if ($got_lang['Langu'] == "en" AND $degre == 3) {$got_lang['DegrS']="rd";}
    $cell = $got_lang['Cousi']." ".$got_lang['Au']." ";

    if ($degre_plus !== 0)
    {   $temp = $degre - $degre_plus;
        $cell = $cell.$temp.$got_lang['DegrS']." ".$got_lang['Degre']." ".$got_tag['AND']." ";
    }
    $cell = $cell.$degre.$got_lang['DegrS']." ".$got_lang['Degre'];

    $this->Cell(0,.2,"",0,1,"L",0);
    $this->Cell(0,3,$cell,0,1,"C",1);
    $this->Cell(0,3,"",0,1,"L",0);
    // $this->Line($this->GetX(),$this->GetY(),$this->GetX() + 170,$this->GetY());
    $this->Cell(0,3,"",0,1,"L",0);
  }
}

}

/*************************************** DEBUT DU SCRIPT *********************************************/
$orientation = "P";        // portrait

{    
    recup_consanguinite();
    
    $pdf = new PDF('P','mm',recup_format());
    $pdf->SetTitle($got_lang['EtCon'].' - '.$res['nom1'][0]);
    $pdf->SetCreator('GeneoTree');
    $pdf->SetAuthor('GeneoTree');
    $pdf->SetMargins(18,20);
    $pdf->SetAutoPageBreak(TRUE, 20);
    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    $pdf->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);

    $entete = trim($got_lang['EtCon']);
    $entete1 = $res['nom1'][0];
    $pdf->AddPage();
    
    $pdf->Cell(0,3,"",0,1,"L",0);
    
    $degre = 0;
    $degre_plus = 0;
    $ii = 0;
    $row0_old = "";
    while ($ii < count($res['id']))
    {    if ($res['id'][$ii] !== $row0_old)
        {    if ($row0_old !== NULL)
            {    $pdf->editer_bas_rupture();
            }
            $pdf->SetFont('DejaVu','',8);
            $pdf->SetTextColor (0);
            $pdf->SetFillColor (255);
            $pdf->SetX (95);
            $pdf->SetFont('DejaVu','B',8);
            $pdf->Cell($pdf->GetStringWidth($got_lang['Gener']." ".$res['generation'][$ii],""),3,$got_lang['Gener']." ".$res['generation'][$ii],0,1,"L",1);
            $pdf->SetFont('DejaVu','',8);
            $pdf->Cell(0,1,"",0,1,"L",0);
            $degre = 0;
            $degre_plus = 0;
        }

        $pdf->SetFillColor (255);

        if ($res['id'][$ii] == $row0_old and $row0_old !== NULL)    // traits verticaux
        {   $pdf->Line($pdf->GetX() + 32, $pdf->GetY() , $pdf->GetX() + 32, $pdf->GetY() + 3);
            $pdf->Line($pdf->GetX() + 142, $pdf->GetY() , $pdf->GetX() + 142, $pdf->GetY() + 3);
            $pdf->cell (1,3,"",0,1,"L",1);    // insertion d'une ligne vide avec retour à la ligne
        }

            //edition nom1, prenom1
        if ($res['id'][$ii] == $row0_old and $res['generation'][$ii] !== "FIN")
        {    $pdf->SetX (25);
        } else 
        {   $pdf->SetX (55);
            $pdf->Line($pdf->GetX() - 5, $pdf->GetY() + 3 , $pdf->GetX(), $pdf->GetY() + 3);
            $pdf->Line($pdf->GetX() + 100, $pdf->GetY() + 3 , $pdf->GetX() + 105, $pdf->GetY() + 3);
            if ($res['generation'][$ii] !== "FIN")
            {    $pdf->Line($pdf->GetX() - 5, $pdf->GetY() + 3 , $pdf->GetX() - 5, $pdf->GetY() + 6);
                $pdf->Line($pdf->GetX() + 105, $pdf->GetY() + 3 , $pdf->GetX() + 105, $pdf->GetY() + 6);
            } else
            {    $pdf->Line($pdf->GetX() - 5, $pdf->GetY() + 3 , $pdf->GetX() - 5, $pdf->GetY() + 0);
                $pdf->Line($pdf->GetX() + 105, $pdf->GetY() + 3 , $pdf->GetX() + 105, $pdf->GetY() + 0);
            }
        }
        $pdf->SetFont('DejaVu','',8);

        $SexColor = recup_color_sexe_decimal($res['sexe1'][$ii]);
        $pdf->SetTextColor ($SexColor[0],$SexColor[1],$SexColor[2]);

        $pdf->cell (50,3,$res['nom1'][$ii],"LTR",0,"C",1);
    
            //edition trait horizontal du 1er couple souche 
        if ($res['id'][$ii] == $row0_old)        
        {    $pdf->SetX($pdf->GetX() + 20);
        }
    
            //edition nom2, prenom2
        if ($res['id'][$ii] == $row0_old and $res['generation'][$ii] !== "FIN")
        {    $pdf->SetX (135);} else {    $pdf->SetX (105);
        }
        $pdf->SetFont('DejaVu','',8);

        $SexColor = recup_color_sexe_decimal($res['sexe2'][$ii]);
        $pdf->SetTextColor ($SexColor[0],$SexColor[1],$SexColor[2]);

        $pdf->cell (50,3,$res['nom2'][$ii],"LTR",1,"C",1);
    
            //edition date1, lieu1
        if ($res['id'][$ii] == $row0_old and $res['generation'][$ii] !== "FIN")
        {    $pdf->SetX (25);} else {    $pdf->SetX (55);
        }
        $pdf->SetFont('DejaVu','',8);
        $pdf->SetTextColor (0,0,0);
        $pdf->cell (50,3,displayDate($res['date_naiss1'][$ii]).' '.$res['lieu_naiss1'][$ii],"LBR",0,"C",1);
    
            //edition date2, lieu2
        if ($res['id'][$ii] == $row0_old and $res['generation'][$ii] !== "FIN")
        {    $pdf->SetX (135);} else {    $pdf->SetX (105);
        }
        $pdf->SetFont('DejaVu','',8);
        $pdf->SetTextColor (0,0,0);
        $pdf->cell (50,3,displayDate($res['date_naiss2'][$ii]).' '.$res['lieu_naiss2'][$ii],"LBR",1,"C",1);
    
        $degre++;
        if ($res['nom1'][$ii] == "")
        {    $degre_plus++;
        }

        $row0_old = $res['id'][$ii];
        $nom1 = $res['nom1'][$ii];
        $nom2 = $res['nom2'][$ii];
        
        $ii++;
    }

    if ($row0_old !== NULL)
    {    $pdf->editer_bas_rupture();
    }

    $pdf->Output();
}