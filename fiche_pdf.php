<?php
require_once  ("_get_ascendancy.php");
require_once  ("_get_descendancy.php");
require_once  ("_sql_requests.php");
require_once  ("_functions.php");
require_once  ("tfpdf/tfpdf.php");
require_once ("languages/".$_REQUEST['lang'].".php");
$pool = sql_connect();

function getPhotoUrl($id)
{   global $sql_pref;
	global $page;

	$query = 'SELECT note_evene
        FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement`
        WHERE type_evene = "FILE"
        and id_indi = '.$id;

    return sql_exec($query,0);
}

class PDF extends TFPDF
{

function init_pdf()
{   global $entete;
    global $row;
    global $got_lang;

    $entete = $got_lang['ImpFi'];

    // $this->SetTitle($row[4].' '.$row[5].' '.$row[6].' '.$row[3].' ');
    $this->SetCreator('GeneoTree');
    $this->SetAuthor('GeneoTree');
    $this->SetMargins(18,20);
    $this->SetAutoPageBreak(TRUE, 20);
    $this->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
    $this->AddFont('DejaVu','B','DejaVuSansCondensed-Bold.ttf',true);
}

function Titre2($text)
{  $this->MultiCell(0,3,"");
   $this->SetFont('DejaVu','B',10);
   $this->SetFillColor(230);
   $this->MultiCell(0,4,$text,0,"L",1);
   $this->MultiCell(0,2,"");
}

function editer_individu($result,$Prefixe=NULL,$principal=false,$Array=false)
{ global $got_lang;

/* row => id_indi(0),nom(1),prenom1(2),tri(3),lieu_naiss(4),sosa_dyn(5),sexe(6),profession(7),date_debut(8),date_fin(9)
   ,prenom2(10),prenom3(11),date_naiss(12),date_deces(14),lieu_deces(15), note_indi(17)
*/

  $final = array();

  // put results in $final array
  if ($Array == "ARRAY")
  { $final = $result;
  } elseif (mysqli_num_rows($result) > 0)
  { while ($row = mysqli_fetch_assoc($result)) 
    { $final[] = $row;
    }
  }
// print_r2($final);

  // edit $final
  for ($ii=0; $ii < count($final); $ii++)
  {
    // name
    $Prenoms = "";
    if (!empty($final[$ii]["prenom2"])) 
    { $Prenoms .= ' '.$final[$ii]["prenom2"];
    }
    
    // sex color
    if ($final[$ii]["sexe"])
    { $SexColor = recup_color_sexe_decimal($final[$ii]["sexe"]);
      $this->SetTextColor ($SexColor[0],$SexColor[1],$SexColor[2]);
    } else
      $this->SetTextColor (0,0,0);
    
    if ($principal) 
    { $this->SetFont('DejaVu','B',14);
    } else 
    { $this->SetFont('DejaVu','B',8);
    }
    $this->SetFillColor(255);

    if ($principal) 
    { $this->MultiCell(0,6,$final[$ii]["nom"].$Prenoms,0,"C");

      // central person ahnen
      if (!empty($final[$ii]["sosa_dyn"]))
      {   $this->SetTextColor (255,0,0);
          $this->MultiCell(0,5,$got_lang["Sosa"].' n° '.$final[$ii]["sosa_dyn"].' - '.$got_lang["Gener"].strlen(decbin($final[$ii]["sosa_dyn"])),0,"C");
      }

      // central person occupation 
      if (!empty($final[$ii]["profession"]))
      {   $this->SetTextColor (0,0,0);
          $this->SetFont('DejaVu','',8);
          $this->MultiCell(0,5,$final[$ii]["profession"],0,"C");
      }
    } else // not principal 
    { 
      $this->SetFont('DejaVu','',8);
      $this->SetTextColor (0);

      // sex color
      if ($final[$ii]["sexe"])
      { $SexColor = recup_color_sexe_decimal($final[$ii]["sexe"]);
        $this->SetTextColor ($SexColor[0],$SexColor[1],$SexColor[2]);
      } else
        $this->SetTextColor (0,0,0);

      // nom
      $this->SetFont('DejaVu','B',8);
      $this->Cell($this->GetStringWidth($final[$ii]["nom"]),3,$final[$ii]["nom"]);
    }

    $this->SetFont('DejaVu','',8);
    if (!$principal AND !empty($Prenoms)) 
    { $this->Cell($this->GetStringWidth($Prenoms),3,$Prenoms);
    }
    $ligne = "";

    // profession 
    if (!empty($final[$ii]["profession"]) AND !$principal) 
      $ligne .= ', '.$final[$ii]["profession"];

    // birth date
    if (!empty($final[$ii]["date_naiss"]))
    { if ($got_lang['Langu'] == 'fr' and $final[$ii]["sexe"] == 'F') 
      { $suf_naiss = 'e';
      } else 
      { $suf_naiss = "";
      }
      if (!$principal) 
        $ligne .= ', ';
      $ligne .= $got_lang['Ne'].$suf_naiss.' '.displayDate($final[$ii]["date_naiss"]);
    }

    // lieu naissance
    if (!empty($final[$ii]["lieu_naiss"])) 
	  $ligne .= ' '.$got_lang['Situa'].' '.$final[$ii]["lieu_naiss"];

    // death date, death place et death age
    if (!empty($final[$ii]["date_deces"])) 
    { $ligne .= ', + '.displayDate($final[$ii]["date_deces"]); 
      if (!empty($final[$ii]["lieu_deces"])) 
      { $ligne .= ' '.$got_lang['Situa'].' '.$final[$ii]["lieu_deces"];
      }
      if (!empty($final[$ii]["dept_deces"])) 
      { $ligne .= '('.$final[$ii]["dept_deces"].') ';
      }
      $ligne .= ' '.displayAge($final[$ii]["date_naiss"],$final[$ii]["date_deces"]);
    } else
    {   $ligne .= ' '.displayAge($final[$ii]["date_naiss"],"");
    }

    $this->SetTextColor (0);
      
    if ($principal) 
    { $this->SetFont('DejaVu','',10);
      $this->MultiCell(0,6,$ligne,0,"C");
    }
    else 
    { $this->SetFont('DejaVu','',8);
      $this->MultiCell(0,3,$ligne,0,"L");
    }
  }

  $this->SetFont('DejaVu','',8);
}

function editer_cousin ($fid,$nb_generations,$nb_generations_desc,$relation)
{ $cousins = recup_cousin ($fid,$nb_generations,$nb_generations_desc,$relation);

  if (isset($cousins[0]['id_indi'])) 
  { $this->Titre2($relation.' ('.count($cousins).')',0,1);
    $this->editer_individu($cousins,"+",false,"ARRAY");
  }
}

function editer_fiche($id_indi)
{
  if (empty($id_indi)) return;

    global $sql_pref;
    global $got_lang;
    global $got_tag;
    global $entete;

    $this->AddPage();

// individu principal
    $query = '
	SELECT 
	 id_indi
	,"" as id_wife
	,sexe
	,sosa_dyn
	," " as sosa_dyn_wife
	,CONCAT(nom, " ", prenom1) as nom
	,date_naiss
	,date_naiss as age1
	,date_naiss as age2
	,profession
	,date_naiss
	,CASE WHEN dept_naiss != "" THEN CONCAT(lieu_naiss, " (", dept_naiss, ")") ELSE lieu_naiss END as lieu_naiss
	,CONCAT (prenom2, " ", prenom3) as prenom2
	,date_deces
	,CASE WHEN dept_deces != "" THEN CONCAT(lieu_deces, " (", dept_deces, ")") ELSE lieu_deces END as lieu_deces
	,note_indi
	FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` 
	WHERE id_indi = '.$id_indi;
    $result = sql_exec($query,0);
	$indi = mysqli_fetch_row($result);
	mysqli_data_seek($result,0);
    $this->editer_individu($result,false,true);

// photos de l'individu principal
    $result1 = getPhotoUrl($id_indi);
    if (mysqli_num_rows($result1) > 0)
    { $Y_encours = $this->GetY();
	  $X_encours = 0;
      while ($row1 = mysqli_fetch_row($result1))
      { $MyFile = MyUrl('picture/'.$_REQUEST['ibase'].'/'.$row1[0], FALSE);
        $PicAttrib = @getimagesize($MyFile);
// print_r2($PicAttrib);
        $HauteurPhoto = 0;
		if ($PicAttrib != false)
        {  if ($PicAttrib[2] == 2 AND isset($PicAttrib[0]))  //jpg only, png are not available with fdpf library
          { $HauteurPhoto = 50;
            $PicRatio = $PicAttrib[0] / $PicAttrib[1];
            $LargImage = $PicAttrib[0] * $HauteurPhoto / $PicAttrib[1];
            if ($PicRatio > 1) {$HauteurPhoto = $HauteurPhoto / $PicRatio;} 	
          
            // pictures with large width (more than page width)
            if ($X_encours + $LargImage > $this->GetPageWidth() - 32)
            { $X_encours = 0;
          	$Y_encours = $Y_encours + $HauteurPhoto;
          	if ($Y_encours > $this->GetPageHeight() - $HauteurPhoto - 16)
          	{ $this->AddPage();
                $X_encours = 0;
          	  $Y_encours = 16;
          	}
            } 
            $this->image($MyFile, $this->GetX() + $X_encours, $Y_encours, 0, $HauteurPhoto);
            $X_encours = $X_encours + $LargImage + 2;
          }
        }
      }
      $this->SetY($Y_encours + $HauteurPhoto);        // on repositionne le position Y pour démarrer correctement l'edition des textes
    }

// parents
    $result = recup_parent($indi[0]);
    $this->Titre2($got_lang["StPar"]);
    $this->editer_individu($result,"+");

// conjoints
    $result = recup_conjoints($indi[0],$indi[2]);
    if (mysqli_num_rows($result) != 0) 
    { $this->Titre2($got_lang["NomCo"]);
      $this->editer_individu($result,$got_lang["Situa"]." ");
    }

// enfants
    $result = recup_enfants($indi[0],$indi[2]);
    if (mysqli_num_rows($result) != 0)
    { $this->Titre2($got_lang['Enfan'].' ('.mysqli_num_rows($result).')');
      $this->editer_individu($result,$got_lang["Situa"]." ");
    }

// frères et soeurs
    $result = recup_fratrie($indi[0],$indi[12],$indi[13]);
    if (mysqli_num_rows($result) != 0)
    { $this->Titre2($got_lang['Frere'].' ('.mysqli_num_rows($result).')');
      $this->editer_individu($result,"+");
    }

// note individuelle
    $this->SetFont('DejaVu','',8);
    if (!empty($indi[14])) 
    { $this->Titre2($got_lang['NotIn']);
      $this->SetFont('DejaVu','',8);
      $this->MultiCell(0,3,str_replace(chr(13),"\n",$indi[14]),0,"J");
    }

// évènements
    $result3 = recup_evenements($indi[0]);

	if (mysqli_num_rows($result3) !== 0)
    { while ($row3 = mysqli_fetch_row($result3))
      {   
  // entete de l'acte
        $this->Titre2(substr($got_lang["Sourc"],0,-1).' '.$got_tag[$row3[1]]);
  // date/lieu de l'acte
        if (!empty($row3[2]) OR !empty($row3[3]))
		{ $this->SetFont('DejaVu','',8);
          $this->MultiCell(0,3,displayDate($row3[2]).' '.$row3[3]);
		}
  // note de l'acte
        if (!empty($row3[4]))
        { $this->SetFont('DejaVu','',8);
		  $this->MultiCell(0,3,str_replace(chr(13),"\n",$row3[4]),0,"J");
	    }
			
  // temoins de l'acte
        if ($row3[6] == "RELA" AND $row3[5] != NULL)
        {   $query = '
			SELECT 
			id_indi
			,"" as id_wife
			,sexe
			,sosa_dyn
			," " as sosa_dyn_wife
			,CONCAT(nom, " ", prenom1) as nom
			,substring(date_naiss,1,4)
			,date_naiss as age1
			,date_naiss as age2
			,profession
			,date_naiss
			,CONCAT (prenom2, " ", prenom3) as prenom2
			,date_deces
			,CASE WHEN dept_deces != "" THEN CONCAT(lieu_deces, " (", dept_deces, ")") ELSE lieu_deces END as lieu_deces
			,note_indi
			FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` 
			WHERE id_indi = '.$row3[5];
			$result = sql_exec($query);
            if (!empty($got_tag[$row3[7]])) 
			{ $this->Cell($this->GetStringWidth($got_tag[$row3[7]]." : "),3,$got_tag[$row3[7]]." : ");
		    }
			$this->editer_individu($result,"+");
        }
  // images de l'acte
        if ($row3[7] !== NULL)
        { $Pictures = explode(",",$row3[7]);
          $ii= 0;
		  while ($ii < count($Pictures))
		  { $MyFile = MyUrl('picture/'.$_REQUEST['ibase'].'/'.$Pictures[$ii],FALSE);
            $size_image = @getimagesize($MyFile);  // quelques fois, les attributs n'existent pas.
            $Largeur = $this->GetPageWidth()-35;

            if (!empty($size_image) AND $size_image[2] == 2) 
			{  $Hauteur = $size_image[1] * $Largeur / $size_image[0];
			   if ($this->GetY() + $Hauteur > $this->GetPageHeight() )  {$this->AddPage();}
               $this->image($MyFile,$this->GetX(),$this->GetY(),$Largeur,$Hauteur);
               $this->SetY($this->GetY() + $Hauteur);
			}
			$ii++;
		  }
        }
      }
    }

// cousins
    $ancetres='';$descendants='';$cpt_generations='';
    $nb_generations = 0;$nb_generations_desc = 2;$relation = $got_lang['PetEf'];
    $this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

    $ancetres='';$descendants='';$cpt_generations='';
    $nb_generations = 2;$nb_generations_desc = 1;$relation = $got_lang['Oncle'];
    $this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

    $ancetres='';$descendants='';$cpt_generations='';
    $nb_generations = 1;$nb_generations_desc = 2;$relation = $got_lang['Neveu'];
    $this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

    $ancetres='';$descendants='';$cpt_generations='';
    $nb_generations = 2;$nb_generations_desc = 2;$relation = $got_lang['Germa'];
    $this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

    $ancetres='';$descendants='';$cpt_generations='';
    $nb_generations = 3;$nb_generations_desc = 1;$relation = $got_lang['OncGr'];
    $this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

    $ancetres='';$descendants='';$cpt_generations='';
    $nb_generations = 3;$nb_generations_desc = 2;$relation = $got_lang['CouGr'];
    $this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);

    $ancetres='';$descendants='';$cpt_generations='';
    $nb_generations = 3;$nb_generations_desc = 3;$relation = $got_lang['CouIs'];
    $this->editer_cousin ($id_indi,$nb_generations,$nb_generations_desc,$relation);
}

}

/*************************************** DEBUT DU SCRIPT *********************************************/
$orientation = "P";        // portrait
if (!isset($_REQUEST['pori'])) {$_REQUEST['pori'] = "";}
if (!isset($_REQUEST['pag'])) {$_REQUEST['pag'] = "";}
if (!isset($_REQUEST['continu'])) {$_REQUEST['continu'] = "KO";}

if ($_REQUEST['pori'] == 'AA') 
{   if ($_REQUEST['continu'] == "OK")
    {   $ancetres[][] = '';
        $cpt_generations = 0;
        $ancetres['id_indi'][0] = $_REQUEST['id'];
        recup_ascendance ($ancetres,0,$_REQUEST["nbgen"]-1,'ME_P');
		$pdf = new PDF($orientation,'mm',recup_format());
        $pdf->init_pdf();
        $temp = $ancetres;
// transfert de tableau, car $ancetres est déjà utilisé dans la fonction editer_fiche
        array_multisort ($temp['sosa_d'],$temp['id_indi'],$temp['generation']);
        for ($ii=0; $ii < count($temp['id_indi']); $ii++)
        { $pdf->editer_fiche($temp['id_indi'][$ii]);
        }
        $pdf->Output();
    } else
    {   require_once("menu.php");
        echo '
        <br><br><br><br><br><br><br>
        <table width=400px style="margin-left:auto; margin-right:auto; border-spacing:20px; border-collapse: separate; padding:30px; background-color:#DDDDDD; border: 3px solid black;">
        <tr><td align=center colspan=2>'.$got_lang['NbGen'].' ?</td></tr>
        <tr><td align=center colspan=2>';
		afficher_menu("nbgen", array("1","2","3","4","5"), array("1","2","3","4","5"));
		echo '</td></tr>
		<tr><td></td></tr>
		<tr><td class=menu_td width=50px><a href=arbre_ascendant.php'.$url.'&id='.$_REQUEST["id"].'&theme='.$_REQUEST["theme"].' style="display:block;width:100%;height:100%;text-decoration:none;color:black;">'.$got_lang['Annul'].'</td>
            <td class=menu_td width=50px align=center><a href=fiche_pdf.php'.$url.'&id='.$_REQUEST["id"].'&continu=OK&pori='.$_REQUEST["pori"].'&nbgen='.$_REQUEST["nbgen"].' style="display:block;width:100%;height:100%;text-decoration:none;color:black;">OK</td>
        </tr>
        </table>
        ';
    }
} 

// all index cards of ascendancy tree
else if ($_REQUEST['pori'] == 'AD') 
{   if ($_REQUEST['continu'] == "OK")
    {   $descendants ['id_indi'][0] = $_REQUEST['id'];
        $cpt_generations_desc = 0;
        $nb_generations_desc = $_REQUEST['nbgen'] - 1;
        recup_descendance ($descendants,0,$nb_generations_desc,'ME_P',''); 
        $query = 'DROP TABLE IF EXISTS '.$sql_pref.'_'.$ADDRC.'_desc_cles';
        sql_exec($query);

        $pdf = new PDF($orientation,'mm',recup_format());
        $pdf->init_pdf();
        $temp = $descendants;    // on est oblige de transferer le tableau, car $descendants est utilise dans la fonction editer_fiche

        array_multisort ($temp['indice'],$temp['id_indi']);
        for ($ii= 0; $ii < count($temp['id_indi']); $ii++) 
        {   $pdf->editer_fiche($temp['id_indi'][$ii]);
        }
        $pdf->Output();
    } else
    {   require_once("menu.php");
        
        echo '
        <br><br><br><br><br><br><br>
        <table width=400px style="margin-left:auto; margin-right:auto; border-spacing:20px; border-collapse: separate; padding:30px; background-color:#DDDDDD; border: 3px solid black;">
        <tr><td align=center colspan=2>'.$got_lang['NbGen'].' ?</td></tr>
        <tr><td align=center colspan=2>';
        afficher_menu("nbgen", array("1","2","3","4","5"), array("1","2","3","4","5"));
        echo '</td></tr>
        <tr><td class=menu_td width=50px><a href=arbre_descendant.php'.$url.'&id='.$_REQUEST["id"].'&theme='.$_REQUEST["theme"].' style="display:block;width:100%;height:100%;text-decoration:none;color:black;">'.$got_lang['Annul'].'</td>
            <td class=menu_td width=50px align=center><a target=_blank href=fiche_pdf.php'.$url.'&id='.$_REQUEST["id"].'&continu=OK&pori='.$_REQUEST["pori"].'&nbgen='.$_REQUEST["nbgen"].' style="display:block;width:100%;height:100%;text-decoration:none;color:black;">OK</td>
        </tr>
        </table>
        ';
    }
}
// all index cards of ascendancy tree
else
{   $pdf = new PDF($orientation,'mm',recup_format());
    $pdf->init_pdf();
    $pdf->editer_fiche($_REQUEST["fid"]);
    $pdf->Output();
}