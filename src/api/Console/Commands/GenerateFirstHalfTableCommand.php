<?php

namespace Palshin\PswCrack\Console\Commands;

use Palshin\PswCrack\DataProvider\ListDataProvider;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Illuminate\Database\Capsule\Manager as Capsule;

#[AsCommand(
    name: 'app:generate-first-half',
    description: 'Generate first_half table for mixed alpha chars and numeric'
)]
class GenerateFirstHalfTableCommand extends Command
{
    const DB_TABLE = 'first_half';
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

        // Generate rainbow table
        $output->writeln('Generating first_half table...');
        $output->writeln('This may take a while...');

        // Create the first_half table if it doesn't exist
        if (!$writeService->connection('db')->getSchemaBuilder()->hasTable(self::DB_TABLE)) {
            $output->writeln('Creating first_half table...');
            $writeService->connection('db')->getSchemaBuilder()->create(self::DB_TABLE, function ($table) {
                $table->increments('id');
                $table->string('value', 3);
                $table->string('hash', 32);
                $table->index('value');
            });
        }

        $values = [];
        foreach ($dataProvider->generate() as $k => $value) {
            if (null !== $value) {
                $values[] = ['value' => $value, 'hash' => md5($value)];
            }

            if($k % 5000 === 0) {
                $writeService->connection('db')->table(self::DB_TABLE)->insert($values);
                $values = [];
            }
        }

        // Insert any remaining pairs
        if (!empty($values)) {
            $writeService->connection('db')->table(self::DB_TABLE)->insert($values);
        }

        $output->writeln('First_half table generation complete!');

        return Command::SUCCESS;
    }
}