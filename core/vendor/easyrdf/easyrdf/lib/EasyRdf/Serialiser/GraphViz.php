<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2012-2013 Nicholas J Humfrey.  All rights reserved.
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
 * Class to serialise an EasyRdf_Graph to GraphViz
 *
 * Depends upon the GraphViz 'dot' command line tools to render images.
 *
 * See http://www.graphviz.org/ for more information.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2012-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Serialiser_GraphViz extends EasyRdf_Serialiser
{
    private $dotCommand = 'dot';
    private $useLabels = false;
    private $onlyLabelled = false;
    private $attributes = array('charset' => 'utf-8');

    /**
     * Constructor
     *
     * @return object EasyRdf_Serialiser_GraphViz
     */
    public function __construct()
    {
    }

    /**
     * Set the path to the GraphViz 'dot' command
     *
     * Default is to search PATH for the command 'dot'.
     *
     * @param string $cmd   The path to the 'dot' command.
     * @return object EasyRdf_Serialiser_GraphViz
     */
    public function setDotCommand($cmd)
    {
        $this->dotCommand = $cmd;
        return $this;
    }

    /**
     * Get the path to the GraphViz 'dot' command
     *
     * The default value is simply 'dot'
     *
     * @return string The path to the 'dot' command.
     */
    public function getDotCommand()
    {
        return $this->dotCommand;
    }

    /**
     * Turn on/off the option to display labels instead of URIs.
     *
     * When this option is turned on, then labels for resources will
     * be displayed instead of the full URI of a resource. This makes
     * it simpler to create friendly diagrams that non-technical people
     * can understand.
     *
     * This option is turned off by default.
     *
     * @param bool $useLabels   A boolean value to turn labels on and off
     * @return object EasyRdf_Serialiser_GraphViz
     */
    public function setUseLabels($useLabels)
    {
        $this->useLabels = $useLabels;
        return $this;
    }

    /**
     * Get the state of the use labels option
     *
     * @return bool The current state of the use labels option
     */
    public function getUseLabels()
    {
        return $this->useLabels;
    }

    /**
     * Turn on/off the option to only display nodes and edges with labels
     *
     * When this option is turned on, then only nodes (resources and literals)
     * and edges (properties) will only be displayed if they have a label. You
     * can use this option, to create concise, diagrams of your data, rather than
     * the RDF.
     *
     * This option is turned off by default.
     *
     * @param bool $onlyLabelled   A boolean value to enable/display only labelled items
     * @return object EasyRdf_Serialiser_GraphViz
     */
    public function setOnlyLabelled($onlyLabelled)
    {
        $this->onlyLabelled = $onlyLabelled;
        return $this;
    }

    /**
     * Get the state of the only Only Labelled option
     *
     * @return bool The current state of the Only Labelled option
     */
    public function getOnlyLabelled()
    {
        return $this->onlyLabelled;
    }

    /**
     * Set an attribute on the GraphViz graph
     *
     * Example:
     *     $serialiser->setAttribute('rotate', 90);
     *
     * See the GraphViz tool documentation for information about the
     * available attributes.
     *
     * @param string $name    The name of the attribute
     * @param string $value   The value for the attribute
     * @return object EasyRdf_Serialiser_GraphViz
     */
    public function setAttribute($name, $value)
    {
        $this->attributes[$name] = $value;
        return $this;
    }

    /**
     * Get an attribute of the GraphViz graph
     *
     * @param string $name    Attribute name
     * @return string The value of the graph attribute
     */
    public function getAttribute($name)
    {
        return $this->attributes[$name];
    }

    /**
     * Convert an EasyRdf object into a GraphViz node identifier
     *
     * @ignore
     */
    protected function nodeName($entity)
    {
        if ($entity instanceof EasyRdf_Resource) {
            if ($entity->isBNode()) {
                return "B".$entity->getUri();
            } else {
                return "R".$entity->getUri();
            }
        } else {
            return "L".$entity;
        }
    }

    /**
     * Internal function to escape a string into DOT safe syntax
     *
     * @ignore
     */
    protected function escape($input)
    {
        if (preg_match('/^([a-z_][a-z_0-9]*|-?(\.[0-9]+|[0-9]+(\.[0-9]*)?))$/i', $input)) {
            return $input;
        } else {
            return '"'.str_replace(
                array("\r\n", "\n", "\r", '"'),
                array('\n',   '\n', '\n', '\"'),
                $input
            ).'"';
        }
    }

    /**
     * Internal function to escape an associate array of attributes and
     * turns it into a DOT notation string
     *
     * @ignore
     */
    protected function escapeAttributes($array)
    {
        $items = '';
        foreach ($array as $k => $v) {
            $items[] = $this->escape($k).'='.$this->escape($v);
        }
        return '['.implode(',', $items).']';
    }

    /**
     * Internal function to create dot syntax line for either a node or an edge
     *
     * @ignore
     */
    protected function serialiseRow($node1, $node2 = null, $attributes = array())
    {
        $result = '  '.$this->escape($node1);
        if ($node2) {
            $result .= ' -> '.$this->escape($node2);
        }
        if (count($attributes)) {
            $result .= ' '.$this->escapeAttributes($attributes);
        }
        return $result.";\n";
    }

    /**
     * Internal function to serialise an EasyRdf_Graph into a DOT formatted string
     *
     * @ignore
     */
    protected function serialiseDot($graph)
    {
        $result = "digraph {\n";

        // Write the graph attributes
        foreach ($this->attributes as $k => $v) {
            $result .= '  '.$this->escape($k).'='.$this->escape($v).";\n";
        }

        // Go through each of the properties and write the edges
        $nodes = array();
        $result .= "\n  // Edges\n";
        foreach ($graph->resources() as $resource) {
            $name1 = $this->nodeName($resource);
            foreach ($resource->propertyUris() as $property) {
                $label = null;
                if ($this->useLabels) {
                    $label = $graph->resource($property)->label();
                }
                if ($label === null) {
                    if ($this->onlyLabelled == true) {
                        continue;
                    } else {
                        $label = EasyRdf_Namespace::shorten($property);
                    }
                }
                foreach ($resource->all("<$property>") as $value) {
                    $name2 = $this->nodeName($value);
                    $nodes[$name1] = $resource;
                    $nodes[$name2] = $value;
                    $result .= $this->serialiseRow(
                        $name1,
                        $name2,
                        array('label' => $label)
                    );
                }
            }
        }

        ksort($nodes);

        $result .= "\n  // Nodes\n";
        foreach ($nodes as $name => $node) {
            $type = substr($name, 0, 1);
            $label = '';
            if ($type == 'R') {
                if ($this->useLabels) {
                    $label = $node->label();
                }
                if (!$label) {
                    $label = $node->shorten();
                }
                if (!$label) {
                    $label = $node->getURI();
                }
                $result .= $this->serialiseRow(
                    $name,
                    null,
                    array(
                        'URL'   => $node->getURI(),
                        'label' => $label,
                        'shape' => 'ellipse',
                        'color' => 'blue'
                    )
                );
            } elseif ($type == 'B') {
                if ($this->useLabels) {
                    $label = $node->label();
                }
                $result .= $this->serialiseRow(
                    $name,
                    null,
                    array(
                        'label' => $label,
                        'shape' => 'circle',
                        'color' => 'green'
                    )
                );
            } else {
                $result .= $this->serialiseRow(
                    $name,
                    null,
                    array(
                        'label' => strval($node),
                        'shape' => 'record',
                    )
                );
            }

        }

        $result .= "}\n";

        return $result;
    }

    /**
     * Internal function to render a graph into an image
     *
     * @ignore
     */
    public function renderImage($graph, $format = 'png')
    {
        $dot = $this->serialiseDot($graph);

        return EasyRdf_Utils::execCommandPipe(
            $this->dotCommand,
            array("-T$format"),
            $dot
        );
    }

    /**
     * Serialise an EasyRdf_Graph into a GraphViz dot document.
     *
     * Supported output format names: dot, gif, png, svg
     *
     * @param EasyRdf_Graph $graph  An EasyRdf_Graph object.
     * @param string        $format The name of the format to convert to.
     * @param array         $options
     * @throws EasyRdf_Exception
     * @return string The RDF in the new desired format.
     */
    public function serialise($graph, $format, array $options = array())
    {
        parent::checkSerialiseParams($graph, $format);

        switch($format) {
            case 'dot':
                return $this->serialiseDot($graph);
            case 'png':
            case 'gif':
            case 'svg':
                return $this->renderImage($graph, $format);
            default:
                throw new EasyRdf_Exception(
                    "EasyRdf_Serialiser_GraphViz does not support: $format"
                );
        }
    }
}
