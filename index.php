<?php 
header('Content-type: text/html; charset=utf-8'); 

// test PHP release
if (version_compare(PHP_VERSION, '5.3.0', '<')) 
{ echo '<span>Version PHP : <b>'.phpversion().'</b>'; 
  echo '<br> Geneotree requires version <b>5.3</b> or higher. You need to change your PHP version.';
  return;
}

mb_internal_encoding('UTF-8');
require_once ("_functions.php");

function verif_config()
{    global $sql_pref;
     global $sql_base;
     include ("config.php");
// if ($INSTALLATION_OK) {echo 'OK';} else {echo 'KO';}
    return $INSTALLATION_OK;
}

function fctBaseMiss($base)
{   global $sql_pref;
    global $pool;

    $query = 'SELECT 1 FROM `'.$sql_pref.'_'.$base.'_individu` WHERE 0=1';
    $result = @mysqli_query($pool,$query);
    if (mysqli_errno($pool) != 0)
    {    return FALSE;}
    else
    {    return TRUE;}
}

/*********************************END OF SCRIPT *************************/

if (!verif_config() and $_REQUEST['install'] != "OK")
{    echo '<script language="JavaScript" type="text/javascript">';
    echo 'window.location = "install.php"';
    echo '</script>'; 
    exit;
}

$pool = sql_connect();

// stockage des cookies
$ExpireCookie = time()+60*60*24*30;

// alimentation des POSTs vides

if (!isset($_REQUEST['lang']))
{ if (isset($_COOKIE["lang"])) 
  { $_REQUEST['lang'] = $_COOKIE["lang"];
  } else 
  { if (mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2) == 'fr') 
    { $_REQUEST['lang'] = "fr";
    } else 
	{ $_REQUEST['lang'] = 'en';
    }
  }
}
// setcookie("lang",$_REQUEST['lang'],$ExpireCookie);
require_once ('languages/'.$_REQUEST["lang"].'.php');

if (!isset($_REQUEST['theme']))
{    if (isset($_COOKIE["theme"])) 
    {    $_REQUEST['theme'] = $_COOKIE["theme"];
    } else 
    {    $_REQUEST['theme'] = "wiki";
    }
}
setcookie("theme",$_REQUEST['theme'],$ExpireCookie);


if (!isset($_REQUEST['inom'])) {$_REQUEST['inom'] = NULL;}

echo '
<!DOCTYPE html>
<HTML>
<HEAD>
<META http-equiv="Content-type" content="text/html; charset=utf-8" name="author" content="Damien Poulain">
<meta name="viewport" content="width=device-width,height=device-height,initial-scale=0.6"/>
<TITLE>GeneoTree v'.$GeneoTreeRelease.' - '.$got_lang["Accue"].'</TITLE>
<LINK rel="stylesheet" href=geneotree.css type="text/css">
<link rel="icon" href="themes/geneotree.ico?v=2" type="image/x-icon">
</HEAD>
<BODY>
<form name=rech_typ method=post>
<table style="margin-bottom:10px; margin-left:auto; margin-right:auto;">
<tr>';

$query = "SHOW TABLES FROM ".$sql_base." LIKE '".$sql_pref."__base'";
$ResultBase = sql_exec($query);

// search box & title
if (sql_num_rows($ResultBase) == 1)
{ $query = 'SELECT * FROM `'.$sql_pref.'__base` ORDER BY 1';
  $result = sql_exec($query);
  if (sql_num_rows($result) >= 1)
  { echo '
    <td>
    <input type=text name=inom size=28>
    <input type=submit value="'.$got_lang['Reche'].'"></td>';

    // positionnement du focus sur le formulaire
    echo '
    <script language="JavaScript" type="text/javascript">
    document.rech_typ.inom.focus();
    </script>'; 

    // titre "choisissez une base"
    echo '
    <td width=450px align=center class=titre>'.$got_lang['ChoBa'].'</td>';

  } else 
  { echo "
    <td width=450px align=center>".$got_lang['MesCh']."</td>";
  }
} else 
{ echo "
  <td width=450px align=center>".$got_lang['MesCh']."</td>";
}

// languages
$handle = opendir('./languages');
while ($file = readdir($handle))
{    if (!is_dir($file) AND substr($file,-3) == 'php')
    {   $Lang = substr($file,0,2);
        $IB = 'IB'.$Lang;
        echo '
        <td align=center><font size=1><b>'.$Lang.'</b></font><br><a href=index.php?lang='.$Lang.'><img border="0" src="languages/'.$Lang.'.png" width=35 height=22><span>'.$got_lang[$IB].'</span></a></td>';
    }
}

// bouton administration
echo '
<td align=right width=200px><a class=menu_td href=admin.php?lang='.$_REQUEST['lang'].'><b>'.$got_lang['Admin'].'</b><span>'.$got_lang['IBAdm'].'</span></a></td>
</tr>
</table>
</form>';

// bases disponibles 
if (!isset($_REQUEST['inom']))
{

  $EnteteCol = array(
   "","","","",""
  ,$got_lang['Bases']
  ,$got_lang['MenLa']
  ,$got_tag['CTRY']
  ,$got_tag['INDI']
  ,$got_lang['Media']
  ,$got_lang['Consa']
  ,$got_lang['Carte']
  ,$got_lang['Logic']
  ,$got_tag['VERS']
  ,$got_tag['DATE']
  );
?>
  <table id="TabMain" class="bord_bas bord_haut" style="margin-bottom:10px; margin-left:auto; margin-right:auto;">
      <thead id="TabMain-header" class=titre_col></thead>
      <tbody id="TabMain-body"></tbody>
  </table></td>

  <script>
  // php variables
  var Annee        = "<?php echo $got_lang["Annee"];?>";
  var gotLang      = <?php echo json_encode($got_lang);?>; 
  var gotTag       = <?php echo json_encode($got_tag);?>;
  var HrefBase     = '&lang=<?php echo $_REQUEST["lang"]?>';
  var sql_pref     = "<?php echo $sql_pref;?>"; 
  var PagePhp      = "index.php";

  </script>
  <script src="script.min.js"></script>
  <script>
  pagination('TabMain','<?php echo json_encode($EnteteCol); ?>','recup_bases',sortColumn = 'base');
  </script>
<?php
}
else
// bases contenant la recherche
{    echo '
    <table>
    <tr>
    <td class=menu_td><a class=menu_td HREF = index.php>'.$got_lang['Retou'].'</a></td>
    </tr>
    </table>';

    $query = 'SELECT * FROM `'.$sql_pref.'__base`';
    $result = sql_exec($query);
    if (@mysqli_num_rows($result) == '0' or !@mysqli_num_rows($result))
    {    echo '
        <br><b>'.$got_lang['MesCh'].'</b>';
        return;
    } 

    $liste["nom"] = array(); $liste["base"] = array();
    while ($row = @mysqli_fetch_row($result))
    {   $query = 'SELECT distinct nom FROM `'.$sql_pref.'_'.$row[0].'_individu` WHERE nom LIKE "'.$_REQUEST['inom'].'%"';
        $result2 = sql_exec($query,0);

        while ($row2 = mysqli_fetch_row($result2))
        {   $liste['nom'][]   = $row2[0];
            $liste['base'][]  = $row[0];
        }
    }

    if (!$liste)
    {    echo '
        <br><b>'.$got_lang['NomPr'].' '.$got_lang['NonAf'].'</b>';
        return;
    } 

    array_multisort ($liste['nom'],$liste['base']);

    echo '
    <br>
    <table class="bord_haut bord_bas">';
    $ii = 0;
    $nom_old = "";
    while ($ii < count($liste['nom']))
    {    if ($ii % 2 == 0) 
        {    echo '
            <tr>';
        } else 
        {    echo '
            <tr class="ligne_tr2">';
        }
        if ($liste['nom'][$ii] !== $nom_old)
        {    echo '
            <td class=bords_verti>'.$liste["nom"][$ii].'</td>
            <td class=bords_verti><a href=listes.php?ibase='.urlencode($liste["base"][$ii]).'&pag=nom&rech='.$liste["nom"][$ii].'>'.$liste["base"][$ii].'</td></a>';
        } else
        {    echo '
            <td class=bords_verti></td>
            <td class=bords_verti><a href=listes.php?ibase='.urlencode($liste["base"][$ii]).'&pag=nom&rech='.$liste['nom'][$ii].'>'.$liste['base'][$ii].'</td></a>';
        }
        echo '
        </tr>';
        $nom_old = $liste["nom"][$ii];
        $ii++;
    }
    echo '
    </table>';
}

echo '
<p align=center><font size=2><b><a href=http://www.geneotree.com>'.$got_lang['Credi'].'</a></b></font></p>
</BODY>
';
mysqli_close($pool);