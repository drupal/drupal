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
 * Class to serialise an EasyRdf_Graph to RDF/XML
 * with no external dependancies.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Serialiser_RdfXml extends EasyRdf_Serialiser
{
    private $outputtedResources = array();

    /** A constant for the RDF Type property URI */
    const RDF_XML_LITERAL = 'http://www.w3.org/1999/02/22-rdf-syntax-ns#XMLLiteral';

    /**
     * Protected method to serialise an object node into an XML object
     * @ignore
     */
    protected function rdfxmlObject($property, $obj, $depth)
    {
        $indent = str_repeat('  ', $depth);
        if (is_object($obj) and $obj instanceof EasyRdf_Resource) {
            $pcount = count($obj->propertyUris());
            $rpcount = $this->reversePropertyCount($obj);
            $alreadyOutput = isset($this->outputtedResources[$obj->getUri()]);

            $tag = "$indent<$property";
            if ($obj->isBNode()) {
                if ($alreadyOutput or $rpcount > 1 or $pcount == 0) {
                    $tag .= " rdf:nodeID=\"".htmlspecialchars($obj->getBNodeId()).'"';
                }
            } else {
                if ($alreadyOutput or $rpcount != 1 or $pcount == 0) {
                    $tag .= " rdf:resource=\"".htmlspecialchars($obj->getURI()).'"';
                }
            }

            if ($alreadyOutput == false and $rpcount == 1 and $pcount > 0) {
                $xml = $this->rdfxmlResource($obj, false, $depth+1);
                if ($xml) {
                    return "$tag>$xml$indent</$property>\n\n";
                } else {
                    return '';
                }
            } else {
                return $tag."/>\n";
            }

        } elseif (is_object($obj) and $obj instanceof EasyRdf_Literal) {
            $atrributes = "";
            $datatype = $obj->getDatatypeUri();
            if ($datatype) {
                if ($datatype == self::RDF_XML_LITERAL) {
                    $atrributes .= " rdf:parseType=\"Literal\"";
                    $value = strval($obj);
                } else {
                    $datatype = htmlspecialchars($datatype);
                    $atrributes .= " rdf:datatype=\"$datatype\"";
                }
            } elseif ($obj->getLang()) {
                $atrributes .= ' xml:lang="'.
                               htmlspecialchars($obj->getLang()).'"';
            }

            // Escape the value
            if (!isset($value)) {
                $value = htmlspecialchars(strval($obj));
            }

            return "$indent<$property$atrributes>$value</$property>\n";
        } else {
            throw new EasyRdf_Exception(
                "Unable to serialise object to xml: ".getType($obj)
            );
        }
    }

    /**
     * Protected method to serialise a whole resource and its properties
     * @ignore
     */
    protected function rdfxmlResource($res, $showNodeId, $depth = 1)
    {
        // Keep track of the resources we have already serialised
        if (isset($this->outputtedResources[$res->getUri()])) {
            return '';
        } else {
            $this->outputtedResources[$res->getUri()] = true;
        }

        // If the resource has no properties - don't serialise it
        $properties = $res->propertyUris();
        if (count($properties) == 0) {
            return '';
        }

        $type = $res->type();
        if ($type) {
            $this->addPrefix($type);
        } else {
            $type = 'rdf:Description';
        }

        $indent = str_repeat('  ', $depth);
        $xml = "\n$indent<$type";
        if ($res->isBNode()) {
            if ($showNodeId) {
                $xml .= ' rdf:nodeID="'.htmlspecialchars($res->getBNodeId()).'"';
            }
        } else {
            $xml .= ' rdf:about="'.htmlspecialchars($res->getUri()).'"';
        }
        $xml .= ">\n";

        if ($res instanceof EasyRdf_Container) {
            foreach ($res as $item) {
                $xml .= $this->rdfxmlObject('rdf:li', $item, $depth+1);
            }
        } else {
            foreach ($properties as $property) {
                $short = EasyRdf_Namespace::shorten($property, true);
                if ($short) {
                    $this->addPrefix($short);
                    $objects = $res->all("<$property>");
                    if ($short == 'rdf:type') {
                        array_shift($objects);
                    }
                    foreach ($objects as $object) {
                        $xml .= $this->rdfxmlObject($short, $object, $depth+1);
                    }
                } else {
                    throw new EasyRdf_Exception(
                        "It is not possible to serialse the property ".
                        "'$property' to RDF/XML."
                    );
                }
            }
        }
        $xml .= "$indent</$type>\n";

        return $xml;
    }


    /**
     * Method to serialise an EasyRdf_Graph to RDF/XML
     *
     * @param EasyRdf_Graph $graph   An EasyRdf_Graph object.
     * @param string        $format  The name of the format to convert to.
     * @param array         $options
     * @throws EasyRdf_Exception
     * @return string The RDF in the new desired format.
     */
    public function serialise($graph, $format, array $options = array())
    {
        parent::checkSerialiseParams($graph, $format);

        if ($format != 'rdfxml') {
            throw new EasyRdf_Exception(
                "EasyRdf_Serialiser_RdfXml does not support: $format"
            );
        }

        // store of namespaces to be appended to the rdf:RDF tag
        $this->prefixes = array('rdf' => true);

        // store of the resource URIs we have serialised
        $this->outputtedResources = array();

        $xml = '';

        // Serialise URIs first
        foreach ($graph->resources() as $resource) {
            if (!$resource->isBnode()) {
                $xml .= $this->rdfxmlResource($resource, true);
            }
        }

        // Serialise bnodes afterwards
        foreach ($graph->resources() as $resource) {
            if ($resource->isBnode()) {
                $xml .= $this->rdfxmlResource($resource, true);
            }
        }

        // iterate through namepsaces array prefix and output a string.
        $namespaceStr = '';
        foreach ($this->prefixes as $prefix => $count) {
            $url = EasyRdf_Namespace::get($prefix);
            if (strlen($namespaceStr)) {
                $namespaceStr .= "\n        ";
            }
            $namespaceStr .= ' xmlns:'.$prefix.'="'.htmlspecialchars($url).'"';
        }

        return "<?xml version=\"1.0\" encoding=\"utf-8\" ?>\n".
               "<rdf:RDF". $namespaceStr . ">\n" . $xml . "\n</rdf:RDF>\n";
    }
}
