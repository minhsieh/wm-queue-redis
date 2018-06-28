wm-queue-redis
=========

這個隊列處理是基於[Workerman](https://www.workerman.net/doc)為基底做的應用，參考原本的專案[walkor/workerman-queue](https://github.com/walkor/workerman-queue)，改用redis作為隊列任務儲存的容器，並稍為做了一點修正。

### Requirement

- php7.* ^
- predis/predis
- ramsey/uuid

### Feature

- 採用REDIS作為隊列儲存的工具，減少sysv對系統的需求
- 每個任務自動產生UID，方便client後續追蹤執行狀態
- 完成或錯誤的任務會紀錄回REDIS，方便重新補跑隊列

### Install
```
git clone https://github.com/minhsieh/wm-queue-redis
cd wn-queue_redis
composer install
cp config.sample.php config.php
#編輯config.php 把redis server的設定填上
```

### Usage
```
#直接啟用Debug模式
php start.php start

#背景Daemon執行
php start.php start -d
```


## 測試發送消息到隊列
```
php Applications/Queue/client_demo.php
```

client_demo.php

```php
<?php
$client = stream_socket_client("tcp://127.0.0.1:1231", $err_no, $err_msg, 5);
if(!$client)
{
    exit($err_msg);
}
$message = array(
    'class' => 'Mail',
    'method' => 'send',
    'args' => array('Mr Hsieh', 'Mr Min', 'hello'),
);
$message = json_encode($message)."\n";
fwrite($client, $message);
echo fread($client, 8192);

```