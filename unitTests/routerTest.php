<?php

if (function_exists('xdebug_disable'))
    xdebug_disable();

require '../src/Router/router.php';

/**
 * Generated by PHPUnit_SkeletonGenerator on 2012-03-03 at 21:49:55.
 */
class routerTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var Router\router
     */
    protected $object;
    
    
    /**
     * Using reflection to return the _config property of the router
     * http://www.php.net/manual/en/class.reflectionproperty.php#99661
     * 
     * @return ReflectionProperty  
     */
    private function getRouteObjectConfig() {
        $r = new ReflectionObject($this->object);
        $prop = $r->getProperty('_config');
        $prop->setAccessible(true);
        return $prop;
    }
    
    /**
     * 
     * @return array The configuration array of the route object (using reflection to make it available)
     */
    private function getRouteObjectConfigData() {      
        return $this->getRouteObjectConfig()->getValue($this->object);            
    }
    
    /**
     * Change the private property _config
     * 
     * @param array $data 
     */
    private function setRouteObjectConfigData($data) {
        $this->getRouteObjectConfig()->setValue($this->object, $data);
    }


    private function addTheseRoutes() {
        $routes = array(
            'get' => array(
                'teste' => 'teste__1',
                '/index\.php\?(\d+)/' => 'index/$1',
                '/^(.+)$/'  => 'index/$1',
                '' => 'index'
             ),
            'put' => array(),
            'delete' => array(),
            'post' => array()
        );
        
        $this->object->addRoute('get', $routes['get']);

        //$this->object->addRoute($routes);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp() {
        $this->object = new Router\router;
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown() {}
    
    
    public function testPrivateGetConfigData() {        
        $testdata = array('php');
        $r = new ReflectionObject($this->object);
        $privGetConfig = $r->getMethod('_getConfig');
        $privGetConfig->setAccessible(true);
        $this->assertEquals($testdata,$privGetConfig->invoke($this->object,'controllers/ext'));                              
    }
    
    /**
    * @expectedException \OutOfBoundsException
    */
    public function testPrivateGetConfigDataFail() {        
        $r = new ReflectionObject($this->object);
        $privGetConfig = $r->getMethod('_getConfig');
        $privGetConfig->setAccessible(true);
        $privGetConfig->invoke($this->object,'noItem');
    }

    public function testClearRoutes() {
        $this->object->clearRoutes();
        $c = $this->getRouteObjectConfigData();
        
        $this->assertTrue(empty($c['routes']));
    }

    /**
     * @covers router::addRoute
     */
    public function testAddRoute() {
        $this->object->clearRoutes();
        
        $this->object->addRoute('get','teste','teste__1');
        
        $c = $this->getRouteObjectConfigData();
        
        $this->assertEquals(array('teste' => 'teste__1'),$c['routes']['GET']);
    }

    public function testAddRouteArray() {
        $this->object->clearRoutes();
        $routes = array(
                        'teste' => 'teste__1',
                        'blaaaa' => 'sffdsasafd'
                   );
        $this->object->addRoute('GET',$routes);
        
        $c = $this->getRouteObjectConfigData();
        
        $this->assertEquals($routes,$c['routes']['GET']);
    }

    public function testMatchRouteNormal() {
        $this->object->clearRoutes();
        $this->addTheseRoutes();

        $this->assertEquals('teste__1',$this->object->matchRoute('teste'));

        $this->assertEquals('index',$this->object->matchRoute(''));
    }

    public function testMatchRouteRegex() {
        $this->addTheseRoutes();
        $this->assertEquals('index/5',$this->object->matchRoute('index.php?5'));
        $this->assertEquals('index/test1',$this->object->matchRoute('test1'));
    }
    
    public function testMatchRouteType() {
        $this->object->clearRoutes();
        $routes = array(
                        'teste' => 'teste__1',
                        'blaaaa' => 'sffdsasafd'
                   );
        $this->object->addRoute('put',$routes);
        
        $c = $this->getRouteObjectConfigData();        
        $this->assertEquals($routes,$c['routes']['PUT']);
        $this->assertEquals('teste__1',$this->object->matchRoute('teste'));
        $this->assertEquals('sffdsasafd',$this->object->matchRoute('blaaaa','put'));
    }
    
    public function testMatchRouteTypeAll() {
        $this->object->clearRoutes();
        $routes = array(
                        'teste' => 'teste__1'                        
                   );
        $routes_all = array(
                        'blaaaa' => 'sffdsasafd'
            );
        $this->object->addRoute('put',$routes);
        $this->object->addRoute('all', $routes_all);
        
        $this->assertEquals('sffdsasafd',$this->object->matchRoute('blaaaa','put'));
    }
    

    /**
    * @expectedException Router\InvalidControllerDirectoryException
    */
    public function testSetControllersDir() {
        $this->object->setControllersDir('no_where/');
    }

    public function testSetControllersDirValid() {
        $this->object->setControllersDir('controllers/');        
        $c = $this->getRouteObjectConfigData();
        $this->assertEquals(array('controllers/'),$c['controllers']['dir']);
                //$this->object->getControllerDir());
    }
    
    public function testGetControllersDir() {
        $c = $this->getRouteObjectConfigData();
        $dir = array('controllers/');
        $c['controllers']['dir'] = $dir;
        
        $this->setRouteObjectConfigData($c);
        
        $this->assertEquals($dir,$this->object->getControllerDir());
    }


    public function testSetControllersState() {        
        $this->object->setControllersState(false);
        $c = $this->getRouteObjectConfigData();
        
        $this->assertFalse($c['controllers']['enabled']);
        //$this->setRouteObjectConfigData($c);
        
        //$this->_config['controllers']['enabled'] = $state;
        //return $this;
    }

    public function testGetControllersState() {
        $c = $this->getRouteObjectConfigData();
        
        $c['controllers']['enabled'] = true;
        $this->setRouteObjectConfigData($c);
                
        $this->assertTrue($this->object->getControllersState());                        
    }
    
    public function testAddControllerExt() {
        $ext = 'php5';
        $this->object->addControllerExt($ext);
        $c = $this->getRouteObjectConfigData();
        
        $this->assertEquals(array('php',$ext),$c['controllers']['ext']);
    }
    
    public function testAddControllerExtArray() {
        $ext = array('php5','php3');
        $this->object->addControllerExt($ext);
        $c = $this->getRouteObjectConfigData();
        
        $this->assertEquals(array('php','php5','php3'),$c['controllers']['ext']);
    }
    
    public function testAddControllerExtPreventDuplication() {
        $ext = 'php';
        $this->object->addControllerExt($ext);
        $c = $this->getRouteObjectConfigData();
        
        $this->assertEquals(array('php'),$c['controllers']['ext']);
    }

    
    public function testDelControllerExt() {
        $ext = 'php';
        $this->object->delControllerExt($ext);
        
        $c = $this->getRouteObjectConfigData();        
        $this->assertEquals(array(),$c['controllers']['ext']);
    }
    
    public function testDelControllerExtArray() {
        $ext = array('php','bogus');
        $this->object->delControllerExt($ext);
        
        $c = $this->getRouteObjectConfigData();        
        $this->assertEquals(array(),$c['controllers']['ext']);
    }

    
    public function testClearControllerExt() {
        $this->object->clearControllerExt();
        
        $c = $this->getRouteObjectConfigData();        
        $this->assertEquals(array(),$c['controllers']['ext']);       
    }

    /**
     * @covers router::run
     * @todo   Implement testRun().
     */
    public function testRun() {
        $this->object->addRoute('ALL','/','controler');        
        $this->object->run('/');
    }
}
