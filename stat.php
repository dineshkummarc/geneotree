<?php
require_once ("_sql_requests.php");
require_once  ("_functions.php");

/****************************** DEBUT DU SCRIPT *****************************************/

include ("menu.php");
?>
<script>
DivIcons ("DivIcon1", "themes/icon-print.png", "stat_pdf.php" + "?" + HrefBase + "&exp=pdf");
dataJson = `[{"Code":"lon", "Nb":0},{"Code":"marA", "Nb":0},{"Code":"marD", "Nb":0},{"Code":"par", "Nb":0},{"Code":"fam", "Nb":0},{"Code":"jum", "Nb":0},{"Code":"nom", "Nb":0},{"Code":"lieu_evene", "Nb":0},{"Code":"dept_evene", "Nb":0},{"Code":"region_evene", "Nb":0},{"Code":"country_evene", "Nb":0},{"Code":"prenom1", "Nb":0}]`;
SubMenuJson(dataJson);
SubMenuSex ();
SubMenuSosa ();
DivSearch();
window.onload = initializeSlider(); // call pagination via date slider
</script>
