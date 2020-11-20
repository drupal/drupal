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
 * Class to parse JSON-LD to an EasyRdf_Graph
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2014 Markus Lanthaler
 * @author     Markus Lanthaler <mail@markus-lanthaler.com>
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Parser_JsonLd extends EasyRdf_Parser
{
    /**
      * Parse a JSON-LD document into an EasyRdf_Graph
      *
      * Attention: Since JSON-LD supports datasets, a document may contain
      * multiple graphs and not just one. This parser returns only the
      * default graph. An alternative would be to merge all graphs.
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

        if ($format != 'jsonld') {
            throw new EasyRdf_Exception(
                "EasyRdf_Parser_JsonLd does not support $format"
            );
        }

        try {
            $quads = \ML\JsonLD\JsonLD::toRdf($data, array('base' => $baseUri));
        } catch (\ML\JsonLD\Exception\JsonLdException $e) {
            throw new EasyRdf_Parser_Exception($e->getMessage());
        }

        foreach ($quads as $quad) {
            // Ignore named graphs
            if (null !== $quad->getGraph()) {
                continue;
            }

            $subject = (string) $quad->getSubject();
            if ('_:' === substr($subject, 0, 2)) {
                $subject = $this->remapBnode($subject);
            }

            $predicate = (string) $quad->getProperty();

            if ($quad->getObject() instanceof \ML\IRI\IRI) {
                $object = array(
                    'type' => 'uri',
                    'value' => (string) $quad->getObject()
                );

                if ('_:' === substr($object['value'], 0, 2)) {
                    $object = array(
                        'type' => 'bnode',
                        'value' => $this->remapBnode($object['value'])
                    );
                }
            } else {
                $object = array(
                    'type' => 'literal',
                    'value' => $quad->getObject()->getValue()
                );

                if ($quad->getObject() instanceof \ML\JsonLD\LanguageTaggedString) {
                    $object['lang'] = $quad->getObject()->getLanguage();
                } else {
                    $object['datatype'] = $quad->getObject()->getType();
                }
            }

            $this->addTriple($subject, $predicate, $object);
        }

        return $this->tripleCount;
    }
}
