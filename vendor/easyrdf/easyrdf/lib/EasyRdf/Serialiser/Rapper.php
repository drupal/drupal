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
 * Class to serialise an EasyRdf_Graph to RDF
 * using the 'rapper' command line tool.
 *
 * Note: the built-in N-Triples serialiser is used to pass data to Rapper.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Serialiser_Rapper extends EasyRdf_Serialiser_Ntriples
{
    private $rapperCmd = null;

    /**
     * Constructor
     *
     * @param string $rapperCmd Optional path to the rapper command to use.
     * @return object EasyRdf_Serialiser_Rapper
     */
    public function __construct($rapperCmd = 'rapper')
    {
        $result = exec("$rapperCmd --version 2>/dev/null", $output, $status);
        if ($status != 0) {
            throw new EasyRdf_Exception(
                "Failed to execute the command '$rapperCmd': $result"
            );
        } else {
            $this->rapperCmd = $rapperCmd;
        }
    }

    /**
     * Serialise an EasyRdf_Graph to the RDF format of choice.
     *
     * @param EasyRdf_Graph $graph   An EasyRdf_Graph object.
     * @param string        $format  The name of the format to convert to.
     * @param array         $options
     * @return string The RDF in the new desired format.
     */
    public function serialise($graph, $format, array $options = array())
    {
        parent::checkSerialiseParams($graph, $format);

        $ntriples = parent::serialise($graph, 'ntriples');

        // Hack to produce more concise RDF/XML
        if ($format == 'rdfxml') {
            $format = 'rdfxml-abbrev';
        }

        return EasyRdf_Utils::execCommandPipe(
            $this->rapperCmd,
            array(
                '--quiet',
                '--input', 'ntriples',
                '--output', $format,
                '-', 'unknown://'
            ),
            $ntriples
        );
    }
}
