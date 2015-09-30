<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Selector;

/**
 * Exact match selector engine. Like the Named selector engine but ignores partial matches.
 */
class ExactNamedSelector extends NamedSelector
{
    public function __construct()
    {
        $this->registerReplacement('%tagTextMatch%', 'normalize-space(string(.)) = %locator%');
        $this->registerReplacement('%valueMatch%', './@value = %locator%');
        $this->registerReplacement('%titleMatch%', './@title = %locator%');
        $this->registerReplacement('%altMatch%', './@alt = %locator%');
        $this->registerReplacement('%relMatch%', './@rel = %locator%');
        $this->registerReplacement('%labelAttributeMatch%', './@label = %locator%');

        parent::__construct();
    }
}
