<?php

namespace Router;

class Dispatcher {

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
     * Config data property. Given in the constructor by the router
     * @var array
     */
    private $_config = array();

    /**
     *
     * @var string|\Router\Routes\route
     */
    private $_route = null;

    /**
     * 
     * @param string|\Router\Routes\route $route
     * @param array $config Configuration data
     */
    public function __construct($route = null, $configData = array()) {

        $this->setRoute($route);        
        $this->setConfig($configData);
        
        if ($route && $configData) {        
            $this->map();
        }
    }

    /**
     * 
     * @param array $config
     * @return \Router\dispatcher
     */
    public function setConfig($config) {
        $this->_config = $config;
        return $this;
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
     * Map the route to module, controller, action and params
     * 
     * @param string|route object $route
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
            $this->mapObjRoute($route);
        }
        else {
            $this->mapStringRoute($route);
        }

        return $this;
    }

    /**
     * 
     * @param type $route
     * @throws InvalidClassPathException
     * @throws InvalidClassException
     */
    public function mapObjRoute(\Router\Routes\route $route) {
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
            $this->_action     = $options['action'] ? : $this->_config['modules'][$this->_defaultModule]['controllers']['default_action'];
        }
        //Normal route object           
        else {
            $this->_module     = $options['module'] ? : $this->_defaultModule;
            $this->_controller = $options['controller'] ? : $this->_config['modules'][$this->_module]['controllers']['default_controller'];

            $this->_controllerFile =
                    $this->findControllerPath(
                    $this->_controller, (array) $this->_config['modules'][$this->_module]['controllers']['dir'], (array) $this->_config['modules'][$this->_module]['controllers']['ext']
            );
            $this->_action         = $options['action'] ? : $this->_config['modules'][$this->_module]['controllers']['default_action'];
        }

        $this->_params = $options['params'] ? : array();
    }

    /**
     * The route is a string and we have to figure out module, controller, action and params
     * 
     * @param string $route
     * @throws ControllerFileNotFound
     */
    public function mapStringRoute($route) {
        $this->setToNullProperties();

        //got tired of seeing such big a big config variable
        $c        = $this->_config;
        $segments = explode('/', $route);
        $pkey     = null;

        while (true) {
            foreach ($segments as $segment) {
                switch (true) {
                    case ($this->_module === null):
                        if (isset($c['modules'][$segment])) {
                            $this->_module = $segment;
                        }
                        else {
                            $this->_module = isset($c['modules_default_module']) ? $c['modules_default_module'] : 'default';
                            continue 3; //restart the loop
                        }
                        break;
                    case ($this->_controller == null && $this->_module):                        
                        $dirs = (array) $c['modules'][$this->_module]['controllers']['dir'];
                        $exts = (array) $c['modules'][$this->_module]['controllers']['ext'];

                        if ($segment == null) {
                            $segment = $c['modules'][$this->_module]['controllers']['default_controller'] ? : 'default';
                        }

                        $controllerPath = $this->findControllerPath($segment, $dirs, $exts);

                        if ($controllerPath) {
                            $this->_controller     = $segment;
                            $this->_controllerFile = $controllerPath;
                        }
                        else {
                            throw new ControllerFileNotFound($this->_module . ' - ' . $segment . ' -- ' . implode('|', $dirs) . ' -- ' . implode('|', $exts));
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
                            $pkey                 = null;
                        }
                        break;
                }
            }

            break;
        }

        if ($pkey) {
            $this->_params[$pkey] = null;
        }

        if ($this->_module && $this->_controller === null) {
            $this->_controller     = $c['modules'][$this->_module]['controllers']['default_controller'];
            $this->_controllerFile = $this->findControllerPath($this->_controller, (array) $c['modules'][$this->_module]['controllers']['dir'], (array) $c['modules'][$this->_module]['controllers']['ext']);
            $this->_action         = $c['modules'][$this->_module]['controllers']['default_action'];
        }

        if ($this->_module && $this->_controller && !$this->_action) {
            $this->_action = $c['modules'][$this->_module]['controllers']['default_action'];
        }

        if ($this->_controllerFile == null) {
            throw new ControllerFileNotFound($this->_controller);
        }
    }

    /**
     * Dispatch the route, ie, load the file (if necessary), instantiate the class and call the method or run just run the function
     * 
     * @throws ControllerClassNotFound
     */
    public function dispatch() {

        $class  = null;
        $object = null;
        $file   = null;

        if ($this->_module && $this->_controller) {
            if (class_exists($this->_controller, false) === false) {
                $file = $this->_controllerFile;
            }
            $class = $this->_controller;
        }

        if ($file) {
            require($file);
        }

        if ($class && class_exists($class, false)) {
            $object = new $class;
        }
        else {
            throw new ControllerClassNotFound($class);
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
         * @todo Implement method suffix (set in config)
         */
        $pStruct = $this->generateParameterStructure($object, $method);

        if ($pStruct != null) {
            if (count($params) < $pStruct['__notOptionalParams']) {
                throw new NotEnoughParametersForMethod($method . ' - ' . count($params) . ' - ' . $pStruct['__notOptionalParams']);
            }

            $params = $this->reOrderParams($pStruct, $params);
        }

        /**
         * @todo Implement Event Before method calling
         */
        if ($object == null) { //function calling
            if (!empty($params)) {
                $this->callFunctionWithParams($method, $params);
            }
            else {
                $method();
            }
        }
        else {
            if (!empty($params)) {
                $this->callObjectActionWithParams($object, $method, $params);
            }
            else {
                $object->$method();
            }
        }

        /**
         * @todo Implement Event After method calling
         */
    }

    /**
     * Facilitate the calling of methods with parameters
     *
     * 
     * @param mixed $object
     * @param string $method
     * @param array $params
     */
    private function callObjectActionWithParams($object, $method, $params) {

        switch (count($params)) { //call_user_func_array is slow!! Try my best to avoid it
            case 1:
                $object->$method($params[0]);
                break;
            case 2:
                $object->$method($params[0], $params[1]);
                break;
            case 3:
                $object->$method($params[0], $params[1], $params[2]);
                break;
            case 4:
                $object->$method($params[0], $params[1], $params[2], $params[3]);
                break;
            case 5:
                $object->$method($params[0], $params[1], $params[2], $params[3], $params[4]);
                break;
            default:
                call_user_func_array(array($object, $method), $params);
        }
    }

    /**
     * 
     * @param clousere $func
     * @param array $params
     */
    private function callFunctionWithParams($func, $params) {

        switch (count($params)) { //call_user_func_array is slow!! Try my best to avoid it
            case 1:
                $func($params[0]);
                break;
            case 2:
                $func($params[0], $params[1]);
                break;
            case 3:
                $func($params[0], $params[1], $params[2]);
                break;
            case 4:
                $func($params[0], $params[1], $params[2], $params[3]);
                break;
            case 5:
                $func($params[0], $params[1], $params[2], $params[3], $params[4]);
                break;
            default:
                call_user_func_array($func, $params);
        }
    }

    /**
     * 
     * @param object|null $object
     * @param function|string $method
     * @return array Structure of parameters
     */
    private function generateParameterStructure($object, $method) {
        $refObj              = null;
        $paramsList          = array();
        $paramsListStructure = array();

        if ($object == null) {
            $refObj     = new \ReflectionFunction($method);
            $paramsList = $refObj->getParameters();
        }
        else {
            if (!method_exists($object, $method)) {
                throw new ActionMethodNotFound(get_class($object) . ' - ' . $method);
            }

            $refObj     = new \ReflectionObject($object);
            $paramsList = $refObj->getMethod($method)->getParameters();
        }

        //no parameters
        if (count($paramsList) == 0) {
            return null;
        }

        $notOptionalParams = 0;

        foreach ($paramsList as $pos => $v) {
            $paramsListStructure[$v->name] = array(
                'position'     => $pos,
                'optional'     => $v->isOptional(),
                'defaultValue' => $v->isDefaultValueAvailable() ? $v->getDefaultValue() : null
            );

            $paramsListStructure['__positions'][] = $v->name;

            if (!$v->isOptional()) {
                $notOptionalParams++;
            }
        }

        $paramsListStructure['__notOptionalParams'] = $notOptionalParams;

        return $paramsListStructure;
    }

    /**
     * This will return the params in the correct order
     * 
     * @param array $pStruct
     * @param array $params
     */
    private function reOrderParams($pStruct, $params) {
        $order = array();

        foreach ($pStruct['__positions'] as $item) {
            $order[] = (isset($params[$item])) ? $params[$item] : $pStruct[$item]['defaultValue'];
        }

        return $order;
    }

    /**
     * Clear all properties
     */
    private function setToNullProperties() {
        $this->_module         = null;
        $this->_controller     = null;
        $this->_controllerFile = null;
        $this->_action         = null;
        $this->_params         = array();
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
            $dir = $this->setDirectoryPathToEndsWithSlash($dir);
            
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
    
    private function setDirectoryPathToEndsWithSlash($path) {
       if ($path[strlen($path) -1] != DIRECTORY_SEPARATOR) {
           $path .= DIRECTORY_SEPARATOR;
       }
       
       return $path;
    }

}

class NoRouteSetException extends \Exception {
    
}

class InvalidClassPathException extends \Exception {
    
}

class InvalidClassException extends \Exception {
    
}

class ControllerFileNotFound extends \Exception {
    
}

class ControllerClassNotFound extends \Exception {
    
}

class ActionMethodNotFound extends \Exception {
    
}

class NotEnoughParametersForMethod extends \Exception {
    
}