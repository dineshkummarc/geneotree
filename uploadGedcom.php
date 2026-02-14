<?php

/*
Reste à étudier
---------------
	'3 DATE'
	'4 DATE'
	,'TYPE'  // 2 TYPE marriage civil/religieux event type : religious, disease, arrival, departure, photo, marriage, census, .... TYPE EMAIL à excluse.
	,'MEDI'  // MEDIA, type de média : numérisation, original, copie. Intéressant à restituer.
	,'PEDI'  // PEDIGREELINK, lien de parenté. 5 valeurs : biologic(al), adopted, birth, foster, parent
*/

/*
Script name : admin.php

Description : manage gedcom

Rights      : administrator

Main features :
- pictures are not loaded in SQL databases. GeneoTree stores name of pictures, and read them in file directory "/picture/[base]"
- offers to download gedcom files from the user's PC/station ((the "ftp" extension is mandatory)
- Cause religious marriages are not exists in the gedcom standard 5.5, creation of a new tag "MARZ" when there are 2 MARR tags for a individual
- for performance reasons (joins between integer columns), INDI and SOUR tags are numbered in integer by "crc32" function
- prefix sql_pref to tables authorize to manage several geneotree php instances on 1 mysql instance. For example, dev and prod on MySql distant provider
- if internet connection is available, places are submit at Geoapify API geocode (3000 requests per day), which return Locality, Department, Region and Country
  if place is not find, insert into a table SQL to future manage exceptions

Database description
--------------------
  GeneoTree Data model includes 4 tables per gedcom file :
  
  "individu" table
  --------------
  attributes of individuals
  primary key  : "id_indi" integer (maximum 2,1 billions)
  foreign keys : "id_pere" integer (father) AND "id_mere" integer (mother) on individu.id_indi (auto join)
  
  "evenement" table
  -----------------
  attributes of  :
  - objets related to an individual : type_evene = 'FILE'
  - events : all events except FILE
  primary key  : id_indi, id_husb, id_wife, type_evene, date_evene
  foreign keys : id_indi          on individu for events relatives at individual : BIRT, DEAT, FILE, ...
  foreign keys : id_husb, id_wife on individu for events related to a couple  : MARR, MARR2, DIV, ...

  "source" table
  --------------
  attributes of sources (tag 0 SOUR)
  primary key : "id_sour" integer

  "even_sour" table
  -----------------
  attributes of : 
  - objets related to "evenement" : type_evene = 'FILE'
  - relation n x n between  "evenement" and "source" : all events except FILE
  primary key : not exist
  foreign key : id_indi, id_husb, id_wife, type_evene, date_evene on "evenement"
  foreign key : id_sour on "source"

Algorithm
---------
if ( ($_REQUEST['pag'] == NULL  OR $_REQUEST['ibase'] == NULL) AND $_REQUEST['pag2'] != 'geo' )
   display gedcom management grid
else if ($_REQUEST['ibase'] != NULL and ($_REQUEST['pag'] == "chg"))
   upload gedcom file in geneotree mysql (main function)
else if ($_REQUEST['ibase'] != '' and $_REQUEST['pag'] == "del")
   form to delete a database in geneotree mysql
else if ($_REQUEST['ibase'] != '' and $_REQUEST['pag'] == "delOK")
   delete a database in geneotree mysql
elseif ($_REQUEST['pag2'] == "geo")
   CRUD to manage places which are not detected by Geoapify

Not managed tags 
----------------
	,'RESN'  // RESTRICTION, access to information has been denied or otherwise restricted.

	,'DATA'  // toujours vide
	,'TIME'  // pas d'intérêt
	,'WWW'   // ne sert que dans l'entête du gedcom
	,'YEARS' // number of years of an event, pas dans la norme, très peu utilisé
	,'BLOOD' // BLOODTYPE, pas dans la norme
	,'FUNER' // FUNERALS, pas dans la norme
	,'EVEN'  // à 95% vide
	,'ADOP'  // n
	,'TEMP'  // code des temples de l'Église de Jésus-Christ des Saints des Derniers Jours 

	,'LABL'  // vide à 98%
	,'XORD'  // toujours vide
	,'FLAGS' // toujours vide
	,'LINES' // n° de ligne, sans intérêt
	,'ENABL' // sans intérêt, 2 valeurs "b" et "bt"
	,'Z'     // sans intérêt, 1 seule valeur "120"
    ,'AGNC'  // AGENCE RESPONSABLE, vide à 98%
	,'LABEL' // toujours vide
	,'STAT'  // STATUS. Quelques erreurs, renseigné avec le pays (confusion avec STATE). Inexploitable.
	,'DESC'  // Pas dans la norme. Très rarement utilisé (hurd, langeard) pour quelques compléments de description
	,'PRTY'  // Pas dans la norme.Très rarement utilisé (hurd, langeard) pour quelques compléments de description
	,'SLGC'  // SEALING CHILD, scellement d'enfant, spécificité mormons
	,'SLGS'  // SEALING SPOUSE, scellement d'époux, spécificité mormons
	,'DEFA'  // pas dans la norme
	,'ROMN'  // pas dans la norme
	,'URL'   // très peu utilisé, et souvent faux
	,'CORP'  // uniquement entête
	,'CEME'  // cimetière, pas dans la norme, très peu utilisé
	,'XTYPE' // pas dans la norme. Tag spécifique
	,'XIDEN' // pas dans la norme. Tag spécifique
	,'XMODE' // pas dans la norme. Tag spécifique
	,'CHAN'  // CHANGE, toujours vide
	,'MPRF'  // pas dans la norme. Ex : V1V1V1. Pas d'intérêt.
	,'SURE'  // pas dans la norme. Ex : 0
	,'RINI'  // pas dans la norme. Ex : pc
	,'CSTA'  // pas dans la norme. Vide. Uniquement kennedy et scarfone.
	,'CENS'  // recensement, vide. periodic count of the population for a designated locality, such as a national or state Census.
	,'DEFN'  // pas dans la norme. Uniquement sybejan DEFN Legitimation of illegitimate child, child not found, ..
	,'GODP'  // balise propriétaire signalée dans la norme. parrain d'un baptême (logiciel GeneWeb)
	,'GENDE' // GENDER, pas dans la norme
	,'MOON'  // ???, pas dans la norme, van_loo MOON Y
	,'LMAR'  // pas dans la norme. Tag spécifique lautenslager 2 LMAR Rooms Katholiek
	);

*/

function fnCountriesLoad() 
{
  $C                = fopen('countryDetect/country_multilingual.csv', 'r');
  $headers          = fgetcsv($C, 1000, ',', '"', '\\');
  $langIndex        = array_search('LANG', $headers);
  $countryNameIndex = array_search('COUNTRY_NAME', $headers);
  while (($row = fgetcsv($C, 1000, ',', '"', '\\')) !== false)
  {
    $lang       = strtolower($row[$langIndex]);
    $country    = strtolower($row[$countryNameIndex]);
    $csv_data[] = array($lang, $country);
  }
  fclose($C);
  return $csv_data;
}

function fnCountryExists($langue, $countryName) 
{
  global $arrCountries;

  $langue      = strtolower($langue);
  $countryName = strtolower($countryName);

  foreach ($arrCountries as $entry) 
  {
    if ($entry[0] === $langue && $entry[1] === $countryName) 
      return true;
  }

  return false;
}

function funcCurl ($url)
{ global $warnFile;

  if (function_exists('curl_init'))
  { $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // désactivation du certificat SSL
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
    $response  = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    if ($http_code === 429)
    {  echo 'Error 429 : limit Geoapify of 3000 requests per day is reached. Wait to morrow.';
       file_put_contents ($warnFile, '[Warning] Error Geoapify 429 : limit of 3000 requests per day is reached. Wait to morrow.'.chr(10), FILE_APPEND);
	}
    curl_close($ch);
    return $response;
  } else
  { echo '<br>Warning : Curl function is not installed on your PHP server. Please contact your Administrator.';
    return false;
  }
}

function decodeLieu($place)
{ global $sql_pref, $arrCountries, $flagFORMPLAC, $posCITY, $posDEPARTMENT, $posREGION, $posCOUNTRY, $posSUBDIV, $posPOSTALCODE;

  $lieu_evene = ''; $dept_evene = ''; $region_evene = ''; $country_evene = ''; $subdiv_evene = ''; $postal_evene = '';

  if ($flagFORMPLAC) 
  { $lieux = explode (',',$place);
// print_r2($lieux);
    if (isset($lieux[$posCITY]))
      if (isset($posCITY)       AND $posCITY < count($lieux)       ) $lieu_evene    = $lieux[$posCITY];
    if (isset($lieux[$posDEPARTMENT]))
      if (isset($posDEPARTMENT) AND $posDEPARTMENT < count($lieux) ) $dept_evene    = $lieux[$posDEPARTMENT];
    if (isset($lieux[$posREGION]))
      if (isset($posREGION)     AND $posREGION < count($lieux)     ) $region_evene  = $lieux[$posREGION];
    if (isset($lieux[$posCOUNTRY]))
      if (isset($posCOUNTRY)    AND $posCOUNTRY < count($lieux)    ) $country_evene = $lieux[$posCOUNTRY];
    if (isset($lieux[$posSUBDIV]))
      if (isset($posSUBDIV)     AND $posSUBDIV < count($lieux)     ) $subdiv_evene  = $lieux[$posSUBDIV];
    if (isset($lieux[$posPOSTALCODE]))
      if (isset($posPOSTALCODE) AND $posPOSTALCODE < count($lieux) ) $postal_evene  = $lieux[$posPOSTALCODE];
  }
  else
  {  
    $place2 = @str_replace(", ",",",$place);        // on enleve les blancs inutiles
    $place2 = preg_replace('/,+/', ',', $place2);    // on enleve les doubles virgules
    if (strpos(' '.$place2,',') == 1) 
      $place2 = mb_substr($place2,1,255);    // on enleve les virgules en premiere position
    
    $lieux = explode (',',$place2);
// echo '</br>Sans FORM:'.$place;
// print_r2($lieux);
    // initialize $arrLieux
    if (count($lieux) == 0) {$lieux[] = "";}
    if (count($lieux) == 1) {$lieux[1] = "";$lieux[2] = "";$lieux[3] = "";$lieux[4] = "";}  
    if (count($lieux) == 2) {$lieux[2] = "";$lieux[3] = "";$lieux[4] = "";}  
    if (count($lieux) == 3) {$lieux[3] = "";$lieux[4] = "";}  
    if (count($lieux) == 4) {$lieux[4] = "";}  
    
    // put place in $lieux[0]. 
    
    //We are looking for the first chain with more than 3 characters : it's the main place.
    if (mb_strlen($lieux[0]) > 3)
    {    $lieux[0] = trim($lieux[0]);
    } elseif (mb_strlen($lieux[1]) > 3)
    {    $lieux[0] = trim($lieux[1]);
    } elseif (mb_strlen($lieux[2]) > 3)
    {    $lieux[0] = trim($lieux[2]);
    } elseif (mb_strlen($lieux[3]) > 3)
    {    $lieux[0] = trim($lieux[3]);
    } elseif (mb_strlen($lieux[4]) > 3)
    {    $lieux[0] = trim($lieux[4]);
    }
    
    // Specific english language : on supprime les parasites
    $chain_sup  = array (' County',' county',' Co.',' co.',',');    
    $lieux[0] = @str_replace($chain_sup,'', $lieux[0]);
    
    // Specific french language : on remplace St et Ste par Saint et Sainte
    if (mb_substr($lieux[0],0,3) == 'St 'or mb_substr($lieux[0],0,3) == 'St.') {$lieux[0] = "Saint ".@mb_substr($lieux[0],3,35);} 
    if (mb_substr($lieux[0],0,4) == 'Ste ' or mb_substr($lieux[0],0,4) == 'Ste.') {$lieux[0] = "Sainte ".@mb_substr($lieux[0],4,34);} 
    $search = array (',st ',',st.',',ste ',',ste.',' st ',' st.',' ste ',' ste.');
    $replace = array (',saint ',',saint.',',sainte ',',sainte.',' saint ',' saint.',' sainte ',' sainte.');
    $lieux[0] = @str_replace($search, $replace, $lieux[0]);

    // put departement in $lieux[1]
    
    // If only one field is filled in, we double it in department, so that department management works
    if ($lieux[1] == "")            
    { $lieux[1] = $lieux[0];
    }
    // Specific french, if exist postal code, we transform it indepartement code (first 2 digits)
    if (preg_match_all("([0-9][0-9][0-9][0-9][0-9]+)",$lieux[1],$department) !== 0)
    { $lieux[1] = substr($department[0][0],0,2);
      // associate departement label with departement code
      if ($lieux[2] != "")
      { $lieux[1] .= " ".$lieux[2];
      }
    }
    $lieux[1] = @str_replace($chain_sup,'', $lieux[1]);
    $lieux[0] = trim($lieux[0]);
    $lieux[1] = trim($lieux[1]);

// echo '<br>'.$place.'<br>0:'.$lieux[0].', 1:'.$lieux[1]; // debug
    
    if (fnCountryExists($_REQUEST['gedlang'], $lieux[0]))
      $country_evene = $lieux[0];
    else
      $lieu_evene = $lieux[0];

    if (fnCountryExists($_REQUEST['gedlang'], $lieux[1]))
      $country_evene = $lieux[1];
    else
      $dept_evene = $lieux[1];

  }

  // prepare update
  $lieuSql    = empty($lieu_evene)    ? 'NULL' : "'".addslashes(rtrim(mb_substr($lieu_evene,0,100)))."'";
  $deptSql    = empty($dept_evene)    ? 'NULL' : "'".addslashes(rtrim(mb_substr($dept_evene,0,100)))."'";
  $regionSql  = empty($region_evene)  ? 'NULL' : "'".addslashes(rtrim(mb_substr($region_evene,0,100)))."'";
  $countrySql = empty($country_evene) ? 'NULL' : "'".addslashes(rtrim(mb_substr($country_evene,0,100)))."'";
  $subdivSql  = empty($subdiv_evene)  ? 'NULL' : "'".addslashes(rtrim(mb_substr($subdiv_evene,0,200)))."'";
  $postalSql  = empty($postal_evene)  ? 'NULL' : "'".addslashes(rtrim(mb_substr($postal_evene,0,100)))."'";
  $placeSql   = empty($place)         ? 'NULL' : "'".addslashes($place)."'";

  $query = 'UPDATE `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement`
            SET lieu_evene    = '.$lieuSql.'
               ,dept_evene    = '.$deptSql.'
               ,region_evene  = '.$regionSql.'
               ,country_evene = '.$countrySql.'
               ,subdiv_evene  = '.$subdivSql.'
               ,postal_evene  = '.$postalSql.'
            WHERE subdiv_evene = '.$placeSql;
  sql_exec($query,0);
}


function MyHash($data) {
  return floor (crc32($data) / 2);  // crc32 return max 2^32-1. To put a hash code in integer MySql (2^31-1) => divide by 2.
}

function GetId ($line)
{ /* Search characters string between @ and @. The result is pass to GetSqlId function.
     Sample :
       Input  : "0 @I1HKC-J3@ SOUR"
       Output : "I1HKC-J3"
*/
     $PosDeb = strpos($line,'@')+1;
	 $Id     = substr($line,$PosDeb, mb_strlen($line));
     $PosFin = strpos($Id,'@');
	 $Id     = substr($Id,0,$PosFin);

     return $Id;
}

function GetSqlId ($LibIndi, $id)  // LibIndi = 'id_indi' ou 'id_sour'
{ 
// Return Sql Id which put in SQL tables.
  global $sql_pref, $logFile, $nb_line;

  if ($id != '')
  { $query = '
      SELECT sql_id 
      FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_'.$LibIndi.'`
  	  WHERE id = '.$id
  	  ;
    $result = sql_exec($query,0);
    $row = sql_fetch_row($result);
 
    if (@mysqli_num_rows($result) > 0)
	{ $sql_id = $row[0];
	} else
    { $sql_id = 0;
    } 
  } else
  { $sql_id = 0;
  }
  return $sql_id;
}
  
function fnLanguageDetect ($text, $flagChoice = false)
{ /* Source     https://pear.php.net/package/Text_LanguageDetect
     @author    2005 Nicholas Pisarro <infinityminusnine+pear@gmail.com> http://dailysedative.com
     @maintenor 2017 Christian Weiske 36 Idastraße, Leipzig, Germany     https://cweiske.de

  Languages supported
  -------------------
  albanian, arabic, azeri, bengali, bulgarian, cebuano, croatian, czech, danish, dutch, english, estonian, farsi, finnish, french, german, 
  hausa, hawaiian, hindi, hungarian, icelandic, indonesian, italian, kazakh, kyrgyz, latin, latvian, lithuanian, macedonian, mongolian, 
  nepali, norwegian, pashto, pidgin, polish, portuguese, romanian, russian, serbian, slovak, slovene, somali, spanish, swahili, swedish, 
  tagalog, turkish, ukrainian, urdu, uzbek, vietnamese, welsh
  */

  $l = new Text_LanguageDetect();
  if ($flagChoice)
    $result = $l->detect($text,4);
  else
    $result = $l->detectSimple($text);

  return $result;
}

function maj_evenement($id_indi, $id_husb, $id_wife, $id_sour, $niveau0=false)
{ // update "evenement" and "even_sour" tables
  global $got_tag, $got_lang, $sql_pref, $level1NotInEvene,
         $level1InProgress, $flagMaria1, $flagMaria2,
         $id_fam, $note_evene, $no_ligne,
         $date_evene, $place_evene, $ageEvent, $religion,
         $date_naiss, $date_deces, $place_birth, $place_death,         // stockage pour rupture individu plus tard
         $logFile, $no_ligne;

  // delete carriage return in $note_evene
  if ($note_evene != NULL)
  { if (mb_substr($note_evene,0,1) == chr(13) or mb_substr($note_evene,0,1) == chr(10)) 
    { $note_evene = mb_substr($note_evene,1,mb_strlen($note_evene));
    }
  }

  // Very important : as soon as there is an $id_fam, a MARZ event is created even if the "1 MARR" tag does not exist.
  if ($level1InProgress =="MARR" AND $flagMaria2) 
  { $level1InProgress = "MARZ";
  } elseif ( $niveau0 AND $id_fam AND !$flagMaria1 AND !$flagMaria2)  
  { $level1InProgress = "MARR";
  }

  // prepare dates
  $result = trt_date($date_evene);
  if (substr($result,0,8) == '00000000')
    if ($date_evene)
     $date_evene = '! '.$date_evene;
    else 
      $date_evene = '';  
  else 
    $date_evene = $result;

  // insert events
  if (!empty($level1InProgress)) $level1InProgress = trim($level1InProgress);
  if (array_key_exists($level1InProgress, $got_tag) != NULL 
      AND !in_array($level1InProgress, $level1NotInEvene)
      AND ($id_indi or $id_husb or $id_wife)
      AND ( $level1InProgress == 'MARR' 
        OR ($level1InProgress != 'MARR' AND ($date_evene OR $note_evene OR $place_evene))
          )
     )
  { 
    // prepare events
    $dateEvenSql   = empty($date_evene)           ? 'NULL' : "'".addslashes(rtrim(mb_substr($date_evene,0,32)))."'";
    $note_evene    = trim(rtf2text($note_evene));
    $noteEvenSql   = empty($note_evene)           ? 'NULL' : "'".addslashes(rtrim(mb_substr($note_evene,0,15691)))."'";
    $placeEvenSql  = empty($place_evene)          ? 'NULL' : "'".addslashes(rtrim(mb_substr($place_evene,0,200)))."'";
    
    // insert events
    $query = 'INSERT INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement` VALUES (
     "'.GetSqlId("id_indi",$id_indi).'"
    ,"'.GetSqlId("id_indi",$id_husb).'"
    ,"'.GetSqlId("id_indi",$id_wife).'"
    ,"'.$level1InProgress.'"
    ,'.$dateEvenSql.'
    ,NULL               /* lieu_evene */
    ,NULL               /* dept_evene */
    ,'.$noteEvenSql.'
    ,NULL               /* file_date */
    ,NULL               /* file_larg */
    ,NULL               /* file_haut */
    ,NULL               /* region_evene */
    ,NULL               /* country_evene */
    ,'.$placeEvenSql.'  /* subdiv_evene */
    ,NULL               /* postal_evene */
    ,NULL               /* titl */
    )';
    sql_exec($query,0);
  }

  // insert sources
  if ($id_sour != '' and $level1InProgress != NULL)        // une reference source a ete trouvee, on l'enregistre
  {  if ($id_indi)
    { $query = 'INSERT IGNORE into `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour` VALUES (
       "'.GetSqlId("id_indi",$id_indi).'"
	  ,0
	  ,0
	  ,"'.$level1InProgress.'"
	  ,"'.addslashes($date_evene).'"
	  ,"'.GetSqlId("id_sour",$id_sour).'"
	  ,"SOUR"
	  ,""
	  ,NULL,NULL,NULL,NULL)';
      sql_exec($query,0);
    } else
    if ($id_husb or $id_wife)
    { 
//echo '<br>'.$id_indi.'/'.$id_husb.'/'.$id_wife.'/'.$id_sour."->"; // debug
      $query = 'INSERT IGNORE into `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour` VALUES (
	   0
	  ,"'.GetSqlId("id_indi",$id_husb).'"
	  ,"'.GetSqlId("id_indi",$id_wife).'"
	  ,"'.$level1InProgress.'"
	  ,"'.addslashes($date_evene).'"
	  ,"'.GetSqlId("id_sour",$id_sour).'"
	  ,"SOUR"
	  ,""
	  ,NULL,NULL,NULL,NULL)';
      sql_exec($query,0);
    }
  }

  $level1InProgress = ""; $level2InProgress = "";
  $date_evene = "";
  $note_evene = ''; 
  $place_evene = ''; 
  $lieu_evene[0] = ""; $lieu_evene[1] = "";  // obsolete
}

function NomPrenom($txt)
{   $txt = mb_ereg_replace('_',' ',$txt);
    if ($txt)
	{ preg_match_all ("([^/]+)",$txt,$match);
	} else
	{ $txt = "";
      $match[0][0] = "";
	}
    if (count($match[0]) >= 2) 
    {    $nomprenom[1] = trim($match[0][0]);
        $nomprenom[2] = $match[0][1];
    }    elseif (count($match[0]) == 1) // pas de prenoms trouves. 
    {    $nomprenom[1] = "";
        $nomprenom[2] = $match[0][0];
    } else
    {    $nomprenom[1] = "";
        $nomprenom[2] = "";
    }

    return $nomprenom;  // le nom est dans [2], les prenoms dans [1]
}

function fnNormalizeString($string)
{ global $GotEncoding;

  // 1. Remplacer caractères accentués manuellement (latin + est-européens usuels)
  $accents = array(
      'À'=>'A','Á'=>'A','Â'=>'A','Ã'=>'A','Ä'=>'A','Å'=>'A','Ā'=>'A','Ă'=>'A','Ą'=>'A',
      'à'=>'a','á'=>'a','â'=>'a','ã'=>'a','ä'=>'a','å'=>'a','ā'=>'a','ă'=>'a','ą'=>'a',
      'Ç'=>'C','Ć'=>'C','Č'=>'C','ç'=>'c','ć'=>'c','č'=>'c',
      'Ď'=>'D','ď'=>'d',
      'È'=>'E','É'=>'E','Ê'=>'E','Ë'=>'E','Ē'=>'E','Ė'=>'E','Ę'=>'E','ě'=>'e',
      'è'=>'e','é'=>'e','ê'=>'e','ë'=>'e','ē'=>'e','ė'=>'e','ę'=>'e',
      'Ì'=>'I','Í'=>'I','Î'=>'I','Ï'=>'I','Ī'=>'I','Į'=>'I','ı'=>'i',
      'ì'=>'i','í'=>'i','î'=>'i','ï'=>'i','ī'=>'i','į'=>'i',
      'Ñ'=>'N','Ń'=>'N','ň'=>'n','ñ'=>'n','ń'=>'n',
      'Ò'=>'O','Ó'=>'O','Ô'=>'O','Õ'=>'O','Ö'=>'O','Ø'=>'O','Ō'=>'O',
      'ò'=>'o','ó'=>'o','ô'=>'o','õ'=>'o','ö'=>'o','ø'=>'o','ō'=>'o',
      'Ŕ'=>'R','Ř'=>'R','ŕ'=>'r','ř'=>'r',
      'Š'=>'S','ś'=>'s','š'=>'s','Ś'=>'S','ș'=>'s','Ș'=>'S',
      'ț'=>'t','Ț'=>'T',
      'Ù'=>'U','Ú'=>'U','Û'=>'U','Ü'=>'U','Ū'=>'U','Ů'=>'U','Ų'=>'U',
      'ù'=>'u','ú'=>'u','û'=>'u','ü'=>'u','ū'=>'u','ů'=>'u','ų'=>'u',
      'Ý'=>'Y','Ÿ'=>'Y','ý'=>'y','ÿ'=>'y',
      'Ž'=>'Z','Ź'=>'Z','Ż'=>'Z','ž'=>'z','ź'=>'z','ż'=>'z',
      'Þ'=>'Th','þ'=>'th','Ð'=>'D','ð'=>'d','ß'=>'ss',
      'Æ' => 'AE', 'æ' => 'ae', 'Œ' => 'OE', 'œ' => 'oe',
      'Ĳ' => 'IJ', 'ĳ' => 'ij', 'ﬀ' => 'ff', 'ﬁ' => 'fi', 'ﬂ' => 'fl', 'ﬃ' => 'ffi',
      'ﬄ' => 'ffl', 'ﬅ' => 'st', 'ﬆ' => 'st',  
  );
  $string = strtr($string, $accents);
// echo '<br>1 '.$string;

  // 2. Remplacer les tirets par un espace
  $string = str_replace('-', ' ', $string);
// echo '<br>2 '.$string;

  // 3. Supprimer les espaces multiples
  $string = preg_replace('/\s+/', ' ', $string);
  $string = trim($string);
// echo '<br>3'.$string;
 
  // 4. Mise en minuscules
  $string = mb_strtolower($string);
// echo '<br>4 '.$string;

  return $string;
}

function fnPlaceDeleteCode ($chaine) 
{ // Delete all numbers between comma
    $chaine = preg_replace('/^\s*\d+\s*,\s*/', '', $chaine);
    $chaine = preg_replace('/,\s*\d+\s*,/', ', ,', $chaine);
    $chaine = preg_replace('/,\s*\d+\s*$/', ',', $chaine);
    return $chaine;
}

function fnPlaceReplaceAcronym($text) 
{ // Replace text entre des virgules ou virgule et fin de chaine. Tenir compte des blancs.
  // Example : 'VA, USA' => 'Virginia, United-States'
  $codes = array(
      'MASS' => 'Massachusetts',
      'PENN' => 'Pennsylvania',
      'USA' => 'United States of America',
      'FRA' => 'France',
      'NOB' => 'Normandie',
      'ARA' => 'Auvergne-Rhône-Alpes',
      'BFC' => 'Bourgogne-Franche-Comté',
      'BRE' => 'Bretagne',
      'CVL' => 'Centre-Val de Loire',
      '20R' => 'Corse',
      'GES' => 'Grand Est',
      'HDF' => 'Hauts-de-France',
      'IDF' => 'Île-de-France',
      'NOR' => 'Normandie',
      'NAQ' => 'Nouvelle-Aquitaine',
      'OCC' => 'Occitanie',
      'PDL' => 'Pays de la Loire',
      'PAC' => 'Provence-Alpes-Côte d\'Azur',
      'AL' => 'Alabama',
      'AK' => 'Alaska',
      'AZ' => 'Arizona',
      'AR' => 'Arkansas',
      'CA' => 'California',
      'CO' => 'Colorado',
      'CT' => 'Connecticut',
      'DC' => 'District of Columbia',
      'DE' => 'Delaware',
      'FL' => 'Florida',
      'GA' => 'Georgia',
      'HI' => 'Hawaii',
      'ID' => 'Idaho',
      'IL' => 'Illinois',
      'IN' => 'Indiana',
      'IA' => 'Iowa',
      'KS' => 'Kansas',
      'KY' => 'Kentucky',
      'LA' => 'Louisiana',
      'ME' => 'Maine',
      'MD' => 'Maryland',
      'MA' => 'Massachusetts',
      'MI' => 'Michigan',
      'MN' => 'Minnesota',
      'MS' => 'Mississippi',
      'MO' => 'Missouri',
      'MT' => 'Montana',
      'NE' => 'Nebraska',
      'NV' => 'Nevada',
      'NH' => 'New Hampshire',
      'NJ' => 'New Jersey',
      'NM' => 'New Mexico',
      'NY' => 'New York',
      'NC' => 'North Carolina',
      'ND' => 'North Dakota',
      'OH' => 'Ohio',
      'OK' => 'Oklahoma',
      'OR' => 'Oregon',
      'PA' => 'Pennsylvania',
      'RI' => 'Rhode Island',
      'SC' => 'South Carolina',
      'SD' => 'South Dakota',
      'TN' => 'Tennessee',
      'TX' => 'Texas',
      'UT' => 'Utah',
      'VT' => 'Vermont',
      'VA' => 'Virginia',
      'WA' => 'Washington',
      'WV' => 'West Virginia',
      'WI' => 'Wisconsin',
      'WY' => 'Wyoming',
  );

  return preg_replace_callback
  ( '/(^|\s*,)\s*([a-zA-Z]{2,4})\s*(?=,|$)/',
    function($matches) use ($codes) 
    { $prefix = $matches[1]; // soit début de chaîne ou une virgule
      $code = strtoupper($matches[2]); // mise en majuscule du code
      if (isset($codes[$code])) 
      {  return $prefix . ' ' . $codes[$code];
      }
      return $matches[0];
    },
    $text
  );
}

function fnPlaceReplace($placeString)
{
  global $logFile, $sql_pref;

  // get a potential replacement place
  $query = 
  " SELECT Known_map_Place
    FROM ".$sql_pref."__map_places 
    WHERE base = '".$_REQUEST["ibase"]."' 
      AND Unknown_map_Place = '".addslashes($placeString)."'
      AND Known_map_Place  != ''
  ";
  $result = sql_exec($query,0);
  if (mysqli_num_rows($result) > 0)
  { $row = mysqli_fetch_row($result);
    $placeString = $row[0];
  }

  return $placeString;
}

function fnPlaceSearch($search, $place) 
{ 
  $search = trim($search);
  if ($search == '')
   return false;
// echo '<br>deb:'.$search.' | '.$place;
  $parts = explode(',', $place);

  foreach ($parts as $part) 
  { $trimmed = trim($part);
    if ($trimmed === $search) 
      return true;
  }

  return false;
}

function fnPlaceSubdiv ($data)
{ // put content of subdivision
  $result = '';
  if (!empty($data['results'][0]['suburb']))               $result .= ' '.$data['results'][0]['suburb'];
  elseif (!empty($data['query']['parsed']['suburb']))      $result .= ' '.$data['query']['parsed']['suburb'];

  if (!empty($data['results'][0]['housenumber']))          $result .= ' '.$data['results'][0]['housenumber'];
  elseif (!empty($data['query']['parsed']['housenumber'])) $result .= ' '.$data['query']['parsed']['housenumber'];

  if (!empty($data['results'][0]['street']))               $result .= ' '.$data['results'][0]['street'];
  elseif (!empty($data['query']['parsed']['street']))      $result .= ' '.$data['query']['parsed']['street'];

  if (!empty($data['results'][0]['house']))                $result .= ' '.$data['results'][0]['house'];
  elseif (!empty($data['query']['parsed']['house']))       $result .= ' '.$data['query']['parsed']['house'];

  return trim($result);
}

function fnPlaceWithoutGeo ($placeString, $noLine)
{
  global $warnFile, $sql_pref;

  $gedcomCompliance = '';

  $query = "INSERT IGNORE INTO ".$sql_pref."__map_places VALUES ('".addslashes($placeString)."', '', '".$_REQUEST["ibase"]."')";
  sql_exec($query,0);

  decodeLieu($placeString);

  if (mb_strpos($placeString, ',') === false)
    $gedcomCompliance = '. The place format does not appear to be Gedcom compliant. The payload must contain a comma-separated list of region names.';

  if (mb_strpos($placeString, '/') !== false)
    $gedcomCompliance .= ' What is the meaning of slash ?';

  $noLine = substr('     '.$noLine,-6);
  file_put_contents ($warnFile, '[Warning] Line '.$noLine.' Place not geolocalized        : "'.$placeString.'"'.$gedcomCompliance.chr(10), FILE_APPEND);
}

function fnPlaceWithGeo ($placeString, $arrParsedPlace, $noLine, $logType = 'L')
{
  global $logFile, $warnFile, $sql_pref;

  // prepare update
  $lieuSql    = empty($arrParsedPlace['city'])    ? 'NULL' : "'".addslashes(rtrim(mb_substr($arrParsedPlace['city'],0,100)))."'";
  $deptSql    = empty($arrParsedPlace['county'])  ? 'NULL' : "'".addslashes(rtrim(mb_substr($arrParsedPlace['county'],0,100)))."'";
  $regionSql  = empty($arrParsedPlace['state'])   ? 'NULL' : "'".addslashes(rtrim(mb_substr($arrParsedPlace['state'],0,100)))."'";
  $countrySql = empty($arrParsedPlace['country']) ? 'NULL' : "'".addslashes(rtrim(mb_substr($arrParsedPlace['country'],0,100)))."'";
  $subdivSql  = empty($arrParsedPlace['suburb'])  ? 'NULL' : "'".addslashes(rtrim(mb_substr($arrParsedPlace['suburb'],0,200)))."'";
  $placeSql   = empty($placeString)               ? 'NULL' : "'".addslashes($placeString)."'";
  
  $query =
   ' UPDATE `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement`
     SET lieu_evene    = '.$lieuSql.'
        ,dept_evene    = '.$deptSql.'
        ,region_evene  = '.$regionSql.'
        ,country_evene = '.$countrySql.'
        ,subdiv_evene  = '.$subdivSql.'
    WHERE subdiv_evene = '.$placeSql;
  sql_exec($query,0);

  $noLine = substr('     '.$noLine,-6);
  if ($logType == 'L')
    file_put_contents ($logFile, '[Info] Line '.$noLine.' Place geolocalized: "'.$placeString.'" => '.$arrParsedPlace["city"].','.$arrParsedPlace["county"].','.$arrParsedPlace["state"].','.$arrParsedPlace["country"].','.$arrParsedPlace["suburb"].chr(10), FILE_APPEND);
  else
    file_put_contents ($warnFile, '[Warning] Line '.$noLine.' Place geolocalized with doubts: "'.$placeString.'" => '.$arrParsedPlace["city"].','.$arrParsedPlace["county"].','.$arrParsedPlace["state"].','.$arrParsedPlace["country"].chr(10), FILE_APPEND);
}

function rtf_isPlainText($s) 
{   $arrfailAt = array("*", "fonttbl", "colortbl", "datastore", "themedata");
    for ($i = 0; $i < count($arrfailAt); $i++)
        if (!empty($s[$arrfailAt[$i]])) return false;
    return true;
}

function rtf2text($text) 
{ // Source : https://github.com/silvermine/php-rtflex

  // Read the data from the input file.
  // $text = file_get_contents($filename);
  if (!strlen($text))
      return "";

  if (!strpos ($text, "\\rtf") )
      return $text;

  // Create empty stack array.
  $document = "";
  $stack = array();
  $j = -1;
  // Read the data character-by- character…
  for ($i = 0, $len = strlen($text); $i < $len; $i++) 
  {   $c = $text[$i];

      // Depending on current character select the further actions.
      switch ($c) {
          // the most important key word backslash
          case "\\":
              // read next character
              $nc = $text[$i + 1];

              // If it is another backslash or nonbreaking space or hyphen,
              // then the character is plain text and add it to the output stream.
              if ($nc == '\\' && rtf_isPlainText($stack[$j])) $document .= '\\';
              elseif ($nc == '~' && rtf_isPlainText($stack[$j])) $document .= ' ';
              elseif ($nc == '_' && rtf_isPlainText($stack[$j])) $document .= '-';
              // If it is an asterisk mark, add it to the stack.
              elseif ($nc == '*') $stack[$j]["*"] = true;
              // If it is a single quote, read next two characters that are the hexadecimal notation
              // of a character we should add to the output stream.
              elseif ($nc == "'") {
                  $hex = substr($text, $i + 2, 2);
                  if (rtf_isPlainText($stack[$j]))
                      $document .= html_entity_decode("&#".hexdec($hex).";");
                  //Shift the pointer.
                  $i += 2;
              // Since, we’ve found the alphabetic character, the next characters are control word
              // and, possibly, some digit parameter.
              } elseif ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') 
              {   $word = "";
                  $param = null;

                  // Start reading characters after the backslash.
                  for ($k = $i + 1, $m = 0; $k < strlen($text); $k++, $m++) 
                  {   $nc = $text[$k];
                      // If the current character is a letter and there were no digits before it,
                      // then we’re still reading the control word. If there were digits, we should stop
                      // since we reach the end of the control word.
                      if ($nc >= 'a' && $nc <= 'z' || $nc >= 'A' && $nc <= 'Z') 
                      {   if (empty($param))
                              $word .= $nc;
                          else
                              break;
                      // If it is a digit, store the parameter.
                      } elseif ($nc >= '0' && $nc <= '9')
                          $param .= $nc;
                      // Since minus sign may occur only before a digit parameter, check whether
                      // $param is empty. Otherwise, we reach the end of the control word.
                      elseif ($nc == '-') 
                      {   if (empty($param))
                              $param .= $nc;
                          else
                              break;
                      } else
                          break;
                  }
                  // Shift the pointer on the number of read characters.
                  $i += $m - 1;

                  // Start analyzing what we’ve read. We are interested mostly in control words.
                  $toText = "";
                  switch (strtolower($word)) 
                  {   // If the control word is "u", then its parameter is the decimal notation of the
                      // Unicode character that should be added to the output stream.
                      // We need to check whether the stack contains \ucN control word. If it does,
                      // we should remove the N characters from the output stream.
                      case "u":
                          $toText .= html_entity_decode("&#x".dechex($param).";");
                          $ucDelta = @$stack[$j]["uc"];
                          if ($ucDelta > 0)
                              $i += $ucDelta;
                      break;
                      // Select line feeds, spaces and tabs.
                      case "par": case "page": case "column": case "line": case "lbr":
                          $toText .= "\n";
                      break;
                      case "emspace": case "enspace": case "qmspace":
                          $toText .= " ";
                      break;
                      case "tab": $toText .= "\t"; break;
                      // Add current date and time instead of corresponding labels.
                      case "chdate": $toText .= date("m.d.Y"); break;
                      case "chdpl": $toText .= date("l, j F Y"); break;
                      case "chdpa": $toText .= date("D, j M Y"); break;
                      case "chtime": $toText .= date("H:i:s"); break;
                      // Replace some reserved characters to their html analogs.
                      case "emdash": $toText .= html_entity_decode("&mdash;"); break;
                      case "endash": $toText .= html_entity_decode("&ndash;"); break;
                      case "bullet": $toText .= html_entity_decode("&#149;"); break;
                      case "lquote": $toText .= html_entity_decode("&lsquo;"); break;
                      case "rquote": $toText .= html_entity_decode("&rsquo;"); break;
                      case "ldblquote": $toText .= html_entity_decode("&laquo;"); break;
                      case "rdblquote": $toText .= html_entity_decode("&raquo;"); break;
                      // Add all other to the control words stack. If a control word
                      // does not include parameters, set &param to true.
                      default:
                          $stack[$j][strtolower($word)] = empty($param) ? true : $param;
                      break;
                  }
                  // Add data to the output stream if required.
                  if (rtf_isPlainText($stack[$j]))
                      $document .= $toText;
              }

              $i++;
          break;
          // If we read the opening brace {, then new subgroup starts and we add
          // new array stack element and write the data from previous stack element to it.
          case "{":
              if ($j >= 0)    // add by D. Poulain cause warning with PHP 8
              { array_push($stack, $stack[$j++]);
              } else
              { $j++;
              }
          break;
          // If we read the closing brace }, then we reach the end of subgroup and should remove
          // the last stack element.
          case "}":
              array_pop($stack);
              $j--;
          break;
          // Skip “trash”.
          case '\0': case '\r': case '\f': case '\n': break;
          // Add other data to the output stream if required.
          default:
              if ($j >= 0)   // add by D. Poulain cause warning
              {  if (rtf_isPlainText($stack[$j]))
                  $document .= $c;
              }
          break;
      }
  }
  // Return result.
  return $document;
}

// Traitement des prenoms
function tdp ($prenom) 
{ $prenoms[0] = "";
  $prenoms[1] = "";
  $prenoms[2] = "";
  $prenom = $prenom.' ';
  if (mb_strpos($prenom, " ") == mb_strlen($prenom)) {}
  else 
  {   $prenoms[0] = mb_substr($prenom, 0, mb_strpos($prenom, " "));
      $prenom = mb_substr($prenom, mb_strpos($prenom, " ")+1,42).' ';
      if (mb_strpos($prenom.' ', " ") == mb_strlen($prenom.' ')) {}
      else
      {    $prenoms[1] = mb_substr($prenom, 0, mb_strpos($prenom, " "));
          $prenom = mb_substr($prenom.' ', mb_strpos($prenom, " ")+1,42).' ';
          if (mb_strpos($prenom.' ', " ") == mb_strlen($prenom.' ')) {}
          else
          {    $prenoms[2] = mb_substr($prenom, 0, mb_strpos($prenom, " "));
              $prenom = mb_substr($prenom.' ', mb_strpos($prenom, " ")+1,42).' ';
          }
      }
  }
  return $prenoms;
}

function replaceRomanNumerals($input) 
{
  // swith function. str_replace function does not available cause I, V and X are repeated
  switch ($input)
  { case 'I'    : $RomanNum =  1; break;
    case 'II'   : $RomanNum =  2; break;
    case 'III'  : $RomanNum =  3; break;
    case 'IV'   : $RomanNum =  4; break;
    case 'V'    : $RomanNum =  5; break;
    case 'VI'   : $RomanNum =  6; break;
    case 'VII'  : $RomanNum =  7; break;
    case 'VIII' : $RomanNum =  8; break;
    case 'IX'   : $RomanNum =  9; break;
    case 'X'    : $RomanNum = 10; break;
    case 'XI'   : $RomanNum = 11; break;
    case 'XII'  : $RomanNum = 12; break;
    case 'XIII' : $RomanNum = 13; break;
    case 'XIV'  : $RomanNum = 14; break;
  }
  return $RomanNum;
}

function Revol2Grego ($DAY, $MONTH, $YEAR)
{ $flagAlphaMonthEmpty = false; $flagDayEmpty = false;

  // fill empty day/month to '1' for function frenchtojd
  if ($MONTH == 0) 
  { $flagAlphaMonthEmpty = true;
    $MONTH = 1;
  }
  if ($DAY == 0)
  { $flagDayEmpty = true;
    $DAY = 1;
  }

  // convert revolutionary in gregorian with frenchtojd function
  $result = cal_from_jd(frenchtojd($MONTH,$DAY,$YEAR), CAL_GREGORIAN);

  // restore initials empty day and month
  if ($flagAlphaMonthEmpty) $result['month']= 0;
  if ($flagDayEmpty)   $result['day']  = 0;

  return $result;
}

function trt_date($date)
{ /*
  The presentation of a family tree has a recurring need to present individuals, events sorted by date.

  Memento Gedcom v7
  -----------------
  6 families of dates format in gedcom v7 :
  - {DD MMM YYYY}
  - {TAG} {PREFIX} {DD MMM YYYY}                 {TAG} can be AFT(er) or BEF(ore)
  - BET {PREFIX}{DD MMM YYYY} AND {PREFIX}{DD MMM YYYY}
  - FROM {PREFIX}{DD MMM YYYY} TO {PREFIX}{DD MMM YYYY}
  - {PREFIX}{DD MMM YYYY} OR {PREFIX}{DD MMM YYYY}
  - BCE {PREFIX}{DD MMM YYYY}                    before Jesus-Christ

  {DD MMM YYYY} can be prefix by ABT(About), CAL(culate), EST(imate) or INT

  This format is not sortable.
  Also, it is interesting to format the dates of the gedcom files with a date in the sortable YYYYMMDD format.

  GeneoTree date format
  ---------------------
  In order this function transforms dates into the next format in MySQL database :
    {YYYYMMDD} {YYYYMMDD} {PREFIX} {PREFIX} {PREFIX} {PREFIX}
      Example: "BET ABT 1939 AND ABT 1945" => "19390000 19450000 BET ABT AND ABT"
  When there is no day,   date = '{YYYYMM}00'.
  When there is no month, date = '{YYYY}0000'.
  
  Then, the client application identifies the months and days "00" so as not to display them.
    Example: "19390000 19450000 BET ABT AND ABT" (GeneoTree MySql format) 
     is display "Between about 1939 and about 1945"
*/

  global $ArrMonthsEn,$ArrMonthsEnFull,$ArrMonthsFr,$ArrMonthsFrFull,$ArrMonthsPl,$ArrMonthsPlFull,$ArrMonthsNum
        ,$ArrRevolutionMonths,$ArrRevolutionMonthsShor,$ArrRevolutionMonthsFull
        ,$ArrRevolutionYearsChar, $ArrRevolutionYearsFull1, $ArrRevolutionYearsFull2
        ,$ArrRevolutionMonthsNum
        ,$ArrDateTags,$ArrDateApproxChar, $ArrDateCharAfter, $ArrDateCharBefore, $ArrDateCharFlagAnd, $ArrDateLogExclusion
        ,$warnFile, $no_ligne;

  // empty date
  if (empty($date))
    return '00000000';

  // variables initialization
  $YEAR1 = "0"; $MONTH1 = "0"; $DAY1  = "0"; 
  $YEAR2 = "0"; $MONTH2 = "0"; $DAY2  = "0";
  $PREF1 = ""; $PREF2  = ""; $PREF3 = ""; $PREF4 = "";
  $UnknownNumber11 = ""; $UnknownNumber12 = "";  $UnknownNumber13 = "";
  $UnknownNumber21 = ""; $UnknownNumber22 = "";  $UnknownNumber23 = ""; 
  $flagRevol = false;
  $flagAlphaMonth1 = false; $flagAlphaMonth2 = false; $flagAnd = false;

  // prepare date
  $date = strtoupper($date);
  $date = str_replace('-',' TO ',$date);
  $date = str_replace($ArrDateCharAfter,' AFT ', $date);
  $date = str_replace($ArrDateCharBefore,' BEF ', $date);
  $date = str_replace($ArrDateApproxChar,' ',$date);

  // date split
  $dateDetail = explode(" ", $date);

  // dateDetail treatment
  foreach ($dateDetail as $part)
  {
    // get numeric values
    if (ctype_digit($part))
    { 
      if (!$flagAnd)
      { if ($flagAlphaMonth1)
        { if (!$YEAR1) $YEAR1 = $part;
        }
        else
          if (!$UnknownNumber11)
            $UnknownNumber11 = $part;
          elseif (!$UnknownNumber12)
            $UnknownNumber12 = $part;
          elseif (!$UnknownNumber13)
            $UnknownNumber13 = $part;
      } 
      else
      { if ($flagAlphaMonth2)
        { if (!$YEAR2) $YEAR2 = $part;
        }
        else
          if (!$UnknownNumber21)
            $UnknownNumber21 = $part;
          elseif (!$UnknownNumber22)
            $UnknownNumber22 = $part;
          elseif (!$UnknownNumber23)
            $UnknownNumber23 = $part;
      }
    }

    // get gregorian alphabetic month
    elseif (in_array($part,$ArrMonthsEn) != false)
    { if (!$flagAnd)
      { $MONTH1 = str_replace ($ArrMonthsEn, $ArrMonthsNum, $part); $flagAlphaMonth1 = true; }
      else
      { $MONTH2 = str_replace ($ArrMonthsEn, $ArrMonthsNum, $part); $flagAlphaMonth2 = true; }
    }
    elseif (in_array($part,$ArrMonthsEnFull) != false)
    { if (!$flagAnd)
      { $MONTH1 = str_replace ($ArrMonthsEnFull, $ArrMonthsNum, $part); $flagAlphaMonth1 = true; }
      else
      { $MONTH2 = str_replace ($ArrMonthsEnFull, $ArrMonthsNum, $part); $flagAlphaMonth2 = true; }
    }
    elseif (in_array($part,$ArrMonthsFr) != false)
    { if (!$flagAnd)
      { $MONTH1 = str_replace ($ArrMonthsFr, $ArrMonthsNum, $part); $flagAlphaMonth1 = true; }
      else
      { $MONTH2 = str_replace ($ArrMonthsFr, $ArrMonthsNum, $part); $flagAlphaMonth2 = true; }
    }
    elseif (in_array($part,$ArrMonthsFrFull) != false)
    { if (!$flagAnd)
      { $MONTH1 = str_replace ($ArrMonthsFrFull, $ArrMonthsNum, $part); $flagAlphaMonth1 = true; }
      else
      { $MONTH2 = str_replace ($ArrMonthsFrFull, $ArrMonthsNum, $part); $flagAlphaMonth2 = true; }
    }
    elseif (in_array($part,$ArrMonthsPl) != false)
    { if (!$flagAnd)
      { $MONTH1 = str_replace ($ArrMonthsPl, $ArrMonthsNum, $part); $flagAlphaMonth1 = true; }
      else
      { $MONTH2 = str_replace ($ArrMonthsPl, $ArrMonthsNum, $part); $flagAlphaMonth2 = true; }
    }
    elseif (in_array($part,$ArrMonthsPlFull) != false)
    { if (!$flagAnd)
      { $MONTH1 = str_replace ($ArrMonthsPlFull, $ArrMonthsNum, $part); $flagAlphaMonth1 = true; }
      else
      { $MONTH2 = str_replace ($ArrMonthsPlFull, $ArrMonthsNum, $part); $flagAlphaMonth2 = true; }
    }

    // detect AND or TO or BIS (BET AND & FROM TO)
    elseif (in_array ($part,$ArrDateCharFlagAnd) != false)
      $flagAnd = true;

    // get revolutionary year : I, II, ...
    elseif (in_array ($part,$ArrRevolutionYearsChar) != false)
    { $flagRevol = true;
      if (!$flagAnd)
        $YEAR1 = replaceRomanNumerals($part);
      else
        $YEAR2 = replaceRomanNumerals($part);
    }
    elseif (in_array ($part,$ArrRevolutionYearsFull1) != false)
    { $flagRevol = true;
      if (!$flagAnd)
        $YEAR1 = trim(substr($part,2,2));
      else
        $YEAR2 = trim(substr($part,2,2));
    }
    elseif (in_array ($part,$ArrRevolutionYearsFull2) != false)
    { $flagRevol = true;
      if (!$flagAnd)
        $YEAR1 = replaceRomanNumerals(trim(substr($part,2,4)));
      else
        $YEAR2 = replaceRomanNumerals(trim(substr($part,2,4)));
    }

    // get revolutionary month : VEND, BRUM, ... VENDEMAIRE, BRUMAIRE, ....
    elseif (in_array ($part,$ArrRevolutionMonths) != false)
    { $flagRevol = true;
      if (!$flagAnd)
      { $MONTH1 = str_replace ($ArrRevolutionMonths, $ArrRevolutionMonthsNum, $part); $flagAlphaMonth1 = true; }
      else
      { $MONTH2 = str_replace ($ArrRevolutionMonths, $ArrRevolutionMonthsNum, $part); $flagAlphaMonth2 = true; }
    }
    elseif (in_array ($part,$ArrRevolutionMonthsShor) != false)
    { $flagRevol = true;
      if (!$flagAnd)
      { $MONTH1 = str_replace ($ArrRevolutionMonthsShor, $ArrRevolutionMonthsNum, $part); $flagAlphaMonth1 = true; }
      else
      { $MONTH2 = str_replace ($ArrRevolutionMonthsShor, $ArrRevolutionMonthsNum, $part); $flagAlphaMonth2 = true; }
    }
    elseif (in_array ($part,$ArrRevolutionMonthsFull) != false)
    { $flagRevol = true;
      if (!$flagAnd)
      { $MONTH1 = str_replace ($ArrRevolutionMonthsFull, $ArrRevolutionMonthsNum, $part); $flagAlphaMonth1 = true; }
      else
      { $MONTH2 = str_replace ($ArrRevolutionMonthsFull, $ArrRevolutionMonthsNum, $part); $flagAlphaMonth2 = true; }
    }

    // get prefixs : AFT, BEF, ...
    if (in_array($part,$ArrDateTags))
      if     (empty($PREF1)) $PREF1 = $part;
      elseif (empty($PREF2)) $PREF2 = $part;
      elseif (empty($PREF3)) $PREF3 = $part;
      elseif (empty($PREF4)) $PREF4 = $part;

    // detect revolutionnary calendar
    if ($part == "#DFRENCH" OR $part == "AN")
      $flagRevol = true;
  }

  // affect unknownumbers to YEAR, MONTH and DAY
  if ($UnknownNumber11)
    if ($flagAlphaMonth1)
      if ($UnknownNumber11)
        $DAY1   = $UnknownNumber11;
      else
        $YEAR1  = $UnknownNumber11;
    else
      if ($UnknownNumber11 AND !$UnknownNumber12)
        $YEAR1  = $UnknownNumber11;
      elseif ($UnknownNumber12 AND !$UnknownNumber13)
      { $MONTH1 = $UnknownNumber11;
        $YEAR1  = $UnknownNumber12;
      }
      elseif (checkdate($UnknownNumber12, $UnknownNumber11, $UnknownNumber13)) 
      { $DAY1   = $UnknownNumber11;
        $MONTH1 = $UnknownNumber12;
        $YEAR1  = $UnknownNumber13;
      } 
      elseif (checkdate($UnknownNumber11, $UnknownNumber12, $UnknownNumber13)) 
      { $DAY1   = $UnknownNumber12;
        $MONTH1 = $UnknownNumber11;
        $YEAR1  = $UnknownNumber13;
      }
      else
      { $DAY1   = '00';
        $MONTH1 = '00';
        $YEAR1  = '0000';
      }
  if ($UnknownNumber21)
    if ($flagAlphaMonth2)
      if ($UnknownNumber21)
        $DAY2   = $UnknownNumber21;
      else
        $YEAR2  = $UnknownNumber21;
    else
      if ($UnknownNumber21 AND !$UnknownNumber22)
        $YEAR2  = $UnknownNumber21;
      elseif ($UnknownNumber22 AND !$UnknownNumber23)
      { $MONTH2 = $UnknownNumber21;
        $YEAR2  = $UnknownNumber22;
      }
      elseif (checkdate($UnknownNumber22, $UnknownNumber21, $UnknownNumber23)) 
      { $DAY2   = $UnknownNumber21;
        $MONTH2 = $UnknownNumber22;
        $YEAR2  = $UnknownNumber23;
      } 
      elseif (checkdate($UnknownNumber21, $UnknownNumber22, $UnknownNumber23)) 
      { $DAY2   = $UnknownNumber22;
        $MONTH2 = $UnknownNumber21;
        $YEAR2  = $UnknownNumber23;
      }
      else
      { $DAY2   = '00';
        $MONTH2 = '00';
        $YEAR2  = '0000';
      }

  // if revolutionay calendar, convert day/month/year in gregorian
  if ($flagRevol)
  { if ($YEAR1 != 0)
    { $result = Revol2Grego($DAY1, $MONTH1, $YEAR1);
      $DAY1   = $result["day"];
      $MONTH1 = $result["month"];
      $YEAR1  = $result["year"];
    }
    if ($YEAR2 != 0)
    { $result = Revol2Grego($DAY2, $MONTH2, $YEAR2);
      $DAY2   = $result["day"];
      $MONTH2 = $result["month"];
      $YEAR2  = $result["year"];
    }
  }

  // complete with left zeros to have YYYYMMDD format 8 characters
  $YEAR1  = substr('000'.$YEAR1,-4);
  $MONTH1 = substr('0'.$MONTH1,-2);
  $DAY1   = substr('0'.$DAY1,-2);
  if ($YEAR2 != 0)
  { $YEAR2  = substr('000'.$YEAR2,-4);
    $MONTH2 = substr('0'.$MONTH2,-2);
    $DAY2   = substr('0'.$DAY2,-2);
  }
  else 
  { $YEAR2  = '';
    $MONTH2 = '';
    $DAY2   = '';
  }

  // out of range treatment 
  if ((intval($YEAR1) > 0 AND intval($YEAR1) <= 31) OR intval($YEAR1) > 3000) 
  { $YEAR1 = '0000'; $MONTH1 = '00'; $DAY1 = '00';}
  if ((intval($YEAR2) > 0 AND intval($YEAR2) <= 31) OR intval($YEAR2) > 3000) 
  { $YEAR2 = '0000'; $MONTH2 = '00'; $DAY2 = '00';}
  if (intval($MONTH1) > 12) 
  { $MONTH1 = '00';
    file_put_contents($warnFile,  '[Warning] Line '.$no_ligne.' date "'.$date.'" does not have a good format.'.chr(10), FILE_APPEND);
  }
  if (intval($MONTH2) > 12) 
  { $MONTH2 = '00';
    file_put_contents($warnFile,  '[Warning] Line '.$no_ligne.' date "'.$date.'" does not have a good format.'.chr(10), FILE_APPEND);
  }
  if (intval($DAY1)   > 31) 
  { $DAY1   = '00';
    file_put_contents($warnFile,  '[Warning] Line '.$no_ligne.' date "'.$date.'" does not have a good format.'.chr(10), FILE_APPEND);
  }
  if (intval($DAY2)   > 31) 
  { $DAY2   = '00';
    file_put_contents($warnFile,  '[Warning] Line '.$no_ligne.' date "'.$date.'" does not have a good format.'.chr(10), FILE_APPEND);
  }

  // if no year found, we look for 4 consecutive digits
  if ($YEAR1 == '0000')
    if (preg_match('/\d{4}/', $date, $matches)) 
      $YEAR1 = $matches[0];

  if ($YEAR1 == '0000' AND !in_array($date,$ArrDateLogExclusion) )
    file_put_contents($warnFile,  '[Warning] Line '.$no_ligne.' date "'.$date.'" does not have a good format.'.chr(10), FILE_APPEND);

  return $YEAR1.$MONTH1.$DAY1." ".$YEAR2.$MONTH2.$DAY2." ".$PREF1." ".$PREF2." ".$PREF3." ".$PREF4;
}

/**********************  BEGIN OF UPLOAD SCRIPT *******************************/

if (!isset($_REQUEST['time']))       $_REQUEST['time']     = "";
if (!isset($_REQUEST['gedlang']))    $_REQUEST['gedlang']  = "";
if (!isset($_REQUEST['gedlangForm'])) $_REQUEST['gedlangForm']  = "";
if (!isset($_REQUEST['irow']))       $_REQUEST['irow']     = 0;
if (!isset($_REQUEST['imap']))       $_REQUEST['imap']     = 0;
if (!isset($_REQUEST['edi']))        $_REQUEST['edi']      = "";
if (!isset($_REQUEST['logi']))       $_REQUEST['logi']     = "";
if (!isset($_REQUEST['progress']))   $_REQUEST['progress'] = 0;

$ArrMonthsEn             = array('JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC');
$ArrMonthsEnFull         = array('JANUARY','FEBRUARY','MARCH','APRIL','MAY','JUNE','JULY','AUGUST','SEPTEMBER','OCTOBER','NOVEMBER','DECEMBER');
$ArrMonthsFr             = array('JANV','FÉV','MAR','AVR','MAI','JUIN','JUIL','AOÛT','SEPT','OCT','NOV','DÉC');
$ArrMonthsFrFull         = array('JANVIER','FÉVRIER','MARS','AVRIL','MAI','JUIN','JUILLET','AOÛT','SEPTEMBRE','OCTOBRE','NOVEMBRE','DÉCEMBRE');
$ArrMonthsPl             = array('STY','LUT','MRZ','KWI','MAJ','CZE','LIP','SIE','WRZ','PAŹ','LIS','GRU');
$ArrMonthsPlFull         = array('STYCZEŃ','LUTY','MARZEC','KWIECIEŃ','MAJ','CZERWIEC','LIPIEC','SIERPIEŃ','WRZESIEŃ','PAŹDZIERNIK','LISTOPAD','GRUDZIEŃ');
$ArrMonthsNum            = array('01','02','03','04','05','06','07','08','09','10','11','12');
$ArrDateTags             = array('ABT','AFT','AND','BEF','BET','CAL','EST','FROM','INT','TO','BCE','OR');
$ArrRevolutionMonths     = array('VEND','BRUM','FRIM','NIVO','PLUV','VENT','GERM','FLOR','PRAI','MESS','THER','FRUC');
$ArrRevolutionMonthsShor = array('VEN','BRU','FRI','NIV','PLU','VEN','GER','FLO','PRA','MES','THE','FRU');
$ArrRevolutionMonthsFull = array('VENDEMIAIRE','BRUMAIRE','FRIMAIRE','NIVOSE','PLUVIOSE','VENTOSE','GERMINAL','FLORIAL','PRAIRIAL','MESSIDOR','THERMIDOR','FRUCTIDOR');
$ArrRevolutionMonthsNum  = array(1,2,3,4,5,6,7,8,9,10,11,12);
$ArrRevolutionYearsChar  = array('I','II','III','IV','V','VI','VII','VIII','IX','X','XI','XII','XIII','XIV');
$ArrRevolutionYearsFull1 = array('AN1','AN2','AN3','AN4','AN5','AN6','AN7','AN8','AN9','AN10','AN11','AN12','AN13','AN14');
$ArrRevolutionYearsFull2 = array('ANI','ANII','ANIII','ANIV','ANV','ANVI','ANVII','ANVIII','ANIX','ANX','ANXI','ANXII','ANXIII','ANXIV');
$ArrDateCharFlagAnd      = array('AND','TO','OR','BIS','OU');
$ArrDateCharAfter        = array('>','&GT;');
$ArrDateCharBefore       = array('<','&LT;');
$ArrDateApproxChar       = array('/','(',')','.',',','@','?');
$ArrDateLogExclusion     = array('YES','ADOPTED','BIC','BORN IN COVENANT','BROWN','CHILD','COMMON LAW','CREMATED','DEAD','DECEASED','DIED','DIVORCED','GODPARENTS'
                                ,'INCONNU','INFANT','INFANCY','NATURALIZED','ONBEKEND','PRIVATE','RES','RESIDED','SUBMITTED','TWIN','UNKNOWN','UNK','UNCLEARED');

  echo '
  <tr><td align=center><br>'.$got_lang['ChGed'].' <b>'.$_REQUEST['ibase'].'.'.$_REQUEST["ext"].'</b></td></tr>
  <tr><td id="message" align=center style="color:red;"><br>'.$got_lang['ProIn'].'</td></tr>
  <tr><td align=center><progress id="myBar" value='.$_REQUEST["progress"].' max="100"></progress><span id=valueProgress>'.$_REQUEST["progress"].'%</span></td></tr>
  ';
?>
  <script>
  function updateMyBar(val) 
  {
    const barre = document.getElementById('myBar');
    const texte = document.getElementById('valueProgress');
    barre.value = val;
    texte.textContent = val + '%';
  }
  </script>

<?php

  // testing opening gedcom file
  $CheminFichier = 'gedcom/'.$_REQUEST["ibase"].'.'.$_REQUEST["ext"];

  $F = @fopen($CheminFichier,"r");
  if ($F == FALSE) 
  {   echo '
      <b>'.$_REQUEST['ibase'].' ?</b> ->'.$got_lang['PasFi'].'
      <br><br><a class=menu_td href=admin.php'.$url.'>Return</a>';
      return;
  }

  // log file initialization
  if (!is_dir("log")) {mkdir ("log");}
  $logFile = "log/".$_REQUEST["ibase"].".log";
  $warnFile = "log/".$_REQUEST["ibase"]."_w.log";

  // Pictures et thumbs directories initialization
  if (!is_dir("picture")) {mkdir ("picture");}
  chdir("picture");
  if (!is_dir($_REQUEST["ibase"]))   {    mkdir ($_REQUEST["ibase"]); }
  chdir ($_REQUEST["ibase"]);
  if (!is_dir('thumbs')) {mkdir ('thumbs');}
  chdir ('..');
  chdir ('..');

  // SQL tables initialization
  if ($_REQUEST['irow'] == 0 AND $_REQUEST['imap'] == 0)  // test pour ne pas droper les bases quand chargement par intervalle de 30 secondes 
  { $query = "DROP TABLE IF EXISTS `".$sql_pref."_".$_REQUEST['ibase']."_individu`" ;
    sql_exec($query,0);

    $query = "CREATE TABLE `".$sql_pref."_".$_REQUEST['ibase']."_individu` 
    (id_indi       int NOT NULL default 0
    ,nom           varchar(32)
    ,prenom1       varchar(32)
    ,prenom2       varchar(32)
    ,prenom3       varchar(32)
    ,sexe          varchar(1)
    ,profession    varchar(42)
    ,date_naiss    varchar(32)
    ,lieu_naiss    varchar(100)
    ,dept_naiss    varchar(100)
    ,date_deces    varchar(32)
    ,lieu_deces    varchar(100)
    ,dept_deces    varchar(100)
    ,note_indi     text
    ,id_pere       int
    ,id_mere       int
    ,sosa_dyn      bigint
	,name2         varchar(32)
	,religion      varchar(32)
    ,PRIMARY KEY   id_indi_PK (id_indi)
    ,KEY           id_pere_FK (id_pere)
    ,KEY           id_mere_FK (id_mere)
    #,KEY           nom        (nom)
    #,KEY           prenom     (prenom1)
    ) "." engine = myisam default ".$collate."_general_ci";
    sql_exec($query);
    
    $query = "DROP TABLE IF EXISTS `".$sql_pref."_".$_REQUEST['ibase']."_evenement`" ;
    sql_exec($query);
    
    $query = "CREATE TABLE `".$sql_pref."_".$_REQUEST['ibase']."_evenement` 
	(id_indi       int          not NULL default 0
    ,id_husb       int          not NULL default 0
    ,id_wife       int          not NULL default 0
    ,type_evene    varchar(4)   NOT NULL
    ,date_evene    varchar(32)
    ,lieu_evene    varchar(100)
    ,dept_evene    varchar(100)
    ,note_evene    varchar(15691)  /* varchar (no TEXT), cause performance : we need to an index during the upload. Maximum length with mysql 5.1 and utf8. characters */
    ,filedate      datetime
    ,filelarg      smallint
    ,filehaut      smallint
    ,region_evene  varchar(100)
    ,country_evene varchar(100)
    ,subdiv_evene  varchar(200)
    ,postal_evene  varchar(10)
	,titl          varchar(32)
    ,KEY           id_indi_FK   (id_indi)
    ,KEY           id_husb_FK   (id_husb)
    ,KEY           id_wife_FK   (id_wife)
    ,KEY           type         (type_evene)       /* recup_media (where type_evene = FILE) et recup_noces (where type_evene = MAR%) */
    ,KEY           lieu         (lieu_evene)       /* search */
	,KEY           note         (note_evene)       /* update filename WHERE note_evene = id_objet (big gain during upload) */
	,KEY           subdiv       (subdiv_evene)     /* update lieu_evene from got_1_place */
	)"." engine = myisam default ".$collate."_general_ci";
    sql_exec($query);
    
    $query = "DROP TABLE IF EXISTS `".$sql_pref."_".$_REQUEST['ibase']."_source`" ;
    sql_exec($query);
    
    $query = "CREATE TABLE `".$sql_pref."_".$_REQUEST['ibase']."_source` 
	(id_sour      int NOT NULL default 0
	,note_source  mediumtext
	,date_sourc   varchar(32)
	,PRIMARY KEY  id_sour_PK (id_sour)
	) "." engine = myisam default ".$collate."_general_ci";
    sql_exec($query);
    
    $query = "DROP TABLE IF EXISTS `".$sql_pref."_".$_REQUEST['ibase']."_even_sour`" ; // relation n-n entre evenement et source
        sql_exec($query);
    
    $query = "CREATE TABLE `".$sql_pref."_".$_REQUEST['ibase']."_even_sour` 
	(id_indi      int          not NULL default 0
    ,id_husb      int          NOT NULL default 0
    ,id_wife      int          not NULL default 0
    ,type_evene   varchar(4)   NOT NULL
    ,date_evene   varchar(32)  
    ,id_sour      int          NOT NULL default 0
    ,type_sourc   varchar(4)   NOT NULL
    ,attr_sourc   varchar(206) ".$collate."_bin  # 206 Max size to Primary key for MySql 9.1
    ,filedate     datetime
    ,filelarg     smallint
    ,filehaut     smallint
    ,titl         varchar(100)
    ,PRIMARY KEY (id_indi,id_husb,id_wife,type_evene,date_evene,id_sour,type_sourc,attr_sourc)
    ,KEY          id_indi_FK (id_indi)
    ,KEY          id_husb_FK (id_husb)
    ,KEY          id_wife_FK (id_wife)
    ,KEY          id_sour_FK (id_sour)
    ,KEY          type_evene (type_evene)                     # recup_media where type_evene = FILE
    ,KEY          indi_even  (id_indi,type_evene)             # evenement.php this_evenement
    ,KEY          husb_wife_even (id_husb,id_wife,type_evene) # evenement.php this_evenement
    ,KEY          attr_sourc (attr_sourc)                     # update filename WHERE attr_sour = id_objet (big gain during upload) 
    ) "." engine = myisam default ".$collate."_general_ci";
    sql_exec($query);
    // type_sourc = SOUR, RELA
    // attr_sourc = Code Witness

    $query = "DROP TABLE IF EXISTS `".$sql_pref."_".$_REQUEST['ibase']."_note`" ;
    sql_exec($query,0);
    
    $query = "CREATE TABLE `".$sql_pref."_".$_REQUEST['ibase']."_note` 
    (id_note      int          NOT NULL default 0
    ,id_indi      int          NOT NULL default 0
    ) "." engine = myisam default ".$collate."_general_ci";
    sql_exec($query,0);
  
    $query = "DROP TABLE IF EXISTS `".$sql_pref."_".$_REQUEST['ibase']."_id_indi`" ;
    sql_exec($query);
  
    $query = "CREATE TABLE `".$sql_pref."_".$_REQUEST['ibase']."_id_indi`
    (id            bigint       NOT NULL default 0
    ,sql_id        int UNIQUE   NOT NULL default 0
    ,PRIMARY KEY PK_id_indi  (id)
    ) "." engine = myisam default ".$collate."_general_ci";
    sql_exec($query,0);
  
    $query = "DROP TABLE IF EXISTS `".$sql_pref."_".$_REQUEST['ibase']."_id_sour`" ;
    sql_exec($query);
  
    $query = "CREATE TABLE `".$sql_pref."_".$_REQUEST['ibase']."_id_sour`
    (id            bigint     NOT NULL default 0
    ,sql_id        int UNIQUE NOT NULL default 0
    ,PRIMARY KEY PK_id_sour (id)
    ) "." engine = myisam default ".$collate."_general_ci";
    sql_exec($query,0);
  
    $query = "DROP TABLE IF EXISTS `".$sql_pref."_".$_REQUEST['ibase']."_place`" ;
    sql_exec($query);
  
    $query = "CREATE TABLE `".$sql_pref."_".$_REQUEST['ibase']."_place`
    (place         varchar(200) UNIQUE
    ,noLine        int
    ) "." engine = myisam default ".$collate."_general_ci";
    sql_exec($query,0);

    // Warning and log file creation
    file_put_contents($logFile, "");
    file_put_contents($warnFile, "");
  }

// BEGIN OF BLOCK 1
  if ($_REQUEST["imap"] == 0)
  {
    // tags which participate to place description, complete tag PLAC
    $ADDRStructure = array(
	 'ADDR'  // main
	,'ADR1'  // complément ADDR adresse beerten,badrenov équivalent subdivision
	,'ADR2'  // complément ADDR adresse beerten, équivalent subdivision
	,'ADR3'  // complément ADDR adresse beerten, équivalent subdivision
	,'CITY'  // complément PLAC important pour aider Geoapify.
	,'STAE'  // STATE. complément PLAC important pour aider Geoapify.
	,'POST'  // STATE. complément PLAC important pour aider Geoapify.
	,'CTRY'  // COUNTRY, complément PLAC important pour aider Geoapify.
	,'COUNT' // COUNTRY, complément PLAC important pour aider Geoapify.
	,'LIEU'  // uniquement de_saint_denis ! équivalent subdivision
	,'CODE'  // uniquement de_saint_denis ! CODE POSTAL
	,'DEPT'  // uniquement de_saint_denis ! département
	,'ADR'   // uniquement de_saint_denis ! adresse
	,'REG'   // uniquement de_saint_denis ! région
    );

    // tags which are not managed, but which not appear in logs.
    $noLogTags = array (
	 'ABBR'  // abbréviation, toujours en doublon avec PLAC, inutile. NE PAS FAIRE APPARAITRE DANS LE LOG.
    ,'AFN'   // Ancestral File Number. Useless.
	,'BAPT'  // BAPTIST specific tag turss
	,'CALN'  // CALL NUMBER, The number used by a repository to identify the specific items in its collections. lié à un repository, caulley, poulain_b
	         //  souvent repris à blanc. Pas très intéressant. NE PAS FAIRE APPARAITRE DANS LE LOG pour ne pas polluer le log.
    ,'CHAN'  // CHANGE useless
    ,'DESC'  // DESCRIPTION useless
	,'ENABL' // specific tag dominguez
    ,'EVEN'  // 2 EVEN Smart matching, discovery, record, ...
    ,'Fact'  // 1 Fact specific tag beerten
	,'FUNE'  // FUNERALS specific tag turss
	,'GENDE' // GENDER specific tag turss
    ,'HYPER' // HYPERLINK specific tag dominguez
    ,'IDNO'  // NATIONAL ID NUMBER useless
    ,'LABL'  // LABEL useless
    ,'LABEL' // LABEL useless
    ,'LINE'  // specific tag turrs
    ,'MEDI'  // 3 MEDIA : internet, archive, ...
	,'MPRF'  // specific tab ponsonnet
    ,'PLAC'  // 1 PLAC pointe toujours sur "0 _PLAC_DEFN". PLACE DEFINITION. Useless. Redundant.
    ,'PRTY'  // specific tag whittaker
    ,'REPO'  // 1 REPOSITORY about level 0 SOUR
    ,'Refer' // 1 Reference specific tag beerten
	,'REFN'  // REFERENCE, A description or number used to identify an item for filing, storage, or other reference purposes. pas d'intérêt
    ,'RESN'  // RESTRICTION PRIVACY
	,'RFN'   // REFERENCE, A description or number used to identify an item for filing, storage, or other reference purposes. pas d'intérêt
	,'REFE'  // REFERENCE, A description or number used to identify an item for filing, storage, or other reference purposes. pas d'intérêt
    ,'RELAT' // RELATION specific tag dominguez
	,'RIN'   // AUTOMATED_RECORD_ID useless
    ,'STAT'  // STATUS useless
	,'TIME'  // useless
	,'UNION' // specific tag dominguez
	,'FORM'  // FORMAT, désigne le format de PLAC. Dans le langue du pays et inutile pour Geoapify.
	,'QUAY'  // QUALITY OF DATA, always a number 1,2,3.... No interest.
	,'DISPL' // DISPLAY, always duplicaite with NAME content
    ,'SURE'  // specific tag ponsonnet
	,'UGNM'  // specific tag ponsonnet
	,'UTYP'  // specific tag ponsonnet
	,'WWW'   // useless
    // address tags
	,'POST'  // code postal, beerten
	,'MAIL'  // oui, uniquement de_saint_denis ! pas d'intérêt
	,'EMAIL' // oui, pas d'intérêt
	,'PHON'  // oui, ne sert que dans l'entête du gedcom
	,'TEL'   // oui, uniquement de_saint_denis ! pas d'intérêt
    // pictures tags, no interest
	,'DIMEN' // DIMENSION des imagesavealpha
	,'MEGAP' // MEGAPIXELS
	,'BPP'   // byte per pixel
	,'DPI'   // dot per pixel
	,'COLOR' // COLOR
	,'BORDE' // BORDER
	,'PRIMA' // PRIMARY name
	,'OUTLI' // OUTLINE
	,'FILL'  // #FFFFFF
	,'POSI'  // POSITION
	,'PICT'  // PICTURE
	,'MAP'   // oui, toujours vide
	,'BOUND' // oui, position géographique boundaryrect
	,'TOP'   // oui, balise tjs vide
	,'LEFT'  // oui, balise tjs vide
	,'RIGHT' // oui, balise tjs vide
	,'BOTTO' // oui, balise tjs vide
	,'GENOM' // oui, GENOMAP inutile avec Geoapify
	,'BOUND' // oui, BOUNDARYRECT inutile avec Geoapify
	,'SIZE'  // oui, inutile avec Geoapify
	,'LATI'  // oui, remplacer par API Geoapify
	,'LONG'  // oui, remplacer par API Geoapify
	,'POINT' // oui, inutile avec Geoapify
    );

    $tagsAlreadyProcessed = array (
     'ADOP'
    ,'INDI'
    ,'BAPM'
    ,'BIRT'
    ,'BURI'
    ,'CENS'
	,'CONF'
    ,'CHR'
    ,'DEAT'
	,'DSCR'
	,'EDUC'
	,'EMIG'
	,'FILE'
	,'FAMC'
	,'FAMS'
	,'FAMI'
	,'FAMIL'
    ,'IMMI'
	,'ISDE'  // ISDEAD
	,'MARB'
	,'MARC'
	,'MARS'
	,'MARL'
	,'NATI'
	,'NCHI'
    ,'OBJE'
	,'HUSB'  // Vérifier 2 HUSB
	,'WIFE'  // Vérifier 2 WIFE
	,'PLAC'
	,'RESI'
	,'SIGN'
	,'SSN'
	,'SOUR'
    );

    // array language ISO 639-1
    $tabLanguage = array (
    'chinese','zulu','deutsch','français','portuguese_brazil',
    'afar','abkhazian','avestan','afrikaans','akan','amharic','aragonese','arabic','assamese','avaric',
    'aymara','azerbaijani','bashkir','belarusian','bulgarian','bihari','bislama','bambara','bengali','tibetan',
    'breton','bosnian','catalan','chechen','chamorro','corsican','cree','czech','old church slavonic','chuvash',
    'welsh','danish','german','divehi','dzongkha','ewe','greek','english','esperanto','spanish',
    'estonian','basque','persian','fulah','finnish','fijian','faroese','french','western frisian','irish',
	
    'scottish gaelic','galician','guarani','gujarati','manx','hausa','hebrew','hindi','hiri motu','croatian',
    'haitian','hungarian','armenian','herero','interlingua','indonesian','interlingue','igbo','sichuan yi','inupiaq',
    'ido','icelandic','italian','inuktitut','japanese','javanese','georgian','kongo','kikuyu','kwanyama',
    'kazakh','greenlandic','khmer','kannada','korean','kanuri','kashmiri','kurdish','komi','cornish',
    'kirghiz','latin','luxembourgish','ganda','limburgish','lingala','lao','lithuanian','luba','latvian',
	
    'malagasy','marshallese','māori','macedonian','malayalam','mongolian','moldavian','marathi','malay','maltese',
    'burmese','nauru','norwegian bokmål','north ndebele','nepali','ndonga','dutch','norwegian nynorsk','norwegian','south ndebele',
    'navajo','chichewa','occitan','ojibwa','oromo','oriya','ossetian','panjabi','pāli','polish',
    'pashto','portuguese','quechua','romansh','kirundi','romanian','russian','kinyarwanda','sanskrit','sardinian',
    'sindhi','northern sami','sango','serbo-croatian','sinhalese','slovak','slovenian','samoan','shona','somali',
	
    'albanian','serbian','swati','sotho','sundanese','swedish','swahili','tamil','telugu','tajik',
    'thai','tigrinya','turkmen','tagalog','tswana','tonga','turkish','tsonga','tatar','twi',
    'tahitian','uighur','ukrainian','urdu','uzbek','venda','vietnamese','volapük','walloon','wolof',
    'xhosa','yiddish','yoruba','zhuang',
    'azeri','farsi','kyrgyz','slovene'  // specific textDetectLanguage library
    );
    
    $tabLang = array (
    'zh','zu','de','fr','pt',
    'aa','ab','ae','af','ak','am','an','ar','as','av',
    'ay','az','ba','be','bg','bh','bi','bm','bn','bo',
    'br','bs','ca','ce','ch','co','cr','cs','cu','cv',
    'cy','da','de','dv','dz','ee','el','en','eo','es',
    'et','eu','fa','ff','fi','fj','fo','fr','fy','ga',
    
    'gd','gl','gn','gu','gv','ha','he','hi','ho','hr',
    'ht','hu','hy','hz','ia','id','ie','ig','ii','ik',
    'io','is','it','iu','ja','jv','ka','kg','ki','kj',
    'kk','kl','km','kn','ko','kr','ks','ku','kv','kw',
    'ky','la','lb','lg','li','ln','lo','lt','lu','lv',
    
    'mg','mh','mi','mk','ml','mn','mo','mr','ms','mt',
    'my','na','nb','nd','ne','ng','nl','nn','no','nr',
    'nv','ny','oc','oj','om','or','os','pa','pi','pl',
    'ps','pt','qu','rm','rn','ro','ru','rw','sa','sc',
    'sd','se','sg','sh','si','sk','sl','sm','sn','so',
    
    'sq','sr','ss','st','su','sv','sw','ta','te','tg',
    'th','ti','tk','tl','tn','to','tr','ts','tt','tw',
    'ty','ug','uk','ur','uz','ve','vi','vo','wa','wo',
    'xh','yi','yo','za',
    'az','fa','ky','sl'
    );

    $level1NotInEvene = array ('CHAN','CHIL','FAMC','FAMS','HUSB','NAME','NOTE','WIFE','SEX','REFN','RFN');

    $ansel = Array
    ('âe','áe','ãe','èe','âo','áo','ão','èo','âa','áa','ãa','èa','âu','áu','ãu','èu','âi','ái','ãi','èi','ây','èy','ðc','~n',
     'âE','áE','ãE','èE','âO','áO','ãO','èO','âA','áA','ãA','èA','âU','áU','ãU','èU','âI','áI','ãI','èI','âY','èY','ðC','~N'
    );
    $ansel = array_map(function($v) {return mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8'); }, $ansel);

    $ansi = Array
    ('é', 'è', 'ê', 'ë', 'ó', 'ò', 'ô', 'ö', 'á', 'à', 'â', 'ä', 'ú', 'ù', 'û', 'ü', 'í', 'ì', 'î', 'ï', 'ý', 'ÿ', 'ç', 'ñ',
     'É', 'È', 'Ê', 'Ë', 'Ó', 'Ò', 'Ô', 'Ö', 'Á', 'À', 'Â', 'Ä', 'Ú', 'Ù', 'Û', 'Ü', 'Í', 'Ì', 'Î', 'Ï', 'Ý', 'Ÿ', 'Ç', 'Ñ'
    );
    $ansi = array_map(function($v) {return mb_convert_encoding($v, 'ISO-8859-1', 'UTF-8'); }, $ansi);

  // variables initialization
    $id_indi = ""; $id_fam = ""; $id_source = ""; $id_note = ""; $id_sour = "";
    $id_husb = ""; $id_wife = ""; $note_indi = ""; $note_sour = ""; $date_sourc =''; $note_evene = '';
    $nom = ""; $name2 = "";$prenoms[0] = "";$prenoms[1] = "";$prenoms[2] = ""; $sexe = ""; $profession = ""; 
	$no_ligne      = 0;
    $NbPlaces      = 0; $NbLines = 0; $counts = array(); 
    $i             = 0; 
    $i_niv0        = 0; 
    $id_obje       = "";
	$ageEvent      = "";
	$religion      = "";
    $GotEncoding   = ''; 
    $FlagANSEL     = 0;
    $flag1PLAC     = false; $flag2PLAC = false; $flagFORMPLAC = false;
    $progress      = 0;
    $posCITY = ''; $posDEPARTMENT = ''; $posREGION = ''; $posCOUNTRY = ''; $posSUBDIV = ''; $posPOSTALCODE = '';
    $supportedGeoapifyLanguages = array('af','am','ar','az','bg','bn','bs','ca','cs','da','de','el','en','es','et','eu','fa','fi','fil','fr','gl','gu','hi','hr','hu','hy','id','is','it','iw','ja','ka','kk','km','kn','ko','ky','lo','lt','lv','mk','ml','mn','mr','ms','my','ne','nl','no','pa','pl','pt','ro','ru','sk','sl','sq','sr','sv','sw','ta','te','th','tr','uk','ur','uz','vi','zh','zu'); 
    $lieu_evene[0] = ""; $lieu_evene[1] = "";
    $gedcomLang    = "";
    $SUBDIV = ''; $TOWN = ''; $DEPARTMENT = ''; $POSTAL = ''; $COUNTRY = ''; $REGION = '';
    if (!isset($_REQUEST['gedlang'])) $_REQUEST['gedlang'] = '';
    if (!isset($_REQUEST['time']) OR $_REQUEST['time'] == NULL) $_REQUEST['time'] = 0;

    /* Creation of Sql identifiers
		"id_indi" and "id_sour" table initialization
		Principle:
		We initialize a temporary table of correspondence between id gedcom versus Hash and id versus sql
		Ex: let's say 2 lines gedcom "0 @I1HKC-J3@ SOUR" and "0 @I1HKC-J5@ SOUR"
		  We extract the ids "I1HKC-J3" and "I1HKC-J5"
		  We transform the ids into hashes "12345678954" and "32165498127"
		  Then, we assign a chronological number for each id found
		  Result in the temporary SQL table:
			 bigint     int
		  12345678954 => 1
		  32165498127 => 2
		While reading the gedcom, we transform all the ids found into hashes
		then we retrieve in the temporary SQL table the SQL identifier that we must use for SQL updates

		N.B: the transformation into hash is done only for performance reasons.
		  When you create a varchar instead of a bigint, the upload performance is significantly degraded
		  Bigint in primary key in the sql table is efficient.
	*/
    while ($line = fgets ($F))
    {
      if (substr($line,0,1) == '0' AND (@mb_substr(trim($line),-5) == ' INDI' OR @mb_substr(trim($line),-10) == 'INDIVIDUAL') 
          AND $_REQUEST['irow'] == 0)
      { $query = 'SELECT MAX(sql_id) FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_id_indi` ';
        $result = sql_exec($query,0);
        $row = sql_fetch_row($result);
    
        $sql_id = $row[0] + 1;
        $query = 'INSERT INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_id_indi` VALUES ('.MyHash(GetId($line)).','.$sql_id.') ';
        sql_exec($query,0);
      }
    
      if (substr($line,0,1) == '0' AND (@mb_substr(trim($line),-5) == ' SOUR' OR @mb_strpos($line, 'SOUR'.chr(13)) != NULL) 
          AND $_REQUEST['irow'] == 0 )
      { $query = 'SELECT MAX(sql_id) FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_id_sour` ';
        $result = sql_exec($query,0);
        $row = sql_fetch_row($result);
    
        $sql_id = $row[0] + 1;
        $query = 'INSERT INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_id_sour` VALUES ('.MyHash(GetId($line)).','.$sql_id.') ';
        sql_exec($query,0);
      }

      $NbLines++;

      if (@substr($line,0,6) == '2 PLAC')
      { 
        if (!isset($counts[$line])) 
        { $counts[$line] = 0;
        }
        $counts[$line]++;
	  }
    }
    rewind($F);

    $NbPlaces = count($counts);  
    $_REQUEST['nblines'] = $NbLines;

    //**** BEGIN OF GEDCOM HEADER ANALYSE ***/

    // skip first line
    fgets ($F);
  
    // find the first line with 0 INDI.
    while ($line = fgets ($F))
    { 
      // MAC file detect
      if (strpos($line, "\r") !== false && strpos($line, "\n") === false) 
      {  $FlagMacFile = TRUE;
      } else 
      {  $FlagMacFile = FALSE;
      }

      // 1 SOUR HEAD (software/editor)
      if ( substr($line,0,6) == '1 SOUR' OR substr($line,0,6) == '2 NAME' )
      { $_REQUEST['logi'] = trim(@mb_substr($line,7,42));
      }

      // 2 CORP
      if (@substr($line,0,6) == '2 CORP')
      { $_REQUEST['edi'] = trim(@mb_substr($line,7,42));
      }

      // 1 LANG
      if (@substr($line,0,6) == '1 LANG')
      {
        $_REQUEST['gedlang'] = mb_convert_encoding (trim(@mb_substr(strtolower($line),7,42)),'UTF-8','Windows-1252');
      }

      /********* BEGIN CHARSET ENCODING ANALYSE ***************
      The goal is to correctly convert the characters in a gedcom file.
      If the characters are encoded in UTF-8, nothing is done.
      If the characters are encoded in ANSI, the conversion is performed using the mb_convert_encoding function with the Windows-1252 parameter, which is the only one that works.
      
      Algorithm to determine UTF-8 or ANSI:
        If gedcom describes characters encoding (95% of cases with 7 known values: ANSI, ASCII, UTF-8, ANSEL, Windows-1251, IBMPC, IBM Windows):
          If (UTF-8) $GotEncoding = 'UTF-8'
          else       $GotEncoding = 'ANSI'    (ANSI, ASCII, ANSEL, Windows, ...)
        else $GotEncoding = 'UTF-8'
  
      At the end, geneotree must know 2 values : UTF-8, ANSI (don't manage Japanese at this time).
      */
  
      // GotEncoding  
      if (substr($line,0,6) == '1 CHAR') 
      { if (substr($line,7,5) == 'UTF-8')
          $GotEncoding = 'UTF-8'; 
        else  
        { $GotEncoding = 'ANSI';
          if (substr($line,7,5) == 'ANSEL') 
            $FlagANSEL = 1;
        }
      }

      if (@substr($line,0,6) == '1 PLAC')
        $flag1PLAC = true;

      if (@substr($line,0,6) == '2 FORM' AND $flag1PLAC)
      { 
        $flagFORMPLAC = true;

        // convert in ANSEL character to detect places tags
        if ($FlagANSEL)
          $line = str_replace($ansel, $ansi, $line);

        // convert in UTF-8 if ANSI characters
        if ($GotEncoding == 'ANSI')
          $line = mb_convert_encoding($line, 'UTF-8', 'Windows-1252');

        $line = fnNormalizeString($line);
        $formatPLAC = substr($line,7);
        // $formatPLAC = str_replace(' ','',$formatPLAC);
        $arrFormatPLAC = explode(',',$formatPLAC);
// echo '<br>'.$line;
// echo count($arrFormatPLAC);
// print_r2($arrFormatPLAC);
        for ($i=0; $i < count($arrFormatPLAC); $i++)
        {
           if (strpos($arrFormatPLAC[$i],'city')      !== false
           OR strpos($arrFormatPLAC[$i],'ville')      !== false
           OR strpos($arrFormatPLAC[$i],'town')       !== false
           OR strpos($arrFormatPLAC[$i],'commune')    !== false
           OR strpos($arrFormatPLAC[$i],'lieu dit')   !== false) $posCITY       = $i;

          elseif (strpos($arrFormatPLAC[$i],'code')   !== false
           OR strpos($arrFormatPLAC[$i],'postal')     !== false) $posPOSTALCODE = $i;

          elseif (strpos($arrFormatPLAC[$i],'county') !== false
           OR strpos($arrFormatPLAC[$i],'dep')        !== false) $posDEPARTMENT = $i;

          elseif (strpos($arrFormatPLAC[$i],'region') !== false) $posREGION     = $i;

          elseif (strpos($arrFormatPLAC[$i],'country')!== false
           OR strpos($arrFormatPLAC[$i],'pays')       !== false) $posCOUNTRY    = $i;

          elseif (strpos($arrFormatPLAC[$i],'subdivision')!== false
           OR strpos($arrFormatPLAC[$i],'street')     !== false
           OR strpos($arrFormatPLAC[$i],'lieudit')    !== false) $posSUBDIV     = $i;
        }

        $flag1PLAC = false;
// echo '<br>'.$posCITY.' | '.$posDEPARTMENT.' | '.$posREGION.' | '.$posCOUNTRY.' | '.$posSUBDIV.' | '.$posPOSTALCODE;
      }

    // detect end of header
      if (@mb_substr(trim($line),-4) == 'INDI' OR @mb_substr(trim($line),-10) == 'INDIVIDUAL')
      { break;
      }

      $no_ligne++;
    }
    // rewind($F);

    if (empty($GotEncoding))
      $GotEncoding = 'UTF-8'; 

    $_REQUEST["encod"] = $GotEncoding;
    $_REQUEST["ansel"] = $FlagANSEL;

    /******* END OF GEDCOM HEADER ANALYSE **************/
  
    // Convert MAC gedcom file to UNIX gedcom file   (ex : _fleege.ged)
    if ($FlagMacFile)
    { $TF = fopen("TempFile.txt", "w");
      while (($line = fgets($F)) !== false) 
  	  { // Remplace les \r par \n pour uniformiser
        $line = str_replace("\r", "\r\n", $line);
  	    fwrite($TF, $line);
      }
  	  fclose($TF);
      fclose($F);
      rename("TempFile.txt", $CheminFichier);
      $F = @fopen($CheminFichier,"r");
    }

    /******* BEGIN OF DETECTING LANGUAGES ***************/

    $langChoice = array(); $text = ''; $arrText = array(); $langDetect = '';
    if (!empty($_REQUEST['gedlangForm']))
      $_REQUEST['gedlang'] = $_REQUEST['gedlangForm'];
    else
    {
      // detect language characters in gedcom file
      $cptPLAC=0; $cptNAME=0; $cptCONC=0; $cptPAGE=0; $cptTEXT=0; $cptNOTE=0; $cptABBR=0; $cptOCCU=0;
      $NbLinesText = 50;
      while ($line = fgets ($F, 2048))
      { 
        // convert ANSEL characters (source Convansel)
        if ($FlagANSEL == 1) 
            $line = str_replace($ansel, $ansi, $line);
	  
        // convert in UTF-8 if ANSI characters
        if ($GotEncoding == 'ANSI')
          $line = mb_convert_encoding($line, 'UTF-8', 'Windows-1252');
	  
        // business code
        if (substr($line,2,4) == "PLAC" AND $cptPLAC < $NbLinesText)
        { $arrText[mb_substr($line,7)] = true;
          $cptPLAC++;
        }
        elseif (substr($line,2,4) == "NAME" AND $cptNAME < $NbLinesText)
        { $arrText[mb_substr($line,7)] = true;
          $cptNAME++;
        }
        elseif (substr($line,2,4) == "CONC" AND $cptCONC < $NbLinesText)
        { $arrText[mb_substr($line,7)] = true;
          $cptCONC++;
        }
        elseif (substr($line,2,4) == "PAGE" AND $cptPAGE < $NbLinesText)
        { $arrText[mb_substr($line,7)] = true;
          $cptPAGE++;
        }
        elseif (substr($line,2,4) == "TEXT" AND $cptTEXT < $NbLinesText)
        { $arrText[mb_substr($line,7)] = true;
          $cptTEXT++;
        }
        elseif (substr($line,2,4) == "NOTE" AND $cptNOTE < $NbLinesText)
        { $arrText[mb_substr($line,7)] = true;
          $cptNOTE++;
        }
        elseif (substr($line,2,4) == "ABBR" AND $cptABBR < $NbLinesText)
        { $arrText[mb_substr($line,7)] = true;
          $cptABBR++;
        }
        elseif (substr($line,2,4) == "OCCU" AND $cptOCCU < $NbLinesText)
        { $arrText[mb_substr($line,7)] = true;
          $cptOCCU++;
        }
      }
      $arrKeys = array_keys($arrText);
      $text = implode(',', $arrKeys);
      $langDetect = fnLanguageDetect($text);
// echo 'LANG: '.$_REQUEST['gedlang'].'<br> Detect: '.$langDetect;
      if ( (!empty($_REQUEST['gedlang']) AND $_REQUEST['gedlang'] == $langDetect)
        OR ( empty($_REQUEST['gedlang']) AND $langDetect !== 'italian')
         )
        $_REQUEST['gedlang'] = trim($langDetect);
      else
      {
        // detect which languages ​​to choose
        $langDetect = fnLanguageDetect($text, true);
        if (isset($_REQUEST['gedlang']))
          $langDetect[$_REQUEST['gedlang'] ] = 1;
        foreach ($langDetect as $key => $value)
        { $langChoice[] = $key;
        }

        // Form to choice language
        echo '
        <form method="POST">
        <tr><td align=center><br>';
        echo $got_lang['DetLa'].'</td></tr>';
        foreach ($langChoice as $gedlang) 
        {
          echo "<tr><td align=center><input type='radio' name='gedlangForm' value='".$gedlang."' required>".$gedlang."</td></tr>";
        }
        echo "<input type='hidden' name='base' value='".$_REQUEST['ibase']."'>";
        echo "<input type='hidden' name='pag'  value=chg>";
        echo '<tr><td align=center><input type="submit" value="OK"></td></tr>';
        echo '</form>';
        return;
      }
    }

    if ($_REQUEST['gedlang'])
      $_REQUEST['gedlang'] = str_replace($tabLanguage, $tabLang, $_REQUEST['gedlang']);
    else
      $_REQUEST['gedlang'] = 'en';

    rewind($F);
// echo '<br>'.$_REQUEST['gedlang'].' | '.$_REQUEST['logi'].' | '.$langDetect;

    /******* END OF DETECTING LANGUAGES ***************/
  
    if ($_REQUEST['irow'] != 0)   // pour lire le 0 HEAD
    { $no_ligne = 2;
      while ($no_ligne <= $_REQUEST['irow']) 
      { $line = fgets ($F);
        $no_ligne = $no_ligne + 1;
      }
    } else
    // on avance le pointeur jusqu'au 1er individu
    { for ($ii=0; $ii <= $no_ligne; $ii++)   // hyper pointu. Il ne faut arriver pile sur le 1er 0 INDI
      { fgets ($F);
      } 
    }
  
    /************************* BEGIN OF READ GEDCOM LINES ************************/

    /* Gedcom files are organized by level from 0 to n. 
    In the Gedcom 5.5 standard, 8 tags are allowed at level 0:
      - FAM
      - INDI(VIDUAL)
      - MULT(IMEDIA)
      - NOTE
      - OBJE(CT)
      - REPO(SITORY)
      - SOUR(CE)
      - SUBM(ITTER)
    In practice, MULTIMEDIA is NEVER used: i have never encountered one.
    SUBMITTER and REPOSITORY are used very very rarely (~1%), and does not provide any interesting information.
    Also, the Geneotree script manages 5 tags of level 0 : FAM, INDI, NOTE, SOUR and OBJE.
    Levels 1 and above reflect the content of Levels 0. In practice, the levels stop at a maximum of 5. 

    The content of the INDI tag feeds the SQL table "individu".
    The content of the FAM tag feeds the columns 
	- "individu.id_pere" and "individu.id_mere" for relation links
	- "evenement" for marriages
    The content of the NOTE tag feeds the column 
	- "individu.note_indi" for individual notes
	- "evenement.note_evene" for event notes
    The content of the REPO and SOUR tags feeds the table "source"
    The table "even_sour" is used to make the link between the table "evenement" and the table "source".
    
    Finally, the media links are stored in "evenement" for the media of individuals, in "even_sour" for the media of events.
    Media files are stored in file system, directory /picture/{basename}.
    */

    while ($line = fgets ($F))
    {
      // convert ANSEL characters (source Convansel)
      if ($FlagANSEL == 1) 
        $line = str_replace($ansel, $ansi, $line);

      // convert in UTF-8 if ANSI characters
      if ($GotEncoding == 'ANSI')
        $line = mb_convert_encoding($line, 'UTF-8', 'Windows-1252');

// echo '<br>'.$no_ligne.' : '.$line;  // debug
  
    // BEGIN OF LEVELS BREAKS

      if (substr($line,0,1) == 0)
      { $i_niv0++;
        maj_evenement($id_indi,$id_husb,$id_wife,$id_source,true);
  
        // NIV 0 INDI
        if ($id_indi) 
        { 
          if ($note_indi AND (mb_substr($note_indi,0,1) == chr(13) or mb_substr($note_indi,0,1) == chr(10)) )
  	      { $note_indi = mb_substr($note_indi,1,mb_strlen($note_indi));
  	      } 

        $prenom2Sql   = empty($prenoms[1])? 'NULL' : "'".addslashes(mb_substr($prenoms[1],0,32))."'";
        $prenom3Sql   = empty($prenoms[2])? 'NULL' : "'".addslashes(mb_substr($prenoms[2],0,32))."'";
        $occupSql     = empty($profession)? 'NULL' : "'".addslashes(mb_substr($profession,0,42))."'";
        $lieuNaissSql = empty($lieuNaiss) ? 'NULL' : "'".addslashes(mb_substr($lieu_naiss[0],0,100))."'";
        $deptNaissSql = empty($deptNaiss) ? 'NULL' : "'".addslashes(mb_substr($lieu_naiss[1],0,100))."'";
        $lieuDecesSql = empty($lieuDeces) ? 'NULL' : "'".addslashes(mb_substr($lieu_deces[0],0,100))."'";
        $deptDecesSql = empty($deptDeces) ? 'NULL' : "'".addslashes(mb_substr($lieu_deces[1],0,100))."'";
        $name2Sql     = empty($name2)     ? 'NULL' : "'".addslashes(mb_substr($name2,0,32))."'";
        $religionSql  = empty($religion)  ? 'NULL' : "'".addslashes(mb_substr($religion,0,32))."'";
		$noteIndi     = trim(rtf2text($note_indi));
        $noteIndiSql  = empty($noteIndi)  ? 'NULL' : "'".addslashes(mb_substr($noteIndi,0,65436))."'";

        $query = 'INSERT INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` VALUES ("'
            .GetSqlId('id_indi',$id_indi).'"
			,"'.addslashes(mb_substr($nom,0,32)).'"
			,"'.addslashes(mb_substr($prenoms[0],0,32)).'"
			,'.$prenom2Sql.'
			,'.$prenom3Sql.'
			,"'.$sexe.'"
			,'.$occupSql.'
			,NULL               /* date BIRT */
			,'.$lieuNaissSql.'
			,'.$deptNaissSql.'
			,NULL               /* date DEAT */
			,'.$lieuDecesSql.'
			,'.$deptDecesSql.'
			,'.$noteIndiSql.'
			,0                  /* id_pere */
			,0                  /* id_mere */
			,0                  /* sosa_dyn */
			,'.$name2Sql.'
			,'.$religionSql.'
            )
            ';
        sql_exec ($query,0);
  
        $id_indi='';$nom='';$name2='';$prenom='';$profession='';
        $date_naiss='';$date_deces='';
        $lieu_naiss='';$lieu_deces='';
        $note_naiss = '';$note_deces = '';
        $note_indi='';
        $filename = '';
        $level1InProgress = '';
        $religion = "";
        }  

        // NIV 0 FAM
        if ($id_fam OR $id_fam === "0")
        { $id_husb = ''; $id_wife = '';
          $enfants = '';
          $i=0;
          $id_fam = '';
          $level1InProgress = '';
          $filename = '';
          $flagMaria1 = '';$flagMaria2 = '';
        }  

        // NIV 0 NOTE => Seule mise  a jour que l'on ne peut pas le faire au fil de l'eau
        if ($id_note or $id_note === "0") 
        {   $query = 'SELECT id_indi 
                  FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_note`
                  WHERE id_note = '.$id_note;
          $result = sql_exec ($query,0);
          $row = mysqli_fetch_row($result);
  
          if (isset($row[0]))
          {    $query = 'UPDATE `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` 
                      SET `note_indi` = "'.addslashes(mb_substr($note_indi,0,65436)).'"
                      WHERE `id_indi` = '.$row[0];
              sql_exec ($query,0);
          }
          $note_indi = '';
          $id_note = '';
        }  

        // NIV 0 SOUR
        if ($id_sour or $id_sour === "0") 
        { if (mb_substr($note_sour,0,1) == chr(13) or mb_substr($note_sour,0,1) == chr(10)) {$note_sour = mb_substr($note_sour,1,mb_strlen($note_sour));}
          $query = 'INSERT into `'.$sql_pref.'_'.$_REQUEST['ibase'].'_source` VALUES (
		   "'.GetSqlId("id_sour",$id_sour).'"
		  ,"'.addslashes(trim(rtf2text($note_sour))).'"
		  ,"'.$date_sourc.'"
		  )';
          sql_exec ($query,0);
  
          $id_sour = '';
		  $note_sour = '';
		  $date_sourc = '';
          $filename = '';
        } 

        // NIV 0 OBJE to initialize $id_obje and $filename
        if ($id_obje != '' or $id_obje === "0") 
        { $id_obje = '';
          $filename = '';
        } 

        $delay = time() - $T_OLD;
        if ($delay >= $timeout)
        { 

          // update progress pourcentage
          $progress = intval( ($no_ligne) / ($NbLines + 1000*$NbPlaces)  * 100);

          $_REQUEST['time'] = $_REQUEST['time'] + $delay;
          $url = url_request();
          echo "<HTML>";
          echo "<HEAD>";
          echo '<META HTTP-EQUIV="Refresh" CONTENT="1; URL=admin.php'.$url.'&pag=chg&pag2=&ext='.$_REQUEST["ext"].'&encod='.$_REQUEST["encod"].'&ansel='.$_REQUEST["ansel"].'&edi='.urlencode($_REQUEST["edi"]).'&logi='.urlencode($_REQUEST["logi"]).'&irow='.$no_ligne.'&progress='.$progress.'&time='.$_REQUEST["time"].'">';
          echo "</HEAD>";
          echo "<BODY>";
          return;
        }

        // update progress bar
        // $progress = intval(($no_ligne / $NbLines) * 100);
        $progress = intval( ($no_ligne) / ($NbLines + 1000*$NbPlaces)  * 100);
        echo "<script>updateMyBar($progress);</script>\n";
        flush();

// echo '<br>Niv0: '.$no_ligne.' : '.$line;

        // captures of new level 0 identifiers
        if (@mb_substr(trim($line),-5) == ' INDI' OR @mb_substr(trim($line),-10) == 'INDIVIDUAL') {$id_indi = MyHash(GetId($line));}
        if (@mb_strpos($line, ' NOTE') != NULL or @mb_strpos($line, 'NOTE'.chr(13)) != NULL)      {$id_note = MyHash(GetId($line));}
        if (@mb_substr(trim($line),-5) == ' SOUR' OR @mb_strpos($line, 'SOUR'.chr(13)) != NULL)   {$id_sour = MyHash(GetId($line));}
        if (@mb_strpos($line, ' FAM')  != NULL or @mb_strpos($line, 'FAM'.chr(13))  != NULL)      {$id_fam  = MyHash(GetId($line));}
        if (@mb_strpos($line, ' OBJE') != NULL or @mb_strpos($line, 'OBJE'.chr(13)) != NULL)      {$id_obje = MyHash(GetId($line));}
      }
      // FIN NIVEAU 0
  
      // NIVEAU 1
      else if (substr($line,0,1) == "1" AND substr($line,2,1) != "_" AND substr($line,2,4) != "OBJE" AND substr($line,2,3) != "RIN")    
      {   
// echo "<br>Niv1: => ".$level1InProgress." | ".$line;  
          maj_evenement($id_indi,$id_husb,$id_wife,$id_source);
          $level1InProgress = trim(substr($line,2,4));
          if ( ($level1InProgress == "DEAT" OR $level1InProgress == "BIRT")  AND (substr($line,7,1) == "Y" OR substr($line,9,1) == "Y") )
          { $date_evene = "Yes";
          }
          $id_source = NULL; $flag2PLAC = false;
      }

      // NIVEAU 2
      else if (substr($line,0,1) == "2" AND substr($line,2,1) != "_")    
      {   $level2InProgress = trim(substr($line,2,4));
// echo "<br>Niv2: => ".$level2InProgress;
      }

      // END OF LEVELS BREAKS

      // BEGIN OF UNIT LINES PROCESS

      // 1 NAME
      if (@substr($line,0,6) == '1 NAME' AND !$nom)
      { $nomprenom = nomprenom (trim(@mb_substr($line,7,2041)));
        $prenoms   = tdp ($nomprenom[1]);
        $nom       = mb_strtoupper(trim($nomprenom[2]));
      }

      // 1 DATE for level 0 SOUR
      elseif (@substr($line,0,6) == '1 DATE' AND $id_sour != 0)
      { $line = mb_ereg_replace('@#DJULIAN@ ','',$line);
        if ($line) {$date_sourc = mb_strtoupper(trim(mb_substr($line,6,32)));}
      }

      // 2 DATE
      elseif (@substr($line,0,6) == '2 DATE' AND !$date_evene)
      { $line = mb_ereg_replace('@#DJULIAN@ ','',$line);
        if ($line) {$date_evene = mb_strtoupper(trim(mb_substr($line,6,32)));}
      }

      /* 3 DATE, date of source or repository of an event. 
        Example : 
          1 SOUR @SOURCE1@
          2 DATA
          3 DATE 1 MAY 1999 => integrated in _source.date_sourc
        If level1 is REPO or SOUR then affect to "_source.date_sourc table"
        if level1 is other (BIRT, DEAT, MARR, etc...), there is no source or repository, then affect to _even_sour.date_evensour
    	if level1 is CHAN, date update is non integrated. No interest.

        1 SOUR est relié à un 0 INDI.
        Ex  : dariano. Mary Grave Dariano, 2 sources.
        Les 2 sources sont insérées dans evenement car type=SOUR, car 1 SOUR est un niveau1_encours. 
        Ca presque bon, il manque juste 3 DATE et, côté client, 1 seule source est affichée.
        
        Ci-dessous, quand 2 SOUR, ça ne passe pas...
        butin
        1 DEAT
        2 SOUR @S164@ => La source S164 existe en niveau 0 : "Registres d'Etat-Civil de Roanne"
        3 DATE 23 Jan 2002 => Dur, dur : date de la source et non pas du décès. S164 n'est pas stocké.
      */

      elseif (@substr($line,0,6) == '3 DATE')
      { 
        if ($level1InProgress == "SOUR") 
		{ $line = mb_ereg_replace('@#DJULIAN@ ','',$line);
	      $date_evene = mb_strtoupper(trim(mb_substr($line,6,32)));
	    }
		else
		{
          file_put_contents($logFile, "[Skipped] Line ".$no_ligne." : ".rtrim($line).chr(10), FILE_APPEND);
		}
      }

      /* 4 DATE, date of source or repository of an event which is declared in an event
           Example : gene.ged
           1 BIRT
           2 SOUR @S12@
           3 DATA
           4 DATE 2 DEC 1842
         If level2 is SOUR then affect to "_source.date_sourc table"
         if level2 is other (BIRT, DEAT, MARR, etc...), there is no source or repository, then affect to _even_sour.date_evensour
      */
      elseif (@substr($line,0,6) == '4 DATE')
      { $line = mb_ereg_replace('@#DJULIAN@ ','',$line);
        file_put_contents($logFile, "[Skipped] Line ".$no_ligne." : ".rtrim($line).chr(10), FILE_APPEND);
        //if ($line) {$date_evene = mb_strtoupper(trim(mb_substr($line,6,32)));}
      }

      // ADDR Structure
	      // 2 CITY refers level 1 ADDR, sometime 1 DOMI, 1 OCCU, ...)
		  // 3 CITY refers 2 ADDR 
      elseif ( (in_array(rtrim(@substr($line,2,4)),$ADDRStructure) OR in_array(rtrim(@substr($line,2,5)),$ADDRStructure) ) 
	            AND trim(@mb_substr($line,7,200)) != "" AND !$flag2PLAC)
      {
         if     (rtrim(@substr($line,2,2)) == 'AD' // ADR1, ADR2, DAR3
              OR rtrim(@substr($line,2,4)) == 'LIEU')  $SUBDIV     = trim(@mb_substr($line,7));
         elseif (rtrim(@substr($line,2,4)) == 'CITY')  $TOWN       = trim(@mb_substr($line,7));
         elseif (rtrim(@substr($line,2,4)) == 'DEPT')  $DEPARTMENT = trim(@mb_substr($line,7));
         elseif (rtrim(@substr($line,2,4)) == 'CODE')  $POSTAL     = trim(@mb_substr($line,7));
         elseif (rtrim(@substr($line,2,4)) == 'CTRY'
              OR rtrim(@substr($line,2,5)) == 'COUNT') $COUNTRY    = trim(@mb_substr($line,7));
         elseif (rtrim(@substr($line,2,4)) == 'STAE'
              OR rtrim(@substr($line,2,3)) == 'REG')   $REGION     = trim(@mb_substr($line,7));

        // $query = 'INSERT IGNORE INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_place` VALUES ("'.mb_substr(addslashes($place_evene),0,200).'")';
        // sql_exec ($query,0);
// echo "<br>debug2: ".$level2InProgress.' | '.$place_evene."<br>";
      }
  
      // 2 PLAC 
            //   1 PLAC exists, but refers to 0 _PLAC_DEFN, no interest
	        //   3 PLAC exists, rare, refers FUNERALS, no interest
      elseif (@substr($line,0,6) == '2 PLAC' AND !$place_evene)     
      { 
        $place_evene = trim(@mb_substr($line,7,210));
        $noLineReal = $no_ligne + 2;
        $query = 'INSERT IGNORE INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_place` VALUES ("'.mb_substr(addslashes($place_evene),0,200).'",'.$noLineReal.')';
        sql_exec ($query,0);
		$flag2PLAC = true;
      }

      // 1 SEX
      elseif (@substr($line,0,5) == '1 SEX')
      { $sexe = @mb_substr($line,6,1);
        if ($sexe == chr(13)) $sexe='';
        if ($sexe != 'M' and $sexe != 'F') 
        { file_put_contents($warnFile, "[Warning] Line ".$no_ligne." SEX=".$sexe." is unknown for ".$nom." ".$prenoms[0].chr(10), FILE_APPEND);
          $sexe = "_";
        }
      }

      // 2 SURN
      // 2 GIVN
      elseif (@substr($line,0,6) == '2 SURN' or @mb_substr($line,0,6) == '2 GIVN')    //perf
      {}
  
      // 1 ou 2 OBJE
        /*Sample : 
          0 @I45@ INDI
            1 NAME
            1 OBJE @O47@ <--
            1 BIRT
              2 OBJE @5377@ <--
              2 SOUR @12697@     // sour id is always associate with 3 OBJE. @12697@ & @M47@ are linked.
                3 OBJE @M47@

          0 @569@ FAM
            1 MARR               //$level1InProgress == "MARR" AND 
              2 OBJE @5604@ <--
          
          0 @O47@ OBJE
            1 FORM jpg
            1 FILE x.jpg
        
          0 @I1@ INDI
            1 _IMG 
            2 OBJE => no object id
            3 FILE Images\Descieux Jeanne.jpg  FILE is directly in 2 OBJE

          On prépare la ligne even_sour qui recevra l'url de la photo de la source
          On prépare la ligne evenement qui recevra l'url de la photo de l'individu (astuce note_evene recoit id_obje)
        */
      else if (@substr($line,0,6) == '1 OBJE' OR @mb_substr($line,0,6) == '2 OBJE' OR @mb_substr($line,0,6) == '3 OBJE')  
       {
          $id_obje = MyHash(GetId($line));

          // 1 OBJE => always in LEVEL 0 INDI. Picture in table "evenement". Object Id is stored in note_evene
          if ( (@substr($line,0,6) == '1 OBJE' AND $id_obje != 0 AND $id_indi != 0)
		    OR (@substr($line,0,6) == '2 OBJE' AND $id_obje == 0 AND $id_indi != 0)
		  )
          { $query = '
            INSERT INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement` VALUES (
             "'.GetSqlId("id_indi",$id_indi).'"
            ,0
            ,0
            ,"FILE"
            ,NULL
            ,NULL
            ,NULL
            ,"'.$id_obje.'"       /* note_evene */
            ,NULL,NULL,NULL      /* filedate, filelarg, filehaut */
            ,NULL,NULL,NULL,NULL /* region, country, subdiv,postal */
			,NULL                /*titl */
            )';
            sql_exec($query,0);
          }

          // 2 OBJE OR 3 OBJE
          elseif 
          ( (   @substr($line,0,6) == '2 OBJE' OR @substr($line,0,6) == '3 OBJE')  
            AND ($id_indi != 0 OR $id_husb != 0 OR $id_wife != 0) 
            AND $id_obje != 0)
          { 
            if ($flagMaria1 AND $level1InProgress == "") {$level1InProgress = "MARR";}
            if ($flagMaria2 AND $level1InProgress == "") {$level1InProgress = "MARZ";}

            $query = '
            INSERT IGNORE into `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour` 
            VALUES (
             "'.GetSqlId("id_indi",$id_indi).'"
            ,"'.GetSqlId("id_indi",$id_husb).'"
            ,"'.GetSqlId("id_indi",$id_wife).'"
            ,"'.$level1InProgress.'"
            ,"'.addslashes(trt_date($date_evene)).'"
            ,0
            ,"FILE"
            ,"'.$id_obje.'" /* astuce : on stocke id_obje pour le futur update */
            ,NULL
            ,NULL
            ,NULL
            ,NULL
            )';   
            sql_exec($query,0);
          }
      }

      // 1 NOTE
      else if (@substr($line,0,6) == '1 NOTE' and ($id_indi != 0 or $id_sour != 0))
      {   
          if (@mb_strpos($line, '@'))        // reference une note de niveau 0
          { $id_note = MyHash(GetId($line));
            if ($id_note != NULL AND $id_sour) {$query = 'INSERT into '.$sql_pref.'_'.$_REQUEST['ibase'].'_note VALUES ("'.$id_note.'","'.$id_sour.'")';sql_exec($query,0);}
            if ($id_note != NULL AND $id_indi) {$query = 'INSERT into '.$sql_pref.'_'.$_REQUEST['ibase'].'_note VALUES ("'.$id_note.'","'.$id_indi.'")';sql_exec($query,0);}
          }
          else                             // texte note individu direct
          { if ($id_indi != 0 /*AND $level1InProgress == ""*/)
            { $note_indi = $note_indi.' '.trim(@mb_substr($line,7,2041));
            }
            if ($id_sour != 0)
            { $note_sour = $note_sour." ".trim(@mb_substr($line,7,2041));
            }
          }
      }

      // 1 EVEN
      else if (@substr($line,0,6) == '1 EVEN' and trim(@mb_substr($line,7,2041) != "") )
	  { $note_indi .= " ".trim(@mb_substr($line,7,2041));
	  }

      // 1 PUBL PUBLICATION
	  // 1 AUTH AUTHOR
      else if ((@substr($line,0,6) == '1 PUBL' OR @substr($line,0,6) == '1 AUTH') and trim(@mb_substr($line,7,2041) != "") )
	  { $note_sour .= " ".trim(@mb_substr($line,7,2041));
	  }

      // NICK   => name2
      // ALIA   => name2
      // 1 TITL => name2
      // GIVN   => name2
      // MIDDLE => name2
      // SURN   => name2
      // LAST   => name2  
      // NSFX  NAME SUFFIX => name2  
      else if (@substr($line,2,4) == 'NICK' 
             OR @substr($line,2,4) == 'ALIA' 
             OR @substr($line,2,4) == 'GIVN' 
             OR @substr($line,2,4) == 'MIDD' 
             OR @substr($line,2,4) == 'SURN' 
             OR @substr($line,2,4) == 'LAST' 
             OR @substr($line,2,4) == 'NSFX'  // NAME SUFFIX
             OR @substr($line,0,6) == '1 TITL' // nobitity title or other
              )
      { 
        if ($id_indi > 0)
        { $position = @mb_strpos($name2, trim(@mb_substr($line,7,200)));
          if ( $position === false)
          {  $name2 .= ' '.trim(@mb_substr($line,7,2041));
             $name2 = mb_ereg_replace('/','',$name2);
// echo "<br>debug name2: ".$name2." | ".$line;
          }
        }
        if ($id_sour > 0)
          $note_sour = trim(@mb_substr($line,7,200));
      }

      // 2 NPFX NAME PREFIX
      else if (@substr($line,2,4) == 'NPFX')
	  { $nom = trim(@mb_substr($line,7,2041)).' '.$nom;
	  }

      // 2 TITL in INDI
        /* Example:
            0 @M1@ INDI
            1 OBJE
            2 FILE file.jpg
            2 TITL Daniel Durand  => TITL tag can be before FILE tag !

            0 @M1@ OBJE
            1 FILE file.jpg
            2 FORM jpg
            2 TITL Epfel, Anna Maria

          On complète (UPDATE) la ligne de la table evenement créée par 2 FILE juste avant.
          N.B : if TITL tag is before FILE tag, KO, l'update ne trouvera rien, mais ce n'est pas souvent le cas.
        */
      else if (@substr($line,0,6) == '2 TITL' AND trim(@mb_substr($line,7,2041)) != ""
	      AND ($id_indi != 0 OR $id_obje != 0) 
	      )
      { 
        $query = '
        UPDATE `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` 
        SET titl = "'.addslashes(trim(@mb_substr($line,7,32))).'"
        WHERE id_indi = "'.GetSqlId("id_indi",$id_indi).'"
        AND type_evene = "FILE"';
        sql_exec($query,0);
      }

      // 2 TITL in FAM
        /* Example:
            0 @M1@ FAM
            1 OBJE
            2 FILE file.jpg
            2 TITL Daniel Durand  => TITL tag can be before FILE tag !
          On complète (UPDATE) la ligne de la table even_sour MARR créée par 2 FILE juste avant.
          N.B : if TITL tag is before FILE tag, KO, l'update ne trouvera rien, mais ce n'est pas souvent le cas.
        */
	  elseif (@substr($line,0,6) == '2 TITL' AND  ($id_husb != 0 OR $id_wife!= 0)  AND trim(@mb_substr($line,7,2041)) != "" )
	  {
	    $query = 
        'UPDATE `'.$sql_pref.'_'.$_REQUEST["ibase"].'_even_sour` 
         SET titl = "'.addslashes(trim(@mb_substr($line,7,2041))).'"
         WHERE id_husb = "'.GetSqlId("id_indi",$id_husb).'" AND id_wife = "'.GetSqlId("id_indi",$id_wife).'"
         AND type_sourc = "FILE"';
        sql_exec($query,0);
      }

      // 3 TITL
      /*   Examples : 
           1 EVEN
           2 OBJE
           3 FORM jpg
           3 TITL toto  => even_sour.titl
           3 FILE C:/Mes documents/FAMILLE/DOCS/Images familles/personnes/durand/lesdurand_80ans.jpg

           1 BIRT
           2 WITN (tag non géré pour l'instant, à gérer d'abord !!!)
           3 NAME /Jobert Emile, Négociant/
           3 TITL Desrois Gaston, Négociant => ???

           1 BURI
           2 ASSO @3896@
           3 TYPE EVEN
           3 TITL Témoin => even_sour type_sour = "RELA" (asso)
      */

	  // 1 ADOP Adoption
      else if (@substr($line,0,6) == '1 ADOP' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag["ADOP"]." : ".trim(@mb_substr($line,7,2041));
      }

      // 1 CENS Census
      else if (@substr($line,0,6) == '1 CENS' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag["CENS"]." : ".trim(@mb_substr($line,7,2041));
      }

      // 1 CONF Confirmation
      else if (@substr($line,0,6) == '1 CONF' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag["CONF"]." : ".trim(@mb_substr($line,7,2041));
      }

	  // 1 EDUC Education
      else if (@substr($line,0,6) == '1 EDUC' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag["EDUC"]." : ".trim(@mb_substr($line,7,2041));
      }

      // 1 GRAD Graduation
      else if (@substr($line,0,6) == '1 GRAD' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag["GRAD"]." : ".trim(@mb_substr($line,7,2041));
      }

	  // 1 IMMI Immigration
      else if (@substr($line,0,6) == '1 IMMI' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag["IMMI"]." : ".trim(@mb_substr($line,7,2041));
      }

	  // 1 EMIG Emigration
      else if (@substr($line,0,6) == '1 EMIG' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag["EMIG"]." : ".trim(@mb_substr($line,7,2041));
      }
      // 1 SIGN Signature
      else if (@substr($line,0,6) == '1 SIGN' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag["SIGN"]." : ".trim(@mb_substr($line,7,2041));
      }

	  // 1 NCHI Number of children
      else if (@substr($line,0,6) == '1 NCHI' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag["NCHI"]." : ".trim(@mb_substr($line,7,2041));
      }

	  // 1 DSCR Description
      else if (@substr($line,0,6) == '1 DSCR' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".trim(@mb_substr($line,7,2041));
      }

	  // 1 NATI Nationality
      else if (@substr($line,0,6) == '1 NATI' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag["NATI"]." : ".trim(@mb_substr($line,7,2041));
      }

	  // 1 SSN Security Social Number
      else if (@substr($line,0,5) == '1 SSN' AND trim(@mb_substr($line,6,2041)) != '')
      {  $note_indi .= " ".$got_tag["SSN"]." : ".trim(@mb_substr($line,6,2041));
      }

	  // 2 CHILDLESS no children
      else if (@substr($line,0,11) == '2 CHILDLESS' AND trim(@mb_substr($line,12,2041)) != '')
      {  $note_indi .= " ".$got_lang['Enfan']." : ".$got_lang['Non']." ".trim(@mb_substr($line,12,2041));
      }

	  // PEDI
      else if (@substr($line,2,4) == 'PEDI' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_indi .= " ".$got_tag['PEDI']." ".trim(@mb_substr($line,7,2041));
      }

	  // PEDIGREELINK
      else if (@substr($line,2,12) == 'PEDIGREELINK' AND trim(@mb_substr($line,13,2041)) != '')
      {  $note_indi .= " ".$got_tag['PEDI']." ".trim(@mb_substr($line,13,2041));
      }

	  // TEMP (temple)
      else if (@substr($line,2,4) == 'TEMP' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_evene .= " ".$got_tag['TEMP']." ".trim(@mb_substr($line,7,2041));
      }

      // 1,2,3 RELI. 1 => individu.religion, 2&3 => event.note_evene
      else if (@substr($line,2,4) == 'RELI' AND trim(@mb_substr($line,7,2041)) != '')
      { if (@substr($line,0,1) == '1') 
             $religion    =     trim(@mb_substr($line,7,2041));
        else $note_evene .= " ".trim(@mb_substr($line,7,2041));
      }

      // 2,3 AGE => event.age
      else if (@substr($line,2,4) == 'AGE ' AND trim(@mb_substr($line,7,2041)) != '')
      { $ageEvent = trim(@mb_substr($line,7,2041));
      }

      // 2 CAUS : lié niveau 1
      else if (@substr($line,0,6) == '2 CAUS' AND trim(@mb_substr($line,7,2041)) != '')
      {  $note_evene .= ' '.trim(@mb_substr($line,7,2041));
      }

      // 1 OCCU
      else if (@substr($line,0,6) == '1 OCCU' AND trim(@mb_substr($line,7,2041)) != '')
      { $profession = trim(@mb_substr($line,7,2041));
        $note_evene = trim(@mb_substr($line,7,2041));
        $level1InProgress = 'OCCU';
      }

      // 1 HUSB 1 WIFE
      else if (@substr($line,0,6) == '1 HUSB')    {$id_husb = MyHash(GetId($line));}
      else if (@substr($line,0,6) == '1 WIFE')    {$id_wife = MyHash(GetId($line));}

      // 1 CHIL
      else if (@substr($line,0,6) == '1 CHIL')    
      { $id_enfant = MyHash(GetId($line));
        $query = 'UPDATE `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` 
            SET `id_pere` = "'.GetSqlId('id_indi',$id_husb).'", `id_mere` = "'.GetSqlId('id_indi',$id_wife).'" 
            WHERE `id_indi` = '.GetSqlId('id_indi',$id_enfant);
         sql_exec ($query,0);
      }

      // 1 MARR
      elseif (@substr($line,0,6) == '1 MARR')
      {  if ($flagMaria1) {$flagMaria1 = FALSE; $flagMaria2 = TRUE;}
         else                 {$flagMaria1 = TRUE;  $flagMaria2 = FALSE;}
         $level1InProgress = "MARR";
      }

      // 1 DIV
      elseif (@substr($line,0,5) == '1 DIV') // on exclue les lignes 1 DIV N (base royal)
      {  if (@substr($line,6,1) != "N") {$level1InProgress = "DIV";}
      }

      // 2 SOUR
      else if (@substr($line,0,6) == '2 SOUR' OR @substr($line,0,6) == '2 REPO')      // A AMELIORER Ancestor File et GeneWeb stocke du texte rattache a la balise 1 (leterrier & milliard.ged)
      { $id_source = MyHash(GetId($line));
      }

      // 2 ASSO
      else if (@substr($line,0,6) == '1 ASSO' OR @substr($line,0,6) == '2 ASSO')
      {    
          $id_asso = MyHash(GetId($line));
      }

      // 3 RELA
	  /*
	  Exemple complet : 
	  2 ASSO @I131@ (ou @F1@)
      3 TYPE Godfather
      3 RELA Godfather
      3 TITL Godfather
	  3 ROLE Godfather
	  */
      else if (
                (@substr($line,0,6) == '2 RELA' 
	          OR @substr($line,0,6) == '3 RELA'
			  OR ( (@substr($line,0,6) == '3 TYPE' OR @substr($line,0,6) == '3 ROLE' OR @substr($line,0,6) == '3 TITL') AND !in_array(@substr($line,7,4),$got_tag) )
			    )
			  AND isset($id_asso)
			  )
      {
        if (@substr($line,0,6) != '3 TITL') 
        { $query = '
          INSERT IGNORE INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour` VALUES ("'
             .GetSqlId("id_indi",$id_indi).'"
          ,"'.GetSqlId("id_indi",$id_husb).'"
          ,"'.GetSqlId("id_indi",$id_wife).'"
          ,"'.$level1InProgress.'"
          ,"'.addslashes(trt_date($date_evene)).'"
          ,"'.GetSqlId("id_indi",$id_asso).'"  /* id_sour */
          ,"RELA"
          ,NULL   /* attr_sourc will completed next with an update */
          ,NULL
		  ,NULL
		  ,NULL
          ,"'.addslashes(trim(@mb_substr($line,7,2041))).'"
		  )';
          sql_exec($query,0);
		  $withnessQuality = trim(@mb_substr($line,7,2041));
		} else
		{

          $query = 'UPDATE `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour`
          SET titl = CONCAT("'.addslashes($withnessQuality).'"," ","'.addslashes(trim(@mb_substr($line,7,2041))).'")
          WHERE id_indi = "'.GetSqlId("id_indi",$id_indi).'"
		    AND id_husb = "'.GetSqlId("id_indi",$id_husb).'"
		    AND id_wife = "'.GetSqlId("id_indi",$id_wife).'"
          ';
          sql_exec($query,0);
		}
      }

      // 1,2,3,4 FILE : on trouve des chemins ou url
      // else if (@substr($line,2,4) == 'FILE' AND trim(mb_substr($line,7,248)) != NULL)
      else if (@substr($line,2,4) == 'FILE' 
        AND (mb_substr(rtrim($line),-4) == '.jpg'
          OR mb_substr(rtrim($line),-4) == '.JPG'
          OR mb_substr(rtrim($line),-4) == '.png'
          OR mb_substr(rtrim($line),-4) == '.PNG'
            )
          )
      {
        // recuperation des noms des fichiers ou url. A AMELIORER : pas reussi oter les backslash en UTF-8.
        $temp = str_replace ('\\','/',trim(mb_substr($line,7,248)));
        $text_gauche = mb_substr($temp,0, mb_strrpos ($temp,"."));
        $text_droite = mb_substr($temp,mb_strrpos ($temp,"."), 2048);
        if (mb_strrpos ($text_gauche,"/") != NULL)
        {    $text_gauche = mb_substr($text_gauche,mb_strrpos ($text_gauche,"/")+1, 255);
        }
        $filename = mb_substr($text_gauche.$text_droite,0,206);
  
        // recuperation largeur, hauteur et date du fichier ([3])
        $attrib_image = array(NULL,NULL,NULL,NULL);
        $MyFile  = MyUrl ('picture/'.$_REQUEST["ibase"].'/'.$filename,FALSE);
        if (!file_exists($MyFile))
        { if (php_uname('n') != "PC-DAMIEN")
            file_put_contents($logFile, '[Warning] Line '.$nb_line.' : "'.$filename.'" '.$got_lang["MesA2"].chr(10), FILE_APPEND);
        } 
        else
        { // test format jpg or png
          $attrib_image    = @getimagesize($MyFile);
          if  ($attrib_image != FALSE)
          { $attrib_image[3] = date ("Y-m-d H:i:s", @filemtime($MyFile));
            if ($attrib_image[2] != 2 AND $attrib_image[2] != 3) 
              file_put_contents($logFile,  '[Warning] Line '.$nb_line.' "'.$filename.'" '.$got_lang["MesF2"].chr(10), FILE_APPEND);
          }
        }

        // sql prepare 
        if ($attrib_image != FALSE)
        { if (empty($attrib_image[0]) ) {$attrib_image[0] = 'NULL';}
          if (empty($attrib_image[1]) ) {$attrib_image[1] = 'NULL';}
          if (empty($attrib_image[2]) ) {$attrib_image[2] = 'NULL';}

          if (empty($attrib_image[3]) ) {$attrib_image[3] = 'NULL';}
          else                          {$attrib_image[3] = '"'.$attrib_image[3].'"';}

        } else
        { $attrib_image = array();
          $attrib_image[0] = 'NULL';
          $attrib_image[1] = 'NULL';
          $attrib_image[2] = 'NULL';
          $attrib_image[3] = 'NULL';
        }
// echo $filename.'2';print_r2($attrib_image); // debug

        // 1 FILE
        /*   dépendant toujours d'un tag O OBJE
             Ex : 
             0 @I45@ INDI  
             1 OBJE @O47@
             0 @O47@ OBJE
             1 FILE x.jpg
             Ici, l'image (objet 047) est associé à l'individu => table evenement.
             Au moment de la création de l'individu, l'id_obje O47 a été stocké : 
             - soit dans evenement.note_evene (origine INDI),
             - soit dans even_sour.attr_sourc (origine FAM, SOUR, REPO)
             Aussi, au moment où on arrive sur le tag 1 FILE, on met à jour la table evenement ou even_sour avec l'url du file.
        */
        if (@substr($line,0,6) == '1 FILE')
        { $query = 'UPDATE `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` 
          SET note_evene = "'.$filename.'"
          ,filedate = '.$attrib_image[3].'
          ,filelarg = '.$attrib_image[0].'
          ,filehaut = '.$attrib_image[1].'
          WHERE note_evene = "'.$id_obje.'"
          AND type_evene = "FILE"';
          sql_exec($query,0);

          $query = 'UPDATE `'.$sql_pref.'_'.$_REQUEST["ibase"].'_even_sour` 
          SET attr_sourc = "'.$filename.'"
          ,filedate = '.$attrib_image[3].'
          ,filelarg = '.$attrib_image[0].'
          ,filehaut = '.$attrib_image[1].'
          WHERE attr_sourc = "'.$id_obje.'"
          AND type_sourc = "FILE"';
          sql_exec($query,0);
        }
  
        // 2 FILE dans INDI
        /*   Exemple 
             0 @I1@ INDI
		     1 OBJE
		     2 FILE file.jpg
		   C'est plus simple, on créé directement le row evenement de type_evene="FILE"
        */
        elseif (@substr($line,0,6) == '2 FILE' and isset($id_indi))
        { if ($id_indi == "") {$id_indi = 0;}
          $query = 'INSERT INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement` VALUES (
           "'.GetSqlId("id_indi",$id_indi).'"
          ,0  /* id_husb */
          ,0  /* id_wife */
          ,"FILE"
          ,NULL  /* date_evene */
          ,NULL  /* lieu_evene */
          ,NULL  /* dept_evene */
          ,"'.$filename.'"  /* note_evene */
          ,'.$attrib_image[3].'
          ,'.$attrib_image[0].'
          ,'.$attrib_image[1].'
          ,NULL  /* region_evene */
          ,NULL  /* country_evene */
          ,NULL  /* subdiv_evene */
          ,NULL  /* postal_evene */
          ,NULL  /* titl */
          )';
          sql_exec ($query,0);
        }

        // 2 FILE dans MARR
        /*   Exemple 
             0 @F1@ FAM
             1 OBJE
             2 FILE file.jpg
           On crée directement le row even_sour de type_evene="FILE"
        */
        elseif (@substr($line,0,6) == '2 FILE' and ($id_fam or $id_fam =="0"))
        { if ($id_husb == "") {$id_husb = 0;}
          if ($id_wife == "") {$id_wife = 0;}
          $query = 'INSERT IGNORE INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour` VALUES (
           0
          ,"'.GetSqlId("id_indi",$id_husb).'"
          ,"'.GetSqlId("id_indi",$id_wife).'"
          ,"MARR"
          ,""
          ,0
          ,"FILE"
          ,"'.substr($filename,0,206).'"
          ,'.$attrib_image[3].'
          ,'.$attrib_image[0].'
          ,'.$attrib_image[1].'
		  ,NULL
          )';
          sql_exec($query,0);
        }

        // 2 FILE dans SOUR 
        /*   Exemple 
             0 @F1@ FAM
             1 OBJE
             2 FILE file.jpg
           On écrit sans les id indi,pere,mere. Un update fait le boulot apres avec l'id_sour
        */
        elseif (@substr($line,0,6) == '2 FILE' and $id_sour != "")
        { $query = 'INSERT IGNORE INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour` VALUES (
           0
          ,0
          ,0
          ,"SOUR"
          ,""
          ,"'.GetSqlId("id_sour",$id_sour).'"
          ,"FILE"
          ,"'.substr($filename,0,206).'"
          ,'.$attrib_image[3].'
          ,'.$attrib_image[0].'
          ,'.$attrib_image[1].'
		  ,NULL
          )';
          sql_exec($query,0);
        }

        // 3 FILE
        /*   Exemple
             0 @I1@ INDI
             1 CREM 
             2 OBJE
             3 FILE file.jpg
           3 FILE dépend d'un niveau 1 (BIRT, DEAT, etc...)
            On insère dans even_sour (table fille de evenement)
        */
        elseif (@substr($line,0,6) == '3 FILE')
        { if ($id_indi == "") {$id_indi = 0;}
          $query = 'INSERT IGNORE INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour` VALUES (
            '.GetSqlId("id_indi",$id_indi).'
          ,"'.GetSqlId("id_indi",$id_husb).'"
          ,"'.GetSqlId("id_indi",$id_wife).'"
          ,"'.$level1InProgress.'"
          ,"'.addslashes(trt_date($date_evene)).'"
          ,0
          ,"FILE"
          ,"'.substr($filename,0,206).'"
          ,'.$attrib_image[3].'
          ,'.$attrib_image[0].'
          ,'.$attrib_image[1].'
		  ,NULL
          )';
          sql_exec($query,0);
        }

        // 4 FILE
        /*   Exemple : 
             0 @I1@ INDI
             1 BIRT
             2 SOUR @S111@
             3 DATA
             4 FILE file.jpg
           4 FILE dépend d'un niveau 2 (SOUR)
           On insère dans even_sour avec l'id_sour
		*/
        elseif (@substr($line,0,6) == '4 FILE')
        { //if ($id_source == "") {$id_source = 0;}
          $query = 'INSERT IGNORE INTO `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour` VALUES (
		   0
		  ,0
		  ,0
		  ,"'.$level1InProgress.'"
		  ,"'.addslashes(trt_date($date_evene)).'"
		  ,"'.GetSqlId("id_sour",$id_source).'"  /* id_sour */
		  ,"FILE"
		  ,"'.substr($filename,0,206).'"
		  ,'.$attrib_image[3].'
		  ,'.$attrib_image[0].'
		  ,'.$attrib_image[1].'
		  ,NULL
		  )';
          sql_exec($query,0);
        }

        else // par précaution : n'arrive jamais !
          file_put_contents($logFile, "[Skipped] Line ".$no_ligne." : ".rtrim($line).chr(10), FILE_APPEND);
      }
  
  // X CONT, CONC, PAGE, TEXT, ABBR
      else if (@substr($line,2,4) == 'CONC'
            OR @substr($line,2,4) == 'PAGE'
            OR @substr($line,2,4) == 'TEXT'
            OR ( @substr($line,2,4) == 'NOTE' AND @substr($line,0,1) != '1')
            OR @substr($line,2,4) == 'ABBR'
			OR @substr($line,0,1) == '<'    // html tags which are at the beginning of the line
			OR @substr($line,0,1) == '&'    // html tags which are at the beginning of the line
			OR @substr($line,0,1) == chr(9) // html tags which are at the beginning of the line
              )
      {   if (@substr($line,0,1) == '<') 
		       $temp = trim(@mb_substr($line,0,2041));
		  else $temp = trim(@mb_substr($line,7,2041));
          
          $note_evene = $note_evene.$temp;
// echo '<br>id:'.$id_indi.' niv:'.$level1InProgress.'=>'.$line; // debug
          if ($id_indi != 0 AND $level1InProgress == "NOTE") 
		  { $note_indi = $note_indi.$temp;
	      }
          if ($id_sour != NULL) {$note_sour = $note_sour.$temp;}

      } else if (@substr($line,2,4) == 'CONT')
      {   $temp = trim(@mb_substr($line,7,2041));
          $note_evene = $note_evene.chr(13).$temp;
          if ($id_indi != NULL AND $level1InProgress == "NOTE") {$note_indi = $note_indi.chr(13).$temp;}
          if ($id_sour != NULL) {$note_sour = $note_sour.chr(13).$temp;}

      // logs
      } elseif (@substr($line,0,1) != "0" 
	        AND @substr($line,2,1) != "_" 
			AND trim(@mb_substr($line,7,2041)) != ""
			AND !in_array(rtrim(substr($line,2,4)), $noLogTags) 
	        AND !in_array(rtrim(substr($line,2,5)), $noLogTags)  
			AND !in_array(rtrim(substr($line,2,4)), $tagsAlreadyProcessed) // essential level 1
            AND !(@substr($line,2,4) == 'TYPE' OR @substr($line,2,4) == 'ROLE') AND !in_array(@substr($line,7,4),$got_tag) // déjà traité
			AND @mb_substr($line,0,6) != "3 ROLE" // uniquement chepla. Doublon avec 3 TYPE
			AND @mb_substr($line,0,6) != "2 ROLE" // uniquement chepla. Doublon avec 3 RELA
			)
      { file_put_contents($logFile, "[Skipped] Line ".$no_ligne." : ".rtrim($line).chr(10), FILE_APPEND);
// if (empty($level1InProgress)) $level1InProgress = ""; // debug
// echo "[Debug] ".$no_ligne." : ".rtrim($line).' => niv1_encours:'.$level1InProgress.'<br>'; // debug
      }

      $no_ligne = $no_ligne + 1;        // on avance le compteur de caractere general
    } 
//    END OF ROWS READING (while)

    fclose($F);
  } 
// END OF BLOCK 1 upload processing

  // Progress bar initialization : first display

  if ($_REQUEST["imap"] == 0 AND $_REQUEST["irow"] == 0)    $_REQUEST["imap"] = 1; // BLOCK 1 < timeout
  if ($_REQUEST["imap"] == 0 AND $no_ligne == $NbLines + 2) $_REQUEST["imap"] = 1; // BLOCK 1 > timeout and finished
  
  if ($_REQUEST["imap"] != 0)
  {
// BEGIN OF BLOCK 2

    $commentaire = ""; 
    $apkc = 'wRN5UdcT3zhOhp0jHzbXVVioG9FdqJxS3uLhrY6ln3m8rWa5wRKI3EX9IxZE2K+Q';
    if (extension_loaded('openssl'))
      $apk = openssl_decrypt($apkc, 'AES-256-CBC', $cle);
    else
    { echo 'Error : Openssl PHP extension is not install on your server. Please contact your administrator server.';
      return;
    }

    // upload countries array to decodeLieu function
    $arrCountries = fnCountriesLoad();

    /***** PLACES PROCESS   ***************/

    // testing Geoapify connection
    $flagGeoapifyConnection = false;
    $url = 'https://api.geoapify.com/v1/geocode/search?text=london&lang=en&format=json&apiKey='.$apk;
    $json = funcCurl($url);
    $arrParsedPlace = json_decode($json, true);
    if (!empty($arrParsedPlace['results'][0]['country']))
      $flagGeoapifyConnection = true;

    // get places finded in block 1
    $query = "SELECT count(*)
              FROM `".$sql_pref."_".$_REQUEST['ibase']."_place`";
    $result = sql_exec($query);
    $row = @mysqli_fetch_row($result);
    if (isset($row[0]))
      $NbPlaces = $row[0];

    $query = "SELECT place,noLine
              FROM `".$sql_pref."_".$_REQUEST['ibase']."_place`";
    $result = sql_exec($query,0);

    // parse without Geoapify
    if (!$flagGeoapifyConnection /*OR php_uname('n') == "PC-DAMIEN"*/) // api maps KO or dév local
    { $flagMaps = 0;
      $imap = 1;
      while($row = @mysqli_fetch_row($result))
      { 
        // get replace place in SQL table map_places
        $placeString = fnPlaceReplace($row[0]);
        decodeLieu($placeString);

        // update progress bar
        $progress = intval( ($_REQUEST['nblines'] +1000*$imap) / ($_REQUEST['nblines'] + 1000*$NbPlaces)  * 100);

        $imap++;
      }
    }

    // parse with Geoapify
    else 
    { 
      // in case of split loading, the MySql pointer is advanced to the places to be processed
      $flagMaps = 1;
      $imap = 1;
      while ($imap < $_REQUEST["imap"])
      { $imap++;
        @mysqli_fetch_row($result);
      }

      while($row = @mysqli_fetch_row($result))
      { 
        // get replace place in SQL table map_places
        $placeString = fnPlaceReplace($row[0]);
        // $placeString = trim($placeString); // a vérifier

        // special case : place is only a country. Exemple "Italy". Calling the API is unnecessary and API error prone.
        $CountryPotential = @str_replace(',','',$placeString);
        if ( fnCountryExists($_REQUEST["gedlang"], $CountryPotential) )
        { $arrParsedPlace['city']    = '';
          $arrParsedPlace['county']  = '';
          $arrParsedPlace['state']   = '';
          $arrParsedPlace['country'] = '';
          $arrParsedPlace['suburb']  = $CountryPotential;
          fnPlaceWithGeo($CountryPotential, $arrParsedPlace, $row[1]);
// echo '<br>0 country';
        }
        else
        {
        // delete characters which make Geoapify request false
        $search  = array(' Co.'   , ' co.'   ,' Co,'    , ' co,'    ,' CO,'    , '.', '(', ')', '-', '?', '/');       // ajout de ' co.', ' Co.' et '/'  /!\"Co." before "."
        $replace = array(' County', ' County',' County,', ' County,',' County,', ' ', ',', ',', ' ', ' ', ' sur '); 
        $placeToGeoapify = @str_replace($search, $replace, $placeString);

        // Specific french language
        $searchFrenchPlaces = array('Basse-Normandie','Haute-Normandie','Basse Normandie','Haute Normandie','Cherbourg','Nord-Pas-de-Calais','Nord Pas de Calais');
        $replaceFrenchPlaces = array('Normandie','Normandie','Normandie','Normandie','Cherbourg-en-Cotentin','Hauts-de-France','Hauts-de-France');
        $placeToGeoapify = @str_replace($searchFrenchPlaces, $replaceFrenchPlaces, $placeToGeoapify);

        // Replace abréviations "Saint" : " st ", ",St.", etc. to Geoapify
        $patterns = array('/\b[Ss][Tt][.\s-]/'   => 'Saint '
                         ,'/\b[Ss]te[.\s-]/'  => 'Sainte ');
        $placeToGeoapify = preg_replace(array_keys($patterns), array_values($patterns), $placeToGeoapify);
      
        // Replace Acronyms to Geoapify
        $placeToGeoapify = fnPlaceReplaceAcronym($placeToGeoapify);
      
        // Delete geographic codes for Geoapify. Ex : "Town,75140,7" => "Town, ,". This codes make Geoapify request false.
        $placeToGeoapify = fnPlaceDeleteCode($placeToGeoapify);

        // Calling the Geoapify Geocoding API with the full address and language
        $url = 'https://api.geoapify.com/v1/geocode/search?text='.urlencode($placeToGeoapify).'&lang='.$_REQUEST["gedlang"].'&format=json&limit=4&apiKey='.$apk;
        $json = funcCurl($url);
        $data = json_decode($json, true);
// echo '<br><br><b>'.$placeString.'</b> placeToGeoapify: '.$placeToGeoapify;
// echo '<br>'.$_REQUEST['gedlang'];
// echo '<br>'.$url;
// print_r2($data);
          // fnPlaceWithGeo($placeString, $arrParsedPlace, $row[1]);
          // fnPlaceWithoutGeo($placeString, $row[1]);

        $nn = count($data);

        // initialized empty data and convert api results to normalized strings
        $normalizedParseCity   = array(); $normalizedParseTown  = array(); $normalizedParseVillage = array(); $normalizedParseHamlet = array();
        $normalizedParseCounty = array(); $normalizedParseState = array(); $normalizedParseCountry = array(); $normalizedParseSuburb = array();
        $normalizedParseHouse = array(); $normalizedParseHousenumber = array(); $normalizedStreet = array(); 
        $ind = 0; $logType = 'L';
        $flagCITY = array(); $flagTOWN = array(); $flagSUBURB = array(); $flagVILLAGE = array(); $flagHAMLET = array(); $flagCOUNTY = array(); $flagSTATE = array(); $flagCOUNTRY = array();
      
        // get normalized texts
        $normalizedPlace = fnNormalizeString($placeToGeoapify);
        for ($ii=0; $ii < count($data); $ii++)
        { 
          if (!empty($data['results'][$ii]['district']) ) 
          {  if (empty($data['results'][$ii]['county']) ) 
               $data['results'][$ii]['county'] = $data['results'][$ii]['district'];
             elseif (empty($data['results'][$ii]['suburb']) ) 
               $data['results'][$ii]['suburb'] = $data['results'][$ii]['district'];
          }
          if (!empty($data['results'][$ii]['city']))        $normalizedParseCity[$ii]         = fnNormalizeString($data['results'][$ii]['city']);        else { $data['results'][$ii]['city']        = ''; $normalizedParseCity[$ii]         = ''; }
          if (!empty($data['results'][$ii]['county']))      $normalizedParseCounty[$ii]       = fnNormalizeString($data['results'][$ii]['county']);      else { $data['results'][$ii]['county']      = ''; $normalizedParseCounty[$ii]       = ''; }
          if (!empty($data['results'][$ii]['state']))       $normalizedParseState[$ii]        = fnNormalizeString($data['results'][$ii]['state']);       else { $data['results'][$ii]['state']       = ''; $normalizedParseState[$ii]        = ''; }
          if (!empty($data['results'][$ii]['country']))     $normalizedParseCountry[$ii]      = fnNormalizeString($data['results'][$ii]['country']);     else { $data['results'][$ii]['country']     = ''; $normalizedParseCountry[$ii]      = ''; }
          if (!empty($data['results'][$ii]['suburb']))      $normalizedParseSuburb[$ii]       = fnNormalizeString($data['results'][$ii]['suburb']);      else { $data['results'][$ii]['suburb']      = ''; $normalizedParseSuburb[$ii]       = ''; }
          if (!empty($data['results'][$ii]['town']))        $normalizedParseTown[$ii]         = fnNormalizeString($data['results'][$ii]['town']);        else { $data['results'][$ii]['town']        = ''; $normalizedParseTown[$ii]         = ''; }
          if (!empty($data['results'][$ii]['village']))     $normalizedParseVillage[$ii]      = fnNormalizeString($data['results'][$ii]['village']);     else { $data['results'][$ii]['village']     = ''; $normalizedParseVillage[$ii]      = ''; }
          if (!empty($data['results'][$ii]['hamlet']))      $normalizedParseHamlet[$ii]       = fnNormalizeString($data['results'][$ii]['hamlet']);      else { $data['results'][$ii]['hamlet']      = ''; $normalizedParseHamlet[$ii]       = ''; }
          if (!empty($data['results'][$ii]['house']))       $normalizedParseHouse[$ii]        = fnNormalizeString($data['results'][$ii]['house']);       else { $data['results'][$ii]['house']       = ''; $normalizedParseHouse[$ii]        = ''; }
          if (!empty($data['results'][$ii]['housenumber'])) $normalizedParseHousenumber[$ii]  = fnNormalizeString($data['results'][$ii]['housenumber']); else { $data['results'][$ii]['housenumber'] = ''; $normalizedParseHousenumber[$ii]  = ''; }
          if (!empty($data['results'][$ii]['street']))      $normalizedParseStreet[$ii]       = fnNormalizeString($data['results'][$ii]['street']);      else { $data['results'][$ii]['street']      = ''; $normalizedParseStreet[$ii]       = ''; }
        }
        if (empty($data['query']['parsed']['city']))        $data['query']['parsed']['city']            = ''; else $data['query']['parsed']['city']        = ucfirst($data['query']['parsed']['city']);
        if (empty($data['query']['parsed']['county']))      $data['query']['parsed']['county']          = ''; else $data['query']['parsed']['county']      = ucfirst($data['query']['parsed']['county']);
        if (empty($data['query']['parsed']['state']))       $data['query']['parsed']['state']           = ''; else $data['query']['parsed']['state']       = ucfirst($data['query']['parsed']['state']);
        if (empty($data['query']['parsed']['country']))     $data['query']['parsed']['country']         = ''; else $data['query']['parsed']['country']     = ucfirst($data['query']['parsed']['country']);
        if (empty($data['query']['parsed']['suburb']))      $data['query']['parsed']['suburb']          = ''; else $data['query']['parsed']['suburb']      = ucfirst($data['query']['parsed']['suburb']);
        if (empty($data['query']['parsed']['house']))       $data['query']['parsed']['house']           = ''; else $data['query']['parsed']['house']       = ucfirst($data['query']['parsed']['house']);
        if (empty($data['query']['parsed']['housenumber'])) $data['query']['parsed']['housenumber']     = ''; else $data['query']['parsed']['housenumber'] = ucfirst($data['query']['parsed']['housenumber']);
        if (empty($data['query']['parsed']['street']))      $data['query']['parsed']['street']          = ''; else $data['query']['parsed']['street']      = ucfirst($data['query']['parsed']['street']);
      
// for ($ii=0; $ii < $nn; $ii++) { echo '<br><br>'.$ii.'  <b>city</b>: '.$data['results'][$ii]['city'].'  <b>county</b>: '.$data['results'][$ii]['county'].'  <b>state</b>: '.$data['results'][$ii]['state'].'  <b>country</b>: '.$data['results'][$ii]['country'].'  <b>suburb</b>: '.$data['results'][$ii]['suburb'].'  <b>town</b>: '.$data['results'][$ii]['town'].'  <b>village</b>: '.$data['results'][$ii]['village'].'  <b>hamlet</b>: '.$data['results'][$ii]['hamlet'].'  <b>housenumber</b>: '.$data['results'][$ii]['housenumber'].'  <b>street</b>: '.$data['results'][$ii]['street'].'  <b>house</b>: '.$data['results'][$ii]['house'];} echo '<br><br><b>pcity</b>: '.$data['query']['parsed']['city'].'  <b>pcounty</b>: '.$data['query']['parsed']['county'].'  <b>pstate</b>: '.$data['query']['parsed']['state'].'  <b>pcountry</b>: '.$data['query']['parsed']['country'].'  <b>psuburb</b>: '.$data['query']['parsed']['suburb'].'  <b>phousenumber</b>: '.$data['query']['parsed']['housenumber'].'  <b>pstreet</b>: '.$data['query']['parsed']['street'].'  <b>phouse</b>: '.$data['query']['parsed']['house'].'<br>';
// print_r2($data);
        // calculate checks to select the better line among geoapify lines : the first line is not always the good one.
        $check = array();
        for ($ii = 0; $ii < $nn; $ii++)
        { $check[$ii] = 0;
	      if (fnPlaceSearch($normalizedParseCity[$ii],    $normalizedPlace)) { $check[$ii]++; $flagCITY[$ii]    = true; } else { $flagCITY[$ii]    = false; }
          if (fnPlaceSearch($normalizedParseTown[$ii],    $normalizedPlace)) { $check[$ii]++; $flagTOWN[$ii]    = true; } else { $flagTOWN[$ii]    = false; }
          if (fnPlaceSearch($normalizedParseSuburb[$ii],  $normalizedPlace)) { $check[$ii]++; $flagSUBURB[$ii]  = true; } else { $flagSUBURB[$ii]  = false; }
          if (fnPlaceSearch($normalizedParseVillage[$ii], $normalizedPlace)) { $check[$ii]++; $flagVILLAGE[$ii] = true; } else { $flagVILLAGE[$ii] = false; }
          if (fnPlaceSearch($normalizedParseHamlet[$ii],  $normalizedPlace)) { $check[$ii]++; $flagHAMLET[$ii]  = true; } else { $flagHAMLET[$ii]  = false; }
          if (fnPlaceSearch($normalizedParseCounty[$ii],  $normalizedPlace)
           OR fnPlaceSearch($normalizedParseCounty[$ii].' county',$normalizedPlace) ) { $check[$ii]++; $flagCOUNTY[$ii]  = true; } else { $flagCOUNTY[$ii]  = false; }
          if (fnPlaceSearch($normalizedParseState[$ii],   $normalizedPlace)) { $check[$ii]++; $flagSTATE[$ii]   = true; } else { $flagSTATE[$ii]   = false; }
          if (fnPlaceSearch($normalizedParseCountry[$ii], $normalizedPlace)) { $check[$ii]++; $flagCOUNTRY[$ii] = true; } else { $flagCOUNTRY[$ii] = false; }
          if (fnPlaceSearch($normalizedParseHouse[$ii],   $normalizedPlace)) $check[$ii]++;
          if (fnPlaceSearch($normalizedParseHousenumber[$ii].' '.$normalizedParseStreet[$ii], $normalizedPlace)) $check[$ii]++;
        }

        // select the best line among geoapify lines
        $maxValue = $check[0];
        foreach ($check as $index => $valeur) 
        { if ($valeur > $maxValue) 
          { $maxValue = $valeur;
            $ind      = $index;
          }
        }
// print_r2($check);
// echo '<br><b>normPlace</b>:'.$normalizedPlace.'<br><b>ind</b>:'.$ind;
// echo '<br><b>normCity</b>:'.$normalizedParseCity[$ind].' <b>normTown</b>:'.$normalizedParseTown[$ind].' <b>normSuburb</b>:'.$normalizedParseSuburb[$ind].' <b>normVillage</b>:'.$normalizedParseVillage[$ind].' <b>normHamlet</b>:'.$normalizedParseHamlet[$ind].' <b>normCounty</b>:'.$normalizedParseCounty[$ind].' <b>normState</b>:'.$normalizedParseState[$ind].' <b>normCountry</b>:'.$normalizedParseCountry[$ind];

        // put the select line into final result
        if ($flagCITY[$ind])
        { $arrParsedPlace['city']    = $data['results'][$ind]['city'];
          $arrParsedPlace['county']  = $data['results'][$ind]['county'];
          $arrParsedPlace['state']   = $data['results'][$ind]['state'];
          $arrParsedPlace['country'] = $data['results'][$ind]['country'];
          $arrParsedPlace['suburb']  = fnPlaceSubdiv($data, $ind);
          fnPlaceWithGeo($placeString, $arrParsedPlace, $row[1]);
// echo '<br>1 city';
        }
        elseif ($flagTOWN[$ind])
        { $arrParsedPlace['city']    = $data['results'][$ind]['town'];
          $arrParsedPlace['county']  = $data['results'][$ind]['county'];
          $arrParsedPlace['state']   = $data['results'][$ind]['state'];
          $arrParsedPlace['country'] = $data['results'][$ind]['country'];
          $arrParsedPlace['suburb']  = fnPlaceSubdiv($data,$ind);
          fnPlaceWithGeo($placeString, $arrParsedPlace, $row[1]);
// echo '<br>2 town';
        }
        elseif ($flagSUBURB[$ind])
        { $arrParsedPlace['city']    = $data['results'][$ind]['suburb'];
          $arrParsedPlace['county']  = $data['results'][$ind]['county'];
          $arrParsedPlace['state']   = $data['results'][$ind]['state'];
          $arrParsedPlace['country'] = $data['results'][$ind]['country'];
          $arrParsedPlace['suburb']  = $data['results'][$ind]['suburb'];
          fnPlaceWithGeo($placeString, $arrParsedPlace, $row[1]);
// echo '<br>3 suburb';
        }
        elseif ($flagVILLAGE[$ind])
        { $arrParsedPlace['city']    = $data['results'][$ind]['village'];
          $arrParsedPlace['county']  = $data['results'][$ind]['county'];
          $arrParsedPlace['state']   = $data['results'][$ind]['state'];
          $arrParsedPlace['country'] = $data['results'][$ind]['country'];
          $arrParsedPlace['suburb']  = $data['results'][$ind]['village'];
          fnPlaceWithGeo($placeString, $arrParsedPlace, $row[1]);
// echo '<br>4 village';
        }
        elseif ($flagHAMLET[$ind])
        { $arrParsedPlace['city']    = $data['results'][$ind]['hamlet'];
          $arrParsedPlace['county']  = $data['results'][$ind]['county'];
          $arrParsedPlace['state']   = $data['results'][$ind]['state'];
          $arrParsedPlace['country'] = $data['results'][$ind]['country'];
          $arrParsedPlace['suburb']  = $data['results'][$ind]['hamlet'];
          fnPlaceWithGeo($placeString, $arrParsedPlace, $row[1]);
// echo '<br>5 hamlet';
        }
        elseif ($flagCOUNTY[$ind])
        { $arrParsedPlace['city']    = $data['query']['parsed']['city'];
          $arrParsedPlace['county']  = $data['results'][$ind]['county'];
          $arrParsedPlace['state']   = $data['results'][$ind]['state'];
          $arrParsedPlace['country'] = $data['results'][$ind]['country'];
          $arrParsedPlace['suburb']  = '';
          if ($data['query']['parsed']['city'] != '') $logType = 'W';
          fnPlaceWithGeo($placeString, $arrParsedPlace, $row[1], $logType);
// echo '<br>6 county';
        }
        elseif ($flagSTATE[$ind]) 
        { $arrParsedPlace['city']    = $data['query']['parsed']['city'];
          $arrParsedPlace['county']  = $data['query']['parsed']['county'];
          $arrParsedPlace['state']   = $data['results'][$ind]['state'];
          $arrParsedPlace['country'] = $data['results'][$ind]['country'];
          $arrParsedPlace['suburb']  = '';
          if ($data['query']['parsed']['city'] != ''
           OR $data['query']['parsed']['county'] != '') 
            $logType = 'W';
          fnPlaceWithGeo($placeString, $arrParsedPlace, $row[1], $logType);
// echo '<br>7 state';
        }
        elseif ($flagCOUNTRY[$ind]) 
        { $arrParsedPlace['city']    = $data['query']['parsed']['city'];
          $arrParsedPlace['county']  = $data['query']['parsed']['county'];
          $arrParsedPlace['state']   = $data['query']['parsed']['state'];
          $arrParsedPlace['country'] = $data['results'][$ind]['country'];
          $arrParsedPlace['suburb']  = '';
          if ($data['query']['parsed']['city'] != ''
           OR $data['query']['parsed']['county'] != ''
           OR $data['query']['parsed']['state'] != '') 
            $logType = 'W';
          fnPlaceWithGeo($placeString, $arrParsedPlace, $row[1], $logType);
// echo '<br>8 country';
        }
        else
        {
          fnPlaceWithoutGeo($placeString, $row[1]);
        }
        } // END OF ISOLATED COUNTRY TEST
// echo '<br>'.$arrParsedPlace['city'].' | '.$arrParsedPlace['county'].' | '.$arrParsedPlace['state'].' | '.$arrParsedPlace['country'].' | '.$arrParsedPlace['suburb'];

        $imap++;
  
        $delay = time() - $T_OLD;
        if ($delay >= $timeout)
        { 

          // update progress bar
          $progress = intval( ($_REQUEST['nblines'] + 1000*$imap) / ($_REQUEST['nblines'] + 1000*$NbPlaces)  * 100);

          $_REQUEST['time'] = intval($_REQUEST['time']) + $delay;
          $url = url_request();
          echo "<HTML>";
          echo "<HEAD>";
          echo '<META HTTP-EQUIV="Refresh" CONTENT="1; URL=admin.php'.$url.'&pag=chg&pag2=&ext='.$_REQUEST["ext"].'&encod='.$_REQUEST["encod"].'&ansel='.$_REQUEST["ansel"].'&edi='.urlencode($_REQUEST["edi"]).'&logi='.urlencode($_REQUEST["logi"]).'&irow=&imap='.$imap.'&nblines='.$_REQUEST["nblines"].'&progress='.$progress.'&time='.$_REQUEST["time"].'&gedlang='.$_REQUEST["gedlang"].'"';
          echo "</HEAD>";
          echo "<BODY>";
          return;
        }

        // update progress bar
        $progress = intval( ($_REQUEST['nblines'] + 1000*$imap) / ($_REQUEST['nblines'] + 1000*$NbPlaces)  * 100);
        echo "<script>updateMyBar($progress);</script>\n";
        flush();

      } // END of while
    } // END places processing

    // display 100% progression
    echo "
      <script>
        const barre = document.getElementById('myBar');
        const message = document.getElementById('message');
        const texte = document.getElementById('valueProgress');
        barre.value = 100;
        message.textContent = '".$got_lang['ProCo']."';
        texte.textContent = '100%';
      </script>
      ";

    // completion "even_sour".attr_sourc with first and last name of relation witness
    $query = "
    UPDATE `".$sql_pref."_".$_REQUEST['ibase']."_even_sour` a
    JOIN `".$sql_pref."_".$_REQUEST['ibase']."_individu` b ON a.id_sour = b.id_indi 
    SET a.attr_sourc = CONCAT(b.nom,' ',b.prenom1)
    WHERE a.type_sourc = 'RELA'
    ";
    sql_exec($query,0);

    // delete url who are not jpg or png
    $query = "DELETE FROM `".$sql_pref."_".$_REQUEST['ibase']."_evenement`
    WHERE note_evene REGEXP '^-?[0-9]+$'
    ";
    sql_exec($query,0);

    $query = "DELETE FROM `".$sql_pref."_".$_REQUEST['ibase']."_even_sour`
    WHERE attr_sourc REGEXP '^-?[0-9]+$'
    ";
    sql_exec($query,0);

    // ADD individu.BIRT & DEAT when there are no birth ou burial event, but there is an isolated baptism ou death event, 
    // the date and place of baptism/burial are retrieved as the date and place of birth/death
    $query = "
      UPDATE `".$sql_pref."_".$_REQUEST['ibase']."_individu` a
      JOIN `".$sql_pref."_".$_REQUEST['ibase']."_evenement` b ON a.id_indi = b.id_indi 
      SET a.date_naiss = b.date_evene
      , a.lieu_naiss = b.lieu_evene
      , a.dept_naiss = b.dept_evene
      WHERE b.type_evene = 'CHR'
        AND substring(b.date_evene,1,4) != ''
        AND EXISTS 
		(   SELECT 1
            FROM `".$sql_pref."_".$_REQUEST['ibase']."_evenement` b2
            WHERE b2.id_indi = b.id_indi
              AND b2.type_evene IN ('BIRT', 'CHR')
            GROUP BY b2.id_indi
            HAVING MIN(b2.type_evene) = 'CHR'
               AND MAX(b2.type_evene) = 'CHR'
        )
    ";
    sql_exec($query,0);
  
    $query = "
      UPDATE `".$sql_pref."_".$_REQUEST['ibase']."_individu` a
      JOIN `".$sql_pref."_".$_REQUEST['ibase']."_evenement` b ON a.id_indi = b.id_indi 
      SET a.date_naiss = b.date_evene
      , a.lieu_naiss = b.lieu_evene
      , a.dept_naiss = b.dept_evene
      WHERE b.type_evene = 'BURI'
        AND substring(b.date_evene,1,4) != ''
        AND EXISTS 
		(   SELECT 1
            FROM `".$sql_pref."_".$_REQUEST['ibase']."_evenement` b2
            WHERE b2.id_indi = b.id_indi
              AND b2.type_evene IN ('BURI', 'DEAT')
            GROUP BY b2.id_indi
            HAVING MIN(b2.type_evene) = 'BURI'
               AND MAX(b2.type_evene) = 'BURI'
        )
  	";
    sql_exec($query,0);

  // denormalization "individu". BIRT and DEAT attributes to simplify queries (avoid a systematic join on event)
    $query = 'UPDATE `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` a
    JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` b ON a.id_indi = b.id_indi 
    SET date_naiss = b.date_evene
       ,lieu_naiss = b.lieu_evene
       ,dept_naiss = b.country_evene
    WHERE b.type_evene = "BIRT"';
    sql_exec($query,0);

    $query = 'UPDATE `'.$sql_pref.'_'.$_REQUEST["ibase"].'_individu` a
    JOIN `'.$sql_pref.'_'.$_REQUEST["ibase"].'_evenement` b ON a.id_indi = b.id_indi 
    SET date_deces = b.date_evene
       ,lieu_deces = b.lieu_evene
       ,dept_deces = b.country_evene
    WHERE b.type_evene = "DEAT"';
    sql_exec($query,0);

  // drop temporary tables
    $query = "DROP TABLE `".$sql_pref."_".$_REQUEST['ibase']."_note`" ;
    sql_exec($query);
    $query = "DROP TABLE `".$sql_pref."_".$_REQUEST['ibase']."_id_indi`" ;
    sql_exec($query);
    $query = "DROP TABLE `".$sql_pref."_".$_REQUEST['ibase']."_id_sour`" ;
    sql_exec($query);
    $query = "DROP TABLE `".$sql_pref."_".$_REQUEST['ibase']."_place`" ;
    sql_exec($query,0);
  
  // STATISTICS puts in __base table
    // nb d'individus
    $query = 'SELECT count(*),COUNT(DISTINCT nom), COUNT(DISTINCT prenom1) 
        FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu`';
    $result = sql_exec($query,0);
    $row = mysqli_fetch_row($result);
    $nb_individu = $row[0];
    if ($row[1] == 0) {$row[1] = 1;} 
        $nb_noms = $row[1] - 1;          // on ignore la valeur ''
    if ($row[2] == 0) {$row[2] = 1;}
    $nb_prenoms = $row[2] - 1;
  
    // nb de lieux
    $query = 'SELECT COUNT(DISTINCT CONCAT(IFNULL(lieu_evene,""),IFNULL(dept_evene,""),IFNULL(region_evene,""),IFNULL(country_evene,""),IFNULL(subdiv_evene,"") ))
      FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement`
      WHERE !(lieu_evene IS NULL AND dept_evene IS NULL AND region_evene IS NULL AND country_evene IS NULL AND subdiv_evene IS NULL)
    ';
    $result = sql_exec($query,0);
    $row = mysqli_fetch_row($result);
    $nb_lieux = $row[0];
  
    // main countrris
    $query = 'SELECT country_evene,COUNT(*)
        FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement`
        WHERE country_evene != ""
        GROUP BY country_evene
        ORDER BY 2 DESC
        LIMIT 0,2';
    $result = sql_exec($query,0);
    while ($row = mysqli_fetch_row($result))
    {    $commentaire .= $row[0].', ';
    }
    $commentaire = mb_substr ($commentaire, 0, mb_strrpos($commentaire,', '));
  
    // nb de sources
    $query = 'SELECT count(*) FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_source`';
    $result = sql_exec($query);
    $row = mysqli_fetch_row($result);
    $nb_sources = $row[0];
    $query = 'SELECT COUNT(*) FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement`
    WHERE note_evene != "" AND type_evene NOT IN ("NOTE","FILE","OCCU","CHAN","RFN","RIN")';
    $result = sql_exec($query);
    $row = mysqli_fetch_row($result);
    $nb_sources = $nb_sources + $row[0];
    $query = 'SELECT COUNT(*) FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu`
    WHERE note_indi != ""';
    $result = sql_exec($query);
    $row = mysqli_fetch_row($result);
    $nb_sources = $nb_sources + $row[0];
  
    // nb de medias        -> rappel : en SQL, le verbe UNION fusionne les doublons
    $query = 'SELECT distinct note_evene
        FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement`
        WHERE type_evene = "FILE"
            AND note_evene != ""
        UNION
        SELECT distinct attr_sourc
        FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_even_sour`
        WHERE type_sourc = "FILE"
            AND attr_sourc != ""
        ORDER BY 1';
    $result = sql_exec($query,0);
    $nb_medias = mysqli_num_rows($result);

    // central person for "Royal" base, we afffect Charles III as central person
    $cujus = 1;
    if ($_REQUEST["ibase"] == "royal" OR $_REQUEST["ibase"] == "royal_small") 
    { if ($_REQUEST["ibase"] == "royal")       $cujus = 58;
      if ($_REQUEST["ibase"] == "royal_small") $cujus = 35;
    }

    // update got__base
    if (ServeurLocal() )
      $hide75 = 0;
    else
      $hide75 = 1;
    $query = 'SELECT * FROM `'.$sql_pref.'__base` WHERE base = "'.$_REQUEST['ibase'].'"';
    $result = sql_exec($query,0);
    $row = mysqli_fetch_row($result);
    if (isset($row[0]))
    {    $query = 'UPDATE '.$sql_pref.'__base SET
             id_decujus   = '.$cujus.'
            ,nb_indi      = '.$nb_individu.'
            ,places       = "'.addslashes($commentaire).'"
            ,nb_sources   = "'.$nb_sources.'"
            ,nb_media     = "'.$nb_medias.'"
            ,geneotree_vers = "'.$GeneoTreeRelease.'"
            ,language     = "'.substr($_REQUEST['gedlang'],0,2).'"
            ,nb_lastname  = "'.$nb_noms.'"
            ,nb_places    = "'.$nb_lieux.'"
            ,soft_name    = "'.addslashes($_REQUEST['logi']).'"
            ,soft_editor  = "'.addslashes($_REQUEST['edi']).'"
            ,datemaj      = now()
            ,charset      = "'.$_REQUEST['encod'].'"
            ,ansel        = '.$_REQUEST['ansel'].'
			,maps         = '.$flagMaps.'
            WHERE base = "'.$_REQUEST['ibase'].'"';
        sql_exec($query,0);
    } else
    { $query = 'INSERT INTO '.$sql_pref.'__base VALUES (
             "'.$_REQUEST['ibase'].'"
            ,"'.$cujus.'"
            ,"'.$nb_individu.'"
            ,"'.addslashes($commentaire).'"
            ,"'.$nb_noms.'"
            ,"'.$nb_lieux.'"
            ,"'.$nb_sources.'"
            ,"'.$nb_medias.'"
            ,"'.addslashes($_REQUEST['logi']).'"
            ,"'.addslashes($_REQUEST['edi']).'"
            ,""   /* password */
            ,now()
            ,"'.$GeneoTreeRelease.'"
            ,"'.substr($_REQUEST['gedlang'],0,2).'"
            ,"'.$_REQUEST['encod'].'"
			,'.$_REQUEST['ansel'].'
            ,'.$hide75.'  /* hide75 */
            ,0  /* consang */
			,'.$flagMaps.'
  		  )';
        sql_exec($query,0);
  
        $query = 'SELECT prenom1,prenom2,prenom3,nom,date_naiss FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu` WHERE id_indi = '.$cujus;
        $result = sql_exec($query,0);
        $row = mysqli_fetch_row($result);
    }
  
    // mise a jour des sosa_dyn et du flag consanguinite
    maj_cujus ($_REQUEST['ibase'],$cujus);

  // FINAL DISPLAY
    echo '
	<tr><td align=center><br><b>SUCCESS </b></td></tr>
    <tr><td align=center><b>'.$_REQUEST["ibase"].'.ged </b> '.$got_lang['Termi'].'</td></tr>';
  
    // display OK button
    echo '
    <tr><td align=center height=50><a class=menu_td href=arbre_ascendant.php?ibase='.urlencode($_REQUEST['ibase']).'&lang='.$_REQUEST["lang"].'>OK</a></td></tr>';
  
    // Statistics

    // detect places geolocalized or not
    if ($flagGeoapifyConnection)
    { $nbPlace = 0; $nbPlaceGeo = 0; $nbPlaceNotGeo = 0;
      $LF = @fopen($logFile,"r");
      $WF = @fopen($warnFile,"r");
      while ($line = fgets ($WF))
      { if (strpos($line,'geolocalized') !== false)
          $nbPlaceNotGeo++;
      }
      while ($line = fgets ($LF))
      { if (strpos($line,'geolocalized') !== false)
          $nbPlaceGeo++;
      }
      rewind($LF);
      rewind($WF);
      $nbPlace = $nbPlaceNotGeo + $nbPlaceGeo;
    }
    else
    { $query = '
        SELECT count(DISTINCT CONCAT(lieu_evene,dept_evene,dept_evene,region_evene,country_evene)) 
        FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement`';
      $result = sql_exec($query);
      $row = mysqli_fetch_row($result);
      $nbPlace = $row[0];
    }

    // display statistics
    echo '</table><table name=masquegeneral style="margin-bottom:10px; margin-left:auto; margin-right:auto;">
    <tr><td align=center colspan=2><br><b>Statistics</b></td></tr>';
    echo '
    <tr><td align=right><b>'.$got_tag['INDIS'].'</b> :</td><td>'.$nb_individu.'</td></tr>';
    echo '
    <tr><td align=right><b>places</b> :</td><td>'.$nbPlace;
    if ($flagGeoapifyConnection)
      echo ' (Geolocalized: '.$nbPlaceGeo.', <a  target=_blank href='.$warnFile.'>not geolocalized:</a> '.$nbPlaceNotGeo.')';
    echo '</td></tr>';
    echo '
    <tr><td align=right><b>sources</b> :</td><td>'.$nb_sources.'</td></tr>';
    echo '
    <tr><td align=right><b>medias</b> :</td><td>'.$nb_medias.'</td></tr>';
    echo '
    <tr><td align=right><b>language</b> :</td><td>'.$_REQUEST["gedlang"].'</td></tr>';

    // charset
    if ($_REQUEST["ansel"] == 1)
    { echo '
      <tr><td align=right><b>'.$got_tag["CHAR"].'</b> :</td><td>ANSEL</td></tr>';
    } else 
    { 
      echo '
      <tr><td align=right><b>'.$got_tag["CHAR"].'</b> :</td><td>'.$_REQUEST["encod"].'</td></tr>';
    }
  
    // display upload delay
    $delay = time() - $T_OLD;
    if ($delay < 0) {$delay = 0;}
    $_REQUEST['time'] = intval($_REQUEST['time']) + $delay;

    if ($_REQUEST['time'] > 60)
    { $minutes = floor($_REQUEST['time'] / 60);
      $secondesRestantes = $_REQUEST['time'] % 60;
      $_REQUEST['time'] = $minutes."' ".$secondesRestantes;
    }
    echo '
    <tr><td align=right><b>Upload time</b> :</td><td>'.$_REQUEST['time'].'"<br></td></tr>
    ';
// if (!empty($WF)) {echo '<tr><td><br></td></tr>';while ($line = fgets ($WF)) { if (strpos($line,'geolocalized') !== false) 	echo '<tr><td colspan=2>'.$line.'</td></tr>'; } } // debug
// if (!empty($LF)) {echo '<tr><td><br></td></tr>';while ($line = fgets ($LF)) { if (strpos($line,'geolocalized') !== false) 	echo '<tr><td colspan=2>'.$line.'</td></tr>'; } } // debug
  } 
// END OF BLOCK 2
