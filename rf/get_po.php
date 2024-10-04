<?php


if (isset($_REQUEST['scaninput'])) $scaninput = $_REQUEST['scaninput'];
else $scaninput = "";
$action = "get_po.php";
$title = "Receive Purchase Order(2)";
$inc = "../assets/css";
$viewport = "0.75";
$x1 = <<<JSON
[
{"class":"binbutton","type":"button","name":"clrbtn","value":"Clear", "onclick":"do_reset();","colspan":5},
{"class":"binbutton","type":"button","name":"nobtn","value":"No UPC","onclick":"do_noupc();"},
{"class":"binbutton","type":"button","name":"logoff","value":"Log Off","onclick":"do_logoff();"}
]
JSON;
$x = json_decode($x1, true);
$buttons = <<<HTML
 <table>
   <tr>

HTML;
foreach ($x as $key => $data) {
    $col = "";
    if (isset($data['colspan'])) $col = " colspan=\"{$data['colspan']}\"";
    $buttons .= <<<HTML
    <td {$col}><input class="{$data['class']}" type="{$data['type']}" name="{$data['name']}" value="{$data['value']}" onclick="{$data['onclick']}"></td>
HTML;

} // end foreach x


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
    <h5><b>Enter Vendor</b></h5>
  </header>
 <form name="form1" action="{$action}" method="post">
  <input type="hidden" name="whseloc" value="">
  <input type="hidden" name="oldloc" value="">
  <input type="hidden" name="batchnum" value="268">
  
  <div class="w3-row-padding w3-margin-bottom">
    <div class="w3-half">
      <div class="w3-container w3-blue w3-padding-8">
        <div class="w3-clear"></div>
                <label><h4>Vendor</label>
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
