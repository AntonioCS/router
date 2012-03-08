<?php


class router {
    /**
    * Were the routes will go
    *
    * @var array
    */
    private $_routes = array();

    private $_config = array();

    private $_view = null;

    private $_paths;

    private $_controller_dirs = array();



    public static $CONFIG = array(
        'controller_dir' => '',
        'ext' => 'php',
        'routes' => array(
            '/^$/' => 'index', //default controller
        ),


    );

    public function __construct($config = null) {
       /*
        if ($config) {
            foreach ($config as $k => $v) {
                switch ($k) {


                }
            }
        }
         */
    }

    /**
    * Set the directory of the controllers
    *
    * @param string/array $dir
    */
    public function setControllerDir($dir) {
        $this->_controller_dirs = array_merge($this->_controller_dirs,(array)$dir);
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
            $this->_reroutes[$route] = $path;
    }
    public function getRoutes() {
        return $this->_routes;
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