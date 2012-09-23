<?php

namespace Router;

class dispatcher {
    
    /**
     *
     * @var string
     */
    private $_module = null;
    /**
     * Class name of the controller (and also the file name)
     * @var string
     */
    private $_controller = null;
    /**
     * File path of where the controller class is located
     * @var string
     */
    private $_controllerFile = null;
    /**
     *
     * @var string
     */
    private $_action = null;
    /**
     *
     * @var array
     */
    private $_params = null;


    /**
     *
     * @var array
     */
    private $_modulesData = array();
    
    /**
     *
     * @var string|\Router\Routes\route
     */
    private $_route = null;
    
    /**
     *
     * @var string
     */
    private $_defaultModule = null;
        
    /**
     * 
     * @param string|\Router\Routes\route $route
     * @param array $modData All modules data
     */
    public function __construct($route, array $modData, $defaultModule = 'default') {            
        $this->setRoute($route);
        $this->setModulesData($modData);
        $this->_defaultModule = $defaultModule;
    }
    
    /**
     * 
     * @param string|\Router\Routes\route $route
     * @return \Router\dispatcher
     */
    public function setRoute($route) {
        $this->_route = $route;
        return $this;
    }
    
    /**
     * 
     * @param array $modData
     * @return \Router\dispatcher
     */
    public function setModulesData(array $modData) {
        $this->_modulesData = $modData;
        return $this;
    }

    /**
     * 
     * @param string $route
     * 
     * @throws NoRouteSetException
     * @throws InvalidClassPathException
     */
    public function dispatch($route = null) {                
        
        if (!$route) {
            if ($this->_route === null) {
                throw new NoRouteSetException;
            }
            
            $route = $this->_route;
        }
            
        $this->_module = null;
        $this->_controller = null;
        $this->_controllerFile = null;
        $this->_action = null;
        $this->_params = array();
                
        if ($route instanceof \Router\Routes\route) {
            $options = $route->getOptions();
            
            //The route is a function
            if (isset($options['func']) && is_callable($options['func'])) {
                //$this->_module = 'customFunction';                
                $this->_action = $route['func'];
            }            
            
            //The route is a class
            elseif (isset($options['class']) && is_array($options['class']) && count($options['class']) > 1) {
                $className = $options['class'][0];
                $classFile = isset($options['class'][1]) ? $options['class'][1] : null;
                
                if ($classFile) {                    
                    if (!is_file($classFile)) {
                        throw new InvalidClassPathException;
                    }
                    
                    $this->_controllerFile = $classFile;
                }
                //If there is no class file the class must already be loaded
                else {
                    if (!class_exists($className)) {
                        throw new InvalidClassException;
                    }
                }
                    
                $this->_controller = $className;
                $this->_action = $options['action'] ?: $this->_modulesData[$this->_defaultModule]['controllers']['default_action'];
            }
            //Normal route            
            else {                             
                $this->_module = $options['module'] ?: $this->_defaultModule;                                    
                $this->_controller = $options['controller'] ?: $this->_modulesData[$this->_defaultModule]['controllers']['default_controller'];
                                
                $this->_controllerFile = 
                        $this->findControllerPath(
                                $this->_controller, 
                                (array)$this->_modulesData[$this->_module]['controllers']['dir'], 
                                (array)$this->_modulesData[$this->_module]['controllers']['ext']
                        );
                $this->_action = $options['action'] ?: $this->_modulesData[$this->_defaultModule]['controllers']['default_action'];
            }            
            
            $this->_params = $options['params'] ?: array();
        }         
        //The route is a string and we have to figure out module, controller, action and params
        else {            
            $segments = explode('/',$route);
            clearstatcache();
            
            while (count($segments)) {
                foreach ($segments as $k => $segment) {
                    switch (null) {
                        case $this->_module:
                            if (isset($this->_modulesData[$segment])) {
                                $this->_module = $segment;
                                array_shift($segments);                                
                            }                         
                            
                            break 2;
                        break;
                        case $this->_controller:
                            if ($this->_module) {
                                $dirs = (array)$this->_modulesData[$this->_module]['controllers']['dir'];
                                $exts = (array)$this->_modulesData[$this->_module]['controllers']['ext'];
                                
                                $controllerPath = $this->findControllerPath($segment, $dirs, $exts);
                                
                                if ($controllerPath) {
                                    $this->_controller = $segment;
                                    $this->_controllerFile = $controllerPath;
                                    $segments = array_slice($segments,$k+1);
                                    break 2;
                                }                                      
                            }                            
                        break;
                        case $this->_action:
                            if ($this->_module && $this->_controller) {
                                $this->_action = $segment;
                                array_shift($segments);
                                
                                $this->_params = $segments;
                                break 2;
                            }
                        break;
                    }
                }
                
                
                
                if ($this->_module && !$this->_controller) {                    
                    $this->_controller = $this->_modulesData[$this->_module]['controllers']['default_controller'];
                }
                             
                if (!$this->_module) {
                    $this->_module = $this->_defaultModule;
                }

                if ($this->_module && $this->_controller && $this->_action){                    
                    if (!empty($this->_params)) {
                        $params = array();
                        $pkey = null;
                        
                        foreach ($this->_params as $k => $p) {                            
                            if (!($k & 1)) {
                                $pkey = $p;                                
                            }
                            else {
                                $params[$pkey] = $p;
                            }                            
                        }
                        $this->_params = $params;
                        
                        //Catch any missing parameters
                        if (!isset($this->_params[$pkey])) {
                            $this->_params[$pkey] = null;
                        }
                    }
                    break;
                }
            }
        }               
    }
    
    /**
     * Try and find the controller in the selected modules dir
     * 
     * @param string $controllerPath File name (without extension)
     * @param array $dirs Directories to search in
     * @param array $exts Possible extensions of file
     */
    private function findControllerPath($controller, array $dirs, array $exts) {

        $controllerPath = null;
        
        foreach ($dirs as $dir) {
            foreach ($exts as $ext) {
                $controllerPath = $dir . $controller . '.' . $ext;

                if (is_file($controllerPath)) {
                    break 2;
                }
                
                $controllerPath = null;
            }
        }
        
        return $controllerPath;
    }
}


class NoRouteSetException extends \Exception {}
class InvalidClassPathException extends \Exception {}
class InvalidClassException extends \Exception {}