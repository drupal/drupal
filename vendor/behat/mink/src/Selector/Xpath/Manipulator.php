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
 * XPath manipulation utility.
 *
 * @author Graham Bates
 * @author Christophe Coevoet <stof@notk.org>
 */
class Manipulator
{
    /**
     * Regex to find union operators not inside brackets.
     */
    const UNION_PATTERN = '/\|(?![^\[]*\])/';

    /**
     * Prepends the XPath prefix to the given XPath.
     *
     * The returned XPath will match elements matching the XPath inside an element
     * matching the prefix.
     *
     * @param string $xpath
     * @param string $prefix
     *
     * @return string
     */
    public function prepend($xpath, $prefix)
    {
        $expressions = array();

        // If the xpath prefix contains a union we need to wrap it in parentheses.
        if (preg_match(self::UNION_PATTERN, $prefix)) {
            $prefix = '('.$prefix.')';
        }

        // Split any unions into individual expressions.
        foreach ($this->splitUnionParts($xpath) as $expression) {
            $expression = trim($expression);
            $parenthesis = '';

            // If the union is inside some braces, we need to preserve the opening braces and apply
            // the prefix only inside it.
            if (preg_match('/^[\(\s*]+/', $expression, $matches)) {
                $parenthesis = $matches[0];
                $expression = substr($expression, strlen($parenthesis));
            }

            // add prefix before element selector
            if (0 === strpos($expression, '/')) {
                $expression = $prefix.$expression;
            } else {
                $expression = $prefix.'/'.$expression;
            }
            $expressions[] = $parenthesis.$expression;
        }

        return implode(' | ', $expressions);
    }

    /**
     * Splits the XPath into parts that are separated by the union operator.
     *
     * @param string $xpath
     *
     * @return string[]
     */
    private function splitUnionParts($xpath)
    {
        if (false === strpos($xpath, '|')) {
            return array($xpath); // If there is no pipe in the string, we know for sure that there is no union
        }

        $xpathLen = strlen($xpath);
        $openedBrackets = 0;
        // Consume whitespaces chars at the beginning of the string (this is the list of chars removed by trim() by default)
        $startPosition = strspn($xpath, " \t\n\r\0\x0B");

        $unionParts = array();

        for ($i = $startPosition; $i <= $xpathLen; ++$i) {
            // Consume all chars until we reach a quote, a bracket or a pipe
            $i += strcspn($xpath, '"\'[]|', $i);

            if ($i < $xpathLen) {
                switch ($xpath[$i]) {
                    case '"':
                    case "'":
                        // Move to the end of the string literal
                        if (false === $i = strpos($xpath, $xpath[$i], $i + 1)) {
                            return array($xpath); // The XPath expression is invalid, don't split it
                        }
                        continue 2;
                    case '[':
                        ++$openedBrackets;
                        continue 2;
                    case ']':
                        --$openedBrackets;
                        continue 2;
                }
            }
            if ($openedBrackets) {
                continue;
            }

            $unionParts[] = substr($xpath, $startPosition, $i - $startPosition);

            if ($i === $xpathLen) {
                return $unionParts;
            }

            // Consume any whitespace chars after the pipe
            $i += strspn($xpath, " \t\n\r\0\x0B", $i + 1);
            $startPosition = $i + 1;
        }

        return array($xpath); // The XPath expression is invalid
    }

}
