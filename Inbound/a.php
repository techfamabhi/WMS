<?php
require_once("../include/db_main.php");
require_once("../include/get_option.php");
$db = new WMS_DB;

$fields = array(
    //Return to Tote as Putaway
    "RET" => array(
        0 => "company",
        1 => "host_po",
        2 => "p_l",   //Product Line
        3 => "part_number",
        4 => "uom",
        5 => "qty",
        6 => "line_type"
    )
);
echo "<pre>";
$in = <<<JSON
{
    "ORDER": {
        "POH": {
            "company": 1,
            "host_po_num": "540862",
            "po_type": "R",
            "vendor": "3690",
            "po_date": "2024\/05\/16 11:49:28",
            "num_lines": 2,
            "bo_flag": "0",
            "xdock": "0",
            "disp_comment": "0",
            "comment": ""
        },
        "POD": [
            {
                "poi_po_num": "540862",
                "poi_line_num": "1",
                "p_l": "DEN",
                "part_number": "3381",
                "part_desc": "73949003119 - DENSO RESISTOR SPARK PL",
                "uom": "EA",
                "qty_ord": 2,
                "mdse_price": 3.07,
                "core_price": 0,
                "line_type": ""
            },
            {
                "poi_po_num": "540862",
                "poi_line_num": "2",
                "p_l": "DEN",
                "part_number": "6731308",
                "part_desc": "72951033 DENSO IGNITION COIL",
                "uom": "EA",
                "qty_ord": 2,
                "mdse_price": 32.53,
                "core_price": 0,
                "line_type": ""
            }
        ]
    }
}
JSON;

$a = check_payload($in);
echo $a;


function check_payload($in)
{
    $result = json_decode($in, true);
    if (json_last_error() === JSON_ERROR_NONE) { // format json as pipe delimited
        if (isset($result["ORDER"]["POH"])) {
            $comp = $result["ORDER"]["POH"]["company"];
            $poType = $result["ORDER"]["POH"]["po_type"];
            $po = $result["ORDER"]["POH"]["host_po_num"];
            if ($poType == "R") { // its a return
                global $db;
                $r = $result["ORDER"]["POD"];
                $opt399 = get_option($db, $comp, 399);
                $opt400 = get_option($db, $comp, 400);
                if (intval($opt399) > 0 and trim($opt400) <> "") { // send part direct to tote
                    if (isset($r) and count($r) > 0) {
                        $return = "";
                        foreach ($r as $d) {
                            $return .= "RET|{$comp}|{$po}|{$d["p_l"]}|{$d["part_number"]}|{$d["uom"]}|{$d["qty_ord"]}|{$d["line_type"]}\n";
                        } // end foreach r
                    }
                    return $return;
                }  // send part direct to tote
            }  // its a return
        } // end order->POH isset
        $dataTypeIn = "json";
        $ret = "";
        $sep = "|";
        // hate to do this but seems the only way to get around it not adding to file
        // if a new line is embeded in the messge
        if (isset($result["ORDER"]["ORD"]["messg"]))
            $result["ORDER"]["ORD"]["messg"] = str_replace("\n", "~~", $result["ORDER"]["ORD"]["messg"]);
        foreach ($result as $key => $data) {
            $j = array_depth($data);
            if ($j > 1) {
                foreach ($data as $key1 => $data1) {
                    $j1 = array_depth($data1);
                    if ($j1 > 1) {
                        foreach ($data1 as $key2 => $data2) {
                            $j2 = array_depth($data2);
                            if ($j2 < 2)
                                $ret .= addTo($key1, $data2, $sep);
                        } // foreach data1
                    } // end is_array data1
                    else
                        $ret .= addTo($key1, $data1, $sep);
                } // end foreach data
            } // is array data
            else
                $ret .= addTo($key, $data, $sep);
        } // end foreach result
        return $ret;
    }  // format json as pipe delimited
    else
        return $in;
} // check payload

