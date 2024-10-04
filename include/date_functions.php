<?php

//date_functions.php

function day_add($Date, $Days) // Add days to a date
{
    return date('m/d/Y', strtotime("{$Date} + {$Days} days"));
} // end date_add

function month_add($Date, $Months) // add months to a date
{
    return date('m/d/Y', strtotime("{$Date} + {$Months} months"));
} // end date_add

function usa_to_eur($inDate) // convert usa date to ISO
{
    return (date("Y-m-d H:i:s", strtotime($inDate)));
}

function eur_to_usa($inDate, $incTime = true) // convert ISO date to usa
{
    $frmt = "m/d/Y";
    if ($incTime) $frmt = "m/d/Y H:i:s";
    return (date($frmt, strtotime($inDate)));
}

function mdy_to_date($mm, $dd, $yy) // m,d,y to date
{
    return (date("m/d/Y", mktime(0, 0, 0, $mm, $dd, $yy)));
} // end mdy_to_date


function day_diff($date1, $date2) // get diff between 2 dates in  days
{
//Returns the number of days between 2 dates
    $now = time(); // or your date as well
    $d1 = strtotime($date1);
    $d2 = strtotime($date2);
    $datediff = $d1 - $d2;
    return (floor($datediff / (60 * 60 * 24)));
} // end day_diff

function gTime($inDate)
{ // gTime return time portion of date
    return (date("H:i", strtotime($inDate)));
} // gTime return time portion of date

function gDate($inDate)
{ // gDate return date portion of date
    return (date("m/d/y", strtotime($inDate)));
} // gDate return date portion of date

function mdiff($in, $inMins = false) // get diff of date to now in minutes
{ // return minutes from in to now
    $fdate = new DateTime($in);
    $now = new DateTime('NOW');
    $d = $fdate->diff($now);
    if ($inMins) return ($d->d * 1440) + ($d->h * 60) + $d->i;
    $h = sprintf("%02d", $d->h);
    $i = sprintf("%02d", $d->i);
    $days = $d->days;
    if ($d->d > 0) return "{$days}d {$h}:{$i}";
    else return "{$h}:{$i}";
} // end mdiff

?>
