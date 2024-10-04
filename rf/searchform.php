<?php
// searchForm.php template for a search form

function searchForm($server, $field = "s_sort", $javascripts = "../assets/js")
{
    if ($javascripts == "") $javascripts = "../assets/js";
    $images = "../images";

    $search_js = <<<HTML
<script src="{$javascripts}/jquery-3.3.1.js" type="text/javascript"></script>
<script language="javascript" type="text/javascript">

function getHTMLSuccess(originalRequest) {
        var response = originalRequest.responseText;
        $('htmlResult').innerHTML = response;
}
function getHTMLFailure() {
        alert('woops, what happened?');
}
function check_param() {
 var {$field} = document.getElementById('{$field}').value;
if ({$field}) { getHTML(); }
} 
function getHTML() {
 var {$field} = document.searchform.{$field}.value;
 var stype  = document.searchform.stype.value;

 var queryString = "{$server}?{$field}=" + {$field} + "&stype=" + stype;
//alert(queryString);
if (!{$field}) { return(false); }
if (!queryString) { return(false); }
$('#htmlResult').load(queryString);

}
function toggle_soptions()
{
 var o=document.getElementById('dropd');
 var d=document.getElementById('stype');
 if (o.style.display == "none")
 {
   o.style.display="block";
   d.size="3";
   d.focus();
 }
 else o.style.display="none";
}
</script>

HTML;

    $search_form = <<<HTML
<form name="searchform" id="searchform">
 <table>
  <tr>
   <td>
  <tr>
   <td> 
    <table
    <tr>
    <td>
    <div id="mag">
     <img onclick="toggle_soptions();" src="{$images}/filtersearch.png" width="16px" height="16px" border="0"/>
     <div id="dropd" style="display:none;" onchange="toggle_soptions();"> 
      <select id="stype" name="stype" onchange="getHTML();">
       <option value="f">Begins With</option>
       <option value="a" selected>Is Anywhere</option>
      </select>
     </div>
    </div>
    </td>
    <td>
      <input type="text" id="{$field}" name="{$field}" size="30" value="" placeholder="Search..." autocomplete="off" onkeyup="getHTML();">
    </td>
    </tr>
    </table>
   </td>
  </tr>
 <tr>
  <td>
   <div id="htmlResult"></div>
   </td>
  </tr>
 </table>
</form>
HTML;
    $ret = array();
    $ret["js"] = $search_js;
    $ret["form"] = $search_form;
    return $ret;
} // end searchForm
?>
