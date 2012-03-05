<?php


class init {

	private $_router = null;

	public function __construct() {
		//parent::__construct();

		$this->_router = acs_router::getInstance();
		require($this->configData->base_dir . 'acs_exception.php');

		//set_error_handler('acs_error_handler'); //Any error that doesn't trow an exception will trow it now
		try {
            $this->loadLang();
            //$this->_router->initiate();

            $controller = null;
            $action = null;

            $route = $_SERVER['QUERY_STRING'];


            /**
            * Construct the route here
            */
            if ($route) {
                if (!$this->configData->using_modrewrite) {
                    //use the array names given in the config file
                    if (isset($_GET[$this->configData->qs_controller])) {
                      $controller = $_GET[$this->configData->qs_controller];
                      unset($_GET[$this->configData->qs_controller]);

                      //only if there is a controller am I going to check for an action!!
                      if (isset($_GET[$this->configData->qs_action])) {
                          $action = $_GET[$this->configData->qs_action];
                          unset($_GET[$this->configData->qs_action]);
                      }
                      else
                        $action = 'index';

                      $route = $controller . '/' . $action;

                      if (!empty($_GET))
                        $route .= '/' . $this->setKeyValue($_GET);
                    }
                }
                else {
                    //There is always a $_GET parameter we only have to check if there are more
                    if (count($_GET) > 1) { //we have paremeters (using ?) must change the route

                        $t = array_keys($_GET);
                        $route = $t[0];

                        $d = $this->setKeyValue(array_slice($_GET,1));

                        if ($d) //add the slash to separate the new data from the controller or action
                           $route .= '/' . $d;
                    }
                }
            }
            $this->_router->route($route);
              //*/
		}
        //catch(Exception $e) {}
		//TODO: Add process code for these excpetions
        /*
        catch (acs_exceptionControllerNotFound $e) {
        }
        catch (acs_exceptionActionNotFound $e) {
        }
        */
        catch (acs_exception_ControlloerActionNotFound $e) {
            $this->_router->loadController('index')->loadAction('index');
        }
		catch (acs_exception $e) {
            if ($this->configData->errorShow) {
			    if (!defined('EXCEPTION_CAUGHT'))
				    define('EXCEPTION_CAUGHT','Caught exception: ');
			    $errormsg = EXCEPTION_CAUGHT . $e->getMessage();
                echo nl2br(PHP_EOL . $errormsg);

                //$this->_router->loadController('errorHandler')->loadAction('error','tete');
            }
		}

	}

    /**
    * Return the $_GET array data in a key:value string
    *
    * @param int $startat If it's necessary not to start at the first item
    */
	private function setKeyValue($data) {
        $d = null;

        if (!empty($data)) {

            $d = array();
            foreach ($data as $k => $v)
                $get[] = $k . ':' . $v;

            $d = implode('/',$get);
        }

        return $d;
    }


	/**
	* Load the language file
	*
	*/
	private function loadlang() {
		$langfile = $this->configData->language_dir. 'lang_' . $this->configData->language . '.' . $this->configData->common_extension;

        if (file_exists($langfile))
            require($langfile);
        else
            throw acs_exception('No language file'); //has to be hardcoded

	}
}

function acs_error_handler($errno, $errstr, $errfile, $errline) {

    if (!class_exists('acs_exception'))
        __autoload('acs_exception'); //Have to call __autoload directly - This is related to a PHP BUG - http://bugs.php.net/bug.php?id=47987

    $m = array(
                'msg' => $errstr,
                'file' => $errfile,
                'line' => $errline,
                'trace' => null
                //var_export(debug_backtrace(),true) <-- too much data
        );


    throw new acs_exception($m, $errno);
}
