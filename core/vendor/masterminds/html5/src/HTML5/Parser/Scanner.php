<?php
namespace Masterminds\HTML5\Parser;

/**
 * The scanner.
 *
 * This scans over an input stream.
 */
class Scanner
{

    const CHARS_HEX = 'abcdefABCDEF01234567890';

    const CHARS_ALNUM = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ01234567890';

    const CHARS_ALPHA = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';

    protected $is;

    // Flipping this to true will give minisculely more debugging info.
    public $debug = false;

    /**
     * Create a new Scanner.
     *
     * @param \Masterminds\HTML5\Parser\InputStream $input
     *            An InputStream to be scanned.
     */
    public function __construct($input)
    {
        $this->is = $input;
    }

    /**
     * Get the current position.
     *
     * @return int The current intiger byte position.
     */
    public function position()
    {
        return $this->is->key();
    }

    /**
     * Take a peek at the next character in the data.
     *
     * @return string The next character.
     */
    public function peek()
    {
        return $this->is->peek();
    }

    /**
     * Get the next character.
     *
     * Note: This advances the pointer.
     *
     * @return string The next character.
     */
    public function next()
    {
        $this->is->next();
        if ($this->is->valid()) {
            if ($this->debug)
                fprintf(STDOUT, "> %s\n", $this->is->current());
            return $this->is->current();
        }

        return false;
    }

    /**
     * Get the current character.
     *
     * Note, this does not advance the pointer.
     *
     * @return string The current character.
     */
    public function current()
    {
        if ($this->is->valid()) {
            return $this->is->current();
        }

        return false;
    }

    /**
     * Silently consume N chars.
     */
    public function consume($count = 1)
    {
        for ($i = 0; $i < $count; ++ $i) {
            $this->next();
        }
    }

    /**
     * Unconsume some of the data.
     * This moves the data pointer backwards.
     *
     * @param int $howMany
     *            The number of characters to move the pointer back.
     */
    public function unconsume($howMany = 1)
    {
        $this->is->unconsume($howMany);
    }

    /**
     * Get the next group of that contains hex characters.
     *
     * Note, along with getting the characters the pointer in the data will be
     * moved as well.
     *
     * @return string The next group that is hex characters.
     */
    public function getHex()
    {
        return $this->is->charsWhile(static::CHARS_HEX);
    }

    /**
     * Get the next group of characters that are ASCII Alpha characters.
     *
     * Note, along with getting the characters the pointer in the data will be
     * moved as well.
     *
     * @return string The next group of ASCII alpha characters.
     */
    public function getAsciiAlpha()
    {
        return $this->is->charsWhile(static::CHARS_ALPHA);
    }

    /**
     * Get the next group of characters that are ASCII Alpha characters and numbers.
     *
     * Note, along with getting the characters the pointer in the data will be
     * moved as well.
     *
     * @return string The next group of ASCII alpha characters and numbers.
     */
    public function getAsciiAlphaNum()
    {
        return $this->is->charsWhile(static::CHARS_ALNUM);
    }

    /**
     * Get the next group of numbers.
     *
     * Note, along with getting the characters the pointer in the data will be
     * moved as well.
     *
     * @return string The next group of numbers.
     */
    public function getNumeric()
    {
        return $this->is->charsWhile('0123456789');
    }

    /**
     * Consume whitespace.
     *
     * Whitespace in HTML5 is: formfeed, tab, newline, space.
     */
    public function whitespace()
    {
        return $this->is->charsWhile("\n\t\f ");
    }

    /**
     * Returns the current line that is being consumed.
     *
     * @return int The current line number.
     */
    public function currentLine()
    {
        return $this->is->currentLine();
    }

    /**
     * Read chars until something in the mask is encountered.
     */
    public function charsUntil($mask)
    {
        return $this->is->charsUntil($mask);
    }

    /**
     * Read chars as long as the mask matches.
     */
    public function charsWhile($mask)
    {
        return $this->is->charsWhile($mask);
    }

    /**
     * Returns the current column of the current line that the tokenizer is at.
     *
     * Newlines are column 0. The first char after a newline is column 1.
     *
     * @return int The column number.
     */
    public function columnOffset()
    {
        return $this->is->columnOffset();
    }

    /**
     * Get all characters until EOF.
     *
     * This consumes characters until the EOF.
     *
     * @return int The number of characters remaining.
     */
    public function remainingChars()
    {
        return $this->is->remainingChars();
    }
}
