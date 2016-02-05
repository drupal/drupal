<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink;

use Behat\Mink\Driver\DriverInterface;
use Behat\Mink\Selector\SelectorsHandler;
use Behat\Mink\Element\DocumentElement;

/**
 * Mink session.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Session
{
    private $driver;
    private $page;
    private $selectorsHandler;

    /**
     * Initializes session.
     *
     * @param DriverInterface  $driver
     * @param SelectorsHandler $selectorsHandler
     */
    public function __construct(DriverInterface $driver, SelectorsHandler $selectorsHandler = null)
    {
        $driver->setSession($this);

        if (null === $selectorsHandler) {
            $selectorsHandler = new SelectorsHandler();
        }

        $this->driver = $driver;
        $this->selectorsHandler = $selectorsHandler;
        $this->page = new DocumentElement($this);
    }

    /**
     * Checks whether session (driver) was started.
     *
     * @return Boolean
     */
    public function isStarted()
    {
        return $this->driver->isStarted();
    }

    /**
     * Starts session driver.
     *
     * Calling any action before visiting a page is an undefined behavior.
     * The only supported method calls on a fresh driver are
     * - visit()
     * - setRequestHeader()
     * - setBasicAuth()
     * - reset()
     * - stop()
     */
    public function start()
    {
        $this->driver->start();
    }

    /**
     * Stops session driver.
     */
    public function stop()
    {
        $this->driver->stop();
    }

    /**
     * Restart session driver.
     */
    public function restart()
    {
        $this->driver->stop();
        $this->driver->start();
    }

    /**
     * Reset session driver state.
     *
     * Calling any action before visiting a page is an undefined behavior.
     * The only supported method calls on a fresh driver are
     * - visit()
     * - setRequestHeader()
     * - setBasicAuth()
     * - reset()
     * - stop()
     */
    public function reset()
    {
        $this->driver->reset();
    }

    /**
     * Returns session driver.
     *
     * @return DriverInterface
     */
    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Returns page element.
     *
     * @return DocumentElement
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Returns selectors handler.
     *
     * @return SelectorsHandler
     */
    public function getSelectorsHandler()
    {
        return $this->selectorsHandler;
    }

    /**
     * Visit specified URL.
     *
     * @param string $url url of the page
     */
    public function visit($url)
    {
        $this->driver->visit($url);
    }

    /**
     * Sets HTTP Basic authentication parameters.
     *
     * @param string|Boolean $user     user name or false to disable authentication
     * @param string         $password password
     */
    public function setBasicAuth($user, $password = '')
    {
        $this->driver->setBasicAuth($user, $password);
    }

    /**
     * Sets specific request header.
     *
     * @param string $name
     * @param string $value
     */
    public function setRequestHeader($name, $value)
    {
        $this->driver->setRequestHeader($name, $value);
    }

    /**
     * Returns all response headers.
     *
     * @return array
     */
    public function getResponseHeaders()
    {
        return $this->driver->getResponseHeaders();
    }

    /**
     * Returns specific response header.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getResponseHeader($name)
    {
        $headers = $this->driver->getResponseHeaders();

        $name = strtolower($name);
        $headers = array_change_key_case($headers, CASE_LOWER);

        if (!isset($headers[$name])) {
            return null;
        }

        return is_array($headers[$name]) ? $headers[$name][0] : $headers[$name];
    }

    /**
     * Sets cookie.
     *
     * @param string $name
     * @param string $value
     */
    public function setCookie($name, $value = null)
    {
        $this->driver->setCookie($name, $value);
    }

    /**
     * Returns cookie by name.
     *
     * @param string $name
     *
     * @return string|null
     */
    public function getCookie($name)
    {
        return $this->driver->getCookie($name);
    }

    /**
     * Returns response status code.
     *
     * @return int
     */
    public function getStatusCode()
    {
        return $this->driver->getStatusCode();
    }

    /**
     * Returns current URL address.
     *
     * @return string
     */
    public function getCurrentUrl()
    {
        return $this->driver->getCurrentUrl();
    }

    /**
     * Capture a screenshot of the current window.
     *
     * @return string screenshot of MIME type image/* depending
     *                on driver (e.g., image/png, image/jpeg)
     */
    public function getScreenshot()
    {
        return $this->driver->getScreenshot();
    }

    /**
     * Return the names of all open windows.
     *
     * @return array Array of all open window's names.
     */
    public function getWindowNames()
    {
        return $this->driver->getWindowNames();
    }

    /**
     * Return the name of the currently active window.
     *
     * @return string The name of the current window.
     */
    public function getWindowName()
    {
        return $this->driver->getWindowName();
    }

    /**
     * Reloads current session page.
     */
    public function reload()
    {
        $this->driver->reload();
    }

    /**
     * Moves backward 1 page in history.
     */
    public function back()
    {
        $this->driver->back();
    }

    /**
     * Moves forward 1 page in history.
     */
    public function forward()
    {
        $this->driver->forward();
    }

    /**
     * Switches to specific browser window.
     *
     * @param string $name window name (null for switching back to main window)
     */
    public function switchToWindow($name = null)
    {
        $this->driver->switchToWindow($name);
    }

    /**
     * Switches to specific iFrame.
     *
     * @param string $name iframe name (null for switching back)
     */
    public function switchToIFrame($name = null)
    {
        $this->driver->switchToIFrame($name);
    }

    /**
     * Execute JS in browser.
     *
     * @param string $script javascript
     */
    public function executeScript($script)
    {
        $this->driver->executeScript($script);
    }

    /**
     * Execute JS in browser and return it's response.
     *
     * @param string $script javascript
     *
     * @return string
     */
    public function evaluateScript($script)
    {
        return $this->driver->evaluateScript($script);
    }

    /**
     * Waits some time or until JS condition turns true.
     *
     * @param int    $time      time in milliseconds
     * @param string $condition JS condition
     *
     * @return bool
     */
    public function wait($time, $condition = 'false')
    {
        return $this->driver->wait($time, $condition);
    }

    /**
     * Set the dimensions of the window.
     *
     * @param int    $width  set the window width, measured in pixels
     * @param int    $height set the window height, measured in pixels
     * @param string $name   window name (null for the main window)
     */
    public function resizeWindow($width, $height, $name = null)
    {
        $this->driver->resizeWindow($width, $height, $name);
    }

    /**
     * Maximize the window if it is not maximized already.
     *
     * @param string $name window name (null for the main window)
     */
    public function maximizeWindow($name = null)
    {
        $this->driver->maximizeWindow($name);
    }
}
