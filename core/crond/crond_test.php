<?php
/* 采集排序首页的24个图片轮播 hot */ 

ini_set('display_errors', 1);
$time_start = microtime(true);
require './core/init.php';

$text = "<xml><appid><![CDATA[wx426b3015555a46be]]><\/appid>\n<attach><![CDATA[\u65fa\u4e1c\u5927\u83dc\u56ed\u8d2d\u7269\u8ba2\u5355\u652f\u4ed8]]><\/attach>\n<bank_type><![CDATA[CFT]]><\/bank_type>\n<cash_fee><![CDATA[1]]><\/cash_fee>\n<fee_type><![CDATA[CNY]]><\/fee_type>\n<is_subscribe><![CDATA[Y]]><\/is_subscribe>\n<mch_id><![CDATA[1225312702]]><\/mch_id>\n<nonce_str><![CDATA[iaopkwntq7n5oh94jvvcx728wepd9xuc]]><\/nonce_str>\n<openid><![CDATA[oHZx6uOSF3kvEPsAhBRsTA4Dfkeo]]><\/openid>\n<out_trade_no><![CDATA[122531270220150831164527]]><\/out_trade_no>\n<result_code><![CDATA[SUCCESS]]><\/result_code>\n<return_code><![CDATA[SUCCESS]]><\/return_code>\n<sign><![CDATA[17A0EEF6EB687BD2E2A8AD0E80C52548]]><\/sign>\n<time_end><![CDATA[20150831164543]]><\/time_end>\n<total_fee>1<\/total_fee>\n<trade_type><![CDATA[NATIVE]]><\/trade_type>\n<transaction_id><![CDATA[1007490492201508310754837486]]><\/transaction_id>\n<\/xml>";

//$text = stripslashes($text);

$url = "http://www.demo.dcyfood.com/api/wpay_notify.php";
$result = cls_curl::post($url, $text);
echo $result;




//$json = '{"discount":"0.00","payment_type":"1","subject":"\u65fa\u4e1c\u5927\u83dc\u56ed\u8d2d\u7269\u8ba2\u5355\u652f\u4ed8","trade_no":"2015092221001004480097741792","buyer_email":"seatle888@gmail.com","gmt_create":"2015-09-22 18:55:48","notify_type":"trade_status_sync","quantity":"1","out_trade_no":"1509221855203675397","seller_id":"2088911905928925","notify_time":"2015-09-22 18:55:52","body":"\u65fa\u4e1c\u5927\u83dc\u56ed\u8d2d\u7269\u8ba2\u5355\u652f\u4ed8","trade_status":"TRADE_SUCCESS","is_total_fee_adjust":"N","total_fee":"0.01","gmt_payment":"2015-09-22 18:55:51","seller_email":"5039252@qq.com","price":"0.01","buyer_id":"2088002628394484","notify_id":"611bab99af3887b0d4f7cf1fa13450djpc","use_coupon":"N","sign_type":"MD5","sign":"5be9f233ecd7ad881d1c33febc7536d7"}';

//$array = json_decode($json, true);
//$url = "http://www.demo.dcyfood.com/api/alipay_notify.php";
//$result = cls_curl::post($url, $array);
//echo $result;




//$json = '{"MSG":"PE1TRz48TWVzc2FnZT48VHJ4UmVzcG9uc2U+PFJldHVybkNvZGU+MDAwMDwvUmV0dXJuQ29kZT48RXJyb3JNZXNzYWdlPr270tezybmmPC9FcnJvck1lc3NhZ2U+PEVDTWVyY2hhbnRUeXBlPkVCVVM8L0VDTWVyY2hhbnRUeXBlPjxNZXJjaGFudElEPjEwMzg4NDQwMjg5MDAzMDwvTWVyY2hhbnRJRD48VHJ4VHlwZT5QYXlSZXE8L1RyeFR5cGU+PE9yZGVyTm8+MTUwOTIyMTkxMDE0NDkxNTgzOTwvT3JkZXJObz48QW1vdW50PjAuMDE8L0Ftb3VudD48QmF0Y2hObz4wMDAyNTA8L0JhdGNoTm8+PFZvdWNoZXJObz4wMDA3MzQ8L1ZvdWNoZXJObz48SG9zdERhdGU+MjAxNS85LzIyPC9Ib3N0RGF0ZT48SG9zdFRpbWU+MTk6MTA6MTk8L0hvc3RUaW1lPjxQYXlUeXBlPkVQMDU1PC9QYXlUeXBlPjxOb3RpZnlUeXBlPjE8L05vdGlmeVR5cGU+PGlSc3BSZWY+OTAxNTA5MjIxOTA5NTcxMTcyNzwvaVJzcFJlZj48L1RyeFJlc3BvbnNlPjwvTWVzc2FnZT48U2lnbmF0dXJlLUFsZ29yaXRobT5TSEExd2l0aFJTQTwvU2lnbmF0dXJlLUFsZ29yaXRobT48U2lnbmF0dXJlPml6T0w0d3RiTFBINXJRYW50b2d1NFBDaUJzWW5SeEJnOUVoR0o2K24rNVNIa2hHZUNmaTlaL0kzbXMwSllQaWFaNHFhN3drdldJZVJ5VjlzS3lGSGlWTklueUxWSXVscG1hRkhvNjlSY3l5ZlBiYmlVL3hSL2I2MTdUditVSTJrblYxSnM0dDluVUlNalYxNXZIQmpMRXpGUEZybEh6QWdHS0JaY01lMnRhYz08L1NpZ25hdHVyZT48L01TRz4="}';
//$array = json_decode($json, true);
//$url = "http://test.dcyfood.com/api/abc_notify.php";
//$result = cls_curl::post($url, $array);
//echo $result;





$time = microtime(true) - $time_start;

echo "Done in $time seconds\n";
