<?php

namespace Palshin\PswCrack\DataProvider;

interface DataProviderInterface
{
    /**
     * Get the last character in the character list
     * 
     * @return string The last character
     */
    public function getCharListLastChar(): string;

    /**
     * Set the last character in the character list
     * 
     * @param string $value The last character
     * @return self
     */
    public function setCharListLastChar(string $value): self;

    /**
     * Get the character list
     * 
     * @return string The character list
     */
    public function getCharList(): string;

    /**
     * Set the character list
     * 
     * @param string $value The character list
     * @return self
     */
    public function setCharList(string $value): self;

    /**
     * Get the next character lookup table
     * 
     * @return array The next character lookup table
     */
    public function getNextCharListTable(): array;

    /**
     * Set the next character lookup table
     * 
     * @param array $value The next character lookup table
     * @return self
     */
    public function setNextCharListTable(array $value): self;

    /**
     * @return mixed[]|\Iterator|\Generator Then current generated value
     *
     * It must return null when generation is finish
     */
    public function generate();
}
