<?php

$htm=<<<HTML
<!DOCTYPE html>
<html>
 <title>Receive PO(s)</title>
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=0.75, width=device-width, user-scalable=yes" />

  <link rel="stylesheet" href="../assets/css/wdi3.css">
 <link rel="stylesheet" href="../assets/css/font-awesome.min.css">
 <link rel="stylesheet" href="../Themes/Multipads/Style.css">
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
 <style>
.btn img {  
    display: inline-block;
    vertical-align: middle;
    background: #fefefe;
    padding: 5px;
    border-radius: 5px;
}
.topright {
  position: relative;
  top: 0px;
  right: 0px;
  padding: 1px;
}
.ctm {
    background-image: "../images/lock.png";
    background-repeat: no-repeat;
    background-attachment: fixed;
    background-position: top-right;
}
 </style>
 
 
   </header>

 <body class="w3-light-grey" >
 <div>
 <label for="scaninput">Scan Part</label>
 <input class="ctm" type="text" name="scaninput" value="" size="22">
 </div>
 <div>
 <label for"packid">Bin/Pack</label>
 <input class="ctm" type="text" name="packid" value="12345" size="15">
 <label for"qty">Qty</label>
 <input class="ctm" type="number" name="qty" value="1" min="0" max="99999">
 </div>
</body>
</html>

HTML;
echo $htm;
