<?php
// require_once ("_sql.inc.php");

function recup_descendance ($descendants, $cpt_sosas, $nb_generations_desc, $perf, $flag_maria)
{
/****************** CONSTITUTION dynamique DE LA REQUETE SQL en fonction du nb de generations***************/
global $sql_pref;
global $nb_generations_desc;
global $descendants;
global $descendants_ma;
global $cpt_generations_desc;
global $cousins;
global $cpt_cousins;
global $collate;
global $ADDRC;

$old_indice = "";                    // Initialition variable pour eviter erreur REPORTING

if ($cpt_sosas == 0)
{   $query = 'DROP TABLE IF EXISTS '.$sql_pref.'_'.$ADDRC.'_desc_cles';
    sql_exec($query);
                                            /*** TRAITEMENT DU PREMIER INDIVIDU **/
        // contrairement aux autres individus, on n'arrive pas par les parents, mais directement par l'individu, d'ou un traitement différent 
        // ce sont les requetes SQL qui diffèrent. On pourrait jouer sur la génération des requêtes et garder ainsi le reste en commun. A faire.
    if ($flag_maria !== "MARR")
    {   $query = "SELECT 
        a.id_indi
        ,CONCAT(a.nom, ' ',a.prenom1) as nom
        ,CONCAT(IFNULL(a.prenom2,''), ' ',IFNULL(a.prenom3,'')) as prenom2
        ,a.sexe
        ,IFNULL(a.profession,'') as profession
        ,IFNULL(a.date_naiss,'') as date_naiss
        ,CASE WHEN a.dept_naiss != '' THEN CONCAT(IFNULL(a.lieu_naiss,''), ' (', IFNULL(a.dept_naiss,''), ')') ELSE IFNULL(a.lieu_naiss,'') END as lieu_naiss
        ,IFNULL(a.date_deces,'') as date_deces
        ,CASE WHEN a.dept_deces != '' THEN CONCAT(IFNULL(a.lieu_deces,''), ' (', IFNULL(a.dept_deces,''), ')') ELSE IFNULL(a.lieu_deces,'') END as lieu_deces
        ,NULL as id_parent
        ,a.sosa_dyn
        ,NULL as id_conj
        ,NULL as date_maria
        ,NULL as lieu_maria
        ,NULL as nom_conj
        ,NULL as pre2_conj
        ,NULL as sexe_conj
        ,NULL as sosa_dyn_conj
        FROM `".$sql_pref."_".$_REQUEST['ibase']."_individu` a
        WHERE a.id_indi = ".$descendants['id_indi'][0];
        $result = sql_exec($query,0);
        $row = mysqli_fetch_assoc($result);
    } else
    {    $query = "
        CREATE TABLE ".$sql_pref."_".$ADDRC."_desc_cles (
        id_indi          int NOT NULL default 0,
        id_conj          int NULL,
        date_maria       varchar(32),
        lieu_maria       varchar(100),
        dept_maria       varchar(100),
        id_parent        int,
        c                char(1) NULL,
        d                int NULL) "." engine = myisam default ".$collate."_general_ci";
        sql_exec($query);

        $query = "set @old = 0";
        sql_exec($query);

        $query = "    INSERT INTO ".$sql_pref."_".$ADDRC."_desc_cles
        SELECT
        a.id_indi
        , case when b.id_wife is not NULL then b.id_wife when c.id_husb is not NULL then c.id_husb end 
        , case when b.id_wife is not NULL then IFNULL(b.date_evene,'') when c.id_husb is not NULL then IFNULL(c.date_evene,'') end 
        , case when b.id_wife is not NULL then IFNULL(b.lieu_evene,'') when c.id_husb is not NULL then IFNULL(c.lieu_evene,'') end 
        , case when b.id_wife is not NULL then IFNULL(b.dept_evene,'') when c.id_husb is not NULL then IFNULL(c.dept_evene,'') end 
        , a.id_indi
        ,if (a.id_indi != @old, 'O', 'N')
        ,@old := a.id_indi
        FROM `".$sql_pref."_".$_REQUEST['ibase']."_individu` a
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_evenement` b ON (a.id_indi = b.id_husb and b.type_evene = 'MARR')
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_evenement` c ON (a.id_indi = c.id_wife and c.type_evene = 'MARR')
        where a.id_indi = ".$descendants['id_indi'][0]."
        and if (a.id_indi != @old, 'O', 'N') = 'O'
        "        ;
        sql_exec($query,0);

        $query = "SELECT 
         a.id_indi
        ,CONCAT (b.nom, ' ',b.prenom1) as nom
        ,CONCAT(IFNULL(b.prenom2,''),' ',IFNULL(b.prenom3,'')) as prenom2
        ,b.sexe
        ,IFNULL(b.profession,'') as profession
        ,IFNULL(b.date_naiss,'') as date_naiss
        ,CASE WHEN b.dept_naiss != '' THEN CONCAT(IFNULL(b.lieu_naiss,''), ' (', IFNULL(b.dept_naiss,''), ')') ELSE IFNULL(b.lieu_naiss,'') END as lieu_naiss
        ,IFNULL(b.date_deces,'') as date_deces
        ,CASE WHEN b.dept_deces != '' THEN CONCAT(IFNULL(b.lieu_deces,''), ' (', IFNULL(b.dept_deces,''), ')') ELSE IFNULL(b.lieu_deces,'') END as lieu_deces
        ,a.id_parent
        ,b.sosa_dyn
        ,a.id_conj
        ,IFNULL(a.date_maria,'') as date_maria
        ,CASE WHEN a.dept_maria != '' THEN CONCAT(IFNULL(a.lieu_maria,''), ' (', IFNULL(a.dept_maria,''), ')') ELSE IFNULL(a.lieu_maria,'') END as lieu_maria
        ,CONCAT(c.nom,' ',c.prenom1) as nom_conj
        ,CONCAT(IFNULL(c.prenom2,''),' ',IFNULL(c.prenom3,'')) as pre2_conj
        ,c.sexe as sexe_conj
        ,c.sosa_dyn as sosa_dyn_conj
        FROM ".$sql_pref."_".$ADDRC."_desc_cles a
        INNER JOIN      `".$sql_pref."_".$_REQUEST['ibase']."_individu` b ON (a.id_indi = b.id_indi)
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_individu` c ON (a.id_conj = c.id_indi)
        ";
        $result = sql_exec($query,0);
        $row = mysqli_fetch_assoc($result);
    }
    if (!empty($row['id_indi']))
    {  $descendants ['id_indi'][0]   = $row['id_indi'];
       $descendants ['indice'][0]    = 'A';
       $descendants['generation'][0] = 0;
       $descendants['date_naiss'][0] = $row['date_naiss'];
       $descendants['lieu_naiss'][0] = $row['lieu_naiss'];
       $descendants['nom'][0]        = $row['nom'];
       $descendants['sosa_d'][0]     = $row['sosa_dyn'];
       $descendants['sexe'][0]       = $row['sexe'];
           
       if ($perf == 'ME_G')
       {   $descendants['prenom2'][0]    = $row['prenom2'];
           $descendants['profession'][0] = $row['profession'];
           $descendants['date_deces'][0] = $row['date_deces'];
           $descendants['lieu_deces'][0] = $row['lieu_deces'];
           $descendants['id_parent'][0]  = $row['id_parent'];
           $descendants['id_conj'][0]    = $row['id_conj'];
           $descendants['date_maria'][0] = $row['date_maria'];
           $descendants['lieu_maria'][0] = $row['lieu_maria'];
           $descendants['nom_conj'][0]   = $row['nom_conj'];
           $descendants['pre2_conj'][0]  = $row['pre2_conj'];
           $descendants['sexe_conj'][0]  = $row['sexe_conj'];
           $descendants['sosa_conj'][0]  = $row['sosa_dyn_conj'];
       }
    } else
    {  $descendants ['id_indi'][0]   = NULL;
       $descendants ['indice'][0]    = NULL;
       $descendants['generation'][0] = NULL;
       $descendants['date_naiss'][0] = NULL;
       $descendants['lieu_naiss'][0] = NULL;
	   $descendants['nom'][0]        = NULL;
       $descendants['sosa_d'][0]     = NULL;
       $descendants['sexe'][0]       = NULL;
       if ($perf == 'ME_G')
       { $descendants['prenom2'][0]  = NULL;
       $descendants['profession'][0] = NULL;
       $descendants['date_deces'][0] = NULL;
       $descendants['lieu_deces'][0] = NULL;
       $descendants['id_parent'][0]  = NULL;
       $descendants ['id_conj'][0]   = NULL;
       $descendants['date_maria'][0] = NULL;
       $descendants['lieu_maria'][0] = NULL;
       $descendants['nom_conj'][0]   = NULL;
       $descendants['pre1_conj'][0]  = NULL;
       $descendants['pre2_conj'][0]  = NULL;
       $descendants['pre3_conj'][0]  = NULL;
       $descendants['sexe_conj'][0]  = NULL;
       $descendants['sosa_conj'][0]  = NULL;
	   }
    }

    $cpt_cousins = 0;
}
/**************************** FIN DU TRAITEMENT DU PREMIER INDIVIDU ***************************/

// if ($descendants ['id_indi'][$cpt_sosas] !== NULL)        // test arret de la récursivité Warning obligatoire.
if (!empty($descendants ['id_indi'][$cpt_sosas]))        // test arret de la récursivité Warning obligatoire.
{
    if ($flag_maria !== "MARR")
    {    $query = "SELECT 
         id_indi
        ,CONCAT(nom, ' ',prenom1) as nom
        ,CONCAT(IFNULL(prenom2,''), ' ',IFNULL(prenom3,'')) as prenom2
        ,sexe
        ,IFNULL(profession,'') as profession
        ,IFNULL(date_naiss,'') as date_naiss
        ,CASE WHEN dept_naiss != '' THEN CONCAT(IFNULL(lieu_naiss,''), ' (', IFNULL(dept_naiss,''), ')') ELSE IFNULL(lieu_naiss,'') END as lieu_naiss
        ,IFNULL(date_deces,'') as date_deces
        ,CASE WHEN dept_deces != '' THEN CONCAT(IFNULL(lieu_deces,''), ' (', IFNULL(dept_deces,''), ')') ELSE IFNULL(lieu_deces,'') END as lieu_deces
        ,id_pere as id_parent
        ,sosa_dyn
        ,NULL as id_conj
        ,NULL as date_maria
        ,NULL as lieu_maria
        ,NULL as nom_conj
        ,NULL as pre2_conj
        ,NULL as sexe_conj
        ,NULL as sosa_dyn_conj
        FROM `".$sql_pref."_".$_REQUEST['ibase']."_individu`
        WHERE id_pere IN (";
        for ($cpt_sosas = 0; $cpt_sosas < count($descendants['id_indi']); $cpt_sosas++)
        {    $query = $query.$descendants ['id_indi'][$cpt_sosas].",";
        }
        $query = substr_replace($query," ",-1,1);                         // Suppression du dernier caractère de la chaine
        $query = $query.") ";

        $query = $query." UNION ALL SELECT 
         id_indi
        ,CONCAT(nom, ' ',prenom1) as nom
        ,CONCAT(IFNULL(prenom2,''), ' ',IFNULL(prenom3,'')) as prenom2
        ,sexe
        ,IFNULL(profession,'') as profession
        ,IFNULL(date_naiss,'') as date_naiss
        ,CASE WHEN dept_naiss != '' THEN CONCAT(IFNULL(lieu_naiss,''), ' (', IFNULL(dept_naiss,''), ')') ELSE IFNULL(lieu_naiss,'') END as lieu_naiss
        ,IFNULL(date_deces,'')
        ,CASE WHEN dept_deces != '' THEN CONCAT(IFNULL(lieu_deces,''), ' (', IFNULL(dept_deces,''), ')') ELSE IFNULL(lieu_deces,'') END as lieu_deces
        ,id_mere
        ,sosa_dyn
        ,NULL
        ,NULL
        ,NULL
        ,NULL
        ,NULL
        ,NULL
        ,NULL
        FROM `".$sql_pref."_".$_REQUEST['ibase']."_individu`
        WHERE id_mere IN (";
        for ($cpt_sosas = 0; $cpt_sosas < count($descendants['id_indi']); $cpt_sosas++)
        {    $query = $query.$descendants ['id_indi'][$cpt_sosas].",";
        }
        $query = substr_replace($query," ",-1,1);                         // Suppression du dernier caractère de la chaine
        $query = $query.")";

        $result = sql_exec($query,0);
    } else
    {   $query = 'DROP TABLE IF EXISTS '.$sql_pref.'_'.$ADDRC.'_desc_cles';
        sql_exec($query);

        $query = "
        CREATE TABLE ".$sql_pref."_".$ADDRC."_desc_cles (
        id_indi     int NOT NULL default 0,
        id_conj     int NULL,
        date_maria  varchar(32),
        lieu_maria  varchar(100),
        dept_maria  varchar(100),
        id_parent   int,
        c           char(1) NULL,
        d           int NULL) "." engine = myisam default ".$collate."_general_ci";
        sql_exec($query);

        $query = "set @old=0";
        sql_exec($query);

        $save_cpt_sosas = $cpt_sosas;
        
        $query = "
        INSERT INTO ".$sql_pref."_".$ADDRC."_desc_cles
        SELECT
        a.id_indi
        , case when b.id_wife is not NULL then b.id_wife when c.id_husb is not NULL then c.id_husb end 
        , case when b.id_wife is not NULL then IFNULL(b.date_evene,'') when c.id_husb is not NULL then IFNULL(c.date_evene,'') end 
        , case when b.id_wife is not NULL then IFNULL(b.lieu_evene,'') when c.id_husb is not NULL then IFNULL(c.lieu_evene,'') end 
        , case when b.id_wife is not NULL then IFNULL(b.dept_evene,'') when c.id_husb is not NULL then IFNULL(c.dept_evene,'') end 
        , a.id_pere
        ,if (a.id_indi != @old, 'O', 'N')
        ,@old := a.id_indi
        FROM `".$sql_pref."_".$_REQUEST['ibase']."_individu` a
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_evenement` b ON (a.id_indi = b.id_husb and b.type_evene = 'MARR')
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_evenement` c ON (a.id_indi = c.id_wife and c.type_evene = 'MARR')
        where a.id_pere IN (";

        while (isset($descendants ['id_indi'][$cpt_sosas]))
        {    $query = $query.$descendants ['id_indi'][$cpt_sosas].",";
            $cpt_sosas = $cpt_sosas + 1;
        }
    
        $query = substr_replace($query," ",-1,1);                         // Suppression du dernier caractère de la chaine

        $query = $query.")
        and if (a.id_indi != @old, 'O', 'N') = 'O'
        UNION ALL
        SELECT
        a.id_indi
        , case when b.id_wife is not NULL then b.id_wife when c.id_husb is not NULL then c.id_husb end 
        , case when b.id_wife is not NULL then IFNULL(b.date_evene,'') when c.id_husb is not NULL then IFNULL(c.date_evene,'') end 
        , case when b.id_wife is not NULL then IFNULL(b.lieu_evene,'') when c.id_husb is not NULL then IFNULL(c.lieu_evene,'') end 
        , case when b.id_wife is not NULL then IFNULL(b.dept_evene,'') when c.id_husb is not NULL then IFNULL(c.dept_evene,'') end 
        , a.id_mere
        ,if (a.id_indi != @old, 'O', 'N')
        ,@old := a.id_indi
        FROM `".$sql_pref."_".$_REQUEST['ibase']."_individu` a
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_evenement` b ON (a.id_indi = b.id_husb and b.type_evene = 'MARR')
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_evenement` c ON (a.id_indi = c.id_wife and c.type_evene = 'MARR')
        where a.id_mere IN (";

        $cpt_sosas = $save_cpt_sosas;            // reinitialisation du compteur pour relire une 2eme fois
        while (isset($descendants ['id_indi'][$cpt_sosas]))
        {    $query = $query.$descendants ['id_indi'][$cpt_sosas].",";
            $cpt_sosas = $cpt_sosas + 1;
        }

        $query = substr_replace($query," ",-1,1);                         // Suppression du dernier caractère de la chaine

        $query = $query.")
        and if (a.id_indi != @old, 'O', 'N') = 'O'
        ";
        sql_exec($query,0);

        $query = "SELECT 
         a.id_indi
        ,CONCAT(b.nom, ' ',b.prenom1) as nom
        ,CONCAT(IFNULL(b.prenom2,''), ' ',IFNULL(b.prenom3,'')) as prenom2
        ,b.sexe
        ,IFNULL(b.profession,'') as profession
        ,IFNULL(b.date_naiss,'') as date_naiss
        ,CASE WHEN b.dept_naiss != '' THEN CONCAT(IFNULL(b.lieu_naiss,''), ' (', IFNULL(b.dept_naiss,''), ')') ELSE IFNULL(b.lieu_naiss,'') END as lieu_naiss
        ,IFNULL(b.date_deces,'') as date_deces
        ,CASE WHEN b.dept_deces != '' THEN CONCAT(IFNULL(b.lieu_deces,''), ' (', IFNULL(b.dept_deces,''), ')') ELSE IFNULL(b.lieu_deces,'') END as lieu_deces
        ,a.id_parent
        ,b.sosa_dyn
        ,a.id_conj
        ,IFNULL(a.date_maria,'') as date_maria
        ,CASE WHEN a.dept_maria != '' THEN CONCAT(IFNULL(a.lieu_maria,''), ' (', IFNULL(a.dept_maria,''), ')') ELSE IFNULL(a.lieu_maria,'') END as lieu_maria
        ,CONCAT(c.nom,' ',c.prenom1) as nom_conj
        ,CONCAT(IFNULL(c.prenom2,''),' ',IFNULL(c.prenom3,'')) as pre2_conj
        ,c.sexe as sexe_conj
        ,c.sosa_dyn as sosa_dyn_conj
        FROM ".$sql_pref."_".$ADDRC."_desc_cles a
        INNER JOIN      `".$sql_pref."_".$_REQUEST['ibase']."_individu` b ON (a.id_indi = b.id_indi)
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_individu` c ON (a.id_conj = c.id_indi)
        ORDER BY a.id_parent, date_naiss
        ";
        $result = sql_exec($query,0);
    }
    $old_cpt_sosas = $cpt_sosas;
    $i = chr(65);                                                    // lettre A
    while ($row = mysqli_fetch_assoc($result))
    {    $flag_doublon = 0;
        $z=0;
        while (isset($descendants['id_indi'][$z]) and $flag_doublon == 0) 
        {    if ($descendants['id_indi'][$z] == $row['id_indi']) 
            {    $flag_doublon = 1;
            } 
            $z = $z+1; 
        }

        if ($flag_doublon == 0)
        {    $j = $cpt_sosas - 1;
            while ($descendants ['id_indi'][$j] != $row['id_parent'])
            {    $j = $j - 1;
            }
            if ($descendants ['id_indi'][$j] != '' and isset($row['id_indi']))
            {    $descendants ['id_indi'][$cpt_sosas] = $row['id_indi'];

                if ($descendants ['indice'][$j] != $old_indice) {$i = "A";}
                $descendants ['indice'][$cpt_sosas] = $descendants ['indice'][$j].$i;    // on accole la lettre de l'alphabet qui va bien
                $temp = $cpt_generations_desc + 1;
                $descendants['generation'][$cpt_sosas] = $temp;
                $descendants['lieu_naiss'][$cpt_sosas] = $row['lieu_naiss'];
                $descendants['nom'][$cpt_sosas]        = $row['nom'];
                $descendants['sosa_d'][$cpt_sosas]     = $row['sosa_dyn'];
                $descendants['sexe'][$cpt_sosas]       = $row['sexe'];
                    
                if ($perf == 'ME_G')
                {   $descendants['prenom2'][$cpt_sosas]    = $row['prenom2'];
                    $descendants['profession'][$cpt_sosas] = $row['profession'];
                    $descendants['date_naiss'][$cpt_sosas] = $row['date_naiss'];
                    $descendants['date_deces'][$cpt_sosas] = $row['date_deces'];
                    $descendants['lieu_deces'][$cpt_sosas] = $row['lieu_deces'];
                    $descendants['id_parent'][$cpt_sosas]  = $row['id_parent'];
                    $descendants['id_conj'][$cpt_sosas]    = $row['id_conj'];
                    $descendants['date_maria'][$cpt_sosas] = $row['date_maria'];
                    $descendants['lieu_maria'][$cpt_sosas] = $row['lieu_maria'];
                    $descendants['nom_conj'][$cpt_sosas]   = $row['nom_conj'];
                    $descendants['pre2_conj'][$cpt_sosas]  = $row['pre2_conj'];
                    $descendants['sexe_conj'][$cpt_sosas]  = $row['sexe_conj'];
                    $descendants['sosa_conj'][$cpt_sosas]  = $row['sosa_dyn_conj'];
                }
                $cpt_sosas = $cpt_sosas + 1;
                $old_indice = $descendants ['indice'][$j];
                $i = chr(ord($i) + 1);                                        // on avance l'alphabet
            }
        }
        // else
        // {   $cousins ['id_indi'][$cpt_cousins]        = $row[0];
            // $cousins ['pere'][$cpt_cousins]        = $row[13];
            // $cousins ['generation'][$cpt_cousins]    = $descendants['generation'][$z-1];
            // $cpt_cousins = $cpt_cousins + 1;
        // }
    }
    $cpt_generations_desc = $cpt_generations_desc + 1;
    if ($cpt_generations_desc <= $nb_generations_desc - 1)
    {    recup_descendance ($descendants, $old_cpt_sosas, $nb_generations_desc, $perf, $flag_maria);         // et on recommence... (appel récursif)
    }
}
}

function afficher_descendance($excel = NULL)
{    // Export Excel. En mode HTML, uniquement pour le deboguage.
    global $descendants;

	$keys = array_keys($descendants);
    if (array_search('prenom2',$keys) === FALSE) {$perf = "ME_P";} else {$perf = "ME_G";}

    if (isset($descendants ['id_indi'][0])) 
    { if ($perf == "ME_G")
	  { 
         array_multisort (
         $descendants['indice']
        ,$descendants['id_indi']
        ,$descendants['generation']
        ,$descendants['nom']
        ,$descendants['prenom2']
        ,$descendants['sexe']
        ,$descendants['profession']
        ,$descendants['date_naiss']
        ,$descendants['lieu_naiss']
        ,$descendants['date_deces']
        ,$descendants['lieu_deces']
        ,$descendants['id_parent']
        ,$descendants['sosa_d']
        ,$descendants['id_conj']   
        ,$descendants['date_maria']
        ,$descendants['lieu_maria']
        ,$descendants['nom_conj']  
        ,$descendants['pre2_conj'] 
        ,$descendants['sexe_conj'] 
        ,$descendants['sosa_conj']
		);

	  } 
        if ($excel == NULL)
        {   $table_d     = '<table>';
            $tr_d        = '<tr>';
            $td_d        = '<td class=bords_verti>';
            $table_f     = '</table>';
            $tr_f        = '</tr>';
            $td_f        = '</td>';
        } else
        {    $table_d    = '';
            $tr_d        = '';
            $td_d        = '';
            $table_f    = '';
            $tr_f        = chr(10);
            $td_f        = chr(9);
        }
        
        $ligne = 
        $table_d
        .$tr_d
        .$td_d.'generation'.$td_f
        .$td_d.'indice'.$td_f
        .$td_d.'id_indi'.$td_f;
		if ($perf == "ME_G")
		{$ligne .=
         $td_d.'nom'.$td_f
        .$td_d.'sosa_d'.$td_f
        .$td_d.'prenom2'.$td_f
        .$td_d.'sexe'.$td_f
        .$td_d.'profession'.$td_f
        .$td_d.'date_naiss'.$td_f
        .$td_d.'lieu_naiss'.$td_f
        .$td_d.'date_deces'.$td_f
        .$td_d.'lieu_deces'.$td_f
        .$td_d.'date_maria'.$td_f
        .$td_d.'lieu_maria'.$td_f
        .$td_d.'nom_conj'.$td_f
        .$td_d.'pre2_conj'.$td_f
        .$td_d.'sexe_conj'.$td_f
        .$td_d.'sosa_conj'.$td_f;
		}
        $ligne .= $tr_f;
		
        if ($excel == NULL) {    echo $ligne;}
        else {    echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');}

        for ($i = 0; $i < count($descendants['id_indi']); $i++)
        {    $ligne =
             $tr_d
            .$td_d.$descendants['generation'][$i].$td_f
            .$td_d.$descendants['indice'][$i].$td_f
            .$td_d.$descendants['id_indi'][$i].$td_f;
		  if ($perf == "ME_G")
          { $ligne .=
	         $td_d.$descendants['nom'][$i].$td_f
            .$td_d.$descendants['sosa_d'][$i].$td_f
            .$td_d.$descendants['prenom2'][$i].$td_f
            .$td_d.$descendants['sexe'][$i].$td_f
            .$td_d.$descendants['profession'][$i].$td_f
            .$td_d.fctDisplayDateExcel($descendants['date_naiss'][$i]).$td_f
            .$td_d.$descendants['lieu_naiss'][$i].$td_f
            .$td_d.fctDisplayDateExcel($descendants['date_deces'][$i]).$td_f
            .$td_d.$descendants['lieu_deces'][$i].$td_f
            .$td_d.fctDisplayDateExcel($descendants['date_maria'][$i]).$td_f
            .$td_d.$descendants['lieu_maria'][$i].$td_f
            .$td_d.$descendants['nom_conj'][$i].$td_f
            .$td_d.$descendants['pre2_conj'][$i].$td_f
            .$td_d.$descendants['sexe_conj'][$i].$td_f
            .$td_d.$descendants['sosa_conj'][$i].$td_f;
		  }
          $ligne .= $tr_f;
            if ($excel == NULL) {    echo $ligne;}
            else {    echo chr(255).chr(254).mb_convert_encoding($ligne, 'UTF-16LE', 'UTF-8');}
        }
//        echo $table_f;
    }
//    echo $i;

}