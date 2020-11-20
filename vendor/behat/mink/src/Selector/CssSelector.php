<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Selector;

use Symfony\Component\CssSelector\CssSelector as CSS;
use Symfony\Component\CssSelector\CssSelectorConverter;

/**
 * CSS selector engine. Transforms CSS to XPath.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class CssSelector implements SelectorInterface
{
    /**
     * Translates CSS into XPath.
     *
     * @param string|array $locator current selector locator
     *
     * @return string
     */
    public function translateToXPath($locator)
    {
        if (!is_string($locator)) {
            throw new \InvalidArgumentException('The CssSelector expects to get a string as locator');
        }

        // Symfony 2.8+ API
        if (class_exists('Symfony\Component\CssSelector\CssSelectorConverter')) {
            $converter = new CssSelectorConverter();

            return $converter->toXPath($locator);
        }

        // old static API for Symfony 2.7 and older
        return CSS::toXPath($locator);
    }
}
