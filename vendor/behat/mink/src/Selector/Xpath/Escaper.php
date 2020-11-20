<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Selector\Xpath;

/**
 * XPath escaper.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class Escaper
{
    /**
     * Escapes the string as a XPath literal.
     *
     * @param string $s
     *
     * @return string
     */
    public function escapeLiteral($s)
    {
        if (false === strpos($s, "'")) {
            return sprintf("'%s'", $s);
        }

        if (false === strpos($s, '"')) {
            return sprintf('"%s"', $s);
        }

        $string = $s;
        $parts = array();
        while (true) {
            if (false !== $pos = strpos($string, "'")) {
                $parts[] = sprintf("'%s'", substr($string, 0, $pos));
                $parts[] = "\"'\"";
                $string = substr($string, $pos + 1);
            } else {
                $parts[] = "'$string'";
                break;
            }
        }

        return sprintf('concat(%s)', implode(',', $parts));
    }
}
