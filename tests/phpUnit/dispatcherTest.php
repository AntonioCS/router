<?php

if (function_exists('xdebug_disable'))
    xdebug_disable();

require '../../src/Router/dispatcher.php';
require '../../src/Router/Routes/route.php';
require '../../src/Router/Routes/routeDynamic.php';
require '../../src/Router/Routes/routeStatic.php';


/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-03-03 at 21:49:55.
 */
class routeTest extends PHPUnit_Framework_TestCase
{

    protected function setUp() {              
    } 
    
    /**
    * @expectedException \Router\NoRouteSetException
    */
    public function testRouteDispatch() {        
        $d = new \Router\dispatcher("",array());
        $d->dispatch();
    }
}