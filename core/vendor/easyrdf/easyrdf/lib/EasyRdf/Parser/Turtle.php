<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2009-2013 Nicholas J Humfrey.
 * Copyright (c) 1997-2013 Aduna (http://www.aduna-software.com/)
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
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 *             Copyright (c) 1997-2006 Aduna (http://www.aduna-software.com/)
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * Class to parse Turtle with no external dependancies.
 *
 * It is a translation from Java to PHP of the Sesame Turtle Parser:
 * http://bit.ly/TurtleParser
 * 
 * Lasted updated against version: 
 * ecda6a15a200a2fc6a062e2e43081257c3ccd4e6   (Mon Jul 29 12:05:58 2013)
 * 
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 *             Copyright (c) 1997-2013 Aduna (http://www.aduna-software.com/)
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Parser_Turtle extends EasyRdf_Parser_Ntriples
{
    protected $data;
    protected $namespaces;
    protected $subject;
    protected $predicate;
    protected $object;
    
    protected $line;
    protected $column;

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
        $this->namespaces = array();
        $this->subject = null;
        $this->predicate = null;
        $this->object = null;
        
        $this->line = 1;
        $this->column = 1;

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
        $directive = '';
        while (true) {
            $c = $this->read();
            if ($c == -1 || self::isWhitespace($c)) {
                $this->unread($c);
                break;
            } else {
                $directive .= $c;
            }
        }

        if (preg_match("/^(@|prefix$|base$)/i", $directive)) {
            $this->parseDirective($directive);
            $this->skipWSC();
            // SPARQL BASE and PREFIX lines do not end in .
            if ($directive[0] == "@") {
                $this->verifyCharacterOrFail($this->read(), ".");
            }
        } else {
            $this->unread($directive);
            $this->parseTriples();
            $this->skipWSC();
            $this->verifyCharacterOrFail($this->read(), ".");
        }
    }

    /**
     * Parse a directive [3]
     * @ignore
     */
    protected function parseDirective($directive)
    {
        $directive = strtolower($directive);
        if ($directive == "prefix" || $directive == '@prefix') {
            $this->parsePrefixID();
        } elseif ($directive == "base" || $directive == '@base') {
            $this->parseBase();
        } elseif (mb_strlen($directive) == 0) {
            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: directive name is missing, expected @prefix or @base",
                $this->line,
                $this->column
            );
        } else {
            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: unknown directive \"$directive\"",
                $this->line,
                $this->column
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
                throw new EasyRdf_Parser_Exception(
                    "Turtle Parse Error: unexpected end of file while reading prefix id",
                    $this->line,
                    $this->column
                );
            }

            $prefixID .= $c;
        }

        $this->skipWSC();
        $this->verifyCharacterOrFail($this->read(), ":");
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
        $c = $this->peek();

        // If the first character is an open bracket we need to decide which of
        // the two parsing methods for blank nodes to use
        if ($c == '[') {
            $c = $this->read();
            $this->skipWSC();
            $c = $this->peek();
            if ($c == ']') {
                $c = $this->read();
                $this->subject = $this->createBNode();
                $this->skipWSC();
                $this->parsePredicateObjectList();
            } else {
                $this->unread('[');
                $this->subject = $this->parseImplicitBlank();
            }
            $this->skipWSC();
            $c = $this->peek();

            // if this is not the end of the statement, recurse into the list of
            // predicate and objects, using the subject parsed above as the subject
            // of the statement.
            if ($c != '.') {
                $this->parsePredicateObjectList();
            }
        } else {
            $this->parseSubject();
            $this->skipWSC();
            $this->parsePredicateObjectList();
        }

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
            } elseif ($c == ';') {
                // empty predicateObjectList, skip to next
                continue;
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
                throw new EasyRdf_Parser_Exception(
                    "Turtle Parse Error: illegal subject type: ".$value['type'],
                    $this->line,
                    $this->column
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
            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: Illegal predicate type: " . $predicate['type'],
                $this->line,
                $this->column
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
        $this->verifyCharacterOrFail($this->read(), "[");

        $bnode = $this->createBNode();

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
            $this->verifyCharacterOrFail($this->read(), "]");

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
        $this->verifyCharacterOrFail($this->read(), "(");

        $c = $this->skipWSC();
        if ($c == ')') {
            // Empty list
            $this->read();
            return array(
                'type' => 'uri',
                'value' => EasyRdf_Namespace::get('rdf') . 'nil'
            );
        } else {
            $listRoot = $this->createBNode();

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
                $newNode = $this->createBNode();

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
        } elseif ($c == '"' || $c == "'") {
            // quoted literal, e.g. "foo" or """foo""" or 'foo' or '''foo'''
            return $this->parseQuotedLiteral();
        } elseif (ctype_digit($c) || $c == '.' || $c == '+' || $c == '-') {
            // integer or double, e.g. 123 or 1.2e3
            return $this->parseNumber();
        } elseif ($c == -1) {
            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: unexpected end of file while reading value",
                $this->line,
                $this->column
            );
        } else {
            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: expected an RDF value here, found '$c'",
                $this->line,
                $this->column
            );
        }
    }

    /**
     * Parses a quoted string, optionally followed by a language tag or datatype.
     * @ignore
     */
    protected function parseQuotedLiteral()
    {
        $label = $this->parseQuotedString();

        // Check for presence of a language tag or datatype
        $c = $this->peek();

        if ($c == '@') {
            $this->read();

            // Read language
            $lang = '';
            $c = $this->read();
            if ($c == -1) {
                throw new EasyRdf_Parser_Exception(
                    "Turtle Parse Error: unexpected end of file while reading language",
                    $this->line,
                    $this->column
                );
            } elseif (!self::isLanguageStartChar($c)) {
                throw new EasyRdf_Parser_Exception(
                    "Turtle Parse Error: expected a letter, found '$c'",
                    $this->line,
                    $this->column
                );
            }

            $lang .= $c;

            $c = $this->read();
            while (!self::isWhitespace($c)) {
                if ($c == '.' || $c == ';' || $c == ',' || $c == ')' || $c == ']' || $c == -1) {
                    break;
                }
                if (self::isLanguageChar($c)) {
                    $lang .= $c;
                } else {
                    throw new EasyRdf_Parser_Exception(
                        "Turtle Parse Error: illegal language tag char: '$c'",
                        $this->line,
                        $this->column
                    );
                }
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
            $this->verifyCharacterOrFail($this->read(), "^");

            // Read datatype
            $datatype = $this->parseValue();
            if ($datatype['type'] == 'uri') {
                return array(
                    'type' => 'literal',
                    'value' => $label,
                    'datatype' => $datatype['value']
                );
            } else {
                throw new EasyRdf_Parser_Exception(
                    "Turtle Parse Error: illegal datatype type: " . $datatype['type'],
                    $this->line,
                    $this->column
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
     * @ignore
     */
    protected function parseQuotedString()
    {
        $result = null;

        $c1 = $this->read();

        // First character should be ' or "
        $this->verifyCharacterOrFail($c1, "\"\'");

        // Check for long-string, which starts and ends with three double quotes
        $c2 = $this->read();
        $c3 = $this->read();

        if ($c2 == $c1 && $c3 == $c1) {
            // Long string
            $result = $this->parseLongString($c2);
        } else {
            // Normal string
            $this->unread($c3);
            $this->unread($c2);

            $result = $this->parseString($c1);
        }

        // Unescape any escape sequences
        return $this->unescapeString($result);
    }

    /**
     * Parses a "normal string". This method requires that the opening character
     * has already been parsed.
     * @param string  $closingCharacter  The type of quote to use (either ' or ")
     * @ignore
     */
    protected function parseString($closingCharacter)
    {
        $str = '';

        while (true) {
            $c = $this->read();

            if ($c == $closingCharacter) {
                break;
            } elseif ($c == -1) {
                throw new EasyRdf_Parser_Exception(
                    "Turtle Parse Error: unexpected end of file while reading string",
                    $this->line,
                    $this->column
                );
            }

            $str .= $c;

            if ($c == '\\') {
                // This escapes the next character, which might be a ' or a "
                $c = $this->read();
                if ($c == -1) {
                    throw new EasyRdf_Parser_Exception(
                        "Turtle Parse Error: unexpected end of file while reading string",
                        $this->line,
                        $this->column
                    );
                }
                $str .= $c;
            }
        }

        return $str;
    }

    /**
     * Parses a """long string""". This method requires that the first three
     * characters have already been parsed.
     * @param string  $closingCharacter  The type of quote to use (either ' or ")
     * @ignore
     */
    protected function parseLongString($closingCharacter)
    {
        $str = '';
        $doubleQuoteCount = 0;

        while ($doubleQuoteCount < 3) {
            $c = $this->read();

            if ($c == -1) {
                throw new EasyRdf_Parser_Exception(
                    "Turtle Parse Error: unexpected end of file while reading long string",
                    $this->line,
                    $this->column
                );
            } elseif ($c == $closingCharacter) {
                $doubleQuoteCount++;
            } else {
                $doubleQuoteCount = 0;
            }

            $str .= $c;

            if ($c == '\\') {
                // This escapes the next character, which might be a ' or "
                $c = $this->read();
                if ($c == -1) {
                    throw new EasyRdf_Parser_Exception(
                        "Turtle Parse Error: unexpected end of file while reading long string",
                        $this->line,
                        $this->column
                    );
                }
                $str .= $c;
            }
        }

        return mb_substr($str, 0, -3);
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
            // read optional fractional digits
            if ($c == '.') {

                if (self::isWhitespace($this->peek())) {
                    // We're parsing an integer that did not have a space before the
                    // period to end the statement
                } else {
                    $value .= $c;
                    $c = $this->read();
                    while (ctype_digit($c)) {
                        $value .= $c;
                        $c = $this->read();
                    }

                    if (mb_strlen($value) == 1) {
                        // We've only parsed a '.'
                        throw new EasyRdf_Parser_Exception(
                            "Turtle Parse Error: object for statement missing",
                            $this->line,
                            $this->column
                        );
                    }

                    // We're parsing a decimal or a double
                    $datatype = EasyRdf_Namespace::get('xsd').'decimal';
                }
            } else {
                if (mb_strlen($value) == 0) {
                    // We've only parsed an 'e' or 'E'
                    throw new EasyRdf_Parser_Exception(
                        "Turtle Parse Error: object for statement missing",
                        $this->line,
                        $this->column
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
                    throw new EasyRdf_Parser_Exception(
                        "Turtle Parse Error: exponent value missing",
                        $this->line,
                        $this->column
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
        $this->verifyCharacterOrFail($this->read(), "<");

        // Read up to the next '>' character
        while (true) {
            $c = $this->read();

            if ($c == '>') {
                break;
            } elseif ($c == -1) {
                throw new EasyRdf_Parser_Exception(
                    "Turtle Parse Error: unexpected end of file while reading URI",
                    $this->line,
                    $this->column
                );
            }

            $uri .= $c;

            if ($c == '\\') {
                // This escapes the next character, which might be a '>'
                $c = $this->read();
                if ($c == -1) {
                    throw new EasyRdf_Parser_Exception(
                        "Turtle Parse Error: unexpected end of file while reading URI",
                        $this->line,
                        $this->column
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
            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: unexpected end of file while readying value",
                $this->line,
                $this->column
            );
        }
        if ($c != ':' && !self::isPrefixStartChar($c)) {
            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: expected a ':' or a letter, found '$c'",
                $this->line,
                $this->column
            );
        }

        $namespace = null;

        if ($c == ':') {
            // qname using default namespace
            if (isset($this->namespaces[''])) {
                $namespace = $this->namespaces[''];
            } else {
                throw new EasyRdf_Parser_Exception(
                    "Turtle Parse Error: default namespace used but not defined",
                    $this->line,
                    $this->column
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

            $this->verifyCharacterOrFail($c, ":");

            if (isset($this->namespaces[$prefix])) {
                $namespace = $this->namespaces[$prefix];
            } else {
                throw new EasyRdf_Parser_Exception(
                    "Turtle Parse Error: namespace prefix '$prefix' used but not defined",
                    $this->line,
                    $this->column
                );
            }
        }

        // $c == ':', read optional local name
        $localName = '';
        $c = $this->read();
        if (self::isNameStartChar($c)) {
            if ($c == '\\') {
                $localName .= $this->readLocalEscapedChar();
            } else {
                $localName .= $c;
            }

            $c = $this->read();
            while (self::isNameChar($c)) {
                if ($c == '\\') {
                    $localName .= $this->readLocalEscapedChar();
                } else {
                    $localName .= $c;
                }
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

    protected function readLocalEscapedChar()
    {
        $c = $this->read();

        if (self::isLocalEscapedChar($c)) {
            return $c;
        } else {
            throw new EasyRdf_Parser_Exception(
                "found '" . $c . "', expected one of: " . implode(', ', self::$localEscapedChars),
                $this->line,
                $this->column
            );
        }
    }

    /**
     * Parses a blank node ID, e.g: _:node1
     * @ignore
     */
    protected function parseNodeID()
    {
        // Node ID should start with "_:"
        $this->verifyCharacterOrFail($this->read(), "_");
        $this->verifyCharacterOrFail($this->read(), ":");

        // Read the node ID
        $c = $this->read();
        if ($c == -1) {
            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: unexpected end of file while reading node id",
                $this->line,
                $this->column
            );
        } elseif (!self::isNameStartChar($c)) {
            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: expected a letter, found '$c'",
                $this->line,
                $this->column
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
    protected function verifyCharacterOrFail($c, $expected)
    {
        if ($c == -1) {
            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: unexpected end of file",
                $this->line,
                $this->column
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

            throw new EasyRdf_Parser_Exception(
                "Turtle Parse Error: $msg",
                $this->line,
                $this->column
            );
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
                $this->processComment();
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
    protected function processComment()
    {
        $comment = '';
        $c = $this->read();
        while ($c != -1 && $c != "\r" && $c != "\n") {
            $comment .= $c;
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
        if (!empty($this->data)) {
            $c = mb_substr($this->data, 0, 1);
            // Keep tracks of which line we are on (0A = Line Feed)
            if ($c == "\x0A") {
                $this->line += 1;
                $this->column = 1;
            } else {
                $this->column += 1;
            }
            $this->data = mb_substr($this->data, 1);
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
        if (!empty($this->data)) {
            return mb_substr($this->data, 0, 1);
        } else {
            return -1;
        }
    }


    /**
     * Steps back, restoring the previous character read() to the input buffer
     * @ignore
     */
    protected function unread($c)
    {
        # FIXME: deal with unreading new lines
        $this->column -= mb_strlen($c);
        $this->data = $c . $this->data;
    }

    /** @ignore */
    protected function createBNode()
    {
        return array(
            'type' => 'bnode',
            'value' => $this->graph->newBNodeId()
        );
    }

    /**
     * Returns true if $c is a whitespace character
     * @ignore
     */
    public static function isWhitespace($c)
    {
        // Whitespace character are space, tab, newline and carriage return:
        return $c == "\x20" || $c == "\x09" || $c == "\x0A" || $c == "\x0D";
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
        return
            $c == '\\' ||
            $c == '_' ||
            $c == ':' ||
            $c == '%' ||
            ctype_digit($c) ||
            self::isPrefixStartChar($c);
    }

    /** @ignore */
    public static function isNameChar($c)
    {
        $o = ord($c);
        return
            self::isNameStartChar($c) ||
            $o >= 0x30 && $o <= 0x39 ||     # 0-9
            $c == '-' ||
            $o == 0x00B7 ||
            $o >= 0x0300 && $o <= 0x036F ||
            $o >= 0x203F && $o <= 0x2040;
    }

    /** @ignore */
    private static $localEscapedChars = array(
        '_', '~', '.', '-', '!', '$', '&', '\'', '(', ')',
        '*', '+', ',', ';', '=', '/', '?', '#', '@', '%'
    );

    /** @ignore */
    public static function isLocalEscapedChar($c)
    {
        return in_array($c, self::$localEscapedChars);
    }

    /** @ignore */
    public static function isPrefixChar($c)
    {
        $o = ord($c);
        return
            $c == '_' ||
            $o >= 0x30 && $o <= 0x39 ||     # 0-9
            self::isPrefixStartChar($c) ||
            $c == '-' ||
            $o == 0x00B7 ||
            $c >= 0x0300 && $c <= 0x036F ||
            $c >= 0x203F && $c <= 0x2040;
    }

    /** @ignore */
    public static function isLanguageStartChar($c)
    {
        $o = ord($c);
        return
            $o >= 0x41 && $o <= 0x5a ||   # A-Z
            $o >= 0x61 && $o <= 0x7a;     # a-z
    }

    /** @ignore */
    public static function isLanguageChar($c)
    {
        $o = ord($c);
        return
            $o >= 0x41 && $o <= 0x5a ||   # A-Z
            $o >= 0x61 && $o <= 0x7a ||   # a-z
            $o >= 0x30 && $o <= 0x39 ||   # 0-9
            $c == '-';
    }
}
