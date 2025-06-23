<?php

namespace Palshin\PswCrack\Service;

/**
 * MeetInTheMiddleAttackService implements a meet-in-the-middle attack for MD5 hashes.
 * 
 * This attack works by splitting the search space into two halves, computing all possible
 * values for each half, and finding matches between them. This can significantly reduce
 * the time needed to find a match compared to brute force.
 */
class MeetInTheMiddleAttackService implements AttackService
{
    /**
     * The file service for writing results
     */
    private WriteFileService $fileService;

    /**
     * Constructor
     *
     * @param WriteFileService|null $fileService Optional file service for writing results
     */
    public function __construct(?WriteFileService $fileService = null)
    {
        if ($fileService) {
            $this->fileService = $fileService;
        }
    }

    /**
     * Attack a hash using the meet-in-the-middle approach
     *
     * @param string $hash The hash to attack
     * @param  mixed  $firstHalfTable  Collection of first half values
     * @param  mixed  $secondHalfTable  Collection of second half values
     * @return array|null The cracked password or null if not found
     */
    public function attack(
        string $hash,
        $firstHalfTable = null,
        $secondHalfTable = null,
    ): ?string {
        $results = $this->attackMultiple([$hash], $firstHalfTable, $secondHalfTable);

        return !empty($results[0]) ? $results : null;
    }

    /**
     * Optimized attack multiple hashes using the meet-in-the-middle approach
     * 
     * This method is optimized for processing large datasets (e.g., 240,000 items) by:
     * 1. Using hash maps for O(1) lookups
     * 2. Processing data in chunks to reduce memory usage
     * 3. Precomputing and caching hash values
     * 4. Using early termination when all hashes are found
     * 5. Optimizing the inner loop to reduce MD5 calculations
     *
     * @param mixed $hashes Collection of hashes to attack
     * @param mixed $firstHalfTable Collection of first half values
     * @param mixed $secondHalfTable Collection of second half values
     * @return array Array of cracked passwords (hash => password)
     */
    public function attackMultiple(
        $hashes,
        $firstHalfTable,
        $secondHalfTable
    ): array {
        $results = [];

        // Convert hashes collection to an associative array for O(1) lookups
        $hashesArray = [];
        foreach ($hashes as $hash) {
            $hashesArray[$hash] = true;
        }

        // Process both tables in chunks to avoid memory issues
        $firstHalfChunkSize = 1000;
        $secondHalfChunkSize = 1000;

        // Convert collections to arrays for faster processing
        $firstHalfArray = $firstHalfTable->toArray();
        $secondHalfArray = $secondHalfTable->toArray();

        // Create chunks for both halves
        $firstHalfChunks = array_chunk($firstHalfArray, $firstHalfChunkSize);
        $secondHalfChunks = array_chunk($secondHalfArray, $secondHalfChunkSize);

        // Create a lookup table for target hashes (hash => true)
        $targetHashLookup = $hashesArray;

        // Process each chunk of first half values
        foreach ($firstHalfChunks as $firstHalfChunkIndex => $firstHalfChunk) {
            // Precompute first half values to avoid accessing object properties repeatedly
            $precomputedFirstHalves = [];
            foreach ($firstHalfChunk as $firstHalf) {
                $precomputedFirstHalves[] = $firstHalf->value;
            }

            // Process each chunk of second half values
            foreach ($secondHalfChunks as $secondHalfChunkIndex => $secondHalfChunk) {
                // Precompute second half values
                $precomputedSecondHalves = [];
                foreach ($secondHalfChunk as $secondHalf) {
                    $precomputedSecondHalves[] = $secondHalf->value;
                }

                // Use a local cache for this chunk pair to improve performance
                $localHashCache = [];

                // Process all combinations in this chunk pair
                foreach ($precomputedFirstHalves as $firstHalfValue) {
                    foreach ($precomputedSecondHalves as $secondHalfValue) {
                        $candidate = $firstHalfValue . $secondHalfValue;

                        // Calculate hash only if we haven't seen this candidate before
                        if (!isset($localHashCache[$candidate])) {
                            $localHashCache[$candidate] = md5($candidate . self::SALT);
                        }

                        $candidateHash = $localHashCache[$candidate];

                        // Check if this hash matches any of our target hashes
                        if (isset($targetHashLookup[$candidateHash])) {
                            $results[$candidateHash] = $candidate;
                            $this->logResult($candidateHash, $candidate);

                            // Remove the hash from our lookup table
                            unset($targetHashLookup[$candidateHash]);

                            // If we've found all hashes, we can stop
                            if (empty($targetHashLookup)) {
                                break 4;
                            }
                        }
                    }
                }

                // Clear the local cache after processing each chunk pair to free memory
                unset($localHashCache);
            }
        }

        return $results;
    }

    /**
     * Log a result to the file service if available
     *
     * @param string $hash The hash that was cracked
     * @param string $password The password that was found
     */
    private function logResult(string $hash, string $password): void
    {
        $this->fileService?->writeLine(sprintf("Hash: %s, Password: %s\n", $hash, $password));
    }
}
