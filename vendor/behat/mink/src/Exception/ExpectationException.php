<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Exception;

use Behat\Mink\Session;

/**
 * Exception thrown for failed expectations.
 *
 * Some specialized child classes are available to customize the error rendering.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ExpectationException extends Exception
{
    private $session;

    /**
     * Initializes exception.
     *
     * @param string     $message   optional message
     * @param Session    $session   session instance
     * @param \Exception $exception expectation exception
     */
    public function __construct($message, Session $session, \Exception $exception = null)
    {
        $this->session = $session;

        if (!$message && null !== $exception) {
            $message = $exception->getMessage();
        }

        parent::__construct($message, 0, $exception);
    }

    /**
     * Returns exception message with additional context info.
     *
     * @return string
     */
    public function __toString()
    {
        try {
            $pageText = $this->pipeString($this->trimString($this->getContext())."\n");
            $string   = sprintf("%s\n\n%s%s", $this->getMessage(), $this->getResponseInfo(), $pageText);
        } catch (\Exception $e) {
            return $this->getMessage();
        }

        return $string;
    }

    /**
     * Gets the context rendered for this exception
     *
     * @return string
     */
    protected function getContext()
    {
        return $this->trimBody($this->getSession()->getPage()->getContent());
    }

    /**
     * Returns exception session.
     *
     * @return Session
     */
    protected function getSession()
    {
        return $this->session;
    }

    /**
     * Prepends every line in a string with pipe (|).
     *
     * @param string $string
     *
     * @return string
     */
    protected function pipeString($string)
    {
        return '|  '.strtr($string, array("\n" => "\n|  "));
    }

    /**
     * Removes response header/footer, letting only <body /> content.
     *
     * @param string $string response content
     *
     * @return string
     */
    protected function trimBody($string)
    {
        $string = preg_replace(array('/^.*<body>/s', '/<\/body>.*$/s'), array('<body>', '</body>'), $string);

        return $string;
    }

    /**
     * Trims string to specified number of chars.
     *
     * @param string  $string response content
     * @param integer $count  trim count
     *
     * @return string
     */
    protected function trimString($string, $count = 1000)
    {
        $string = trim($string);

        if ($count < mb_strlen($string)) {
            return mb_substr($string, 0, $count - 3).'...';
        }

        return $string;
    }

    /**
     * Returns response information string.
     *
     * @return string
     */
    protected function getResponseInfo()
    {
        $driver = basename(str_replace('\\', '/', get_class($this->session->getDriver())));

        $info = '+--[ ';
        try {
            $info .= 'HTTP/1.1 '.$this->session->getStatusCode().' | ';
        } catch (UnsupportedDriverActionException $e) {
            // Ignore the status code when not supported
        }
        $info .= $this->session->getCurrentUrl().' | '.$driver." ]\n|\n";

        return $info;
    }
}
