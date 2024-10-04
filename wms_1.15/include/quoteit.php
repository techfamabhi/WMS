<?php
// $Id: quotit.php,v 1.2 2015/12/02 18:16:02 root Exp root $
// $Source: /usr1/include/RCS/quotit.php,v $
function quoteit($in,$for_db=0)
{
 if ($for_db > 0 and strpos($in,'"')) $in=str_replace('"','""',$in);
 //if (strpos($in,'"')) $in=str_replace('"','\"',$in);

 return "\"" . $in . "\"";
}
?>
