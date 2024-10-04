<?php

function searchDdropDown($server,$field="vendor",$onchange="",$placeHolder="Select",$assets="../assets")
{
$a=file_get_contents($server);
$search_js=<<<HTML
<link href="{$assets}/css/Selectstyle.css" rel="stylesheet" type="text/css">
<script src="{$assets}/js/jquery-1.12.4.js"></script>
<script src="{$assets}/js/Selectstyle.js"></script>

HTML;

$oc="";
if (trim($onchange) <> "") $oc=" onchange=\"{$onchange}\" ";
$search_form=<<<HTML

<div class="container">
<select theme="google" {$oc}width="400" style="" name="{$field}" placeholder="{$placeHolder}" data-search="true">
  {$a}
</select>
</div>
<script>
jQuery(document).ready(function($) {
	$('select').selectstyle({
		width  : 400,
		height : 300,
		theme  : 'light',
		onchange : function(val){}
	});
});
</script>
</body>
</html>
HTML;
$ret=array();
$ret["js"]=$search_js;
$ret["form"]=$search_form;
return $ret;
} // end searchDropDown
?>
