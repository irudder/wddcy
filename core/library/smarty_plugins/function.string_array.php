<?php
/**
 * 把规范的字符串转为数组并assign
 * @package Smarty
 * @subpackage plugins
 * <{string_array val=  name= spstring= }>
 */
function smarty_function_string_array($params, &$smarty)
{
    if ( empty($params['val']) )
    {
        $smarty->assign($params['name'], array());
        return '';
    }
    if ( empty($params['name']) )
    {
        $smarty->assign($params['name'], array());
        return '';
    }
    if( empty($params['spstring']) )
    {
        $params['spstring'] = "\n";
    }
    
    $arr = explode($params['spstring'], $params['val']);
    
    $smarty->assign($params['name'], $arr);
    
    return '';
    
}


 