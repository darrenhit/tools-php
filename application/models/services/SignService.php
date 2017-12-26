<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @Filename: SignService.php
 * @User: 王玉龙（wangyulong@hecom.cn）
 * @DateTime: 2017/10/28 09:09
 * @Description 红圈签到、签退业务层
 */
class SignService extends CI_Model {

    const SIGN_FLAG = 'hecom_sign';
    const SIGN_URL = 'https://mm.hecom.cn/mobile-0.0.1-SNAPSHOT/rcm/e/rcment_30002/newAttendanceManage/uploadAttendance.do';

    private $_signInStartTime = 0;
    private $_signInEndTime = 0;
    private $_signOutStartTime = 0;
    private $_signOutEndTime = 0;

    public function __construct() {
        parent::__construct();
        $this->load->library('mylog');
        $this->load->library('request');
        $this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));

        $this->_signInStartTime = strtotime(date('Y-m-d') . ' 8:50:00');
        $this->_signInEndTime = strtotime(date('Y-m-d') . ' 9:20:00');
        $this->_signOutStartTime = strtotime(date('Y-m-d') . ' 18:10:00');
        $this->_signOutEndTime = strtotime(date('Y-m-d') . ' 22:59:59');
    }

    public function autoClockServ() {
        $this->doClock();
    }

    public function signInServ() {
        $arrHeader = $this->_getHeader();
        $arrParams = array(
            'sign_in_place_acq' => '0',
            'timeBucketCode' => '6159004',
            'renderTime' => '1505903861710',
            'lat' => 30.278477,
            'flag' => 0,
            'signFlag' => '0',
            'imageName' => '',
            'address' => '文一西路与绿汀路交叉口',
            'sign_out_place_acq' => NULL,
            'lng' => 119.9857,
            'poiName' => '海创大厦',
            'range' => 0,
            'remark' => ''
        );
        $arrSignInRes = $this->request(self::SIGN_URL, $arrParams, $arrHeader);
        if (!empty($arrSignInRes)) {
            $this->mylog->info('签到成功：' . json_encode($arrSignInRes), 'sign');
        }
        $this->mylog->info('红圈签到', 'sign');
    }

    public function signOutServ() {
        $arrHeader = $this->_getHeader();
        $arrParams = array(
            'sign_in_place_acq' => NULL,
            'timeBucketCode' => '6159004',
            'renderTime' => '1505903861710',
            'lat' => 30.278477,
            'flag' => 1,
            'signFlag' => '0',
            'imageName' => '',
            'address' => '文一西路与绿汀路交叉口',
            'sign_out_place_acq' => '0',
            'lng' => 119.9857,
            'poiName' => '海创大厦',
            'range' => 0,
            'remark' => ''
        );
        $arrSignOutRes = $this->request(self::SIGN_URL, $arrParams, $arrHeader);
        if (!empty($arrSignOutRes)) {
            $this->mylog->info('签退成功：' . json_encode($arrSignOutRes), 'sign');
        }
        $this->mylog->info('红圈签退', 'sign');
    }

    private function _checkWorkDay() {
        if (in_array(date('N'), range(1, 5))) {
            return TRUE;
        }
        $this->config->load('spec_workday');
        $arrSpecWorkDay = $this->config->item('spec_workday');
        if (!empty($arrSpecWorkDay) && is_array($arrSpecWorkDay) && in_array(date('Y/m/d'), $arrSpecWorkDay)) {
            return TRUE;
        }
        return FALSE;
    }

    private function _createFlag() {
        if ($this->cache->memcached->get(self::SIGN_FLAG)) {
            return FALSE;
        } else {
            return $this->cache->memcached->save(self::SIGN_FLAG, date('Y-m-d H:i:s') . '新增签到标志', 0);
        }
    }

    private function _delFlag() {
        if ($this->cache->memcached->get(self::SIGN_FLAG)) {
            return $this->cache->memcached->delete(self::SIGN_FLAG);
        } else {
            return FALSE;
        }
    }

    private function doClock() {
        $intTime = time();
        if ($this->_checkWorkDay()) {
            if ($this->_signInStartTime < $intTime && $this->_signInEndTime > $intTime) {
                // 可以签到
                if (FALSE == $this->_getFlag() && $this->_getProbability()) {
                    $this->mylog->debug('走随机签到', 'sign');
                    $this->signInServ();
                    $this->_createFlag();
                }
            } else if ($this->_signOutStartTime < $intTime && $this->_signOutEndTime > $intTime) {
                // 可以签退
                if ($this->_getFlag() && $this->_getProbability()) {
                    $this->mylog->debug('走随机签退', 'sign');
                    $this->signOutServ();
                    $this->_delFlag();
                }
            } else if ($intTime == $this->_signInEndTime && FALSE == $this->_getFlag()) {
                // 最后一次签到机会
                $this->mylog->debug('走最后一次签到', 'sign');
                $this->signInServ();
                $this->_createFlag();
            } else if ($intTime == $this->_signOutEndTime && $this->_getFlag()) {
                // 最后一次签退机会
                $this->mylog->debug('走最后一次签退', 'sign');
                $this->signOutServ();
                $this->_delFlag();
            }
        }
    }

    private function _getFlag() {
        return $this->cache->memcached->get(self::SIGN_FLAG);
    }

    private function _getHeader() {
        return array(
            'Host:mm.hecom.cn',
            'Cookie:credibleMobileSendTime=-1; ctuMobileSendTime=-1; mobileSendTime=-1; riskCredibleMobileSendTime=-1; riskMobileAccoutSendTime=-1; riskMobileBankSendTime=-1; riskMobileCreditSendTime=-1',
            'appType:2',
            'clientType:IOS',
            'User-Agent:RedCircleManager/6.3.9 (iPhone; iOS 10.3.3; Scale/2.00)',
            'tid:f2ce776625cf65187a8589f998b835c9ed9eb083769c5d23fdfa0747b450047d',
            'mobileModel:iPhone SE',
            'entCode:rcment_30002',
            'packageName:com.qineng.Sosgpsfmcg',
            'version:6.3.9',
            'Connection:keep-alive',
            'uid:rcmuser1814210',
            'user-locale:zh_CN',
            'Accept-Language:zh-Hans-CN;q=1',
            'loginId:17681875895',
            'sessionId:IOS_rcmuser1814210_1493536131456_tndu',
            'OSType:ios',
            'Accept:*/*',
            'Content-Type:application/json',
            'Accept-Encoding:gzip, deflate'
        );
    }

    private function _getOptions() {
        return array(
            'timeout' => 5,
            'useragent' => 'RedCircleManager/6.3.9 (iPhone; iOS 10.3.3; Scale/2.00)',
        );
    }

    private function _getProbability() {
        $intRandomNum = rand(0, 10);
        if ($intRandomNum % 5 == 0) {
            return TRUE;
        }
        return FALSE;
    }

    private function request($strUrl, $arrData, $arrHeader) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $strUrl);
        curl_setopt($ch, CURLOPT_POST, TRUE);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($arrData));
        curl_setopt($ch, CURLOPT_HEADER, 0);
        $arrDefaultHeader = array(
            'Content-Type: application/json; charset=utf-8',
            'Content-Length:' . strlen(json_encode($arrData)));
        $arrHeader = array_merge($arrDefaultHeader, $arrHeader);
        //var_dump($strUrl, json_encode($arrData), $arrHeader);die;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $arrHeader);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $res = curl_exec($ch);
        $errNo = curl_errno($ch);
        if ($errNo) {
            $arrError = array('errorno' => $errNo, 'errmsg' => curl_error($ch));
            $this->mylog->error('请求失败，失败原因：' . json_encode($arrError), 'request');
            return $arrError;
        }
        curl_close($ch);
        $this->mylog->info('请求结果：' . $res, 'sign');
        return json_decode($res, true);
    }
}