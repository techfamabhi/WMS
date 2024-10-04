<?php
$css = "../assets/css";
$htm = <<<HTML
<!DOCTYPE html>
<html>
<title>Bin Assignment</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="initial-scale=0.75, width=device-width, user-scalable=yes" />

<link rel="stylesheet" href="{$css}/wdi3.css">
<link rel="stylesheet" href="{$css}/css">
<link rel="stylesheet" href="{$css}/font-awesome.min.css">
<link href="/jq/bootstrap.min.css" rel="stylesheet">
<link href="../Themes/Multipads/Style.css" rel="stylesheet">


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
</style>

<body class="w3-light-grey">

 
<!-- !PAGE CONTENT! -->
<div class="w3-main" style="margin-left:10px;margin-top:4px;">

  <!-- Header -->
  <header class="w3-container" style="padding-top:12px">
    <h5><b><h2>WIX 51515 Assigned to bin: A0101A</h2></b></h5>
  </header>
 <form name="form1" action="/Bluejay/bin_assign/binscan.php" method="post">
  <input type="hidden" name="whseloc" value="A0101A">
  <input type="hidden" name="oldloc" value="A0101A">
  <input type="hidden" name="batchnum" value="268">
  <div class="w3-row-padding w3-margin-bottom">
      <div class="w3-half">
      <div class="w3-container w3-green w3-padding-8"><h3>WIX 51515:  Oil Filter</h3></div>
    </div>
    <div class="w3-clear"></div>
     <div class="w3-half">
      <div class="w3-container w3-green w3-padding-8">
       <div class="w3-clear"></div>
        <table>
         <tr>
          <td><h2>Bin</td>
          <td><div class="w3-container w3-white">A 01 01 A  </h2></div></td>
         </tr>
        </table>
        <h2>Scan Part<br>
        <input type="text" name="scaninput" value="" style="text-transform:uppercase" onchange="do_bin();">
</h4>
<br>
<br>

       </div>
      </div>
     </div>
  <table>
   <tr>
    <td><input class="binbutton" type="button" value="Clear" onclick="do_reset();"></td>
    <td colspan="4">&nbsp;</td>
    <td><input class="binbutton" type="button" value="No UPC" onclick="do_noupc();"></td>
    <td><input class="binbutton" type="button" name="logoff" value="Log Off" onclick="do_logoff();"></td>
   </tr>
  </table>
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
function do_noupc()
{
 document.form1.scaninput.style.display='none';
 document.form1.scaninput.value="NoUPC";
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
?>
