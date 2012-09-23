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

        
    /**
     *
     * @var \Router\dispatcher
     */
    private $_object = null;
    
    /**
     * Return the module, controller, controllerClass, action and params from the dispatcher class
     * 
     * @return array
     */
    private function _getVars() {
        $r = new ReflectionObject($this->_object);
        
        $module = $r->getProperty('_module');
        $module->setAccessible(true);        
        $controller = $r->getProperty('_controller');
        $controller->setAccessible(true);
        $controllerFile = $r->getProperty('_controllerFile');
        $controllerFile->setAccessible(true);
        $action = $r->getProperty('_action');
        $action->setAccessible(true);
        $params = $r->getProperty('_params');
        $params->setAccessible(true);
            
               
        $values = array(
            'module' => $module->getValue($this->_object),
            'controller' => $controller->getValue($this->_object),
            'controllerFile' => $controllerFile->getValue($this->_object),
            'action' => $action->getValue($this->_object),
            'params' => $params->getValue($this->_object)
        );        
        
        return $values;        
    }

    private $_modData = array(
        'default' => array(
            'controllers' =>
                array(
                    'enabled' => true,
                    'dir' => '',
                    'default_controller' => '', //ADD to config
                    'default_action' => 'index',
                    'ext' => 'php',
                    'match_controller_view' => '',
                    'match_controller_action_view' => ''
                )
            ),
        'other' => array(
            'controllers' =>
                array(
                    'enabled' => true,
                    'dir' => '',
                    'default_controller' => '',
                    'default_action' => 'index',
                    'ext' => 'php',
                    'match_controller_view' => '',
                    'match_controller_action_view' => ''
                )
            ),
        'other2' => array(
            'controllers' =>
                array(
                    'enabled' => true
                )
            )
    );
            
    protected function setUp() { 
        $this->_modData['default']['controllers']['dir'] = dirname(__FILE__) . '/testAssets/modules/default/controllers/';
        $this->_modData['other']['controllers']['dir'] = dirname(__FILE__) . '/testAssets/modules/other/controllers/';        
        
        $this->_object = new \Router\dispatcher("",$this->_modData);
    } 
    
    /**
    * @expectedException \Router\NoRouteSetException
    */
    public function testRouteDispatchFail() {        
        $d = new \Router\dispatcher(null,array());
        $d->dispatch();
    }
    
    
    public function _testRouteStringDispatch() {        
        $this->_object->dispatch("other/controller/action/param1/param1value/param2/param2value");             
        $this->assertDispatcherInternalValues('default','controller.php','controller','action',array());        
    }
    
    public function testRouteObjectDispatchStatic() {
        $r = new \Router\Routes\routeStatic('test/teste1', array(
            'module' => 'other',
            'controller' => 'controller'            
        ));
                
        $this->_object->dispatch($r);
        
        $this->assertDispatcherInternalValues('other','controller','controller.php','index',array(),true);        
        
        //$v = $this->_getVars();        
        //var_dump($v);
    }
    
    public function _testeRouteObjectDispatchDynamic() {
        $r = new \Router\Routes\routeDynamic('test/teste1/:username', array(
            'module' => 'other',
            'controller' => 'controller'            
        ));
        
        $r->match('test/teste1/antonio');
        
        //var_dump('options -> ' , $r->getOptions());
        $this->_object->dispatch($r);
        $v = $this->_getVars();        
        var_dump($v);
        
        
    }
    
    /**
     * 
     * @param string $module
     * @param string $controller
     * @param string $controllerFile - Without the path just the file name
     * @param string $action
     * @param string $params
     * @param bool $vardump
     */
    private function assertDispatcherInternalValues($module,$controller,$controllerFile,$action,$params, $vardump = false) {
        $v = $this->_getVars();
        
        if ($vardump) {
            var_dump($v);
        }
        
        $returnedControllerValue = $v['controllerFile'] ? basename($v['controllerFile']) : null;
        
        $this->assertEquals($v['module'],$module);
        $this->assertEquals($v['controller'],$controller);
        $this->assertEquals($returnedControllerValue,$controllerFile);
        $this->assertEquals($v['action'],$action);
        $this->assertEquals($v['params'],$params);
    }
}
