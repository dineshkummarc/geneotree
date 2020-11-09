<?php

function nbj2age($nbj)
{	$age[0] = floor($nbj/365.25);
	$age[1] = floor( ($nbj - $age[0]*365.25) / 30.4375);
	$age[2] = round( ($nbj - $age[0]*365.25 - $age[1]*30.4375), 0 );
	return $age;
}

function recup_deces ($ibase, $ideb, $ifin, $tri,$sexe,$limit = 18, $sosa = "Tous")
{	if ($ifin == NULL) {$ifin = 99999;}
	if ($sosa == "Sosa") {$sosa = 1;} else {$sosa = 0;}
//	if ($ideb == "")	{	$ideb = '""';}		// grossiere astuce pour tromper la requête SQL

	$query = "SELECT
		id_indi
		,sosa_dyn
		,nom
		,prenom1
		,tri
		,DATEDIFF(STR_TO_DATE(date_deces,'%d %M %Y'), STR_TO_DATE(date_naiss,'%d %M %Y') )  as calcul
		,sexe
		FROM got_".$ibase."_individu
		WHERE sexe LIKE '".$sexe."'
	 	and tri >= '".$ideb."' and tri < ".$ifin."
	 	and sosa_dyn >= ".$sosa."
		and date_deces != '' 
		and date_deces not like 'ABT%'
		and date_deces not like 'BEF%'
		and date_deces not like 'AFT%'
		and date_deces not like 'BET%'
		and length(date_deces) >= 10
		and date_naiss != '' 
		and date_naiss not like 'ABT%'
		and date_naiss not like 'BEF%'
		and date_naiss not like 'AFT%'
		and date_naiss not like 'BET%'
		and length(date_naiss) >= 10
		ORDER BY calcul ".$tri."
		LIMIT 0,".$limit;

	return sql_exec($query,0);
}

function recup_eclair($ibase, $ipag, $ideb, $ifin, $colonne, $colonne2)
{	global $ADDRC;
	global $collate;
	global $pool;    // en attendant la fonction

	if (!isset($ifin)) {$ifin = html_entity_decode ('&#65436;', ENT_COMPAT, "UTF-8");}


	$query = 'CREATE TEMPORARY TABLE got__eclair (
	 col varchar(42)
	,col2 varchar(42)
	,cpt int
	,dept  varchar(42)';
	$query = $query.')'.$collate.';';

	$query = $query.'
	INSERT INTO got__eclair SELECT 
	 '.$colonne.' 
	,'.$colonne2.'
	, COUNT(DISTINCT b.id_indi)
	, MAX(a.dept_evene)
	FROM got_'.$ibase.'_evenement a
	INNER JOIN got_'.$ibase.'_individu b ON (a.id_husb = b.id_indi)
	WHERE a.type_evene IN ("MARR","BIRT","DEAT") 
	and  ('.$colonne.' >= "'.$ideb.'" and '.$colonne.' < "'.$ifin.'") 
	GROUP BY '.$colonne.','.$colonne2.'
	UNION ALL  SELECT
	 '.$colonne.' 
	,'.$colonne2.'
	, COUNT(DISTINCT b.id_indi)
	, MAX(a.dept_evene)
	FROM got_'.$ibase.'_evenement a
	INNER JOIN got_'.$ibase.'_individu b ON ( a.id_wife = b.id_indi)
	WHERE a.type_evene IN ("MARR","BIRT","DEAT") 
	and  ('.$colonne.' >= "'.$ideb.'" and '.$colonne.' < "'.$ifin.'") 
	GROUP BY '.$colonne.','.$colonne2.'
	UNION ALL SELECT
	 '.$colonne.' 
	,'.$colonne2.'
	, COUNT(DISTINCT b.id_indi)
	, MAX(a.dept_evene)
	FROM got_'.$ibase.'_evenement a
	INNER JOIN got_'.$ibase.'_individu b ON (a.id_indi = b.id_indi)
	WHERE a.type_evene IN ("MARR","BIRT","DEAT") 
	and  ('.$colonne.' >= "'.$ideb.'" and '.$colonne.' < "'.$ifin.'") 
	GROUP BY '.$colonne.','.$colonne2.';'
	;

	$query = $query.'
	SELECT col,col2, sum(cpt), MAX(dept)';
	$query = $query.' 
	FROM got__eclair
	GROUP BY col,col2	
	ORDER BY 1,3 desc,2;';
//echo $query;
	mysqli_multi_query($pool,$query);
	$result = mysqli_store_result($pool);
	mysqli_next_result($pool);
	$result = mysqli_store_result($pool);
	mysqli_next_result($pool);
	$result = mysqli_store_result($pool);

	return $result;
}

function recup_eclair2($ibase, $ipag, $icont)
{	global $colonne;
	global $ADDRC;
	global $collate;
	global $pool;  // attendant
/*  Pour les noms et le prénoms, on lit "individu" et on ramène date et lieu de naissance
    Pour les départements et lieux, on lire "evenement", on ramène les dates et lieux de naissance des individus concernés
*/
	if ($icont == '%')	{$icont = '!';}; // PERF -> on force une valeur bidon pour ne rien afficher, sinon toute la base

	if ($ipag == "no" or $ipag == "pr")
	{	$query = 'SELECT b.id_indi, b.nom, b.prenom1, b.TRI, b.lieu_naiss, b.sosa_dyn,b.sexe, b.prenom2, b.prenom3, b.dept_naiss
		FROM got_'.$ibase.'_individu b
		WHERE '.$colonne.' LIKE "'.$icont.'" 
		ORDER BY 2,3';

		$result = sql_exec($query,0);
		
	} else
	{	

		$query = 'CREATE TEMPORARY TABLE got__eclair2 (
		 id_indi int
		,nom varchar(42)
		,prenom1 varchar(42)
		,tri smallint
		,lieu_naiss varchar(42)
		,sosa_dyn BIGINT
		,sexe TINYTEXT
		,prenom2 varchar(42)
		,prenom3 varchar(42)
		,dept_naiss varchar(42)';
		$query = $query.')'.$collate.';';

		$query = $query.' 
		INSERT INTO got__eclair2 SELECT
		b.id_indi, max(b.nom), max(b.prenom1), max(b.TRI), max(b.lieu_naiss),max( b.sosa_dyn), max(b.sexe), max(b.prenom2), max(b.prenom3), max(b.dept_naiss)
		FROM got_'.$ibase.'_evenement a
		INNER JOIN got_'.$ibase.'_individu b ON (a.id_indi = b.id_indi)
		WHERE '.$colonne.' LIKE "'.$icont.'"
		GROUP BY b.id_indi
		UNION ALL SELECT
				b.id_indi, max(b.nom), max(b.prenom1), max(b.TRI), max(b.lieu_naiss),max( b.sosa_dyn), max(b.sexe), max(b.prenom2), max(b.prenom3), max(b.dept_naiss)
		FROM got_'.$ibase.'_evenement a
		INNER JOIN got_'.$ibase.'_individu b ON (a.id_husb = b.id_indi)
		WHERE '.$colonne.' LIKE "'.$icont.'"
		GROUP BY b.id_indi
		UNION ALL SELECT
				b.id_indi, max(b.nom), max(b.prenom1), max(b.TRI), max(b.lieu_naiss),max( b.sosa_dyn), max(b.sexe), max(b.prenom2), max(b.prenom3), max(b.dept_naiss)
		FROM got_'.$ibase.'_evenement a
		INNER JOIN got_'.$ibase.'_individu b ON (a.id_wife = b.id_indi)
		WHERE '.$colonne.' LIKE "'.$icont.'"
		GROUP BY b.id_indi;
		';
	
		$query = $query.'
		SELECT id_indi, max(nom), max(prenom1), max(TRI), max(lieu_naiss),max(sosa_dyn), max(sexe), max(prenom2), max(prenom3), max(dept_naiss)';
		$query = $query.' 
		FROM got__eclair2
		GROUP BY id_indi
		ORDER BY 2;';

		mysqli_multi_query($pool,$query);
		$result = mysqli_store_result($pool);
		mysqli_next_result($pool);
		$result = mysqli_store_result($pool);
		mysqli_next_result($pool);
		$result = mysqli_store_result($pool);

	}	
	return $result;
}

function recup_intervalle($ibase, $ipag, $ideb, $intervalle, $sens)
{	
	/********** CETTE FONCTION CALCULE LA DATE DEBUT & FIN EN FONCTION DE LA BASE ET DE L'INTERVALLE ********/
	// $debfin[0] recoit la valeur deb a utiliser dans les requêtes SQL (blanc pour initialisation)
	// $debfin[1] recoit la valeur de fin a utiliser dans les requêtes SQL
	$debfin[0] = "";
	$debfin[1] = "";

	switch ($ipag)
	{	case 'no' : $colonne = "nom";  break;
		case 'li' : $colonne = "lieu_evene"; break;
		case 'pr' : $colonne = "prenom1";	  break;
		case 'de' : $colonne = "dept_evene";  break;
		case 'st' : $colonne = "nom";  break;
	}

	if ($ipag == "st") {$limite_gregorien = "1579 ";} else {$limite_gregorien = "0";}
			// recuperation de la premiere annee active

	if ($ipag == "no" or $ipag == "pr" or $ipag == "st")  
	{	$query = 'SELECT min(tri),max(tri)
			FROM got_'.$ibase.'_individu 
			WHERE tri != "" and tri >= '.$limite_gregorien.' and '.$colonne.' != ""
		';
	} else
	{	$query = 'SELECT min(anne_evene),max(anne_evene)
			FROM got_'.$ibase.'_evenement
			WHERE anne_evene != "" and anne_evene >= '.$limite_gregorien.' and '.$colonne.' != ""
			and type_evene in ("BIRT","DEAT","MARR")
		';
	}
	$result = sql_exec($query,0);
	$row = mysqli_fetch_row($result);

	$date_systeme = getdate();
	$annee_systeme = $date_systeme['year'];

	if ($row[0] !== NULL)		// gestion du cas rare ou il n'y a aucun lieu ou aucun nom dans la base
	{	$annee_deb = $row[0];
		$annee_fin = $row[1];
	} else
	{	$annee_deb = $annee_systeme;
		$annee_fin = $annee_systeme;
	}

	if ($ideb == NULL)	{$ideb = $annee_deb;}

	if ($sens !== NULL)		// i.e la demande d'intervalle d'annee est demandée
	{	if ($sens == "moins")
		{	$debfin[1] = $ideb;
			$debfin[0] = $ideb - $intervalle;
			if ($debfin[0] < $annee_deb)
			{	$debfin[0] = $annee_fin - $intervalle;
				$debfin[1] = $annee_fin;
			}
		}
		if ($sens == "plus")
		{	if ($ideb <= $annee_fin)
			{	$debfin[0] = $ideb;
			} else
			{	$debfin[0] = $annee_deb;
			}
			$debfin[1] = $debfin[0] + $intervalle;
			if ($debfin[1] > $annee_systeme)
			{	$debfin[1] = $annee_systeme;
			}
		}
	} else
	{	$debfin[1] = $annee_systeme;
	}
//	$debfin[2] = $annee_deb;

	return $debfin;
}

function recup_jumeaux($ibase, $ideb, $ifin, $sexe, $limit = 18, $sosa = "Tous")
{	if ($ifin == NULL) {$ifin = 99999;}
	if ($sosa == "Sosa") {$sosa = 1;} else {$sosa = 0;}
	
	$query ="SELECT
		min(id_indi)
		,min(sosa_dyn)
		,min(nom)
		,CONCAT(min(prenom1),' & ',max(prenom1))
		,min(tri)
		,count(*)
		,max(sexe)
		FROM got_".$ibase."_individu
		WHERE sexe LIKE '".$sexe."'
		and tri >= '".$ideb."' and tri < ".$ifin."
	 	and sosa_dyn >= ".$sosa."
		and date_naiss != '' 
		and date_naiss not like 'ABT%'
		and date_naiss not like 'BEF%'
		and date_naiss not like 'AFT%'
		and date_naiss not like 'BET%'
		and length(date_naiss) >= 10
		GROUP BY date_naiss
		HAVING count(*) > 1 and (min(id_pere)= max(id_pere) or min(id_mere) = max(id_mere) )
		ORDER BY 6 DESC, 3
		LIMIT 0,".$limit
		;
	return sql_exec($query,0);
}

function recup_lettres ($result)
{/* 
		Cette fonction retourne les meilleurs intervalles SQL pour afficher environ 35 lignes par intervalle
				En entree, le resultat trié par MySql de recup_occurrence (ci-après). Une liste de chaine de caractere a traiter.
		
		Principe : essayer de faire des pages de 35 lignes. Si pour faire 35 lignes, il faut 200 intervalles, on se limite à 16 intervalles.
		Si pour un intervalle donné, ca donne vraiment trop de lignes , il faut limiter la requete pour ne pas trop balayer.
		
		En sortie, la fonction donne les paramètres SQL ideb et ifin à passer comme suit : WHERE col >= ideb and col < ifin
		iaff est la valeur de fin qu'on affiche pour l'utilisateur final (correspondrait virtuellement a la valeur passee a <=)

		Les caractères non alphabétiques (essentiellement blancs et apostrophes) pourraient etre gérés dans cette fonction
		mais ne serait pas gérés par les operateurs >= et < car ils ont des codes ASCII inférieurs à A (32 et 39)
		d'où une approximation du résultat autour des noms comportants des blancs et apostrophe (nb de lignes incontrôlés)
		Il faudrait améliorer les opérateurs de la clause WHERE, mais je ne vois pas comment	
*/
	$nb_occur_total = mysqli_num_rows($result);
	if ($_REQUEST['lcont'] == "") 
	{	$nb_intervalles = 12;
	} else
	{	$nb_intervalles = 7;
	}
	if ($nb_occur_total > $nb_intervalles*35)
	{	$nb_occur = round($nb_occur_total / $nb_intervalles,0);
	} else
	{	$nb_occur = 35;
	}

	$ii = 0;
// le premier caractere qui ressemble à une lettre est le point d'exclamation HTML(hex)-> &#33; 
	$lettre['deb'][] = html_entity_decode ('&#33;', ENT_COMPAT, "UTF-8");
	while ($row = mysqli_fetch_row($result))
	{	
//echo '<br>row: '.$row[0];
		if ($ii > $nb_occur) // a ameliorer pour ne pas avoir 200 intervalles....
		{	$old_row = strtoupper(sans_accent($old));
			$new_row = strtoupper(sans_accent($row[0]));
			if (mb_substr($new_row,0,2) !== mb_substr($old_row,0,2) )
			{	$larg_inter = 2;
			} else
			{	$larg_inter = 3;
			}
// on avance le pointeur jusqu'a ce que les 2 ou 3 1er caracteres soient differents
			while (mb_substr($new_row,0,$larg_inter) ==  mb_substr($old_row,0,$larg_inter))
			{	$old = $row[0];
				$row = mysqli_fetch_row($result);	// on avance juste pour eviter de trouver le même ['fin'] deja stocke
				$old_row = strtoupper(sans_accent($old));
				$new_row = strtoupper(sans_accent($row[0]));
			}
// new_row et old_row sont differents, on stocke
			if ($row[0] !== NULL)
			{	//echo '<br>TOP: '.$row[0];
				$lettre['aff'][] = rtrim(mb_substr($old_row,0,$larg_inter));
				$lettre['fin'][] = rtrim(mb_substr($new_row,0,$larg_inter));
// on initialise le prochain deb avec la valeur de l'ancien fin
				$lettre['deb'][] = rtrim(mb_substr($new_row,0,$larg_inter));
			}

			$ii = 0;
		}
		$ii++;
		$old = $row[0];
	}
// Le dernier caractere UTF-8 correctement interprété est &#65535. 
// J'ai choisi une lettre légèrement inférieure qui ressemble à un Z. Avec ça, je gère même le mandarin !
	$lettre['aff'][] = html_entity_decode ('&#65436;', ENT_COMPAT, "UTF-8");
	$lettre['fin'][] = html_entity_decode ('&#65436;', ENT_COMPAT, "UTF-8");
//echo '<br><table>';for ($ii = 0; $ii < count($lettre['fin']); $ii++){	echo '<tr><td>'.$ii.'</td><td>'.$lettre['deb'][$ii].'</td><td>'.$lettre['aff'][$ii].'</td><td>'.$lettre['fin'][$ii].'</td></tr>';}echo '</table>';
//print_r ($lettre);
	return $lettre;
}

function recup_maries ($ibase, $ideb, $ifin, $tri,$sexe,$limit = 18, $sosa = "Tous")
{	if ($ifin == NULL) {$ifin = 99999;}
	if ($sosa == "Sosa") {$sosa = 1;} else {$sosa = 0;}
	$query_part1 = "
		select 
		b.id_indi
		,b.sosa_dyn
		,b.nom
		,b.prenom1
		,b.tri
		,DATEDIFF(STR_TO_DATE(date_evene,'%d %M %Y'), STR_TO_DATE(b.date_naiss,'%d %M %Y') ) as calcul
		,b.sexe
		FROM (got_".$ibase."_evenement a
		LEFT OUTER JOIN got_".$ibase."_individu b ON a.id_";

	$query_part2 = " = b.id_indi)
		WHERE a.type_evene = 'MARR'
	 	and b.tri >= '".$ideb."' and b.tri < ".$ifin."
	 	and b.sosa_dyn >= ".$sosa."
		and date_evene != '' 
		and date_evene not like 'ABT%'
		and date_evene not like 'BEF%'
		and date_evene not like 'AFT%'
		and date_evene not like 'BET%'
		and length(date_evene) >= 10
		and date_naiss != '' 
		and date_naiss not like 'ABT%'
		and date_naiss not like 'BEF%'
		and date_naiss not like 'AFT%'
		and date_naiss not like 'BET%'
		and length(date_naiss) >= 10
	";

	if ($sexe == "_")
		$query = $query_part1."husb".$query_part2." UNION ALL ".$query_part1."wife".$query_part2." ORDER BY calcul ".$tri." LIMIT 0,".$limit;	
	if ($sexe == "M")
		$query = $query_part1."husb".$query_part2." ORDER BY calcul ".$tri." LIMIT 0,".$limit;	
	if ($sexe == "F")
		$query = $query_part1."wife".$query_part2." ORDER BY calcul ".$tri." LIMIT 0,".$limit;	
	return sql_exec($query,0);
}

function recup_media($ibase, $icont, $ideb, $ifin)
{	global $lettre;

	if ($icont == NULL) {$icont = "AUTR";}
	if ($ideb == '') {$ideb = $lettre['deb'][0];}  // initialisation pour l'arrivee 
	if ($ifin == '') {$ifin = $lettre['fin'][0];}

	if ($icont == "AUTR")
	{	$operateur = "=";
	} else
	{	$operateur = "!=";
	}
	$query = '
		SELECT a.attr_sourc as nom,a.type_evene,a.id_indi,b.nom,b.prenom1,b.sosa_dyn,b.sexe,"","","","",""
		FROM (`got_'.$ibase.'_even_sour` a 
		INNER JOIN `got_'.$ibase.'_individu` b ON a.id_indi = b.id_indi)
		WHERE a.type_sourc = "FILE" and id_sour '.$operateur.' ""
		and a.attr_sourc >= "'.$ideb.'" and a.attr_sourc < "'.$ifin.'"
		UNION
		SELECT a.attr_sourc as nom,a.type_evene,a.id_husb
		,b.nom,b.prenom1,b.sosa_dyn,b.sexe
		,c.nom,c.prenom1,c.sosa_dyn,c.sexe,a.id_wife
		FROM ((`got_'.$ibase.'_even_sour` a 
		INNER JOIN `got_'.$ibase.'_individu` b ON a.id_husb = b.id_indi)
		INNER JOIN `got_'.$ibase.'_individu` c ON a.id_wife = c.id_indi)
		WHERE a.type_sourc = "FILE" and id_sour '.$operateur.' ""
		and a.attr_sourc >= "'.$ideb.'" and a.attr_sourc < "'.$ifin.'"
		';
	if ($icont == "AUTR")
	{	$query = $query.
		'UNION
		SELECT a.note_evene as fich,a.type_evene,a.id_indi
		,b.nom,b.prenom1,b.sosa_dyn,b.sexe
		,"" as nom_wife,"" as prenom1_wife,"" as sosa_dyn_wife,"" as sexe_wife,"" as id_wife
		FROM (`got_'.$ibase.'_evenement` a 
		INNER JOIN `got_'.$ibase.'_individu` b ON a.id_indi = b.id_indi)
		WHERE type_evene = "FILE"
		and a.note_evene >= "'.$ideb.'" and a.note_evene < "'.$ifin.'"
		ORDER BY 1';
	} else
	{	$query = $query.
		'ORDER BY 1';
	}
	return sql_exec($query,0);
}

function recup_noces ($ibase, $ideb, $ifin, $tri, $sexe ,$limit = 18, $sosa = "Tous")
{	// Le filtre intervalle annee est tres difficile a poser. Il faut poser le filtre sur l'annee TRI des conjoints
	// apres le resultat du calcul de la duree entre date mariage et date 1er deces conjoint ! 
	// Cad, en une requête, poser un HAVING sur un GROUP BY bidon (id_indi). A faire quand je serais plus courageux !
	if ($ifin == NULL) {$ifin = 99999;}
	if ($sosa == "Sosa") {$sosa = 1;} else {$sosa = 0;}
	$query = "SELECT ";
	if ($sexe !== "F")
	{	$query = $query ."b.id_indi
		,max(b.sosa_dyn)
		,max(b.nom)
		,max(b.prenom1)
		,max(b.tri)";
	} else
	{	$query = $query ."c.id_indi
		,max(c.sosa_dyn)
		,max(c.nom)
		,max(c.prenom1)
		,max(c.tri)";
	}

	if ($tri == "asc")
	{		$query = $query .",min";
	} else
	{		$query = $query .",max";
	}
	$query = $query ."(CASE WHEN STR_TO_DATE(b.date_deces,'%d %M %Y') < STR_TO_DATE(c.date_deces,'%d %M %Y')
		THEN DATEDIFF(STR_TO_DATE(b.date_deces,'%d %M %Y'), STR_TO_DATE(a.date_evene,'%d %M %Y')) 
		ELSE DATEDIFF(STR_TO_DATE(c.date_deces,'%d %M %Y'), STR_TO_DATE(a.date_evene,'%d %M %Y')) 
		END) as calcul";
	if ($sexe !== "F") {$query = $query.",'M'";} else {$query = $query.",'F'";}
		$query = $query." FROM ((got_".$ibase."_evenement a 
		LEFT OUTER JOIN got_".$ibase."_individu b ON a.id_husb = b.id_indi)
		LEFT OUTER JOIN got_".$ibase."_individu c ON a.id_wife = c.id_indi)
		WHERE a.type_evene = 'MARR'
		and a.date_evene != '' 
		and a.date_evene not like 'ABT%'
		and a.date_evene not like 'BEF%'
		and a.date_evene not like 'AFT%'
		and a.date_evene not like 'BET%'
		and length(a.date_evene) >= 10
		and b.date_deces != '' 
		and b.date_deces not like 'ABT%'
		and b.date_deces not like 'BEF%'
		and b.date_deces not like 'AFT%'
		and b.date_deces not like 'BET%'
		and length(b.date_deces) >= 10
	 	and c.date_deces != '' 
		and c.date_deces not like 'ABT%'
		and c.date_deces not like 'BEF%'
		and c.date_deces not like 'AFT%'
		and c.date_deces not like 'BET%'
		and length(c.date_deces) >= 10";
	if ($sexe !== "F")
	{	$query = $query." GROUP BY b.id_indi
	 	HAVING max(b.tri) >= '".$ideb."' and max(b.tri) < ".$ifin."";
	} else 
	{	$query = $query." GROUP BY c.id_indi
	 	HAVING max(c.tri) >= '".$ideb."' and max(c.tri) < ".$ifin."";
	}

	if ($sexe == "%")
	{
		$query = $query. " UNION ALL SELECT ";
		$query = $query ."c.id_indi
		,max(c.sosa_dyn)
		,max(c.nom)
		,max(c.prenom1)
		,max(c.tri)";
		if ($tri == "asc")
		{		$query = $query .",min";
		} else
		{		$query = $query .",max";
		}
		$query = $query ."(CASE WHEN STR_TO_DATE(b.date_deces,'%d %M %Y') < STR_TO_DATE(c.date_deces,'%d %M %Y')
			THEN DATEDIFF(STR_TO_DATE(b.date_deces,'%d %M %Y'), STR_TO_DATE(a.date_evene,'%d %M %Y')) 
			ELSE DATEDIFF(STR_TO_DATE(c.date_deces,'%d %M %Y'), STR_TO_DATE(a.date_evene,'%d %M %Y')) 
			END) as calcul";
		$query = $query.",'F'";
		$query = $query." FROM ((got_".$ibase."_evenement a 
		LEFT OUTER JOIN got_".$ibase."_individu b ON a.id_husb = b.id_indi)
		LEFT OUTER JOIN got_".$ibase."_individu c ON a.id_wife = c.id_indi)
		WHERE a.type_evene = 'MARR'
		and a.date_evene != '' 
		and a.date_evene not like 'ABT%'
		and a.date_evene not like 'BEF%'
		and a.date_evene not like 'AFT%'
		and a.date_evene not like 'BET%'
		and length(a.date_evene) >= 10
		and b.date_deces != '' 
		and b.date_deces not like 'ABT%'
		and b.date_deces not like 'BEF%'
		and b.date_deces not like 'AFT%'
		and b.date_deces not like 'BET%'
		and length(b.date_deces) >= 10
		and c.date_deces != '' 
		and c.date_deces not like 'ABT%'
		and c.date_deces not like 'BEF%'
		and c.date_deces not like 'AFT%'
		and c.date_deces not like 'BET%'
		and length(c.date_deces) >= 10";
		$query = $query." GROUP BY c.id_indi
	 	HAVING max(c.tri) >= '".$ideb."' and max(c.tri) < ".$ifin."";
	}

		$query = $query." ORDER BY calcul ".$tri."
		LIMIT 0,".$limit;
	
	return sql_exec($query,0);
}

function recup_occurrences($ibase, $ipag, $colonne)
{	// appelee depuis liste et liste_pdf

	if ($ipag == "no" or $ipag == "pr")
	{	$query = 'SELECT '.$colonne.',count(*)
			FROM got_'.$ibase.'_individu
			WHERE '.$colonne.' != ""			
			GROUP BY '.$colonne.'
			ORDER BY '.$colonne;
	} else 
	{	$query = 'SELECT '.$colonne.',count(distinct id_indi)
			FROM got_'.$ibase.'_evenement
			WHERE '.$colonne.' != ""
			and type_evene in ("BIRT","DEAT","MARR")
			GROUP BY '.$colonne.'
			ORDER BY '.$colonne;
	} 
	$result1 = sql_exec($query,0);
	return $result1;
}

function recup_parents($ibase, $ideb, $ifin, $tri,$sexe,$type = age,$limit = 18, $sosa = "Tous")
{	
	// $type = age (par defaut), on restitue l'age du parent à la naissance des enfants (asc ou desc)
	// $type = nb, on restitue le nb d'enfants du parent (desc implicite)
	// $type = ecart, on restitue l'écart entre l'aine et le benjamin (desc implicite)
	// requete en 2 fois UNION ALL pour eviter le jointure pere OR mere tres mauvaises performances
	global $collate;
	global $pool; // en attendant

	if ($ifin == NULL) {$ifin = 99999;}
	if ($sosa == "Sosa") {$sosa = 1;} else {$sosa = 0;}

	$query = 'CREATE TEMPORARY TABLE got__'.$tri.$type.' (
 	 id_indi int
	,sosa_dyn		bigint
	,nom			varchar(32)
	,prenom1		varchar(32)
	,tri			smallint
	,calcul		 int
	,sexe			tinytext';
	$query = $query.')'.$collate.';';

	$query = $query." INSERT into got__".$tri.$type."
	SELECT 
	a.id_indi
	,max(a.sosa_dyn)
	,max(a.nom)
	,max(a.prenom1)
	,max(a.tri)
	,";
	if ($type == 'age')		
	{	if ($tri == 'desc')
		{	$query = $query.'max';
		} else
		{	$query = $query.'min';
		}
		$query = $query."(DATEDIFF(STR_TO_DATE(c.date_naiss,'%d %M %Y'), STR_TO_DATE(a.date_naiss,'%d %M %Y') )) as calcul";
	} elseif ($type == 'nb')
	{	$query = $query."count(*) as calcul";	
	} else
	{	$query = $query."max(  DATEDIFF(curdate(), STR_TO_DATE(c.date_naiss,'%d %M %Y') ) ) - min(  DATEDIFF(curdate(), STR_TO_DATE(c.date_naiss,'%d %M %Y') ) )
				 as calcul";
	}		
	$query = $query."
	,max(a.sexe)
	FROM got_".$ibase."_individu a, got_".$ibase."_individu c 
	WHERE (a.id_indi = c.id_pere) /* jointure id_mere dans la 2eme requete */
	AND a.sexe LIKE 'M'";
	if ($type == 'age')		
	{	$query = $query." and c.date_naiss != '' 
		and c.date_naiss not like 'ABT%'
		and c.date_naiss not like 'BEF%'
		and c.date_naiss not like 'AFT%'
		and c.date_naiss not like 'BET%'
		and length(c.date_naiss) >= 10

		and a.date_naiss != '' 
		and a.date_naiss not like 'ABT%'
		and a.date_naiss not like 'BEF%'
		and a.date_naiss not like 'AFT%'
		and a.date_naiss not like 'BET%'
		and length(a.date_naiss) >= 10";
	} elseif ($type == 'ecart')
	{	$query = $query." and c.date_naiss != '' 
		and c.date_naiss not like 'ABT%'
		and c.date_naiss not like 'BEF%'
		and c.date_naiss not like 'AFT%'
		and c.date_naiss not like 'BET%'
		and length(c.date_naiss) >= 10";
	}

	$query = $query." 
	GROUP BY a.id_indi
	HAVING calcul != ''
 	and max(a.tri) >= '".$ideb."' and max(a.tri) < ".$ifin;

	$query = $query." 
	UNION ALL
	SELECT 
	a.id_indi
	,max(a.sosa_dyn)
	,max(a.nom)
	,max(a.prenom1)
	,max(a.tri)
	,";
	if ($type == 'age')		
	{	if ($tri == 'desc')
		{	$query = $query.'max';
		} else
		{	$query = $query.'min';
		}
		$query = $query."(DATEDIFF(STR_TO_DATE(c.date_naiss,'%d %M %Y'), STR_TO_DATE(a.date_naiss,'%d %M %Y') )) as calcul";
	} elseif ($type == 'nb')
	{	$query = $query."count(*) as calcul";	
	} else
	{	$query = $query."max(  DATEDIFF(curdate(), STR_TO_DATE(c.date_naiss,'%d %M %Y') ) ) - min(  DATEDIFF(curdate(), STR_TO_DATE(c.date_naiss,'%d %M %Y') ) )
				 as calcul";
	}		
	$query = $query."
	,max(a.sexe)
	FROM got_".$ibase."_individu a, got_".$ibase."_individu c 
	WHERE (a.id_indi = c.id_mere) 
	AND a.sexe LIKE 'F'";
	if ($type == 'age')		
	{	$query = $query." and c.date_naiss != '' 
		and c.date_naiss not like 'ABT%'
		and c.date_naiss not like 'BEF%'
		and c.date_naiss not like 'AFT%'
		and c.date_naiss not like 'BET%'
		and length(c.date_naiss) >= 10

		and a.date_naiss != '' 
		and a.date_naiss not like 'ABT%'
		and a.date_naiss not like 'BEF%'
		and a.date_naiss not like 'AFT%'
		and a.date_naiss not like 'BET%'
		and length(a.date_naiss) >= 10";
	} elseif ($type == 'ecart')
	{	$query = $query." and c.date_naiss != '' 
		and c.date_naiss not like 'ABT%'
		and c.date_naiss not like 'BEF%'
		and c.date_naiss not like 'AFT%'
		and c.date_naiss not like 'BET%'
		and length(c.date_naiss) >= 10";
	}

	$query = $query." 
	GROUP BY a.id_indi
	HAVING calcul != ''
 	and max(a.tri) >= '".$ideb."' and max(a.tri) < ".$ifin;

	$query = $query." 
	ORDER BY 6 ".$tri.",3
	LIMIT 0,150
	;";

	$query = $query." 
	SELECT *
	FROM got__".$tri.$type."
	WHERE sexe LIKE '".$sexe."'
	LIMIT 0,".$limit."
	;";

	mysqli_multi_query($pool,$query);

	$result = mysqli_store_result($pool);
	mysqli_next_result($pool);
	$result = mysqli_store_result($pool);
	@mysqli_next_result($pool);  // arobase pour proteger des warnings STR_TO_DATE sur des dates non conformes (rare, base brioul)
	return mysqli_store_result($pool);
}

function recup_palmares($ibase, $ipag, $ideb, $ifin, $sosa = "Tous")
{	$res["nom"] = NULL;
	$res["nb"] = NULL;
	
	if ($ifin == NULL) {$ifin = 99999;}
	if ($ipag == "no")	{	$colonne = "nom";}
	if ($ipag == "pr")	{	$colonne = "prenom1";}
	if ($ipag == "li")	{	$colonne = "lieu_evene";} 
	if ($ipag == "de")	{	$colonne = "dept_evene";} 
	if ($sosa == "Sosa") {$sosa = 1;} else {$sosa = 0;}
	if ($ideb == "")	{	$ideb = '""';}		// grossiere astuce pour tromper la requête SQL

	if ($ipag == "no" or $ipag == "pr")
	{	$query = 'SELECT '.$colonne.',count(*)
			FROM got_'.$ibase.'_individu
			WHERE '.$colonne.' != ""
			and tri >= '.$ideb.' and tri <= '.$ifin.'
			and sosa_dyn >= '.$sosa.'
			GROUP BY '.$colonne.'
			ORDER BY '.$colonne;
	} else
	{	$query = 'SELECT '.$colonne.',count(distinct id_indi)
			FROM got_'.$ibase.'_evenement a
			WHERE '.$colonne.' != ""
			and type_evene in ("BIRT","DEAT","MARR")
			and anne_evene >= '.$ideb.' and anne_evene <= '.$ifin.'
			GROUP BY '.$colonne.'
			ORDER BY '.$colonne;
	}			/* FIN CODE COMMUN a la clause ideb, ifin près */
	$result = sql_exec($query,0);

	while ($row = mysqli_fetch_row($result))
	{	$res['nom'][] = $row[0];
		$res['nb'][] = $row[1];
	}
	
	if (count($res['nom']) > 1) {array_multisort ($res['nb'], SORT_DESC, $res['nom']);}

	return $res;
}

function recup_source($ibase, $match, $ldeb, $lpas, $spag)
{	global $ADDRC;
	global $collate;
	global $pool; // attendant
	$nb_mots = count($match);
	// si spag = NOTE, on retourne les notes des individus (les notes des evenements sont affichés dans BIRT, DEAT, MARR et AUTR)
	// si spag = SOUR, on retourne les vraies sources, pas souvent renseignées dans les gedcoms, mais pourtant la base des généalogistes, documents d'archives notamment
	
	if ($spag == 'NOTE')
	{	
		$query = 'SELECT SQL_CALC_FOUND_ROWS note_indi,id_indi,prenom1,prenom2,prenom3,nom,"NOTE",sosa_dyn,id_indi AS cal,sexe
		FROM got_'.$ibase.'_individu
		WHERE note_indi != "" ';
		if ($nb_mots !== FALSE)
		{	$query = $query.' AND (note_indi like "%'.$match[0].'%"';
			for ($ii = 1; $ii < $nb_mots; $ii++) 
			{	$query = $query. ' OR note_indi like "%'.$match[$ii].'%"';
			}
			$query = $query.')';
		}
		$query = $query.' ORDER BY 1
		LIMIT '.$ldeb.','.$lpas.';'
		;
		$resulset[0] = sql_exec($query,0);
	}
	
	if ($spag == 'Sourc')
	{
		$query = 'CREATE TEMPORARY TABLE got__source1 (
		 note_source  MEDIUMTEXT
		,type_evene varchar(4)
		,id_sour int
		,id_indi int
		,id_husb int
		,id_wife int';
		$query = $query.')'.$collate.';';
	
		$query = $query.' INSERT INTO got__source1
		SELECT note_source, b.type_evene,a.id_sour, b.id_indi, b.id_husb, b.id_wife
		FROM got_'.$ibase.'_source a
		INNER JOIN got_'.$ibase.'_even_sour b ON (a.id_sour = b.id_sour)
		AND b.type_sourc = "SOUR" ';
		if ($nb_mots !== FALSE)
		{	$query = $query.' AND (note_source like "%'.$match[0].'%"';
			for ($ii = 1; $ii < $nb_mots; $ii++) 
			{	$query = $query. ' OR note_source like "%'.$match[$ii].'%"';
			}
			$query = $query.');';
		}

		$query = $query.'SELECT SQL_CALC_FOUND_ROWS
		note_source, c.id_indi,c.prenom1, c.prenom2, c.prenom3, c.nom, a.type_evene,c.sosa_dyn,a.id_sour,c.sexe
		FROM got__source1 a
		INNER JOIN got_'.$ibase.'_individu c ON (a.id_indi = c.id_indi)
		
	/* Les tables temporaires ne peuvent être utilisées plusieurs fois. A modifier
	UNION ALL SELECT
		note_source, c.id_indi,c.prenom1, c.prenom2, c.prenom3, c.nom, a.type_evene,c.sosa_dyn,a.id_sour,c.sexe
		FROM got__source1 a
		INNER JOIN got_'.$ibase.'_individu c ON (a.id_husb = c.id_indi) 
		UNION ALL SELECT
		note_source, c.id_indi,c.prenom1, c.prenom2, c.prenom3, c.nom, a.type_evene,c.sosa_dyn,a.id_sour,c.sexe
		FROM got__source1 a
		INNER JOIN got_'.$ibase.'_individu c ON (a.id_wife = c.id_indi)
	*/
	  ';
		
		mysqli_multi_query($pool,$query);
	
		$result = mysqli_store_result($pool);
		mysqli_next_result($pool);
		$result = mysqli_store_result($pool);
		mysqli_next_result($pool);
		$resulset[0] = mysqli_store_result($pool);
	}

	$result = sql_exec('SELECT FOUND_ROWS()');
	$row = mysqli_fetch_row($result);
	
	$resulset[1]  = ($ldeb  / $lpas ) + 1;       // numero de la page correspondant au parametre ldeb
	$resulset[2] = floor($row[0] / $lpas) + 1;   // nombre de page total
	if ($ldeb == 0)			{	$resulset[3] = ($resulset[2] - 1) * $lpas;	} else	{	$resulset[3] = $ldeb - $lpas;	} // calcul de l'enregistrement début page précédente
	if ($ldeb + $lpas > $row[0])		{	$resulset[4] = 0;	} else	{	$resulset[4] = $ldeb + $lpas;	} // calcul de l'enregistrement début page suivante
	
	return $resulset;
}

function recup_source_evene($ibase, $ibase2, $ideb , $ifin, $pere, $mere, $dept, $lieu, $sosa, $spag, $iautr, $ldeb, $lpas, $perf = NULL)
{	global $got_tag;
	global $got_lang;
	global $collate;
	global $ADDRC;
// Perf = ALL pour sortir tous les évènements en mémoire pour l'export Excel

	if ($ifin == NULL) {$ifin = 99999;}
	$where_autre = "";

	if ($perf != 'ALL')
	{	if ($spag == 'BIRT' or $spag == 'DEAT')	{	$type_evene = $spag."%";}
		if ($spag == "AUTR")
		{	if ($iautr == $got_lang['Tous'])  
			{	$type_evene = "%";
				$where_autre = " and a.type_evene not in ('BIRT','DEAT','MARR','FILE') ";
			} else
			{	$type_evene = array_search($iautr,$got_tag).'%';
			}
		}
	}
	if ($perf == 'ALL')
	{	$type_evene = "%";
	}

	if ($ldeb < 0)	{$ldeb = 0;}

	$query = 'SELECT SQL_CALC_FOUND_ROWS a.id_indi, a.date_evene
	  , max(a.lieu_evene), max(a.dept_evene)
		, max(b.sosa_dyn), max(b.nom), max(b.prenom1), max(b.prenom2), max(b.prenom3), max(b.sexe)
		, max(c.nom), max(c.prenom1), max(c.prenom2), max(c.prenom3)
		, max(d.nom), max(d.prenom1), max(d.prenom2), max(d.prenom3)
		, a.type_evene';

	if ($ibase != '$club')
	{	$query = $query.', max(a.note_evene), max( ifnull(e.id_sour,0) )';
	} else
	{	$query = $query.', f.base, "" ';
	}

	$query = $query.', max(a.anne_evene)
		FROM got_'.$ibase.'_evenement a
		INNER JOIN got_'.$ibase.'_individu b ON a.id_indi = b.id_indi
		LEFT OUTER JOIN got_'.$ibase.'_individu c ON b.id_pere = c.id_indi
		LEFT OUTER JOIN got_'.$ibase.'_individu d ON b.id_mere = d.id_indi';

	if ($ibase != '$club')
	{	$query = $query.' LEFT OUTER JOIN got_'.$ibase.'_even_sour e ON a.id_indi = e.id_indi and a.type_evene = e.type_evene';
	} else
	{	$query = $query.' INNER JOIN g__club f ON (a.id_indi BETWEEN f.indi_min and f.indi_max)';
	}

	$query = $query.' WHERE a.type_evene like "'.$type_evene.'"
	AND b.nom like "'.$ibase2.'%"
	AND a.dept_evene like "'.$dept.'%"';

	if ($pere)	{$query = $query.' AND c.nom like "'.$pere.'%"';}
	if ($mere)	{$query = $query.' AND d.nom like "'.$mere.'%"';}
	
	$query = $query.' AND a.anne_evene between "'.$ideb.'" and '.$ifin.'
	AND a.lieu_evene like "'.$lieu.'%"
	'.$where_autre;

	if ($sosa == 'Sosa' ) 	{	$query = $query.' and b.sosa_dyn >= 1'; }

	$query = $query.'
	GROUP BY a.id_indi, a.date_evene, a.type_evene
	ORDER BY 6,7
	LIMIT '.$ldeb.','.$lpas;
	
	$resulset[0] = sql_exec($query,0);

	$result = sql_exec('SELECT FOUND_ROWS()');
	$row = mysqli_fetch_row($result);

	$resulset[1]  = ($ldeb  / $lpas ) + 1;       // numero de la page correspondant au parametre ldeb
	$resulset[2] = floor($row[0] / $lpas) + 1;   // nom de page total
	if ($ldeb == 0)			{	$resulset[3] = ($resulset[2] - 1) * $lpas;	} else	{	$resulset[3] = $ldeb - $lpas;	} // calcul de l'enregistrement début page précédente
	if ($ldeb + $lpas > $row[0])		{	$resulset[4] = 0;	} else	{	$resulset[4] = $ldeb + $lpas;	} // calcul de l'enregistrement début page suivante

	if ($perf == 'BD_P')
	{	$query = "DROP TABLE got_".$ADDRC."_sources";
		sql_exec($query,2);
	
		$query = "
		CREATE TABLE got_".$ADDRC."_sources (
		id_indi			int NULL default '0',
		date_evene		varchar(32),
		lieu_evene		varchar(42) NULL,
		dept_evene		varchar(42) NULL,
		sosa_dyn		varchar(32) NULL,
		nom_indi				varchar(32) NULL,
		prenom1_indi			varchar(32) NULL,
		prenom2_indi			varchar(32) NULL,
		prenom3_indi			varchar(32) NULL,
		sexe			tinytext,
		nom_pere				varchar(32) NULL,
		prenom1_pere			varchar(32) NULL,
		prenom2_pere			varchar(32) NULL,
		prenom3_pere			varchar(32) NULL,
		nom_mere				varchar(32) NULL,
		prenom1_mere			varchar(32) NULL,
		prenom2_mere			varchar(32) NULL,
		prenom3_mere			varchar(32) NULL,
		type_evene			varchar(4) NULL,
		note_evene			mediumtext NULL,
		id_sour					int NULL,
		annee_evene			smallint
		) ".$collate;
		sql_exec($query,0);
		
		while ($row = mysqli_fetch_row ($resulset[0]) )
		{
			$query = 'INSERT INTO got_'.$ADDRC.'_sources VALUES ("'
			.$row[0].'","'
			.$row[1].'","'
			.$row[2].'","'
			.$row[3].'","'
			.$row[4].'","'
			.$row[5].'","'
			.$row[6].'","'
			.$row[7].'","'
			.$row[8].'","'
			.$row[9].'","'
			.$row[10].'","'
			.$row[11].'","'
			.$row[12].'","'
			.$row[13].'","'
			.$row[14].'","'
			.$row[15].'","'
			.$row[16].'","'
			.$row[17].'","'
			.$row[18].'","'
			.$row[19].'","'
			.$row[20].'","'
			.$row[21].'")';
			sql_exec($query);
		}
		mysqli_data_seek ($resulset[0], 0 );
	}
	return $resulset;
}

function recup_source_marr($ibase, $ibase2, $nom_wife, $dept, $lieu, $sosa, $ideb, $ifin, $ldeb, $lpas, $perf = NULL)
{	global $collate;
	global $ADDRC;

	if ($lieu == NULL) {$lieu = "%";}
	if ($ifin == NULL) {$ifin = 99999;}

	if ($ldeb < 0)	{$ldeb = 0;}

	$query = 'SELECT SQL_CALC_FOUND_ROWS a.date_evene, max(a.lieu_evene), max(a.dept_evene)
	, a.id_husb, max(b.sosa_dyn), max(b.nom), max(b.prenom1), max(b.prenom2), max(b.prenom3), max(b.sexe)
	, a.id_wife, max(c.sosa_dyn), max(c.nom), max(c.prenom1), max(c.prenom2), max(c.prenom3), max(c.sexe)
	, max(ifnull(d.id_sour,0))
	, max(ifnull(e.id_sour,0))
	, max(a.anne_evene)
	, max(a.note_evene)
	FROM got_'.$ibase.'_evenement a
	LEFT OUTER JOIN got_'.$ibase.'_individu b ON a.id_husb = b.id_indi
	LEFT OUTER JOIN got_'.$ibase.'_individu c ON a.id_wife = c.id_indi
	LEFT OUTER JOIN got_'.$ibase.'_even_sour d ON a.id_husb = d.id_husb and a.type_evene = d.type_evene
	LEFT OUTER JOIN got_'.$ibase.'_even_sour e ON a.id_wife = e.id_wife and a.type_evene = e.type_evene
	WHERE a.type_evene = "MARR"
	AND a.anne_evene between "'.$ideb.'" and '.$ifin.'
	AND a.dept_evene like "'.$dept.'%"
	AND a.lieu_evene like "'.$lieu.'%"';

	if ($ibase2) {$query = $query.' AND b.nom LIKE "'.$ibase2.'%"';}
	if ($nom_wife) {$query = $query.' AND c.nom LIKE "'.$nom_wife.'%"';}

	if ($sosa == "Sosa") 	{	$query = $query.' and b.sosa_dyn >= 1 and c.sosa_dyn >= 1'; }

	$query = $query.'
	GROUP BY a.date_evene, a.id_husb, a.id_wife
	ORDER BY 6,7
	LIMIT '.$ldeb.','.$lpas;

	$resulset[0] = sql_exec($query,0);

	$result = sql_exec('SELECT FOUND_ROWS()');
	$row = mysqli_fetch_row($result);

	$resulset[1]  = ($ldeb  / $lpas ) + 1;       // numero de la page correspondant au parametre ldeb
	$resulset[2] = floor($row[0] / $lpas) + 1;   // nom de page total
	if ($ldeb == 0)			{	$resulset[3] = ($resulset[2] - 1) * $lpas;	} else	{	$resulset[3] = $ldeb - $lpas;	} // calcul de l'enregistrement début page précédente
	if ($ldeb + $lpas > $row[0])		{	$resulset[4] = 0;	} else	{	$resulset[4] = $ldeb + $lpas;	} // calcul de l'enregistrement début page suivante

	if ($perf == 'BD_P')
	{	$query = "DROP TABLE got_".$ADDRC."_mariages";
		sql_exec($query,2);
	
		$query = "
		CREATE TABLE got_".$ADDRC."_mariages (
		date_evene			varchar(32),
		lieu_evene			varchar(42) NULL,
		dept_evene			varchar(42) NULL,
		id_husb					int NULL,
		sosa_dyn_husb		varchar(32) NULL,
		nom_husb				varchar(32) NULL,
		prenom1_husb		varchar(32) NULL,
		prenom2_husb		varchar(32) NULL,
		prenom3_husb		varchar(32) NULL,
		sexe_husb				tinytext,
		id_wife					int NULL,
		sosa_dyn_wife		varchar(32) NULL,
		nom_wife				varchar(32) NULL,
		prenom1_wife		varchar(32) NULL,
		prenom2_wife		varchar(32) NULL,
		prenom3_wife		varchar(32) NULL,
		sexe_wife				tinytext,
		id_sour_husb		int NULL,
		id_sour_wife		int NULL,
		annee_evene			smallint,
		note_evene			mediumtext NULL
		) ".$collate;
		sql_exec($query);
		
		while ($row = mysqli_fetch_row ($resulset[0]) )
		{
			$query = 'INSERT INTO got_'.$ADDRC.'_mariages VALUES ("'
			.$row[0].'","'
			.$row[1].'","'
			.$row[2].'","'
			.$row[3].'","'
			.$row[4].'","'
			.$row[5].'","'
			.$row[6].'","'
			.$row[7].'","'
			.$row[8].'","'
			.$row[9].'","'
			.$row[10].'","'
			.$row[11].'","'
			.$row[12].'","'
			.$row[13].'","'
			.$row[14].'","'
			.$row[15].'","'
			.$row[16].'","'
			.$row[17].'","'
			.$row[18].'","'
			.$row[19].'","'
			.$row[20].'")';
			sql_exec($query);
		}
		mysqli_data_seek ($resulset[0], 0 );
	}
	return $resulset;
}

?>