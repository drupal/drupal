<?php

/*
 * This file is part of the Mink package.
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Behat\Mink\Selector;

use Behat\Mink\Selector\Xpath\Escaper;

/**
 * Named selectors engine. Uses registered XPath selectors to create new expressions.
 *
 * @author Konstantin Kudryashov <ever.zet@gmail.com>
 */
class NamedSelector implements SelectorInterface
{
    private $replacements = array(
        // simple replacements
        '%lowercaseType%' => "translate(./@type, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')",
        '%lowercaseRole%' => "translate(./@role, 'ABCDEFGHIJKLMNOPQRSTUVWXYZ', 'abcdefghijklmnopqrstuvwxyz')",
        '%tagTextMatch%' => 'contains(normalize-space(string(.)), %locator%)',
        '%labelTextMatch%' => './@id = //label[%tagTextMatch%]/@for',
        '%idMatch%' => './@id = %locator%',
        '%valueMatch%' => 'contains(./@value, %locator%)',
        '%idOrValueMatch%' => '(%idMatch% or %valueMatch%)',
        '%idOrNameMatch%' => '(%idMatch% or ./@name = %locator%)',
        '%placeholderMatch%' => './@placeholder = %locator%',
        '%titleMatch%' => 'contains(./@title, %locator%)',
        '%altMatch%' => 'contains(./@alt, %locator%)',
        '%relMatch%' => 'contains(./@rel, %locator%)',
        '%labelAttributeMatch%' => 'contains(./@label, %locator%)',

        // complex replacements
        '%inputTypeWithoutPlaceholderFilter%' => "%lowercaseType% = 'radio' or %lowercaseType% = 'checkbox' or %lowercaseType% = 'file'",
        '%fieldFilterWithPlaceholder%' => 'self::input[not(%inputTypeWithoutPlaceholderFilter%)] | self::textarea',
        '%fieldMatchWithPlaceholder%' => '(%idOrNameMatch% or %labelTextMatch% or %placeholderMatch%)',
        '%fieldMatchWithoutPlaceholder%' => '(%idOrNameMatch% or %labelTextMatch%)',
        '%fieldFilterWithoutPlaceholder%' => 'self::input[%inputTypeWithoutPlaceholderFilter%] | self::select',
        '%buttonTypeFilter%' => "%lowercaseType% = 'submit' or %lowercaseType% = 'image' or %lowercaseType% = 'button' or %lowercaseType% = 'reset'",
        '%notFieldTypeFilter%' => "not(%buttonTypeFilter% or %lowercaseType% = 'hidden')",
        '%buttonMatch%' => '%idOrNameMatch% or %valueMatch% or %titleMatch%',
        '%linkMatch%' => '(%idMatch% or %tagTextMatch% or %titleMatch% or %relMatch%)',
        '%imgAltMatch%' => './/img[%altMatch%]',
    );

    private $selectors = array(
        'fieldset' => <<<XPATH
.//fieldset
[(%idMatch% or .//legend[%tagTextMatch%])]
XPATH

        ,'field' => <<<XPATH
.//*
[%fieldFilterWithPlaceholder%][%notFieldTypeFilter%][%fieldMatchWithPlaceholder%]
|
.//label[%tagTextMatch%]//.//*[%fieldFilterWithPlaceholder%][%notFieldTypeFilter%]
|
.//*
[%fieldFilterWithoutPlaceholder%][%notFieldTypeFilter%][%fieldMatchWithoutPlaceholder%]
|
.//label[%tagTextMatch%]//.//*[%fieldFilterWithoutPlaceholder%][%notFieldTypeFilter%]
XPATH

        ,'link' => <<<XPATH
.//a
[./@href][(%linkMatch% or %imgAltMatch%)]
|
.//*
[%lowercaseRole% = 'link'][(%idOrValueMatch% or %titleMatch% or %tagTextMatch%)]
XPATH

        ,'button' => <<<XPATH
.//input
[%buttonTypeFilter%][(%buttonMatch%)]
|
.//input
[%lowercaseType% = 'image'][%altMatch%]
|
.//button
[(%buttonMatch% or %tagTextMatch%)]
|
.//*
[%lowercaseRole% = 'button'][(%buttonMatch% or %tagTextMatch%)]
XPATH

        ,'link_or_button' => <<<XPATH
.//a
[./@href][(%linkMatch% or %imgAltMatch%)]
|
.//input
[%buttonTypeFilter%][(%idOrValueMatch% or %titleMatch%)]
|
.//input
[%lowercaseType% = 'image'][%altMatch%]
|
.//button
[(%idOrValueMatch% or %titleMatch% or %tagTextMatch%)]
|
.//*
[(%lowercaseRole% = 'button' or %lowercaseRole% = 'link')][(%idOrValueMatch% or %titleMatch% or %tagTextMatch%)]
XPATH

        ,'content' => <<<XPATH
./descendant-or-self::*
[%tagTextMatch%]
XPATH

        ,'select' => <<<XPATH
.//select
[%fieldMatchWithoutPlaceholder%]
|
.//label[%tagTextMatch%]//.//select
XPATH

        ,'checkbox' => <<<XPATH
.//input
[%lowercaseType% = 'checkbox'][%fieldMatchWithoutPlaceholder%]
|
.//label[%tagTextMatch%]//.//input[%lowercaseType% = 'checkbox']
XPATH

        ,'radio' => <<<XPATH
.//input
[%lowercaseType% = 'radio'][%fieldMatchWithoutPlaceholder%]
|
.//label[%tagTextMatch%]//.//input[%lowercaseType% = 'radio']
XPATH

        ,'file' => <<<XPATH
.//input
[%lowercaseType% = 'file'][%fieldMatchWithoutPlaceholder%]
|
.//label[%tagTextMatch%]//.//input[%lowercaseType% = 'file']
XPATH

        ,'optgroup' => <<<XPATH
.//optgroup
[%labelAttributeMatch%]
XPATH

        ,'option' => <<<XPATH
.//option
[(./@value = %locator% or %tagTextMatch%)]
XPATH

        ,'table' => <<<XPATH
.//table
[(%idMatch% or .//caption[%tagTextMatch%])]
XPATH
        ,'id' => <<<XPATH
.//*[%idMatch%]
XPATH
    ,'id_or_name' => <<<XPATH
.//*[%idOrNameMatch%]
XPATH
    );
    private $xpathEscaper;

    /**
     * Creates selector instance.
     */
    public function __construct()
    {
        $this->xpathEscaper = new Escaper();

        foreach ($this->replacements as $from => $to) {
            $this->replacements[$from] = strtr($to, $this->replacements);
        }

        foreach ($this->selectors as $alias => $selector) {
            $this->selectors[$alias] = strtr($selector, $this->replacements);
        }
    }

    /**
     * Registers new XPath selector with specified name.
     *
     * @param string $name  name for selector
     * @param string $xpath xpath expression
     */
    public function registerNamedXpath($name, $xpath)
    {
        $this->selectors[$name] = $xpath;
    }

    /**
     * Translates provided locator into XPath.
     *
     * @param string|array $locator selector name or array of (selector_name, locator)
     *
     * @return string
     *
     * @throws \InvalidArgumentException
     */
    public function translateToXPath($locator)
    {
        if (\is_array($locator)) {
            if (2 !== \count($locator)) {
                throw new \InvalidArgumentException('NamedSelector expects array(name, locator) as argument');
            }

            $selector = $locator[0];
            $locator = $locator[1];
        } else {
            $selector = (string) $locator;
            $locator = null;
        }

        if (!isset($this->selectors[$selector])) {
            throw new \InvalidArgumentException(sprintf(
                'Unknown named selector provided: "%s". Expected one of (%s)',
                $selector,
                implode(', ', array_keys($this->selectors))
            ));
        }

        $xpath = $this->selectors[$selector];

        if (null !== $locator) {
            $xpath = strtr($xpath, array('%locator%' => $this->escapeLocator($locator)));
        }

        return $xpath;
    }

    /**
     * Registers a replacement in the list of replacements.
     *
     * This method must be called in the constructor before calling the parent constructor.
     *
     * @param string $from
     * @param string $to
     */
    protected function registerReplacement($from, $to)
    {
        $this->replacements[$from] = $to;
    }

    private function escapeLocator($locator)
    {
        // If the locator looks like an escaped one, don't escape it again for BC reasons.
        if (
            preg_match('/^\'[^\']*+\'$/', $locator)
            || (false !== strpos($locator, '\'') && preg_match('/^"[^"]*+"$/', $locator))
            || ((8 < $length = strlen($locator)) && 'concat(' === substr($locator, 0, 7) && ')' === $locator[$length - 1])
        ) {
            @trigger_error(
                'Passing an escaped locator to the named selector is deprecated as of 1.7 and will be removed in 2.0.'
                .' Pass the raw value instead.',
                E_USER_DEPRECATED
            );

            return $locator;
        }

        return $this->xpathEscaper->escapeLiteral($locator);
    }
}
