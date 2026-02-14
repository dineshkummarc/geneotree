<?php
require_once ("tfpdf/tfpdf.php");
require_once ("_sql_requests.php");
require_once  ("_functions.php");
require_once ("languages/".$_REQUEST['lang'].".php");    
$pool = sql_connect();


class PDF extends TFPDF
{

function editer_liste(){
	global $got_lang;
    global $entete;
    global $entete1;

    switch ($_REQUEST['pag'])
    {   case 'no' : $entete = $got_lang['LisNo'];  break;
        case 'li' : $entete = $got_lang['LisLi'];  break;
        case 'de' : $entete = $got_lang['Depar'];  break;
        case 'pr' : $entete = $got_lang['LisPr'];  break;
    }

    $entete1 = $got_lang['LisPa'].' '.$got_lang['Bases'].' '.$_REQUEST['ibase'];

    $this->SetTitle($entete);
    $this->SetCreator('GeneoTree');
    $this->SetAuthor('GeneoTree');
    $this->SetMargins(18,20);
    $this->SetAutoPageBreak(TRUE, 20);
    $this->SetFont('Arial','B',8);

    $result = recup_eclair("","","");

    // width of column 1
    $larg_col1 = 1;
    while ($row = sql_fetch_row($result))
    {   $larg_cell = $this->GetStringWidth($row[5]);
        if ($larg_cell > $larg_col1)
        {   $larg_col1 = $larg_cell;
        }
    }
    mysqli_data_seek($result,0);
    if ($larg_col1 > 70) {$larg_col1 = 70;}

    // width of column 2
    $larg_col2 = $this->GetPageWidth() - $larg_col1 - 35;

    $entete1 = $got_lang['LisAl'].' '.$got_lang['Bases'].' '.$_REQUEST['ibase'];
    $this->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    $this->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);
    $this->AddPage();
    $this->SetTextColor (0,0,0);
    $this->SetFillColor(255);

    $this->cell (1,3,'',0,1);            // deplacement curseur debut ligne suivante
    $ii=0;
    while ($row = mysqli_fetch_row($result))
    {
      // change color of interline
      if ($ii % 2 == 0) {$this->SetFillColor(255);} else {$this->SetFillColor(215,234,247);}

      // colonne 1
      $this->SetFont('DejaVu','B',8);
      $this->SetTextColor (0,0,128);
      $this->Cell ($larg_col1,3,$row[5],0,0,"L",true);

      // column 2 [nb]
      $this->SetFont('DejaVu','',8);
      $this->SetTextColor (0,0,0);
      $this->Cell (6,3,'['.$row[6].']',0,0,"R",true);

      // colonne 3
      $this->MultiCell ($larg_col2,3,$row[7],0,"L",true);

      $ii++;
    }

    $this->Output();
}

} 

/*************************************** DEBUT DU SCRIPT *********************************************/
$pool = sql_connect();
$orientation = "P";        // portrait
// on sort systematiquement la liste des individus (nom, prenom, lieu naiss, deces).
if ($_REQUEST['exp'] == "pdf")
{    @set_time_limit(120);
    $pdf = new PDF('P','mm',recup_format())
	
	;
    $pdf->editer_liste();
}
elseif ($_REQUEST['exp'] == "excel")
{
    header('Content-type:application/vnd.ms-excel');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="GeneoTree_List_'.$_REQUEST['ibase'].'.csv"');
    
    $query = '
	  SELECT 
	   CONCAT(a.nom, " ", a.prenom1) as nom
	  ,CONCAT(a.prenom2, " ", a.prenom3) as prenom
	  ,a.sexe
	  ,a.profession
	  ,a.date_naiss
	  ,a.lieu_naiss
	  ,a.dept_naiss
	  ,a.date_deces
	  ,a.lieu_deces
	  ,a.dept_deces
	  ,a.note_indi
	  ,CONCAT(b.nom, " ", b.prenom1) as nom_pere
	  ,b.profession as prof_pere
	  ,CONCAT(c.nom, " ", c.prenom1) as nom_mere
	  ,c.profession as prof_mere
	  FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` a
	  LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` b ON (a.id_pere = b.id_indi)
	  LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` c ON (a.id_mere = c.id_indi)
	  ORDER BY nom, prenom
	  ';
    $result = sql_exec($query,0);

    $ligne =
            $got_tag["NAME"]
    .chr(9).$got_lang["Preno"]
    .chr(9).$got_tag["SEX"]
    .chr(9).$got_tag["OCCU"]
    .chr(9).$got_tag["DATE"]." ".$got_tag["BIRT"]
    .chr(9).$got_tag["PLAC"]." ".$got_tag["BIRT"]
    .chr(9).$got_lang["Depar"]." ".$got_tag["BIRT"]
    .chr(9).$got_tag["DATE"]." ".$got_tag["DEAT"]
    .chr(9).$got_tag["PLAC"]." ".$got_tag["DEAT"]
    .chr(9).$got_tag["DATE"]." ".$got_tag["DEAT"]
    .chr(9).$got_tag["NAME"]." ".$got_lang['Pere']
    .chr(9).$got_tag["OCCU"]." ".$got_lang['Pere']
    .chr(9).$got_tag["NAME"]." ".$got_lang['Mere']
    .chr(9).$got_tag["OCCU"]." ".$got_lang['Pere']
    .chr(9).$got_tag["NOTE"]
    .chr(10);
    echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');

    while ($row = mysqli_fetch_assoc($result))
    { if (isset($row["note_indi"])) 
		   { $note_indi = mb_ereg_replace("\n", " ", $row["note_indi"]);
	         $note_indi = mb_ereg_replace("\r", " ", $row["note_indi"]);
	       } else 
		   { $note_indi = "";
	       }
        $ligne = 
                $row["nom"]
        .chr(9).$row["prenom"]
        .chr(9).$row["sexe"]
        .chr(9).$row["profession"]
        .chr(9).fctDisplayDateExcel($row["date_naiss"])
        .chr(9).$row["lieu_naiss"]
        .chr(9).$row["dept_naiss"]
        .chr(9).fctDisplayDateExcel($row["date_deces"])
        .chr(9).$row["lieu_deces"]
        .chr(9).$row["dept_deces"]
        .chr(9).$row["nom_pere"]
        .chr(9).$row["prof_pere"]
        .chr(9).$row["nom_mere"]
        .chr(9).$row["prof_mere"]
        .chr(9).$note_indi
        .chr(10);
        echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');
    }
}