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

// Create the attack service
$fileService = new WriteFileService('./storage/meet_results.txt', 'ab+');
$attackService = new \Palshin\PswCrack\Service\MeetInTheMiddleAttackService($fileService);
$firstHalfProvider = $capsule->connection('db')->table('first_half')->whereRaw('length(value)>=2')->get();
$secondHalfProvider = $capsule->connection('db')->table('first_half')->whereRaw('length(value)=3')->get();
//
$users = $capsule->connection('db')->table('not_so_smart_users')->get();
    //->chunk(100, function (Illuminate\Support\Collection $users) use ($attackService, $firstHalfProvider, $secondHalfProvider, $fileService) {
//    foreach ($users as $index => $user) {

//       }
        $targetHashes = $users->pluck('password');

        echo "Attacking...\n";

        $startTime = microtime(true);
        $result = $attackService->attackMultiple($targetHashes, $firstHalfProvider, $secondHalfProvider);
        $endTime = microtime(true);

        if ($result) {
            echo "Password found: " . implode(', ', $result) . "\n";
            $fileService->writeLine("Password found: " . implode(', ', $result) . "\n");
        } else {
            echo "Password not found\n";
        }

        echo "Time taken: " . ($endTime - $startTime) . " seconds\n\n";
        $fileService->writeLine("Time taken: " . ($endTime - $startTime) . " seconds\n\n");

//});
