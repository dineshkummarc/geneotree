<?php

// Gestion des messages d'erreurs
{   // Environnement production
    ini_set('display_errors', 0);         // Ne pas afficher les erreurs (on les log éventuellement)
    ini_set('display_startup_errors', 0); // Pas d'affichage des erreurs de démarrage
    error_reporting(E_ERROR | E_PARSE);   // Affiche uniquement les erreurs graves et les erreurs de syntaxe
    ini_set('log_errors', 1);
    ini_set('error_log', '/logs/error.log'); 
}

//  Variables initialization
$GeneoTreeRelease = "5.03";
$browserLanguage = substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2);
$page = basename($_SERVER["PHP_SELF"]);
if (isset($_REQUEST['ibase'])) {$ibase = $_REQUEST['ibase'];} else {$ibase="";}
$MarrTags = array("MARR","MARZ","MARC","MARB","MARL","MARS","DIV","ENGA","ORDI");
$today = getdate();
$ADDRC = str_replace(array('.',':'),'',getenv("REMOTE_ADDR"));


/****************************** Utilities fonctions *************************************/

// array_column fonction available for PHP 5.3
function my_array_column($array, $column_key, $index_key = null)
{
  $result = array();
  
  foreach ($array as $item) {
      if (isset($item[$column_key])) {
          if ($index_key !== null && isset($item[$index_key])) {
              $result[$item[$index_key]] = $item[$column_key];
          } else {
              $result[] = $item[$column_key];
          }
      }
  }
  
  return $result;
}

// print_r function improve with best display
function print_r2($array, $level = 0) 
{

	// Début de l'arborescence avec une indentation pour chaque niveau d'imbrication
    $indent = str_repeat('  ', $level);

    if (!is_array($array) && !is_object($array)) 
	{   echo $indent . htmlspecialchars($array) . "\n";
        return;
    }

    echo $indent . "<ul>\n";
    foreach ($array as $key => $value) 
	{   // Gestion des clés (objets ou tableaux) et des valeurs
        echo $indent . '  <li><strong>' . htmlspecialchars($key) . '</strong> : ';
        if (is_array($value) || is_object($value)) 
		{
            // Si c'est un tableau ou objet, on appelle la fonction de façon récursive
            echo "\n";
            print_r2($value, $level + 1);
        } else 
		{
            // Sinon, afficher directement la valeur
            if ($value == null) $value ="";
			echo htmlspecialchars($value);
        }
        echo "</li>\n";
    }
    echo $indent . "</ul>\n";
}

function print_row($result)
{  $numColumns = mysqli_num_fields($result);
   echo '<table>';
   while ($row = mysqli_fetch_row($result) )
   {  echo '<tr>';
      for ($i=0; $i < $numColumns; $i++)
	  { echo '<td style="border-bottom:1px solid black;">'.$row[$i].'</td>';
	  }
	  echo '</tr>';
   }
   echo '</table>';
}

function recup_color_sexe($sexe)
{ if ($sexe == "F")     return "#AC0253";    // RGB 172,2,83
  elseif ($sexe == "M") return "#000080";    // RGB 0,0,128
  else                  return "#606060";
}

// recup sex color for fpdf library in decimal
function recup_color_sexe_decimal($sexe)
{ if ($sexe == "F")     return array(172,2,83);
  elseif ($sexe == "M") return array(0,0,128);
  else                  return array(60,60,60);
}

function recup_liste_css()
{
    $handle = opendir(getcwd().'/themes');
    while (($file = readdir($handle)) != FALSE) 
    {    $match = '';
        $count_preg = preg_match_all('([0-9]+)',$file,$match);
        if ($count_preg == 0) {$match[0][0] = "";}
        if ($file != "." and $file != ".." and mb_strpos($file,"css") != 0 and $match[0][0] == "")
        {    $liste_css[] = mb_substr($file,0,mb_strlen($file)-4);
        }
    }
    closedir($handle);
    sort($liste_css);
    return $liste_css;
}

function url_request()
{  return '?ibase='.urlencode($_REQUEST["ibase"]).'&lang='.$_REQUEST["lang"];
}

function url_post()
{ $Url = 
   '?ibase='.urlencode($_REQUEST["ibase"])
  .'&lang='.$_REQUEST["lang"]
  .'&theme='.$_REQUEST["theme"]
  .'&id='.$_REQUEST["id"]
  .'&pag='.$_REQUEST["pag"]
  .'&ext='.$_REQUEST["ext"]
  ;

  if (basename($_SERVER['PHP_SELF']) != "admin.php")
  $Url .=
   '&fid='.$_REQUEST["fid"]
  .'&sex='.$_REQUEST["sex"]
  .'&sosa='.$_REQUEST["sosa"]
  .'&rech='.$_REQUEST["rech"]
  .'&type='.$_REQUEST["type"]
  .'&orient='.$_REQUEST["orient"]
  .'&nbgen='.$_REQUEST["nbgen"]
  .'&implex='.$_REQUEST["implex"]
  .'&SpeGe='.$_REQUEST["SpeGe"]
  .'&pori='.$_REQUEST["pori"]
  .'&gedlang='.$_REQUEST["gedlang"]
  .'&gedlangForm='.$_REQUEST["gedlangForm"]
  .'&time='.$_REQUEST["time"]
  ;

  echo $Url;
}

function MyUrl($url, $encode = TRUE) 
{    if (PHP_OS !== "WINNT") 
    { //$url = utf8_decode($url);
      $url = mb_convert_encoding($url, 'ISO-8859-1', 'UTF-8'); // UTF-8 => ISO
    }
    if ($encode)
    { $url = urlencode($url);
      $url = str_replace('+', '%20', $url); // pour le symbole espace, on met un %20 au lieu du +
      $url = str_replace('%2F', '/', $url); // pour le symbole / convertit en %2F, on remet un /
    }
    return $url;
}

function recup_format()
{   if (substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,5) == "en-US") {return "Letter";} else {return "A4";};
}

function ServeurLocal()
{   $Local = FALSE;
    $enligne = getenv("REMOTE_ADDR");
    $Local = ($enligne == '127.0.0.1' || $enligne == '192.168.181.2' || $enligne == '192.168.1.2' || $enligne == '192.168.31.22' || $enligne == '::1');
    return $Local;
}

/***************************** SQL functions ***********************************/

function sql_connect()
{ global $sql_pref, $pool, $passe_admin, $collate, $hide75;
  
  include ("config.php");
  
  // Connexion à MySqli
  if (version_compare(PHP_VERSION, '5.3.0', '<')) 
  { $pool = mysqli_connect($sql_host,$sql_user,$sql_pass)
      or die ("<br>User : <b>".$sql_user."</b><br>Verify config.php.");
  } else 
  { $pool = mysqli_connect($sql_host,$sql_user,$sql_pass)
      or die ("<br>User : <b>".$sql_user."</b><br>Verify config.php.");}

  // declare UTF-8 dialog  
  mysqli_query($pool,"SET NAMES 'UTF8'");   //on signale a MySql que l'on dialogue en UTF-8. MySql ne detecte pas lui-meme les caracteres qu'on lui envoie.

  // create database
  $query = "SHOW DATABASES LIKE '".$sql_base."'";
  $result = mysqli_query($pool,$query);
  if (mysqli_num_rows($result) == 0)
  { $query = "CREATE DATABASE ".$sql_base;
    mysqli_query($pool,$query);
    if (mysqli_errno($pool) != 0) echo mysqli_errno($pool)." : ".mysqli_error($pool)."<BR>";
  }

  // detect collation 
  $result = mysqli_query($pool,"SELECT VERSION() AS version");
  $row = mysqli_fetch_assoc($result);
  $version = $row["version"];
  $collate = ' character set utf8 collate utf8'; 
  if (version_compare($version, '8.0.0', '>=')) 
  { $collate = ' character set utf8mb3 collate utf8mb3'; }
  if (version_compare($version, '8.0.0', '>=') || strpos($version, 'MariaDB') !== false)
  { $collate = ' character set utf8mb4 collate utf8mb4'; }

  // use geneotree
  $query = "use ".$sql_base;
  mysqli_query($pool,$query);
  if (mysqli_errno($pool) != 0) echo mysqli_errno($pool)." : ".mysqli_error($pool)."<BR>";

  // Recuperation $hide75
  $hide75 = 1;
  if (!empty($_REQUEST["ibase"]))
  { $query = 'SELECT hide75 FROM '.$sql_pref.'__base WHERE base ="'.$_REQUEST["ibase"].'"';
    $result = mysqli_query($pool,$query);
    if (mysqli_num_rows($result))
    { $row = mysqli_fetch_row($result);
      $hide75 = $row[0];
    }
  }

  return $pool;
}

function sql_exec($query,$debug = 0)
{ global $pool;

  if ($debug != 0)
  { echo '<br>'.time().'<br>'.$query;
  }

  if ($debug != 2)
  {   $result = mysqli_query($pool,$query);
      if (mysqli_errno($pool) != 0 AND mysqli_errno($pool) != 1062)
      {   echo "<BR>".mysqli_errno($pool)." : ".$query;
          $result = NULL;
      }
  }
  else $result = false;

  return $result;
}

function sql_fetch_row($result)
{   if ($result) {return $row = mysqli_fetch_row($result);} else {return $row = NULL;}
}

function sql_num_rows($result)
{   if ($result) {return $row = mysqli_num_rows($result);} else {return $row = NULL;}
}

/***************************** GEDCOM functions  *************************************/

function maj_cujus ($base,$fid)
{ global $sql_pref;
  global $ancetres;
  global $ancetres_fs;
  global $communs;
  global $cpt_generations;
  global $ADDRC;  // pour passer la variable a recup_ascendance et g__pref

  require_once ("_get_ascendancy.php");
  
  $ancetres[][] = ''; $descendants = '';$cpt_generations = 0;
  $ancetres['id_indi'][0] = $fid;
  $nb_generations = 100;
  recup_ascendance($ancetres,0,40,'');  
  
  // si ancetres communs detectes, alors le de-cujus est consanguin
  if (isset($communs ['id'][0]))   
    $consang = 1;
  else 
    $consang = 0;

  $query = '
    UPDATE '.$sql_pref.'__base 
    SET consang = '.$consang.' 
    WHERE base = "'.$base.'"';
  sql_exec($query);

  // mise a jour des nouveaux sosas
  $i = 0;
  if ($ancetres['id_indi'][0] != "")
  { // ahnen initialization in indiv table
    $query = "UPDATE `".$sql_pref."_".$base."_individu`
              SET sosa_dyn = 0";
    sql_exec($query);
  
    while ($i < count($ancetres['id_indi']))
    { $query = "UPDATE `".$sql_pref."_".$base."_individu`
                SET sosa_dyn = ".$ancetres['sosa_d'][$i]."
                WHERE id_indi = ".$ancetres['id_indi'][$i];
      sql_exec($query,0);
      $i++;
    }
    // udpate databases table
    $query = '
      UPDATE '.$sql_pref.'__base 
      SET id_decujus = '.$fid.'
      WHERE base = "'.$base.'"';
    sql_exec($query,0);
  }
}

function existe_sosa()
{    global $sql_pref;

    $query = 'SELECT distinct SOSA_DYN FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_individu`';
    $result = sql_exec($query);
    if (@mysqli_num_rows($result) !== NULL)  // si de cujus non calcule, inhiber la fonctionnalite sosa
    {    return TRUE;
    } else 
    {    return FALSE;
    }
}

/****************************  Business functions  ***********************************/

function fctAhnenCell ($ahnen)
{
  $ahnenLine = '';
  if ($ahnen != 0)
    if ($ahnen < 512)  // small ahnen
      return $ahnen;
    else
      return 'Gen'.strlen(decbin($ahnen));
}

function recup_identite($id_indi,$base)
{    global $sql_pref;

    $query = 'SELECT 
	CONCAT(nom," ",prenom1)
	,date_naiss
	,lieu_naiss
	,sexe
    FROM '.$sql_pref.'_'.$base.'_individu
    WHERE id_indi = '.$id_indi;
    $result = sql_exec($query);
    $row = mysqli_fetch_row($result);
    return $row;
}

function getDatePrefix($YYYYMMDD, $NoSeq, $flagTag, $flagHide75, $yearOnly)
{ 
  /* call by displayDate  */

  global $got_lang, $got_tag, $PREFIX, $INTER; 

  // variables initialization
  $DAY=""; $MONTH=""; $YEAR="";

  if ($YYYYMMDD != "") 
  { 
    // day exists
    if (substr($YYYYMMDD,6,2) != "00")
    { 
      // delete the first character "0" if exists
      if (substr($YYYYMMDD,6,1) == "0") 
        $DAY = substr($YYYYMMDD,7,1); 
      else
        $DAY = substr($YYYYMMDD,6,2); 

      // add prefix "at" for first date
      if ($NoSeq == "1") 
           $PREFIX .= " ".$got_lang["Le"];
      else $INTER  .= " ".$got_lang["Le"];

    // day not exists
    } else 
    { $DAY = "";

      // add prefix "on" if not hide75
      if ($flagTag == "0") 
        if ($NoSeq == "1") 
             $PREFIX .= " ".$got_lang["En"];
        else $INTER  .= " ".$got_lang["En"];
    }

    if (substr($YYYYMMDD,4,2)) 
      $MONTH = $got_tag[substr($YYYYMMDD,4,2)];

    if ($flagHide75 == "1") 
      $YEAR = "X";
    else 
      $YEAR  = intval(substr($YYYYMMDD,0,4));
  }

  if ($yearOnly)
  { $PREFIX = "";
    $INTER  = "";
    return $YEAR;
  }
  else
    return $DAY." ".$MONTH." ".$YEAR;
}


function fctDisplayDateExcel($date)
{ 
  /* Input format  : "20000400 20010300 BET AND" 
     Output format : "2000/04"
     If month = '00' => '2000'
     If day   = '00' => '2000/04'
     else "YYYY/MM/DD"
  */

  if (empty($date))
    return '';

  if ($date == "! Yes")
    return 'Yes';

  // date with year only
  if (substr($date,4,2) == "00")
    return substr($date,0,4);

  // date with year and month only
  elseif (substr($date,6,2) == "00")
    return substr($date,0,4).'/'.substr($date,4,2);

  // date complete
  else 
    return substr($date,0,4).'/'.substr($date,4,2).'/'.substr($date,6,2);
}

function displayDate($date, $yearOnly = false, $withOutPrefix = false)
{ 
  /* Output format :                     [PREFIX] [DAY1] [MONTH1] [YEAR1] [INTER] [DAY2] [MONTH2] [YEAR2]
     Ex: "20000400 20010300 BET AND" => "between"  "10"    "apr"  "2000"   "and"    ""   "march"   "2001"
  */

  if (empty($date))
    return '';

  if (substr($date,0,1) == '!')
    return $date;

  global $got_lang, $got_tag, $hide75, $today, $PREFIX, $INTER;

  // variables initialization
  $PREFIX=""; $DATE1=""; $INTER="";  $DATE2="";
  $flagTag = false;

  $part = explode(" ",$date);
  if (empty($part[1])) $part[1] = '';
  if (empty($part[2])) $part[2] = '';
  if (empty($part[3])) $part[3] = '';
  if (empty($part[4])) $part[4] = '';
  if (empty($part[5])) $part[5] = '';

  // option not display recent dates
  $flagY1 = false; $flagY2 = false;

  if ($hide75 == 1)
  { if (intval(substr($part[0],0,4)) >= $today['year'] - 75)
    { $flagY1 = 1;
    } 
    if (intval(substr($part[1],0,4)) >= $today['year'] - 75)
    { $flagY2 = 1;
    }
  }

  // prefix
  if (!$yearOnly AND !$withOutPrefix)
  { if (!empty($part[2])) 
      $PREFIX  = $got_tag[$part[2]]; 
    if ($part[3] == 'ABT') 
      $PREFIX .= " ".$got_tag[$part[3]];
  }

  // first date
  if (!empty($part[2]))
    $flagTag = true;
  $DATE1 = getDatePrefix($part[0],"1",$flagTag,$flagY1,$yearOnly);

  // inter
  if (!$yearOnly AND !$withOutPrefix)
  {
    if ($part[3] == "AND" OR $part[3] == "TO") $INTER  = $got_tag[$part[3]];
    if ($part[4] == "AND" OR $part[4] == "TO") $INTER  = $got_tag[$part[4]];
    if ($part[5] == "ABT")                     $INTER  .= " ".$got_tag[$part[5]];
  }

  // second date
  if ($part[3] == "AND" OR $part[3] == "TO" OR $part[4] == "AND" OR $part[4] == "TO") 
    $flagTag = true;
  if ($part[1] != "00000000")
    $DATE2 = getDatePrefix($part[1],"2",$flagTag,$flagY2,$yearOnly);

  // specific French  
  if (trim($PREFIX) == "de le") $PREFIX = "du";
  if (trim($PREFIX) == "à le")  $PREFIX = "au";
  if (trim($INTER)  == "de le") $INTER  = "du";
  if (trim($INTER)  == "à le")  $INTER  = "au";

  return trim($PREFIX." ".$DATE1." ".$INTER." ".$DATE2);
}

function displayAge ($BIRTdate,$DEATdate, $flagShortFormat = false)
{
/*
  call from ascendant_pdf (short format), fiche_pdf and descandancy_pdf
  displayAge is duplicated in javascript in script.js, call from card and statistics (short format)

  Person alive
  ------------
  if DEATdate is empty and BIRTdate is less 99 years AND différent of "Yes", the person is considered alive.
    so DEATdate is remplace by system date.

  Output formats
  --------------                $PREFIX         $ABT   $YEARn  $YEAR  $AND  $MONTHn  $MONTH
  dates are completed
    ShortFormat                                        [YYY]   "yrs"          [mm]     "m"
    Not StatFormat
      dead person               "age at death"         [YYY]  "years" "and"   [mm]  "months"
      alive person              "age"                  [YYY]  "years" "and"   [mm]  "months"

  dates are not completed
    ShortFormat                                        [YYY]   "yrs"  "~"
    Not StatFormat
      dead person               "age at death" "about" [YYY]  "years"
      alive person              "age"          "about" [YYY]  "years"

*/

  global $got_lang, $got_tag, $today;

  if (empty($BIRTdate)) return '';
  if (empty($DEATdate)) $DEATdate = '';

  // variables initialization
  $PREFIX=''; $ABT=''; $YEARn=''; $YEAR=''; $AND=''; $MONTHn =''; $MONTH = '';
  $flagAlive = false;
  $BIRTdate = substr($BIRTdate,0,8); $DEATdate = substr($DEATdate,0,8);

  // empty or wrong dates
  if (!$BIRTdate OR substr($BIRTdate,0,1) == '!' OR substr($DEATdate,0,1) == '!')
    return '';

  // detect person alive
  if ($BIRTdate AND !$DEATdate)
    if (substr($BIRTdate,0,4) > $today['year'] - 99)
    {  $DEATdate  = substr('000'.$today['year'],-4).substr('0'.$today['mon'],-2).substr('0'.$today['mday'],-2);
       $flagAlive = true;
    }
    else
      return '';

  // Calculate the différence between BIRT and DEAD date
  $d1 = DateTime::createFromFormat('Ymd', $BIRTdate);
  $d2 = DateTime::createFromFormat('Ymd', $DEATdate);
  $interval = $d1->diff($d2);

  // put year and month results
  $YEARn  = $interval->y;
  $MONTHn = $interval->m;

  // dates complete treatment
  if (substr($BIRTdate,4,2) != '00' AND substr($BIRTdate,6,2) != '00'
  AND substr($DEATdate,4,2) != '00' AND substr($DEATdate,6,2) != '00'
     )
  { $YEAR  = $got_lang['Ans'];
    $MONTH = $got_lang['Mois'];
    if ($flagShortFormat)
    { $YEAR   = $got_lang['Annee'];
      $MONTH  = 'm';
    }
    else
    { $AND   = $got_tag['AND'];
      if ($flagAlive)
        $PREFIX = $got_lang['Age'];
      else
        $PREFIX = $got_lang['AgeDe'];
    }
  }

  // dates not complete treatment
  else
  { $YEAR   = $got_lang['Ans'];
    $MONTHn = '';
    if ($flagShortFormat)
    { $YEAR   = $got_lang['Annee'];
      $AND = '~';
    }
    else
    { $ABT = $got_lang['Envir'];
      if ($flagAlive)
        $PREFIX = $got_lang['Age'];
      else
       $PREFIX = $got_lang['AgeDe'];
	}
  }

  if ($ABT != '') $ABT .= ' ';
  return trim($PREFIX.' '.$ABT.$YEARn.' '.$YEAR.' '.$AND.' '.$MONTHn.' '.$MONTH);
}

function getFirstName($fullName) 
{ $fullName = trim($fullName);
  if (isset($fullName[0])) 
  { $parts = explode(' ', $fullName);

    // Trouver la partie qui est le prénom
    foreach ($parts as $part) 
	{ // Vérifier que le premier caractère est une majuscule et le second une minuscule en utilisant UTF-8
      if (mb_strtoupper(mb_substr($part, 0, 1, 'UTF-8'), 'UTF-8') === mb_substr($part, 0, 1, 'UTF-8') &&
          mb_strtolower(mb_substr($part, 1, 1, 'UTF-8'), 'UTF-8') === mb_substr($part, 1, 1, 'UTF-8')) 
	  {  return $part; // Retourner le prénom
      }
    }
  }
  return null; // Retourner null si aucun prénom n'est trouvé
}

function afficher_cellule ($id_cell, $id_indi, $sosa_dyn, $nom, $sexe, $profession, $date_naiss, $lieu_naiss, $date_deces, $lieu_deces, $central=NULL)
{
/* 
ascendance : photo à gauche, texte à droite
mixte : 3 lignes => nom, photo, naissance profession
descendance : idem ascendance, sans les retours de lignes
*/
  if (!$id_indi) 
    return;

  global $sql_pref, $got_lang, $page, $hide75;

  if ($page == "arbre_mixte.php") {$FlagMixTree = TRUE;} else {$FlagMixTree = FALSE;}

  // div de navigation : 4 arbres
  afficher_fleche_nav($id_cell, $id_indi);

  echo '
  <table style="border-collapse:collapse; border-spacing:0; margin:0px;">';

  // $ii => numero de la ligne dans la cellule (de 0 à 6)
  for ($ii = 0; $ii < 6; $ii++)
  { if ($ii==0 OR $ii==2 OR $ii==4)
    {    echo '
        <tr>';}

    if ($ii !== 4 AND $FlagMixTree)
    {    echo '
        <td align=center style="padding:0px;">';
    } else
    {    echo '
        <td style="vertical-align:top;">';
    }

    if (($ii == 0 AND $FlagMixTree) OR  ($ii == 1 AND !$FlagMixTree))
    { 
      // sosa (en entier pour le personnage central, losange pour les autres (le n° sosa peut être très grand)
      echo '<font color=red><b> '.fctAhnenCell($sosa_dyn).'</b></font>';

      // nom prenom
      echo '
      <a href=# onclick=displayFiche('.$id_indi.',"'.$sexe.'")>';
      echo '
      <font color='.recup_color_sexe($sexe).'><b>'.$nom;
      if ($FlagMixTree)
      {    echo '
          <br>';
      }
      echo '
      </b></font></a>';
      echo "&ensp;";
    }

    if (($ii == 0 AND !$FlagMixTree) OR  ($ii == 2 AND $FlagMixTree))
    {
      // photo
      $query = 'SELECT note_evene,filelarg/filehaut FROM `'.$sql_pref.'_'.$_REQUEST['ibase'].'_evenement`
      WHERE id_indi = '.$id_indi.' and type_evene = "FILE"';
      $result = sql_exec($query,0);
      $row = sql_fetch_row($result);
      if (isset($row[0]) AND isset($row[1])) // exist filename with dimensions of thumbnail
      { 
        // si image trop large, on force la largeur pour préserver la taille des cellules des arbres
        if ($FlagMixTree) // mix tree
    	{ $HeightPicture = 130;
        } else 
    	{ $HeightPicture = 54;
        }

        if ($row[1] > 1) 
		{ $HeightPicture = $HeightPicture / $row[1];
	    }
        echo '<img height='.$HeightPicture.' src='.MyUrl("picture/".$_REQUEST["ibase"].'/'.$row[0]).'>';
      }
    }

    if (($ii == 1 AND !$FlagMixTree) OR  ($ii == 4 AND $FlagMixTree))
    { if (!$FlagMixTree) 
	  { echo '<br>';
      } // pas de saut de ligne pour l'arbre mixte.

      // date lieu de naissance
      if (!empty($date_naiss))
      {	echo $got_lang['Ne'];
      	if ($_REQUEST['lang'] == "fr" and $sexe == 'F') {echo 'e';}
      	echo ' <b>'.displayDate($date_naiss,"YES").'</b>';
      }
      if (!empty($lieu_naiss))
      {    echo ' '.$got_lang['Situa'].' '.$lieu_naiss;
      }

      // age, deces 
      if ($_REQUEST['pag'] == "DEAT")
      {   if (!empty($date_deces))
      	{	echo '<br>'.html_entity_decode ("&#134", ENT_COMPAT, "UTF-8");
      		echo ' <b>'.displayDate($date_deces,"YES").'</b>';
      	}
      	if (!empty($lieu_deces))
      	{    echo ' '.$got_lang['Situa'].' '.$lieu_deces;
      	}
      } elseif ($_REQUEST['pag'] == "AGE")
      { echo '
        <br>';
        if ($date_deces != '')
        {    echo html_entity_decode ("&#134", ENT_COMPAT, "UTF-8").' <b>'.displayAge($date_naiss,$date_deces).'</b>';
        }
        echo ' <b>'.$profession.'</b>';
      }
    }
        echo '
        </td>';
        if ($ii==1 OR $ii==3 OR $ii==5)
        {    echo '
            </tr>';
        }
    }
    echo '</table>';
}

function afficher_fleche_nav($id_cell, $id_indi)
{ global $url;
  global $got_lang;
  global $page;

  if ($page == "arbre_mixte.php") 
  { $decalagegauche = -80;
  } elseif ($page == "arbre_descendant.php") 
  { $decalagegauche = -45;
  } else // ascendandy tree
  { $decalagegauche = 0;
  }

  echo '
  <div style="position:absolute; visibility:hidden; z-index:1;">
    <div id="'.$id_cell.'" class=invisible align=left style="position:absolute;
         left:'.$decalagegauche.'px; 
         top:35px; 
		 width:290px;">
      <section class=menu>
        <ul style="list-style:none;">
          <li><a href=arbre_mixte.php'.$url.'&id='.$id_indi.'&fid='.$_REQUEST['fid'].'><img src=themes/fleche_milieu.png heigth=20 width=20> '.$got_lang['ArMix'].'</a></li>
          <li><a href=arbre_ascendant.php'.$url.'&id='.$id_indi.'&fid='.$_REQUEST['fid'].'><img src=themes/fleche_haut.png heigth=20 width=20> '.$got_lang['ArAsc'].'</a></li>
          <li><a href=arbre_descendant.php'.$url.'&id='.$id_indi.'&fid='.$_REQUEST['fid'].'><img src=themes/fleche_bas.png heigth=20 width=20> '.$got_lang['ArDes'].'</a></li>';
          if ($_REQUEST['csg'] == 1)
          {    echo '<li><a href=arbre_consanguinite.php'.$url.'&id='.$id_indi.'&fid='.$_REQUEST['fid'].'><img src=themes/fleche_consang.png heigth=20 width=20> '.$got_lang['EtCon'].'</a></li>';
          }
      echo '
        </ul>
      </section>
    </div>
  </div>';
}

function afficher_lien_indiv ($id_indi, $sosa, $nom, $prenom2 = NULL, $sexe="M", $base = NULL, $source = NULL, $lg = 100)
{   global $got_lang;
	global $page;

// lien de l'individu
    echo '<a href='.$page;
    url_post();
    echo '&fid='.$id_indi;
    echo '><font color='.recup_color_sexe($sexe).'><b>'.mb_substr($nom.' '.$prenom2,0,$lg).'</b></font>
    <span>'.$got_lang['IBFih'].'</span>
    </a>';

//  sosa
    if ($sosa != 0)
    {    echo '<font color=red><b>'.$sosa.'</b></font>';
    }

// logo S vert existence source. Pas alimenté dans les arbres, car pas l'info dans la requete SQL. Alimenté dans source.
    if ($source)
    {    echo '<img src="themes/source.png"><span>'.$got_lang['Sourc'].'</span></a>&nbsp;';
    }
}

function afficher_liste_deroulante($nom_liste, $cont_liste, $select, $flag_submi = "YES")
{    echo '<select name='.$nom_liste;
    if ($flag_submi == "YES")    {echo ' onchange="submit();"';}
    echo '>';
    $count_liste = count($cont_liste);echo $count_liste;
    for ($ii = 0; $ii < $count_liste; $ii++)
    {    echo ' <option class=ligne_tr2 ';
        if ($select == $cont_liste[$ii]) {echo ' SELECTED';}
        echo ' >'.$cont_liste[$ii].'</option>';
    }
    echo '</select>';
}

function afficher_menu($nom_liste, $lib, $code, $nb=array())
{   global $page;

    if (!isset($_REQUEST['type'])) {$_REQUEST['type'] = "";}
    if (!isset($_REQUEST['orient'])) {$_REQUEST['orient'] = "";}
    if (!isset($_REQUEST['nbgen'])) {$_REQUEST['nbgen'] = 4;}
    if (!isset($_REQUEST['implex'])) {$_REQUEST['implex'] = "";}
    if (!isset($_REQUEST['SpeGe'])) {$_REQUEST['SpeGe'] = "";}

    for ($ii = 0; $ii < count($code); $ii++)
    {    if ($_REQUEST[$nom_liste] == $code[$ii]) {$class = "menu_encours";} else {$class = "menu_td";}

        echo '<a class='.$class.' href='.$page;
        url_post();
		if (!isset($nb[$ii])) {$nb[$ii] = "";}
        echo '&'.$nom_liste.'='.$code[$ii].'>'.$lib[$ii].'</a><span style="font-size:0.8em;">'.$nb[$ii].'</span>&ensp;
        ';
    }
}

function afficher_trait_horizontal($coor_trait)
{    echo '<div style="position:absolute; left:'.$coor_trait[0].'px; top: '.$coor_trait[1].'px; width: '.$coor_trait[2].'px;" class=trait_arbre_horiz></div>';
}

function afficher_trait_vertical($coor_trait)
{    echo '<div style="position:absolute; left:'.$coor_trait[0].'px; top: '.$coor_trait[1].'px; height: '.$coor_trait[2].'px;" class=trait_arbre_verti></div>';
}