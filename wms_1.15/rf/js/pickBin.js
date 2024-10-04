// pickBin.js
function checkBin(entBin,qty)
{
 var others=getElementById('othLoc');
 var len=others.length;
 var binOk=false;
 var qtyOk=false;
 var w;
 for (i=0; i<len; ++1)
 {
  //split it 
  w = others[i].value.split("|");
  if (entBin == w[0]) { binOk=true; }
  if (qty == w[0]) { qtyOk=true; }
 }
 
}
