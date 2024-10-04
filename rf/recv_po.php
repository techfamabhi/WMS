<?php
//04/23/19 dse move bin validation to tmp_bin_xref instead of WHSELOC
//06/22/22 dse update updQty to new parthist bins
//09/21/22 dse move update inv to server
//10/03/22 dse correct tote type and target zone
//01/03/22 dse correct tote not working, recvTo was being ignored
//01/04/22 dse Added Allowing Not on PO parts
//05/06/24 dse Added warning if over-receiving a part
//05/13/24 dse Added qty reality check
//05/21/24 dse Changed to use latest batch# so don't have to merge batches
//06/12/24 dse Changed warning if over-receiving a part, brought up interem screen for OK or cancel
//06/17/24 dse Changed RCV in tote Hdr to RCS if option 27 is on

/*TODO
if poNumber is set, check it for valid PO, if not, set topColor to red
when scanning tote, tote must have a tote_location of " " or "RCV"
otherwise, don't let them use that tote

Once Part is scanned, display;
PO Qty Ordered,
Qty all ready received
last bin (if possible)
need to figure which PO a part pertains to and pass and store until saved

 add company to PO search

Chrome has a problem with autoplay, haven't been able to correct it.


//toDo  Correct qty to be qty 1, add span with total qty extended to eaches
  also, line type for RCPT_SCAN needs to be populated from POITEMS line type

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
require_once("../include/chklogin.php");
if (isset($_REQUEST) and count($_REQUEST) < 1) {
    //if (isset($_SESSION["rf"]["POs"])) unset($_SESSION["rf"]);
    //if (isset($_SESSION["rf"]["HPOs"])) unset($_SESSION["rf"]);
    unset($_SESSION["rf"]);
    session_write_close();
    session_start();
}
//echo "<pre>1";
//print_r($_REQUEST);
//print_r($_SESSION);
//echo "</pre>";

//temp
$main_ms = 1;
$comp = 1;
$topColor = "blue";
if (isset($_REQUEST["validPO"]) and $_REQUEST["validPO"] == "red") $topColor = "red";
require($_SESSION["wms"]["wmsConfig"]);
require_once("{$wmsInclude}/cl_rf1.php");
require_once("{$wmsInclude}/get_table.php");
require_once("{$wmsInclude}/cl_addupdel.php");
require_once("{$wmsInclude}/cl_inv.php");
require_once("{$wmsInclude}/wr_log.php");
require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/get_option.php");
require_once("{$wmsInclude}/restSrv.php");
require_once("collapse.php");
$RESTSRV = "http://{$wmsIp}{$wmsServer}/PO_srv.php";
$TOTESRV = "http://{$wmsIp}{$wmsServer}/RcptLine.php";

$db = new WMS_DB;
//$db->DBDBG="/tmp/dave.dbg";
//echo "<pre>2";
//print_r($_SESSION["rf"]);
//echo "</pre>";
$opt = array();
$opt[20] = get_option($db, $comp, 20);
$opt[24] = get_option($db, $comp, 24);
$opt[27] = get_option($db, $comp, 27);

if (isset($_REQUEST["func"]) and $_REQUEST["func"] == "cancel") {
    $func = "scanPart";
    $scaninput = "";
    $_REQUEST["scaninput"] = "";
    $_REQUEST["func"] = "scanPart";
}
if (isset($_REQUEST["scaninput"]) and $_REQUEST["scaninput"] == "complete") { // flag recpt done
    if (isset($_SESSION["rf"]["RECEIPT"])) $batch = $_SESSION["rf"]["RECEIPT"];
    else $batch = 0;

    if ($batch > 0) {
        $req = array("action" => "flagRcptDone",
            "batch_num" => $batch,
            "comp" => $comp
        );
        $ret = restSrv($TOTESRV, $req);
        $rdata = (json_decode($ret, true));
        //print_r($rdata);
        if (!isset($rdata["error"])) {
            unset($_SESSION["rf"]);
            session_write_close();
            session_start();
        } // end no error
        // if flaging is ok, check if open lines, if not receive the batch
        $ret = checkOpenLines($RESTSRV, $comp, $batch);
        if ($ret !== false) {
            $rdata = (json_decode($ret, true));

//print_r($rdata);
//exit; // temp
        }
    } // end batch > 0
    $_REQUEST["scaninput"] = "";
    if (isset($scaninput)) unset($scaninput);
    unset($_REQUEST);
} // flag recpt done


if (isset($_SESSION["REQDATA"])) {
    $w = "";
    if (isset($_SESSION["REQDATA"]["ts"])) $w = $_SESSION["REQDATA"]["ts"];
    if (isset($_REQUEST["ts"])) $w1 = $_REQUEST["ts"]; else $w1 = time();
    //if ($_REQUEST == $_SESSION["REQDATA"])
    if ($w == $w1 and 1 == 1) { // looks like they refreshed the screen
//echo "<pre>";
//print_r($_REQUEST);
//exit;
        if (isset($_REQUEST["func"]) and $_REQUEST["func"] == "scanBin") { // reset back to scan the part again
            $r = setRequest($_REQUEST["comp"], $_REQUEST["vendor"], $_REQUEST["thisprogram"], $w);
            $r["msg"] = "Cancelled, please re-scan Part";
            $r["msg"] = "";
            $_REQUEST = $r;
            unset($r);
            unset($w);
            unset($w1);
        } // reset back to scan the part again
    } // looks like they refreshed the screen
} // end REQDATA is set
if (isset($_REQUEST)) {
    $_SESSION["REQDATA"] = $_REQUEST;
    foreach (array_keys($_REQUEST) as $w) {
        $$w = $_REQUEST[$w];
    }
}
//echo "<pre>1";
//print_r($_REQUEST);
//print_r($_SESSION);
//echo "</pre>";

if (isset($_REQUEST["func"])) $func = $_REQUEST["func"]; else $func = "";
if (isset($func) and $func == "notOnPO") {
    if (isset($B1) and $B1 == "Ok") {
        // add part to PO
        // Add part to PO here
        $req = array(
            "action" => "addItemToPo",
            "po" => $poi_po_num,
            "comp" => $comp,
            "shadow" => $shadow,
            "p_l" => $p_l,
            "part_number" => $part_number,
            "uom" => $uom,
            "qty" => $qty,
            "pdesc" => $pdesc,
            "upc" => $upc,
            "mdse_price" => $mdse_price,
            "core_price" => $core_price,
            "weight" => $weight,
            "case_uom" => $case_uom,
            "case_qty" => $case_qty
        );

        $ret = restSrv($TOTESRV, $req);
        $rdata = (json_decode($ret, true));
        if (isset($rdata["insertItem"]) and $rdata["insertItem"] > 0) { // line has been added
            $htm = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
</head>
<body onload="document.form1.submit();">
 <form name="form1" action="{$thisprogram}" method="get">
<input type="hidden" name="thisprogram" value="{$thisprogram}">
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="msg" id="msg" value="Part Added to PO">
  <input type="hidden" name="scaninput" value="{$upc}">
  <input type="hidden" name="recvTo" value="{$recvTo}">
  <input type="hidden" name="recvType" value="{$recvType}">
  <input type="hidden" name="lookPO" value="{$lookPO}">
 </form>
</body>
</html>

HTML;
            echo $htm;
            exit;
        } // line has been added
        else { // insert of item failed
            echo "<pre>Insert of new Item Failed\n\n";
            print_r($rdata);
            exit;
        } // insert of item failed

        /*
[vendor] => BBC
    [thisprogram] => /wms/rf/recv_po.php
    [func] => scanPart
    [recvTo] => b
    [recvType] => 1
    [lookPO] => 1
    [scaninput] =>  24006

*/

//echo "<pre>1";
//print_r($req);
//print_r($rdata);
////print_r($_REQUEST);
//echo "</pre>";
//exit;
    } // user pressed Add It
    else {
        $func = "scanPart";
        $_REQUEST["func"] = "scanPart";
        $_REQUEST["scaninput"] = "";

        if (isset($B2)) unset($B2);
        if (isset($B1)) unset($B1);
    }
} // end not on Po
if ($func == "vendSrch") {
    $htm = <<<HTML
<html>
 <head>
 </head>
 <body onload="document.form1.submit();">
  <form name="form1" action="vendSrch.php" method="get">

HTML;
    foreach ($_REQUEST as $key => $val) {
        if (!is_array($val) and $key <> "vendor")
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

if (isset($_REQUEST["poNumber"])) $poNumber = $_REQUEST["poNumber"]; else $poNumber = "";
if (isset($_REQUEST["vendor"])) $vendor = $_REQUEST["vendor"]; else $vendor = "";
if ($func == "vendor" and $poNumber <> "" and $vendor == "") {
    $w = getPO($poNumber);
    if (isset($w["wms_po_num"])) { // valid host po#
        $_REQUEST["func"] = "addedPO";
        $_REQUEST["lookPO"] = 1;
        $_REQUEST["scaninput"] = "";
        $_REQUEST["POs"][0] = $w["wms_po_num"];
        $_REQUEST["HPO"][0] = $w["host_po_num"];
    }

}

if ($func == "addedPO") {
    if (isset($_REQUEST["poNumber"])) { // got at least 1 po#
        $w = getPO($_REQUEST["poNumber"]);
        if (isset($w["wms_po_num"])) { // valid host po#
            $_REQUEST["func"] = "selectPO";
            $_REQUEST["lookPO"] = 1;
            $_REQUEST["scaninput"] = "";
            $_REQUEST["POs"][0] = $w["wms_po_num"];
            $_REQUEST["HPO"][0] = $w["host_po_num"];
        }

        $i = 1;
        if (isset($_REQUEST["poNum"]) and count($_REQUEST["poNum"]) > 0) { // more than 1 po
            foreach ($_REQUEST["poNum"] as $key => $p) {
                if (trim($p) <> "") {
                    $y = getPO($p);
                    if (isset($y["wms_po_num"])) {
                        $_REQUEST["POs"][$i] = $y["wms_po_num"];
                        $_REQUEST["HPO"][$i] = $y["host_po_num"];
                        $i++;
                    } // end p <> ""
                } // end foreach poNum
            }  // more than 1 po
        } // got at least 1 po#
        unset($_REQUEST["numberPOs"]);
        unset($_REQUEST["poNumber"]);
        unset($_REQUEST["poNum"]);
    } // valid host po#
} // end func = addedPO

if (isset($func) and $func == "Cancel") {
    $r = setRequest($comp, $vendor, $thisprogram, $w);
}


require_once("ddform.php");
require_once("cl_PARTS2.php");
if (!function_exists("get_contrl")) {
    require_once("{$wmsInclude}/get_contrl.php");
}

$sounds = "../assets";
$thisprogram = $_SERVER["SCRIPT_NAME"];

if (isset($B2) and $B2 == "ClEaR") {
    $url = $thisprogram;
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
    echo $htm;
    exit;
} // end B2 = ClEaR

if (isset($_REQUEST["scaninput"])) $scaninput = trim($_REQUEST["scaninput"]); else $scaninput = "";
if (isset($_REQUEST["func"])) $func = $_REQUEST["func"]; else $func = "";
if (isset($_REQUEST["UPC"])) $UPC = $_REQUEST["UPC"]; else $UPC = "";
if (isset($_REQUEST["comp"])) $comp = $_REQUEST["comp"]; else $comp = $main_ms;
if (isset($_REQUEST["msg"])) $msg = $_REQUEST["msg"]; else $msg = "";
if (isset($_REQUEST["msg2"])) $msg2 = $_REQUEST["msg2"]; else $msg2 = "";
if (isset($_REQUEST["msgColor"])) $msgColor = $_REQUEST["msgColor"]; else $msgColor = "";
if (isset($_REQUEST["POs"])) $POs = $_REQUEST["POs"];
else if (isset($_SESSION["rf"]["POs"])) $POs = $_SESSION["rf"]["POs"];
else $POs = array();
if (isset($_REQUEST["HPO"])) $HPO = $_REQUEST["HPO"];
else if (isset($_SESSION["rf"]["HPO"])) $HPO = $_SESSION["rf"]["HPO"];
else $HPO = array();
if (isset($_REQUEST["vendor"])) $vendor = $_REQUEST["vendor"];
else $vendor = "";
if (trim($vendor) == "" and isset($_SESSION["rf"]["vend"]["vendor"]) and count($POs) > 0)
    $vendor = $_SESSION["rf"]["vend"]["vendor"];

if (isset($_REQUEST["lookPO"])) $lookPO = $_REQUEST["lookPO"]; else $lookPO = 0;
$use_sess = false;
if (isset($_REQUEST["recvType"])) $recvType = $_REQUEST["recvType"]; else $use_sess = true;
if (isset($_REQUEST["recvTo"])) $recvTo = $_REQUEST["recvTo"]; else $use_sess = true;

if (isset($recvTo) and $recvTo <> "") $_SESSION["rf"]["recvTo"] = $recvTo;

if ($use_sess) {
    if (isset($_SESSION["rf"]["recvTo"])) $recvTo = $_SESSION["rf"]["recvTo"];
    else $recvTo = $opt[24];
    if (isset($_SESSION["rf"]["recvType"])) $recvType = $_SESSION["rf"]["recvType"];
    else $recvType = "1";
} // end use sess

if ($func == "vendor") $vendor = $scaninput;

$opt[21] = get_option($db, $comp, 21);
if (trim($opt[21]) == "") $opt[21] = "PO";
$pg = new displayRF;
$pg->viewport = "1.10";
$pg->dispLogo = false;
$pg->title = "Receive PO, ASN or Customer Return";
$jsx = <<<HTML
<script src="/jq/jquery-1.12.4.js" type="text/javascript"></script>
<script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
shortcut.add("F1",function() {
        do_reset();
});
shortcut.add("return", function() {
 var a=document.form1.poNumber;

       //if (document.getElementById("validPO").value == "green") document.form1.submit();
       if (a.value !== "") 
       {
        var b=document.getElementById("validPO");
        var ok=true;
        if (ok)
        {
         validatePO(a,1);
         b=document.getElementById("validPO");
         if (b.value !== "") 
         {
          // alert(document.getElementById("validPO").value);
          document.form1.submit();
          ok=false;
          return false;
         }
        } 
       }
        else return false ;
});
</script>

HTML;
$pg->jsh;
if ($func == "") $pg->jsh = $jsx;
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
function do_resetpo()
{
 document.form1.poNumber.value="";
 document.form1.submit();
}
function do_complete()
{
 document.form1.scaninput.style.display='none';
 document.form1.scaninput.value="complete";
 document.form1.submit();
}
function do_review(rcpt,user)
{
 document.form1.scaninput.style.display='none';
 document.form1.action="rcpt_review.php?rcpt=" + rcpt + "&user=" + user;
 document.form1.scaninput.value="review";
 document.form1.submit();
}
function chg_prompt(pType)
{
 //var prm = pType.options[pType.selectedIndex].text;
 //document.getElementById('typeLabel').innerHTML=prm + ' #';
 return true;
}
function add_more()
{
  document.form1.func.value="addMore";
  document.form1.submit();
 //addFields(2);
 //return true ;
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
     morePOs.appendChild(document.createTextNode("Document " + (parseInt(i)) + "  "));
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
//alert(morePOs.innerHTML);
}

function validatePO(fld,fr=0)
{
 var po=fld.value;
 var result;
 var v=document.getElementById('vendor');
 var vendor=v.value;
 var params = "&po=" + po + "&vend=" + vendor;
 // var url="http://{$wmsIp}/{$wmsHome}/rf/checkPO.php";
 var url="http://" + location.host + "/{$wmsHome}/rf/checkPO.php";
 
 //alert(url);
 //alert(location.host);
//alert("setting validPO to blank, line 421");
 //document.getElementById("validPO").value = "";

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
     var msg="Vendor: " + result;
 //alert(result);
     if (vendor !== "" && vendor !== result)
      {
//alert("in 1");
       msg=result + " is not the same Vendor, last PO was vendor: " + vendor;
       fld.value="";
       document.getElementById("theMessage").innerHTML = msg
       document.getElementById("mWindow").className = "w3-container w3-red w3-padding-8";
//alert("setting validPO to red, line 453");
       document.getElementById("validPO").value = "red";
       if (fr > 0)
        {
         fld.focus();
         return false;
        }
      }
    else if (result == "NOTFOUND") {
//alert("in 2");
       msg="PO " + po + " is Not on File";
       fld.value="";
       document.getElementById("theMessage").innerHTML = msg
       document.getElementById("mWindow").className = "w3-container w3-red w3-padding-8";
//alert("setting validPO to red, line 464");
       document.getElementById("validPO").value = "red";
       if (fr > 0)
        {
         fld.focus();
         return false;
        }
    }
    else
     {
      document.getElementById("theMessage").innerHTML = msg
      document.getElementById("mWindow").className = "w3-container w3-green w3-padding-8";
//alert("setting validPO to green, line 502");
      document.getElementById("validPO").value = "green";
      document.form1.func.value="addedPO";
      v.value=vendor;
      if (fr > 0) document.form1.submit();
      else return true;
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
function chk_seltr(x)
{
  var ele = document.getElementsByName('POs[]');
  if (typeof(ele.checked) !== 'undefined')
  {
  var i=x.rowIndex - 1;
  //if (!i) i=x-1;
  if (ele[i].checked == false)
  {
   ele[i].checked = true;
  }
 else
  {
   ele[i].checked = false;
  }
  }
  chk_sel();
}
function chk_selcb(x)
{
  var ele = document.getElementsByName('POs[]');
  var i=x - 1;
  if (!i) i=x-1;
  if (ele[i].checked == false)
  {
   ele[i].checked = true;
  }
 else
  {
   ele[i].checked = false;
  }
  chk_sel();
}

</script>

HTML;

$pg->jsh .= "\n" . collapseCss();

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
    $oldFunc = $func;
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
        if ($oldFunc == "vendor") {
            unset($poNumber);
            unset($_REQUEST["poNumber"]);
            unset($poNum);
            unset($_REQUEST["poNum"]);
        }
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

//if (isset($_SESSION["rf"]["POs"]))
//{
    //unset($_SESSION["rf"]["POs"]);
    //unset($_SESSION["rf"]["HPO"]);
//}
    $rT1 = "";
    $rT2 = "";
    if ($recvTo == "a") $rT1 = " checked";
    if ($recvTo == "b" or $recvTo == "") $rT2 = " checked";
    if (!isset($poNumber)) $poNumber = "";
    $amLink = "add_more();";
// remming out add more icon, can add it back in later
    $dunsel = <<<HTML
        <a href="#" onclick="{$amLink}"><img src="../images/add_more.png" width="32px" height="32px" border="0" title="Add More"/></a>
HTML;
    $dunsel = "&nbsp;";
    $poInput = <<<HTML
   <tr>
    <td class="FieldCaptionTD" >Document #</td>
    <td>
     <table style="overflow:auto;">
      <tr>
       <td>
        <input type="text" class="w3-white" name="poNumber" value="{$poNumber}" onchange="validatePO(this);">
       </td>
       <td>
        {$dunsel}
       </td>
       <td>
        <a href="#" onclick="srchVendor()"><img src="../images/vendsrch.png" width="32px" height="32px" border="0" title="Search by Vendor"/></a>
       </td>
      </tr>
     </table>
    </td>
   </tr>

HTML;

    if ($func == "addMore") {
        if (!isset($numberPOs)) $numberPOs = 1;
        $numberPOs = $numberPOs + 3;
        for ($i = 1; $i <= $numberPOs; $i++) {
            $f = "poNum[{$i}]";
            if (isset($$f)) $val = $$f; else $val = "";
            $poInput .= <<<HTML
    <tr>
     <td class="FieldCaptionTD" >Document {$i}</td>
     <td>
      <input class="w3-white" type="text" name="{$f}" value="{$val}" onchange="validatePO(this);">
     <td>
    <tr>

HTML;
        } // end for i loop

    } // end addMore

    $rt = array();
    $rt[1] = "";
    $rt[2] = "";
    $rt[3] = "";
    $rt[4] = "";
    $rt[5] = "";
    if (isset($recvType)) $rt[$recvType] = " selected";

    $recvngType = <<<HTML
<tr role="row">
       <td class="FieldCaptionTD" >Receiving Type</td>
       <td><select class="w3-white" onchange="chg_prompt(this);" name="recvType" placeholder="Select Type">
             <option value="1"{$rt[1]}>Purchase Order</option>
             <option value="2"{$rt[2]}>ASN</option>
             <option value="3"{$rt[3]}>Transfer</option>
             <option value="4"{$rt[4]}>Customer Return</option>
             <option value="5"{$rt[5]}>Unexpected Receipt</option>
            </select>
       </td>
      </tr>

HTML;
    $recvngType = "";

    $htm = <<<HTML
<div class="col-md-16">
 <div class="panel-body">
  <div class="table-responsive">
   <form name="form1" action="{$thisprogram}" method="get">
   <input type="hidden" name="func" value="vendor">
   <input type="hidden" name="vendor" id="vendor" value="">
   <input type="hidden" name="thisprogram" value="{$thisprogram}">
   <input type="hidden" name="validPO" id="validPO" value="">
   <input type="hidden" name="msg" id="msg" value="{$msg}">
   <input type="hidden" name="numberPOs" id="numberPOs" value="1">
   <div class="w3-quarter">
    <div id="mWindow" class="w3-{$topColor}">
     <table role="table" class="table table-bordered table-striped">
      <thead role="rowgroup">
       <tr role="row">
        <td colspan="2">
         <span id="theMessage" style="word-wrap: normal; font-weight: bold; font-size: large; text-align: cput;"></span>
        </td>
       </tr>
      </thead>
      <tr role="row">
       <td align="center" colspan="2" class="w3-white">
       </td>
      </tr>
      <tr>
       <td class="FieldCaptionTD" >Receive To</td>
       <td><input type="radio" id="rT1" name="recvTo" value="a"{$rT1}>
           <label for "rT1">Tote, Cart or Pallet</label>
           <br>
           <input type="radio" id="rT2" name="recvTo" value="b"{$rT2}>
           <label for "rT2">Direct to Bin</label>
       </td>
      </tr>
{$poInput}
      <tr>
       <td colspan="2">
        <span id="theMessage" style="word-wrap: normal; font-weight: bold; font-size: large; text-align: cput;"></span>
       </td>
      </tr>
      <tr>
       <td colspan="2">
        <button class="binbutton" id="B1" name="B1" value="Submit" onclick="document.form1.submit();">Submit</button>
        <button class="binbutton" id="B2" name="B2" value="ClEaR" onclick="do_resetpo()">Clear</button>
       </td>
      </tr>
     </table>
    </div>
   </div>
   </form>
  </div>
 </div>
</div>

HTML;
} // end vendor is empty

if ($lookPO > 0 and $func == "scanBin") {
    $numRows = 0;
    $toteNOZ = 0;
    $theBin = strtoupper(trim($_REQUEST["scaninput"]));
    if ($recvTo == "a") {
        require_once("{$wmsInclude}/cl_TOTES.php");
        $bincls = new TOTE;
        $toteInfo = $bincls->getToteHdr($theBin, $comp);
        $req = array("action" => "getToteLoc",
            "tote_id" => $theBin,
            "comp" => $comp
        );
        $ret = restSrv($TOTESRV, $req);
        $rToteInfo = (json_decode($ret, true));

//echo "<pre>thebin={$theBin} rToteInfo ";
//print_r($toteInfo);
//print_r($rToteInfo);
//print_r($bincls);
//exit;
        $ttzone = "";
        if (isset($rToteInfo[1]["target_zone"])) $ttzone = trim($rToteInfo[1]["target_zone"]);
        $numRows = $bincls->numRows;

        $updTote = false;
        if ($numRows > 0 and $toteInfo["tote_status"] < 2) $updTote = true;
        if (!$updTote and $numRows > 0 and (trim($toteInfo["tote_location"]) == "" or $toteInfo["tote_type"] == "RCV" or $toteInfo["tote_type"] == "RCS")) $updTote = true;
        if (1 == 2 and isset($prefZone) and $ttzone <> "") { // check if part target zone matches the totes target zone
            $w = explode("|", $prefZone);
            if ($ttzone <> $w[0]) {
                $numRows = 0;
                $toteNOZ = 1;
                $updTote = false;
                $message = "Tote is slated for zone {$ttzone}, part goes to {$w[0]}";
            }
        } // check if part target zone matches the totes target zone
        if ($updTote) { // tote is ok to use
            $ref = "";
            if (isset($RECEIPT)) $ref = $RECEIPT;
            $tt = "RCV";
            if ($opt[27] > 0) $tt = "RCS";
            $rc = $bincls->updToteHdr($theBin, $comp, 2, $tt, $ttzone, $ref);
        } // tote is ok to use
        //$bincls->addItemToTote($theBin,$shadow,$qtyRecvd,$partUOM);
    } // recvTo == "a"
    else {
        require_once("{$wmsInclude}/cl_bins.php");
        $bincls = new BIN;
        $bincls->Company = 1;
        $bincls->User = $UserLogin;
        $bincls->lookUp($theBin, 0);
        $numRows = $bincls->numRows;
        if (isset($bincls->binInfo) and count($bincls->binInfo) > 0 and $numRows == 0) $numRows = 1;
    } // recvTo <> "a"
//echo "<pre>";
//print_r($bincls);
//print_r($bincls);
//exit;
    if ($numRows < 1) { // invalid bin
        $func = "scanPart";
        $scaninput = $UPC;
        if ($toteNOZ < 1) {
            $msg = "Invalid Bin";
            if ($recvTo == "a") $msg = "Invalid Tote";
        } else $msg2 = $message;
    } // invalid bin
    else { // good part, good PO, good bin, save it
        //echo "<pre>ood part, good PO, good bin, save it {$scaninput} {$theBin}\n";
        //print_r($_REQUEST);
        //print_r($_SESSION);
        //if (isset($prefZone)) print_r($prefZone);
        //if (isset($prodline)) print_r($prodline);
//exit;
        if (isset($_SESSION["rf"]["RECEIPT"])) {
            // set request data to call server to update
            $tmpTotes = array();
            $theBin = strtoupper(trim($_REQUEST["scaninput"]));
            if (isset($_SESSION["rf"]["totes"])) {
                if (!isset($_SESSION["rf"]["totes"][$theBin]))
                    $tmpTotes[$theBin] = $theBin;
            } // totes are set
            else { // totes are not set
                $tmpTotes[$theBin] = $theBin;
            } // totes are not set

            $req = array(
                "action" => "recvReciept",
                "batch" => $_SESSION["rf"]["RECEIPT"],
                "RECEIPT" => $_SESSION["rf"]["RECEIPT"],
                "userId" => $_SESSION["wms"]["UserID"],
                "recvType" => $_SESSION["rf"]["recvType"],
                "recvTo" => $_SESSION["rf"]["recvTo"],
                "POs" => $POs,
                "HPO" => $HPO,
                "vendor" => $vendor,
                "shadow" => $shadow,
                "comp" => $comp,
                "UPC" => $UPC,
                "PPL" => $PPL,
                "PPN" => $PPN,
                "PPD" => $PPD,
                "partUOM" => $partUOM,
                "pkgUOM" => $pkgUOM,
                "pkgQty" => $pkgQty,
                "totalQty" => $tqty,
                "prefZone" => $prefZone,
                "qtyRecvd" => $qtyRecvd,
                "BinTote" => $scaninput,
                "totes" => $tmpTotes
            );
            unset($tmpTotes);
// Remmed this out 11/21/22
//echo "<pre> {$RESTSRV}";
//print_r($req);
// Remmed this out 11/21/22
            $ret = restSrv($RESTSRV, $req);
            $ret = (json_decode($ret, true));
// Remmed this out 11/21/22
//print_r($ret);
//exit;
// Remmed this out 11/21/22
            if (1 == 2) { // 1 = 2 moved this to server
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
                    $whichPO = setPOforPart($db, $shadow, $POs, $qtyRecvd);
                    $po = $POs[$whichPO];
                    // get line # from selected PO
                    $tmp = chkPartOnPO($db, $shadow, array(0 => $po), $qtyRecvd);
                    $poline = 0;
                    $qtyOrd = 0;
                    if (isset($tmp[1]["poi_line_num"])) {
                        $poline = $tmp[1]["poi_line_num"];
                        $qtyOrd = $tmp[1]["qty_ord"];
                    }
                    $stockd = 0;
                    //if ($_SESSION["rf"]["recvTo"] == 'b') $stockd=($qtyRecvd * $pkgQty);
                    if ($_SESSION["rf"]["recvTo"] == 'b') $stockd = ($qtyRecvd);


                    $hpo = $HPO[$whichPO];
                    wr_log("/tmp/testfunc.txt", "count_batch({$batch});");
                    $lines = count_batch($db, $batch);
                    $next_line = $lines + 1;
                    $reqdata["batch_num"] = $batch;
                    $reqdata["line_num"] = $next_line;
                    $reqdata["pkgUOM"] = $pkgUOM;
                    $reqdata["scan_upc"] = $UPC;
                    $reqdata["po_number"] = $po;
                    $reqdata["po_line_num"] = $poline;
                    $reqdata["scan_status"] = 0;
                    $reqdata["scan_user"] = $theUser;
                    $reqdata["pack_id"] = $scaninput;
                    $reqdata["shadow"] = $shadow;
                    $reqdata["partUOM"] = $partUOM;
                    $reqdata["line_type"] = "0"; // need to pass this too
                    $reqdata["pkgQty"] = $pkgQty;
                    $reqdata["scanQty"] = $qtyRecvd;
                    $reqdata["totalQty"] = $qtyRecvd;
                    $reqdata["timesScanned"] = 1;
                    $reqdata["recv_to"] = $_SESSION["rf"]["recvTo"];
                    $reqdata["qty_stockd"] = $stockd;
                    $reqdata["totalOrd"] = $qtyOrd;
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
                    $stockd = 0;
                    //if ($_SESSION["rf"]["recvTo"] == 'b') $stockd=($qtyRecvd * $pkgQty);
                    if ($_SESSION["rf"]["recvTo"] == 'b') $stockd = $qtyRecvd;

                    $reqdata["scanQty"] = $w["scanQty"] + $qtyRecvd;
                    //$reqdata["totalQty"]=($reqdata["scanQty"] * $pkgQty);
                    $reqdata["totalQty"] = $qtyrecvd;
                    $reqdata["timesScanned"] = $w["timesScanned"] + 1;
                    $reqdata["qty_stockd"] = $w["qty_stockd"] + $stockd;
                    $return_code = $upd->updRecord($reqdata, "RCPT_SCAN", $where);
                } // update qty in scan record
                $save_RCPTSAN = $reqdata;
                $save_RCPTSCAN_where = $where;

                $whichPO = setPOforPart($db, $shadow, $POs, $qtyRecvd);
                $po = $POs[$whichPO];
                $hpo = $HPO[$whichPO];
                //$qty=($qtyRecvd * $pkgQty);
                $qty = $qtyRecvd;
                $binType = substr($opt[21], 0, 1);
                if ($_SESSION["rf"]["recvTo"] == "b") { // recv to Bin, update WHSEQTY and add PARTHIST
                    $tmp = getPrice($db, $reqdata["po_number"], $reqdata["po_line_num"]);
                    if (isset($mst)) {
                        if (trim($mst["primary_bin"]) <> "") $binType = substr($opt[21], 1, 1);
                    } // end mst is set
                    if (2 == 2) {
                        // use cl_inv class
                        $tt = "RCV";
                        if ($opt[27] > 0) $tt = "RCS";
                        $sparams1 = array(
                            "wms_trans_id" => $po,
                            "shadow" => $shadow,
                            "company" => $comp,
                            "psource" => $vendor,
                            "user_id" => $theUser,
                            "host_id" => $hpo,
                            "ext_ref" => "Direct To Bin",
                            "trans_type" => $tt,
                            "in_qty" => $qty,
                            "uom" => $partUOM,
                            "floc" => $theBin,
                            "tloc" => "Received",
                            "inv_code" => "0",
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
                else { // ********************* Recv to Tote *****************************
//echo "<pre>";
                    //add tote to session
                    $lasttote = 0;
                    if (isset($_SESSION["rf"]["totes"])) {
                        if (!isset($_SESSION["rf"]["totes"][$theBin]))
                            $_SESSION["rf"]["totes"][$theBin] = $theBin;
                    } // totes are set
                    else { // totes are not set
                        $_SESSION["rf"]["totes"][$theBin] = $theBin;
                    } // totes are not set

                    $prefz = "";
                    $prefi = 0;
                    if (isset($prefZone)) {
                        $k = explode("|", $prefZone);
                        if (isset($k[0])) $prefz = $k[0];
                        if (isset($k[1])) $prefi = intval($k[1]);
                    }
                    if (isset($prodline)) {
                        $prefz = $prodline["pl_perfered_zone"];
                        //$prefi=sprintf("%02d",$prodline["pl_perfered_aisle"]);
                        $prefi = $prodline["pl_perfered_aisle"];
                    }

//add tote to RCPT_TOTE with bincls->updRcptTote($req)
                    $tt = "RCV";
                    if ($opt[27] > 0) $tt = "RCS";
                    $req = array(
                        "rcpt_num" => $batch,
                        "tote_id" => $theBin,
                        "rcpt_status" => 0,
                        "last_zone" => $tt,
                        "last_loc" => "",
                        "target_zone" => $prefz,
                        "target_aisle" => $prefi
                    );
//print_r($req);
                    $rc = $bincls->updRcptTote($req);

//add part to totedtl,
//echo "<pre>bin: {$theBin} shadow: {$shadow} qty: {$qtyRecvd} uom: {$partUOM}\n";
//exit;
                    $rc1 = $bincls->addItemToTote($theBin, $shadow, $qtyRecvd, $partUOM);

//add to parthist, with from bin "Receiving" and to bin of tote#
                    $req = array(
                        "wms_trans_id" => $po,
                        "shadow" => $shadow,
                        "company" => $comp,
                        "psource" => $vendor,
                        "user_id" => $theUser,
                        "host_id" => $hpo,
                        "ext_ref" => "To Putaway",
                        "trans_type" => $tt,
                        "in_qty" => $qty,
                        "uom" => $partUOM,
                        "floc" => $theBin,
                        "tloc" => "Received",
                        "inv_code" => "0",
                        "mdse_price" => 0.00,
                        "core_price" => 0.00,
                        "in_qty_core" => 0,
                        "in_qty_def" => 0,
                        "bin_type" => $binType
                    );
                    $trans = new invUpdate;
                    $rc2 = $trans->updQty($req, false);

//print_r($toteInfo);
//print_r($_REQUEST);
//exit;
                } // * END *************** Recv to Tote *****************************
                //echo "<pre>batch={$batch} w=";

                //echo "rc={$rc}\n";
                //echo "$return_code\n";
                //echo $SQL;
                //print_r($sparams);
                //print_r($_SESSION);
                //exit;

            }  // 1 = 2 moved this to server
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

        $htm = frmtPartScan($vendor, $msg, $topColor);
        $htm .= frmtPOdata($comp);

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
    //if (!isset($_SESSION["rf"]["RECEIPT"]) and count($POs) > 0)
    if (count($POs) > 0) {
// This needs to display current active recevings to see if user wants to join in 1
// have server get the receipt#
// if existing unfinished receipts, return the existing number
// else get a new one
        $p = "";
        $r = 0;
        $f = "host_po_num";
        if (isset($poNumber) and isset($numberPOs) and $numberPOs == 1 and $poNumber <> "") {
            $p = $poNumber;
        }
        if (isset($POs) and count($POs) == 1 and $POs[0] <> "") {
            $p = $POs[0];
            $f = "wms_po_num";
        }

        if ($p <> "") {
            $req = array("action" => "getBatchByPO",
                "company" => $comp,
                "{$f}" => $p
            );
            $rdata = restSrv($RESTSRV, $req);
            $ret = (json_decode($rdata, true));
            if (isset($ret["numRows"])) {
                if ($ret["numRows"] > 0) $r = $ret[$ret["numRows"]]["batch"];
            }
            $rcpt_num = $r;
        }

        if ($r == 0) $rcpt_num = get_contrl($db, $comp, "RECEIPT");
        if ($rcpt_num > 0) $_SESSION["rf"]["RECEIPT"] = $rcpt_num;
    }
    $pg->title = setTitle($POs, $HPO, $vendor);

    $htm = frmtPartScan($vendor, $msg, $topColor);
    $htm .= frmtPOdata($comp);
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
            $atype = $parts["Result"]["alt_type_code"];
            if ($atype < 0) $atype = -$atype; else $atype = 1;
            $part["packQty"] = $atype;
            $UPC = $upc;
            $part["comp"] = $main_ms;
            $shadow = $part["shadow_number"];
            $alt_type = $parts["Result"]["alt_type_code"];
            $UOM = $parts["Result"]["unit_of_measure"];
            $PKGUOM = $parts["Result"]["alt_uom"];
            if ($alt_type > 0) $qty_scanned = 1; else $qty_scanned = -$alt_type;
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
            if (strlen($upc) > 11) {
                $recvPo = urlencode(json_encode($_REQUEST));
                $url = "addUpc.php?UPC={$upc}&reqst={$recvPo}";
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
                echo $htm;
                exit;

//echo "<pre>{$url}";
//print_r($_REQUEST);
//print_r($_SESSION);
//exit;
            } // end partNumber > 11

            $htm = frmtPartScan($vendor, "Invalid Part Entered, Please try again", "red");
            $htm .= frmtPOdata($comp);
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
                $msg = "Part not found on any PO";
                if (count($POs) < 2) $msg = "Part not found on this PO";
                $htm = frmtNotOnPo($POs, $parts, $msg);
//echo "<pre>";
//print_r($parts);
                //exit;
                //load qty, bin and possible serial number inputs
                //here
            }
//echo "<pre>";
//print_r($poitems);
//exit;
            if ($poitems["numRows"] > 0) {
                $topColor = "green";
                $totRecv = $poitems["inRecv"];
                $totOrd = $poitems["totalOrd"];
                $openQty = $totOrd - ($totRecv + $poitems["totalPrevRecvd"]);
                $infoColor = "";
                if ($poitems["inRecv"] > 0) {
                    $recvInfo = "Ordered: {$totOrd}, Recvd so far: {$totRecv}";
                    if ($totRecv >= $totOrd) $infoColor = "yellow";
                    if (!isset($overRide) and $totRecv >= $totOrd) {
                        $overRecvd = true;
                        goto askOverRecv;
                    }
                }
                $htm = frmtBin($part, $vendor, $qty_scanned, $PKGUOM, $mst, $topColor, $msg, $recvInfo, $infoColor, $openQty);
            }
        } // end not a choose nuparts = 1
    } // its numeric must be a upc
    else { // scaninput == ""
        $pg->title = setTitle($POs, $HPO, $vendor);

        $htm = frmtPartScan($vendor, $msg, $topColor);
        $htm .= frmtPOdata($comp);

    } // end scaninput == ""
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
      <table class="table table-bordered table-striped table-hover">
       <tr>
        <th class="FieldCaptionTD" width="5%">Select</th>
        <th class="FieldCaptionTD">Document#</th>
        <th class="FieldCaptionTD">Date</th>
        <th class="FieldCaptionTD">Num Lines</th>
        <th class="FieldCaptionTD">Status</th>
        <th class="FieldCaptionTD">Due Date</th>
       </tr>
HTML;

    foreach ($po as $key => $row) {
        // do somthing with wms_po_num
        $k = $key;
        $statDesc = $row["po_status"];
        if ($row["po_status"] == -1) $statDesc = "BackOrders";
        if ($row["po_status"] == 0) $statDesc = "Not Received";
        if ($row["po_status"] == 1) $statDesc = "On Dock";
        if ($row["po_status"] == 2) $statDesc = "In Process";
        if ($row["po_status"] == 3) $statDesc = "In Putaway";
        if ($row["po_status"] == 4) $statDesc = "Updating";
        if ($row["po_status"] > 4) $statDesc = "Received";
        if ($row["po_status"] == 6) $statDesc = "BackOrders";

        // <tr onclick="chk_seltr(this);">
        $htm .= <<<HTML
       <tr>
        <td><input type="checkbox" name="POs[]" value="{$row["wms_po_num"]}" onclick="chk_seltr({$k});">
        <input type="hidden" name="HPO[]" value="{$row["host_po_num"]}">
</td>
        <td align="right">{$row["host_po_num"]}</td>
        <td>{$row["po_date"]}</td>
        <td align="right">{$row["num_lines"]}</td>
        <td>{$statDesc}</td>
        <td>{$row["est_deliv_date"]}</td>
       </tr>

HTML;
    } // end foreach po
    $htm .= <<<HTML
      <tr>
       <td align="center" colspan="6">
 <button class="binbutton" id="B1" name="B1" onclick="document.form1.submit();" disabled>Receive Selected</button>
    <button class="binbutton" type="button" value="Select Vendor" onclick="srchVendor();">Select Vendor</button></td>
       </td>
      </tr>
      </table>
      </form>
     </div>
    </div>
   </div>
HTML;

} // end POs

askOverRecv:
if (isset($overRecvd) and isset($part)) {
    $hiddens = "";

    foreach (array_keys($_REQUEST) as $w) {

        $hiddens .= <<<HTML
<input type="hidden" name="{$w}" value="{$_REQUEST[$w]}">

HTML;
    }
    $hiddens .= <<<HTML
<input type="hidden" name="overRide" value="0">

HTML;

    $formName = "form1";
    $color = "w3-yellow";
    $p_l = $part["p_l"];
    $pn = $part["part_number"];
    $shd = $part["shadow_number"];
    $pdesc = $part["part_desc"];

    $htm = <<<HTML

 <form name="{$formName}" action="{$thisprogram}" method="get">
{$hiddens}
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
     <div class="container {$color} w3-padding-8">
     <div class="w3-yellow">
      <div class="w3-padding-8 FormHeaderFont">{$recvInfo}
</div>
        <span class="w3-clear">
<strong>{$p_l} {$pn}</strong>
&nbsp;{$pdesc}</span>
<br>

        <div class="clear"></div>
      <div class="row">
       <div class="col-75">
        <table style="position:relative;left: 6px;" class="table table-bordered table-striped">
         <tr>
          <td colspan="5" class="FormSubHeaderFont w3-white">Are you sure you want to over receive this part</td>
         </tr>
         <tr>
          <td colspan="5" class="w3-yellow">
           <div>
            <input type="radio" style="height:35px; width:35px; vertical-align: middle;" onclick="do_submit();" value="1" name="R1"> <strong>OK</strong><br>
           </div>
           <div>
            <input type="radio" style="height:35px; width:35px; vertical-align: middle;" onclick="do_cancel();" value="2" name="R1"> <strong>Cancel</strong><br>
           </div>
          </td>
         </tr>
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>
        </table>
       </div>
      </div>
    <br>
     <div class="col-75" style="word-wrap: normal;font-weight: bold; font-size: large; text-align: cput;">{$recvInfo}
    </div>
     </div>
     </div>
    </div>
  </div>
 </form>
<script>
function do_submit()
{
 document.{$formName}.overRide.value=1;
 document.{$formName}.submit();
}
function do_cancel()
{
 document.form1.func.value="cancel";
 document.form1.submit();
}
</script>

HTML;

}

$pg->body = $htm;
//if (isset($_SESSION["rf"]["RECEIPT"]))
//{
//$pg->addMenuLink("javascript:do_review({$_SESSION["rf"]["RECEIPT"]},{$_SESSION["wms"]["UserID"]});","Review");
//$pg->addMenuLink("javascript:do_complete({$_SESSION["rf"]["RECEIPT"]});","Complete");
//} // end RECEIPT is set

//$buttons=loadButtons("SubMit|clrbtn|complete|review");
//$pg->body.=$buttons;
$pg->Bootstrap = true;
$pg->color = $topColor;
$pg->Display();
//echo "<pre>";
//print_r($_REQUEST);

//print_r($_SESSION["REQDATA"]);
//
//Old program

file_put_contents("/tmp/d.htm", $htm);
//echo "<pre>In Old Program\n";
//print_r($_REQUEST);
//print_r($_SESSION);


$htm = <<<HTML
</body>
</html>

HTML;
echo $htm;
exit;

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

function frmtPartScan($vendor, $msg, $color)
{
    global $thisprogram;
    global $recvTo;
    global $recvType;
    global $topColor;
    $userId = $_SESSION["wms"]["UserID"];
    $msgcolor = $color;
    if (trim($msg) == "" or substr($msg, 0, 5) == "Last:") {
        $msg .= "<br>";
        $msgcolor = "green";
        $TopColor = "green";
    } else {
        $msg = "<h4>{$msg}</h4><br>";
        $color = "red";
        $TopColor = "red";
    }
    $canAction = "do_resetp();";
    if (isset($_REQUEST["func"])) {
        if ($_REQUEST["func"] == "vendor"
            or $_REQUEST["func"] == "selectPO"
            or $_REQUEST["func"] == "addedPO") $canAction = "cancelPO();";
    }
    if (isset($_SESSION["rf"]["RECEIPT"])) $batch = $_SESSION["rf"]["RECEIPT"];
    else $batch = 0;
    $htm = <<<HTML
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="vendor" value="{$vendor}">
  <input type="hidden" name="thisprogram" value="{$thisprogram}">
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="recvTo" id="recvTo" value="{$recvTo}">
  <input type="hidden" name="recvType" id="recvType" value="{$recvType}">
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
  <div class"w3-row">
   <div class"w3-quarter">
    <button class="binbutton" type="button" value="Cancel" onclick="{$canAction}">Cancel</button</td>
    <button class="binbutton" type="button" value="Review" onclick="do_review({$batch},{$userId});">Review</button</td>
    <button class="binbutton" type="button" value="Complete" onclick="do_complete({$batch});">Complete</button</td>
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
function cancelPO()
{
 document.form1.scaninput.value="";
 window.location.href="{$_SERVER["PHP_SELF"]}";
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
function chk_seltr(ele)
{
  if (ele.checked === true) ele.checked = false; else ele.checked=true;
  chk_sel();
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
      <div class="w3-container w3-{$topColor} w3-padding-8">
        <div class="w3-clear"></div>
                <label><h4>Scan Bin</label>
        <input type="text" name="scaninput" value="" style="text-transform:uppercase" onchange="do_bin();">

</h4>
<br>
<br>

      </div>
    </div>
  </div>
    <button class="binbutton" type="button" value="Cancel" onclick="do_resetp();">Cancel 1</button></td>
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
          <div class="w3-container FormSubHeaderFont DataTD">Multiple Parts Found, Please Choose!</div>

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
    $poitems["totalPrevRecvd"] = 0;
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
                if ($key == "qty_recvd") {
                    $poitems["totalPrevRecvd"] = $poitems["totalPrevRecvd"] + $data;
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
{"class":"binbutton","type":"button","name":"review","value":"Review","onclick":"do_review({$_SESSION["rf"]["RECEIPT"]},{$_SESSION["wms"]["UserID"]});"}
]
JSON;
//TEMP
//echo "<pre>";
//var_dump(get_defined_vars());
//exit;
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

function frmtBin($part, $vendor, $qty_scanned, $pkguom, $mst, $color, $msg, $recvInfo, $infoColor = "", $openQty = 0)
{
    global $thisprogram;
    global $prodline;
    global $sounds;
    global $recvTo;
    global $msg2;
    global $comp;
    global $opt;
    global $db;
    $msgcolor = $color;
    $bsound = "";
    if (trim($msg) == "" or substr($msg, 0, 5) == "Last:" or substr($msg, 0, 8) == "Ordered:" or $msg = "Part Added to PO") {
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
    if (substr($msg2, 0, 7) == "Tote is") {
        $bsound = <<<HTML
<audio controls autoplay >
  <source src="{$sounds}/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;
    }

    if (trim($recvInfo) <> "") {
        $msgcolor = "amber";
        if ($infoColor <> "") $msgcolor = $infoColor;
        $msg = "<h4 class=\"w3-amber\">{$recvInfo}</h4>";
    }
    //$qty_scanned="12";
    if (($pkguom == "NULL" or $pkguom == "") and $part["packQty"] > 1) $pkguom = "CS";
    $p_l = $part["p_l"];
    $pn = $part["part_number"];
    $shd = $part["shadow_number"];
    $pdesc = $part["part_desc"];
    $partuom = $part["unit_of_measure"];
    //$tqty=$qty_scanned * $part["packQty"];
    $tqty = $qty_scanned;
    $uomDesc = "{$tqty} {$partuom}";
    $b = "<strong>";
    $be = "</strong>";
    $uom1 = "";
    if ($pkguom == $partuom) $uom = $partuom;
    else {
        $uom1 = "{$b} {$pkguom} of {$qty_scanned} = _tqty_ {$partuom}{$be}";
        $uomDesc = "1 {$pkguom} of {$qty_scanned}, Receiving {$tqty} {$partuom}";
        $uom = "{$b}{$uomDesc}{$be}";
        $tqty = $part["packQty"];
    }
    if (isset($mst[1]["primary_bin"])) $bin = $mst[1]["primary_bin"];
    else $bin = "";
    $binPrompt = "Primary Bin:";
    if (trim($bin) == "") {
        if (isset($prodline)) {
            //$bin=$prodline["pl_perfered_zone"] . "-" . sprintf("%02d",$prodline["pl_perfered_aisle"]);
            $bin = $prodline["pl_perfered_zone"] . "-" . $prodline["pl_perfered_aisle"];
            $binPrompt = "Preferred Zone-Aisle:";
        } else $bin = "No Primary Bin Set";

        $SQL = <<<SQL
select whs_location from WHSELOC
where whs_shadow = {$shd}
and whs_company = {$comp}

SQL;
        $z1 = $db->gData($SQL);
        if (isset($z1[1]) and isset($z1[1]["whs_location"])) $bin = $z1[1]["whs_location"];

    } // end bin is empty
    $prefBin = $bin;
    $bigBin = "";
    if ($prefBin <> "") {
        $w = "";
        $prefZone = "";
        $k = explode("-", $prefBin);
        if (isset($k[0])) $w = $k[0];
        if (isset($k[0])) $prefZone = $k[0];
        if (isset($k[1])) $w .= " {$k[1]}";
        if (isset($k[1])) $prefZone .= "|{$k[1]}";
        $bigBin = <<<HTML
<div style="font-weight: 900;font-size: 35px;position: relative; right: 0px;">{$w}</div>
HTML;
    }
    if ($recvTo == "a") $binPrmpt = "Tote"; else $binPrmpt = "Bin";

    if ($bigBin == "") $bigBin = "<br>";

//toDo  Correct qty to be qty 1, add span with total qty extended to eaches
    if (!isset($ts)) $ts = time();
//echo "<pre>";
//print_r(get_defined_vars());
//exit;
    $htm = <<<HTML
 <form name="form1" action="{$thisprogram}" method="get">
  <input type="hidden" name="thisprogram" value="{$thisprogram}">
  <input type="hidden" name="vendor" value="{$vendor}">
  <input type="hidden" name="func" value="scanBin">
  <input type="hidden" name="ts" value="{$ts}">
  <input type="hidden" name="msg2" value="{$msg2}">
  <input type="hidden" name="shadow" value="{$shd}">
  <input type="hidden" name="lookPO" value="2">
  <input type="hidden" name="comp" value="{$part["comp"]}">
  <input type="hidden" name="UPC" value="{$part["upc"]}">
  <input type="hidden" name="PPL" value="{$p_l}">
  <input type="hidden" name="PPN" value="{$pn}">
  <input type="hidden" name="PPD" value="{$pdesc}">
  <input type="hidden" name="partUOM" value="{$partuom}">
  <input type="hidden" name="pkgUOM" value="{$pkguom}">
  <input type="hidden" name="openQty" value="{$openQty}">
  <input type="hidden" name="pkgQty" value="{$qty_scanned}">
  <input type="hidden" name="uomDesc" value="{$uomDesc}">
  <input type="hidden" name="tqty" value="{$tqty}">
  <input type="hidden" name="prefZone" value="{$prefZone}">

  <div class="w3-row-padding w3-medium w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-container w3-{$color}">
      <div class="w3-{$msgcolor}">
         {$msg}
      <div>
<span class="w3-clear">
{$b}{$p_l} {$pn}{$be}
&nbsp;{$pdesc}</span>
<br>
<span class="w3-clear">
        <label>Qty</label>
<input type="number" min="0" max="{$opt[20]}" class="w3-white" value="{$tqty}" name="qtyRecvd" onchange="recalc_qty(this,{$qty_scanned},{$openQty});">
<span id="extQty" name="extQty">{$uom}</span>
{$bigBin}
{$binPrompt} <strong>{$bin}</strong>
</span>
<br>
HTML;
    if ($msg2 <> "") $htm .= "<span class=\"w3-amber\"><b>{$msg2}</b></span>\n";
    $htm .= <<<HTML
        <div class="w3-clear">
                <label>Scan {$binPrmpt}</label>
<input type="text" class="w3-white" onchange="do_bin();" value=" " name="scaninput" placeholder="Enter Bin or Tote...">
<br>
<br>
     
        </div>
      </div>
    </div>
  </div>
    <input class="binbutton" type="button" value="Cancel" onclick="do_resetp();"></td>
 </form>
{$bsound}
<script>
function recalc_qty(el,pkgqty,openQty)
{
 var qtyrec=el.value;
 var tq=(qtyrec * pkgqty);
 var msg;
 var ok;
  badqty=0;
  if (el.value != "") {
    if (parseInt(el.value) < parseInt(el.min)) {
      badqty=1;
    }
    if (parseInt(el.value) > parseInt(el.max)) {
      badqty=2;
    }
  } 
  if (badqty > 0)
  {
    if (badqty === 1) msg="Quantity below " + el.min + " is not allowed";
    if (badqty === 2) msg="Quantity above " + el.max + " is not allowed";
    alert(msg);
    document.form1.qtyRecvd.value={$tqty};
    document.form1.qtyRecvd.focus();
    return false;
  }
  else
  {
   if (tq > openQty)
   {
    msg="There is only Qty " + openQty + " still open on this PO, Are You Sure you wish to receive " + tq + "?";
    if (isNeg(openQty)) msg="Already Received " + Math.abs(tq) + " more than the Qty Ordered, You Sure you wish to receive More?";
    if (confirm(msg))
    {
     document.form1.tqty.value=tq;
     var extq=document.form1.uomDesc.value;
     document.getElementById('extQty').innerHTML=extq.replace("_tqty_",tq);
    }
   else 
    {
     document.form1.qtyRecvd.value={$tqty};
     document.form1.scaninput.focus();
     return false;
    }
  } // badqty check passed
 }
}
function isNeg(num)
{
 return num < 0;
}
</script>
HTML;
    file_put_contents("/tmp/recvPOBin.htm", $htm);
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
select max(line_num) as cnt
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
         recv_to,
         totalOrd,
         qty_stockd
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
function getPO($poNumber)
{
    global $RESTSRV;
    $ret = array();
    if ($poNumber <> "") {
        $req = array("action" => "getPO",
            "host_po_num" => $poNumber
        );
        $rdata = restSrv($RESTSRV, $req);
        $ret = (json_decode($rdata, true));
        if (isset($ret["po_status"]) and abs($ret["po_status"]) < 2) {
            $req = array("action" => "setPOStatus",
                "company" => $ret["company"],
                "wms_po_num" => $ret["wms_po_num"],
                "po_status" => 2
            );
            $tmp = restSrv($RESTSRV, $req);
        }
    } // end poNumber <> ""
    return ($ret);
} // end getPO

function setPOforPart($db, $shadow, $POs, $qty)
{
    $ret = array();
    $ret["po"] = 0;
    $ret["line"] = 0;
    $poitems = chkPartOnPO($db, $shadow, $POs, $qty);
//echo "<pre>";
//print_r($poitems);
//exit;
    if ($poitems["numRows"] < 1) {
        //Item not found on any of the PO's, what to do?
        //
        echo "Help, part was not found on any PO";
        exit;
    }
    if ($poitems["numRows"] > 0) { // set to po index
        foreach ($POs as $key => $p) {
            if ($p == $poitems["1"]["poi_po_num"]) $ret = $key;
        }
    } // set to po index
    return $ret;
} // end setPOforPart

function checkOpenLines($SRV, $comp, $batch, $po = "")
{
    if ($batch > 0) {
        $req = array("action" => "openOnPO",
            "batch" => $batch,
            "comp" => $comp
        );
    } else if (trim($po) <> "") {
        $req = array("action" => "openOnPO",
            "batch" => $batch,
            "comp" => $comp
        );
    }
    if (isset($req)) {
        $ret = restSrv($SRV, $req);
        $rdata = (json_decode($ret, true));
        return $ret;
    } else return false;
} // end checkOpenLines

function checkIfMore($SRV, $comp, $batch, $po)
{
    $ret = array();
    if (is_array($po)) { // check each po to see if open
        foreach ($po as $key => $p) {
            $req = array("action" => "fetchDetail",
                "company" => $comp,
                "batch" => $batch,
                "wms_po_num" => $p,
                "plSearch" => "",
                "pnSearch" => ""
            );
        } // end foreach po
    } // check each po to see if open
    else { // single PO
        $req = array("action" => "fetchDetail",
            "company" => $comp,
            "batch" => $batch,
            "wms_po_num" => $po,
            "plSearch" => "",
            "pnSearch" => ""
        );

    } // single PO
    $rc = restSrv($SRV, $req);
    $rdata = (json_decode($rc, true));
    return $rdata;
} // end check if more

function frmtPOdata($comp)
{
    global $RESTSRV;
    $htm = "";

    $POs = $_SESSION["rf"]["POs"];
    if (isset($_SESSION["rf"]["RECEIPT"])) $batch = $_SESSION["rf"]["RECEIPT"];
    else $batch = 0;
    $i = 0;
    foreach ($POs as $key => $po) {
        $in = checkIfMore($RESTSRV, $comp, $batch, $po);

        if (count($in) > 0) {
            // in is an array from fetchDetail from the PO_srv
            $open = "";
            $done = "";
            $htm = "";
            $htm1 = "";
            $htm2 = "";
            $htm1 = <<<HTML
      <br>
      <div class="collapsible">
        <span class="FormSubHeaderFont">Open - Items: _oCount_, Qty: _oHash_ </span>
      </div>
      <div class="content">
       <table class="table table-bordered table-striped">
        <tr>
         <td width="5%" class="FieldCaptionTD">Line#</td>
         <td width="5%" class="FieldCaptionTD">P/L</td>
         <td width="20%" class="FieldCaptionTD">Part#</td>
         <td width="5%" align="right" class="FieldCaptionTD">Orderd</td>
         <td width="5%" align="right" class="FieldCaptionTD">Recvd</td>
         <td width="5%" align="right" class="FieldCaptionTD">Open</td>
         <td width="5%" align="right" class="FieldCaptionTD">Stockd</td>
         <td class="FieldCaptionTD">Bin/Tote</td>
        </tr>
        _PNRDetail_
       </table>
      </div>
HTML;

            $htm2 = <<<HTML
      <br>
      <div class="collapsible">
        <span class="FormSubHeaderFont">Received in Full - Items: _dCount_, Qty: _dHash_ </span>
      </div>
      <div class="content">
       <table class="table table-bordered table-striped">
        <tr>
         <td class="FieldCaptionTD">Line#</td>
         <td class="FieldCaptionTD">P/L</td>
         <td class="FieldCaptionTD">Part#</td>
         <td align="right" class="FieldCaptionTD">Orderd</td>
         <td align="right" class="FieldCaptionTD">Recvd</td>
         <td align="right" class="FieldCaptionTD">Stockd</td>
         <td class="FieldCaptionTD">Bin/Tote</td>
        </tr>
        _PRDetail_
       </table>
      </div>

HTML;
            $hnr = false;
            $hr = false;
            $ocnt = 0;
            $dcnt = 0;
            $ohsh = 0;
            $dhsh = 0;
            foreach ($in as $key => $d) {
                if (!isset($d["qtyStocked"])) $d["qtyStocked"] = 0;
                $ln = $d["poi_line_num"];
                $pl = $d["p_l"];
                $pn = $d["part_number"];
                $qo = $d["qty_ord"];
                $qr = $d["qty_recvd"];
                $qs = $d["qtyStocked"];
                $pack = $d["pack_id"];
                $left = $qo - $qr;
                $a = <<<HTML
            <td align="center">{$left}</td>

HTML;
                if ($left < 1) {
                    $left = 0;
                    $a = "";
                }
                $w = <<<HTML
           <tr>
            <td>{$ln}</td>
            <td>{$pl}</td>
            <td>{$pn}</td>
            <td align="center">{$qo}</td>
            <td align="center">{$qr}</td>
            {$a}
            <td align="center">{$qs}</td>
            <td>{$pack}</td>
           </tr>

HTML;
                if ($left > 0) {
                    $open .= $w;
                    $hnr = true;
                    $ocnt++;
                    $ohsh = $ohsh + $left;
                } else {
                    $done .= $w;
                    $hr = true;
                    $dcnt++;
                    $dhsh = $dhsh + $qr;
                }
            } // end foreach in
            if ($hnr) $htm .= str_replace("_PNRDetail_", $open, $htm1);
            if ($hr) $htm .= str_replace("_PRDetail_", $done, $htm2);
            $htm = str_replace("_oCount_", $ocnt, $htm);
            $htm = str_replace("_dCount_", $dcnt, $htm);
            $htm = str_replace("_oHash_", $ohsh, $htm);
            $htm = str_replace("_dHash_", $dhsh, $htm);
        } // end count in > 0
        $i++;
        if ($i > 0) break; // just formatting first PO right now
    } // end foreach POs
    //$htm=$open . $done;
    $htm .= "\n" . collapseJs();
    return ($htm);

} // end format PO data


function frmtNotOnPo($po, $part, $msg = "")
{
    global $recvTo;
    global $recvType;
    global $lookPO;
    global $thisprogram;

    $color = "yellow";

//Only doing first po right now, in future make user select po if multple
    $poNum = $po[0];
    $pl = $part["Part"]["p_l"];
    $pn = $part["Part"]["part_number"];
    $partNum = "{$pl} {$pn}";
    $partDesc = "{$part["Part"]["part_desc"]}";
    $partUOM = "{$part["Part"]["unit_of_measure"]}";
    $upc = $part["Result"]["alt_part_number"];
    $shadow = $part["Part"]["shadow_number"];
    $mdse_price = $part["Part"]["cost"];
    $core_price = $part["Part"]["core"];
    $weight = $part["Part"]["part_weight"];
    $case_uom = $part["Result"]["alt_uom"];
    $case_qty = $part["Result"]["alt_type_code"];
    if ($case_qty < 0) $case_qty = -$case_qty; else $case_qty = 1;
    $partQty = $case_qty;
    $at = $part["Result"]["alt_type_code"];
    if ($at < 0) $partQty = -$at;
    if ($msg == "") $msg = "Part Not Found on PO";

    $hiddens = <<<HTML
  <input type="hidden" name="thisprogram" value="{$thisprogram}">
  <input type="hidden" name="func" id="func" value="notOnPO">
  <input type="hidden" name="scaninput" value="{$upc}">
  <input type="hidden" name="recvTo" value="{$recvTo}">
  <input type="hidden" name="recvType" value="{$recvType}">
  <input type="hidden" name="lookPO" value="{$lookPO}">
  <input type="hidden" name="poi_po_num" value="{$poNum}">
  <input type="hidden" name="shadow" value="{$part["Part"]["shadow_number"]}">
  <input type="hidden" name="p_l" value="{$pl}">
  <input type="hidden" name="part_number" value="{$pn}">
  <input type="hidden" name="uom" value="{$partUOM}">
  <input type="hidden" name="qty" value="{$partQty}">
  <input type="hidden" name="pdesc" value="{$partDesc}">
  <input type="hidden" name="upc" value="{$upc}">
  <input type="hidden" name="mdse_price" value="{$mdse_price}">
  <input type="hidden" name="core_price" value="{$core_price}">
  <input type="hidden" name="weight" value="{$weight}">
  <input type="hidden" name="case_uom" value="{$case_uom}">
  <input type="hidden" name="case_qty" value="{$case_qty}">
HTML;

    $htm = <<<HTML
 <form name="form1" action="recv_po.php" method="get">
{$hiddens}
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
     <div class="container w3-{$color} w3-padding-8">
     <div class="w3-white">
      <div class="w3-padding-8 FormHeaderFont">
</div>
        <span class="w3-{$color}"><br></span>
        <div class="clear"></div>
      <div class="row">
       <div class="col-75">
        <table style="position:relative;left: 6px;" class="table table-bordered table-striped">
         <tr>
          <td colspan="5" class="w3-white"><span class="FormHeaderFont">{$msg}</span></td>
         </tr>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Part Number</td>
          <td class="DataTD" align="left" width="10%">{$partNum}</td>
          </td>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Description</td>
          <td class="DataTD" align="left" width="10%">{$partDesc}</td>
          </td>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">UOM</td>
          <td class="DataTD" align="left" width="10%">{$partUOM}</td>
          </td>
         <tr>
          <td class="FieldCaptionTD" align="left" width="10%">Qty</td>
          <td class="DataTD" align="left" width="10%">{$partQty}</td>
          </td>
         </tr>
         <tr>
          <td colspan="5" class="w3-white">&nbsp;</td>
         </tr>

         <tr>
          <td colspan="5">


          </td>
         </tr>

        </table>
       </div>
      </div>
       <br>
        <div class="col-75" style="word-wrap: normal;font-weight: bold; font-size: large; text-align: cput;">{$msg}</div>
     </div>
        <div class="col-75" style="word-wrap: normal;font-weight: bold; font-size: large; text-align: cput;">
           <button class="binbutton-small" id="b1" name="B1" value="Ok" onclick="do_submit();">Add It</button>

           <button class="binbutton-small" id="b2" name="B2" value="Cancel" onclick="do_submit();">Cancel</button>
</div>
     </div>
    </div>
  </div>
 </form>
HTML;

    return $htm;
} // end frmtNotOnPo


?>
