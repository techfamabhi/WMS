<?php
echo "<pre>";
$orderZones="";;
$zone="A";
$orderZones=addZone($orderZones,$zone);
$zone="A";
$orderZones=addZone($orderZones,$zone);
$zone="B";
$orderZones=addZone($orderZones,$zone);
$zone="A";
$orderZones=addZone($orderZones,$zone);


function addZone($orderZones,$zone)
{
       $zone=trim($zone);
       if (strpos(trim($orderZones),$zone) === false)
       {
        $comma="";
        if (strlen($orderZones) > 0) $comma=",";
        $orderZones.="{$comma}{$zone}";
       }

 return $orderZones;
}
