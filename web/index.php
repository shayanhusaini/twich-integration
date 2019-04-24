<?php
require '../vendor/autoload.php';
use Symfony\Component\HttpFoundation\Request;
use NewTwitchApi\HelixGuzzleClient;
use GuzzleHttp\Client;

$app = new Silex\Application();
$app['debug'] = true;
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
    $params = $request->query->all();
    if (isset($params['code'])) {
        $guzzleClient = new Client();
        $uri = 'https://id.twitch.tv/oauth2/token?client_id='.getenv('TWICH_CLIENT_ID').'&client_secret='.getenv('TWICH_CLIENT_SECRET').'&code='.$params['code'].'&grant_type=authorization_code&redirect_uri='.getenv('TWICH_REDIRECT_URI');
        $response = $guzzleClient->post($uri, []);
        //$app['session']->set('twich_token', $response->getBody());
        echo '<pre>';
        print_r($response->getBody()->getContents());
        echo '</pre>';
    }

    return $app['twig']->render('index.twig', [
        'client_id' => getenv('TWICH_CLIENT_ID'),
        'redirect_url' => getenv('TWICH_REDIRECT_URI'),
        'scope' => 'user_read',
    ]);
});
$app->run();
