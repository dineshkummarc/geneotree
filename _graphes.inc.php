<?php

function bar_horiz($resx,$resy,$titre1,$titre2,$nomimage,$nb_ligne,$pdf = NULL, $orientation = NULL)
{		// resx contient les libelles, resy les nombres

	require_once ("_caracteres.inc.php");
	$flag_teinte ="";
	$flag_theme = "";
	$theme = "";
	$teinte = "";
	if (count($resx) < $nb_ligne) {$nb_ligne = count($resx);}
	
				//recuperation des teintes des couleurs
	if ($pdf == NULL)
	{	$F = fopen("themes/".$_REQUEST['theme'].".css","r");
		while ($ligne = fgets ($F, 2048))
		{	if ($flag_teinte == 'OK')		{$teinte = substr(trim($ligne),-8);$flag_teinte = 'KO';}
			if ($flag_theme == 'OK')		{$theme = substr(trim($ligne),-8);$flag_theme = 'KO';}
			if (trim($ligne) == '.cell_indiv') {$flag_teinte = 'OK';}
			if (trim($ligne) == 'BODY') {$flag_theme = 'OK';}
		}
		fclose ($F);
		$theme = substr($theme,0,7);
		$teinte = substr($teinte,0,7);
	} else
	{	$theme = "#B48C78";			// marron clair en rapport avec la couleur de la bordure des pdf
		$teinte = "#FFFFFF";		// blanc pour le fond des graphes pdf
	}

	$r = hexdec(substr($teinte,1,2));
	$g = hexdec(substr($teinte,3,2));
	$b = hexdec(substr($teinte,5,2));

				// calcul de left (largeur des libelles) et alimentation de $datax et y (contenu du graphe filtré)
	if ($pdf == "YES" and $nb_ligne > 50)	{$nb_ligne = 50;}	// restriction a 50 lignes maxi, sinon graphe plus grand qu'une page A4

	$left = 0;
	for ($ii = 0; isset($resx) and $ii < $nb_ligne; $ii++)
	{	$larg = largeur_cellule($resx[$ii],'');
		if ($larg > $left)
		{	$left = $larg;
		}
		$datax[] = $resx[$ii];
		$datay[] = $resy[$ii];
	}
	$left = $left * 5.2;

	echo '<table><tr><td width = 40px><td><br>';
	echo '<div> <canvas id="canvas" height=500 width=900px></canvas> </div>';
	echo '<script>';
  echo '	var barChartData = 	{	labels : [';
	for ($ii = 0; $ii < count($resx) and $ii < $nb_ligne; $ii++)
	{	
		if ($ii == 0) 
		{	echo '"'.$resx[$ii].'"';
		} else
		{	echo ',"'.$resx[$ii].'"';
		}
	}
  echo '],';
  echo '		datasets : [';
  echo '			{';
  echo '				label: "My First dataset",';
  echo '				fillColor : "rgba('.$r.','.$g.','.$b.',1)",';
  echo '				strokeColor : "rgba(220,220,220,0.8)",';
  echo '				highlightFill: "rgba(220,220,220,0.75)",';
  echo '				highlightStroke: "rgba(220,220,220,1)",';
  echo '				data : [';
	for ($ii = 0; $ii < count($resy) and $ii < $nb_ligne; $ii++)
	{	
		if ($ii == 0) 
		{	echo '"'.$resy[$ii].'"';
		} else
		{	echo ',"'.$resy[$ii].'"';
		}
	}
  echo ']';
  echo '			}';
  echo '		]';
  echo '';
  echo '	};';

  echo '	window.onload = function()';
  echo '	{	var ctx = document.getElementById("canvas").getContext("2d");';
  echo '    var chart = new Chart(ctx).Bar(barChartData, {barShowStroke: false, scaleFontSize:15, scaleFontStyle: "bold"});';
	echo '    var IdCanvas= document.getElementById("canvas");';
	echo '    var image = IdCanvas.toDataURL("image/png");';
//	echo '    saveAs(image, "image.png");';
//	echo '    IdCanvas.toBlob(function(blob) {    saveAs(blob, "pretty.png"); });  ';
  echo '	};';

	echo '</script>';

echo '</td><td></td></tr></table>';
}
?>