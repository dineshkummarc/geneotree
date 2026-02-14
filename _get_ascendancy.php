<?php
// require_once ("_sql.inc.php");

function recup_ascendance ($ancetres, $cpt_sosas, $nb_generations, $perf = "")
{
global $sql_pref;
global $ibase;
global $ancetres;        // résultat intermédiaire des ancêtres distincts
global $ancetres_fs;    // résultat intermédiaire des ancêtres distincts
global $communs;        // résultat final ancetres communs
global $cpt_generations;
global $collate;

/************************** Description variable $perf **************************

Selon les besoins, il faut optimiser l'utilisation mémoire/base de donnée, sinon 
on atteint les limites 30 secondes en SQL ou bien 8 MO d'utilisation de mémoire.

ME_P : Standard. Petite Mémoire, peu d'info remontées en mémoire
ME_G : Grosse Memoire : résultats complets en mémoire
         Condition d'utilisation limité à 8 générations, sinon on dépasse les 8 Mo
         Ex : Affichage dynamique d'ascendance (5 générations)
NULL : résultat en mémoire minimum : uniquement les ids, les sosas dynamique 
    Ex : Calcul de consanguinité

***********************************************************************************/

/* Une première requête pour ne pas perdre le sosa de départ (c'est plus pratique pour les programmes appelants) */
if ($cpt_sosas == 0)
{   $query = "SELECT 
        a.id_indi
        ,CONCAT(a.nom,' ',a.prenom1) as nom
        ,CONCAT(IFNULL(a.prenom2,''),' ',IFNULL(a.prenom3,'')) as prenom2
        ,a.sexe
        ,IFNULL(a.profession,'') as profession
        ,IFNULL(a.date_naiss,'') as date_naiss
        ,CASE WHEN a.dept_naiss != '' THEN CONCAT(IFNULL(a.lieu_naiss,''), ' (', IFNULL(a.dept_naiss,''), ')') ELSE IFNULL(a.lieu_naiss,'') END as lieu_naiss
        ,IFNULL(a.date_deces,'') as date_deces
        ,CASE WHEN a.dept_deces != '' THEN CONCAT(IFNULL(a.lieu_deces,''), ' (', IFNULL(a.dept_deces,''), ')') ELSE IFNULL(a.lieu_deces,'') END as lieu_deces
        ,IFNULL(b.date_evene,'') as date_maria
        ,CASE WHEN b.dept_evene != '' THEN CONCAT(IFNULL(b.lieu_evene,''), ' (', IFNULL(b.dept_evene,''), ')') ELSE IFNULL(b.lieu_evene,'') END as lieu_maria
        ,case when a.id_pere = 0 then 99999999 else a.id_pere end as id_pere
        ,case when a.id_mere = 0 then 99999999 else a.id_mere end as id_mere
        ,a.sosa_dyn
        FROM (`".$sql_pref."_".$ibase."_individu` a 
        LEFT OUTER JOIN `".$sql_pref."_".$ibase."_evenement` b ON (a.id_indi = b.id_husb or a.id_indi = b.id_wife) and b.type_evene = 'MARR')
        WHERE a.id_indi = ".$ancetres['id_indi'][0];
    $result = sql_exec($query,0);
    $row = mysqli_fetch_assoc($result);
    if (isset($row['id_indi'])) {$ancetres['id_indi'][$cpt_sosas] = $row['id_indi'];} else {$ancetres['id_indi'][$cpt_sosas] = "";}
    $ancetres['generation'][$cpt_sosas] = 0;
    $ancetres['sosa_d'][$cpt_sosas] = 1;        //construction dynamique de la numérotation sosa

    $ancetres['nom'][0]        = $row['nom'];
	$ancetres['sexe'][0]       = $row['sexe'];
    $ancetres['date_naiss'][0] = $row['date_naiss'];
    $ancetres['lieu_naiss'][0] = $row['lieu_naiss'];
    $ancetres['id_pere'][0]    = $row['id_pere'];
    $ancetres['id_mere'][0]    = $row['id_mere'];
    if ($perf == 'ME_G')
    {   $ancetres['prenom2'][0]    = $row['prenom2'];
        $ancetres['profession'][0] = $row['profession'];
        $ancetres['date_deces'][0] = $row['date_deces'];
        $ancetres['lieu_deces'][0] = $row['lieu_deces'];
        $ancetres['date_maria'][0] = $row['date_maria'];
        $ancetres['lieu_maria'][0] = $row['lieu_maria'];
        $ancetres['sosa_dyn'][0]   = $row['sosa_dyn'];
        $ancetres['sosa_d_ref'][0] = "";

        $query = "
		SELECT 
		 id_indi
		,CONCAT(nom,' ',prenom1) as nom
		,sosa_dyn
		,sexe
		,IFNULL(profession,'') as profession
		,IFNULL(date_naiss,'') as date_naiss
		,CASE WHEN dept_naiss != '' THEN CONCAT(IFNULL(lieu_naiss,''), ' (', IFNULL(dept_naiss,''), ')') ELSE IFNULL(lieu_naiss,'') END as lieu_naiss
		,IFNULL(date_deces,'') as date_deces
		,CASE WHEN dept_deces != '' THEN CONCAT(IFNULL(lieu_deces,''), ' (', IFNULL(dept_deces,''), ')') ELSE IFNULL(lieu_deces,'') END as lieu_deces
        FROM `".$sql_pref."_".$ibase."_individu` 
        WHERE (id_pere = '".$row ['id_pere']."' and id_mere = '".$row['id_mere']."')
            and id_indi != '".$row['id_indi']."'
        ORDER BY date_naiss";
        $result = sql_exec($query,0);
        
        $cpt_freres = 0;
        while ($row2 = mysqli_fetch_assoc($result))
        {   $ancetres_fs ['id_indi'][ $ancetres['id_indi'][0] ][$cpt_freres]    = $ancetres['id_indi'][0];
            $ancetres_fs ['sosa_d'][ $ancetres['id_indi'][0] ][$cpt_freres]     = 1;
            $ancetres_fs ['id_fs'][ $ancetres['id_indi'][0] ][$cpt_freres]      = $row2['id_indi'];
            $ancetres_fs ['nom'][ $ancetres['id_indi'][0] ][$cpt_freres]        = $row2['nom'];
            $ancetres_fs ['sosa_dyn'][ $ancetres['id_indi'][0] ][$cpt_freres]   = $row2['sosa_dyn'];
            $ancetres_fs ['sexe'][ $ancetres['id_indi'][0] ][$cpt_freres]       = $row2['sexe'];
            $ancetres_fs ['profession'][ $ancetres['id_indi'][0] ][$cpt_freres] = $row2['profession'];
            $ancetres_fs ['date_naiss'][ $ancetres['id_indi'][0] ][$cpt_freres] = $row2['date_naiss'];
            $ancetres_fs ['lieu_naiss'][ $ancetres['id_indi'][0] ][$cpt_freres] = $row2['lieu_naiss'];
            $ancetres_fs ['date_deces'][ $ancetres['id_indi'][0] ][$cpt_freres] = $row2['date_deces'];
            $ancetres_fs ['lieu_deces'][ $ancetres['id_indi'][0] ][$cpt_freres] = $row2['lieu_deces'];
            $cpt_freres = $cpt_freres + 1;
        }
    }
}

/* On prend le tableau des ancêtres, et on cherche les pères et mères à partir du compteur sosas positionné sur le 1er de la dernière fournée trouvée */

$query = "SELECT 
 a.id_indi
,max(a1.id_indi) as id_pere
,max(a2.id_indi) as id_mere
,max(CONCAT (a1.nom,' ',a1.prenom1)) as nom_pere
,max(CONCAT (a2.nom,' ',a2.prenom1)) as nom_mere
,max(CONCAT (IFNULL(a1.prenom2,''),' ',IFNULL(a1.prenom3,''))) as prenom2_pere
,max(CONCAT (IFNULL(a2.prenom2,''),' ',IFNULL(a2.prenom3,''))) as prenom2_mere
,max(a1.sexe) as sexe_pere
,max(a2.sexe) as sexe_mere
,max(IFNULL(a1.profession,'')) as profession_pere
,max(IFNULL(a2.profession,'')) as profession_mere
,max(IFNULL(a1.date_naiss,'')) as date_naiss_pere
,max(IFNULL(a2.date_naiss,'')) as date_naiss_mere
,max(CASE WHEN a1.dept_naiss != '' THEN CONCAT(IFNULL(a1.lieu_naiss,''), ' (', IFNULL(a1.dept_naiss,''), ')') ELSE IFNULL(a1.lieu_naiss,'') END) as lieu_naiss_pere
,max(CASE WHEN a2.dept_naiss != '' THEN CONCAT(IFNULL(a2.lieu_naiss,''), ' (', IFNULL(a2.dept_naiss,''), ')') ELSE IFNULL(a2.lieu_naiss,'') END) as lieu_naiss_mere
,max(IFNULL(a1.date_deces,'')) as date_deces_pere
,max(IFNULL(a2.date_deces,'')) as date_deces_mere
,max(CASE WHEN a1.dept_deces != '' THEN CONCAT(IFNULL(a1.lieu_deces,''), ' (', IFNULL(a1.dept_deces,''), ')') ELSE IFNULL(a1.lieu_deces,'') END) as lieu_deces_pere
,max(CASE WHEN a2.dept_deces != '' THEN CONCAT(IFNULL(a2.lieu_deces,''), ' (', IFNULL(a2.dept_deces,''), ')') ELSE IFNULL(a2.lieu_deces,'') END) as lieu_deces_mere
,max(IFNULL(b.date_evene,'')) as date_maria
,max(CASE WHEN b.dept_evene != '' THEN CONCAT(IFNULL(b.lieu_evene,''), ' (', IFNULL(b.dept_evene,''), ')') ELSE IFNULL(b.lieu_evene,'') END) as lieu_maria
,max(case when a1.id_pere IS NULL then 0 else a1.id_pere end) as id_pere_pere
,max(case when a1.id_mere IS NULL then 0 else a1.id_mere end) as id_pere_mere
,max(case when a2.id_pere IS NULL then 0 else a2.id_pere end) as id_mere_pere
,max(case when a2.id_mere IS NULL then 0 else a2.id_mere end) as id_mere_mere
,max(a1.sosa_dyn) as sosa_dyn_pere
,max(a2.sosa_dyn) as sosa_dyn_mere

FROM (((`".$sql_pref."_".$ibase."_individu` a
LEFT OUTER JOIN `".$sql_pref."_".$ibase."_individu`  a1  ON  a.id_pere = a1.id_indi)
LEFT OUTER JOIN `".$sql_pref."_".$ibase."_individu`  a2  ON  a.id_mere = a2.id_indi)
LEFT OUTER JOIN `".$sql_pref."_".$ibase."_evenement` b  ON  (a.id_pere = b.id_husb and a.id_mere = b.id_wife) and b.type_evene = 'MARR')
WHERE a.id_indi IN (";

$flag = 0;    
while (@$ancetres['id_indi'][$cpt_sosas] != '')
//while ($cpt_sosas < count($ancetres['id_indi']))
{   $query = $query.$ancetres['id_indi'][$cpt_sosas].",";
    $cpt_sosas++;
    $flag = 1;
}
//$cpt_sosas++;
if ($flag == 1) // test crucial pour arrêter l'appel récursif
{
    $query = substr_replace($query," ",-1,1); // Suppression du dernier caractère de la chaine
    $query = $query.") GROUP BY a.id_indi";

/* Puis dans la nouvelle fournée trouvée, on cherche les ancetres par rapport à l'ancienne fournée 
s'il existe, on le déclare ancetre commun, et on ne l'intègre pas une 2ème fois
s'il n'existe pas, on l'intègre définitivement dans les ancêtres             */

    $result = sql_exec($query,0);

    $old_cpt_sosas = $cpt_sosas;
    while ($row = mysqli_fetch_assoc($result))
    {    
        $flag_homme_commun = 0;
        if ($row['id_pere'] !== NULL)    //le père existe
        {    $j = array_search($row['id_pere'],$ancetres['id_indi']);
            if ($j)    {$flag_homme_commun = 1;}
// afficher_ascendance();
// echo '<br>id_pere -> '.$row[1];
// echo '<br>j -> '.$j.'<br><br><br><br>';
            $z = array_search($row['id_indi'],$ancetres['id_indi']);

            if ($flag_homme_commun == 1)                                    // si on en a trouvé 1, c'est un ancetre commun
            {   $communs ['id'][]         = $row['id_pere'];
                $communs ['sosa_d'][]     = $ancetres['sosa_d'][$z] * 2; //construction dynamique de la numérotation sosa
                $communs ['nom'][]        = $row['nom_pere'];
                $communs ['date_naiss'][] = $row['date_naiss_pere'];
                $communs ['date_deces'][] = $row['date_deces_pere'];
                $communs ['lieu_naiss'][] = $row['lieu_naiss_pere'];
                $communs ['generation'][] = $cpt_generations + 1;
                $communs ['sosa_d_ref'][] = $ancetres['sosa_d'][$j];

            }
            else
            {   $ancetres['id_indi']   [$cpt_sosas] = $row['id_pere'];
                $ancetres['generation'][$cpt_sosas] = $cpt_generations + 1;
                $ancetres['sosa_d']    [$cpt_sosas] = $ancetres['sosa_d'][$z] * 2;//construction dynamique de la numérotation sosa
// echo '<br>'.$cpt_sosas.":".$ancetres['id_indi'][$cpt_sosas]."|".$ancetres['sosa_d'][$cpt_sosas];
                $cpt_generations_plus = $cpt_generations + 1;
                $sosa_dyn = $ancetres['sosa_d'][$z] * 2;

				$ancetres['nom'][$cpt_sosas]        = $row['nom_pere'];
				$ancetres['sexe'][$cpt_sosas]       = $row['sexe_pere'];
                $ancetres['date_naiss'][$cpt_sosas] = $row['date_naiss_pere'];
                $ancetres['lieu_naiss'][$cpt_sosas] = $row['lieu_naiss_pere'];
                $ancetres['id_pere'][$cpt_sosas]    = $row['id_pere_pere'];
                $ancetres['id_mere'][$cpt_sosas]    = $row['id_mere_pere'];
                if ($perf == 'ME_G')
                { $ancetres['prenom2'][$cpt_sosas]    = $row['prenom2_pere'];
                  $ancetres['profession'][$cpt_sosas] = $row['profession_pere'];
                  $ancetres['date_deces'][$cpt_sosas] = $row['date_deces_pere'];
                  $ancetres['lieu_deces'][$cpt_sosas] = $row['lieu_deces_pere'];
                  $ancetres['date_maria'][$cpt_sosas] = $row['date_maria'];
                  $ancetres['lieu_maria'][$cpt_sosas] = $row['lieu_maria'];
                  $ancetres['sosa_dyn'][$cpt_sosas]   = $row['sosa_dyn_pere'];
                  $ancetres['sosa_d_ref'][$cpt_sosas] = "";

                  if (!($row['id_pere_pere'] == 0 AND $row['id_pere_mere'] == 0) )
                  { $query = "
                    SELECT 
                     id_indi
                    ,CONCAT (nom,' ',prenom1) as nom
                    ,sosa_dyn
                    ,sexe
                    ,IFNULL(profession,'') as profession
                    ,IFNULL(date_naiss,'') as date_naiss
                    ,CASE WHEN dept_naiss != '' THEN CONCAT(IFNULL(lieu_naiss,''), ' (', IFNULL(dept_naiss,''), ')') ELSE IFNULL(lieu_naiss,'') END as lieu_naiss
                    ,IFNULL(date_deces,'') as date_deces
                    ,CASE WHEN dept_deces != '' THEN CONCAT(IFNULL(lieu_deces,''), ' (', IFNULL(dept_deces,''), ')') ELSE IFNULL(lieu_deces,'') END as lieu_deces
                    FROM `".$sql_pref."_".$ibase."_individu` 
                    WHERE (id_pere = '".$row ['id_pere_pere']."' and id_mere = '".$row['id_pere_mere']."')
                        and id_indi != ".$row['id_pere']."
                    ORDER BY date_naiss";
                    $result2 = sql_exec($query,0);
                    
                    $cpt_freres = 0;
                    while ($row2 = mysqli_fetch_assoc($result2))
                    {   $ancetres_fs['id_indi']   [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $ancetres['id_indi'][$cpt_sosas];
                        $ancetres_fs['sosa_d']    [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $sosa_dyn;
                        $ancetres_fs['id_fs']     [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['id_indi'];
                        $ancetres_fs['nom']       [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['nom'];
                        $ancetres_fs['sosa_dyn']  [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['sosa_dyn'];
                        $ancetres_fs['sexe']      [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['sexe'];
                        $ancetres_fs['profession'][$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['profession'];
                        $ancetres_fs['date_naiss'][$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['date_naiss'];
                        $ancetres_fs['lieu_naiss'][$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['lieu_naiss'];
                        $ancetres_fs['date_deces'][$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['date_deces'];
                        $ancetres_fs['lieu_deces'][$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['lieu_deces'];
                        $cpt_freres = $cpt_freres + 1;
                    }
                  }
                }

                $cpt_sosas = $cpt_sosas + 1;                
            }
        }

        $flag_femme_commun = 0;
        if ($row['id_mere'] !== NULL)
        {    
            $j = array_search($row['id_mere'],$ancetres['id_indi']);
            if ($j)    {$flag_femme_commun = 1;}

            $z = array_search($row['id_indi'],$ancetres['id_indi']);

            if ($flag_femme_commun == 1)        // si on en a trouvé 1, c'est un ancetre commun
            {   $communs['id'][]         = $row['id_mere'];
                $communs['sosa_d'][]     = $ancetres['sosa_d'][$z] * 2 + 1;//construction dynamique de la numérotation sosa
                $communs['nom'][]        = $row['nom_mere'];
                $communs['date_naiss'][] = $row['date_naiss_mere'];
                $communs['date_deces'][] = $row['date_deces_mere'];
                $communs['lieu_naiss'][] = $row['lieu_naiss_mere'];
                $communs['generation'][] = $cpt_generations + 1;
                $communs['sosa_d_ref'][] = $ancetres['sosa_d'][$j];
            }
            else
            {   $ancetres['id_indi'][$cpt_sosas] = $row['id_mere'];
                $ancetres['generation'][$cpt_sosas] = $cpt_generations + 1;
                $ancetres['sosa_d'][$cpt_sosas] = $ancetres['sosa_d'][$z] * 2 + 1;//construction dynamique de la numérotation sosa

                $cpt_generations_plus = $cpt_generations + 1;
                $sosa_dyn = $ancetres['sosa_d'][$z] * 2 + 1;

                $ancetres['nom'][$cpt_sosas]        = $row['nom_mere'];
                $ancetres['sexe'][$cpt_sosas]       = $row['sexe_mere'];
                $ancetres['date_naiss'][$cpt_sosas] = $row['date_naiss_mere'];
                $ancetres['lieu_naiss'][$cpt_sosas] = $row['lieu_naiss_mere'];
                $ancetres['id_pere'][$cpt_sosas]    = $row['id_pere_mere'];
                $ancetres['id_mere'][$cpt_sosas]    = $row['id_mere_mere'];
                if ($perf == 'ME_G')
                { $ancetres['prenom2'][$cpt_sosas]    = $row['prenom2_mere'];
                  $ancetres['profession'][$cpt_sosas] = $row['profession_mere'];
                  $ancetres['date_deces'][$cpt_sosas] = $row['date_deces_mere'];
                  $ancetres['lieu_deces'][$cpt_sosas] = $row['lieu_deces_mere'];
                  $ancetres['date_maria'][$cpt_sosas] = $row['date_maria'];
                  $ancetres['lieu_maria'][$cpt_sosas] = $row['lieu_maria'];
                  $ancetres['sosa_dyn'][$cpt_sosas]   = $row['sosa_dyn_mere'];
                  $ancetres['sosa_d_ref'][$cpt_sosas] = "";

                  if (!($row['id_mere_pere'] == 0 AND $row['id_mere_mere'] == 0) )
                  { $query = "
                    SELECT
                     id_indi
                    ,CONCAT(nom,' ',prenom1) as nom_mere
                    ,sosa_dyn as sosa_dyn_mere
                    ,sexe as sexe_mere
                    ,IFNULL(profession,'') as profession_mere
                    ,IFNULL(date_naiss,'') as date_naiss_mere
                    ,CASE WHEN dept_naiss != '' THEN CONCAT(IFNULL(lieu_naiss,''), ' (', IFNULL(dept_naiss,''), ')') ELSE IFNULL(lieu_naiss,'') END as lieu_naiss_mere
                    ,IFNULL(date_deces,'') as date_deces_mere
                    ,CASE WHEN dept_deces != '' THEN CONCAT(IFNULL(lieu_deces,''), ' (', IFNULL(dept_deces,''), ')') ELSE IFNULL(lieu_deces,'') END as lieu_deces_mere
                    FROM `".$sql_pref."_".$ibase."_individu` _mere
                    WHERE (id_pere = '".$row['id_mere_pere']."' AND id_mere = '".$row['id_mere_mere']."')
                        and id_indi != ".$row['id_mere']."
                    ORDER BY date_naiss";
                    $result2 = sql_exec($query,0);

                    $cpt_freres = 0;
                    while ($row2 = mysqli_fetch_assoc($result2))
                    {   $ancetres_fs['id_indi']   [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $ancetres['id_indi'][$cpt_sosas];
                        $ancetres_fs['sosa_d']    [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $sosa_dyn;
                        $ancetres_fs['id_fs']     [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['id_indi'];
                        $ancetres_fs['nom']       [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['nom_mere'];
                        $ancetres_fs['sosa_dyn']  [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['sosa_dyn_mere'];
                        $ancetres_fs['sexe']      [$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['sexe_mere'];
                        $ancetres_fs['profession'][$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['profession_mere'];
                        $ancetres_fs['date_naiss'][$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['date_naiss_mere'];
                        $ancetres_fs['lieu_naiss'][$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['lieu_naiss_mere'];
                        $ancetres_fs['date_deces'][$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['date_deces_mere'];
                        $ancetres_fs['lieu_deces'][$ancetres['id_indi'][$cpt_sosas] ][$cpt_freres] = $row2['lieu_deces_mere'];
                        $cpt_freres = $cpt_freres + 1;
                    }
                  }
                }

                $cpt_sosas = $cpt_sosas + 1;
            }
        }
        if ($flag_homme_commun == 1 or $flag_femme_commun == 1) {$flag_homme_commun = 0;$flag_femme_commun = 0;}
    }
    $cpt_generations = $cpt_generations + 1;
    if ($cpt_generations <= $nb_generations - 1) 
    {    recup_ascendance ($ancetres, $old_cpt_sosas, $nb_generations, $perf);        // et on recommence... (appel récursif)
    }
}
}

function afficher_commun()
{ global $communs;
            // function uniquement pour le debug.
  if (isset($communs ['id'][0])) 
  {    echo '<table>';
      echo '<tr>';
      echo '<td class=bords_verti>i</td>';
      echo '<td class=bords_verti>id</td>';
      echo '<td class=bords_verti>sosa_d</td>';
      echo '<td class=bords_verti>nom</td>';
      echo '<td class=bords_verti>date_naiss</td>';
      echo '<td class=bords_verti>date_deces</td>';
      echo '<td class=bords_verti>lieu_naiss</td>';
      echo '<td class=bords_verti>generation</td>';
      echo '<td class=bords_verti>sosa_d_ref</td>';
      echo '</tr>';
  
      for ($i = 0; $i < count($communs['id']); $i++)
      {    echo '<tr>';
          echo '<td class=bords_verti>'.$i.'</td>';
          echo '<td class=bords_verti>'.$communs ['id'][$i].'</td>';
          echo '<td class=bords_verti>'.$communs ['sosa_d'][$i].'</td>';
          echo '<td class=bords_verti>'.$communs ['nom'][$i].'</td>';
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
{   global $ancetres;
    global $ancetres_fs;
	$keys = array_keys($ancetres);
    if (array_search('prenom2',$keys) === FALSE) {$perf = "";} else {$perf = "ME_G";}

    if (isset($ancetres['id_indi'][0])) 
    {    
      if ($excel == NULL)
      {   $table_d    = '<table>';
          $tr_d        = '<tr>';
          $td_d        = '<td class=bords_verti>';
          $table_f    = '</table>';
          $tr_f        = '</tr>';
          $td_f        = '</td>';
      } else
      {   $table_d    = '';
          $tr_d        = '';
          $td_d        = '';
          $table_f    = '';
          $tr_f        = chr(10);
          $td_f        = chr(9);
      }

      $ligne =
       $table_d
      .$tr_d
      .$td_d.'i'.$td_f
      .$td_d.'id_indi'.$td_f
      .$td_d.'sosa_d'.$td_f
      .$td_d.'generation'.$td_f
      .$td_d.'nom'.$td_f
      .$td_d.'sexe'.$td_f
      .$td_d.'date_naiss'.$td_f
      .$td_d.'lieu_naiss'.$td_f
      .$td_d.'id_pere'.$td_f
      .$td_d.'id_mere'.$td_f;
      if ($perf == "ME_G") 
      {  $ligne .=
         $td_d.'sosa_d_ref'.$td_f
        .$td_d.'sosa_dyn'.$td_f
        .$td_d.'prenom2'.$td_f
        .$td_d.'profession'.$td_f
        .$td_d.'date_maria'.$td_f
        .$td_d.'lieu_maria'.$td_f
        .$td_d.'date_deces'.$td_f
        .$td_d.'lieu_deces'.$td_f
        .$tr_f;
	  }

      if ($excel) {echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');}
      else {echo $ligne;}

      for ($i = 0; $i < count($ancetres['id_indi']); $i++)
      {  $ligne = 
         $tr_d
        .$td_d.$i.$td_f
        .$td_d.$ancetres['id_indi'][$i].$td_f
        .$td_d.$ancetres['sosa_d'][$i].$td_f
        .$td_d.$ancetres['generation'][$i].$td_f
        .$td_d.$ancetres['nom'][$i].$td_f
        .$td_d.$ancetres['sexe'][$i].$td_f
        .$td_d.fctDisplayDateExcel($ancetres['date_naiss'][$i]).$td_f
        .$td_d.$ancetres['lieu_naiss'][$i].$td_f
        .$td_d.$ancetres['id_pere'][$i].$td_f
        .$td_d.$ancetres['id_mere'][$i].$td_f;
        if ($perf == "ME_G") 
        {  $ligne .=
           $td_d.$ancetres['sosa_d_ref'][$i].$td_f
          .$td_d.$ancetres['sosa_dyn'][$i].$td_f
	      .$td_d.$ancetres['prenom2'][$i].$td_f
          .$td_d.$ancetres['profession'][$i].$td_f
          .$td_d.fctDisplayDateExcel($ancetres['date_maria'][$i]).$td_f
          .$td_d.$ancetres['lieu_maria'][$i].$td_f
          .$td_d.fctDisplayDateExcel($ancetres['date_deces'][$i]).$td_f
          .$td_d.$ancetres['lieu_deces'][$i].$td_f
          .$tr_f;
		}
        if ($excel) {echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');}
        else {echo $ligne;}
      }
        $ligne = $ligne.$table_f;

        if ($excel == NULL)
        {
            $ligne =
             $table_d
            .$tr_d
            .$td_d.'i'.$td_f
            .$td_d.'id_indi'.$td_f
            .$td_d.'sosa_d'.$td_f
            .$td_d.'sosa_dyn'.$td_f
            .$td_d.'id_fs'.$td_f
            .$td_d.'nom'.$td_f
            .$td_d.'sexe'.$td_f
            .$td_d.'profession'.$td_f
            .$td_d.'date_naiss'.$td_f
            .$td_d.'lieu_naiss'.$td_f
            .$td_d.'date_deces'.$td_f
            .$td_d.'lieu_deces'.$td_f
            .$tr_f;
            echo $ligne;
    
            for ($ii = 0; $ii < @count($ancetres['id_indi']); $ii++)
            { if (isset(  $ancetres_fs['sosa_d'][$ancetres['id_indi'][$ii] ] ) )
			  { for ($jj = 0; $jj < @count($ancetres_fs['sosa_d'][$ancetres['id_indi'][$ii] ]); $jj++)
                {    $ligne = 
                     $tr_d
                    .$td_d.$jj.$td_f
                    .$td_d.$ancetres_fs['id_indi']   [$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$td_d.$ancetres_fs['sosa_d']    [$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$td_d.$ancetres_fs['sosa_dyn']  [$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$td_d.$ancetres_fs['id_fs']     [$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$td_d.$ancetres_fs['nom']       [$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$td_d.$ancetres_fs['sexe']      [$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$td_d.$ancetres_fs['profession'][$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$td_d.$ancetres_fs['date_naiss'][$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$td_d.$ancetres_fs['lieu_naiss'][$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$td_d.$ancetres_fs['date_deces'][$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$td_d.$ancetres_fs['lieu_deces'][$ancetres['id_indi'][$ii] ][$jj].$td_f
                    .$tr_f;
                    echo $ligne;
                }
			  }
            }
            echo $table_f;
        }
	}

}

function inserer_resultat($i_ancetres,$zz,$perf)
{ // Fonction complémentaire appelée dans la fonction integrer implexe (indépendant de recherche d'ascendance 
  // Cette indépendance permet de travailler les implexes dans une 2ème passe, comme un calcul analytique.
  // Cela permet de plus grandes possibilités algorithmiques.

	global $ancetres;
	$ancetres['nom'][$i_ancetres]        = $ancetres['nom'][$zz];
	$ancetres['sexe'][$i_ancetres]       = $ancetres['sexe'][$zz];
	$ancetres['date_naiss'][$i_ancetres] = $ancetres['date_naiss'][$zz];
	$ancetres['lieu_naiss'][$i_ancetres] = $ancetres['lieu_naiss'][$zz];
	$ancetres['id_pere'][$i_ancetres]    = $ancetres['id_pere'][$zz];
	$ancetres['id_mere'][$i_ancetres]    = $ancetres['id_mere'][$zz];
	if ($perf == 'ME_G')
	{   $ancetres['prenom2'][$i_ancetres]    = $ancetres['prenom2'][$zz];
		$ancetres['profession'][$i_ancetres] = $ancetres['profession'][$zz];
		$ancetres['date_deces'][$i_ancetres] = $ancetres['date_deces'][$zz];
		$ancetres['lieu_deces'][$i_ancetres] = $ancetres['lieu_deces'][$zz];
		$ancetres['date_maria'][$i_ancetres] = $ancetres['date_maria'][$zz];
		$ancetres['lieu_maria'][$i_ancetres] = $ancetres['lieu_maria'][$zz];
		$ancetres['sosa_dyn'][$i_ancetres]   = $ancetres['sosa_dyn'][$zz];
	}
}

function integrer_implexe($nb_generations, $perf)
{   global $ancetres;
    global $communs;
        // Principe de base: repérer toutes les lignes à dupliquer (en fonction du nb de generation) et les inserer.
        // 2 astuces algorithmiques majeures :
        //    - l'insertion des implexes au fur et à mesure dans le même tableau initial 1ère passe permet de gérer la récursivité à l infini
        //    - l'avancement des ancetres uniquement par rapport aux ancetres trouvés lors de la generation précédente
        //        permet d'optimiser au mieux le nombre de boucles : 4 boucles imbriquées aux petits oignons.
        // Résultat : code super court (36  lignes) qui peut gérer les implexes les plus complexes. 
        //   Ex : une base de 1200 souches communes, 13000 implexes générés en 100 secondes.
        //   Ex : je trouve des implexes que Heredis 8 ne trouvent pas. Et ils sont bons !
// afficher_commun();

    $i_ancetres = count($ancetres['id_indi']);
    if (!empty($communs['id']))
    {   for ($ii = 0; $ii < count($communs['id']); $ii++)        //peu de lignes....
		{   $ecart_sosa = $communs['sosa_d_ref'][$ii] - $communs['sosa_d'][$ii];    // peut etre negatif
			$delta_gen_max = $nb_generations - $communs['generation'][$ii];  // rappel : nb_generations est passé en parametre d'appel de la proc
			$zz = array_search($communs['sosa_d_ref'][$ii],$ancetres['sosa_d']);
			$ancetres['id_indi'][$i_ancetres]       = $ancetres['id_indi'][$zz];
			$ancetres['generation'][$i_ancetres]    = $communs['generation'][$ii];
			$ancetres['sosa_d'][$i_ancetres]        = $communs['sosa_d'][$ii];
			$ancetres['sosa_d_ref'][$i_ancetres]    = $communs['sosa_d_ref'][$ii];
			inserer_resultat($i_ancetres,$zz,$perf);
			$i_ancetres++;

			$temp = array();
			$sosa_d_ref = array();
			$delta_gen = 1;
			$sosa_d_ref[0] = $communs['sosa_d_ref'][$ii];
			while ($delta_gen <= $delta_gen_max)        // 1 boucle par génération à remonter
			{    for ($jj = 0; $jj < @count($sosa_d_ref); $jj++)    // on lit les sosa_ref trouvés lors de la boucle précédente maxi 200 en moyenne sur la generation 10
				{    for ($kk = $sosa_d_ref[$jj] * 2; $kk < ($sosa_d_ref[$jj] + 1) * 2; $kk++)    // 2 boucles pour trouver le père et la mère
					{    $zz = array_search($kk,$ancetres['sosa_d']);
						 if ($zz !== FALSE and isset($sosa_d_ref[$jj]))        // a partir de la 2eme generation delta, on peut ne pas trouver de sosa.
						{   $ancetres['id_indi'][$i_ancetres]       = $ancetres['id_indi'][$zz];
							$ancetres['generation'][$i_ancetres]    = $communs['generation'][$ii] + $delta_gen;
							$ancetres['sosa_d'][$i_ancetres]        = $kk - $ecart_sosa * pow(2,$delta_gen);
							$ancetres['sosa_d_ref'][$i_ancetres]    = $kk;
							inserer_resultat($i_ancetres,$zz,$perf);
// echo '<br>jj:'.$jj.' kk:'.$kk.' zz:'.$zz.' sosa_d:'.$ancetres['sosa_d'][$i_ancetres].'s_ref:'.$ancetres['sosa_d_ref'][$i_ancetres].'gene:'.$ancetres['generation'][$i_ancetres];
							$temp[] = $kk;
// $i_temp++;
							$i_ancetres++;
						}
					}
				}
				$delta_gen++;
				$sosa_d_ref = $temp;
				$temp = array();
			}
		}
    }
// afficher_ascendance();
}

function recup_consanguinite()
{   global $ibase;
    global $res;
    global $ancetres;
    global $communs;

    function sosa2generation ($sosa)
    {    $i = 0;
        while (pow (2,$i) <= $sosa) 
        {    $i = $i + 1;
        }
        return $i;
    }

    // for ($ii = 0; $ii < 1500; $ii++){$ancetres['id_indi'][] = '';}        // sinon Notice: Undefined offset: 1 _get_ascendancy.php on line 250 !!!
    // $ancetres[][] = '';$communs[][] = '';$cpt_generations = 0;
    $ancetres = array();$communs = array();$cpt_generations = 0;
    $ancetres['id_indi'][0] = $_REQUEST['id'];
    recup_ascendance ($ancetres,0,15,'ME_G');
    // afficher_ascendance();
    // afficher_commun();

    $i_communs = 0;
    $i_t_consang = 0;
    if (isset ($communs['generation']))
    {    while ($i_communs < count($communs ['generation'])) 
        {    //$ligne[] = "";    
            for ($ii = 0; $ii < 12; $ii++){$ligne[$ii] = '';}
            if ($communs['sosa_d'][$i_communs] % 2 == 0)
            {   $ligne[0] = $communs ['id'][$i_communs];
                $ligne[1] = $communs ['sosa_d'][$i_communs];
                $ligne[2] = $communs ['nom'][$i_communs];
                $ligne[3] = $communs ['date_naiss'][$i_communs];
                $ligne[4] = $communs ['lieu_naiss'][$i_communs];
                $i_communs++;
            }
            if ($communs['sosa_d'][$i_communs] % 2 == 1)    
            {   $ligne[5] = $communs ['id'][$i_communs];
                $ligne[6] = $communs ['sosa_d'][$i_communs];
                $ligne[7] = $communs ['nom'][$i_communs];
                $ligne[8] = $communs ['date_naiss'][$i_communs];
                $ligne[9] = $communs ['lieu_naiss'][$i_communs];
                $i_communs++;
            }
    
            $res["id"][]            = $i_t_consang;
            $res["generation"][]    = $communs ['generation'][$i_communs - 1];
            $res["id1"][]           = $ligne[0];
            $res["nom1"][]          = $ligne[2];
            $res["date_naiss1"][]   = $ligne[3];
            $res["lieu_naiss1"][]   = $ligne[4];
            $res["id2"][]           = $ligne[5];
            $res["nom2"][]          = $ligne[7];
            $res["date_naiss2"][]   = $ligne[8];
            $res["lieu_naiss2"][]   = $ligne[9];
            $res["sexe1"][]         = "M";
            $res["sexe2"][]         = "F";

                            // Stockage du sosa souche ($sosa2)
            if ($ligne[0] != NULL)
            {   $sosa2    = $ligne[1];
                $id_sosa2 = $ligne[0];
            } else
            {   $sosa2    = $ligne[6];
                $id_sosa2 = $ligne[5];
            }

                            // Recherche l'ascendance existante de l'autre sosa de la souche ($sosa1)
            $z = 0;while ($ancetres['id_indi'][$z] !== $id_sosa2) {$z=$z+1;}
            $sosa1 = $ancetres ['sosa_d'][$z];

                            // Affichage génération intermédiaire s'il y a lieu
//            $degre_plus = 0;
            while (sosa2generation ($sosa1) < sosa2generation ($sosa2))
            {    $sosa2 = floor($sosa2 / 2);
                $z = 0;while ($ancetres ['sosa_d'][$z] != $sosa2) {$z=$z+1;}
                
                $row = recup_identite($ancetres['id_indi'][$z], $ibase);

                $res["id"][]            = $i_t_consang;
                $res["generation"][]    = $communs ['generation'][$i_communs - 1];
                $res["id1"][]           = 0;
                $res["nom1"][]          = "";
                $res["date_naiss1"][]   = "";
                $res["lieu_naiss1"][]   = "";
                $res["id2"][]           = $ancetres['id_indi'][$z];
                $res["nom2"][]          = $row[0];
                $res["date_naiss2"][]   = $row[1];
                $res["lieu_naiss2"][]   = $row[2];
                $res["sexe1"][]         = "";
                $res["sexe2"][]         = $row[3];
            }

            $sosa1 = floor($sosa1 / 2);
            $sosa2 = floor($sosa2 / 2);
            $degre = 0;
            while ($sosa1 != $sosa2)
            {    $y = 0;while ($ancetres ['sosa_d'][$y] != $sosa1) {$y=$y+1;}
                $z = 0;while ($ancetres ['sosa_d'][$z] != $sosa2) {$z=$z+1;}

                $row = recup_identite($ancetres['id_indi'][$y], $ibase);
                $nom_y = $row[0];
                $date_y = $row[1];
                $lieu_y = $row[2];
                $sexe_y = $row[3];

                $row = recup_identite($ancetres['id_indi'][$z], $ibase);

                $sosa1 = floor($sosa1 / 2);
                $sosa2 = floor($sosa2 / 2);
                $degre = $degre + 1;

                if ($sosa1 !== $sosa2)
                {    $temp = $communs ['generation'][$i_communs - 1];
                } else
                {    $temp = 'FIN';    // astuce pour indiquer le couple consanguin
                }

                $res["id"][]            = $i_t_consang;
                $res["generation"][]    = $temp;
                $res["id1"][]           = $ancetres['id_indi'][$y];
                $res["nom1"][]          = $nom_y;
                $res["date_naiss1"][]   = $date_y;
                $res["lieu_naiss1"][]   = $lieu_y;
                $res["id2"][]           = $ancetres['id_indi'][$z];
                $res["nom2"][]          = $row[0];
                $res["date_naiss2"][]   = $row[1];
                $res["lieu_naiss2"][]   = $row[2];
                $res["sexe1"][]         = $sexe_y;
                $res["sexe2"][]         = $row[3];

            }
            $i_t_consang++;
        }
    }
}
