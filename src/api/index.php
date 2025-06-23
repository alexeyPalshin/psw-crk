<?php
/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;

$capsule = new Capsule;

$capsule->addConnection([
    'driver' => 'mysql',
    'host' => 'db',
    'database' => getenv('DB_DATABASE'),
    'username' => getenv('DB_USERNAME'),
    'password' => getenv('DB_PASSWORD'),
], 'db');

$capsule->setAsGlobal();
$capsule->bootEloquent();

$router = new \Bramus\Router\Router();

$router->setNamespace('\Palshin\PswCrack\Controllers');

// Handle preflight OPTIONS requests
$router->options('.*', function() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("HTTP/1.1 200 OK");
    exit();
});

// User routes
$router->get('users', 'UsersController@index');
$router->post('crack/{hash}', 'PswCrackController@crack');

// Set CORS headers for all routes
$router->before('GET|POST|PUT|DELETE', '.*', function() {
    header("Access-Control-Allow-Origin: *");
    header("Access-Control-Allow-Headers: *");
});

$router->run();

exit();