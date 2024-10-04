<?php

foreach (array_keys($_REQUEST) as $w) {
    $$w = $_REQUEST[$w];
}
error_reporting(0);


ignore_user_abort(true);

$ok = 0;
$s_sort = strtoupper($_REQUEST['s_sort']);
$pfrom = $_REQUEST['pfrom'];
$pthru = $_REQUEST['pthru'];
$stype = $_REQUEST['stype'];

if (!empty($s_sort)) {
    $ok = 1;
}
if ($ok) {
    require("../include/db_main.php");
    $db = new WMS_DB;
    $srch = "%{$s_sort}%";
    if (strlen($s_sort) == 1 or $stype == "f")
        $srch = "{$s_sort}%";

    //May have to add image back later
    //menu_image,
    $SQL = <<<SQL
select 
 distinct
 menu_desc,
 menu_url,
 menu_target
from WEB_MENU
where upper(menu_desc) like "{$srch}"
  and menu_priv >=  {$pfrom}
  and menu_priv <= {$pthru}
  and menu_line > 0
group by menu_desc
having upper(menu_desc) like "{$srch}"
  and menu_priv >=  {$pfrom}
  and menu_priv <= {$pthru}
  and menu_line > 0
SQL;
    //MYSQL version
    $SQL = <<<SQL
select
 distinct
 menu_desc,
 menu_url,
 menu_target
from WEB_MENU
where menu_priv >=  {$pfrom}
  and menu_priv <= {$pthru}
  and menu_line > 0
  and upper(menu_desc) like "{$srch}"
group by menu_desc

SQL;
    $rc = $db->query($SQL);
    $numrows = $db->num_rows();
    $i = 1;
    while ($i <= $numrows) {
        $db->next_record();
        if ($numrows) {
            $menu[$i]['desc'] = $db->f("menu_desc");
            //$menu[$i]['image']=$db->f("menu_image");
            $menu[$i]['url'] = $db->f("menu_url");
            //$menu[$i]['priv']=$db->f("menu_priv");
            $menu[$i]['target'] = $db->f("menu_target");
        }
        $i++;
    } // while i < numr


    //End Include Common Files
    $pinfo = <<<HTML
<tr>
<td>
<table border="0" width="100%" class="MultipadsFormTABLE">
HTML;
    if (isset($menu) && count($menu))
        foreach ($menu as $item) {
            $targ = "";
            if (trim($item['target']) <> "")
                $targ = "target=\"{$item['target']}\" ";
            $pinfo .= <<<HTML
<tr>
<td>
<a {$targ} href="{$item['url']}">{$item['desc']}</a> 
</td>
</tr>
HTML;
        } // end foreach menu

    $pinfo .= <<<HTML
</td>
</tr>
</table>
HTML;
    echo $pinfo;
    //$fp=fopen("/tmp/ajax.log", "a");
    //fwrite($fp,"$pinfo\n");
    //fclose($fp);

}
?>