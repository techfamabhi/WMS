<?php
function dispBins($whseLoc)
{
    // display all bin locations in a collapsable field
    if (count($whseLoc) > 0) {
        $ihdr = "<td>&nbsp;</td>";
        $ihtm = "";
        $detail = <<<HTML

   <div class="collapsible">
    <span class="FormSubHeaderFont">Bin Locations</span>
   </div>
   <div class="content">
    <table width="50%" class="table table-bordered table-striped">
     <tr>
      <td class="FieldCaptionTD">Bin</td>
      <td  align="right" class="FieldCaptionTD">Qty</td>
     </tr>

HTML;
        foreach ($whseLoc as $rec => $l) {
            $tdt = "";
            $btype = $l["whs_code"];
            if ($btype == "P") {
                $btype = "*&nbsp;";
                $tdt = " title=\"This is the Primary Bin\"";
            } else $btype = "&nbsp;&nbsp;";
            $theBin = $l["whs_location"];
            $theQty = $l["whs_qty"];
            $ihtm = "<td>&nbsp;</td>";
            if (substr($theBin, 0, 1) == "!") $theBin = "Tote: " . substr($theBin, 1);
            $detail .= <<<HTML
     <tr>
      <td {$tdt}>{$btype}{$theBin}</td>
      <td align="right">{$l["whs_qty"]}</td>
     </tr>

HTML;
        } // end foreach whseLoc
        $detail .= <<<HTML
    </table>
   </div>

HTML;
    } // end count whseLoc > 0
    return $detail;
} // end dispBins
?>
