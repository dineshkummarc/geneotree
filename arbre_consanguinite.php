<?php
require_once ("_get_ascendancy.php");
require_once ("_functions.php");

function afficher_bas_rupture()
{   global $degre;
    global $degre_plus;
    global $got_lang;
    global $got_tag;
    global $nom1;
    global $nom2;

    $degre = $degre - 1;
    if ($got_lang['Langu'] == "en" AND $degre == 2) {$got_lang['DegrS']="nd";}
    if ($got_lang['Langu'] == "en" AND $degre == 3) {$got_lang['DegrS']="rd";}
    echo "<tr><td colspan=5 align=center><b><br>".$nom1." ".$got_tag['AND']." ".$nom2." ".$got_lang['Sont']." ".$got_lang['Cousi']." ".$got_lang['Au']." ".$degre.$got_lang['DegrS']." ".$got_lang['Degre'];
    
    if ($degre_plus != 0)
    {  $degre = $degre - $degre_plus;
       echo " ".$got_tag['AND']." ".$degre.$got_lang['DegrS']." ".$got_lang['Degre'];
    }

    echo "</b></td></tr>";

    echo "<tr><td colspan=5 align=center>&nbsp;</td></tr>";
    echo "<tr><td colspan=5 align=center>&nbsp;</td></tr>";
}
/*************************************** DEBUT DU SCRIPT *********************************************/

// open general mask
include ("menu.php");

$row = recup_identite($_REQUEST['id'], $_REQUEST['ibase']);

// print icon
?>
<script>
DivIcons ("DivIcon1", "themes/icon-print.png", "arbre_consanguinite_pdf.php" + "?" + HrefBase + "&id=<?php echo $_REQUEST['id']?>");
</script>
<?php

// name of individu
echo "<p class=titre>".$got_lang['EtCon']." ".$row[0]." ".displayDate($row[1],true)."</p><br>";

// get consanginity tree content
recup_consanguinite();   // put $res

// display consanginity tree
echo '<table width=100%>';

$degre = 0;
$degre_plus = 0;
$ii = 0;
$i_t_consang=0;
$row0_old = NULL;

if (isset($res['id'])) {
while ($ii < count($res['id']))
{ if ($res['id'][$ii] !== $row0_old)
  {  if ($row0_old !== NULL)
    {    afficher_bas_rupture();
        $i_t_consang++;
    }
    echo "<tr><td width=100% colspan=5 align='center'><b>".$got_lang['Gener']." ".$res['generation'][$ii]."<br>&nbsp;</b></td></tr>";
    $degre = 0;
    $degre_plus = 0;
  }

  // vertical line between cells
  if ($res['id'][$ii] == $row0_old and $row0_old !== NULL)
  { echo '<tr><td></td><td class=trait_arbre_verti><br></td><td></td><td></td><td class=trait_arbre_verti><br></td></tr>';
  }

  // cell first column
  echo "<tr>";
  echo '<td class=cell_indiv colspan=2 align=center OnMouseOver=afficher_bulle("C'.$ii.'") OnMouseOut=desafficher_bulle("C'.$ii.'")>';
  afficher_cellule ('C'.$ii, $res['id1'][$ii], "", $res['nom1'][$ii], $res['sexe1'][$ii], "", $res['date_naiss1'][$ii], $res['lieu_naiss1'][$ii], "", "","N");

  // horizontal line between first columns
  if ($res['id'][$ii] !== $row0_old)
  {    echo '<td class=trait_arbre_horiz>&emsp;</td>';
  }    else
  {    echo '<td>&emsp;</td>';
  }

  // cell second column
  echo '<td class=cell_indiv colspan=2 align=center OnMouseOver=afficher_bulle("C'.$ii.'") OnMouseOut=desafficher_bulle("C'.$ii.'")>';
  afficher_cellule ('C'.$ii, $res['id2'][$ii], "", $res['nom2'][$ii], $res['sexe2'][$ii], "", $res['date_naiss2'][$ii], $res['lieu_naiss2'][$ii], "", "","N");
  echo "</tr>";

  $degre++;
  if ($res['nom1'][$ii] == NULL)    {$degre_plus++;}
  $row0_old = $res['id'][$ii];
  $nom1     = $res['nom1'][$ii];
  $nom2     = $res['nom2'][$ii];
  
  $ii++;
}
if ($row0_old !== NULL)
{    afficher_bas_rupture();
    $i_t_consang++;
}}
echo "<tr><td colspan=5 align=center><b>".$i_t_consang." ".$got_lang['SouCo']."</b></td></tr>";
echo "</table>";

// on ferme le masque général
include ("_inc_html_card.php");