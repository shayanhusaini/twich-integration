<?php
require '../vendor/autoload.php';

$app = new Eole\Sandstone\Application();
$app['debug'] = true;

// Sandstone requires JMS serializer
$app->register(new Eole\Sandstone\Serializer\ServiceProvider());

// Register and configure your websocket server
$app->register(new Eole\Sandstone\Websocket\ServiceProvider(), [
    'sandstone.websocket.server' => [
        'bind' => '0.0.0.0',
        'port' => '25569',
    ],
]);

// Register Push Server and ZMQ bridge extension
$app->register(new \Eole\Sandstone\Push\ServiceProvider());
$app->register(new \Eole\Sandstone\Push\Bridge\ZMQ\ServiceProvider(), [
    'sandstone.push.server' => [
        'bind' => '127.0.0.1',
        'host' => '127.0.0.1',
        'port' => 5555,
    ],
]);

// Register serializer metadata
$app['serializer.builder']->addMetadataDir(
    __DIR__,
    ''
);

// Add a route `chat/{channel}` and its Topic factory (works same as mounting API endpoints)
$app->topic('chat/{channel}', function ($topicPattern) {
    return new ChatTopic($topicPattern);
});

// Encapsulate your application and start websocket server
$websocketServer = new Eole\Sandstone\Websocket\Server($app);

$websocketServer->run();