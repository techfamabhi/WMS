<?php
$wmsInclude="../include";
require_once("{$wmsInclude}/cl_template.php");
 $parser = new parser;
 $parser->theme("en");
 $parser->config->show=false;

$data=array(
"heading"=>"Template Array Table Tester",
"cols"=>4,
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
);
 
 $temPlate="test";
 $ret=$parser->parse($temPlate,$data);
echo $ret;
