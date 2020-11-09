<?php
/********************** TRAITEMENTS SUR LES CHAINES DE CARACTERES ***********************************/

function affichage_date ($date, $annee_seule = NULL, $date_seule = NULL) // Traitement des dates
{	global $got_lang;
	global $pool;
	
	$query = 'SELECT defa_centa FROM g__base WHERE base ="'.$_REQUEST["ibase"].'"';
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);
	$centa = $row[0];

	$aujourdhui = getdate();
	$count_preg = preg_match_all ('([0-9][0-9][0-9][0-9])',$date,$annee);
	if ($count_preg == 0) {$annee[0][0] = "";}

	if ($annee_seule !== NULL) // option affichage uniquement de l'annee (pour des raisons de place)
	{	$date = $annee[0][0];
	} else
	{	$mois_let = Array('JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC','ABT','BEF','AFT','BET','AND');
		$mois_fr = Array('Jan','Fév','Mars','Avr','Mai','Juin','Juil','Août','Sept','Oct','Nov','Déc','vers','avant','après','entre','et');
		$mois_en = Array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec','about','before','after','between','and');
		
		if ($got_lang['Langu'] == 'fr')
		{	$date = str_replace($mois_let, $mois_fr, $date);
		} else
		{	$date = str_replace($mois_let, $mois_en, $date);
		}
		
		$pre_date = "";
		$temp = explode (' ',$date);
		if (count($temp) == 1) {$temp[1] = "";$temp[2] = "";}
		if (count($temp) == 2) {$temp[2] = "";}
		if ($temp[2] > 0 and $temp[2] < 99999)		// date complete
		{	if ($date_seule) {$pre_date = '';} else {$pre_date = $got_lang['Le'];}
		} else if ($temp[0] > 31 and $temp[0] < 99999)		// annee seule. On precise "en"
		{	$pre_date = $got_lang['En'];
		}
		if ($pre_date != "")	{	$date = $pre_date.' '.$date;}
	}

	if ($centa == "Yes" and $annee[0][0] >= $aujourdhui['year'] - 75)
	{	$date = str_replace($annee[0],'X',$date);
	}

	return $date;
}

function affichage_age ($date_debut,$date_fin,$sans_prefixe = NULL)
{	global $got_lang;
	global $pool;

	$query = 'SELECT defa_centa FROM g__base WHERE base ="'.$_REQUEST["ibase"].'"';
	$result = sql_exec($query);
	$row = mysqli_fetch_row($result);
	$centa = $row[0];

	$age[1] = "";$age[2] = "";
	preg_match_all ('([0-9][0-9][0-9][0-9])',$date_fin,$annee);
	$aujourdhui = getdate();

	if ($centa == "Yes" and $annee[0][0] >= $aujourdhui['year'] - 75)
	{	$final = $got_lang['AgeDe']." X ".$got_lang['Ans'];
	} else
	{	$deb = explode (' ',$date_debut);
		if (count($deb) == 1) {$deb[1] = "";$deb[2] = "";}
		if (count($deb) == 2) {$deb[2] = "";}
		if ($deb[2] > 0 and $deb[2] < 99999)	{$deb_OK = TRUE;} else {$deb_OK = "";}
		$fin = explode (' ',$date_fin);
		if (count($fin) == 1) {$fin[1] = "";$fin[2] = "";}
		if (count($fin) == 2) {$fin[2] = "";}
		if ($fin[2] > 0 and $fin[2] < 99999)	{$fin_OK = TRUE;} else {$fin_OK = "";}
	
		if ( (mb_substr($date_debut,0,3) == "BEF" and $fin_OK == TRUE) or (mb_substr($date_fin,0,3) == "AFT" and $deb_OK == TRUE) )
		{	$age[0] = $got_lang['PluDe'];
			$age[1] = mb_substr($date_fin,-4) - mb_substr($date_debut,-4);
		} else if ( (mb_substr($date_debut,0,3) == "ABT" and $fin_OK == TRUE) or (mb_substr($date_fin,0,3) == "ABT" and $deb_OK == TRUE) )
		{	$age[0] = $got_lang['Envir'];
			$age[1] = mb_substr($date_fin,-4) - mb_substr($date_debut,-4);
		} else if ( (mb_substr($date_debut,0,3) == "AFT" and $fin_OK == TRUE) or (mb_substr($date_fin,0,3) == "BEF" and $deb_OK == TRUE) )
		{	$age[0] = $got_lang['MoiDe'];
			$age[1] = mb_substr($date_fin,-4) - mb_substr($date_debut,-4);
		} else if ( (mb_substr($date_debut,0,3) == "BET" and $fin_OK == TRUE) )
		{	$age[0] = $got_lang['Entre'];
			$age[1] = mb_substr($date_fin,-4) - mb_substr($date_debut,-4);
			$age[2] = mb_substr($date_fin,-4) - $deb[1];
		} else if ( (mb_substr($date_fin,0,3) == "BET" and $deb_OK == TRUE) )
		{	$age[0] = $got_lang['Entre'];
			$age[1] = $fin[1] - mb_substr($date_debut,-4);
			$age[2] = mb_substr($date_fin,-4) - mb_substr($date_debut,-4);
		} else if ($fin_OK == TRUE and $deb_OK == TRUE) // on a 2 dates début et fin complète
		{	$mois_let = Array('JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
			$mois_ch = Array('1','2','3','4','5','6','7','8','9','10','11','12');
			$age[0] = "";
			$deb[1] = @mb_ereg_replace($mois_let,$mois_ch,$deb[1]);
			$fin[1] = @mb_ereg_replace($mois_let,$mois_ch,$fin[1]);
			$age[1] = round ( ($fin[2]+ $fin[1]/12 + $fin[0]/365) - ($deb[2]+ $deb[1]/12 + $deb[0]/365) , 0);
		}
		
		if ($age[1] != "")
		{	if ($sans_prefixe != "YES") {$final = $got_lang['AgeDe'].' ';} else {$final = "";}
			if ($age[2] != "")
			{	$final = $final.$age[0].' '.$age[1].' '.$got_lang['Et'].' '.$age[2].' '.$got_lang['Ans'];
			} else
			{	$final = $final.$age[0].' '.$age[1].' '.$got_lang['Ans'];
			}
		} else
		{	$final = "";
		}
	}
	return $final;
}

function sans_accent($str, $encoding='utf-8')
{// Source http://www.infowebmaster.fr/tutoriel/php-enlever-accents

    // transformer les caractères accentués en entités HTML
    $str = htmlentities($str, ENT_NOQUOTES, $encoding);
 
    // remplacer les entités HTML pour avoir juste le premier caractères non accentués. Ex : "&ecute;" => "e", "&Ecute;" => "E", "à" => "a" ...
    $str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '\1', $str);
 
    // Remplacer les ligatures tel que : ?, Æ ...   Ex : "œ" => "oe"
    $str = preg_replace('#&([A-za-z]{2})(?:lig);#', '\1', $str);

    // Supprimer tout le reste
    $str = preg_replace('#&[^;]+;#', '', $str);
 
    return $str;
}

function largeur_cellule ($text, $gras)
{/*				Tableau des largeurs arial pour tous les caracteres. 
					L'indice 0 correspond au chr(32) soit blanc
					Les tailles sont stockées en millimetre pour un police de 600 pouces
					Ajuster le coeff reducteur en fonction de la taille desiree 
						exemple : 73 pour 8 pouces 							*/
	$larg = Array(55,55,71,113,113,185,136,39,67,67,79,119,56,67,56,56,113,113,113,113,113,113,113,113,113,113,56,56,119,119,119,113,213,137,137,147,147,137,126,158,148,57,97,136,113,171,147,159,137,159,148,137,127,149,137,196,137,137,125,57,57,57,97,113,69,114,114,103,112,112,57,113,113,45,45,101,45,173,113,113,113,113,68,102,56,113,101,149,102,102,102,69,53,69,120,71,114,71,45,113,68,209,113,113,67,209,137,69,209,71,125,73,73,45,45,68,68,71,113,209,68,209,101,68,196,72,101,137,57,67,113,113,113,113,53,113,68,152,75,113,119,69,151,68,82,119,69,69,68,110,57,68,69,74,114,172,172,173,125,137,137,137,137,137,137,137,205,148,137,137,137,137,57,57,57,57,149,149,160,160,160,160,160,121,160,149,149,149,149,136,136,125,114,114,114,114,114,114,183,103,115,115,115,115,58,58,58,58,114,114,114,114,114,114,114,120,125,114,114,114,114,102,114,102);

	$i = 0;
	$total = 0;
	while (mb_substr($text,$i,1) != NULL)
	{	$j = ord(mb_substr($text,$i,1)) - 32;
		if ($j >= 0 and $j<= 224) 
		{	$total = $total + $larg[$j]; }
		$i++;
	}
//echo $text.' : '.$total.'<br>';
	if ($gras == 'B' or $gras == 'b') {$coeff = 1.05;} else {$coeff = 1;}
	return $total / 73 * $coeff + 1.4;
}

function recup_dim_page()
{
	if (@$_REQUEST['forma'] == "A4")
	{	$dim_page[1] = 297;
		$dim_page[0] = 210;
	} else
	{	$dim_page[1] = 279.4;
		$dim_page[0] = 215.9;
	}
	return $dim_page;
}

function url_request()
{	$url = "";
	reset($_REQUEST);
	while (key($_REQUEST)) 
	{	if ( $_REQUEST[key($_REQUEST)] !== "" 
				and  key($_REQUEST) !== "iout" 
				and key($_REQUEST) !== "passeami" 
				and key($_REQUEST) !== "passe2"
//				and mb_strpos(' '.key($_REQUEST),'_') == 0  // bloque les variables Free __usmt, cto_free, cto_alice
		   )
		{	$url = $url.'&'.key($_REQUEST).'='.mb_ereg_replace(' ','+',$_REQUEST[key($_REQUEST)]);
		}
		next($_REQUEST);
	}
	return '?'.mb_substr($url,1);
}
?>
