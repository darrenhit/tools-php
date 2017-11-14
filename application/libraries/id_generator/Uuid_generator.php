<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

require(APPPATH . 'libraries/id_generator/Abstract_id_generator.php');

/**
 *
 */
class Uuid_generator extends Abstract_id_generator {

    public function __construct($params = array()) {
        parent::__construct();
    }

    public function generate_id() {
        return uniqid();
    }
}
