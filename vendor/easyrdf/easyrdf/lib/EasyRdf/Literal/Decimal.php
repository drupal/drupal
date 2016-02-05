<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2009-2013 Nicholas J Humfrey.  All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright notice,
 *    this list of conditions and the following disclaimer in the documentation
 *    and/or other materials provided with the distribution.
 * 3. The name of the author 'Nicholas J Humfrey" may be used to endorse or
 *    promote products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS"
 * AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE
 * IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE
 * ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT OWNER OR CONTRIBUTORS BE
 * LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR
 * CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF
 * SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS
 * INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN
 * CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE)
 * ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * Class that represents an RDF Literal of datatype xsd:decimal
 *
 * @package    EasyRdf
 * @link       http://www.w3.org/TR/xmlschema-2/#decimal
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Literal_Decimal extends EasyRdf_Literal
{
    /**
     * written according to http://www.w3.org/TR/xmlschema-2/#decimal
     */
    const DECIMAL_REGEX = '^([+\-]?)(((\d+)?\.(\d+))|((\d+)\.?))$';

    /** Constructor for creating a new decimal literal
     *
     * @param  double|int|string $value    The value of the literal
     * @param  string            $lang     Should be null (literals with a datatype can't have a language)
     * @param  string            $datatype Optional datatype (default 'xsd:decimal')
     *
     * @throws UnexpectedValueException
     * @return EasyRdf_Literal_Decimal
     */
    public function __construct($value, $lang = null, $datatype = null)
    {
        if (is_string($value)) {
            self::validate($value);
        } elseif (is_double($value) or is_int($value)) {
            $locale_data = localeconv();
            $value = str_replace($locale_data['decimal_point'], '.', strval($value));
        } else {
            throw new UnexpectedValueException('EasyRdf_Literal_Decimal expects int/float/string as value');
        }

        $value = self::canonicalise($value);

        parent::__construct($value, null, $datatype);
    }

    /** Return the value of the literal cast to a PHP string
     *
     * @return string
     */
    public function getValue()
    {
        return strval($this->value);
    }

    /**
     * @param string $value
     *
     * @throws UnexpectedValueException
     */
    public static function validate($value)
    {
        if (!mb_ereg_match(self::DECIMAL_REGEX, $value)) {
            throw new UnexpectedValueException("'{$value}' doesn't look like a valid decimal");
        }
    }

    /**
     * Converts valid xsd:decimal literal to Canonical representation
     * see http://www.w3.org/TR/xmlschema-2/#decimal
     *
     * @param string $value Valid xsd:decimal literal
     *
     * @return string
     */
    public static function canonicalise($value)
    {
        $pieces = array();
        mb_ereg(self::DECIMAL_REGEX, $value, $pieces);

        $sign       = $pieces[1] === '-' ? '-' : '';  // '+' is not allowed
        $integer    = ltrim(($pieces[4] !== false) ? $pieces[4] : $pieces[7], '0');
        $fractional = rtrim($pieces[5], '0');

        if (empty($integer)) {
            $integer = '0';
        }

        if (empty($fractional)) {
            $fractional = '0';
        }

        return "{$sign}{$integer}.{$fractional}";
    }
}
