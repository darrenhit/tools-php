<?php

if (!defined('BASEPATH')) {
    exit('No direct script access allowed');
}

/**
 *
 */
abstract class Abstract_id_generator {

    public function __construct($params = array()) {
    }

    abstract public function generate_id();
}
