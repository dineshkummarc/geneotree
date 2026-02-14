<?php
require_once ("_get_descendancy.php");
require_once ("tfpdf/tfpdf.php");
require_once  ("_functions.php");
require_once ("languages/".$_REQUEST['lang'].".php");    
$pool = sql_connect();

class PDF extends TFPDF
{

function edition_cellule ($cell,$font,$sexe ='')
{            // B : noir gras, F : rose normal, M : bleu normal, '' : noir normal

    $this->SetFont('DejaVu',$font,8);
    
	// sex color
    if ($sexe)
	{ $SexColor = recup_color_sexe_decimal($sexe);
      $this->SetTextColor ($SexColor[0],$SexColor[1],$SexColor[2]);
	} else
      $this->SetTextColor (0,0,0);

    $this->SetFillColor (255,255,255);

    $larg_cell = $this->GetStringWidth($cell) + 0.8;
    if ($larg_cell + $this->GetX() > $this->GetPageWidth() - 15)    // gestion du changement de ligne
    {    $this->cell (0,3,'',0,1);            // deplacement curseur debut ligne suivante
        $this->edition_traits_verticaux();
        $this->SetX(90);                    // deplacement curseur milieu de ligne
    }
    $this->cell ($larg_cell,3,$cell,0,0,"L",1);        // edition finale de la cellule
}

function edition_traits_verticaux ()
{    global $descendants;
    global $ii;

    $posX = $this->GetX();
    $posY = $this->GetY();        // stockage position no generation
    if ($descendants['generation'][$ii] != 0)
    {    $indice = 0;
        while ($indice < $descendants['generation'][$ii])
        {    $this->line ($posX + 6 * $indice,$posY - 2,$posX + 6 * $indice,$posY + 1);        // traits verticaux
            $indice++;
        }
    }
    return;
}

}

/*************************************** DEBUT DU SCRIPT *********************************************/
error_reporting(E_ALL & ~E_NOTICE);    // pas reussi a l'enlever Erreur offset vide dans recup_descendance
$orientation = "P";        // portrait

$OLD_TIME = time();

if ($_REQUEST['type'] == NULL)
{    
    $nb_generations_desc = $_REQUEST['pag'] - 1;
    $descendants = array();
    $descendants ['id_indi'] [0] = $_REQUEST['id'];
    $cpt_generations = 0;
    recup_descendance (0,0,0,'ME_G','MARR');    
// print_r2($descendants);
    array_multisort (
    $descendants['indice']
    ,$descendants['id_indi']
    ,$descendants['generation']
    ,$descendants['nom']
    ,$descendants['prenom2']
    ,$descendants['sexe']
    ,$descendants['profession']
    ,$descendants['date_naiss']
    ,$descendants['lieu_naiss']
    ,$descendants['date_deces']
    ,$descendants['lieu_deces']
    ,$descendants['id_parent']
    ,$descendants['sosa_d']
    ,$descendants['id_conj']   
    ,$descendants['date_maria']
    ,$descendants['lieu_maria']
    ,$descendants['nom_conj']  
    ,$descendants['pre2_conj'] 
    ,$descendants['sexe_conj'] 
    ,$descendants['sosa_conj']);        
// afficher_descendance();
    
    $entete = $got_lang['ArDes'];
    $entete1 = $descendants['nom'][0].' '.$descendants['prenom2'][0];
    
    $pdf = new PDF('P','mm',recup_format());
    $pdf->SetTitle($descendants['nom'][0].' '.$descendants['prenom2'][0].' '.$got_lang['ArDes']);
    $pdf->SetCreator('GeneoTree');
    $pdf->SetAuthor('GeneoTree');
    $pdf->SetMargins(18,20);
    $pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    $pdf->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);
    $pdf->AddPage();
    $pdf->SetAutoPageBreak(TRUE, 20);

    $pdf->cell(0,3,"",0,1);

    $nb_descendants = count ($descendants['id_indi']);
    $tota_desce = 0;
    for ($ii = 0; $ii < $nb_descendants; $ii++)
    {
                        // affichage indentation
    
        $pdf->SetTextColor (0,0,0);
        $pdf->SetFont('Arial','',8);

        $pdf->edition_traits_verticaux();
    
        $posX = $pdf->GetX();
        $posY = $pdf->GetY();                    // stockage position no generation
        if ($descendants['generation'][$ii] != 0)
        {    $pdf->line ($posX + 6 * ($descendants['generation'][$ii] - 1),$posY + 1,$posX + 6 * ($descendants['generation'][$ii] - 1) + 3,$posY + 1);            // trait horizontal
        }
        $pdf->cell (($descendants['generation'][$ii] * 6) + 0.1,3,$descendants['generation'][$ii] + 1,0,0,"R");        // no generation cadre a droite
    
    
                    // affichage nom prenom
        $cell = $descendants['nom'][$ii].' '.$descendants['prenom2'][$ii];
    
        $pdf->edition_cellule ($cell,'B',$descendants['sexe'][$ii]);
                    // affichage de la naissance
        if ($descendants['date_naiss'][$ii] != '')
        {    $cell = $got_lang['Ne'];
            if ($got_lang['Langu'] == 'fr' and $descendants['sexe'][$ii] == 'F') {$cell = $cell.'e';}
            $pdf->edition_cellule ($cell,'');

            $cell = displayDate($descendants['date_naiss'][$ii]);
            $pdf->edition_cellule ($cell,'B');
        }
        if ($descendants['lieu_naiss'][$ii] != "")
        {   $cell = $got_lang['Situa'].' '.$descendants['lieu_naiss'][$ii];
            $pdf->edition_cellule ($cell,'');
        }
                // affichage des conjoints
        if (isset($descendants['id_conj'][$ii])) 
        {   $tota_desce++;
            $cell = ', '.$got_lang['Marie'];
            if ($got_lang['Langu'] == 'fr' and $descendants['sexe'][$ii] == 'F') {$cell = $cell.'e';}
            $pdf->edition_cellule ($cell,'');
            if ($descendants['date_maria'][$ii] != "")
            {    $cell = displayDate($descendants['date_maria'][$ii]);
                $pdf->edition_cellule ($cell,'B');
            }
            if ($descendants['lieu_maria'][$ii] != "")
            {   $cell = $got_lang['Situa'].' '.$descendants['lieu_maria'][$ii]." ";
                $pdf->edition_cellule ($cell,'');
            }
            if ($descendants['nom_conj'][$ii] != '' or $descendants['pre2_conj'][$ii] != '')
            {   $cell = $got_lang['Avec'];
                $pdf->edition_cellule ($cell,'');
                $cell = $descendants['nom_conj'][$ii].' '.$descendants['pre2_conj'][$ii];
                $pdf->edition_cellule ($cell,'B',$descendants['sexe_conj'][$ii]);
            }
        }
                // affichage deces
        if ($descendants['date_deces'][$ii] != "")
        {   $cell = ', '.$got_lang['Deced'];
            if ($got_lang['Langu'] == 'fr' and $descendants['sexe'][$ii] == 'F') {$cell = $cell.'e';}
            $pdf->edition_cellule ($cell,'');
            if ($descendants['date_deces'][$ii] != "")
            {    $cell = displayDate($descendants['date_deces'][$ii]);
                $pdf->edition_cellule ($cell,'B');
            }
            if ($descendants['lieu_deces'][$ii] != "")
            {   $cell = $got_lang['Situa'].' '.$descendants['lieu_deces'][$ii]." ";
                $pdf->edition_cellule ($cell,'');
            }
            $cell = displayAge($descendants['date_naiss'][$ii],$descendants['date_deces'][$ii]);
            $pdf->edition_cellule ($cell,'');
        }
    
        $pdf->cell (0,3,'',0,1);            // deplacement du curseur en d�but de ligne suivante
    }
    $ii = $ii - 1;
    
    $pdf->Cell (0,3,'',0,1);            // deplacement du curseur en d�but de ligne suivante
    $pdf->SetFont('DejaVu','B',10);
    $pdf->SetTextColor(0);
    $pdf->MultiCell (0,4,$ii." ".$got_tag['DESC'],0,"L",1);
    $pdf->MultiCell (0,4,$tota_desce." ".$got_lang['NomCS'],0,"L",1);

    $pdf->Output();
}
elseif ($_REQUEST['type'] == "excel")
{
    header('Content-type:application/vnd.ms-excel');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="GeneoTree_Descendancy_'.$_REQUEST['ibase'].'.csv"');
    
    $nb_generations_desc = 40;
    $descendants[] =array();
    $descendants ['id_indi'] [0] = $_REQUEST['id'];
    $cpt_generations = 0;
    recup_descendance (0,0,0,'ME_G','MARR');    
    
    afficher_descendance("YES");
}
$query = 'DROP TABLE IF EXISTS '.$sql_pref.'_'.$ADDRC.'_desc_cles';
sql_exec($query);