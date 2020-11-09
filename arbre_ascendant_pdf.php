<?php
require_once ("_sql.inc.php");
require_once ("tfpdf/tfpdf.php");
require_once ("_recup_ascendance.inc.php");
require_once ("_caracteres.inc.php");
require_once ("languages/lang.".$_REQUEST['lang'].".inc.php");	
$pool = sql_connect();
//require_once ('_graphes.inc.php');  // ne fonctionne plus avec la nouvelle librairie chartjs

function position_page ($sosa_d,$generation)	// retourne l'endroit d'un individu dans un arbre 5 generations (32 individus par page)
{ 	$temp = pow ( 2, ( fmod (($generation - 1) , 4 ) ) + 1); // => hyper sioux
	return  $temp + fmod ($sosa_d, $temp);
}

function no_page ($sosa_d,$generation)		// retourne le numero de page theorique d'un sosa dans un arbre 5 generations
{	global $gen_page;

//Gen_page nb pages par generations [1] => 1 [2] => 1 [3] => 1 [4] => 1     [5] => 16 [6] => 16 [7] => 16 [8] => 16      [9] => 256

	$depart_gene = pow(2,$generation);
	$plage = $depart_gene / $gen_page[$generation];
	$pre_final = floor(($sosa_d - $depart_gene) / $plage ) ;
// echo '<br>'.$sosa_d.' / '.$generation.' / '.$depart_gene.' / '.$gen_page[$generation].' / '.$plage.' = '.$pre_final;
	if ($gen_page[$generation] == 1)		{return $pre_final + 1;}	// decalage indice depuis 0 pour aller a 1
	if ($gen_page[$generation] == 16)		{return $pre_final + 2;}	// deca 1 + 1ere page
	if ($gen_page[$generation] == 256)		{return $pre_final + 18;}	// deca 1 + 1ere page + 16 pages
	if ($gen_page[$generation] == 4096)		{return $pre_final + 274;} // deca 1  + 1ere page + 16 pages + 256 pages
	if ($gen_page[$generation] == 65536)	{return $pre_final + 4370;} // 274 + 4096 = 4370
	if ($gen_page[$generation] == 1048576)	{return $pre_final + 69906;} // 4370 + 65536 = 1052946
	if ($gen_page[$generation] == 16777216)	{return $pre_final + 1118482;} // 
	if ($gen_page[$generation] == 268435456)			{return $pre_final + 17895698;} // 
	if ($gen_page[$generation] == 4294967296)			{return $pre_final + 286331154;} // 
	if ($gen_page[$generation] == 68719476736)			{return $pre_final + 4581298450;} // 
	if ($gen_page[$generation] == 1099511627776)		{return $pre_final + 73300775186;} // 
	if ($gen_page[$generation] == 17592186044416)		{return $pre_final + 1172812402962;} // 
	if ($gen_page[$generation] == 281474976710656)		{return $pre_final + 18764998447378;} // 
	if ($gen_page[$generation] == 4503599627370500)		{return $pre_final + 300239975158034;} // 
	if ($gen_page[$generation] >= 72057594037927900)	{return $pre_final + 4803839602528530;} // 
}	

function inserer_ancetres($sosa_d,$generation,$nom)
{	global $ancetres;

	$ancetres['id_indi'][]			= "";
	$ancetres['generation'][]	= $generation;
	$ancetres['sosa_d'][]		= $sosa_d;
	$ancetres['sosa_d_ref'][]	= "0";
	$ancetres['nom'][]			= $nom;
	$ancetres['prenom1'][]		= "";
	$ancetres['prenom2'][]		= "";
	$ancetres['prenom3'][]		= "";
	$ancetres['sexe'][]			= "";
	$ancetres['profession'][]	= "";
	$ancetres['date_naiss'][]	= "";
	$ancetres['lieu_naiss'][]	= "";
	$ancetres['dept_naiss'][]	= "";
	$ancetres['date_deces'][]	= "";
	$ancetres['lieu_deces'][]	= "";
	$ancetres['dept_deces'][]	= "";
	$ancetres['date_maria'][]	= "";
	$ancetres['lieu_maria'][]	= "";
	$ancetres['dept_maria'][]	= "";
	$ancetres['id_pere'][]		= "";
	$ancetres['id_mere'][]		= "";
	$ancetres['sosa_dyn'][]		= "";
	$ancetres['nopag_theo'][]	= no_page ($sosa_d,$generation);
	$ancetres['posit_page'][]	= position_page ($sosa_d,$generation);
//echo '<br>sosa_d:'.$sosa_d.'/'.$generation.'->nopage:'.no_page ($sosa_d,$generation).'/pos:'.position_page ($sosa_d,$generation);
}

class PDF extends TFPDF
{

function edition_cellule ($cell,$font,$larg,$fin)
{	global $i;
	global $ancetres;
				// B : noir gras, F : rose normal, M : bleu normal, '' : noir normal

	if ($font == "F") {$this->SetTextColor (172,2,83);$font = '';}
	else if ($font == "M") {$this->SetTextColor (0,0,128);$font = '';}
	else if ($font == "B" or $font == "") {$this->SetTextColor (0,0,0);}
	else {$this->SetTextColor (0,0,0);$font = '';}
	$this->SetFont('DejaVu','',8);

	if ($ancetres ['sosa_d'][$i] % 2 == 0 or $ancetres ['sosa_d'][$i] == 1)
	{	$this->SetFillColor(255);
		$this->Cell ($larg,3,$cell,"LR",$fin,"L",1);		// edition finale de la cellule
	} else
	{	$this->SetFillColor(210);
		$this->Cell ($larg,3,$cell,"LR",$fin,"L",1);		// edition finale de la cellule
	}
}

function editer_traits($POSIT,$XX,$YY)
{											// edition des traits relatifs à une case donnée
	global $x;
	global $y;
	global $col;
	global $haut_cellu;
	global $larg_cellu;

	$this->SetLineWidth(0.35);	// épaisseur des lignes par défaut
	$this->SetDrawColor (0);	// couleur du trait par défaut

	if ($_REQUEST['orient'] == "P")
	{	if ($POSIT == 2)
		{	$this->Line (8, $YY + $col['haute'][2]/2, 8, $y[1]);	//vertical
			$this->Line (8, $YY + $col['haute'][2]/2, 10, $YY + $col['haute'][2]/2); 	// horizontal
		}
		if ($POSIT == 3)
		{	$this->Line (8, $YY + $col['haute'][2]/2, 8, $y[1] + 21);	//vertical
			$this->Line (8, $YY + $col['haute'][2]/2, 10, $YY + $col['haute'][2]/2); 	// horizontal
		}
						// trait horizontal gauche sortant
		if ($POSIT >= 4 and $POSIT < 8) {$this->Line ($XX - 3, $YY + $col['haute'][3]/2, $XX, $YY + $col['haute'][3]/2);}
		elseif ($POSIT >= 8 and $POSIT < 16) {$this->Line ($XX - 3, $YY + $col['haute'][4]/2, $XX, $YY + $col['haute'][4]/2);}
		elseif ($POSIT >= 16) {$this->Line ($XX - 3, $YY + $col['haute'][5]/2, $XX, $YY + $col['haute'][5]/2);}
	
						// trait vertical et sortant droite du precedent
		if ($POSIT == 4 or $POSIT == 6)
		{	$haute_vert3 = $col['depar'][2] + $col['haute'][2]/2 - $col['depar'][3] - $col['haute'][3]/2;
			$this->Line ($XX - 3, $YY + $col['haute'][3]/2, $XX - 3, $YY + $col['haute'][3]/2 + $haute_vert3);	// vertical
			$this->Line ($XX - 6, $YY + $col['haute'][3]/2 + $haute_vert3, $XX - 3, $YY + $col['haute'][3]/2 + $haute_vert3); // horiz bas
		}
		if ($POSIT == 5 or $POSIT == 7)
		{	$haute_vert3 = $col['depar'][2] + $col['haute'][2]/2 - $col['depar'][3] - $col['haute'][3]/2;
			$this->Line ($XX - 3, $YY + $col['haute'][3]/2, $XX - 3, $YY + $col['haute'][3]/2 - $haute_vert3);	// vertical
			$this->Line ($XX - 6, $YY + $col['haute'][3]/2 - $haute_vert3, $XX - 3, $YY + $col['haute'][3]/2 - $haute_vert3); 	// horiz bas
		}
	
	 	if ($POSIT >= 8 and $POSIT < 16)
		{	$haute_vert4 = $col['depar'][3] + $col['haute'][3]/2 - $col['depar'][4] - $col['haute'][4]/2;
			if ($POSIT % 2 == 0)
			{	$this->Line ($XX - 3, $YY + $col['haute'][4]/2, $XX - 3, $YY + $col['haute'][4]/2 + $haute_vert4);	// vertical
				$this->Line ($XX - 6, $YY + $col['haute'][4]/2 + $haute_vert4, $XX - 3, $YY + $col['haute'][4]/2 + $haute_vert4);	// horiz bas
			} else
			{	$this->Line ($XX - 3, $YY + $col['haute'][4]/2, $XX - 3, $YY + $col['haute'][4]/2 - $haute_vert4);	// vertical
				$this->Line ($XX - 6, $YY + $col['haute'][4]/2 - $haute_vert4, $XX - 3, $YY + $col['haute'][4]/2 - $haute_vert4);// horiz haut
			} 
		}
	
	 	if ($POSIT >= 16)
		{	$haute_vert = $col['depar'][4] + $col['haute'][4]/2 - $col['depar'][5] - $col['haute'][5]/2;
			if ($POSIT % 2 == 0)
			{	$this->Line ($XX - 3, $YY + $col['haute'][5]/2, $XX - 3, $YY + $col['haute'][5]/2 + $haute_vert);	// vertical
				$this->Line ($XX - 6, $YY + $col['haute'][5]/2 + $haute_vert, $XX - 3, $YY + $col['haute'][5]/2 + $haute_vert); 				// horiz bas
			} 
			else
	 		{	$this->Line ($XX - 3, $YY + $col['haute'][5]/2, $XX - 3, $YY + $col['haute'][5]/2 - $haute_vert);	// vertical
				$this->Line ($XX - 6, $YY + $col['haute'][5]/2 - $haute_vert, $XX - 3, $YY + $col['haute'][5]/2 - $haute_vert); 					// horiz haut
			} 
		}
	} else
	{	if ($POSIT >= 2 and $POSIT < 8)
		{ 	$this->Line ($XX + $larg_cellu / 2, $YY + $haut_cellu, $XX + $larg_cellu / 2, $YY + $haut_cellu + 6);	//vertical dessous
			$this->Line ($XX + $larg_cellu / 2, $YY + $haut_cellu + 6, $x[floor($POSIT / 2)] + $larg_cellu * ($POSIT % 2), $YY + $haut_cellu + 6); 	// horizontal
		}
		if ($POSIT >= 8 and $POSIT < 16)
		{ 	$this->Line ($XX + $larg_cellu / 2, $YY + $haut_cellu, $XX + $larg_cellu / 2, $YY + $haut_cellu + 6);	//vertical dessous
			$this->Line ($XX + $larg_cellu / 2, $YY + $haut_cellu + 6, $x[floor($POSIT / 2)] + $larg_cellu / 2, $YY + $haut_cellu + 6); 	// horizontal
			$this->Line ($x[floor($POSIT / 2)] + $larg_cellu / 2, $YY + $haut_cellu + 6, $x[floor($POSIT / 2)] + $larg_cellu / 2, $YY + $haut_cellu + 9);	//vertical entre epoux
		}
		if ($POSIT >= 16 and $POSIT < 32)
		{ 	if ($POSIT % 2 == 1)
			{	$this->Line ($x[floor($POSIT / 2)] + $larg_cellu / 2, $YY + $haut_cellu, $x[floor($POSIT / 2)] + $larg_cellu / 2, $YY + $haut_cellu + 12);	//vertical dessous
			} else
			{	$this->Line ($XX, $YY + $haut_cellu, $XX, $YY + $haut_cellu + 6);
				$this->Line ($XX + $larg_cellu, $YY + $haut_cellu, $XX + $larg_cellu, $YY + $haut_cellu + 6);
			}
		}
	}
}

function editer_cases_vides()
{						/************** Edition des contours de toutes les cases d'une page (uniquement pour l'option speciale genealogiste */
	global $x;
	global $y;
	global $col;
	global $larg_cellu;
	global $haut_cellu;

	if ($_REQUEST['orient'] == "P")
	{	$zz = 1;	// contours cadre
		while ($zz < 8)
		{	
			$this->Line ($x[$zz], $y[$zz], $x[$zz] + 39, $y[$zz]);
			$this->Line ($x[$zz], $y[$zz] + 21, $x[$zz] + 39, $y[$zz] + 21);
			$this->Line ($x[$zz], $y[$zz], $x[$zz], $y[$zz] + 21);
			$this->Line ($x[$zz] + 39, $y[$zz], $x[$zz] + 39, $y[$zz] + 21);
			$zz++;
		}
		while ($zz < 16)
		{	$this->Line ($x[$zz], $y[$zz], $x[$zz] + 39, $y[$zz]);
			$this->Line ($x[$zz], $y[$zz] + 18, $x[$zz] + 39, $y[$zz] + 18);
			$this->Line ($x[$zz], $y[$zz], $x[$zz], $y[$zz] + 18);
			$this->Line ($x[$zz] + 39, $y[$zz], $x[$zz] + 39, $y[$zz] + 18);
			$zz++;
		}
		while ($zz < 32)
		{	$this->Line ($x[$zz], $y[$zz], $x[$zz] + 53, $y[$zz]);
			$this->Line ($x[$zz], $y[$zz] + 9, $x[$zz] + 53, $y[$zz] + 9);
			$this->Line ($x[$zz], $y[$zz], $x[$zz], $y[$zz] + 9);
			$this->Line ($x[$zz] + 53, $y[$zz], $x[$zz] + 53, $y[$zz] + 9);
			$zz++;
		} 
	} else
	{	for ($zz = 1; $zz < 32; $zz++)
		{	$this->Line ($x[$zz], $y[$zz], $x[$zz] + $larg_cellu, $y[$zz]);
			$this->Line ($x[$zz], $y[$zz] + $haut_cellu, $x[$zz] + $larg_cellu, $y[$zz] + $haut_cellu);
			$this->Line ($x[$zz], $y[$zz], $x[$zz], $y[$zz] + $haut_cellu);
			$this->Line ($x[$zz] + $larg_cellu, $y[$zz], $x[$zz] + $larg_cellu, $y[$zz] + $haut_cellu);
		}
	}
}

function editer_cell_principale($ia,$central = NULL)	
{	global $ancetres;
	global $ancetres_fs;
	global $x;
	global $y;
	global $col;
	global $got_lang;
	global $link;
	global $link2;
	global $no_special;
	global $XX;
	global $YY;
	global $POSIT;
	global $larg_cellu;
	global $haut_cellu;

	$this->SetLineWidth(0.35);	// épaisseur des lignes par défaut
	$this->SetFillColor (255);	// couleur du fond par défaut

	$this->editer_traits($POSIT,$XX,$YY);

	$decale_no_sosa = 0;

				// affichage photo en coordonnées absolues
	if ($ancetres['id_indi'][$ia] != NULL and $POSIT < 16 and $ia < 32)
	{
		$query = 'SELECT note_evene 
			FROM got_'.$_REQUEST['ibase'].'_evenement
			WHERE id_indi = '.$ancetres['id_indi'][$ia].' and type_evene = "FILE"';
		$result = sql_exec($query);
		$row = mysqli_fetch_row($result);
		if ($row[0] != NULL)
		{	$fp = @fopen('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0], "rb");
			if ($fp == TRUE)		// fichier non trouve
			{	if ($_REQUEST['orient'] == "P")
				{	if ($POSIT < 8)
					{	$this->Image ('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0], $XX, $YY - 21, 0, 21);
					} else
					{	$decale_no_sosa = 11;	// on decale le no de sosa pour la 3ème colonne
						if (fmod ($POSIT,2) == 0)
						{	$this->Image ('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0], $XX - 11, $YY, 0, 13);
						} else
						{	$this->Image ('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0], $XX - 11, $YY + 5, 0, 13);
						}
					}
				} else
				{	if ($POSIT < 4)
					{	$this->Image ('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0], $XX, $YY - 21, 0, 21);
					} elseif ($POSIT < 8)
					{	$decale_no_sosa = 13;
						$this->Image ('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0], $XX - 11, $YY, 0, 14);
					} else
					{	$this->Image ('picture/'.$_REQUEST['ibase'].'/thumbs/'.$row[0], $XX, $YY - 11, 0, 11);
					}
				}
			}
		}
	}
					// affichage du numero de sosa reference
	$this->SetTextColor (0,0,0);
	$this->SetFont('DejaVu','',7);
	if ($POSIT >= 2)
	{	if ($POSIT % 2 == 0 and $_REQUEST['orient'] == "P" or $_REQUEST['orient'] == "L")
		{	$this->SetXY ($XX - 7.5 - $decale_no_sosa,$YY);
		}
		else 
		{	if 		($POSIT < 8)	{$this->SetXY ($XX - 7.5,$YY + 18);} 
			else if ($POSIT < 16)	{$this->SetXY ($XX - 7.5 - $decale_no_sosa,$YY + 15);}
			else				{$this->SetXY ($XX - 7.5,$YY + 7);}
		}
//		$this->MultiCell (8,3,$ancetres['sosa_d'][$ia],0,R,1);
		$this->MultiCell (8,3,$no_special[$POSIT],0,"R"); 					//Special Damien	
	}


/************************* DEBUT constitution cellule ***************************/

					// Edition nom prenom profession 

	$this->SetXY ($XX,$YY);

					// couleur du texte
	if ($ancetres ['sosa_d_ref'][$ia] !== "")
	{	$this->SetTextColor (170);
	} elseif (trim($ancetres ['sexe'][$ia]) == "F")
		{	$this->SetTextColor (172,2,83);
		} else 
		{	$this->SetTextColor (0,0,128);
		}

	if ($_REQUEST['orient'] == "P")
	{	if ($POSIT >= 16)
		{	if ($ancetres ['profession'][$ia] != '')
			{	$pre_prof = ' - ';
			} else 
			{	$pre_prof = '';
			}
			$ligne1 = $ancetres ['nom'][$ia].' '
			.$ancetres ['prenom1'][$ia].' '
			.$ancetres ['prenom2'][$ia].' '
			.$ancetres ['prenom3'][$ia]
			.$pre_prof
			.$ancetres ['profession'][$ia];
	
			$this->SetFont('DejaVu','',7);
			$this->Cell($larg_cellu,3,$ligne1,"LTR",2,"L",1);
			$this->SetFont('DejaVu','',7);
	 	} else if ($POSIT >= 8  and $POSIT < 16)
		{	if (mb_strlen($ancetres ['nom'][$ia].' '.$ancetres ['prenom1'][$ia].' '.$ancetres ['prenom2'][$ia]) >= 30)
			{	$ligne1 = $ancetres ['nom'][$ia].' '.$ancetres ['prenom1'][$ia];
				if ($ancetres ['prenom2'][$ia] == '')
				{	$ligne2 = $ancetres ['profession'][$ia];
				} else 
				{	$ligne2 = $ancetres ['prenom2'][$ia].' - '.$ancetres ['profession'][$ia];
				}
			} else 
			{	$ligne1 = $ancetres ['nom'][$ia].' '.$ancetres ['prenom1'][$ia].' '.$ancetres ['prenom2'][$ia];
				if ($ancetres ['prenom3'][$ia] == '')
				{	$ligne2 = $ancetres ['profession'][$ia];
				} else 
				{	$ligne2 = $ancetres ['prenom3'][$ia].' - '.$ancetres ['profession'][$ia];
				}
			}
			$this->SetFont('DejaVu','',8);
			$this->Cell($larg_cellu,3,$ligne1,"LTR",2,"L",1);
			$this->SetFont('DejaVu','',8);
			$this->Cell($larg_cellu,3,$ligne2,"LR",2,"L",1);
		} else if ($POSIT >= 1)
		{	if (mb_strlen($ancetres ['nom'][$ia].' '.$ancetres ['prenom1'][$ia].' '.$ancetres ['prenom2'][$ia]) >= 26)
			{	$ligne1 = $ancetres ['nom'][$ia].' '.$ancetres ['prenom1'][$ia];
				$ligne2 = $ancetres ['prenom2'][$ia].' '.$ancetres ['prenom3'][$ia];
				$ligne3 = $ancetres ['profession'][$ia];
			} else 
			{	$ligne1 = $ancetres ['nom'][$ia].' '.$ancetres ['prenom1'][$ia].' '.$ancetres ['prenom2'][$ia];
				$ligne2 = $ancetres ['profession'][$ia];
				$ligne3 = '';
			}
			$this->SetFont('DejaVu','',8);
			$this->Cell($larg_cellu,3,$ligne1,"LTR",2,"L",1);
			$this->SetFont('DejaVu','',8);
			$this->Cell($larg_cellu,3,$ligne2,"LR",2,"L",1);
			$this->Cell($larg_cellu,3,$ligne3,"LR",2,"L",1);
		}
	} else
	{	$ligne1 = $ancetres ['nom'][$ia];
		if ($ancetres['prenom1'][$ia] !== '')
		{	$ligne2 = $ancetres['prenom1'][$ia];
			if ($ancetres['prenom2'][$ia] !== '')
			{	$ligne3 = $ancetres['prenom2'][$ia];
				if ($ancetres['prenom3'][$ia] !== '')
				{	$ligne4 = $ancetres['prenom3'][$ia];
					$ligne5 = $ancetres['profession'][$ia];
				} else
				{	$ligne4 = $ancetres['profession'][$ia];
					$ligne5 = '';
				}
			} else
			{	$ligne3 = $ancetres['profession'][$ia];
				$ligne4 = '';
				$ligne5 = '';
			}
		} else
		{	$ligne2 = $ancetres['profession'][$ia];
			$ligne3 = '';
			$ligne4 = '';
			$ligne5 = '';
		}
		$this->SetFont('DejaVu','',8);
		$this->Cell($larg_cellu,3,$ligne1,"LTR",2,"L",1);
		$this->Cell($larg_cellu,3,$ligne2,"LR",2,"L",1);
		$this->SetFont('DejaVu','',8);
		$this->Cell($larg_cellu,3,$ligne3,"LR",2,"L",1);
		$this->Cell($larg_cellu,3,$ligne4,"LR",2,"L",1);
		$this->Cell($larg_cellu,3,$ligne5,"LR",2,"L",1);
	}

					// date et lieu naissance
	if ($ancetres ['date_naiss'][$ia] != '') 
	{	if ($ancetres['sexe'][$ia] == 'F')
		{	$pre_date_naiss = $got_lang['Ne']."e ";
		} else
		{	$pre_date_naiss = $got_lang['Ne']." ";
		}
	} else 
	{	$pre_date_naiss = '';
	}

	if ($ancetres ['lieu_naiss'][$ia] != '')
	{	$pre_lieu_naiss = $got_lang['Situa'].' ';
	} else 
	{	$pre_lieu_naiss = '';
	}

	if (mb_strlen($ancetres ['dept_naiss'][$ia]) == 2)
	{	$post_lieu_naiss = '('.$ancetres ['dept_naiss'][$ia].')';
	} else 
	{	$post_lieu_naiss = '';
	}

	if ($ancetres ['nom'][$ia] != '' 
		and $ancetres ['sosa_d_ref'][$ia] == "" 
		and $ancetres ['lieu_naiss'][$ia] == '' 
		and $_REQUEST['opti_reche'] == "Y")
	{	$fill_lieu_naiss=210;		// fond des lieu en gris pale pour visualiser les actes a chercher (option page vide)
	} else 
	{	$fill_lieu_naiss=255;
	}

	if ($ancetres ['nom'][$ia] != '' 
		and $ancetres ['sosa_d_ref'][$ia] == ""
		and $_REQUEST['opti_reche'] == "Y"
		and ($ancetres ['date_naiss'][$ia] == ''
			 or mb_substr($ancetres['date_naiss'][$ia],0,3) == 'ABT' 
			 or mb_substr($ancetres['date_naiss'][$ia],0,3) == 'BEF' 
			 or mb_substr($ancetres['date_naiss'][$ia],0,3) == 'AFT' 
			 or mb_substr($ancetres['date_naiss'][$ia],0,3) == 'BET' 
			 ) 
		)
	{	$fill_date_naiss=210;		// fond des dates en gris pale pour visualiser les actes a chercher (option page vide)
	} else 
	{	$fill_date_naiss=255;
	}

	if ($POSIT < 16 or $_REQUEST['orient'] == "L")
	{	if ($POSIT < 8) {$this->SetFont('DejaVu','',8);} else {$this->SetFont('DejaVu','',7);}
		$this->SetFillColor($fill_date_naiss);
		$this->Cell($larg_cellu,3,$pre_date_naiss.affichage_date($ancetres ['date_naiss'][$ia]),"LR",2,"L",1);
		$this->SetFillColor($fill_lieu_naiss);
		$this->Cell($larg_cellu,3,$pre_lieu_naiss.$ancetres ['lieu_naiss'][$ia].$post_lieu_naiss,"LR",2,"L",1);
		$this->SetFillColor(255);
		if ($POSIT < 8) {$this->SetFont('DejaVu','',8);} else {$this->SetFont('DejaVu','',7);}
	} else
	{	$this->SetFont('DejaVu','',7);
		$this->SetFillColor($fill_lieu_naiss);
		$this->Cell($larg_cellu,3,$pre_date_naiss.affichage_date($ancetres ['date_naiss'][$ia])." ".$pre_lieu_naiss.$ancetres ['lieu_naiss'][$ia].$post_lieu_naiss,"LR",2,"L",1);
		$this->SetFillColor(255);
		$this->SetFont('DejaVu','',7);
	}

					// date et lieu deces
	if ($ancetres ['date_deces'][$ia] != '')
	{	$pre_date_deces = "+ ";
	} else 
	{	$pre_date_deces = '';
	}
	if ($ancetres ['nom'][$ia] != '' 
		and $ancetres ['sosa_d_ref'][$ia] == "" 
		and mb_substr($ancetres ['date_naiss'][$ia],-4) < 1900
		and $_REQUEST['opti_reche'] == "Y"
		and ($ancetres ['date_deces'][$ia] == '' 
			 or mb_substr($ancetres['date_deces'][$ia],0,3) == 'ABT' 
			 or mb_substr($ancetres['date_deces'][$ia],0,3) == 'BEF' 
			 or mb_substr($ancetres['date_deces'][$ia],0,3) == 'AFT' 
			 or mb_substr($ancetres['date_deces'][$ia],0,3) == 'BET' 
			)
		)
	{	$fill_date_deces=210;		// fond des dates en gris pale pour visualiser les actes a chercher (option page vide)
	} else 
	{	$fill_date_deces=255;
	}
	if ($ancetres ['lieu_deces'][$ia] != '')
	{	$pre_lieu_deces = $got_lang['Situa'].' ';
	} else 
	{	$pre_lieu_deces = '';
	}

	if (mb_strlen($ancetres ['dept_deces'][$ia]) == 2)
	{	$post_lieu_deces = '('.$ancetres ['dept_deces'][$ia].')';
	} else 
	{	$post_lieu_deces = '';
	}

	if ($ancetres ['nom'][$ia] != '' 
		and $ancetres ['sosa_d_ref'][$ia] == ""
		and $ancetres ['lieu_deces'][$ia] == '' 
		and mb_substr($ancetres ['date_naiss'][$ia],-4) < 1900
		and $_REQUEST['opti_reche'] == "Y"
		)
	{	$fill_lieu_deces=210;		// fond des dates en gris pale pour visualiser les actes a chercher (option page vide)
	} else 
	{	$fill_lieu_deces=255;
	}

	if ($POSIT < 16 or $_REQUEST['orient'] == "L")
	{	$this->SetFillColor($fill_date_deces);
		$this->Cell($larg_cellu,3,$pre_date_deces.affichage_date($ancetres ['date_deces'][$ia]),"LR",2,"L",1);
		$this->SetFillColor($fill_lieu_deces);
		$this->Cell($larg_cellu,3,$pre_lieu_deces.$ancetres ['lieu_deces'][$ia].$post_lieu_deces,"LBR",2,"L",1);
		$this->SetFillColor(255);
	} else
	{	$this->SetFillColor($fill_lieu_deces);
		$this->Cell($larg_cellu,3,$pre_date_deces.affichage_date($ancetres ['date_deces'][$ia])." ".$pre_lieu_deces.$ancetres ['lieu_deces'][$ia].$post_lieu_deces,"LBR",2,"L",1);
		$this->SetFillColor(255);
	}


/**************************** Fin constitution cellule principale ********************************/


					// affichage des frères et soeurs
									// ne sont pas affichés pour l'arbre paysage, il n'y a vraiment pas de place
	if ($_REQUEST['orient'] == "P")
	{	$i_ancetres_fs = 0;
		$ligne = NULL;
		if ($POSIT < 16) 
		{	if ($ancetres['id_indi'][$ia] != '')
			{	
// print_r($ancetres_fs);
				$i_ancetres_fs = 0;
				while ($i_ancetres_fs < @count($ancetres_fs['id_fs'][ $ancetres['id_indi'][$ia] ]) )
				{	$indice = floor ($i_ancetres_fs/3);	// 3 freres et soeurs maxi par ligne
					$ligne[$indice] = $ligne[$indice].$ancetres_fs['prenom1'][$ancetres['id_indi'][$ia]][$i_ancetres_fs].', ';
					$i_ancetres_fs++;//echo 'toto';
				}
			}
			$this->SetFont('DejaVu','',7);
			if ($ancetres ['sosa_d_ref'][$ia] !== "")
			{	$this->SetTextColor (170);
			} else
			{	$this->SetTextColor (0,0,0);
			}
			if ($i_ancetres_fs > 0) {$pre_fs = $i_ancetres_fs.' '.$got_lang['F&S'].': ';} else {$pre_fs = '';}
	
						// ligne d'un 1/10 millimètre pour ne pas écraser la bordure inferieure de la case
			$x_courant = $this->GetX();
			$y_courant = $this->GetY();
			$this->SetXY($x_courant,$y_courant + 0.4);
			
			if ($ligne[0] == "")	{$fond = 0;}	else {$fond= 1;}
			$this->Cell($larg_cellu,2.5,$pre_fs.mb_substr(trim($ligne[0]),0,-1),0,2,"L",$fond);
			if ($ligne[1] == "")	{$fond = 0;}	else {$fond= 1;}
			$this->Cell($larg_cellu,2.5,mb_substr(trim($ligne[1]),0,-1),0,2,"L",$fond);
			if ($ligne[2] == "")	{$fond = 0;}	else {$fond= 1;}
			$this->Cell($larg_cellu,2.5,mb_substr(trim($ligne[2]),0,-1),0,2,"L",$fond);
		}
	}

					// affichage du mariage
	$this->SetFont('DejaVu','',7);
	if ($ancetres ['sosa_d_ref'][$ia] !== "")
	{	$this->SetTextColor (170);
	} else
	{	$this->SetTextColor (0,0,0);
	}
	if (fmod ($POSIT,2) == 0 or $POSIT == 1)
	{	if ($ancetres ['date_maria'][$ia] != '') 
		{	$pre_date_maria = $got_lang['Marie'].' ';
		} else 
		{	$pre_date_maria = '';
		}
		if ($ancetres ['nom'][$ia] != '' 
			and $ancetres ['sosa_d_ref'][$ia] == ""
			and $_REQUEST['opti_reche'] == "Y"
			and ($ancetres ['date_maria'][$ia] == '' 
				 or mb_substr($ancetres['date_maria'][$ia],0,3) == 'ABT' 
				 or mb_substr($ancetres['date_maria'][$ia],0,3) == 'BEF' 
				 or mb_substr($ancetres['date_maria'][$ia],0,3) == 'AFT' 
				 or mb_substr($ancetres['date_maria'][$ia],0,3) == 'BET' 
				)
			)
			{	$fill_date_maria=210;
			} else 
			{	$fill_date_maria=255;
			}

		if ($ancetres ['lieu_maria'][$ia] != '') 
		{	$pre_lieu_maria = $got_lang['Situa'].' ';
		} else 
		{	$pre_lieu_maria = '';
		}
		if ($ancetres ['nom'][$ia] != '' 
			and $ancetres ['sosa_d_ref'][$ia] == "" 
			and $ancetres ['lieu_maria'][$ia] == '' 
			and $_REQUEST['opti_reche'] == "Y"
			)
			{	$fill_lieu_maria=210;
			} else 
			{	$fill_lieu_maria=255;
			}
					// ligne d'un 1/10 millimètre pour ne pas écraser la bordure inferieure de la case
		$x_courant = $this->GetX();
		$y_courant = $this->GetY();
		if ($_REQUEST['orient'] == "P" or $_REQUEST['orient'] == "L" and $POSIT >= 16)
		{	$this->SetXY($x_courant + 0.4,$y_courant + 0.4);
		} elseif ($POSIT >= 8)
		{	$this->SetXY($x_courant + 15.4,$y_courant + 0.4);
		} elseif ($POSIT >= 4)
		{	$this->SetXY($x_courant + 2.4 * $larg_cellu,$y_courant + 0.4);
		} elseif ($POSIT >= 2)
		{	$this->SetXY($x_courant + 3.7 * $larg_cellu,$y_courant + 0.4);
		}
		if ($POSIT < 16 or $_REQUEST['orient'] == "L") 
		{	if ($POSIT < 8) {$this->SetFont('DejaVu','',8);} else {$this->SetFont('DejaVu','',7);}
			$this->SetFillColor($fill_date_maria);
			$this->Cell($larg_cellu,2.5,$pre_date_maria.affichage_date($ancetres ['date_maria'][$ia]),0,2,"L",1);
			$this->SetFillColor($fill_lieu_maria);
			$this->Cell($larg_cellu,2.5,$pre_lieu_maria.$ancetres ['lieu_maria'][$ia],0,2,"L",1);
			$this->SetFillColor(255);
		} else
		{	$this->SetFillColor($fill_lieu_maria);
			$this->Cell($larg_cellu,2.5,$pre_date_maria.affichage_date($ancetres ['date_maria'][$ia])." ".$pre_lieu_maria.$ancetres ['lieu_maria'][$ia],0,2,"L",1);
			$this->SetFillColor(255);
		}
	} 

				// affichage report page suivante 
	if ($POSIT >= 16 and $ancetres['nopag_suiv'][$ia] !== "")
	{	if ($ancetres['generation'][$ia] < $_REQUEST['nbgen'])
		{	if ($_REQUEST['orient'] == "P")
			{	$this->SetXY($XX + $larg_cellu + 1, $YY + 3);
			} else
			{	$this->SetXY($XX + $larg_cellu / 2 - 3, $YY - 6.5);
			}
//			$this->SetLink($link2[ $ancetres['nopag_suiv'][$ia] ]);
 			if ($_REQUEST['orient'] == "P" or ($_REQUEST['orient'] == "L" and $POSIT % 2 !== 1) )
			{	$link[ $ancetres['nopag_suiv'][$ia] ] = $this->AddLink();
				$this->SetFont("DejaVu","");
				$this->SetTextColor(0,0,255);
				$this->Cell(5,3,'page',0,2,"C",1,$link[ $ancetres['nopag_suiv'][$ia] ]);
				$this->Cell(5,3,$ancetres['nopag_suiv'][$ia],0,2,"C",1,$link[ $ancetres['nopag_suiv'][$ia] ]); 
			}
 		} else	// i.e pas de page suivante, mais existence d'ancetres dans la base : on le signale
 		{	if ($_REQUEST['orient'] == "P")
			{	$this->Line($XX + $larg_cellu, $YY+4, $XX + $larg_cellu +1, $YY+4);
				$this->Line($XX + $larg_cellu + 1, $YY + 2, $XX + $larg_cellu + 3, $YY + 2);
				$this->Line($XX + $larg_cellu + 1, $YY + 2, $XX + $larg_cellu + 1, $YY + 6);
				$this->Line($XX + $larg_cellu + 1, $YY + 6, $XX + $larg_cellu + 3, $YY + 6);
			} elseif ($POSIT % 2 == 0)
			{	$this->Line($XX + $larg_cellu / 2, $YY, $XX + $larg_cellu / 2, $YY - 2);
				$this->Line($XX + $larg_cellu / 2 - 8, $YY - 2, $XX + $larg_cellu / 2 + 8, $YY - 2);
				$this->Line($XX + $larg_cellu / 2 - 8, $YY - 2, $XX + $larg_cellu / 2 - 8, $YY - 4);
				$this->Line($XX + $larg_cellu / 2 + 8, $YY - 2, $XX + $larg_cellu / 2 + 8, $YY - 4);
			}
 		}
	} 
}

}

/**************************** DEBUT DU SCRIPT ************************************/

global $XX;
global $YY;
global $POSIT;
global $haut_cellu;
global $larg_cellu;

$dim_page = recup_dim_page();



$OLD_TIME = time();

if ($_REQUEST["continu"] !== "OK" and $_REQUEST["itype"] != "excel")
{	
	if (!$_REQUEST['nbgen']) {$_REQUEST['nbgen'] = 8;}
//	if (!$_REQUEST["opti_reche"]) {$_REQUEST["opti_reche"] = 'N';}
//	if (!$_REQUEST["implex"]) {$_REQUEST["implex"] = 'N';}
//	$titre_page = "GeneoTree v".$got_lang['Relea']." - ".$got_lang['ArAsc'];
	require_once ("menu.php");

	echo '</td></tr></table>';	// pour fermer la table du menu

	echo '<div align=center style="position:absolute; left: 355px; top:180px; width: 400px" class="cell_indiv titre">';	// div pour positionner
	if ($_REQUEST["itype"] == "arbre")
	{ echo '<b>'.$got_lang['IBPdf'].'</b>';
	} else
	{ echo '<b>'.$got_lang['IBPdL'].'</b>';
	}
	echo '</div>';

	echo '<div align=center style="position:absolute; left: 355px; top:250px; width: 400px" class=cell_indiv>';	// div pour positionner
	echo '<FORM method="POST" name=criteres>';
	if ($_REQUEST["itype"] == "arbre")
	{	echo '<br><b>Orientation : </b>';
		afficher_radio_bouton("orient",array("Portait","Paysage"),array("P","L"),$_REQUEST["orient"],"YES");
	}
	echo '<br><br><b>Nb generations : </b>';
	afficher_radio_bouton("nbgen",array(8,12,16,"Tous"),array(8,12,16,40),$_REQUEST["nbgen"],"YES");

	echo '<br><br>'.$got_lang["Avec"].' implexe : <input type="checkbox" name="implex" value="Y">';
	if ($_REQUEST["itype"] == "arbre" and $_REQUEST["orient"] == "P") 
	{	echo '<br><br>'.$got_lang['SpeGe'].' : <input type="checkbox" name="opti_reche" value="Y" title="'.$got_lang['IBSpe'].'">';
	} else
	{	echo '<br><br>';
	}
  
	echo '<br><input type="submit" name="continu" value="OK">';
  echo str_repeat("&nbsp;",5).'<input type="button" value="Annul" name="stop" onclick="window.location=&quot;arbre_ascendant.php?ibase='.$_REQUEST['ibase'].'&id='.$_REQUEST['id'].'&lang='.$_REQUEST['lang'].'&theme='.$_REQUEST['theme'].'&format='.$_REQUEST['forma'].'&quot;">';
	echo '</FORM>';
	echo '<br></div>';

		// positionnement du focus sur le bouton OK
	echo '<script language="JavaScript" type="text/javascript">	document.criteres.continu.focus();	</script>';
	
} elseif ($_REQUEST['itype'] == "arbre")
{	@set_time_limit(180);

	for ($ii = 0; $ii <= 100; $ii++)		// initialisation du tableau de correspondance generation/groupe pour un arbre 5 generation
	{	//gen_grp[$ii] = ;
		$gen_page[$ii] = pow(16,floor(($ii+3)/4)-1);	// nb de pages par groupe
	}

	$ancetres[][]='';$communs[][]='';
	$ancetres['id_indi'] [0] = $_REQUEST['id'];
	$cpt_generations = 0;
	recup_ascendance ($ancetres,0,$_REQUEST['nbgen'] + 1,'ME_G');		// ajout d'une generation pour faire apparaitre les traits en bout de generation
	if ($_REQUEST['implex'] == "Y" or $_REQUEST['opti_reche'] == "Y")	{integrer_implexe($_REQUEST['nbgen'] + 1,'ME_G');}
	// if ($_REQUEST['implex'] == "Y" or $_REQUEST['opti_reche'] == "Y")	{integrer_implexe($_REQUEST['nbgen'],'ME_G');}
	$nb_ancetres = @count($ancetres['id_indi']);

   	$ii = 0;				// 1ère passe : on ajoute les no de page théoriques et les positions dans la page au tableau ancetres
	while ($ii < $nb_ancetres)
	{	$ancetres['nopag_theo'][$ii] = no_page ($ancetres['sosa_d'][$ii],$ancetres['generation'][$ii]);
		$ancetres['posit_page'][$ii] = position_page ($ancetres['sosa_d'][$ii],$ancetres['generation'][$ii]);
 		if ($ancetres['id_pere'][$ii] == NULL and $ancetres['id_mere'][$ii] !== NULL)
		{	$fille_mere_possi['generation'][]	= $ancetres['generation'][$ii];
			$fille_mere_possi['sosa_d'][]		= $ancetres['sosa_d'][$ii];
			$fille_mere_possi['nom'][]			= $ancetres['nom'][$ii];
			$fille_mere_possi['nopag_theo'][]	= $ancetres['nopag_theo'][$ii];
			$fille_mere_possi['posit_page'][]	= $ancetres['posit_page'][$ii];
		}
 		$ii++;
	}
// afficher_ascendance();
					// 1ere passe bis optionnelle speciale genealogiste 
	if ($_REQUEST['opti_reche'] == "Y")
	{						// on ajoute les branches peres inconnus
		for ($ii = 0; $ii < @count($fille_mere_possi['generation']); $ii++)
		{	$ii_pere = array_search ($fille_mere_possi['sosa_d'][$ii] * 2 + 1,$ancetres['sosa_d']);
			if ($ancetres['nom'][$ii_pere] == $fille_mere_possi['nom'][$ii] )
			{	inserer_ancetres($ancetres['sosa_d'][$ii_pere] - 1,$ancetres['generation'][$ii_pere],"Père inconnu");
	
				$temp = "";
				$sosa_d_ref = "";
				$delta_gen = $ancetres['generation'][$ii_pere];
				$sosa_d_ref[0] = $ancetres['sosa_d'][$ii_pere] - 1;
				while ($delta_gen <= $_REQUEST['nbgen'] and $delta_gen <= 7)	// 1 boucle par génération à remonter (idem fonction integrer_implexe)
				{	for ($jj = 0; $jj < count($sosa_d_ref); $jj++)	// on lit les sosa_ref trouvés lors de la boucle précédente
					{	for ($kk = $sosa_d_ref[$jj] * 2; $kk < ($sosa_d_ref[$jj] + 1) * 2; $kk++)	
						{	inserer_ancetres($kk, $delta_gen + 1,"Père inconnu");
							$temp[] = $kk;
						}
					}
					$delta_gen++;
					$sosa_d_ref = $temp;
					$temp = "";
				}
			}
		}
							// on ajoute les pages vides uniquement pour les 8 premieres generations
		for ($i_pages = 2; $i_pages <= 17; $i_pages++)
		{	$temp = array_search($i_pages,$ancetres['nopag_theo']);
			if ($temp == FALSE)
			{	inserer_ancetres (2 * $i_pages + 28,5,"");
			}
		}
	}

					// tri par pages theoriques
 	array_multisort ($ancetres ['nopag_theo'],$ancetres ['posit_page']
				,$ancetres['id_indi'],$ancetres ['generation'],$ancetres ['sosa_d'],$ancetres ['sosa_d_ref']
				,$ancetres ['nom'],$ancetres ['prenom1'],$ancetres ['prenom2'],$ancetres ['prenom3']
				,$ancetres ['sexe'],$ancetres ['profession']
				,$ancetres ['date_naiss'],$ancetres ['lieu_naiss'],$ancetres ['dept_naiss']
				,$ancetres ['date_deces'],$ancetres ['lieu_deces'],$ancetres ['dept_deces']
				,$ancetres ['date_maria'],$ancetres ['lieu_maria'],$ancetres ['dept_maria']
				,$ancetres ['id_pere'],$ancetres ['id_mere'],$ancetres ['sosa_dyn']); 

 					// 2ème passe : on ajoute les 'nopag_reel', n° de page reels
	$nb_ancetres = count($ancetres['id_indi']);
    $ii = 1;				 
	$ancetres['nopag_reel'][0] = 1;
	$i_page = 0;
	$old_num_page = "&";
	while ($ii < $nb_ancetres)		
	{	if ($ancetres['nopag_theo'][$ii] !== $old_num_page)	
		{	$i_page++;
		}
		$ancetres['nopag_reel'][$ii] = $i_page;
		$old_num_page = $ancetres['nopag_theo'][$ii];
		$ii++;
	} 

    				// 3ème passe : on ajoute les no des pages suivantes 
	$ii = 0;		//	(on en profite pour stocker le personnage principal de la page suivante
	$page_central[1] = 0;
	while ($ii < $nb_ancetres)		
	{	if ($ancetres['posit_page'][$ii] >= 16)
		{	$temp = array_search ($ancetres['sosa_d'][$ii] * 2,$ancetres['sosa_d']);
			if ($temp !== FALSE)
			{	$ancetres['nopag_suiv'][$ii] = $ancetres['nopag_reel'][$temp];

// 				if ($page_central[ $ancetres['nopag_reel'][$temp] ] == "")    // A priori, test inutile, toujours renseigné
 				{	$page_central[ $ancetres['nopag_reel'][$temp] ] = $ii;
					$page_prec[ $ancetres['nopag_reel'][$temp] ] = $ancetres['nopag_reel'][$ii];
				}
			} else
			{	$temp = array_search ($ancetres['sosa_d'][$ii] * 2 + 1,$ancetres['sosa_d']);
				if ($temp !== FALSE)
				{	$ancetres['nopag_suiv'][$ii] = $ancetres['nopag_reel'][$temp];
					if ($page_central[ $ancetres['nopag_reel'][$temp] ] == "")
					{	$page_central[ $ancetres['nopag_reel'][$temp] ] = $ii;
						$page_prec[ $ancetres['nopag_reel'][$temp] ] = $ancetres['nopag_reel'][$ii];
					}
				} else
				{	$ancetres['nopag_suiv'][$ii] = "";
				}
			}
		}
		$ii++;
	}

// afficher_ascendance();
//for ($ii=0; $ii < count($page_central) ; $ii++) {echo '<br>ii'.$ii.'->'.$page_central[$ii].'/'.$page_prec[$ii];}  

	recup_pts_asc($_REQUEST['forma'], $_REQUEST["orient"]);

	$no_special = array (2=>1, 3=>2, 4=>11, 5=>12, 6=>21, 7=>22, 8=>111
				, 9=>112, 10=>121, 11=>122, 12=>211, 13=>212, 14=>221, 15=>222,	16=>1111
				,17=>1112, 18=>1121, 19=>1122, 20=>1211, 21=>1212, 22=>1221, 23=>1222, 24=>2111
				,25=>2112, 26=>2121, 27=>2122, 28=>2211, 29=>2212, 30=>2221, 31=>2222);

	$entete = $got_lang['ArAsc'];
	$entete1 = $ancetres ['prenom1'][0]." ".$ancetres ['prenom2'][0]." ".$ancetres ['prenom3'][0]." ".$ancetres ['nom'][0];
	$orientation = $_REQUEST['orient'];			// passage du parametre pour la fonction header generique de fpdf

	$pdf=new PDF($orientation,"mm",$_REQUEST['forma']);
	$pdf->SetTitle($entete.' - '.$entete1);
	$pdf->SetCreator('GeneoTree');
	$pdf->SetAuthor('GeneoTree');
	$pdf->SetMargins(18,20);
	$pdf->SetAutoPageBreak(TRUE, 20);
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);
			
	$ii = 1;
	$old_nopag_theo = "";
	while ($ii < $nb_ancetres and $ancetres['generation'][$ii] <= $_REQUEST['nbgen'])	// test generation pour ne pas afficher la generation supp
	{	if ($ancetres['nopag_theo'][$ii] !== $old_nopag_theo)
		{	$pdf->AddPage(); // $entete et $entete1 sont affiches dans le header tfpdf.php
			$pdf->SetLink($link[ $ancetres['nopag_reel'][$ii] ],90);						// ancre pour linker dessus. Valeur bidon pour la premiere page. 
			$pdf->SetAutoPageBreak(TRUE, 7);		// on mange exceptionnellement dans la bordure
			$pdf->SetTopMargin(7);					// on mange exceptionnellement dans la bordure

			$XX = $x[1];
			$YY = $y[1];
			$POSIT = 1;
						// taille des cellules
			if ($_REQUEST['orient'] == "P")
			{	if ($POSIT < 16) 
				{	$larg_cellu = 39;
				} else
				{	$larg_cellu = 53;
				}
			} else
			{	$larg_cellu = 25;
				$haut_cellu = 27;
			}

			if ($_REQUEST['opti_reche'] == "Y")
			{	$pdf->editer_cases_vides();
				for ($kk = 1; $kk < 32; $kk++)
				{	$pdf->editer_traits($kk,$x[$kk],$y[$kk]);
				
				}
			}

			$pdf->editer_cell_principale($page_central[ $ancetres['nopag_reel'][$ii] ]);

					// affichage des conjoints uniquement pour le personnage central
			if ($ii !== 1 and $_REQUEST["orient"] == "P")		// sauf pour le personnage de départ : le conjoint n'existe pas dans le tableau d'ascendance !!!
			{	$pdf->SetFont('DejaVu','',8);
				$pdf->SetXY (7,$y[1] + 36);
		
				if ($ancetres ['sosa_d_ref'][$ii] !== "")
				{	$pdf->SetTextColor (170);
				} elseif (trim($ancetres ['sexe'][$ii]) == "F")
				{	$pdf->SetTextColor (0,0,128);
				} else
				{	$pdf->SetTextColor (172,2,83);
				}
				if (trim($ancetres ['sexe'][$page_central[ $ancetres['nopag_reel'][$ii] ] ]) == "F")
				{	$temp = array_search($ancetres['sosa_d'][$page_central[ $ancetres['nopag_reel'][$ii] ] ] - 1, $ancetres['sosa_d']);
				} else
				{	$temp = array_search($ancetres['sosa_d'][$page_central[ $ancetres['nopag_reel'][$ii] ] ] + 1, $ancetres['sosa_d']);
				}
				if ($temp !== FALSE)
				{	$pdf->Cell(15,3,$got_lang['Avec'],0,2,"L",1);
					$pdf->Cell(15,3,$ancetres ['nom'][$temp].' '.$ancetres ['prenom1'][$temp].' '.$ancetres ['prenom2'][$temp].' '.$ancetres ['prenom3'][$temp],0,2,"L",1);
				}
/*							Tentative de placement d'un lien hypertexte pour retour vers la page appellante -> Pas réussi !
 				$pdf->SetFont('DejaVu','u',8);
				$pdf->SetXY (4,$y[1] + 10);
				$link2[ $ancetres['nopag_reel'][$ii] ] = $pdf->AddLink();
				$pdf->SetFont("DejaVu",u);
				$pdf->SetTextColor(0,0,255);
				$pdf->Cell(5,3,'pag',0,2,C,0,$link2[ $ancetres['nopag_reel'][$ii] ]);
				$pdf->Cell(5,3,$page_prec[ $ancetres['nopag_reel'][$ii] ],0,2,C,0,$link2[ $ancetres['nopag_reel'][$ii] ]);  */
			}
		}
						// affichage cellule principale
		$XX = $x[$ancetres['posit_page'][$ii]];
		$YY = $y[$ancetres['posit_page'][$ii]];
		$POSIT = $ancetres['posit_page'][$ii];
	
						// taille des cellules
		if ($_REQUEST['orient'] == "P")
		{	if ($POSIT < 16) 
			{	$larg_cellu = 39;
			} else
			{	$larg_cellu = 53;
			}
		} else
		{	$larg_cellu = 25;
			$haut_cellu = 27;
		}

		$pdf->editer_cell_principale($ii);

		$old_nopag_theo = $ancetres['nopag_theo'][$ii];
		$ii++;
	}
	$pdf->Output();
}
elseif ($_REQUEST['itype'] == "liste")
{	@set_time_limit(120);
	$ancetres[][] = "";
	$communs = NULL;
	$ancetres['id_indi'] [0] = $_REQUEST['id'];
	recup_ascendance ($ancetres,0,$_REQUEST['nbgen'],'ME_G');
	if ($_REQUEST['implex'] == "Y")	{integrer_implexe($_REQUEST['nbgen'],'ME_G');}

	array_multisort ($ancetres ['sosa_d'],$ancetres ['sosa_dyn'],$ancetres['id_indi']
	,$ancetres ['generation'],$ancetres ['sosa_d_ref']
	,$ancetres ['nom'],$ancetres ['prenom1'],$ancetres ['prenom2'],$ancetres ['prenom3']
	,$ancetres ['sexe'],$ancetres ['profession']
	,$ancetres ['date_naiss'],$ancetres ['lieu_naiss'],$ancetres ['dept_naiss']
	,$ancetres ['date_deces'],$ancetres ['lieu_deces'],$ancetres ['dept_deces']
	,$ancetres ['date_maria'],$ancetres ['lieu_maria'],$ancetres ['dept_maria']
	,$ancetres ['id_pere'],$ancetres ['id_mere']); 
//afficher_ascendance();

	$orientation = "L";
	$entete = "Ascendancy List";
	$entete1 = $ancetres ['prenom1'][0].' '.$ancetres ['prenom2'][0].' '.$ancetres ['prenom3'][0].' '.$ancetres ['nom'][0];
	$pdf = new PDF($orientation,'mm',$_REQUEST['forma']);
	$pdf->SetTitle($entete.' - '.$entete1);
	$pdf->SetCreator('GeneoTree');
	$pdf->SetAuthor('GeneoTree');
	$pdf->SetMargins(18,20);
	$pdf->SetAutoPageBreak(TRUE, 20);
	$pdf->AddFont('DejaVu','','DejaVuSansCondensed.ttf',true);

	$pdf->AddPage();

	$nb_ancetres = count($ancetres['id_indi']);
	$old_gen = 0;
	$nb_indiv_dans_gen = 0;
	$i_indiv = 0;
	for ($i = 0; $i < $nb_ancetres; $i++ )
	{	$temp = largeur_cellule ($ancetres ['sosa_d'][$i],'');
		if ($temp > $larg_sosa_d) {$larg_sosa_d = $temp;}
		$temp = largeur_cellule ($ancetres ['prenom1'][$i].' '.$ancetres ['prenom2'][$i].' '.$ancetres ['prenom3'][$i].' '.$ancetres ['nom'][$i],'');
		if ($temp > $larg_nom) {$larg_nom = $temp;}
		$temp = largeur_cellule (affichage_date($ancetres['date_naiss'][$i]),'B');
		if ($temp > $larg_date_naiss) {$larg_date_naiss = $temp;}
		$temp = largeur_cellule ($ancetres['lieu_naiss'][$i],'');
		if ($temp > $larg_lieu_naiss) {$larg_lieu_naiss = $temp;}
		$temp = largeur_cellule (affichage_date($ancetres['date_maria'][$i]),'B');
		if ($temp > $larg_date_maria) {$larg_date_maria = $temp;}
		$temp = largeur_cellule ($ancetres['lieu_maria'][$i],'');
		if ($temp > $larg_lieu_maria) {$larg_lieu_maria = $temp;}
		$temp = largeur_cellule (affichage_date($ancetres['date_deces'][$i]),'B');
		if ($temp > $larg_date_deces) {$larg_date_deces = $temp;}
		$temp = largeur_cellule ($ancetres['lieu_deces'][$i],'');
		if ($temp > $larg_lieu_deces) {$larg_lieu_deces = $temp;}

		if ($ancetres['generation'][$i] != $old_gen)
		{	$indiv['nb'][] = $nb_indiv_dans_gen;
			$gen = $i_indiv + 1;
			$indiv['gen'][] = $gen;
			$nb_indiv_dans_gen = 0;
			$i_indiv++;
		}
		
		$old_gen = $ancetres['generation'][$i];
		$nb_indiv_dans_gen++; 
	}
	$indiv['nb'][$i_indiv] = $nb_indiv_dans_gen;
	$gen = $i_indiv + 1;
	$indiv['gen'][] = $gen;

	$pdf->SetFillColor(255);
	$old_generation = "";
	for ($i = 0; $i < $nb_ancetres; $i++ )
	{	if ($ancetres ['generation'][$i] !== $old_generation)
		{	$temp = $ancetres['generation'][$i];
			$pdf->SetFont('DejaVu','',10);
			$pdf->Cell($larg_sosa_d+$larg_nom+$larg_date_naiss+$larg_lieu_naiss+$larg_date_maria+$larg_lieu_maria+$larg_date_deces+$larg_lieu_deces,4,'Generation '.$temp,"TB",1,"L",1);
			$pdf->SetFont('DejaVu','',8);
			$old_generation = $ancetres ['generation'][$i];
		}

		$pdf->SetFont('Arial','',8);
		$pdf->cell ($larg_sosa_d,3,$ancetres ['sosa_d'][$i],"LR",0,"L",1);		// edition finale de la cellule
		$pdf->edition_cellule ($ancetres ['prenom1'][$i].' '.$ancetres ['prenom2'][$i].' '.$ancetres ['prenom3'][$i].' '.$ancetres ['nom'][$i],$ancetres ['sexe'][$i],$larg_nom,0);
		$pdf->edition_cellule (affichage_date($ancetres ['date_naiss'][$i]),'B',$larg_date_naiss,0);
		$pdf->edition_cellule ($ancetres ['lieu_naiss'][$i],'',$larg_lieu_naiss,0);
		$pdf->edition_cellule (affichage_date($ancetres ['date_maria'][$i]),'B',$larg_date_maria,0);
		$pdf->edition_cellule ($ancetres ['lieu_maria'][$i],'',$larg_lieu_maria,0);
		$pdf->edition_cellule (affichage_date($ancetres ['date_deces'][$i]),'B',$larg_date_deces,0);
		$pdf->edition_cellule ($ancetres ['lieu_deces'][$i],'',$larg_lieu_deces,1);
	}

	$pdf->SetFont('DejaVu','',12);
	$pdf->Cell(0,3,"",0,1,"L",1);	
	$pdf->Cell(0,3,$nb_ancetres." ".$got_lang['Ancet'],0,1,"L",1);	

//	$pdf->Addpage();

//	bar_horiz($indiv['gen'],$indiv['nb'],$got_lang['ArAsc'],$entete1,"stat_asc",$i_indiv,"YES","VERTICAL"); desactive temporairement
//	$pdf->Image('stat_asc'.'.png',70,30);
//	unlink('stat_asc'.'.png');

	$pdf->Output();
} 
elseif ($_REQUEST['itype'] == "excel")
{

	header('Content-type:application/vnd.ms-excel');
	header('Content-Transfer-Encoding: binary');
	header('Content-Disposition: attachment; filename="GeneoTree_Ascendancy_'.$_REQUEST['ibase'].'.csv"');

	$ancetres[][] = '';$cpt_generations = 0;
	$ancetres['id_indi'] [0] = $_REQUEST['id'];
	recup_ascendance ($ancetres,0,40,'ME_G');
	integrer_implexe(40,'ME_G');
	
	afficher_ascendance('Excel');
}
?>