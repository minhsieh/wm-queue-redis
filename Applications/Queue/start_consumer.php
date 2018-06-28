<?php
use Workerman\Worker;

$consumer = new Worker;

//Set queue id
$consumer->queueId = 1231;
$consumer->config = include(APP_PATH."/config.php");

$consumer->onWorkerStart = function($consumer){
    //Init REDIS client.
    $consumer->redis = new Predis\Client([
        'scheme' => 'tcp',
        'host'   => $consumer->config['redis_host'],
        'port'   => $consumer->config['redis_port'],
    ]);
    $consumer->redis->select($consumer->config['redis_db']);

    \Workerman\Lib\Timer::add(0.5, function() use ($consumer){
        while(1){
            try{
                //Get padding queue from redis.
                $message = $consumer->redis->lPop(QUEUE_PREF.$consumer->queueId);
                if(!$message){
                    return;
                }
                // message format must like: {"uid":"e4eaaaf2-d142-11e1-b3e4-080027620cdd","class":"class_name", "method":"method_name", "args":[]}
                $message = json_decode($message, true);

                if(!isset($message['class'])  || !isset($message['method']) || !isset($message['args'])){
                    throw new Exception("unknow message format: ".json_encode($message) ,903);
                }

                $class_name = "\\Consumer\\".$message['class'];
                $method = $message['method'];
                $args = (array)$message['args'];

                //check class is exist
                if(!class_exists($class_name)){
                    throw new Exception("$class_name not exist.", 904);
                }

                $class = new $class_name;
                $callback = array($class, $method);

                //check class method is callalbe
                if(!is_callable($callback)){
                    throw new Exception("$class_name::$method not exist", 905);
                }

                call_user_func_array($callback, $args);

                //put done queue into done list for old check.
                $consumer->redis->hset(DONE_PREF.$consumer->queueId , $message['uid'] , json_encode($message));
            }catch(Exception $ex){
                //display error message to console
                echo "[error] ".$ex->getCode().":".$ex->getMessage();

                //put error queue to error list.
                array_unshift($message , ['error_code' => $ex->getCode() , 'error_msg' => $ex->getMessage()]);
                $consumer->redis->hset(ERR_PREF.$consumer->queueId , $message['uid'] , json_encode($message));
            }
            
        }
    });
};