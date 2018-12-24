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
    const SIGN_URL = 'https://mm.hecom.cn/mobile-0.0.1-SNAPSHOT/rcm/e/rcment_30002//attend/clock/clock.do';
    const EMP_CODE = '1131705053';
    const GET_ATTEND_CLOCK_RESULT_RUL = 'https://mm.hecom.cn/mobile-0.0.1-SNAPSHOT/rcm/e/rcment_30002//attend/clock/getAttendClockResult.do';

    private $_signInStartTime = 0;
    private $_signInEndTime = 0;
    private $_signOutStartTime = 0;
    private $_signOutEndTime = 0;

    public function __construct() {
        parent::__construct();
        $this->load->library('mylog');
        $this->load->library('request');
        $this->load->driver('cache', array('adapter' => 'memcached', 'backup' => 'file'));

        $this->_signInStartTime = strtotime(date('Y-m-d') . ' 8:30:00');
        $this->_signInEndTime = strtotime(date('Y-m-d') . ' 8:43:00');
        $this->_signOutStartTime = strtotime(date('Y-m-d') . ' 18:26:00');
        $this->_signOutEndTime = strtotime(date('Y-m-d') . ' 22:59:59');
    }

    public function autoClockServ() {
        $this->doClock();
    }

    public function signInServ() {
        $arrAttendClockResult = $this->_getAttendClockResult();
        $arrHeader = $this->_getHeader();
        $arrParams = array(
            'classId' => 1941,
            'address' => '文一西路与绿汀路交叉口',
            'clockDeviceType' => 1,
            'attendDate' => strtotime(date('Y-m-d 00:00:00')) . '000',
            'isSpeedClock' => 'y',
            'latitude' => '30.277664648552424',
            'groupId' => 2801,
            'poiName' => '海创大厦',
            'distance' => (rand(0, 99) + mt_rand()/mt_getrandmax()) . rand(100, 999),
            'clockType' => 1,
            'classTimeId' => !empty($arrAttendClockResult['data']['toBeClockVo']['classTimeId']) ? $arrAttendClockResult['data']['toBeClockVo']['classTimeId'] : 1802,
            'longitude' => '119.98566163399281',
            'clockDeviceCode' => 'F1F2F87A-C4D9-498E-BAEA-A3689821FFCD',
            'locationType' => 1
        );
        $arrSignInRes = $this->request(self::SIGN_URL, $arrParams, $arrHeader);
        if (!empty($arrSignInRes)) {
            $this->mylog->info('签到成功：' . json_encode($arrSignInRes), 'sign');
        }
        $this->mylog->info('红圈签到', 'sign');
    }

    public function signOutServ() {
        $arrAttendClockResult = $this->_getAttendClockResult();
        $arrHeader = $this->_getHeader();
        $arrParams = array(
            'classId' => 1941,
            'address' => '文一西路与绿汀路交叉口',
            'clockDeviceType' => 1,
            'attendDate' => strtotime(date('Y-m-d 00:00:00')) . '000',
            'isSpeedClock' => (rand(0, 2) % 2) ? 'y' : 'n',
            'latitude' => '30.277664648552424',
            'groupId' => 2801,
            'poiName' => '海创大厦',
            'distance' => (rand(0, 99) + mt_rand()/mt_getrandmax()) . rand(100, 999),
            'clockType' => 2,
            'classTimeId' => !empty($arrAttendClockResult['data']['toBeClockVo']['classTimeId']) ? $arrAttendClockResult['data']['toBeClockVo']['classTimeId'] : 1802,
            'longitude' => '119.98566163399281',
            'clockDeviceCode' => 'F1F2F87A-C4D9-498E-BAEA-A3689821FFCD',
            'locationType' => 1
        );
        $arrSignOutRes = $this->request(self::SIGN_URL, $arrParams, $arrHeader);
        if (!empty($arrSignOutRes)) {
            $this->mylog->info('签退成功：' . json_encode($arrSignOutRes), 'sign');
        }
        $this->mylog->info('红圈签退', 'sign');
    }

    private function _checkWorkDay() {
        // 特殊的节假日
        $this->config->load('spec_weekends', TRUE);
        $arrSpecWeekends = $this->config->item('spec_weekends');
        if (!empty($arrSpecWeekends) && is_array($arrSpecWeekends) && in_array(date('Y/m/d'), $arrSpecWeekends)) {
            return FALSE;
        }
        // 工作日
        if (in_array(date('N'), range(1, 5))) {
            return TRUE;
        }
        // 特殊的工作日
        $this->config->load('spec_workday', TRUE);
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
                $this->mylog->info('进入随机签到时间段', 'sign');
                // 可以签到
                if (FALSE == $this->_getFlag() && $this->_getProbability()) {
                    $this->mylog->info('走随机签到', 'sign');
                    $this->signInServ();
                    $this->_createFlag();
                }
            } else if ($this->_signOutStartTime < $intTime && $this->_signOutEndTime > $intTime) {
                $this->mylog->info('进入随机签退时间段', 'sign');
                // 可以签退
                if ($this->_getFlag() && $this->_getProbability()) {
                    $this->mylog->info('走随机签退', 'sign');
                    $this->signOutServ();
                    $this->_delFlag();
                }
            } else if ($intTime >= $this->_signInEndTime && $intTime <= ($this->_signInEndTime + 120) && FALSE == $this->_getFlag()) {
                // 最后一次签到机会（两分钟以内）
                $this->mylog->info('走最后一次签到', 'sign');
                $this->signInServ();
                $this->_createFlag();
            } else if ($intTime >= $this->_signOutEndTime && $intTime <= ($this->_signOutEndTime + 120) && $this->_getFlag()) {
                // 最后一次签退机会（两分钟以内）
                $this->mylog->info('走最后一次签退', 'sign');
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
            'Host: mm.hecom.cn',
            'appType: 2',
            'clientType: IOS',
            'User-Agent: RedCircleManager/6.6.0_a1 (iPhone; iOS 11.2.6; Scale/2.00)',
            'mobileModel: iPhone SE',
            'entCode: rcment_30002',
            'net-type: wifi',
            'packageName: com.Sosgps.RedCircleManager',
            'version: 6.6.0_a1',
            'Connection: keep-alive',
            'uid: rcmuser1814210',
            'user-locale: zh_CN',
            'Accept-Language: zh-Hans-CN;q=1',
            'loginId: 17681875895',
            'sessionId: IOS_rcmuser1814210_1535970873074_sgTD',
            'OSType: ios',
            'Accept: */*',
            'Content-Type: charset=utf-8',
            'Accept-Encoding: br, gzip, deflate'
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

    private function _getAttendClockResult() {
        $arrHeader = $this->_getHeader();
        $arrParams = array(
            'empCode' => self::EMP_CODE,
            'attendDate' => strtotime(date('Y-m-d 00:00:00')) . '000'
        );
        $arrAttendClockResult = $this->request(self::GET_ATTEND_CLOCK_RESULT_RUL, $arrParams, $arrHeader);
        if (!empty($arrAttendClockResult['result']) && 0 == $arrAttendClockResult['result']) {
            $this->mylog->info('打卡记录：' . json_encode($arrAttendClockResult['data']));
            return $arrAttendClockResult;
        } else {
            return false;
        }
    }
}
