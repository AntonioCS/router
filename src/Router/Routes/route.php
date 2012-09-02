<?php


namespace Router\Routes;
/**
 * Description of route
 *
 * @author AntonioCS
 */
class route {
    
    /**
     * Default options for the route
     * @var array
     */
    protected $_options = array(
        'module' => null,
        'controller' => null,
        'action' => null,        
        'func' => null,
        /**
         * Must be in the format 
         * class => array('ClassName','ClassMethod',[if class not loaded, path to file]);
         */
        'class' => array(),
        'params' => null
    );
    
    /**
     * This will contain the route
     * @var string
     */
    protected $_routePattern = null;
    
    /**
     * Where the match data will be stored.
     * When a match is performed (route and routeDynamic) the match result is stored here
     * @var array
     */
    protected $_routeMatch = array();


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
        if ($options instanceof \Closure) {
            $options = array('func' => $options);
        }
        
        $this->_options = array_merge($this->_options,$options);
        return $this;
    }
    
    /**
     * Return route options
     * @return array 
     */
    public function getOptions() {
       return $this->_options; 
    }
    
    /**
     * 
     * @param string $route
     */
    public function setRoute($route) {
        $this->_routePattern = $route;
    }    
    
    /**
     * Return route pattern
     * 
     * @return string
     */
    public function getPattern() {
        return $this->_routePattern;
    }
    
    /**
     * 
     * @return array
     */
    public function getRouteMatch() {
        return $this->_routeMatch;
    }
    
    /**
     * Match given route with the route pattern set
     * 
     * @param string $route
     * @return boolean
     */
    public function match($route) {
        $match = array();
        
        if (preg_match("~$this->_routePattern~",$route,$match)) {            
            $this->_routeMatch = $match;
            return true;
        }
        
        return false;
    }
}
