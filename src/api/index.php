<?php

define('CHAIN_LENGTH', 1000);
define('HASH_LAST_BYTE', 15);
define('AMOUNT', 100000);
/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
require __DIR__.'/../../vendor/autoload.php';

use Illuminate\Database\Capsule\Manager as Capsule;
use Palshin\PswCrack\Service\RainbowTableService;
use Palshin\PswCrack\DataProvider\ListDataProvider;
use Palshin\PswCrack\Service\MeetInTheMiddleAttackService;
use Palshin\PswCrack\Service\WriteFileService;

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
//
//$pairs = [];
//$words = [];
//$reductions = [];
//
//mt_srand(CHAIN_LENGTH);
//
//$i = 0;
//while($i < CHAIN_LENGTH) {
//    $positions = []; // массив, содержащий номера байт хэша для каждой конкретной редукции
//    $positions[] = mt_rand(0, HASH_LAST_BYTE);
//    for($j = 1; $j < 4; ++$j) {
//        do {
//            $ind = mt_rand(0, HASH_LAST_BYTE);
//            if(!in_array($ind, $positions)) { // используются только различные байты
//                $positions[] = $ind;
//                break;
//            }
//        }
//        while(true);
//    }
//    if(!in_array($positions, $reductions)) { // все редукции различны
//        $reductions[] = $positions;
//        ++$i;
//    }
//}
//
//for($j = 1; $j <= AMOUNT; ++$j) {
//    do {
//        $start = getWord(true); // генерация случайного слова (начало цепочки)
//        if(!in_array($start, $words)) {
//            $words[] = $start;
//            break;
//        }
//    }
//    while(true);
//
//    $finish = getEndOfChain($start); // вычисление конца цепочки
//    $pairs[] = ['finish' => $finish, 'start' => $start];
//
//    if($j % 5000 == 0) {
//        $capsule->connection('db')->table('rainbowtable_5')->insert($pairs);
//        $pairs = [];
//    }
//}
//
//function getWord($newRandom = false) {
//    $alphabet = range('a', 'z');//array_merge(range('A', 'Z'), range('0', '9'));
//
//    $lastChar = count($alphabet) - 1;
//
//    if($newRandom) {
//        mt_srand();
//    }
//
//    $word = $alphabet[mt_rand(0, $lastChar)];
//    for($i = 1; $i < 6; ++$i) {
//        $word .= $alphabet[mt_rand(0, $lastChar)];
//    }
//    return $word;
//}
//
//function getEndOfChain($word, $startStep = 0, $length = CHAIN_LENGTH) {
//    for($i = $startStep; $i < $length; ++$i) {
//        $hash = md5($word.'ThisIs-A-Salt123');
//        $word = reduction($hash, $i);
//    }
//    return $word;
//}
//
//function reduction($hash, $step) {
//    global $reductions;
//    $pos = $reductions[$step % CHAIN_LENGTH];
//
//    mt_srand(ord($hash[$pos[0]]) | ord($hash[$pos[1]]) << 8 | ord($hash[$pos[2]]) << 16 | ord($hash[$pos[3]]) << 24);
//
//    return getWord();
//}
//
//function attack($user, $step) {
//    global $capsule;
//    $fileService = new \Palshin\PswCrack\Service\WriteFileService('./storage/rainbow.txt', 'ab+');
//    $hash = $user->password;
//
//        $words = getWordsInChain($hash);
//
//        $pairs = $capsule->connection('db')->table('rainbowtable_5')->whereIn('finish', $words)->get();
//
//        for($j = 0, $m = count($pairs); $j < $m; ++$j) {
//            $steps = array_search($pairs[$j]->finish, $words);
//            $word = getEndOfChain($pairs[$j]->start, 0, $steps);
//
//            if(md5($word.'ThisIs-A-Salt123') === $hash) {
//                $fileService->write(sprintf('User with id %d has password: %s hashed: %s', $user->user_id, $word, $hash));
//                break;
//            }
//        }
//}
//
//function getWordsInChain($hash) {
//    $words = []; // массив слов для длины цепи в 100, 99 и тд до 1
//    for($i = 0, $n = CHAIN_LENGTH; $i < $n; ++$i) {
//        $wordStart = reduction($hash, $i);
//        $wordEnd = getEndOfChain($wordStart, $i + 1);
//        $words[] = $wordEnd;
//    }
//    return $words;
//}

//$fileService = new \Palshin\PswCrack\Service\WriteFileService('./storage/rainbow.txt', 'r');

//$rainbowService = new RainbowTableService();
//die();
$fileService = new WriteFileService('./storage/mitm_results.txt', 'ab+');

// Create the attack service
$attackService = new MeetInTheMiddleAttackService('ThisIs-A-Salt123', $fileService);
$firstHalfProvider = new ListDataProvider(2, 2, implode(array_merge(range('0', '9'), range('A', 'Z'))));
$secondHalfProvider = new ListDataProvider(2, 2, implode(array_merge(range('0', '9'), range('A', 'Z'))));

$users = $capsule->connection('db')->table('not_so_smart_users')->orderBy('user_id')->lazy()
    ->each(function (object $user) use ($attackService, $firstHalfProvider, $secondHalfProvider) {
        $targetHash = $user->password;
        echo "Target hash: $targetHash\n";
        echo "Attacking...\n";

        $startTime = microtime(true);
        $result = $attackService->attack($targetHash, $firstHalfProvider, $secondHalfProvider);
        $endTime = microtime(true);

        if ($result) {
            echo "Password found: $result\n";
        } else {
            echo "Password not found\n";
        }

        echo "Time taken: " . ($endTime - $startTime) . " seconds\n\n";

});
