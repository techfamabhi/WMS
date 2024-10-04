<?php
//vsrch.php function to create vendor search screen
//wont work unless I use javascript to populate table from json
//because of the search

// Session must be started prior to calling
/*TODO

*/

function vsrch(
)
{
} // end vsrch

$surl="http://localhost/wms/servers/sVendor.php?stype=j";

$venInfo="";

$jsh=<<<HTML
<style>
.btn {
  width: 95px;
}
label {
 font-size: 1.2em;
}
</style>

HTML;
$jsb=<<<HTML
<script>
 document.getElementById('scaninput').focus();

function movetoend(fld)
{
 var val=fld.value;
 fld.value='';
 fld.value=val;
 return(true);
}
function do_submit()
{
 document.form1.submit();
}
</script>

HTML;

$title="Vendor Select";
$thisprogram=$_SERVER["SCRIPT_NAME"];

else $scaninput="";
else $func="";
else $msg="";
else $msgColor="";
else $vendor="";
if ($func == "selectVend") $vendor=$scaninput;
$htm="";
if ($scaninput <> "" and $vendor == "") $vendor=$scaninput;
if ($msg <> "") $pg->msg=$msg;
if ($msgColor <> "") $pg->msgColor=$msgColor;
if ($scaninput == "ClEaR")
{
 $func="";
 $vendor="";
}
$vendor=strtoupper($vendor);
//echo "vendor={$vendor}\n";
$url="{$surl}&vendor={$vendor}%";
$x=file_get_contents($url);
$vend=json_decode($x,true);
if ($vendor == "")
{
 $_SESSION["rf"]["function"]="RPO";
 $_SESSION["rf"]["vend"]=$vend;
} // end vendor <> ""
else
{ // vendor is empty
//Vendor input
//get vendors
$url="{$surl}&vendor={$vendor}%";
$x=file_get_contents($url);
$vend=json_decode($x,true);
$detail="";
$venInfo=<<<HTML
<div class="table-responsive">
      <form name="form2" action="/wms/rf/recv_po.php" method="get">
      <input type="hidden" name="func" value="selectVend">
      <table class="table table-bordered table-striped">
       <tr>
        <th class="FieldCaptionTD">Vendor</th>
        <th class="FieldCaptionTD">Name</th>
       </tr>
_DETAIL_
      </table>
      </form>
     </div>

HTML;
 if (count($vend) > 0)
 {
  foreach ($vend as $key=>$data)
  {
   $v=$data["vendor"];
   $detail.=<<<HTML
       <tr>
        <td>
        <a href="recv_po.php?func=vendor&scaninput={$data["vendor"]}" class="btn btn-success" target="_self"> {$v}</a>
</td>
        <td>{$data["name"]}</td>
       </tr>

HTML;
  } // end foreach vend
 $venInfo=str_replace("_DETAIL_",$detail,$venInfo);
 } //end count vend > 0
} // end vendor is empty

$body=<<<HTML
 <form name="form1" action="{$thisprogram}" method="get">

  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-container w3-blue w3-padding-8">
        <div class="w3-clear"></div>
                <label>Vendor</label>
<input class="w3-white" onchange="do_submit();" id="scaninput" name="scaninput" placeholder="Enter Vendor" value="{$vendor}" onfocus="movetoend(this);">
<br>

      </div>
{$venInfo}
    </div>
<br>
  </div>
 </form>

HTML;

$pg->Display();
?>
