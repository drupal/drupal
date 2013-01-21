<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2009-2012 Nicholas J Humfrey.
 * Copyright (c) 1997-2006 Aduna (http://www.aduna-software.com/)
 * All rights reserved.
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
 * @copyright  Copyright (c) 2009-2012 Nicholas J Humfrey
 *             Copyright (c) 1997-2006 Aduna (http://www.aduna-software.com/)
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @version    $Id$
 */

/**
 * Class to parse Turtle with no external dependancies.
 *
 * http://www.w3.org/TR/turtle/
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2012 Nicholas J Humfrey
 *             Copyright (c) 1997-2006 Aduna (http://www.aduna-software.com/)
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Parser_Turtle extends EasyRdf_Parser_Ntriples
{
    /**
     * Constructor
     *
     * @return object EasyRdf_Parser_Turtle
     */
    public function __construct()
    {
    }

    /**
     * Parse Turtle into an EasyRdf_Graph
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

        if ($format != 'turtle') {
            throw new EasyRdf_Exception(
                "EasyRdf_Parser_Turtle does not support: $format"
            );
        }

        $this->data = $data;
        $this->len = strlen($data);
        $this->pos = 0;

        $this->namespaces = array();
        $this->subject = null;
        $this->predicate = null;
        $this->object = null;

        $this->resetBnodeMap();

        $c = $this->skipWSC();
        while ($c != -1) {
            $this->parseStatement();
            $c = $this->skipWSC();
        }

        return $this->tripleCount;
    }


    /**
     * Parse a statement [2]
     * @ignore
     */
    protected function parseStatement()
    {
        $c = $this->peek();
        if ($c == '@') {
            $this->parseDirective();
            $this->skipWSC();
            $this->verifyCharacter($this->read(), ".");
        } else {
            $this->parseTriples();
            $this->skipWSC();
            $this->verifyCharacter($this->read(), ".");
        }
    }

    /**
     * Parse a directive [3]
     * @ignore
     */
    protected function parseDirective()
    {
        // Verify that the first characters form the string "prefix"
        $this->verifyCharacter($this->read(), "@");

        $directive = '';

        $c = $this->read();
        while ($c != -1 && !self::isWhitespace($c)) {
            $directive .= $c;
            $c = $this->read();
        }

        if ($directive == "prefix") {
            $this->parsePrefixID();
        } elseif ($directive == "base") {
            $this->parseBase();
        } elseif (strlen($directive) == 0) {
            throw new EasyRdf_Exception(
                "Turtle Parse Error: directive name is missing, expected @prefix or @base"
            );
        } else {
            throw new EasyRdf_Exception(
                "Turtle Parse Error: unknown directive \"@$directive\""
            );
        }
    }

    /**
     * Parse a prefixID [4]
     * @ignore
     */
    protected function parsePrefixID()
    {
        $this->skipWSC();

        // Read prefix ID (e.g. "rdf:" or ":")
        $prefixID = '';

        while (true) {
            $c = $this->read();
            if ($c == ':') {
                $this->unread($c);
                break;
            } elseif (self::isWhitespace($c)) {
                break;
            } elseif ($c == -1) {
                throw new EasyRdf_Exception(
                    "Turtle Parse Error: unexpected end of file while reading prefix id"
                );
            }

            $prefixID .= $c;
        }

        $this->skipWSC();
        $this->verifyCharacter($this->read(), ":");
        $this->skipWSC();

        // Read the namespace URI
        $namespace = $this->parseURI();

        // Store local namespace mapping
        $this->namespaces[$prefixID] = $namespace['value'];
    }

    /**
     * Parse base [5]
     * @ignore
     */
    protected function parseBase()
    {
        $this->skipWSC();

        $baseUri = $this->parseURI();
        $this->baseUri = new EasyRdf_ParsedUri($baseUri['value']);
    }

    /**
     * Parse triples [6]
     * @ignore
     */
    protected function parseTriples()
    {
        $this->parseSubject();
        $this->skipWSC();
        $this->parsePredicateObjectList();

        $this->subject = null;
        $this->predicate = null;
        $this->object = null;
    }

    /**
     * Parse a predicateObjectList [7]
     * @ignore
     */
    protected function parsePredicateObjectList()
    {
        $this->predicate = $this->parsePredicate();

        $this->skipWSC();
        $this->parseObjectList();

        while ($this->skipWSC() == ';') {
            $this->read();

            $c = $this->skipWSC();

            if ($c == '.' || $c == ']') {
                break;
            }

            $this->predicate = $this->parsePredicate();

            $this->skipWSC();

            $this->parseObjectList();
        }
    }

    /**
     * Parse a objectList [8]
     * @ignore
     */
    protected function parseObjectList()
    {
        $this->parseObject();

        while ($this->skipWSC() == ',') {
            $this->read();
            $this->skipWSC();
            $this->parseObject();
        }
    }

    /**
     * Parse a subject [10]
     * @ignore
     */
    protected function parseSubject()
    {
        $c = $this->peek();
        if ($c == '(') {
            $this->subject = $this->parseCollection();
        } elseif ($c == '[') {
            $this->subject = $this->parseImplicitBlank();
        } else {
            $value = $this->parseValue();

            if ($value['type'] == 'uri' or $value['type'] == 'bnode') {
                $this->subject = $value;
            } else {
                throw new EasyRdf_Exception(
                    "Turtle Parse Error: illegal subject type: ".$value['type']
                );
            }
        }
    }

    /**
     * Parse a predicate [11]
     * @ignore
     */
    protected function parsePredicate()
    {
        // Check if the short-cut 'a' is used
        $c1 = $this->read();

        if ($c1 == 'a') {
            $c2 = $this->read();

            if (self::isWhitespace($c2)) {
                // Short-cut is used, return the rdf:type URI
                return array(
                    'type' => 'uri',
                    'value' => EasyRdf_Namespace::get('rdf') . 'type'
                );
            }

            // Short-cut is not used, unread all characters
            $this->unread($c2);
        }
        $this->unread($c1);

        // Predicate is a normal resource
        $predicate = $this->parseValue();
        if ($predicate['type'] == 'uri') {
            return $predicate;
        } else {
            throw new EasyRdf_Exception(
                "Turtle Parse Error: Illegal predicate value: " . $predicate
            );
        }
    }

    /**
     * Parse a object [12]
     * @ignore
     */
    protected function parseObject()
    {
        $c = $this->peek();

        if ($c == '(') {
            $this->object = $this->parseCollection();
        } elseif ($c == '[') {
            $this->object = $this->parseImplicitBlank();
        } else {
            $this->object = $this->parseValue();
        }

        $this->addTriple(
            $this->subject['value'],
            $this->predicate['value'],
            $this->object
        );
    }

    /**
     * Parses a blankNodePropertyList [15]
     *
     * This method parses the token []
     * and predicateObjectLists that are surrounded by square brackets.
     *
     * @ignore
     */
    protected function parseImplicitBlank()
    {
        $this->verifyCharacter($this->read(), "[");

        $bnode = array(
            'type' => 'bnode',
            'value' => $this->graph->newBNodeId()
        );

        $c = $this->read();
        if ($c != ']') {
            $this->unread($c);

            // Remember current subject and predicate
            $oldSubject = $this->subject;
            $oldPredicate = $this->predicate;

            // generated bNode becomes subject
            $this->subject = $bnode;

            // Enter recursion with nested predicate-object list
            $this->skipWSC();

            $this->parsePredicateObjectList();

            $this->skipWSC();

            // Read closing bracket
            $this->verifyCharacter($this->read(), "]");

            // Restore previous subject and predicate
            $this->subject = $oldSubject;
            $this->predicate = $oldPredicate;
        }

        return $bnode;
    }

    /**
     * Parses a collection [16], e.g: ( item1 item2 item3 )
     * @ignore
     */
    protected function parseCollection()
    {
        $this->verifyCharacter($this->read(), "(");

        $c = $this->skipWSC();
        if ($c == ')') {
            // Empty list
            $this->read();
            return array(
                'type' => 'uri',
                'value' => EasyRdf_Namespace::get('rdf') . 'nil'
            );
        } else {
            $listRoot = array(
                'type' => 'bnode',
                'value' => $this->graph->newBNodeId()
            );

            // Remember current subject and predicate
            $oldSubject = $this->subject;
            $oldPredicate = $this->predicate;

            // generated bNode becomes subject, predicate becomes rdf:first
            $this->subject = $listRoot;
            $this->predicate = array(
                'type' => 'uri',
                'value' => EasyRdf_Namespace::get('rdf') . 'first'
            );

            $this->parseObject();
            $bNode = $listRoot;

            while ($this->skipWSC() != ')') {
                // Create another list node and link it to the previous
                $newNode = array(
                    'type' => 'bnode',
                    'value' => $this->graph->newBNodeId()
                );

                $this->addTriple(
                    $bNode['value'],
                    EasyRdf_Namespace::get('rdf') . 'rest',
                    $newNode
                );

                // New node becomes the current
                $this->subject = $bNode = $newNode;

                $this->parseObject();
            }

            // Skip ')'
            $this->read();

            // Close the list
            $this->addTriple(
                $bNode['value'],
                EasyRdf_Namespace::get('rdf') . 'rest',
                array(
                    'type' => 'uri',
                    'value' => EasyRdf_Namespace::get('rdf') . 'nil'
                )
            );

            // Restore previous subject and predicate
            $this->subject = $oldSubject;
            $this->predicate = $oldPredicate;

            return $listRoot;
        }
    }

    /**
     * Parses an RDF value. This method parses uriref, qname, node ID, quoted
     * literal, integer, double and boolean.
     * @ignore
     */
    protected function parseValue()
    {
        $c = $this->peek();

        if ($c == '<') {
            // uriref, e.g. <foo://bar>
            return $this->parseURI();
        } elseif ($c == ':' || self::isPrefixStartChar($c)) {
            // qname or boolean
            return $this->parseQNameOrBoolean();
        } elseif ($c == '_') {
            // node ID, e.g. _:n1
            return $this->parseNodeID();
        } elseif ($c == '"' or $c == "'") {
            // quoted literal, e.g. "foo" or """foo""" or 'foo' or '''foo'''
            return $this->parseQuotedLiteral($c);
        } elseif (ctype_digit($c) || $c == '.' || $c == '+' || $c == '-') {
            // integer or double, e.g. 123 or 1.2e3
            return $this->parseNumber();
        } elseif ($c == -1) {
            throw new EasyRdf_Exception(
                "Turtle Parse Error: unexpected end of file while reading value"
            );
        } else {
            throw new EasyRdf_Exception(
                "Turtle Parse Error: expected an RDF value here, found '$c'"
            );
        }
    }

    /**
     * Parses a quoted string, optionally followed by a language tag or datatype.
     * @param string  $quote  The type of quote to use (either ' or ")
     * @ignore
     */
    protected function parseQuotedLiteral($quote)
    {
        $label = $this->parseQuotedString($quote);

        // Check for presence of a language tag or datatype
        $c = $this->peek();

        if ($c == '@') {
            $this->read();

            // Read language
            $lang = '';
            $c = $this->read();
            if ($c == -1) {
                throw new EasyRdf_Exception(
                    "Turtle Parse Error: unexpected end of file while reading language"
                );
            } elseif (!self::isLanguageStartChar($c)) {
                throw new EasyRdf_Exception(
                    "Turtle Parse Error: expected a letter, found '$c'"
                );
            }

            $lang .= $c;

            $c = $this->read();
            while (self::isLanguageChar($c)) {
                $lang .= $c;
                $c = $this->read();
            }

            $this->unread($c);

            return array(
                'type' => 'literal',
                'value' => $label,
                'lang' => $lang
            );
        } elseif ($c == '^') {
            $this->read();

            // next character should be another '^'
            $this->verifyCharacter($this->read(), "^");

            // Read datatype
            $datatype = $this->parseValue();
            if ($datatype['type'] == 'uri') {
                return array(
                    'type' => 'literal',
                    'value' => $label,
                    'datatype' => $datatype['value']
                );
            } else {
                throw new EasyRdf_Exception(
                    "Turtle Parse Error: illegal datatype value: $datatype"
                );
            }
        } else {
            return array(
                'type' => 'literal',
                'value' => $label
            );
        }
    }

    /**
     * Parses a quoted string, which is either a "normal string" or a """long string""".
     * @param string  $quote  The type of quote to use (either ' or ")
     * @ignore
     */
    protected function parseQuotedString($quote)
    {
        $result = null;

        // First character should be ' or "
        $this->verifyCharacter($this->read(), $quote);

        // Check for long-string, which starts and ends with three double quotes
        $c2 = $this->read();
        $c3 = $this->read();

        if ($c2 == $quote && $c3 == $quote) {
            // Long string
            $result = $this->parseLongString($quote);
        } else {
            // Normal string
            $this->unread($c3);
            $this->unread($c2);

            $result = $this->parseString($quote);
        }

        // Unescape any escape sequences
        return $this->unescapeString($result);
    }

    /**
     * Parses a "normal string". This method assumes that the first double quote
     * has already been parsed.
     * @param string  $quote  The type of quote to use (either ' or ")
     * @ignore
     */
    protected function parseString($quote)
    {
        $str = '';

        while (true) {
            $c = $this->read();

            if ($c == $quote) {
                break;
            } elseif ($c == -1) {
                throw new EasyRdf_Exception(
                    "Turtle Parse Error: unexpected end of file while reading string"
                );
            }

            $str .= $c;

            if ($c == '\\') {
                // This escapes the next character, which might be a ' or a "
                $c = $this->read();
                if ($c == -1) {
                    throw new EasyRdf_Exception(
                        "Turtle Parse Error: unexpected end of file while reading string"
                    );
                }
                $str .= $c;
            }
        }

        return $str;
    }

    /**
     * Parses a """long string""". This method assumes that the first three
     * double quotes have already been parsed.
     * @param string  $quote  The type of quote to use (either ' or ")
     * @ignore
     */
    protected function parseLongString($quote)
    {
        $str = '';
        $doubleQuoteCount = 0;

        while ($doubleQuoteCount < 3) {
            $c = $this->read();

            if ($c == -1) {
                throw new EasyRdf_Exception(
                    "Turtle Parse Error: unexpected end of file while reading long string"
                );
            } elseif ($c == $quote) {
                $doubleQuoteCount++;
            } else {
                $doubleQuoteCount = 0;
            }

            $str .= $c;

            if ($c == '\\') {
                // This escapes the next character, which might be a ' or "
                $c = $this->read();
                if ($c == -1) {
                    throw new EasyRdf_Exception(
                        "Turtle Parse Error: unexpected end of file while reading long string"
                    );
                }
                $str .= $c;
            }
        }

        return substr($str, 0, -3);
    }

    /**
     * Parses a numeric value, either of type integer, decimal or double
     * @ignore
     */
    protected function parseNumber()
    {
        $value = '';
        $datatype = EasyRdf_Namespace::get('xsd').'integer';

        $c = $this->read();

        // read optional sign character
        if ($c == '+' || $c == '-') {
            $value .= $c;
            $c = $this->read();
        }

        while (ctype_digit($c)) {
            $value .= $c;
            $c = $this->read();
        }

        if ($c == '.' || $c == 'e' || $c == 'E') {
            // We're parsing a decimal or a double
            $datatype = EasyRdf_Namespace::get('xsd').'decimal';

            // read optional fractional digits
            if ($c == '.') {
                $value .= $c;
                $c = $this->read();
                while (ctype_digit($c)) {
                    $value .= $c;
                    $c = $this->read();
                }

                if (strlen($value) == 1) {
                    // We've only parsed a '.'
                    throw new EasyRdf_Exception(
                        "Turtle Parse Error: object for statement missing"
                    );
                }
            } else {
                if (strlen($value) == 0) {
                    // We've only parsed an 'e' or 'E'
                    throw new EasyRdf_Exception(
                        "Turtle Parse Error: object for statement missing"
                    );
                }
            }

            // read optional exponent
            if ($c == 'e' || $c == 'E') {
                $datatype = EasyRdf_Namespace::get('xsd').'double';
                $value .= $c;

                $c = $this->read();
                if ($c == '+' || $c == '-') {
                    $value .= $c;
                    $c = $this->read();
                }

                if (!ctype_digit($c)) {
                    throw new EasyRdf_Exception(
                        "Turtle Parse Error: Exponent value missing"
                    );
                }

                $value .= $c;

                $c = $this->read();
                while (ctype_digit($c)) {
                    $value .= $c;
                    $c = $this->read();
                }
            }
        }

        // Unread last character, it isn't part of the number
        $this->unread($c);

        // Return result as a typed literal
        return array(
            'type' => 'literal',
            'value' => $value,
            'datatype' => $datatype
        );
    }

    /**
     * Parses a URI / IRI
     * @ignore
     */
    protected function parseURI()
    {
        $uri = '';

        // First character should be '<'
        $this->verifyCharacter($this->read(), "<");

        // Read up to the next '>' character
        while (true) {
            $c = $this->read();

            if ($c == '>') {
                break;
            } elseif ($c == -1) {
                throw new EasyRdf_Exception(
                    "Turtle Parse Error: unexpected end of file while reading URI"
                );
            }

            $uri .= $c;

            if ($c == '\\') {
                // This escapes the next character, which might be a '>'
                $c = $this->read();
                if ($c == -1) {
                    throw new EasyRdf_Exception(
                        "Turtle Parse Error: unexpected end of file while reading URI"
                    );
                }
                $uri .= $c;
            }
        }

        // Unescape any escape sequences
        $uri = $this->unescapeString($uri);

        return array(
            'type' => 'uri',
            'value' => $this->resolve($uri)
        );
    }

    /**
     * Parses qnames and boolean values, which have equivalent starting
     * characters.
     * @ignore
     */
    protected function parseQNameOrBoolean()
    {
        // First character should be a ':' or a letter
        $c = $this->read();
        if ($c == -1) {
            throw new EasyRdf_Exception(
                "Turtle Parse Error: unexpected end of file while readying value"
            );
        }
        if ($c != ':' && !self::isPrefixStartChar($c)) {
            throw new EasyRdf_Exception(
                "Turtle Parse Error: expected a ':' or a letter, found '$c'"
            );
        }

        $namespace = null;

        if ($c == ':') {
            // qname using default namespace
            $namespace = $this->namespaces[""];
            if ($namespace == null) {
                throw new EasyRdf_Exception(
                    "Turtle Parse Error: default namespace used but not defined"
                );
            }
        } else {
            // $c is the first letter of the prefix
            $prefix = $c;

            $c = $this->read();
            while (self::isPrefixChar($c)) {
                $prefix .= $c;
                $c = $this->read();
            }

            if ($c != ':') {
                // prefix may actually be a boolean value
                $value = $prefix;

                if ($value == "true" || $value == "false") {
                    return array(
                        'type' => 'literal',
                        'value' => $value,
                        'datatype' => EasyRdf_Namespace::get('xsd') . 'boolean'
                    );
                }
            }

            $this->verifyCharacter($c, ":");

            if (isset($this->namespaces[$prefix])) {
                $namespace = $this->namespaces[$prefix];
            } else {
                throw new EasyRdf_Exception(
                    "Turtle Parse Error: namespace prefix '$prefix' used but not defined"
                );
            }
        }

        // $c == ':', read optional local name
        $localName = '';
        $c = $this->read();
        if (self::isNameStartChar($c)) {
            $localName .= $c;

            $c = $this->read();
            while (self::isNameChar($c)) {
                $localName .= $c;
                $c = $this->read();
            }
        }

        // Unread last character
        $this->unread($c);

        // Note: namespace has already been resolved
        return array(
            'type' => 'uri',
            'value' => $namespace . $localName
        );
    }

    /**
     * Parses a blank node ID, e.g: _:node1
     * @ignore
     */
    protected function parseNodeID()
    {
        // Node ID should start with "_:"
        $this->verifyCharacter($this->read(), "_");
        $this->verifyCharacter($this->read(), ":");

        // Read the node ID
        $c = $this->read();
        if ($c == -1) {
            throw new EasyRdf_Exception(
                "Turtle Parse Error: unexpected end of file while reading node id"
            );
        } elseif (!self::isNameStartChar($c)) {
            throw new EasyRdf_Exception(
                "Turtle Parse Error: expected a letter, found '$c'"
            );
        }

        // Read all following letter and numbers, they are part of the name
        $name = $c;
        $c = $this->read();
        while (self::isNameChar($c)) {
            $name .= $c;
            $c = $this->read();
        }

        $this->unread($c);

        return array(
            'type' => 'bnode',
            'value' => $this->remapBnode($name)
        );
    }

    protected function resolve($uri)
    {
        if ($this->baseUri) {
            return $this->baseUri->resolve($uri)->toString();
        } else {
            return $uri;
        }
    }

    /**
     * Verifies that the supplied character $c is one of the expected
     * characters specified in $expected. This method will throw a
     * exception if this is not the case.
     * @ignore
     */
    protected function verifyCharacter($c, $expected)
    {
        if ($c == -1) {
            throw new EasyRdf_Exception(
                "Turtle Parse Error: unexpected end of file"
            );
        } elseif (strpbrk($c, $expected) === false) {
            $msg = 'expected ';
            for ($i = 0; $i < strlen($expected); $i++) {
                if ($i > 0) {
                    $msg .= " or ";
                }
                $msg .= '\''.$expected[$i].'\'';
            }
            $msg .= ", found '$c'";

            throw new EasyRdf_Exception("Turtle Parse Error: $msg");
        }
    }

    /**
     * Skip through whitespace and comments
     * @ignore
     */
    protected function skipWSC()
    {
        $c = $this->read();
        while (self::isWhitespace($c) || $c == '#') {
            if ($c == '#') {
                $this->skipLine();
            }

            $c = $this->read();
        }

        $this->unread($c);
        return $c;
    }

    /**
     * Consumes characters from reader until the first EOL has been read.
     * @ignore
     */
    protected function skipLine()
    {
        $c = $this->read();
        while ($c != -1 && $c != "\r" && $c != "\n") {
            $c = $this->read();
        }

        // c is equal to -1, \r or \n.
        // In case c is equal to \r, we should also read a following \n.
        if ($c == "\r") {
            $c = $this->read();
            if ($c != "\n") {
                $this->unread($c);
            }
        }
    }

    /**
     * Read a single character from the input buffer.
     * Returns -1 when the end of the file is reached.
     * @ignore
     */
    protected function read()
    {
        if ($this->pos < $this->len) {
            $c = $this->data[$this->pos];
            $this->pos++;
            return $c;
        } else {
            return -1;
        }
    }

    /**
     * Gets the next character to be returned by read()
     * without removing it from the input buffer.
     * @ignore
     */
    protected function peek()
    {
        if ($this->pos < $this->len) {
            return $this->data[$this->pos];
        } else {
            return -1;
        }
    }


    /**
     * Steps back, restoring the previous character read() to the input buffer
     * @ignore
     */
    protected function unread()
    {
        if ($this->pos > 0) {
            $this->pos--;
        } else {
            throw new EasyRdf_Exception("Turtle Parse Error: unread error");
        }
    }

    /**
     * Returns true if $c is a whitespace character
     * @ignore
     */
    public static function isWhitespace($c)
    {
        // Whitespace character are space, tab, newline and carriage return:
        return $c == " " || $c == "\t" || $c == "\r" || $c == "\n";
    }

    /** @ignore */
    public static function isPrefixStartChar($c)
    {
        $o = ord($c);
        return
            $o >= 0x41   && $o <= 0x5a ||     # A-Z
            $o >= 0x61   && $o <= 0x7a ||     # a-z
            $o >= 0x00C0 && $o <= 0x00D6 ||
            $o >= 0x00D8 && $o <= 0x00F6 ||
            $o >= 0x00F8 && $o <= 0x02FF ||
            $o >= 0x0370 && $o <= 0x037D ||
            $o >= 0x037F && $o <= 0x1FFF ||
            $o >= 0x200C && $o <= 0x200D ||
            $o >= 0x2070 && $o <= 0x218F ||
            $o >= 0x2C00 && $o <= 0x2FEF ||
            $o >= 0x3001 && $o <= 0xD7FF ||
            $o >= 0xF900 && $o <= 0xFDCF ||
            $o >= 0xFDF0 && $o <= 0xFFFD ||
            $o >= 0x10000 && $o <= 0xEFFFF;
    }

    /** @ignore */
    public static function isNameStartChar($c)
    {
        return $c == '_' || self::isPrefixStartChar($c);
    }

    /** @ignore */
    public static function isNameChar($c)
    {
        $o = ord($c);
        return
            self::isNameStartChar($c) ||
            $c == '-' ||
            $o >= 0x30 && $o <= 0x39 ||   # numeric
            $o == 0x00B7 ||
            $o >= 0x0300 && $o <= 0x036F ||
            $o >= 0x203F && $o <= 0x2040;
    }

    /** @ignore */
    public static function isPrefixChar($c)
    {
        return self::isNameChar($c);
    }

    /** @ignore */
    public static function isLanguageStartChar($c)
    {
        $o = ord($c);
        return
            $o >= 0x41   && $o <= 0x5a ||
            $o >= 0x61   && $o <= 0x7a;
    }

    /** @ignore */
    public static function isLanguageChar($c)
    {
        $o = ord($c);
        return
            $o >= 0x41   && $o <= 0x5a ||   # A-Z
            $o >= 0x61   && $o <= 0x7a ||   # a-z
            $o >= 0x30   && $o <= 0x39 ||   # 0-9
            $c == '-';
    }
}
