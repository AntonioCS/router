<?php


class router {

    /**
    * Holds the controllers instance
    *
    * @var acs_controller
    */
	private $_controller = null;

    /**
    * Holds the controller data. Name, path, view path, class name, action
    *
    * @var string
    */
	private $_controller_data = array(
                                    'name' => null,
                                    'path' => null,
                                    'class' => null,
                                    'view' => null,
                                    'action' => null
                                );

    /**
    * Default suffix for the controller class
    *
    * @var string
    */
	private $_controllerKeyword = '_Controller';

    /**
    * Default controllers parent
    *
    * @var string
    */
	private $_parent_controller_class = 'acs_controller';

    /**
    * Name of the controller's class
    *
    * @var string
    */
    private $_controller_class = null;

    /**
    * Holds the controllers name
    *
    * @var string
    */
    private $_controller_name = null;

    /**
    *
    * @var string
    */
    private $_controller_path = null;

    /** singleton code **/
	protected static $_instance = null;

	public static function getInstance() {
		if (!self::$_instance)
			self::$_instance = new acs_router();

		return self::$_instance;
	}
	/** singleton code end **/

	//Should not be public, but it has to be
	public function __construct() {
		//parent::__construct();
	}

    /**
    * Retrieve saved controller data
    *
    * @param string $item
    */
    public function getControllerData($item = 'name') {
        if (isset($this->_controller_data[$item]))
            return $this->_controller_data[$item];

        return null;
    }

    /**
    * Save controller data
    *
    * @param string $item
    * @param mixed $value
    */
    private function setControllerData($item,$value) {
        $this->_controller_data[$item] = $value;
    }

    /**
	* This method will parse the given route to the list of routes in the acs_routes file
	* and will return the correct route
	*
	* @param mixed $givenroute
	* @return mixed
	*/
	public function parseRoute($givenroute) {
		require($this->configData->pathroutes);

		$newroute = null;
		if ($this->configData->log_routes)
			$this->log->accesslog("Trying to match $givenroute");

		foreach ($acs_routerTable as $pattern => $value) {

			if (preg_match($pattern,$givenroute,$match)) {
				$newroute = preg_replace($pattern,$value,$givenroute);

				if ($this->configData->log_routes) {
					$this->log->accesslog("Routes match found: $pattern");
					$this->log->accesslog("New route: $newroute");
				}

				return $newroute;
			}
		}
		if ($this->configData->log_routes)
			$this->log->accesslog("No matches found");

		return $givenroute;
	}

    /**
    * When this is function is called it will have to be in the format: controller/action/parms, even if mod rewrite is not on
    *
    * @param string $route
    */
    public function route($route) {
        $path = $this->createRouteStructure($this->parseRoute($route));

        $this->loadController($path['controller'])
                ->loadAction($path['action'], $path['parms']);
    }

    /**
    * Create the array with controller, action and parms items
    *
    * @param mixed $route
    * @return array
    */
    private function createRouteStructure($route) {
        $path = array();

        $controller_dir = $this->configData->controller_dir;
        $acs_controller_dir = $this->configData->acs_controller_dir;

        $p = explode('/',$route);

        $p_ = null; //In case there are many subfolders

        $controller = array();
        $action = $parms = null;

        while (true) {
            if (!isset($p[0]))
                throw new acs_exception('Route problem');

            if (!empty($controller)) //We have something here
                $p_ = implode('/',$controller) . '/'; //if we have dir/dir/controller  or deeper

            //check controller and acs controller dirs
            if (is_dir($controller_dir . $p_ . $p[0]) || is_dir($acs_controller_dir . $p_ . $p[0])) {

                $c = $p[0] . $this->_controllerKeyword . '.' . $this->configData->common_extension;
                //In case the folder is also the name of the controller and so it was ommited like controller/action where controller is a folder which also has the file controller inside it
                if (file_exists($controller_dir . $p_ . $p[0] . '/' . $c) || file_exists($acs_controller_dir . $p_ .$p[0] . '/' . $c)) {
                    $controller[] = $p[0]; //directory
                    $controller[] = $p[0]; //actual controller
                    break;
                }
                else
                    $controller[] = array_shift($p);

                continue;
            }
            else {
                $controller[] = $p[0];
                break;
            }
        }

        $path['controller'] = implode('/',$controller);
        $path['action'] = (isset($p[1]) ? $p[1] : 'index');

        $path['parms'] = null;

        if (isset($p[2]))
            $path['parms'] = array_slice($p,2);

        return $path;
    }


	/**
	* @desc This will load the specified controller
	*
	* @param string $loadcontroller - Name of the controller with no controllerKeyword nor file extension
	* @return void
	*/

    public function loadController($controller, $view = null) {
        $this->_controller_name = $controller;

        //in case the controller is inside a subfolder I must use the basename
        $c_name = basename($controller);
        $c = $c_name . $this->_controllerKeyword; //class name
        $c_file = $controller . $this->_controllerKeyword . '.' . $this->configData->common_extension; //must use the controller again in case of subfolder. The $c variable only contains the file

        $this->setControllerData('controller_name',$c_name);
        $this->setControllerData('controller_class',$c);
        $this->setControllerData('controller_path',$c_file);

        $c_path = null;

        $c_dir = $this->configData->controller_dir;
        $acs_c_dir = $this->configData->acs_controller_dir;

        //The path has already been set I just have to see if it's in the user controllers dir or the frameworks controller dir
        switch (true) {
            case (file_exists($c_dir . $c_file)):
                $c_path = $c_dir . $c_file;
            break;
            case (file_exists($acs_c_dir . $c_file)):
                $c_path = $acs_c_dir . $c_file;
            break;
            default:
                throw new acs_exception_controlloeractionnotfound(CONTROLLER_ERROR_NOCONTROLLER . ": $c");
                //throw new acs_exception(array('teste', CONTROLLER_ERROR_NOCONTROLLER . ": $c"),1);
        }

        $this->_controller_path = $c_path;
        $this->setControllerData('controller_full_path', $c_path);


        //Loading main controller
        if (!class_exists('MainController',false))
            require($this->configData->maincontroller . '.' . $this->configData->common_extension);

        require($c_path);

        if (class_exists($c,false)) {

            $this->_controller_class = $c;

            //create view
            $possibleViews = $this->createPossibleViews($controller);
            $this->setControllerData('controller_possible_views', $possibleViews);

            if ($view)
                array_unshift($view,$possibleViews);

            $v = new acs_view($possibleViews);

            if ($v->hasview())
                $this->setControllerData('controller_view',$v->getPath());


            ($this->configData->Debug ? $this->log->debug('Instantiating class ', $c) : null);
            $this->_controller = new $c($v);

            if (is_subclass_of($this->_controller,$this->_parent_controller_class)) {
                return $this;
            }
            else
                throw new acs_exception(CONTROLLER_ERROR_NOPARENT . ' - ' . get_parent_class($this->_controller),1);
        }
        else
            throw new acs_exceptionControlloerActionNotFound(CONTROLLER_ERROR_NOCLASS . ": $c");
    }

    /**
    * Load controllers action
    *
    * @param string $action
    * @param string|array $parms
    * @param bool $autoCallView
    */
    public function loadAction($action, $parms = null, $autoCallView = true) {

        if ($this->_controller) {
            if ($action) {
                if (method_exists($this->_controller,$action)) {

                    $this->createActionView($action);

                    $this->setControllerData('action',$action);

                    $this->_controller->action_name = $action;

                    //The method was called with parameters and the method has parameters
                    if ($parms && ($actionParameters = $this->actionParamStructure($action)) != null) {

                        $this->setControllerData('parameters', $parms);
                        //ordered parameters
                        $oparms = $this->processActionParameters($parms, $actionParameters);

                        $this->setControllerData('ordered_parameters', $oparms);

                        $this->callBeforeAction($action,$oparms);

                        $this->callActionWithParms($action,$oparms);

                        $this->callAfterAction($action,$oparms);
                    }
                    else {
                        $this->callBeforeAction($action);

                        $this->_controller->$action();

                        $this->callAfterAction($action);
                    }
                }
                //Action does not exist
                else {
                    ($this->configData->Debug ? $this->log->debug('No action') : null);

                    //Handle the non-existance of the action in the controller
                    //'_no_action' Event
                    if (method_exists($this->_controller,'_no_action')) {
                        ($this->configData->Debug ? $this->log->debug('Controller has _no_action action. Calling with: ', $action) : null);
                        $this->setControllerData('action','_no_action');
                        //TODO Should I call the createActionView method for this?
                        //TODO maybe make this recursive by calling again the loadAction method
                        $this->_controller->_no_action($action);


                    }
                    else
                        throw new acs_exceptionControlloerActionNotFound("Action $action does not exist");
                        //throw new acs_exceptionActionNotFound(CONTROLLER_ERROR_NOACTION . ": $action",1);
                }
            }
            //no action set, loading index action
            else {
                if (method_exists($this->_controller,'index')) {
                    $this->createActionView('index');
                    $this->setControllerData('action','index');

                    ($this->configData->Debug ? $this->log->debug('Calling index action') : null);
                    $this->_controller->index();//the index function doesn't take parameters, if needed just use the routes file to reroute the index to another file :P
                }
                else
                    throw new acs_exceptionControlloerActionNotFound('Action Index does not exist');
            }
        }
        else
            throw new acs_exceptionControlloerActionNotFound("No controller loaded");


        //At last check if the view is to be called, if there were no errors
        if ($autoCallView) {
            $this->loadView();
        }
    }

    /**
    * Process the parameters passed and set them in the correct order
    *
    * @param array $parms
    * @param array $actionParameters
    *
    * @return array
    */
    private function processActionParameters($parms, $actionParameters) {
        $oparms = array(); //ordered parameters

        //when I call it internally this might not be an array
        if (is_array($parms)) {
            $named_param = array();

            //foreach ($parms as $k => $v) {
            for ($i = 0, $t = count($parms); $i <= $t; $i++) {
                if (!isset($parms[$i]))
                    break;

                $v = $parms[$i];

                //check to see if it has the : seperator (just in case I do something like key:value)
                if (strpos($v,':') !== false) {
                    $e = explode(':',$v);
                    $key = $e[0];

                    if (isset($actionParameters[$key])) {
                        $pos = $actionParameters[$key]['position']; //positon of this parameter in the action parameter list

                        $v = implode(':',array_splice($e,1)); //just in case the value has : in it

                        $named_param[$pos] = $v;
                        continue;
                    }
                }
                //Check if a value has the name of one of the parameters in the action
                elseif (isset($actionParameters[$v])) {
                    //if it does, get the value of the next item in line
                    $next_value = ++$i;
                    if (isset($parms[$next_value])) {
                        $pos = $actionParameters[$v]['position'];
                        $value = $parms[$next_value];

                        $named_param[$pos] = $value;
                        continue;
                    }
                }

                $oparms[$i] = $v;
            }

            //to prevent erroneus inserts I must insert these in the end
            if (!empty($named_param)) {
                ksort($named_param); //must sort the keys
                foreach ($named_param as $k => $v)
                    helper_array::inject($oparms,$k,$v);
            }
        }
        else {
            $oparms[0] = $parms;
        }

        return $oparms;
    }

    /**
    * Fetch parameter from parameter list
    *
    * @param string $paremeter
    * @return mixed
    */
    public function fetchParameter($paremeter) {
        $pa = $this->getControllerData('parameters');

        if ($pa) {
            foreach ($pa as $k => $p) {
                if ($p == $paremeter) {
                    return (isset($pa[$k+1]) ? $pa[$k+1] : null);
                }
            }
        }

        return null;
    }

    /**
    * Fetch the Nth paremter
    *
    * @param mixed $n
    * @return mixed
    */
    public function fetchNumParameter($n) {
        $pa = $this->getControllerData('parameters');

        if ($pa && isset($pa[$n]))
            return $pa[$n];

        return null;
    }

    /**
    * Return last parameter
    *
    */
    public function fetchLastParameter() {
        $pa = $this->getControllerData('parameters');

        if ($pa)
            return end($pa);
    }

    /**
    * Facilitate the calling of methods with parameters
    *
    * @param mixed $action
    * @param mixed $oparms
    */
    private function callActionWithParms($action, $oparms) {

        switch (count($oparms)) { //call_user_func_array is slow!! Try my best to avoid it
            case 1:
                $this->_controller->$action($oparms[0]);
            break;
            case 2:
                $this->_controller->$action($oparms[0],$oparms[1]);
            break;
            case 3:
                $this->_controller->$action($oparms[0],$oparms[1],$oparms[2]);
            break;
            case 4:
                $this->_controller->$action($oparms[0],$oparms[1],$oparms[2],$oparms[3]);
            break;
            case 5:
                $this->_controller->$action($oparms[0],$oparms[1],$oparms[2],$oparms[3],$oparms[4]);
            break;
            default:
                call_user_func_array(array($this->_controller,$action),$oparms);
        }
    }

    /**
    * Call beforeAction and beforeAction_<action>
    *
    * @param string $action
    * @param array $parms
    */
    private function callBeforeAction($action,$parms = null) {
        $b_action_all = 'beforeAction';
        $b_action = 'beforeAction_' . $action;

        if ($parms)
            array_unshift($parms, $action);

        $this->callActions(array($b_action_all, $b_action), $parms);
    }
    /**
    * Create the possible view files
    *
    * @param string $path
    * @param mixed $action
    *
    * @return array
    */
    private function createPossibleViews($path, $action = null) {
        $path = helper_file::nix_slashes($path);
        $options = array();

        if (strpos($path, '/') !== false) { //is there is a slash in the path
            $options[] = $path . ($action ? '/' . $action : null);
            $options[] = str_replace('/','_',$path) . ($action ? '_' . $action : null);
        }
        elseif ($action) {
            $options[] = $path . '/' . $action;
        }

        $options[] = basename($path) . ($action ? '_' . $action : null);

        if ($this->configData->log_possibleviews) {
            $this->log->accesslog('Possible views for ' . $path . ":\n" . var_export($options,true));
        }

        return $options;
    }

    /**
    * Load the view for the action
    *
    * @param string $action
    */
    //TODO: Make this better. Somehow this is messing up when the controller is called from a folder. So it becomes folder/<controler>/view
    private function createActionView($action) {
        //var_dump($this->createPossibleViews($this->_controller_name,$action));
        $action_view = new acs_view($this->createPossibleViews($this->_controller_name,$action));

        if ($action_view->hasview())
            $this->_controller->view_action = $action_view;
    }

    /**
    * Call afterAction and afterAction_<action>
    *
    * @param string $action
    * @param array $parms
    */
    private function callAfterAction($action, $parms = null) {
        $a_action_all = 'afterAction';
        $a_action = 'afterAtion_' . $action;

        if ($parms)
            array_unshift($parms, $action);

        $this->callActions(array($a_action_all, $a_action), $parms);
    }

    /**
    * Call given actions
    *
    * @param mixed $actions
    * @param mixed $parms
    */
    private function callActions($actions, $parms) {
        foreach ($actions as $action) {
            if (method_exists($this->_controller,$action)) {
                if ($parms)
                    $this->callActionWithParms($action, $parms);
                else
                    $this->_controller->$action;
            }
        }
    }

    /**
    * Create action parameter structure
    *
    * @param string $action
    */
    private function actionParamStructure($action) {

        $reflector = new ReflectionClass($this->_controller_class);
        $methodParms = $reflector->getMethod($action)->getParameters();

        //The method has no parameters
        if (!count($methodParms))
            return null;

        //http://php.net/manual/en/class.reflectionparameter.php
        $actionParamStructure = array();

        foreach ($methodParms as $k => $v) {
            $actionParamStructure[$v->name]['position'] = $k;

            if ($v->isOptional()) {
                $actionParamStructure[$v->name]['optional'] = true;
                if ($v->isDefaultValueAvailable())
                    $actionParamStructure[$v->name]['defaultvalue'] = $v->getDefaultValue();
            }
            else
                $actionParamStructure[$v->name]['optional'] = false;
        }

        return $actionParamStructure;
    }


    //private function loadView(/*$viewCached = null*/) {
	public function loadView($extraData = null) {
        ($this->configData->Debug ? $this->log->debug('Loadview starting') : null);

        if ($extraData && is_array($extraData))
            $this->_controller->view->addData($extraData);

        //If this is set the router will call the showonlydata that will output the data in the set format
        if ($this->_controller->view->onlydata) {
            ($this->configData->Debug ? $this->log->debug('Only show data') : null);
            $this->_controller->view->showonlydata();
        }
        elseif (!$this->_controller->view->seen()) { //if this is not false we are going to show the view
            if (!$this->_controller->view->hasview()) { //this controller has no view
                ($this->configData->Debug ? $this->log->debug('No view in controller') : null);
            }

            //If it's necessary to do something before the render method of the view is called
            if (method_exists($this->_controller,'beforeRender') && $this->_controller->statusBeforeRender())
                $this->_controller->beforeRender();

            else
                echo 'n existe';

            ($this->configData->Debug ? $this->log->debug('Getting view data') : null);
            $data = $this->_controller->view->render(true,$this->configData->parseWesternCharacters);

            if (method_exists($this->_controller,'afterRender') && $this->_controller->statusAfterRender())
                $this->_controller->afterRender();

            ($this->configData->Debug ? $this->log->debug('Rendering') : null);
            echo $data;
        }
        else
            ($this->configData->Debug ? $this->log->debug('The noshow was used') : null);
    }

    /**
    * Method to get the Controller, Action or the Data
    *
    * @param string $param - Parameter required. Possible values: controller, action, data
    * @return string
    */
    private function getRouterParameter($param) {
        $place = 0;

        switch ($param) {
            case 'controller':
                $param = $this->configData->qs_controller;
                $place = 1;
            break;
            case 'action':
                $param = $this->configData->qs_action;
                $place = 2;
            break;
            case 'data':
                //$param = $this->configData->qs_data;
                $place = 3;
            break;
            default:
                $param = null;
        }

        if (!$param)
            return $param;

        if (!$this->configData->using_modrewrite) {
            if (!self::$customRoute) { //If I don't have a custom route I can use the query string
                if (isset($_GET[$param]))
                    return ($place == 3 ? explode(':',$_GET[$param]) : $_GET[$param]);

                return null;
            }
            else {
                $t = $this->cr_GET($param);
                return ($place == 3 ? explode(':',$t) : $t);
            }
        }
        else //if the place is 3, it's the data and I am suppose to get everything that comes with it
            return helper_url::getserverarg($place,($place == 3)); //the url helper will automatically check for the customRoute variable
    }

    private function getCorrectPath($type,$item) {


    }

    public function __destruct() {
        if ($this->configData->Debug && $this->configData->DebugToFirebug && $this->log->is_logmsgFirebug()) {
            $debug = new acs_view('debugconsole');

            $debug->debug_msg = $this->log->debugFirebug();

            echo $debug->render();
        }
    }
}
