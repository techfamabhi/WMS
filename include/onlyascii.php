<?php
function onlyascii($in) // filter out excel junk high order characters 
    //so they don't end up in the database
{
    $out = "";
    for ($i = 0; $i <= strlen($in); $i++) {
        $z = substr($in, $i, 1);
        $j = ord($z);
        if ($j == 9 or ($j > 31 and $j < 127)) {
            $out .= $z;
        } else {
            $out .= " ";
        }
    }
    return $out;
}

?>
