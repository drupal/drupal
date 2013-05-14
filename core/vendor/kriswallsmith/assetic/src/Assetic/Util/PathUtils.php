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

/**
 * Path Utils.
 *
 * @author Johannes M. Schmitt <schmittjoh@gmail.com>
 */
abstract class PathUtils
{
    public static function resolvePath($path, array $vars, array $values)
    {
        $map = array();
        foreach ($vars as $var) {
            if (false === strpos($path, '{'.$var.'}')) {
                continue;
            }

            if (!isset($values[$var])) {
                throw new \InvalidArgumentException(sprintf('The path "%s" contains the variable "%s", but was not given any value for it.', $path, $var));
            }

            $map['{'.$var.'}'] = $values[$var];
        }

        return strtr($path, $map);
    }

    final private function __construct() { }
}
