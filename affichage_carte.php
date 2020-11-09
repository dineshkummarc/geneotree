<?php
header('Content-type: text/html; charset=ISO-8859'); 
require_once ("_sql.inc.php");
require_once ("_recup_ascendance.inc.php");
require_once ("_recup_descendance.inc.php");
require_once ("_boites.inc.php");
require_once ("_caracteres.inc.php");
require_once  ("_stat.inc.php");   // pour la fonction recup_source_evene uniquement
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	


if (@$_REQUEST['ifin'] != "ge")
{ 
?>
	<!--  ============= script de bulle d aide ================= -->
	<SCRIPT TYPE="text/javascript" >

	function afficher_bulle(id)
	{	document.getElementById(id).style.visibility="visible";
	}
	
	function desafficher_bulle(id)
	{	document.getElementById(id).style.visibility="hidden";
	}
	</SCRIPT> 
	
	<!-- ================== fin du script de bulle d aide ============== -->
	</head>
<?php
}

function recup_liste_nom($fcont,$fcont2,$limit = NULL)
{	global $ADDRC;

	if ($fcont == NULL or $fcont == '&')	{$fcont = '%';}
	if ($fcont2 == NULL){$fcont2 = '%';}
	if ($limit == NULL) {$limit = 100;}		// on protege les pages HTML par defaut a 100 lignes, surtout quand fcont = %

	if ($_REQUEST['ipag'] == "AA")
	{	$query = 'SELECT id_indi,nom,prenom1,date_naiss,sosa_dyn,lieu_naiss,sexe,"'.$_REQUEST['ibase'].'"
			FROM got_'.$ADDRC.'_ascendants 
			WHERE lieu_naiss LIKE "'.$fcont.'" 
			and lieu_naiss != ""
			ORDER BY 2,3,4
			LIMIT 0,'.$limit;
		$result = sql_exec($query,0);
	}

	if ($_REQUEST['ipag'] == "AD")
	{	$query = 'SELECT id_indi,nom,prenom1,date_naiss,sosa_dyn,lieu_naiss,sexe,"'.$_REQUEST['ibase'].'"
			FROM got_'.$ADDRC.'_descendants 
			WHERE lieu_naiss LIKE "'.$fcont.'" 
			and lieu_naiss != ""
			ORDER BY 2,3,4
			LIMIT 0,'.$limit;
		$result = sql_exec($query);
	}
	if ($_REQUEST['ipag'] == "BIRT" or $_REQUEST['ipag'] == "DEAT" or $_REQUEST['ipag'] == "AUTR")
	{	$query = 'SELECT id_indi,nom_indi,prenom1_indi,date_evene,sosa_dyn,lieu_evene,sexe,"'.$_REQUEST['ibase'].'"
			FROM got_'.$ADDRC.'_sources 
			WHERE lieu_evene LIKE "'.$fcont.'" 
			and lieu_evene != ""
			ORDER BY 2,3,4
			LIMIT 0,'.$limit;
		$result = sql_exec($query);
	}
	if ($_REQUEST['ipag'] == "MARR")
	{	$query = 'SELECT id_husb,nom_husb,prenom1_husb,date_evene,sosa_dyn_husb,lieu_evene,sexe_husb,"'.$_REQUEST['ibase'].'"
			FROM got_'.$ADDRC.'_mariages 
			WHERE lieu_evene LIKE "'.$fcont.'" 
			and lieu_evene != ""
			ORDER BY 2,3,4
			LIMIT 0,'.$limit;
		$result = sql_exec($query);
	}
	return $result;
}

function alim_addr_multi ($table_source, $col_dept, $col_lieu)
{	global $ADDRC;
	global $rad;
//	global $table_source;
	
	$query = '
	SELECT a.'.$col_dept.',count(*)
	FROM got_'.$ADDRC.'_'.$table_source.' a
	INNER JOIN g__geolieu b ON (a.'.$col_dept.' = b.dept AND a.'.$col_lieu.' = b.commune)
	GROUP BY a.'.$col_dept.'
	ORDER BY 2 desc';
	$result = sql_exec($query,0);

	while ($row = mysqli_fetch_row($result))
	{	$rad['dept'][]  = $row[0];
		if ($row[0] > '0' and $row[0] < '1000') {$pays = 'FR';} else {$pays = 'US';}
		$rad['carte'][] = $pays.'_'.$row[0];
	}
//	if (!isset($_REQUEST['carte'])) {$_REQUEST['carte'] = $rad['carte'][0];echo 'pass';}
	if ($_REQUEST['carte'] == NULL) {$_REQUEST['carte'] = $rad['carte'][0];}

	$query = '
	SELECT a.'.$col_dept.',a.'.$col_lieu.',count(*),max(b.pays),max(b.longitude), max(b.latitude)
	FROM got_'.$ADDRC.'_'.$table_source.' a
	INNER JOIN g__geolieu b ON (a.'.$col_dept.' = b.dept AND a.'.$col_lieu.' = b.commune)
	WHERE a.'.$col_dept.' like "%"
	GROUP BY a.'.$col_dept.', a.'.$col_lieu;
	$result = sql_exec($query,0);	

	return $result;
}


/************************************************* DEBUT DU SCRIPT ********************************************************
carte contient le nom de la carte
lcont contient le nom de la commune du 2eme onglet
icont2 contient le filtre nom ou le prenom supplementaire
*/
$_REQUEST['lcont'] = @str_replace('+',' ',$_REQUEST['lcont']);
$_REQUEST['icont2'] = @str_replace('+',' ',$_REQUEST['icont2']);
$ADDRC = str_replace(array('.',':'),'',getenv("REMOTE_ADDR"));
$old = time();

//  1 - Initialisation des donnéees -  on rejoue les fonctions recup en alimentant des bases SQL ADDR asc, desc, sources, mariages (option BD_P).

//if (@$_REQUEST['carte'] == NULL)  // gain de performance. On ne relance pas les données de base pendant qu'on navigue entre les departements.
//																	// Tres important, surtout sur les grosses ascendances.
//{	
	if ($_REQUEST['ipag'] == "AA")	
	{	$ancetres['id_indi'] [0] = $_REQUEST['id'];
		$cpt_generations = 0;
		recup_ascendance ($ancetres,0,8,'BD_P');		// on limite a  pour garantir des temps de reponses corrects, eviter les arbres qui remontent aux rgyptiens
	}
	if ($_REQUEST['ipag'] == "AD")	
	{	$nb_generations_desc = 8;		// on limite a 8 pour garantir des temps de reponses corrects et eviter les arbres qui remontent aux egyptiens
		$descendants = '';
		$descendants ['id_indi'] [0] = $_REQUEST['id'];
		$cpt_generations = 0;
		recup_descendance (0,0,8,'BD_P','');
	}
	if ($_REQUEST['ipag'] == "BIRT" or $_REQUEST['ipag'] == "DEAT" or $_REQUEST['ipag'] == "AUTR")
	{	$result_evene = recup_source_evene($_REQUEST['ibase'], $_REQUEST['ibase2'], $_REQUEST['sdeb'], $_REQUEST['sfin'], $_REQUEST['pere'], $_REQUEST['mere'], $_REQUEST['dept'], $_REQUEST['lieu'], $_REQUEST['sosa'], $_REQUEST['spag'], $_REQUEST['iautr'], 0, 5000, 'BD_P');
	}
	if ($_REQUEST['ipag'] == "MARR")
	{ $result_evene = recup_source_marr($_REQUEST['ibase'], $_REQUEST['ibase2'], $_REQUEST['nom_wife'], $_REQUEST['dept'], $_REQUEST['lieu'], $_REQUEST['sosa'], $_REQUEST['sdeb'], $_REQUEST['sfin'], 0, 5000, 'BD_P');
	}
//}

// 2 - Recuperation des points cartographique des communes concernés, ainsi que la liste des départements concernés. 

$rad = array();
$ADDRC = str_replace(array('.',':'),'',getenv("REMOTE_ADDR"));   // arrive normalement par menu.php, mais on ne peut pas appeler le menu cause de l'export kml dans la meme page

if ($_REQUEST['ipag'] == "AA") { $table_source = 'ascendants'; $result = alim_addr_multi ($table_source, 'dept_naiss', 'lieu_naiss'); } 
if ($_REQUEST['ipag'] == "AD") { $table_source = 'descendants'; $result = alim_addr_multi ($table_source, 'dept_naiss', 'lieu_naiss'); }
if ($_REQUEST['ipag'] == "BIRT" or $_REQUEST['ipag'] == "DEAT" or $_REQUEST['ipag'] == "AUTR") { $table_source = 'sources'; $result = alim_addr_multi ($table_source, 'dept_evene', 'lieu_evene'); }
if ($_REQUEST['ipag'] == "MARR") { $table_source = 'mariages'; $result = alim_addr_multi ($table_source, 'dept_evene', 'lieu_evene'); }

// 3 - Calcul et stockage des pixels à afficher sur la pages WEB ou le fichier PDF

if ( $_REQUEST['ipag'] != "Sourc")
{	$query = 'SELECT code_pays, code_dept, longitude_g, longitude_d, largeur_jpg, latitude_h, latitude_b, hauteur_jpg, lib_dept
		FROM g__geodept
		where code_dept = "'.substr($_REQUEST['carte'],3,2).'"
		and code_pays = "'.substr($_REQUEST['carte'],0,2).'"';
	$result2 = sql_exec($query,0);
	$carte = mysqli_fetch_row($result2);   // attribut de LA carte dept

	while ($row = mysqli_fetch_row($result)) 
	{	$commcart['dept'][] = $row[0];
		$commcart['lieu'][] = $row[1];
		$commcart['nb'][]   = $row[2];
		$commcart['pays'][] = $row[3];
		$commcart['longi'][]= $row[4];
		$commcart['latit'][]= $row[5];
		$commcart['xpix'][]= round( ($row[4] - $carte[2])/($carte[3] - $carte[2])  * $carte[4]);
		$commcart['ypix'][]= round( ($row[5] - $carte[5])/($carte[6] - $carte[5])  * $carte[7]);
		$commcart['xmm'][]= 0.352777 * round( ($row[4] - $carte[2])/($carte[3] - $carte[2])  * $carte[4]) ;		// conversion pixel en millimetres
		$commcart['ymm'][]= 0.352777 * round( ($row[5] - $carte[5])/($carte[6] - $carte[5])  * $carte[7]);		// conversion pixel en millimetres
	}
}

$query = "DROP table got_".$ADDRC."_commcarte";
sql_exec($query,2);

$query = "CREATE TABLE got_".$ADDRC."_commcarte (
  dept		char(42) NOT NULL default '',
  commune	char(50) NOT NULL default '',
  nb		int(3) unsigned NOT NULL default '0',
  pays		varchar(2) NOT NULL,
  longitude	float NOT NULL default '0',
  latitude	float NOT NULL default '0',
  x			float NOT NULL default '0',
  y			float NOT NULL default '0',
  x_mm		float NOT NULL default '0',
  y_mm		float NOT NULL default '0',
  PRIMARY KEY  (commune, dept)
) ".$collate;
sql_exec($query);

for ($ii=0; $ii < count($commcart['dept']); $ii++)
{	$query = 'INSERT INTO got_'.$ADDRC.'_commcarte VALUES ("'.$commcart['dept'][$ii].'","'.$commcart['lieu'][$ii].'","'.$commcart['nb'][$ii].'","'.$commcart['pays'][$ii].'","'.$commcart['longi'][$ii].'","'.$commcart['latit'][$ii].'","'.$commcart['xpix'][$ii].'","'.$commcart['ypix'][$ii].'","'.$commcart['xmm'][$ii].'","'.$commcart['ymm'][$ii].'")';
	sql_exec($query);
}
	

// 4 - Preparation du titre du 2eme volet

if ($_REQUEST['ipag'] == 'AA' OR $_REQUEST['ipag'] == 'AD')
{	$query = 'SELECT prenom1,prenom2,prenom3,nom 
			FROM got_'.$_REQUEST['ibase'].'_individu 
			WHERE id_indi = '.$_REQUEST['id'];
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);
}

if ($_REQUEST['ipag'] == 'AA')	{$titre1 = $got_lang['Ascen'];}
else if ($_REQUEST['ipag'] == 'AD') {$titre1 = $got_lang['Desce'];}
else if ($_REQUEST['ipag'] == 'pr') {$titre1 = $got_lang['Preno'];}
else if ($_REQUEST['ipag'] == 'no') {$titre1 = $got_lang['Noms'];}

if ($_REQUEST['ipag'] == 'AA' OR $_REQUEST['ipag'] == 'AD')
{	$titre1 = $titre1.' '.$row[0].' '.$row[1].' '.$row[2].' '.$row[3];
	$titre2 = $carte[1].' '.ucfirst($carte[8]);
}
if ($_REQUEST['ipag'] == 'pr' OR $_REQUEST['ipag'] == 'no')
{	$titre1 = $titre1.' : '.$_REQUEST['icont2'];
	$titre2 = str_replace('&, ','',$titre2);
}

// 5 - Affichage final (html avec cartographie copyright GeneoTree ou generation Kml)

if ($_REQUEST['ifin'] != "ge")   // affichage carte jpeg
{	require_once ("menu.php");
	$url = url_request();		

	echo '<table><tr><td width=570px>';   // cadre principal

	echo '<table><tr>';
	echo "<td><a HREF=affichage_carte_pdf.php".$url."&ititre=".str_replace(' ','_',$titre1)."&addrc=".$ADDRC." title='".$got_lang['IBFih']."' target=_blank><img border=0 width=35 heigth=35 src=themes/icon-print.png></a></td>";
	echo "<td class=titre width=100% align=center><b>".$titre1."<br>".$titre2."</b></td>";
	echo "</tr></table>";

					/*************** affichage des radios-boutons pour le choix des departements *****************/

	echo '<form method=post>';
	echo '<b>Departements : </b>';
	if ($rad) {	afficher_radio_bouton ("carte",$rad['dept'],$rad['carte'],$_REQUEST['carte'],"YES"); }
	else {echo '<b>'.$got_lang['PasPo'].'</b>';}
	echo '</form>';

					/*************** affichage de la carte avec dessin des points (ellipses noires) ***********************/

	if (version_gd() == FALSE)
	{	exit;
	}	
	echo '<IMG src="affichage_carte_prep.php?ibase='.$_REQUEST['ibase'].'&id='.$_REQUEST['id'].'&ipag='.$_REQUEST['ipag'].'&carte='.$_REQUEST['carte'].'&addrc='.$ADDRC.'" usemap="#Map000">';

					/**************** affichage des bulles interactives html en superposition des points *****************/

	echo '<map name="Map000">';
			// recuperation des points
	$query = 'SELECT commune,nb,x,y FROM got_'.$ADDRC.'_commcarte';
	if ($_REQUEST['carte'] !== 'US_US')	{$query = $query.' WHERE dept = "'.substr($_REQUEST['carte'],-2).'"';}	// optimisation pas tranchante :=)
	$result = sql_exec($query,0);	
	$i = 0;
	while ($row = mysqli_fetch_row($result) )
	{	$x = $row[2]+4;
		$y = $row[3]+4;
		$x_bulle = $x+20;
		$y_bulle = $y+150;
		echo '<div class="bulle" id="b'.$i.'"  style="position: absolute; left: '.$x_bulle.'px; top: '.$y_bulle.'px;">'.$row[0].'['.$row[1].']</div>';
		echo '<area shape="circle" coords="'.$x.','.$y.',6" href="affichage_carte.php'.$url.'&lcont='.$row[0].'" OnMouseOver="afficher_bulle('.chr(39).'b'.$i.chr(39).')" OnMouseOut="desafficher_bulle('.chr(39).'b'.$i.chr(39).')">';
		$i = $i + 1;
	}
					/**********  cas particulier de l'affichage de la carte US en entier. Amener à disparaitre je pense **************/
	if ($_REQUEST['carte'] == 'US_US')
	{	echo '
		<area href=affichage_carte.php'.$url.'&carte=US_AL shape=polygon coords="359, 198, 354, 259, 366, 258, 366, 251, 389, 251, 391, 231, 384, 199">
		<area href=affichage_carte.php'.$url.'&carte=US_AR shape=polygon coords="296, 179, 349, 179, 329, 216, 329, 224, 302, 224, 302, 219, 297, 216">
		<area href=affichage_carte.php'.$url.'&carte=US_AZ shape=polygon coords="155, 172, 155, 247, 133, 247, 100, 231, 100, 224, 100, 211, 105, 209, 100, 199, 97, 184, 105, 184, 105, 172">
		<area href=affichage_carte.php'.$url.'&carte=US_CA shape=polygon coords="45, 107, 5, 107, 3, 126, 10, 146, 43, 204, 75, 231, 100, 231, 98, 221, 103, 206, 98, 196, 47, 146">
		<area href=affichage_carte.php'.$url.'&carte=US_CO shape=rect    coords="155, 119, 225, 173">
		<area href=affichage_carte.php'.$url.'&carte=US_CT shape=polygon coords="504, 105, 519, 105, 517, 115, 502, 115">
		<area href=affichage_carte.php'.$url.'&carte=US_DE shape=polygon coords="470, 152, 472, 177, 484, 177">
		<area href=affichage_carte.php'.$url.'&carte=US_FL shape=polygon coords="366, 259, 366, 251, 391, 251, 392, 254, 421, 258, 419, 254, 424, 256, 442, 308, 431, 338, 417, 338, 399, 276">
		<area href=affichage_carte.php'.$url.'&carte=US_GA shape=polygon coords="384, 198, 389, 246, 392, 254, 419, 258, 424, 254, 429, 239, 427, 231, 417, 216, 407, 204, 409, 198">
		<area href=affichage_carte.php'.$url.'&carte=US_ID shape=polygon coords="75, 14, 75, 49, 82, 62, 73, 77, 77, 81, 75, 109, 135, 109, 135, 72, 118, 76, 105, 59, 100, 61, 102, 47, 90, 34, 87, 27, 87, 14">
		<area href=affichage_carte.php'.$url.'&carte=US_IL shape=polygon coords="334, 99, 342, 106, 329, 113, 327, 126, 327, 136, 336, 144, 336, 153, 346, 163, 349, 178, 354, 166, 359, 166, 359, 158, 366, 146, 367, 114, 364, 101">
		<area href=affichage_carte.php'.$url.'&carte=US_IN shape=polygon coords="366, 109, 366, 148, 362, 161, 376, 161, 392, 144, 392, 109">
		<area href=affichage_carte.php'.$url.'&carte=US_IA shape=polygon coords="277, 86, 276, 98, 284, 126, 326, 126, 331, 126, 329, 114, 336, 113, 342, 103, 334, 99, 329, 88">
		<area href=affichage_carte.php'.$url.'&carte=US_KS shape=polygon coords="296, 171, 296, 146, 289, 134, 222, 134, 222, 172">
		<area href=affichage_carte.php'.$url.'&carte=US_KY shape=polygon coords="346, 178, 409, 176, 419, 166, 412, 151, 394, 144, 374, 161, 361, 161">
		<area href=affichage_carte.php'.$url.'&carte=US_LA shape=polygon coords="301, 224, 301, 238, 306, 251, 304, 264, 299, 266, 339, 276, 349, 276, 349, 259, 342, 251, 327, 251, 327, 246, 332, 233, 329, 224">
		<area href=affichage_carte.php'.$url.'&carte=US_ME shape=polygon coords="524, 63, 527, 66, 531, 96, 569, 69, 559, 36, 544, 36">
		<area href=affichage_carte.php'.$url.'&carte=US_MD shape=polygon coords="459, 136, 481, 136, 482, 153, 489, 153, 484, 164, 476, 164, 469, 149">
		<area href=affichage_carte.php'.$url.'&carte=US_MA shape=polygon coords="506, 96, 534, 96, 536, 116, 527, 116, 524, 106, 504, 106">
		<area href=affichage_carte.php'.$url.'&carte=US_MI shape=polygon coords="337, 46, 362, 58, 369, 66, 374, 109, 407, 109, 419, 89, 414, 63, 389, 41, 359, 26">
		<area href=affichage_carte.php'.$url.'&carte=US_MN shape=polygon coords="271, 15, 277, 55, 274, 60, 277, 63, 276, 88, 329, 88, 329, 83, 314, 71, 314, 58, 319, 56, 319, 46, 346, 28, 332, 26, 327, 26, 309, 18, 307, 20, 291, 15">
		<area href=affichage_carte.php'.$url.'&carte=US_MS shape=polygon coords="359, 198, 354, 259, 344, 259, 342, 249, 326, 251, 332, 231, 329, 216, 339, 198">
		<area href=affichage_carte.php'.$url.'&carte=US_MO shape=polygon coords="286, 126, 294, 138, 296, 146, 296, 179, 349, 179, 349, 173, 347, 171, 347, 164, 336, 158, 336, 144, 329, 138, 327, 126">
		<area href=affichage_carte.php'.$url.'&carte=US_MT shape=polygon coords="203, 66, 133, 67, 133, 74, 117, 76, 108, 59, 100, 59, 102, 44, 88, 34, 85, 27, 85, 17, 203, 17">
		<area href=affichage_carte.php'.$url.'&carte=US_NE shape=polygon coords="204, 92, 204, 119, 224, 119, 224, 134, 289, 134, 284, 127, 284, 117, 279, 102, 274, 97, 264, 94, 261, 92">
		<area href=affichage_carte.php'.$url.'&carte=US_NV shape=polygon coords="47, 106, 47, 148, 100, 200, 97, 183, 105, 183, 105, 106">
		<area href=affichage_carte.php'.$url.'&carte=US_NH shape=polygon coords="524, 65, 512, 98, 529, 96, 527, 65">
		<area href=affichage_carte.php'.$url.'&carte=US_NJ shape=polygon coords="491, 114, 487, 128, 479, 136, 491, 146, 501, 134, 501, 126, 504, 124">
		<area href=affichage_carte.php'.$url.'&carte=US_NM shape=polygon coords="155, 172, 213, 172, 213, 239, 162, 239, 163, 247, 155, 247">
		<area href=affichage_carte.php'.$url.'&carte=US_NY shape=polygon coords="442, 101, 449, 86, 474, 84, 481, 71, 491, 66, 504, 69, 506, 98, 502, 121, 489, 114, 489, 109, 482, 104, 452, 106, 444, 104">
		<area href=affichage_carte.php'.$url.'&carte=US_NC shape=polygon coords="422, 176, 481, 176, 484, 191, 474, 206, 454, 214, 444, 201, 434, 201, 427, 194, 414, 194, 412, 198, 397, 198, 419, 181">
		<area href=affichage_carte.php'.$url.'&carte=US_ND shape=polygon coords="203, 14, 203, 56, 277, 57, 270, 14">
		<area href=affichage_carte.php'.$url.'&carte=US_OH shape=polygon coords="392, 109, 434, 106, 434, 131, 414, 151, 401, 149, 392, 143">
		<area href=affichage_carte.php'.$url.'&carte=US_OK shape=polygon coords="296, 172, 299, 219, 291, 212, 282, 214, 272, 214, 259, 212, 252, 209, 244, 204, 244, 179, 214, 179, 214, 174">
		<area href=affichage_carte.php'.$url.'&carte=US_OR shape=polygon coords="77, 54, 58, 54, 35, 59, 22, 61, 18, 52, 5, 52, 7, 71, 3, 94, 7, 107, 77, 107, 77, 79, 73, 74, 82, 59">
		<area href=affichage_carte.php'.$url.'&carte=US_PA shape=polygon coords="434, 103, 434, 136, 481, 136, 489, 131, 484, 124, 491, 114, 484, 103, 477, 106, 444, 106">
		<area href=affichage_carte.php'.$url.'&carte=US_RI shape=rect    coords="517, 110, 525, 115">
		<area href=affichage_carte.php'.$url.'&carte=US_SC shape=polygon coords="429, 241, 424, 224, 409, 201, 414, 196, 429, 196, 434, 201, 444, 201, 456, 216">
		<area href=affichage_carte.php'.$url.'&carte=US_SD shape=polygon coords="204, 56, 277, 56, 274, 59, 277, 62, 277, 87, 276, 99, 267, 94, 259, 94, 204, 94">
		<area href=affichage_carte.php'.$url.'&carte=US_TN shape=polygon coords="347, 176, 422, 176, 394, 198, 339, 198">
		<area href=affichage_carte.php'.$url.'&carte=US_TX shape=polygon coords="214, 179, 214, 237, 179, 237, 196, 259, 199, 269, 211, 279, 217, 266, 229, 266, 254, 309, 262, 314, 269, 316, 269, 299, 277, 282, 286, 282, 304, 264, 307, 249, 302, 236, 302, 219, 291, 214, 276, 216, 257, 211, 244, 202, 244, 179">
		<area href=affichage_carte.php'.$url.'&carte=US_UT shape=polygon coords="105, 107, 105, 171, 155, 172, 155, 121, 135, 122, 135, 107">
		<area href=affichage_carte.php'.$url.'&carte=US_VT shape=polygon coords="504, 68, 506, 96, 512, 96, 522, 66">
		<area href=affichage_carte.php'.$url.'&carte=US_VA shape=polygon coords="481, 176, 407, 176, 421, 164, 424, 169, 439, 166, 439, 159, 459, 141, 466, 146, 472, 156">
		<area href=affichage_carte.php'.$url.'&carte=US_WA shape=polygon coords="2, 24, 15, 27, 15, 16, 75, 16, 75, 54, 57, 54, 35, 59, 20, 59, 20, 51, 13, 51, 8, 56">
		<area href=affichage_carte.php'.$url.'&carte=US_WV shape=polygon coords="432, 131, 437, 136, 444, 136, 444, 141, 454, 139, 459, 141, 447, 151, 437, 161, 437, 164, 424, 171, 414, 159, 416, 151, 426, 141, 434, 141">
		<area href=affichage_carte.php'.$url.'&carte=US_WI shape=polygon coords="321, 43, 319, 53, 314, 60, 314, 70, 329, 83, 331, 95, 339, 100, 364, 100, 367, 68, 364, 65, 362, 57">
		<area href=affichage_carte.php'.$url.'&carte=US_WY shape=rect    coords="135, 67, 205, 121">
		';
	}
	echo '</map>';
	
	/***************** affichage des individus correspondants aux communes ***********/

	echo '</td>';

	if ($_REQUEST['lcont'] != NULL)
	{	
		$result = recup_liste_nom($_REQUEST['lcont'],$_REQUEST['icont2']);
		
		echo '<td width = 355px>';
		echo '<p class=titre>'.$_REQUEST['lcont'];
		if ($_REQUEST['ipag'] == 'no' or $_REQUEST['ipag'] == 'pr') {echo ' - '.$_REQUEST['icont2'];}
		echo '</p>';
	
		echo '<table>';
		$ii = 0;
		while ($row = mysqli_fetch_row($result))
		{	if ($ii % 2 == 0)	{echo '<tr class=ligne_tr1>';} else {echo '<tr class=ligne_tr2>';}
			echo '<td class=bords_verti width=215>';
			echo afficher_lien_indiv ($row[0],$url, $row[4], $row[1],$row[2],"","",$row[6]);
			echo '</td>';
			echo '<td class=bords_verti width=15>&nbsp;'.affichage_date($row[3],"YES").'</td>';
		 	echo '<td class=bords_verti width=70>&nbsp;'.substr($row[5],0,7).'</td>';
			echo '</tr>';
			$ii++;
		}
	echo '<td width=355px>';  // grand cadre
	require_once ("fiche.php");
	echo '</td>';
		echo '</table>';
		echo '</td>';
	}
	echo '</tr></table>';

} else		/********************* Generation du fichier KML *************************************/
{
						// recuperation des points
	$query = 'SELECT commune,longitude,latitude,nb FROM got_'.$ADDRC.'_commcarte 
	WHERE longitude != -20 and latitude != 0 ORDER BY 1';
	$result = sql_exec($query,0);

	$result_nom = recup_liste_nom('',$_REQUEST['icont2'], 5000);

					// Ecriture du KML. Attention au code couleur "XBGR" au lieu de "RGB", X etant la transparence -->

	header("Content-type: application/vnd.google-earth.kml+xml");
	header('Content-Transfer-Encoding: binary');
	header('Content-Disposition: attachment; filename="GeneoTree.kml"');

	$content = '<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://earth.google.com/kml/2.2">
<Folder>
  <name>'.strtoupper($titre1.' '.$titre2).'</name>
  <open>1</open>';
			while ($row = mysqli_fetch_row($result) )
			{	$content = $content.'
  <Placemark>
    <name>';
				while ($row_nom = mysqli_fetch_row($result_nom))
				{	if ($row_nom[5] == $row[0])
					{	$content = $content. $row_nom[1].' '.$row_nom[2].' '.substr($row_nom[3],-4).' - 
		';
					}
				}
				mysqli_data_seek($result_nom,0);
    $content = $content.'
    </name>
    <open>1</open>
    <description>'.$row[0].'</description>
   <styleUrl>root://styleMaps#default+nicon=0x304+hicon=0x314</styleUrl>
	<Style id="default_copy0+icon=http://maps.google.com/mapfiles/kml/pal3/icon60.png_copy0">
		<LabelStyle><color>DDFFFF00</color></LabelStyle>
		<IconStyle>
			<color>DDFFFF00</color>
			<scale>1.5</scale>
			<Icon><href>http://maps.google.com/mapfiles/kml/paddle/wht-blank.png</href></Icon>
			<hotSpot x="32" y="1" xunits="pixels" yunits="pixels"/>
		</IconStyle>
	</Style>

    <Point>
      <coordinates>'.$row[1].','.$row[2].',5</coordinates>
    </Point>
  </Placemark>';
			}
			$content = $content.'
</Folder>
</kml>'; 

//echo chr(255).chr(254).mb_convert_encoding($content, 'UTF-16LE', 'UTF-8');
//echo mb_convert_encoding($content, 'UTF-16LE', 'UTF-8');
echo utf8_decode($content);

$query = 'DROP TABLE got_'.$ADDRC.'_commcarte';
sql_exec($query);

}

$query = 'DROP TABLE got_'.$ADDRC.'_'.$table_source;
sql_exec($query);

?>
