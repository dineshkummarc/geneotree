<?php
error_reporting(0);	// Decommenter pour la mise en production
$collate	= ' engine = myisam default character set utf8 collate utf8_general_ci'; //evite les defauts comme ENGINE = INNODB

function ServeurLocal()
{	$enligne = getenv("REMOTE_ADDR");
	return ($enligne == '127.0.0.1' || $enligne == '192.168.181.2' || $enligne == '192.168.1.2' || $enligne == '192.168.31.22' || $enligne == '::1');
	// return (FALSE);
}

function geo_pertinente($dept_naiss)
{ // $dept_naiss tableau d'1 colonne. On teste si le tableau contient au moins une valeur pertinente, cad existante dans geo__geo
	// pour que les requetes affichage_carte.php (jointure individu, g__geolieu repondent)
	// Il faudrait tester la jointure commune, mais c'est trop couteux (plusieurs dizaines de milliers de lignes). 
	// Implication : dans affich carte, il faut gérer l'exception où toutes les communes d'une population sont inconnues dans un dept connu. Ca arrive...
	global $pool;

	$query = 'SELECT code_dept FROM g__geodept';
	$result = sql_exec($query,2);
	while ($row = @mysqli_fetch_row($result))
	{	$code_dept[] = $row[0];
	}

	$final = FALSE;
	for ($ii=0; $ii < count($dept_naiss); $ii++)
	{	if (@array_search ($dept_naiss[$ii], $code_dept) )
		{	$final = TRUE;
		}
	}
	return $final;
}

function maj_cujus ($base,$fid,$usage = 'ADMIN')
{	global $ancetres;		
	global $ancetres_fs;
	global $communs;
	global $cpt_generations;
	global $ADDRC;  // pour passer la variable a recup_ascendance et g__pref

	require_once ("_recup_ascendance.inc.php");

	$ancetres[][] = ''; $descendants = '';$cpt_generations = 0;
	$ancetres['id_indi'][0] = $fid;
	$nb_generations = 100;
	recup_ascendance($ancetres,0,40,'');  

			// si ancetres communs detectes, alors le decujus est consanguin
	if (isset($communs ['id'][0]))   { $consang = 'O';} else { $consang = 'N';}
	$query = 'UPDATE g__base SET consang = "'.$consang.'" WHERE base = "'.$base.'"';
	sql_exec($query);

			// remise à zéro des sosas précédents
	$query = "UPDATE `got_".$base."_individu`
		SET sosa_dyn = 0";
	sql_exec($query);

			// mise a jour des nouveaux sosas
	$i = 0;
	while ($i < count($ancetres['id_indi']) )
	{	$query = "UPDATE `got_".$base."_individu`
				SET sosa_dyn = ".$ancetres['sosa_d'][$i]."
				WHERE id_indi = ".$ancetres['id_indi'][$i];
		sql_exec($query,0);
		$i++;
	}
			// mise a jour de la table de reference
	$query = 'UPDATE g__base 
		SET sosa_principal = '.$fid.'
		WHERE base = "'.$base.'"';
	sql_exec($query);
}

function existe_sosa()
{	$query = 'SELECT distinct SOSA_DYN FROM got_'.$_REQUEST['ibase'].'_individu';
	$result = sql_exec($query);
	if (@mysqli_num_rows($result) !== NULL)  // si de cujus non calcule, inhiber la fonctionnalite sosa
	{	return TRUE;
	} else 
	{	return FALSE;
	}
}

function recup_identite($id_indi,$base)
{	$query = 'SELECT nom,prenom1,date_naiss,lieu_naiss,sexe
	FROM got_'.$base.'_individu
	WHERE id_indi = '.$id_indi;
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);
	return $row;
}

function recup_dept()
{	global $pool;
	$query = 'SELECT code_dept,	lib_dept FROM g__geodept';
	$result = sql_exec($query,2);
	if (mysqli_errno($pool) == 0)
	{	while ($row = mysqli_fetch_row($result))
		{	$dept['codeb'][] = trim($row[0]);
			$dept['codev'][] = ','.trim($row[0]);
			$dept['codef'][] = 'F'.trim($row[0]);
			$dept['libb'][] = trim($row[1]);
			$dept['libv'][] = ','.trim($row[1]);
		}
	}
	return $dept;
}

function recup_color_sexe($sexe)
{	if ($sexe == "F")
	{	return "#AC0253";    //		$color_sexe[1] = "172"; 	$color_sexe[2] = "2"; 	$color_sexe[3] = "83";
	} else
	{	return "#000080";    //		$color_sexe[1] = "0";  	$color_sexe[2] = "0";  		$color_sexe[3] = "128";
	}
}

function recup_liste_css()
{
	$handle = opendir( getcwd().'/themes' );
	while ( ($file = readdir($handle)) != FALSE ) 
	{	$match = '';
		$count_preg = preg_match_all('([0-9]+)',$file,$match);
		if ($count_preg == 0) {$match[0][0] = "";}
		if ($file != "." and $file != ".." and mb_strpos($file,"css") != 0 and $match[0][0] == "")
		{	$liste_css[] = mb_substr($file,0,mb_strlen($file)-4);
		}
	}
	closedir($handle);
	sort($liste_css);
	return $liste_css;
}

function recup_timeout()
{//echo '<br>Max :'.ini_get('max_execution_time');
	if ($_SERVER["SERVER_NAME"] != "geneotree.com")
	{	@set_time_limit(36000);
		if (ini_get('max_execution_time') == 36000)
		{	$timeout[0] = 'OK';
			$timeout[1] = 36000;	// on gère une variable max_time_php car init_get peut retourner NULL sur du PHP 3
//		{	$timeout[0] = 'KO';
//			$timeout[1] = 10;	// on gère une variable max_time_php car init_get peut retourner NULL sur du PHP 3
		} elseif (ini_get('max_execution_time') == NULL)
		{	$timeout[0] = 'KO';
			$timeout[1] = 10;
		} else		// init_get valide
		{	$timeout[0] = 'KO';
			$timeout[1] = ini_get('max_execution_time');
		}
	} else
	{	$timeout[0] = 'KO';
		$timeout[1] = 10;
	}
//echo '<br>'.$timeout[0].'/'.$timeout[1];
	return $timeout;
}

function sql_connect()
{	global $pool;
	global $passe_admin;
	global $collate;

	include ("config.php");

	$pool = mysqli_connect('p:'.$sql_host,$sql_user,$sql_pass,$sql_base)   // connection mode permanent (meilleurs temps de reponses)
	or die("Connexion KO<br><br>Base : <b>".$sql_host."</b><br>Database : <b>".$sql_base."</b><br>User : <b>".$sql_user."</b><br>Password : ********<br><br>Verify <b>config.php</b>");
	@mysqli_query($pool,"SET NAMES 'UTF8'");   //on signale a MySql que l'on dialogue en UTF-8. MySql ne detecte pas lui-meme les caracteres qu'on lui envoie.

	return $pool;
}

function sql_exec($query,$debug = 0)
{	global $pool;

	$result = @mysqli_query($pool,$query);
	if ($debug == 1)
	{	echo '<br>'.$query;
	}
	if (mysqli_errno($pool) != 0 and mysqli_errno($pool) != 1062 and $debug != 2)
		echo "<BR>".mysqli_errno($pool)." : ".$query;
		/* Erreur 1062 : autorisation des duplicate keys pour le chargement des gedcom -> individus affectés 2 fois à une même famille. 
		Cela arrive de temps en temps. Dans ce cas, il ne faut pas interrompre le chargement de gedcom*/

	return $result;
}

function version_php_gd_OK()
{	$phpv = explode ('.',phpversion());
			// si version PHP inferieure a 4.3.10, c'est rapé, notamment pour la bibliotheque GD qui est en version 1
	if ( ($phpv[0] == 4 and $phpv[1] == 3 and $phpv[2] >= 10) or ($phpv[0] == 4 and $phpv[1] >= 4) or $phpv[0] >= 5 )
	{	return TRUE;
	} else
	{	return FALSE;
	}
}

function version_gd()
{	global $got_lang;

	if (!extension_loaded('gd'))
	{	echo $got_lang['MesGd'];
		return FALSE;
	} else
	{	return TRUE;
	}
}
?>