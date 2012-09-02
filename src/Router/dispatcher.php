<?php

namespace Router;

class dispatcher {
               
    private $_modulesData = array();
    
    private $_route = null;
    
    
    /**
     * 
     * @param string|\Router\Routes\route $route
     * @param array $modData
     */
    public function __construct($route, array $modData) {            
        $this->_route = $route;
        $this->_modulesData = $modData;                
    }
    
    
    public function dispatch($route = null) {
        
        if (!$route) {
            if (!$this->_route) {
                throw new NoRouteSetException;
            }
            
            $route = $this->_route;
        }
            
        $module = null;
        $controller = null;
        $action = null;
        $params = null;
                
        if (is_array($route)) {
            if (isset($route['func']) && is_callable($route['func'])) {                
                $action = $route['func'];
            }            
            
            if (isset($route['class']) && is_array($route['class']) && count($route['class'] > 1)) {
                if (isset($route['class'][2])) {
                    $fileClassPath = $route['class'][2];
                    if (!is_file($fileClassPath)) {
                        throw new InvalidClassPathException;
                    }
                    
                    $this->_modulesData['customClass'] = $fileClassPath;                    
                }
                    
                $module = 'customClass';
                $controller = $route['class'][0];
                $action = $route['class'][1];                
            }
            
            //else get data
            
        } 
        else {
            $segments = explode('/',$route);
            
            while (true) {
                foreach ($segments as $k => $segment) {
                    switch (null) {
                        case $module:
                            if (isset($this->_modulesData[$segment])) {
                                $module = $segment;
                                array_shift($segment);                                
                            }                         
                            
                            break 2;
                        break;
                        case $controller:
                            if ($module) {
                                $dirs = $this->_modulesData[$module]['controllers']['dir'];
                                $exts = $this->_modulesData[$module]['controllers']['ext'];
                                
                                foreach ($dirs as $dir) {
                                    foreach ($exts as $ext) {
                                        $controller = $dir . '/' .$segment . '.' . $ext;
                                        
                                        if (!is_file($controller)) {
                                            $controller = null;                                            
                                        }
                                        else {
                                            $segments = array_slice($segments,$k+1);
                                            break 2;
                                        } 
                                    }
                                }                                
                            }                            
                        break;
                        case $action:
                            if ($module && $controller) {
                                $action = $segment;
                                array_shift($segments);
                                
                                $params = $segments;
                                break 2;
                            }
                        break;
                    }
                }
                
                if (!$module)
                    $module = 'default';
                
                if ($module && $controller && $action)
                    break;
            }
        }
    }
}


class NoRouteSetException extends \Exception {}
class InvalidClassPathException extends \Exception {}