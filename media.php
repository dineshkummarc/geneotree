<?php
require_once  ("_sql_requests.php");
require_once  ("_functions.php");

/********************* DEBUT DU SCRIPT ****************************/

include ("menu.php");

$EnteteCol = array("","","","","",$got_tag['NAME'], $got_lang['NomCo'], $got_lang['Evene'], $got_tag["FILE"],"",$got_lang["Larg"],$got_lang["Haut"]);
?>
<script>
SubMenuSql ("get_menu_media");
SubMenuSex ();
SubMenuSosa ();
DivSearch ();
pagination('TabMain','<?php echo json_encode($EnteteCol); ?>','recup_media',sortColumn = 'nom');
</script>

