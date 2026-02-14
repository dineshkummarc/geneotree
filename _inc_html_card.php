</td>

<td style="vertical-align:top; display:flex; flex-direction:column;">  <!--  display:flex and flex-direction:column authorize width:100% and align:center with span tag -->

<table><tr><td>

  <table>
  <tr><td> 
<!-- print icon -->
  <div id=DivCardPrint></div>
  </td><td>
<!-- change de-cujus button -->
  <button id="openModalBtn" style="display:none;"><?php echo $got_lang['IBCuj']?></button>
  </td>
  </tr><tr>
  <td colspan=2>
  <div id="modal" class="modal ligne_tr2" style="width:100%;">
	<!--<div class="modal-content" role="dialog" aria-labelledby="modalTitle" aria-describedby="modalDescription">-->
	<div class="modal-content" role="dialog" style="width:100%;">
		<p id="modalTitle"><?php echo $got_lang['MesCu'];?></p>
		&emsp;&emsp;<button id="yesBtn">Oui</button>
		&emsp;<button id="noBtn">Non</button>
	</div>
  </div>
  </td></tr></table>

</td></tr></table>

<!-- special table to size index card to 500 px -->
<table><colgroup><col style="width: 500px;"></colgroup>
<tr><td style="vertical-align:top; display:flex; flex-direction:column;">

<!-- Card menu -->
<span id=DivCardName></span>
<div id="menu" class="menu" style="display:none; z-index:1; position:relative; left:50px; margin:0px; padding:0px; width:260px;">
  <a href=# id="link1"><img src=themes/fleche_milieu.png heigth=20 width=20> <?php echo $got_lang['ArMix'];?></a>
  <a href=# id="link2"><img src=themes/fleche_haut.png heigth=20 width=20><?php echo $got_lang['ArAsc'];?></a>
  <a href=# id="link3"><img src=themes/fleche_bas.png heigth=20 width=20><?php echo $got_lang['ArDes'];?></a>
  <?php
  if ($_REQUEST['csg'] == 'O')
  {    echo '<a href=#  id="link4"><img src=themes/fleche_consang.png heigth=20 width=20> '.$got_lang['EtCon'].'</a></li>';
  }?>
</div>

<span id=DivCard></span>

<!-- map  -->
<span id=headerMap></span>
<span id=map></span>

<?php
// Parents, Spouse, Children, Siblings
  $labels = array
  ('Parents'
  ,'Spouse'
  ,'Children'
  ,'Siblings'
  );
  foreach ($labels as $label) 
  {  echo  '
     <span  id=Div'.$label.' style="font-size: 1.2em;font-weight: bold;text-align:left;"></span>
     <table id=Tab'.$label.' class="bord_haut bord_bas"">
        <thead id=Tab'.$label.'-header class="titre_col ligne_tr2"></thead>
        <tbody id='.$label.'-body></tbody>
     </table>';
  }

// Events
  $labels = array
  ('Source0'
  ,'Source1'
  ,'Source2'
  ,'Source3'
  ,'Source4'
  ,'Source5'
  ,'Source6'
  ,'Source7'
  ,'Source8'
  ,'Source9'
  ,'Source10'
  ,'Source11'
  ,'Source12'
  ,'Source13'
  ,'Source14'
  ,'Source15'
  ,'Source16'
  ,'Source17'
  ,'Source18'
  ,'Source19'
  ,'Source20'
  ,'Source21'
  ,'Source22'
  ,'Source23'
  ,'Source24'
  ,'Source25'
  );
  foreach ($labels as $label) 
  {
   echo  '
	 <span id=Div'.$label.'Line></span>
	 <span id=Div'.$label.' class=ligne_tr2 style="text-align:left; width=100%;"></span>
	 ';
  }

// Cousins
  $labels = array
  ('PetEf'
  ,'Oncle'
  ,'Neveu'
  ,'Germa'
  ,'OncGr'
  ,'CouGr'
  ,'CouIs'
  );
  foreach ($labels as $label) 
  {  echo  '
     <span id=Div'.$label.' style="font-size: 1.3em;font-weight: bold;text-align:left;"></span>
     <table id="Tab'.$label.'" class="bord_haut bord_bas"">
        <thead id="Tab'.$label.'-header" class=titre_col></thead>
        <tbody id="'.$label.'-body"></tbody>
     </table>';
  }
?>
</td></tr></table>

    </td>
  </tr></table>
</BODY>
</HTML>