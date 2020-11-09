<?php
header('Content-type: text/html; charset=utf-8'); 
error_reporting(0);	
require_once ("_sql.inc.php");
require_once ("_boites.inc.php");
require_once ("_caracteres.inc.php");
// initialise la langue de l'utilisateur en fonction de la langue du navigateur. (les préférences utilisateurs ne sont pas encore connues.)
if (!isset($_REQUEST['lang']))
{	if (mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2) == 'fr')
	{	$_REQUEST['lang'] = "fr";
	}	else 
	{	$_REQUEST['lang'] = 'en';
	}
}
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	

$pool = sql_connect();
include ("config.php");

if (!Serveurlocal())
{	if(@$_REQUEST["pass"]== $passe_admin) 
//	{	setcookie("passe2",crypt($passe_admin, "r1")); // crypt - non facile de deviner
	{	setcookie("passe2",$passe_admin);   // doit passer avant toute commande alimentant le header (contrainte htpp)
		header("Location: ".$_SERVER["PHP_SELF"]);
	}
//	$isOK = (strcmp(@$_COOKIE["passe2"], crypt($passe_admin, "r1")) == 0);

	if (@$_COOKIE["passe2"] !== $passe_admin)
	{	echo 'GeneoTree administrator password';
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
	
		$F = @fopen("_compteur2.php","r");
		if ($F != FALSE) {require_once ("_compteur2.php");}
		
		exit;
	}
}

function progression($nb_lignes_total) 
{
?>
	<script>
	var nb_lignes_total = <?php echo $nb_lignes_total; ?>;
	function afficher_progress(nb_ligne) 
	{	perc = Math.round(100 * (nb_ligne / nb_lignes_total));
		if (perc > 100) perc = 100;
		progress = document.getElementById("progress_div");
		if (progress)
		{	progress.innerHTML = perc + "%";
		}
	}
	</script>
<?php

	echo '<div id="progress_div">%</div>';	// pour faire patienter en attendant le 1er affichage provenant des boucles
	flush();
}

/********************** FONCTIONS POUR LA GESTION DES GEDCOMS ********************************/
function ouvrir_gedcom($base, $url)
{	$F = @fopen("gedcom/".$base.".ged","r");
	if ($F == FALSE) 
	{	$F = @fopen("gedcom/".$base.".GED","r");
		if ($F == FALSE)
		{	echo '<b>'.$_REQUEST['ibase'].' ?</b> -> Unknown file. Contact the administrator to upload your gedcom file in /gedcom library';
			echo '<br><br><a class=menu_td href=admin.php'.$url.'>Return</a>';
			exit;
		}
	}
	return $F;
}

function sdg ($texte)
{	// fonction suppression des double guillemets (garantit l'insert SQL sans echec)
	$texte = mb_ereg_replace('"', "'", $texte);
	$texte = str_replace("\\", "-", $texte);  // ne fonctionne pas en UTF-8. Le back slash est inconnu ?
	return $texte;
}

function NomPrenom($txt)
{	$txt = mb_ereg_replace('_',' ',$txt);
	preg_match_all ("([^/]+)",$txt,$match);
	if (count($match[0]) >= 2) 
	{	$nomprenom[1] = trim($match[0][0]);
		$nomprenom[2] = $match[0][1];
	}	elseif (count($match[0]) == 1) // pas de prenoms trouves. 
	{	$nomprenom[1] = "";
		$nomprenom[2] = $match[0][0];
	} else
	{	$nomprenom[1] = "";
		$nomprenom[2] = "";
	}
	return $nomprenom;  // le nom est dans [2], les prenoms dans [1]
}

function tdp ($prenom) // Traitement des prenoms
{	$prenoms[0] = "";
	$prenoms[1] = "";
	$prenoms[2] = "";
	$prenom = sdg($prenom).' ';
	if (mb_strpos($prenom, " ") == mb_strlen($prenom)) {}
	else 
	{	$prenoms[0] = mb_substr($prenom, 0, mb_strpos($prenom, " ") );
		$prenom = mb_substr($prenom, mb_strpos($prenom, " ")+1,42 ).' ';
		if (mb_strpos($prenom.' ', " ") == mb_strlen($prenom.' ')) {}
		else
		{	$prenoms[1] = mb_substr($prenom, 0, mb_strpos($prenom, " ") );
			$prenom = mb_substr($prenom.' ', mb_strpos($prenom, " ")+1,42 ).' ';
			if (mb_strpos($prenom.' ', " ") == mb_strlen($prenom.' ')) {}
			else
			{	$prenoms[2] = mb_substr($prenom, 0, mb_strpos($prenom, " ") );
				$prenom = mb_substr($prenom.' ', mb_strpos($prenom, " ")+1,42 ).' ';
			}
		}
	}
	return $prenoms;
}

function tr_lieu ($lieu) // Traitement des lieux
{	global $dept;	// table des departement -> a renseigner l'exterieur de la fonction

	$chain_sup  = array (' County',' county',' Co.',' co.',',');	// on supprime les parasites
	$chain_repl = array ('-');	// on remplace les tirets par des blancs pour la jointure avec la table communes

	$lieu = sans_accent($lieu);
	$lieu = @str_replace(", ",",",$lieu);		// on enleve les blancs inutiles
	$lieu = @str_replace(",,",",",$lieu);		// on enleve les doubles virgules
	$lieu = @str_replace(",,",",",$lieu);		// on enleve les doubles virgules
	$lieu = @str_replace(",,",",",$lieu);		// on enleve les doubles virgules
	$lieu = @str_replace(",,",",",$lieu);		// on enleve les doubles virgules
	$lieu = @str_replace(",,",",",$lieu);		// on enleve les doubles virgules	if (strpos(' '.$lieu,',') == 1) {$lieu = substr($lieu,1,255);}	// on enleve les virgules en premiere position
//echo '<tr><td>'.$lieu.'</td>';
	$lieux = explode (',',$lieu);
	if (count($lieux) == 0) {$lieux[] = "";}  
	if (count($lieux) == 1) {$lieux[1] = "";$lieux[2] = "";$lieux[3] = "";$lieux[4] = "";}  
	if (count($lieux) == 2) {$lieux[2] = "";$lieux[3] = "";$lieux[4] = "";}  
	if (count($lieux) == 3) {$lieux[3] = "";$lieux[4] = "";}  
	if (count($lieux) == 4) {$lieux[4] = "";}  

			// alimentation de la ville : 1a chaine qui ne contient pas de code (majuscule ou chiffre coll  de longueur inferieure a 3)
	if (strlen($lieux[0]) > 3)
	{	$lieux[0] = trim($lieux[0]);
	} elseif (strlen($lieux[1]) > 3)
	{	$lieux[0] = trim($lieux[1]);
	} elseif (strlen($lieux[2]) > 3)
	{	$lieux[0] = trim($lieux[2]);
	} elseif (strlen($lieux[3]) > 3)
	{	$lieux[0] = trim($lieux[3]);
	} elseif (strlen($lieux[4]) > 3)
	{	$lieux[0] = trim($lieux[4]);
	}
	$lieux[0] = @str_replace($chain_sup,'', $lieux[0]);
	$lieux[0] = @str_replace($chain_repl,' ', $lieux[0]);
	if (substr($lieux[0],0,3) == 'St 'or substr($lieux[0],0,3) == 'St.') {$lieux[0] = "Saint ".@substr($lieux[0],3,35);} // on remplace St et Ste par Saint et Sainte
	if (substr($lieux[0],0,4) == 'Ste ' or substr($lieux[0],0,4) == 'Ste.') {$lieux[0] = "Sainte ".@substr($lieux[0],4,34);} // on remplace St et Ste par Saint et Sainte

			// alimentation du departement : pour pr?rer la jointure cartographie. Si pas trouv?on reconstitue un d?rtement ?artir du lieu.
	$pos_codev = strpos(' '.str_replace($dept['codev'],'&&&',' '.$lieu),'&&&');	// on cherche un code departement pres d une virgule
	if ($pos_codev > 0 and (substr($lieu,$pos_codev + 1,1) == "," or substr($lieu,$pos_codev + 4,1) == "," or ord(substr($lieu,$pos_codev + 1,1)) == 0 /*or ord(substr($lieu,$pos_codev + 4,1)) == 0*/))
	{	$lieux[1] = strtoupper(substr($lieu,$pos_codev - 1,2));
	} else
	{	$pos_libv = strpos(' '.str_replace($dept['libv'],'&&&',' '.strtolower($lieu)),'&&&');		// on cherche un libelle de departement pres d une virgule
		if ($pos_libv > 0)
		{	$temp_code = str_replace($dept['libv'],$dept['codev'],strtolower($lieu));
			$lieux[1] = strtoupper(substr($temp_code,$pos_libv - 1,2));
		} else
		{	$pos_codef = strpos(' '.str_replace($dept['codef'],'&&&',' '.$lieu),'&&&');	// on cherche un code departement pres d un F uniquement pour la France
			if ($pos_codef > 0 and (substr($lieu,$pos_codef + 1,1) == "," or substr($lieu,$pos_codef + 1,1) == chr(10) ))
			{	$lieux[1] = strtoupper(substr($lieu,$pos_codef - 1,2));
			} else
			{	$pos_libb = strpos(' '.str_replace($dept['libb'],'&&&',' '.strtolower($lieu)),'&&&');		// on cherche un libelle de departement en tete de ligne
				if ($pos_libb == 2)
				{	$temp_code = str_replace($dept['libb'],$dept['codeb'],strtolower($lieu));
					$lieux[1] = strtoupper(substr($temp_code,$pos_libb - 2,2));
				} else
				{	if ($lieux[1] == "")			// un seul champ renseigne on le double dans departement
					{	$lieux[1] = $lieux[0];
					}
//else {echo '<td>!!!</td>';}
				}
			}
		}
	}

	$lieux[1] = @str_replace($chain_sup,'', $lieux[1]);
	$lieux[1] = trim($lieux[1]);
//echo '<td>0:'.$lieux[0].'</td><td>1:'.$lieux[1].'</td></tr>';
	return $lieux;
}

function convertir_caract ($text,$encode)
{ 
	if ($encode == 'ANSEL')
	{	$ansel = Array		// table de correspondance pompée dans les sources de Convansel !)
		    ('âe','áe','ãe','èe','âo','áo','ão','èo','âa','áa','ãa','èa','âu','áu','ãu','èu','âi','ái','ãi','èi','ây','èy','ðc','~n',
		     'âE','áE','ãE','èE','âO','áO','ãO','èO','âA','áA','ãA','èA','âU','áU','ãU','èU','âI','áI','ãI','èI','âY','èY','ðC','~N');
		$ansi = Array
		    ('é', 'è', 'ê', 'ë', 'ó', 'ò', 'ô', 'ö', 'á', 'à', 'â', 'ä', 'ú', 'ù', 'û', 'ü', 'í', 'ì', 'î', 'ï', 'ý', 'ÿ', 'ç', 'ñ',
		     'É', 'È', 'Ê', 'Ë', 'Ó', 'Ò', 'Ô', 'Ö', 'Á', 'À', 'Â', 'Ä', 'Ú', 'Ù', 'Û', 'Ü', 'Í', 'Ì', 'Î', 'Ï', 'Ý', 'Ÿ', 'Ç', 'Ñ');
		$text = mb_convert_encoding($text, "UTF-8", "ASCII"); // on est obliger de recoder en ASCII, car les données arrivent je ne sais pas comment.
		$text = str_replace($ansel, $ansi, $text);
	}
	if ($encode == 'ANSI')
	{	$text = utf8_encode($text);
	}
	return ltrim($text);
}

function creation_miniature($filename, $fileurl,$ibase_sauv)
{	global $nb_ligne;
//echo '<br>'.$filename.'---'.$fileurl;

					// test extension jpg ou png
//	$cpm = preg_match_all("/\.([^\.]+)$/", $filename, $match);
//	if ($cpm > 0)	{$ext = strtolower(trim($match[1][0]));} else {$ext = "";}
	$ext = substr($filename, strrpos($filename, '.')+1, 4);
	if ($ext != 'jpg' and $ext != 'jpeg' and $ext != 'jp2' and $ext != 'png' and $ext != 'JPG' and $ext != 'JPEG' and $ext != 'JP2' and $ext != 'PNG')
	{	echo '<tr><td>WARNING&nbsp;</td><td>&nbsp;'.$filename.'</td><td>&nbsp;Format <b>'.$ext.'</b> is not supported. Please use JPEG or PNG formats.</td></tr>';
		return;
	}
				// recuperation images internet
	if ($fileurl and !file_exists('picture/'.$ibase_sauv.'/'.$filename) )
	{
    $in=    @fopen($fileurl, "rb");
    // tant qu'il y a des 1 et des 0, on boucle
    while ($temp = @fread($in,8192))
    {	$brut = $brut.$temp;
    }
    @fclose($in); // on referme l'ouverture sur le fichier source

		if ($in)
	  { $out=   @fopen('picture/'.$ibase_sauv.'/'.$filename, "wb");
	    fwrite($out, $brut); 
	    @fclose($out);// on referme le fichier qu'on vient de creer
	  }
	}

				// test existence fichier
	if (!file_exists('picture/'.$ibase_sauv.'/'.$filename))
	{	$str = htmlentities($filename, ENT_NOQUOTES, 'utf-8');
		$str = preg_replace('#&([A-za-z])(?:acute|grave|cedil|circ|orn|ring|slash|th|tilde|uml);#', '___', $str);
		if (strpos ($str,'___') )
 		{	$line = $nb_ligne - 1;
 			echo '<tr><td>WARNING&nbsp;</td><td>&nbsp;'.$filename.'&nbsp;</td><td>&nbsp;accentued character <b>'.substr($filename,strpos ($str,'___'),2).'</b> is not authorized. Modify gedcom file in line <b>'.$line.'</b></td></tr>';
 		} else
		{	echo "<tr><td>WARNING&nbsp;</td><td>&nbsp;".$filename."&nbsp;</td><td>&nbsp;hadn't localized. Deposit it in /picture directory and upload base again.</td></tr>";
		}
	} elseif (!file_exists('picture/'.$ibase_sauv.'/thumbs/'.$filename))		// la miniature existe, on gagne du temps a ne pas la recreer
	{
					// recu format image
		$taille = @getimagesize('picture/'.$ibase_sauv.'/'.$filename);
	
					/* si image deja petite ou fonction miniature pas dispo, on utilise l'image telle quelle (copie dans repertoire thumbs)
					sinon, on cree le timbre */
		if (($taille[0] < 150) && ($taille[1] < 150) or version_php_gd_OK() == FALSE or version_gd() == FALSE)
		{	
			copy('picture/'.$ibase_sauv.'/'.$filename, 'picture/'.$ibase_sauv.'/thumbs/'.$filename);
			if (version_php_gd_OK() == FALSE)   // version gd ?
			{	echo '<tr><td>WARNING&nbsp;</td><td>&nbsp;'.$_SERVER['SERVER_SOFTWARE'].'&nbsp;</td><td>&nbsp;WARNING : Thumbnail creation have failed. To optimize response times, you can upgrade your PHP release.</td></tr>';
			}
		} else 	// OK pour la miniaturisation
		{	$largeur = 100;
			$hauteur = round($taille[1] * ($largeur/$taille[0]));
	
			if ($ext=="jpg" || $ext=="jpeg" || $ext=="jp2" || $ext=="JPG" || $ext=="JPEG" || $ext=="JP2")
			{	$im = @imagecreatefromjpeg('picture/'.$ibase_sauv.'/'.$filename);   // @ dans les cas ou le fichier jpeg est corrompu
			} else if ($ext=="png")
			{	$im = @imagecreatefrompng('picture/'.$ibase_sauv.'/'.$filename);
			} 
			if ($im)
			{	$new = imagecreatetruecolor($largeur, $hauteur);
				imagecopyresampled($new, $im, 0, 0, 0, 0, $largeur, $hauteur, $taille[0], $taille[1]);
				if ($ext=="jpg" || $ext=="jpeg" || $ext=="jp2"  || $ext=="JPG" || $ext=="JPEG" || $ext=="JP2")
				{	imagejpeg($new, 'picture/'.$ibase_sauv.'/thumbs/'.$filename);
				} else
				{	imagepng($new, 'picture/'.$ibase_sauv.'/thumbs/'.$filename);
				}
				imagedestroy($im);
				imagedestroy($new);
			}
		}
	}
}

function maj_evenement($id_indi,$id_pere,$id_mere,$id_sour,$niveau)
{	global $got_tag;
	global $got_lang;
	global $niveau1_encours;global $maria_encours;global $id_fam;
	global $date_evene;global $lieu_evene;global $note_evene;
	global $date_naiss;global $lieu_naiss;		// stockage pour rupture individu plus tard
	global $date_deces;global $lieu_deces;
	global $asso;

/* Pb : comment inserer un evenement mariage quand aucune balise MARR ? */
	if (mb_substr($note_evene,0,1) == chr(13) or mb_substr($note_evene,0,1) == chr(10)) {$note_evene = mb_substr($note_evene,1,mb_strlen($note_evene));}
	if ($niveau1_encours == "BIRT")	{$date_naiss = $date_evene;$lieu_naiss = $lieu_evene;}
	if ($niveau1_encours == "DEAT")	{$date_deces = $date_evene;$lieu_deces = $lieu_evene;}

//echo '<br>'.$niveau.'/'.$id_fam.'/'.$niveau1_encours.'/'.$maria_encours.'/'.$id_indi.'/'.$id_pere.'/'.$id_mere.'/'.$id_sour;

			// on ecrit les sources correspondant aux evenements
	if ($id_sour != '' and $niveau1_encours != NULL)		// une reference source a ete trouvee, on l'enregistre
	{	if ($id_indi)
		{	$query = 'INSERT into `got_'.$_REQUEST['ibase'].'_even_sour` VALUES ("'.$id_indi.'",0,0,"'.$niveau1_encours.'","'.$date_evene.'","'.mb_substr($lieu_evene[0],0,42).'","'.mb_substr($lieu_evene[1],0,42).'","'.$id_sour.'","SOUR","")';
			sql_exec($query,0);
		} else
		if ($id_pere or $id_mere)
		{	//echo '<br>'.$id_indi.'/'.$id_pere.'/'.$id_mere.'/'.$id_sour."->";
			$query = 'INSERT into `got_'.$_REQUEST['ibase'].'_even_sour` VALUES (0,"'.$id_pere.'","'.$id_mere.'","'.$niveau1_encours.'","'.$date_evene.'","'.mb_substr($lieu_evene[0],0,42).'","'.mb_substr($lieu_evene[1],0,42).'","'.$id_sour.'","SOUR","")';
			sql_exec($query,0);
		}
	}

	if (  ($niveau == "0"	and $id_fam and $maria_encours == NULL )
//	      or ($niveau = 1 and $got_tag[$niveau1_encours] != NULL)
	      or ($niveau = 1 and array_key_exists($niveau1_encours, $got_tag) != NULL)
	    ) // exclusions des tags non reconnu par geneotree ET cas particulier de creation d'un evenement mariage par defaut quand absence de saisie
	{	if ( ($id_indi or $id_pere or $id_mere) )
		{	$count_preg = preg_match_all("([0-9][0-9][0-9][0-9]+)",$date_evene,$anne_evene); // si on trouve 4 chiffres consecutifs, c'est une annee
			if ($niveau == "0") {$niveau1_encours = "MARR";}  // forcage balise MARR
			if ($id_indi == "") {$id_indi = 0;}
			if ($id_pere == "") {$id_pere = 0;}
			if ($id_mere == "") {$id_mere = 0;}
			if ($count_preg == 0) {$anne_evene[0][0] = 0;}

			$query = 'INSERT INTO `got_'.$_REQUEST['ibase'].'_evenement` VALUES ("'.$id_indi.'","'.$id_pere.'","'.$id_mere.'","'.$niveau1_encours.'","'.$date_evene.'","'.mb_substr($lieu_evene[0],0,42).'","'.mb_substr($lieu_evene[1],0,42).'","'.$note_evene.'","'.substr($anne_evene[0][0],-4).'")';
			sql_exec($query,0);

						// integration des temoins witness
			for ($aa = 0; $aa < @count($asso['id']); $aa++)
			{	if ($id_indi == "") {$id_indi = 0;}
				if ($id_pere == "") {$id_pere = 0;}
				if ($id_mere == "") {$id_mere = 0;}
				$query = 'INSERT INTO `got_'.$_REQUEST['ibase'].'_even_sour` VALUES ("'
					.$id_indi.'","'.$id_pere.'","'.$id_mere.'","'.$niveau1_encours.'","'.$date_evene.'","'.mb_substr($lieu_evene[0],0,42).'","'.mb_substr($lieu_evene[1],0,42).'","'.$asso['id'][$aa].'","RELA","'.$asso['temoi'][$aa].'")';
				sql_exec($query,0);
			}

							// integration des urls. A priori, aucun FILE ne correspond a un evenement niveau 1 (je laisse le code au cas ou).
/*				for ($aa = 0; $aa < count($filename); $aa++)
			{	$query = 'INSERT INTO `got_'.$_REQUEST['ibase'].'_even_sour` VALUES ("'
					.$id_indi.'","'.$id_pere.'","'.$id_mere.'","'.$niveau1_encours.'","'.$date_evene.'","'.mb_substr($lieu_evene[0],0,42).'","'.mb_substr($lieu_evene[1],0,42).'","","FILE","'.mb_substr($filename[$aa],0,255).'")';
				sql_exec($query,0);
				creation_miniature ($filename[$aa]), $filename[$aa]);
			}
			$filename = NULL;
*/		
		}
	}
//		else 
//		{	echo '<br>'.$got_lang['MesT1'].' <b>'.$niveau1_encours.'</b> <i>"'.$note_evene.'"</i> '.$got_lang['MesT2'];
//		}
	
	$niveau1_encours = NULL;
	$asso = NULL;
	$date_evene = ""; $lieu_evene[0] = " "; $lieu_evene[1] = " "; $note_evene = ''; $anne_evene = '';
}

function supprimer_base_club ($ibase_sauv)
{	$query = "SELECT * FROM g__club WHERE base = '".$ibase_sauv."'";
	$result = sql_exec($query,0);
	$row = mysqli_fetch_row($result);

	$vclub = '$club';
	if ($row[0])
	{	$query = 'DELETE FROM `got_'.$vclub.'_individu` WHERE id_indi BETWEEN '.$row[1].' AND '.$row[2];
		sql_exec($query,0);
		$query = 'DELETE FROM `got_'.$vclub.'_evenement` WHERE id_indi BETWEEN '.$row[1].' AND '.$row[2].' or id_husb BETWEEN '.$row[1].' AND '.$row[2].' or id_wife BETWEEN '.$row[1].' AND '.$row[2];
		sql_exec($query,0);
		$query = 'DELETE FROM `got_'.$vclub.'_even_sour` WHERE id_indi BETWEEN '.$row[1].' AND '.$row[2].' or id_husb BETWEEN '.$row[1].' AND '.$row[2].' or id_wife BETWEEN '.$row[1].' AND '.$row[2];
		sql_exec($query,0);
		if ($row[3] !== 0)
		{	$query = 'DELETE FROM `got_'.$vclub.'_source` WHERE id_sour BETWEEN '.$row[3].' AND '.$row[4];
			sql_exec($query,0);
		}
		
		$query = 'DELETE FROM g__club WHERE base = "'.$ibase_sauv.'"';
		sql_exec($query,0);
		
		/* IMPORTANT : mettre a jour le de-cujus de g__base, base club, au cas ou on detruit le personnage principal */
	}
}
/*****************FONCTIONS POUR LA GESTION DES BASES GEOGRAPHIQUES *****************************/

function trl ($lieu) // Traitement des lieux
{
	$lieu = sans_accent($lieu);
	$lieu = @mb_ereg_replace('-', ' ', $lieu);  // on supprime les tirets pour la jointure avec la table communes
	if (@mb_substr($lieu,0,3) == 'St ') {$lieu = "Saint ".@mb_substr($lieu,3,35);} // on remplace St et Ste par Saint et Sainte
	if (@mb_substr($lieu,0,4) == 'Ste ') {$lieu = "Sainte ".@mb_substr($lieu,4,34);} // on remplace St et Ste par Saint et Sainte
	return strtolower($lieu);
}

function maj_fichier_perso()
{	global $communes_perso;
	global $cle;
	global $position;

	if ($position !== FALSE)	
	{	$gauche = substr($communes_perso,0,$position + strlen($_REQUEST['commu'].$_REQUEST['depar'].$_REQUEST['pays'].'AA'));
		$milieu = ";".$_REQUEST['latit'].";".$_REQUEST['longi'];
		$position_fin_ligne = strpos($communes_perso,chr(10),$position);
		$droite = substr($communes_perso,$position_fin_ligne, strlen($communes_perso) );
		$communes_perso = $gauche.$milieu.$droite; 
	} else
	{	$communes_perso = $communes_perso.$cle.";".$_REQUEST['latit'].";".trim($_REQUEST['longi']).chr(10);
	}
	file_put_contents ("geo/communes_perso.dat",$communes_perso);
}

/********************** DEBUT DU SCRIPT ************************************/
/*********************** ATTENTION : le fichier admin.php doit reste encode ASCII pour que la fonction de conversion ANSEL fonctionne ***********************/

$query = "
CREATE TABLE g__base (
  base varchar(255) NOT NULL,
  sosa_principal int(10) unsigned NOT NULL default '0',
  volume	int (6) unsigned,
  commentaire varchar(120),
  source INT(6),
  media INT(6),
  version char(4),
  defa_langu char(2),
  defa_theme varchar(20),
  defa_palma char(4),
  defa_inter char(4),
  `defa_forma` char(16) NOT NULL,
  `defa_centa` char(3) NOT NULL,
  PRIMARY KEY BASES_PK (base)
) ".$collate;
sql_exec($query,2);

$query = 'SELECT COLUMN_NAME
FROM INFORMATION_SCHEMA.COLUMNS
WHERE table_name = "g__base"
AND TABLE_SCHEMA = "'.$sql_base.'"
AND COLUMN_NAME = "consang" ';
$result = sql_exec($query);
$row = mysqli_fetch_row($result);

if (!$row[0])
{	$query = ' ALTER TABLE `g__base` 
						ADD `consang` varchar(1) default "N",
						ADD `logiciel` varchar(42),
						ADD `editeur` varchar(42)';
	sql_exec($query);
}

$query = "CREATE TABLE g__club (
 base varchar(255) NOT NULL
,indi_min int NOT NULL
,indi_max int NOT NULL
,sour_min int NOT NULL
,sour_max int NOT NULL
,PRIMARY KEY CLUB_PK (base)
)";
sql_exec($query,2);

$url = url_request();
$img_absentes[] = NULL;
if (!isset($_REQUEST['irow'])) {$_REQUEST['irow'] = "";}
if (!isset($_REQUEST['icont'])) {$_REQUEST['icont'] = "";}
if (!isset($_REQUEST['ipag2'])) {$_REQUEST['ipag2'] = "";}
if (!isset($_REQUEST['ibase'])) {$_REQUEST['ibase'] = "";}
if (!isset($_REQUEST['club'])) {$_REQUEST['club'] = "N";}

echo '<!DOCTYPE html>';

$ADDRC = str_replace(array('.',':'),'',getenv("REMOTE_ADDR"));

switch ($_REQUEST["ipag2"])
{	case	"geo"		: $titre = $got_lang['CarTi'];break;
	case	"def"		: $titre = $got_lang['Defau'];break;
	default	: $titre = 'Gedcom';
}
echo "<HTML>";
echo "<HEAD>";
//echo '<META http-equiv="Content-type" content="text/html; charset=iso-8859-1" name="author" content="Damien Poulain">';
echo '<META http-equiv="Content-type" content="text/html; charset=utf-8" name="author" content="Damien Poulain">';
echo "<TITLE>GeneoTree v".$got_lang['Relea']." - ".$got_lang['Admin']."</TITLE>";
echo "<LINK rel='stylesheet' href='themes/wikipedia.css' type='text/css'>";
echo "</HEAD>";
echo "<BODY>";


				// Semblable a menu.php
echo '<table><tr>';
echo '<td><b><a href = index.php><img border=0 width=35 height=35 src="themes/icon-home.png"></a></b></td>';
echo '<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>';
echo '<td><font size=4><b>Administration - '.$titre.'</b></font></td>';	
echo '</tr></table>';

				// Sous-menus administration
echo '<table><tr>';
echo '<td class=menu_td><a class=menu_td href = admin.php?ipag2=geo&ipays&icomm=ZZZ&commu&pays&depar&latit&longi&valid_geo>'.$got_lang['CarTi'].'</a></td>';	
echo '<td>&nbsp;&nbsp;</td>';
echo '<td class=menu_td><a class=menu_td href = admin.php?ibase=&ipag2=&club=N>'.$got_tag['GEDC'].'</a></td>';	
echo '<td>&nbsp;&nbsp;</td>';
echo '<td class=menu_td><a class=menu_td href = admin.php?ipag2=def&valid>'.$got_lang['Defau'].'</a></td>';	
echo '<td>&nbsp;&nbsp;</td>';

//echo '<td class=menu_td><a class=menu_td href = admin.php?ipag2=spc>Cousins</a></td>';	
echo '</tr></table>';


//                          DEBUT DU TRAITEMENT PRINCIPAL

if ($_REQUEST['ipag2'] == NULL) //i.e gestion des bases et fichiers gedcom
{
					// Upload des fichiers gedcom
	echo '<br>';
	include ('_upload.inc.php');

	global $char;
	if (Serveurlocal()) {	$Chemin = getcwd();	} else 	{	$Chemin = 'geneotree';}
	echo '<br>Deposit your gedcom files the follow directory : "<b>'.$Chemin.'/gedcom</b>" and your pictures in "<b>'.$Chemin.'/picture</b>" directories';
	echo '<br>Click on the <b>arrows</b> to upload gedcom files';
	if ( ($_REQUEST['ibase'] == NULL) or ($_REQUEST['ibase'] != '' and $_REQUEST['iaction'] == "del" and $_REQUEST['fconfirm'] == "KO") )
	{					// lecture des fichiers gedcom dans /gedcom
		$handle = opendir('./gedcom');
		while ($file = readdir($handle) ) 
		{	
			if (substr($file,-3) == "ged" or substr($file,-3) == "GED")
			
			{	$taille_fichier = round(filesize(getcwd().'/gedcom/'.$file)/1024,0);
				$date_fichier = strftime("%c",filemtime(getcwd().'/gedcom/'.$file));
				$file = substr($file,0,strlen($file)-4);
				preg_match_all("([a-zA-Z0-9_]+)", $file, $match);
			
				if (strlen($file) > 50)	// max sql 64, geo prend 14 (got_ + _evenement), il reste 50
				{	echo '<br>WARNING : <b>'.$file.'.ged</b> is up to 50 caracters. Thanks to modify it to upload in GeneoTree<br>';
				} elseif ($file == $match[0][0])
				{	$base['nom'][] = $file;
					$base['fich'][] = 'OK';
					$base['base'][] = 'KO';
					$base['club'][] = 'KO';
					$base['date'][] = $date_fichier;
					$base['taille'][] = $taille_fichier;
				} else
				{	echo '<br>WARNING : <b>'.$file.'.ged</b> contains space or special characters which are not supported. Thanks to rename it.';
				}
			}
		}
		closedir($handle);

							// lecture des bases deja uploadees (double lecture sql et file system, pas facile)
	
		$query = "SELECT base FROM g__base order by 1";
		$result = sql_exec($query,2);
		while($row = @mysqli_fetch_row($result))  // 1ere arrivee : pas de bases chargees
		{	$indice ='';
			$indice = array_search ( $row[0], $base['nom'] );

			if ( $indice or $indice === 0 )
			{	$base['base'][$indice] = 'OK';}
			else
			{	$base['nom'][] = $row[0];
				$base['fich'][] = 'KO';
				$base['base'][] = 'OK';
				$base['club'][] = 'KO';
				$base['date'][] = '';
				$base['taille'][] = '';
			}
		}

		$query = "SELECT base FROM g__club";
		$result = sql_exec($query,2);
		while($row = @mysqli_fetch_row($result))
		{	$indice = array_search ( $row[0], $base['nom'] );
			$base['club'][$indice] = 'OK';
		}
		
		@array_multisort ($base['nom'],$base['fich'],$base['base'],$base['club'],$base['date'],$base['taille']);

							// lecture des 2 resultats : soit existe fichier, existe base, ou les 2
	
		echo '<br><table>';
		
		echo '<tr class=ligne_tr2>';
		echo '<td align=center class="titre bord_haut bords_verti bord_bas" colspan=2><b>Gedcom files availables</b></td>';
		echo '<td style="width:30px; background:white;"></td>';
		echo '<td align=center class="titre bord_haut bords_verti bord_bas"><b><a title="one gedcom file in dedicated base">GeneoTree databases</a></b></td>';
		echo '<td style="width:100px; background:white;"></td>';
		echo '<td align=center class="titre bord_haut bords_verti bord_bas"><b><a title="all gedcom files in Club base">Bases in "Club"</a></b></td>';
		echo "</tr>";
	
		$ii = 0;
		while ($ii < count($base['nom']))
		{	if ($base['fich'][$ii] == 'OK')
			{	$href_visu = "<a href=admin.php?ibase=".mb_ereg_replace(' ','_',$base['nom'][$ii])."&iaction=visu&ipag2= title='".$got_lang['IBVis']."'>".$base['nom'][$ii].".ged</a>";
				$href_chg  = "<a href=admin.php?ibase=".mb_ereg_replace(' ','_',$base['nom'][$ii])."&iaction=chg&irow=0&club=N&ipag2= title='".$got_lang['IBCha']."'><img src=themes/fleche_droite.png border=0></a>";
				$href_chgclub  = "<a href=admin.php?ibase=".mb_ereg_replace(' ','_',$base['nom'][$ii])."&iaction=chg&club=Y&ipag2= title='".$got_lang['IBCha']."'><img src=themes/fleche_droite.png border=0></a>";
			} else 
			{	$href_visu ='';$href_chg = '';$href_chgclub = '';
			}
			if ($base['base'][$ii] == 'OK')
			{	$href_del  = "<a href=admin.php?ibase=".mb_ereg_replace(' ','_',$base['nom'][$ii])."&iaction=del&club=N&ipag2=&fconfirm= title='".$got_lang['IBSup']."'>".$base['nom'][$ii]."</a>";
			} else 
			{	$href_del = '';
			}

			if ($base['club'][$ii] == 'OK')
			{	$href_delclub  = "<a href=admin.php?ibase=".mb_ereg_replace(' ','_',$base['nom'][$ii])."&iaction=del&club=Y&ipag2=&fconfirm= title='".$got_lang['IBSup']."'>".$base['nom'][$ii]."</a>";
			} else 
			{	$href_delclub = '';
			}

			if ($ii % 2 == 0) {echo '<tr class="ligne_tr1">';} else {echo '<tr class="ligne_tr2">';}

			echo '<td align=left class=bords_verti>&nbsp;'.$href_visu.'&nbsp;</td>';
			echo '<td align=right class=bords_verti><font size = 1>&nbsp;'.$base['taille'][$ii].' Ko&nbsp;</font></td>';
//			echo '<td align=right class=bords_verti><font size = 1>&nbsp;'.$base['date'][$ii].'&nbsp;</font></td>';
			echo '<td align=right class=bords_verti style="background:white">'.$href_chg.'&nbsp;</td>';
			echo '<td align=left class=bords_verti>&nbsp;'.$href_del.'&nbsp;</td>';
			echo '<td align=right class=bords_verti style="background:white">&nbsp;'.$href_chgclub.'&nbsp;</td>';
			echo '<td align=left class=bords_verti>&nbsp;'.$href_delclub.'&nbsp;</td>';
			echo "</tr>";
			$ii++;
		}
		echo '<tr><td class=bord_haut colspan=2>&nbsp;</td><td></td><td class=bord_haut></td><td></td><td class=bord_haut></td></tr>';
		echo '</table><br>';
	}
	else if ($_REQUEST['ibase'] != NULL and ($_REQUEST['iaction'] == "chg" or $_REQUEST['iaction'] == "visu"))
	{	
				// initialisation des nombreuses variables
		$id_indi = ""; $id_indi2 = ""; $id_fam = ""; $id_source = ""; $id_source2 = ""; $id_note = ""; $id_sour = "";
		$pere = ""; $mere = ""; $pere2 = ""; $mere2 = ""; $note_indi = ""; $note_sour = "";
		$nom = ""; $prenoms[0] = "";$prenoms[1] = "";$prenoms[2] = ""; $sexe = ""; $profession = ""; $sour_ajus = 0; $commentaire = "";	
		$lieu_evene[0] = " "; $lieu_evene[1] = " ";

		$F = ouvrir_gedcom($_REQUEST['ibase'], $url);

		if ($F != FALSE)

		{	if ($_REQUEST['iaction'] == "visu")
			{	echo '<br><a class=menu_td href=admin.php?ibase=&ipag2=>Return</a><br><br>';
				$ii = 0;
				while ($ligne = fgets ($F, 2048) and $ii < 10000 )   // 10000 lignes pour proteger la m?ire du navigateur
				{	echo $ligne."<br>";
					$ii++;
				}
			} else

			{			// debut du chargement du fichier gedcom
				echo '<br>File <b>'.$_REQUEST['ibase'].'.ged</b> Current upload of gedcom file <br>';
				$T_OLD = time();

				/* Detection jeu de caracteres ANSEL/ANSI et ASCII/UTF-8
							Format fichier : on cherche l'encodage UTF-8 sur les 3 premiers octets. Si pas trouv?c'est du ASCII.
							Encodage, on cherche les tags ANSEL et UTF8, si pas trouv?c'est du ANSI
							Dans la fonction convertir_caract(), on tient compte de ANSEL et on encode UTF8 si besoin.
							Le but final est de tout pr?rer pour bien stocker en UTF8 dans MySql.
				*/
				$encode = 'ANSI';
				$i = 0;
				while ($i < 100) 
				{	$ligne = fgets ($F, 255);
					if (@mb_strpos($ligne, 'ANSEL') != NULL ) {$encode = 'ANSEL';}
					if (@mb_strpos($ligne, 'UTF-8') != NULL ) {$encode = 'UTF-8';} 
//					if (mb_detect_encoding($ligne, 'auto') == 'UTF-8') {$encode = 'UTF-8';echo $ligne;}
					$i = $i +1;
				}
				echo 'Characters detected : <b>'.$encode.'</b><br>';

				/*********** FIN Detection format fichier et encodage  */

						/* Preparation des tables SQL */

				$ibase_sauv = $_REQUEST['ibase'];   // on sauvegarde nom fichier gedcom car ibase devient $Club pour la base Club
				if ($_REQUEST['club'] == 'Y')
				{ $_REQUEST['ibase'] = '$club';
				}

				if (!$_REQUEST['irow'])  // test pour ne pas droper les bases quand chargement par intervalle de 30 secondes 
				{	if ($_REQUEST['club'] !== 'Y')
					{	$query = "DROP TABLE got_".$_REQUEST['ibase']."_individu" ;
						sql_exec($query,2);
					}
					$query = "CREATE TABLE got_".$_REQUEST['ibase']."_individu (
					id_indi			int NOT NULL default 0
					,nom			varchar(32)
					,prenom1		varchar(32)
					,prenom2		varchar(32)
					,prenom3		varchar(32)
					,sexe			tinytext
					,profession		varchar(42)
					,date_naiss		varchar(32)
					,lieu_naiss		varchar(42)
					,dept_naiss		varchar(42)
					,date_deces		varchar(32)
					,lieu_deces		varchar(42)
					,dept_deces		varchar(42)
					,note_indi		text
					,id_pere		int
					,id_mere		int
					,tri			smallint
					,sosa_dyn		bigint
					,anne_deces		smallint
					,PRIMARY KEY 	PK_INDI  (id_indi)
					,KEY 			FK1_NOM  (nom)
					,KEY 			FK2_PREN (prenom1)
					,KEY			FK3_PERE (id_pere)
					,KEY			FK4_MERE (id_mere)
					) ".$collate;
					sql_exec($query,2);

					if ($_REQUEST['club'] !== 'Y')
					{	$query = "DROP TABLE got_".$_REQUEST['ibase']."_evenement" ;
						sql_exec($query,2);
					}
					$query = "CREATE TABLE got_".$_REQUEST['ibase']."_evenement (
					id_indi			int not NULL default 0
					,id_husb			int not NULL default 0
					,id_wife			int not NULL default 0
					,type_evene		varchar(4) NOT NULL
					,date_evene		varchar(32)
					,lieu_evene		varchar(42)
					,dept_evene		varchar(42)
					,note_evene		mediumtext
					,anne_evene		smallint
					,KEY 			FK1_INDI (id_indi, type_evene)
					,KEY 			FK2_HUSB (id_husb, type_evene)
					,KEY 			FK3_WIFE (id_wife, type_evene)
					,KEY 			FK4_WIFE (id_husb, id_wife, type_evene)
					,KEY 			FK5_TYPE (type_evene)
					,KEY 			FK6_LIEU (lieu_evene)
					) ".$collate;
					sql_exec($query,2);
	
					if ($_REQUEST['club'] !== 'Y')
					{	$query = "DROP TABLE got_".$_REQUEST['ibase']."_source" ;
						sql_exec($query,2);
					}
					$query = "CREATE TABLE got_".$_REQUEST['ibase']."_source (
					id_sour			int NOT NULL default 0,
					note_source		mediumtext,
					PRIMARY KEY 	PK_SOUR (id_sour)
					) ".$collate;
					sql_exec($query,2);
	
					if ($_REQUEST['club'] !== 'Y')
					{	$query = "DROP TABLE got_".$_REQUEST['ibase']."_even_sour" ; // relation n-n entre evenement et source
						sql_exec($query,2);
					}
					$query = "CREATE TABLE got_".$_REQUEST['ibase']."_even_sour (
					id_indi			int not NULL default 0,
					id_husb			int NOT NULL default 0,
					id_wife			int not NULL default 0,
					type_evene		varchar(4) NOT NULL,
					date_evene		varchar(32),
					lieu_evene		varchar(42),
					dept_evene		varchar(42),
					id_sour			int NOT NULL default 0,
					type_sourc		varchar(4) NOT NULL,
					attr_sourc		varchar(255),
					KEY 			FK1_INDI (id_indi, type_evene),
					KEY 			FK2_HUSB (id_husb, type_evene),
					KEY 			FK3_WIFE (id_wife, type_evene),
					KEY 			FK4_SOUR (id_sour)
					) ".$collate;
					sql_exec($query,2);
							// type_sourc = SOUR, RELA
							// attr_sourc = Code Witness
	
	
					$query = "DROP TABLE got_".$ADDRC."_note" ;
					sql_exec($query,2);
	
					$query = "CREATE TABLE got_".$ADDRC."_note (
					id_note			int NOT NULL default 0,
					id_indi			int NOT NULL default 0,
					PRIMARY KEY 	PK_NOTE (id_note)
					) ".$collate;
					sql_exec($query,0);
	
					$query = "DROP TABLE got_".$ADDRC."_obje" ;
					sql_exec($query,2);
	
					$query = "CREATE TABLE got_".$ADDRC."_obje (
					id_obje			int NOT NULL default 0,
					id_niv1			int NOT NULL default 0,
					niv1				varchar(4),
					PRIMARY KEY 	PK_NOTE (id_obje)
					) ".$collate;
					sql_exec($query,0);

				}

						// preparation des repertoires pictures

						// creation du repertoire thumbs si inexistant
				chdir("picture");
				if (!is_dir($ibase_sauv) )   {	mkdir ($ibase_sauv); }
				chdir ($ibase_sauv);
				if (!is_dir('thumbs') )  						{	mkdir ('thumbs'); }
				chdir ('..');
				chdir ('..');

				
						// prelecture de la table des departements pour la fonction trl_lieu plus tard

				$final = FALSE;
				$query = 'describe g__geodept';
				$result = sql_exec($query,2);
			
				if (mysqli_errno($pool) == 0)
				{	$query = 'select * from g__geodept limit 1';
					$result = sql_exec($query);
					$row = mysqli_fetch_row($result);
					if ($row[0])
					{	$final = TRUE;
					}
				}

				if ($final) {$dept = recup_dept();}

				/************************* debut de la lecture principale ************************/
				/* Principe : on considere que seul les INDI, FAM, SOURC, NOTE peuvent etre isole sur un niveau 0
				ce qui est tres approximatif. Tout peut etre isole dans un gedom
				exemples : OCCUPATION, EMOTIONALRELATIONSHIP,TWIN, PLAC,PICTURE
				Quand un niveau 0 correspondant une table du modele pas de probleme, la fin de la lecture, on lit
					ex : INDI -> insertion dans individu
						SOUR -> insertion dans source
						FAM -> insertion capitale dans la table relation
								insertion du mariage dans la table evenement 
								et update pere,mere dans individu pour denormalisation qui ne sert pratiquement a rien.
									 Je suis a 2 doigts de le supprimer. A VOIR PLUS TARD.
				Les notes. Elles sont toujours au niveau 1, donc indendent toujours d'1 indiv ou d'une famille.
				Mais elles sont qqfois stockees par pointeur sur 0 NOTE.
				Et l obligation de stocker le pointeur en SQL car au moment o le lit (1 NOTE), on n'a pas le texte.
				Idem avec 1 OCCU et le logiciel GenoPro. J'imagine que ce logiciel g  une table de r OCCU.
				*/
				echo '<table>';  // Pour la presentation des messages d'erreurs

				$F = ouvrir_gedcom($ibase_sauv, $url);
				$timeout = recup_timeout();

				$nb_lignes_total = 0;
				while ($ligne = fgets ($F, 255)) 
				{	$nb_lignes_total++;
				}	// une premiere boucle pour compter les lignes

				rewind($F);										// remet le pointeur fichier au debut
				progression($nb_lignes_total);			// charge les scripts et affiche une premiere fois la barre
				flush();

				// determination des correcteurs de compteurs
				$indi_ajus = 0;
				$sour_cpt = 0;

				if ($_REQUEST['club'] == 'Y')
				{
					$indi_cpt = 0;
					$indi_max = 0;
					$sour_max = 0;
					$sour_ajus = 0;

//					$indi_min = 0;
//					$sour_min = 0;

					$continue = TRUE;
					while ($ligne = fgets ($F, 255) and $continue ) 
					{	if 	( @mb_strpos($ligne, ' INDI') and mb_substr($ligne,0,1) == '0')
						{	$continue = FALSE;
							preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$temp);
							$indi_min = $temp[0][0];
						}
					}
					rewind($F);										// remet le pointeur fichier au debut

					$continue = TRUE;
					while ($ligne = fgets ($F, 255) and $continue ) 
					{	if 	( @mb_strpos($ligne, ' SOUR') and mb_substr($ligne,0,1) == '0')
						{	$continue = FALSE;
							preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$temp);
							$sour_min = $temp[0][0];
						}
					}
					rewind($F);										// remet le pointeur fichier au debut

					while ($ligne = fgets ($F, 255)) 
					{	if 	( @mb_strpos($ligne, ' INDI') and mb_substr($ligne,0,1) == '0')
						{	preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$temp);
							$indi_cpt++;
							if ($temp[0][0] > $indi_max) {$indi_max = $temp[0][0];}
						}
						if 	( @mb_strpos($ligne, ' SOUR') and mb_substr($ligne,0,1) == '0')
						{	preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$temp);
							$sour_cpt++;
							if ($temp[0][0] > $sour_max) {$sour_max = $temp[0][0];}
						}
					}
					rewind($F);										// remet le pointeur fichier au debut

					if ( intval($indi_max) - intval($indi_min) > intval($indi_cpt) + 20000 or intval($sour_max) - intval($sour_min) > intval($sour_cpt) + 20000)
					{	$interv_indiv = intval($indi_max) - intval($indi_min);
						$interv_sourc = intval($sour_max) - intval($sour_min);
						echo "<br><br><b>UPLOAD FAILED</b> => The interval of numerotation of this gedcom is more than 20000";
						if ($interv_indiv > 20000) {echo '<br><br>INDIV Interval numerotation = <b>'.$interv_indiv.'</b> (Min : '.$indi_min." Max : ".$indi_max.')';}
						if ($interv_sourc > 20000) {echo '<br><br>SOURCE Interval numerotation = <b>'.$interv_sourc.'</b> (Min : '.$sour_min." Max : ".$sour_max.')';}
//						if ($interv_sourc > 20000) {echo "<br>SOURCE Numerotation -> Min : ".$sour_min." Max : ".$sour_max.' => Interval = <b>'.$interv_sourc.'</b>';}
						echo "<br><br>Rebuild your gedcom file and retry.";
						echo '<br><br><a class=menu_td href=admin.php?ibase&ipag2&club=N>OK</a>';	// mise a jour des sosa_dyn
						exit;
					}

					$query = 'SELECT max(indi_max) FROM g__club';
					$result = sql_exec ($query,0);
					$row = @mysqli_fetch_row($result);
					$indi_ajus = $row[0] - $indi_min + 1;

					$query = 'SELECT max(sour_max) FROM g__club';
					$result = sql_exec ($query,0);
					$row = @mysqli_fetch_row($result);
					$sour_ajus = $row[0] - $sour_min + 1;
				}

				$nb_ligne=0;
				if ($_REQUEST['irow'] != 0)   // pour lire le 0 HEAD
				{ while ($nb_ligne <= $_REQUEST['irow']) {$ligne = fgets ($F, 2048); $nb_ligne = $nb_ligne + 1;}
				}
echo '<table>';				// debug pour affichage tr_lieu
				$i=0;$i_niv0 = 0; $cpt_asso = -1;
				while ($ligne = convertir_caract (fgets ($F, 2048), $encode) )
				{	 
//echo '<br>'.$ligne;
					if (@mb_substr($ligne,0,1) == 0 )			// on vient de finir de lire un niv 0 (individu, famille, source, note, obje)
					{	$i_niv0++;
						if ($id_indi or $id_indi == "0") {$id_indi2 = $id_indi + $indi_ajus;}
						if ($id_source or $id_source == "0") {$id_source2 = $id_source + $sour_ajus;}
						if ($pere or $pere == "0") {$pere2 = $pere + $indi_ajus;}
						if ($mere or $mere == "0") {$mere2 = $mere + $indi_ajus;}
//echo '<br>===> Niv 0 : '.$niveau1_encours.' / Id_indi : '.$id_indi2.'/pere:'.$pere.'/pere2:'.$pere2.'/'.$mere2.'/'.$id_source2;
						maj_evenement($id_indi2,$pere2,$mere2,$id_source2,"0");
						if ($id_indi2 or $id_indi2 == "0") // on vient de finir de lire un individu
						{	if (mb_substr($note_indi,0,1) == chr(13) or mb_substr($note_indi,0,1) == chr(10)) {$note_indi = mb_substr($note_indi,1,mb_strlen($note_indi));}
							$count_date_naiss = preg_match_all("([0-9][0-9][0-9][0-9]+)",$date_naiss,$tri); // si on trouve 4 chiffres consecutifs, c'est une annee
							$count_lieu_naiss = preg_match_all("([0-9][0-9][0-9][0-9]+)",$date_deces,$anne_deces); // si on trouve 4 chiffres consecutifs, c'est une annee
//echo '<br>Date:'.$date_naiss.'/Tri:'.$tri[0][0];
							if ($count_date_naiss == 0) {$tri[0][0] = 0;}
							if ($count_lieu_naiss == 0) {$anne_deces[0][0] = 0;}

							$query = 'INSERT INTO `got_'.$_REQUEST['ibase'].'_individu` VALUES ("'
								.$id_indi2.'","'.mb_substr($nom,0,32).'","'.mb_substr($prenoms[0],0,32).'","'.mb_substr($prenoms[1],0,32).'","'.mb_substr($prenoms[2],0,32).'","'
								.$sexe.'","'.mb_substr($profession,0,42).'","'
								.$date_naiss.'","'.mb_substr($lieu_naiss[0],0,42).'","'.mb_substr($lieu_naiss[1],0,42).'","'
								.$date_deces.'","'.mb_substr($lieu_deces[0],0,42).'","'.mb_substr($lieu_deces[1],0,42).'","'
								.mb_substr($note_indi,0,65436).'",0,0,"'.substr($tri[0][0],-4).'",0,"'.substr($anne_deces[0][0],-4).'")';
							sql_exec ($query,0);

											// integration des urls
							for ($aa = 0; $aa < @count($filename); $aa++)
							{	if ($id_indi2 == "") {$id_indi2 = 0;}
								$query = 'INSERT INTO `got_'.$_REQUEST['ibase'].'_evenement` VALUES ("'
									.$id_indi2.'",0,0,"FILE","","","","'.$filename[$aa].'",0)';
								sql_exec ($query,0);
								creation_miniature ($filename[$aa], $fileurl[$aa], $ibase_sauv);
							}
							$id_indi=NULL;$id_indi2=NULL;$nom=NULL;$prenom=NULL;$profession=NULL;
							$date_naiss=NULL;$date_deces=NULL;$anne_deces = NULL;
							$lieu_naiss=NULL;$lieu_deces=NULL;
							$note_naiss = NULL;$note_deces = NULL;
							$note_indi=NULL;
							$filename = NULL;
							$fileurl = NULL;
							$niveau1_encours = '';
							$cpt_asso = -1;
						} else 

						if ($id_fam or $id_fam =="0") // on vient de finir de lire une famille : $pere, $mere, $enfants sont alimentees
						{	
//echo "<br>FAM : ".$pere."/".$mere;
							$pere2 = $pere + $indi_ajus;
							$mere2 = $mere + $indi_ajus;

											// integration des urls rattachees a une famille (pas courant)
							for ($aa = 0; $aa < @count($filename); $aa++)
							{	if ($pere2 == "") {$pere2 = 0;}
								if ($mere2 == "") {$mere2 = 0;}
								$query = 'INSERT INTO `got_'.$_REQUEST['ibase'].'_even_sour` VALUES (
									0,"'.$pere2.'","'.$mere2.'","MARR","","","","","FILE","'.substr($filename[$aa],0,255).'")';
								sql_exec($query,0);
								creation_miniature ($filename[$aa], $fileurl[$aa], $ibase_sauv);
							}

							$pere=NULL;$mere=NULL;
							$pere2=NULL;$mere2=NULL;
							$enfants=NULL;
							$i=0;
							$id_fam = NULL;
							$niveau1_encours=NULL;
							$filename = NULL;
							$fileurl = NULL;
							$maria_encours = NULL;
							$cpt_asso = -1;
						} else 

						if ($id_note or $id_note == "0") // on vient de finir de lire une note. On met a jour la note dans la table individu. 
																	// Seule mise  a jour que l'on ne peut pas le faire au fil de l'eau.
						{	$query = 'SELECT id_indi 
									FROM `got_'.$ADDRC.'_note`
									WHERE id_note = '.$id_note;
							$result = sql_exec ($query,0);
							$row = mysqli_fetch_row($result);
	
							if ($row[0] != NULL)
							{	$query = 'UPDATE got_'.$_REQUEST['ibase'].'_individu 
										SET `note_indi` = "'.substr($note_indi,0,65436).'"
										WHERE `id_indi` = '.$row[0];
								sql_exec ($query,0);
							}
							$note_indi = NULL;
							$id_note = NULL;
							$cpt_asso = -1;
						} else 

						if ($id_sour or $id_sour == "0") // on vient de finir de lire une source 
						{	$id_sour2 = $id_sour + $sour_ajus;
							if (mb_substr($note_sour,0,1) == chr(13) or mb_substr($note_sour,0,1) == chr(10)) {$note_sour = mb_substr($note_sour,1,mb_strlen($note_sour));}
							$query = 'INSERT into got_'.$_REQUEST['ibase'].'_source VALUES ("'.$id_sour2.'","'.$note_sour.'")';
							sql_exec ($query,0);

											// integration des urls on ecrit sans les id indi,pere,mere. Un update fait le boulot apres.
							for ($aa = 0; $aa < @count($filename); $aa++)
							{	$query = 'INSERT INTO `got_'.$_REQUEST['ibase'].'_even_sour` VALUES (
									0,0,0,"SOUR","","","","'.$id_sour2.'","FILE","'.substr($filename[$aa],0,255).'")';
								sql_exec($query,0);
								creation_miniature ($filename[$aa], $fileurl[$aa], $ibase_sauv);
							}

							$id_sour = NULL;
							$id_sour2 = NULL;
							$note_sour = NULL;
							$filename = NULL;
							$fileurl = NULL;
							$cpt_asso = -1;
						} else

						if ($id_obje or $id_obje = "0") // on vient de finir de lire une objet, $filename et 3 sont remplis, on insere dans la table evene_sour 
						{	
											// on lit les id_indi dans ADDRC obje et on insere le filename picture dans evenenement pour l'individu concerné (en general 1 seul)
							$query = 'SELECT * 
									FROM `got_'.$ADDRC.'_obje`
									WHERE niv1="INDI" and id_obje = '.$id_obje;
							$result = sql_exec ($query,0);
							$row = mysqli_fetch_row($result);

							for ($aa = 0; $aa < count($filename); $aa++)
							{	if ($row[1] == "") {$row[1] = 0;}
								$query = 'INSERT INTO `got_'.$_REQUEST['ibase'].'_evenement` VALUES ("'
									.$row[1].'",0,0,"FILE","","","","'.$filename[$aa].'",0)';
								sql_exec ($query,0);
								creation_miniature ($filename[$aa], $fileurl[$aa], $ibase_sauv);
							}
							$id_obje = NULL;
							$filename = NULL; $fileurl = NULL;
						} else

						if ($head)
						{	$head = FALSE;    // pour eviter que 1 SOUR ou 1 FILE ne soient affectés n'importe ou
						}

						if ($timeout[0] == 'KO' and time() - $T_OLD >= $timeout[1] - 1)
						{	$url = url_request();
							echo "<HTML>";
							echo "<HEAD>";
							echo '<META HTTP-EQUIV="Refresh" CONTENT="1; URL=admin.php'.$url.'&ibase='.$ibase_sauv.'&iaction=chg&ipag2=&irow='.$nb_ligne.'">';
							echo "</HEAD>";
							echo "<BODY>";
							echo '<br><a href= admin.php?ibase='.$_REQUEST['ibase'].'&iaction=chg&char='.$_REQUEST['char'].'&ipag2=&irow='.$nb_ligne.'>'.$got_lang['Conti'].'</a>';
							return;
						}

							//affiche la barre de progression tous les 25 niveaux 0
						if ($i_niv0 % 25 == 0)
						{	print "\n<script type=\"text/javascript\">afficher_progress($nb_ligne);</script>\n";
							flush();
						}

						if (@mb_strpos($ligne, ' INDI') != NULL or @mb_strpos($ligne, 'INDI'.chr(13)) != NULL)	{preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$id_indi);$id_indi = $id_indi[0][0];} else {$id_indi = "";}
						if (@mb_strpos($ligne, ' NOTE') != NULL or @mb_strpos($ligne, 'NOTE'.chr(13)) != NULL)	{$cpm = preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$id_note); if ($cpm > 0) {$id_note = $id_note[0][0];} else {$id_note = "";}}
						if (@mb_strpos($ligne, ' SOUR') != NULL or @mb_strpos($ligne, 'SOUR'.chr(13)) != NULL)	{$cpm = preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$id_sour); if ($cpm > 0) {$id_sour = $id_sour[0][0];} else {$id_sour = "";}}
						if (@mb_strpos($ligne, ' FAM')  != NULL or @mb_strpos($ligne, 'FAM'.chr(13))  != NULL)	{preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$id_fam); $id_fam = $id_fam[0][0];} else {$id_fam = "";}
						if (@mb_strpos($ligne, ' OBJE') != NULL or @mb_strpos($ligne, 'OBJE'.chr(13)) != NULL)	{preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$id_obje); $id_obje = $id_obje[0][0];} else {$id_obje = "";}
						if (@mb_strpos($ligne, ' HEAD') != NULL or @mb_strpos($ligne, 'HEAD'.chr(13)) != NULL)	{$head = TRUE;}
					}


					else if (@mb_substr($ligne,0,1) == "1")	// on vient de lire un evenement niv 1 dans un niv 0 (even dans indi,source,fam,note)
					{	if ($id_indi) {$id_indi2 = $id_indi + $indi_ajus;}
						if ($id_source) {$id_source2 = $id_source + $sour_ajus;}
						if ($pere) {$pere2 = $pere + $indi_ajus;}
						if ($mere) {$mere2 = $mere + $indi_ajus;}
						if (@mb_substr($niveau1_encours,0,3) == 'MAR') {$maria_encours = 'MAR';}
//echo $id_indi2.'/'.$pere2.'/'.$mere2.'/'.$id_source2.'<br>';
//echo '<br>maria_encours : '.$maria_encours;

						maj_evenement($id_indi2,$pere2,$mere2,$id_source2,"1");
						$id_source2 = NULL;$id_source = NULL;
						$cpt_asso = -1;
					}


									//	DEBUT ALIMENTATION DES LIGNES COURANTES

					if (@mb_substr($ligne,0,6) == '1 NAME')	{$nomprenom	= nomprenom (sdg(trim(@mb_substr($ligne,7,2041))));
																$prenoms	= tdp ($nomprenom[1]);
																$nom 		= strtoupper(trim($nomprenom[2]));}

					else if (@mb_substr($ligne,0,6) == '2 DATE')
					{	$ligne = mb_ereg_replace('@#DJULIAN@ ','',$ligne);
						$date_evene = strtoupper(trim(sdg(mb_substr($ligne,6,32))));
					}

					else if (@mb_substr($ligne,0,6) == '2 PLAC') 	{$lieu_evene = tr_lieu(sdg(trim(@mb_substr($ligne,7,248))));	}

					else if (@mb_substr($ligne,0,5) == '1 SEX')	{$sexe = @mb_substr($ligne,6,1);	}

					else if (@mb_substr($ligne,0,6) == '2 SURN' or @mb_substr($ligne,0,6) == '2 GIVN')	//perf
					{}

					else if (@mb_substr($ligne,0,6) == '1 OBJE' and $id_indi != '')  
					{	$cpm = preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$id_obje); if ($cpm > 0)	{$id_obje = $id_obje[0][0];} else {$id_obje = NULL;}
						if ($id_obje != NULL)	
						{	$query = 'INSERT into got_'.$ADDRC.'_obje VALUES ("'.$id_obje.'","'.$id_indi2.'","INDI")';
							sql_exec($query,0);
						}
					}

					else if (@mb_substr($ligne,0,6) == '1 OBJE' and $id_fam != '')  
					{	$cpm = preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$id_obje); if ($cpm > 0)	{$id_obje = $id_obje[0][0];} else {$id_obje = NULL;}
						if ($id_obje != NULL)	
						{	$query = 'INSERT into got_'.$ADDRC.'_obje VALUES ("'.$id_obje.'","'.$id_fam.'","FAM")';
							sql_exec($query,0);
						}
					}

					else if (@mb_substr($ligne,0,6) == '1 NOTE' and $id_indi != '')
					{	if (@mb_strpos($ligne, '@'))		// reference un note niveau 0
						{	$cpm = preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$notes); if ($cpm > 0)	{$notes = $notes[0][0];} else {$notes = NULL;}
							if ($notes != NULL)	
							{	$query = 'INSERT into got_'.$ADDRC.'_note VALUES ("'.$notes.'","'.$id_indi2.'")';
								sql_exec($query,0);
							}
						}
						else 							// texte note individu direct
						{	if ($id_indi != '')						{$note_indi = $note_indi.' '.sdg(trim(@mb_substr($ligne,7,2041)));}
//						if ($id_sour != '')					{$note_sour = $note_sour.chr(13).sdg(trim(@mb_substr($ligne,7,2041)));}
						}
					}
								// les CONx niveau 1 sont forcement des notes de FAM ou de SOURCE. Les notes INDI sont en 2 (normal) ou 4(data)
					else if (@mb_substr($ligne,0,6) == '1 CONC')
					{	if ($id_sour == '')						{$note_indi = $note_indi.sdg(trim(@mb_substr($ligne,7,2041)));	}
						else 									{$note_sour = $note_sour.sdg(trim(@mb_substr($ligne,7,2041)));	}
					}
					else if (@mb_substr($ligne,0,6) == '1 CONT')
					{	if ($id_sour == '')						{$note_indi = $note_indi.' '.sdg(trim(@mb_substr($ligne,7,2041)));	}
						else 									{$note_sour = $note_sour.' '.sdg(trim(@mb_substr($ligne,7,2041)));	}
					}
							// balises pour les SOURCE niveau 0 peut-etre perfectible
					else if (@mb_substr($ligne,0,6) == '1 TITL' 
							or @mb_substr($ligne,0,6) == '1 ABBR'
							or @mb_substr($ligne,0,6) == '1 REPO'  // voir si on peut tester les references @ inutiles
							or @mb_substr($ligne,0,6) == '1 TEXT'){$note_sour = $note_sour.' '.sdg(trim(@mb_substr($ligne,7,2041)));	}

					else if (@mb_substr($ligne,0,6) == '1 OCCU')	{$profession = sdg(trim(@mb_substr($ligne,7,2041)));
																 $note_evene = sdg(trim(@mb_substr($ligne,7,2041)));
																 $niveau1_encours = 'OCCU';}

					else if (@mb_substr($ligne,0,6) == '1 HUSB')	{preg_match_all("([0-9]+)",@mb_substr($ligne,1,20),$pere);$pere = $pere[0][0];}
					else if (@mb_substr($ligne,0,6) == '1 WIFE')	{preg_match_all("([0-9]+)",@mb_substr($ligne,1,20),$mere);$mere = $mere[0][0];}
					else if (@mb_substr($ligne,0,6) == '1 CHIL')	
					{	preg_match_all("([0-9]+)",@mb_substr($ligne,1,20),$temp);
//					$enfants[$i] = $temp[0][0]; 
//					$i = $i + 1;

						$id_enfant =  $temp[0][0] + $indi_ajus;
						if ($pere) {$pere2 = $pere + $indi_ajus;}
						if ($mere) {$mere2 = $mere + $indi_ajus;}

						if ($pere2 == "") {$pere2 = 0;}
						if ($mere2 == "") {$mere2 = 0;}
						$query = 'UPDATE got_'.$_REQUEST['ibase'].'_individu 
							SET `id_pere` = "'.$pere2.'", `id_mere` = "'.$mere2.'" 
							WHERE `id_indi` = '.$id_enfant;
						sql_exec ($query);
					}

					else if (@mb_substr($ligne,0,1) == '1' and mb_substr($ligne,0,5) != '1 FAM' and mb_substr($ligne,0,6) != '1 FILE' )	// stockage niveau1_encours
					{	if (substr($ligne,0,6) == '1 SOUR' and $head) { $_REQUEST['logi'] = sdg(trim(@mb_substr($ligne,7,42))); }
						else 
						{	$niveau1_encours = trim(mb_substr($ligne,2,4));
							$note_evene = sdg(trim(@mb_substr($ligne,7,2041)));
						}
					} 
																			// on stocke les autres niveau 1
					else if (@mb_substr($ligne,0,6) == '2 CORP')
					{	$_REQUEST['edi'] = sdg(trim(@mb_substr($ligne,7,42)));
					}
	
					else if (@mb_substr($ligne,0,6) == '2 SOUR')	  // A AMELIORER Ancestor File et GeneWeb stocke du texte rattache a la balise 1 (leterrier & milliard.ged)
					{	$cpm = preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$id_temp);
						if (	$cpm > 0) 
						{	$id_source = $id_temp[0][0];
						} else 
						{	$id_source = 0;
						}
					}

					else if (@mb_substr($ligne,0,6) == '2 ASSO')	
					{	
						preg_match_all("([0-9]+)",mb_substr($ligne,1,20),$id_asso);
						$cpt_asso++;
						$asso['id'][$cpt_asso] = $id_asso[0][0];
//						$asso['id'][] = $id_asso[0][0];
						$asso['temoi'][$cpt_asso] = "";
						}
					else if (@mb_substr($ligne,0,6) == '3 RELA')	
					{
						$asso['temoi'][$cpt_asso] = sdg(trim(@mb_substr($ligne,7,2041)));
//						$asso['temoi'][] = sdg(trim(@mb_substr($ligne,7,2041)));
					}
	
								// les CONx niveau 2 concernent tous les types de notes
					else if (@mb_substr($ligne,0,6) == '2 CONC')	{$temp = sdg(trim(@mb_substr($ligne,7,2041)));
						$note_evene = $note_evene.$temp;
						if ($id_indi != NULL) {$note_indi = $note_indi.$temp;}
						if ($id_sour != NULL) {$note_sour = $note_sour.$temp;}
					}
					else if (@mb_substr($ligne,0,6) == '2 CONT')	{$temp = sdg(trim(@mb_substr($ligne,7,2041)));
						$note_evene = $note_evene.' '.$temp;
						if ($id_indi != NULL) {$note_indi = $note_indi.' '.$temp;}
						if ($id_sour != NULL) {$note_sour = $note_sour.' '.$temp;}
					}
	
					// recuperation des noms des fichiers. Traiter tout avec des fonctions ISO. Pas reussi oter les backslash en UTF-8.
					else if (( (@substr($ligne,0,6) == '1 FILE' and !$head) or (@substr($ligne,0,6) == '2 FILE') or (@substr($ligne,0,6) == '3 FILE') ))	
					{	
						if (strpos($ligne,'http'))
						{ $fileurl[] = substr($ligne,strpos($ligne,'http'),strlen($ligne) - strpos($ligne,'http') - 2 ) ;
						} else {$fileurl[] = '';}
						$temp = str_replace ('\\','/',trim(substr($ligne,7,248)));
						$text_gauche = substr($temp,0, strrpos ($temp,"."));
						$text_droite = substr($temp,strrpos ($temp,"."), 2048);
						if (strrpos ($text_gauche,"/") != NULL)
						{	$text_gauche = substr($text_gauche,strrpos ($text_gauche,"/")+1, 255);
						}
						$temp = $text_gauche.$text_droite;
						$filename[] = $temp;
					}
					else if (@mb_substr($ligne,0,6) == '2 NOTE')	{$temp = sdg(trim(@mb_substr($ligne,7,2041)));
						$note_evene = $note_evene.' '.$temp;
					}
					else if (@mb_substr($ligne,0,6) == '3 CONT')	{$temp = sdg(trim(@mb_substr($ligne,7,2041)));
						$note_evene = $note_evene.' '.$temp;
					}
					else if (@mb_substr($ligne,0,6) == '3 CONS')	{$temp = sdg(trim(@mb_substr($ligne,7,2041)));
						$note_evene = $note_evene.' '.$temp;
					}
	
					else if (  @mb_substr($ligne,0,6) == '3 PAGE' 
							or @mb_substr($ligne,0,6) == '4 TEXT'
							or @mb_substr($ligne,0,6) == '5 CONT'
							or @mb_substr($ligne,0,6) == '6 CONT'
							)
					{	$note_evene = $note_evene.' '.sdg(trim(@mb_substr($ligne,7,2041)));
					}
	
									//	FIN ALIMENTATION DES LIGNES COURANTES
	
					$nb_ligne = $nb_ligne + 1;		// on avance le compteur de caractere general
				}
				fclose($F);

						/*au cas o 100% ne serait pas affiche */
				print "\n<script type=\"text/javascript\">afficher_progress($nb_ligne);</script>\n";
				flush();

						// completion de la cle even_sour pour permettre la jointure entre fichiers source et evenement source
				$query = "UPDATE got_".$_REQUEST['ibase']."_even_sour a, got_".$_REQUEST['ibase']."_even_sour b
				SET a.id_indi = b.id_indi, a.id_husb = b.id_husb, a.id_wife = b.id_wife, a.type_evene = b.type_evene
				, a.date_evene = b.date_evene, a.lieu_evene = b.lieu_evene, a.dept_evene = b.dept_evene
				WHERE a.id_sour = b.id_sour
				AND a.type_sourc = 'FILE' and b.type_sourc != 'FILE'";
				sql_exec($query,0);


				/*             ALIMENTATION DE LA TABLE DE REFERENCE DES BASES _bases ********************************/

						// gestion du referentiel de la base Club
				if ($_REQUEST['club'] == "Y")
				{	

						// suppression de la base si elle existe
					supprimer_base_club ($ibase_sauv);

					$indi1 = $indi_min + $indi_ajus;
					$indi2 = $indi_max + $indi_ajus;
					if ($sour_min)
					{	$sour1 = intval($sour_min) + intval($sour_ajus);
						$sour2 = intval($sour_max) + intval($sour_ajus);
					} else
					{	$sour1 = 0;
						$sour2 = 0;
					}
					$query = "INSERT INTO g__club VALUES ('".$ibase_sauv."',".$indi1.",".$indi2.",".$sour1.",".$sour2.")";
					sql_exec($query,0);
				}

				/* on stocke le nb d'individu pour la table _bases */
				$query = 'SELECT count(*) FROM `got_'.$_REQUEST['ibase'].'_individu`';
				$result = sql_exec($query);
				$row = mysqli_fetch_row($result);
				$nb_individu = $row[0];
				
				/* on stocke les 7 communes les plus utilisees */
				$query = 'SELECT lieu_naiss,count(*),dept_naiss
					FROM `got_'.$_REQUEST['ibase'].'_individu`
					WHERE lieu_naiss != ""
					GROUP BY lieu_naiss,dept_naiss ORDER BY 2 desc';
				$result = sql_exec($query);
				$i = 0;
				while ($row = mysqli_fetch_row($result))
				{	if ($i < 7) 
					{	if ($row[2] != '') {$temp = '(<b>'.$row[2].'</b>)';} else {$temp = '';}
						$commentaire = $commentaire.' '.$row[0].$temp.',';
					}
					$i = $i + 1;
				}
				$commentaire = mb_substr($commentaire,0,120);
				$commentaire = mb_substr ($commentaire, 0, mb_strrpos($commentaire,',') );
				$nb_lieux = $i;
					// nb de sources
				$query = 'SELECT count(*) FROM `got_'.$_REQUEST['ibase'].'_source`';
				$result = sql_exec($query);
				$row = mysqli_fetch_row($result);
				$nb_sources = $row[0];
	
					// nb de medias		-> rappel : en SQL, le verbe UNION fusionne les doublons
				$query = 'SELECT distinct note_evene
					FROM got_'.$_REQUEST['ibase'].'_evenement
					WHERE type_evene = "FILE"
						AND note_evene != ""
					UNION
					SELECT distinct attr_sourc
					FROM got_'.$_REQUEST['ibase'].'_even_sour
					WHERE type_sourc = "FILE"
						AND attr_sourc != ""
					ORDER BY 1';
				$result = sql_exec($query,0);
				$nb_medias = mysqli_num_rows($result);


					// affichage final pour info
				echo '<br><br><b>SUCCESS </b> : Gedcom file <b>'.$ibase_sauv.'.ged </b>  is successfully upoad in base  <b>'.$_REQUEST['ibase'].'</b><br>';
				echo $nb_individu.' individus<br>';
				echo $nb_lieux.' lieux<br>';
				echo $nb_sources.' sources<br>';
				echo $nb_medias.' medias';
				
				
				// recherche et mise pour du de-cujus 
				
								// on met a jour la table _bases en faisant attention a ne pas ecraser le de-cujus existant*/
				$query = 'SELECT * FROM `g__base` WHERE base = "'.$_REQUEST['ibase'].'"';
				$result = sql_exec($query);
				$row = mysqli_fetch_row($result);
				if ($row[0] != NULL)
				{	$query = 'UPDATE g__base 
						SET volume = '.$nb_individu.'
						, commentaire = "'.$commentaire.'"
						, source = "'.$nb_sources.'"
						, media = "'.$nb_medias.'"
						, version = "'.$got_lang['Relea'].'"
						, logiciel = "'.$_REQUEST['logi'].'"
						, editeur = "'.$_REQUEST['edi'].'"
						WHERE base = "'.$_REQUEST['ibase'].'"';
					sql_exec($query);
					$cujus = $row[1];
				} else
				{	//recherche du meilleur de-cujus
					$query = 'SELECT id_indi,nom FROM `got_'.$_REQUEST['ibase'].'_individu` LIMIT 0,1'; // ajout de la colonne nom, sinon la PK remonte toujours 1
					$result = sql_exec($query);
					$row = mysqli_fetch_row($result);
					$cujus = $row[0];

					if (mb_substr($_SERVER['HTTP_ACCEPT_LANGUAGE'],0,2) == 'fr')
					{	$lang = 'fr'; $forma = 'A4';
					} else
					{	$lang = 'en'; $forma = 'letter';
					}
					$query = 'INSERT INTO g__base VALUES ("'.$_REQUEST['ibase'].'"
						,"'.$row[0].'"
						,"'.$nb_individu.'"
						,"'.$commentaire.'"
						,"'.$nb_sources.'"
						,"'.$nb_medias.'"
						,"'.$got_lang['Relea'].'"
						,"'.$lang.'"
						,"wikipedia"
						,"15"
						,"200"
						,"'.$forma.'"';
						if (!Serveurlocal()) {$query = $query.',"Yes"';} else {$query = $query.',"No"';}
						$query = $query
						.',"N"
						,"'.$_REQUEST['logi'].'"
						,"'.$_REQUEST['edi'].'"
						)';
					sql_exec($query);

					$query = 'SELECT prenom1,prenom2,prenom3,nom,date_naiss FROM `got_'.$_REQUEST['ibase'].'_individu` WHERE id_indi = '.$row[0];
					$result = sql_exec($query);
					$row = mysqli_fetch_row($result);
					echo '<br><br>Best de-cujus finded : <b>'.$row[0].' '.$row[1].' '.$row[2].' '.$row[3].' '.$row[4].'</b>';
				}

//$temp = time() - $T_OLD;
//echo '<br>'.$temp.' secondes';

				maj_cujus ($_REQUEST['ibase'],$cujus);			// mise a jour des sosa_dyn
				echo '<br><br><a class=menu_td href=admin.php?ibase&ipag2&club=N>OK</a>';	// mise a jour des sosa_dyn

				$query = "DROP TABLE got_".$ADDRC."_note" ;
				sql_exec($query);

				$query = "DROP TABLE got_".$ADDRC."_obje" ;
				sql_exec($query);

			}
		}
		@fclose($F);
	}

//	else if ($_REQUEST['ibase'] != '' and $_REQUEST['iaction'] == "cuj")
//	{	maj_cujus ($_REQUEST['ibase'],$_REQUEST['id']);			// mise a jour des sosa_dyn
//			print "<script language=\"JavaScript\" type=\"text/javascript\">\nwindow.location = 'admin.php?ibase&ipag2&club=N';\n</script>\n"; //recharge la page
//	}

					//**************************** Question suppression d'une base *******************************
	else if ($_REQUEST['ibase'] != '' and $_REQUEST['iaction'] == "del" and $_REQUEST['fconfirm'] == NULL)
	{	
		echo "<br><br>";
		echo '<form method=post>';
		echo 'Do you really want to delete the base '.$_REQUEST["ibase"].' ? ';
		afficher_radio_bouton ("fconfirm",array('Yes','No'),array("OK","KO"),"OK","YES");  // par definition, une boite de confirmation n'est pas submitte
		echo '</form>';
	}
					//**************************** Suppression d'une base *******************************
	else if ($_REQUEST['ibase'] != '' and $_REQUEST['iaction'] == "del" and $_REQUEST['fconfirm'] == "OK")
	{	if ($_REQUEST['club'] !== "Y")
		{	$query = 'DROP TABLE got_'.$_REQUEST['ibase'].'_individu';
			sql_exec($query,2);
			$query = 'DROP TABLE got_'.$_REQUEST['ibase'].'_source';
			sql_exec($query,2);
			$query = 'DROP TABLE got_'.$_REQUEST['ibase'].'_evenement';
			sql_exec($query,2);
			$query = 'DROP TABLE got_'.$_REQUEST['ibase'].'_even_sour';
			sql_exec($query,2);
			$query = 'DELETE FROM `g__base` WHERE base = "'.$_REQUEST['ibase'].'"';
			sql_exec($query);
			if ($_REQUEST['ibase'] == '$club')
			{	$query = 'TRUNCATE TABLE g__club';
				sql_exec($query,0);
			}
		} else
		{ supprimer_base_club($_REQUEST['ibase']);
		}

		echo '<br>'.$_REQUEST['ibase'].' : Base is deleted';
		echo '<br><br><a class=menu_td href=admin.php>Return</a>';
		echo '<br>';
	}
/*********************************** GESTION DES BASES GEOGRAPHIQUES *****************************************/
} elseif ($_REQUEST['ipag2'] == "geo")
{	$F = @fopen("geo/geo.dat","r");		// si le fichier n'existe pas, c'est normal, choix d'installation de base
	if ($F == FALSE)
	{	echo '<br>'."Geographic option is not installed. <br>Install the \"full\" file.";
	} elseif ($_REQUEST['ipays'] == NULL)
	{
		$query = "CREATE TABLE g__geodept (
		  code_pays		varchar(3) NOT NULL,
		  code_dept		varchar(3) NOT NULL,
		  longitude_g	float default NULL,
		  longitude_d	float default NULL,
		  largeur_jpg	int,
		  latitude_h	float default NULL,
		  latitude_b	float default NULL,
		  hauteur_jpg	int,
		  lib_dept		varchar(32),
		  PRIMARY KEY (code_dept,code_pays)
		  ) ".$collate;
		sql_exec($query,2);
	
		$query = "CREATE TABLE g__geolieu (
		  commune varchar(50) not NULL,
		  dept char(7) not NULL,
		  pays char(3) not NULL,
		  latitude float,
		  longitude float,
		  PRIMARY KEY COMMUNE_PK (commune,dept)
		  ) ".$collate;
		sql_exec($query,2);

		$code_pays = array ('FR','US');
		$lib_pays  = array ('France','United States');

			// detection chargement des bases geo. Si pas chargées, on présente l'onglet Carto a l'ouverture d'admin.
		$query = 'SELECT code_pays FROM g__geodept LIMIT 0,1';
		$result = sql_exec($query,2);		// les bases ne sont pas forcement encore chargees
		$row = @mysqli_fetch_row($result);

	
		if (!isset($row[0]) ) 
		{
				echo '<p class=defilement style=background:white;>The <b>US</b> topographic map <b>is not loaded</b>. Discover the beautiful GeneoTree maps !<br>Clic on <b>Upload</b> Wait about one minute.</p>';
		}
		echo '<br><table><tr>';		// tableau principal

		echo '<td>';

			echo '<table class="bord_haut bord_bas">';

			echo '<tr class=ligne_tr2>';
			echo '<td colspan=2 class="titre bords_verti bord_bas"><b>Geo bases</b></td>';
			echo '</tr>';

			for ($ii = 0;$ii < count($code_pays);$ii++)
			{	$query = 'SELECT distinct code_pays FROM g__geodept WHERE code_pays = "'.$code_pays[$ii].'"';
				$result = sql_exec($query,2);		// les bases ne sont pas forcement encore chargees
				$row = mysqli_fetch_row($result);
			if ($ii % 2 == 0)	{echo '<tr class=ligne_tr1>';} else {echo '<tr class=ligne_tr2>';} 
				echo '<td class=bords_verti>&nbsp;'.$lib_pays[$ii].'</td>';
				if ($row[0] == NULL)
				{	echo '<td class=bords_verti><a href=admin.php?ipag2=geo&ipays='.$code_pays[$ii].'&icomm=ZZZ&commu&pays&depar&latit&longi&valid_geo>&nbsp;Upload</a></td>';}
				else
				{	echo '<td class=bords_verti align=center><b>OK</b></td>';}
				echo '</tr>';
			}
			echo '</table>';

		echo '</td>';
		echo '<td width=20px></td><td>';


		if (!isset($_REQUEST['icomm']))	{$_REQUEST['icomm'] = "ZZZZZZ";}
		$avant = array ("'","St ","st ","ST ","Ste ","ste ","STE ");
		$apres = array (" ","saint ","saint ","saint ","sainte ","sainte ","sainte ");
		
		$query = 'SELECT commune,latitude, longitude, dept,pays
				FROM g__geolieu
				WHERE commune like "'.str_replace($avant,$apres,$_REQUEST['icomm']).'%"
				ORDER BY pays,dept,commune';
		$result = sql_exec($query,0);

		echo '<p class="titre bords_verti bord_bas bord_haut">Verif a topographic point</p>';

		echo "<form name=verif_topo method=post>";
		echo "	<input type=text name=icomm size=30 value=".$_REQUEST['icomm'].">";
		echo "	<input type=submit value=Search>";
		echo "</form>";

		echo '<table class="bord_haut bord_bas">';
		echo '<tr class=ligne_tr2>';
		echo '<td class=bords_verti><b>&nbsp;'.$got_tag['CTRY'].'</b></td>';
		echo '<td class=bords_verti><b>&nbsp;Departments</b></td>';
		echo '<td class=bords_verti><b>Places</b></td>';
		echo '<td class=bords_verti><b>&nbsp;Latitude</b></td>';
		echo '<td class=bords_verti><b>&nbsp;Longitude</b></td>';
		echo '</tr>';
		$ii = 0;
		while ($row = mysqli_fetch_row($result))
		{	if ($ii % 2 == 0)	{echo '<tr class=ligne_tr2>';} else {echo '<tr class=ligne_tr1>';}
			echo '<td class=bords_verti align=center>'.$row[4].'</td>';
			echo '<td class=bords_verti align=center>'.$row[3].'</td>';
			echo '<td class=bords_verti><a href=admin.php'.$url.'ipag2=geo&icomm='.str_replace(' ','%20',$_REQUEST['icomm']).'&pays='.trim($row[4]).'&depar='.$row[3].'&commu='.str_replace(' ','%20',$row[0]).'&latit='.$row[1].'&longi='.$row[2].'&ipays&valid_geo>'.$row[0].'</a></td>';
			echo '<td class=bords_verti align=center>'.$row[1].'</td>';
			echo '<td class=bords_verti align=center>'.$row[2].'</td>';
			echo '</tr>';
			$ii++;
		}
		echo '</table>';


			echo '</td>';
		echo '<td width=20px></td><td>';


		$query = 'SELECT commune,latitude, longitude, dept,pays
				FROM g__geolieu
				WHERE commune = "'.$_REQUEST['icomm'].'"';
		$result = sql_exec($query,0);
		$row = mysqli_fetch_row($result);

		echo '<p class="titre bords_verti bord_bas bord_haut">Update a topographic point</p>';

		echo '<table class="bord_haut bord_bas bords_verti">';
		echo "<form name=saisie_topo method=post>";
		echo '<tr><td>Places</td><td><input type=text name=commu size=30 value="'.$_REQUEST['commu'].'"></td></tr>';
		echo '<tr><td>'.$got_tag['CTRY'].'</td><td><input type=text name=pays size=5 value="'.$_REQUEST['pays'].'"></td></tr>';
		echo '<tr><td>Departments</td><td><input type=text name=depar size=5 value="'.$_REQUEST['depar'].'"></td></tr>';
		echo '<tr><td>Latitude</td><td><input type=text name=latit size=20 value="'.$_REQUEST['latit'].'"></td></tr>';
		echo '<tr><td>Longitude</td><td><input type=text name=longi size=20 value="'.$_REQUEST['longi'].'"></td></tr>';
		echo '<tr><td align=center colspan=2><input type=submit name=valid_geo value=OK></td></tr>';
		echo '</form>';
		echo '</table>';


			echo '</td>';
			echo '</tr></table>';

			// positionnement du focus sur le formulaire
		echo '<script language="JavaScript" type="text/javascript">
		document.verif_topo.icomm.focus();
		</script>';		

		if ($_REQUEST["valid_geo"] == "OK")		// i.e une saisie a ete effectuee par l'administrateur
		{	$query = 'SELECT commune,dept,pays,latitude,longitude
					FROM g__geolieu
					WHERE commune = "'.$_REQUEST['commu'].'" AND dept = "'.$_REQUEST['depar'].'"';
			$result = sql_exec($query,0);
			$row = mysqli_fetch_row($result);

			$communes_perso = @file_get_contents ("geo/communes_perso.dat");
			$cle = $_REQUEST['commu'].";".$_REQUEST['depar'].";".trim($_REQUEST['pays']);
			$position = strpos($communes_perso, $cle);

			if ($row[0] !== NULL)
			{	$query = 'UPDATE g__geolieu SET 
					latitude = "'.$_REQUEST['latit'].'"
					,longitude = "'.$_REQUEST['longi'].'"
					WHERE commune = "'.$_REQUEST['commu'].'" AND dept = "'.$_REQUEST['depar'].'"';
				sql_exec($query);
				if ($position !== FALSE)	
				{	maj_fichier_perso($position);
				}
			} else
			{	$query = 'INSERT INTO g__geolieu VALUES ("'.$_REQUEST['commu'].'","'.$_REQUEST['depar'].'","'.$_REQUEST['pays'].'","'.$_REQUEST['latit'].'","'.$_REQUEST['longi'].'")';
				sql_exec($query);
				maj_fichier_perso($position);
			}
		}
	} else		// i.e chargement de la base $_REQUEST['ipays']
	{
		while ($ligne = fgets ($F, 255)) 
		{	$champs = explode (';',$ligne);
			if ($champs[0] == $_REQUEST['ipays'])
			{	$query = 'INSERT into g__geodept VALUES ("'.$champs[0].'","'.$champs[1].'","'.$champs[2].'","'.$champs[3].'","'.$champs[4].'","'.$champs[5].'","'.$champs[6].'","'.$champs[7].'","'.$champs[8].'")';
				sql_exec($query,0);
			}
		}
		if ($_REQUEST['irow'] == NULL)	{$_REQUEST['irow'] = 0;}		// on initialise le debut du chargement
	
		$timeout = recup_timeout();		// voir fonction dans _sql.inc.php. On considere que l'include appellerans la page appelante
		echo '<br>Currently upload geographic database .<b>'.$_REQUEST['ipays'].'</b><br>';
	
		$T_OLD = time();
		$F = fopen("geo/communes.dat","r");
	
		$nb_lignes_total = 0;
		while ($ligne = fgets ($F, 255)) 				// une premiere boucle pour compter les lignes qui co r cher (plusieurs secondes)
		{	$champs = explode (';',$ligne);
			if ($champs[2] == trim($_REQUEST['ipays'])) {$nb_lignes_total++;}
		}	
		rewind($F);								// remet le pointeur fichier au debut
		progression($nb_lignes_total);			// charge les scripts et affiche une premiere fois la barre
		flush();
	
		$i = 0;
		while ($i <= $_REQUEST['irow']) {$ligne = fgets ($F, 255); $i = $i +1;}
		$_REQUEST['irow'] = NULL;
		while ($ligne = fgets ($F, 255)) 
		{	$champs = explode (';',$ligne);
			if ($champs[3] != NULL and $champs[4] != NULL and trim($champs[2]) == trim($_REQUEST['ipays']))	// coordonnees non nulles pour un pays
			{	$query = 'INSERT into g__geolieu VALUES ("'.substr(trl($champs[0]),0,50).'","'.$champs[1].'","'.$champs[2].'","'.$champs[3].'","'.trim($champs[4]).'")';
				sql_exec($query,0);
				$_REQUEST['icont']++;
								/* affiche la barre de progression tous les 1000 rows */
				if ($_REQUEST['icont'] % 1000 == 0)
				{	print "\n<script type=\"text/javascript\">afficher_progress($_REQUEST[icont]);</script>\n";
					flush();
				}
	
				if ($timeout[0] == 'KO' and time() - $T_OLD >= $timeout[1] - 2)	// au cas ou le serveur est TRES charg  on se met 2 secondes de marge
				{	echo '<a href= admin.php?ipays='.$_REQUEST['ipays'].'&irow='.$i.'&icont='.$_REQUEST['icont'].'&ipag2='.$_REQUEST['ipag2'].'>Click to continue.</a>';
					return "KO";
				}
			}
			$i++;
		}
		echo '<p>Base <b>'.$_REQUEST['ipays'].'</b> is now uploaded.</p>';
//		echo '<p><a href= admin.php?ipag2=geo&ipays&icomm=ZZZZ&commu&pays&depar&latit&longi&valid_geo>OK</a></p>';
		echo '<br><p><a class=menu_td href= admin.php'.$url.'&ipays>OK</a></p>';
	}
/************************************* GESTION DES DEFAUTS *********************************************************/
} elseif ($_REQUEST['ipag2'] == "def")
{	
	$query = "SELECT * FROM g__base ORDER BY 1"; 
	$result = sql_exec($query);

	if (mysqli_errno($pool) == 0)
	{	if ($_REQUEST['valid'] == "OK")
		{	while($row = mysqli_fetch_row($result))
			{	$query = 'UPDATE g__base
					SET sosa_principal= "'.$_REQUEST[$row[0]."_cujus"].'"
					,defa_langu = "'.$_REQUEST[$row[0]."_langu"].'"
					,defa_theme = "'.$_REQUEST[$row[0]."_theme"].'"
					,defa_forma = "'.$_REQUEST[$row[0]."_forma"].'"
					,defa_centa = "'.$_REQUEST[$row[0]."_centa"].'"
					,defa_palma = "'.$_REQUEST[$row[0]."_palma"].'"
					,defa_inter = "'.$_REQUEST[$row[0]."_inter"].'"
					WHERE base = "'.$row[0].'"
					';
				sql_exec($query,0);
			}
			print "<script language=\"JavaScript\" type=\"text/javascript\">\nwindow.location = 'admin.php?ipag2=def&valid';\n</script>\n"; //recharge la page
		}

		function radio_bouton($nom_liste, $cont_lib, $cont_code, $select) // sans les classes css qui ne fonctionne pas ?!?!?
		{	for ($ii = 0; $ii < count($cont_code); $ii++) 
			{	echo '<input type=radio name='.$nom_liste.' value='.$cont_code[$ii].' id='.$nom_liste.$ii;
				if ($cont_code[$ii] == $select) {echo ' checked="checked"'; }
				echo '>';
				echo '<LABEL for="'.$nom_liste.$ii.'">'.$cont_lib[$ii].'</LABEL>';
			}
		}	

		$list_langu = array("en","fr","hu");
		$list_forma = array("A4","Letter");
		$list_centa = array("Yes","No");
		$list_theme = recup_liste_css();
		$list_palma = array(15,30,50,75,100);
		$list_inter = array(250,200,150,100,50);

		echo '<form method=post>';
		echo '<br><table class="bord_haut bord_bas">';
		echo '<tr class="ligne_tr2">';
		echo '<td class="titre bords_verti bord_bas"><b>Bases</b></td>';
		echo '<td class="titre bords_verti bord_bas"><b>Cujus</b></td>';
		echo '<td class="titre bords_verti bord_bas"><b>Lang.</b></td>';
		echo '<td class="titre bords_verti bord_bas"><b>Format</b></td>';
		echo '<td class="titre bords_verti bord_bas"><b>'.$got_lang['Centa'].'</b></td>';
		echo '<td class="titre bords_verti bord_bas"><b>Themes</b></td>';
		echo '<td class="titre bords_verti bord_bas"><b>'.$got_lang['LisPa'].'</b></td>';
		echo '<td class="titre bords_verti bord_bas"><b>'.$got_lang['Inter'].'</b></td>';
		echo '</tr>';	

		$i = 0;
		while($row = mysqli_fetch_row($result))
		{	//if ($i % 2 == 0) {echo '<tr class="ligne_tr1">';} else {echo '<tr class="ligne_tr2">';}
			echo "<td class=bords_verti>".$row[0]."</td>";
			echo "<td class=bords_verti align=center>";
			echo '<input type=text size=2 name='.$row[0].'_cujus value='.$row[1].'>';
			echo "</td>";
			echo "<td class=bords_verti align=center>";
			radio_bouton($row[0].'_langu',$list_langu,$list_langu,$row[7]);
			echo "</td>";
			echo "<td>";
			radio_bouton($row[0].'_forma',$list_forma,$list_forma,$row[11]);
			echo "</td>";
			echo "<td class=bords_verti align=center>";
			radio_bouton($row[0].'_centa',$list_centa,$list_centa,$row[12]);
			echo "</td>";
			echo "<td class=bords_verti align=center>";
			afficher_liste_deroulante($row[0].'_theme',$list_theme,$row[8],"NO");
			echo "</td>";
			echo "<td class=bords_verti align=center>";
			afficher_liste_deroulante($row[0].'_palma',$list_palma,$row[9],"NO");
			echo "</td>";
			echo "<td class=bords_verti align=center>";
			afficher_liste_deroulante($row[0].'_inter',$list_inter,$row[10],"NO");
			echo "</td>";
			echo "</tr>";
			$i = $i + 1;
		}
		echo "</table>";
		echo '<input type="submit" name=valid value="OK">';
		echo '</form>';
	}

/************************************* SUPER COUSINS *********************************************************/
} elseif ($_REQUEST['ipag2'] == "spc")
{	$query = 'SELECT base,sosa_principal,commentaire FROM g__base WHERE base != "$club" ORDER BY 1';
	$result = sql_exec($query,2);
	if (mysqli_errno($pool) == 0)
	{	echo '<table class="bord_haut bord_bas">';
	
		echo '<tr class=ligne_tr2>';
		echo '<td align=center class=bords_verti><b>Bases</b></td>';
		echo '<td align=center class=bords_verti><b>Main places</b></td>';
		echo '</tr>';
	
		$ii = 0;
		while ($row = mysqli_fetch_row($result) )
		{	$row_id = recup_identite($row[1],$row[0]);
			if ($ii % 2 == 0)	{echo '<tr class=ligne_tr1>';} else {echo '<tr class=ligne_tr2>';} 
			echo '<td class=bords_verti><a href=compare.php?ibase='.$row[0].'&id='.$row[1].'&theme=wikipedia&lang=en title="'.$got_lang['IBCom'].'"><b>'.$row_id[1].' '.$row_id[0].'</b> (Base '.$row[0].')</font></td>';
			echo '<td class=bords_verti>'.mb_substr($row[2],0,100).'</font></td>';
			echo '</tr>';
			$ii++;
		}
		echo '</table>';
	}
}
?>