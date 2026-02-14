<?php
header('Content-type: text/html; charset=utf-8'); 

require_once ("_functions.php");
include ("config.php");

/*********************************DEBUT DU SCRIPT *************************/
// menu.php est l'entete de toutes les pages GeneoTree après le choix du gedcom dans la page index.php
// menu.php gère le mot de passe ami, l'affichage des préférences utilisateurs : langue, theme.
// menu.php appelle _sql.inc.php et les fichiers de langue pour toutes pages, hors pdf et admin

// POST variables initialization
              // generic
if (!isset($_GET['pag']))         {$_GET['pag']       = "no";}
if (!isset($_REQUEST['sex']))     {$_REQUEST['sex']   = "_";}
if (!isset($_REQUEST['sosa']))    {$_REQUEST['sosa']  = 0;}
if (!isset($_REQUEST['rech']))    {$_REQUEST['rech']  = "";}
              //form pdf filters
if (!isset($_REQUEST['implex']))  {$_REQUEST['implex']= "_";}
if (!isset($_REQUEST['type']))    {$_REQUEST['type']  = "";}
if (!isset($_REQUEST['nbgen']))   {$_REQUEST['nbgen'] = 4;}
if (!isset($_REQUEST['orient']))  {$_REQUEST['orient']= "";}
if (!isset($_REQUEST['SpeGe']))   {$_REQUEST['SpeGe'] = "";}
if (!isset($_REQUEST['pori']))    {$_REQUEST['pori']  = "";}

if (!isset($_REQUEST['ext']))     {$_REQUEST['ext']   = "";}
if (!isset($_REQUEST['gedlang'])) {$_REQUEST['gedlang']  = "";}
if (!isset($_REQUEST['gedlangForm'])){$_REQUEST['gedlangForm']  = "";}
if (!isset($_REQUEST['time']))    {$_REQUEST['time']  = "";}

                // date slider
if (empty($minValue)) {$minValue = "";}
if (empty($maxValue)) {$maxValue = "";}

$pool = sql_connect();

// $query = 'SELECT password,sosa_principal,consang,media,source,nb_noms,nb_lieux,nb_dept,nb_prenoms,volume FROM '.$sql_pref.'__base WHERE base = "'.$_REQUEST['ibase'].'"';
$query = 'SELECT * FROM '.$sql_pref.'__base WHERE base = "'.$_REQUEST['ibase'].'"';
$result = sql_exec($query);
$row = mysqli_fetch_assoc($result);

if (!Serveurlocal() and (($passe_ami and $_COOKIE ["passeami"] != $passe_ami) or ($row["password"] and $_COOKIE ["passebase"] != $row["password"])))
{    if (!$_POST["pass"] or ($passe_ami and $_POST["pass"] != $passe_ami) or ($row["password"] and $_POST["pass"] != $row["password"])) 
    {   require ("languages/".$_REQUEST['lang'].".php");
        echo $got_lang['Frie1'];
        echo '<form name=mdp method="post">';
        echo '<table><tr>';
        echo '<td><input type="password" name="pass"></td>';
        echo '<td><input type="Submit" value="Entrer"></td>';
        echo '</tr></table>';
        echo '</form>';

            // positionnement du focus sur le formulaire
        echo '<script language="JavaScript" type="text/javascript">
        document.mdp.pass.focus();
            </script>';    
        exit;
    }
    elseif ($passe_ami and @$_POST["pass"] == $passe_ami) // on teste d'abord le mot de passe ami general 
    {    // setcookie("passeami",crypt($passe_ami,"dam")); // crypt - non facile de deviner
        setcookie("passeami",$passe_ami);
        Header("Location: ".$_SERVER["PHP_SELF"]."?ibase=".$_REQUEST['ibase']."&lang=".$_REQUEST['lang']);
    }
    elseif ($row["password"] and @$_POST["pass"] == $row["password"])   // sinon on teste le mot de passe particulier à une base
    {    setcookie("passebase",$row["password"]);
        Header("Location: ".$_SERVER["PHP_SELF"]."?ibase=".$_REQUEST['ibase']."&lang=".$_REQUEST['lang']);
    }
    else {echo 'FATAL ERROR : Cookie or password are not detect correctly. Contact your administrator';}
}

/*********************** DEBUT GESTION DES PREFERENCES utilisateur ******************/

// On stocke en permanence les préférences des utilisateurs dans les cookies, pour lui réafficher à la prochaine ouverture du navigateur.

global $pool;

// alimentation des POSTs vides
$Centra = urlencode($_REQUEST['ibase'])."_centra";
if (!isset($_REQUEST['id']) OR $_REQUEST['id'] == "")    {$_REQUEST['id'] = $row["id_decujus"];}
// if (!isset($_REQUEST['lang']))        {if (isset($_COOKIE["lang"])) {$_REQUEST['lang'] = $_COOKIE["lang"];} else {$_REQUEST['lang'] = "en";} }
if (!isset($_REQUEST['palma']))        {if (isset($_COOKIE["palma"])) {$_REQUEST['palma'] = $_COOKIE["palma"];} else {$_REQUEST['palma'] = "30";} }
if (!isset($_REQUEST['theme']))        {if (isset($_COOKIE["theme"])) {$_REQUEST['theme'] = $_COOKIE["theme"];} else {$_REQUEST['theme'] = "wiki";} }

// stockage des POSTs dans les cookies
$ExpireCookie = time()+60*60*24*30;  // validité 1 mois
setcookie($Centra,$_REQUEST['id'],$ExpireCookie);
// setcookie("lang",$_REQUEST['lang'],$ExpireCookie);
setcookie("palma",$_REQUEST['palma'],$ExpireCookie);
setcookie("theme",$_REQUEST['theme'],$ExpireCookie);

// call language file (POST variable is initialized)
if (!isset($_REQUEST['lang']))
{ if (mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2) == 'fr')
  {    $_REQUEST['lang'] = "fr";
  }    else 
  {    $_REQUEST['lang'] = 'en';
  }
}
require ("languages/".$_REQUEST['lang'].".php");

if (!isset($_REQUEST['fid'])) {$_REQUEST['fid'] = $_REQUEST['id'];}
if (!isset($_REQUEST['ifin'])) {$_REQUEST['ifin'] = "";}
if (!isset($_REQUEST['pag']))
{   if (in_array(substr($page,0,4),array("list","stat"))) { $_REQUEST['pag'] = "no";}
    elseif (in_array(substr($page,0,4),array("even","sour","medi"))) { $_REQUEST['pag'] = "BIRT";}
    elseif ($page == "arbre_ascendant.php") { $_REQUEST['pag'] = "Age";}
    elseif (in_array(substr($page,0,4),array("arbr","cons"))) { $_REQUEST['pag'] = "";}
    else {echo "probleme pag";}
}
if (!isset($_REQUEST['csg']))  {$_REQUEST['csg'] = $row["consang"];}

$url = url_request();

/*********************** START DISPLAY ********************************/
?>
<!DOCTYPE html>
<HTML>
<HEAD>
<META charset="UTF-8">
<META http-equiv="Content-type" content="text/html; charset=utf-8" name="author" content="Damien Poulain">
<META name="viewport" content="width=device-width,height=device-height,initial-scale=0.6"/>
<TITLE>GeneoTree v<?php echo $GeneoTreeRelease?></TITLE>

<LINK rel="stylesheet" href="geneotree.css" type="text/css">
<LINK rel="stylesheet" href="themes/<?php echo $_REQUEST["theme"]?>.css" type="text/css">
<link rel="icon" href="themes/geneotree.ico?v=2" type="image/x-icon">

<!-- Leaflet OpenStreetMap -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" />
<link rel="stylesheet" href="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.css" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="https://unpkg.com/leaflet.fullscreen@1.6.0/Control.FullScreen.js"></script>
<script src="chartjs/Chart.min.js"></script>
<script src="chartjs/chartjs-plugin-datalabels.min.js"></script>

<script>
// php variables
var ADDRC        = "<?php echo $ADDRC;?>";
var Annee        = "<?php echo $got_lang["Annee"];?>";
var browserLanguage = "<?php echo $browserLanguage;?>";
var hide75       = "<?php echo $hide75;?>"; 
var csg          = "<?php echo $_REQUEST['csg'];?>";
var getPag       = "<?php echo $_GET['pag']; ?>";
var gotLang      = <?php echo json_encode($got_lang);?>; 
var gotTag       = <?php echo json_encode($got_tag);?>;
var HrefBase     = '&ibase=<?php echo urlencode($_REQUEST["ibase"]);?>&lang=<?php echo $_REQUEST["lang"]?>&pag=<?php echo $_REQUEST["pag"]?>&sex=<?php echo $_REQUEST["sex"]?>&sosa=<?php echo $_REQUEST["sosa"]?>&rech=<?php echo urlencode($_REQUEST["rech"]);?>&id=<?php echo $_REQUEST["id"]?>';
var ibase        = "<?php echo $_GET['ibase'];?>";
var ii           = 0;
var MarrTags     = <?php echo json_encode ($MarrTags);?>;
var pag          = "<?php echo $_GET['pag'];?>"
var PagePhp      = "<?php echo $page?>";
var Rech         = "<?php if (isset($_REQUEST['rech'])) {echo $_REQUEST['rech'];} else {echo '';} ?>";
var ServeurLocal = "<?php echo Serveurlocal(); ?>";
var Sex          = "<?php if (isset($_REQUEST['sex']))  {echo $_REQUEST['sex'];} else {echo '_';} ?>";
var sql_pref     = "<?php echo $sql_pref;?>"; 
var Sosa         = "<?php if (isset($_REQUEST['sosa'])) {echo $_REQUEST['sosa'];} else {echo '0';} ?>";
var today        = <?php echo json_encode($today);?>;
var flagMaps     = <?php echo $row["maps"];?>
</script>
<script src="script.min.js"></script>

<?php
/*
|-----------------------------------------|
| Header : home, base, languages, themes  |
|-----------------------------------------|
| Main menu : tree,list,stat,event,media  |
|-----------------------------------------|
| DivIcon1 DivIcon2 DivIcon2 DivSubMenu   |
|-----------------------------------------|
| DivSex  DivSosa  DivSearch DivDateSlider|
|-----------------------------------------|
| TabMain  | TabDetail| DivCard           |
|          |          | TabParents        |
|          |          | TabSpouse         |
|          |          | TabChildren       |
|          |          | TabSiblings       |
|          |          | TabGrandChildren  |
|          |          | TabUncles         |
|          |          | TabNephews        |
|          |          | TabFirstCousins   |
|          |          | TabSecondCousins  |
|          |          | TabDistantCousins |
|          |          | TabGreatUncles    |
|          |          | DivSource1        |
|          |          | DivSource2        |
|          |          | Div [...]         |
|          |          | DivSourceN        |
|-----------------------------------------|
*/

echo '
</HEAD>
<BODY>
<table name=structure style="margin-left:auto; margin-right:auto; border: 2px solid black;">
<tr>
  <td>
    <a href = index.php?lang='.$_REQUEST["lang"].'><img width=35 height=35 src="themes/icon-home.png"></a>';

// base
echo '
    &emsp;&emsp;&emsp;&emsp;
    <span style="font-size:2em;">'.$got_lang['Bases'].' <b>'.ucfirst($_REQUEST['ibase']).'</b></span>
    <span style="font-size:em;font-size:small;">'.$row["nb_indi"].' '.$got_tag["INDI"].'s</span>';

include ("_inc_lang_theme.php");

if (Serveurlocal())
{ echo '<div id="Resolution" style="font-size:small;"></div>';
  ?>
  <script>

  function displayWindowSize()
  {   var w = window.innerWidth;
  	var h = window.innerHeight;
  	document.getElementById("Resolution").innerHTML = "&emsp;&emsp;&emsp;&emsp;" + browserLanguage + "&ensp;" + w + "x" + h;
  }

  // On associe la fonction displayWindowSize à l évènement resize
  window.addEventListener("resize", displayWindowSize);
  // Appel pour le 1er affichage
  displayWindowSize();

  </script>
  <?php
}

echo '</td></tr>'; // End TR1

// sub menus
switch (substr($page,0,4))
{   case 'arbr' : $Class1="menu_encours"; $Class2="menu_td"; $Class3="menu_td"; $Class4="menu_td"; $Class5="menu_td"; $Class6="menu_td"; $Class7="menu_td";break;
    case 'list' : $Class1="menu_td"; $Class4="menu_td"; $Class5="menu_td"; $Class6="menu_td"; $Class7="menu_td";
            if (@$_REQUEST["pag"] == "lieu_evene") {$Class2="menu_td"; $Class3="menu_encours";} else {$Class2="menu_encours"; $Class3="menu_td";}
            break;
    case 'even' : $Class1="menu_td"; $Class2="menu_td"; $Class3="menu_td"; $Class4="menu_encours"; $Class5="menu_td"; $Class6="menu_td"; $Class7="menu_td";break;
    case 'stat' : $Class1="menu_td"; $Class2="menu_td"; $Class3="menu_td"; $Class4="menu_td"; $Class5="menu_encours"; $Class6="menu_td"; $Class7="menu_td";break;
    case 'sour' : $Class1="menu_td"; $Class2="menu_td"; $Class3="menu_td"; $Class4="menu_td"; $Class5="menu_td"; $Class6="menu_encours"; $Class7="menu_td";break;
    case 'medi' : $Class1="menu_td"; $Class2="menu_td"; $Class3="menu_td"; $Class4="menu_td"; $Class5="menu_td"; $Class6="menu_td"; $Class7="menu_encours";break;
    default : $Class1="menu_td"; $Class2="menu_td"; $Class3="menu_td"; $Class4="menu_td"; $Class5="menu_td"; $Class6="menu_td"; $Class7="menu_td";break;
}

echo '
<tr>
<td colspan=3 height=40px>
';
// fenêtre dynamique de navigation appellée sur le premier menu "arbre".
echo '
<table style="margin-bottom:8px;">
<tr><td></td>
<td>
    <nav class="menu">
        <section class="categorie">
            <a href=arbre_ascendant.php'.$url.'&id='.$row["id_decujus"].'&fid='.$_REQUEST['fid'].'>'.$got_lang['MenAr'].'</a>
            <ul>
                <li><a href=arbre_mixte.php'.$url.'&id='.$_REQUEST['id'].'&fid='.$_REQUEST['fid'].'&theme='.$_REQUEST["theme"].'&palma='.$_REQUEST['palma'].'><img src=themes/fleche_milieu.png heigth=20 width=20> '.$got_lang['ArMix'].'</a></li>
                <li><a href=arbre_ascendant.php'.$url.'&id='.$_REQUEST['id'].'&fid='.$_REQUEST['fid'].'><img src=themes/fleche_haut.png heigth=20 width=20> '.$got_lang['ArAsc'].'</a></li>
                <li><a href=arbre_descendant.php'.$url.'&id='.$_REQUEST['id'].'&fid='.$_REQUEST['fid'].'&theme='.$_REQUEST["theme"].'&palma='.$_REQUEST['palma'].'><img src=themes/fleche_bas.png heigth=20 width=20> '.$got_lang['ArDes'].'</a></li>';
                if ($_REQUEST['csg'] == 'O')
                {    echo '<li><a href=arbre_consanguinite.php'.$url.'&id='.$_REQUEST['id'].'&fid='.$_REQUEST['fid'].'&theme='.$_REQUEST["theme"].'&palma='.$_REQUEST['palma'].'><img src=themes/fleche_consang.png heigth=20 width=20> '.$got_lang['EtCon'].'</a></li>';
                }
echo '
            </ul>
        </section>
    </nav>
</td>
<td>&emsp;<a class='.$Class2.' href = listes.php'.$url.'&pag=nom>'.$got_lang['Noms'].'</a></td>
<td>&emsp;<a class='.$Class3.' href = listes.php'.$url.'&pag=lieu_evene>'.$got_lang['Lieux'].'</a></td>
<td>&emsp;<a class='.$Class5.' href = stat.php'.$url.'&pag=lon>'.$got_lang['PalNo'].'</a></td>
<td>&emsp;<a class='.$Class4.' href = evenement.php'.$url.'&pag=BIRT>'.$got_lang['Evene'].'</a></td>';
if ($row["nb_media"] != 0) {echo '&emsp;
    <td>&emsp;<a class='.$Class7.' href = media.php'.$url.'&pag=%>'.$got_lang['Media'].'</a></td>';}
echo '
</tr></table>

</td></tr></table>'; // end TR1 & TR2

echo '

<table style="margin-left:auto; margin-right:auto;">
<tr id=TR3>
  <td style="min-width:600px; padding:10px;">
    <div id=DivIcon1></div> 
    &emsp;
    <div id=DivIcon2></div>
    &emsp;
    <div id=DivIcon3></div>
    &emsp;
    <div id=DivIcon4></div>
    &emsp;
    <div id=DivSubMenu></div>
  </td>
</tr>
</table>

<table style="margin-left:auto; margin-right:auto;">
<tr id=TR4 >
  <td id=DivSex></td>
  <td>&emsp;</td>
  <td id=DivSosa></td>
  <td>&emsp;</td>
  <td id=DivSearch></td>
  <td>&emsp;</td>
';
if (substr($page,0,5) !== "arbre" AND substr($page,0,5) !== "media" AND substr($page,0,13) !== "consanguinite")
{  echo '<td id=DivDateSlider style="position: relative; width: 200px;">
    <span id="minYearLabel" class="range-label"></span>
    <span id="maxYearLabel" class="range-label"></span>
    <input type="range" id="rangeMin" step="20" oninput="updateSlider()">
    <input type="range" id="rangeMax" step="20" oninput="updateSlider()">
  </td>
  ';
}
echo '
</tr>
<table width=100%>
<tr>
  <td align=right  id="waitMessage" style="display:none; color: red;">
  </td>
</tr>
</table>

</table>

<table style="margin-left:auto; margin-right:auto;">

<tr id=TR5>
';

if (substr($page,0,5) !== "arbre" 
AND substr($page,0,9) !== "fiche_pdf" 
AND !(substr($page,0,4) == "stat" AND in_array($_REQUEST["pag"], array("nom","lieu_evene","dept_evene","region_evene","country_evene","prenom1"))  )
   )
{ echo '
    <td style="vertical-align:top;">
      <div id=DivTitleMain class=titre></div>
      <table id="TabMain" class="bord_bas bord_haut">
          <thead id="TabMain-header" class=titre_col></thead>
          <tbody id="TabMain-body"></tbody>
      </table></td>
    <td style="vertical-align:top;">
      <div id=DivTitleMain2 class=titre></div>
      <table id="TabMain2" class="bord_bas bord_haut">
          <thead id="TabMain2-header" class=titre_col></thead>
          <tbody id="TabMain2-body"></tbody>
      </table>';
    include ("_inc_html_card.php");
} else
{  echo '<td style="vertical-align:top;">';
   if (substr($page,0,4) == "stat" AND in_array($_REQUEST["pag"], array("nom","lieu_evene","dept_evene","region_evene","country_evene","prenom1"))  )
   { echo '<canvas id=statCanvas width=900 height=450></canvas>';
   }
}
