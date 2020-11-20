<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Exception;

use Behat\Mink\Driver\DriverInterface;
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
    private $driver;

    /**
     * Initializes exception.
     *
     * @param string                  $message   optional message
     * @param DriverInterface|Session $driver    driver instance (or session for BC)
     * @param \Exception|null         $exception expectation exception
     */
    public function __construct($message, $driver, \Exception $exception = null)
    {
        if ($driver instanceof Session) {
            @trigger_error('Passing a Session object to the ExpectationException constructor is deprecated as of Mink 1.7. Pass the driver instead.', E_USER_DEPRECATED);

            $this->session = $driver;
            $this->driver = $driver->getDriver();
        } elseif (!$driver instanceof DriverInterface) {
            // Trigger an exception as we cannot typehint a disjunction
            throw new \InvalidArgumentException('The ExpectationException constructor expects a DriverInterface or a Session.');
        } else {
            $this->driver = $driver;
        }

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
            $string = sprintf("%s\n\n%s%s", $this->getMessage(), $this->getResponseInfo(), $pageText);
        } catch (\Exception $e) {
            return $this->getMessage();
        }

        return $string;
    }

    /**
     * Gets the context rendered for this exception.
     *
     * @return string
     */
    protected function getContext()
    {
        return $this->trimBody($this->driver->getContent());
    }

    /**
     * Returns driver.
     *
     * @return DriverInterface
     */
    protected function getDriver()
    {
        return $this->driver;
    }

    /**
     * Returns exception session.
     *
     * @return Session
     *
     * @deprecated since 1.7, to be removed in 2.0. Use getDriver and the driver API instead.
     */
    protected function getSession()
    {
        if (null === $this->session) {
            throw new \LogicException(sprintf('The deprecated method %s cannot be used when passing a driver in the constructor', __METHOD__));
        }

        @trigger_error(sprintf('The method %s is deprecated as of Mink 1.7 and will be removed in 2.0. Use getDriver and the driver API instead.', __METHOD__), E_USER_DEPRECATED);

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
     * @param string $string response content
     * @param int    $count  trim count
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
        $driver = basename(str_replace('\\', '/', get_class($this->driver)));

        $info = '+--[ ';
        try {
            $info .= 'HTTP/1.1 '.$this->driver->getStatusCode().' | ';
        } catch (UnsupportedDriverActionException $e) {
            // Ignore the status code when not supported
        }
        $info .= $this->driver->getCurrentUrl().' | '.$driver." ]\n|\n";

        return $info;
    }
}
