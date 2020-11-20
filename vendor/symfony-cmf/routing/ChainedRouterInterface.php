<?php

/*
 * This file is part of the Symfony CMF package.
 *
 * (c) 2011-2015 Symfony CMF
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Cmf\Component\Routing;

use Symfony\Component\Routing\RouterInterface;

/**
 * Interface to combine the VersatileGeneratorInterface with the RouterInterface.
 */
interface ChainedRouterInterface extends RouterInterface, VersatileGeneratorInterface
{
}
