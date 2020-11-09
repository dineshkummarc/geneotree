<?php
require_once ("_recup_ascendance.inc.php");
require_once ("_caracteres.inc.php");
require_once ("_boites.inc.php");
//require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	


function afficher_bas_rupture()
{	global $degre;
	global $degre_plus;
	global $got_lang;
	global $prenom1;
	global $nom1;
	global $prenom2;
	global $nom2;
	
	$degre = $degre - 1;
	if ($degre == 1) {$suffixe = 'er';} else {$suffixe = 'eme';}
	echo "<tr><td colspan=5 align=center><b><br>".$prenom1." ".$nom1." ".$got_lang['Et']." ".$prenom2." ".$nom2." ".$got_lang['Sont']." ".$got_lang['Cousi']." ".$got_lang['Au']." ".$degre.$suffixe." ".$got_lang['Degre'];
	
	if ($degre_plus != 0)
	{	$degre = $degre - $degre_plus;
	echo " ".$got_lang['Et']." ".$degre."eme ".$got_lang['Degre'];
	}

	echo "</b></td></tr>";

	echo "<tr><td colspan=5 align=center>&nbsp;</td></tr>";
	echo "<tr><td colspan=5 align=center>&nbsp;</td></tr>";
}
/*************************************** DEBUT DU SCRIPT *********************************************/
$_REQUEST["ftop"] = 8;
$_REQUEST["scrolly"] = 0;

//$titre_page = "GeneoTree v".$got_lang['Relea']." - ".$got_lang['EtCon'];
require_once ("menu.php");

$row = recup_identite($_REQUEST['fid'], $_REQUEST['ibase']);

echo '<table><tr><td width=925px>';   // 1ere colonne sur 3

$url = url_request();

echo '<table><tr>';
echo '<td><a href=consanguinite_pdf.php'.$url.' title="'.$got_lang['IBPdf'].'" target=_blank><img border=0 width=35 heigth=35 src=themes/icon-print.png></a></td>';
echo "<td class=titre width=100%><b>".$got_lang['EtCon']." ".$row[1]." ".$row[0]."</b></td>";
echo '</tr></table>';

echo '<br>';
if ($_REQUEST['ityprech'] == NULL)
{	recup_consanguinite();
	echo '<table>';

	$degre = 0;
	$degre_plus = 0;
	$ii = 0;
	$i_t_consang=0;
	while ($ii < count($res['id']) )
	{	if ($res['id'][$ii] !== $row0_old)
		{	if ($row0_old !== NULL)
			{	afficher_bas_rupture();
				$i_t_consang++;
			}
			echo "<tr><td width=100% colspan=5 align='center'><b>Generation ".$res['generation'][$ii]."<br>&nbsp;</b></td></tr>";
			$degre = 0;
			$degre_plus = 0;
		}

					// trait vertical entre les cellules
		if ($res['id'][$ii] == $row0_old and $row0_old !== NULL)
		{	
			echo '<tr><td></td><td class=trait_arbre_verti><br></td><td></td><td></td><td class=trait_arbre_verti><br></td></tr>';
		}

		echo "<tr>";
		echo "<td class=cell_indiv colspan=2 align=center>";
		afficher_lien_indiv ($res['id1'][$ii], $url, 1,$res['nom1'][$ii],$res['prenom1'][$ii],"","",$res['sexe1'][$ii]);
		echo "<br>".affichage_date($res['date_naiss1'][$ii])." ".$res['lieu_naiss1'][$ii]."</td>";

					// affichage trait horizontal entre les premières cellules
			if ($res['id'][$ii] !== $row0_old)
			{	echo '<td class=trait_arbre_horiz>&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			}	else
			{	echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;</td>';
			}

		echo "<td class=cell_indiv colspan=2 align=center>";
		afficher_lien_indiv ($res['id2'][$ii],$url, 1,$res['nom2'][$ii],$res['prenom2'][$ii],"","",$res['sexe2'][$ii]);
		echo "<br>".affichage_date($res['date_naiss2'][$ii])." ".$res['lieu_naiss2'][$ii]." </td>";
		echo "</tr>";

		$degre++;
		if ($res['nom1'][$ii] == NULL and $res['prenom1'][$ii] == NULL)	{$degre_plus++;}
		$row0_old = $res['id'][$ii];
		$nom1 = $res['nom1'][$ii];
		$prenom1 = $res['prenom1'][$ii];
		$nom2 = $res['nom2'][$ii];
		$prenom2 = $res['prenom2'][$ii];
		
		$ii++;
	}
	if ($row0_old !== NULL)
	{	afficher_bas_rupture();
		$i_t_consang++;
	}
	echo "<tr><td colspan=5 align=center><b>".$i_t_consang." ".$got_lang['SouCo']."</b></td></tr>";
	echo "</table>";
}

if ($_REQUEST['ityprech'] == "RelSo")
{
	echo "";
}

echo '</td><td width=1px></td>';  //2eme colonne vide
echo'<td width=355px>';   //3eme colonne fiche
require_once ("fiche.php");
echo '</td></tr></table>'; // on ferme tout correctement
?>
</BODY>
</HTML>


