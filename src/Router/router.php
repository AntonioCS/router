<?php

namespace Router;

class Router {
    
    const routeTypes = 'GET|POST|DELETE|PUT';    

    /**
     * Where all the configurations will go
     *
     * @var array
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
     * Class construct
     *
     * @param array $config
     */
    public function __construct($config = null) {
        if ($config) {
            $this->_config = $config;
        }
    }

    /**
     * 
     * @param string $rtype
     * @return string|null
     */
    public function isValidRouteType($rtype) {
        $types = explode('|',self::routeTypes);
        $rtype = strtoupper($rtype);
        
        if (in_array($rtype, $types)) {
            return $rtype;
        }
        
        return null;
    }
    
    /**
    * Add new route to route list
    *
    * @param string|array $type GET/PUT/DELETE/POST/ALL - REQUEST_METHOD
    * @param string|array $routeName - Name for the route
    * @param Routes\Route|array $route
    *
    * @todo Correct ALL problem. The all should not have a place in the routes array. Routes with all type should be put in all route types
    * @return \Router\router
    */
    //public function addRoute($type,$routeName,Routes\Route $route) {
    public function addRoute($type,$routeName,$route) {
        
        //Routes\Route
        /*
        foreach ($types as $type) {                 
            $this->_config->setValue("routes/$type/$routeName", $route);
        }
        */
        $type = strtoupper($type);
        $this->_config['routes'][$type][$routeName] = $route;
        
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
        $type = $this->isValidRouteType($type);
        $routeTypes = explode('|',self::routeTypes);
        $route = array();
        
        foreach ($routeTypes as $currentType) {
            if (($type && $currentType != $type) || empty($this->_config['routes'][$currentType])) {
                continue;
            }            
            
            if ($name) {
                if (isset($this->_config['routes'][$currentType][$name])) {
                    $route[$currentType] = $this->_config['routes'][$currentType][$name];
                }
                else {
                    continue;
                }
            }
            else {
                $route[$currentType] = $this->_config['routes'][$type];
            }
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
    public function matchRoute($route, $type = null) {
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
        else {            
            throw new NoRoutesException();
        }

        return false;
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
                $path = $_SERVER['QUERY_STRING'];
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
        }

        $route = $this->matchRoute($path,$type);

        if ($this->onlyRoutesInList()) {
            throw new PathNotInRouteListException($path);
        }

        //Call dispatcher
        $d = $this->getDispatcher();
        
        if (!$d) {
            throw new NoDispatcherSetException();
        }
        
        $d->setRoute($route);                
        
        $d->dispatch();
    }
    
    
    private function onlyRoutesInList() {
        /*
        if ($this->_config->fetch('only_route_entries') && !$this->_isRouteInList) {
            throw new PathNotInRouteListException($path);
        }
        */
        return false;
    }
    
    public function setDispatcher($dispatcher) {
        $this->_dispatcher = $dispatcher;        
        $this->_dispatcher->setConfig($this->_config);
        
        return $this;
    }
    
    public function getDispatcher() {
        return $this->_dispatcher;
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
class NoDispatcherSetException extends \Exception {}