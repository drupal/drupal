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

        return CSS::toXPath($locator);
    }
}
