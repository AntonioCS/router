<?php

namespace Router;


class router {

    /**
     * Where all the configurations will go
     *
     * @var mixed     
     */
    private $_config = array();
    
    /**
     * This will contain the instance of the router
     * 
     * @var router
     */
    private static $INSTANCE = null;   
    
    /**
     * Used to determine if a match was found in the routes list (in case I have the only_route_entries set to true)
     * 
     * @var bool
     */
    private $_isRouteInList = false;
               
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
        $valid_route_types = $this->_config->fetch('valid_route_types');        
        $type = strtoupper($type);
        
        if (isset($valid_route_types[$type]) && $valid_route_types[$type]) 
            return $type;
        
        throw new InvalidRouteTypeException($type);                        
    }
    
    /**
     * Class construct
     * 
     * @param array $config 
     */
    public function __construct($config = null) {
        if (!$config) {
            throw new NoConfigurationsSetException();
        }
                
        $this->_config = $config;
    }

    /**
     * Retrieve the instance of the class
     * 
     * @param array $config
     * 
     * @return router 
     */
    public static function getInstance($config = null) {
        if (!self::$INSTANCE)
            self::$INSTANCE = new self($config);
        
        return self::$INSTANCE;        
    }
    
    /**
     * Enable or Disable the use of file controllers
     *
     * @param bool $state
     * 
     * @return router
     * 
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
    public function setControllersDir($dir, $module = 'default') {
        //if (!isset($this->_config['controllers']['dir']))
            //$this->_config['controllers']['dir'] = array();

        foreach ((array)$dir as $dirCheck) {
            if (!is_dir($dirCheck))
                throw new InvalidControllerDirectoryException($dirCheck);
        }

        //$this->_config['controllers']['dir'] = array_merge($this->_config['controllers']['dir'],(array)$dir);
        $this->_config->mergeValue("modules/$module/controllers/dir",(array)$dir);
        return $this;
    }

    /**
    * Return the directory(ies) to the controller(s)
    *
    * @return array
    */
    public function getControllerDir($module = 'default') {
        return $this->_config->fetch("modules/$module/controllers/dir");
    }

    /**
    * Add a valid extension to a controller file
    *
    * @param string/array $ext
    * 
    * @return router
    */
    public function addControllerExt($ext,$module = 'default') {
        if (is_array($ext)) {
            foreach ($ext as $e)
                $this->addControllerExt($e);
        }
        else {            
            $this->_config->mergeValue("modules/$module/controllers/ext",(array)$ext);
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
    public function delControllerExt($ext,$module = 'default') {
        $key = "modules/$module/controllers/ext";        
        $existing_ext = $this->_config->fetch($key);
        
        $this->_config->setValue($key,array_diff($existing_ext,(array)$ext));                
    }

    /**
    * Clear Controller extensions
    *
    * @returns router
    */
    public function clearControllerExt($module = 'default') {
        $this->_config->setValue("modules/$module/controllers/ext",array());
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
    public function addRoute($type,$routeName,$route = null,$destination = null) {

        if (is_array($routeName)) {
            foreach ($routeName as $rName => $v) { 
                $route = current(array_keys($v));
                $dest = $v[$route];
                $this->addRoute($type,$rName,$route,$dest);
            }
        }
        else {            
            $type = $this->_isValidRouteType($type);                        
            $this->_config->setValue("routes/$type/$routeName/$route", $destination);                  
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
    public function getRoutes($type = null, $name = null) {
        $type = ($type ? $this->_isValidRouteType($type) : null);
        $routes = $this->_config->fetch("routes/$type/$name");
        
        return $routes;
    }
    
    /**
    * Remove all routes
    *
    * @return router
    */
    public function clearRoutes() {        
        $this->_config->setValue('routes', 
                                    array(
                                        'GET' =>  array(),
                                        'POST' => array(),
                                        'PUT' => array(),
                                        'DELETE' => array(),
                                        'ALL' => array()
                                    )
                                );
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
                $types = array_keys($this->_config->fetch('valid_route_types'));
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
            if (!empty($pattern) && strlen($pattern) > 1 && $pattern[0] == '/' && preg_match($pattern,$route,$match)) {
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
        
        if ($this->_config->fetch('only_route_entries') && !$this->_isRouteInList) {
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
class NoConfigurationsSetException extends \Exception {}
class InvalidControllerDirectoryException extends \Exception {}
class NoControllerDirectoryException extends \Exception {}
class InvalidRouteTypeException extends \Exception {}
class NoPathSpecifiedException extends \Exception {}
class PathNotInRouteListException extends \Exception {}