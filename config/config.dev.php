<?php

/**
* Default configurations
*
* @var array
*/
$routerConfig = array();

/**
* To only allow routes that are in the route list. 
* This is useful if there are controller settings but you still want to 
* deny access to controllers that are not in the route list
* 
* @var bool
*/
$routerConfig['only_route_entries'] = false;

/**
* Container of the modules
* 
* @var array 
*/
$routerConfig['modules'] =  array();
/**
* Default module. If there is no module specified these settings will be used
* 
* @var array 
*/
$routerConfig['modules']['default'] =  array();
/**
* File system Controller settings
* @var array
*/
$routerConfig['modules']['default']['controllers'] = array();
/**
* To search for controllers if no route matched a function or class
* @var bool
*/
$routerConfig['modules']['default']['controllers']['enabled'] = true;
/**
* Path to controllers
* @var array
*/
$routerConfig['modules']['default']['controllers']['dir'] = array();
/**
* Default action to be called when there is no action specified
*@var string 
*/
$routerConfig['modules']['default']['controllers']['default_action'] = 'index';
/**
* Ext of controllers
* @var array
*/
$routerConfig['modules']['default']['controllers']['ext'] =  array('php');
/**
* Try to match a view to the controller
* @var bool
*/
$routerConfig['modules']['default']['controllers']['match_controller_view'] =  true;
/**
* Try to match a view to the controller's action
* @var bool
*/
$routerConfig['modules']['default']['controllers']['match_controller_action_view'] =  true;

/**
 * Routes of the router separated by types
 */
$routerConfig['routes'] = array();
$routerConfig['routes']['GET']['defaultRoute'] = new \Router\Routes\routeStatic('', 
        array(
            'module' => 'default',
            'controller' => 'index',
            'action' => ''
        )
); //default controller    
$routerConfig['routes']['POST'] = array();
$routerConfig['routes']['PUT'] = array();
$routerConfig['routes']['DELETE'] = array();
$routerConfig['routes']['ALL'] = array();

/**
* Valid route types
* 
* @var array
*/
$routerConfig['valid_route_types'] = array();
$routerConfig['valid_route_types']['GET'] =  1;
$routerConfig['valid_route_types']['PUT'] =  1;
$routerConfig['valid_route_types']['POST'] =  1;
$routerConfig['valid_route_types']['DELETE'] =  1;
$routerConfig['valid_route_types']['ALL'] =  1; //Special case so that a route can be valid in any case

$routerConfig['modrewrite'] = array();
$routerConfig['modrewrite']['enabled'] = true;
//if not using modrewrite where to get controller and action
$routerConfig['modrewrite']['query_string'] = array();
//All the query string parameters besides these two will be treated as parameters to the action
$routerConfig['modrewrite']['query_string']['controller'] = 'qc';
$routerConfig['modrewrite']['query_string']['action'] = 'qa';

/**
* Name/function of the view class
* @var string/function
*/
$routerConfig['view_class'] = '';