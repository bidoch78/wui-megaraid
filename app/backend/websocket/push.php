<?php 

    declare(strict_types=1);

    namespace megaraid\socket;

    $entryData = array(
        'category' => "kittensCategory"
      , 'title'    => "ohoh"
      , 'article'  => "yes"
      , 'when'     => time()
    );

    $context = new \ZMQContext();
    $socket = $context->getSocket(\ZMQ::SOCKET_PUSH, 'my pusher');
    $socket->connect("tcp://localhost:5555");

    $socket->send(json_encode($entryData));
    
?>