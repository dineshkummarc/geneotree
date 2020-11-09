<?php
error_reporting(E_ALL & ~E_NOTICE);	
require_once ("_sql.inc.php");
$pool = sql_connect();

function recup_ascendance ($ancetres, $cpt_sosas, $nb_generations, $perf)
{
global $ancetres;		// résultat intermédiaire des ancêtres distincts
global $ancetres_fs;	// résultat intermédiaire des ancêtres distincts
global $communs;		// résultat final ancetres communs
global $cpt_generations;
global $collate;
global $ADDRC;
/************************** Description variable $perf **************************

Selon les besoins, il faut optimiser l'utilisation mémoire/base de donnée, sinon 
on atteint les limites 30secondes en SQL ou bien 8 MO d'utilisation de mémoire.

ME_P : Standard. Petite Mémoire, peu d'info remontées en mémoire
ME_G : Grosse Memoire : résultats complets en mémoire
	     Condition d'utilisation limité à 8 générations, sinon on dépasse les 8 Mo
	     Ex : Affichage dynamique d'ascendance (5 générations)
BD_P : Petite Base de donnée : résultat dans MySQL minimum
	     Uniquement pour l'affichage et edition des cartes, pour éviter de recalculer les gros arbres à chaque fois. Meme temps de reponse que ME_P.
NULL : résultat en mémoire minimum : uniquement les ids, les sosas dynamique 
	Ex : Calcul de consanguinité

***********************************************************************************/

/* Une première requête pour ne pas perdre le sosa de départ (c'est plus pratique pour les programmes appelants) */
if ($cpt_sosas == 0)
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
		,b.date_evene
		,b.lieu_evene
		,b.dept_evene
		,case when a.id_pere = 0 then 99999999 else a.id_pere end
		,case when a.id_mere = 0 then 99999999 else a.id_mere end
		,a.tri
		,a.sosa_dyn
		"
		." FROM (got_".$_REQUEST['ibase']."_individu a LEFT OUTER JOIN got_".$_REQUEST['ibase']."_evenement b ON (a.id_indi = b.id_husb or a.id_indi = b.id_wife) and b.type_evene = 'MARR')"
		." WHERE a.id_indi = ".$ancetres['id_indi'][0];
	$result = sql_exec($query,0);
	$row = mysqli_fetch_row($result);
	$ancetres['id_indi'][$cpt_sosas] = $row[0];
	$ancetres['generation'][$cpt_sosas] = 0;
	$ancetres['sosa_d'][$cpt_sosas] = 1;		//construction dynamique de la numérotation sosa

	if ($perf == 'BD_P')
	{
		$query = "DROP TABLE got_".$ADDRC."_ascendants";
		sql_exec($query,2);
	
		$query = "
		CREATE TABLE got_".$ADDRC."_ascendants (
		id_indi			int NOT NULL default '0',
		generation		varchar(32),
		sosa_dyn		varchar(32) NOT NULL,
		nom				varchar(32) NOT NULL,
		prenom1			varchar(32) NOT NULL,
		date_naiss		varchar(20) NOT NULL,
		lieu_naiss		varchar(42) NOT NULL,
		dept_naiss		varchar(42) NOT NULL,
		sexe			tinytext,
		PRIMARY KEY  (id_indi,sosa_dyn)
		) ".$collate;
		sql_exec($query,2);
	
		$query = 'INSERT INTO got_'.$ADDRC.'_ascendants VALUES ("'
		.$row[0].'",0,1,"'
		.$row[1].'","' 
		.$row[2].'","' 
		.$row[7].'","' 
		.$row[8].'","' 
		.$row[9].'","' 
		.$row[5].'")';
		sql_exec($query);
	}

	if ($perf == 'ME_G')
	{	$ancetres['nom'][0]			= $row[1];
		$ancetres['prenom1'][0]		= $row[2];
		$ancetres['prenom2'][0]		= $row[3];
		$ancetres['prenom3'][0]		= $row[4];
		$ancetres['sexe'][0]		= $row[5];
		$ancetres['profession'][0]	= $row[6];
		$ancetres['date_naiss'][0]	= $row[7];
		$ancetres['lieu_naiss'][0]	= $row[8];
		$ancetres['dept_naiss'][0]	= $row[9];
		$ancetres['date_deces'][0]	= $row[10];
		$ancetres['lieu_deces'][0]	= $row[11];
		$ancetres['dept_deces'][0]	= $row[12];
		$ancetres['date_maria'][0]	= $row[13];
		$ancetres['lieu_maria'][0]	= $row[14];
		$ancetres['dept_maria'][0]	= $row[15];
		$ancetres['id_pere'][0]		= $row[16];
		$ancetres['id_mere'][0]		= $row[17];
		$ancetres['sosa_dyn'][0]	= $row[19];
		$ancetres['sosa_d_ref'][0]	= "";

		$query = 'SELECT distinct id_indi,prenom1,sosa_dyn,sexe,tri
			FROM got_'.$_REQUEST['ibase'].'_individu 
			WHERE (id_pere = "'.$row [16].'" and id_mere = "'.$row[17].'")
				and id_indi != "'.$row[0].'"
			ORDER BY tri';
		$result = sql_exec($query,0);
		
		$cpt_freres = 0;
		while ($row2 = mysqli_fetch_row($result) )
		{	$ancetres_fs ['id_indi'][ $ancetres['id_indi'][0] ][$cpt_freres] = $ancetres['id_indi'][0];
			$ancetres_fs ['sosa_d'][ $ancetres['id_indi'][0] ][$cpt_freres] = 1;
			$ancetres_fs ['id_fs'][ $ancetres['id_indi'][0] ][$cpt_freres]	 = $row2[0];
			$ancetres_fs ['prenom1'][ $ancetres['id_indi'][0] ][$cpt_freres] = $row2[1];
			$ancetres_fs ['sosa_dyn'][ $ancetres['id_indi'][0] ][$cpt_freres] = $row2[2];
			$ancetres_fs ['sexe'][ $ancetres['id_indi'][0] ][$cpt_freres] = $row2[3];
			$cpt_freres = $cpt_freres + 1;
		}
	}

	if ($perf == 'ME_P')
	{	$ancetres['nom'][0]		= $row[1];
		$ancetres['prenom1'][0]	= $row[2];
		$ancetres['prenom2'][0]	= $row[3];
		$ancetres['sexe'][0]		= $row[5];
		$ancetres['tri'][0]		= $row[18];
	}

}

/* On prend le tableau des ancêtres, et on cherche les pères et mères à partir du compteur sosas positionné sur le 1er de la dernière fournée trouvée */

$query = "SELECT a.id_indi
		,max(a1.id_indi),max(a2.id_indi)
		,max(a1.nom),max(a2.nom)
		,max(a1.prenom1),max(a2.prenom1)
		,max(a1.prenom2),max(a2.prenom2)
		,max(a1.prenom3),max(a2.prenom3)
		,max(a1.sexe),max(a2.sexe)
		,max(a1.profession),max(a2.profession)
		,max(a1.date_naiss),max(a2.date_naiss)
		,max(a1.lieu_naiss),max(a2.lieu_naiss)
		,max(a1.dept_naiss),max(a2.dept_naiss)
		,max(a1.date_deces),max(a2.date_deces)
		,max(a1.lieu_deces),max(a2.lieu_deces)
		,max(a1.dept_deces),max(a2.dept_deces)
		,max(b.date_evene)
		,max(b.lieu_evene)
		,max(b.dept_evene)
		,max(case when a1.id_pere = 0 then 999999999 else a1.id_pere end)
		,max(case when a2.id_pere = 0 then 999999999 else a2.id_pere end)
		,max(case when a1.id_mere = 0 then 999999999 else a1.id_mere end)
		,max(case when a2.id_mere = 0 then 999999999 else a2.id_mere end)
		,max(a1.tri),max(a2.tri)
		,max(a1.sosa_dyn),max(a2.sosa_dyn)
		"
		." FROM (((got_".$_REQUEST['ibase']."_individu a"
		." LEFT OUTER JOIN got_".$_REQUEST['ibase']."_individu  a1  ON  a.id_pere = a1.id_indi )"
		." LEFT OUTER JOIN got_".$_REQUEST['ibase']."_individu  a2  ON  a.id_mere = a2.id_indi )"
		." LEFT OUTER JOIN got_".$_REQUEST['ibase']."_evenement b  ON  (a.id_pere = b.id_husb and a.id_mere = b.id_wife) and b.type_evene = 'MARR' )"
		." WHERE a.id_indi IN (";

$flag = 0;	
while (@$ancetres['id_indi'][$cpt_sosas] != '')
//while ($cpt_sosas < count($ancetres['id_indi']))
{	$query = $query.$ancetres['id_indi'][$cpt_sosas].",";
	$cpt_sosas++;
	$flag = 1;
}
//$cpt_sosas++;
if ($flag == 1)															// test crucial pour arrêter l'appel récursif
{
	$query = substr_replace($query," ",-1,1); 							// Suppression du dernier caractère de la chaine
	$query = $query.") GROUP BY a.id_indi";

/* Puis dans la nouvelle fournée trouvée, on cherche les ancetres par rapport à l'ancienne fournée 
s'il existe, on le déclare ancetre commun, et on ne l'intègre pas une 2ème fois
s'il n'existe pas, on l'intègre définitivement dans les ancêtres             */

	$result = sql_exec($query,0);

	$old_cpt_sosas = $cpt_sosas;
	while ($row = mysqli_fetch_row($result))
	{	
		$flag_homme_commun = 0;
		if ($row[1] !== NULL)	//le père existe
		{	$j = array_search($row[1],$ancetres['id_indi']);
			if ($j)	{$flag_homme_commun = 1;}
// afficher_ascendance();
// echo '<br>id_pere -> '.$row[1];
// echo '<br>j -> '.$j.'<br><br><br><br>';
			$z = array_search($row[0],$ancetres['id_indi']);

			if ($flag_homme_commun == 1)									// si on en a trouvé 1, c'est un ancetre commun
			{	$communs ['id'][] = $row[1];
				$communs ['sosa_d'][] = $ancetres['sosa_d'][$z] * 2; //construction dynamique de la numérotation sosa
				$communs ['nom'][] = $row[3];
				$communs ['prenom'][] = $row[5];
				$communs ['date_naiss'][] = $row[15];
				$communs ['date_deces'][] = $row[21];
				$communs ['lieu_naiss'][] = $row[17];
				$communs ['generation'][] = $cpt_generations + 1;
				$communs ['sosa_d_ref'][] = $ancetres['sosa_d'][$j];

			}
			else
			{	$ancetres['id_indi'][$cpt_sosas] = $row[1];
				$ancetres['generation'][$cpt_sosas] = $cpt_generations + 1;
				$ancetres['sosa_d'][$cpt_sosas] = $ancetres['sosa_d'][$z] * 2;//construction dynamique de la numérotation sosa
// echo $cpt_sosas.":".$ancetres['nom'][$cpt_sosas]."/".$ancetres['prenom'][$cpt_sosas]."/".$ancetres['date_naiss'][$cpt_sosas]."/".$ancetres['date_deces'][$cpt_sosas]."/".$ancetres['lieu_naiss'][$cpt_sosas]."<br>";
				$cpt_generations_plus = $cpt_generations + 1;
				$sosa_dyn = $ancetres['sosa_d'][$z] * 2;

				if ($perf == 'BD_P')
				{
					$query = 'INSERT INTO got_'.$ADDRC.'_ascendants VALUES ("'
					.$row[1].'","'
					.$cpt_generations_plus.'","'
					.$sosa_dyn.'","'
					.$row[3].'","'
					.$row[5].'","'
					.$row[15].'","'
					.$row[17].'","'
					.$row[19].'","'
					.$row[11].'")';
					sql_exec($query,0);
				}
				
				if ($perf == 'ME_G')
				{
					$ancetres['nom'][$cpt_sosas]		= $row[3];
					$ancetres['prenom1'][$cpt_sosas]	= $row[5];
					$ancetres['prenom2'][$cpt_sosas]	= $row[7];
					$ancetres['prenom3'][$cpt_sosas]	= $row[9];
					$ancetres['sexe'][$cpt_sosas]		= $row[11];
					$ancetres['profession'][$cpt_sosas] = $row[13];
					$ancetres['date_naiss'][$cpt_sosas] = $row[15];
					$ancetres['lieu_naiss'][$cpt_sosas] = $row[17];
					$ancetres['dept_naiss'][$cpt_sosas] = $row[19];
					$ancetres['date_deces'][$cpt_sosas] = $row[21];
					$ancetres['lieu_deces'][$cpt_sosas] = $row[23];
					$ancetres['dept_deces'][$cpt_sosas] = $row[25];
					$ancetres['date_maria'][$cpt_sosas] = $row[27];
					$ancetres['lieu_maria'][$cpt_sosas] = $row[28];
					$ancetres['dept_maria'][$cpt_sosas] = $row[29];
					$ancetres['id_pere'][$cpt_sosas]	= $row[30];
					$ancetres['id_mere'][$cpt_sosas]	= $row[32];
					$ancetres['sosa_dyn'][$cpt_sosas]	= $row[36];
					$ancetres['sosa_d_ref'][$cpt_sosas]	= "";

					$query = "SELECT id_indi,prenom1,sosa_dyn,sexe,tri
						FROM got_".$_REQUEST['ibase']."_individu 
						WHERE (id_pere = '".$row [30]."' and id_mere = '".$row[32]."')
							and id_indi != ".$row[1]."
						ORDER BY tri";
					$result2 = sql_exec($query,0);
					
					$cpt_freres = 0;
					while ($row2 = mysqli_fetch_row($result2) )
					{	$ancetres_fs['id_indi'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $ancetres['id_indi'][$cpt_sosas];
						$ancetres_fs['sosa_d'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $sosa_dyn;
						$ancetres_fs['id_fs'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres]	 = $row2[0];
						$ancetres_fs['prenom1'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2[1];
						$ancetres_fs['sosa_dyn'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2[2];
						$ancetres_fs['sexe'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2[3];
						$cpt_freres = $cpt_freres + 1;
					}
				}

				if ($perf == 'ME_P')
				{
					$ancetres['nom'][$cpt_sosas]		= $row[3];
					$ancetres['prenom1'][$cpt_sosas]	= $row[5];
					$ancetres['prenom2'][$cpt_sosas]	= $row[7];
					$ancetres['sexe'][$cpt_sosas]		= $row[11];
					$ancetres['tri'][$cpt_sosas]		= $row[34];
				}

				$cpt_sosas = $cpt_sosas + 1;				
			}
		}

		$flag_femme_commun = 0;
		if ($row[2] !== NULL)
		{	
			$j = array_search($row[2],$ancetres['id_indi']);
			if ($j)	{$flag_femme_commun = 1;}

			$z = array_search($row[0],$ancetres['id_indi']);

			if ($flag_femme_commun == 1)		// si on en a trouvé 1, c'est un ancetre commun
			{	$communs['id'][] = $row[2];
				$communs['sosa_d'][] = $ancetres['sosa_d'][$z] * 2 + 1;//construction dynamique de la numérotation sosa
				$communs['nom'][] = $row[4];
				$communs['prenom'][] = $row[6];
				$communs['date_naiss'][] = $row[16];
				$communs['date_deces'][] = $row[22];
				$communs['lieu_naiss'][] = $row[18];
				$communs['generation'][] = $cpt_generations + 1;
				$communs['sosa_d_ref'][] = $ancetres['sosa_d'][$j];
			}
			else
			{	$ancetres['id_indi'][$cpt_sosas] = $row[2];
				$ancetres['generation'][$cpt_sosas] = $cpt_generations + 1;
				$ancetres['sosa_d'][$cpt_sosas] = $ancetres['sosa_d'][$z] * 2 + 1;//construction dynamique de la numérotation sosa

				$cpt_generations_plus = $cpt_generations + 1;
				$sosa_dyn = $ancetres['sosa_d'][$z] * 2 + 1;

				if ($perf == 'BD_P')
				{	$query = 'INSERT INTO got_'.$ADDRC.'_ascendants VALUES ("'
					.$row[2].'","'
					.$cpt_generations_plus.'","'
					.$sosa_dyn.'","'
					.$row[4].'","'
					.$row[6].'","'
					.$row[16].'","'
					.$row[18].'","'
					.$row[20].'","'
					.$row[12].'")';
					sql_exec($query,0);
				}

				if ($perf == 'ME_G')
				{	$ancetres['nom'][$cpt_sosas]		= $row[4];
					$ancetres['prenom1'][$cpt_sosas]	= $row[6];
					$ancetres['prenom2'][$cpt_sosas]	= $row[8];
					$ancetres['prenom3'][$cpt_sosas]	= $row[10];
					$ancetres['sexe'][$cpt_sosas]		= $row[12];
					$ancetres['profession'][$cpt_sosas] = $row[14];
					$ancetres['date_naiss'][$cpt_sosas] = $row[16];
					$ancetres['lieu_naiss'][$cpt_sosas] = $row[18];
					$ancetres['dept_naiss'][$cpt_sosas] = $row[20];
					$ancetres['date_deces'][$cpt_sosas] = $row[22];
					$ancetres['lieu_deces'][$cpt_sosas] = $row[24];
					$ancetres['dept_deces'][$cpt_sosas] = $row[26];
					$ancetres['date_maria'][$cpt_sosas] = $row[27];
					$ancetres['lieu_maria'][$cpt_sosas] = $row[28];
					$ancetres['dept_maria'][$cpt_sosas] = $row[29];
					$ancetres['id_pere'][$cpt_sosas]	= $row[31];
					$ancetres['id_mere'][$cpt_sosas]	= $row[33];
					$ancetres['sosa_dyn'][$cpt_sosas]	= $row[37];
					$ancetres['sosa_d_ref'][$cpt_sosas]	= "";

					$query = 'SELECT distinct id_indi,prenom1,sosa_dyn,sexe
						FROM got_'.$_REQUEST['ibase'].'_individu 
						WHERE (id_pere = "'.$row[31].'" or id_mere = "'.$row[33].'")
							and id_indi != '.$row[2].'
						ORDER BY tri';
					$result2 = sql_exec($query,0);

					$cpt_freres = 0;
					while ($row2 = mysqli_fetch_row($result2) )
					{	$ancetres_fs['id_indi'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $ancetres['id_indi'][$cpt_sosas];
						$ancetres_fs['sosa_d'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $sosa_dyn;
						$ancetres_fs['id_fs'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres]	 = $row2[0];
						$ancetres_fs['prenom1'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2[1];
						$ancetres_fs['sosa_dyn'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2[2];
						$ancetres_fs['sexe'][ $ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2[3];
						$cpt_freres = $cpt_freres + 1;
					}
				}

				if ($perf == 'ME_P')
				{
					$ancetres['nom'][$cpt_sosas]		= $row[4];
					$ancetres['prenom1'][$cpt_sosas]	= $row[6];
					$ancetres['prenom2'][$cpt_sosas]	= $row[8];
					$ancetres['sexe'][$cpt_sosas]		= $row[12];
					$ancetres['tri'][$cpt_sosas]		= $row[35];
				}

				$cpt_sosas = $cpt_sosas + 1;
			}
		}
		if ($flag_homme_commun == 1 or $flag_femme_commun == 1) {$flag_homme_commun = 0;$flag_femme_commun = 0;}
	}
	$cpt_generations = $cpt_generations + 1;
	if ($cpt_generations <= $nb_generations - 1) 
	{	recup_ascendance ($ancetres, $old_cpt_sosas, $nb_generations, $perf);		// et on recommence... (appel récursif)
	}
}
}

function afficher_commun()
{global $communs;
			// function uniquement pour le debug.
if (isset($communs ['id'][0])) 
{	echo '<table>';
	echo '<tr>';
	echo '<td class=bords_verti>i</td>';
	echo '<td class=bords_verti>id</td>';
	echo '<td class=bords_verti>sosa_d</td>';
	echo '<td class=bords_verti>nom</td>';
	echo '<td class=bords_verti>prenom</td>';
	echo '<td class=bords_verti>date_naiss</td>';
	echo '<td class=bords_verti>date_deces</td>';
	echo '<td class=bords_verti>lieu_naiss</td>';
	echo '<td class=bords_verti>generation</td>';
	echo '<td class=bords_verti>sosa_d_ref</td>';
	echo '</tr>';

	for ($i = 0; $i < count($communs['id']); $i++ )
	{	echo '<tr>';
		echo '<td class=bords_verti>'.$i.'</td>';
		echo '<td class=bords_verti>'.$communs ['id'][$i].'</td>';
		echo '<td class=bords_verti>'.$communs ['sosa_d'][$i].'</td>';
		echo '<td class=bords_verti>'.$communs ['nom'][$i].'</td>';
		echo '<td class=bords_verti>'.$communs ['prenom'][$i].'</td>';
		echo '<td class=bords_verti>'.$communs ['date_naiss'][$i].'</td>';
		echo '<td class=bords_verti>'.$communs ['date_deces'][$i].'</td>';
		echo '<td class=bords_verti>'.$communs ['lieu_naiss'][$i].'</td>';
		echo '<td class=bords_verti>'.$communs ['generation'][$i].'</td>';
		echo '<td class=bords_verti>'.$communs ['sosa_d_ref'][$i].'</td>';
		echo '</tr>';
	}
	echo '</table>';
}
}

function afficher_ascendance($excel = NULL)
{	global $ancetres;
	global $ancetres_fs;

	if (isset($ancetres['id_indi'][0])) 
	{	
		// array_multisort ($ancetres['sosa_d'],$ancetres['sosa_d_ref'],$ancetres['sosa_dyn'],$ancetres['generation']
		// ,$ancetres['nom']	,$ancetres['prenom1'],$ancetres['prenom2'],$ancetres['prenom3']
		// ,$ancetres['sexe'],$ancetres['profession']
		// ,$ancetres['date_naiss'],$ancetres['lieu_naiss'],$ancetres['dept_naiss']
		// ,$ancetres['date_maria'],$ancetres['lieu_maria'],$ancetres['dept_maria']
		// ,$ancetres['date_deces'],$ancetres['lieu_deces'],$ancetres['dept_deces']
		// ,$ancetres['id_indi'],$ancetres['id_pere'],$ancetres['id_mere']); 

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
		.$td_d.'nopag_theo'.$td_f
		.$td_d.'posit_page'.$td_f
		.$td_d.'sosa_d'.$td_f
		.$td_d.'sosa_d_ref'.$td_f
		.$td_d.'generation'.$td_f
		.$td_d.'sosa_dyn'.$td_f
		.$td_d.'nom'.$td_f
		.$td_d.'prenom1'.$td_f
		.$td_d.'prenom2'.$td_f
		.$td_d.'prenom3'.$td_f
		.$td_d.'sexe'.$td_f
		.$td_d.'profession'.$td_f
		.$td_d.'date_naiss'.$td_f
		.$td_d.'lieu_naiss'.$td_f
		.$td_d.'dept_naiss'.$td_f
		.$td_d.'date_maria'.$td_f
		.$td_d.'lieu_maria'.$td_f
		.$td_d.'dept_maria'.$td_f
		.$td_d.'date_deces'.$td_f
		.$td_d.'lieu_deces'.$td_f
		.$td_d.'dept_deces'.$td_f
		.$td_d.'id_pere'.$td_f
		.$td_d.'id_mere'.$td_f
		.$tr_f;
		if ($excel) {echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');}
		else {echo $ligne;}

		for ($i = 0; $i < count($ancetres['id_indi']); $i++ )
		{	$ligne = 
			 $tr_d
			.$td_d.$ancetres['nopag_theo'][$i].$td_f
			.$td_d.$ancetres['posit_page'][$i].$td_f
			.$td_d.$ancetres['sosa_d'][$i].$td_f
			.$td_d.$ancetres['sosa_d_ref'][$i].$td_f
			.$td_d.$ancetres['generation'][$i].$td_f
			.$td_d.$ancetres['sosa_dyn'][$i].$td_f
			.$td_d.$ancetres['nom'][$i].$td_f
			.$td_d.$ancetres['prenom1'][$i].$td_f
			.$td_d.$ancetres['prenom2'][$i].$td_f
			.$td_d.$ancetres['prenom3'][$i].$td_f
			.$td_d.$ancetres['sexe'][$i].$td_f
			.$td_d.$ancetres['profession'][$i].$td_f
			.$td_d.$ancetres['date_naiss'][$i].$td_f
			.$td_d.$ancetres['lieu_naiss'][$i].$td_f
			.$td_d.$ancetres['dept_naiss'][$i].$td_f
			.$td_d.$ancetres['date_maria'][$i].$td_f
			.$td_d.$ancetres['lieu_maria'][$i].$td_f
			.$td_d.$ancetres['dept_maria'][$i].$td_f
			.$td_d.$ancetres['date_deces'][$i].$td_f
			.$td_d.$ancetres['lieu_deces'][$i].$td_f
			.$td_d.$ancetres['dept_deces'][$i].$td_f
			.$td_d.$ancetres['id_pere'][$i].$td_f
			.$td_d.$ancetres['id_mere'][$i].$td_f
			.$tr_f;
		if ($excel) {echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');}
		else {echo $ligne;}
		}
		$ligne = $ligne.$table_f;
/*
		if ($excel == NULL)
		{
			$ligne =
			 $table_d
			.$tr_d
			.$td_d.'i'.$td_f
			.$td_d.'sosa_d'.$td_f
			.$td_d.'id_fs'.$td_f
			.$td_d.'prenom1'.$td_f
			.$td_d.'sexe'.$td_f
			.$td_d.'sosa_dyn'.$td_f
			.$td_d.'id_indi'.$td_f
			.$tr_f;
			echo $ligne;
	
			for ($ii = 0; $ii < @count($ancetres['id_indi']); $ii++ )
			{	for ($jj = 0; $jj < @count($ancetres_fs['sosa_d'][$ancetres['id_indi'][$ii] ]); $jj++ )
				{	$ligne = 
					 $tr_d
					.$td_d.$jj.$td_f
					.$td_d.$ancetres_fs['sosa_d'][ $ancetres['id_indi'][$ii] ][$jj].$td_f
					.$td_d.$ancetres_fs['id_fs'][ $ancetres['id_indi'][$ii] ][$jj].$td_f
					.$td_d.$ancetres_fs['prenom1'][ $ancetres['id_indi'][$ii] ][$jj].$td_f
					.$td_d.$ancetres_fs['sexe'][ $ancetres['id_indi'][$ii] ][$jj].$td_f
					.$td_d.$ancetres_fs['sosa_dyn'][ $ancetres['id_indi'][$ii] ][$jj].$td_f
					.$td_d.$ancetres_fs['id_indi'][ $ancetres['id_indi'][$ii] ][$jj].$td_f
					.$tr_f;
					echo $ligne;
				}
			}
			echo $table_f;
		}
*/
	}
	echo $i;

}

function integrer_implexe($nb_generations, $perf)
{	global $ancetres;
	global $communs;
		// Programme complémentaire et indépendant de la recherche d'ascendance (sans implexe)
		// Cette indépendance permet de travailler les implexes dans une 2ème passe, comme un calcul analytique.
		// Cela permet de plus grandes possibilités algorithmiques.
	function inserer_resultat($i_ancetres,$zz,$perf)
	{	global $ancetres;
		if ($perf == 'ME_G')
		{	$ancetres['nom'][$i_ancetres]		= $ancetres['nom'][$zz];
			$ancetres['prenom1'][$i_ancetres]	= $ancetres['prenom1'][$zz];
			$ancetres['prenom2'][$i_ancetres]	= $ancetres['prenom2'][$zz];
			$ancetres['prenom3'][$i_ancetres]	= $ancetres['prenom3'][$zz];
			$ancetres['sexe'][$i_ancetres]		= $ancetres['sexe'][$zz];
			$ancetres['profession'][$i_ancetres]= $ancetres['profession'][$zz];
			$ancetres['date_naiss'][$i_ancetres]= $ancetres['date_naiss'][$zz];
			$ancetres['lieu_naiss'][$i_ancetres]= $ancetres['lieu_naiss'][$zz];
			$ancetres['dept_naiss'][$i_ancetres]= $ancetres['dept_naiss'][$zz];
			$ancetres['date_deces'][$i_ancetres]= $ancetres['date_deces'][$zz];
			$ancetres['lieu_deces'][$i_ancetres]= $ancetres['lieu_deces'][$zz];
			$ancetres['dept_deces'][$i_ancetres]= $ancetres['dept_deces'][$zz];
			$ancetres['date_maria'][$i_ancetres]= $ancetres['date_maria'][$zz];
			$ancetres['lieu_maria'][$i_ancetres]= $ancetres['lieu_maria'][$zz];
			$ancetres['dept_maria'][$i_ancetres]= $ancetres['dept_maria'][$zz];
			$ancetres['id_pere'][$i_ancetres]	= $ancetres['id_pere'][$zz];
			$ancetres['id_mere'][$i_ancetres]	= $ancetres['id_mere'][$zz];
			$ancetres['sosa_dyn'][$i_ancetres]	= $ancetres['sosa_dyn'][$zz];
		}
		if ($perf == 'ME_P')
		{	$ancetres['nom'][$i_ancetres]		= $ancetres['nom'][$zz];
			$ancetres['prenom1'][$i_ancetres]	= $ancetres['prenom1'][$zz];
			$ancetres['prenom2'][$i_ancetres]	= $ancetres['prenom2'][$zz];
			$ancetres['sexe'][$i_ancetres]		= $ancetres['sexe'][$zz];
			$ancetres['tri'][$i_ancetres]		= $ancetres['tri'][$zz];
		}

		if ($perf == 'BD_P')
		{	$query = 'INSERT INTO got_'.$ADDRC.'_ascendants SELECT "'
			.$ancetres['id_indi'][$i_ancetres].'","'
			.$ancetres['generation'][$i_ancetres].'","'
			.$ancetres['sosa_d'][$i_ancetres].'",
			nom,prenom1,date_naiss,lieu_naiss,dept_naiss
			FROM got_'.$ADDRC.'_ascendants
			WHERE id_indi = "'.$ancetres['id_indi'][$zz].'"
			';
			sql_exec($query);
		}
	}
		// Principe de base: repérer toutes les lignes à dupliquer (en fonction du nb de generation) et les inserer.
		// 2 astuces algorithmiques majeures :
		//	- l'insertion des implexes au fur et à mesure dans le même tableau initial 1ère passe permet de gérer la récursivité à l infini
		//	- l'avancement des ancetres uniquement par rapport aux ancetres trouvés lors de la generation précédente
		//		permet d'optimiser au mieux le nombre de boucles : 4 boucles imbriquées aux petits oignons.
		// Résultat : code super court (36  lignes) qui peut gérer les implexes les plus complexes. 
		//   Ex : une base de 1200 souches communes, 13000 implexes générés en 100 secondes.
		//   Ex : je trouve des implexes que Heredis 8 ne trouvent pas. Et ils sont bons !
// afficher_commun();

	$i_ancetres = count($ancetres['id_indi']);
	for ($ii = 0; $ii < @count($communs['id']); $ii++ )		//peu de lignes....
	{	$ecart_sosa = $communs['sosa_d_ref'][$ii] - $communs['sosa_d'][$ii];	// peut etre negatif
		$delta_gen_max = $nb_generations - $communs['generation'][$ii];  // rappel : nb_generations est passé en parametre d'appel de la proc
		$zz = array_search($communs['sosa_d_ref'][$ii],$ancetres['sosa_d']);
		$ancetres['id_indi'][$i_ancetres]			= $ancetres['id_indi'][$zz];
		$ancetres['generation'][$i_ancetres]	= $communs['generation'][$ii];
		$ancetres['sosa_d'][$i_ancetres]		= $communs['sosa_d'][$ii];
		$ancetres['sosa_d_ref'][$i_ancetres]		= $communs['sosa_d_ref'][$ii];
		inserer_resultat($i_ancetres,$zz,$perf);
		$i_ancetres++;

		$temp = array();
		$sosa_d_ref = array();
		$delta_gen = 1;
		$sosa_d_ref[0] = $communs['sosa_d_ref'][$ii];
		while ($delta_gen <= $delta_gen_max)		// 1 boucle par génération à remonter
		{	for ($jj = 0; $jj < @count($sosa_d_ref); $jj++)	// on lit les sosa_ref trouvés lors de la boucle précédente maxi 200 en moyenne sur la generation 10
			{	for ($kk = $sosa_d_ref[$jj] * 2; $kk < ($sosa_d_ref[$jj] + 1) * 2; $kk++)	// 2 boucles pour trouver le père et la mère
				{	$zz = array_search($kk,$ancetres['sosa_d']);
					 if ($zz !== FALSE and isset($sosa_d_ref[$jj]) )		// a partir de la 2eme generation delta, on peut ne pas trouver de sosa.
					{	$ancetres['id_indi'][$i_ancetres]			= $ancetres['id_indi'][$zz];
						$ancetres['generation'][$i_ancetres]	= $communs['generation'][$ii] + $delta_gen;
						$ancetres['sosa_d'][$i_ancetres]		= $kk - $ecart_sosa * pow(2,$delta_gen);
						$ancetres['sosa_d_ref'][$i_ancetres]		= $kk;
						inserer_resultat($i_ancetres,$zz,$perf);
// echo '<br>jj:'.$jj.' kk:'.$kk.' zz:'.$zz.' sosa_d:'.$ancetres['sosa_d'][$i_ancetres].'s_ref:'.$ancetres['sosa_d_ref'][$i_ancetres].'gene:'.$ancetres['generation'][$i_ancetres];
						$temp[] = $kk;
						$i_temp++;
						$i_ancetres++;
					}
				}
			}
			$delta_gen++;
			$sosa_d_ref = $temp;
			$temp = array();
		}
	}
// afficher_ascendance();
}

function recup_pts_asc($format, $orient)  // positionnement des boites pour le pdf ascendance
{	global $x;
	global $y;
	global $col;
	
	if ($orient == "P")
	{	for ($ii = 1; $ii < 2; $ii++)	{	$x[$ii] = 7;}
		for ($ii = 2; $ii < 4; $ii++)	{	$x[$ii] = 10;}
		for ($ii = 4; $ii < 8; $ii++)	{	$x[$ii] = 55;}
		for ($ii = 8; $ii < 16; $ii++)	{	$x[$ii] = 100;}
		for ($ii = 16; $ii < 32; $ii++)	{	$x[$ii] = 145;}
	
		if ($format == "Letter")
		{	$col['depar'][1] = 132; $col['haute'][1] = 21;
			$col['depar'][2] = 68.5;$col['haute'][2] = 21;$col['inter'][2] = 127;
			$col['depar'][3] = 36.8;$col['haute'][3] = 21;$col['inter'][3] = 63.5;
			$col['depar'][4] = 22.4;$col['haute'][4] = 18;$col['inter'][4] = 31.8;
			$col['depar'][5] = 18.9;$col['haute'][5] =  9;$col['inter'][5] = 15.9; 
		} else
		{	$col['depar'][1] = 138; $col['haute'][1] = 21;
			$col['depar'][2] = 73.0;$col['haute'][2] = 21;$col['inter'][2] = 135;
			$col['depar'][3] = 39.3;$col['haute'][3] = 21;$col['inter'][3] = 67.5;
			$col['depar'][4] = 23.9;$col['haute'][4] = 18;$col['inter'][4] = 33.8;
			$col['depar'][5] = 19.95;$col['haute'][5] =  9;$col['inter'][5] = 16.9; 
		}
	
		for ($ii = 1; $ii < 2; $ii++)	{$y[$ii] = $col['depar'][1];}
		for ($ii = 2; $ii < 4; $ii++)	{$y[$ii] = $col['depar'][2] + $col['inter'][2] * ($ii - 2);}
		for ($ii = 4; $ii < 8; $ii++)	{$y[$ii] = $col['depar'][3] + $col['inter'][3] * ($ii - 4);}
		for ($ii = 8; $ii < 16; $ii++)	{$y[$ii] = $col['depar'][4] + $col['inter'][4] * ($ii - 8);}
		for ($ii = 16;$ii < 32; $ii++)	{$y[$ii] = $col['depar'][5] + $col['inter'][5] * ($ii - 16);}
	}

	if ($orient == "L")
	{	$larg_cellu = 25;
		$haut_cellu = 27;
		$dim_page = recup_dim_page();

		for ($ii = 1; $ii < 2; $ii++)	{	$x[$ii] = ($ii - 0) * ($dim_page[1] / 2) - ($larg_cellu / 2);}
		for ($ii = 2; $ii < 4; $ii++)	{	$x[$ii] = ($ii - 1) * ($dim_page[1] / 2.25) - 2 * $larg_cellu - 11;}
		for ($ii = 4; $ii < 8; $ii++)	{	$x[$ii] = ($ii - 3) * ($dim_page[1] / 4.5) - $larg_cellu - 4;}

		for ($ii = 8; $ii < 16; $ii++)	{	$x[$ii] = ($ii - 7) * ($dim_page[1] / 9) - ($larg_cellu / 2);}
		for ($ii = 16; $ii < 32; $ii = $ii + 2)	{$x[$ii] = $x[$ii / 2]; $x[$ii + 1] = $x[$ii / 2];}

		for ($ii = 1; $ii < 2; $ii++)	{	$y[$ii] = $dim_page[0] - $haut_cellu - 16;}
		for ($ii = 2; $ii < 4; $ii++)	{	$y[$ii] = $dim_page[0] - $haut_cellu - 31;}
		for ($ii = 4; $ii < 8; $ii++)	{	$y[$ii] = 30 + 3 * $haut_cellu + 27;}
		for ($ii = 8; $ii < 16; $ii++)	{	$y[$ii] = 30 + 2 * $haut_cellu + 18;}
		for ($ii = 16; $ii < 32; $ii = $ii + 2)	{	$y[$ii] =  30;	$y[$ii + 1] = 30 + $haut_cellu + 6;}
//for ($ii = 1; $ii < 32; $ii++)	{	echo '<br>'.$ii.'->'.$x[$ii];}
	}

}

function recup_pts_mix($type,$numero,$total = NULL)
{	global $dim_page;

	if ($dim_page[1] == NULL)	{$dim_page[1] = 297;}
	if ($type == "pa")
	{	if ($numero == 1)
		{	$x = $dim_page[1]/2 - 20;
		}
		if ($numero >= 2 and $numero < 4)
		{	$x = $dim_page[1]/4*((($numero-1)*2)-1)-20;
		}
		if ($numero >= 4 and $numero < 8)
		{	$x = $dim_page[1]/8*((($numero-3)*2)-1)-20;
		}
	}

	if ($type == "pf")
	{	$larg = $dim_page[1]/2 - 21;	// on travaille sur un peu moins de la moitié de la largeur de page
		$temp = floor(($total+1)/2);				// nb d'individu maxi sur une moitié de page
		$x = (((($numero + 1)/$temp) -(1 / $temp / 2))* $larg) - 11;
		if ( ($numero + 1) > $temp)
		{	$x = $x + 41;						// quand on travaille sur la moitié droite, on décale à droite
		}
	}

	if ($type == "pe")
	{	$x = (((($numero) / $total) -(1 / $total / 2))* $dim_page[1]) - 12.5;
	}

	return $x;
}

function recup_consanguinite()
{	global $res;
	global $ancetres;
	global $communs;

	function sosa2generation ($sosa)
	{	$i = 0;
		while (pow (2,$i) <= $sosa) 
		{	$i = $i + 1;
		}
		return $i;
	}

	// for ($ii = 0; $ii < 1500; $ii++){$ancetres['id_indi'][] = '';}		// sinon Notice: Undefined offset: 1 _recup_ascendance.inc.php on line 250 !!!
	// $ancetres[][] = '';$communs[][] = '';$cpt_generations = 0;
	$ancetres = array();$communs = array();$cpt_generations = 0;
	$ancetres['id_indi'][0] = $_REQUEST['fid'];
	recup_ascendance ($ancetres,0,15,'ME_G');
	// afficher_ascendance();
	// afficher_commun();

	$i_communs = 0;
	$i_t_consang = 0;
	if (isset ($communs['generation']))
	{	while ($i_communs < count($communs ['generation'])) 
		{	//$ligne[] = "";	
			for ($ii = 0; $ii < 12; $ii++){$ligne[$ii] = '';}
			if ($communs['sosa_d'][$i_communs] % 2 == 0)
			{	$ligne[0] = $communs ['id'][$i_communs];
				$ligne[1] = $communs ['sosa_d'][$i_communs];
				$ligne[2] = $communs ['nom'][$i_communs];
				$ligne[3] = $communs ['prenom'][$i_communs];
				$ligne[4] = $communs ['date_naiss'][$i_communs];
				$ligne[5] = $communs ['lieu_naiss'][$i_communs];
				$i_communs++;
			}
			if ($communs['sosa_d'][$i_communs] % 2 == 1)	
			{	$ligne[6] = $communs ['id'][$i_communs];
				$ligne[7] = $communs ['sosa_d'][$i_communs];
				$ligne[8] = $communs ['nom'][$i_communs];
				$ligne[9] = $communs ['prenom'][$i_communs];
				$ligne[10] = $communs ['date_naiss'][$i_communs];
				$ligne[11] = $communs ['lieu_naiss'][$i_communs];
				$i_communs++;
			}
	
			$res["id"][]					= $i_t_consang;
			$res["generation"][]	= $communs ['generation'][$i_communs - 1];
			$res["id1"][]					= $ligne[0];
			$res["nom1"][]				= $ligne[2];
			$res["prenom1"][]			= $ligne[3];
			$res["date_naiss1"][]	= $ligne[4];
			$res["lieu_naiss1"][]	= $ligne[5];
			$res["id2"][]					= $ligne[6];
			$res["nom2"][]				= $ligne[8];
			$res["prenom2"][]			= $ligne[9];
			$res["date_naiss2"][]	= $ligne[10];
			$res["lieu_naiss2"][]	= $ligne[11];
			$res["sexe1"][]				= "M";
			$res["sexe2"][]				= "F";

							// Stockage du sosa souche ($sosa2)
			if ($ligne[0] != NULL)
			{	$sosa2= $ligne[1];
				$id_sosa2= $ligne[0];
			} else
			{	$sosa2= $ligne[7];
				$id_sosa2= $ligne[6];
			}

							// Recherche l'ascendance existante de l'autre sosa de la souche ($sosa1)
			$z = 0;while ($ancetres['id_indi'][$z] !== $id_sosa2) {$z=$z+1;}
			$sosa1 = $ancetres ['sosa_d'][$z];

							// Affichage génération intermédiaire s'il y a lieu
//			$degre_plus = 0;
			while (sosa2generation ($sosa1) < sosa2generation ($sosa2))
			{	$sosa2 = floor($sosa2 / 2);
				$z = 0;while ($ancetres ['sosa_d'][$z] != $sosa2) {$z=$z+1;}
				
				$row = recup_identite($ancetres['id_indi'][$z], $_REQUEST['ibase']);

				$res["id"][]					= $i_t_consang;
				$res["generation"][]	= $communs ['generation'][$i_communs - 1];
				$res["id1"][]					= 0;
				$res["nom1"][]				= "";
				$res["prenom1"][]			= "";
				$res["date_naiss1"][]	= "";
				$res["lieu_naiss1"][]	= "";
				$res["id2"][]					= $ancetres['id_indi'][$z];
				$res["nom2"][]				= $row[0];
				$res["prenom2"][]			= $row[1];
				$res["date_naiss2"][]	= $row[2];
				$res["lieu_naiss2"][]	= $row[3];
				$res["sexe1"][]				= "";
				$res["sexe2"][]				= $row[4];
			}

			$sosa1 = floor($sosa1 / 2);
			$sosa2 = floor($sosa2 / 2);
			$degre = 0;
			while ($sosa1 != $sosa2)
			{	$y = 0;while ($ancetres ['sosa_d'][$y] != $sosa1) {$y=$y+1;}
				$z = 0;while ($ancetres ['sosa_d'][$z] != $sosa2) {$z=$z+1;}

				$row = recup_identite($ancetres['id_indi'][$y], $_REQUEST['ibase']);
				$nom_y = $row[0];
				$prenom_y = $row[1];
				$date_y = $row[2];
				$lieu_y = $row[3];
				$sexe_y = $row[4];

				$row = recup_identite($ancetres['id_indi'][$z], $_REQUEST['ibase']);

				$sosa1 = floor($sosa1 / 2);
				$sosa2 = floor($sosa2 / 2);
				$degre = $degre + 1;

				if ($sosa1 !== $sosa2)
				{	$temp = $communs ['generation'][$i_communs - 1];
				} else
				{	$temp = 'FIN';	// astuce pour indiquer le couple consanguin
				}

				$res["id"][]					= $i_t_consang;
				$res["generation"][]	= $temp;
				$res["id1"][]					= $ancetres['id_indi'][$y];
				$res["nom1"][]				= $nom_y;
				$res["prenom1"][]			= $prenom_y;
				$res["date_naiss1"][]	= $date_y;
				$res["lieu_naiss1"][]	= $lieu_y;
				$res["id2"][]					= $ancetres['id_indi'][$z];
				$res["nom2"][]				= $row[0];
				$res["prenom2"][]			= $row[1];
				$res["date_naiss2"][]	= $row[2];
				$res["lieu_naiss2"][]	= $row[3];
				$res["sexe1"][]				= $sexe_y;
				$res["sexe2"][]				= $row[4];

			}
			$i_t_consang++;
		}
	}
}

?>
