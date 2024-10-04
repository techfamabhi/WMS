<?php
// restSrv.php - REST service, convert array to json and send via curl
/*
 07/21/22 dse add master server processor

 pass in url and array of fldnames=>values

$f=array("action"=>"fetchall",
"numRows"=>10,
"startRec"=>1,
"company"=>1,
"custname"=>"");
$rc=restSrv("http://localhost/wms/servers/PICK_srv.php",$f);
echo $rc; // echo's json
// or 
echo "<pre>";
print_r(json_decode($rc,true)); // dumps array
*/

function restSrv($url,$fields)
{
 $srvName=basename($url);
 $json="";
 if (!is_array($fields)) return false;
 if (count($fields))
 {
  //if (get_cfg_var('wmsdir') and $srvName <> "WMS_srv.php")
  //{
   //$wmsDir=get_cfg_var('wmsdir');
   //$s=basename($wmsDir);
   //$srv="http://localhost/{$s}/servers/WMS_srv.php";
   //$fields["serverName"]=$srvName;
  //$w=json_encode($fields);
//echo "<pre> sending to {$srv}\n{$url}\n{$w}</pre>";
   ////$url=$srv;
  //} // wmsdir is set

  $w=json_encode($fields);
  if ($w) $json=$w;
 } // end count fields

 $ch = curl_init();
 curl_setopt($ch, CURLOPT_URL, $url);
 curl_setopt($ch, CURLOPT_POST, 1);
 curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
 curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
 curl_setopt ($ch, CURLOPT_SSL_VERIFYPEER, 0);
 curl_setopt ($ch, CURLOPT_SSL_VERIFYHOST, 0);
 $result = curl_exec($ch);
 curl_close($ch);
 return $result;
}
?>
