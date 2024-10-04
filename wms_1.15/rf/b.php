<!DOCTYPE html>
<html>
 <title><strong>A-02-01-C</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Bin Check another Bin">Clear</button></title>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.10, width=device-width, user-scalable=yes" />

 <link rel="stylesheet" href="css/wdi3.css">
 <link rel="stylesheet" href="../assets/css/font-awesome.min.css">
 <link rel="stylesheet" href="../Themes/Multipads/Style.css">
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
 <style>
 .menuI {
  position: absolute;
  right:0;
 }
 </style>
 
 <script src="/jq/shortcut.js" type="text/javascript"></script>
<script>
shortcut.add("return",function() {
  document.getElementById('srClr').click();
});
</script>
<style>
.myRed
{
color:#fff!important;
background-color:#ff7777!important
}
</style>
</head>

 <body class="w3-light-grey" >
<!-- !PAGE CONTENT! -->
  <div class="w3-main" style="margin-left:8px;margin-top:2px;">
   <!-- Header -->
   <div class="w3-half w3-row-padding w3-medium" style="padding-right:12px">
    <header class="w3-container w3-blue" style="padding-top:2px">
     <h3><b><strong>A-02-01-C</strong>
&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="clearSearch()" title="Bin Check another Bin">Clear</button></b>
<div style='float:right;'>
 <div style='position: fixed; top:20px;'>
    <a class="menuI" title="Menu" href="/wms/webmenu.php"><img border="0" src="/wms/images/menu_grey.png"></a>

 </div>
</div>
</h3>
     <span id="sw"></span>
    </header>
   </div>
  </div>
  
  
  
 </body>
</html>
   <div class="w3-clear"></div>
 <div class="w3-half">
 <form name="form1" action="bincheck.php" method="get">
      <input type="hidden" name="scaninput" value="">
        <div class="panel-body">
         <div class="table-responsive">
          <table class="table table-bordered table-striped">
           <tr>
            <td colspan="6" class="FormSubHeaderFont">Bin: A-02-01-C</td>
           </tr>
           <tr>
            <td>
             <table width="100%">
              <tr>
               <th style="text-align:center" class="FieldCaptionTD">Zone</th>
               <th style="text-align:center" class="FieldCaptionTD">Aisle</th>
               <th style="text-align:center" class="FieldCaptionTD">Section</th>
               <th style="text-align:center" class="FieldCaptionTD">Level</th>
               <th style="text-align:center" ></th>
               <th>&nbsp;</th>
               <th class="FieldCaptionTD" style="text-align:right;padding-right:10px">Length</th>
               <th class="FieldCaptionTD" style="text-align:right;padding-right:10px">Width</th>
               <th class="FieldCaptionTD" style="text-align:right;padding-right:10px">Height</th>
               <th class="FieldCaptionTD" style="text-align:right;padding-right:10px">Volume</th>
               <th class="FieldCaptionTD" style="text-align:right;padding-right:10px">SqFt</th>
              </tr>
              <tr>
               <td style="text-align:center">A</td>
               <td style="text-align:center">2</td>
               <td style="text-align:center">1</td>
               <td style="text-align:center">C</td>
               <td style="text-align:center">&nbsp;</td>
               <td>&nbsp;</td>
               <td style="text-align:right;padding-right:10px">48.00</td>
               <td style="text-align:right;padding-right:10px">12.00</td>
               <td style="text-align:right;padding-right:10px">14.25</td>
               <td style="text-align:right;padding-right:10px">8208.00</td>
               <td style="text-align:right;padding-right:10px">4.75</td>
              </tr>
             </table>
            </td>
           </tr>
          </table>
          <table class="table table-bordered table-striped">
           <tr>
            <td colspan="6" class="FormSubHeaderFont">Parts in this Bin:</td>
           </tr>
           <tr>
            <td width="5%" class="FieldCaptionTD">P/L</td>
            <td width="20%" class="FieldCaptionTD">Part Number</td>
            <td width="20%" class="FieldCaptionTD">Description</td>
            <td width="5%" style="text-align:right;padding-right:10px" class="FieldCaptionTD">Qty</td>
            <td width="5%" class="FieldCaptionTD">UOM</td>
            <td width="5%" class="FieldCaptionTD">Type</td>
           </tr>
           <tr>
            <td>WIX</td>
            <td>24006</td>
            <td>Fuel Filter</td>
            <td style="text-align:right;padding-right:10px">4</td>
            <td>EA</td>
            <td>O</td>
           </tr>           <tr>
            <td>WIX</td>
            <td>24013</td>
            <td>Cabin Air Filter</td>
            <td style="text-align:right;padding-right:10px">5</td>
            <td>EA</td>
            <td>P</td>
           </tr>           <tr>
            <td></td>
            <td></td>
            <td></td>
            <td style="text-align:right;padding-right:10px"></td>
            <td></td>
            <td></td>
           </tr>
          </table>
         </div>
        </div>
      <br>
 </form>
</div>
<script>
function clearSearch()
{
 document.form1.scaninput.value="";
 document.form1.submit();
}
</script>
  
 </body>
</html>
