<?php
require '../vendor/autoload.php';
use GuzzleHttp\Client;
use Symfony\Component\Form\Extension\Core\Type\FormType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;

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
    $is_logged_in = false;
    $params = $request->query->all();
    if (isset($params['code'])) {
        $guzzleClient = new Client();
        $uri = 'https://id.twitch.tv/oauth2/token?client_id=' . getenv('TWICH_CLIENT_ID') . '&client_secret=' . getenv('TWICH_CLIENT_SECRET') . '&code=' . $params['code'] . '&grant_type=authorization_code&redirect_uri=' . getenv('TWICH_REDIRECT_URI');
        $response = $guzzleClient->post($uri, []);
        $decode = json_decode((string) $response->getBody(), true);
        $app['session']->set('twich_token', ['token' => $decode]);
        $is_logged_in = true;
    }

    return $app['twig']->render('index.twig', [
        'client_id' => getenv('TWICH_CLIENT_ID'),
        'redirect_url' => getenv('TWICH_REDIRECT_URI'),
        'scope' => 'user_read',
        'is_logged_in' => $is_logged_in,
    ]);
});

$app->post('/streamer', function(Request $request) use ($app) {
    $username = $request->get('name');
    $twich_token = $app['session']->get('twich_token');
    echo '<pre>';
    var_dump($twich_token);
    exit;
    $guzzleClient = new Client(['Authorization' => 'Bearer '.$twich_token['access_token']]);
    $uri = 'https://api.twitch.tv/helix/users?login='.$username;
    $response = $guzzleClient->get($uri, []);
    echo '<pre>';
    print_r((string) $response->getBody());
});
$app->run();
