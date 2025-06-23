<?php

namespace Palshin\PswCrack\Controllers;

use Palshin\PswCrack\Service\MeetInTheMiddleAttackService;
use Palshin\PswCrack\Service\WriteFileService;
use Palshin\PswCrack\Service\RainbowTableAttackService;

class PswCrackController extends BaseController
{
    private $capsule;
    private $attackService;
    private $table = 'first_half';

    public function __construct()
    {
        global $capsule;
        $fileService = new WriteFileService('./storage/crackme_results.txt', 'ab+');
        $this->capsule = $capsule;
        $this->attackService = new MeetInTheMiddleAttackService($fileService);

        // Initialize the Rainbow attack service
//        $this->attackService = new RainbowTableAttackService($capsule, $fileService);
    }

    public function crack(string $targetHash)
    {
        $firstHalfProvider = $this->capsule->connection('db')->table($this->table)->whereRaw('length(value)<=3')->get();
        $secondHalfProvider = $this->capsule->connection('db')->table($this->table)->whereRaw('length(value)<=3')->get();

        $startTime = microtime(true);
        $result = $this->attackService->attackMultiple([$targetHash], $firstHalfProvider, $secondHalfProvider);
        $endTime = microtime(true);

        $response = [
            'hash' => $targetHash,
            'password' => $result,
            'found' => $result !== null,
            'time_taken' => round($endTime - $startTime, 4) . ' seconds'
        ];

        if ($result) {
            $this->json($response);
        }
    }
}
