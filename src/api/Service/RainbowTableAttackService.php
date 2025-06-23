<?php

namespace Palshin\PswCrack\Service;

use Illuminate\Database\Capsule\Manager as Capsule;

class RainbowTableAttackService implements AttackService
{
    const CHAIN_LENGTH = 2400;
    const HASH_LAST_BYTE = 15;
    
    private $capsule;
    private $reductions = [];
    private $fileService;
    
    public function __construct(Capsule $capsule, ?WriteFileService $fileService = null)
    {
        $this->capsule = $capsule;
        $this->fileService = $fileService;
        
        // Initialize reduction functions
        $this->initializeReductions();
    }
    
    /**
     * Initialize the reduction functions array
     */
    private function initializeReductions()
    {
        // Seeds the random number generator with chain length
        mt_srand(self::CHAIN_LENGTH);
        
        $i = 0;
        while ($i < self::CHAIN_LENGTH) {
            // Contains byte hash number for particular reduction function
            $positions = [];
            $positions[] = mt_rand(0, self::HASH_LAST_BYTE);
            
            for ($j = 1; $j < 4; ++$j) {
                do {
                    $ind = mt_rand(0, self::HASH_LAST_BYTE);
                    if (!in_array($ind, $positions, true)) {
                        $positions[] = $ind;
                        break;
                    }
                } while (true);
            }
            
            if (!in_array($positions, $this->reductions, true)) {
                $this->reductions[] = $positions;
                ++$i;
            }
        }
    }
    
    /**
     * Crack a single MD5 hash using rainbow table
     */
    public function attack(string $hash, $firstHalfTable = null, $secondHalfTable = null): ?string
    {
        // Get all possible words in the chain for this hash
        $words = $this->getWordsInChain($hash);
        
        // Find matching pairs in the rainbow table
        $pairs = $this->capsule->connection('db')
            ->table('rainbow_6')
            ->whereIn('finish', $words)
            ->get();
        
        // Check each pair to find the original password
        foreach ($pairs as $pair) {
            $steps = array_search($pair->finish, $words, true);
            $word = $this->getEndOfChain($pair->start, 0, $steps);
            
            // Verify if this is the correct password
            if (md5($word . self::SALT) === $hash) {
                // Log the result if file service is available
                if ($this->fileService) {
                    $this->logResult($hash, $word);
                }
                
                return $word;
            }
        }
        
        return null;
    }
    
    /**
     * Crack multiple MD5 hashes using rainbow table
     */
    public function crackMultiple(array $hashes): array
    {
        $results = [];
        
        foreach ($hashes as $hash) {
            $result = $this->attack($hash);
            if ($result) {
                $results[$hash] = $result;
            }
        }
        
        return $results;
    }
    
    /**
     * Generate a random word of length 6 with uppercase, lowercase, and numeric characters
     */
    private function getWord(bool $newRandom = false): string
    {
        $alphabet = array_merge(range('a', 'z'), range('A', 'Z'), range('0', '9'));
        $lastChar = count($alphabet) - 1;
        
        if ($newRandom) {
            mt_srand();
        }
        
        $word = $alphabet[mt_rand(0, $lastChar)];
        for ($i = 1; $i < 6; ++$i) {
            $word .= $alphabet[mt_rand(0, $lastChar)];
        }
        
        return $word;
    }
    
    /**
     * Compute the end of a chain starting from a given word
     */
    private function getEndOfChain(string $word, int $startStep = 0, int $length = self::CHAIN_LENGTH): string
    {
        for ($i = $startStep; $i < $length; ++$i) {
            $hash = md5($word . self::SALT);
            $word = $this->reduction($hash, $i);
        }
        
        return $word;
    }
    
    /**
     * Reduction function that converts a hash to a word
     */
    private function reduction(string $hash, int $step): string
    {
        $pos = $this->reductions[$step % self::CHAIN_LENGTH];
        
        mt_srand(ord($hash[$pos[0]]) | ord($hash[$pos[1]]) << 8 | ord($hash[$pos[2]]) << 16 | ord($hash[$pos[3]]) << 24);
        
        return $this->getWord();
    }
    
    /**
     * Get all possible words in a chain for a given hash
     */
    private function getWordsInChain(string $hash): array
    {
        $words = [];
        
        for ($i = 0; $i < self::CHAIN_LENGTH; ++$i) {
            $wordStart = $this->reduction($hash, $i);
            $wordEnd = $this->getEndOfChain($wordStart, $i + 1);
            $words[] = $wordEnd;
        }
        
        return $words;
    }

    /**
     * Log a result to the file service if available
     *
     * @param string $hash The hash that was cracked
     * @param string $password The password that was found
     */
    private function logResult(string $hash, string $password): void
    {
        $this->fileService?->write(sprintf("Hash: %s, Password: %s\n", $hash, $password));
    }
}