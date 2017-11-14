<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * @Filename: Log.php
 * @User: 王玉龙（wangyulong@hecom.cn）
 * @DateTime: 2017/10/28 09:31
 * @Description 日志类库
 */
require_once APPPATH . 'third_party/vendor/autoload.php';

class Mylog {

    const DEFAULT_CHANNEL = 'all';
    const DEFAULT_PATH = APPPATH . 'logs/';

    private $_CI;
    private $_logId = '';
    private $_loggers = array();
    private $dateFormat = "Y-m-d H:i:s";
    private $outputFormat = "[%level_name%] [%datetime%] %message% " . PHP_EOL;

    public function __construct() {
        $this->_CI = &get_instance();

        if (isset($_SERVER['HTTP_LOGID']) && !empty($_SERVER['HTTP_LOGID'])) {
            $this->_logId = $_SERVER['HTTP_LOGID'];
        } else {
            $this->_CI->load->library('id_generator/Uuid_generator');
            $this->_logId = $this->_CI->uuid_generator->generate_id();
        }
    }

    public function critical($strMessage, $strChannel = self::DEFAULT_CHANNEL) {
        return $this->_doLog($strChannel, $this->_logId . ' ' . $strMessage, \Monolog\Logger::CRITICAL);
    }

    public function error($strMessage, $strChannel = self::DEFAULT_CHANNEL) {
        return $this->_doLog($strChannel, $this->_logId . ' ' . $strMessage, \Monolog\Logger::ERROR);
    }

    public function debug($strMessage, $strChannel = self::DEFAULT_CHANNEL) {
        return $this->_doLog($strChannel, $this->_logId . ' ' . $strMessage, \Monolog\Logger::DEBUG);
    }

    public function info($strMessage, $strChannel = self::DEFAULT_CHANNEL) {
        return $this->_doLog($strChannel, $this->_logId . ' ' . $strMessage, \Monolog\Logger::INFO);
    }

    public function warning($strMessage, $strChannel = self::DEFAULT_CHANNEL) {
        return $this->_doLog($strChannel, $this->_logId . ' ' . $strMessage, \Monolog\Logger::WARNING);
    }

    public function getLogId() {
        return $this->_logId;
    }

    public function setLogId($strLogId) {
        $this->_logId = $strLogId;
    }

    private function _createChannel($channel, $formatter, $level) {
        $logger = new \Monolog\Logger($channel);

        //create handler
        $handler = new MyHandler(self::DEFAULT_PATH, $channel, $level);
        $handler->setFormatter($formatter);
        $logger->pushHandler($handler);
        $this->_loggers[$channel] = $logger;
    }

    private function _doLog($channel, $content, $level) {
        try {
            if (!isset($this->_loggers[$channel])) {
                $formatter = new \Monolog\Formatter\LineFormatter($this->outputFormat, $this->dateFormat);
                $this->_createChannel($channel, $formatter, $level);
            }
            $this->_loggers[$channel]->addRecord($level, $content);
        } catch (Exception $e) {
            return false;
        }
    }
}

Class MyHandler extends \Monolog\Handler\StreamHandler {
    public function __construct($strLogPath, $strChannel, $level = \Monolog\Logger::DEBUG) {
        $logChannel = $strLogPath . SERVICE_NAME . "-" . $strChannel . "-" . date('Ymd', time()) . ".log";
        parent::__construct($logChannel, $level, true, 0666);
    }

}