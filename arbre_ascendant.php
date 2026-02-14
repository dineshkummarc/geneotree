<?php
require_once ("_get_ascendancy.php");
require_once ("_get_descendancy.php");
require_once ("_functions.php");

function cell($ii)
{   global $Indiv, $url;

    if ($Indiv["id_indi"][$ii])
    {  afficher_cellule ($ii, $Indiv["id_indi"][$ii], $Indiv["sosa_dyn"][$ii], $Indiv["nom"][$ii], $Indiv["sexe"][$ii], $Indiv["profession"][$ii], $Indiv["date_naiss"][$ii], $Indiv["lieu_naiss"][$ii], $Indiv["date_deces"][$ii], $Indiv["lieu_deces"][$ii], $Indiv["central"][$ii]);
    } else 
    {  echo '&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;&emsp;';
    }
}

function fleche($ii)
{   global $Indiv;
    global $url;

    if (isset($Indiv["sosa_dyn"][$ii]) AND (isset($Indiv["sosa_dyn"][$ii*2]) OR isset($Indiv["sosa_dyn"][$ii*2+1])) )
    {    echo '<a href="arbre_ascendant.php'.$url.'&id='.$Indiv["id_indi"][$ii].'&fid='.$_REQUEST["fid"].'&pag='.$_REQUEST["pag"].'"><img src=themes\fleche_droite.png></a>';
    }
}

function fs($ii)
{   global $Indiv;
    global $ancetres_fs;
    global $url;
	global $page;
// print_r2($ancetres_fs['nom']);
// echo $Indiv["id_indi"][$ii];
// return;
// print_r ($ancetres_fs["nom"][1220]);return;
// $FirstName = getFirstName($ancetres_fs["nom"][ $Indiv["id_indi"][$ii]]);
// echo $ancetres_fs["nom"][ $Indiv["id_indi"][$ii]].'/'.$FirstName;return;

    $nb_fs = 0;
    if (!empty($ancetres_fs["nom"][ $Indiv["id_indi"][$ii]])) 
	{ $nb_fs = count($ancetres_fs["nom"][ $Indiv["id_indi"][$ii]]);
    }
    if ($nb_fs > 0)
    {    for ($zz = 0; $zz < $nb_fs; $zz++)
        {   if ($zz % 4 ==0 AND $zz != 0) {echo '<br>';} 
            if ($ancetres_fs["nom"][ $Indiv["id_indi"][$ii] ][$zz])
            {   echo '<a style="color:'.recup_color_sexe($ancetres_fs["sexe"][ $Indiv["id_indi"][$ii] ][$zz]).';" onclick="displayFiche('.$ancetres_fs["id_fs"][ $Indiv["id_indi"][$ii] ][$zz].')">'.getFirstName($ancetres_fs["nom"][$Indiv["id_indi"][$ii] ][$zz]).'</a>';
                if ($zz != $nb_fs -1) {echo ', ';} // virgule, sauf pour le dernier individu
            }
        }
    }
}

/************************************ DEBUT DU SCRIPT ***************************************/

if (!isset($_REQUEST['type'])) {$_REQUEST['type'] = "";}

if ($_REQUEST['type'] == "excel")
{   $pool = sql_connect();

    $ancetres[][] = '';$cpt_generations = 0;
    $ancetres['id_indi'][0] = $_REQUEST['id'];
    recup_ascendance ($ancetres,0,99999,'ME_G');
    integrer_implexe(99999,'ME_G');

    header('Content-type:application/vnd.ms-excel');
    header('Content-Transfer-Encoding: binary');
    header('Content-Disposition: attachment; filename="GeneoTree_Ascendancy_'.$_REQUEST['ibase'].'.csv"');
    afficher_ascendance('Excel');
    return;
}

// On ouvre le masque  général
include ("menu.php");

if (!isset($_REQUEST['pag'])) {$_REQUEST['pag'] = "Age";} 

// left arrow
   // on récupère l'identifiant du 1er descendant uniquement pour afficher la flèche vers la gauche
$nb_generations_desc = 2;
$descendants ['id_indi'][0] = $_REQUEST['id'];
recup_descendance (0,0,0,'ME_G',NULL,NULL);
$query = 'DROP TABLE IF EXISTS '.$sql_pref.'_'.$ADDRC.'_desc_cles';
sql_exec($query);
//afficher_descendance();return; //debogue

$id_descendant = NULL;
for ($ii=0; $ii < count($descendants ['id_indi']); $ii++)
{  if ($descendants["generation"][$ii] == 1 AND $descendants ["sosa_d"][$ii] != 0)
   {  $id_descendant = $descendants ['id_indi'][$ii];
   }
   if ($descendants["generation"][$ii] == 1 AND $id_descendant == NULL)
   {  $id_descendant = $descendants ['id_indi'][$ii];
   }
}

// get ascendancy
$ancetres[][] = '';$communs[][] = '';$cpt_generations = 0;
$ancetres['id_indi'][0] = $_REQUEST['id'];
recup_ascendance ($ancetres,0,5,'ME_G');
integrer_implexe(5,'ME_G');
// afficher_ascendance();return; // debogue

?>
<script>
flag_excel = "<?php echo $flag_excel?>";
DivIcons ("DivIcon1", "themes/icon-print.png", "arbre_ascendant.php" + "?" + HrefBase + "&id=<?php echo $_REQUEST['id']?>&type=arbre&nbgen=4&orient=L&implex=Y&SpeGe=N");
if (flag_excel !== "No")
{   DivIcons ("DivIcon2", "themes/icon-excel.png", "arbre_ascendant.php" + "?" + HrefBase + "&id=<?php echo $_REQUEST["id"]?>&type=excel&nbgen=25");
}
DivIcons ("DivIcon3", "themes/icon-folder-grey.png", "fiche_pdf.php" + "?" + HrefBase + "&id=<?php echo $_REQUEST["id"]?>&pori=AA");
dataJson = `[{"Code":"AGE", "Nb":0},{"Code":"DEAT", "Nb":0}]`;
SubMenuJson(dataJson);
Nom = "<?php echo $ancetres["nom"][0];?>";
displayStatAscendancy(<?php echo $_REQUEST['id'];?>, Nom);
</script>
<?php

// affichage de l'arbre ascendant

for ($ii=0; $ii < 32; $ii++)
{   $Indiv["id_indi"][$ii]    = "";
    $Indiv["sosa_dyn"][$ii]   = "";
    $Indiv["nom"][$ii]        = "";
    $Indiv["sexe"][$ii]       = "";
    $Indiv["profession"][$ii] = "";
    $Indiv["date_naiss"][$ii] = "";
    $Indiv["lieu_naiss"][$ii] = "";
    $Indiv["date_deces"][$ii] = "";
    $Indiv["lieu_deces"][$ii] = "";
    $Indiv["central"][$ii]    = NULL;
}

for ($jj=0; $jj < count($ancetres["id_indi"]); $jj++)
{   $Indiv["id_indi"][ $ancetres["sosa_d"][$jj] ] = $ancetres["id_indi"][$jj];
    $Indiv["sosa_dyn"][ $ancetres["sosa_d"][$jj] ] = $ancetres["sosa_dyn"][$jj];
    $Indiv["nom"][ $ancetres["sosa_d"][$jj] ] = $ancetres["nom"][$jj];
    $Indiv["sexe"][ $ancetres["sosa_d"][$jj] ] = $ancetres["sexe"][$jj];
    $Indiv["profession"][ $ancetres["sosa_d"][$jj] ] = $ancetres["profession"][$jj];
    $Indiv["date_naiss"][ $ancetres["sosa_d"][$jj] ] = $ancetres["date_naiss"][$jj];
    $Indiv["lieu_naiss"][ $ancetres["sosa_d"][$jj] ] = $ancetres["lieu_naiss"][$jj];
    $Indiv["date_deces"][ $ancetres["sosa_d"][$jj] ] = $ancetres["date_deces"][$jj];
    $Indiv["lieu_deces"][ $ancetres["sosa_d"][$jj] ] = $ancetres["lieu_deces"][$jj];
    if ($ancetres["sosa_d"][$jj] == 1) { $Indiv["central"][ $ancetres["sosa_d"][$jj] ] = "O"; }
}

echo '
<table style="border-collapse: separate; border-spacing:2px;">

<tr>
<td>&nbsp;</td>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td colspan="1" rowspan="4"><img src=themes\branches_asc3.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][16]) 
{ echo 'OnMouseOver=afficher_bulle("16") OnMouseOut=desafficher_bulle("16")';}
echo '>';
cell(16);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(16);
echo '</td>
</tr>

<tr>
<td>&nbsp;</td>
<td></td>
<td></td>
<td></td>
<td colspan="1" rowspan="6"><img src=themes\branches_asc4.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][8]) 
{ echo 'OnMouseOver=afficher_bulle("8") OnMouseOut=desafficher_bulle("8")';}
echo '>';
cell(8);
echo '</td>
</tr>

<tr>
<td>&nbsp;</td>
<td></td>
<td></td>
<td></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][17]) 
{ echo 'OnMouseOver=afficher_bulle("17") OnMouseOut=desafficher_bulle("17")';}
echo '>';
cell(17);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(17);
echo '</td>
</tr>

<tr>
<td>&nbsp;</td>
<td></td>
<td colspan="1" rowspan="10"><img src=themes\branches_asc.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][4]) 
{ echo 'OnMouseOver=afficher_bulle("4") OnMouseOut=desafficher_bulle("4")';}
echo '>';
cell(4);
echo '</td>
<td style="vertical-align:top;">';
fs(8);
echo '</td>
</tr>

<tr>
<td>&nbsp;</td>
<td></td>
<td></td>
<td colspan="1" rowspan="4"><img src=themes\branches_asc3.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][18]) 
{ echo 'OnMouseOver=afficher_bulle("18") OnMouseOut=desafficher_bulle("18")';}
echo '>';
cell(18);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(18);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td style="vertical-align:top;">';
fs(4);
echo '</td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][9]) 
{ echo 'OnMouseOver=afficher_bulle("9") OnMouseOut=desafficher_bulle("9")';}
echo '>';
cell(9);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][19]) 
{ echo 'OnMouseOver=afficher_bulle("19") OnMouseOut=desafficher_bulle("19")';}
echo '>';
cell(19);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(19);
echo '</td>
</tr>

<tr>
<td colspan="1" rowspan="8" align=right><img src=themes\branches_asc01.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][2]) 
{ echo 'OnMouseOver=afficher_bulle("2") OnMouseOut=desafficher_bulle("2")';}
echo '>';
cell(2);
echo '</td>
<td></td>
<td></td>
<td style="vertical-align:top;">';
fs(9);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td><td colspan="1" rowspan="4"><img src=themes\branches_asc3.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][20]) 
{ echo 'OnMouseOver=afficher_bulle("20") OnMouseOut=desafficher_bulle("20")';}
echo '>';
cell(20);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(20);
echo'</td>
</tr>

<tr>
<td style="vertical-align:top;">';
fs(2);
echo '</td>
<td></td><td colspan="1" rowspan="6"><img src=themes\branches_asc4.png></td>
<td rowspan="2" class="cell_indiv" '; 
if ($Indiv["id_indi"][10]) 
{ echo 'OnMouseOver=afficher_bulle("10") OnMouseOut=desafficher_bulle("10")';}
echo '>';
cell(10);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][21]) 
{ echo 'OnMouseOver=afficher_bulle("21") OnMouseOut=desafficher_bulle("21")';}
echo '>';
cell(21);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(21);
echo '</td>
</tr>

<tr>
<td></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][5]) 
{ echo 'OnMouseOver=afficher_bulle("5") OnMouseOut=desafficher_bulle("5")';}
echo '>';
cell(5);
echo '</td>
<td style="vertical-align:top;">';
fs(10);
echo '</td>
</tr>

<tr>
<td class="trait"></td>
<td></td>
<td colspan="1" rowspan="4"><img src=themes\branches_asc3.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][22]) 
{ echo 'OnMouseOver=afficher_bulle("22") OnMouseOut=desafficher_bulle("22")';}
echo '>';
cell(22);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(22);
echo '</td>
</tr>

<tr>
<td class="trait" name="14A"></td>
<td></td>
<td style="vertical-align:top;">';
fs(5);
echo '</td>
<td rowspan="2" class="cell_indiv" '; 
if ($Indiv["id_indi"][11]) 
{ echo 'OnMouseOver=afficher_bulle("11") OnMouseOut=desafficher_bulle("11")';}
echo '>';
cell(11);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][23]) 
{ echo 'OnMouseOver=afficher_bulle("23") OnMouseOut=desafficher_bulle("23")';}
echo '>';
cell(23);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(23);
echo'</td>
</tr>

<tr>
<td colspan=1 rowspan=2>';
if (isset($id_descendant))
{ echo '<a href="arbre_ascendant.php'.$url.'&id='.$id_descendant.'&fid='.$_REQUEST["fid"].'&pag='.$_REQUEST["pag"].'"><img src=themes/fleche_gauche.png></a>'; }
echo '</td>
<td colspan=1 rowspan=2 class="cell_indivP"'; 
if ($Indiv["id_indi"][1]) 
{ echo 'OnMouseOver=afficher_bulle("1") OnMouseOut=desafficher_bulle("1")';}
echo '>';
cell(1);
echo '</td>
<td></td>
<td></td>
<td></td>
<td style="vertical-align:top;">';
fs(11);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td>
<td></td>
<td colspan="1" rowspan="4"><img src=themes\branches_asc3.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][24]) 
{ echo 'OnMouseOver=afficher_bulle("24") OnMouseOut=desafficher_bulle("24")';}
echo '>';
cell(24);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(24);
echo '</td>
</tr>

<tr>
<td colspan="1" rowspan="8" align=right><img src=themes\branches_asc02.png></td>
<td style="vertical-align:top;">';
fs(1);
echo '</td>
<td></td>
<td></td>
<td colspan="1" rowspan="6"><img src=themes\branches_asc4.png></td>
<td rowspan="2" class="cell_indiv" '; 
if ($Indiv["id_indi"][12]) 
{ echo 'OnMouseOver=afficher_bulle("12") OnMouseOut=desafficher_bulle("12")';}
echo '>';
cell(12);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][25]) 
{ echo 'OnMouseOver=afficher_bulle("25") OnMouseOut=desafficher_bulle("25")';}
echo '>';
cell(25);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(25);
echo '</td>
</tr>

<tr>
<td></td>
<td colspan="1" rowspan="10"><img src=themes\branches_asc.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][6]) 
{ echo 'OnMouseOver=afficher_bulle("6") OnMouseOut=desafficher_bulle("6")';}
echo '>';
cell(6);
echo '</td>
<td style="vertical-align:top;">';
fs(12);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td colspan="1" rowspan="4"><img src=themes\branches_asc3.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][26]) 
{ echo 'OnMouseOver=afficher_bulle("26") OnMouseOut=desafficher_bulle("26")';}
echo '>';
cell(26);
echo '</td><td colspan="1" rowspan="2">';
fleche(26);
echo'</td>
</tr>

<tr>
<td></td>
<td style="vertical-align:top;">';
fs(6);
echo '</td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][13]) 
{ echo 'OnMouseOver=afficher_bulle("13") OnMouseOut=desafficher_bulle("13")';}
echo '>';
cell(13);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][27]) 
{ echo 'OnMouseOver=afficher_bulle("27") OnMouseOut=desafficher_bulle("27")';}
echo '>';
cell(27);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(27);
echo'</td>
</tr>

<tr>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][3]) 
{ echo 'OnMouseOver=afficher_bulle("3") OnMouseOut=desafficher_bulle("3")';}
echo '>';
cell(3);
echo '</td>
<td></td>
<td></td>
<td style="vertical-align:top;">';
fs(13);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td>
<td colspan="1" rowspan="4"><img src=themes\branches_asc3.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][28]) 
{ echo 'OnMouseOver=afficher_bulle("28") OnMouseOut=desafficher_bulle("28")';}
echo '>';
cell(28);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(28);
echo'</td>
</tr>

<tr>
<td></td>
<td style="vertical-align:top;">';
fs(3);
echo '</td>
<td></td>
<td colspan="1" rowspan="6"><img src=themes\branches_asc4.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][14]) 
{ echo 'OnMouseOver=afficher_bulle("14") OnMouseOut=desafficher_bulle("14")';}
echo '>';
cell(14);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][29]) 
{ echo 'OnMouseOver=afficher_bulle("29") OnMouseOut=desafficher_bulle("29")';}
echo '>';
cell(29);
echo '</td><td colspan="1" rowspan="2">';
fleche(29);
echo'</td>
</tr>

<tr>
<td></td>
<td></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][7]) 
{ echo 'OnMouseOver=afficher_bulle("7") OnMouseOut=desafficher_bulle("7")';}
echo '>';
cell(7);
echo '</td>
<td style="vertical-align:top;">';
fs(14);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td>
<td colspan="1" rowspan="4"><img src=themes\branches_asc3.png></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][30]) 
{ echo 'OnMouseOver=afficher_bulle("30") OnMouseOut=desafficher_bulle("30")';}
echo '>';
cell(30);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(30);
echo'</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td>
<td style="vertical-align:top;">';
fs(7);
echo '</td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][15]) 
{ echo 'OnMouseOver=afficher_bulle("15") OnMouseOut=desafficher_bulle("15")';}
echo '>';
cell(15);
echo '</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td>
<td></td>
<td rowspan="2" class="cell_indiv"'; 
if ($Indiv["id_indi"][31]) 
{ echo 'OnMouseOver=afficher_bulle("31") OnMouseOut=desafficher_bulle("31")';}
echo '>';
cell(31);
echo '</td>
<td colspan="1" rowspan="2">';
fleche(31);
echo'</td>
</tr>

<tr>
<td></td>
<td></td>
<td></td>
<td></td>
<td></td>
<td style="vertical-align:top;">';
fs(15);
echo '</td>
</tr></table>
';


// on ferme le masque général
include ("_inc_html_card.php");


// formulaire choix edition
if (in_array($_REQUEST["type"], array("arbre","liste")))
{   
    echo '
    <FORM method="POST">
    <div style="position:absolute; left:300px; top:300px; margin-left:auto; margin-right:auto; padding:30px; background-color:#DDDDDD; border: 3px solid black;" >';
  // titre
    echo '
    <p class=titre align=center><b>'.$got_lang['IBPdf'].'</b></p>
    <p>&nbsp;';
  // type
    echo '
    <p align=center>';
    afficher_menu("type",array($got_lang['Arbre'],$got_lang['List']),array("arbre","liste")).'</p>';
    echo '
    <p>&nbsp;';

  //orientation
    if ($_REQUEST["type"] == "arbre")
    {    echo '
        <p>&nbsp;</p><p align=center><b>Orientation : </b>';
        afficher_menu("orient",array($got_lang['Paysa'],$got_lang['Porta']),array("L","P")).'</p>';
    }
  // nb générations
    echo '
    <br><br><b>'.$got_lang['NbGen'].': </b>';
    afficher_menu("nbgen",array(5,9,13,$got_lang['Tous']),array(4,8,12,40));
  // implexe
    echo '
    <p>&nbsp;';
    echo '
    <p>&nbsp;</p><p align=center>'.$got_lang["Implx"].' : ';
    afficher_menu("implex",array($got_lang["Non"], $got_lang["Oui"]),array("N","Y"));
  // cases grisées
    if ($_REQUEST["type"] == "arbre" and $_REQUEST["orient"] == "P") 
    {    echo '
        <p>&nbsp;</p><p align=center>'.$got_lang["SpeGe"].' : ';
        afficher_menu("SpeGe",array($got_lang["Non"], $got_lang["Oui"]),array("N","Y"));
    }

    echo '
    <p>&nbsp;
    <table width=100% style="border-spacing:20px; border-collapse: separate;">
    <tr>
    <td class=menu_td><a href=arbre_ascendant.php'.$url.' style="display:block;width:100%;height:100%;text-decoration:none;color:black;">Annul</td>
    <td class=menu_td align=center><a target=_blank href=arbre_ascendant_pdf.php'.$url.'&type='.$_REQUEST["type"].'&orient='.$_REQUEST["orient"].'&nbgen='.$_REQUEST["nbgen"].'&implex='.$_REQUEST["implex"].'&SpeGe='.$_REQUEST["SpeGe"].'&id='.$_REQUEST["id"].' style="display:block;width:100%;height:100%;text-decoration:none;color:black;">OK</td>
    </tr>
    </table>
    
    </div>
    </FORM>';

    return;
}
