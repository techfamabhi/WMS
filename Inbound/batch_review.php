<?php
// 07/18/24 dse add read of opt 9104 and warn user to make sure po is not locked

/*TODO
if Adjust qty,  
if select distinct paud_inv_code from PARTHIST where paud_id = {internal po#} and paud_shadow = {shadow#}  <> "-"

to do that I must;
1) set qty to diff from old qty to new qty
2) update WHSEQTY
3) update WHSELOC
4) insert into PARTHIST
5) adjust RCPT_SCAN qty

if adjust bin, do two transactions,
   1) 1 take it out like above for the old bin or tote,
   2) put it back in on the new bin or tote like above
*/
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);


session_start();
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";
$thisprogram = $_SERVER["SCRIPT_NAME"];
$returnTo = $thisprogram;
if (isset($_REQUEST["sorter"])) $sorter = $_REQUEST["sorter"]; else $sorter = "";
if (isset($_REQUEST["sortDir"])) $sortDir = $_REQUEST["sortDir"]; else $sortDir = "";
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/wr_log.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/restSrv.php");
require_once("{$wmsInclude}/get_option.php");
$SRV = "http://{$wmsIp}{$wmsServer}/RcptLine.php";

if (!isset($comp)) $comp = 1;

$db = new WMS_DB;

$opt[28] = get_option($db, $comp, 28);
$opt[9104] = get_option($db, 0, 9104);

$showMerge = false;
if (isset($func) and $func == "merge") { // merge these batches to 1
    $j = strpos($batch_num, ",");
    if ($j > 0) {
        $k = explode(",", $batch_num);
        require_once("mergeBatchs.php");
        $rc = mergeBatchs($db, $k);
        unset($func);
    } // j > 0
//echo "<pre>";
//print_r($_REQUEST);
//exit;
} // merge these batches to 1
if (isset($func) and $func == "final") { // finalize batch and close modal
    if (isset($finalize) and $finalize > 0) { // finalize the batch
        $msg = "";
        $RESTSRV = "http://{$wmsIp}{$wmsServer}/WMS2ERP.php";
        $req = array("action" => "flagRcptDone",
            "batch_num" => $finalize,
            "comp" => $comp
        );
        $ret = restSrv($RESTSRV, $req);
        $rdata = (json_decode($ret, true));
        // if flaging is ok, receive the batch
        if (!isset($rdata["error"])) {
            $req = array("action" => "Receive",
                "batch_num" => $finalize,
                "comp" => $comp
            );
            $ret = restSrv($RESTSRV, $req);
            $rc = (json_decode($ret, true));
            if ($rc > 0) $msg = "Batch Released Successfully";
        } // end no error
        else {
            $msg = $rdata["message"];
        } // an error occurred

        $htm = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1.5">
<script>
 parent.cancel_modal();
</script>
</head>
 <body>
  <h1>{$msg}</h1>
 </body>
</html>
HTML;
        echo $htm;
        exit;

    } // finalize the batch
} // finalize batch and close modal
$batch = "";
if ($batch_num <> "") $batch = $batch_num;
if ($batch <> "") {
    $req = array("action" => "getBatchDetail",
        "batch" => $batch,
        "comp" => $comp
    );
    $ret = restSrv($SRV, $req);
    $rdata = (json_decode($ret, true));
    //print_r($rdata);

    if (!isset($nh)) $nh = 0;
} // end batch > 0

//temp
$main_ms = 1;
$sounds = "../assets";

$pg = new displayRF;
$pg->viewport = "1.0";
$pg->dispLogo = false;
$pg->jsh = <<<HTML
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script src="/jq/vue_2.6.14_min.js"></script>
<script src="/jq/axios.min.js"></script>
<script>
shortcut.add("F1",function() {
        do_reset();
});

function showHide(inflag)
{
 if (inflag) document.getElementById('editRcpt').style.display="block";
 else document.getElementById('editRcpt').style.display="none";
}
function editItem(batch,line)
{
 showHide(true);
 application.fetchData(batch,line);
 //alert(batch + ' ' + line);
}
function updData()
{
 showHide(false);
 application.submitData();
 //alert(batch + ' ' + line);
}
function doFinal()
{
  var ok=confirm("Please make sure the Purchase Order is not Locked in your {$opt[9104]} system");
 if (ok)
 {
 var fnc=document.form1.func;
 if (fnc.value !== "modQty") document.form1.func.value="final";
 }
 else return false;

 //var fnc=document.form1.func;
 //if (fnc.value !== "modQty") document.form1.func.value="final";
 
}

function doMerge(po,batch)
{
 document.form1.func.value="merge";
 document.form1.submit();
}

  function cancel_modal() {
    var modal = document.getElementById("myModal");
    document.getElementById('modalFrame').src = "";
    modal.style.display = "none";
    document.form1.submit();
    //do_refresh();
}

function showDetail(item){
 var ijson=JSON.parse(rjson);
 var i1=ijson[item];
 var i2=JSON.stringify(i1);

 var url='editBatch.php?json=' + i2;
 setframe(url);
  }
function setframe(ifr) {
     var modal = document.getElementById("myModal");
     document.getElementById('modalFrame').src = ifr;
     modal.style.display = "block";
    }

</script>

<style>
html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
.binbutton {
    background-color: #2196F3;
    border: none;
    color: white;
    padding: 15px 32px;
    text-align: center;
    text-decoration: none;
    display: inline-block;
    font-size: 20px;
    margin: 4px 2px;
    cursor: pointer;
}
.binbutton:disabled {
    background-color: #dddddd;
}
.binbutton:enabled {
    background-color: #2196F3;
}
label {
 font-size: 1.2em;
}
strong {
 font-size: 1.2em;
}

/* The Modal (background) */
.modal {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  /* left: calc( 0.25rem + 20.5% ); */
  top: 20px;
  width: 78%;
  height: 100%;
  overflow: auto; /* Enable scroll if needed */
  background-color: #fefefe;
}

/* Modal Content */
.modal-content {
  background-color: #fefefe;
  margin: auto;
  padding: 0px;
  border: 0px solid #888;
  width: 88%;
  height: 90%;

}

/* The Close Button */
.close {
  color: dodgerblue;
  float: right;
  position: relative;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
/*
.close:focus {
  color: #000;
  text-decoration: none;
  cursor: pointer;
}
*/

</style>

HTML;

$receipt = $rdata;
//echo "<pre>batch={$batch_num}\n";
//print_r($receipt);
//exit;

$j = strpos($batch_num, ",");
$showMerge = false;
$merge = "";
if ($j > 0) {
    $showMerge = true;
    $k = json_encode(explode(",", $batch_num), true);
    $merge = <<<HTML
<span style="float:right">
<button name="merge" onclick="doMerge({$po},{$batch_num});" value="{$batch_num}" class="btn btn-info"><i class="fa fa-merge" aria-hidden="true" ></i> Merge</button>
 </span>
HTML;
}

if (!isset($msg)) $msg = "";
$ptitle = "Received so Far";
if (isset($batch_num)) $ptitle = "PO: {$po} -- Batch: {$batch_num} Detail";
$button = <<<HTML
<button name="goBack" onclick="javascript:parent.cancel_modal();" class="btn btn-info btn-xs">Close </button>
HTML;

$body = <<<HTML
<!-- The Modal -->
 <div id="myModal" class="modal">
  <!-- Modal content -->
  <div class="modal-content">
    <span class="close" onclick="cancel_modal();">&times;</span>
    <iframe id="modalFrame" width="100%" height="100%" border="0"></iframe>
  </div>
 </div>
<div class="panel panel-default">
 <div class="panel-heading">
  <div class="messg">{$msg}</div>
 </div>
 <br />
 <div class="row">
  <div class="col-md-4">
       <h3 class="panel-title">
{$button}
{$ptitle}
</h3>
  </div>
 </div>
 </div>
  <div id="updMessg" class="messg"></div>
   <div class="panel-body">
    <div class="table-responsive">
      <form name="form1" action="{$thisprogram}" method="GET">
      <input type="hidden" name="sorter" id="sorter" value="{$sorter}">
      <input type="hidden" name="sortDir" id="sortDir" value="$sortDir">
      <input type="hidden" name="func" id="func" value="">
      <input type="hidden" name="po" id="po" value="{$po}">
      <input type="hidden" name="nh" id="nh" value="{$nh}">
      <input type="hidden" name="moditem" id="moditem" value="">
      <input type="hidden" name="modoqty" id="modoqty" value="">
      <input type="hidden" name="modnqty" id="modnqty" value="">
      <input type="hidden" name="modjson" id="modjson" value="">
      <input type="hidden" name="batch_num" id="batch_num" value="{$batch_num}">
      <table class="table table-bordered table-striped overflow-auto">
       <thead>
        <tr>
         <th nowrap class="FieldCaptionTD">Batch#</th>
         <th nowrap class="FieldCaptionTD">PO#</th>
         <th nowrap class="FieldCaptionTD">P/L</th>
         <th nowrap class="FieldCaptionTD">Part Number</th>
         <th nowrap class="FieldCaptionTD">Description</th>
         <th nowrap class="FieldCaptionTD">Qty Ord</th>
         <th nowrap class="FieldCaptionTD">Prev Recvd</th>
         <th nowrap class="FieldCaptionTD">This Recvd</th>
         <th nowrap class="FieldCaptionTD">Stocked</th>
         <th nowrap class="FieldCaptionTD">Tote/Bin</span></th>
         <th nowrap class="FieldCaptionTD">Open Qty</th>
         <th nowrap class="FieldCaptionTD">&nbsp;</th>
        </tr>
       </thead>

HTML;
//echo "<pre>";
//print_r($receipt);
//exit;
$obatch = 0;
$nbatch = 0;
$brecvd = 0;
$bstock = 0;
if (count(isset($receipt) ? $receipt : []) > 0) {
    $rjson = json_encode($receipt);
    $body .= <<<HTML
<script>
 var rjson='{$rjson}';
</script>
HTML;
    foreach ($receipt as $line => $item) {
        if (is_numeric($line)) {
            $ocls = "";
            $totalPrec = 0;
            $onclick = "";
            $nbatch = $item["batch_num"];
            if ($obatch == 0) $obatch = $nbatch;
            if ($obatch <> $nbatch) { // print Finalize button
                $body .= addFinalize($nbatch, $brecvd, $bstock);
                $obatch = $nbatch;
                $brecvd = 0;
                $bstock = 0;
            } // print Finalize button

            $brecvd = $brecvd + $item["thisRecvd"];
            $bstock = $bstock + $item["Stocked"];
            $rcls = "";
            $scls = "";
            $tQty = $item["thisRecvd"];
            $pQty = $item["prevRecvd"];
            $qty_rec = $tQty + $pQty;
            $qty_ord = $item["qty_ord"];
            $qty_stocked = $item["Stocked"];
            if ($qty_rec > 0) $rcls = "Alt2DataTD";     // red over-recvd
            if ($qty_rec == 0) $rcls = "Alt3DataTD";      // if set to gray, not recvd
            if ($qty_rec < 0) $rcls = "bg-danger";     // they recvd negative, different red
            if ($qty_rec > $qty_ord) $rcls = "AltDataTD"; // yellow overage
            if ($qty_rec == $qty_ord) $rcls = "";          // if all correct, no color
            if ($qty_stocked < $tQty) $scls = "Alt5DataTD";
            if ($qty_stocked == $tQty) $scls = "Alt4DataTD";
            if ($qty_stocked > $tQty) $scls = "Alt7DataTD";
            $ocls = "";
            if ($item["OpenQty"] < 0) $ocls = "Alt2DataTD";
            $ii = json_encode($item);
            $edit = "&nbsp;";
            if ($spriv_thru >= $opt[28]) {
                //<button class="btnlink" style="border: none;" name="edit" onclick="showDetail({$line});"><img src="{$wmsImages}/edit2.png" border="0" title="Edit this Record"></button>
//<img src="{$wmsImages}/edit2.png" style="border: none;" onclick="showDetail({$line});" title="Edit this Record">
                $edit = <<<HTML
<img src="{$wmsImages}/edit2.png" style="border: none;" onclick="showDetail({$line});" title="Edit this Record">
HTML;
            } // end edit priv
// <span id="tRd_{$line}" onclick="modQty({$line});" style="display: block">{$item["thisRecvd"]}</span>
            $body .= <<<HTML
        <tr {$onclick}>
         <td align="right">{$item["batch_num"]}</td>
         <td align="right">{$item["host_po_num"]}</td>
         <td>{$item["p_l"]}</td>
         <td>{$item["part_number"]}</td>
         <td>{$item["part_desc"]}</td>
         <td align="right">{$item["qty_ord"]}</td>
         <td align="right">{$item["prevRecvd"]}</td>
         <td align="right" class="{$rcls}">{$item["thisRecvd"]}</td>
         <td {$ocls} align="right" class="{$scls}">{$item["Stocked"]}</td>
         <td nowrap>{$item["Tote"]}</td>
         <td align="right" class="{$ocls}">{$item["OpenQty"]}</td>
         <td> {$edit}</td>
        </tr>

HTML;
        } // end line is numeric
    } // end foreach receipt
    $body .= addFinalize($nbatch, $brecvd, $bstock);
} // count receipt > 0
if (!$showMerge) $merge = "";
$body .= <<<HTML
       </table>
      </form>
{$merge}
    </div>
   </div>
 <script>
  function modQty(item,e)
  {
   if(e.keyCode === 13){ e.preventDefault(); }
   var ijson=JSON.parse(rjson);
   var i1=ijson[item];
   var i2=JSON.stringify(i1);
   document.getElementById('moditem').value=item;;
   document.getElementById('modjson').value=i2;;
   document.getElementById('modoqty').value=i1["thisRecvd"];
   document.getElementById('modnqty').value=document.getElementById('thisRecvd').value;
   document.form1.func.value="modQty";
 //  var nam="tRd_" + item;
   //var tRd=document.getElementById(nam);
   //var old=tRd.innerHTML;
   //var nam="tRe_" + item;
   //var tRe=document.getElementById(nam);
   //if( tRd && tRd.style.display == 'block')    
        //tRd.style.display = 'none';
    //else 
        //tRd.style.display = 'block';
   //if( tRe && tRe.style.display == 'block')    
        //tRe.style.display = 'none';
    //else 
        //tRe.style.display = 'block';
 //alert(JSON.stringify(i1));
document.form1.submit();
  }
 
 </script>

HTML;
$pg->body = $body;
//$buttons=loadButtons("SubMit|clrbtn|complete|review");
//$pg->body.=$buttons;
$pg->Bootstrap = true;
$pg->title = "Review Receipt";
if (isset($nh) and $nh) $pg->noHeader = true;
$pg->Display();
echo "</body>\n</html>";

function addFinalize($batch, $brecvd, $bstock)
{
    global $showMerge;
    if ($brecvd == $bstock) {
        $ret = <<<HTML
        <tr>
        <td align="right" colspan="11">
<button name="finalize" onclick="doFinal();" value={$batch} class="btn btn-info"><i class="fa fa-flag-checkered" aria-hidden="true" ></i> Finalize</button>
        </td>
        </tr>
HTML;
    } else {
        $showMerge = false;
        $ret = <<<HTML
        <tr>
        <td align="left" colspan="11">
<h4>Putaway not Completed, Received {$brecvd}, Putaway {$bstock}</h4>
        </td>
        </tr>
HTML;
    }
    return $ret;

} // end add Finalize


?>
