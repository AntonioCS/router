<?php

namespace Router\Routes;

class RouteDynamic extends Route {
    
    /**
     * This is what will replace <:place holder>
     * So when a route comes like so:
     *  path/<:place holder>/folder/<:place holder>
     * 
     * @var string
     */
    private $_placeHolderRegex = array(
                                        "^(\w+|string)" => '(\S+)',
                                        "^number(s)?" => '(\d+)'
                            );
    
    /**
     * List of place holders
     * This will be an array in the format
     * 
     * array(<placeholder> => <position in route string>)
     *      
     * @var array
     */
    private $_placeHoldersPosition = array();
    
    /**
     *
     * @var string
     */
    private $_routePatternOriginal = null;    
    
    
    private function areTherePlaceHolders() {
        return count($this->_placeHoldersPosition);
    }
    
    private function setPlaceHolderPosition($placeHolder,$position) {
        $this->_placeHoldersPosition[$placeHolder] = $position;
        return $this;
    }
    
    private function getAllPlaceHolders() {
        return array_keys($this->_placeHoldersPosition);
    }
    
    private function getPlaceHolderPosition($placeHolder) {
        if (isset($this->_placeHoldersPosition[$placeHolder])) {
            return $this->_placeHoldersPosition[$placeHolder];
        }
        
        return false;
    }
    /**
     *
     * @param string $route 
     */
    public function setRoute($route) {
        $this->_routePatternOriginal = $route;        
        
        $segments = explode('/',$route);
        $parsedRoutePattern = array();        
                
               
        foreach ($segments as $place => $segment) {                        
            $parsedRoutePattern[] = ($segment[0] === ':') ? $this->parseForPlaceHolders(substr($segment, 1), $place) : $segment;
        }
        
        $this->setRoutePattern(implode('/',$parsedRoutePattern));
    }
    
    /**
     * 
     * @param string $segment
     * @param int $position
     * @return string
     * @throws \Exception
     */
    private function parseForPlaceHolders($placeHolder, $position) {                
        $regexSegment = null;

        foreach ($this->_placeHolderRegex as $internalRegexToMatchPlaceHolder => $regexForPlaceHolder) {
            if (preg_match("/$internalRegexToMatchPlaceHolder/", $placeHolder)) {
                $regexSegment = $regexForPlaceHolder;
            }
        }

        if (!$regexSegment) {
            throw new \Exception('Place holder miss match');
        }

        $this->setPlaceHolderPosition($placeHolder, $position); 

        return $regexSegment;        
    }
    
    /**
     * 
     * @param string $route
     */
    public function match($route) {        
        
        if (parent::match($route)) {
            if ($this->areTherePlaceHolders()) {
                $matchedRouteInFull = explode('/',$this->_routeMatch[0]);
                $allPlaceHolders = $this->getAllPlaceHolders();
                
                foreach ($allPlaceHolders as $placeHolder) {
                    $placeHolderLocation = $this->getPlaceHolderPosition($placeHolder);                    
                    $this->_options['params'][$placeHolder] = (isset($matchedRouteInFull[$placeHolderLocation]) ? $matchedRouteInFull[$placeHolderLocation] : null);
                }
            }   
            
            return true;
        }
        
        return false;
    }
}
