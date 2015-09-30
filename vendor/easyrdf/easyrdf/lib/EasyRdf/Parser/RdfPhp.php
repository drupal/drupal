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
 * Class to parse RDF with no external dependancies.
 *
 * http://n2.talis.com/wiki/RDF_PHP_Specification
 * docs/appendix-a-rdf-formats-php.md
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Parser_RdfPhp extends EasyRdf_Parser
{
    /**
     * Constructor
     *
     * @return object EasyRdf_Parser_RdfPhp
     */
    public function __construct()
    {
    }

    /**
      * Parse RDF/PHP into an EasyRdf_Graph
      *
      * @param object EasyRdf_Graph $graph   the graph to load the data into
      * @param string               $data    the RDF document data
      * @param string               $format  the format of the input data
      * @param string               $baseUri the base URI of the data being parsed
      * @return integer             The number of triples added to the graph
      */
    public function parse($graph, $data, $format, $baseUri)
    {
        $this->checkParseParams($graph, $data, $format, $baseUri);

        if ($format != 'php') {
            throw new EasyRdf_Exception(
                "EasyRdf_Parser_RdfPhp does not support: $format"
            );
        }

        foreach ($data as $subject => $properties) {
            if (substr($subject, 0, 2) === '_:') {
                $subject = $this->remapBnode($subject);
            } elseif (preg_match('/^\w+$/', $subject)) {
                # Cope with invalid RDF/JSON serialisations that
                # put the node name in, without the _: prefix
                # (such as net.fortytwo.sesametools.rdfjson)
                $subject = $this->remapBnode($subject);
            }

            foreach ($properties as $property => $objects) {
                foreach ($objects as $object) {
                    if ($object['type'] === 'bnode') {
                        $object['value'] = $this->remapBnode($object['value']);
                    }
                    $this->addTriple($subject, $property, $object);
                }
            }
        }

        return $this->tripleCount;
    }
}
