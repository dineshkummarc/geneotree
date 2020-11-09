<?php
header('Content-type: text/html; charset=utf-8'); 
require_once ("_sql.inc.php");

function verif_config()
{	include ("config.php");
// if ($INSTALLATION_OK) {echo 'OK';} else {echo 'KO';}
	return $INSTALLATION_OK;
}

function recup_sosa_principal ($base, $id)
{ 
	$query = 'SELECT CONCAT(prenom1," ",nom) 
		FROM got_'.$base.'_individu
		WHERE id_indi = "'.$id.'"';
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);

	return ($row[0]);
}

/*********************************DEBUT DU SCRIPT *************************/
if (!verif_config() and $_REQUEST['install'] != "OK")
{	echo '<script language="JavaScript" type="text/javascript">';
	echo 'window.location = "install.php"';
	echo '</script>'; 
	exit;
}

$pool = sql_connect();  

// si tout premier acces à l'application après l'installation. 
// initialise la langue de l'utilisateur en fonction de la langue du navigateur. 
if (!isset($_REQUEST['lang']))
{	if (mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2) == 'fr')
	{	$_REQUEST['lang'] = "fr";
	}	else 
	{	$_REQUEST['lang'] = 'en';
	}
}
if (!isset($_REQUEST['ibase'])) {$_REQUEST['ibase'] = "";}
if (!isset($_REQUEST['lcont'])) {$_REQUEST['lcont'] = "";}

require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	

			// detection chargement des bases geo. Si pas chargées, on présente l'onglet Carto a l'ouverture d'admin.
$query = 'SELECT code_pays FROM g__geodept LIMIT 0,1';
$result = sql_exec($query,2);		// les bases ne sont pas forcement encore chargees
$row = @mysqli_fetch_row($result);
if (isset($row[0]) ) {$carto = '';} else {$carto = 'geo';}

echo "<HTML>";
echo "<HEAD>";
echo '<META http-equiv="Content-type" content="text/html; charset=utf-8" name="author" content="Damien Poulain">';
echo "<TITLE>GeneoTree v".$got_lang['Relea']." - ".$got_lang['Accue']."</TITLE>";
echo "<LINK rel='stylesheet' href='themes/wikipedia.css' type='text/css'>";
echo '<link rel="shortcut icon" href="themes/geneotre.ico">';
echo "</HEAD>";

echo "<form name=rech_typ method=post>";
	echo '<table><tr>';
	/**************************** ADD LANGUAGE HERE *****************************/
	echo '<td align=center><font size=1><b>Fr</b></font><br><a href="index.php?lang=fr" title='.$got_lang['IBFr'].'><img border="0" src="themes/fr.gif" border="0" width="25" height="16"></a></td>';
	echo '<td align=center><font size=1><b>En</b></font><br><a href="index.php?lang=en" title='.$got_lang['IBEn'].'><img border="0" src="themes/en.gif" border="0" width="25" height="16"></a></td>';
	echo '<td align=center><font size=1><b>Hu</b></font><br><a href="index.php?lang=hu" title='.$got_lang['IBHu'].'><img border="0" src="themes/hu.gif" border="0" width="25" height="16"></a></td>';
	echo '<td align=center><font size=1><b>It</b></font><br><a href="index.php?lang=it" title='.$got_lang['IBIt'].'><img border="0" src="themes/it.gif" border="0" width="25" height="16"></a></td>';
	echo '<td align=center>&nbsp;<td>';
	/**************************** END LANGUAGE AREA ****************************/
	
	echo '<td width=300px>';
	echo '   <input type=text name=lcont size=28 value="'.$got_lang['Reche'].' '.$got_lang['Noms'].'">';
	echo '   <input type=submit value="'.$got_lang['Reche'].'">';
	echo '</td>';
	
	echo '<td width=450px class=titre>'.$got_lang['ChoBa'].'</td>';
	echo '<td class=menu_td width=100px><a class=menu_td href=admin.php?lang='.$_REQUEST['lang'].'&ibase=&ipag2='.$carto.' title="'.$got_lang['IBAdm'].'"><b>'.$got_lang['Admin'].'</b></a></td>';
	echo '</tr></table>';
echo "</form>";

	// positionnement du focus sur le formulaire
echo '<script language="JavaScript" type="text/javascript">
document.rech_typ.lcont.focus();
	</script>';	


/******************************************* AFFICHAGE DES BASES (bouton RECHERCHE) ********************************************************/

if ($_REQUEST['ibase'] == '' and $_REQUEST['lcont'] == '')
{	echo '<table class="bord_haut bord_bas">';
	echo '<tr class=ligne_tr2>';
	echo '<td class="titre bords_verti bord_bas">'.$got_lang['Bases'].'</td>';
	echo '<td class="titre bords_verti bord_bas">'.$got_lang['DefIn'].'</td>';
	echo '<td class="titre bords_verti bord_bas">'.$got_lang['Taill'].'</td>';
	echo '<td class="titre bords_verti bord_bas">'.$got_lang['Sourc'].'</td>';
	echo '<td class="titre bords_verti bord_bas">'.$got_lang['Media'].'</td>';
	echo '<td class="titre bords_verti bord_bas">'.$got_lang['PriCo'].'</td>';
	echo '</tr>';
	
	$query = 'SELECT * FROM g__base ORDER BY 1';
	$result = sql_exec($query,2);
	
	$i=0;
	while ($row = @mysqli_fetch_row($result))
	{
		if (strpos($row[0], '$club') )
		{	$href_menu = "source.php";
		} else
		{	$href_menu = "arbre_ascendant.php";
		}
		if ($i % 2 == 0) {echo '<tr class="ligne_tr1">';} else {echo '<tr class="ligne_tr2">';}
		echo "<td class=bords_verti><a href = ".$href_menu."?ibase=".$row[0]."&lang=".$_REQUEST['lang'].">".str_replace('$','',$row[0])."</td></a>";
		echo "<td class=bords_verti><b>".recup_sosa_principal($row[0],$row[1])."</b></td>";
		echo "<td class=bords_verti align='right'>".$row[2]."</td>";
		echo "<td class=bords_verti align='center'>".$row[4]."</td>";
		echo "<td class=bords_verti align='center'>".$row[5]."</td>";
		echo "<td class=bords_verti>".mb_substr($row[3],0,120)."</td>";
		echo "</tr>";
		$i++;
	}
	echo "</table>";
}
/******************************************* RECHERCHE MULTI BASES (bouton RECHERCHE) ********************************************************/

if ($_REQUEST['lcont'] !== NULL and $_REQUEST['lcont'] !== "")
{	echo '<table><tr><td class=menu_td>';
	echo '<a class=menu_td HREF = index.php>'.$got_lang['Retou'].'</a>';
	echo '</td></tr></table>';

	$query = 'SELECT * FROM g__base';
	$result = sql_exec($query,2);
	if ( @mysqli_num_rows($result) == '0' or !@mysqli_num_rows($result) )
	{	echo '<br><b>'.$got_lang['MesCh'].'</b>';
		exit;
	} 

	$liste='';
	while ($row = @mysqli_fetch_row($result))
	{	$query = 'SELECT distinct nom FROM got_'.$row[0].'_individu WHERE nom LIKE "'.$_REQUEST['lcont'].'%"';
		$result2 = sql_exec($query,0);

		while ($row2 = mysqli_fetch_row($result2))
		{	$liste['nom'][] = $row2[0];
			$liste['base'][]  = $row[0];
		}
	}

	if ( !$liste )
	{	echo '<br><b>'.$got_lang['NomPr'].' '.$got_lang['NonAf'].'</b>';
		exit;
	} 

	array_multisort ($liste['nom'],$liste['base']);

	echo '<br><table class="bord_haut bord_bas">';
	$ii = 0;
	$nom_old = "";
	while ($ii < count($liste['nom']))
	{	if ($ii % 2 == 0) 
		{	echo '<tr class="ligne_tr1">';} else {echo '<tr class="ligne_tr2">';
		}
		if ($liste['nom'][$ii] !== $nom_old)
		{	echo "<td class=bords_verti>".$liste['nom'][$ii]."</td>";
			echo "<td class=bords_verti><a href=listes.php?ibase=".$liste['base'][$ii]."&ipag=no&lcont=".$liste['nom'][$ii].">".$liste['base'][$ii]."</td></a>";
		} else
		{	echo "<td class=bords_verti></td>";
			echo "<td class=bords_verti><a href=listes.php?ibase=".$liste['base'][$ii]."&ipag=no&lcont=".$liste['nom'][$ii].">".$liste['base'][$ii]."</td></a>";
		}
		echo '</tr>';
		$nom_old = $liste['nom'][$ii];
		$ii++;
	}
	echo '</table>';
}

echo '<p align = "center"><font size="1"><b><a href=http://www.geneotree.com>'.$got_lang['Credi'].'</a></b></font></p>';

?>
