<?php
include("config.php");

if ($ftp_server)
{	// Mise en place d'une connexion basique
		$conn_id = ftp_connect($ftp_server);
		
	// Identification avec un nom d'utilisateur et un mot de passe
		$login_result = ftp_login($conn_id,$ftp_user ,$ftp_pass);
}

/* variables à modifier */
$taillemax = 104857600; // taille max d'un fichier (multiple de 1024)
$rep = "gedcom/"; // répertoire de destination

// fichier courant (URI absolue) : formulaire récursif
$PHP_SELF = basename($_SERVER['PHP_SELF']);

if($_POST and isset($_FILES['lefichier']) ) 
{	$msg = array(); // message
	$fichier = $_FILES['lefichier']; 

	for($i=0; $i < count($fichier['name']); $i++) 
	{	// nom du fichier original = nom par défaut
		$nom = $fichier['name'][$i];
		// test existence fichier
		if(!mb_strlen($nom)) 
		{	$msg[] = "No file !";
			continue;
		}

		// répertoire de destination
		$destination = $rep.$nom;

		// tests erreurs
		if($fichier['error'][$i]) 
		{	switch($fichier['error'][$i]) 
			{	// dépassement de upload_max_filesize dans php.ini
				case UPLOAD_ERR_INI_SIZE:
				  $msg[] = "File is too big !"; 
				  break;

				// dépassement de MAX_FILE_SIZE dans le formulaire
				case UPLOAD_ERR_FORM_SIZE:
				  $msg[] = "File is too big ! ".$fichier['size'][$i]."(more than  ".(INT)($taillemax/1024)." MB)"; 
				  break;

				// autres erreurs
				default:
				  $msg[] = "No issue to read the file ?".$nom;
			}
		}
		// test taille fichier
		elseif($fichier['size'][$i] > $taillemax)
			$msg[] = "File ".$nom." is too big : ".$fichier['size'][$i];
		else 
		{	if (!Serveurlocal())
			{		// test upload sur serveur (rep. temporaire)
				if (!@is_uploaded_file($fichier['tmp_name'][$i])  )
					$msg[] = $got_lang['UplKO']." ".$nom;
					// test transfert du serveur au répertoire
				elseif(!@move_uploaded_file($fichier['tmp_name'][$i], $destination) )
					$msg[] = "Transfert was failed with ".$nom;
				else
					$msg[] = "<b>".$nom."</b> ".$got_lang['UplOK'];
			} else
			{	if(!@copy ($fichier['tmp_name'][$i], $destination) )
					$msg[] = $got_lang['UplKO']." ".$nom;
				else
					$msg[] = "<b>".$nom."</b> ".$got_lang['UplOK'];
			}
		}
	}
	// affichage confirmation
	for($i=0; $i < count($msg); $i++)
		echo '<p>'.$msg[$i].'</p>';
}

	// 1 fichier par défaut (ou supérieur à $maxfichier)
$upload = (isset($_REQUEST['upload']) && $_REQUEST['upload'] <= $maxfichier) ? $_REQUEST['upload'] : 1;

	// le formulaire
if ( (function_exists('ftp_connect') and function_exists('ftp_login') and $ftp_server) or ServeurLocal() )
{	echo "<FORM action='$PHP_SELF' enctype='multipart/form-data' method='post'>\n";
		// boucle selon nombre de fichiers $upload
	for ($i=1; $i <= $upload; $i++) 
	{	echo "<input type='hidden' name='MAX_FILE_SIZE' value='$taillemax'>";
		echo "<input type='file' name='lefichier[]'  size=80>";
	}
	echo "<br><input type='submit' value='Upload'>";
	echo '</FORM>';
}
?>
