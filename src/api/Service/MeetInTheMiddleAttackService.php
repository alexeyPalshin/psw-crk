<?php

namespace Palshin\PswCrack\Service;

use Palshin\PswCrack\DataProvider\DataProviderInterface;

/**
 * MeetInTheMiddleAttackService implements a meet-in-the-middle attack for MD5 hashes.
 * 
 * This attack works by splitting the search space into two halves, computing all possible
 * values for each half, and finding matches between them. This can significantly reduce
 * the time needed to find a match compared to brute force.
 */
class MeetInTheMiddleAttackService
{
    public static array $firstHalfTable = [];

    /**
     * The salt used for hashing
     */
    private string $salt;

    /**
     * The file service for writing results
     */
    private WriteFileService $fileService;

    /**
     * Constructor
     *
     * @param string $salt The salt to use for hashing
     * @param WriteFileService|null $fileService Optional file service for writing results
     */
    public function __construct(string $salt = 'ThisIs-A-Salt123', ?WriteFileService $fileService = null)
    {
        $this->salt = $salt;

        if ($fileService) {
            $this->fileService = $fileService;
        }
    }

    /**
     * Set the file service for writing results
     *
     * @param WriteFileService $fileService The file service
     * @return self
     */
    public function setFileService(WriteFileService $fileService): self
    {
        $this->fileService = $fileService;
        return $this;
    }

    /**
     * Attack a hash using the meet-in-the-middle approach
     *
     * @param string $hash The hash to attack
     * @param DataProviderInterface $firstHalfProvider Data provider for the first half
     * @param DataProviderInterface $secondHalfProvider Data provider for the second half
     * @param string $intermediateFunction Optional function to apply between halves
     * @return string|null The cracked password or null if not found
     */
    public function attack(
        string $hash,
        DataProviderInterface $firstHalfProvider,
        DataProviderInterface $secondHalfProvider,
        string $intermediateFunction = 'md5'
    ): ?string {

        // Step 1: Generate all possible values for the first half and store their hashes
        if (!self::$firstHalfTable) {
            foreach ($firstHalfProvider->generate() as $firstHalf) {
                if ($firstHalf === null) {
                    break;
                }

                // Store the hash of the first half as the key for quick lookup
                $firstHalfHash = $intermediateFunction($firstHalf);
                self::$firstHalfTable[$firstHalfHash] = $firstHalf;
            }
        }


        // Step 2: Generate all possible values for the second half and check for matches

        $secondHalfProvider->resetCurrentValue();
        foreach ($secondHalfProvider->generate() as $secondHalf) {
            if ($secondHalf === null) {
                break;
            }

            // For each second half value, check if combining with any first half value produces the target hash
            foreach (self::$firstHalfTable as $firstHalfHash => $firstHalf) {
                $candidate = $firstHalf . $secondHalf;
                $candidateHash = md5($candidate . $this->salt);

                if ($candidateHash === $hash) {
                    // Found a match
                    $this->logResult($hash, $candidate);
                    return $candidate;
                }
            }
        }

        return null;
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
     * @param string $intermediateFunction Optional function to apply between halves
     * @return array Array of cracked passwords (hash => password)
     */
    public function attackMultiple(
        $hashes,
        $firstHalfTable,
        $secondHalfTable,
        string $intermediateFunction = 'md5'
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

        // Precompute salt concatenation to avoid doing it repeatedly
        $salt = $this->salt;

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
                            $localHashCache[$candidate] = md5($candidate . $salt);
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
                                break 3;
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
     * Optimized attack that uses memory more efficiently by processing chunks of the first half
     *
     * @param string $hash The hash to attack
     * @param DataProviderInterface $firstHalfProvider Data provider for the first half
     * @param DataProviderInterface $secondHalfProvider Data provider for the second half
     * @param int $chunkSize Size of chunks to process at once
     * @param string $intermediateFunction Optional function to apply between halves
     * @return string|null The cracked password or null if not found
     */
    public function attackOptimized(
        string $hash, 
        DataProviderInterface $firstHalfProvider, 
        DataProviderInterface $secondHalfProvider,
        int $chunkSize = 10000,
        string $intermediateFunction = 'md5'
    ): ?string {
        $firstHalfValues = [];
        $count = 0;

        // Process the first half in chunks
        foreach ($firstHalfProvider->generate() as $firstHalf) {
            if ($firstHalf === null) {
                break;
            }

            $firstHalfValues[] = $firstHalf;
            $count++;

            if ($count >= $chunkSize) {
                // Process this chunk
                $result = $this->processChunk($hash, $firstHalfValues, $secondHalfProvider, $intermediateFunction);
                if ($result !== null) {
                    return $result;
                }

                // Reset for next chunk
                $firstHalfValues = [];
                $count = 0;
            }
        }

        // Process any remaining values
        if (!empty($firstHalfValues)) {
            return $this->processChunk($hash, $firstHalfValues, $secondHalfProvider, $intermediateFunction);
        }

        return null;
    }

    /**
     * Process a chunk of first half values against all second half values
     *
     * @param string $hash The hash to attack
     * @param array $firstHalfValues Array of first half values
     * @param DataProviderInterface $secondHalfProvider Data provider for the second half
     * @param string $intermediateFunction Function to apply between halves
     * @return string|null The cracked password or null if not found
     */
    private function processChunk(
        string $hash, 
        array $firstHalfValues, 
        DataProviderInterface $secondHalfProvider,
        string $intermediateFunction
    ): ?string {
        // Build lookup table for this chunk
        $firstHalfTable = [];
        foreach ($firstHalfValues as $firstHalf) {
            $firstHalfHash = $intermediateFunction($firstHalf);
            $firstHalfTable[$firstHalfHash] = $firstHalf;
        }

        // Check against all second half values
        foreach ($secondHalfProvider->generate() as $secondHalf) {
            if ($secondHalf === null) {
                break;
            }

            foreach ($firstHalfTable as $firstHalfHash => $firstHalf) {
                $candidate = $firstHalf . $secondHalf;
                $candidateHash = md5($candidate . $this->salt);

                if ($candidateHash === $hash) {
                    $this->logResult($hash, $candidate);
                    return $candidate;
                }
            }
        }

        return null;
    }

    /**
     * Log a result to the file service if available
     *
     * @param string $hash The hash that was cracked
     * @param string $password The password that was found
     */
    private function logResult(string $hash, string $password): void
    {
        if (isset($this->fileService)) {
            $this->fileService->writeLine(sprintf("Cracked hash: %s, Password: %s", $hash, $password));
        }
    }
}
