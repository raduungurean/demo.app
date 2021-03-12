<?php

use App\Domain\Post\Event\PostWasAdded;
use App\Domain\Post\Repository\PostRepositoryInterface;
use App\Factory\LoggerFactory;
use App\Handler\DefaultErrorHandler;
use App\Infrastructure\Projection\Firebase\PostWasCreatedProjection;
use App\Infrastructure\Repository\Post\PostRepository;
use Cake\Database\Connection;
use Jose\Component\Checker\ClaimCheckerManager;
use Jose\Component\Checker\ExpirationTimeChecker;
use Jose\Component\Checker\IssuedAtChecker;
use Jose\Component\Checker\NotBeforeChecker;
use Jose\Component\Core\AlgorithmManager;
use Jose\Component\Signature\Algorithm\RS256;
use Jose\Component\Signature\JWSVerifier;
use Kreait\Firebase\Factory;
use League\Event\EventDispatcher;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Selective\BasePath\BasePathMiddleware;
use Selective\Validation\Encoder\JsonEncoder;
use Selective\Validation\Middleware\ValidationExceptionMiddleware;
use Selective\Validation\Transformer\ErrorDetailsResultTransformer;
use Slim\App;
use Slim\Factory\AppFactory;
use Slim\Interfaces\RouteParserInterface;
use Slim\Middleware\ErrorMiddleware;
use Slim\Psr7\Factory\StreamFactory;
use Slim\Views\PhpRenderer;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Input\InputOption;
use Tuupola\Middleware\HttpBasicAuthentication;

return [
    // Application settings
    'settings' => function () {
        return require __DIR__ . '/settings.php';
    },
    'authUrl' => function () {
        return 'https://api.league7.app/';
    },
    'jwksBaseUrl' => function (ContainerInterface $container) {
        return $container->get('authUrl') . '.well-known/';
    },
    'jwksUrl' => function ($container) {
        return $container->get('jwksBaseUrl') . 'jwks.json';
    },
    'JWSVerifier' => function () {
        return new JWSVerifier(
            new AlgorithmManager([
                new RS256(),
            ])
        );
    },
    'ClaimCheckerManager' => function () {
        return new ClaimCheckerManager([
            new IssuedAtChecker(),
            new NotBeforeChecker(30),
            new ExpirationTimeChecker(),
        ]);
    },
    'Firestore' => function () {
        $factory = (new Factory)
            ->withServiceAccount(__DIR__ . '/../custom-jwt-auth-firebase-adminsdk.json');
        return $factory->createFirestore();
    },
    FilesystemAdapter::class => function () {
        return new FilesystemAdapter();
    },
    EventDispatcher::class => function (ContainerInterface $container) {
        $dispatcher = new EventDispatcher();
        $dispatcher->subscribeTo(PostWasAdded::class, $container->get(PostWasCreatedProjection::class));
        return $dispatcher;
    },
    PostRepositoryInterface::class => function (ContainerInterface $container) {
        return $container->get(PostRepository::class);
    },
    App::class => function (ContainerInterface $container) {
        AppFactory::setContainer($container);

        return AppFactory::create();
    },
    // For the responder
    ResponseFactoryInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getResponseFactory();
    },

    StreamFactoryInterface::class => function () {
        return new StreamFactory();
    },

    // The Slim RouterParser
    RouteParserInterface::class => function (ContainerInterface $container) {
        return $container->get(App::class)->getRouteCollector()->getRouteParser();
    },

    // The logger factory
    LoggerFactory::class => function (ContainerInterface $container) {
        return new LoggerFactory($container->get('settings')['logger']);
    },

    BasePathMiddleware::class => function (ContainerInterface $container) {
        $app = $container->get(App::class);

        return new BasePathMiddleware($app);
    },

    // Database connection
    Connection::class => function (ContainerInterface $container) {
        return new Connection($container->get('settings')['db']);
    },

    PDO::class => function (ContainerInterface $container) {
        $db = $container->get(Connection::class);
        $driver = $db->getDriver();
        $driver->connect();

        return $driver->getConnection();
    },

    ValidationExceptionMiddleware::class => function (ContainerInterface $container) {
        $factory = $container->get(ResponseFactoryInterface::class);

        return new ValidationExceptionMiddleware(
            $factory,
            new ErrorDetailsResultTransformer(),
            new JsonEncoder()
        );
    },

    ErrorMiddleware::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['error'];
        $app = $container->get(App::class);

        $logger = $container->get(LoggerFactory::class)
            ->addFileHandler('error.log')
            ->createLogger();

        $errorMiddleware = new ErrorMiddleware(
            $app->getCallableResolver(),
            $app->getResponseFactory(),
            (bool)$settings['display_error_details'],
            (bool)$settings['log_errors'],
            (bool)$settings['log_error_details'],
            $logger
        );

        $errorMiddleware->setDefaultErrorHandler($container->get(DefaultErrorHandler::class));

        return $errorMiddleware;
    },

    Application::class => function (ContainerInterface $container) {
        $application = new Application();

        $application->getDefinition()->addOption(
            new InputOption('--env', '-e', InputOption::VALUE_REQUIRED, 'The Environment name.', 'development')
        );

        foreach ($container->get('settings')['commands'] as $class) {
            $application->add($container->get($class));
        }

        return $application;
    },

    PhpRenderer::class => function (ContainerInterface $container) {
        return new PhpRenderer($container->get('settings')['template']);
    },

    HttpBasicAuthentication::class => function (ContainerInterface $container) {
        $settings = $container->get('settings')['api_auth'];

        return new HttpBasicAuthentication($settings);
    },
];
