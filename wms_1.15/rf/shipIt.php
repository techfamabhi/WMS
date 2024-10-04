<?php
/* ship it
returns ;
status = false, unpicked items or order not found
         true, if shipped
msg    = message

*/

function shipIt($comp,$hostOrdernum,$PickSRV,$ShipSRV)
{
 $ret=array();
 $ret["status"]=false;
 $ret["msg"]="";

 $req=array("action"=>"fetchOrder",
  "company"=>$comp,
  "scaninput"=>$hostOrdernum,
  "process"=>"PACK"
   );
 $rc=restSrv($PickSRV,$req);
 $w=(json_decode($rc,true));
 if (isset($w["Order"]))
 { // display Order and Tote Info
  if ($w["Order"]["spec_order_num"] == "1")
   {
    $ret["msg"]="There are Special Orders or Sourced Parts on the Order";
    return $ret;
   }
  $host_order_num = $w["Order"]["host_order_num"];

  if ($w["Order"]["order_num"] > 0)
  {
   $order_num=$w["Order"]["order_num"];
   $hostOrder=$w["Order"]["host_order_num"];
   // check unpicked
   if (count($w["unPicked"]) < 1)
   {
    $contr=0;
    if (isset($w["Totes"]) and count($w["Totes"]) > 0)
    {
     // release all the totes into same container and ship it
     foreach ($w["Totes"] as $t)
     {
      $req=array("action"=>"releaseTote",
    		"order_num" => $order_num,
    		"comp"=> $comp,
    		"toteId"=> $t["tote_num"],
    		"container"=> $contr
     		);
      $rc=restSrv($ShipSRV,$req);
      $rdata=(json_decode($rc,true));
      if (isset($rdata["Container"])) $contr=$rdata["Container"];
     } // end foreach tote
  
    } // end there are totes
    $req=array("action"=>"Ship",
    "order_num" => $order_num,
    "comp"=> $comp,
    "override"=>"1"
     );
    $rc=restSrv($ShipSRV,$req);
    $rdata=(json_decode($rc,true));
    if (isset($msg)) $msg="";
    $ret["status"]=true;
    if ($contr > 0) $ret["msg"]="{$hostOrder} Shipped in Container {$contr}";
   } // end unpicked < 1
   else
    $ret["msg"]="There are Unpicked Parts on the Order";
  } // order number is set
   else
    $ret["msg"]="Order Not Found";
 } // display Order and Tote Info
 return $ret;
} // end shipIt

?>
