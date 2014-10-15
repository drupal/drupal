<?php

/*
 * This file is part of the Assetic package, an OpenSky project.
 *
 * (c) 2010-2013 OpenSky Project Inc
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Assetic\Util;

abstract class PathUtils extends VarUtils
{
    public static function resolvePath($path, array $vars, array $values)
    {
        return static::resolve($path, $vars, $values);
    }
}
