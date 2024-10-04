<?php
/*

 <link rel="stylesheet" href="/wms/assets/css/font-awesome.min.css">
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
*/
$htm=<<<HTML
<!DOCTYPE html>
<html lang="en">
 <head>
 <title>Putaway Tote # ORDER151</title>
 <meta name="robots" content="noindex">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes">
  <link rel="stylesheet" href="/wms/assets/css/wdi3.css">
 <link rel="stylesheet" href="/wms/assets/css/wms.css">
 <link rel="stylesheet" href="/wms/Themes/Multipads/Style.css">
 <script>
  window.name="putaway";
 </script>
<style>
.collapsible {
  background-color: #87CEEB!important;
  color: white;
  cursor: pointer;
  padding: 18px;
  width: 100%;
  border: none;
  text-align: left;
  outline: none;
  font-size: 15px;
}

.active, .collapsible:hover {
  background-color: #555;
}

.collapsible:after {
  content: '+';
  color: white;
  font-weight: bold;
  float: right;
  margin-left: 5px;
}

.active:after {
  content: "";
}

.content {
  padding: 0 18px;
  max-height: 0;
  overflow: hidden;
  transition: max-height 0.2s ease-out;
  background-color: #f1f1f1;
}
</style>

</head>
<body>

          <div class="collapsible">
           <span class="FormSubHeaderFont">Bin Locations</span>
          </div>
          <div class="content">
          <table class="table table-bordered table-striped">
           <tr>
            <td class="FieldCaptionTD">Bin</td>
            <td class="FieldCaptionTD">Qty</td>
           </tr>
           <tr>
            <td title="This is the Primary Bin">*&nbsp;A-06-09-E</td>
            <td>62</td>
           </tr>
           <tr>
            <td>&nbsp;&nbsp;Tote: ORDER151</td>
            <td>9</td>
           </tr>
          </table>
         </div>

  <script>
var coll = document.getElementsByClassName("collapsible");
var i;

alert(coll.length);
for (i = 0; i < coll.length; i++) {
  coll[i].addEventListener("click", function() {
    this.classList.toggle("active");
    var content = this.nextElementSibling;
    if (content.style.maxHeight){
      content.style.maxHeight = null;
    } else {
      content.style.maxHeight = content.scrollHeight + "px";
    }
  });
}
</script>
  
 </body>
</html>

HTML;
echo $htm;
