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
 * Class that represents an RDF Literal of datatype xsd:hexBinary
 *
 * @package    EasyRdf
 * @link       http://www.w3.org/TR/xmlschema-2/#hexBinary
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Literal_HexBinary extends EasyRdf_Literal
{
    /** Constructor for creating a new xsd:hexBinary literal
     *
     * @param  mixed  $value     The value of the literal (already encoded as hexadecimal)
     * @param  string $lang      Should be null (literals with a datatype can't have a language)
     * @param  string $datatype  Optional datatype (default 'xsd:hexBinary')
     * @return object EasyRdf_Literal_HexBinary
     */
    public function __construct($value, $lang = null, $datatype = null)
    {
        // Normalise the canonical representation, as specified here:
        // http://www.w3.org/TR/xmlschema-2/#hexBinary-canonical-repr
        $value = strtoupper($value);

        // Validate the data
        if (preg_match('/[^A-F0-9]/', $value)) {
            throw new InvalidArgumentException(
                "Literal of type xsd:hexBinary contains non-hexadecimal characters"
            );
        }

        parent::__construct(strtoupper($value), null, 'xsd:hexBinary');
    }

    /** Constructor for creating a new literal object from a binary blob
     *
     * @param  string $binary  The binary data
     * @return object EasyRdf_Literal_HexBinary
     */
    public static function fromBinary($binary)
    {
        return new self( bin2hex($binary) );
    }

    /** Decode the hexadecimal string into a binary blob
     *
     * @return string The binary blob
     */
    public function toBinary()
    {
        return pack("H*", $this->value);
    }
}
