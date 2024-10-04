<?php
//StockView  version 1.0
// 01/11/2018 dse initial
// 01/17/2018 dse Release 
// 03/16/18 Add Function keys and comp dropdown
// 01/07 21 Change Receiver link to new receiver display
// 01/12 21 Add search for credit memos only
// 02/25/21 dse add nh argument to supress header
// 02/22/23 dse add bin to search

/* TODO
At Cust/Vend/Oper allow radio button bring up proper seach boxes
1 for customer
1 for Vendor
1 for operator

Find out why Pics are not being added to parthist

figure way to know there are no more rows (from stored proc)

Add Company dropdown

*/
foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
//error_reporting(0);
//echo "<pre>";
//print_r($_REQUEST);
//echo "</pre>";


if (get_cfg_var('wmsdir'))
    $wmsDir = get_cfg_var('wmsdir');
else {
    echo "<h1>WMS System is not Configured on this System</h1>";
    exit;
}
$top = str_replace("/var/www", "", $wmsDir);

session_start();
require($_SESSION["wms"]["wmsConfig"]);

$thisprogram = "stockvw.php";
require_once("{$wmsInclude}/chklogin.php");
require_once("{$wmsInclude}/cl_Bluejay.php");
require_once("{$wmsInclude}/restSrv.php");
require_once("{$wmsInclude}/date_functions.php");
$title = "Listing of Parts";
$panelTitle = "Parts";
$Bluejay = $top;
$RESTSRV = "http://{$wmsIp}{$wmsServer}/STOCKVW.php";
$SRVPHP = "{$wmsServer}/parthist.php";
$DRPSRV = "{$wmsServer}/dropdowns.php";

//$oper=$_SESSION["operator"];
//$comp=$_SESSION["company_num"];
//$fpriv=$_SESSION["spriv_from"];
//$tpriv=$_SESSION["spriv_thru"];
//$menu=$_SESSION["menu_number"];
//$login=$_SESSION["UserLogin"];
if (isset($_SERVER["HTTP_REFERER"]))
    $REFER = $_SERVER["HTTP_REFERER"];
else
    $REFER = "";


require_once("{$wmsInclude}/db_main.php");
require_once("{$wmsInclude}/get_table.php");
require_once("{$wmsInclude}/quoteit.php");
require_once("{$wmsInclude}/company_dropdown.php");
require_once("{$wmsInclude}/cl_PARTS2.php");

$db = new WMS_DB;
$db1 = new WMS_DB;
$PM = new PARTS;

require_once("{$wmsInclude}/cl_Bluejay.php");
$pg = new Bluejay;
//echo "<pre>";
//print_r($_REQUEST);

if (isset($_REQUEST["Error"]))
    $Error = $_REQUEST["Error"];
else
    $Error = "";
if (isset($_REQUEST["startrec"]))
    $startrec = $_REQUEST["startrec"];
else
    $startrec = 1;
$numrecs_deflt = false;
if (isset($_REQUEST["numrecs"]))
    $numrecs = $_REQUEST["numrecs"];
else {
    $numrecs = 25;
    $numrecs_deflt = true;
}
if (isset($_REQUEST["comp"]))
    $comp = $_REQUEST["comp"];
else
    $comp = 0;
if (isset($_REQUEST["esrc"]))
    $esrc = $_REQUEST["esrc"];
else
    $esrc = "";
if (isset($_REQUEST["shadow"]))
    $shadow = $_REQUEST["shadow"];
else
    $shadow = "NULL";
if (isset($_REQUEST["partnum"]))
    $partnum = $_REQUEST["partnum"];
else
    $partnum = "";
if (isset($_REQUEST["epl"]))
    $epl = $_REQUEST["epl"];
else
    $epl = "";
if (isset($_REQUEST["epo"]))
    $epo = $_REQUEST["epo"];
else
    $epo = "";
if (isset($_REQUEST["eref"]))
    $eref = $_REQUEST["eref"];
else
    $eref = "";
if (isset($_REQUEST["ept"]))
    $ept = $_REQUEST["ept"];
else
    $ept = "";
if (isset($_REQUEST["ebin"]))
    $ebin = $_REQUEST["ebin"];
else
    $ebin = "";

$comp_select = company_dropdown($db, $comp, "All");
if (isset($esrc) and $esrc <> "")
    $first_field = "eref";
else
    $first_field = "ebin";

$pg->title = "StockView";
$title = $pg->title;
$pg->js = <<<HTML
<script src="/jq/jquery-1.12.4.js" type="text/javascript"></script>
<script src="/jq/shortcut.js" type="text/javascript"></script>

<script language="javascript" type="text/javascript">

function setnext(sr) {
       document.form2.startrec.value=sr;
       document.form2.submit();
      }
function setesrc(val)
{
       document.form1.esrc.value=val;
$('#htmlResult').empty();
       document.form1.partnum.focus();
}
function setshadow(val,pl,part)
{
//alert(val);
       document.form1.shadow.value=val;
$('#htmlResult').empty();
       if (val != "NULL")
        {
         document.form1.epl.value=pl;
         document.form1.partnum.value=part;
         document.form1.epl.disabled=true;
         document.form1.eref.focus();
         if (document.form1.esrc.value != "") document.form1.submit();
        }
       //document.form1.search.focus();
}
function reset_form() {
       document.form1.comp.value=0;
       document.form1.ebin.value="";
       document.form1.esrc.value="";
       document.form1.epl.value="";
       document.form1.startrec.value=1;
       document.form1.partnum.value="";
       document.form1.eref.value="";
       document.form1.epo.value="";
       document.form1.ept.value="";
       document.form1.shadow.value="NULL";
       document.form1.submit();
      }

function do_search()
 {
  var ok=false;
  if (document.form1.esrc.value != "") ok=true;
  if (document.form1.ebin.value != "") ok=true;
  if (document.form1.shadow.value != "null") ok=true;
  if (ok) document.form1.submit();
  else return(false);
 }


shortcut.add("Shift+F3",function() {
        reset_form();
});
shortcut.add("F7",function() {
        reset_form();
});
shortcut.add("F3",function() {
        do_search();
});
shortcut.add("Page_Up",function() { pageUp(); });
shortcut.add("Up",function() { pageUp(); });
shortcut.add("Page_Down",function() { pageDown(); });
shortcut.add("Down",function() { pageDown(); });
function pageDown()
{
       var sr=document.form2.startrec.value;
       var nr=document.form2.numrecs.value;
       var ns=Number(sr) + Number(nr);
        setnext(ns);
}
function pageUp()
{
       var sr=document.form2.startrec.value;
       var nr=document.form2.numrecs.value;
       var ns=Number(sr) - Number(nr);
        setnext(ns);
}

function check_param() {
 var b_sort = document.getElementById('esrc').value;
if (b_sort) { getHTML(); }
}
function getHTML(fld) {
      var f1=document.activeElement.name;
//alert(f1);
if (fld === "esrc")
{
 var b_sort = document.form1.esrc.value;
 b_sort=b_sort.replace(" ","_");
 var queryString = "../servers/cust_srv.php?s_b_sort=" + b_sort;
if (!b_sort) { return(false); }
} // end fld = esrc
if (fld === "partnum")
{
 var pnum = document.form1.partnum.value;
 pnum=pnum.replace(" ","_");
 var queryString = "check_part.php?pnum=" + pnum;
if (!pnum) { return(false); }
} // end fld = partnum
$('#htmlResult').load(queryString);
}

$(document).on('keypress', 'input', function(e) {

  if(e.keyCode == 13 && e.target.type !== 'submit') {
    e.preventDefault();
    var inputs = $(this).closest('form').find(':input:visible');
            inputs.eq( inputs.index(this)+ 1 ).focus();
    //return $(e.target).blur().focus();
  }

});

function printit() {
//{document.getElementById("b5").style.display="none";}
window.print();
//{document.getElementById("b5").style.display="block";}
}


</script>
<style>
.footer {
    position: fixed;
    left: 0;
    bottom: 0;
    width: 100%;
    color: white;
    text-align: center;
}
</style>


HTML;
$nh_htm = "";
if (isset($nh)) {
    $pg->noHeader = true;
    if ($numrecs_deflt) {
        $numrecs = 20;
        if ($nh == 2)
            $numrecs = 15;
    }
    $nh_htm = <<<HTML
    <input type="hidden" name="nh"    value="{$nh}">

HTML;
}
$pg->Display();


$fieldmap = array(
    "paud_type" => "Type",
    "userName" => "User",
    "paud_date" => "Date",
    "paud_source" => "Cust/Op",
    "paud_ref" => "Ref#",
    "paud_floc" => "From Loc",
    "paud_tloc" => "To Loc",
    "p_l" => "P/L",
    "part_number" => "Part Number",
    "part_desc" => "Desc",
    "paud_qty" => "Qty",
    "paud_prev_qty" => "PrvQty",
    "paud_ref" => "Ref#",
    "paud_ext_ref" => "PO/ExtRef",
    "paud_qty_core" => "CoreQty",
    "paud_qty_def" => "DefQty",
    "paud_id" => "",
    "shadow_number" => ""
);

$htm_div = <<<HTML
        <div id="htmlResult">
        </div>

HTML;
$fcomp = $comp;
$tcomp = $comp;
if ($comp == 0) {
    $fcomp = 1;
    $tcomp = 9999;
}
//$esrc=strtoupper($ersc);
$esrc = strtoupper($esrc);
$ebin = strtoupper($ebin);
$epl = strtoupper($epl);
$epo = strtoupper($epo);
$eref = strtoupper($eref);
$ept = strtoupper($ept);
if ($esrc == "")
    $src = "%";
else
    $src = $esrc;
if ($ebin == "")
    $bin = "";
else
    $bin = $ebin;

if ($ept == "%")
    $ept = "";
if ($ept == "")
    $ptype = "%";
else {
    $ptype = "[{$ept}]";
}

//$startrec=13;
if ($epl == "")
    $pl = "%";
else
    $pl = $epl;
if ($epo == "")
    $po = "%";
else
    $po = $epo;
if ($eref == "")
    $ref = "%";
else
    $ref = $eref;
$req = array(
    "action" => "fetchAll",
    "company" => $fcomp,
    "startRec" => $startrec,
    "numRows" => $numrecs,
    "shadow_number" => $shadow,
    "pl" => $pl, // add to server
    "paudType" => $ptype,
    "src" => $src, // add to server
    "bin" => $bin,
    "extref" => $po,
    "transId" => $ref
);


$SQL = <<<SQL
exec mp_stockvw4 @paud_fcomp={$fcomp},
		 @paud_tcomp={$tcomp} ,
                 @paud_src="{$src}",
                 @paud_shdw={$shadow},
                 @paud_typ= "{$ptype}",
                 @start_rec={$startrec},
                 @numrecs={$numrecs},
                 @pl = "{$pl}",
                 @po="{$po}",
                 @ref="{$ref}"

SQL;
$svarray = array();
$ok = false;

if ($ebin <> "%" and $ebin <> "")
    $ok = true;
if ($src <> "%" and $src <> "")
    $ok = true;
if ($shadow <> "NULL")
    $ok = true;
if ($ok) {
    //echo "<pre>{$SQL}\n</pre>";
//exit;
    $rc = restSrv($RESTSRV, $req);
    $svData = (json_decode($rc, true));
    if (isset($svData["rowData"]) and $svData["rowData"] > 0)
        $svarray = $svData["rowData"];
    //echo "<pre>";
//print_r($svData);
//exit;

    if (1 == 2) {
        $rc = $db->query($SQL);
        $numrows = $db->num_rows();
        $i = 1;
        while ($i <= $numrows) {
            $db->next_record();
            if ($numrows and $db->Record) {
                foreach ($db->Record as $key => $data) {
                    if (!is_numeric($key)) {
                        $svarray[$i]["$key"] = $data;
                    }
                }
            }
            $i++;
        } // while i < numrows
    } // end 1 == 2

} // end $ok
//TODO else needs to be added to display a message to enter a cust or part#

//echo "<pre>{$SQL}\n";
//print_r($svarray);
//exit;
//print_r($fieldmap);
// Code for type select box
$epta = array();
$epta[0] = "";
$epta[1] = "";
$epta[2] = "";
$epta[3] = "";
$epta[4] = "";
if ($ept == "%")
    $epta[0] = " selected";
if ($ept == "")
    $epta[0] = " selected";
if ($ept == "ICT")
    $epta[1] = " selected";
if ($ept == "PRDBA")
    $epta[2] = " selected";
if ($ept == "LX")
    $epta[3] = " selected";
if ($ept == "C")
    $epta[4] = " selected";

//<td  align="center" class="DataTD">
//<input title="Enter 0 for All" id="comp" name="comp" size="4" value="{$comp}">
//</td>
$upcase = 'style="text-transform: uppercase" ';
if (!isset($oldpartnum))
    $oldpartnum = "";
$htm = <<<HTML
<form name="form1" action="{$_SERVER["PHP_SELF"]}" method="GET">
{$nh_htm}
<table width="90%" class="table table-striped">
 <tr>
   <td align="center" colspan="8" class="MultipadsFormHeaderFont">Part History</td>
 </tr>
 <tr>
   <td align="center" class="FieldCaptionTD">Whse</td>
   <td align="center" class="FieldCaptionTD">Bin</td>
   <td align="center" class="FieldCaptionTD">Cust/Vend/Oper</td>
   <td align="center" class="FieldCaptionTD">Part Number</td>
   <td align="center" class="FieldCaptionTD">P/L</td>
   <td align="center" class="FieldCaptionTD">Ref#</td>
   <td align="center" class="FieldCaptionTD">ExtRef</td>
   <td align="center" class="FieldCaptionTD">Type</td>
   <td align="center" class="FieldCaptionTD">Start Record</td>
   <td align="center" class="FieldCaptionTD">PageSize</td>
   <td align="center" class="FieldCaptionTD">&nbsp;</td>
 </tr>
 <tr>
  <td align="right">{$comp_select}</td>
  <td  align="center">
    <input id="ebin" name="ebin" {$upcase} size="18" value="{$ebin}">
 </td>
  <td  align="center">
    <input id="esrc" title="Enter Customer#, Customer Name, or Vendor Code or Operator#" name="esrc" size="6" value="{$esrc}"
autocomplete="off" {$upcase} value="" onkeyup="getHTML('esrc');"
>
 </td>
  <td  align="center">
    <input id="partnum" name="partnum" size="22" {$upcase} value="{$partnum}" onchange="getHTML('partnum');">
    <input type="hidden" name="shadow"    value="{$shadow}">
    <input type="hidden" name="oldpartnum"   value="{$oldpartnum}">
 </td>
  <td  align="center">
    <input id="epl" name="epl" {$upcase} size="3" value="{$epl}">
 </td>
  <td  align="center">
    <input id="eref" name="eref" {$upcase} size="8" value="{$eref}">
 </td>
  <td  align="center">
    <input id="epo" name="epo" {$upcase} size="15" value="{$epo}">
 </td>
  <td  align="center">
    <select id="ept" name="ept">
      <option value="%"{$epta[0]}>All</option>
      <option value="ICT"{$epta[1]}>Invoices/Credits/Transfers</option>
      <option value="PRDBA"{$epta[2]}>Purchases/Vendor Return/Adjmnts</option>
      <option value="LX"{$epta[3]}>Lost Sales/Deleted Parts</option>
      <option value="C"{$epta[4]}>Credit Memos</option>
    </select>
 </td>
  <td  align="center">
    <input id="startrec" name="startrec" size="6" title="Record number to start display at" value="{$startrec}">
 </td>
  <td  align="center">
    <input id="numrecs" name="numrecs" size="4" title="Number of records per page" value="{$numrecs}">
 </td>
  <td  align="center">
    <button type="submit" name="search">Search</button>
    <button type="button" name="B2" onclick="reset_form();">Clear</button>
 </td>
 </tr>
</table>
</form>
<script>
document.form1.{$first_field}.focus();
</script>
HTML;
$htm_sv = <<<HTML
<form name="form2" action="{$_SERVER["PHP_SELF"]}" method="GET">
{$nh_htm}
<table width="90%" class="table table-bordered table-striped overflow-auto">
 <tr>
<input type="hidden" name="startrec"  value="{$startrec}">
<input type="hidden" name="numrecs"   value="{$numrecs}">
<input type="hidden" name="comp"     value="{$comp}">
<input type="hidden" name="esrc"      value="{$esrc}">
<input type="hidden" name="epl"       value="{$epl}">
<input type="hidden" name="partnum"   value="{$partnum}">
<input type="hidden" name="epo"       value="{$epo}">
<input type="hidden" name="eref"       value="{$eref}">
<input type="hidden" name="ept"       value="{$ept}">
<input type="hidden" name="shadow"    value="{$shadow}">

HTML;
foreach ($fieldmap as $fld => $hding) {
    if ($hding <> "") {
        $htm_sv .= <<<HTML
  <td align="center" class="FieldCaptionTD">{$hding}</td>
  
HTML;
    } // end heading <> ""
} // end foreach fieldmap
$htm_sv .= <<<HTML
 </tr>

HTML;
if (count($svarray) > 0)
    foreach ($svarray as $row => $data) {
        $htm_sv .= <<<HTML
 <tr>

HTML;
        setlocale(LC_MONETARY, 'en_US');
        foreach ($fieldmap as $fld => $hding) {
            $ref_href = "";
            $refend = "";
            if ($fld == "paud_ref") {
                $o = $data["paud_id"];
                if ($o > 0) { // o > 0
                    $t = $data["paud_type"];
                    if ($t == "I" or $t == "C" or $t == "T") { // it's an order
                        $ref_href = <<<HTML
          <a href="/pdf/dbinv2pdf.php?o_number={$o}" target="_blank">
HTML;
                        $refend = "</a>";
                    } // it's an order
                    if ($t == "P" or $t == "R" or $t == "D" or $t == "B") { // it's an order
// /Bluejay/DRECPTS_list.php?hrp_number={$o}
                        $ref_href = <<<HTML
          <a href="/Bluejay/recving/hrecvr.php?hrp_number={$o}&shadow={$data["paud_shadow"]}" target="_blank">
HTML;
                        $refend = "</a>";
                    } //  o > 0
                    //if PO, I think this it it;


                } // o > 0, we have a order or po number
            } // end fld = paud_ref

            if ($hding <> "") {
                $alg = "";
                $value = $data[$fld];
                $t = $data["paud_type"];
                switch ($fld) {
                    case "paud_type":
                    case "paud_source":
                    case "paud_ref":
                    case "p_l":
                    case "paud_floc":
                    case "paud_tloc":
                    case "part_number":
                    case "part_desc":
                    case "paud_ext_ref":
                    case "paud_linetype":
                    case "userName":
                        $alg = ' align="left"';
                        break;
                    case "paud_date":
                        $alg = ' align="left"';
                        $value = trim(eur_to_usa($value, true));
                        break;
                    case "paud_price":
                    case "paud_core_price":
                        $alg = ' align="right"';
                        //$value=money_format('%.2n', $value);
                        //$value=sprintf('%01.2f', $value);
                        $value = number_format($value, 2);
                        if (floatval($value) == 0.00)
                            $value = " ";
                        break;
                    case "paud_id":
                        $o = $data["paud_id"];
                        if ($o > 0) {
                            if ($t == "I" or $t == "C" or $t == "T") { // it's an order
                                $ref_href = <<<HTML
          <a href="/pdf/dbinv2pdf.php?o_number={$o}" framname="invoice">
HTML;
                                $refend = "</a>";
                            } // it's an order
                        } // o > 0, we have a order or po number

                    default:
                        $alg = ' align="right"';
                        break;
                } // end switch fld
                $cls = "";
                if ($t == "C")
                    $cls = "Alt2DataTD";
                if ($t == "D")
                    $cls = "AltDataTD";
                if ($t == "A")
                    $cls = "AltDataTD";
                if ($t == "L")
                    $cls = "Alt3DataTD";
                if ($t == "T")
                    $cls = "Alt6DataTD";
                if ($t == "PIC")
                    $cls = "Alt5DataTD";
                if ($t == "RCV")
                    $cls = "Alt4DataTD";
                if ($t == "B")
                    $cls = "Alt4DataTD";
                $tdTitle = "";
                if ($data["paud_inv_code"] == "-") {
                    $cls = "wms-sand"; // non inventory
                    $tdTitle = "title=\"Information only, inventory was not adjusted in this transaction.\" ";
                }
                $htm_sv .= <<<HTML
    <td {$tdTitle}nowrap {$alg} class="{$cls}">{$ref_href}{$value}{$refend}</td>

HTML;
            } // end hding <> ""
        } // end foreach fieldmap#2
    } // end foreach svarray
//Make page nav
$prev = "";
if ($startrec > 1) {
    $prec = intval(($startrec) - $numrecs);
    if ($prec < 1)
        $prec = 1;
    $prev = <<<HTML
 <a href="javascript:setnext({$prec})">< Prev</a>

HTML;
} // add prev page
$first = "";
if ($startrec > 1)
    $first = <<<HTML
 <a href="javascript:setnext(1);"><< First</a>

HTML;
$next = $startrec + $numrecs;
$htm .= <<<HTML
   </tr>
  </table>
HTML;

$htm_nav = <<<HTML
<table>
<tr>
<td>{$first}</td>
<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td>{$prev}</td>
<td>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</td>
<td><a href="javascript:setnext({$next})">Next ></a></td>
 </tr>
</table>
</form>
HTML;
echo $htm;
echo $htm_div;
if ($ok) {
    echo $htm_sv;
    echo $htm_nav;
}

$footer = <<<HTML
<div class="footer">
 <table width="100%">
  <tr>
   <td class="FieldCaptionTD" onclick="document.form1.submit();">F3 Search</td>
   <td class="FieldCaptionTD" onclick="reset_form();">Shift-F3 Clear</td>
   <td class="FieldCaptionTD" onclick="reset_form();">F7 Reset</td>
   <td class="FieldCaptionTD">&nbsp;</td>
   <td class="FieldCaptionTD" onclick="pageDown();">PageDown</td>
   <td class="FieldCaptionTD" onclick="pageUp();">PageUp</td>
  </tr>
</div>
HTML;
echo $footer;
//phpinfo(INFO_VARIABLES);

?>