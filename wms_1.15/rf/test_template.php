<?php
$wmsInclude="../include";
require_once("{$wmsInclude}/cl_template.php");
$msghtm="";
$msg="";
$nh=0;
$toteId=0;
$color="blue";
$thisprogram=basename($_SERVER["PHP_SELF"]);
$header=<<<HTML
<!DOCTYPE html>
<html>
 <head>
 <title>Pallet/Tote Move</title>
 <meta name="robots" content="noindex">
 <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
 <meta name="viewport" content="initial-scale=1.0, width=device-width, user-scalable=yes" />
 <script>
  window.name="test_template";
 </script>

  <link rel="stylesheet" href="/wms/assets/css/wdi3.css">
 <link rel="stylesheet" href="/wms/assets/css/font-awesome.min.css">
 <link rel="stylesheet" href="/wms/Themes/Multipads/Style.css">
 <link href="/jq/bootstrap.min.css" rel="stylesheet">
 <link rel="stylesheet" href="/wms/assets/css/wms.css">

</head>

 <body class="w3-light-grey" >
<!-- !PAGE CONTENT! -->
HTML;

 if ($msg <> "") $color="red";

    $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="testProgram">
  <input type="hidden" name="nh" value="{$nh}">
  <input type="hidden" name="scanTote" value="">
HTML;
   $formName="form1";
   $msg2="Scan Tote/Pallet (Tote, Pallet, Cart, etc) to Move";
   // field params
   $fldPrompt="Tote or Pallet";
   $fldPlaceHolder="Scan Tote/Pallet Id to Move";
   $fldTitle=" title=\"{$msg2}\"";
   $onChange="do_submit();";
   $fldType="text";
   $fldVal="";
   $fldName="tote_id";
   $fldId=" id=\"toteid\"";
   //js to support onChange and buttons 
   $extra_js="";
   $buttons=setStdButtons();

   $data=array("formName"=>$formName,
              "formAction"=>$thisprogram,
              "hiddens"=>$hiddens,
              "color"=>"w3-{$color}",
              "onChange"=>$onChange,
              "fieldType"=>$fldType,
              "fieldValue"=>$fldVal,
              "fieldPrompt"=>$fldPrompt,
              "fieldPlaceHolder"=>$fldPlaceHolder,
              "fieldName"=>$fldName,
              "fieldId"=>$fldId,
              "fieldTitle"=>$fldTitle,
              "msg"=>$msg,
              "msg2"=>$msg2,
              "buttons"=>$buttons,
              "function"=>$extra_js
    );
  $htm=frmtScreen($data,$thisprogram,"generic2");

echo <<<HTML
{$header}
{$htm}
</body>
</html>
HTML;

function frmtScreen($data,$thisprogram,$temPlate="generic1",$incFunction=true)
{
 $ret="";
 $parser = new parser;
 $parser->theme("en");
 $parser->config->show=false;
 $ret=$parser->parse($temPlate,$data);
 if ($incFunction)
 {
  $ret.=<<<HTML
<script>
function do_submit()
{
 document.{$data["formName"]}.submit();
}
</script>
HTML;
 }
 return $ret;

} // end frmtScreen
function setStdButtons($DorC="D")
{
 // args D=Done, C=Cancel
 $w="done";
 $w1="Done";
 if ($DorC == "C")
 {
  $w="cancel";
  $w1="Cancel";
 }
    $buttons=array(
0=>array(
"btn_id"=>"b1",
"btn_name"=>"B1",
"btn_value"=>"submit",
"btn_onclick"=>"do_submit();",
"btn_prompt"=>"Submit"
),
1=>array(
"btn_id"=>"b2",
"btn_name"=>"B2",
"btn_value"=>$w,
"btn_onclick"=>"do_done();",
"btn_prompt"=>$w1
)
);
 return $buttons;
} // end setStdButtons

?>
