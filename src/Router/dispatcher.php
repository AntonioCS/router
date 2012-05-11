<?php

namespace Router;

class dispatcher {
               
    private $_config = array();
    
    public function __construct($config, $route) {        
        $this->_config = $config;
        
        $this->map($route);
    }
    
    
    private function map() {
        
        if (is_callable($route)) {
            $res = $route();
            //dispatch function or array
        }
        elseif (is_array($route)) {
            
        }
        else {
            ////dispatch file system route if found
            //http://hakipedia.com/index.php/Poison_Null_Byte
            //Use $file = str_replace(chr(0), '', $string);          
        }
    }
}