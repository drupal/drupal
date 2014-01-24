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
 * Class that represents an RDF Literal of datatype xsd:date
 *
 * @package    EasyRdf
 * @link       http://www.w3.org/TR/xmlschema-2/#date
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Literal_Date extends EasyRdf_Literal
{
    /** Constructor for creating a new date literal
     *
     * If the value is a DateTime object, then it will be converted to the xsd:date format.
     * If no value is given or is is null, then the current date is used.
     *
     * @see DateTime
     *
     * @param  mixed  $value     The value of the literal
     * @param  string $lang      Should be null (literals with a datatype can't have a language)
     * @param  string $datatype  Optional datatype (default 'xsd:date')
     * @return object EasyRdf_Literal_Date
     */
    public function __construct($value = null, $lang = null, $datatype = null)
    {
        // If $value is null, use today's date
        if (is_null($value)) {
            $value = new DateTime('today');
        }

        // Convert DateTime object into string
        if ($value instanceof DateTime) {
            $value = $value->format('Y-m-d');
        }

        parent::__construct($value, null, $datatype);
    }

    /** Parses a string using DateTime and creates a new literal
     *
     * Example:
     *   $date = EasyRdf_Literal_Date::parse('1 January 2011');
     *
     * @see DateTime
     * @param string $value The date to parse
     * @return object EasyRdf_Literal_Date
     */
    public static function parse($value)
    {
        $value = new DateTime($value);
        return new EasyRdf_Literal_Date($value);
    }

    /** Returns the date as a PHP DateTime object
     *
     * @see DateTime::format
     * @return string
     */
    public function getValue()
    {
        return new DateTime($this->value);
    }

    /** Returns date formatted according to given format
     *
     * @see DateTime::format
     * @param string $format
     * @return string
     */
    public function format($format)
    {
        return $this->getValue()->format($format);
    }

    /** A full integer representation of the year, 4 digits
     *
     * @return integer
     */
    public function year()
    {
        return (int)$this->format('Y');
    }

    /** Integer representation of the month
     *
     * @return integer
     */
    public function month()
    {
        return (int)$this->format('m');
    }

    /** Integer representation of the day of the month
     *
     * @return integer
     */
    public function day()
    {
        return (int)$this->format('d');
    }
}
