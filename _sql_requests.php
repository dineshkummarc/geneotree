<?php

function nbj2age($nbj)
{ $age[0] = floor($nbj/365.25);
  $age[1] = floor(($nbj - $age[0]*365.25) / 30.4375);
  $age[2] = round(($nbj - $age[0]*365.25 - $age[1]*30.4375), 0);
  return $age;
}

function recup_bases()
{ global $sql_pref;

  $query = '
    SELECT
     "" as id_indi
    ,"" as id_wife
    ,"" as sexe
    ,"" as sosa
    ,"" as sosa_wife
    ,base
    ,language
    ,substring(places,1,30) as places
    ,nb_indi
    ,nb_media
    ,consang
    ,maps
    ,substring(soft_name,1,30) as soft_name
    ,geneotree_vers
    ,datemaj
  FROM `'.$sql_pref.'__base` 
  ';

  return $query;
}

function recup_baseGrid()
{ global $sql_pref;

  $query = '
    SELECT
     "" as id_indi
    ,"" as id_wife
    ,"" as sexe
    ,"" as sosa
    ,"" as sosa_wife
    ,filename
    ,sizefile
    ,datefile
    ,"" as upload
    ,base
    ,language
    ,nb_indi
    ,nb_lastname
    ,nb_places
    ,nb_sources
    ,nb_media
    ,consang
    ,maps
    ,substring(soft_name,1,15) as soft_name
    ,substring(soft_editor,1,15) as soft_editor
    ,ansel
    ,charset
    ,geneotree_vers
    ,datemaj
    ,hide75
    ,password
    ,"" as editBase
    ,"" as deleteBase
    FROM '.$sql_pref.'__baseGrid
  ';

  return $query;
}

function recup_eclair($minYear = "", $maxYear = "", $FlagMinMax = FALSE, $IdLine = "")
{ global $sql_pref;
  global $ADDRC;
  global $collate;
  global $pool;
  global $debfin;
  global $got_lang;
  global $page;
/*
from listes.php (first and second grid), listes_pdf.php, stats.php (charts)
get number of individuals by nom, prenom1, lieu and departement.

*/
  if ($maxYear == "")    {$maxYear = "9999";}
  if (empty($IdLine))    {$IdLine = "%";}
  $IdLineSql  = addslashes(rtrim($IdLine));

  $query  = "CREATE TEMPORARY TABLE ligne (no CHAR(1)) "." engine = myisam default ".$collate."_general_ci".";";
  $query .= 'INSERT INTO ligne VALUES ("1");';
  $query .= 'INSERT INTO ligne VALUES ("2");';

  // list by name or fistname
  if ($_REQUEST["pag"] == "nom" OR $_REQUEST["pag"] == "prenom1")
  {
    if ($IdLine == "%")  // main list (First List)
    { 
	  $SELECT = '
	   "" as id_indi
	  ,"" as id_wife
	  ,"" as sexe
	  ,"" as sosa
	  ,"" as sosa_wife
      ,CONCAT('.$_REQUEST["pag"].'
	          , " ("
			  , IFNULL(MIN(substring(a.date_naiss,1,4)),"")
			  ,"-"
			  , IFNULL(MAX(substring(a.date_naiss,1,4)),"")
			  ,")"
			  ) AS Col1
      ,COUNT( DISTINCT a.id_indi) AS nb
	  ';
	  if ($page != "stat.php") 
	  { 
 	    $SELECT .= '
            ,GROUP_CONCAT(DISTINCT
            CASE WHEN d.id_wife IS NOT NULL AND z.no = "2" THEN CONCAT(IFNULL(d.lieu_evene,""), CASE WHEN d.lieu_evene != "" THEN " (" ELSE "" END, IFNULL(d.country_evene,""), CASE WHEN d.lieu_evene != "" THEN ")" ELSE "" END)
                 WHEN c.id_husb IS NOT NULL AND z.no = "1" THEN CONCAT(IFNULL(c.lieu_evene,""), CASE WHEN c.lieu_evene != "" THEN " (" ELSE "" END, IFNULL(c.country_evene,""), CASE WHEN c.lieu_evene != "" THEN ")" ELSE "" END)
  	        WHEN b.id_indi IS NOT NULL                THEN CONCAT(IFNULL(b.lieu_evene,""), CASE WHEN b.lieu_evene != "" THEN " (" ELSE "" END, IFNULL(b.country_evene,""), CASE WHEN b.lieu_evene != "" THEN ")" ELSE "" END)
            END SEPARATOR ", ") AS Detail';
      }
  	  $GROUPBY = 'GROUP BY '.$_REQUEST["pag"];

  	} ELSE       // detail by individuals (Second liste)
	{ 
      $SELECT = 'DISTINCT 
		 a.id_indi
		,"" as id_wife
		,a.sexe
		,a.sosa_dyn as sosa_d
		,"" as sosa_wife
		,CONCAT(a.nom , " ", a.prenom1) as nom
		,substring(a.date_naiss,1,4) as date_naiss
		,CASE WHEN a.dept_naiss != "" THEN CONCAT(IFNULL(a.lieu_naiss,""), " (", IFNULL(a.dept_naiss,""), ")") ELSE a.lieu_naiss END as lieu_naiss
		';

	  $GROUPBY = ' ';
	}

    $query .= '
      SELECT 
      '.$SELECT.' 
      FROM      `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu`  a
      LEFT JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` b ON (a.id_indi = b.id_indi)
      LEFT JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` c ON (a.id_indi = c.id_husb) 
      LEFT JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` d ON (a.id_indi = d.id_wife) 
      INNER JOIN ligne z ON (1=1)
      WHERE  
          a.sosa_dyn >= '.$_REQUEST["sosa"].' 
	  AND a.sexe  LIKE "'.$_REQUEST["sex"].'" 
      AND CONCAT(LOWER(IFNULL(b.lieu_evene,"")), LOWER(IFNULL(c.lieu_evene,"")), LOWER(IFNULL(d.lieu_evene,"")), LOWER('.$_REQUEST["pag"].')) LIKE "%'.strtolower($_REQUEST["rech"]).'%"
      AND ( 
          (substring(b.date_evene,1,4) BETWEEN "'.$minYear.'" AND "'.$maxYear.'")
       OR (substring(c.date_evene,1,4) BETWEEN "'.$minYear.'" AND "'.$maxYear.'")
       OR (substring(d.date_evene,1,4) BETWEEN "'.$minYear.'" AND "'.$maxYear.'")
          )
      AND '.$_REQUEST["pag"].' LIKE "'.$IdLineSql.'"
	   '.$GROUPBY;
      
	  if ($page == "stat.php") 
	  { $query .= " ORDER BY Nb DESC LIMIT 0,200";
	  }
	  ;

  }  // End of Nom/Prenom1
  else
  { 
    if ($IdLine == "%")  // Fist list
    { $SELECT = 
	  'DISTINCT 
	   "" as id_indi
	  ,"" as id_wife
	  ,"" as sexe
	  ,"" as sosa
	  ,"" as sosa_wife
      ,CONCAT ('.$_REQUEST["pag"].', " ", CASE WHEN country_evene IS NULL THEN "" ELSE CONCAT("(",country_evene,")") END ) AS Col1
      ,COUNT(DISTINCT 
	      CASE WHEN b.id_indi IS NOT NULL THEN b.id_indi
               WHEN c.id_indi IS NOT NULL AND z.no = "1" THEN c.id_indi 
      		   WHEN d.id_indi IS NOT NULL AND z.no = "2" THEN d.id_indi 
        END) AS nb
      ,GROUP_CONCAT(DISTINCT
        CASE WHEN b.id_indi IS NOT NULL THEN b.nom
             WHEN c.id_indi IS NOT NULL AND z.no = "1" THEN c.nom 
      		 WHEN d.id_indi IS NOT NULL AND z.no = "2" THEN d.nom 
        END SEPARATOR ", ") AS Detail
	  ';
	  $GROUPBY = ' GROUP BY '.$_REQUEST["pag"];
	} else   // Second list, by individuals
	{ $SELECT = ' DISTINCT
         CASE WHEN b.id_indi IS NOT NULL THEN b.id_indi
               WHEN c.id_indi IS NOT NULL AND z.no = "1" THEN c.id_indi 
               WHEN d.id_indi IS NOT NULL AND z.no = "2" THEN d.id_indi 
         END as id_indi
        ,"" as id_wife
        ,CASE WHEN b.id_indi IS NOT NULL THEN b.sexe
               WHEN c.id_indi IS NOT NULL AND z.no = "1" THEN c.sexe 
               WHEN d.id_indi IS NOT NULL AND z.no = "2" THEN d.sexe
         END as sexe
        ,CASE WHEN b.id_indi IS NOT NULL THEN b.sosa_dyn
               WHEN c.id_indi IS NOT NULL AND z.no = "1" THEN c.sosa_dyn 
               WHEN d.id_indi IS NOT NULL AND z.no = "2" THEN d.sosa_dyn 
         END as sosa_d
        ,"" as sosa_wife
        ,CASE WHEN b.id_indi IS NOT NULL THEN CONCAT(b.nom, " ", b.prenom1)
               WHEN c.id_indi IS NOT NULL AND z.no = "1" THEN CONCAT(c.nom, " ", c.prenom1)
               WHEN d.id_indi IS NOT NULL AND z.no = "2" THEN CONCAT(d.nom, " ", d.prenom1)
         END AS nom
        ,CASE WHEN b.id_indi IS NOT NULL THEN b.date_naiss
               WHEN c.id_indi IS NOT NULL AND z.no = "1" THEN c.date_naiss
               WHEN d.id_indi IS NOT NULL AND z.no = "2" THEN d.date_naiss
         END as date_naiss
        ,CASE WHEN b.id_indi IS NOT NULL THEN CASE WHEN b.dept_naiss != "" THEN CONCAT(IFNULL(b.lieu_naiss,""), " (", IFNULL(b.dept_naiss,""), ")") ELSE b.lieu_naiss END
               WHEN c.id_indi IS NOT NULL AND z.no = "1" THEN CASE WHEN c.dept_naiss != "" THEN CONCAT(IFNULL(c.lieu_naiss,""), " (", IFNULL(c.dept_naiss,""), ")") ELSE c.lieu_naiss END
               WHEN d.id_indi IS NOT NULL AND z.no = "2" THEN CASE WHEN d.dept_naiss != "" THEN CONCAT(IFNULL(d.lieu_naiss,""), " (", IFNULL(d.dept_naiss,""), ")") ELSE d.lieu_naiss END
         END as lieu_naiss
        ';

      $GROUPBY = '';
    }

    $query .= 'SELECT '.
    $SELECT.'
    FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` a
    LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON (a.id_indi = b.id_indi AND b.sosa_dyn >= '.$_REQUEST["sosa"].' AND b.sexe LIKE "'.$_REQUEST["sex"].'")
    LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` c ON (a.id_husb = c.id_indi AND c.sosa_dyn >= '.$_REQUEST["sosa"].' AND c.sexe LIKE "'.$_REQUEST["sex"].'")
    LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` d ON (a.id_wife = d.id_indi AND d.sosa_dyn >= '.$_REQUEST["sosa"].' AND d.sexe LIKE "'.$_REQUEST["sex"].'")
    INNER JOIN ligne z ON (1=1)
    WHERE a.lieu_evene != "" 
    AND NOT (b.sexe IS NULL AND c.sexe IS NULL AND d.sexe IS NULL) 
    AND NOT (b.sosa_dyn IS NULL AND c.sosa_dyn IS NULL AND d.sosa_dyn IS NULL) 
    AND CONCAT(LOWER(IFNULL(lieu_evene,""))
	       , LOWER(IFNULL(dept_evene,""))
	       , LOWER(IFNULL(country_evene,""))
	       , LOWER(IFNULL(region_evene,""))
		   , LOWER(IFNULL(b.nom,""))
		   , LOWER(IFNULL(c.nom,""))
		   , LOWER(IFNULL(d.nom,""))
		   ) LIKE "%'.strtolower($_REQUEST["rech"]).'%"
    AND substring(a.date_evene,1,4) BETWEEN "'.$minYear.'" AND "'.$maxYear.'" 
    AND a.'.$_REQUEST["pag"].' LIKE "'.$IdLineSql.'"
    '.$GROUPBY.'
	';

	if ($page == "stat.php") 
	{ $query .= " ORDER BY Nb DESC LIMIT 0,200";
	}

  }

  if ($FlagMinMax)
  { $query = '
    SELECT
	MIN(substring(date_evene,1,4)) as minYear
	,MAX(substring(date_evene,1,4)) as `maxYear`
	FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` a
	WHERE date_evene > "0000"
	';
  }

// echo $query;return;
  if ($page != "listes_pdf.php" and $page != "stat.php")
  { return $query;
  } ELSE
  { mysqli_multi_query($pool,$query);
    mysqli_next_result($pool);
    mysqli_next_result($pool);
    mysqli_next_result($pool);
    return mysqli_store_result($pool);
  }
}

function get_events($minYear = "", $maxYear = "", $FlagMinMax = FALSE, $FlagResult = FALSE)
{ global $sql_pref;
  global $collate;
  global $pool;
  global $page;

  if ($_REQUEST["exp"] == "excel") {$_REQUEST["pag"] = "%";} 
  if ($_REQUEST["pag"] == "_")     {$_REQUEST["pag"] = "%";}
  if ($_REQUEST['sex'] == "ALL")   {$_REQUEST['sex'] = "_";}
  if ($maxYear == "")              {$maxYear = "9999";}

  $query = "";
  if ($FlagMinMax)
  { $query .= 
    "SELECT
     MIN(substring(date,1,4)) as minYear
    ,MAX(substring(date,1,4)) as `maxYear`
    FROM (
    ";
  } 
  $query .=
    'SELECT
     CASE WHEN c.id_indi IS NOT NULL THEN c.id_indi 
          WHEN b.id_indi IS NOT NULL THEN b.id_indi
          ELSE ""  
     END as id_indi
    ,CASE WHEN d.id_indi IS NOT NULL THEN d.id_indi
          ELSE ""  
     END as id_wife
    ,CASE WHEN c.id_indi IS NOT NULL THEN c.sexe
          WHEN b.id_indi IS NOT NULL THEN b.sexe
          ELSE ""  
     END as sexe
    ,CASE WHEN c.id_indi IS NOT NULL THEN c.sosa_dyn 
          WHEN b.id_indi IS NOT NULL THEN b.sosa_dyn
          ELSE ""  
     END as sosa_d
    ,CASE WHEN d.id_indi IS NOT NULL THEN d.sosa_dyn ELSE "" END as sosa_dyn_wife
    ,CASE WHEN c.id_indi IS NOT NULL THEN CONCAT(c.nom, " ", c.prenom1)
          WHEN b.id_indi IS NOT NULL THEN CONCAT(b.nom, " ", b.prenom1)
          ELSE ""  
     END as nom
    ,CASE WHEN d.id_indi IS NOT NULL THEN CONCAT(d.nom, " ", d.prenom1)
          ELSE ""  
     END as nom_wife
    ,a.type_evene
    ,IFNULL(a.date_evene,"") as date
    ,CASE WHEN a.dept_evene != "" THEN CONCAT(IFNULL(a.lieu_evene,""), " (", IFNULL(a.dept_evene,""), ")") ELSE IFNULL(a.lieu_evene,"") END AS lieu_naiss /*lieu_naiss to display Map */
    ,a.note_evene 
	';
	if ($_REQUEST["exp"] == "excel" OR $_REQUEST["exp"] == "pdf")
	{ $query .= '
      ,CASE WHEN c.id_indi IS NOT NULL THEN CONCAT(IFNULL(c.prenom2,""), " ", IFNULL(c.prenom3,""))
            WHEN b.id_indi IS NOT NULL THEN CONCAT(IFNULL(b.prenom2,""), " ", IFNULL(b.prenom3,""))
		END as prenom2
	  ,a.date_evene
	  ';
	}
  $query .= ' FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` a
  LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON (a.id_indi = b.id_indi AND b.sosa_dyn >= '.$_REQUEST["sosa"].' AND b.sexe LIKE "'.$_REQUEST["sex"].'")
  LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` c ON (a.id_husb = c.id_indi AND c.sosa_dyn >= '.$_REQUEST["sosa"].' AND c.sexe LIKE "'.$_REQUEST["sex"].'")
  LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` d ON (a.id_wife = d.id_indi AND d.sosa_dyn >= '.$_REQUEST["sosa"].' AND d.sexe LIKE "'.$_REQUEST["sex"].'")
  WHERE NOT (b.sexe IS NULL AND c.sexe IS NULL AND d.sexe IS NULL) 
  AND NOT (b.sosa_dyn IS NULL AND c.sosa_dyn IS NULL AND d.sosa_dyn IS NULL) 
  AND CONCAT(LOWER(IFNULL(lieu_evene,""))
           ,LOWER(IFNULL(dept_evene,""))
           ,LOWER(IFNULL(date_evene,""))
  	     ,LOWER(IFNULL(b.nom,""))
  	     ,LOWER(IFNULL(c.nom,""))
  	     ,LOWER(IFNULL(d.nom,""))
  	     ,LOWER(IFNULL(a.note_evene,""))
  	   ) LIKE "%'.strtolower($_REQUEST["rech"]).'%"
  AND a.type_evene NOT IN ("NOTE","FILE","OCCU","CHAN","RFN","RIN")
  AND a.type_evene like "'.$_REQUEST["pag"].'"
  ';

  if ($minYear != '')
    $query .= ' AND substring(a.date_evene,1,4) BETWEEN "'.$minYear.'" AND "'.$maxYear.'"';

  $query .= ' AND NOT (c.id_indi IS NULL AND b.id_indi IS NULL AND d.id_indi IS NULL)
    UNION ALL
    /* add individual notes which are not events */
    SELECT
     id_indi
    ,"" AS id_wife
    ,sexe
    ,sosa_dyn AS sosa_d
    ,"" AS sosa_dyn_wife
    ,CASE WHEN id_indi IS NOT NULL THEN CONCAT(nom, " ", prenom1) ELSE "" END AS nom
    ,"" AS nom_wife
    ,"INDI" as type_evene
    ,substring(IFNULL(date_naiss,""),1,4)
    ,CASE WHEN dept_naiss != "" THEN CONCAT(IFNULL(lieu_naiss,""), " (", IFNULL(dept_naiss,""), ")") ELSE IFNULL(lieu_naiss,"") END AS lieu_naiss 
    ,IFNULL(note_indi,"")
    ';
    if ($_REQUEST["exp"] == "excel" OR $_REQUEST["exp"] == "pdf")
    { $query .= '
      ,CONCAT(IFNULL(prenom2,""), " ", IFNULL(prenom3,"")) as prenom2
	  ,date_naiss
      ';
    }
    $query .= '	FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu`
    WHERE note_indi != ""
    AND "'.$_REQUEST["pag"].'" = "INDI"
    AND sosa_dyn >= '.$_REQUEST["sosa"].'
    AND sexe LIKE "'.$_REQUEST["sex"].'"
    AND CONCAT(LOWER(IFNULL(lieu_naiss,""))
             ,LOWER(IFNULL(dept_naiss,""))
             ,LOWER(IFNULL(date_naiss,""))
    	     ,LOWER(IFNULL(nom,""))
    	     ,LOWER(IFNULL(note_indi,""))
    	   ) LIKE "%'.strtolower($_REQUEST["rech"]).'%"
  ';

  if ($minYear != '') $query .= 
  ' AND substring(date_naiss,1,4) BETWEEN "'.$minYear.'" AND "'.$maxYear.'"';

  if ($FlagMinMax)
  { $query .=
    "  ) AS Req
    WHERE substring(date,1,4) > '0000'";
  }

// echo $query;return;
  if ($_REQUEST["exp"] == "excel" OR $FlagResult)
  { return sql_exec($query,0);
  } else 
  { return $query;
  }
}

/************************** Satistics function *****************************/

function recup_deces ($minYear = "", $maxYear = "", $FlagMinMax = FALSE, $SortOrder = "ASC")
{   global $sql_pref;
	global $page;

  if ($maxYear == "")              {$maxYear = "9999";}

    $query = 
	" SELECT ";
	if ($FlagMinMax)
	{ $query .= 
      "MIN(substring(date_naiss,1,4)) as minYear
	  ,MAX(substring(date_naiss,1,4)) as `maxYear`
	  ";
	} else
	{ $query .=
      "id_indi
       ,'' as id_wife
      ,sexe
      ,sosa_dyn
      ,'' as sosa_dyn_wife
      ,CONCAT (nom, ' ', prenom1) as nom
      ,substring(IFNULL(date_naiss,''),1,4) as date
      ,DATEDIFF(STR_TO_DATE(date_deces,'%Y %m %d'), STR_TO_DATE(date_naiss,'%Y %m %d'))  as calcul
      ";
    }
    $query .= " FROM `".$sql_pref."_".$_REQUEST['ibase']."_individu`
    WHERE sexe LIKE '".$_REQUEST['sex']."'
    and substring(date_naiss,1,4) BETWEEN '".$minYear."' AND '".$maxYear."'
    and sosa_dyn >= ".$_REQUEST["sosa"]."
    AND CONCAT(LOWER(IFNULL(nom,'')), LOWER(IFNULL(prenom1,'')), LOWER(IFNULL(substring(date_naiss,1,4),''))) LIKE '%".strtolower($_REQUEST['rech'])."%'
    AND date_naiss >= '0' AND date_deces >= '0'  /* exclusion ! */
    AND substring(date_naiss,7,2) > '00'   /* date complète */
    and substring(date_deces,7,2) > '00'   /* date complète */
    ";

// echo $query;
    if ($page == "stat_pdf.php")
	{ $query .= " ORDER BY 8 DESC LIMIT 0,15;";
      return sql_exec($query,0);
	} else
    { return $query;
	}
}

function recup_jumeaux($minYear = "", $maxYear = "", $FlagMinMax = FALSE, $SortOrder = "ASC")
{ global $sql_pref;
  global $page;

  if ($maxYear == "")              {$maxYear = "9999";}

  $query = "";
  if ($FlagMinMax)
  { $query .= 
    "SELECT
     MIN(substring(date,1,4)) as minYear
    ,MAX(substring(date,1,4)) as `maxYear`
    FROM (
    ";
  }
  $query .=
  "SELECT
  min(id_indi) as id_indi
  ,'' as id_wife
  ,max(sexe) as sexe
  ,min(sosa_dyn) as sosa
  ,'' as sosa_wife
  ,CONCAT( min(nom), ' ',min(prenom1),' & ',max(prenom1)) as nom
  ,date_naiss as date
  ,count(*) as nb
  FROM `".$sql_pref."_".$_REQUEST['ibase']."_individu`
  WHERE sexe LIKE '".$_REQUEST['sex']."'
  and substring(date_naiss,1,4) BETWEEN '".$minYear."' AND '".$maxYear."'
  and sosa_dyn >= ".$_REQUEST["sosa"]."
  AND CONCAT(LOWER(IFNULL(nom,'')), LOWER(IFNULL(prenom1,'')), LOWER(IFNULL(substring(date_naiss,1,4),''))) LIKE '%".strtolower($_REQUEST['rech'])."%'
  AND date_naiss >= '0'   /* exclusion ! */
  AND substring(date_naiss,7,2) > '00'   /* date complète */
  GROUP BY date_naiss
  HAVING count(*) > 1 and (min(id_pere)= max(id_pere) or min(id_mere) = max(id_mere))
  ";
  
  if ($FlagMinMax)
  { $query .=
    "  ) AS Req";
  }

// echo $query;return;
    if ($page == "stat_pdf.php")
	{ $query .= " ORDER BY 8 DESC LIMIT 0,15;";
      return sql_exec($query);
	} else
    { return $query;
	}
}

function recup_maries ($minYear = "", $maxYear = "", $FlagMinMax = FALSE, $SortOrder = "ASC")
{   global $sql_pref;
	global $page;

  if ($maxYear == "")              {$maxYear = "9999";}

  $query = "";
  if ($FlagMinMax)
  { $query .= 
    "SELECT
     MIN(substring(date,1,4)) as minYear
    ,MAX(substring(date,1,4)) as `maxYear`
    FROM (
    ";
  }

    $query_part1 = "
        SELECT 
         b.id_indi
        ,'' as id_wife
        ,b.sexe
        ,b.sosa_dyn
        ,'' as sosa_dyn_wife
        ,CONCAT(b.nom, ' ', b.prenom1) as nom
        ,b.date_naiss as date
        ,DATEDIFF(STR_TO_DATE(date_evene,'%Y %m %d'), STR_TO_DATE(b.date_naiss,'%Y %m %d')) as calcul
        FROM (`".$sql_pref."_".$_REQUEST['ibase']."_evenement` a
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_individu` b ON a.id_";

    $query_part2 = " = b.id_indi)
        WHERE a.type_evene = 'MARR'
        and substring(b.date_naiss,1,4) BETWEEN '".$minYear."' AND '".$maxYear."'
        and b.sosa_dyn >= ".$_REQUEST["sosa"]."
        AND CONCAT(LOWER(IFNULL(b.nom,'')), LOWER(IFNULL(b.prenom1,'')), LOWER(IFNULL(substring(b.date_naiss,1,4),''))) LIKE '%".strtolower($_REQUEST['rech'])."%'
        AND b.date_naiss >= '0'   /* exclusion ! */
        AND substring(b.date_naiss,7,2) > '00'   /* date complète */
        AND a.date_evene >= '0'   /* exclusion ! */
        AND substring(a.date_evene,7,2) > '00'   /* date complète */
    ";
// echo $_REQUEST['sex'].'<br>'.$query_part1.'<br>'.$query_part2;
    if ($_REQUEST['sex'] == "_")
    {    $query .= $query_part1."husb".$query_part2." UNION ALL ".$query_part1."wife".$query_part2;    }
    if ($_REQUEST['sex'] == "M")
    {    $query .= $query_part1."husb".$query_part2;}
    if ($_REQUEST['sex'] == "F")
    {    $query .= $query_part1."wife".$query_part2;    }

  if ($FlagMinMax)
  { $query .=
    "  ) AS Req";
  }

// echo $query;return;
    if ($page == "stat_pdf.php")
	{ $query .= " ORDER BY 8 ".$SortOrder." LIMIT 0,15;";
      return sql_exec($query);
	} else
    { return $query;
	}
}

function recup_noces ($minYear = "", $maxYear = "", $FlagMinMax = FALSE, $SortOrder = "ASC")
{   global $sql_pref;
	global $page;

  if ($maxYear == "")              {$maxYear = "9999";}

  $query = "";
  if ($FlagMinMax)
  { $query .= 
    "SELECT
     MIN(date) as minYear
    ,MAX(date) as `maxYear`
    FROM (
    ";
  }
    $query .= "SELECT ";
    if ($_REQUEST['sex'] !== "F")
    {    $query .= "
         b.id_indi
		,'' as id_wife
		,'M' as sexe
        ,max(b.sosa_dyn)  as sosa_dyn
		,'' as sosa_dyn_wife
        ,max(CONCAT(b.nom, ' ', b.prenom1) ) as nom
        ,max(substring(b.date_naiss,1,4)) as date";
    } else
    {    $query .= "
         c.id_indi
		,'' as id_wife
		,'F' as sexe
        ,max(c.sosa_dyn) as sosa_dyn
		,'' as sosa_dyn_wife
        ,max(CONCAT(c.nom, ' ', c.prenom1) ) as nom
        ,max(substring(c.date_naiss,1,4)) as date";
    }
// gérer le MIN ou MAX en fonction du tri
    $query .= ",max(CASE WHEN STR_TO_DATE(b.date_deces,'%Y %m %d') < STR_TO_DATE(c.date_deces,'%Y %m %d')
        THEN DATEDIFF(STR_TO_DATE(b.date_deces,'%Y %m %d'), STR_TO_DATE(a.date_evene,'%Y %m %d')) 
        ELSE DATEDIFF(STR_TO_DATE(c.date_deces,'%Y %m %d'), STR_TO_DATE(a.date_evene,'%Y %m %d')) 
        END) as calcul";
        $query = $query." FROM ((`".$sql_pref."_".$_REQUEST['ibase']."_evenement` a 
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_individu` b ON a.id_husb = b.id_indi)
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_individu` c ON a.id_wife = c.id_indi)
        WHERE a.type_evene LIKE 'MAR%'
        AND a.date_evene >= '0'   /* exclusion ! */
        AND substring(a.date_evene,7,2) > '00'   /* date complète */
        AND b.date_deces >= '0'   /* exclusion ! */
        AND substring(b.date_deces,7,2) > '00'   /* date complète */
        AND c.date_deces >= '0'   /* exclusion ! */
        AND substring(c.date_deces,7,2) > '00'   /* date complète */
    ";
    if ($_REQUEST['sex'] !== "F")
    {    $query = $query." GROUP BY b.id_indi
         HAVING max(substring(b.date_naiss,1,4)) BETWEEN '".$minYear."' AND '".$maxYear."'
	        AND MAX(b.sosa_dyn) >= ".$_REQUEST["sosa"]."
            AND MAX(CONCAT(LOWER(IFNULL(b.nom,'')), LOWER(IFNULL(b.prenom1,'')), LOWER(IFNULL(substring(b.date_naiss,1,4),''))) ) LIKE '%".strtolower($_REQUEST['rech'])."%'";

    } else 
    {    $query = $query." GROUP BY c.id_indi
         HAVING max(substring(c.date_naiss,1,4)) BETWEEN '".$minYear."' AND '".$maxYear."'
            AND MAX(c.sosa_dyn) >= ".$_REQUEST["sosa"]."
            AND MAX(CONCAT(LOWER(IFNULL(c.nom,'')), LOWER(IFNULL(c.prenom1,'')), LOWER(IFNULL(substring(c.date_naiss,1,4),''))) ) LIKE '%".strtolower($_REQUEST['rech'])."%'";
    }

    if ($_REQUEST['sex'] == "_")
    {
        $query = $query. " UNION ALL SELECT ";
        $query .= "
         c.id_indi
        ,'' as id_wife
        ,'F' as sexe
        ,max(c.sosa_dyn) as sosa
        ,'' as sosa_wife
        ,max(CONCAT(c.nom, ' ', c.prenom1) ) as nom
        ,max(substring(c.date_naiss,1,4)) as date";
// gérer le MIN ou MAX en fonction du tri
        $query .= ",(CASE WHEN STR_TO_DATE(b.date_deces,'%Y %m %d') < STR_TO_DATE(c.date_deces,'%Y %m %d')
            THEN DATEDIFF(STR_TO_DATE(b.date_deces,'%Y %m %d'), STR_TO_DATE(a.date_evene,'%Y %m %d')) 
            ELSE DATEDIFF(STR_TO_DATE(c.date_deces,'%Y %m %d'), STR_TO_DATE(a.date_evene,'%Y %m %d')) 
            END) as calcul";
        $query = $query." FROM ((`".$sql_pref."_".$_REQUEST['ibase']."_evenement` a 
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_individu` b ON a.id_husb = b.id_indi)
        LEFT OUTER JOIN `".$sql_pref."_".$_REQUEST['ibase']."_individu` c ON a.id_wife = c.id_indi)
        WHERE a.type_evene LIKE 'MAR%'
        AND a.date_evene >= '0'   /* exclusion ! */
        AND substring(a.date_evene,7,2) > '00'   /* date complète */
        AND b.date_deces >= '0'   /* exclusion ! */
        AND substring(b.date_deces,7,2) > '00'   /* date complète */
        AND c.date_deces >= '0'   /* exclusion ! */
        AND substring(c.date_deces,7,2) > '00'   /* date complète */
        ";
        $query = $query." GROUP BY c.id_indi
        HAVING MAX(substring(c.date_naiss,1,4)) BETWEEN '".$minYear."' AND '".$maxYear."' 
           AND MAX(c.sosa_dyn) >= ".$_REQUEST["sosa"];
    }

  if ($FlagMinMax)
  { $query .= 
    ") as req";
  }

// echo $query;return;
    if ($page == "stat_pdf.php")
	{ $query .= " ORDER BY 8 ".$SortOrder." LIMIT 0,15;";
      return sql_exec($query);
	} else
    { return $query;
	}
}

function recup_parents($minYear = "", $maxYear = "", $FlagMinMax = FALSE, $SortOrder = "ASC", $type = "age")
{ /* 
  $type ne sert que pour stat.pdf.
     $type = age (par defaut), on restitue l'age du parent à la naissance des enfants (asc ou desc)
     $type = nb, on restitue le nb d'enfants du parent (desc implicite)
     $type = ecart, on restitue l'écart entre l'aine et le benjamin (desc implicite)
  requete en 2 fois UNION ALL pour eviter le jointure pere OR mere tres mauvaises performances
	*/
  global $sql_pref, $collate, $pool, $page;

  if ($maxYear == "")              {$maxYear = "9999";}
  if ($_REQUEST["sex"] == "")      {$_REQUEST["sex"] = "_";}
  $query = "";

  if ($FlagMinMax)
  { $query .= 
    "SELECT
     MIN(substring(date,1,4)) as minYear
    ,MAX(substring(date,1,4)) as `maxYear`
    FROM (
    ";
  }

  $query .= "
    SELECT 
     a.id_indi as id_indi
    ,'' as id_wife
    ,max(a.sexe) as sexe
    ,max(a.sosa_dyn) as sosa_dyn
    ,'' as sosa_dyn_wife
    ,max(CONCAT(a.nom, ' ', a.prenom1)) as nom
    ,max(a.date_naiss) as date
    ,";
  if ($type == 'age')
  {    $query = $query."MAX(DATEDIFF(STR_TO_DATE(c.date_naiss,'%Y %m %d'), STR_TO_DATE(a.date_naiss,'%Y %m %d'))) as calcul";
  } elseif ($type == 'nb')
  {    $query = $query."count(*) as calcul";    
  } else
  {    $query = $query."max(DATEDIFF(curdate(), STR_TO_DATE(c.date_naiss,'%Y %m %d'))) - min(DATEDIFF(curdate(), STR_TO_DATE(c.date_naiss,'%Y %m %d')))
               as calcul";
  }        
  $query = $query."
    FROM `".$sql_pref."_".$_REQUEST['ibase']."_individu` a, ".$sql_pref."_".$_REQUEST['ibase']."_individu c 
    WHERE (a.id_indi = c.id_pere) /* jointure id_mere dans la 2eme requete */
    AND a.sexe LIKE '".$_REQUEST["sex"]."'
    AND a.sosa_dyn >= ".$_REQUEST["sosa"]."
    AND CONCAT(LOWER(IFNULL(a.nom,'')), LOWER(IFNULL(a.prenom1,'')), LOWER(IFNULL(substring(a.date_naiss,1,4),''))) LIKE '%".strtolower($_REQUEST['rech'])."%'
    GROUP BY a.id_indi
    HAVING calcul IS NOT NULL AND max(substring(a.date_naiss,1,4)) BETWEEN '".$minYear."' AND '".$maxYear."'";

  $query = $query." 
    UNION ALL
    SELECT 
     a.id_indi as id_indi
    ,'' as id_wife
    ,max(a.sexe) as sexe
    ,max(a.sosa_dyn) as sosa_dyn
    ,'' as sosa_dyn_wife
    ,max(CONCAT(a.nom, ' ', a.prenom1)) as nom
    ,max(a.date_naiss) as date
    ,";
  if ($type == 'age')        
  {    $query = $query."MAX(DATEDIFF(STR_TO_DATE(c.date_naiss,'%Y %m %d'), STR_TO_DATE(a.date_naiss,'%Y %m %d'))) as calcul";
  } elseif ($type == 'nb')
  {    $query = $query."count(*) as calcul";    
  } else
  {    $query = $query."max(DATEDIFF(curdate(), STR_TO_DATE(c.date_naiss,'%Y %m %d'))) - min(DATEDIFF(curdate(), STR_TO_DATE(c.date_naiss,'%Y %m %d')))
               as calcul";
  }        
  $query = $query."
    FROM `".$sql_pref."_".$_REQUEST['ibase']."_individu` a, ".$sql_pref."_".$_REQUEST['ibase']."_individu c 
    WHERE (a.id_indi = c.id_mere) 
    AND a.sexe LIKE '".$_REQUEST["sex"]."'
    AND a.sosa_dyn >= ".$_REQUEST["sosa"]."
    AND CONCAT(LOWER(IFNULL(a.nom,'')), LOWER(IFNULL(a.prenom1,'')), LOWER(IFNULL(substring(a.date_naiss,1,4),''))) LIKE '%".strtolower($_REQUEST['rech'])."%'
    GROUP BY a.id_indi
    HAVING calcul IS NOT NULL AND max(substring(a.date_naiss,1,4)) BETWEEN '".$minYear."' AND '".$maxYear."'";

  if ($FlagMinMax)
  { $query .= 
    ") as Req
    WHERE substring(date,1,4) > '0000'";
  }

  if ($page == "stat_pdf.php")
  { $query .= " ORDER BY 8 ".$SortOrder." LIMIT 0,15;";
    $result = sql_exec ($query,0);
    return $result;
  } else
  {
// echo $query;return;
    return $query;
  }
}

/************************************* Media functions *******************************/

function recup_media($Result = FALSE)
{   global $pool;
    global $sql_pref;
	global $page;

    if ($_REQUEST['pag'] == "_") {$_REQUEST['pag'] = "%";}
    if ($_REQUEST["pag"] != "%" OR $_REQUEST["pag"] != "INDI") {$query = '';}
    
    if ($_REQUEST["pag"] == "%" OR $_REQUEST["pag"] == "INDI")
    {    $query = '
        SELECT 
         a.id_indi
		,"" as id_wife
		,b.sexe
		,b.sosa_dyn
		,"" as sosa_dyn_wife
		,CONCAT (b.nom, " ", b.prenom1) as nom
		,"" as nom_wife
		,"INDI" as type_evene
		,a.note_evene as url
		,filedate
		,filelarg
		,filehaut
		,a.titl
        FROM (`'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` a 
        INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON a.id_indi = b.id_indi)
        WHERE a.type_evene = "FILE"
        AND b.sexe LIKE "'.$_REQUEST["sex"].'" 
        AND b.sosa_dyn >= '.$_REQUEST["sosa"].'
        AND CONCAT(LOWER(a.note_evene),"-", LOWER(IFNULL(b.nom,"")), LOWER(IFNULL(b.prenom1,""))) LIKE "%'.strtolower($_REQUEST["rech"]).'%"
        ';
    }
    if ($_REQUEST["pag"] == "%")
    {    $query .= ' UNION ALL';
    }
    if ($_REQUEST["pag"] == "%" OR $_REQUEST["pag"] != "INDI")
    {   $query .= ' SELECT 
         a.id_indi
		,"" as id_wife
		,b.sexe
		,b.sosa_dyn
		,"" as sosa_dyn_wife
		,CONCAT(b.nom, " ", b.prenom1) as nom
		,"" as nom_wife
		,a.type_evene
        ,a.attr_sourc as url
		,filedate
		,filelarg
		,filehaut
		,a.titl
        FROM (`'.$sql_pref.'_'.$_REQUEST["ibase"].'_even_sour` a 
        INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON a.id_indi = b.id_indi)
        WHERE a.type_sourc = "FILE" 
        AND a.type_evene LIKE "'.$_REQUEST["pag"].'"
        AND b.sexe LIKE "'.$_REQUEST["sex"].'" 
        AND b.sosa_dyn >= '.$_REQUEST["sosa"].'
        AND CONCAT(LOWER(a.attr_sourc),"-", LOWER(IFNULL(b.nom,"")), LOWER(IFNULL(b.prenom1,""))) LIKE "%'.strtolower($_REQUEST["rech"]).'%"

        UNION ALL
        SELECT 
		 a.id_husb
		,a.id_wife
		,b.sexe
		,b.sosa_dyn
		,c.sosa_dyn
		,CONCAT(b.nom, " ",b.prenom1)
		,CONCAT(c.nom, " ",c.prenom1)
		,a.type_evene
		,a.attr_sourc as url
		,filedate
		,filelarg
		,filehaut
		,a.titl
        FROM ((`'.$sql_pref.'_'.$_REQUEST["ibase"].'_even_sour` a 
        INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON a.id_husb = b.id_indi)
        INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` c ON a.id_wife = c.id_indi)
        WHERE a.type_sourc = "FILE" 
        AND a.type_evene LIKE "'.$_REQUEST["pag"].'"
        AND (b.sexe LIKE "'.$_REQUEST["sex"].'" OR c.sexe LIKE "'.$_REQUEST["sex"].'")
        AND (b.sosa_dyn >= '.$_REQUEST["sosa"].' OR c.sosa_dyn >= '.$_REQUEST["sosa"].')
        AND CONCAT(LOWER(a.attr_sourc),"-", LOWER(IFNULL(b.nom,"")), LOWER(IFNULL(c.nom,"")), LOWER(IFNULL(b.prenom1,"")), LOWER(IFNULL(b.prenom1,""))) LIKE "%'.strtolower($_REQUEST["rech"]).'%"
        ';
    }
// echo $query;
    if ($page == "stat_pdf.php")
	{ return sql_exec($query);
	} else
    { return $query;
	}
}
/******************** CARD : First part : identity card functions *************************/

function recup_individu($fid) 
{ global $sql_pref;

  $query = '
  SELECT 
   a.id_indi 
  ,MAX(CONCAT(nom, " ", prenom1)) as nom
  ,MAX(IFNULL(prenom2,""))      as prenom2
  ,MAX(IFNULL(prenom3,""))      as prenom3
  ,MAX(sexe)                    as sexe
  ,MAX(IFNULL(profession,""))   as profession
  ,MAX(IFNULL(date_naiss,""))   as date_naiss
  ,MAX(CASE WHEN dept_naiss != "" THEN CONCAT(IFNULL(lieu_naiss,""), " (", IFNULL(dept_naiss,""), ")") ELSE IFNULL(lieu_naiss,"") END) as lieu_naiss
  ,MAX(IFNULL(date_deces,""))   as date_deces
  ,MAX(CASE WHEN dept_deces != "" THEN CONCAT(IFNULL(lieu_deces,""), " (", IFNULL(dept_deces,""), ")") ELSE IFNULL(lieu_deces,"") END) as lieu_deces
  ,MAX(IFNULL(note_indi,""))    as note_indi
  ,MAX(IFNULL(date_naiss,""))   as date_naiss
  ,MAX(sosa_dyn)                as sosa
  ,MAX(IFNULL(name2,""))        as name2
  ,GROUP_CONCAT(IFNULL(b.note_evene,""),"|",IFNULL(b.titl,"") SEPARATOR "/") as url /* format "url|titl" */
  FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` a
  LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement` b ON (a.id_indi = b.id_indi AND b.type_evene = "FILE")
  WHERE a.id_indi = '.$fid.'
  GROUP BY a.id_indi
  ';
// echo $query;return;
  return $query;
}

function recup_parent($id_indi)
{   global $sql_pref;
	global $page;

    $query = '
    SELECT 
	 b.id_indi
	,0 as id_wife
	,b.sexe
	,b.sosa_dyn
	,0 as sosa_dyn_wife
	,CONCAT (b.nom, " ", b.prenom1) as nom
	,IFNULL(b.date_naiss,"") as date_naiss
    ,CASE WHEN b.dept_naiss != "" THEN CONCAT(IFNULL(b.lieu_naiss,""), " (", IFNULL(b.dept_naiss,""), ")") ELSE IFNULL(b.lieu_naiss,"") END as lieu_naiss
	,IFNULL(a.date_naiss,"") as age1
	,IFNULL(b.date_naiss,"") as age2
	';
    if ($page == "fiche_pdf.php")
    { $query .= '
      ,b.profession
      ,"" as date_evene
      ,CONCAT(IFNULL(b.prenom2,""), " ", IFNULL(b.prenom3,"")) as prenom2
      ,IFNULL(b.date_deces,"") as date_deces
      ,CASE WHEN b.dept_deces != "" THEN CONCAT(IFNULL(b.lieu_deces,""), " (", IFNULL(b.dept_deces,""), ")") ELSE IFNULL(b.lieu_deces,"") END as lieu_deces
	  ';
	}
	$query .= '
    FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` a
    LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` b ON (a.id_pere = b.id_indi)
    WHERE a.id_indi = '.$id_indi.' AND b.id_indi IS NOT NULL
    UNION ALL
    SELECT 
	 b.id_indi
	,0 as id_wife
	,b.sexe
	,b.sosa_dyn
	,0 as sosa_dyn_wife
	,CONCAT (b.nom, " ", b.prenom1) as nom
	,IFNULL(b.date_naiss,"") as date_naiss
    ,CASE WHEN b.dept_naiss != "" THEN CONCAT(IFNULL(b.lieu_naiss,""), " (", IFNULL(b.dept_naiss,""), ")") ELSE IFNULL(b.lieu_naiss,"") END as lieu_naiss
	,IFNULL(a.date_naiss,"") as age1
	,IFNULL(b.date_naiss,"") as age2
	';
    if ($page == "fiche_pdf.php")
    { $query .= '
      ,b.profession
      ,"" as date_evene
      ,CONCAT(b.prenom2, " ", b.prenom3) as prenom2
      ,IFNULL(b.date_deces,"")
      ,CASE WHEN b.dept_deces != "" THEN CONCAT(IFNULL(b.lieu_deces,""), " (", IFNULL(b.dept_deces,""), ")") ELSE IFNULL(b.lieu_deces,"") END as lieu_deces
	  ';
	}
	$query .= '
    FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` a
    LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` b ON (a.id_mere = b.id_indi)
    WHERE a.id_indi = '.$id_indi.' AND b.id_indi IS NOT NULL'
    ;
// echo $query;
    if ($page == "fiche_pdf.php")
	{ return sql_exec($query,0);
	} else
    { return $query;
	}
}

function recup_conjoints($id_indi, $sex)
{   global $sql_pref;
	global $page;

    $query = '
    SELECT 
     c.id_indi
	,"" as id_wife
    ,c.sexe
    ,c.sosa_dyn as sosa
    ,"" as sosa_wife
    ,CONCAT(c.nom, " ", c.prenom1) as nom
    ,IFNULL(c.date_naiss,"") as date_naiss
    ,CASE WHEN c.dept_naiss != "" THEN CONCAT(IFNULL(c.lieu_naiss,""), " (", IFNULL(c.dept_naiss,""), ")") ELSE IFNULL(c.lieu_naiss,"") END as lieu_naiss
	,IFNULL(b.date_naiss,"") as age1
	,IFNULL(c.date_naiss,"") as age2
	';
    if ($page == "fiche_pdf.php")
    { $query .= '
      ,c.profession
      ,a.date_evene
      ,CONCAT(c.prenom2, " ", c.prenom3) as prenom2
      ,IFNULL(c.date_deces,"") as date_deces
      ,CASE WHEN c.dept_deces != "" THEN CONCAT(IFNULL(c.lieu_deces,""), " (", IFNULL(c.dept_deces,""), ")") ELSE IFNULL(c.lieu_deces,"") END as lieu_deces
	  ';
	}
    $query .= ' 
	FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement` a
    INNER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` b ON (';
    if ($sex == 'M') {$query .= 'a.id_husb ';} else {$query .= 'a.id_wife ';}
    $query .= ' = b.id_indi)
    LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` c ON (';
    if ($sex == 'M') {$query .= 'a.id_wife ';} else {$query .= 'a.id_husb ';}
    $query .= ' = c.id_indi) 
    WHERE a.type_evene IN ("MARR")
    AND ';
    if ($sex == 'M') {$query .= 'a.id_husb ';} else {$query .= 'a.id_wife ';}
    $query .= ' = '.$id_indi
    ;
// echo $query;
    if ($page == "fiche_pdf.php")
	{ return sql_exec($query,0);
	} else
    { return $query;
	}
}

function recup_enfants($id_indi,$sex)
{   global $sql_pref;
	global $page;

    $query = '
    SELECT 
	 b.id_indi
	,"" as id_wife
	,b.sexe
	,b.sosa_dyn as sosa
	,"" as sosa_wife
	,CONCAT(b.nom, " ", b.prenom1) as nom
	,IFNULL(b.date_naiss,"") as date_naiss
    ,CASE WHEN b.dept_naiss != "" THEN CONCAT(IFNULL(b.lieu_naiss,""), " (", IFNULL(b.dept_naiss,""), ")") ELSE IFNULL(b.lieu_naiss,"") END as lieu_naiss
	,IFNULL(a.date_naiss,"") as age1
	,IFNULL(b.date_naiss,"") as age2
	';

    if ($page == "fiche_pdf.php")
	{	$query .= '
		,IFNULL(b.profession,"") as profession
        ,CONCAT(IFNULL(b.prenom2,""), " ", IFNULL(b.prenom3,"")) as prenom2
		,IFNULL(b.date_deces,"") as date_deces
        ,CASE WHEN b.dept_deces != "" THEN CONCAT(IFNULL(b.lieu_deces,""), " (", IFNULL(b.dept_deces,""), ")") ELSE IFNULL(b.lieu_deces,"") END as lieu_deces';
	}
    $query .= '
	FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` a
    INNER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` b ON (a.id_indi = ';
    if ($sex == 'M') {$query = $query.' b.id_pere) ';} else {$query = $query.' b.id_mere) ';}
    $query .= 'WHERE a.id_indi = '.$id_indi
	;

    if ($page == "fiche_pdf.php")
	{ return sql_exec($query,0);
	} else
    { return $query;
	}
}

function recup_fratrie($id_indi)
{   global $sql_pref;
	global $page;

    $query = '
	SELECT
	 b.id_indi
	,"" as id_wife
	,b.sexe
	,b.sosa_dyn as sosa
	,"" as sosa_wife
	,CONCAT(b.nom, " ", b.prenom1) as nom
	,IFNULL(b.date_naiss,"") as date_naiss
    ,CASE WHEN b.dept_naiss != "" THEN CONCAT(IFNULL(b.lieu_naiss,""), " (", IFNULL(b.dept_naiss,""), ")") ELSE IFNULL(b.lieu_naiss,"") END as lieu_naiss
	,IFNULL(a.date_naiss,"") as age1
	,IFNULL(b.date_naiss,"") as age2
	';
    if ($page == "fiche_pdf.php")
	{	$query .= '
        ,b.profession
        ,CONCAT(b.prenom2, " ", b.prenom3) as prenom2
		,IFNULL(b.date_deces,"")
        ,CASE WHEN b.dept_deces != "" THEN CONCAT(IFNULL(b.lieu_deces,""), " (", IFNULL(b.dept_deces,""), ")") ELSE IFNULL(b.lieu_deces,"") END as lieu_deces
        ';
	}
    $query .= '
	FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` a
    INNER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` b ON ( a.id_pere = b.id_pere AND a.id_indi != b.id_indi AND a.id_pere != 0)
    WHERE a.id_indi = '.$id_indi.' 
    UNION
    SELECT 
	 b.id_indi
	,"" as id_wife
	,b.sexe
	,b.sosa_dyn as sosa
	,"" as sosa_wife
	,CONCAT(b.nom, " ", b.prenom1) as nom
	,IFNULL(b.date_naiss,"") as date_naiss
    ,CASE WHEN b.dept_naiss != "" THEN CONCAT(IFNULL(b.lieu_naiss,""), " (", IFNULL(b.dept_naiss,""), ")") ELSE IFNULL(b.lieu_naiss,"") END as lieu_naiss
	,IFNULL(a.date_naiss,"") as age1
	,IFNULL(b.date_naiss,"") as age2
	';
    if ($page == "fiche_pdf.php")
	{	$query .= '
		,b.profession
        ,CONCAT(b.prenom2, " ", b.prenom3) as prenom2
		,IFNULL(b.date_deces,"")
        ,CASE WHEN b.dept_deces != "" THEN CONCAT(IFNULL(b.lieu_deces,""), " (", IFNULL(b.dept_deces,""), ")") ELSE IFNULL(b.lieu_deces,"") END as lieu_deces
		';
	}
    $query .= '
	FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` a
    INNER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` b ON (a.id_mere = b.id_mere AND a.id_indi != b.id_indi AND a.id_mere != 0)
    WHERE a.id_indi = '.$id_indi
    ;

    if ($page == "fiche_pdf.php")
	{ return sql_exec($query,0);
	} else
    { return $query;
	}
}

/****************** CARD : Second part : events details functions ***************************/

function recup_evenements($id_indi)
{   global $sql_pref;
	global $page;
// Remarque : dans cette requête, on récupère les notes et fichier des sources
    $query = '
	SELECT 
     substring(a.date_evene,1,4)
    ,a.type_evene
    ,IFNULL(a.date_evene,"") as date_evene
    ,a.lieu_evene
    , CONCAT(IFNULL(a.note_evene,"")," ", IFNULL(c.note_source,"")) AS note_source
    ,MAX(b.id_sour) AS id_sourc
    ,GROUP_CONCAT(IFNULL(b.id_sour,""),"|",IFNULL(b.type_sourc,"") SEPARATOR "/") AS type_sourc
    ,GROUP_CONCAT(IFNULL(b.attr_sourc,""),"|",IFNULL(b.titl,""),"|",IFNULL(b.id_sour,"") SEPARATOR "/") as attr_sourc
    FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement` a
    LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour` b ON (a.id_indi = b.id_indi and a.id_husb = b.id_husb and a.id_wife = b.id_wife
            and a.type_evene = b.type_evene and a.date_evene = b.date_evene)
	LEFT OUTER JOIN `'.$sql_pref.'_'.$_REQUEST['ibase'].'_source` c ON (b.type_sourc = "SOUR" AND b.id_sour = c.id_sour)
    WHERE (a.id_indi = '.$id_indi.' OR a.id_husb = '.$id_indi.' OR a.id_wife = '.$id_indi.')
    AND a.type_evene NOT IN ("FILE","OCCU","CHAN")
	GROUP BY a.type_evene, a.date_evene, a.lieu_evene, note_source 
	';
// echo $query.'<br>';
    if ($page == "fiche_pdf.php")
	{ return sql_exec($query,0);
	} else
    { return $query;
	}
}

/********************* CARD : Third part : cousins *****************************/

function recup_popul ($id_indi,$nb_gen_asc,$nb_gen_desc)
{ global $ancetres, $descendants, $cpt_generations, $nb_generations_desc, $cpt_generations_desc;
  global $ADDRC, $sql_pref;

//  ancêtres du niveau demandé
    $ancetres = array(); 
    $descendants = '';$cpt_generations = 0;
    $ancetres['id_indi'][0] = $id_indi;
    recup_ascendance ($ancetres,0,$nb_gen_asc,'ME_G');

    $final['id_indi']   = array();
    $final['nom']       = array();
    $final['lieu_naiss']= array();
    $final['sosa_d']    = array();
    $final['sexe']      = array();
    $final['profession']= array();
    $final['datedeb']   = array();
    $final['datefin']   = array();
    $final['prenom2']   = array();
    $final['date_naiss']= array();
    $final['date_deces']= array();
    $final['lieu_deces']= array();

    $cpt_final = 0;
    for ($i = 0; $i < count($ancetres['id_indi']); $i++)
    {   if ($ancetres ['generation'][$i] == $nb_gen_asc)
        {   $descendants = array();
            $descendants ['id_indi'] [0] = $ancetres['id_indi'][$i];
            $cpt_generations_desc = 0;
            $nb_generations_desc = $nb_gen_desc;
// descendants de ces ancêtres (balèze!)
            recup_descendance (0,0,$nb_generations_desc,'ME_G','');
$query = 'DROP TABLE IF EXISTS '.$sql_pref.'_'.$ADDRC.'_desc_cles';
sql_exec($query);
            for ($j = 0; $j < count($descendants ['id_indi']); $j++)
            {   if ($descendants['generation'][$j] == $nb_generations_desc)    // sélection des descendants du niveau demandé
                {   
				    $flag_present = 0;
                    for ($k=0; $k < count($final['id_indi']); $k++)
                    {    if ($descendants ['id_indi'][$j] == $final['id_indi'][$k])    {$flag_present = 1;}
                    }
                    if ($flag_present == 0) 
                    {
						$final['id_indi']   [$cpt_final] = $descendants['id_indi'][$j];
                        $final['nom']       [$cpt_final] = $descendants['nom'][$j];
                        $final['lieu_naiss'][$cpt_final] = $descendants['lieu_naiss'][$j];
                        $final['sosa_d']    [$cpt_final] = $descendants['sosa_d'][$j];
                        $final['sexe']      [$cpt_final] = $descendants['sexe'][$j];
                        $final['profession'][$cpt_final] = $descendants['profession'][$j];
                        $final['datedeb']   [$cpt_final] = $descendants['date_naiss'][$j];
                        $final['datefin']   [$cpt_final] = $ancetres   ['date_naiss'][0];
                        $final['prenom2']   [$cpt_final] = $descendants['prenom2'][$j];
                        $final['date_naiss'][$cpt_final] = $descendants['date_naiss'][$j];
                        $final['date_deces'][$cpt_final] = $descendants['date_deces'][$j];
                        $final['lieu_deces'][$cpt_final] = $descendants['lieu_deces'][$j];
                        $cpt_final = $cpt_final + 1;
                    }
                }
            }
        }
    }
    return $final;
}

function recup_cousin ($id_indi,$nb_gen_asc,$nb_generations_desc)
{ global $page;
/*
                                    PRINCIPE DE L'ALGO
On calcule toujours une population principale A (correspondant aux critères demandés)
et une population secondaire B, qui correspond aux individus à exclure de la population principale.
  Exemple avec les cousins gemains
    A - lire les grands-parents (2ème génération ascendante) et leurs petits-enfants (2ème génération descendante depuis les grands-parents)
        on récupère alors les propres frères & soeurs de l'individu  qu'il faut les exclure car ce ne sont pas des cousins germains.
    B - Pour connaîtres les frères et soeurs, lire les parents (1ème génération ascendante) et leurs descendants (1ère génération descendante)
    C - On retire la population B de la population A
N.B : La population B se calcule de la meme facon que la population A en retirant 1 aux nb de générations
*/

// Population A
    $popul_a = recup_popul ($id_indi,$nb_gen_asc,$nb_generations_desc);

// Population B
    $nb_gen_asc = $nb_gen_asc - 1;
    $nb_generations_desc = $nb_generations_desc - 1;
    $popul_b = recup_popul ($id_indi,$nb_gen_asc,$nb_generations_desc);

// Population A moins population B
    $cpt_cousins = 0;
    $final = array();

  if (!empty($popul_a['id_indi'])) 
  {
    for ($i = 0; $i < count($popul_a['id_indi']); $i++)
    {
		$flag = 0;
        for ($j = 0; $j < count($popul_b ['id_indi']); $j++)
        {    if ($popul_b ['id_indi'][$j] == $popul_a['id_indi'][$i]) {$flag = 1;}
        }

        if ($flag == 0)            // si individu non trouve dans B, ca roule
        {   $final[$cpt_cousins]['id_indi']    = $popul_a['id_indi'][$i];
            $final[$cpt_cousins]['id_wife']    = "";
            $final[$cpt_cousins]['sexe']       = $popul_a['sexe'][$i];
            $final[$cpt_cousins]['sosa']       = $popul_a['sosa_d'][$i];
            $final[$cpt_cousins]['sosa_wife']  = "";
            $final[$cpt_cousins]['nom']        = $popul_a['nom'][$i];
            $final[$cpt_cousins]['date_naiss'] = $popul_a['date_naiss'][$i];
            $final[$cpt_cousins]['lieu_naiss'] = $popul_a['lieu_naiss'][$i];
            $final[$cpt_cousins]['age1']       = $popul_a['datefin'][$i];
            $final[$cpt_cousins]['age2']       = $popul_a['date_naiss'][$i];
            if ($page == "fiche_pdf.php")
			{ $final[$cpt_cousins]['profession'] = $popul_a['profession'][$i];
              $final[$cpt_cousins]['datedeb']    = $popul_a['datedeb'][$i];
              $final[$cpt_cousins]['datefin']    = $popul_a['datefin'][$i];
              $final[$cpt_cousins]['prenom2']    = $popul_a['prenom2'][$i];
              $final[$cpt_cousins]['date_deces'] = $popul_a['date_deces'][$i];
              $final[$cpt_cousins]['lieu_deces'] = $popul_a['lieu_deces'][$i];
			}
			
			
            $cpt_cousins = $cpt_cousins + 1;
        }
    }

    if (isset($final[0]['id_indi'])) 
    {  usort($final, function($a, $b) {return strcmp($a['date_naiss'], $b['date_naiss']); });
    }
// print_r2($final);
  }

  return $final;
}

/************************************** Sub menus functions **********************************/

function get_menu_source ()
{ global $sql_pref;

  $query = '
  SELECT 
   type_evene as Code
   ,SUM(Nb) as Nb
  FROM 
  (   SELECT b.type_evene
      ,COUNT(*) AS Nb
      FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_source` a
      INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_even_sour` b ON (a.id_sour = b.id_sour)
      WHERE b.type_sourc = "SOUR"
      GROUP BY b.type_evene
  
      UNION
      SELECT type_evene, COUNT(*)
      FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement`
      WHERE note_evene != ""
      AND type_evene NOT IN ("NOTE","FILE","OCCU","CHAN","RFN","RIN")
      GROUP BY type_evene
  
      UNION
      SELECT "INDI",COUNT(*)
      FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu`
      WHERE note_indi != ""

      UNION ALL
      SELECT 
       "ALL"
       ,SUM(Nb)
      FROM 
      (   SELECT COUNT(*) AS Nb
          FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_source` a
          INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_even_sour` b ON (a.id_sour = b.id_sour)
          WHERE b.type_sourc = "SOUR"
      
          UNION
          SELECT COUNT(*)
          FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement`
          WHERE note_evene != ""
          AND type_evene NOT IN ("NOTE","FILE","OCCU","CHAN","RFN","RIN")
      
          UNION
          SELECT COUNT(*)
          FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu`
          WHERE note_indi != ""
      ) AS TotalRequete


  ) AS Requete
  GROUP BY type_evene
  ';
  return $query;
}

function get_menu_events ()
{ global $sql_pref;

  $query = '
  SELECT 
   type_evene as Code
  , COUNT(*) as Nb
  FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement`
  WHERE type_evene NOT IN ("NOTE","FILE","OCCU","CHAN","RFN","RIN") and type_evene != ""
  GROUP BY type_evene

  UNION ALL
  /* individuals notes */
  SELECT 
   "INDI" as Code
  , COUNT(*) as Nb
  FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu`
  WHERE note_indi != ""
  GROUP BY "INDI"
  HAVING COUNT(*) > 0

  UNION ALL
  /* Full total */
  SELECT 
  "%" as Code
  ,SUM(nb) AS Nb
  FROM ( 
    SELECT 
   "%" as Code
  , COUNT(*) as Nb
  FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement`
  WHERE type_evene NOT IN ("NOTE","FILE","OCCU","CHAN","RFN","RIN") and type_evene != ""
  UNION ALL  
    SELECT 
   "INDI" as Code
  , COUNT(*) as Nb
  FROM `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu`
  WHERE note_indi != ""
  ) AS req
  ';
// echo $query;
  return $query;
}

function get_menu_media ()
{ global $sql_pref;

  $query = '
  SELECT 
   "INDI" as Code
  ,COUNT(*) as Nb
  FROM (`'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` a
  INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON a.id_indi = b.id_indi)
  WHERE type_evene = "FILE"  AND a.type_evene != ""
  GROUP BY a.type_evene
  
  UNION ALL
  SELECT 
   a.type_evene
  ,COUNT(*)
  FROM (`'.$sql_pref.'_'.$_REQUEST["ibase"].'_even_sour` a
  INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON a.id_indi = b.id_indi)
  WHERE a.type_sourc = "FILE"  AND a.type_evene != ""
  GROUP BY a.type_evene
  
  UNION ALL
  SELECT 
   a.type_evene
  ,COUNT(*)
  FROM ((`'.$sql_pref.'_'.$_REQUEST["ibase"].'_even_sour` a
  INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON a.id_husb = b.id_indi)
  INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` c ON a.id_wife = c.id_indi)
  WHERE a.type_sourc = "FILE" AND a.type_evene != ""
  GROUP BY a.type_evene
  
  UNION ALL

  SELECT 
    Code
   ,SUM(Nb) as Nb
  FROM 
  ( SELECT 
     "%" as Code
    ,COUNT(*) as Nb
    FROM (`'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` a
    INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON a.id_indi = b.id_indi)
    WHERE type_evene = "FILE"  AND a.type_evene != ""
    
    UNION ALL
    SELECT 
     "%" AS Code
    ,COUNT(*)
    FROM ((`'.$sql_pref.'_'.$_REQUEST["ibase"].'_even_sour` a
    INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON a.id_husb = b.id_indi)
    INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` c ON a.id_wife = c.id_indi)
    WHERE a.type_sourc = "FILE" AND a.type_evene != ""
    
    UNION ALL
    SELECT 
     "%" AS Code
    ,COUNT(*)
    FROM (`'.$sql_pref.'_'.$_REQUEST["ibase"].'_even_sour` a
    INNER JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` b ON a.id_indi = b.id_indi)
    WHERE a.type_sourc = "FILE"  AND a.type_evene != ""
   ) AS Requete
   GROUP BY Code  
  ';
  return $query;
}
