<?php

namespace Palshin\PswCrack\Console\Commands;

use Illuminate\Database\Capsule\Manager as Capsule;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:generate-rainbow-6',
    description: 'Generate rainbow table for passwords with max length 6 containing upper and lower chars and numbers'
)]
class GenerateRainbowTable6Command extends Command
{
    const string SALT = 'ThisIs-A-Salt123';
    const int CHAIN_LENGTH = 2400;
    const int HASH_LAST_BYTE = 15;
    const int AMOUNT = 40000000; // Number of chains to generate

    public array $charList = [];

    protected function configure(): void
    {
        $this
            // configure an argument
            ->addArgument('chars', InputArgument::IS_ARRAY | InputArgument::OPTIONAL, 'characters for table generation.')
        ;
    }
    
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->charList = !empty($input->getArgument('chars')) ? $input->getArgument('chars') : array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));;

        $output->writeln('Generating rainbow table for passwords with max length 6...');
        
        // Initialize database connection
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
        
        // Initialize arrays
        $pairs = [];
        $words = [];
        $reductions = [];
        
        // Initialize reduction functions
        $output->writeln('Initializing reduction functions...');
        $i = 0;
        while ($i < self::CHAIN_LENGTH) {
            $positions = []; // Array containing hash byte numbers for each reduction function
            $positions[] = mt_rand(0, self::HASH_LAST_BYTE);
            
            for ($j = 1; $j < 4; ++$j) {
                do {
                    $ind = mt_rand(0, self::HASH_LAST_BYTE);
                    if (!in_array($ind, $positions)) { // Use only different bytes
                        $positions[] = $ind;
                        break;
                    }
                } while (true);
            }
            
            if (!in_array($positions, $reductions)) { // All reductions are different
                $reductions[] = $positions;
                ++$i;
            }
        }
        
        // Generate rainbow table
        $output->writeln('Generating rainbow table...');
        $output->writeln('This may take a while...');
        
        // Create the rainbow_6 table if it doesn't exist
        if (!$capsule->connection('db')->getSchemaBuilder()->hasTable('rainbow_6')) {
            $output->writeln('Creating rainbow_6 table...');
            $capsule->connection('db')->getSchemaBuilder()->create('rainbow_6', function ($table) {
                $table->increments('id');
                $table->string('start', 6);
                $table->string('finish', 6);
                $table->index('finish');
            });
        }
        
        // Generate chains and store them in the database
        for ($j = 1; $j <= self::AMOUNT; ++$j) {
            do {
                $start = $this->getWord(true); // Generate random word (start of chain)
                if (!in_array($start, $words)) {
                    $words[] = $start;
                    break;
                }
            } while (true);
            
            $finish = $this->getEndOfChain($start, $reductions); // Calculate end of chain
            $pairs[] = ['start' => $start, 'finish' => $finish];
            
            if ($j % 5000 == 0) {
                $output->writeln("Generated $j chains...");
                $capsule->connection('db')->table('rainbow_6')->insert($pairs);
                $pairs = [];
            }
        }
        
        // Insert any remaining pairs
        if (!empty($pairs)) {
            $capsule->connection('db')->table('rainbow_6')->insert($pairs);
        }
        
        $output->writeln('Rainbow table generation complete!');
        
        return Command::SUCCESS;
    }
    
    /**
     * Generate a random word of length 6 with uppercase, lowercase, and numeric characters
     */
    private function getWord(bool $newRandom = false): string
    {
        $lastChar = count($this->charList) - 1;
        
        if ($newRandom) {
            mt_srand();
        }
        
        $word = $this->charList[mt_rand(0, $lastChar)];
        for ($i = 1; $i < 6; ++$i) {
            $word .= $this->charList[mt_rand(0, $lastChar)];
        }
        
        return $word;
    }
    
    /**
     * Compute the end of a chain starting from a given word
     */
    private function getEndOfChain(string $word, array $reductions, int $startStep = 0, int $length = self::CHAIN_LENGTH): string
    {
        for ($i = $startStep; $i < $length; ++$i) {
            $hash = md5($word . self::SALT);
            $word = $this->reduction($hash, $i, $reductions);
        }
        
        return $word;
    }
    
    /**
     * Reduction function that converts a hash to a word
     */
    private function reduction(string $hash, int $step, array $reductions): string
    {
        $pos = $reductions[$step % self::CHAIN_LENGTH];
        
        mt_srand(ord($hash[$pos[0]]) | ord($hash[$pos[1]]) << 8 | ord($hash[$pos[2]]) << 16 | ord($hash[$pos[3]]) << 24);
        
        return $this->getWord();
    }
}