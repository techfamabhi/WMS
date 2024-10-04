<?php

$htm = <<<HTML
<html>
<head>
<script>
function checkBin(entBin,qty)
{
 entBin=entBin.toUpperCase()
 var Ok=true;
 var correctBin = document.getElementById('bintoScan').value;
 if (entBin != correctBin)
 {
 Ok=false;
  var others = document.getElementsByName("otherLoc[]");
  var len=others.length;
  var binOk=false;
  var qtyOk=false;
  var w;
  var i;
  for (i=0; i<len; i++)
  {
   w = others[i].value.split("|");
   if (entBin == w[0]) 
    { 
     binOk=true; 
     if (qty <= parseInt(w[1])) { qtyOk=true; }
    }
  } // end for i
 if (binOk && qtyOk) if (confirm("Would you like to use " + entBin + " instead of " + correctBin + "?")) Ok=true;

 else if (binOk) alert("There is not enough Qty in bin: " + entBin + ", please go to Bin: " + correctBin);
 if (!binOk && !qtyOk) alert("Incorrect Bin has been entered, please go to Bin: " + correctBin);
 }
if (Ok) alert("submit here");
}
</script>
</head>
<body>
 <input type="hidden" name="bintoScan" id="bintoScan" value="A-02-03-C">
 <input type="hidden" name="otherLoc[]" id="othLoc[]" value="A-02-01-C|4">
 <input type="hidden" name="otherLoc[]" id="othLoc[]" value="A-02-06-B|1">

 <label>enter Bin (A-02-03-C)</label>
 <input type="text" style="text-transform:uppercase" name="Bin" value="" onchange="checkBin(this.value,2);">
<button onclick="checkBin('A-02-01-C',2);">A-02-01-C for 4</button>
<button onclick="checkBin('A-05-01-C',2);">A-05-01-C for 4</button>

HTML;
echo $htm;
