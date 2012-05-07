<?php

namespace Router;

class dispatcher {
               
    private $_config = array();
    
    public function __construct($config, $route) {        
        $this->_config = $config;        
    }

}