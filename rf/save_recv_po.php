<?php
//04/23/19 dse move bin validation to tmp_bin_xref instead of WHSELOC

/*TODO

Abandoned because I couldn't get the onchange event to work properly
when dynamically adding more PO's with javascript.

Reverting back to setting some vars and submitint the form and let
php add the more PO fields

Once Part is scanned, display;
PO Qty Ordered,
Qty all ready received
last bin (if possible)
need to figure which PO a part pertains to and pass and store until saved

 add company to PO search

Chrome has a problem with autoplay, haven't been able to correct it.


//toDo  Correct qty to be qty 1, add span with total qty extended to eaches

screens needed;
x	Invalid Part (red)
x	Multiple parts found (yellow)
	Good Part Found with open POs (green)
	Good Part Found, but no Open PO's (yellow)
	Review
*/
//if (isset($_REQUEST["scaninput"])) $scaninput=trim($_REQUEST["scaninput"]); else $scaninput="";
//error_reporting(0);

session_start();
echo "<pre>";
print_r($_REQUEST);
echo "</pre>";

//temp
$main_ms = 1;
$comp = 1;
if (isset($_SESSION["REQDATA"])) {
    $w = "";
    if (isset($_SESSION["REQDATA"]["ts"])) $w = $_SESSION["REQDATA"]["ts"];
    if (isset($_REQUEST["ts"])) $w1 = $_REQUEST["ts"]; else $w1 = time();
    //if ($_REQUEST == $_SESSION["REQDATA"])
    if ($w == $w1) { // looks like they refreshed the screen
        if (isset($_REQUEST["func"]) and $_REQUEST["func"] == "scanBin") { // reset back to scan the part again
            $r = setRequest($_REQUEST["comp"], $_REQUEST["vendor"], $_REQUEST["thisprogram"], $w);
            $r["msg"] = "Cancelled, please re-scan Part";
            $_REQUEST = $r;
            unset($r);
            unset($w);
            unset($w1);
        } // reset back to scan the part again
    } // looks like they refreshed the screen
} // end REQDATA is set
$_SESSION["REQDATA"] = $_REQUEST;

if (isset($_REQUEST["func"]) and $_REQUEST["func"] == "vendSrch") {
    $htm = <<<HTML
<html>
 <head>
 </head>
 <body onload="document.form1.submit();">
  <form name="form1" action="vendSrch.php" method="get">

HTML;
    foreach ($_REQUEST as $key => $val) {
        $htm .= <<<HTML
   <input type="hidden" name="{$key}" value="{$val}">

HTML;
    } // end foreach request
    $htm .= <<<HTML
  </form>
 </body>
</html>

HTML;
    echo $htm;
    exit;
} // end vendSrch clicked

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
if (isset($func) and $func == "Cancel") {
    $r = setRequest($comp, $vendor, $thisprogram, $w);
}

require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/get_table.php");
require_once("{$wmsInclude}/cl_addupdel.php");
require_once("{$wmsInclude}/cl_inv.php");
require_once("{$wmsInclude}/wr_log.php");
require_once("{$wmsInclude}/get_option.php");

require_once("ddform.php");
require_once("cl_PARTS2.php");
require_once("{$wmsInclude}/db_main.php");
if (!function_exists("get_contrl")) {
    require_once("{$wmsInclude}/get_contrl.php");
}
require_once("{$wmsInclude}/chklogin.php");

$sounds = "../assets";
$thisprogram = $_SERVER["SCRIPT_NAME"];

if (isset($_REQUEST["scaninput"])) $scaninput = trim($_REQUEST["scaninput"]); else $scaninput = "";
if (isset($_REQUEST["func"])) $func = $_REQUEST["func"]; else $func = "";
if (isset($_REQUEST["UPC"])) $UPC = $_REQUEST["UPC"]; else $UPC = "";
if (isset($_REQUEST["comp"])) $comp = $_REQUEST["comp"]; else $comp = $main_ms;
if (isset($_REQUEST["msg"])) $msg = $_REQUEST["msg"]; else $msg = "";
if (isset($_REQUEST["msgColor"])) $msgColor = $_REQUEST["msgColor"]; else $msgColor = "";
if (isset($_REQUEST["vendor"])) $vendor = $_REQUEST["vendor"]; else $vendor = "";
if (isset($_REQUEST["POs"])) $POs = $_REQUEST["POs"]; else $POs = array();
if (isset($_REQUEST["HPO"])) $HPO = $_REQUEST["HPO"]; else $HPO = array();
if (isset($_REQUEST["lookPO"])) $lookPO = $_REQUEST["lookPO"]; else $lookPO = 0;
if (isset($_REQUEST["recvType"])) $recvType = $_REQUEST["recvType"]; else $recvType = "1";
if (isset($_REQUEST["recvTo"])) $recvTo = $_REQUEST["recvTo"]; else $recvTo = "b";
if ($func == "vendor") $vendor = $scaninput;

$db = new WMS_DB;
$opt[21] = get_option($db, $comp, 21);
if (trim($opt[21]) == "") $opt[21] = "PO";
$pg = new displayRF;
$pg->viewport = "1.10";
$pg->dispLogo = false;
$pg->title = "Receive PO(s)";
$pg->jsh = <<<HTML
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
shortcut.add("F1",function() {
        do_reset();
});
shortcut.add("return",function() {
        return false ;
});
</script>

HTML;
$pg->jsb = <<<HTML
<script>
 if (document.form1.func.value == 'vendor')
  {
   if (typeof document.form1.scaninput !== 'undefined') 
    { 
     document.form1.scaninput.focus(); 
     document.form1.scaninput.click();
    }
   else
   if (typeof document.form1.poNumber !== 'undefined') 
    { 
     document.form1.poNumber.focus(); 
    }
  } 
 else 
 {
   if (typeof document.form1.scaninput !== 'undefined') { document.form1.scaninput.focus(); }
   else
   if (typeof document.form1.poNumber !== 'undefined') { document.form1.poNumber.focus(); }
 }

function do_bin()
{
 document.form1.submit();
}
function do_reset()
{
 document.form1.scaninput.style.display='none';
 document.form1.scaninput.value="ClEaR";
 document.form1.submit();
}
function do_resetp()
{
 document.form1.scaninput.style.display='none';
 document.form1.scaninput.value="newPart";
 document.form1.submit();
}
function do_complete()
{
 document.form1.scaninput.style.display='none';
 document.form1.scaninput.value="complete";
 document.form1.submit();
}
function do_review(rcpt)
{
 document.form1.scaninput.style.display='none';
 document.form1.action="rcpt_review.php?rcpt=" + rcpt;
 document.form1.scaninput.value="review";
 document.form1.submit();
}
function chg_prompt(pType)
{
 var prm = pType.options[pType.selectedIndex].text;
 document.getElementById('typeLabel').innerHTML=prm + ' #';
}
function add_more()
{
 addFields(2);
 return true ;
}
function addFields(numAdd)
{
    // get the current number of inputs
    var number = document.getElementById("numberPOs").value;
    var toAdd = parseInt(number) + parseInt(numAdd) + 1;
    var w;
    var w1;
    // Get the element where the inputs will be added to
    var morePOs = document.getElementById("morePOs");
    // Remove every children it had before
    while (morePOs.hasChildNodes()) {
    morePOs.removeChild(morePOs.lastChild);
    }
    for (i=1;i<toAdd;i++){
    // Append a node with a random text
     morePOs.appendChild(document.createTextNode("Purchase Order " + (parseInt(i)) + "  "));
     // Create an <input> element, set its type and name attributes
     var input = document.createElement("input");
     w="poNumber" + i;     
     input.type = "text";
     input.name = w;
     input.id = w;
     morePOs.appendChild(input);
     w1="validatePO(" + w + ");";
     //input.onchange=validatePO( input.name ); 
     input.setAttribute('onChange','validatePO(' + w + ');');
     // Append a line break
     morePOs.appendChild(document.createElement("br"));
    }
    morePOs.appendChild(document.createElement("br"));
    document.getElementById("numberPOs").value=i;
alert(morePOs.innerHTML);
}

function validatePO(fld)
{
 var theField=document.getElementsByName(fld);
alert("fld=" + fld.name);
 var po=theField.value;
alert("po=" + po);
 var result;
 var vendor=document.getElementById('vendor').value;
 var params = "&po=" + po + "&vend=" + vendor;
 var url="http://{$wmsIp}/{$wmsHome}/rf/checkPO.php";

 if (window.XMLHttpRequest) {// code for IE7+, Firefox, Chrome, Opera, Safari
        Http = new XMLHttpRequest();
    }
    else {// code for IE6, IE5
        Http = new ActiveXObject("Microsoft.XMLHTTP");
    }
  Http.onreadystatechange = function () {
        if (Http.readyState == 4 && Http.status == 200) {
            // accessing to parent scope`s variable
            result = Http.responseText;
            callback(result);
        }
    }

 Http.open("POST",url,true);
 Http.setRequestHeader("Content-type", "application/x-www-form-urlencoded");
 Http.send(params);

 function callback (data){
     var result = data;
     //alert(result)
     var msg="Vendor: " + result;
     if (vendor !== "" && vendor !== result)
      {
       msg=result + " is not the same Vendor, last PO was vendor: " + vendor;
       return false;
      }
     document.getElementById("theMessage").innerHTML = msg
     if (result == "success") {
         return true;
     }
    else if (result == "failure") {
        document.getElementById("theMessage").innerHTML = Http.responseText;
        alert(Http.responseText);
        return false;
    }
   }

} // end validatePO

function srchVendor()
{
  //document.form1.action="vendSrch.php";
  document.form1.func.value="vendSrch";
  document.form1.submit();
  
}
function chk_sel()
{
 document.getElementById('B1').disabled=true;
 var ele = document.getElementsByName('POs[]');
 for (var i = 0; i < ele.length; i++) {
  if (ele[i].checked) document.getElementById('B1').disabled=false;
}
}
</script>

HTML;

if (count($POs) < 1 and isset($_SESSION["rf"]["POs"]) and count($_SESSION["rf"]["POs"]) > 0) {
    $POs = $_SESSION["rf"]["POs"];
    $HPO = $_SESSION["rf"]["HPO"];
}
$_SESSION["rf"]["recvType"] = $recvType;
$_SESSION["rf"]["recvTo"] = $recvTo;

$htm = "";
if (count($POs) > 0) $pg->title = setTitle($POs, $HPO, $vendor);
if ($msg <> "") $pg->msg = $msg;
if ($msgColor <> "") $pg->msgColor = $msgColor;
if ($scaninput == "ClEaR") {
    $scaninput = "";
    if ($func == "scanPart") $func = "selectPO";
    if ($func == "scanBin") {
        $func = "selectPO";
        $lookPL = 1;
        unset($shadow);
        unset($UPC);
        unset($PPL);
        unset($PPN);
        unset($PPD);
        unset($partUOM);
        unset($pkgUOM);
        unset($pkgQty);
        unset($uomDesc);
        unset($tqty);
    } // end scanBin
} // end Clear
if ($scaninput == "newPart") {
    $func = "selectPO";
    $scaninput = "";
    $lookPO = 1;
}
if (trim($vendor) <> "") {
    $SQL = <<<SQL
select
vendor,
name,
allow_to_bin,
allow_inplace,
allow_bo
from VENDORS
where vendor = "{$vendor}"

SQL;

    $vend = array();
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $vend["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    $_SESSION["rf"]["function"] = "RPO";
    $_SESSION["rf"]["vend"] = $vend;
} // end vendor <> ""
else { // vendor is empty
//Vendor input
//get vendors
    $SQL = <<<SQL
select
 vendor,
 name
from VENDORS
where vendor <> " "
order by name
SQL;
    $vendors = array();
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $vendors[$i]["vendor"] = $db->f("vendor");
            $vendors[$i]["name"] = $db->f("name");
        }
        $i++;
    } // while i < numr

    $pinfo = "";
    if (count($vendors)) foreach ($vendors as $item) {
        $targ = "";
        $w = $item["name"];
        if (trim($w) == "") $w = $item["vendor"];
        $pinfo .= <<<HTML
<option value="{$item["vendor"]}">{$w}</option>

HTML;
    } // end foreach vendors

//$showScreenSize=<<<HTML
    //<script>
    //var sz='width=' + screen.width + ' height=' + screen.height;
    //alert(sz);
    //</script>
//
//HTML;

    if (isset($_SESSION["rf"]["POs"])) {
        unset($_SESSION["rf"]["POs"]);
        unset($_SESSION["rf"]["HPO"]);
    }
    $rT1 = "";
    $rT2 = "";
    if ($recvTo == "a") $rT1 = " checked";
    if ($recvTo == "b" or $recvTo == "") $rT2 = " checked";
    $htm = <<<HTML
 <form name="form1" action="{$thisprogram}" method="get">
 <input type="hidden" name="func" value="vendor">
 <input type="hidden" name="vendor" id="vendor" value="">
 <input type="hidden" name="thisprogram" value="{$thisprogram}">
 <input type="hidden" name="numberPOs" id="numberPOs" value="1">

  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-container w3-blue w3-padding-8">
       <div class="row">
   <div class="col-75">
       <span id="theMessage" style="word-wrap: normal; font-weight: bold; font-size: large; text-align: cput;"></span>
   </div>
  </div>
        <div class="w3-clear"></div>
                <label>Receiving Type</label>
<select class="w3-white" onchange="chg_prompt(this);" name="recvType" placeholder="Select Type">
 <option value="1">Purchase Order</option>
 <option value="2">ASN</option>
 <option value="3">Transfer</option>
 <option value="4">Customer Return</option>
 <option value="5">Special Order</option>
</select>
<br>
<br>
<table style="color:white; font: bold;">
<tr>
<td><label>Receive To &nbsp; &nbsp;</label></td>
<td><input type="radio" id="rT1" name="recvTo" value="a"{$rT1}>
 <label for "rT1">Tote, Cart or Pallet</label>
</td>
<tr>
<td>&nbsp;</td>
<td><input type="radio" id="rT2" name="recvTo" value="b"{$rT2}>
 <label for "rT2">Direct to Bin</label>
</td>
</tr>
</table>
<br>
                <label id="typeLabel">Purchase Order #</label>
<input class="w3-white" type="text" name="poNumber" id="poNumber" onchange="validatePO(this.id);">
<a href="#" onclick="add_more()"><img src="../images/add_more.png" width="32px" height="32px" border="0" title="Add More"/></a>
<a href="#" onclick="srchVendor()"><img src="../images/vendsrch.png" width="32px" height="32px" border="0" title="Search by Vendor"/></a>
<br>
<div id="morePOs"/>
<br>

      </div>
    </div>
  </div>
  <div class="w3-clear"></div>
  <div class="col-10">
 <input class="binbutton" id="B1" name="B1" type="submit"/>
  </div>
 </form>

HTML;
} // end vendor is empty

if ($lookPO > 0 and $func == "scanBin") {
    require_once("{$wmsInclude}/cl_bins.php");
    $bincls = new BIN;
    $bincls->Company = 1;
    $bincls->User = $UserLogin;
    $theBin = strtoupper(trim($_REQUEST["scaninput"]));
    $bincls->lookUp($theBin, 0);
    if ($bincls->numRows < 1) { // invalid bin
        $func = "scanPart";
        $scaninput = $UPC;
        $msg = "Invalid Bin";
    } // invalid bin
    else { // good part, good PO, good bin, save it
        //echo "<pre>{$scaninput} {$theBin}\n";
        //print_r($_REQUEST);
        //print_r($_SESSION);
        if (isset($_SESSION["rf"]["RECEIPT"])) {
            $batch = $_SESSION["rf"]["RECEIPT"];
            wr_log("/tmp/testfunc.txt", "get_batch({$batch});");
            $w = get_batch($db, $batch);
            $upd = new AddUpdDel;
            if ($w["status"] == -35) { // batch does not exist yet
                $reqdata = array();
                $reqdata["batch_num"] = $batch;
                $reqdata["user_id"] = $_SESSION["wms"]["UserID"];
                $reqdata["batch_status"] = 0;
                $reqdata["batch_date"] = date("Y/m/d H:i:s");
                $reqdata["batch_company"] = $comp;
                $reqdata["batch_type"] = $_SESSION["rf"]["recvType"];
                $reqdata["action"] = 2;
                $where = "where batch_num = {$batch}";
                $return_code = $upd->updRecord($reqdata, "RCPT_BATCH", $where);
                //echo "batch return_code={$return_code}\n";

                foreach ($POs as $key => $po) {
                    unset($reqdata);
                    $reqdata = array();
                    $reqdata["wms_po_num"] = $po;
                    $reqdata["batch_num"] = $batch;
                    $reqdata["action"] = 2;
                    $where = "where wms_po_num = {$po} and batch_num = {$batch}";
                    $return_code = $upd->updRecord($reqdata, "RCPT_INWORK", $where);
                    //echo "inwork return_code={$return_code}\n";
                }
            } // batch does not exist yet
            //add batch detail
            unset($reqdata);
            $reqdata = array();
            $theUser = $_SESSION["wms"]["UserID"];
            wr_log("/tmp/testfunc.txt", "get_batchDetail({$batch},{$shadow},{$theUser});");
            $w = get_batchDetail($db, $batch, $shadow, $theUser);
            if (empty($w)) { //add new record

                wr_log("/tmp/testfunc.txt", "count_batch({$batch});");
                $lines = count_batch($db, $batch);
                $next_line = $lines + 1;
                $reqdata["batch_num"] = $batch;
                $reqdata["line_num"] = $next_line;
                $reqdata["pkgUOM"] = $pkgUOM;
                $reqdata["scan_upc"] = $UPC;
                $reqdata["po_number"] = $POs[0]; // This needs attention, need to figure which PO this part pertains to
                $reqdata["po_line_num"] = 0; // same as po number
                $reqdata["scan_status"] = 0;
                $reqdata["scan_user"] = $theUser;
                $reqdata["pack_id"] = $scaninput;
                $reqdata["shadow"] = $shadow;
                $reqdata["partUOM"] = $partUOM;
                $reqdata["line_type"] = " "; // need to pass this too
                $reqdata["pkgQty"] = $pkgQty;
                $reqdata["scanQty"] = $qtyRecvd;
                $reqdata["totalQty"] = ($qtyRecvd * $pkgQty);
                $reqdata["timesScanned"] = 1;
                $reqdata["recv_to"] = $_SESSION["rf"]["recvTo"];
                $reqdata["action"] = 2;
                $where = <<<SQL
 where batch_num = {$batch}
   and shadow = {$shadow}
   and scan_user = {$theUser}

SQL;
                $return_code = $upd->updRecord($reqdata, "RCPT_SCAN", $where);
            } //add new record
            else { // update qty in scan record
                $theUser = $_SESSION["wms"]["UserID"];
                $reqdata = $w;
                $where = <<<SQL
 where batch_num = {$batch}
   and shadow = {$shadow}
   and scan_user = {$theUser}

SQL;
                $reqdata["scanQty"] = $w["scanQty"] + $qtyRecvd;
                $reqdata["totalQty"] = ($reqdata["scanQty"] * $pkgQty);
                $reqdata["timesScanned"] = $w["timesScanned"] + 1;
                $return_code = $upd->updRecord($reqdata, "RCPT_SCAN", $where);
            } // update qty in scan record
            $save_RCPTSAN = $reqdata;
            $save_RCPTSCAN_where = $where;

            if ($_SESSION["rf"]["recvTo"] == "b") { // recv to Bin, update WHSEQTY and add PARTHIST
                $po = $POs[0];
                $hpo = $HPO[0];
                $qty = ($qtyRecvd * $pkgQty);
                $tmp = getPrice($db, $reqdata["po_number"], $reqdata["po_line_num"]);
                $binType = substr($opt[21], 0, 1);
                if (isset($mst)) {
                    if (trim($mst["primary_bin"]) <> "") $binType = substr($opt[21], 1, 1);
                } // end mst is set
                if (1 == 2) {
                    $sparams = array(
                        0 => array("value" => $po, "type" => "INT"),
                        1 => array("value" => $shadow, "type" => "INT"),
                        2 => array("value" => $comp, "type" => "INT"),
                        3 => array("value" => $vendor, "type" => "STR"),
                        4 => array("value" => $theUser, "type" => "INT"),
                        5 => array("value" => $hpo, "type" => "STR"),
                        6 => array("value" => "Direct To Bin", "type" => "STR"),
                        7 => array("value" => "RCV", "type" => "STR"),
                        8 => array("value" => $qty, "type" => "INT"),
                        9 => array("value" => $partUOM, "type" => "STR"),
                        10 => array("value" => $theBin, "type" => "STR"),
                        11 => array("value" => 1, "type" => "INT"),
                        12 => array("value" => $tmp["cost"], "type" => "STR"),
                        13 => array("value" => $tmp["core"], "type" => "STR"),
                        14 => array("value" => 0, "type" => "INT", "IO" => true),
                        15 => array("value" => 0, "type" => "INT"),
                        16 => array("value" => $binType, "type" => "STR")
                    );
                    //Stored Procedure way (no begin/commit or rollback)
                    //$rc=$db->execStored("wp_updQty",$sparams);
                    //echo "rc={$rc}\n";
                    //print_r($sparams);
                } // end 1 == 2

                if (2 == 2) {
                    // use cl_inv class
                    $sparams1 = array(
                        "wms_trans_id" => $po,
                        "shadow" => $shadow,
                        "company" => $comp,
                        "psource" => $vendor,
                        "user_id" => $theUser,
                        "host_id" => $hpo,
                        "ext_ref" => "Direct To Bin",
                        "trans_type" => "RCV",
                        "in_qty" => $qty,
                        "uom" => $partUOM,
                        "bin" => $theBin,
                        "inv_code" => "1",
                        "mdse_price" => $tmp["cost"],
                        "core_price" => $tmp["core"],
                        "in_qty_core" => 0,
                        "in_qty_def" => 0,
                        "bin_type" => $binType
                    );

                    $trans = new invUpdate;
                    $rc = $trans->updQty($sparams1); // 1=success, 0=failed

                    //Do something on failure *********************************************
                } // end 2 == 2
                if ($rc > 0) { // update inv was successful
                    $reqdata["scan_status"] = 1;
                    $return_code = $upd->updRecord($reqdata, "RCPT_SCAN", $where);
                } // update inv was successful

            } // recv to Bin, update WHSEQTY and add PARTHIST
//echo "<pre>batch={$batch} w=";

//echo "rc={$rc}\n";
//echo "$return_code\n";
//echo $SQL;
//print_r($sparams);
//print_r($_SESSION);

//exit;
            //$reqdata["SCAN"]=
            $msg = "Last: {$PPL} {$PPN} {$PPD} Qty: {$qtyRecvd} to: {$scaninput}";
            $lookPO = 1;
            $func = "selectPO";
            unset($shadow);
            unset($UPC);
            unset($PPL);
            unset($PPN);
            unset($PPD);
            unset($partUOM);
            unset($pkgUOM);
            unset($pkgQty);
            unset($uomDesc);
            unset($tqty);
            unset($qtyRecvd);
            unset($scaninput);
        }
        $pg->title = setTitle($POs, $HPO, $vendor);
        $pg->title .= "<br> {$msg}";
        if (!isset($msg)) $msg = "";

        $htm = frmtPartScan($vendor, $msg, "blue");

    }  // good part, good PO, good bin, save it
} // end scan bin
//POs
//echo "lookPO={$lookPO} func={$func}\n";
if ($lookPO > 0 and $func == "selectPO") {
    //get shadow number of scaninput

    unset($_SESSION["POs"]);
    unset($_SESSION["rf"]["POs"]);
    unset($_SESSION["rf"]["HPO"]);
    $_SESSION["rf"]["POs"] = $POs;
    if (!isset($_SESSION["rf"]["RECEIPT"]) and count($POs) > 0) {
//echo "getting control number\n";
        $rcpt_num = get_contrl($db, $comp, "RECEIPT");
        if ($rcpt_num > 0) $_SESSION["rf"]["RECEIPT"] = $rcpt_num;
//echo "rcpt_num= {$rcpt_num}\n";
    }
    $pg->title = setTitle($POs, $HPO, $vendor);

    $htm = frmtPartScan($vendor, $msg, "blue");
} // end lookPO > 0 and func = selectPO

if (isset($cqty) and isset($choice) and isset($shd) and isset($pnum)) {
    $lookPO = 1;
    $func = "scanPart";
    $scaninput = $pnum;
}
if ($lookPO > 0 and $func == "scanPart") {
    if ($scaninput <> "") { // its numeric must be a upc
        $upc = $scaninput;
        //if ($upc == $shd) $upc=".{$shd}";
        wr_log("/tmp/testfunc.txt", "chkPart({$upc},{$main_ms});");
        $parts = chkPart($upc, $main_ms);
        $numparts = $parts["status"];
        if ($numparts == 1) {
            $part = $parts["Part"];
            $part["upc"] = $upc;
            $UPC = $upc;
            $part["comp"] = $main_ms;
            $shadow = $part["shadow_number"];
            $alt_type = $parts["Result"]["alt_type_code"];
            $UOM = $parts["Result"]["unit_of_measure"];
            $PKGUOM = $parts["Result"]["alt_uom"];
            if ($alt_type > 0) $qty_scanned = 1; else $qty_scanned = -$alt_type_code;
            $mst = $parts["WhseQty"];
            $prodline = $parts["ProdLine"];
            //load qty, bin and possible serial number inputs
            //here
        } // end numparts = 1
        if ($numparts == -35) { // nof
            $numparts = 0;
            $shadow = 0;
            $pl = "???";
            $part["p_l"] = $pl;
            $part["part_number"] = $upc;
            $part["shadow_number"] = 0;
            $part["part_desc"] = "Not Found!";
            $part["alt_type_code"] = 0;
            $pnum = "";
            $htm = frmtPartScan($vendor, "Invalid Part Entered, Please try again", "red");
            if (isset($playsound) and $playsound > 0) $bgsound = <<<HTML
<audio controls autoplay hidden>
  <source src="{$sounds}/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;
            $htm .= $bgsound;
        } // nof
        if ($numparts > 1) { // choose
            $htm = frmtChoose($upc, $parts);
        } // choose
        $recvInfo = "";
        if ($numparts == 1) {
            //wr_log("/tmp/testfunc.txt","chkPartOnPO({$shadow},".var_dump($POs) . ",{$qty_scanned});");
            $poitems = chkPartOnPO($db, $shadow, $POs, $qty_scanned);
            if ($poitems["numRows"] < 1) {
                //Item not found on any of the PO's, what to do?
                //
                echo "Help, part was not found on any PO";
                exit;
                //load qty, bin and possible serial number inputs
                //here
            }
//echo "<pre>";
//print_r($poitems);
//exit;
            if ($poitems["numRows"] > 0) {
                $pg->color = "green";
                $totRecv = $poitems["inRecv"];
                $totOrd = $poitems["totalOrd"];
                if ($poitems["inRecv"] > 0) {
                    $recvInfo = "Ordered: {$totOrd}, Recvd so far: {$totRecv}";
                }
                $htm = frmtBin($part, $vendor, $qty_scanned, $PKGUOM, $mst, "blue", $msg, $recvInfo);
            }
        } // end not a choose nuparts = 1
    } // its numeric must be a upc
} // end lookPO > 0 and func = scanPart

//echo "vendor={$vendor} pocount=" . count($POs) . " lookPL={$lookPO}\n";
if (trim($vendor) <> "" and count($POs) < 1 and $lookPO < 1) {
    $SQL = <<<SQL
select
company,
wms_po_num,
host_po_num,
po_type,
vendor,
po_date,
num_lines,
po_status,
bo_flag,
est_deliv_date,
ship_via,
sched_date,
xdock
from POHEADER
where vendor = "{$vendor}"
and po_status between -6 and 6
order by host_po_num
SQL;

    $po = array();
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    if ($key == "po_date" or $key == "est_deliv_date" or $key == "sched_date") {
                        $po[$i]["$key"] = date("m/d/Y", strtotime($data));
                    } else {
                        $po[$i]["$key"] = $data;
                    }
                }
            }
        }
        $i++;
    } // while i < numrows
    $msg = "";
    $pg->msg = "";
    if (count($po) < 1) {
        $msg = urlencode("There are no Open PO's for vendor: {$vendor}");
        $url = $_SERVER["SCRIPT_NAME"] . "?msg={$msg}";
        $htm = <<<HTML
 <html>
 <head>
 <script>
window.location.href="{$url}";
 </script>
 </head>
 <body>
 </body>
</html>

HTML;
//echo "IM HERE2";
        echo $htm;
//print_r($_SESSION);
//exit;
    } // end no po's for this vendor

    $htm = <<<HTML
<div class="panel panel-default">
    <div class="panel-heading">
     <div class="row">
      <div class="col-md-6">
       <h3 class="panel-title">Expected PO's from {$vendor}</h3>
      </div>
     </div>
    </div>
    <div class="panel-body">
     <div class="table-responsive">
      <form name="form1" action="{$thisprogram}" method="get">
      <input type="hidden" name="thisprogram" value="{$thisprogram}">
      <input type="hidden" name="vendor" value="{$vendor}">
      <input type="hidden" name="func" value="selectPO">
      <input type="hidden" name="recvType" value="{$recvType}">
      <input type="hidden" name="recvTo" value="{$recvTo}">
      <input type="hidden" name="lookPO" value="1">
      <input type="hidden" name="upc" value="{$UPC}">
      <input type="hidden" name="comp" value="{$comp}">
      <input type="input" name="scaninput" value="" style="display:none">
      <table class="table table-bordered table-striped">
       <tr>
        <th class="FieldCaptionTD">&nbsp;</th>
        <th class="FieldCaptionTD">PO#</th>
        <th class="FieldCaptionTD">Date</th>
        <th class="FieldCaptionTD">Num Lines</th>
        <th class="FieldCaptionTD">Status</th>
        <th class="FieldCaptionTD">Exp Date</th>
       </tr>
HTML;

    foreach ($po as $key => $row) {
        // do somthing with wms_po_num
        $htm .= <<<HTML
       <tr>
        <td><input type="checkbox" name="POs[]" value="{$row["wms_po_num"]}" onclick="chk_sel();">
        <input type="hidden" name="HPO[]" value="{$row["host_po_num"]}">
</td>
        <td align="right">{$row["host_po_num"]}</td>
        <td>{$row["po_date"]}</td>
        <td align="right">{$row["num_lines"]}</td>
        <td>{$row["po_status"]}</td>
        <td>{$row["est_deliv_date"]}</td>
       </tr>

HTML;
    } // end foreach po
    $htm .= <<<HTML
      <tr>
       <td align="center" colspan="6">
 <button class="binbutton" id="B1" name="B1" onclick="document.form1.submit();" disabled>Receive Selected</button>
    <input class="binbutton" type="button" value="Select Vendor" onclick="do_reset();"></td>
       </td>
      </tr>
      </table>
      </form>
     </div>
    </div>
   </div>
HTML;

} // end POs
$pg->body = $htm;
if (isset($_SESSION["rf"]["RECEIPT"])) {
    $pg->addMenuLink("javascript:do_review({$_SESSION["rf"]["RECEIPT"]});", "Review");
    $pg->addMenuLink("javascript:do_complete({$_SESSION["rf"]["RECEIPT"]});", "Complete");
} // end RECEIPT is set

//$buttons=loadButtons("SubMit|clrbtn|complete|review");
//$pg->body.=$buttons;
$pg->Display();
//echo "<pre>";
//print_r($_REQUEST);

//print_r($_SESSION["REQDATA"]);
//
//Old program

//echo "<pre>In Old Program\n";
//print_r($_REQUEST);
//print_r($_SESSION);
exit;
//require_once("footer.php");
$main_ms = 1;
$main_abbr = "JD WMS";


if (isset($_REQUEST["scaninput"])) $scaninput = $_REQUEST["scaninput"]; else $scaninput = "";
if (isset($_REQUEST["whseloc"])) $whseloc = $_REQUEST["whseloc"]; else $whseloc = "";
if (isset($_REQUEST["oldloc"])) $oldloc = $_REQUEST["oldloc"]; else $oldloc = "";
if (isset($_REQUEST["shd"])) $shd = $_REQUEST["shd"]; else $shd = 0;
if (isset($_REQUEST["cqty"])) $cqty = $_REQUEST["cqty"]; else $cqty = 0;
if (isset($_REQUEST["batchnum"])) $batchnum = $_REQUEST["batchnum"]; else $batchnum = 0;
$playsound = 0;
$bgsound = "";
$inoupc = false;
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";

if ($batchnum == 0) { // get batch num
    //$batchnum=get_contrl($db,0,"BINASNG");
    $batchnum = 1;
    //get user from session
    //$user="dave";
    $user = $_SESSION["BINSCAN"]["Name"];
    $rc = add_batch($db, $batchnum, $main_ms, $user);
} // get batch num

$thisprogram = $_SERVER["REQUEST_URI"];
if ($scaninput == "ClEaR") {
    $whseloc = "";
    $scaninput = "";
}

if ($scaninput == "NoUPC") {
    $rc = log_error($db, $batchnum, 3, $oldloc, $whseloc, $scaninput);
    $scaninput = "";
    $inoupc = true;
    $color = "yellow";
    $disp = "Bin";
    $msg = "<h3>Part with No UPC</h3>";
    $hdr = "<h2>No UPC logged to bin: {$whseloc}</h2>";
    $bin = $whseloc;
    $dbin = $bin;
    $choose_htm = "";
    $j = 0;
} // end of noUPC


$end = <<<HTML
</body>
</html>

HTML;

$htm = <<<HTML
{$header}
{$main}
{$scripts}
{$end}

HTML;
echo $htm;

function chkPart($pnum, $comp)
{
    global $main_ms;
    $ret = array();
    $ret["upc"] = $pnum;
    $ret["comp"] = $comp;
    $pr = new PARTS;
    $pnum = trim($pnum);
    $a = $pr->lookup($pnum);
    if ($pr->status == -35) {
        $ret["status"] = -35;
        return $ret;
    }
    if (count($a) == 1) $ret = $pr->Load($a[1]["shadow_number"], $main_ms);
    $ret["status"] = $pr->status;
    $ret["numRows"] = count($pr->status);
    if ($pr->status > 1) {
        $ret = $a;
        $ret["numRows"] = $pr->status;
        $ret["status"] = $pr->status;
    } else {
        $ret["Result"] = $a[1];
        $ret["Part"] = $pr->Data;
        $ret["ProdLine"] = $pr->ProdLine;
        $ret["WhseQty"] = $pr->WHSEQTY;
        $ret["Alternates"] = $pr->Alternates;
    }
    unset($pr);
    return $ret;
}

function getPrice($db, $po, $po_line)
{
    $ret = array("cost" => 0.00, "core" => 0.00);
    $SQL = <<<SQL
select mdse_price as cost,
       core_price as core 
from POITEMS
where poi_po_num = {$po}
  and poi_line_num = {$po_line}

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret["cost"] = $db->f("cost");
            $ret["core"] = $db->f("core");
        }
        $i++;
    } // while i < numrows
    return $ret;


} // end getPrice
function get_part($db, $pnum_in)
{
    $ret = array();
    $ret["status"] = 0;
    $ret["num_rows"] = 0;
    $i = 0;
    $SQL = <<<SQL
SELECT alt_part_number,alt_type_code, alt_uom,
 shadow_number,
 p_l,
 part_number,
 unit_of_measure,
 shadow_number
 part_desc,
 part_long_desc, 
 part_seq_num,
 part_category,
 part_class
 part_subline,
 part_group,
 part_returnable, 
 serial_num_flag,
 special_instr,
 hazard_id,
 kit_flag,
 cost,
 core,
 core_group 
 FROM ALTERNAT,PARTS
 WHERE alt_part_number like "{$pnum_in}"
 AND  shadow_number = alt_shadow_num
SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret[$i]["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    $ret["num_rows"] = $numrows;
    if ($ret["num_rows"] == 0) {
        $ret["status"] = -35;
    }
    return $ret;
} // end get_part

function get_mstqty($db, $comp, $shadow)
{
    $ret = array();
    $SQL = <<<SQL
 select 
 primary_bin as whse_location,
 qty_avail,
 qty_alloc,
 qty_on_order,
 qty_on_vendbo,
 qty_on_custbo,
 qty_defect,
 qty_core
 from WHSEQTY
 where ms_shadow = $shadow
 and ms_company = $comp

SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    return $ret;
} // end get_mstqty

function addupd_line($db, $rma, $part, $qty, $Rtype, $last12)
{
    $rcc = array(0 => 0, 1 => $qty, 2 => 0);
    $linenum = -1;
    //check if part exists on rma
    $SQL = <<<SQL
select rmd_line_num,rmd_qty
from RMA_DTL
where rmd_number = {$rma}
and rmd_shadow = {$part->Data["shadow_number"]}
and rmd_type ="{$Rtype}"
SQL;


    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $linenum = $db->f("rmd_line_num");
            $oqty = $db->f("rmd_qty");
        }
        $i++;
    } // while i < numrows

    if ($linenum < 0) { //add new line
        $SQL = <<<SQL
  select rma_num_lines from RMA_HDR where rma_number = {$rma}

SQL;
//echo $SQL;

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows) {
                $linenum = $db->f("rma_num_lines");
            }
            $i++;
        } // while i < numrows

        if ($linenum < 0) {
            echo "Header Not Found!";
            exit;
        }

        $linenum++;
        //check part and type in rma dtl, if there add to qty, else increment line#
        //insert SQL
        $iSQL = <<<SQL
  insert into RMA_DTL
  (
    rmd_number,
    rmd_line_num,
    rmd_shadow,
    rmd_pl,
    rmd_part_number,
    rmd_desc,
    rmd_qty,
    rmd_type,
    rmd_last_12,
    rmd_act_type, rmd_line_stat, rmd_orig_comp, rmd_orig_inv, rmd_orig_order, 
    rmd_orig_line, rmd_mdse_price, rmd_def_price, rmd_core_price,
    rmd_mdse_prsc, rmd_def_prsc, rmd_core_prsc
   )
   values(
    {$rma},
    {$linenum}, 
    {$part->Data["shadow_number"]},
    "{$part->Data["p_l"]}",
    "{$part->Data["part_number"]}",
    "{$part->Data["part_desc"]}",
    {$qty},
    "{$Rtype}",
    {$last12},
    "",0,0,"",0,
    0,0.00,0.00,0.00,
    "","","")

   update RMA_HDR set rma_num_lines={$linenum} where rma_number = {$rma}
SQL;
        $rcc[0] = $db->Update($iSQL);
    }  //add new line
    else { // update line
        $nqty = $oqty + $qty;
        $rcc[1] = $nqty;
        $uSQL = <<<SQL
update RMA_DTL set rmd_qty = {$nqty}
where rmd_number = {$rma}
and rmd_line_num = {$linenum}
and rmd_shadow = {$part->Data["shadow_number"]}
and rmd_type ="{$Rtype}"

SQL;
        $rcc[0] = $db->Update($uSQL);
    } // update line
    $rcc[2] = $linenum;
    return $rcc;
} // end addupd_line

function frmt_Nline($line, $pl, $part, $desc, $qty, $ty, $valid, $title, $dclass)
{
    if ($pl == "???") $valid = "";
    $bgsound = "";
    $cls = "DataTD";
    if (trim($dclass) == "") $dclass = "class=\"DataTD\"";
    $link = <<<HTML
<a href="#" onclick="do_edit('rma_edit.php',{$line});">Edit</a>
HTML;
    $dty = $ty;
    if ($ty == "C" and $line == "") { // it's a core part with no value
        $dty = "&nbsp;";
        $link = "&nbsp;";
        $cls = "Alt2DataTD";
        $dclass = "class=\"Alt2DataTD\"";
        $bgsound = <<<HTML
<audio controls autoplay hidden>
  <source src="{$sounds}/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;

    } // it's a core part with no value
    $htm = <<<HTML
<tr>
 <td width="5%" class="{$cls}" >{$line}</td>
 <td width="5%" class="{$cls}" >{$qty}</td>
 <td width="5%" class="{$cls}" ><strong>{$pl}</strong></td>
 <td width="20%" class="{$cls}" ><strong>{$part}</strong></td>
 <td width="30%" class="{$cls}" ><strong>{$desc}</strong></td>
 <td width="5%" class="{$cls}" >{$dty}</td>
 <td width="5%" class="{$cls}" >{$link}</td>
 <td width="50%" nowrap {$title}{$dclass}>{$valid}{$bgsound}</td>
</tr>

HTML;
    return $htm;
} // end frmt_Nline
function load_dtl($db, $batchnum, $line = "")
{
    $part_htm = "";
    $extra = "";
    if (isset($rmalines)) unset($rmalines);
    $rmalines = array();
    if ($line <> "") { //not invalid
        $extra = "and rmd_line_num <> {$line}";
    } //not invalid
    $SQL = <<<SQL
select rmd_line_num,rmd_pl,rmd_part_number,rmd_desc,rmd_qty,rmd_last_12,rmd_type
from RMA_DTL
where rmd_number = {$batchnum}
{$extra}
order by rmd_number,rmd_line_num

SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $rmalines[$i]["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    if (count($rmalines)) { // have existing lines
        foreach ($rmalines as $key => $litem) {
            $valid = "OK";
            $vt = "";
            $tv = "";
            if ($litem["rmd_type"] <> "C") if ($litem["rmd_last_12"] < 1) {
                $valid = "Can't Find Purchase Record!";
                $tv = "class=\"Alt2DataTD\" ";
                $vt = "title=\"Customer Has Not Bought this Part!\"";
            }
            $part_htm .= frmt_Nline($litem["rmd_line_num"], $litem["rmd_pl"], $litem["rmd_part_number"], $litem["rmd_desc"], $litem["rmd_qty"], $litem["rmd_type"], $valid, $tv, $vt);

        } // end foreach rmalines
    } // have existing lines
    return $part_htm;
} // end load_dtl
function add_batch($db, $batchnum, $comp, $user)
{
    $SQL = <<<SQL
insert into WDI_BINSCAN
 (batch_num, scan_date, scan_by, company, batch_status)
values({$batchnum},getdate(),"{$user}",$comp,0)

SQL;
    $rc = $db->Update($SQL);
    return $rc;
} // end add_batch
function add_binscan($db, $batchnum, $whseloc, $shadow, $qty_avail, $qty_alloc, $sqty, $btype = "M")
{
    $bline = 0;
    $mode = "upd";
    $SQL = <<<SQL
select batch_line,qty
from WDI_ASGBIN
where batch_num = {$batchnum}
and shadow = {$shadow}
and whse_loc = "{$whseloc}"
SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $bline = $db->f("batch_line");
            $qty = $db->f("qty");
        }
        $i++;
    } // while i < numrows

    if ($bline < 1) {
        $mode = "add";
        $SQL = <<<SQL
select isnull(max(batch_line),0) as line_num from WDI_ASGBIN
where batch_num = {$batchnum}
SQL;

        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows) {
                $bline = $db->f("line_num") + 1;
            }
            $i++;
        } // while i < numrows
        $qty = 0;
    } // end bline < 1
    if ($mode == "upd") {
        $sqty = $sqty + $qty;
        $SQL = <<<SQL
   update WDI_ASGBIN set qty = {$sqty}
   where batch_num = $batchnum
    and batch_line = {$bline}
    and shadow = {$shadow}
    and whse_loc = "{$whseloc}"

SQL;
        $rc = $db->Update($SQL);
    } // end upd mode
    else { //add
        $SQL = <<<SQL
 insert into WDI_ASGBIN
 (batch_num, batch_line, whse_loc, bin_type, shadow,
  qty, qty_avail, qty_alloc , line_status)
values ({$batchnum},{$bline},"{$whseloc}","{$btype}",{$shadow},
        {$sqty},{$qty_avail},{$qty_alloc},0)
SQL;
        $rc = $db->Update($SQL);
    } // end add mode
    return $rc;
} // end add_binscan

function log_error($db, $batchnum, $type, $lbin, $bin, $upc)
{
// 1=BIN NOF, 2=upc NOF, 3=No UPC on box, 4 Duplicate UPC found, 5=Shadow not chosen
    $SQL = <<<SQL
insert into WDI_BINERROR
(batch_num, ex_type, last_bin, this_bin, upc)
values({$batchnum},{$type},"{$lbin}","{$bin}","{$upc}")
SQL;
    $rc = $db->Update($SQL);
    return $rc;
} // end log_error
function frmtPartScan($vendor, $msg, $color)
{
    global $thisprogram;
    $msgcolor = $color;
    if (trim($msg) == "" or substr($msg, 0, 5) == "Last:") {
        $msg .= "<br>";
        $msgcolor = "green";
    } else {
        $msg = "<h4>{$msg}</h4><br>";
        $color = "red";
    }
    $htm = <<<HTML
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="vendor" value="{$vendor}">
  <input type="hidden" name="thisprogram" value="{$thisprogram}">
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="lookPO" value="1">
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-container w3-{$color} w3-padding-8">
        <span class="w3-{$msgcolor}">{$msg}</span>
        <div class="w3-clear"></div>
                <label>Scan Part</label>
<input type="text" class="w3-white" onchange="do_bin();" value=" " name="scaninput" placeholder="Scan or Enter Part...">
<br>
<br>

      </div>
    </div>
  </div>
 </form>

 </div>
<script>
document.form1.scaninput.focus();

function do_bin()
{
 document.form1.submit();
}
function do_reset()
{
 document.form1.scaninput.style.display='none';
 document.form1.scaninput.value="ClEaR";
 document.form1.submit();
}
function chk_sel()
{
 document.getElementById('B1').disabled=true;
 var ele = document.getElementsByName('POs[]');
 for (var i = 0; i < ele.length; i++) {
  if (ele[i].checked) document.getElementById('B1').disabled=false;
}
}
</script>

HTML;
    return $htm;

} // end frmtPartScan
?>


<?php

$title = "Template";
$inc = "../assets/css";
$viewport = "0.75";


$htm = <<<HTML
<!DOCTYPE html>
<html>
<title>{$title}</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="initial-scale={$viewport}, width=device-width, user-scalable=yes" />

<link rel="stylesheet" href="{$inc}/wdi3.css">
<link rel="stylesheet" href="{$inc}/css">
<link rel="stylesheet" href="{$inc}/font-awesome.min.css">
<link rel="stylesheet" href="../Themes/Multipads/Style.css">


<style>
html,body,h1,h2,h3,h4,h5 {font-family: "Raleway", sans-serif}
.binbutton {
    background-color: #86c5f9;
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
</style>

<body class="w3-light-grey">
<!-- !PAGE CONTENT! -->
<div class="w3-main" style="margin-left:1px;margin-top:4px;">

  <!-- Header -->
  <header class="w3-container" style="padding-top:12px">
    <h5><b>Awaiting Bin Scan</b></h5>
  </header>
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="thisprogram" value="{$thisprogram}">
  <input type="hidden" name="whseloc" value="">
  <input type="hidden" name="oldloc" value="">
  <input type="hidden" name="batchnum" value="268">
  
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-container w3-blue w3-padding-8">
        <div class="w3-clear"></div>
                <label><h4>Scan Bin</label>
        <input type="text" name="scaninput" value="" style="text-transform:uppercase" onchange="do_bin();">

</h4>
<br>
<br>

      </div>
    </div>
  </div>
{$buttons}
 </form>
</div>
<script>
document.form1.scaninput.focus();

function do_bin()
{
 document.form1.submit();
}
function do_reset()
{
 document.form1.scaninput.style.display='none';
 document.form1.scaninput.value="ClEaR";
 document.form1.submit();
}
function do_logoff()
{
 document.location.href="Login.php";
}
</script>
</body>
</html>

HTML;
echo "IM HERE1";
echo $htm;
function frmtChoose($upc, $data)
{
    global $thisprogram;
    global $main_ms;
    $th = "class=\"FormHeaderFont\" ";
    $tc = "class=\"FieldCaptionTD\" ";
    $td = "class=\"DataTD\" ";
    $td = "class=\"w3-bar-block\" ";
    $ta = "class=\"AltDataTD\" ";
    $bsound = "";
    $choose_htm = <<<HTML
 <form name="form2" action="{$thisprogram}" method="get">
  <input type="hidden" name="thisprogram" value="{$thisprogram}">
  <input type="hidden" name="form_name" value="form2">
  <input type="hidden" name="eline" value="">
  <input type="hidden" name="comp" value="{$main_ms}">
  <input type="hidden" name="shd" value="">
  <input type="hidden" name="cqty" value="">
    <div class="w3-half">
      <div class="w3-container w3-yellow w3-padding-8">
        <div class="w3-clear"></div>
          <div class="w3-container FormSubHeaderFont w3-white">Multiple Parts Found, Please Choose!</div>

  <table class="table table-bordered table-striped">
   <tr>
    <th {$tc} width="5%">Select</th>
    <th {$tc} width="3%">P/L&nbsp;</th>
    <th {$tc} width="20%">Part#</th>
    <th {$tc} width="30%">Description</th>
    <th {$tc} width="5%">PackQty</th>
   </tr>
   {$bsound}
HTML;
    $i = 1;
    foreach ($data as $rec => $part) {
        if (isset($part["p_l"])) {
            $p_l = $part["p_l"];
            $pn = $part["part_number"];
            $shd = $part["shadow_number"];
            $pdesc = $part["part_desc"];
            $upc = $part["alt_part_number"];
            $cqty = 1;
            $atype = $part["alt_type_code"];
            if ($atype < 0) {
                $cqty = -$atype;
            };
            $choose_htm .= <<<HTML
   <tr>
    <input type="hidden" name="cshadow[{$i}]" value="{$shd}">
    <input type="hidden" name="dupeupc[{$i}]" value="{$upc}">
    <td class="DataTD" style="color: black;"><input type="checkbox" name="pnum" value=".{$shd}" onchange="do_choose({$shd});"></td>
<td class="DataTD" style="color: black;">{$p_l}</td>
<td class="DataTD" style="color: black;">{$pn}</td>
<td class="DataTD" style="color: black;">{$pdesc}</td>
<td class="DataTD" style="color: black;">1</td>

   </tr>

HTML;
            $i++;
        } // pl is set
    } // end foreach data
    $choose_htm .= <<<HTML
    <input type="hidden" name="choice" value="">
 </table>
      </div>
     </div>
    </div>
  </div>
 <input type="hidden" name="oldupc" value="{$upc}">
</form>
<script>
  function do_choose(pn,qty,choice) {
       document.form2.cqty.value=qty;
       document.form2.choice.value=choice;
       document.form2.shd.value=pn;
       document.form2.submit();
      }
</script>


HTML;
    return $choose_htm;
} // end frmtChoose
function chkPartOnPO($db, $shadow, $POs, $qty_scanned = 1)
{
    $poitems = array();
    $poitems["numRows"] = 0;
    $poitems["inRecv"] = 0;
    $poitems["totalOrd"] = 0;
    if (count($POs) < 1 or $shadow < 1) return $poitems;
    $P = "";
    $comma = "";
    foreach ($POs as $p) {
        $P .= "{$comma}{$p}";
        $comma = ",";
    } // end foreach POs
    $wt = "=";
    $we = "";
    if (count($POs) > 1) {
        $wt = "in (";
        $we = ")";
    }
    $where = <<<SQL
where poi_po_num {$wt}{$P}{$we}
 and shadow = {$shadow}

SQL;

    $SQL = <<<SQL
select
poi_po_num,
poi_line_num,
shadow,
p_l,
part_number,
part_desc,
uom,
qty_ord,
qty_recvd,
qty_bo,
qty_cancel,
mdse_price,
core_price,
weight,
volume,
case_uom,
case_qty,
poi_status,
vendor_ship_qty,
packing_slip,
tracking_num,
bill_lading,
container_id,
carton_id,
line_type,
{$qty_scanned} as qty_scanned
from POITEMS
{$where}
order by poi_po_num,poi_line_num
 
SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $poitems[$i]["$key"] = $data;
                }
                if ($key == "qty_ord") {
                    $poitems["totalOrd"] = $poitems["totalOrd"] + $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    $poitems["numRows"] = $numrows;
    //check current open receipts for this part
    $whr = str_replace("poi_po_num", "wms_po_num", $where);
    $SQL = <<<SQL
 select sum(totalQty) as inRecv
from RCPT_INWORK,RCPT_SCAN
{$whr}
and  RCPT_INWORK.batch_num = RCPT_SCAN.batch_num
and scan_status < 2
SQL;
    $inRecv = 0;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $inRecv = $db->f("inRecv");
        }
        $i++;
    } // while i < numrows

    $poitems["inRecv"] = $inRecv;
    return $poitems;
} // end chkPartOnPO

function loadButtons($match)
{
    // match is a string of buttons to display separated by pipes
    // eg.. "clrbtn|nobtn|logoff"

    if ($match == "") return "";
    $matchString = $match;
    if (isset($_SESSION["rf"]["RECEIPT"])) {
        $x1 = <<<JSON
[
{"class":"binbutton","type":"button","name":"SubMit","value":"Submit", "onclick":"do_bin();","colspan":5},
{"class":"binbutton","type":"button","name":"clrbtn","value":"Clear", "onclick":"do_reset();","colspan":5},
{"class":"binbutton","type":"button","name":"complete","value":"Complete","onclick":"do_complete({$_SESSION["rf"]["RECEIPT"]});"},
{"class":"binbutton","type":"button","name":"review","value":"Review","onclick":"do_review({$_SESSION["rf"]["RECEIPT"]});"}
]
JSON;
    } else {
        $x1 = <<<JSON
[
{"class":"binbutton","type":"button","name":"SubMit","value":"Submit", "onclick":"do_bin();","colspan":5},
{"class":"binbutton","type":"button","name":"clrbtn","value":"Clear", "onclick":"do_reset();","colspan":5}
]
JSON;
    }
    $x = json_decode($x1, true);
    $buttons = <<<HTML
  <div class="w3-half w3-center w3-medium w3-margin-bottom">

HTML;
    foreach ($x as $key => $data) {
        //if (strpos($matchString, $data["name"]) !== false)
        if (preg_match("/({$matchString})/i", $data["name"])) {
            $col = "";
            if (isset($data["colspan"])) $col = " colspan=\"{$data["colspan"]}\"";
            $buttons .= <<<HTML
    <input class="{$data["class"]}" type="{$data["type"]}" name="{$data["name"]}" value="{$data["value"]}" onclick="{$data["onclick"]}">
HTML;
        } // end of preg_match, button is turned on

    } // end foreach x
    $buttons .= <<<HTML
 </div>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
<p>&nbsp;</p>
HTML;
    return $buttons;
} // end loadButtons

function frmtBin($part, $vendor, $qty_scanned, $pkguom, $mst, $color, $msg, $recvInfo)
{
    global $thisprogram;
    global $prodline;
    global $sounds;
    $msgcolor = $color;
    $bsound = "";
    if (trim($msg) == "") {
        $msg = "     <br>";
    } else {
        $msg = "<h4>{$msg}</h4>";
        $bsound = <<<HTML
<audio controls autoplay >
  <source src="{$sounds}/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;

        $color = "red";
        $msgcolor = "red";
    }

    if (trim($recvInfo) <> "") {
        $msgcolor = "yellow";
        $msg = "<h4>{$recvInfo}</h4>";
    }
    //$qty_scanned="12";
    //$pkguom="CS";
    $p_l = $part["p_l"];
    $pn = $part["part_number"];
    $shd = $part["shadow_number"];
    $pdesc = $part["part_desc"];
    $partuom = $part["unit_of_measure"];
    $tqty = $qty_scanned;
    $uomDesc = "{$tqty} {$partuom}";
    $b = "<strong>";
    $be = "</strong>";
    $uom1 = "";
    if ($pkguom == $partuom) $uom = $partuom;
    else {
        $uom1 = "{$b} {$pkguom} of {$qty_scanned} = _tqty_ {$partuom}{$be}";
        $uomDesc = "{$pkguom} of {$qty_scanned} = {$tqty} {$partuom}";
        $uom = "{$b}{$uomDesc}{$be}";
        $tqty = 1;
    }
    if (isset($mst[1]["primary_bin"])) $bin = $mst[1]["primary_bin"];
    else $bin = "";
    $binPrompt = "Primary Bin:";
    if (trim($bin) == "") {
        if (isset($prodline)) {
            $bin = $prodline["pl_perfered_zone"] . "-" . sprintf("%02d", $prodline["pl_perfered_aisle"]);
            $binPrompt = "Preferred Zone-Aisle:";
        } else $bin = "No Primary Bin Set";
    } // end bin is empty
    $prefBin = $bin;
    $bigBin = "";
    if ($prefBin <> "") {
        $w = "";
        $k = explode("-", $prefBin);
        if (isset($k[0])) $w = $k[0];
        if (isset($k[1])) $w .= " {$k[1]}";
        $bigBin = <<<HTML
<div style="font-weight: 900;font-size: 65px;position: relative; right: 0px;">{$w}</div>
HTML;
    }
    if ($bigBin == "") $bigBin = "<br>";

//toDo  Correct qty to be qty 1, add span with total qty extended to eaches
    if (!isset($ts)) $ts = time();
    $htm = <<<HTML
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="thisprogram" value="{$thisprogram}">
  <input type="hidden" name="vendor" value="{$vendor}">
  <input type="hidden" name="func" value="scanBin">
  <input type="hidden" name="ts" value="{$ts}">
  <input type="hidden" name="shadow" value="{$shd}">
  <input type="hidden" name="lookPO" value="2">
  <input type="hidden" name="comp" value="{$part["comp"]}">
  <input type="hidden" name="UPC" value="{$part["upc"]}">
  <input type="hidden" name="PPL" value="{$p_l}">
  <input type="hidden" name="PPN" value="{$pn}">
  <input type="hidden" name="PPD" value="{$pdesc}">
  <input type="hidden" name="partUOM" value="{$partuom}">
  <input type="hidden" name="pkgUOM" value="{$pkguom}">
  <input type="hidden" name="pkgQty" value="{$qty_scanned}">
  <input type="hidden" name="uomDesc" value="{$uomDesc}">
  <input type="hidden" name="tqty" value="{$tqty}">

  <div class="w3-row-padding w3-medium w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-{$msgcolor}">
         {$msg}
      <div>
      <div class="w3-container w3-{$color}">
<span class="w3-clear">
{$b}{$p_l} {$pn}{$be}
&nbsp;{$pdesc}</span>
<br>
<span class="w3-clear">
        <label>Qty</label>
<input type="number" min="0" max="99999" class="w3-white" value="{$tqty}" name="qtyRecvd" onchange="recalc_qty(this.value,{$qty_scanned});">
<span id="extQty" name="extQty">{$uom}</span>
{$bigBin}
{$binPrompt} <strong>{$bin}</strong>
</span>
<br>
        <div class="w3-clear">
                <label>Scan Bin</label>
<input type="text" class="w3-white" onchange="do_bin();" value=" " name="scaninput" placeholder="Enter Bin or Tote...">
<br>
<br>

</div>
      </div>
    </div>
  </div>
 </form>
{$bsound}
<script>
function recalc_qty(qtyrec,pkgqty)
{
 var tq=(qtyrec * pkgqty);
 document.form1.tqty.value=tq;
 var extq=document.form1.uomDesc.value;
 document.getElementById('extQty').innerHTML=extq.replace("_tqty_",tq);
}
</script>
HTML;
    return $htm;
} // end frmtBin
function setTitle($POs, $HPO, $vendor)
{
    $ret = "";
    $_SESSION["rf"]["POs"] = $POs;
    $w = "";
    $comma = "";
    foreach ($POs as $key => $p) {
        $_SESSION["rf"]["HPO"][$key] = $HPO[$key];
        $z = $HPO[$key];
        $w .= "{$comma} {$z}";
        $comma = ",";
    } // end foreach POs
    $rt1 = $_SESSION["rf"]["recvTo"];
    $rt = "Tote";
    if ($rt1 == "b") $rt = "Bin";
    $w1 = "PO";
    if (count($POs) > 1) $w1 = "PO&apos;s";
    $ret = " Receiving {$vendor} {$w1}:{$w} to {$rt}";
    return $ret;
} // end setTitle

function count_batch($db, $batch)
{
    $ret = 0;
    $SQL = <<<SQL
select count(*) as cnt
from RCPT_SCAN
where batch_num = {$batch}
SQL;

    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $ret = $db->f("cnt");
        }
        $i++;
    } // while i < numrows
    return $ret;
} // end count_batch
function get_batch($db, $batch)
{
    // args db= connection to db, batch = batchnum
    $ret = array();
    $ret["status"] = -35;
    if ($batch > 0) {
        $SQL = <<<SQL
select * from RCPT_BATCH
where batch_num = {$batch}

SQL;
        $SQL1 = <<<SQL
 select RCPT_INWORK.wms_po_num, host_po_num
 from RCPT_INWORK,POHEADER
where batch_num = {$batch}
  and POHEADER.wms_po_num = RCPT_INWORK.wms_po_num

SQL;
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $ret["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
        if (count($ret) > 1) $ret["status"] = 1;
        $rc = $db->query($SQL1);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $ret["POs"][$i]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows

    } // end batch > 0
    return $ret;
} // end get batch
function get_batchDetail($db, $batch, $shadow, $user)
{
    $ret = array();
    $SQL = <<<SQL
 select	 batch_num,
	 line_num,
	 pkgUOM,
	 scan_upc,
	 po_number,
	 po_line_num,
	 scan_status,
	 scan_user,
	 pack_id,
	 shadow,
	 partUOM,
	 line_type,
	 pkgQty,
	 scanQty,
	 totalQty,
	 timesScanned,
         recv_to
 from RCPT_SCAN
 where batch_num = {$batch}
   and shadow = {$shadow}
   and scan_user = {$user}

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows and $db->Record) {
            foreach ($db->Record as $key => $data) {
                if (!is_numeric($key)) {
                    $ret["$key"] = $data;
                }
            }
        }
        $i++;
    } // while i < numrows
    return $ret;
} // end get_batchDetail
function setRequest($comp, $vendor, $thisprogram, $timestamp)
{
    $r = array();
    $r["thisprogram"] = $thisprogram;
    $r["vendor"] = $vendor;
    $r["func"] = "selectPO";
    $r["lookPO"] = 1;
    $r["ts"] = $timestamp;
    $r["upc"] = "";
    $r["comp"] = $comp;
    $r["scaninput"] = "";
    return $r;
} // end setRequest
?>
