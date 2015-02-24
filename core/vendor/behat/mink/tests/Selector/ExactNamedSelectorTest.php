<?php

namespace Behat\Mink\Tests\Selector;

use Behat\Mink\Selector\ExactNamedSelector;

class ExactNamedSelectorTest extends NamedSelectorTest
{
    protected function getSelector()
    {
        return new ExactNamedSelector();
    }

    protected function allowPartialMatch()
    {
        return false;
    }
}
