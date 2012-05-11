<?php

namespace Router;


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
         * To only allow routes that are in the route list. 
         * This is useful if there are controller settings but you still want to 
         * deny access to controllers that are not in the route list
         * 
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
             * Default action to be called when there is no action specified
             *@var string 
             */
            'default_action' => 'index',
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
            'match_controller_action_view' => true
        ),
        //Routes of the router separated by types
        'routes' => array(
            'GET' => array(
                '' => 'index' //default controller
            ),
            'POST' => array(),
            'PUT' => array(),
            'DELETE' => array(),
            'ALL' => array()
        ),   
       /**
        * Valid route types
        * 
        * @var array
        */
        'valid_route_types' => array(
            'GET' => 1,
            'PUT' => 1,
            'POST' => 1,
            'DELETE' => 1,
            'ALL' => 1 //Special case so that a route can be valid in any case
        ),
        
        'modrewrite' => array(
            'enabled' => true,
            //if not using modrewrite where to get controller and action
            'query_string' => array(
                //All the query string parameters besides these two will be treated as parameters to the action
                'controller' => 'qc',
                'action' => 'qa'
            )
        ),
        /**
         * Name/function of the view class
         * @var string/function
         */
        'view_class' => ''       
    );
    
    /**
     * Used to determine if a match was found in the routes list (in case I have the only_route_entries set to true)
     * 
     * @var bool
     */
    private $_isRouteInList = false;
    
    
    /**
     * Access the _config property and return specified section
     * 
     * @param string $section
     * @return array/string
     * 
     * @throws OutOfBoundsException 
     */
    private function _getConfig($section) {
        $sections = explode('/',$section);
        $tempSectionData = null;
        $e = null;        
        
        foreach ($sections as $currentSection) {
            if ($tempSectionData) {
                if (!isset($tempSectionData[$currentSection])) {
                    $e = $section . ' - ' . $currentSection;
                    break;
                }
                else {
                    $tempSectionData = $tempSectionData[$currentSection];
                }
            }
            else {
                if (!isset($this->_config[$currentSection])) {
                    $e = $section . ' - ' . $currentSection;
                    break;
                }
                else {
                    $tempSectionData = $this->_config[$currentSection];
                }
            }
        }
        
        if ($e)
            throw new \OutOfBoundsException($e);
        
        return $tempSectionData;            
    }
    
    
    /**
     * Check if a type is valid.
     * 
     * @param string $type 
     * 
     * @return string $type Return the type if valid (in uppercase)
     *      
     * @throws InvalidRouteTypeException 
     */
    private function _isValidRouteType($type) {
        $valid_route_types = $this->_getConfig('valid_route_types');        
        $type = strtoupper($type);
        
        if (!isset($valid_route_types[$type])) 
            throw new InvalidRouteTypeException($type);
        
        
        return $type;
        
    }
    

    public function __construct($config = null) {
        if (!$config)
            $this->_config = self::$CONFIG;
        else {
            $this->_config = array_merge(self::$CONFIG,$config);
        }
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
    * Add a valid extension to a controller file
    *
    * @param string/array $ext
    * 
    * @return router
    */
    public function addControllerExt($ext) {
        if (is_array($ext)) {
            foreach ($ext as $e)
                $this->addControllerExt ($e);
        }
        else {
            if (!isset($this->_config['controllers']['ext']))
                $this->_config['controllers']['ext'] = array();
            
            if (in_array($ext, $this->_config['controllers']['ext']) === false)        
                array_push($this->_config['controllers']['ext'],$ext);
        }
        
        return $this;
    }

    /**
    * Delete an extension for a controller
    *
    * @param string/array $ext
    *
    * @return router
    */
    public function delControllerExt($ext) {
        if (is_array($ext)) {
            foreach ($ext as $e)
                $this->delControllerExt($e);
        }
        else {
            foreach ($this->_config['controllers']['ext'] as $k => $e) {
                if ($e == $ext) {
                    unset($this->_config['controllers']['ext'][$k]);
                    break;
                }
            }

            return $this;
        }
    }

    /**
    * Clear Controller extensions
    *
    * @returns router
    */
    public function clearControllerExt() {
        $this->_config['controllers']['ext'] = array();
        return $this;
    }


    public function setControllerMatchView($state) {

    }

    public function getControllerMatchView() {

    }

    public function setControllerMatchActionView($state) {

    }

    public function getControllerMatchActionView($state) {

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

    /**
    * Create a new route in the 
    *
    * @param string $type GET/PUT/DELETE/POST/ALL - REQUEST_METHOD
    * @param string/array $route - REQUEST_URI/QUERY_STRING
    * @param string/function/array/null $destination - The destination can be null because I set the option to only allow registered routes. 
    *                                      So it might not redirect to any where or have a function/class but just be a normal route to a controller/action file
    *
    * 
    * @return router
    */
    public function addRoute($type,$route,$destination = null) {

        if (is_array($route)) {
            foreach ($route as $k => $v) {      
                $this->addRoute($type,$k,$v);
            }
        }
        else {
            if (!isset($this->_config['routes']))
                $this->_config['routes'] = array();
            
            $type = $this->_isValidRouteType($type);            
            
            if (!isset($this->_config['routes'][$type])) {                                                                        
                $this->_config['routes'][$type] = array();
            }

            $this->_config['routes'][$type][$route] = $destination;
        }
        
        return $this;
    }    



    /**
     * Return routes
     *
     * @param string $type 
     * 
     * @throws InvalidRouteTypeException
     * 
     * @return array
     */
    public function getRoutes($type = null) {
        $routes = $this->_getConfig('routes');
        
        if ($type) {  
            $type = $this->_isValidRouteType($type);
                        
            if (!isset($routes[$type]))
                return null;
            
            return $routes[$type];
        }
        
        return $routes;
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
    * Try to match the route to any route in the routes list. (going through all the types)
    *
    * @param mixed $route
    * @param string $type 
    */
    public function matchRoute($route, $type = null) {                    
        $routes = $this->getRoutes($type);        
        
        if (!empty($routes)) {
            if ($type) {
                $all = $this->getRoutes('all');
                if (!empty($all))
                    $routes = array_merge($routes,$all);                
                
                $route = $this->_matchRouteRecursive($routes, $route);
            }
            else {
                $types = array_keys($this->_getConfig('valid_route_types'));
                $newroute = null;
                                
                foreach ($types as $route_type) {  
                    if (!isset($routes[$route_type]))
                        continue;
                    
                    $newroute = $this->_matchRouteRecursive($routes[$route_type], $route);
                    if ($newroute != $route || $this->_isRouteInList) {                      
                        $route = $newroute;
                        break;
                    }                    
                }
            }
        }

        return $route;
    }
    
    /**
     * Match the route to a given set of routes (used with matchRoute)
     * 
     * @param array $routes - Routes of a specific type (GET, POST etc..)
     * @param string $route
     * @return string
     */
    private function _matchRouteRecursive($routes,$route) {        
        foreach ($routes as $pattern => $value) {
            //var_dump($route,$pattern,$value);
            //The delimiter must be / for this to work correctly
            if (!empty($pattern) && $pattern[0] == '/' && preg_match($pattern,$route,$match)) {
                $this->_isRouteInList = true;
                        
                if ($value != null) {
                    if (!is_callable($value) && !is_array($value)) {
                        //if matched test/(\d+) to test/$1
                        $route = preg_replace($pattern,$value,$route);
                    }
                    else
                        $route = $value;
                }
                break;
            }
            elseif ($pattern == $route) {
                $this->_isRouteInList = true;
                
                if ($value != null)
                    $route = $value;
                
                break;
            }
        }

        return $route;
    }

    /**
    * Run the given path
    *
    * @param string/null $path - Url
    * @param string/null $type - Request method
    *
    * If null the current url will be used
    */
    public function run($path = null, $type = null) {
        if ($path === null) {
            if (isset($_SERVER) && isset($_SERVER['QUERY_STRING'])) {
                //In an mvc environment the index.php gets all the requests so I only need the query string part
                $path = str_replace(chr(0),'',$_SERVER['QUERY_STRING']); //Nul byte protection - http://hakipedia.com/index.php/Poison_Null_Byte
            }
            else {
                throw new NoPathSpecifiedException();
            }
        }        
        
        if (!$type) {
            if (isset($_SERVER) && isset($_SERVER['REQUEST_METHOD'])) {
                $type = $_SERVER['REQUEST_METHOD'];
            }            
        }
        
        
        $route = $this->matchRoute($path,$type);                
        $res = null;
        
        if ($this->_getConfig('only_route_entries') && !$this->_isRouteInList) {
            throw new PathNotInRouteListException($route);
        }
        
        //Call dispatcher
       
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

/** 
 * Router exceptions
 */
class InvalidControllerDirectoryException extends \Exception {}
class NoControllerDirectoryException extends \Exception {}
class InvalidRouteTypeException extends \Exception {}
class NoPathSpecifiedException extends \Exception {}
class PathNotInRouteListException extends \Exception {}