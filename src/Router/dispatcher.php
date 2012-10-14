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
    public function map($route = null) {                
        
        if (!$route) {
            if ($this->_route === null) {
                throw new NoRouteSetException;
            }
            
            $route = $this->_route;
        }
            
        $this->setToNullProperties();
                
        if ($route instanceof \Router\Routes\route) {
            $options = $route->getOptions();
            
            //The route is a function
            if (isset($options['func']) && is_callable($options['func'])) {
                $this->_action = $route['func'];
            }            
            
            //The route is a class
            elseif (isset($options['class']) && is_array($options['class']) && count($options['class'])) {
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
                $this->_controller = $options['controller'] ?: $this->_modulesData[$this->_module]['controllers']['default_controller'];
                                
                $this->_controllerFile = 
                        $this->findControllerPath(
                                $this->_controller, 
                                (array)$this->_modulesData[$this->_module]['controllers']['dir'], 
                                (array)$this->_modulesData[$this->_module]['controllers']['ext']
                        );
                $this->_action = $options['action'] ?: $this->_modulesData[$this->_module]['controllers']['default_action'];
            }            
            
            $this->_params = $options['params'] ?: array();
        }                 
        else {     
            $this->mapStringRoute($route);                        
        }               
        
        return $this;
    }
    
    /**
     * The route is a string and we have to figure out module, controller, action and params
     * 
     * @param string $route
     * @throws ControllerFileNotFound
     */
    public function mapStringRoute($route) {   
        $this->setToNullProperties();
        
        $segments = explode('/',$route);
        $pkey = null;
        
        while (true) {               
            foreach ($segments as $segment) {
                switch (true) {
                    case ($this->_module == null):
                        if (isset($this->_modulesData[$segment])) {
                            $this->_module = $segment;                        
                        }
                        else {
                            $this->_module = $this->_defaultModule;                            
                            continue 3; //restart the loop
                        }
                    break;
                    case ($this->_controller == null && $this->_module):                        
                        $dirs = (array)$this->_modulesData[$this->_module]['controllers']['dir'];
                        $exts = (array)$this->_modulesData[$this->_module]['controllers']['ext'];

                        $controllerPath = $this->findControllerPath($segment, $dirs, $exts);

                        if ($controllerPath) {
                            $this->_controller = $segment;
                            $this->_controllerFile = $controllerPath;                            
                        }    
                        else {
                            throw new ControllerFileNotFound;
                        }
                    break;
                    case ($this->_action == null):
                        $this->_action = $segment;
                    break;
                    case ($this->_module && $this->_controller && $this->_action):                        
                        if ($pkey == null) {
                            $pkey = $segment;
                        }
                        else {
                            $this->_params[$pkey] = $segment;
                            $pkey = null;
                        }                   
                    break;
                }
            }
            
            break;
        }
        
        if ($pkey) {              
            $this->_params[$pkey] = null;              
        }
        
        if ($this->_module && !$this->_controller) {                    
            $this->_controller = $this->_modulesData[$this->_module]['controllers']['default_controller'];
            $this->_controllerFile = $this->findControllerPath($this->_controller, (array)$this->_modulesData[$this->_module]['controllers']['dir'], (array)$this->_modulesData[$this->_module]['controllers']['ext']);
            $this->_action = $this->_modulesData[$this->_module]['controllers']['default_action'];
        }      
        
        if ($this->_module && $this->_controller && !$this->_action) {
            $this->_action = $this->_modulesData[$this->_module]['controllers']['default_action'];            
        }
        
        if ($this->_controllerFile == null) {
            throw new ControllerFileNotFound;
        }
    }
    
    /**
     * 
     * @throws ControllerClassNotFound
     */
    public function dispatch() {

        $class = null;
        $object = null;        
        $file = null;
                 
        if ($this->_module != null && $this->_controller != null) {            
            if (!class_exists($this->_controller,false)) {
                $file = $this->_controllerFile;                     
            }
            $class = $this->_controller;                
        }
        
        if ($file) {            
            require($file);
        }
        
        if ($class && class_exists($class,false)) {                  
            $object = new $class;
        }
        else {
            throw new ControllerClassNotFound;
        }
                            
        $this->callMethod($object, $this->_action, $this->_params);
    }
    
    /**
     * Method to call function or class method
     * 
     * @param object|null $object
     * @param string|function $method
     * @param array|null $params
     */
    private function callMethod($object, $method, $params) {        
        /**
         * @todo Implement parameter passing
         */
        $pStruct = $this->generateParameterStructure($object, $method);
        
        /**
         * @todo Implement Event Before method calling
         */
        if ($object == null) { //function calling
            $method();
        }                
        else {
            /**             
             * @todo Implement method suffix (set in config)
             */
            $object->$method();
        }
        
        /**
         * @todo Implement Event After method calling
         */
    }
    
    /**
     * 
     * @param object|null $object
     * @param function|string $method
     * @return array Structure of parameters
     */
    private function generateParameterStructure($object,$method) {
        $refObj = null;
        $paramsList = array();
        $paramsListStructure = array();
        
        if ($object == null) {
            $refObj = new \ReflectionFunction($method);
            $paramsList = $refObj->getParameters();
        }
        else {
            if (!method_exists($object, $method)) {
                throw new ActionMethodNotFound(get_class($object) . ' - ' . $method);
            }
            
            $refObj = new \ReflectionObject($object);
            $paramsList = $refObj->getMethod($method)->getParameters();
        }
        
        //no parameters
        if (count($paramsList) == 0) {
            return null;
        }
                
        foreach ($paramsList as $pos => $v) {                        
            $paramsListStructure[$v->name] = array(
                'position' => $pos,
                'optional' => $v->isOptional(),
                'defaultValue' => $v->isDefaultValueAvailable() ? $v->getDefaultValue() : null
            );
        }
        
        return $paramsListStructure;
    }
    
    /**
     * Clear all properties
     */
    private function setToNullProperties() {
        $this->_module = null;
        $this->_controller = null;
        $this->_controllerFile = null;
        $this->_action = null;
        $this->_params = array();
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
class ControllerFileNotFound extends \Exception {}
class ControllerClassNotFound extends \Exception {}
class ActionMethodNotFound extends \Exception {}