<?php
$part = array
(
    "status" => 1,
    "numRows" => 1,
    "Result" => array
    (
        "shadow_number" => 1230975,
        "p_l" => "BSH",
        "part_number" => "00109",
        "part_desc" => "72954008101 - BOSCH IGNITION C",
        "unit_of_measure" => "EA",
        "alt_part_number" => "00109",
        "alt_type_code" => 9998,
        "alt_uom" => "EA"
    ),

    "Part" => array
    (
        "p_l" => "BSH",
        "part_number" => "00109",
        "part_desc" => "72954008101 - BOSCH IGNITION C",
        "part_long_desc" => "72954008101 - BOSCH IGNITION COIL",
        "unit_of_measure" => "EA",
        "part_seq_num" => 0,
        "part_subline" => "IGC",
        "part_category" => "",
        "part_group" => "BIN -",
        "part_class" => "",
        "date_added" => "01/02/2018",
        "lmaint_date" => "00/00/0000",
        "serial_num_flag" => 0,
        "part_status" => "",
        "special_instr" => "",
        "hazard_id" => "",
        "kit_flag" => 0,
        "cost" => 21.120,
        "core" => 0.000,
        "core_group" => "",
        "part_returnable" => "",
        "shadow_number" => 1230975,
        "part_weight" => 0.000
    ),

    "ProdLine" => array
    (
        "pl_code" => "BSH",
        "pl_company" => 1,
        "pl_desc" => "BOSCH",
        "pl_vend_code" => "",
        "pl_perfered_zone" => "",
        "pl_perfered_aisle" => "",
        "pl_date_added" => "05/11/2017",
        "pl_num_notes" => 0
    ),

    "WhseQty" => array
    (
        "1" => array
        (
            "ms_shadow" => 1230975,
            "ms_company" => 1,
            "primary_bin" => "",
            "qty_avail" => 0,
            "qty_alloc" => 0,
            "qty_putaway" => 0,
            "qty_overstk" => 0,
            "qty_on_order" => 0,
            "qty_on_vendbo" => 0,
            "qty_on_custbo" => 0,
            "qty_defect" => 0,
            "qty_core" => 0,
            "max_shelf" => 0,
            "minimum" => 0,
            "maximum" => 0,
            "cost" => 21.120,
            "core" => 0.000
        ),

    ),

    "Alternates" => array
    ()

);

$top = <<<HTML
<!DOCTYPE html>
<html>
 <head>
 <title>Putaway</title>
 <meta name="robots" content="noindex">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes" />
 <script>
  window.name="notOnPo";
 </script>

  <link rel="stylesheet" href="/wms/assets/css/wdi3.css">
 <link rel="stylesheet" href="/wms/assets/css/font-awesome.min.css">
 <link rel="stylesheet" href="/wms/Themes/Multipads/Style.css">
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="/wms/assets/css/wms.css">
 <style>
 .menuI {
  position: absolute;
  right:0;
 }
 </style>
 
 <script>
function openalt(url,nlns) {
        hgt=210 + (nlns * 25);
        var popup=window.open(url,"popup", "toolbar=no,left=0,top=125,status=yes,resizable=yes,scrollbars=yes,width=600,height=" + hgt );
 return(false);
     }
function doView(tote)
{
 var url="tcont.php?toteId=" + tote;
 openalt(url,10);
 return false;
}
</script>

</head>

 <body class="w3-light-grey" >
<!-- !PAGE CONTENT! -->
<header class="w3-container w3-light-blue" style="border-radius: 5px;padding-top:4px;padding-bottom:8px;">
 <table width="98%" class="topnav1 z-blue">
  <tr>
   <td nowrap width="25%">
     <span><b><span id="pageTitle">Not On PO</span></b>
   </td>
     <div style='float:right;'>
      <div style='position: fixed; top:1px;'>
           <a class="menuI" title="Menu" href="/wms/webmenu.php"><img border="0" src="/wms/images/menu_grey.png"></a>

      </div>
     </div>

  </tr>
 </table>
</header>

HTML;

$msg = "Part not found on any PO";
$htm = frmtNotOnPo(123456, $part, $msg);
echo $top;
echo $htm;

function frmtNotOnPo($po, $part, $msg = "")
{
    $color = "yellow";

    $pl = $part['Part']['p_l'];
    $pn = $part['Part']['part_number'];
    $partNum = "{$pl} {$pn}";
    $partDesc = "{$part['Part']['part_desc']}";
    $partUOM = "{$part['Part']['unit_of_measure']}";
    $upc = $part['Result']['alt_part_number'];
    $partQty = 1;
    $at = $part['Result']['alt_type_code'];
    if ($at < 0) $partQty = -$at;
    if ($msg == "") $msg = "Part Not Found on PO";

    $hiddens = <<<HTML
  <input type="hidden" name="shadow" value="{$part['Part']['shadow_number']}">
  <input type="hidden" name="p_l" value="{$pl}">
  <input type="hidden" name="part_number" value="{$pn}">
  <input type="hidden" name="uom" value="{$partUOM}">
  <input type="hidden" name="qty" value="{$partQty}">
  <input type="hidden" name="pdesc" value="{$partDesc}">
  <input type="hidden" name="upc" value="{$upc}">
HTML;

    $htm = <<<HTML
 <form name="form1" action="recv_po.php" method="get">
  <input type="hidden" name="func" id="func" value="notOnPO">
  <input type="hidden" name="nh" value="0">
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
          <td colspan="5" class="w3-white"><span class="FormHeaderFont">Oops!</span></td>
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
