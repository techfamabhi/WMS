<?php
//10/19/20 dse add onchange
//06/15/22 dse convert to WMS

if (isset($wmsInclude)) require_once("{$wmsInclude}/get_table.php");

function get_companys($db1, $sc = 0)
{
    $where = "where company_number > 0";
    if ($sc > 0) $where = "where company_number = {$sc}";
    $comp_info = array();
    $Comp = get_table($db1, "COMPANY", $where);
    $qstring = $Comp["Select"];
    $rc = $db1->query($qstring);
    $numrows = $db1->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db1->next_record();
        if ($numrows) {
            foreach ($Comp as $key => $pvalue) {
                if ($key <> "Select") {
                    $fld_name = $pvalue["name"];
                    if ($fld_name == "company_number") $compnum = $db1->f("$fld_name");
                    $comp_info[$compnum][$fld_name] = $db1->f("$fld_name");
                }
            } // end foreach Comp
        }
        $i++;
    } // while i < numrows
    return ($comp_info);
} //end of get_companys

function company_dropdown($db, $sel_comp = 0, $deflt = "", $oc = "")
{
    $onchange = "";
    if ($oc <> "") $onchange = "onchange=\"{$oc}\"";
    $dval = "";
    $dpr = "Select Value";
    if ($deflt == "All") {
        $dval = "0";
        $dpr = "All";
    }
    $companys = get_companys($db, 0);
    $SD = "";
    foreach ($companys as $com => $cdata) {
        $sel = "";
        if ($cdata["company_number"] == $sel_comp) $sel = " selected";
        $SD .= <<<HTML
     <option value="{$com}"{$sel}>{$com} - {$cdata["company_abbr"]}</option>

HTML;

    } // end foreach companys
    $comp_select = <<<HTML
        <select id="comp" class="Select" name="comp" {$onchange}>
          <option selected value="{$dval}">{$dpr}</option>
{$SD}
        </select>
HTML;

    return ($comp_select);
} // end company_dropdown
?>
