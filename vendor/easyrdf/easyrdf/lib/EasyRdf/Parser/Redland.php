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
 * Class to parse RDF using Redland (librdf) C library.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Parser_Redland extends EasyRdf_Parser
{
    /** Variable set to the librdf world */
    private $world = null;

    /** Parser feature URI string for getting the error count of last parse. */
    const LIBRDF_PARSER_FEATURE_ERROR_COUNT =
        'http://feature.librdf.org/parser-error-count';

    /*
     *  Types supported by Redland:
     *
     *  ntriples: N-Triples
     *  turtle: Turtle Terse RDF Triple Language
     *  trig: TriG - Turtle with Named Graphs
     *  rss-tag-soup: RSS Tag Soup
     *  grddl: Gleaning Resource Descriptions from Dialects of Languages
     *  guess: Pick the parser to use using content type and URI
     *  rdfa: RDF/A via librdfa
     *  raptor: (null)
     *  rdfxml: RDF/XML
     */


    /**
     * Convert a librdf node type into a string
     * @ignore
     */
    protected function nodeTypeString($node)
    {
        switch(librdf_node_get_type($node)) {
            case 1:
                return 'uri';
                break;
            case 2:
                return 'literal';
                break;
            case 4:
                return 'bnode';
                break;
            default:
                return 'unknown';
                break;
        }
    }

    /**
     * Convert the URI for a node into a string
     * @ignore
     */
    protected function nodeUriString($node)
    {
        $type = EasyRdf_Parser_Redland::nodeTypeString($node);
        if ($type == 'uri') {
            $uri = librdf_node_get_uri($node);
            if (!$uri) {
                throw new EasyRdf_Exception("Failed to get URI of node");
            }
            $str = librdf_uri_to_string($uri);
            if (!$str) {
                throw new EasyRdf_Exception(
                    "Failed to convert librdf_uri to string"
                );
            }
            return $str;
        } elseif ($type == 'bnode') {
            return $this->remapBnode(
                librdf_node_get_blank_identifier($node)
            );
        } else {
            throw new EasyRdf_Exception("Unsupported type: ".$type);
        }
    }

    /**
     * Convert a node into an associate array
     * @ignore
     */
    protected function nodeToRdfPhp($node)
    {
        $object = array();
        $object['type'] = EasyRdf_Parser_Redland::nodeTypeString($node);
        if ($object['type'] == 'uri') {
            $object['value'] = EasyRdf_Parser_Redland::nodeUriString($node);
        } elseif ($object['type'] == 'bnode') {
            $object['value'] = '_:'.librdf_node_get_blank_identifier($node);
        } elseif ($object['type'] == 'literal') {
            $object['value'] = librdf_node_get_literal_value($node);
            $lang = librdf_node_get_literal_value_language($node);
            if ($lang) {
                $object['lang'] = $lang;
            }
            $datatype = librdf_node_get_literal_value_datatype_uri($node);
            if ($datatype) {
                $object['datatype'] = librdf_uri_to_string($datatype);
            }
        } else {
            throw new EasyRdf_Exception("Unsupported type: ".$object['type']);
        }
        return $object;
    }

    /**
     * Return the number of errors during parsing
     * @ignore
     */
    protected function parserErrorCount($parser)
    {
        $errorUri = librdf_new_uri(
            $this->world,
            self::LIBRDF_PARSER_FEATURE_ERROR_COUNT
        );
        $errorNode = librdf_parser_get_feature($parser, $errorUri);
        $errorCount = librdf_node_get_literal_value($errorNode);
        librdf_free_uri($errorUri);
        return $errorCount;
    }

    /**
     * Constructor
     *
     * @return object EasyRdf_Parser_Redland
     */
    public function __construct()
    {
        if (extension_loaded('redland')) {
            $this->world = librdf_php_get_world();
            if (!$this->world) {
                throw new EasyRdf_Exception(
                    "Failed to initialise librdf world."
                );
            }
        } else {
            throw new EasyRdf_Exception(
                "Redland PHP extension is not available."
            );
        }
    }

    /**
      * Parse an RDF document into an EasyRdf_Graph
      *
      * @param object EasyRdf_Graph $graph   the graph to load the data into
      * @param string               $data    the RDF document data
      * @param string               $format  the format of the input data
      * @param string               $baseUri the base URI of the data being parsed
      * @return integer             The number of triples added to the graph
      */
    public function parse($graph, $data, $format, $baseUri)
    {
        parent::checkParseParams($graph, $data, $format, $baseUri);

        $parser = librdf_new_parser($this->world, $format, null, null);
        if (!$parser) {
            throw new EasyRdf_Exception(
                "Failed to create librdf_parser of type: $format"
            );
        }

        $rdfUri = librdf_new_uri($this->world, $baseUri);
        if (!$rdfUri) {
            throw new EasyRdf_Exception(
                "Failed to create librdf_uri from: $baseUri"
            );
        }

        $stream = librdf_parser_parse_string_as_stream($parser, $data, $rdfUri);
        if (!$stream) {
            throw new EasyRdf_Parser_Exception(
                "Failed to parse RDF stream"
            );
        }

        do {
            $statement = librdf_stream_get_object($stream);
            if ($statement) {
                $subject = EasyRdf_Parser_Redland::nodeUriString(
                    librdf_statement_get_subject($statement)
                );
                $predicate = EasyRdf_Parser_Redland::nodeUriString(
                    librdf_statement_get_predicate($statement)
                );
                $object = EasyRdf_Parser_Redland::nodeToRdfPhp(
                    librdf_statement_get_object($statement)
                );

                $this->addTriple($subject, $predicate, $object);
            }
        } while (!librdf_stream_next($stream));

        $errorCount = $this->parserErrorCount($parser);
        if ($errorCount) {
            throw new EasyRdf_Parser_Exception("$errorCount errors while parsing.");
        }

        librdf_free_uri($rdfUri);
        librdf_free_stream($stream);
        librdf_free_parser($parser);

        return $this->tripleCount;
    }
}
