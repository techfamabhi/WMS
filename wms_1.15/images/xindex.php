<?php
$htm=<<<HTML
<html>
 <base target="_self">
 <head>
  <META HTTP-EQUIV="Refresh" CONTENT="0;URL=../Login.php">
 </head>
 <body>
 </body>
</html>
HTML;
echo $htm;
exit;

$fl=find_all_files("/var/www/Bluejay/images");
$htm=<<<HTML
<html>
 <base target="_self">
 <head>
 </head>
 <body>

HTML;
foreach ($fl as $key=>$file)
{
 $f=str_replace("/var/www","",$file);
 $htm.=<<<HTML
<img src="{$f}" width="48px" height="48px" border="0">{$f}<br>

HTML;
} // end foreach fl
$htm.=<<<HTML
 </body>
</html>

HTML;
echo $htm;
function find_all_files($dir)
{
    $root = scandir($dir);
    foreach($root as $value)
    {
        if($value === '.' || $value === '..') {continue;}
        if(is_file("$dir/$value")) {$result[]="$dir/$value";continue;}
        foreach(find_all_files("$dir/$value") as $value)
        {
            $result[]=$value;
        }
    }
    return $result;
}
?>
