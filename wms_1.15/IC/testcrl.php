<?php

require_once("../include/db_main.php");
require_once("../include/get_contrl.php");
$db=new DB_MySQL;

echo "<pre>running getcontrol\n";
$a=get_contrl($db,0,"PARTS");
echo "a={$a}\n";
