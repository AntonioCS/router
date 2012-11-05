<?php

namespace Router;

class router {

    /**
     * Where all the configurations will go
     *
     * @var SettingsManager\settingsManager
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
     * @return array $type Return the type if valid (in uppercase). If type is ALL it will return all types
     *
     * @throws InvalidRouteTypeException
     */
    private function _isValidRouteType($type) {
        $valid_route_types = $this->_config->fetch('valid_route_types');
        $type = strtoupper($type);

        if (isset($valid_route_types[$type]) && $valid_route_types[$type]) {                        
            if ($type == 'ALL') {
                return array_filter(array_keys($valid_route_types),function($value) { return $value != 'ALL'; });
            }
            else {
                return array($type);
            }
        }

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
    public function getControllersState($module) {
        return $this->_config->fetch("modules/$module/controllers/enabled");
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
    * @return \Router\router
    */
    public function delControllerExt($ext,$module = 'default') {
        $key = "modules/$module/controllers/ext";
        $existing_ext = $this->_config->fetch($key);

        $this->_config->setValue($key,array_diff($existing_ext,(array)$ext));
    }

    /**
    * Clear Controller extensions
    *
    * @returns \Router\router
    */
    public function clearControllerExt($module = 'default') {
        $this->_config->setValue("modules/$module/controllers/ext",array());
        return $this;
    }

    /**
     * Add multiple routes at once
     * Routes must be in the format
     * [ROUTE TYPE]
     * [
     *      [routeName] => route,
     *      [routeName] => route,
     * ],
     * [ROUTE TYPE]
     * ...
     *
     * @param array $routes
     * @return \Router\router
     */
    public function addRoutes($routes) {
        foreach ($routes as $routeType => $routes) {
            foreach ($routes as $routeName => $route){
                $this->addRoute($routeType, $routeName, $route);
            }
        }

        return $this;
    }
    /**
    * Add new route to route list
    *
    * @param string $type GET/PUT/DELETE/POST/ALL - REQUEST_METHOD
    * @param string|array $routeName - Name for the route
    * @param Routes\Route $route
    *
    * @todo Correct ALL problem. The all should not have a place in the routes array. Routes with all type should be put in all route types
    * @return \Router\router
    */
    public function addRoute($type,$routeName,Routes\Route $route) {

        $types = $this->_isValidRouteType($type);
        foreach ($types as $type) {                 
            $this->_config->setValue("routes/$type/$routeName", $route);
        }

        return $this;
    }

    /**
     * Add route as get
     *
     * @param string $routeName
     * @param string|\Router\route $route
     * @return \Router\router
     */
    public function addGet($routeName,$route) {
        return $this->addRoute('GET', $routeName, $route);
    }

    /**
     * Add route as post
     *
     * @param string $routeName
     * @param string|\Router\route $route
     * @return \Router\router
     */
    public function addPost($routeName,$route) {
        return $this->addRoute('POST', $routeName, $route);
    }

    /**
     * Add route as put
     *
     * @param string $routeName
     * @param string|\Router\route $route
     * @return \Router\router
     */
    public function addPut($routeName,$route) {
        return $this->addRoute('PUT', $routeName, $route);
    }

    /**
     * Add route as delete
     *
     * @param string $routeName
     * @param string|\Router\route $route
     * @return \Router\router
     */
    public function addDelete($routeName,$route) {
        return $this->addRoute('DELETE', $routeName, $route);
    }

    /**
     * Return routes of specif type
     *
     * @param string $type
     *
     * @throws InvalidRouteTypeException
     *
     * @return array
     */
    public function getRoutes($type = null, $name = null) {
        $type = $this->_isValidRouteType($type);             
        $route = array();
        
        foreach ($type as $currentType) {
            $fetchPath = "routes/$currentType" . ($name ? "/$name" : null);        
            $route[$currentType] = $this->_config->fetch($fetchPath);
        }
        
        return $route;
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
                                        'DELETE' => array()
                                    )
                                );
        return $this;
    }

    /**
    * Try to match the route path given to any route in the routes list. (going through all the types)
    *
    * @param mixed $route
    * @param string $type
    *
    * @return string|\Router\Routes\route Return the matching route object or if there is no match return the route string
    */
    public function matchRoute($route, $type = 'all') {
        /**
         * @todo FIX ERROR! If type is all the getRoutes will return an array containg all routes...
         */
        $routes = $this->getRoutes($type);
        $this->_isRouteInList = false;
      
        /**
         * @todo continued: This is not made to work with [GET] => [routes], [POST] => [routes]...
         */
        if (!empty($routes)) {
            foreach ($routes as $routeType => $routeTypeRoutes) {
                foreach ($routeTypeRoutes as $routeName => $currentRoute) {                 
                                                      
                    if (is_object($currentRoute) && $currentRoute->match($route)) {
                        $this->_isRouteInList = true;
                        return $currentRoute;                        
                    }                    
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
    public function run($path = null, $type = 'ALL') {
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
                $type = 'ALL';
        }

        $route = $this->matchRoute($path,$type);

        if ($this->_config->fetch('only_route_entries') && !$this->_isRouteInList) {
            throw new PathNotInRouteListException($path);
        }

        //Call dispatcher
        $d = new \Router\dispatcher($route,$this->_config->getData());
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