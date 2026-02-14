<?php
require_once ("_get_ascendancy.php");
require_once ("_get_descendancy.php");
require_once ("_functions.php");

for ($ii=0; $ii < 60; $ii++) {$grid[$ii] = "";}
$grid[0] = "TR";$grid[10] = "TR";$grid[20] = "TR";$grid[30] = "TR";$grid[40] = "TR";$grid[50] = "TR";
$grid[2] = "4";$grid[4] = "5";$grid[6] = "6";$grid[8] = "7";
$grid[35] = "1";$grid[23] = "2";$grid[27] = "3";$grid[12] = "4";$grid[14] = "5";$grid[16] = "6";$grid[18] = "7";

/*************************************** DEBUT DU SCRIPT *********************************************/

// On ouvre le masque  général
include ("menu.php");

$ancetres['id_indi'][0] = $_REQUEST['id'];
$cpt_generations = 0;
recup_ascendance ($ancetres,0,2,'ME_G');
// afficher_ascendance();return;
$nb_fs = 0;
if (!empty($ancetres_fs["id_fs"] [$_REQUEST['id']])) 
{ $nb_fs = count($ancetres_fs["id_fs"] [$_REQUEST['id']]);
}
switch ($nb_fs)
{   case 0: break;
    case 1: $grid[33]=1; break;
    case 2: $grid[33]=1;
            $grid[37]=2; break;
    case 3: $grid[33]=1;$grid[34]=2;
            $grid[36]=3; break;
    case 4: $grid[33]=1;$grid[34]=2; 
            $grid[36]=3;$grid[37]=4; break;
    case 5: $grid[32]=1;$grid[33]=2;$grid[34]=3;
            $grid[36]=4;$grid[37]=5; break;
    case 6: $grid[32]=1;$grid[33]=2;$grid[34]=3;
            $grid[36]=4;$grid[37]=5;$grid[38]=6; break;
    case 7: $grid[31]=1;$grid[32]=2;$grid[33]=3;$grid[34]=4;
            $grid[36]=5;$grid[37]=6;$grid[38]=7; break;
    default: $grid[31]=1;$grid[32]=2;$grid[33]=3;$grid[34]=4;
            $grid[36]=5;$grid[37]=6;$grid[38]=7;$grid[39]=8; break;
}

$descendants ['id_indi'][0] = $_REQUEST['id'];
$cpt_generations = 0;
recup_descendance (0,0,1,'ME_G','');
$query = 'DROP TABLE IF EXISTS '.$sql_pref.'_'.$ADDRC.'_desc_cles';
sql_exec($query);
// afficher_descendance();return;

$nb_desc= @count($descendants["generation"]) - 1;
switch ($nb_desc)
{   case 0: break;
    case 1: $grid[45]=1; break;
    case 2: $grid[44]=1;$grid[46]=2; break;
    case 3: $grid[43]=1;$grid[45]=2;$grid[47]=3; break;
    case 4: $grid[43]=1;$grid[44]=2;$grid[46]=3;$grid[47]=4; break;
    case 5: $grid[43]=1;$grid[44]=2;$grid[45]=3;$grid[46]=4;$grid[47]=5; break;
    case 6: $grid[42]=1;$grid[43]=2;$grid[44]=3;$grid[45]=4;$grid[46]=5;$grid[47]=6; break;
    case 7: $grid[42]=1;$grid[43]=2;$grid[44]=3;$grid[45]=4;$grid[46]=5;$grid[47]=6;$grid[48]=7; break;
    case 8: $grid[41]=1;$grid[42]=2;$grid[43]=3;$grid[44]=4;$grid[45]=5;$grid[46]=6;$grid[47]=7;$grid[48]=8; break;
    default: $grid[41]=1;$grid[42]=2;$grid[43]=3;$grid[44]=4;$grid[45]=5;$grid[46]=6;$grid[47]=7;$grid[48]=8;$grid[49]=9; break;
}

// bouton PDF
// echo '<table width=100% style="border-collapse: separate;"><tr>'; // collapse incompatible avec arrondi
// echo '<td><a HREF=arbre_mixte_pdf.php'.$url.'&id='.$_REQUEST["id"].' title="'.$got_lang['IBPdf'].'" target=_blank><img width=35 heigth=35 src=themes/icon-print.png></a></td>';
// echo '</tr></table>';
?>
<script>
DivIcons ("DivIcon1", "themes/icon-print.png", "arbre_mixte_pdf.php" + "?" + HrefBase + "&id=<?php echo $_REQUEST['id']?>");
</script>
<?php
echo '
<table style="border-collapse: separate;border-spacing: 15px 5px;">';
for ($ii=0; $ii < 60; $ii++)
{ //echo '<br>'.$ii.'|'.$grid[$ii];
// changement de lignes
    if ($grid[$ii] == "TR") 
    {   if ($ii == 20) {echo '</tr><tr><td></td><td colspan=3 align=center><img width=100% heigth=100% src=themes/branches_asc2.png></td><td></td><td colspan=3 align=center><img width=100% heigth=100% src=themes/branches_asc2.png></td><td></td></tr><tr>';}
        elseif ($ii == 30) {echo '</tr><tr><td colspan=2></td><td colspan=5 align=center><img width=100% heigth=100% src=themes/branches_asc1.png></td><td colspan=2></td></tr><tr>';}
        elseif ($ii == 40 and $nb_desc != 0) {echo '</tr><tr><td colspan=3></td><td colspan=3 align=center><img width=100% heigth=100% src=themes/branches_desc.png></td><td colspan=3></td></tr><tr>';}
        else {echo '</tr><tr>';}
    }
// fleches
    elseif ($ii < 10)
    {    echo '<td align=center>';
        if ($ii % 2 == 0) 
        {    if (array_search($grid[$ii],$ancetres["sosa_d"]) !== FALSE)
            {    $jj = array_search($grid[$ii],$ancetres["sosa_d"]);
                echo '<a href=arbre_mixte.php'.$url.'&id='.$ancetres["id_indi"][$jj].'><img width=35 src=themes/fleche_haut.png></a>';
            }
        }
        echo '</td>';
    }
// ascendants
    elseif (($ii > 10  and $ii < 30 OR $ii == 35) AND $grid[$ii]) 
    {   if ($grid[$ii] == 1) {$cell_indiv = "cell_indivP";} else {$cell_indiv = "cell_indiv";}
        if (array_search($grid[$ii],$ancetres["sosa_d"]) !== FALSE)
        {    echo '<td align=center class='.$cell_indiv.' OnMouseOver=afficher_bulle("'.$grid[$ii].'") OnMouseOut=desafficher_bulle("'.$grid[$ii].'")>';
            $jj = array_search($grid[$ii],$ancetres["sosa_d"]);
            if ($grid[$ii] == 1) {$central = "O";} else {$central = "N";}
            afficher_cellule ($grid[$ii], $ancetres["id_indi"][$jj], $ancetres["sosa_dyn"][$jj], $ancetres["nom"][$jj], $ancetres["sexe"][$jj], $ancetres["profession"][$jj], $ancetres["date_naiss"][$jj], $ancetres["lieu_naiss"][$jj], $ancetres["date_deces"][$jj], $ancetres["lieu_deces"][$jj],$central);
            echo '</td>';
        } else 
        {    echo '<td width=100 class='.$cell_indiv.'><br><br><br><br><br><br><br><br><br></td>';
        }
    }
// fratrie
    elseif ($ii > 30  and $ii < 40 AND $ii != 35 AND $grid[$ii]) 
    {    
        echo '<td align=center class=cell_indiv OnMouseOver=afficher_bulle("F'.$grid[$ii].'") OnMouseOut=desafficher_bulle("F'.$grid[$ii].'")>';
        
        $jj = $grid[$ii] - 1;
        afficher_cellule ('F'.$grid[$ii]
		,$ancetres_fs["id_fs"][$_REQUEST['id']][$jj]
		,$ancetres_fs["sosa_dyn"][$_REQUEST['id']][$jj]
		,$ancetres_fs["nom"][$_REQUEST['id']][$jj]
		,$ancetres_fs["sexe"][$_REQUEST['id']][$jj]
		,$ancetres_fs["profession"][$_REQUEST['id']][$jj]
		,$ancetres_fs["date_naiss"][$_REQUEST['id']][$jj]
		,$ancetres_fs["lieu_naiss"][$_REQUEST['id']][$jj]
		,$ancetres_fs["date_deces"][$_REQUEST['id']][$jj]
		,$ancetres_fs["lieu_deces"][$_REQUEST['id']][$jj]
		);
        echo '</td>';
    }
// descendants
    elseif ($ii > 40  and $ii < 50 AND $grid[$ii]) 
    {    echo '<td align=center class=cell_indiv OnMouseOver=afficher_bulle("D'.$grid[$ii].'") OnMouseOut=desafficher_bulle("D'.$grid[$ii].'")>';
        
        $jj = $grid[$ii];
        afficher_cellule ('D'.$grid[$ii], $descendants["id_indi"][$jj], $descendants["sosa_d"][$jj], $descendants["nom"][$jj], $descendants["sexe"][$jj], $descendants["profession"][$jj], $descendants["date_naiss"][$jj], $descendants["lieu_naiss"][$jj], $descendants["date_deces"][$jj], $descendants["lieu_deces"][$jj]);

        echo '</td>';
    }
// fleches
    elseif ($ii > 50 AND $ii < 60)
    {    echo '<td align=center>';
        $indice_desc = $ii - 10; // on récupère la présence des descendants
        $jj = $grid[$indice_desc];

        if ($jj) {echo '<a href=arbre_mixte.php'.$url.'&id='.$descendants["id_indi"][$jj].'><img width=35 src=themes/fleche_bas.png></a>';}
        echo '</td>';
    }
// cases vides
    else 
    {echo '<td style="padding:0px;"></td>';}
}
echo '</tr></table>';

// on ferme le masque général
include ("_inc_html_card.php");