<?php

namespace Palshin\PswCrack\DataProvider;

/**
 * ListDataProvider generates all possible combinations of characters
 * from a given character set within specified length constraints.
 */
class ListDataProvider implements DataProviderInterface
{
    /**
     * The last character in the character list
     */
    private string $charListLastChar;

    /**
     * The current value being generated
     */
    private string $currentValue;

    /**
     * Lookup table for the next character in sequence
     * 
     * @var array<string, string>
     */
    private array $nextCharListTable = [];

    /**
     * Minimum length of generated combinations
     */
    private int $minLength;

    /**
     * Maximum length of generated combinations
     */
    private int $maxLength;

    /**
     * Character set to use for generating combinations
     */
    private string $charList;

    /**
     * Initialize the data provider with configuration parameters
     *
     * @param int $minLength Minimum length of generated combinations
     * @param int $maxLength Maximum length of generated combinations
     * @param string $charList Character set to use for generating combinations
     * 
     * @throws \InvalidArgumentException If parameters are invalid
     */
    public function __construct(
        int $minLength,
        int $maxLength,
        string $charList
    )
    {
        // Validate input parameters
        if ($minLength < 1) {
            throw new \InvalidArgumentException('Minimum length must be at least 1');
        }

        if ($maxLength < $minLength) {
            throw new \InvalidArgumentException('Maximum length must be greater than or equal to minimum length');
        }

        if (empty($charList)) {
            throw new \InvalidArgumentException('Character list cannot be empty');
        }

        $this->minLength = $minLength;
        $this->maxLength = $maxLength;
        $this->charList = $charList;

        $charListLimit = strlen($this->charList) - 1;

        // Build the next character lookup table
        for ($i = 0; $i < $charListLimit; $i++) {
            $this->nextCharListTable[$this->charList[$i]] = $this->charList[$i + 1];
        }

        $this->charListLastChar = $this->charList[$charListLimit];
        $this->currentValue = str_repeat($this->charList[0], $this->minLength);
    }

    /**
     * Generates all possible character combinations within the specified length range.
     * 
     * This is a generator function that yields each combination in sequence.
     * When all combinations have been generated, it yields null to signal completion.
     *
     * @return \Generator<string|null> A generator that yields each combination or null when finished
     */
    public function generate(): \Generator
    {
        while (strlen($this->currentValue) <= $this->maxLength) {
            yield $this->currentValue;
            $this->persistIncrement();
        }

        yield null;
    }

    /**
     * Updates the current value by incrementing it to the next combination.
     *
     * @return string The new current value after incrementing
     */
    private function persistIncrement(): string
    {
        return $this->currentValue = $this->increment();
    }

    /**
     * Increments the current value to the next combination in the sequence.
     * 
     * This method works by examining characters from right to left.
     * When a character that isn't the last in the character list is found, it's replaced
     * with the next character in sequence, and all characters to its right are reset to
     * the first character in the list.
     * 
     * If all characters are the last in the list, the length is increased by one.
     *
     * @return string The incremented value
     */
    private function increment(): string
    {
        $valueLength = strlen($this->currentValue);

        // Iterate through the characters from right to left
        for ($charPos = -1; $charPos >= -$valueLength; $charPos--) {
            $currentChar = substr($this->currentValue, $charPos, 1);

            // If the current character is not the last in our character list
            if ($this->charListLastChar !== $currentChar) {
                // Replace current character with the next one in sequence
                $nextChar = $this->nextCharListTable[$currentChar];
                $newValue = substr_replace($this->currentValue, $nextChar, $charPos, 1);

                // Reset all characters to the right of the current position
                $prefixLength = $valueLength + $charPos + 1;
                $suffixLength = -$charPos - 1;

                return substr($newValue, 0, $prefixLength) . str_repeat($this->charList[0], $suffixLength);
            }
        }

        // If we've checked all characters and they're all the last in our list,
        // increase the length by 1 and reset all characters
        return str_repeat($this->charList[0], $valueLength + 1);
    }

    /**
     * Gets the current value being generated
     *
     * @return string The current value
     */
    public function getCurrentValue(): string
    {
        return $this->currentValue;
    }

    /**
     * Gets the minimum length setting
     *
     * @return int The minimum length
     */
    public function getMinLength(): int
    {
        return $this->minLength;
    }

    /**
     * Gets the maximum length setting
     *
     * @return int The maximum length
     */
    public function getMaxLength(): int
    {
        return $this->maxLength;
    }

    /**
     * Gets the character list being used
     *
     * @return string The character list
     */
    public function getCharList(): string
    {
        return $this->charList;
    }

    /**
     * Sets the character list
     *
     * @param string $value The character list to use
     * @return self
     * @throws \InvalidArgumentException If the character list is empty
     */
    public function setCharList(string $value): self
    {
        if (empty($value)) {
            throw new \InvalidArgumentException('Character list cannot be empty');
        }

        $this->charList = $value;

        // Rebuild the next character lookup table
        $charListLimit = strlen($this->charList) - 1;
        $this->nextCharListTable = [];

        for ($i = 0; $i < $charListLimit; $i++) {
            $this->nextCharListTable[$this->charList[$i]] = $this->charList[$i + 1];
        }

        $this->charListLastChar = $this->charList[$charListLimit];

        return $this;
    }

    /**
     * Gets the last character in the character list
     *
     * @return string The last character
     */
    public function getCharListLastChar(): string
    {
        return $this->charListLastChar;
    }

    /**
     * Sets the last character in the character list
     *
     * @param string $value The last character
     * @return self
     */
    public function setCharListLastChar(string $value): self
    {
        $this->charListLastChar = $value;
        return $this;
    }

    /**
     * Gets the next character lookup table
     *
     * @return array The next character lookup table
     */
    public function getNextCharListTable(): array
    {
        return $this->nextCharListTable;
    }

    /**
     * Sets the next character lookup table
     *
     * @param array $value The next character lookup table
     * @return self
     */
    public function setNextCharListTable(array $value): self
    {
        $this->nextCharListTable = $value;
        return $this;
    }

    /**
     * Gets the current state of the generator
     * 
     * This can be used to save the state and resume generation later
     *
     * @return array The current state
     */
    public function getState(): array
    {
        return [
            'currentValue' => $this->currentValue,
            'minLength' => $this->minLength,
            'maxLength' => $this->maxLength,
            'charList' => $this->charList
        ];
    }

    /**
     * Sets the current value to a specific string
     * 
     * This can be used to resume generation from a specific point
     *
     * @param string $value The value to set
     * @return self
     * @throws \InvalidArgumentException If the value is invalid
     */
    public function setCurrentValue(string $value): self
    {
        $valueLength = strlen($value);

        if ($valueLength < $this->minLength || $valueLength > $this->maxLength) {
            throw new \InvalidArgumentException(
                "Value length must be between {$this->minLength} and {$this->maxLength}"
            );
        }

        // Validate that the value only contains characters from the charList
        for ($i = 0; $i < $valueLength; $i++) {
            if (strpos($this->charList, $value[$i]) === false) {
                throw new \InvalidArgumentException(
                    "Value contains invalid characters not in the character list"
                );
            }
        }

        $this->currentValue = $value;
        return $this;
    }

    public function resetCurrentValue(): self
    {
        $this->currentValue = str_repeat($this->charList[0], $this->minLength);

        return $this;
    }
}
