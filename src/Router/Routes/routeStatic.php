<?php


namespace Router\Routes;

/**
 * Description of static
 *
 * @author AntonioCS
 */
class RouteStatic extends Route {    
    
     /**
     * 
     * @param string $route
     * @return bool
     */
    public function match($route) {
        return ($route == $this->_routePattern);
    }
}