<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @Filename: Sign.php
 * @User: 王玉龙（wangyulong@hecom.cn）
 * @DateTime: 2017/10/28 09:03
 * @Description 红圈签到、签退控制器
 */
class Sign extends CI_Controller {

    public function __construct() {
        parent::__construct();
        $this->load->model('services/signService');
    }

    /**
     * @description 签到
     */
    public function signIn() {
        $this->signService->signInServ();
    }

    /**
     * @description 签退
     */
    public function signOut() {
        $this->signService->signOutServ();
    }

    /**
     * @description 自动打卡
     */
    public function autoClock() {
        $this->signService->autoClockServ();
    }
}