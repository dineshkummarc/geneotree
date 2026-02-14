<?php
require_once ("_get_descendancy.php");
require_once ("_functions.php");

/*************************************** RECUPERATION DES DONNEES *********************************************/

// On ouvre le masque  général
include ("menu.php");

// alimentation de nb_generation_desc indispensable pour l'appel de recup_descendance
if (!in_array($_REQUEST['pag'], array(1,2,3,4,5,6))) {$_REQUEST['pag'] = 6;}

$nb_generations_desc = $_REQUEST['pag'];
$descendants ['id_indi'] [0] = $_REQUEST['id'];
recup_descendance (0,0,0,'ME_G','MARR');
$query = 'DROP TABLE IF EXISTS '.$sql_pref.'_'.$ADDRC.'_desc_cles';
sql_exec($query);
// afficher_descendance();//return; //debogue
array_multisort ($descendants['indice'],$descendants['id_indi'],$descendants['generation'],$descendants['nom'],$descendants['prenom2'],$descendants['sexe'],$descendants['profession'],$descendants['date_naiss'],$descendants['lieu_naiss'],$descendants['date_deces'],$descendants['lieu_deces'],$descendants['id_parent'],$descendants['sosa_d'],$descendants['id_conj']   ,$descendants['date_maria'],$descendants['lieu_maria'],$descendants['nom_conj']  ,$descendants['pre2_conj']  ,$descendants['sexe_conj'] ,$descendants['sosa_conj']);
// usort($descendants, function($a, $b) {return strcmp($a['indice'], $b['indice']); });

// détection fleche suivante
$old_ii = 0;
for ($ii=0; $ii < count ($descendants['id_indi']); $ii++)
{  if ($descendants["generation"][$ii] == $_REQUEST["pag"])
   {  $descendants['fleche'][$old_ii] = "O";
   } else 
   {  $descendants['fleche'][$old_ii] = "N";
   }
   $old_ii = $ii;
}
$descendants['fleche'][$old_ii] = "N";

?>
<script>
flag_excel = "<?php echo $flag_excel?>";
DivIcons ("DivIcon1", "themes/icon-print.png", "arbre_descendant_pdf.php" + "?" + HrefBase + "&id=<?php echo $_REQUEST['id']?>&pag=<?php echo $_REQUEST["pag"]?>&pori=AD&type=");
if (flag_excel !== "No")
{   DivIcons ("DivIcon2", "themes/icon-excel.png", "arbre_descendant_pdf.php" + "?" + HrefBase + "&id=<?php echo $_REQUEST["id"]?>&pag=<?php echo $_REQUEST["pag"]?>&pori=AD&type=excel");
}
DivIcons ("DivIcon3", "themes/icon-folder-grey.png", "fiche_pdf.php" + "?" + HrefBase + "&id=<?php echo $_REQUEST["id"]?>&pag=<?php echo $_REQUEST["pag"]?>&pori=AD");
dataJson = `[{"Code":"1", "Nb":0},{"Code":"2", "Nb":0},{"Code":"3", "Nb":0},{"Code":"4", "Nb":0},{"Code":"5", "Nb":0},{"Code":"6", "Nb":0}]`;
SubMenuJson(dataJson);

display_stat_descendance (<?php echo $_REQUEST['id'];?>);
</script>
<?php

// arbre
echo '
<table style="border-collapse:separate;">
';

for ($ii=0; $ii < count ($descendants['id_indi']); $ii++)
{ if ($descendants["generation"][$ii] != $_REQUEST["pag"])
  { $colspandeb = $descendants["generation"][$ii] + 1;
    if ($descendants["generation"][$ii] == 0) {$cell_indiv = "cell_indivP";} else {$cell_indiv = "cell_indiv";}
    
    //spaces before cell
	echo '<tr><td colspan='.$colspandeb.' align=right></td>';

    // lines before cell
    if ($ii != 0) 
	{ echo '
      <td align=right><img heigth=100% src=themes/branche-coude.png></td>';
	} else 
	{ echo '
      <td align=right></td>';
	}
    if ($ii == 0) { $central = "O";}

    // cell
    echo '
    <td colspan=2 class='.$cell_indiv.' OnMouseOver=afficher_bulle("D'.$ii.'") OnMouseOut=desafficher_bulle("D'.$ii.'")>';
    afficher_cellule ('D'.$ii, $descendants["id_indi"][$ii], $descendants["sosa_d"][$ii], $descendants["nom"][$ii], $descendants["sexe"][$ii], $descendants["profession"][$ii], $descendants["date_naiss"][$ii], $descendants["lieu_naiss"][$ii], $descendants["date_deces"][$ii], $descendants["lieu_deces"][$ii],$central);
    $colspanfin = 7 - $descendants["generation"][$ii];
    echo '</td>';

    // narrow after cell
    if ($descendants["fleche"][$ii] == "O") 
    {    echo '
        <td colspan='.$colspanfin.'><a href=arbre_descendant.php'.$url.'&id='.$descendants["id_indi"][$ii].'><img src=themes/fleche_droite.png></a></td>';
    }
    echo '
    </tr>';
  }
}
echo '
</table>';


/*
            // affichage des conjoints
//    if (isset($descendants['id_conj'][$ii]))
//    {
//        echo ', '.$got_lang['Marie'];
//        if ($got_lang['Langu'] == 'fr' and $descendants['sexe'][$ii] == 'F') {echo 'e';}
//        if ($descendants['date_maria'][$ii] != "")
//        {    echo " <b> ".displayDate($descendants['date_maria'][$ii]);
//            echo "</b>";
//        }
//        if ($descendants['lieu_maria'][$ii] != "")
//        {    echo ' '.$got_lang['Situa'].' '.$descendants['lieu_maria'][$ii]." ";
//            if ($descendants['dept_maria'][$ii] != "")
//            {    echo '('.$descendants['dept_maria'][$ii].')';
//            }
//        }
//        if ($descendants['nom_conj'][$ii] != '' or $descendants['pre1_conj'][$ii] != '')
//        {    echo $got_lang['Avec'];
//            afficher_lien_indiv ($descendants['id_conj'][$ii], $descendants['sosa_conj'][$ii], $descendants['nom_conj'][$ii],$descendants['pre1_conj'][$ii],$descendants['pre2_conj'][$ii],$descendants['pre3_conj'][$ii],$descendants['sexe_conj'][$ii]);
//        }
//        $tota_desce++;
//    }
    
// une ligne blanche pour separer l'affichage des totaux
//if ($ii % 2 == 0) 
//
*/

// on ferme le masque général
include ("_inc_html_card.php");