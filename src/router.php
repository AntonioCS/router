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
            '/^$/' => 'index', //default controller
        )
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
                $this->addRoute($k,$v);
            }
        else
            $this->_config['routes'][$route] = $path;
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
    * Try to match the route to any route in the routes list
    *
    * @param mixed $route
    */
    public function matchRoute($route) {
        $routes = $this->getRoutes();

        foreach ($routes as $pattern => $value) {
            //The delimiter must be / for this to work correctly
            if ($pattern[0] == '/' && preg_match($pattern,$route,$match)) {

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

    }

    public function setView($view) {

    }

    public function getView() {

    }



    public function loadcontroller($controller) {
    }

    /**
    * Set the path to the controllers
    *
    * @param string/array $path
    *
    * @return instance
    */
    public function setControllerPath($path) {
        if (is_array())
            $this->_config['controller_dir'] = array_merge($this->_config['controller_dir'],$path);
        else
            $this->_config['controller_dir'][] = $path;

        return $this;
    }

    /**
    * Return the paths to the controllers
    *
    */
    public function getControllerPath() {
        return $this->_config['controller_dir'];
    }

    private function processPath($path) {

    }
}

class dispatcher {

}