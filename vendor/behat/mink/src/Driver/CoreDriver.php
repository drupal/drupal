<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Driver;

use Behat\Mink\Exception\UnsupportedDriverActionException;
use Behat\Mink\Session;

/**
 * Core driver.
 * All other drivers should extend this class for future compatibility.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
abstract class CoreDriver implements DriverInterface
{
    /**
     * {@inheritdoc}
     */
    public function setSession(Session $session)
    {
        throw new UnsupportedDriverActionException('Setting the session is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function start()
    {
        throw new UnsupportedDriverActionException('Starting the driver is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function isStarted()
    {
        throw new UnsupportedDriverActionException('Checking the driver state is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function stop()
    {
        throw new UnsupportedDriverActionException('Stopping the driver is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        throw new UnsupportedDriverActionException('Resetting the driver is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function visit($url)
    {
        throw new UnsupportedDriverActionException('Visiting an url is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getCurrentUrl()
    {
        throw new UnsupportedDriverActionException('Getting the current url is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getContent()
    {
        throw new UnsupportedDriverActionException('Getting the page content is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function find($xpath)
    {
        throw new UnsupportedDriverActionException('Finding elements is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getTagName($xpath)
    {
        throw new UnsupportedDriverActionException('Getting the tag name is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getText($xpath)
    {
        throw new UnsupportedDriverActionException('Getting the element text is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getHtml($xpath)
    {
        throw new UnsupportedDriverActionException('Getting the element inner HTML is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getOuterHtml($xpath)
    {
        throw new UnsupportedDriverActionException('Getting the element outer HTML is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getAttribute($xpath, $name)
    {
        throw new UnsupportedDriverActionException('Getting the element attribute is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getValue($xpath)
    {
        throw new UnsupportedDriverActionException('Getting the field value is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function setValue($xpath, $value)
    {
        throw new UnsupportedDriverActionException('Setting the field value is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function check($xpath)
    {
        throw new UnsupportedDriverActionException('Checking a checkbox is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function uncheck($xpath)
    {
        throw new UnsupportedDriverActionException('Unchecking a checkbox is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function isChecked($xpath)
    {
        throw new UnsupportedDriverActionException('Getting the state of a checkbox is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function selectOption($xpath, $value, $multiple = false)
    {
        throw new UnsupportedDriverActionException('Selecting an option is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function click($xpath)
    {
        throw new UnsupportedDriverActionException('Clicking on an element is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function attachFile($xpath, $path)
    {
        throw new UnsupportedDriverActionException('Attaching a file in an input is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function reload()
    {
        throw new UnsupportedDriverActionException('Page reloading is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function forward()
    {
        throw new UnsupportedDriverActionException('Forward action is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function back()
    {
        throw new UnsupportedDriverActionException('Backward action is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function setBasicAuth($user, $password)
    {
        throw new UnsupportedDriverActionException('Basic auth setup is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToWindow($name = null)
    {
        throw new UnsupportedDriverActionException('Windows management is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function switchToIFrame($name = null)
    {
        throw new UnsupportedDriverActionException('iFrames management is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function setRequestHeader($name, $value)
    {
        throw new UnsupportedDriverActionException('Request headers manipulation is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getResponseHeaders()
    {
        throw new UnsupportedDriverActionException('Response headers are not available from %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function setCookie($name, $value = null)
    {
        throw new UnsupportedDriverActionException('Cookies manipulation is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getCookie($name)
    {
        throw new UnsupportedDriverActionException('Cookies are not available from %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getStatusCode()
    {
        throw new UnsupportedDriverActionException('Status code is not available from %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getScreenshot()
    {
        throw new UnsupportedDriverActionException('Screenshots are not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowNames()
    {
        throw new UnsupportedDriverActionException('Listing all window names is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function getWindowName()
    {
        throw new UnsupportedDriverActionException('Listing this window name is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function doubleClick($xpath)
    {
        throw new UnsupportedDriverActionException('Double-clicking is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function rightClick($xpath)
    {
        throw new UnsupportedDriverActionException('Right-clicking is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function isVisible($xpath)
    {
        throw new UnsupportedDriverActionException('Element visibility check is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function isSelected($xpath)
    {
        throw new UnsupportedDriverActionException('Element selection check is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function mouseOver($xpath)
    {
        throw new UnsupportedDriverActionException('Mouse manipulations are not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function focus($xpath)
    {
        throw new UnsupportedDriverActionException('Mouse manipulations are not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function blur($xpath)
    {
        throw new UnsupportedDriverActionException('Mouse manipulations are not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function keyPress($xpath, $char, $modifier = null)
    {
        throw new UnsupportedDriverActionException('Keyboard manipulations are not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function keyDown($xpath, $char, $modifier = null)
    {
        throw new UnsupportedDriverActionException('Keyboard manipulations are not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function keyUp($xpath, $char, $modifier = null)
    {
        throw new UnsupportedDriverActionException('Keyboard manipulations are not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function dragTo($sourceXpath, $destinationXpath)
    {
        throw new UnsupportedDriverActionException('Mouse manipulations are not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function executeScript($script)
    {
        throw new UnsupportedDriverActionException('JS is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function evaluateScript($script)
    {
        throw new UnsupportedDriverActionException('JS is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function wait($timeout, $condition)
    {
        throw new UnsupportedDriverActionException('JS is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function resizeWindow($width, $height, $name = null)
    {
        throw new UnsupportedDriverActionException('Window resizing is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function maximizeWindow($name = null)
    {
        throw new UnsupportedDriverActionException('Window maximize is not supported by %s', $this);
    }

    /**
     * {@inheritdoc}
     */
    public function submitForm($xpath)
    {
        throw new UnsupportedDriverActionException('Form submission is not supported by %s', $this);
    }
}
