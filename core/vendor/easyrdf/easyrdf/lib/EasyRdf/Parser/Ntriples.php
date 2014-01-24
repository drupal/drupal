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
 * A pure-php class to parse N-Triples with no dependancies.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Parser_Ntriples extends EasyRdf_Parser
{
    /**
     * Decodes an encoded N-Triples string. Any \-escape sequences are substituted
     * with their decoded value.
     *
     * @param  string $str An encoded N-Triples string.
     * @return The unencoded string.
     **/
    protected function unescapeString($str)
    {
        if (strpos($str, '\\') === false) {
            return $str;
        }

        $mappings = array(
            't' => chr(0x09),
            'b' => chr(0x08),
            'n' => chr(0x0A),
            'r' => chr(0x0D),
            'f' => chr(0x0C),
            '\"' => chr(0x22),
            '\'' => chr(0x27)
        );
        foreach ($mappings as $in => $out) {
            $str = preg_replace('/\x5c([' . $in . '])/', $out, $str);
        }

        if (stripos($str, '\u') === false) {
            return $str;
        }

        while (preg_match('/\\\(U)([0-9A-F]{8})/', $str, $matches) ||
               preg_match('/\\\(u)([0-9A-F]{4})/', $str, $matches)) {
            $no = hexdec($matches[2]);
            if ($no < 128) {                // 0x80
                $char = chr($no);
            } elseif ($no < 2048) {         // 0x800
                $char = chr(($no >> 6) + 192) .
                        chr(($no & 63) + 128);
            } elseif ($no < 65536) {        // 0x10000
                $char = chr(($no >> 12) + 224) .
                        chr((($no >> 6) & 63) + 128) .
                        chr(($no & 63) + 128);
            } elseif ($no < 2097152) {      // 0x200000
                $char = chr(($no >> 18) + 240) .
                        chr((($no >> 12) & 63) + 128) .
                        chr((($no >> 6) & 63) + 128) .
                        chr(($no & 63) + 128);
            } else {
                # FIXME: throw an exception instead?
                $char = '';
            }
            $str = str_replace('\\' . $matches[1] . $matches[2], $char, $str);
        }
        return $str;
    }

    /**
     * @ignore
     */
    protected function parseNtriplesSubject($sub, $lineNum)
    {
        if (preg_match('/<([^<>]+)>/', $sub, $matches)) {
            return $this->unescapeString($matches[1]);
        } elseif (preg_match('/_:([A-Za-z0-9]*)/', $sub, $matches)) {
            if (empty($matches[1])) {
                return $this->graph->newBNodeId();
            } else {
                $nodeid = $this->unescapeString($matches[1]);
                return $this->remapBnode($nodeid);
            }
        } else {
            throw new EasyRdf_Parser_Exception(
                "Failed to parse subject: $sub",
                $lineNum
            );
        }
    }

    /**
     * @ignore
     */
    protected function parseNtriplesObject($obj, $lineNum)
    {
        if (preg_match('/"(.+)"\^\^<([^<>]+)>/', $obj, $matches)) {
            return array(
                'type' => 'literal',
                'value' => $this->unescapeString($matches[1]),
                'datatype' => $this->unescapeString($matches[2])
            );
        } elseif (preg_match('/"(.+)"@([\w\-]+)/', $obj, $matches)) {
            return array(
                'type' => 'literal',
                'value' => $this->unescapeString($matches[1]),
                'lang' => $this->unescapeString($matches[2])
            );
        } elseif (preg_match('/"(.*)"/', $obj, $matches)) {
            return array('type' => 'literal', 'value' => $this->unescapeString($matches[1]));
        } elseif (preg_match('/<([^<>]+)>/', $obj, $matches)) {
            return array('type' => 'uri', 'value' => $matches[1]);
        } elseif (preg_match('/_:([A-Za-z0-9]*)/', $obj, $matches)) {
            if (empty($matches[1])) {
                return array(
                    'type' => 'bnode',
                    'value' => $this->graph->newBNodeId()
                );
            } else {
                $nodeid = $this->unescapeString($matches[1]);
                return array(
                    'type' => 'bnode',
                    'value' => $this->remapBnode($nodeid)
                );
            }
        } else {
            throw new EasyRdf_Parser_Exception(
                "Failed to parse object: $obj",
                $lineNum
            );
        }
    }

    /**
      * Parse an N-Triples document into an EasyRdf_Graph
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

        if ($format != 'ntriples') {
            throw new EasyRdf_Exception(
                "EasyRdf_Parser_Ntriples does not support: $format"
            );
        }

        $lines = preg_split("/\x0D?\x0A/", strval($data));
        foreach ($lines as $index => $line) {
            $lineNum = $index + 1;
            if (preg_match("/^\s*#/", $line)) {
                # Comment
                continue;
            } elseif (preg_match("/^\s*(.+?)\s+<([^<>]+?)>\s+(.+?)\s*\.\s*$/", $line, $matches)) {
                $this->addTriple(
                    $this->parseNtriplesSubject($matches[1], $lineNum),
                    $this->unescapeString($matches[2]),
                    $this->parseNtriplesObject($matches[3], $lineNum)
                );
            } elseif (preg_match("/^\s*$/", $line)) {
                # Blank line
                continue;
            } else {
                throw new EasyRdf_Parser_Exception(
                    "Failed to parse statement",
                    $lineNum
                );
            }
        }

        return $this->tripleCount;
    }
}
