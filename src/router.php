<?php


class router {

    /**
     * Where all the configurations will go
     *
     * @var array
     */
    private $_config = array();

    private $_view = null;

    /**
     * Default configurations
     *
     * @var array
     */
    public static $CONFIG = array(
        'controller_dir' => array(),
        'ext' => 'php',
        'routes' => array(
            '' => 'index', //default controller
        ),

        'match_controller_action_view' => true,
        'view_dir' => array()
    );

    public function __construct($config = null) {
        if (!$config)
            $this->_config = self::$CONFIG;
    }

    /**
    * Set the directory of the controllers
    *
    * @param string/array $dir
    *
    * @return router
    *
    * @throws InvalidControllerDirectoryException
    */
    public function setControllersDir($dir) {
        if (!isset($this->_config['controller_dir']))
            $this->_config['controller_dir'] = array();

        foreach ((array)$dir as $dirCheck) {
            if (!is_dir($dirCheck))
                throw new InvalidControllerDirectoryException();
        }

        $this->_config['controller_dir'] = array_merge($this->_config['controller_dir'],(array)$dir);
        return $this;
    }

    /**
    * Return the directory(ies) to the controller(s)
    *
    * @return array
    */
    public function getControllerDir() {
        return $this->_config['controller_dir'];
    }

    /**
    * Set the destination of route
    *
    * @param string/array $route
    * @param string/function/array/null $path
    *
    * @return router
    */
    public function addRoute($route,$path = null) {

        if (is_array($route))
            foreach ($route as $k => $v) {
                $this->addRoute($k,$v);
            }
        else {
            if (!isset($this->_config['routes']))
                $this->_config['routes'] = array();

            $this->_config['routes'][$route] = $path;
        }
        return $this;
    }

    /**
     * Return routes
     *
     * @return array
     */
    public function getRoutes() {
        return $this->_config['routes'];
    }

    /**
    * Remove all routes
    *
    * @return router
    */
    public function clearRoutes() {
        $this->_config['routes'] = array();
        return $this;
    }

    /**
    * Try to match the route to any route in the routes list
    *
    * @param mixed $route
    */
    public function matchRoute($route) {
        $routes = $this->getRoutes();

        foreach ($routes as $pattern => $value) {
            //The delimiter must be / for this to work correctly
            if (!empty($pattern) && $pattern[0] == '/' && preg_match($pattern,$route,$match)) {

                if (!is_callable($value) && !is_array($value)) {
                    //if matched test/(\d+) to test/$1
                    $route = preg_replace($pattern,$value,$route);
                }
                else
                    $route = $value;

                break;
            }
            elseif ($pattern == $route) {
                $route = $value;
                break;
            }
        }

        return $route;
    }

    /**
    * Run the given path
    *
    * @param string/null $path
    *
    * If null the current url will be used
    */
    public function run($path = null) {
        $route = $this->matchRoute($path);

        if (is_callable($value) && is_array($value)) {
            //dispatch function or array
        }
        else {//dispatch file system route if found
            //http://hakipedia.com/index.php/Poison_Null_Byte
            //Use $file = str_replace(chr(0), '', $string);
        }

    }

    /**
    * Set a template render object
    *
    * @param object $view
    */
    public function setView($view) {

    }

    /**
    * Return render object
    *
    */
    public function getView() {

    }



    public function loadcontroller($controller) {
    }

}

class InvalidControllerDirectoryException extends Exception {}
class NoControllerDirectoryException extends Exception {}


class dispatcher {

}