<?php
// convert_comp.php - Convert Host company Id to WMS company number

/* USAGE
$wmsc=convert_comp("A1");
echo "WMS Company= {$wmsc}";
*/

function convert_comp($hostcomp)
{
 // need to figure how to move url to  home dir 
 // and use config variable: $compconvertUrl  for URL

 $w=explode("/",$_SERVER["SCRIPT_NAME"]);
 $top=$w[1];
 $url="http://localhost/{$top}/servers/COMPANY_srv.php";
 $json_request=<<<JSON
{"action": "convert","host_company": "A1"}

JSON;
$opts = array('http' =>
        array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => $json_request
        )
    );

    $context = stream_context_create($opts);
    $x=file_get_contents($url,false,$context);
    $w=json_decode($x,true);
    if (isset($w["company_number"]))
    {
     $ret=$w["company_number"];
    }
    else $ret=-35;
    return($ret);
} // end convert_comp
?>
