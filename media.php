<?php
require_once  ("_boites.inc.php");
require_once  ("_stat.inc.php");
require_once  ("_caracteres.inc.php");

/********************* DEBUT DU SCRIPT ****************************/
$_REQUEST["ftop"] = 8;
$_REQUEST["scrolly"] = 0;

//$titre_page = "GeneoTree v".$got_lang['Relea']." - ".$got_lang['Media'];
require_once ("menu.php");

echo '<table><tr><td width=925px>';   // 1ere colonne sur 3

if (!isset($_REQUEST['mpag'])) {$_REQUEST['mpag'] = "AUTR";}		// defaut sur les pictures
if (!isset($_REQUEST['mid'])) {$_REQUEST['mid'] = "";}
if (!isset($_REQUEST['lcont'])) {$_REQUEST['lcont'] = "";}

$url = url_request();

if ($_REQUEST['mpag'] == "AUTR")
{	$query = 'SELECT note_evene,max(type_evene)
		FROM got_'.$_REQUEST['ibase'].'_evenement
		WHERE type_evene = "FILE"
			AND note_evene != ""
		GROUP BY note_evene
		UNION
		SELECT attr_sourc,max(type_evene)
		FROM got_'.$_REQUEST['ibase'].'_even_sour
		WHERE type_sourc = "FILE" and id_sour = ""
		GROUP BY attr_sourc
		ORDER BY 1';
} else
{	$query = 'SELECT attr_sourc,max(type_evene)
		FROM got_'.$_REQUEST['ibase'].'_even_sour
		WHERE type_sourc = "FILE" and id_sour != ""
		GROUP BY attr_sourc
		ORDER BY 1';
}


echo "<table width=100%><tr>";
echo "<td><a HREF = media_pdf.php".$url." title='".$got_lang['IBPdf']."' target=_blank><img border=0 width=35 heigth=35 src=themes/icon-print.png></a></td>";
echo "<td class=titre width=100%>".$got_lang['LisMe']."</td>";
echo "</tr><tr><td></td><td align=center>";
echo '<form name=rech_typ method=post>';
afficher_radio_bouton("mpag", array("Individus","Sources"), array("AUTR","SOUR"), $_REQUEST['mpag']);
echo "</td><td></form></td></tr></table>";

if ($_REQUEST["mpag"] == 'AUTR') {$titre = 'Individus';}
if ($_REQUEST["mpag"] == 'SOUR') {$titre = 'Sources';}
	
$result = sql_exec($query,0);
$row = mysqli_fetch_row($result);
if ($row[0] == NULL)
{	echo '<br><p class=titre>'.$got_lang['PasMe'].'</p>';
} else
{	$lettre = recup_lettres ($result);

	if (!isset($_REQUEST['mdeb']))
	{	$_REQUEST['mdeb'] = $lettre['deb'][0];
		$_REQUEST['maff'] = $lettre['fin'][0];
	}
	if (!isset($_REQUEST['mfin'])) {$_REQUEST['mfin'] = "";}
	if (!isset($_REQUEST['mid'])) {$_REQUEST['mid'] = "";}

			// affichage des groupes de lettre trouvées 
	echo '<br><p align=center style="background-color:white;">';
	for ($i = 0; $i < count($lettre['deb']); $i++)
	{	echo ' <a class=menu_td href=media.php'.$url.'&mdeb='.$lettre['deb'][$i].'&mfin='.$lettre['fin'][$i].'&maff='.$lettre['aff'][$i].'&mid=><b>['.$lettre['deb'][$i].'-'.$lettre['aff'][$i].']</b></a> ';
	}
	echo '</p>';

	echo '<br><p class=titre  style="background-color:white;">'.$titre.' ['.$_REQUEST['mdeb'].'-'.$_REQUEST['maff'].']</p>';

	if ($_REQUEST['mid'] == "")
	{	$result = recup_media($_REQUEST['ibase'],$_REQUEST['mpag'],$_REQUEST['mdeb'],$_REQUEST['mfin']);
	
		echo '<table  class="bord_bas bord_haut"><tr>';
		$old = "";
		$j = 0;
		while ($row = mysqli_fetch_row($result))
		{	if ($row[0] !== $old)
			{	if ($j > 3)
				{	echo '</tr><tr>';
					$j = 0;
				}
				$j = $j + 1;

				$size_image = @getimagesize('picture/'.$_REQUEST['ibase'].'/'.$row[0]);
				echo '<td class="bords_verti bord_bas" align="center"><a href="javascript:PopupPic(&quot;'.str_replace(' ','+','picture/'.$_REQUEST['ibase'].'/'.$row[0]).'&quot;,'.$size_image[0].','.$size_image[1].')">';
				echo '<img border="0" src="picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0].'" >';

				echo '<br></a>';
				echo '<textarea cols=24 wrap=hard>'.$row[0].'</textarea>';
			}

			if ($row[1] !== 'FILE')
			{	echo ''.$got_tag[$row[1]].' '.$got_lang['De'].' ';
			}
			echo afficher_lien_indiv ($row[2], $url, $row[5],$row[3],$row[4],"","",$row[6]);
			if ($row[11] !== "")		//i.e id epouse existant
			{	echo $got_lang['Et']." ";
				echo afficher_lien_indiv ($row[11], $url, $row[9],$row[7],$row[8],"","",$row[10]);
			}

			$old = $row[0];
		}
	} else
	{	echo '<table><tr>';

		$query = 'SELECT * FROM got_'.$_REQUEST['ibase'].'_source
		WHERE id_sour = "'.$_REQUEST['mid'].'"';
		$result = sql_exec ($query);
		$row = mysqli_fetch_row($result);
		$row[1] = mb_ereg_replace(chr(13),'<br>',$row[1]);
		echo $row[1];
		echo '<br><img src="picture/'.$_REQUEST['ibase'].'/'.$_REQUEST['url'].'">';
	echo "</tr></table>";

	}
} 

echo '</td><td width=1px></td>';  //2eme colonne vide

if ($_REQUEST['mid'] == "")
{	echo'<td width=355px>';   //3eme colonne fiche
	require_once ("fiche.php");
	echo '</td>'; // on ferme tout correctement
}
echo '</tr></table>'; // on ferme tout correctement
?>