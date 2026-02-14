<?php
require_once ("_get_ascendancy.php");
require_once ("_get_descendancy.php");
require_once ("tfpdf/tfpdf.php");
require_once  ("_functions.php");
require_once ("languages/".$_REQUEST['lang'].".php");    
$pool = sql_connect();

class PDF extends TFPDF
{

function recup_pts_mix($type,$numero,$total = NULL)
{
    if ($type == "pa")
    {    if ($numero == 1)
        {    $x = $this->GetPageWidth()/2 - 20;
        }
        if ($numero >= 2 and $numero < 4)
        {    $x = $this->GetPageWidth()/4*((($numero-1)*2)-1)-20;
        }
        if ($numero >= 4 and $numero < 8)
        {    $x = $this->GetPageWidth()/8*((($numero-3)*2)-1)-20;
        }
    }

    if ($type == "pf")
    {    $larg = $this->GetPageWidth()/2 - 21;    // on travaille sur un peu moins de la moitié de la largeur de page
        $temp = floor(($total+1)/2);                // nb d'individu maxi sur une moitié de page
        $x = (((($numero + 1)/$temp) -(1 / $temp / 2))* $larg) - 11;
        if (($numero + 1) > $temp)
        {    $x = $x + 41;                        // quand on travaille sur la moitié droite, on décale à droite
        }
    }

    if ($type == "pe")
    {    $x = (((($numero) / $total) -(1 / $total / 2))* $this->GetPageWidth()) - 12.5;
    }

    return $x;
}

function editer_cellule ($id_indi,$nom,$prenom2,$sexe,$profession,$date_naiss,$lieu_naiss,$date_deces,$lieu_deces,$x,$y,$format)
{   global $got_lang;
    global $sql_pref;

    $this->SetLineWidth(0.3);

                // recherche photo
    $query = 'SELECT note_evene 
        FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement`
        WHERE id_indi = '.$id_indi.' and type_evene = "FILE"';
    $result = sql_exec($query);
    $row = sql_fetch_row($result);

	// sex color
    if ($sexe)
	{ $SexColor = recup_color_sexe_decimal($sexe);
      $this->SetTextColor ($SexColor[0],$SexColor[1],$SexColor[2]);
	} else
      $this->SetTextColor (0,0,0);


    $this->SetXY ($x,$y);
    $this->SetFont('DejaVu','',8);
    $this->SetFillColor (255);

    if ($format == 'G')
    {   $larg_cellule = 40;
        $ligne1 = $nom;
        $ligne2 = $prenom2;
        $ligne3 = $profession;
        $this->SetFont('DejaVu','B',8);
        $this->Cell ($larg_cellule,3,$ligne1,"LTR",2,"C",1);
        $this->SetFont('DejaVu','',8);
        $this->Cell ($larg_cellule,3,$ligne2,"LR",2,"C",1);
        $this->Cell ($larg_cellule,3,$ligne3,"LR",2,"C",1);
    } else
    {   $larg_cellule = 24;
        $ligne1 = $nom;
        $ligne2 = $prenom2;
        $ligne3 = $profession;
        $this->SetFont('DejaVu','B',8);
        $this->Cell ($larg_cellule,3,$ligne1,"LTR",2,"C",1);
        $this->Cell ($larg_cellule,3,$ligne2,"LR",2,"C",1);
        $this->SetFont('DejaVu','',8);
        $this->Cell ($larg_cellule,3,$ligne3,"LR",2,"C",1);
    }

    $x = $this->GetX();
    $y = $this->GetY();

    if ($row) 
    {    $this->Cell ($larg_cellule,20,'',"LR",2,"C",1);

        $MyUrl = MyUrl('picture/'.$_REQUEST['ibase'].'/'.$row[0], FALSE);
        $F = @fopen($MyUrl,"rb");
        if ($F != FALSE)
        {           // on bloque la hauteur finale à 20 millimètres
                    // on calcule la largeur par rapport aux proportions de l'image
          $caract_image = @getimagesize($MyUrl);
          if ($caract_image[2] == 2) // test identique à tfpf ligne 1481
          { $rapport = $caract_image[0] / $caract_image[1];
            if ($rapport > 1) {$hauteur_image = 20 / $rapport;} else {$hauteur_image = 20;}
            if ($format == 'G')
            {    $this->Image ($MyUrl, $x + 10, $y, 0, $hauteur_image);
            } else
            {    $this->Image ($MyUrl, $x + 5, $y, 0, $hauteur_image);
            }
          }
        }
        if ($date_naiss != NULL)    {$ligne1 = $got_lang['Ne'].' '.displayDate($date_naiss,"YES");} else {$ligne1 = '';}
        if ($lieu_naiss != NULL)    {$ligne2 = $got_lang['Situa'].' '.$lieu_naiss;} else {$ligne2 = '';}
        $this->Cell ($larg_cellule,3,$ligne1,"LR",2,"C",1);
        $this->Cell ($larg_cellule,3,$ligne2,"LBR",2,"C",1);
    } else
    {    if ($date_naiss != NULL)    {$ligne1 = $got_lang['Ne'].' '.displayDate($date_naiss);} else {$ligne1 = '';}
        if ($lieu_naiss != NULL)    {$ligne2 = $got_lang['Situa'].' '.$lieu_naiss;} else {$ligne2 = '';}
        if ($date_deces != NULL)    {$ligne3 = '+ '.displayDate($date_deces);} else {$ligne3 = '';}
        if ($lieu_deces != NULL)    {$ligne4 = $got_lang['Situa'].' '.$lieu_deces;} else {$ligne4 = '';}
        $this->Cell ($larg_cellule,4,'',"LR",2,"C",1);
        $this->Cell ($larg_cellule,3,$ligne1,"LR",2,"C",1);
        $this->Cell ($larg_cellule,3,$ligne2,"LR",2,"C",1);
        $this->Cell ($larg_cellule,3,$ligne3,"LR",2,"C",1);
        $this->Cell ($larg_cellule,3,$ligne4,"LR",2,"C",1);
        $this->Cell ($larg_cellule,4,'',"LR",2,"C",1);
        $this->Cell ($larg_cellule,6,'',"LBR",2,"C",1);
    }


/*    if ($row[0] != '')        // si photo existe, on affiche un r�sum� pour ne pas prendre trop de place
    {    $cell = $nom.' '.$prenom1.' - '.$profession.
        "\n\n\n\n\n\n\n\n\n";
        if ($date_naiss != '') {$cell = $cell.$got_lang['Ne'].' '.displayDate($date_naiss);} else {$cell=$cell."\n";}
        if ($lieu_naiss != '') {$cell = $cell.' � '.$lieu_naiss;} else {$cell=$cell."\n\n";}
    } else
    {    $cell = $nom."\n".$prenom1.' '.$prenom2.' '.$prenom3.
        "\n".$profession;
        if ($date_naiss != '') {$cell=$cell."\n\n".$got_lang['Ne']." ".displayDate($date_naiss);} else {$cell=$cell."\n";}
        if ($lieu_naiss != '') {$cell=$cell."\n� ".$lieu_naiss.')';} else {$cell=$cell."\n\n";}
        if ($date_deces != '') {$cell=$cell."\n\n+ '.displayDate($date_deces);} else {$cell=$cell."\n";}
        if ($lieu_deces != '') {$cell=$cell."\n� ".$lieu_deces.')';} else {$cell=$cell."\n\n";}
    }*/

}

function pa ($sosa_d)
{   global $ancetres;
    global $got_lang;

    $this->SetLineWidth(0.6);

    $i = array_search($sosa_d,$ancetres['sosa_d']);
    if ($i !== FALSE)             // si ancetre trouv�
    {    //$this->line(
        $x = $this->recup_pts_mix("pa",$sosa_d);
        if ($sosa_d == 1)
        {   $y = 111;
        }
        if ($sosa_d >= 2 and $sosa_d < 4)
        {   $y = 68;
            $this->line($this->GetPageWidth()/2,107,$this->GetPageWidth()/2,111);
            $this->line($this->GetPageHeight()/2,107,$x + 20,107);
            $this->line($x + 20,107,$x + 20,103);
        }
        if ($sosa_d >= 4 and $sosa_d < 8)
        {   $y = 25;
            if ($sosa_d <=5)    {$xdepart = $this->GetPageWidth()/2 - $this->GetPageWidth()/4;}
            else     {$xdepart = $this->GetPageWidth()/2 + $this->GetPageWidth()/4;}
            $this->line($x + 20,64,$x + 20,59);     // vert haut
            $this->line($xdepart,64,$x + 20,64);    // horiz
            $this->line($xdepart,64,$xdepart,68);   // vert bas
        }

        $this->editer_cellule ($ancetres['id_indi'][$i],$ancetres['nom'][$i],$ancetres['prenom2'][$i],$ancetres['sexe'][$i],$ancetres['profession'][$i],$ancetres['date_naiss'][$i],$ancetres['lieu_naiss'][$i],$ancetres['date_deces'][$i],$ancetres['lieu_deces'][$i],$x,$y,'G');
    }
}

function pf ($id_fs, $nb_freres, $ii)
{    global $sql_pref;

    $this->SetLineWidth(0.6);

    $query = 'SELECT
               CONCAT(nom," ",prenom1)
              ,CONCAT(prenom2," ",prenom3)
	          ,sexe
			  ,profession
	          ,date_naiss
			  ,CASE WHEN dept_naiss != "" THEN CONCAT(lieu_naiss, " (", dept_naiss, ")") ELSE lieu_naiss END
			  ,date_deces
			  ,CASE WHEN dept_deces != "" THEN CONCAT(lieu_deces, " (", dept_deces, ")") ELSE lieu_deces END
        FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu`
        WHERE id_indi = '.$id_fs;
    $result = sql_exec($query,0);
    $row = mysqli_fetch_row($result);

    $y = 111;
    $x = $this->recup_pts_mix("pf",$ii,$nb_freres);

    if ($row[0] != '')
    {    $this->editer_cellule ($id_fs,$row[0],$row[1],$row[2],$row[3],$row[4],$row[5],$row[6],$row[7],$x,$y,'P');
    }
}

function pe ($nb_enfants, $ii)
{    global $descendants;

    $this->SetLineWidth(0.6);

    $y = 154;
    $x = $this->recup_pts_mix ("pe",$ii,$nb_enfants);
    $this->editer_cellule ($descendants['id_indi'][$ii],$descendants['nom'][$ii],$descendants['prenom2'][$ii],$descendants['sexe'][$ii],$descendants['profession'][$ii],$descendants['date_naiss'][$ii],$descendants['lieu_naiss'][$ii],$descendants['date_deces'][$ii],$descendants['lieu_deces'][$ii],$x,$y,'P');

    $this->line($this->GetPageWidth()/2,146.5,$this->GetPageWidth()/2,150);
    $this->line($this->GetPageHeight()/2,150,$x + 12.5,150);
    $this->line($x + 12.5,150,$x + 12.5,153.5);
}

}

/*************************************** DEBUT DU SCRIPT *********************************************/
$orientation = "L";        // paysage

$ancetres['id_indi'] [0] = $_REQUEST['id'];
$cpt_generations = 0;
recup_ascendance ($ancetres,0,2,'ME_G');
// afficher_ascendance(); //debug

$entete = $got_lang['ArMix'];
$entete1 = $ancetres['nom'][0];

$pdf = new PDF($orientation,'mm',recup_format());
$pdf->SetTitle($entete1.' '.$got_lang['ArMix']);
$pdf->SetCreator('GeneoTree');
$pdf->SetAuthor('GeneoTree');
$pdf->SetMargins(18,20);
$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
$pdf->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);
$pdf->AddPage();

// grands-parents
$pdf->pa(4);
$pdf->pa(5);
$pdf->pa(6);
$pdf->pa(7);

// parents
$pdf->pa(2);
$pdf->pa(3);

// frères et soeurs
  // les premieres lignes de $ancetre_fs sont par definition celles du personnage central
  // un premier passage pour detecter le nb de freres et soeurs, nécessaire au calcul des emplacements
if ($ancetres_fs)
{
  if (array_key_exists($_REQUEST['id'], $ancetres_fs['id_indi']))
  { $nb_freres = count($ancetres_fs['id_indi'][ $_REQUEST['id'] ]);  // pb si pas de freres et soeurs 
  } else
  { $nb_freres = 0;
  }

  if ($nb_freres <= 10)
    $max_freres = $nb_freres;
  else
    $max_freres = 10;

  for ($ii = 0; $ii < $max_freres; $ii++)
    $pdf->pf($ancetres_fs['id_fs'][ $_REQUEST['id'] ][$ii],$max_freres,$ii);
  
  if ($nb_freres > 10)
  { $pdf->SetTextColor(0);
    $pdf->SetXY($pdf->GetPageHeight() - 20,100);
    // $pdf->SetFont("Symbol","B","24");    // a am�liorer. La font symbol avec tfpdf utf8
    $pdf->Cell(4,3,chr(222));
  }
}

// personnage central
$pdf->pa(1);

// enfants
$descendants = array();
$descendants ['id_indi'] [0] = $_REQUEST['id'];
$cpt_generations = 0;
recup_descendance (0,0,1,'ME_G','');
$query = 'DROP TABLE IF EXISTS '.$sql_pref.'_'.$ADDRC.'_desc_cles';
sql_exec($query);

$nb_enfants = count($descendants['id_indi']) - 1;    // on enlève le personnage central
if ($nb_enfants <= 11)    {$max_enfants = $nb_enfants;} else {$max_enfants = 11;}
for ($ii = 1; $ii <= $max_enfants; $ii++)
{ $pdf->pe($max_enfants,$ii);
}
if ($nb_enfants > 11)
{ $pdf->SetTextColor(0);
  $pdf->SetXY($pdf->GetPageHeight() - 20,140);
  // $pdf->SetFont("Symbol","B","24");
  $pdf->Cell(4,3,chr(222));
}

$pdf->Output();
