<?php

namespace Palshin\PswCrack\Console\Commands;

use Palshin\PswCrack\DataProvider\ListDataProvider;
use Palshin\PswCrack\Service\RainbowTableService;
use Palshin\PswCrack\Service\WriteFileService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

#[AsCommand(
    name: 'app:generate-table',
    description: 'Generate rainbow table for mixed alpha chars and numeric'
)]
class GenerateRainbowTableCommand extends Command
{
    const DEFAULT_MIN_LENGTH = 1;
    const DEFAULT_MAX_LENGTH = 3;
    const MIX_ALPHA_NUMERIC = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';

    protected function execute(InputInterface $input, OutputInterface $output): int
    {

        $dataProvider = new ListDataProvider(
            self::DEFAULT_MIN_LENGTH,
            self::DEFAULT_MAX_LENGTH,
            self::MIX_ALPHA_NUMERIC
        );

        $writeService = new Capsule;

        $writeService->addConnection([
            'driver' => 'mysql',
            'host' => 'db',
            'database' => getenv('DB_DATABASE'),
            'username' => getenv('DB_USERNAME'),
            'password' => getenv('DB_PASSWORD'),
        ], 'db');

        $writeService->setAsGlobal();
        $writeService->bootEloquent();
        $rainbowService = new RainbowTableService();

        $rainbowService->generateRainbowTable($writeService, $dataProvider);

        return Command::SUCCESS;

        // or return this if some error happened during the execution
        // (it's equivalent to returning int(1))
        // return Command::FAILURE;

        // or return this to indicate incorrect command usage; e.g. invalid options
        // or missing arguments (it's equivalent to returning int(2))
        // return Command::INVALID
    }
}