<?php
/**
 * Usage
 * ===============================
 * php start.php start
 */

ini_set('display_errors', 'on');

// function exceptionErrorHandler($errno, $errstr, $errfile, $errline ){
//     throw new ErrorException($errstr, $errno ,$errno, $errfile, $errline);
// }

// set_error_handler("exceptionErrorHandler");

use Workerman\Worker;

//Check extension is loaded
if(!extension_loaded('pcntl')){
    exit("Please install pcntl extension. See http://doc3.workerman.net/install/install.html\n");
}

if(!extension_loaded('posix')){
    exit("Please install posix extension. See http://doc3.workerman.net/install/install.html\n");
}

//Define
define('GLOBAL_START', 1);
define('APP_PATH', __DIR__);
define('QUEUE_PREF', 'QUEUE_');
define('DONE_PREF', 'DONE_');
define('ERROR_PREF', 'ERROR_');

require_once __DIR__ . '/Workerman/Autoloader.php';

//Composer autoload
require_once __DIR__ . '/vendor/autoload.php';

//Start all started with start_ prefix
foreach(glob(__DIR__.'/Applications/*/start*.php') as $start_file){
    require_once $start_file;
}

// Run All
Worker::runAll();