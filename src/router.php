<?php


class router {   

    /**
     * Where all the configurations will go
     * 
     * @var array
     */
    private $_config = array();

    private $_view = null;


    private $_controller_dirs = array();


    
    /**
     * Default configurations
     * 
     * @var array
     */
    public static $CONFIG = array(
        'controller_dir' => array(),
        'ext' => 'php',
        'routes' => array(
            '/^$/' => 'index', //default controller
        ),


    );

    public function __construct($config = null) {
 
    }

    /**
    * Set the directory of the controllers
    *
    * @param string/array $dir
    */
    public function setControllerzsDir($dir) {
        $this->_config['controller_dirs'] = array_merge($this->_config['controller_dirs'],(array)$dir);
        return $this;
    }

    /**
    * Set the destination of route
    *
    * @param string/array $route
    * @param array/null $path This will be an associative array like 'Key' => function()
    */
    public function addRoute($route,$path = null) {
        if (is_array($route))
            foreach ($route as $k => $v) {
                $this->addReRoute($k,$v);
            }
        else
            $this->_config['routes'][$route] = $path;
    }
    
    public function getRoutes() {
        return $this->_config['routes'];
    }

    /**
    * Run the given path
    *
    * @param string/null $path
    *
    * If null the current url will be used
    */
    public function run($path = null) {
    }

    public function setView($view) {

    }

    public function getView() {

    }



    public function loadcontroller($controller) {
    }

    public function setControllerPath() {

    }

    public function getControllerPath() {

    }

    private function processPath($path) {

    }
}