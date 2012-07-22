<?php

namespace Router;


/**
 * Route class for the router  
 */
class route {
    
    private static $TYPE_REGEX = 1;
    private static $TYPE_DYNAMIC = 1;
    private static $TYPE_STATIC = 0;
    
    
    private $_type = null;
    
    private $_pattern = null;
    
    private $_patternRaw = null;
    
    private $_r = null;
    
    /**
     * This is what will replace :<place holder>
     * So when a route comes like so:
     *  path/:<place holder>/folder/:<place holder>
     * 
     * @var string
     */
    private $_placeHolderRegex = '(\S+)';
    
    private $_options = array(
        'module' => null,
        'controller' => null,
        'action' => null,
        'params' => null
    );
        
    /**
     *
     * @param string $route
     * @param route $options 
     */
    public function __construct($route, $options = array()) {
        $this->setRoute($route);        
        $this->setOptions($options);
    }
    
    /**
     *
     * @param type $options
     * @return \Router\route 
     */
    public function setOptions($options) {
        $this->_options = array_merge($options,$this->_options);
        return $this;
    }
    
    /**
     *
     * @return type 
     */
    public function getOptions() {
        return $this->_options;
    }
    
    /**
     *
     * @param type $route 
     */
    public function setRoute($route) {
        $this->_patternRaw = $route;        
        
        $rawPattern = explode('/',$route);
        $parsedPattern = array();
        
        $dynamic = false;
        
        foreach ($rawPattern as $section) {
            
            //Found place holder
            if ($section[0] === ':') {
                $dynamic = true;
                $m = substr($section, 1);
                $regex = $this->_placeHolderRegex;
                
                if (isset($this->_options['param'][$m])) {
                    $regex = $this->_options['param'][$m];
                } 
                
                $section = $regex;
            }
            
            $parsedPattern[] = $section;
        }
        
        $this->_pattern = explode('/',$parsedPattern);
        
        if ($dynamic) {
            $this->_r = new \Router\routeRegex($this->_pattern);
        }
        else {
            $this->_r = new \Router\routeStatic($this->_pattern);
        }
        
    }
    
    /**
     * Return the route pattern
     * 
     * @return string
     */
    public function getRoute() {
        return $this->_pattern;
    }
    
    /**
     * Return true if the given route matches the route set
     * 
     * @param string $route
     * @return bool
     */
    public function match($route) {
        return ($route == $this->_route);
    }    
}

class routeStatic extends \Router\route {
    
    public function __construct($route, $options = array()) {
        $this->_type = self::$TYPE_STATIC;
        parent::__construct($route, $options);
    }    
    
    public function setRoute($route) {
        $this->_pattern = $route;
    }
   
    public function match($route) {
        return ($route == $this->_pattern);
    }    
}

class routeRegex extends \Router\route {
    
    public function __construct($route, $options = array()) {
        $this->_type = self::$TYPE_REGEX;
        
        parent::__construct($route, $options);
    }
       
    
    public function setRoute($route) {
        $this->_pattern = "/^$route$/";
    }
    
    public function match($route) {
        if (preg_match($this->_pattern,$route,$match)) {
            return array($this->_options, array('matches' => $match));
        }
        
        return null;
    }
    
}