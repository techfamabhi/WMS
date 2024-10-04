<!DOCTYPE html>
<html>
<head>
    <title>Putaway</title>
    <meta name="robots" content="noindex">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes"/>
    <script>
        window.name = "scantest1";
    </script>

    <link rel="stylesheet" href="/wms/assets/css/wdi3.css">
    <link href="/jq/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="/wms/Themes/Multipads/Style.css">
    <link rel="stylesheet" href="/wms/assets/css/wms.css">
    <?php
    /*
     <link rel="stylesheet" href="/wms/assets/css/font-awesome.min.css">
     <style>
     .menuI {
      position: absolute;
      right:0;
     }
     </style>
    */
    ?>

    <script>
        function openalt(url, nlns) {
            hgt = 210 + (nlns * 25);
            var popup = window.open(url, "popup", "toolbar=no,left=0,top=125,status=yes,resizable=yes,scrollbars=yes,width=600,height=" + hgt);
            return (false);
        }

        function doView(tote) {
            var url = "tcont.php?toteId=" + tote;
            openalt(url, 10);
            return false;
        }
    </script>

</head>

<body class="w3-light-grey">
<?php
/*
<!-- !PAGE CONTENT! -->
<header class="w3-container w3-green" style="border-radius: 5px;padding-top:4px;padding-bottom:8px;">
 <table width="98%" class="topnav1 z-blue">
  <tr>
   <td nowrap width="25%">
     <span><b><span id="pageTitle">Putaway</span></b>
   </td>
   <td nowrap width="15%"><a href="/wms/webmenu.php">Exit</a>
   </td>

  </tr>
 </table>
</header>
  
  
  
  
<style>
.medBin {
background-color: #87CEEB!important;
  color: white;
  cursor: pointer;
  padding: 10px;
  border: none;
  text-align: left;
  outline: none;
  font-size: 25px;
  font-weight: bold;
}
</style>
*/
?>
<form name="form1" action="scantest1.php" method="get">
    <input type="hidden" name="func" id="func" value="putBin">
    <input type="hidden" name="nh" value="0">
    <input type="hidden" name="toteId" value="152">
    <input type="hidden" name="wmspo" value="1013">
    <input type="hidden" name="hostpo" value="99591">
    <input type="hidden" name="batch_num" value="134">
    <input type="hidden" name="comp" value="1">
    <input type="hidden" name="shadow" value="609121">
    <input type="hidden" name="partUOM" value="EA">
    <input type="hidden" name="pkgQty" value="1">
    <input type="hidden" name="primaryBin" value="A-03-18-B">
    <?php
    /*

      <div class="w3-row-padding w3-margin-bottom">
        <div class="w3-half">
         <div class="container w3-green w3-padding-8">
         <div class="w3-white">
          <div class="w3-padding-8 FormHeaderFont">
    </div>
            <span class="w3-green"><br></span>
            <div class="clear"></div>
          <div class="row">
           <div class="col-75">
    */
    ?>
    <table style="position:relative;left: 6px;" class="table table-bordered table-striped">
        <tr>
            <td class="FieldCaptionTD" colspan="2" align="left" nowrap>Part Number</td>
            <td class="FieldCaptionTD" align="right" nowrap>Qty</td>
            <td class="FieldCaptionTD" align="right" nowrap>Qty In Tote</td>
            <td class="FieldCaptionTD" colspan="1" align="left" nowrap>Description</td>
        </tr>
        <tr>
            <td colspan="2" align="left" nowrap>WIX 49088</td>
            <td align="right" nowrap>
                <input name="Qty" type="number" value="1" step="1" min="0" max="99999">
            </td>
            <td align="right" nowrap>1</td>
            <td class="w3-white" colspan="1" align="left" nowrap>Air Filter</td>
        </tr>
        <tr>
            <td colspan="5" class="w3-white">

                <span class="medBin">Primary Bin A-03-18-B&nbsp;&nbsp;&nbsp;&nbsp;</span>


            </td>
        <tr>
            <td colspan="5" class="w3-white">&nbsp;</td>
        </tr>
        <tr>
            <td nowrap class="FieldCaptionTD" align="left" width="10%">Scan Bin A-03-18-B</td>
            <td class="w3-white" colspan="4" align="left" width="10%">
                <input name="bin" type="text" onchange="document.form1.submit();" value="">
            </td>
        </tr>
        <tr>
            <td colspan="5" class="w3-white">&nbsp;</td>
        </tr>

        <tr>
            <td colspan="5">

                <input type="submit" class="binbutton-small" id="b1" name="B1" value="Submit"
                       onclick="document.form1.submit();">

                <button type="button" class="binbutton-small" id="b3" name="B3" value="ViewTote"
                        onclick="doView(152); return false;">View
                </button>

                <button type="button" class="binbutton-small" id="b2" name="B2" value="done" onclick="do_done();">Done
                </button>

            </td>
        </tr>

    </table>
    <?php
    /*
           </div>
        <br>

         <div class="col-75" style="word-wrap: normal;font-weight: bold; font-size: large; margin-left: 20px; text-align: cput;">Scan the Bin to put this item into
        </div>

          </div>
         </div>
         </div>
        </div>
      </div>
    */
    ?>
</form>
<script>
    document.form1.bin.focus();

    function do_done() {
        document.form1.func.value = "donePressed";
        document.form1.submit();
    }
</script>
<script>
    function do_submit() {
        document.form1.submit();
    }
</script>
<pre>
<?php
print_r($_REQUEST);
?>
</pre>
</body>
</html>
