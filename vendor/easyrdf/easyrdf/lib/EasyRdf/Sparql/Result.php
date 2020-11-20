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
 * Class for returned for SPARQL SELECT and ASK query responses.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Sparql_Result extends ArrayIterator
{
    private $type = null;
    private $boolean = null;

    private $ordered = null;
    private $distinct = null;
    private $fields = array();

    /** A constant for the SPARQL Query Results XML Format namespace */
    const SPARQL_XML_RESULTS_NS = 'http://www.w3.org/2005/sparql-results#';

    /** Create a new SPARQL Result object
     *
     * You should not normally need to create a SPARQL result
     * object directly - it will be constructed automatically
     * for you by EasyRdf_Sparql_Client.
     *
     * @param string $data      The SPARQL result body
     * @param string $mimeType  The MIME type of the result
     */
    public function __construct($data, $mimeType)
    {
        if ($mimeType == 'application/sparql-results+xml') {
            return $this->parseXml($data);
        } elseif ($mimeType == 'application/sparql-results+json') {
            return $this->parseJson($data);
        } else {
            throw new EasyRdf_Exception(
                "Unsupported SPARQL Query Results format: $mimeType"
            );
        }
    }

    /** Get the query result type (boolean/bindings)
     *
     * ASK queries return a result of type 'boolean'.
     * SELECT query return a result of type 'bindings'.
     *
     * @return string The query result type.
     */
    public function getType()
    {
        return $this->type;
    }

    /** Return the boolean value of the query result
     *
     * If the query was of type boolean then this method will
     * return either true or false. If the query was of some other
     * type then this method will return null.
     *
     * @return boolean The result of the query.
     */
    public function getBoolean()
    {
        return $this->boolean;
    }

    /** Return true if the result of the query was true.
     *
     * @return boolean True if the query result was true.
     */
    public function isTrue()
    {
        return $this->boolean == true;
    }

    /** Return false if the result of the query was false.
     *
     * @return boolean True if the query result was false.
     */
    public function isFalse()
    {
        return $this->boolean == false;
    }

    /** Return the number of fields in a query result of type bindings.
     *
     * @return integer The number of fields.
     */
    public function numFields()
    {
        return count($this->fields);
    }

    /** Return the number of rows in a query result of type bindings.
     *
     * @return integer The number of rows.
     */
    public function numRows()
    {
        return count($this);
    }

    /** Get the field names in a query result of type bindings.
     *
     * @return array The names of the fields in the result.
     */
    public function getFields()
    {
        return $this->fields;
    }

    /** Return a human readable view of the query result.
     *
     * This method is intended to be a debugging aid and will
     * return a pretty-print view of the query result.
     *
     * @param  string  $format  Either 'text' or 'html'
     */
    public function dump($format = 'html')
    {
        if ($this->type == 'bindings') {
            $result = '';
            if ($format == 'html') {
                $result .= "<table class='sparql-results' style='border-collapse:collapse'>";
                $result .= "<tr>";
                foreach ($this->fields as $field) {
                    $result .= "<th style='border:solid 1px #000;padding:4px;".
                               "vertical-align:top;background-color:#eee;'>".
                               "?$field</th>";
                }
                $result .= "</tr>";
                foreach ($this as $row) {
                    $result .= "<tr>";
                    foreach ($this->fields as $field) {
                        if (isset($row->$field)) {
                            $result .= "<td style='border:solid 1px #000;padding:4px;".
                                       "vertical-align:top'>".
                                       $row->$field->dumpValue($format)."</td>";
                        } else {
                            $result .= "<td>&nbsp;</td>";
                        }
                    }
                    $result .= "</tr>";
                }
                $result .= "</table>";
            } else {
                // First calculate the width of each comment
                $colWidths = array();
                foreach ($this->fields as $field) {
                    $colWidths[$field] = strlen($field);
                }

                $textData = array();
                foreach ($this as $row) {
                    $textRow = array();
                    foreach ($row as $k => $v) {
                        $textRow[$k] = $v->dumpValue('text');
                        $width = strlen($textRow[$k]);
                        if ($colWidths[$k] < $width) {
                            $colWidths[$k] = $width;
                        }
                    }
                    $textData[] = $textRow;
                }

                // Create a horizontal rule
                $hr = "+";
                foreach ($colWidths as $k => $v) {
                    $hr .= "-".str_repeat('-', $v).'-+';
                }

                // Output the field names
                $result .= "$hr\n|";
                foreach ($this->fields as $field) {
                    $result .= ' '.str_pad("?$field", $colWidths[$field]).' |';
                }

                // Output each of the rows
                $result .= "\n$hr\n";
                foreach ($textData as $textRow) {
                    $result .= '|';
                    foreach ($textRow as $k => $v) {
                        $result .= ' '.str_pad($v, $colWidths[$k]).' |';
                    }
                    $result .= "\n";
                }
                $result .= "$hr\n";

            }
            return $result;
        } elseif ($this->type == 'boolean') {
            $str = ($this->boolean ? 'true' : 'false');
            if ($format == 'html') {
                return "<p>Result: <span style='font-weight:bold'>$str</span></p>";
            } else {
                return "Result: $str";
            }
        } else {
            throw new EasyRdf_Exception(
                "Failed to dump SPARQL Query Results format, unknown type: ". $this->type
            );
        }
    }

    /** Create a new EasyRdf_Resource or EasyRdf_Literal depending
     *  on the type of data passed in.
     *
     * @ignore
     */
    protected function newTerm($data)
    {
        switch($data['type']) {
            case 'bnode':
                return new EasyRdf_Resource('_:'.$data['value']);
            case 'uri':
                return new EasyRdf_Resource($data['value']);
            case 'literal':
            case 'typed-literal':
                return EasyRdf_Literal::create($data);
            default:
                throw new EasyRdf_Exception(
                    "Failed to parse SPARQL Query Results format, unknown term type: ".
                    $data['type']
                );
        }
    }

    /** Parse a SPARQL result in the XML format into the object.
     *
     * @ignore
     */
    protected function parseXml($data)
    {
        $doc = new DOMDocument();
        $doc->loadXML($data);

        # Check for valid root node.
        if ($doc->hasChildNodes() == false or
            $doc->childNodes->length != 1 or
            $doc->firstChild->nodeName != 'sparql' or
            $doc->firstChild->namespaceURI != self::SPARQL_XML_RESULTS_NS) {
            throw new EasyRdf_Exception(
                "Incorrect root node in SPARQL XML Query Results format"
            );
        }

        # Is it the result of an ASK query?
        $boolean = $doc->getElementsByTagName('boolean');
        if ($boolean->length) {
            $this->type = 'boolean';
            $value = $boolean->item(0)->nodeValue;
            $this->boolean = $value == 'true' ? true : false;
            return;
        }

        # Get a list of variables from the header
        $head = $doc->getElementsByTagName('head');
        if ($head->length) {
            $variables = $head->item(0)->getElementsByTagName('variable');
            foreach ($variables as $variable) {
                $this->fields[] = $variable->getAttribute('name');
            }
        }

        # Is it the result of a SELECT query?
        $resultstag = $doc->getElementsByTagName('results');
        if ($resultstag->length) {
            $this->type = 'bindings';
            $results = $resultstag->item(0)->getElementsByTagName('result');
            foreach ($results as $result) {
                $bindings = $result->getElementsByTagName('binding');
                $t = new stdClass();
                foreach ($bindings as $binding) {
                    $key = $binding->getAttribute('name');
                    foreach ($binding->childNodes as $node) {
                        if ($node->nodeType != XML_ELEMENT_NODE) {
                            continue;
                        }
                        $t->$key = $this->newTerm(
                            array(
                                'type' => $node->nodeName,
                                'value' => $node->nodeValue,
                                'lang' => $node->getAttribute('xml:lang'),
                                'datatype' => $node->getAttribute('datatype')
                            )
                        );
                        break;
                    }
                }
                $this[] = $t;
            }
            return $this;
        }

        throw new EasyRdf_Exception(
            "Failed to parse SPARQL XML Query Results format"
        );
    }

    /** Parse a SPARQL result in the JSON format into the object.
     *
     * @ignore
     */
    protected function parseJson($data)
    {
        // Decode JSON to an array
        $data = json_decode($data, true);

        if (isset($data['boolean'])) {
            $this->type = 'boolean';
            $this->boolean = $data['boolean'];
        } elseif (isset($data['results'])) {
            $this->type = 'bindings';
            if (isset($data['head']['vars'])) {
                $this->fields = $data['head']['vars'];
            }

            foreach ($data['results']['bindings'] as $row) {
                $t = new stdClass();
                foreach ($row as $key => $value) {
                    $t->$key = $this->newTerm($value);
                }
                $this[] = $t;
            }
        } else {
            throw new EasyRdf_Exception(
                "Failed to parse SPARQL JSON Query Results format"
            );
        }
    }

    /** Magic method to return value of the result to string
     *
     * If this is a boolean result then it will return 'true' or 'false'.
     * If it is a bindings type, then it will dump as a text based table.
     *
     * @return string A string representation of the result.
     */
    public function __toString()
    {
        if ($this->type == 'boolean') {
            return $this->boolean ? 'true' : 'false';
        } else {
            return $this->dump('text');
        }
    }
}
