<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @Filename: Request.php
 * @User: 王玉龙（wangyulong@hecom.cn）
 * @DateTime: 2017/10/28 09:15
 * @Description 网络请求类库
 */
class Request {

    private $_CI = NULL;

    public function __construct() {
        $this->_CI = & get_instance();
        $this->_CI->load->library('mylog');
    }

    public function doRequest($strUrl, $strMethod = 'post', $arrParams = array(), $arrHeader = array(), $arrOption = array()) {
        $floatStartTime = microtime(TRUE);
        try {
            if (empty($arrOption)) {
                $arrOption = array('timeout' => 10);
            }

            $this->_CI->load->helper('str');
            if (str_starts_with($strUrl, 'https')) {
                $arrOption['CURLOPT_SSL_VERIFYPEER'] = false;
                $arrOption['CURLOPT_SSL_VERIFYHOST'] = false;
            }

            $defaultHeader = array(
                'Content-type' => 'application/x-www-form-urlencoded',
                'logid' => $this->_CI->mylog->getLogId(),
            );
            $arrHeader = array_merge($defaultHeader, $arrHeader);

            $strMethod = strtolower($strMethod);
            //var_dump($strUrl, $strMethod, $arrParams, $arrHeader, $arrOption);die;
            if ($strMethod == 'post' || $strMethod == 'put' || $strMethod == 'patch') {
                $resp = Requests::$strMethod(
                    $strUrl,
                    $arrHeader,
                    $arrParams,
                    $arrOption
                );
            } else if ($strMethod == 'get' || $strMethod == 'head' || $strMethod == 'delete') {
                $resp = Requests::$strMethod(
                    $strUrl,
                    $arrHeader,
                    $arrOption
                );
            } else {
                $this->_CI->mylog->error("unsupported method: $strMethod", 'request');
                return false;
            }
        } catch (Exception $ex) {
            $this->_CI->mylog->error(sprintf('request failed.msg:%s', $ex->getMessage()), 'request');
            return false;
        }

        if ($resp->status_code != 200) {
            $this->_CI->mylog->error(sprintf('request,url:%s,params:%s,response:%s', $strUrl, json_encode($arrParams), strval($resp->body)), 'request');
            return false;
        }
        $resp_body = json_decode($resp->body, true);
        if (empty($resp_body)) {
            $this->_CI->mylog->error(sprintf('request,url:%s,params:%s,response:%s', $strUrl, json_encode($arrParams), strval($resp->body)), 'request');
            return false;
        }

        $floatEndTime = microtime(TRUE);
        $floatElapsed = $floatEndTime - $floatStartTime;

        if ($floatElapsed > 1) {
            $this->_CI->mylog->warning(sprintf('slow request,url:%s,request time:%f', $strUrl, $floatElapsed), 'request');
        }

        if ($resp_body['res'] != 1) {
            $this->_CI->mylog->error(sprintf('request,url:%s,params:%s,response:%s', $strUrl, json_encode($arrParams), strval($resp->body)), 'request');
        } else {
            $this->_CI->mylog->debug(sprintf('request,url:%s,params:%s,response:%s', $strUrl, json_encode($arrParams), strval($resp->body)), 'request');
        }
        return $resp_body;
    }
}