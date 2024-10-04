<?php
$wmsInclude = "../include";
require_once("{$wmsInclude}/cl_template.php");
require_once("{$wmsInclude}/cl_rf1.php");
$pg = new displayRF;
$pg->viewport = "1.0";
$pg->dispLogo = false;
$pg->Bootstrap = true;
if (isset($title)) $pg->title = $title;
if (isset($color)) $pg->color = $color; else $color = "light-blue";

$parser = new parser;
$parser->theme("en");
$parser->config->show = false;

$data = array(
    "heading" => "Template Array Table Tester",
    "cols" => 4,
    "color" => "w3-green",
    "msg" => "Putaway/Move Tote # 144",
    "formName" => "form1"
);
/*
"flds"=>array(
0=>array("prompt"=>"Tote"),
1=>array("prompt"=>"Location"),
2=>array("prompt"=>"#Items"),
3=>array("prompt"=>"Hash")
),
"items"=>array(
0=>array("newTr"=>1,"prompt"=>"Tote", "value"=>138),
1=>array("newTr"=>0,"prompt"=>"Location", "value"=>"RCV"),
2=>array("newTr"=>0,"prompt"=>"#Items", "value"=>"5"),
3=>array("newTr"=>2,"prompt"=>"Hash", "value"=>"20"),
4=>array("newTr"=>1,"prompt"=>"Tote", "value"=>139),
5=>array("newTr"=>0,"prompt"=>"Location", "value"=>"RCV"),
6=>array("newTr"=>0,"prompt"=>"#Items", "value"=>"2"),
7=>array("newTr"=>2,"prompt"=>"Hash", "value"=>"3")
)
*/
$buttons = array(
    0 => array(
        "btn_id" => "b1",
        "btn_name" => "B1",
        "btn_value" => "submit",
        "btn_onclick" => "do_submit();",
        "btn_prompt" => "Submit"
    ),
    1 => array(
        "btn_id" => "b2",
        "btn_name" => "B2",
        "btn_value" => "cancel",
        "btn_onclick" => "do_done();",
        "btn_prompt" => "Cancel"
    )
);

$data["buttons"] = $buttons;

$temPlate = "radio1";
$ret = $parser->parse($temPlate, $data);

$pg->title = "Putaway/Move Tote # 144";
$pg->Display();
echo $ret;
