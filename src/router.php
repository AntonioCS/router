<?php


class router {

    /**
     * Where all the configurations will go
     *
     * @var array
     */
    private $_config = array();

    /**
     * Default configurations
     *
     * @var array
     */
    public static $CONFIG = array(        
        
        /**
         * To only allow routes that are in the route list. This is useful if there are controller settings but you still want to deny access to controllers that are not in the route list
         * @var bool
         */
        'only_route_entries' => false,
        /**
         * File system Controller settings
         * @var array
         */
        'controllers' => array(            
            /**
             * To search for controllers if no route matched a function or class 
             * @var bool
             */
            'enabled' => true,            
            /**
             * Path to controllers 
             * @var array
             */
            'dir' => array(),            
            /**
             * Ext of controllers 
             * @var array
             */
            'ext' => array('php'),            
            /**
             * Try to match a view to the controller
             * @var bool
             */
            'match_controller_view' => true,            
            /**
             * Try to match a view to the controller's action
             * @var bool 
             */
            'match_controller_action_view' => true,
            /**
             * Name/function of the view class
             * @var string/function 
             */            
            'view_class' => '',
        ),        
        //Routes of the router separated by types
        'routes' => array(
            'get' => array(
                '' => 'index' //default controller
            ),
            'post' => array(),
            'put' => array(),
            'delete' => array(),
            'all' => array()
        ),
        'modrewrite' => array(
            'enabled' => true,
            //if not using modrewrite where to get controller and action
            'query_string' => array(
                //All the query string parameters besides these two will be treated as parameters to the action
                'controller' => 'qc',
                'action' => 'qa'
            )            
        )                       
    );

    public function __construct($config = null) {
        if (!$config)
            $this->_config = self::$CONFIG;
    }

    /**
     * Enable or Disable the use of file controllers
     * 
     * @param bool $state 
     * 
     * @return router
     */
    public function setControllersState($state) {
        $this->_config['controllers']['enabled'] = $state;
        return $this;        
    }
    
    /**
     *
     * @return bool
     */
    public function getControllersState() {
        return $this->_config['controllers']['enabled'];        
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
        if (!isset($this->_config['controllers']['dir']))
            $this->_config['controllers']['dir'] = array();

        foreach ((array)$dir as $dirCheck) {
            if (!is_dir($dirCheck))
                throw new InvalidControllerDirectoryException($dirCheck);
        }

        $this->_config['controllers']['dir'] = array_merge($this->_config['controllers']['dir'],(array)$dir);
        return $this;
    }

    /**
    * Return the directory(ies) to the controller(s)
    *
    * @return array
    */
    public function getControllerDir() {
        return $this->_config['controllers']['dir'];
    }

    /**
    * Set the mod rewrite state variable
    *
    * @param bool $state
    */
    public function setModrewriteState($state) {
        $this->_config['modrewrite']['enable'] = (bool)$state;
        return $this;
    }


    /**
    * Fetch mod rewrite setting
    *
    */
    public function getModrewriteState() {
        return $this->_config['modrewrite']['enable'];
    }

    public function formatRoute($route) {


    }

    /**
    * Set the destination of route
    *
    * @param string $type GET/PUT/DELETE/POST - REQUEST_METHOD
    * @param string/array $route - REQUEST_URI/QUERY_STRING
    * @param string/function/array/null $destination - 
    *
    * @return router
    */
    public function addRoute($type,$route,$destination = null) {

        if (is_array($type))
            foreach ($route as $k => $v) {
                $this->addRoute($k,$v);
            }
        else {
            if (!isset($this->_config['routes']))
                $this->_config['routes'] = array();

            $this->_config['routes'][$route] = $destination;
        }
        return $this;
    }
    
    public function addGet($route,$dest = null) {
        return $this->addRoute('get', $route,$dest);
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
            $value();
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