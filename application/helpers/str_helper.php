<?php if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

if (!(function_exists('str_starts_with'))) {
    function str_starts_with($haystack, $needle) {
        return substr_compare($haystack, $needle, 0, strlen($needle)) === 0;
    }
}

if (!(function_exists('str_ends_with'))) {
    function str_ends_with($haystack, $needle) {
        return substr_compare($haystack, $needle, -strlen($needle)) === 0;
    }
}

//去除字符串中所有的空格和换行符
if (!(function_exists('trimall'))) {
function trimall($str){
    $qian=array(" ","　","\t","\n","\r");
    return str_replace($qian, '', $str);   
}
}
//打印函数
if(!(function_exists('p'))) {
	function p($data) {
		echo '<pre style="color:white;backgroud:#333;font-size:14px;">';
	    print_r($data);
	    echo '</pre>';
	}
}

if(!(function_exists('p_v'))) {
	function p_v($data) {
		echo '<pre style="color:white;backgroud:#333;font-size:14px;">';
	    var_dump($data);
	    echo '</pre>';
	}
}
//验证是否是正确的日期格式
if(!(function_exists('checkDateFormat'))) {
	function checkDateFormat($date) {
		$pattern="/^\d{4}\/\d{1,2}\/\d{1,2}$/";
//		echo $date;
		if (preg_match($pattern, $date)) {
			return true;
		}
		return false;
	}
}
//验证是否是正确的邮箱格式
if(!(function_exists('checkEmailFormat'))) {
	function checkEmailFormat($email) {
		if(!filter_var($email, FILTER_VALIDATE_EMAIL))
        {
           return false;
        }
        return true;
	}
}
//验证是否是正确的定位格式
//if(!(function_exists('checkGpsFormat'))) {
//	function checkGpsFormat($gps) {
//		if (preg_match ("/(\d0,3.\d1,6)∗/", $gps)) {
//			return true;
//		}
//		return false;
//	}
//}
//验证是否是正确的手机格式
if(!(function_exists('checkPhoneFormat'))) {
	function checkPhoneFormat($phonenumber) {
		if(preg_match("/^1[34578]{1}\d{9}$/",$phonenumber)){  
            return true;  
        }
        return false;
	}
}
//验证是否是正确的gps坐标格式
if(!(function_exists('checkGpsFormat'))) {
	function checkGpsFormat($gps) {
		if(preg_match("/\((\d)+.(\d)+,(\d)+.(\d)+\)/",$gps)){  
            return true;  
        }
        return false;
	}
}
//excel时间转system时间
if(!(function_exists('excelTimeToSystemTime'))) {
	function excelTimeToSystemTime($excelTime) {
		$newTime = intval($excelTime);
		$time = ($newTime-25569)*24*60*60; //获得秒数
        return date('Y-m-d H:i:s', $time);   
	}
}

