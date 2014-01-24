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
 * Class to serialise an EasyRdf_Graph to JSON-LD
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2013 Alexey Zakhlestin
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Serialiser_JsonLd extends EasyRdf_Serialiser
{
    public function __construct()
    {
        if (!class_exists('\ML\JsonLD\JsonLD')) {
            throw new LogicException('Please install "ml/json-ld" dependency to use JSON-LD serialisation');
        }

        parent::__construct();
    }

    /**
     * @param EasyRdf_Graph $graph
     * @param string        $format
     * @param array         $options
     * @throws EasyRdf_Exception
     * @return string
     */
    public function serialise($graph, $format, array $options = array())
    {
        parent::checkSerialiseParams($graph, $format);

        if ($format != 'jsonld') {
            throw new EasyRdf_Exception(__CLASS__.' does not support: '.$format);
        }


        $ld_graph = new \ML\JsonLD\Graph();
        $nodes = array(); // cache for id-to-node association

        foreach ($graph->toRdfPhp() as $resource => $properties) {
            if (array_key_exists($resource, $nodes)) {
                $node = $nodes[$resource];
            } else {
                $node = $ld_graph->createNode($resource);
                $nodes[$resource] = $node;
            }

            foreach ($properties as $property => $values) {
                foreach ($values as $value) {
                    if ($value['type'] == 'bnode' or $value['type'] == 'uri') {
                        if (array_key_exists($value['value'], $nodes)) {
                            $_value = $nodes[$value['value']];
                        } else {
                            $_value = $ld_graph->createNode($value['value']);
                            $nodes[$value['value']] = $_value;
                        }
                    } elseif ($value['type'] == 'literal') {
                        if (isset($value['lang'])) {
                            $_value = new \ML\JsonLD\LanguageTaggedString($value['value'], $value['lang']);
                        } elseif (isset($value['datatype'])) {
                            $_value = new \ML\JsonLD\TypedValue($value['value'], $value['datatype']);
                        } else {
                            $_value = $value['value'];
                        }
                    } else {
                        throw new EasyRdf_Exception(
                            "Unable to serialise object to JSON-LD: ".$value['type']
                        );
                    }

                    if ($property == "http://www.w3.org/1999/02/22-rdf-syntax-ns#type") {
                        $node->addType($_value);
                    } else {
                        $node->addPropertyValue($property, $_value);
                    }
                }
            }
        }

        // OPTIONS
        $use_native_types = !(isset($options['expand_native_types']) and $options['expand_native_types'] == true);
        $should_compact = (isset($options['compact']) and $options['compact'] == true);

        // expanded form
        $data = $ld_graph->toJsonLd($use_native_types);

        if ($should_compact) {
            // compact form
            $compact_context = isset($options['context']) ? $options['context'] : null;
            $compact_options = array(
                'useNativeTypes' => $use_native_types,
                'base' => $graph->getUri()
            );

            $data = \ML\JsonLD\JsonLD::compact($data, $compact_context, $compact_options);
        }

        return \ML\JsonLD\JsonLD::toString($data);
    }
}
