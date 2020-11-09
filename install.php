<?php
require_once ("_sql.inc.php");
require_once ("_boites.inc.php");
include ("config.php");

function version_php_OK()
{	$phpv = explode ('.',phpversion());
			// si version PHP inferieure a 5.3, la connexion mysqli persistent n est pas supportee

	if (($phpv[0] == 5 and $phpv[1] >= 3) or ($phpv[0] == 7))
	{	return TRUE; 
	} else
	{	return FALSE;
	}
}

function version_mysql_OK($pool)
{	global $mysql;

			// si version mysql inferieure a 4.1.1, les collations ne sont pas support褳. La version 3.8 de geneotree est UTF-8, dont MySQL 4.1.1 minimum.
	$query = 'show variables like "version"';
	$result = mysqli_query($pool,$query);
	$row = mysqli_fetch_row($result);
	$mysql = $row[1]; 
	$mysqlv = explode ('.',$mysql); 
	if ( ($mysqlv[0] == 4 and $mysqlv[1] == 1 and $mysqlv[2] >= 1) or ($mysqlv[0] == 4 and $mysqlv[1] >= 2) or $mysqlv[0] >= 5 )
	{	return TRUE;
	} else
	{	return FALSE;
	}
}

/**************************************** DEBUT DU SCRIPT **********************************************/

if ($flag_excel == '')
{	$flag_excel = 'Yes';
}

// if (!isset($_POST['admi'])) {$_POST['admi'] = "";}
// if (!isset($_POST['frie'])) {$_POST['frie'] = "";}
// if (!isset($_POST['exce'])) {$_POST['exce'] = "";}
// if (!isset($_POST['ftps'])) {$_POST['ftps'] = "";}
// if (!isset($_POST['ftpu'])) {$_POST['ftpu'] = "";}
// if (!isset($_POST['ftpp'])) {$_POST['ftpp'] = "";}

echo '<!DOCTYPE html>';
echo "<HTML>";
echo "<HEAD>";
echo '<META http-equiv="Content-type" content="text/html; charset=utf-8" name="author" content="Damien Poulain">'; //l'install est caractere latin car uniquement en anglais
echo "<TITLE>Geneotree - Installation</TITLE>";
echo "<LINK rel='stylesheet' href='themes/wikipedia.css' type='text/css'>";
echo "</HEAD>";
echo "<BODY>";

if (!version_php_OK())
{	echo "Your PHP Version is .".phpversion()."<br>. This release is not supported by GeneoTree. You must have release 5.3 or higher.";
		exit;
}

if (!extension_loaded('mbstring'))
{	echo 'PHP Extension <b>mbstring</b> is not installed. You must install it to run GeneoTree. See <b>php.ini</b> in Apache directory.';
		exit;
}

if (!extension_loaded('mysqli'))
{	echo 'PHP Extension <b>mysqli</b> is not installed. You must install it to run GeneoTree. See <b>php.ini</b> in Apache directory.';
		exit;
}

if ( !version_gd() )
{	echo '<p class=defilement style=background:white;>WARNING : PHP Extension <b>gd2</b> is not installed. Maps and pictures will can displayed. See <b>php.ini</b> in Apache directory.</p>';
}

if (!function_exists('ftp_connect') or !function_exists('ftp_login'))
{	echo '<p class=defilement style=background:white;>WARNING : PHP Extension <b>ftp</b> is not installed. File search button will can displayed. See <b>php.ini or phpForApache.ini</b> in Apache directory.</p>';
}

if (!$INSTALLATION_OK)
{
	if ($_POST['valid'] !== 'VALID')
	{
		if (isset($_POST['host']) ) {$sql_host    = $_POST['host'];}
		if (isset($_POST['base']) ) {$sql_base    = $_POST['base'];}
		if (isset($_POST['user']) ) {$sql_user    = $_POST['user'];}
		if (isset($_POST['pass']) ) {$sql_pass    = $_POST['pass'];}
		if (isset($_POST['admi']) ) {$passe_admin = $_POST['admi'];}
		if (isset($_POST['frie']) ) {$passe_ami   = $_POST['frie'];}
		if (isset($_POST['exce']) ) {$flag_excel  = $_POST['exce'];}
		if (isset($_POST['ftps']) ) {$ftp_server  = $_POST['ftps'];}
		if (isset($_POST['ftpu']) ) {$ftp_user    = $_POST['ftpu'];}
		if (isset($_POST['ftpp']) ) {$ftp_pass    = $_POST['ftpp'];}

		echo '<FORM method = "post" name="param_install">';
		echo'<table><tr>';
		echo '<td class=titre colspan=3><font size=5>GeneoTree Installation</font></td>';
		echo '</tr><tr>';
		echo '<td colspan=3>&nbsp;</td>';
		echo '</tr><tr>';
		echo '<td colspan=3>&nbsp;</td>';
		echo '</tr><tr>';
		echo '<td align=center colspan=3><b><font size=3>Obligatory parameters</b></td>';
		echo '</tr><tr>';
		echo '<td><b>Technical name of MySQL Server</b></td>';
		echo '<td><input type=text name=host value="'.$sql_host.'" size=30></td>';
		echo '<td>Must be similar with the name which is declared (localhost, sql.free.fr, etc...)</td>';
		echo '</tr><tr>';
		echo '<td><b>Name of MySQL database</b></td>';
		echo '<td><input type=text name=base value="'.$sql_base.'" size=30></td>';
		echo '<td>Choose yourself a name without blanks and specials characters</td>';
		echo '</tr><tr>';
		echo '<td><b>MySQL administrator user</b></td>';
		echo '<td><input type=text name=user value="'.$sql_user.'" size=30></td>';
		echo '<td>It must be exist on the server (usually root)</td>';
		echo '</tr><tr>';
		echo '<td><b>MySQL administrator password</b></td>';
		echo '<td><input type=password name=pass value="'.$sql_pass.'" size=30></td>';
		echo '<td></td>';
		echo '</tr><tr>';
		if (!ServeurLocal() )
		{	echo '<td><b>GeneoTree administration password</b></td>';
			echo '<td><input type=password name=admi value="'.$passe_admin.'" size=30></td>';
			echo '<td>It protect the Administration page</td>';
			echo '</tr><tr>';
			echo '<td colspan=3>&nbsp;</td>';
			echo '</tr><tr>';
			echo '<td colspan=3><font size=3>Optionnal GeneoTree parameters</td>';
			echo '</tr><tr>';
			echo '<td><b>GeneoTree friend password</b></td>';
			echo '<td><input type=text name=frie value="'.$passe_ami.'" size=30></td>';
			echo '<td>It protect all Geneotree pages (blank equal no protection)</td>';
			echo '</tr><tr>';
			echo '<td><b>Excel exports available</b></td>';
			echo '<td>';
			afficher_radio_bouton("exce", array ('Yes','No'), array ('Yes','No'), $flag_excel, 'YES');
			echo '</td>';
			echo '<td>default Yes</td>';
			echo '</td></tr>';
			if (function_exists('ftp_connect') and function_exists('ftp_login')) 
			{	echo '<td colspan=3>&nbsp;</td>';
				echo '</tr><tr>';
				echo '<td colspan=3><font size=3>Optionnal FTP parameters</td>';
				echo '</tr><tr>';
				echo '<td colspan=3><font size=3>FTP parameters are useful for administrators to upload gedcom files without FTP Transferts Tools</td>';
				echo '</tr><tr>';
				echo '<td><b>FTP Server</b></td>';
				echo '<td><input type=text name=ftps value="'.$ftp_server.'" size=30></td>';
				echo '<td>For example ftpperso.free.fr</td>';
				echo '</tr><tr>';
				echo '<td><b>FTP User</b></td>';
				echo '<td><input type=text name=ftpu value="'.$ftp_user.'" size=30></td>';
				echo '<td>Your FTP user</td>';
				echo '</tr><tr>';
				echo '<td><b>FTP Password</b></td>';
				echo '<td><input type=password name=ftpp value="'.$ftp_pass.'" size=30></td>';
				echo '<td>Your FTP password</td>';
			}
			echo '</font></tr><tr>';
		}
		echo '<tr>';
		echo '<td colspan=3 align=center><input type="submit" name="test" value="Test"></td>';
		echo '</tr><tr>';
		echo '<td colspan=3>In case of FTP parameters, close all FTP applications. GeneoTree will test your connexion</td>';
		echo '</tr><tr>';
		echo '<td colspan=3>&nbsp;</td>';
		echo '</tr><tr>';

		if (isset($_POST['test']))
		{	$pool = @mysqli_connect($_POST['host'],$_POST['user'],$_POST['pass']);
			if (!$pool) 
			{	echo '<td colspan=3><b>ERROR : Connection error : ('. mysqli_connect_errno().') : '.mysqli_connect_error().' Try again.</b></td></tr>';
				exit;
			}
			if (!version_mysql_OK($pool))
			{	echo '<td colspan=3><b>Your MySQL release is '.$mysql.'. Geneotree ask 4.1.1 minimum. Upgrade your MySQL Server please.</b></td>';
			} else
			{	
				@mysqli_select_db($pool,$_POST['base']);
				if (mysqli_errno($pool) != 0)
				{	$query = "create database ".$_POST['base']." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
					@mysqli_query($pool,$query);
					if (mysqli_errno($pool) != 0) echo mysqli_errno($pool)." : ".mysqli_error($pool)."<BR>";
//					$query = "use ".$sql_base;
//					@mysqli_query($pool,$query);
//					if (mysqli_errno($pool) != 0) echo mysqli_errno($pool)." : ".mysqli_error($pool)."<BR>";
				}

				if (!ServeurLocal())
				{	if ($_POST['admi'] == '')
					{	echo '<td colspan=3><b>A geneoTree administration password is required</b></td>';
					} else
					{	if ( ($_POST['ftps'] or $_POST['ftpu'] or $_POST['ftpp']) )
						{	$conn_id = @ftp_connect($_POST['ftps']);
							if (!$conn_id)
							{	echo '<td colspan=3><b>FTP Serveur name is wrong. Try others.</b></td>';
							} else
							{	if (!@ftp_login($conn_id,$_POST['ftpu'],$_POST['ftpp'])  )
								{	echo '<td colspan=3><b>FTP user/password are wrongs. Try others.</b></td>';
								} else
								{	echo '<td colspan=3><b>GeneoTree has been connecting with successfull. You can validate this parameters. Enjoy.</b></td>';
									echo '</tr><tr>';
									echo '<td>&nbsp;</td>';
									echo '</tr><tr>';
									echo '<td align=center colspan=3><input type="submit" size=130 name="valid" value="VALID"></td>';
								}
							}
						} else
						{	echo '<td colspan=3><b>GeneoTree has been connecting with successfull. You can validate this parameters. Enjoy.</b></td>';
							echo '</tr><tr>';
							echo '<td>&nbsp;</td>';
							echo '</tr><tr>';
							echo '<td align=center colspan=3><input type="submit" size=130 name="valid" value="VALID"></td>';
						}
					}
				} else
				{	echo '<td colspan=3><b>GeneoTree has been connecting with successfull. You can validate this parameters. Enjoy.</b></td>';
					echo '</tr><tr>';
					echo '<td>&nbsp;</td>';
					echo '</tr><tr>';
					echo '<td align=center colspan=3><input type="submit" size=130 name="valid" value="VALID"></td>';
				}
			}
		}

		echo '</tr></table>';
		echo '</FORM>';
	} else
	{	$F = fopen("config.php","wb");
		fputs($F,"<?php\n");
		fputs($F,"\n");
		fputs($F,"/******* PLEASE DON'T MODIFY THE FOLLOWING LINE - NE PAS MODIFIER LA LIGNE CI-DESSOUS SVP ********/\n");
		fputs($F,"\n");
		fputs($F,"\$INSTALLATION_OK = TRUE;\n");
		fputs($F,"\n");
		fputs($F,"/*************************************************************************************************/\n");
		fputs($F,"\n");
		fputs($F,"\n");
		fputs($F,"/****************************** BEGIN INITIALIZATION CONNECTION PARAMETERS ***********************/\n");
		fputs($F,"\n");
		fputs($F,"\$sql_host = '".$_POST['host']."';\n");
		fputs($F,"\$sql_base = '".$_POST['base']."';\n");
		fputs($F,"\$sql_user = '".$_POST['user']."';\n");
		fputs($F,"\$sql_pass = '".$_POST['pass']."';\n");
		fputs($F,"\n");
		fputs($F,"\$passe_admin = '".$_POST['admi']."';\n");
		fputs($F,"\$passe_ami   = '".$_POST['frie']."';\n");
		fputs($F,"\$flag_excel  = '".$_POST['exce']."';\n");
		fputs($F,"\n");
		fputs($F,"\$ftp_server  = '".$_POST['ftps']."';\n");
		fputs($F,"\$ftp_user    = '".$_POST['ftpu']."';\n");
		fputs($F,"\$ftp_pass    = '".$_POST['ftpp']."';\n");
		fputs($F,"\n");
		fputs($F,"/******************************* END INITIALIZATION CONNECTION PARAMETERS ************************/\n");
		fputs($F,"?>\n");
		fclose($F);

		echo '<script language="JavaScript" type="text/javascript">';
		echo "window.location = 'index.php?install=OK';";  // on force install = OK, car le include config.php ne se rafraichit pas à cause du cache.
		echo '</script>'; 
		exit;
	}
}
?>