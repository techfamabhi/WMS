<?php
//cl_modal.php -- gen stuff needed to create a Modal with an iframe
//11/04/20  dse initial
/*
 * you need to imbed StyleSheet, Modal and javaScript into your html
 *
 * use onchange or onclick to set the iframe content of the model
 * example: onchange="setframe('do_whatever.php');"
 *
 * to add arguments, use setframe1,arguments (include ?'s and &'s)
 * example: onclick="setframe('do_whatever.php','?arg1=1234');"
*/

class cl_Modal
{
    public $StyleSheet;
    public $Modal;
    public $javaScript;

    function init($modalName="myModal",$frameName="frame")
    {

    $this->StyleSheet=<<<HTML
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

HTML;

$this->Modal=<<<HTML
<!-- The Modal -->
<div id="{$modalName}" class="modal">

  <!-- Modal content -->
  <div class="modal-content">
    <span class="close" onclick="cancel_modal();">&times;</span>
    <iframe id="{$frameName}" width="100%" height="100%" border="0"></iframe>
  </div>
</div>

HTML;
  //set the javascript
  $this->javaScript=<<<HTML
<script>
// Get the modal
var modal = document.getElementById("{$modalName}");

// Get the button that opens the modal
//var btn = document.getElementById("myBtn");

// Get the <span> element that closes the modal
//var span = document.getElementsByClassName("close")[0];

// When the user clicks the button, open the modal
function setframe(ifr) {
  document.getElementById('{$frameName}').src = ifr;
  modal.style.display = "block";
    }
function setframe1(ifr,args) {
  document.getElementById('{$frameName}').src = ifr + args;
  modal.style.display = "block";
    }

function cancel_modal() {
    document.getElementById('{$frameName}').src = "";
    modal.style.display = "none";
    location.reload();
}

// When the user clicks on <span> (x), close the modal
//span.onclick = function() {
    //modal.style.display = "none";
    //document.getElementById('{$frameName}').src = "";
    //location.reload();
//}

// When the user clicks anywhere outside of the modal, close it
window.onclick = function(event) {
  if (event.target == modal) {
    modal.style.display = "none";
    document.getElementById('{$frameName}').src = "";
    location.reload();
  }
}
</script>
HTML;
    } // end function init
} // end class cl_Modal
?>
