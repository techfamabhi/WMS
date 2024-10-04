<?php

$f="F1";
$F1="99525";
if (isset($$f)) $val=$$f; else $val="";
echo "{$f} = {$val}";
exit;
?>
<!DOCTYPE html>
<html>
 <head>
 <title>Packing</title>
 <meta name="robots" content="noindex">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes" />
 <script>
  window.name="pack";
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
 
 
</head>

 <body class="w3-light-grey" >
<!-- !PAGE CONTENT! -->
<div class="topnav w3-light-blue" id="rfTopnav">
<a href="#"><strong>Packing</strong></a>
<a href="#">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;</a>
 <a href="javascript:document.form1.submit();">Pack It</a>
 <a href="/wms/webmenu.php">Exit</a>
 <a href="javascript:void(0);" class="icon" onclick="menuClick()">
<img border="0" src="/wms/images/menu_grey.png">
 </a>
</div>
     <span id="sw"></span>
     <span id="sw"></span>

<script>
function menuClick() {
  var x = document.getElementById("rfTopnav");
  if (x.className === "topnav") {
    x.className += " responsive";
  } else {
    x.className = "topnav";
  }
}
</script>

  
  
  
     <div class="panel-body">
    <div class="table-responsive">
     <form name="form1" action="pack.php" method="get">
  <input type="hidden" name="func" id="func" value="orderOrTote">
  <input type="hidden" name="nh" value="0">
  <input type="hidden" name="scanned[]" value="116">
      <input type="hidden" name="orderFound" value="10055">
      <input type="hidden" name="hostordernum" value="900008">
      <input type="hidden" name="detailTote" value="">
<table class="w3-half table table-bordered table-striped">
<tr>
<td>
<style>
.collapsible {
  background-color: #777;
  color: white;
  cursor: pointer;
  padding: 18px;
  width: 100%;
  border: none;
  text-align: left;
  outline: none;
  font-size: 15px;
}

.active, .collapsible:hover {
  background-color: #555;
}

.collapsible:after {
  content: '+';
  color: white;
  font-weight: bold;
  float: right;
  margin-left: 5px;
}

.active:after {
  content: "";
}

.content {
  padding: 0 18px;
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.2s ease-out;
  background-color: #f1f1f1;
}
</style>
       <div class="collapsible">
        <span class="wmsBold">Order #:</span>
        <span>900008&nbsp;&nbsp;&nbsp;&nbsp;</span>
        <span class="wmsBold">Customer:</span>
        <span>200122</span>
       </div>
       <div class="content">
        <div class="row">
         <span class="wmsBold">AUTOMOTIVE EXPERTS</span>
        </div>
        <div class="row">
         <span> 740 SCOTT NIXON MEMORIAL DR.</span>
        </div>
        <div class="row">
         <span>AUGUSTA, GA 30907 </span>
        </div>
        <div class="row">
         <span class="wmsBold">PO#:</span>
         <span width="10%">Tim&nbsp;&nbsp;&nbsp;&nbsp;</span>
         <span class="wmsBold">Date Req:</span>
         <span colspan="1">03/25/2022</span>
        </div>
       </div>
      <table class="table table-bordered table-striped">
       <tr>
        <td class="FieldCaptionTD" align="center" width="10%">Zones</td>
        <td class="FieldCaptionTD" align="center" width="10%">Ship Via</td>
        <td class="FieldCaptionTD" align="center" width="10%">Priority</td>
        <td class="FieldCaptionTD" align="center" width="10%">Ship Compl</td>
       </tr>
       <tr>
        <td align="center">B</td>
        <td align="center">UPS</td>
        <td align="center">1</td>
        <td align="center" width="10%">Y</td>
       </tr>
      </table>

</td>
</tr>
<tr>
<td>
         <table class="table table-bordered table-striped">
          <tr>
           <td colspan="3" class="FormSubHeaderFont">Totes</td>
          </tr>
          <tr>
           <th class="FieldCaptionTD">Tote Id</th>
           <th class="FieldCaptionTD">&nbsp;</th>
           <th class="FieldCaptionTD">Zone</th>
           <th class="FieldCaptionTD" align="center">Staging Area</th>
          <tr>
          <tr>
           <td>116</td>
           <td><button name="verify[116]" onclick="document.form1.submit();" class="btn btn-info btn-xs">Verify</button></td>
           <td align="center">A</td>
           <td align="center">PACK</td>
          </tr>
         </table>
        </td>
       </tr>
</td>
</tr>
<tr>
<td>
         <table class="table table-bordered table-striped">
          <tr>
           <td colspan="5" class="FormSubHeaderFont">Totes and Contents</td>
          </tr>
          <tr>
           <th class="FieldCaptionTD">Zone</th>
           <th class="FieldCaptionTD">Tote Id</th>
           <th class="FieldCaptionTD">Part Number</th>
           <th class="FieldCaptionTD">Qty</th>
           <th class="FieldCaptionTD">UOM</th>
          </tr>
          <tr>
           <td align="center">A</td>
           <td>116</td>
           <td align="left">WIX 51358</td>
           <td align="center">1</td>
           <td align="center">EA</td>
          </tr>
          <tr>
           <td align="center">A</td>
           <td>116</td>
           <td align="left">WIX 51064</td>
           <td align="center">1</td>
           <td align="center">EA</td>
          </tr>
         </table>
        </td>
       </tr>
</tr><tr>
<td colspan="4">
         <button class="binbutton-small" id="B1" name="B1" onclick="document.form1.submit();">Pack It</button>
         <button class="binbutton-small" id="B2" name="B2" value="cancel">Cancel</button>
       </tr>
      </table>
     </form>
    </div>
   </div>

<script>
var coll = document.getElementsByClassName("collapsible");
var i;

for (i = 0; i < coll.length; i++) {
  coll[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var content = this.nextElementSibling;
    if (content.style.maxHeight){
      content.style.maxHeight = null;
    } else {
      content.style.maxHeight = content.scrollHeight + "px";
    }
  });
}

 </script>
  
 </body>
</html>
