<?php
require_once("{$wmsInclude}/cl_template.php");

// Application Specific Variables -------------------------------------
$temPlate="scanpart";
$title="Stock Check";
$panelTitle="Part";
//$SRVPHP="{$wmsServer}/COMPANY_srv.php";
//$DRPSRV="{$wmsServer}/dropdowns.php";
// end application specific variables ---------------------------------


$msg="";
$msgcolor="";
$js="";
  $title.=<<<HTML
&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<button class="btn btn-primary btn-xs" name="srClr" id="srClr" onclick="do_binl()" title="Lookup Parts by Bin">Lookup by Bin</button>
HTML;
  $hiddens=<<<HTML
  <input type="hidden" name="func" id="func" value="scanPart">
  <input type="hidden" name="lastfunc" value="">
HTML;
  $data=array("formName"=>"form1",
            "formAction"=>$thisprogram,
            "hiddens"=>$hiddens,
            "color"=>"myRed",
            "onChange"=>"do_bin();",
            "fieldType"=>"text",
            "fieldValue"=>"",
            "fieldPrompt"=>"Part Number",
            "fieldPlaceHolder"=>"Scan or enter Part",
            "fieldName"=>"scaninput",
            "fieldId"=>" id=\"partnum\"",
            "fieldTitle"=>" title=\"Scan or Enter Part Number\""
  );
   $mainSection=frmtPartScan($data,$thisprogram);
   if (isset($playsound) and $playsound > 0) $mainSection.=<<<HTML
<audio controls autoplay hidden>
  <source src="{$wmsAssets}/sounds/psycho.wav" type="audio/wav">
</audio>
HTML;

function frmtPartScan($data,$thisprogram)
{
 $ret="";
 $temPlate="scanpart";
 $parser = new parser;
 $parser->theme("en");
 $parser->config->show=false;
 $ret=$parser->parse($temPlate,$data);
 $ret.=<<<HTML
<script>
function do_bin()
{
 document.{$data["formName"]}.submit();
}
</script>
HTML;

 return $ret;

} // end frmtPartScan

