<?php
header('Content-type: text/html; charset=utf-8'); 
//header('Content-type: text/html; charset=ISO-8859'); 
// menu.php est l'entete de toutes les pages GeneoTree après le choix du gedcom dans la page index.php
// menu.php gère le mot de passe ami, l'affichage des préférences utilisateurs : langue, theme, intervalle, taille des listes.
// menu.php appelle _sql.inc.php et les fichiers de langue pour toutes pages, hors pdf et admin
require_once  ("_sql.inc.php");
require_once ("_boites.inc.php");
require_once ("_caracteres.inc.php");
include ("config.php");

/*********************************DEBUT DU SCRIPT *************************/

$pool = sql_connect();
if (!Serveurlocal())
{	if(@$_POST["pass"] == $passe_ami and $passe_ami !== "") 
	{	
//		setcookie("passeami",crypt($passe_ami,"dam")); // crypt - non facile de deviner
		setcookie("passeami",$passe_ami);
		Header("Location: ".$_SERVER["PHP_SELF"]."?ibase=".$_REQUEST['ibase']);
	}

//	$isOK = (strcmp(@$_COOKIE["passeami"], crypt($passe_ami,"dam")) == 0);
	$isOK = (strcmp(@$_COOKIE["passeami"], $passe_ami) == 0);
	if(!$isOK) 
	{	echo $got_lang['Frie1'];
		echo '<form name=mdp method="post">';
		echo '<table><tr>';
		echo '<td><input type="password" name="pass"></td>';
		echo '<td><input type="Submit" value="Entrer"></td>';
		echo '</tr></table>';
		echo '</form>';
	
			// positionnement du focus sur le formulaire
		echo '<script language="JavaScript" type="text/javascript">
		document.mdp.pass.focus();
			</script>';	
		exit;
	}
}
$ADDRC = str_replace(array('.',':'),'',getenv("REMOTE_ADDR"));

/*********************** DEBUT GESTION DES PREFERENCES utilisateur ******************/

/*  Principe :
		Il existe des préférences par défaut pour chaque base (table got_base), gérées par l'administrateur qui peut vouloir qu'une base aient des palmares de 30, theme wood.
    L'utilisateur les modifier et garder ses préférences (table got__pref).
*/

global $pool;
			// creation g__pref la premiere fois
$query = "CREATE TABLE g__pref
	(	 addr  varchar(20) NOT NULL
		,base  varchar(255) NOT NULL
		,cujus int
		,forma varchar(6)
		,inter varchar(4)
		,lang  varchar(2)
		,palma varchar(4)
		,theme varchar(16)
		,pref1 varchar(8)
		,pref2 varchar(8)
		,pref3 varchar(8)
		,pref4 varchar(8)
		,pref5 varchar(8)
		,pref6 varchar(8)
		,pref7 varchar(8)
		,pref8 varchar(8)
		,pref9 varchar(8)
		,PRIMARY KEY ADDR_PK (addr,base)
	) ".$collate;
sql_exec($query,2);

			// on recupere systematiquement toutes les preferences utilisateurs
$query = 'SELECT * FROM g__pref WHERE addr = "'.$ADDRC.'" and base ="'.$_REQUEST['ibase'].'"';
$result = sql_exec($query,2);
$row = @mysqli_fetch_row($result);

/* si variable POST vides
      si Variables Base existantes : on renseigne les variables POST avec les variables basesa base 
		  si Variables Base inexistantes ((1er accès de l'IP sur la base) : on renseigne avec la base et les POSTs avec les defauts 
*/

if ( !isset($_REQUEST['fid']) )
{	if ( isset($row[0]) ) 	
	{	$_REQUEST['fid']      = $row[2];
		$_REQUEST['forma']      = $row[3];	
		$_REQUEST['intervalle'] = $row[4];
		$_REQUEST['lang']       = $row[5];
		$_REQUEST['palma']      = $row[6];
		$_REQUEST['theme']      = $row[7];
	} else // 1er acces de l'IP au menu
	{ // on recupere les defauts geres par l'administrateur
		
		$query = "SELECT * FROM g__base WHERE base = '".$_REQUEST['ibase']."'";
		$result = sql_exec($query);
		$row = mysqli_fetch_row($result);
		
		$_REQUEST['fid'] = $row[1];
		$_REQUEST['forma'] = $row[11];
		$_REQUEST['intervalle'] = $row[10];
		$_REQUEST['lang'] = $row[7];
		$_REQUEST['palma'] = $row[9];
		$_REQUEST['theme'] = $row[8];
		$query = 'INSERT INTO g__pref value ("'.$ADDRC.'","'.$_REQUEST['ibase'].'","'.$row[1].'","'.$row[11].'","'.$row[10].'","'.$row[7].'","'.$row[9].'","'.$row[8].'","'.substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2).'","'.date("Ymd",time()).'",NULL,NULL,NULL,NULL,NULL,NULL,NULL)';
		sql_exec($query,0);
	}
} 
// si Variables POST existantes et différentes des variables Bases, on stocke la nouvelle variable POST en base de données
if ( $_REQUEST['fid'] !=  $row[2] or $_REQUEST['forma'] !=  $row[3] or $_REQUEST['intervalle'] !=  $row[4] or $_REQUEST['lang'] !=  $row[5] or $_REQUEST['palma'] !=  $row[6] or $_REQUEST['theme'] !=  $row[7] ) 
{	$query = 'UPDATE g__pref SET cujus="'.$_REQUEST['fid'].'",forma="'.$_REQUEST['forma'].'",inter="'.$_REQUEST['intervalle'].'",lang="'.$_REQUEST['lang'].'",palma="'.$_REQUEST['palma'].'",theme="'.$_REQUEST['theme'].'" ,pref1="'.substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2).'",pref2="'.date("Ymd",time()).'"
	WHERE addr = "'.$ADDRC.'" and base ="'.$_REQUEST['ibase'].'"'; 
	sql_exec($query,0);
}

// on peut appeller le fichier de langue, la variable POST lang est forcement remplie
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	

/*********** FIN GESTION DES PREFERENCES ************************/

if (!isset($_REQUEST['id'])) {$_REQUEST['id'] = $_REQUEST['fid'];}
if (!isset($_REQUEST['fid'])) {$_REQUEST['fid'] = $_REQUEST['id'];}
if (!isset($_REQUEST['ifin'])) {$_REQUEST['ifin'] = "";}
if (!isset($_REQUEST['csg'])) 
{	$query = "SELECT consang FROM g__base WHERE base = '".$_REQUEST['ibase']."'";
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);
	$_REQUEST['csg'] = $row[0];
}

$url = url_request();

			// debut de l'affichage
if ($_REQUEST['ifin'] !== "ge")
{	echo '<!DOCTYPE html>';
	echo "<HTML>";
	echo "<HEAD>";  
  echo '<META http-equiv="Content-type" content="text/html; charset=utf-8" name="author" content="Damien Poulain">'; //l'index est caractere latin car geneotree n'a que 3 langues pour l'instant. Les 3 sont latines.
//  echo '<META http-equiv="Content-type" content="text/html; charset=iso-8859-1" name="author" content="Damien Poulain">'; //l'index est caractere latin car geneotree n'a que 3 langues pour l'instant. Les 3 sont latines.
	echo "<TITLE>GeneoTree v".$got_lang['Relea']."</TITLE>";
	echo "<LINK rel='stylesheet' href='themes/".$_REQUEST['theme'].".css' type='text/css'>";
	echo '<link rel="shortcut icon" href="themes/geneotre.ico">';

?>
<SCRIPT language="javascript">

	function afficher_bulle(id)
	{	document.getElementById(id).style.visibility="visible";
	}

	function desafficher_bulle(id)
	{	document.getElementById(id).style.visibility="hidden";
	}

	function afficher_fiche(idelement)
	{	var y = document.getElementById(idelement).offsetTop - 0;
		var scroll_y = document.body.scrollTop || document.documentElement.scrollTop;
		var yy = window.innerHeight || document.body.clientHeight;
		var fid = idelement.substring(3);
		var ftop = Math.round(y / yy) * yy + (scroll_y % yy) - yy + 8;
		if (ftop < 0) {ftop = 8;}
		window.location.replace("<?php echo basename($_SERVER["PHP_SELF"]).$url?>&fid=" + fid + "&ftop=" + ftop  + "&scrolly=" + scroll_y);
		return;
	}

  function PopupPic(sPicURL, largeur, hauteur) 
  {	largeur = largeur + 200;
  	hauteur = hauteur + 200;
  	window.open( "popup.php?pict="+sPicURL, "", "resizable=non, Width="+largeur+", Height="+hauteur+"  "); 
  } 

</script> 

<?php
	echo "</HEAD>";
	echo "<BODY>";
	echo '<table>';					// grand cadre
	echo '<tr>';
	echo '    <td class=trait_double colspan=4 width=920px>';

	echo '<table><tr>';				//cadre superieur haut
	echo '<td width=20><a href = index.php?lang='.$_REQUEST["lang"].' title="'.$got_lang['IBHom'].'"><img border=0  width=35 height=35 src="themes/icon-home.png"></a></td>';
	if ($_REQUEST['ibase'] != '$club') 
	{	echo '<td class=titre width=480><b>Base '.ucfirst($_REQUEST['ibase']).'</b></td>';
	}	else 
	{	echo '<td class=titre width=480><b>Base '.mb_substr(ucfirst($_REQUEST['ibase']),1).'</b></td>';
		}
	/**************************** ADD LANGUAGE HERE *****************************/
	echo '<td align=right width=185 valign="middle"><b>'.$got_tag["LANG"].' </b>';
	echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&lang=en" title="'.$got_lang['IBEn'].'"><img border="0" src="themes/en.gif" border="0" width="25" height="16"></a>';
	echo '&nbsp;<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&lang=fr" title="'.$got_lang['IBFr'].'"><img border="0" src="themes/fr.gif" border="0" width="25" height="16"></a>';
	echo '&nbsp;<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&lang=hu" title="'.$got_lang['IBHu'].'"><img border="0" src="themes/hu.gif" border="0" width="25" height="16"></a>';
	echo '&nbsp;<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&lang=it" title="'.$got_lang['IBIt'].'"><img border="0" src="themes/it.gif" border="0" width="25" height="16"></a>';
	echo '</td>';
	/**************************** END LANGUAGE AREA ****************************/

	/**************************** ADD THEME HERE *****************************/
	echo '<td width=215 align=right>&nbsp;<b>Theme </b>';
	echo '<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&theme=wikipedia"><img border="0" src="themes/wiki.png" border="0" width="25" height="16"></a>';
	echo '&nbsp;<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&theme=wood"><img border="0" src="themes/wood.png" border="0" width="25" height="16"></a>';
	echo '&nbsp;<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&theme=aqua"><img border="0" src="themes/aqua.png" border="0" width="25" height="16"></a>';
	echo '&nbsp;<a href="http://'.$_SERVER["SERVER_NAME"].$_SERVER["PHP_SELF"].$url.'&theme=ivory"><img border="0" src="themes/ivory.png" border="0" width="25" height="16"></a>';
	echo '</td>';
	/**************************** END THEME AREA *****************************/
	
	
	echo '</tr>';
	echo '<form method=post>';

	echo '<tr>';
	echo '<td colspan=3 align=left height=17>';
	echo '<a class=menu_td href = arbre_ascendant.php'.$url.' title="'.$got_lang['IBArb'].'">'.$got_lang['MenAr'].'</a>&nbsp;';
	echo '<a class=menu_td href = listes.php'.$url.' title="'.$got_lang['IBLis'].'">'.$got_lang['MenLi'].'</a>&nbsp;';
	echo '<a class=menu_td href = graphes.php'.$url.' title="'.$got_lang['IBGra'].'">'.$got_lang['MenGr'].'</a>&nbsp;';
	echo '<a class=menu_td href = stat.php'.$url.' title="'.$got_lang['IBSta'].'">'.$got_lang['PalNo'].'</a>&nbsp;';
	echo '<a class=menu_td href = source.php'.$url.' title="'.$got_lang['IBSou'].'">'.$got_lang['Sourc'].'</a>&nbsp;';
	echo '<a class=menu_td href = media.php'.$url.' title="'.$got_lang['IBMed'].'">'.$got_lang['Media'].'</a>';
	echo '</td><td align=right valign=top><b>'.$got_lang["Forma"].'</b>';
	afficher_radio_bouton("forma",array("A4","Letter"),array("A4","Letter"),$_REQUEST['forma']);
	echo '</form>';
	echo '</td></tr></table>';
	echo '		</td>';
	echo '</tr></table>';  
}
?>