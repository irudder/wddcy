<?php
/**
 * kb的流量转为Mbps
 *
 * @param $status
 * @return void
 */               
function smarty_modifier_day2date( $dayh )
{
   //12013021
   $y = substr($dayh, 0, 2);
   $m = substr($dayh, 2, 2);
   $d = substr($dayh, 4, 2);
   $h = substr($dayh, 6, 2);
   return "20{$y}-{$m}-{$d} {$h}(havg)";
}
