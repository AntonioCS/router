<?php

namespace Router\Routes;

class routeDynamic extends route {
    
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
    private $_placeHolders = array();
    
    /**
     *
     * @var string
     */
    private $_routePatternOriginal = null;    
    
    /**
     *
     * @param string $route 
     */
    public function setRoute($route) {
        $this->_routePatternOriginal = $route;        
        
        $rawPattern = explode('/',$route);
        $parsedPattern = array();
        $placeHolder = null;
                
               
        foreach ($rawPattern as $place => $section) {
            
            //Found place holder
            if ($section[0] === ':') {        
                $placeHolder = substr($section, 1);
                $regex = null;
                
                foreach ($this->_placeHolderRegex as $kmatch => $mregex) {
                    if (preg_match("/$kmatch/", $placeHolder)) {
                        $regex = $mregex;
                    }
                }
                
                if (!$regex) {
                    throw new \Exception('Place holder miss match');
                }
                              
                $this->_placeHolders[$placeHolder] = $place;
                
                $section = $regex;
            }
            
            $parsedPattern[] = $section;
        }
        
        $this->_routePattern = implode('/',$parsedPattern);        
    }
    
    /**
     * 
     * @param string $route
     */
    public function match($route) {        
        
        if (parent::match($route)) {
            if (!empty($this->_placeHolders)) {
                $pieces = explode('/',array_shift($this->_routeMatch));
                
                foreach ($this->_placeHolders as $p => $location) {
                    $this->_options['params'][$p] = $pieces[$location];
                }
            }   
            
            return true;
        }
        
        return false;
    }
}
