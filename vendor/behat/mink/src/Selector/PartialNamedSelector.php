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
 * Named selectors engine. Uses registered XPath selectors to create new expressions.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class PartialNamedSelector extends NamedSelector
{
    public function __construct()
    {
        $this->registerReplacement('%tagTextMatch%', 'contains(normalize-space(string(.)), %locator%)');
        $this->registerReplacement('%valueMatch%', 'contains(./@value, %locator%)');
        $this->registerReplacement('%titleMatch%', 'contains(./@title, %locator%)');
        $this->registerReplacement('%altMatch%', 'contains(./@alt, %locator%)');
        $this->registerReplacement('%relMatch%', 'contains(./@rel, %locator%)');
        $this->registerReplacement('%labelAttributeMatch%', 'contains(./@label, %locator%)');

        parent::__construct();
    }
}
