<?php
// $Id: get_contrl.php,v 1.2 2021/12/31 21:58:42 root Exp $
// $Source: /usr1/include/RCS/get_contrl.php,v $

function get_contrl($db,$company,$ctrl_key)
{
 $ctrl_num=0;
/***************************** Get Control Number *****************************/
/* Get Lock and next number*/
        //$db->Trans("B"); // start transaction
        //$SQL =<<<SQL
//UPDATE CONTROL SET control_number = control_number
//WHERE control_key ="{$ctrl_key}"
  //AND control_company ={$company}
//
//SQL;
//echo "<pre>";
        //$rc=$db->query($SQL);

        $SQL=<<<SQL
SELECT control_number,control_maxnum,control_reset_to
FROM CONTROL 
WHERE control_key ="{$ctrl_key}"
  AND control_company ={$company}
FOR UPDATE

SQL;
//echo "{$SQL}\n";
	$rc=$db->query($SQL);
	$numrows=$db->num_rows();

	$i=1;
	while ($i <= $numrows)
	{
	 $db->next_record();
	     if ($db->f("control_number"))
	     {
        	$ctrl_num = $db->f("control_number") + 1;
        	$ctrl_max = $db->f("control_maxnum");
        	$ctrl_reset = $db->f("control_reset_to");
        	if ($ctrl_num > $ctrl_max) { $ctrl_num = $ctrl_reset; }
	       } // db f(control_number)
	$i++;
	 } // while i < numrows


     if ($ctrl_num) {
        $SQL =<<<SQL
UPDATE CONTROL SET control_number = {$ctrl_num}
WHERE control_key ="{$ctrl_key}"
  AND control_company ={$company}

SQL;
//echo "{$SQL}\n";
        //$db->Trans("C"); //commitstart transaction
//      $SQL="UNLOCK TABLES";
     $rc=$db->Update($SQL);
     }
//    $db->free_result();
//echo "ctrl_num={$ctrl_num}\n";
return $ctrl_num;
}
?>
