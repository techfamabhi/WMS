<?php

$comp = 1;
$src = "picking.php?nh=1&orderType=%&noSelect=1";
$seconds = "10";

$styleJs = <<<HTML
<style>
      @media (max-width: 780px) {
        .hidden-mobile {
          display: none;
        }
      }
      @media (max-width: 319px) {
        .hidden-mobile1 {
          display: none;
        }
      }
</style>
<title>Picking Queue</title>
<script>
function openalt(url,nlns) {
        hgt=210 + (nlns * 25);
        var popup=window.open(url,"popup", "toolbar=no,left=0,top=125,status=yes,resizable=yes,scrollbars=yes,width=600,height=" + hgt );
 return(false);
     }

function showItems(ordnum)
{
 var url="orddtl.php?orderNum=" + ordnum;
 openalt(url,10);
 return false;
}

</script>
<style>
.zoneButton {
  background-color: #4db8ff;
  border: none;
  color: white;
  padding: 2px;
  text-align: center;
  text-decoration: none;
  display: inline-block;
  font-size: 12px;
  font-weight: bold;
  margin: 0px 2px;
  border-radius: 10px;
}

</style>
<script>
function setSort(fld)
{
 var ele=document.getElementById('sorter');
 var sdir=document.getElementById('sortDir');
 var sortArrow=document.getElementById('si_' + fld);
 ele.value=fld;
 sortArrow.innerHTML="";
 if (sdir.value === 'asc')
  {
   sortArrow.innerHTML='<img src="/wms/images/sort_desc.png" width="16" height="16" border="0" title="Sort Descending"/>';
   document.getElementById('sortDir').value="desc";
  }
 else
  {
   ele.value=fld;
   sortArrow.innerHTML='<img src="/wms/images/sort_asc.png" width="16" height="16" border="0" title="Sort Ascending"/>';
   document.getElementById('sortDir').value="asc";
  }

 document.form1.submit();
}
</script>

<style>
/* The Modal (background) */
.modal {
  display: none; /* Hidden by default */
  position: fixed; /* Stay in place */
  z-index: 1; /* Sit on top */
  left: calc( 0.25rem + 10% );
  top: 60px;
  width: 601px;
  height: 100%;
  overflow: auto; /* Enable scroll if needed */
  background-color: #fefefe;
}

/* Modal Content */
.modal-content {
  background-color: #fefefe;
  margin: auto;
  padding: 0px;
  border: 0px solid #888;
  height: 80%;
  width: 601px;

}

/* The Close Button */
.close {
  color: dodgerblue;
  float: right;
  position: relative;
  font-size: 28px;
  font-weight: bold;
}

.close:hover,
.close:focus {
  color: #000;
  text-decoration: none;
  cursor: pointer;
}
</style>

<!-- The Modal -->
<div id="myModal" class="modal" style="height: 100%">

  <!-- Modal content -->
  <div class="modal-content">
    <span class="close" onclick="cancel_modal();">&times;</span>
    <iframe id="modalFrame" width="100%" height="100%" border="0"></iframe>
  </div>
</div>

<script>
// Get the modal
var modal = document.getElementById("myModal");

// Get the button that opens the modal
//var btn = document.getElementById("myBtn");

// Get the <span> element that closes the modal
//var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal
function setframe(ifr) {
  document.getElementById('modalFrame').src = ifr;
  modal.style.display = "block";
    }
function setframe1(ifr,args) {
  document.getElementById('modalFrame').src = ifr + args;
  modal.style.display = "block";
    }

function cancel_modal() {
    document.getElementById('modalFrame').src = "";
    modal.style.display = "none";
    location.reload();
}

// When the user clicks on <span> (x), close the modal
//span.onclick = function() {
    //modal.style.display = "none";
    //document.getElementById('modalFrame').src = "";
    //location.reload();
//}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
    document.getElementById('modalFrame').src = "";
    location.reload();
  }
}
</script>

HTML;
$htm = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta http-equiv="Content-Language" content="en-us">
<meta http-equiv="refresh" content="{$seconds}">
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="robots" content="noindex">
<meta name="viewport" content="width=device-width, initial-scale=1.5">

<link href="/jq/bootstrap.min.css" rel="stylesheet">
<link href="/wms/Themes/Multipads/Style.css?=time()" type="text/css" rel="stylesheet">
<link rel="stylesheet" href="/wms/assets/css/wms.css">
{$styleJs}
</head>
<body>

<iframe class="responsive-iframe" id="punchout" name="punchout" frameborder="0" width="100%" height="900px" src="{$src}" marginheight="0px"></iframe>
</body>
</html>
HTML;

echo $htm;
