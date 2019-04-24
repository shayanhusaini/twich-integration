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

//Register form service
$app->register(new Silex\Provider\FormServiceProvider());

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
        $app['session']->set('twich_token', (string) $response->getBody());
        $is_logged_in = true;
    }

    $form = $app['form.factory']->createBuilder(FormType::class, ['name' => 'Streamer Username'])
            ->add('name')
            ->add('submit', SubmitType::class, ['label' => 'Save'])
            ->getForm();

    return $app['twig']->render('index.twig', [
        'client_id' => getenv('TWICH_CLIENT_ID'),
        'redirect_url' => getenv('TWICH_REDIRECT_URI'),
        'scope' => 'user_read',
        'is_logged_in' => $is_logged_in,
        'form' => $form->createView()
    ]);
});
$app->run();
