<?php
header('Content-type: text/html; charset=utf-8');
header('Cache-Control: no-cache');
header('X-Accel-Buffering: no'); // désactive le buffering avec Nginx
ob_implicit_flush(true);
ob_end_flush();

// detect max execution time of the server to reinitialized gedcom upload with multi-passes.
// Minus 2 seconds, cause sometimes 1 second is not enough with the round trips with the geoapify server
$timeout = ini_get('max_execution_time');
if ($timeout == NULL)
  $timeout = 28;
else
  $timeout = $timeout - 2;
// echo 'timeout:'.$timeout; //debug

// In mod_php mode (Apache integrated into WAMP), the PHP process can reuse the same instance 
// for several successive requests in the same thread.
// The same internal timeout context continue to increment.
set_time_limit(0);

require_once ("_functions.php");
require_once 'LanguageDetect/Text/LanguageDetect.php';
// print_r2($_REQUEST); // debug

// if PHP version < 8.1.0, force to read end of line to Macintosh files
if (version_compare(PHP_VERSION, '8.1.0', '<'))
  ini_set('auto_detect_line_endings', '1');

// default language
if (!isset($_REQUEST['lang']))
  $_REQUEST['lang'] = 'en';

// default theme
if (empty($_REQUEST['theme'])) 
  $_REQUEST['theme'] = 'wiki';

require_once ("languages/".$_REQUEST['lang'].".php");

$pool = sql_connect();
include ("config.php");  // $passe_admin
if (empty($_COOKIE["MdpCookie"])) {$_COOKIE["MdpCookie"] = "";}
$cle = "6hBm3dEV4XwrUAMbriE34yf2h65k6fC786SG97QU";

if (!Serveurlocal() AND $_COOKIE["MdpCookie"] != crypt($passe_admin, $cle) )
{ if (empty($_REQUEST["pass"])) {$_REQUEST["pass"]= "";}

  if ($_REQUEST["pass"] == $passe_admin)
  {  @setcookie("MdpCookie",crypt($passe_admin, $cle));
  } else

  {  if ($_REQUEST["pass"] == "" OR ($_REQUEST["pass"] != "" AND $_REQUEST["pass"] != $passe_admin) )
     {
        echo '
        GeneoTree administrator password
        <form name=mdp method="post">
        <table><tr>
        <td><input type="password" name="pass"></td>
        <td><input type="Submit" value="Entrer"></td>
        </tr></table>
        </form>';

        echo '<script language="JavaScript" type="text/javascript">
        document.mdp.pass.focus();
        </script>';
     }
     if ($_REQUEST["pass"] != "" AND $_REQUEST["pass"] != $passe_admin)
     {  echo '
        The password is wrong. Please enter it again.
        ';
     }
     return;
  }
}

function compareInsensitive($a, $b)
{
  // Comparaison insensible à la casse des valeurs
  return strcasecmp($a["nom"], $b["nom"]);
}

function radio_bouton($nom_liste, $cont_lib, $cont_code, $select)
{ for ($ii = 0; $ii < count($cont_code); $ii++)
  { echo '<input type=radio name='.$nom_liste.' value='.$cont_code[$ii].' id='.$nom_liste.$ii;
    if ($cont_code[$ii] == $select) 
      echo ' checked="checked"';
    echo '>';
    echo '<LABEL for="'.$nom_liste.$ii.'">'.$cont_lib[$ii].'</LABEL>&emsp;';
  }
}

function searchIgnoreCase($needle, $haystack)
{ foreach ($haystack as $key => $value) 
  { if (strcasecmp($needle, $value) === 0) 
      return $key;
  }
  return false;
}

/****************************************** BEGIN OF MAIN SCRIPT **************************************************************/
// initialisation des variables POST
if (!isset($_REQUEST['ext']))        $_REQUEST['ext']      = "";
if (!isset($_REQUEST['ibase']))      $_REQUEST['ibase']    = "";
if (!isset($_REQUEST['type']))       $_REQUEST['type']     = "";
if (!isset($_REQUEST['id']))         $_REQUEST["id"]       = "";
if (!isset($_REQUEST['hide75']))     $_REQUEST['hide75']   = 1;
if (!isset($_REQUEST['pag']))        $_REQUEST['pag']      = "";
if (!isset($_REQUEST['pag2']))       $_REQUEST['pag2']     = "";
if (!isset($_REQUEST['password']))   $_REQUEST['password'] = "";

?>
  <script>
  // php variables
  var Annee        = "<?php echo $got_lang["Annee"];?>";
  var gotLang      = <?php echo json_encode($got_lang);?>;
  var gotTag       = <?php echo json_encode($got_tag);?>;
  var HrefBase     = "<?php echo '&lang='.$_REQUEST["lang"].'&pag=chg&irow=0&pag2=';?>";
  var sql_pref     = "<?php echo $sql_pref;?>";
  var ibase        = "<?php echo $_REQUEST["ibase"];?>";
  var PagePhp      = "admin.php";
  </script>

  <script src="script.min.js"></script>

<?php
$url = url_request();

// Create table got__base
$query = "SHOW TABLES FROM ".$sql_base." LIKE '".$sql_pref."__base'";
$ResultBase = sql_exec($query,0);

if (sql_num_rows($ResultBase) == 1)
{ $query = "SHOW COLUMNS FROM ".$sql_pref."__base LIKE 'maps'";
  $ResultVersion = sql_exec($query,0);

  if (sql_num_rows($ResultVersion) == 0)
  $query = "DROP TABLE ".$sql_pref."__base";
  sql_exec($query);
}
if (sql_num_rows($ResultBase) == 0 OR sql_num_rows($ResultVersion) == 0)
{
  $query = "
  CREATE TABLE ".$sql_pref."__base (
   base           varchar(250) NOT NULL
  ,id_decujus     int(10) unsigned NOT NULL default '0'
  ,nb_indi        int (6) unsigned
  ,places         varchar(120)
  ,nb_lastname    int
  ,nb_places      int
  ,nb_sources     INT(6)
  ,nb_media       INT(6)
  ,soft_name      varchar(42)
  ,soft_editor    varchar(42)
  ,password       varchar(42)
  ,datemaj        datetime
  ,geneotree_vers char(4)
  ,language       char(2)
  ,charset        varchar(5)
  ,ansel          bit
  ,hide75         bit
  ,consang        bit
  ,maps           bit
  ,PRIMARY KEY BASES_PK (base)) "." engine = myisam default ".$collate."_general_ci";
  sql_exec($query);
}


// Create table got__baseGrid
$query = "SHOW TABLES FROM ".$sql_base." LIKE '".$sql_pref."__baseGrid'";
$ResultBase = sql_exec($query,0);

if (sql_num_rows($ResultBase) == 0)
{
  $query = "
  CREATE TABLE ".$sql_pref."__baseGrid
  (filename       varchar(250)
  ,sizefile       smallint
  ,datefile       varchar(32)
  ,base           varchar(250)
  ,id_decujus     int(10) unsigned NOT NULL default '0'
  ,nb_indi        int (6) unsigned
  ,places         varchar(120)
  ,nb_lastname    int
  ,nb_places      int
  ,nb_sources     INT(6)
  ,nb_media       INT(6)
  ,soft_name      varchar(42)
  ,soft_editor    varchar(42)
  ,password       varchar(42)
  ,datemaj        datetime
  ,geneotree_vers char(4)
  ,language       char(2)
  ,charset        varchar(5)
  ,ansel          bit
  ,hide75         bit
  ,consang        bit
  ,maps           bit
  ) "." engine = myisam default ".$collate."_general_ci";
  sql_exec($query,0);
}

// create table got__map_places
$query = "SHOW TABLES FROM ".$sql_base." LIKE '".$sql_pref."__map_places'";
$result = sql_exec($query);

if (sql_num_rows($result) == 0)
{ $query = "
  CREATE TABLE ".$sql_pref."__map_places
  ( Unknown_map_Place varchar(100) UNIQUE NOT NULL
   ,Known_map_Place   varchar(100) NOT NULL
   ,Base                    varchar(50) NOT NULL
  )
  "." engine = myisam default ".$collate."_general_ci";
  sql_exec($query);
}

// focus on active menu
switch ($_REQUEST["pag2"])
{   case "geo" : $class1 = "menu_encours"; $class2 = "menu_td";      break;
    default    : $class1 = "menu_td";      $class2 = "menu_encours"; break;
}

// begin of display page
echo '<!DOCTYPE html>
<HTML>
<HEAD>
<META HTTP-EQUIV="Content-type" CONTENT="text/html; CHARSET=utf-8" NAME="author" CONTENT="Damien Poulain">
<META NAME="viewport" CONTENT="width=device-width,height=device-height,initial-scale=0.6"/>
<TITLE>GeneoTree v'.$GeneoTreeRelease.' - '.$got_lang["Admin"].'</TITLE>
<LINK rel="stylesheet" href="geneotree.css" type="text/css">
<LINK rel="stylesheet" href="themes/'.$_REQUEST["theme"].'.css" type="text/css">
<link rel="shortcut icon" href="themes/geneotre.ico">
</HEAD>
<BODY>

<table name=masquegeneral style="margin-bottom:10px; margin-left:auto; margin-right:auto;">
<tr><td style="text-align:center; border-top: 2px solid black; border-left: 2px solid black; border-right: 2px solid black;">

  <a href=index.php><img width=35 height=35 src="themes/icon-home.png"></a>
  &emsp;&emsp;&emsp;<span align=center class=titre>'.$got_lang['Admin'].'</span>
  ';

  include ("_inc_lang_theme.php");

  echo '
</td></tr>

<tr><td align=center  height=40px style="border-bottom: 2px solid black; border-left: 2px solid black; border-right: 2px solid black;">
<a class='.$class2.' href = admin.php?lang='.$_REQUEST["lang"].'&theme='.$_REQUEST["theme"].'>'.$got_tag['GEDC'].'</a>
&emsp;&emsp;
<a class='.$class1.' href = admin.php?lang='.$_REQUEST["lang"].'&theme='.$_REQUEST["theme"].'&pag2=geo>'.$got_lang['CarTi'].'</a>

</td></tr>
';

$T_OLD = time();

// display Gedcom grid and form to upload gedcom file
if ( ($_REQUEST['pag'] == NULL  OR $_REQUEST['ibase'] == NULL) AND $_REQUEST['pag2'] != 'geo' )
{
  global $char;

  echo '
  <tr><td align=center>
  ';

  if (!isset($_POST['Envoyer']))
    $_POST['Envoyer'] = "";
  $PHP_SELF = $page;
  $dossier = 'gedcom/';

  if (Serveurlocal())
  { $Chemin = getcwd();
  } else
  { $Chemin = 'geneotree';
  }

  if (extension_loaded('ftp'))
  {  echo '<br>
    <FORM method=POST action="'.$PHP_SELF.'" enctype="multipart/form-data">
    <input type="file" name="gedcom"> ('.ini_get("upload_max_filesize").' Max)
    &ensp;&ensp;
    <input type="submit" name="Envoyer" value="OK">
    </FORM>
    ';
  } else
  { echo $got_lang['OuChg'].' <span style="font-family:courier;font-weight:bold;font-size:1.1em;">'.$Chemin.'/gedcom/</span>
    <br>'.$got_lang['OuPic'].' <span style="font-family:courier;font-weight:bold;font-size:1.1em;">'.$Chemin.'/picture/</span><span style="font-family:courier;font-size:1.1em;font-style:italic;">gedname/</span>
    <br>'.$got_lang['CliFl']
    ;
  }

  if ($_POST['Envoyer'] == 'OK')
  { $fichier = basename($_FILES['gedcom']['name']);
    $destination = $dossier.$fichier;
    $extension = strrchr($_FILES['gedcom']['name'], '.');

    if (!$fichier)
    { echo 'No file...<br>'.$got_lang['UplKO']." ".$fichier;}
    elseif (!in_array($extension, array(".ged",".GED")))
    { echo 'Only .ged file extension is attended...<br>'.$got_lang['UplKO']." ".$fichier;
    } else
    { $fichier = strtr($fichier,'ÀÁÂÃÄÅÇÈÉÊËÌÍÎÏÒÓÔÕÖÙÚÛÜÝàáâãäåçèéêëìíîïðòóôõöùúûüýÿ','AAAAAACEEEEIIIIOOOOOUUUUYaaaaaaceeeeiiiioooooouuuuyy');
      $fichier = preg_replace('/([^.a-z0-9]+)/i', '_', $fichier);
      $move = move_uploaded_file($_FILES['gedcom']['tmp_name'], $dossier . $fichier);
      if ($move)
      {    echo $fichier." ".$got_lang['UplOK'];
      } elseif ($_FILES["gedcom"]["error"] == 1)
      {     echo "The size limit defined in php.ini (".ini_get("upload_max_filesize").") has been reached. <br>".$got_lang['UplKO']." ".$fichier;
      } else
      {     echo "Error : ".$_FILES["gedcom"]["error"]."<br>".$got_lang['UplKO']." ".$fichier;
      }
    }
  }

  echo '
  </td></tr>
  ';

  // insert SQL metadata bases into MySql table "baseGrid"
  $query = "
  DELETE FROM  ".$sql_pref."__baseGrid
  ";
  sql_exec($query,0);

  $query = "
  INSERT INTO ".$sql_pref."__baseGrid (base,id_decujus,nb_indi,places,nb_lastname,nb_places,nb_sources,nb_media,soft_name,soft_editor,password,datemaj,geneotree_vers,language,charset,ansel,hide75,consang,maps)
  SELECT * FROM ".$sql_pref."__base
  ";
  sql_exec($query,0);

  // upgrade Mysql table "_baseGrid" with gedcom files in /gedcom directory
  //   "_baseGrid is a temporary table which call by script.js to display Grid of gedcom files and bases
  $CheminRepertoire = getcwd().'/gedcom/';
  $handle = opendir('./gedcom');

  while ($file = readdir($handle))
  { $filename = mb_substr($file,0,mb_strlen($file)-4);

    if (strtoupper(mb_substr($file,-3)) == "GED")
    { $sizefile = round(filesize($CheminRepertoire.$file)/1024,0);
      $datefile = date ("Y-m-d",filemtime($CheminRepertoire.$file));

      // Nom de table MySQl : tous les caractères autorisés dans le nom d'un fichier sauf ‘/’ et ‘.’
      $CaracInterdit = preg_match_all("([/.])", $filename, $match);

      if (mb_strlen($file) > 50)    // max sql 64, geneotree prend 14 characters ("got__evenement"), il reste 50
      {  $baseFich = '50';
      } elseif ($CaracInterdit)
      {  $baseFich = 'FO';
      } else
      {  $baseFich = 'OK';
      }

      // we test if the gedcom file exists in the MySql geneotree database
      $query = '
      SELECT 1
      FROM '.$sql_pref.'__baseGrid
      WHERE base = "'.$filename.'"
      ';
      $result = sql_exec($query,0);

      if (sql_num_rows($result) == 0)
        $query = '
        INSERT INTO '.$sql_pref.'__baseGrid (filename,sizefile,datefile) VALUES
        ("'.$file.'"
        ,"'.$sizefile.'"
        ,"'.$datefile.'"
        )';
      else
        $query = '
        UPDATE '.$sql_pref.'__baseGrid
        SET
          filename = "'.$file.'"
         ,sizefile = "'.$sizefile.'"
         ,datefile = "'.$datefile.'"
        WHERE base = "'.$filename.'"
        ';
      $result = sql_exec($query);
    }
  }
  closedir($handle);

  // affichage fichiers/bases
  $EnteteCol = array(
   "","","","",""
  ,"Filename"
  ,"SizeFile"
  ,"DateFile"
  ,""
  ,"Base"
  ,"Lang"
  ,"Nb Ind"
  ,"Nb Name"
  ,"Nb Place"
  ,$got_tag["SOUR"]
  ,$got_lang['Media']
  ,"Consang"
  ,"Maps"
  ,$got_lang['Logic']
  ,$got_lang['Edite']
  ,"ANSEL"
  ,"Char"
  ,$got_tag["VERS"]
  ,"Maj"
  ,"Hide75"
  ,"Password"
  ,"Edit"
  ,"Del."
  );
?>

  <tr><td align=center>
  <div id="modal" class="modal ligne_tr2" style="width:100%;">
    <!--<div class="modal-content" role="dialog" aria-labelledby="modalTitle" aria-describedby="modalDescription">-->
    <div class="modal-content" role="dialog" style="width:100%;">
        <p id="modalTitle"><?php echo $got_lang['ConSu'];?></p>
        &emsp;&emsp;<button id="yesBtn"><?php echo $got_lang['Oui'];?></button>
        &emsp;<button id="noBtn"><?php echo $got_lang['Non'];?></button>
    </div>
  </div>
  </td></tr>

  <tr><td align=center>
  <FORM>
  <table id="FormEditBase" class="modal ligne_tr2" style="width:400px; display:none;">
    <tr>
      <td align="right">Password</td>
      <td><input id="textPassword" type="password" size="10" name="password" value=""></td>
    </tr>
    <tr>
      <td align="right"><?php echo $got_lang['Centa'];?></td>
      <td>
        <input id="radioHide75" type="radio" name="hide75" value="1"> <?php echo $got_lang['Oui'];?>
        <input type="radio" name="hide75" value="0"> <?php echo $got_lang['Non'];?>
      </td>
    </tr>
    <tr>
      <td colspan="2">
        <input type="submit" value="" id="SubmitButton" class="save-button">
      </td>
    </tr>
  </table>
  </FORM>
  </td></tr>

  <tr><td id="waitMessage" align=center style="display:none; color: red;"></td></tr>

  <table id="TabMain" class="bord_bas bord_haut" style="margin-bottom:10px; margin-left:auto; margin-right:auto;">
      <thead id="TabMain-header" class=titre_col></thead>
      <tbody id="TabMain-body"></tbody>
  </table></td>

  <script>
  pagination('TabMain','<?php echo json_encode($EnteteCol); ?>','recup_baseGrid',sortColumn = 'filename');
  </script>
<?php

  if (!extension_loaded('ftp'))
  { echo '<p><i>Information : the PHP extension "ftp" is not installed on your server.</i></p>';
  }

}

/****************  End of display Gedcom grid  **************************/

// Upload Gedcom file
else if ($_REQUEST['ibase'] != NULL and ($_REQUEST['pag'] == "chg"))
{
  include ("uploadGedcom.php");
}

//  ************************  CRUD Geoapify Places  ***************************************

elseif ($_REQUEST['pag2'] == "geo")
{
  $table = $sql_pref.'__map_places';

  // Obtenir les colonnes de la table
  $result = mysqli_query($pool, "SHOW COLUMNS FROM $table");
  $columns = array();
  while ($row = mysqli_fetch_assoc($result))
  {  $columns[] = $row['Field'];
  }

  // Gérer la recherche
  $search = '';
  if (isset($_GET['search']))
  {  $search = mysqli_real_escape_string($pool, $_GET['search']);
  }

  // Gérer la suppression
  if (isset($_GET['delete']))
  {   $delete_id = mysqli_real_escape_string($pool, $_GET['delete']);
      mysqli_query($pool, "DELETE FROM $table WHERE {$columns[0]}='$delete_id'");
  }

  // Gérer l'ajout
  if (isset($_POST['add']))
  {   $values = array_map(function($column) use ($pool)
      {
          return "'" . mysqli_real_escape_string($pool, $_POST[$column]) . "'";
      }, $columns);
      $columns_str = implode(', ', $columns);
      $values_str = implode(', ', $values);
      mysqli_query($pool, "INSERT INTO $table ($columns_str) VALUES ($values_str)");
  }

  // Gérer la mise à jour
  if (isset($_POST['update']))
  {   $update_id = mysqli_real_escape_string($pool, $_POST[$columns[0]]);
      $set_str = implode(', ', array_map(function($column) use ($pool)
      {  return "$column='" . mysqli_real_escape_string($pool, $_POST[$column]) . "'";
      }, $columns));
      mysqli_query($pool, "UPDATE $table SET $set_str WHERE {$columns[0]}='$update_id'");
  }

  // Rechercher et obtenir les lignes
  $query = "SELECT * FROM $table";
  if ($search)
  {  $query .= " WHERE " . implode(" LIKE '%$search%' OR ", $columns) . " LIKE '%$search%'";
  }
  $query .= " ORDER BY {$columns[0]} LIMIT 200";
  $result = mysqli_query($pool, $query);

?>
  <tr><td>

  <button onclick="fctToggleAdd()"><?php echo $got_lang["Ajout"];?></button>
  <div id="add_form" style="display: none;">
      <form method="post">
      <table>
          <?php foreach ($columns as $column) : ?>
          <tr>
          <td>
              <label><?php
              if (substr($column,0,7) == "Unknown") {echo $got_lang["Unkno"];}
              if (substr($column,0,5) == "Known")   {echo $got_lang["Known"];}
              if ($column == "Base")                {echo $got_lang['Bases'];}
              ?>
              </label>
          </td><td>
              <input type="text" name="<?php echo $column; ?>" required><br>
          </td></tr>
          <?php endforeach;?>
          <tr>
          <td></td>
          <td>
              <button type="submit" name="add"><?php echo $got_lang["Ajout"];?></button>
              <button type="button" onclick="fctCancelAdd()"><?php echo $got_lang["Annul"];?></button>
          </td></tr>
          </table>
      </form>
  </div>

  <!-- Formulaire de recherche -->
  <form method="get">
      <input type="text" name="search" placeholder="<?php echo $got_lang["Reche"];?>..." value="<?php echo htmlspecialchars($search); ?>">
      <input type="hidden" name="pag2" value="geo">
      <button type="submit"><?php echo $got_lang["Reche"];?></button>
  </form>

  <table border="1">
      <thead>
          <tr class=ligne_tr2>
              <?php foreach ($columns as $column) :
              ?>
              <th style="padding:0px 10px 0px 10px;"><?php
              if (substr($column,0,7) == "Unknown") {echo $got_lang["Unkno"];}
              if (substr($column,0,5) == "Known")   {echo $got_lang["Known"];}
              if ($column == "Base")                {echo $got_lang['Bases'];}
              ?></th>
              <?php endforeach; ?>
              <th>Actions</th>
          </tr>
      </thead>
      <tbody>
          <?php $ii = 0;
              while ($row = mysqli_fetch_assoc($result)) :
              if ($ii % 2 == 0) {$classLine = "ligne_tr1";} else {$classLine = "ligne_tr2";} ?>
              <tr class=<?php echo $classLine;?> id="row_<?php echo $row[$columns[0]]; ?>">
                  <?php foreach ($columns as $column) : ?>
                      <td class=bords_verti><?php echo $row[$column]; ?></td>
                  <?php endforeach; ?>
                  <td style="padding:0px 10px 0px 10px;">
                      <button style="padding: 0px; margin: 0px 10px 0px 0px;" onclick="fctToggleEdit('<?php echo $row[$columns[0]]; ?>')">
                          <img width=20 heigth=20 src="themes/icon-edit.png" alt="Modifier">
                      </button>
                      <button style="padding: 0px; margin: 0px;" onclick="fctConfirmDelete('<?php echo $row[$columns[0]]; ?>')">
                          <img width=20 heigth=20 src="themes/icon-delete.png" alt="Supprimer">
                      </button>
                  </td>
              </tr>
              <tr id="edit_<?php echo $row[$columns[0]]; ?>" style="display: none;">
                  <td colspan="<?php echo count($columns) + 1; ?>">
                      <form id="edit_form_<?php echo $row[$columns[0]]; ?>">
                      <table>
                          <?php foreach ($columns as $column) : ?>
                              <tr><td><label style="font-weight:bold;"><?php
                              if (substr($column,0,7) == "Unknown") {echo $got_lang["Unkno"];}
                              if (substr($column,0,5) == "Known")   {echo $got_lang["Known"];}
                              if ($column == "Base")                {echo $got_lang['Bases'];}
                              ?>:</label>
                              </td><td>
                              <input style="font-weight:normal;width:300px;" type="text" name="<?php echo $column; ?>" value="<?php echo $row[$column]; ?>" required><br>
                              </td></tr>
                          <?php endforeach; ?>
                        </table>
                          <input type="hidden" name="<?php echo $columns[0]; ?>" value="<?php echo $row[$columns[0]]; ?>">
                          <input type="hidden" name=search value="<?php echo htmlspecialchars($search); ?>">
                          <button type="button" onclick="fctUpdateRow('<?php echo $row[$columns[0]]; ?>')">Valider</button>
                          <button type="button" onclick="fctCancelEdit('<?php echo $row[$columns[0]]; ?>')">Annuler</button>
                      </form>
                  </td>
              </tr>
          <?php $ii++; endwhile; ?>
      </tbody>
  </table>
<?php
}
echo '</td></tr></table>'; //fermeture masque general
mysqli_close($pool);