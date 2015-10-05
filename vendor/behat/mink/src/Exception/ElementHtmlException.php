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
use Behat\Mink\Element\Element;
use Behat\Mink\Session;

/**
 * Exception thrown when an expectation on the HTML of an element fails.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class ElementHtmlException extends ExpectationException
{
    /**
     * Element instance.
     *
     * @var Element
     */
    protected $element;

    /**
     * Initializes exception.
     *
     * @param string                  $message   optional message
     * @param DriverInterface|Session $driver    driver instance
     * @param Element                 $element   element
     * @param \Exception              $exception expectation exception
     */
    public function __construct($message, $driver, Element $element, \Exception $exception = null)
    {
        $this->element = $element;

        parent::__construct($message, $driver, $exception);
    }

    protected function getContext()
    {
        return $this->element->getOuterHtml();
    }
}
