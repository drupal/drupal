<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Selector;

use Behat\Mink\Selector\Xpath\Escaper;

/**
 * Selectors handler.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class SelectorsHandler
{
    private $selectors;
    private $escaper;

    /**
     * Initializes selectors handler.
     *
     * @param SelectorInterface[] $selectors default selectors to register
     */
    public function __construct(array $selectors = array())
    {
        $this->escaper = new Escaper();

        $this->registerSelector('named_partial', new PartialNamedSelector());
        $this->registerSelector('named_exact', new ExactNamedSelector());
        $this->registerSelector('css', new CssSelector());

        foreach ($selectors as $name => $selector) {
            $this->registerSelector($name, $selector);
        }
    }

    /**
     * Registers new selector engine with specified name.
     *
     * @param string            $name     selector engine name
     * @param SelectorInterface $selector selector engine instance
     */
    public function registerSelector($name, SelectorInterface $selector)
    {
        $this->selectors[$name] = $selector;
    }

    /**
     * Checks whether selector with specified name is registered on handler.
     *
     * @param string $name selector engine name
     *
     * @return Boolean
     */
    public function isSelectorRegistered($name)
    {
        return isset($this->selectors[$name]);
    }

    /**
     * Returns selector engine with specified name.
     *
     * @param string $name selector engine name
     *
     * @return SelectorInterface
     *
     * @throws \InvalidArgumentException
     */
    public function getSelector($name)
    {
        if ('named' === $name) {
            @trigger_error(
                'Using the "named" selector directly from the handler is deprecated as of 1.6 and will be removed in 2.0.'
                .' Use the "named_partial" or use the "named" selector through the Element API instead.',
                E_USER_DEPRECATED
            );
            $name = 'named_partial';
        }

        if (!$this->isSelectorRegistered($name)) {
            throw new \InvalidArgumentException("Selector \"$name\" is not registered.");
        }

        return $this->selectors[$name];
    }

    /**
     * Translates selector with specified name to XPath.
     *
     * @param string       $selector selector engine name (registered)
     * @param string|array $locator  selector locator (an array or a string depending of the selector being used)
     *
     * @return string
     */
    public function selectorToXpath($selector, $locator)
    {
        if ('xpath' === $selector) {
            if (!is_string($locator)) {
                throw new \InvalidArgumentException('The xpath selector expects to get a string as locator');
            }

            return $locator;
        }

        return $this->getSelector($selector)->translateToXPath($locator);
    }

    /**
     * Translates string to XPath literal.
     *
     * @deprecated since Mink 1.7. Use \Behat\Mink\Selector\Xpath\Escaper::escapeLiteral when building Xpath
     *             or pass the unescaped value when using the named selector.
     *
     * @param string $s
     *
     * @return string
     */
    public function xpathLiteral($s)
    {
        @trigger_error(
            'The '.__METHOD__.' method is deprecated as of 1.7 and will be removed in 2.0.'
            .' Use \Behat\Mink\Selector\Xpath\Escaper::escapeLiteral instead when building Xpath'
            .' or pass the unescaped value when using the named selector.',
            E_USER_DEPRECATED
        );

        return $this->escaper->escapeLiteral($s);
    }
}
