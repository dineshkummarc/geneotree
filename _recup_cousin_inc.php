<?php
function recup_popul ($fid,$nb_generations,$nb_gener_desc,$relation)
{	global $ancetres;
	global $descendants;
	global $cpt_generations;
	global $nb_generations_desc;
	global $cpt_generations_desc;
//echo $fid.$nb_generations.$nb_generations_desc.$relation.'<br>';
	$ancetres = array(); 
	$descendants = '';$cpt_generations = 0;
	$ancetres['id_indi'][0] = $fid;
	recup_ascendance ($ancetres,0,$nb_generations,'ME_G');

	$final['id_indi'][] = "";
	$cpt_final = 0;
//	$i = 0;
	for ($i = 0; $i < count($ancetres['id_indi']); $i++)
//	while ($ancetres['id_indi'][$i] != '') 
	{	if ($ancetres ['generation'][$i] == $nb_generations)			// sélection des ancêtres du niveau demandé
		{	
//echo 'A:'.$ancetres ['nom'][$i].$ancetres ['prenom1'][$i].'<br>';
			$descendants = array();
			$descendants ['id_indi'] [0] = $ancetres['id_indi'][$i];
			$cpt_generations_desc = 0;
			$nb_generations_desc = $nb_gener_desc;
//echo 'Nb desc :'.$nb_generations_desc.'<br>';
			recup_descendance (0,0,$nb_generations_desc,'ME_G','');						// recherche des descendants de ces ancêtres (balèze!)

//			$j = 0;
			for ($j = 0; $j < count($descendants ['id_indi']); $j++)
			{	
//echo $descendants ['nom'][$j].$descendants ['prenom1'][$j].'/'.$descendants ['niveau'][$j].'<br>';
				if ($descendants ['niveau'][$j] == $nb_generations_desc)	// sélection des descendants du niveau demandé
				{	
//echo $descendants ['nom'][$j].$descendants ['prenom1'][$j].'/'.$descendants ['niveau'][$j].'<br>';
					$flag_present = 0;
					$k = 0;
					while ($final['id_indi'][$k] != '' and $flag_present == 0)
					{	if ($descendants ['id_indi'][$j] == $final['id_indi'][$k])	{$flag_present = 1;}
						$k = $k + 1;
					}
					if ($flag_present == 0) 
					{	$final['id_indi'][$cpt_final] = $descendants ['id_indi'][$j];
						$final['nom'][$cpt_final] = $descendants ['nom'][$j];
						$final['prenom1'][$cpt_final] = $descendants ['prenom1'][$j];
						$final['prenom2'][$cpt_final] = $descendants ['prenom2'][$j];
						$final['prenom3'][$cpt_final] = $descendants ['prenom3'][$j];
						$final['sexe'][$cpt_final] = $descendants ['sexe'][$j];
						$final['profession'][$cpt_final] = $descendants ['profession'][$j];
						$final['date_naiss'][$cpt_final] = $descendants ['date_naiss'][$j];
						$final['lieu_naiss'][$cpt_final] = $descendants ['lieu_naiss'][$j];
						$final['dept_naiss'][$cpt_final] = $descendants ['dept_naiss'][$j];
						$final['date_deces'][$cpt_final] = $descendants ['date_deces'][$j];
						$final['lieu_deces'][$cpt_final] = $descendants ['lieu_deces'][$j];
						$final['dept_deces'][$cpt_final] = $descendants ['dept_deces'][$j];
						$final['sosa_dyn'][$cpt_final] = $descendants ['sosa_dyn'][$j];
						$cpt_final = $cpt_final + 1;
					}
				}
			}
		}
//		$i = $i + 1;
	}
	return $final;
}

function recup_cousin ($fid,$nb_generations,$nb_generations_desc,$relation)
{
/*
									PRINCIPE DE L'ALGO
		On calcule toujours une population principale A (correspondant aux critères demandés)
		et une population secondaire B, qui correspond aux individus à exclure de la population principale.
		  	Exemple des cousins gemains
		  		A - lire les grands-parents (2ème génération ascendante) et leurs petits-enfants (2ème génération descendante)
		  		on récupère alors les propres frères & soeurs de l'individu (qui ne sont pas des cousins germains)
		  		il faut les exclure.
				B - lire les parents (1ème génération ascendante) et leurs descendants (1ère génération descendante)
		  		C - retirer la population B à la population A
		 N.B : La population B se calcule de la meme facon que la population en retirant 1 aux nb de générations
*/

				// Population A
	$popul_a = recup_popul ($fid,$nb_generations,$nb_generations_desc,$relation);

				// 	Population B
	$nb_generations = $nb_generations - 1;
	$nb_generations_desc = $nb_generations_desc - 1;
	$popul_b = recup_popul ($fid,$nb_generations,$nb_generations_desc,$relation);

				// Population A moins population B
//	$i = 0;
	$cpt_cousins = 0;
	$final = array();

	for ($i = 0; $i < @count($popul_a['nom']); $i++)
//	while ($popul_a['nom'][$i] != '') 
	{	//$j = 0;
		$flag = 0;
//		while ($popul_b ['nom'][$j] != '' and $flag == 0) 
		for ($j = 0; $j < count($popul_b ['id_indi']); $j++)
		{	if ($popul_b ['id_indi'][$j] == $popul_a['id_indi'][$i]) {$flag = 1;}
//			$j = $j + 1;
		}
		if ($flag == 0)			// si individu non trouve dans B, ca roule
		{	$final['id_indi'][$cpt_cousins] = $popul_a['id_indi'][$i];
			$final['nom'][$cpt_cousins] = $popul_a['nom'][$i];
			$final['prenom1'][$cpt_cousins] = $popul_a['prenom1'][$i];
			$final['prenom2'][$cpt_cousins] = $popul_a['prenom2'][$i];
			$final['prenom3'][$cpt_cousins] = $popul_a['prenom3'][$i];
			$final['sexe'][$cpt_cousins] = $popul_a['sexe'][$i];
			$final['profession'][$cpt_cousins] = $popul_a['profession'][$i];
			$final['date_naiss'][$cpt_cousins] = $popul_a['date_naiss'][$i];
			$final['lieu_naiss'][$cpt_cousins] = $popul_a['lieu_naiss'][$i];
			$final['dept_naiss'][$cpt_cousins] = $popul_a['dept_naiss'][$i];
			$final['date_deces'][$cpt_cousins] = $popul_a['date_deces'][$i];
			$final['lieu_deces'][$cpt_cousins] = $popul_a['lieu_deces'][$i];
			$final['dept_deces'][$cpt_cousins] = $popul_a['dept_deces'][$i];
			$final['sosa_dyn'][$cpt_cousins] = $popul_a['sosa_dyn'][$i];
			$cpt_cousins = $cpt_cousins + 1;
		}
		//$i = $i + 1;
	}
	return $final;
}
?>