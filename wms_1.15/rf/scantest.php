<?php

foreach (array_keys($_REQUEST) as $w) { $$w=$_REQUEST[$w]; }
$oldScan="";
if (isset($theScan)) $oldScan="<h2>Last Scan = |{$theScan}|</h2>";

$htm=<<<HTML
<!DOCTYPE html>
<html>
 <head>
 <title>Putaway</title>
 <meta name="robots" content="noindex">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes" />

</head>

 <body class="w3-light-grey" >
 <h1>Scanner test</h1>
{$oldScan}

<form name="form1" action="{$_SERVER["PHP_SELF"]}">
<input type="text" name="theScan" value="" onchange="document.form.submit();">
   <br>
  <input type="submit" value="Submit">
</form>
<script>
 document.form1.theScan.focus();
</script>
 </body>
</html>

HTML;
echo $htm;
