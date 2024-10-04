<?
// sortFiles
// sort an array of path/filenames by FIFO (first in first out)
// returns full path if addPath is true
// filters out . and .. entries

function sortFiles($path,$in,$addPath=false)
{
 $out = array();
 if (empty($in)) return $out;
 $result = array();
 // get the filemtime and make it the key of the new array
 foreach ($in as $key=>$file)
  {
   if ($file != "." && $file != "..")
     {
      $lastModified = date('U', filemtime("{$path}/{$file}"));
      if ($addPath) $result[$lastModified]="{$path}/{$file}";
       else $result[$lastModified]=$file;
     } // not . or ..
  } // end foreach in

  ksort($result); // ascending sort by key
  $i=0;
  // reset array keys starting at 0 in current order
  foreach ($result as $key=>$file)
  {
   $out[$i]=$file;
   $i++;
  } // end foreach result

 return $out;
} // end getFiles
?>

