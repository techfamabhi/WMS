<?php
$tote=103;
$title="Verifying Tote {$tote}";

$hdr=<<<HTML
<html>
 <head>
 <title>{$title}</title>
 <meta name="robots" content="noindex">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes" />
 <script>
  window.name="scanVerify";
 </script>

  <link rel="stylesheet" href="../assets/css/wdi3.css">
 <link rel="stylesheet" href="../assets/css/font-awesome.min.css">
 <link rel="stylesheet" href="../Themes/Multipads/Style.css">
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="../assets/css/wms.css">
 <style>
 .menuI {
  position: absolute;
  right:0;
 }
 </style>
 
 <script>
function do_pack(arg)
{
 document.form1.func.value=arg;
 document.form1.submit();
}
</script>
</head>

 <body class="w3-light-grey" >

HTML;

$ppl="";
$ppart="";
$pqty="";
$tqty="";
$pphtm="";
$ppl="WIX";
$ppart="24006";
$pqty="1";
$tqty="2";

if ($ppart <> "")
{
 $pphtm=<<<HTML
<tr>
      <td colspan="4" class="FormSubHeaderFont">Previous Part</th>
     </tr>
     <tr>
      <td class="FieldCaptionTD" align="left" width="5%">P/L</td>
      <td class="FieldCaptionTD" align="left" width="30%">Part Number</td>
      <td class="FieldCaptionTD" align="center" width="5%">Qty</td>
      <td class="FieldCaptionTD" align="center" width="5%">Tot Qty</td>
     </tr>
     <tr>
      <td align="left" width="5%">{$ppl}</td>
      <td align="left" width="30%">{$ppart}</td>
      <td align="center" width="5%">{$pqty}</td>
      <td align="center" width="5%">{$tqty}</td>
     </tr>

HTML;
}

$htm=<<<HTML
<div class="container w3-blue w3-half" style="margin-left:14px;">
<h4 id="sw"><strong>{$title}</strong></h4>
 <form name="form1" action="pack.php" method="get">
  <input type="hidden" name="func" id="func" value="scanVerify">
  <input type="hidden" name="nh" value="0">
  <input type="hidden" name="tote" value="{$tote}">
  <input type="hidden" name="ppl" value="{$ppl}">
  <input type="hidden" name="ppart" value="{$ppart}">
  <input type="hidden" name="pqty" value="{$pqty}">
  <input type="hidden" name="tqty" value="{$tqty}">
  <div class="row">
   <div class="col-75">
    <table class="table table-bordered table-striped">
{$pphtm}     <tr>
      <td colspan="4" class="w3-white">&nbsp;</td>
     </tr>
     <tr>
      <td class="FieldCaptionTD" align="left" width="10%">Scan Part</td>
      <td class="w3-white" colspan="3" align="left" width="10%">
      <input class="w3-white" name="scaninput" type="text" onchange="do_submit();" value="" placeholder="Scan Part UPC"  id="thescan" title="Scan Part UPC">
      </td>
     </tr>
     <tr>
      <td colspan="4" class="w3-white">&nbsp;</td>
     </tr>
     <tr>
      <td colspan="4">
         <button class="binbutton-small" id="B1" name="B1" onclick="do_submit();">Submit</button>
         <button class="binbutton-small" id="B1" value="done" name="B1" onclick="do_done();">Done</button>
      </td>
     </tr>
      </table>
   </div>
  </div>
    <br>
    <br>
 </form>
</div>
     </form>
  

<script>
    document.form1.scaninput.focus();
</script>
<script>
function do_submit()
{
 document.form1.submit();
}
</script>
 </body>
</html>
HTML;
echo $hdr;
echo $htm;
