<?php

if (function_exists('xdebug_disable'))
    xdebug_disable();


require '../../../src/Router/Dispatcher.php';
require '../../../src/Router/Routes/Route.php';
require '../../../src/Router/Routes/RouteDynamic.php';
require '../../../src/Router/Routes/RouteStatic.php';

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-03-03 at 21:49:55.
 */
class routeTest extends PHPUnit_Framework_TestCase {

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

        $module         = $r->getProperty('_module');
        $module->setAccessible(true);
        $controller     = $r->getProperty('_controller');
        $controller->setAccessible(true);
        $controllerFile = $r->getProperty('_controllerFile');
        $controllerFile->setAccessible(true);
        $action         = $r->getProperty('_action');
        $action->setAccessible(true);
        $params         = $r->getProperty('_params');
        $params->setAccessible(true);


        $values = array(
            'module'         => $module->getValue($this->_object),
            'controller'     => $controller->getValue($this->_object),
            'controllerFile' => $controllerFile->getValue($this->_object),
            'action'         => $action->getValue($this->_object),
            'params'         => $params->getValue($this->_object)
        );

        return $values;
    }

    private $_modData = array(
        'default' => array(
            'controllers' =>
            array(
                'enabled'                      => true,
                'dir'                          => '',
                'default_controller'           => 'controller', //ADD to config
                'default_action'               => 'index',
                'ext'                          => 'php',
                'match_controller_view'        => '',
                'match_controller_action_view' => ''
            )
        ),
        'other'   => array(
            'controllers' =>
            array(
                'enabled'                      => true,
                'dir'                          => '',
                'default_controller'           => 'default',
                'default_action'               => 'index',
                'ext'                          => 'php',
                'match_controller_view'        => '',
                'match_controller_action_view' => ''
            )
        ),
        'other2'  => array(
            'controllers' =>
            array(
                'enabled' => true
            )
        )
    );

    protected function setUp() {

        $routerConfig = (require('../../../config/config.php'));

        $this->_modData['default']['controllers']['dir'] = realpath(dirname(__FILE__) . '/../testAssets/modules/default/controllers/');
        $this->_modData['other']['controllers']['dir']   = realpath(dirname(__FILE__) . '/../testAssets/modules/other/controllers/');

        $config        = array_merge(
                $routerConfig, array('modules' => $this->_modData)
        );
        $this->_object = new \Router\dispatcher(null, $config);
    }

    /**
     * @expectedException \Router\NoRouteSetException
     * 
     */
    public function testRouteDispatchFail() {
        $d = new \Router\dispatcher(null, array());
        $d->map();
    }

    /**
     * @group StringRoute
     */
    public function testRouteStringMap() {
        $this->_object->map("other/controller/action");
        $this->assertDispatcherInternalValues(
                'other', 'controller', $this->_modData['other']['controllers']['dir'] . '/controller.php', 'action', array()
        );
    }

    /**
     * @group StringRoute
     */
    public function testRouteStringMapDirectCall() {
        $this->_object->mapStringRoute("other/controller/action");
        $this->assertDispatcherInternalValues('other', 'controller', $this->_modData['other']['controllers']['dir'] . '/controller.php', 'action', array());
    }

    /**
     * Module Only 
     * http://example/other 
     * @group StringRoute
     */
    public function testRouteStringModuleOnly() {
        $this->_object->map("other");
        $this->assertDispatcherInternalValues('other', 'default', $this->_modData['other']['controllers']['dir'] . '/default.php', 'index', array());
    }

    /**
     * Invalid module maps to controller name 
     * http://example/controller
     * @group StringRoute
     */
    public function testRouteStringMapToDefaultModule() {
        $this->_object->map("controller");
        $this->assertDispatcherInternalValues('default', 'controller', $this->_modData['default']['controllers']['dir'] . '/controller.php', 'index', array());
    }

    /**
     * Invalid module map to controller name with action:
     * http://example/controller
     * @group StringRoute
     */
    public function testRouteStringMapToDefaultModuleWithControllerAction() {
        $this->_object->map("controller/action");
        $this->assertDispatcherInternalValues('default', 'controller', $this->_modData['default']['controllers']['dir'] . '/controller.php', 'action', array());
    }

    /**
     * Invalid module map to controller name with action and params:
     * http://example/controller
     * @group StringRoute
     */
    public function testRouteStringMapToDefaultModuleWithControllerActionAndParams() {
        $this->_object->map("controller/action/param1/param1value/param2/param2value/param3");
        $this->assertDispatcherInternalValues('default', 'controller', $this->_modData['default']['controllers']['dir'] . '/controller.php', 'action', array(
            'param1' => 'param1value',
            'param2' => 'param2value',
            'param3' => null
        ));
    }

    /**
     * Module + controller
     * http://example/other/controller     
     * @group StringRoute
     */
    public function testRouteStringMapModuleController() {
        $this->_object->map("other/controller");
        $this->assertDispatcherInternalValues('other', 'controller', $this->_modData['other']['controllers']['dir'] . '/controller.php', 'index', array());
    }

    /**
     * Module + controller + action:
     * http://example/other/controller/action
     * @group StringRoute
     */
    public function testRouteStringMapModuleControllerAction() {
        $this->_object->map("other/controller/action");
        $this->assertDispatcherInternalValues('other', 'controller', $this->_modData['other']['controllers']['dir'] . '/controller.php', 'action', array());
    }

    /**
     * Module + controller + action + params 
     * http://example/other/controller/action/param1/param1value/param2/param2value 
     * @group StringRoute
     */
    public function testRouteStringMapModuleControllerActionParams() {
        $this->_object->map("other/controller/action/param1/param1value/param2/param2value");
        $this->assertDispatcherInternalValues('other', 'controller', $this->_modData['other']['controllers']['dir'] . '/controller.php', 'action', array(
            'param1' => 'param1value',
            'param2' => 'param2value'
        ));
    }

    /**
     * @expectedException \Router\ControllerFileNotFound
     * @group StringRoute     
     */
    public function testeRouteStringMapUnknownController() {
        $this->_object->map("UnknownController/action");
    }

    /**
     * @group RouteObject
     */
    public function testRouteObjectMapStatic() {
        $r = new \Router\Routes\routeStatic('test/teste1', array(
            'module'     => 'other',
            'controller' => 'controller'
        ));

        $this->_object->map($r);
        $this->assertDispatcherInternalValues('other', 'controller', $this->_modData['other']['controllers']['dir'] . '/controller.php', 'index', array(), false);
    }

    /**
     * @group RouteObject
     * @group params
     */
    public function testeRouteObjectMapDynamic() {
        $r = new \Router\Routes\routeDynamic('test/teste1/:username', array(
            'module'     => 'other',
            'controller' => 'controller'
        ));

        $r->match('test/teste1/antonio');

        $this->_object->map($r);
        
        $this->assertDispatcherInternalValues(
                'other', 
                'controller', 
                $this->_modData['other']['controllers']['dir'] . '/controller.php', 
                'index', 
                array(
                    'username' => 'antonio'
                )
        );

        return $this->_object;
    }

    /**
     * @depends testeRouteObjectMapDynamic
     * @group params
     */
    public function testDispatchActionWithParams($obj) {
        $obj->dispatch();
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
    private function assertDispatcherInternalValues($module, $controller, $controllerFile, $action, $params, $vardump = false) {
        $v = $this->_getVars();

        if ($vardump) {
            var_dump($v);
        }

        $this->assertEquals($v['module'], $module, 'Value for module does not match');
        $this->assertEquals($v['controller'], $controller, 'Value for controller does not match');
        $this->assertEquals($v['controllerFile'], $controllerFile, 'Value for controller path does not match');
        $this->assertEquals($v['action'], $action, 'Value for action does not match');
        $this->assertEquals($v['params'], $params, 'Value for parameters does not match');
    }

}
