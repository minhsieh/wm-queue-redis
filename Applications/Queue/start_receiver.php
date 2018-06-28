<?php
use Workerman\Worker;
use Ramsey\Uuid\Uuid;
use Ramsey\Uuid\Exception\UnsatisfiedDependencyException;

$queue_id = 1231;

$msg_receiver = new Worker('Text://0.0.0.0:1231');
$msg_receiver->queueId = $queue_id;
$msg_receiver->config = include(APP_PATH."/config.php");

$msg_receiver->onWorkerStart = function($msg_receiver){
    //init redis client
    $msg_receiver->redis = new Predis\Client([
        'host'   => $msg_receiver->config['redis_host'],
        'port'   => $msg_receiver->config['redis_port'],
    ]);
    $msg_receiver->redis->select($msg_receiver->config['redis_db']);
};

$msg_receiver->onMessage = function($connection , $message) use ($msg_receiver) {
    try{
        //Validate message
        $message = json_decode($message , true);

        if(empty($message['class']) || empty($message['method'])){
            throw new Exception('request format not validated.',901);
        }

        //Assign this queue uuid
        $message['uid'] = Uuid::uuid4()->toString();

        print_r($message);

        //push queue into REDIS queue list.
        $result = $msg_receiver->redis->rpush("QUEUE_".$msg_receiver->queueId , json_encode($message));
        if(!$result){
            throw new Exception('push into redis failed.',902);
        }

        //return response to client
        return $connection->send(json_encode(['code' => 0 , 'msg' => 'success' , 'uid' => $message['uid'], 'count' => $result]));
    }catch(Exception $ex){
        return $connection->send(json_encode(['code' => empty($ex->getCode())?999:$ex->getCode()  , 'msg' => $ex->getMessage()]));
    }
    
};