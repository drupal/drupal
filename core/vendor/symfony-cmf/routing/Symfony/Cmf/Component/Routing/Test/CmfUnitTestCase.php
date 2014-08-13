<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2014 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing\Test;

class CmfUnitTestCase extends \PHPUnit_Framework_TestCase
{

    protected function buildMock($class, array $methods = array())
    {
        return $this->getMockBuilder($class)
                ->disableOriginalConstructor()
                ->setMethods($methods)
                ->getMock();
    }

}
