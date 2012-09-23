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
        //$this->_config->setValue(['controllers']['enabled'] = $state;
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
    * Add new route to route list
    *
    * @param string $type GET/PUT/DELETE/POST/ALL - REQUEST_METHOD
    * @param string|array $routeName - Name for the route
    * @param string|array|Routes\Route $route 
    *
    * @todo Correct ALL problem. The all should not have a place in the routes array. Routes with all type should be put in all route types
    * @return router
    */    
    public function addRoute($type,$routeName,$route) {
        
        $type = $this->_isValidRouteType($type);                        
        $this->_config->setValue("routes/$type/$routeName", $route);                  
        
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
        $type = ($type ? $this->_isValidRouteType($type) : 'ALL');
        $fetchPath = "routes/$type" . ($name ? "/$name" : null);
        
        return $this->_config->fetch($fetchPath);                
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
    * 
    * @return string|\Router\Routes\route Return the matching route object or if there is no match return the route string
    */
    public function matchRoute($route, $type = 'all') {                    
        $routes = $this->getRoutes($type);
        $this->_isRouteInList = false;
                                
        if (!empty($routes)) {
            foreach ($routes as $routeName => $currentRoute) {
                if ($currentRoute->match($route)) {
                    $this->_isRouteInList = true;
                    $route = $currentRoute;
                    break;
                }
            }                        
        }
        else
            throw new NoRoutesException();

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
    public function run($path = null, $type = 'GET') {
        if ($path === null) {
            if (isset($_SERVER) && isset($_SERVER['QUERY_STRING'])) {
                //In an mvc environment the index.php gets all the requests so I only need the query string part
                $path =$_SERVER['QUERY_STRING']; //Nul byte protection - http://hakipedia.com/index.php/Poison_Null_Byte
            }
            else {
                throw new NoPathSpecifiedException();
            }
        }        
        
        //http://hakipedia.com/index.php/Poison_Null_Byte
        $path = str_replace(chr(0),'',$path);
        
        if (!$type) {
            if (isset($_SERVER) && isset($_SERVER['REQUEST_METHOD'])) {
                $type = $_SERVER['REQUEST_METHOD'];
            }            
            else 
                $type = 'GET';
        }        
        
        $route = $this->matchRoute($path,$type);                

        if ($this->_config->fetch('only_route_entries') && !$this->_isRouteInList) {
            throw new PathNotInRouteListException($path);
        }
        
        //Call dispatcher 
        $d = new \Router\dispatcher($route,$this->_config->fetch('modules'));        
        $d->dispatch();
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
class NoRoutesException extends \Exception {}