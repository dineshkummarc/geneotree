<?php
require_once ("_functions.php");
require_once ("languages/en.php");
include ("config.php");

/**************************************** DEBUT DU SCRIPT **********************************************/

if ($flag_excel == '')
{    $flag_excel = 'Yes';
}

if (!isset($_POST['valid'])) {$_POST['valid'] = "";}
if (!isset($_POST['frie']))  {$_POST['frie']  = "";}
if (!isset($_POST['exce']))  {$_POST['exce']  = "Yes";}
if (!isset($_POST['ftps']))  {$_POST['ftps']  = "";}
if (!isset($_POST['ftpu']))  {$_POST['ftpu']  = "";}
if (!isset($_POST['ftpp']))  {$_POST['ftpp']  = "";}

echo '<!DOCTYPE html>
<HTML>
<HEAD>
<META http-equiv="Content-type" content="text/html; charset=utf-8" name="author" content="Damien Poulain">
<TITLE>Geneotree - Installation</TITLE>
<LINK rel=stylesheet href=geneotree.css type="text/css">
</HEAD>

<BODY>';

if (!extension_loaded('mysqli'))
{    echo 'PHP Extension <b>mysqli</b> is not installed. You must install it to run GeneoTree. See <b>php.ini</b> in Apache directory.';
        return;
}

if (!$INSTALLATION_OK)
{
    if ($_POST['valid'] !== 'VALID')
    {
        if (isset($_POST['host'])) {$sql_host    = $_POST['host'];}
        if (isset($_POST['base'])) {$sql_base    = $_POST['base'];}
        if (isset($_POST['user'])) {$sql_user    = $_POST['user'];}
        if (isset($_POST['pref'])) {$sql_pref    = $_POST['pref'];}
        if (isset($_POST['pass'])) {$sql_pass    = $_POST['pass'];}
        if (isset($_POST['admi'])) {$passe_admin = $_POST['admi'];}
        if (isset($_POST['frie'])) {$passe_ami   = $_POST['frie'];}
        if (isset($_POST['exce'])) {$flag_excel  = $_POST['exce'];}
        if (isset($_POST['ftps'])) {$ftp_server  = $_POST['ftps'];}
        if (isset($_POST['ftpu'])) {$ftp_user    = $_POST['ftpu'];}
        if (isset($_POST['ftpp'])) {$ftp_pass    = $_POST['ftpp'];}
        // if (isset($_POST['club'])) {$flag_club   = $_POST['club'];}

// formulaire principal
        echo '
        <FORM method = "post">
        <table>
        <tr>
        <td class=titre colspan=3 align=center><font size=5>GeneoTree Installation</font></td>
        </tr>
        <tr>
        <td align=center colspan=3><br><b>Obligatory parameters<br>&nbsp;</b></td>
        </tr>
        <tr>
        <td><b>MySQL instance name</b></td>
        <td><input type=text name=host value="'.$sql_host.'" size=30></td>
        <td>Must be similar with the name which is declared (localhost, sql.free.fr, etc...)</td>
        </tr>
        <tr>
        <td><b>MySQL database name</b></td>
        <td><input type=text name=base value="'.$sql_base.'" size=30></td>
        <td>Refered to your provider. In local db, usually "GeneoTree"</td>
        </tr>
        <tr>
        <td><b>MySQL user</b></td>
        <td><input type=text name=user value="'.$sql_user.'" size=30></td>
        <td>It must be exist on the server (usually root)</td>
        </tr>
        <tr>
        <td><b>MySQL password</b></td>
        <td><input type=password name=pass value="'.$sql_pass.'" size=30></td>
        <td></td>
        </tr>
        <tr><td colspan=3>&nbsp;</td></tr>
        <tr>
        <td><b>GeneoTree tables prefix</b></td>
        <td><input type=text name=pref value="'.$sql_pref.'" size=30></td>
        <td></td>
        </tr>
        <tr>';
        if (!ServeurLocal())
        {    echo '
            <td><b>GeneoTree administration password</b></td>
            <td><input type=password name=admi value="'.$passe_admin.'" size=30></td>
            <td>Protect the Administration page by a password</td>
            </tr>
            <tr>
            <td colspan=3 align=center><br><b>Optionnal GeneoTree parameters<br>&nbsp;</b></td>
            </tr>
            <tr>
            <td><b>GeneoTree friend password</b></td>
            <td><input type=text name=frie value="'.$passe_ami.'" size=30></td>
            <td>This option protect all Geneotree pages by a password (blank equal no protection)</td>
            </tr>
            <tr style="height:50px;">
            <td><b>Excel exports available</b></td>
            <td>';
            afficher_menu("exce", array ('Yes','No'), array ('Yes','No'));
            echo '
            </td>
            <td>This option authorize Excel exports for all databases</td>
            </tr>
            ';
            if (extension_loaded('ftp')) 
            {    echo '
                <tr>
                <td colspan=3 align=center><br><b>Optionnal FTP parameters</b><br>FTP parameters are useful for administrators to upload gedcom files without FTP Transferts Tools</b><br>&nbsp;</td>
                </tr>
                <tr>
                <td><b>FTP Server</b></td>
                <td><input type=text name=ftps value="'.$ftp_server.'" size=30></td>
                <td>For example ftpperso.free.fr</td>
                </tr>
                <tr>
                <td><b>FTP User</b></td>
                <td><input type=text name=ftpu value="'.$ftp_user.'" size=30></td>
                <td>Your FTP user</td>
                </tr>
                <tr>
                <td><b>FTP Password</b></td>
                <td><input type=password name=ftpp value="'.$ftp_pass.'" size=30></td>
                <td>Your FTP password</td>
                <tr><td>&nbsp;</td></tr>
		        <tr><td colspan=3>Close all FTP applications. GeneoTree will test your connexion</td></tr>';
            }
            echo '
            </tr>
            <tr>';
        }
        echo '
        <tr><td>&nbsp;</td></tr>
        <tr><td></td><td align=center><input type="submit" name="test" value="Test"></td><td></td></tr>
		<tr><td colspan=3>&nbsp;</td></tr><tr>
        <tr><td>&nbsp;</td></tr>';

        if (isset($_POST['test']))
        {    $pool = @mysqli_connect($_POST['host'],$_POST['user'],$_POST['pass']);
            if (!$pool)    // on considere que si le test mysqli est positif, la version MySql est correcte (supérieure à 4.1.1).
            {    echo '<td colspan=3><b>ERROR : Connection error : ('. mysqli_connect_errno().') : '.mysqli_connect_error().' Try again.</b></td></tr>';
                exit;
            }

            @mysqli_select_db($pool,$_POST['base']);
            if (mysqli_errno($pool) !== 0)
            {    $query = "create database ".$_POST['base']." DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
                @mysqli_query($pool,$query);
                if (mysqli_errno($pool) != 0) echo mysqli_errno($pool)." : ".mysqli_error($pool)."<BR>";
            }

            if (!ServeurLocal())
            {    if ($_POST['admi'] == '')
                {    echo '<td colspan=3><b>A geneoTree administration password is required</b></td>';
                } else
                {    if (($_POST['ftps'] or $_POST['ftpu'] or $_POST['ftpp']))
                    {    $conn_id = @ftp_connect($_POST['ftps']);
                        if (!$conn_id)
                        {    echo '<td colspan=3><b>FTP Serveur name is wrong. Try others.</b></td>';
                        } else
                        {    if (!@ftp_login($conn_id,$_POST['ftpu'],$_POST['ftpp']))
                            {    echo '<td colspan=3><b>FTP user/password are wrongs. Try others.</b></td>';
                            } else
                            {    echo '
                                <td colspan=3><b>GeneoTree has been connecting with successfull. You can validate this parameters. Enjoy.</b></td>
                                </tr><tr>
                                <td>&nbsp;</td>
                                </tr><tr>
                                <td></td><td align=center><input type="submit" size=130 name="valid" value="VALID"></td><td></td>';
                            }
                        }
                    } else
                    {    echo '
                        <td colspan=3><b>GeneoTree has been connecting with successfull. You can validate this parameters. Enjoy.</b></td>
                        </tr><tr>
                        <td>&nbsp;</td>
                        </tr><tr>
                        <td></td><td align=center><input type="submit" size=130 name="valid" value="VALID"></td><td></td>';
                    }
                }
            } else
            {    echo '
                <td colspan=3><b>GeneoTree has been connecting with successfull. You can validate this parameters. Enjoy.</b></td>
                </tr><tr>
                <td>&nbsp;</td>
                </tr><tr>
                <td></td><td align=center><input type="submit" size=130 name="valid" value="VALID"></td><td></td>';
            }
            mysqli_close($pool);
        }

        echo '
        </tr>
        </table>
        </FORM>';

		if (!extension_loaded('ftp') AND !ServeurLocal())
		{    echo '<br>WARNING : PHP Extension <b>ftp is not installed on this server</b>. 
			You will have to <b>upload</b> your gedcom files <b>yourself</b> with the ftp file transfer tool in /geneotree/gedcom directory.
			<br>&emsp;&emsp;&emsp;&emsp;&emsp;&ensp;If you have privileges, see php.ini or phpForApache.ini in Apache directory.';
		}

		if (!extension_loaded('mbstring'))
		{   echo '<br>WARNING : PHP Extension <b>mbstring</b> is not installed : unicode characters cannot be displayed. See <b>php.ini</b> in Apache directory.';
			exit;
		}

		if (!extension_loaded('gd'))
		{    echo '<br>WARNING : PHP Extension <b>gd</b> is not installed : thumbnails cannot be created. See <b>php.ini</b> in Apache directory.';
		}

    } else
    {    $F = fopen("config.php","wb");
        fputs($F,"<?php\n\n");
        fputs($F,"\$INSTALLATION_OK = TRUE;\n\n");
        fputs($F,"\$sql_host = '".$_POST['host']."';\n");
        fputs($F,"\$sql_base = '".$_POST['base']."';\n");
        fputs($F,"\$sql_user = '".$_POST['user']."';\n");
        fputs($F,"\$sql_pref = '".$_POST['pref']."';\n");
        fputs($F,"\$sql_pass = '".$_POST['pass']."';\n\n");
        fputs($F,"\$passe_admin = '".$_POST['admi']."';\n");
        fputs($F,"\$passe_ami   = '".$_POST['frie']."';\n");
        fputs($F,"\$flag_excel  = '".$_POST['exce']."';\n");
        fputs($F,"\$flag_club   = '".$_POST['club']."';\n\n");
        fputs($F,"\$ftp_server  = '".$_POST['ftps']."';\n");
        fputs($F,"\$ftp_user    = '".$_POST['ftpu']."';\n");
        fputs($F,"\$ftp_pass    = '".$_POST['ftpp']."';\n\n");
        fputs($F,"?>");
        fclose($F);

        echo '
        <script language="JavaScript" type="text/javascript">
        window.location = "admin.php";
        </script>'; 
    }
}