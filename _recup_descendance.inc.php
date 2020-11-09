<?php
require_once ("_sql.inc.php");

function recup_descendance ($descendants, $cpt_sosas, $nb_generations_desc, $perf, $flag_maria)
{
/****************** CONSTITUTION dynamique DE LA REQUETE SQL en fonction du nb de generations***************/

global $nb_generations_desc;
global $descendants;
global $descendants_ma;
global $cpt_generations_desc;
global $cousins;
global $cpt_cousins;
global $collate;
global $ADDRC;

$old_indice = "";					// Initialition variable pour eviter erreur REPORTING

if ($cpt_sosas == 0)
{	$query = 'DROP TABLE got_'.$ADDRC.'_desc_cles';
	sql_exec($query,2);
											/*** TRAITEMENT DU PREMIER INDIVIDU **/
		// contrairement aux autres individus, on n'arrive pas par les parents, mais directement par l'individu, d'ou un traitement différent 
		// ce sont les requetes SQL qui diffèrent. On pourrait jouer sur la génération des requêtes et garder ainsi le reste en commun. A faire.
	if ($flag_maria !== "MARR")
	{	$query = "SELECT 
		a.id_indi
		,a.nom
		,a.prenom1
		,a.prenom2
		,a.prenom3
		,a.sexe
		,a.profession
		,a.date_naiss
		,a.lieu_naiss
		,a.dept_naiss
		,a.date_deces
		,a.lieu_deces
		,a.dept_deces
		,a.sosa_dyn
		FROM got_".$_REQUEST['ibase']."_individu a
		WHERE a.id_indi = ".$descendants['id_indi'][0];
		$result = sql_exec($query,0);
		$row = mysqli_fetch_row($result);
	} else
	{	$query = "
		CREATE TABLE got_".$ADDRC."_desc_cles (
		id_indi			int NOT NULL default 0,
		id_conj			int NULL,
		date_maria		varchar(32),
		lieu_maria		varchar(32),
		dept_maria		varchar(32),
		id_parent		int,
		c				char(1) NULL,
		d				int NULL
		) ".$collate;
		sql_exec($query);

		$query = "set @old=''";
		sql_exec($query);

		$query = "	INSERT INTO got_".$ADDRC."_desc_cles
		SELECT
		a.id_indi
		, case when b.id_wife is not NULL then b.id_wife when c.id_husb is not NULL then c.id_husb end 
		, case when b.id_wife is not NULL then b.date_evene when c.id_husb is not NULL then c.date_evene end 
		, case when b.id_wife is not NULL then b.lieu_evene when c.id_husb is not NULL then c.lieu_evene end 
		, case when b.id_wife is not NULL then b.dept_evene when c.id_husb is not NULL then c.dept_evene end 
		, a.id_indi
		,if (a.id_indi != @old, 'O', 'N')
		,@old := a.id_indi
		FROM got_".$_REQUEST['ibase']."_individu a
		LEFT OUTER JOIN got_".$_REQUEST['ibase']."_evenement b ON (a.id_indi = b.id_husb and b.type_evene = 'MARR')
		LEFT OUTER JOIN got_".$_REQUEST['ibase']."_evenement c ON (a.id_indi = c.id_wife and c.type_evene = 'MARR')
		where a.id_indi = ".$descendants['id_indi'][0]."
		and if (a.id_indi != @old, 'O', 'N') = 'O'
		"		;
		sql_exec($query,0);

		$query = "SELECT 
		 a.id_indi
		,b.nom
		,b.prenom1
		,b.prenom2
		,b.prenom3
		,b.sexe
		,b.profession
		,b.date_naiss
		,b.lieu_naiss
		,b.dept_naiss
		,b.date_deces
		,b.lieu_deces
		,b.dept_deces
		,a.id_parent
		,b.sosa_dyn
		,a.id_conj
		,a.date_maria
		,a.lieu_maria
		,a.dept_maria
		,c.nom
		,c.prenom1
		,c.prenom2
		,c.prenom3
		,c.sexe
		,c.sosa_dyn
		FROM got_".$ADDRC."_desc_cles a
		INNER JOIN      got_".$_REQUEST['ibase']."_individu b ON (a.id_indi = b.id_indi)
		LEFT OUTER JOIN got_".$_REQUEST['ibase']."_individu c ON (a.id_conj = c.id_indi)
		";
		$result = sql_exec($query,0);
		$row = mysqli_fetch_row($result);
	}

	$descendants ['id_indi'][0]       = $row[0];
	$descendants ['indice'][0]       = 'A';

	if ($perf == 'BD_P')
	{
		$query = 'DROP TABLE got_'.$ADDRC.'_descendants';
		sql_exec($query,2);
	
		$query = "
		CREATE TABLE got_".$ADDRC."_descendants (
		id_indi			int NOT NULL default '0',
		indice			varchar(20) NOT NULL,
		niveau			int(2) unsigned NOT NULL,
		nom				varchar(32) NOT NULL,
		prenom1			varchar(32) NOT NULL,
		sexe			tinytext NOT NULL,
		date_naiss		varchar(20) NOT NULL,
		lieu_naiss		varchar(42) NOT NULL,
		dept_naiss		varchar(42) NOT NULL,
		sosa_dyn		bigint NOT NULL,
		PRIMARY KEY INDI_PK (`id_indi`)
		) ".$collate;
		sql_exec($query);
	
		$query = 'INSERT INTO got_'.$ADDRC.'_descendants VALUES ("'
		.$row[0].'","A","0","'
		.$row[1].'","'
		.$row[2].'","'
		.$row[5].'","'
		.$row[7].'","'
		.$row[8].'","'
		.$row[9].'","'
		.$row[13].'")';
		sql_exec($query);
	}

	if ($perf == 'ME_G')
	{	$descendants['niveau'][0]		= 0;
		$descendants['nom'][0]			= $row[1];
		$descendants['prenom1'][0]		= $row[2];
		$descendants['prenom2'][0]		= $row[3];
		$descendants['prenom3'][0]		= $row[4];
		$descendants['sexe'][0]			= $row[5];
		$descendants['profession'][0]	= $row[6];
		$descendants['date_naiss'][0]	= $row[7];
		$descendants['lieu_naiss'][0]	= $row[8];
		$descendants['dept_naiss'][0]	= $row[9];
		$descendants['date_deces'][0]	= $row[10];
		$descendants['lieu_deces'][0]	= $row[11];
		$descendants['dept_deces'][0]	= $row[12];
		$descendants['id_parent'][0]	= $row[13];
		$descendants['sosa_dyn'][0]		= $row[14];
		$descendants ['id_conj'][0]   = $row[15];
		$descendants['date_maria'][0]	= $row[16];
		$descendants['lieu_maria'][0]	= $row[17];
		$descendants['dept_maria'][0]	= $row[18];
		$descendants['nom_conj'][0]		= $row[19];
		$descendants['pre1_conj'][0]	= $row[20];
		$descendants['pre2_conj'][0]	= $row[21];
		$descendants['pre3_conj'][0]	= $row[22];
		$descendants['sexe_conj'][0]	= $row[23];
		$descendants['sosa_conj'][0]	= $row[24];
	}

	$cpt_cousins = 0;
}
											/*** FIN DU TRAITEMENT DU PREMIER INDIVIDU **/
											
if ($descendants ['id_indi'][$cpt_sosas] !== NULL)		// test arret de la récursivité
{		
	if ($flag_maria !== "MARR")
	{	$query = "SELECT 
		 id_indi
		,nom
		,prenom1
		,prenom2
		,prenom3
		,sexe
		,profession
		,date_naiss
		,lieu_naiss
		,dept_naiss
		,date_deces
		,lieu_deces
		,dept_deces
		,id_pere
		,sosa_dyn
		,tri
		FROM got_".$_REQUEST['ibase']."_individu
		WHERE id_pere IN (";
		for ($cpt_sosas = 0; $cpt_sosas < count($descendants['id_indi']); $cpt_sosas++)
		{	$query = $query.$descendants ['id_indi'][$cpt_sosas].",";
		}
		$query = substr_replace($query," ",-1,1); 						// Suppression du dernier caractère de la chaine
		$query = $query.") ";

		$query = $query." UNION ALL SELECT 
		 id_indi
		,nom
		,prenom1
		,prenom2
		,prenom3
		,sexe
		,profession
		,date_naiss
		,lieu_naiss
		,dept_naiss
		,date_deces
		,lieu_deces
		,dept_deces
		,id_mere
		,sosa_dyn
		,tri
		FROM got_".$_REQUEST['ibase']."_individu
		WHERE id_mere IN (";
		for ($cpt_sosas = 0; $cpt_sosas < count($descendants['id_indi']); $cpt_sosas++)
		{	$query = $query.$descendants ['id_indi'][$cpt_sosas].",";
		}
		$query = substr_replace($query," ",-1,1); 						// Suppression du dernier caractère de la chaine
		$query = $query.")";
		$query = $query." ORDER BY 14,16";
		$result = sql_exec($query,0);
	} else
	{	$query = 'DROP TABLE got_'.$ADDRC.'_desc_cles';
		sql_exec($query,2);

		$query = "
		CREATE TABLE got_".$ADDRC."_desc_cles (
		id_indi			int NOT NULL default 0,
		id_conj			int NULL,
		date_maria		varchar(32),
		lieu_maria		varchar(32),
		dept_maria		varchar(32),
		id_parent		int,
		c				char(1) NULL,
		d				int NULL
		) ".$collate;
		sql_exec($query);

		$query = "set @old=''";
		sql_exec($query);

		$save_cpt_sosas = $cpt_sosas;
		
		$query = "	INSERT INTO got_".$ADDRC."_desc_cles
		SELECT
		a.id_indi
		, case when b.id_wife is not NULL then b.id_wife when c.id_husb is not NULL then c.id_husb end 
		, case when b.id_wife is not NULL then b.date_evene when c.id_husb is not NULL then c.date_evene end 
		, case when b.id_wife is not NULL then b.lieu_evene when c.id_husb is not NULL then c.lieu_evene end 
		, case when b.id_wife is not NULL then b.dept_evene when c.id_husb is not NULL then c.dept_evene end 
		, a.id_pere
		,if (a.id_indi != @old, 'O', 'N')
		,@old := a.id_indi
		FROM got_".$_REQUEST['ibase']."_individu a
		LEFT OUTER JOIN got_".$_REQUEST['ibase']."_evenement b ON (a.id_indi = b.id_husb and b.type_evene = 'MARR')
		LEFT OUTER JOIN got_".$_REQUEST['ibase']."_evenement c ON (a.id_indi = c.id_wife and c.type_evene = 'MARR')
		where a.id_pere IN (";

		while (isset($descendants ['id_indi'][$cpt_sosas]))
		{	$query = $query.$descendants ['id_indi'][$cpt_sosas].",";
			$cpt_sosas = $cpt_sosas + 1;
		}
	
		$query = substr_replace($query," ",-1,1); 						// Suppression du dernier caractère de la chaine

		$query = $query.")
		and if (a.id_indi != @old, 'O', 'N') = 'O'
		UNION ALL
		SELECT
		a.id_indi
		, case when b.id_wife is not NULL then b.id_wife when c.id_husb is not NULL then c.id_husb end 
		, case when b.id_wife is not NULL then b.date_evene when c.id_husb is not NULL then c.date_evene end 
		, case when b.id_wife is not NULL then b.lieu_evene when c.id_husb is not NULL then c.lieu_evene end 
		, case when b.id_wife is not NULL then b.dept_evene when c.id_husb is not NULL then c.dept_evene end 
		, a.id_mere
		,if (a.id_indi != @old, 'O', 'N')
		,@old := a.id_indi
		FROM got_".$_REQUEST['ibase']."_individu a
		LEFT OUTER JOIN got_".$_REQUEST['ibase']."_evenement b ON (a.id_indi = b.id_husb and b.type_evene = 'MARR')
		LEFT OUTER JOIN got_".$_REQUEST['ibase']."_evenement c ON (a.id_indi = c.id_wife and c.type_evene = 'MARR')
		where a.id_mere IN (";

		$cpt_sosas = $save_cpt_sosas;			// reinitialisation du compteur pour relire une 2eme fois
		while (isset($descendants ['id_indi'][$cpt_sosas]))
		{	$query = $query.$descendants ['id_indi'][$cpt_sosas].",";
			$cpt_sosas = $cpt_sosas + 1;
		}

		$query = substr_replace($query," ",-1,1); 						// Suppression du dernier caractère de la chaine

		$query = $query.")
		and if (a.id_indi != @old, 'O', 'N') = 'O'
		";
		sql_exec($query,0);

		$query = "SELECT 
		 a.id_indi
		,b.nom
		,b.prenom1
		,b.prenom2
		,b.prenom3
		,b.sexe
		,b.profession
		,b.date_naiss
		,b.lieu_naiss
		,b.dept_naiss
		,b.date_deces
		,b.lieu_deces
		,b.dept_deces
		,a.id_parent
		,b.sosa_dyn
		,a.id_conj
		,a.date_maria
		,a.lieu_maria
		,a.dept_maria
		,c.nom
		,c.prenom1
		,c.prenom2
		,c.prenom3
		,c.sexe
		,c.sosa_dyn
		FROM got_".$ADDRC."_desc_cles a
		INNER JOIN      got_".$_REQUEST['ibase']."_individu b ON (a.id_indi = b.id_indi)
		LEFT OUTER JOIN got_".$_REQUEST['ibase']."_individu c ON (a.id_conj = c.id_indi)
		ORDER BY a.id_parent, b.tri
		";
		$result = sql_exec($query,0);
	}
	$old_cpt_sosas = $cpt_sosas;
	$i = chr(65);													// lettre A
	while ($row = mysqli_fetch_row($result))
	{	$flag_doublon = 0;
		$z=0;
		while (isset($descendants['id_indi'][$z]) and $flag_doublon == 0) 
		{	if ($descendants['id_indi'][$z] == $row[0]) 
			{	$flag_doublon = 1;
			} 
			$z = $z+1; 
		}

		if ($flag_doublon == 0)
		{	$j = $cpt_sosas - 1;
			while ($descendants ['id_indi'][$j] != $row[13])
			{	$j = $j - 1;
			}
			if ($descendants ['id_indi'][$j] != '' and isset($row[0]) )
			{	$descendants ['id_indi'][$cpt_sosas] = $row[0];

				if ($descendants ['indice'][$j] != $old_indice) {$i = "A";}
				$descendants ['indice'][$cpt_sosas] = $descendants ['indice'][$j].$i;	// on accole la lettre de l'alphabet qui va bien
				$temp = $cpt_generations_desc + 1;

				if ($perf == 'BD_P')
				{	$query = 'INSERT INTO got_'.$ADDRC.'_descendants VALUES ("'
					.$row[0].'","'
					.$descendants ['indice'][$cpt_sosas].'","'
					.$temp.'","'
					.$row[1].'","'
					.$row[2].'","'
					.$row[5].'","'
					.$row[7].'","'
					.$row[8].'","'
					.$row[9].'","'
					.$row[13].'")';
					sql_exec($query);
				}

				if ($perf == 'ME_G')
				{	$descendants['niveau'][$cpt_sosas]		= $temp;
					$descendants['nom'][$cpt_sosas]			= $row[1];
					$descendants['prenom1'][$cpt_sosas]		= $row[2];
					$descendants['prenom2'][$cpt_sosas]		= $row[3];
					$descendants['prenom3'][$cpt_sosas]		= $row[4];
					$descendants['sexe'][$cpt_sosas]		= $row[5];
					$descendants['profession'][$cpt_sosas]	= $row[6];
					$descendants['date_naiss'][$cpt_sosas]	= $row[7];
					$descendants['lieu_naiss'][$cpt_sosas]	= $row[8];
					$descendants['dept_naiss'][$cpt_sosas]	= $row[9];
					$descendants['date_deces'][$cpt_sosas]	= $row[10];
					$descendants['lieu_deces'][$cpt_sosas]	= $row[11];
					$descendants['dept_deces'][$cpt_sosas]	= $row[12];
					$descendants['id_parent'][$cpt_sosas]	= $row[13];
					$descendants['sosa_dyn'][$cpt_sosas]	= $row[14];

					$descendants['id_conj'][$cpt_sosas]		= $row[15];
					$descendants['date_maria'][$cpt_sosas]	= $row[16];
					$descendants['lieu_maria'][$cpt_sosas]	= $row[17];
					$descendants['dept_maria'][$cpt_sosas]	= $row[18];
					$descendants['nom_conj'][$cpt_sosas]	= $row[19];
					$descendants['pre1_conj'][$cpt_sosas]	= $row[20];
					$descendants['pre2_conj'][$cpt_sosas]	= $row[21];
					$descendants['pre3_conj'][$cpt_sosas]	= $row[22];
					$descendants['sexe_conj'][$cpt_sosas]	= $row[23];
					$descendants['sosa_conj'][$cpt_sosas]	= $row[24];
				}
				$cpt_sosas = $cpt_sosas + 1;
				$old_indice = $descendants ['indice'][$j];
				$i = chr(ord($i) + 1);										// on avance l'alphabet
			}
		}
		else
		{	$cousins ['id_indi'][$cpt_cousins]		= $row[0];
			$cousins ['pere'][$cpt_cousins]		= $row[13];
			$cousins ['niveau'][$cpt_cousins]	= $descendants['niveau'][$z-1];
			$cpt_cousins = $cpt_cousins + 1;
		}
	}
	$cpt_generations_desc = $cpt_generations_desc + 1;
	if ($cpt_generations_desc <= $nb_generations_desc - 1)
	{	recup_descendance ($descendants, $old_cpt_sosas, $nb_generations_desc, $perf, $flag_maria); 		// et on recommence... (appel récursif)
	}
}
}

function afficher_descendance($excel = NULL)
{	// Export Excel. En mode HTML, uniquement pour le deboguage.
	global $descendants;

	if (isset($descendants ['id_indi'][0])) 
	{	
		array_multisort (
		$descendants['indice']
		,$descendants['id_indi']
		,$descendants['niveau']
		,$descendants['nom']
		,$descendants['prenom1']
		,$descendants['prenom2']
		,$descendants['prenom3']
		,$descendants['sexe']
		,$descendants['profession']
		,$descendants['date_naiss']
		,$descendants['lieu_naiss']
		,$descendants['dept_naiss']
		,$descendants['date_deces']
		,$descendants['lieu_deces']
		,$descendants['dept_deces']
		,$descendants['id_parent']
		,$descendants['sosa_dyn']
		,$descendants['id_conj']   
		,$descendants['date_maria']
		,$descendants['lieu_maria']
		,$descendants['dept_maria']
		,$descendants['nom_conj']  
		,$descendants['pre1_conj'] 
		,$descendants['pre2_conj'] 
		,$descendants['pre3_conj'] 
		,$descendants['sexe_conj'] 
		,$descendants['sosa_conj'] 
		);			
		
		if ($excel == NULL)
		{	$table_d	= '<table>';
			$tr_d		= '<tr>';
			$td_d		= '<td class=bords_verti>';
			$table_f	= '</table>';
			$tr_f		= '</tr>';
			$td_f		= '</td>';
		} else
		{	$table_d	= '';
			$tr_d		= '';
			$td_d		= '';
			$table_f	= '';
			$tr_f		= chr(10);
			$td_f		= chr(9);
		}
		
		$ligne = 
		$table_d
		.$tr_d
		.$td_d.'niveau'.$td_f
//		.$td_d.'indice'.$td_f
//		.$td_d.'sosa_dyn'.$td_f
		.$td_d.'nom'.$td_f
		.$td_d.'prenom1'.$td_f
		.$td_d.'prenom2'.$td_f
		.$td_d.'prenom3'.$td_f
		.$td_d.'sexe'.$td_f
		.$td_d.'profession'.$td_f
		.$td_d.'date_naiss'.$td_f
		.$td_d.'lieu_naiss'.$td_f
		.$td_d.'dept_naiss'.$td_f
		.$td_d.'date_deces'.$td_f
		.$td_d.'lieu_deces'.$td_f
		.$td_d.'dept_deces'.$td_f
		.$td_d.'id_indi'.$td_f
		.$td_d.'date_maria'.$td_f
		.$td_d.'lieu_maria'.$td_f
		.$td_d.'dept_maria'.$td_f
		.$td_d.'nom_conj'.$td_f
		.$td_d.'pre1_conj'.$td_f
		.$td_d.'pre2_conj'.$td_f
		.$td_d.'pre3_conj'.$td_f
		.$td_d.'sexe_conj'.$td_f
		.$td_d.'sosa_conj'.$td_f
		.$tr_f;
		if ($excel == NULL) {	echo $ligne;}
		else {	echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');}

		for ($i = 0; $i < count($descendants['id_indi']); $i++ )
		{	$ligne =
			 $tr_d
			.$td_d.$descendants['niveau'][$i].$td_f
//			.$td_d.$descendants['indice'][$i].$td_f
//			.$td_d.$descendants['sosa_dyn'][$i].$td_f
			.$td_d.$descendants['nom'][$i].$td_f
			.$td_d.$descendants['prenom1'][$i].$td_f
			.$td_d.$descendants['prenom2'][$i].$td_f
			.$td_d.$descendants['prenom3'][$i].$td_f
			.$td_d.$descendants['sexe'][$i].$td_f
			.$td_d.$descendants['profession'][$i].$td_f
			.$td_d.$descendants['date_naiss'][$i].$td_f
			.$td_d.$descendants['lieu_naiss'][$i].$td_f
			.$td_d.$descendants['dept_naiss'][$i].$td_f
			.$td_d.$descendants['date_deces'][$i].$td_f
			.$td_d.$descendants['lieu_deces'][$i].$td_f
			.$td_d.$descendants['dept_deces'][$i].$td_f
			.$td_d.$descendants['id_indi'][$i].$td_f
			.$td_d.$descendants['date_maria'][$i].$td_f
			.$td_d.$descendants['lieu_maria'][$i].$td_f
			.$td_d.$descendants['dept_maria'][$i].$td_f
			.$td_d.$descendants['nom_conj'][$i].$td_f
			.$td_d.$descendants['pre1_conj'][$i].$td_f
			.$td_d.$descendants['pre2_conj'][$i].$td_f
			.$td_d.$descendants['pre3_conj'][$i].$td_f
			.$td_d.$descendants['sexe_conj'][$i].$td_f
			.$td_d.$descendants['sosa_conj'][$i].$td_f
			.$tr_f;
			if ($excel == NULL) {	echo $ligne;}
			else {	echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');}
		}
//		echo $table_f;
	}
//	echo $i;

}
?>