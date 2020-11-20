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
 * A pure-php class to parse RDF/JSON with no dependancies.
 *
 * http://n2.talis.com/wiki/RDF_JSON_Specification
 * docs/appendix-a-rdf-formats-json.md
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Parser_Json extends EasyRdf_Parser_RdfPhp
{
    private $jsonLastErrorExists = false;

    /**
     * Constructor
     *
     * @return object EasyRdf_Parser_Json
     */
    public function __construct()
    {
        $this->jsonLastErrorExists = function_exists('json_last_error');
    }

    /** Return the last JSON parser error as a string
     *
     * If json_last_error() is not available a generic message will be returned.
     *
     * @ignore
     */
    protected function jsonLastErrorString()
    {
        if ($this->jsonLastErrorExists) {
            switch (json_last_error()) {
                case JSON_ERROR_NONE:
                    return null;
                case JSON_ERROR_DEPTH:
                    return "JSON Parse error: the maximum stack depth has been exceeded";
                case JSON_ERROR_STATE_MISMATCH:
                    return "JSON Parse error: invalid or malformed JSON";
                case JSON_ERROR_CTRL_CHAR:
                    return "JSON Parse error: control character error, possibly incorrectly encoded";
                case JSON_ERROR_SYNTAX:
                    return "JSON Parse syntax error";
                case JSON_ERROR_UTF8:
                    return "JSON Parse error: malformed UTF-8 characters, possibly incorrectly encoded";
                default:
                    return "JSON Parse error: unknown";
            }
        } else {
            return "JSON Parse error";
        }
    }

    /** Parse the triple-centric JSON format, as output by libraptor
     *
     * http://librdf.org/raptor/api/serializer-json.html
     *
     * @ignore
     */
    protected function parseJsonTriples($data, $baseUri)
    {
        foreach ($data['triples'] as $triple) {
            if ($triple['subject']['type'] == 'bnode') {
                $subject = $this->remapBnode($triple['subject']['value']);
            } else {
                $subject = $triple['subject']['value'];
            }

            $predicate = $triple['predicate']['value'];

            if ($triple['object']['type'] == 'bnode') {
                $object = array(
                    'type' => 'bnode',
                    'value' => $this->remapBnode($triple['object']['value'])
                );
            } else {
                $object = $triple['object'];
            }

            $this->addTriple($subject, $predicate, $object);
        }

        return $this->tripleCount;
    }

    /**
      * Parse RDF/JSON into an EasyRdf_Graph
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

        if ($format != 'json') {
            throw new EasyRdf_Exception(
                "EasyRdf_Parser_Json does not support: $format"
            );
        }

        $decoded = @json_decode(strval($data), true);
        if ($decoded === null) {
            throw new EasyRdf_Parser_Exception(
                $this->jsonLastErrorString()
            );
        }

        if (array_key_exists('triples', $decoded)) {
            return $this->parseJsonTriples($decoded, $baseUri);
        } else {
            return parent::parse($graph, $decoded, 'php', $baseUri);
        }
    }
}
