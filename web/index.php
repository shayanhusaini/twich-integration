<?php
require __DIR__.'/../vendor/autoload.php';
use Symfony\Component\HttpFoundation\Request;
use TwichIntegration\Components\TwichAPI;

ini_set('display_errors', 1);
$dotenv = Dotenv\Dotenv::create(__DIR__.'/../');
$dotenv->load();

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

//Register session service
$app->register(new Silex\Provider\SessionServiceProvider());

// Register the monolog logging service
$app->register(new Silex\Provider\MonologServiceProvider(), array(
    'monolog.logfile' => 'php://stderr',
));
// Register view rendering
$app->register(new Silex\Provider\TwigServiceProvider(), array(
    'twig.path' => __DIR__ . '/views',
));

// Our web handlers
$app->get('/', function (Request $request) use ($app) {
    $app['monolog']->addDebug('logging output.');
    $is_logged_in = false;
    $params = $request->query->all();
    if (isset($params['code'])) {
        $twich = new TwichAPI();
        $app['session']->set('twich_token', $twich->getUser($params['code']));
        $is_logged_in = true;
    }

    return $app['twig']->render('index.twig', [
        'client_id' => getenv('TWICH_CLIENT_ID'),
        'redirect_url' => getenv('TWICH_REDIRECT_URI'),
        'scope' => 'user_read',
        'is_logged_in' => $is_logged_in,
    ]);
});

$app->post('/streamer', function (Request $request) use ($app) {
    $username = $request->get('streamer');
    $twich_token = $app['session']->get('twich_token');
    $twich = new TwichAPI();
    $streamer = $twich->getStreamer($twich_token['access_token'], $username);
    $app['session']->set('favorite_streamer', $streamer);
    $userStream = $twich->getStreamByUsername($twich_token['access_token'], $username);
    $twich->subscribeForEvents($userStream['data'][0]['user_id']);
    $ls_uri = 'https://twitch.tv/streams/'.$userStream['data'][0]['id'].'/channel/'.$userStream['data'][0]['user_id'];
    return $app['twig']->render('stream.twig', [
        'live_stream_url' => $ls_uri
    ]);
});

$app->run();
