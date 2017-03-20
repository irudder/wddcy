<?php
/**
 * 机房名称
 *
 * @param $mid
 * @return void
 */               
function smarty_modifier_machine_room_name( $mid )
{
   return mod_server::get_machine_rooms($mid);
}
