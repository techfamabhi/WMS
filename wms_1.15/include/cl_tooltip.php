<?php
/* requires assets/css/tooltip.css
was going to display line items in a table on a tooltip, but
 never finished because line count may be greator than the screen
can handle
was going to pass in array like
array("title"=>"My Title",
"Header"=>array("Fld1Prompt","fld2Prompt",etc...),
"Fields"=>array(value1, value2, etc...)
)

*/

class ToolTip
{
 public Tip;

 function render($tip=array())
 {
 $hdr="";
 $det="";
 if (isset($tip["Fields"])) $cnt=count($tip["Fields"]);
 $htm=<<<HTML
<div class="tooltip"><span class="tooltiptext">
 <table border="1" class="FormTABLE">

HTML;
  if (isset($tip["title"]))
  $htm.=<<<HTML
 <tr>
 <th colspan="4" nowrap align="center">{$title}</th>
 </tr>

HTML;
 if (isset($tip["Header"]) and count($tip["Header"]) > 0)
 $hdr="  <tr>\n";
 $det="";
 foreach ($tip["Header"] as $key=>$t)
 {
   $hdr.=<<<HTML
   <th class="ColumnTD">{$t}</th>
HTML;
  
 } // end foreach Header 
 $hdr.="  </tr>";

 <tr>
  <td class="DataTD">1</td>
  <td class="DataTD">GPD</td>
  <td class="DataTD" nowrap>1711446</td>
  <td class="DataTD">1</td>
 </tr></table>
</span>

HTML;
 } // end render
} // end ToolTip
?>
