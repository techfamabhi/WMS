<!DOCTYPE html>
<html>
 <title>Bin Check</title>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.10, width=device-width, user-scalable=yes" />

  <link rel="stylesheet" href="../assets/css/wdi3.css">
 <link rel="stylesheet" href="../assets/css/font-awesome.min.css">
 <link rel="stylesheet" href="../Themes/Multipads/Style.css">
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
 <style>
 .menuI {
  position: absolute;
  right:0;
 }
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
     <h3><b>Bin Check&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="do_binl()" title="Lookup Parts by Bin">Lookup by Part</button></b>
<div style='float:right;'>
 <div style='position: fixed; top:20px;'>
    <a class="menuI" title="Menu" href="/wms/webmenu.php"><img border="0" src="/wms/images/menu_grey.png"></a>

 </div>
</div>
</h3>
     <span id="sw">    <div class="w3-half">
      <div  style="margin-left:0px;" class="w3-container"><span style="font-weight: bold; font-size: large; text-align: center;">Invalid Bin</span></div>
    </div></span>
    </header>
   </div>
  </div>
  
  
  
 </body>
</html>
  

 <form name="form1" action="bincheck.php" method="get">
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="lastfunc" value="">
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
     <div class="w3-container myRed w3-padding-8">
        <span class="w3-orange"><br></span>
        <div class="w3-clear"></div>
                <label>Bin Number</label>
                <input type="text" class="w3-white" onchange="do_bin();" value="" name="scaninput" placeholder="Scan or enter Part" id="partnum" title="Scan or Enter Bin Number">
                <br>
                <br>
     </div>
    </div>
  </div>
 </form>
<script>
 document.form1.scaninput.focus();
</script>
<script>
function do_bin()
{
 document.form1.submit();
}
</script><script>
 function do_binl()
 {
  document.location.href="stockchk.php";
 }
</script>
  
 </body>
</html>
