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
 * Class that represents an RDF resource
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Resource
{
    /** The URI for this resource */
    protected $uri = null;

    /** The Graph that this resource belongs to */
    protected $graph = null;


    /** Constructor
     *
     * * Please do not call new EasyRdf_Resource() directly *
     *
     * To create a new resource use the get method in a graph:
     * $resource = $graph->resource('http://www.example.com/');
     *
     */
    public function __construct($uri, $graph = null)
    {
        if (!is_string($uri) or $uri == null or $uri == '') {
            throw new InvalidArgumentException(
                "\$uri should be a string and cannot be null or empty"
            );
        }

        $this->uri = $uri;

        # Check that $graph is an EasyRdf_Graph object
        if (is_object($graph) and $graph instanceof EasyRdf_Graph) {
            $this->graph = $graph;
        } elseif (!is_null($graph)) {
            throw new InvalidArgumentException(
                "\$graph should be an EasyRdf_Graph object"
            );
        }
    }

    /**
     * Return the graph that this resource belongs to
     *
     * @return EasyRdf_Graph
     */
    public function getGraph()
    {
        return $this->graph;
    }

    /** Returns the URI for the resource.
     *
     * @return string  URI of this resource.
     */
    public function getUri()
    {
        return $this->uri;
    }

    /** Check to see if a resource is a blank node.
     *
     * @return bool True if this resource is a blank node.
     */
    public function isBNode()
    {
        if (substr($this->uri, 0, 2) == '_:') {
            return true;
        } else {
            return false;
        }
    }

    /** Get the identifier for a blank node
     *
     * Returns null if the resource is not a blank node.
     *
     * @return string The identifer for the bnode
     */
    public function getBNodeId()
    {
        if (substr($this->uri, 0, 2) == '_:') {
            return substr($this->uri, 2);
        } else {
            return null;
        }
    }

    /** Get a the prefix of the namespace that this resource is part of
     *
     * This method will return null the resource isn't part of any
     * registered namespace.
     *
     * @return string The namespace prefix of the resource (e.g. foaf)
     */
    public function prefix()
    {
        return EasyRdf_Namespace::prefixOfUri($this->uri);
    }

    /** Get a shortened version of the resources URI.
     *
     * This method will return the full URI if the resource isn't part of any
     * registered namespace.
     *
     * @return string The shortened URI of this resource (e.g. foaf:name)
     */
    public function shorten()
    {
        return EasyRdf_Namespace::shorten($this->uri);
    }

    /** Gets the local name of the URI of this resource
     *
     * The local name is defined as the part of the URI string
     * after the last occurrence of the '#', ':' or '/' character.
     *
     * @return string The local name
     */
    public function localName()
    {
        if (preg_match("|([^#:/]+)$|", $this->uri, $matches)) {
            return $matches[1];
        }
    }

    /** Parse the URI of the resource and return as a ParsedUri object
     *
     * @return EasyRdf_ParsedUri
     */
    public function parseUri()
    {
        return new EasyRdf_ParsedUri($this->uri);
    }

    /** Generates an HTML anchor tag, linking to this resource.
     *
     * If no text is given, then the URI also uses as the link text.
     *
     * @param  string  $text    Text for the link.
     * @param  array   $options Associative array of attributes for the anchor tag
     * @return string  The HTML link string
     */
    public function htmlLink($text = null, $options = array())
    {
        $options = array_merge(array('href' => $this->uri), $options);
        if ($text === null) {
            $text = $this->uri;
        }

        $html = "<a";
        foreach ($options as $key => $value) {
            if (!preg_match('/^[-\w]+$/', $key)) {
                throw new InvalidArgumentException(
                    "\$options should use valid attribute names as keys"
                );
            }

            $html .= " ".htmlspecialchars($key)."=\"".
                         htmlspecialchars($value)."\"";
        }
        $html .= ">".htmlspecialchars($text)."</a>";

        return $html;
    }

    /** Returns the properties of the resource as an RDF/PHP associative array
     *
     * For example:
     * array('type' => 'uri', 'value' => 'http://www.example.com/')
     *
     * @return array  The properties of the resource
     */
    public function toRdfPhp()
    {
        if ($this->isBNode()) {
            return array('type' => 'bnode', 'value' => $this->uri);
        } else {
            return array('type' => 'uri', 'value' => $this->uri);
        }
    }

    /** Return pretty-print view of the resource
     *
     * @param  string $format Either 'html' or 'text'
     * @param  string $color The colour of the text
     * @return string
     */
    public function dumpValue($format = 'html', $color = 'blue')
    {
        return EasyRdf_Utils::dumpResourceValue($this, $format, $color);
    }

    /** Magic method to return URI of resource when casted to string
     *
     * @return string The URI of the resource
     */
    public function __toString()
    {
        return $this->uri;
    }



    /** Throw can exception if the resource does not belong to a graph
     *  @ignore
     */
    protected function checkHasGraph()
    {
        if (!$this->graph) {
            throw new EasyRdf_Exception(
                "EasyRdf_Resource is not part of a graph."
            );
        }
    }

    /** Perform a load (download of remote URI) of the resource into the graph
     *
     * The document type is optional but should be specified if it
     * can't be guessed or got from the HTTP headers.
     *
     * @param  string  $format  Optional format of the data (eg. rdfxml)
     */
    public function load($format = null)
    {
        $this->checkHasGraph();
        return $this->graph->load($this->uri, $format);
    }

    /** Delete a property (or optionally just a specific value)
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  object  $value The value to delete (null to delete all values)
     * @return null
     */
    public function delete($property, $value = null)
    {
        $this->checkHasGraph();
        return $this->graph->delete($this->uri, $property, $value);
    }

    /** Add values to for a property of the resource
     *
     * Example:
     *   $resource->add('prefix:property', 'value');
     *
     * @param  mixed $property   The property name
     * @param  mixed $value      The value for the property
     * @return integer           The number of values added (1 or 0)
     */
    public function add($property, $value)
    {
        $this->checkHasGraph();
        return $this->graph->add($this->uri, $property, $value);
    }

    /** Add a literal value as a property of the resource
     *
     * The value can either be a single value or an array of values.
     *
     * Example:
     *   $resource->add('dc:title', 'Title of Page');
     *
     * @param  mixed  $property  The property name
     * @param  mixed  $values    The value or values for the property
     * @param  string $lang      The language of the literal
     * @return integer           The number of values added
     */
    public function addLiteral($property, $values, $lang = null)
    {
        $this->checkHasGraph();
        return $this->graph->addLiteral($this->uri, $property, $values, $lang);
    }

    /** Add a resource as a property of the resource
     *
     * Example:
     *   $bob->add('foaf:knows', 'http://example.com/alice');
     *
     * @param  mixed $property   The property name
     * @param  mixed $resource2  The resource to be the value of the property
     * @return integer           The number of values added (1 or 0)
     */
    public function addResource($property, $resource2)
    {
        $this->checkHasGraph();
        return $this->graph->addResource($this->uri, $property, $resource2);
    }

    /** Set value for a property
     *
     * The new value(s) will replace the existing values for the property.
     * The name of the property should be a string.
     * If you set a property to null or an empty array, then the property
     * will be deleted.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  mixed   $value    The value for the property.
     * @return integer           The number of values added (1 or 0)
     */
    public function set($property, $value)
    {
        $this->checkHasGraph();
        return $this->graph->set($this->uri, $property, $value);
    }

    /** Get a single value for a property
     *
     * If multiple values are set for a property then the value returned
     * may be arbitrary.
     *
     * If $property is an array, then the first item in the array that matches
     * a property that exists is returned.
     *
     * This method will return null if the property does not exist.
     *
     * @param  string|array $property The name of the property (e.g. foaf:name)
     * @param  string       $type     The type of value to filter by (e.g. literal or resource)
     * @param  string       $lang     The language to filter by (e.g. en)
     * @return mixed                  A value associated with the property
     */
    public function get($property, $type = null, $lang = null)
    {
        $this->checkHasGraph();
        return $this->graph->get($this->uri, $property, $type, $lang);
    }

    /** Get a single literal value for a property of the resource
     *
     * If multiple values are set for a property then the value returned
     * may be arbitrary.
     *
     * This method will return null if there is not literal value for the
     * property.
     *
     * @param  string|array $property The name of the property (e.g. foaf:name)
     * @param  string       $lang     The language to filter by (e.g. en)
     * @return object EasyRdf_Literal Literal value associated with the property
     */
    public function getLiteral($property, $lang = null)
    {
        $this->checkHasGraph();
        return $this->graph->get($this->uri, $property, 'literal', $lang);
    }

    /** Get a single resource value for a property of the resource
     *
     * If multiple values are set for a property then the value returned
     * may be arbitrary.
     *
     * This method will return null if there is not resource for the
     * property.
     *
     * @param  string|array $property The name of the property (e.g. foaf:name)
     * @return object EasyRdf_Resource Resource associated with the property
     */
    public function getResource($property)
    {
        $this->checkHasGraph();
        return $this->graph->get($this->uri, $property, 'resource');
    }

    /** Get all values for a property
     *
     * This method will return an empty array if the property does not exist.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  string  $type     The type of value to filter by (e.g. literal)
     * @param  string  $lang     The language to filter by (e.g. en)
     * @return array             An array of values associated with the property
     */
    public function all($property, $type = null, $lang = null)
    {
        $this->checkHasGraph();
        return $this->graph->all($this->uri, $property, $type, $lang);
    }

    /** Get all literal values for a property of the resource
     *
     * This method will return an empty array if the resource does not
     * has any literal values for that property.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  string  $lang     The language to filter by (e.g. en)
     * @return array             An array of values associated with the property
     */
    public function allLiterals($property, $lang = null)
    {
        $this->checkHasGraph();
        return $this->graph->all($this->uri, $property, 'literal', $lang);
    }

    /** Get all resources for a property of the resource
     *
     * This method will return an empty array if the resource does not
     * has any resources for that property.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @return array             An array of values associated with the property
     */
    public function allResources($property)
    {
        $this->checkHasGraph();
        return $this->graph->all($this->uri, $property, 'resource');
    }

    /** Count the number of values for a property of a resource
     *
     * This method will return 0 if the property does not exist.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  string  $type     The type of value to filter by (e.g. literal)
     * @param  string  $lang     The language to filter by (e.g. en)
     * @return integer           The number of values associated with the property
     */
    public function countValues($property, $type = null, $lang = null)
    {
        $this->checkHasGraph();
        return $this->graph->countValues($this->uri, $property, $type, $lang);
    }

    /** Concatenate all values for a property into a string.
     *
     * The default is to join the values together with a space character.
     * This method will return an empty string if the property does not exist.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  string  $glue     The string to glue the values together with.
     * @param  string  $lang     The language to filter by (e.g. en)
     * @return string            Concatenation of all the values.
     */
    public function join($property, $glue = ' ', $lang = null)
    {
        $this->checkHasGraph();
        return $this->graph->join($this->uri, $property, $glue, $lang);
    }

    /** Get a list of the full URIs for the properties of this resource.
     *
     * This method will return an empty array if the resource has no properties.
     *
     * @return array            Array of full URIs
     */
    public function propertyUris()
    {
        $this->checkHasGraph();
        return $this->graph->propertyUris($this->uri);
    }

    /** Get a list of all the shortened property names (qnames) for a resource.
     *
     * This method will return an empty array if the resource has no properties.
     *
     * @return array            Array of shortened URIs
     */
    public function properties()
    {
        $this->checkHasGraph();
        return $this->graph->properties($this->uri);
    }

    /** Get a list of the full URIs for the properties that point to this resource.
     *
     * @return array   Array of full property URIs
     */
    public function reversePropertyUris()
    {
        $this->checkHasGraph();
        return $this->graph->reversePropertyUris($this->uri);
    }

    /** Check to see if a property exists for this resource.
     *
     * This method will return true if the property exists.
     * If the value parameter is given, then it will only return true
     * if the value also exists for that property.
     *
     * @param  string  $property The name of the property (e.g. foaf:name)
     * @param  mixed   $value    An optional value of the property
     * @return bool              True if value the property exists.
     */
    public function hasProperty($property, $value = null)
    {
        $this->checkHasGraph();
        return $this->graph->hasProperty($this->uri, $property, $value);
    }

    /** Get a list of types for a resource.
     *
     * The types will each be a shortened URI as a string.
     * This method will return an empty array if the resource has no types.
     *
     * @return array All types assocated with the resource (e.g. foaf:Person)
     */
    public function types()
    {
        $this->checkHasGraph();
        return $this->graph->types($this->uri);
    }

    /** Get a single type for a resource.
     *
     * The type will be a shortened URI as a string.
     * If the resource has multiple types then the type returned
     * may be arbitrary.
     * This method will return null if the resource has no type.
     *
     * @return string A type assocated with the resource (e.g. foaf:Person)
     */
    public function type()
    {
        $this->checkHasGraph();
        return $this->graph->type($this->uri);
    }

    /** Get a single type for a resource, as a resource.
     *
     * The type will be returned as an EasyRdf_Resource.
     * If the resource has multiple types then the type returned
     * may be arbitrary.
     * This method will return null if the resource has no type.
     *
     * @return EasyRdf_Resource A type assocated with the resource.
     */
    public function typeAsResource()
    {
        $this->checkHasGraph();
        return $this->graph->typeAsResource($this->uri);
    }

    /**
     * Get a list of types for a resource, as Resources.
     *
     * @return EasyRdf_Resource[]
     * @throws EasyRdf_Exception
     */
    public function typesAsResources()
    {
        $this->checkHasGraph();
        return $this->graph->typesAsResources($this->uri);
    }

    /** Check if a resource is of the specified type
     *
     * @param  string  $type The type to check (e.g. foaf:Person)
     * @return boolean       True if resource is of specified type.
     */
    public function isA($type)
    {
        $this->checkHasGraph();
        return $this->graph->isA($this->uri, $type);
    }

    /** Add one or more rdf:type properties to the resource
     *
     * @param  string  $types    One or more types to add (e.g. foaf:Person)
     * @return integer           The number of types added
     */
    public function addType($types)
    {
        $this->checkHasGraph();
        return $this->graph->addType($this->uri, $types);
    }

    /** Change the rdf:type property for the resource
     *
     * Note that the PHP class of the resource will not change.
     *
     * @param  string  $type     The new type (e.g. foaf:Person)
     * @return integer           The number of types added
     */
    public function setType($type)
    {
        $this->checkHasGraph();
        return $this->graph->setType($this->uri, $type);
    }

    /** Get the primary topic of this resource.
     *
     * Returns null if no primary topic is available.
     *
     * @return EasyRdf_Resource The primary topic of this resource.
     */
    public function primaryTopic()
    {
        $this->checkHasGraph();
        return $this->graph->primaryTopic($this->uri);
    }

    /** Get a human readable label for this resource
     *
     * This method will check a number of properties for the resource
     * (in the order: skos:prefLabel, rdfs:label, foaf:name, dc:title)
     * and return an approriate first that is available. If no label
     * is available then it will return null.
     *
     * @return string A label for the resource.
     */
    public function label($lang = null)
    {
        $this->checkHasGraph();
        return $this->graph->label($this->uri, $lang);
    }

    /** Return a human readable view of the resource and its properties
     *
     * This method is intended to be a debugging aid and will
     * print a resource and its properties.
     *
     * @param  string $format   Either 'html' or 'text'
     * @return string
     */
    public function dump($format = 'html')
    {
        $this->checkHasGraph();
        return $this->graph->dumpResource($this->uri, $format);
    }

    /** Magic method to get a property of a resource
     *
     * Note that only properties in the default namespace can be accessed in this way.
     *
     * Example:
     *   $value = $resource->title;
     *
     * @see EasyRdf_Namespace::setDefault()
     * @param  string $name The name of the property
     * @return string       A single value for the named property
     */
    public function __get($name)
    {
        return $this->graph->get($this->uri, $name);
    }

    /** Magic method to set the value for a property of a resource
     *
     * Note that only properties in the default namespace can be accessed in this way.
     *
     * Example:
     *   $resource->title = 'Title';
     *
     * @see EasyRdf_Namespace::setDefault()
     * @param  string $name The name of the property
     * @param  string $value The value for the property
     */
    public function __set($name, $value)
    {
        return $this->graph->set($this->uri, $name, $value);
    }

    /** Magic method to check if a property exists
     *
     * Note that only properties in the default namespace can be accessed in this way.
     *
     * Example:
     *   if (isset($resource->title)) { blah(); }
     *
     * @see EasyRdf_Namespace::setDefault()
     * @param string $name The name of the property
     */
    public function __isset($name)
    {
        return $this->graph->hasProperty($this->uri, $name);
    }

    /** Magic method to delete a property of the resource
     *
     * Note that only properties in the default namespace can be accessed in this way.
     *
     * Example:
     *   unset($resource->title);
     *
     * @see EasyRdf_Namespace::setDefault()
     * @param string $name The name of the property
     */
    public function __unset($name)
    {
        return $this->graph->delete($this->uri, $name);
    }
}
