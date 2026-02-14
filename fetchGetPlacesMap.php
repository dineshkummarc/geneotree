<?php
require_once  ("config.php");
require_once ("_functions.php");
sql_connect();

// Récupération de toutes les correspondances entre OldPlace et MapPlace
$query = "SELECT Base,Unknown_GoogleMap_Place, Known_GoogleMap_Place FROM ".$sql_pref."__googlemap_places";
$result = mysqli_query($pool, $query);

// Tableau pour stocker les correspondances de villes
$correspondances = array();

if ($result) 
  {
    while ($row = mysqli_fetch_assoc($result)) {
        $correspondances[$row['Unknown_GoogleMap_Place']] = $row['Known_GoogleMap_Place'];
  }
}

// Fermeture de la connexion à la base de données
mysqli_close($pool);

// Encodage du tableau associatif en JSON et envoi au JavaScript
header('Content-Type: application/json');
echo json_encode($correspondances);
?>
