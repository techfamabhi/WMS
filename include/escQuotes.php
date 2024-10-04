<?php
function escQuotes($in)
{
    $out = str_replace('"', '\"', $in);
    $out = str_replace("'", "\'", $out);
    return $out;
}

?>
