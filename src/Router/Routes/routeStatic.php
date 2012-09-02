<?php


namespace Router\Routes;

/**
 * Description of static
 *
 * @author AntonioCS
 */
class routeStatic extends route {    
    
     /**
     * 
     * @param string $route
     * @return bool
     */
    public function match($route) {
        return ($route == $this->_routePattern);
    }
}