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
 * Class to serialise an EasyRdf_Graph to N-Triples
 * with no external dependancies.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Serialiser_Ntriples extends EasyRdf_Serialiser
{
    private $escChars = array();   // Character encoding cache

    /**
     * @ignore
     */
    protected function escapeString($str)
    {
        if (strpos(utf8_decode(str_replace('?', '', $str)), '?') === false) {
            $str = utf8_decode($str);
        }

        $result = '';
        $strLen = strlen($str);
        for ($i = 0; $i < $strLen; $i++) {
            $c = $str[$i];
            if (!isset($this->escChars[$c])) {
                $this->escChars[$c] = $this->escapedChar($c);
            }
            $result .= $this->escChars[$c];
        }
        return $result;
    }

    /**
     * @ignore
     */
    protected function unicodeCharNo($c)
    {
        $cUtf = utf8_encode($c);
        $bl = strlen($cUtf); /* binary length */
        $r = 0;
        switch ($bl) {
            case 1: /* 0####### (0-127) */
                $r = ord($cUtf);
                break;
            case 2: /* 110##### 10###### = 192+x 128+x */
                $r = ((ord($cUtf[0]) - 192) * 64) +
                     (ord($cUtf[1]) - 128);
                break;
            case 3: /* 1110#### 10###### 10###### = 224+x 128+x 128+x */
                $r = ((ord($cUtf[0]) - 224) * 4096) +
                     ((ord($cUtf[1]) - 128) * 64) +
                     (ord($cUtf[2]) - 128);
                break;
            case 4: /* 1111#### 10###### 10###### 10###### = 240+x 128+x 128+x 128+x */
                $r = ((ord($cUtf[0]) - 240) * 262144) +
                     ((ord($cUtf[1]) - 128) * 4096) +
                     ((ord($cUtf[2]) - 128) * 64) +
                     (ord($cUtf[3]) - 128);
                break;
        }
        return $r;
    }

    /**
     * @ignore
     */
    protected function escapedChar($c)
    {
        $no = $this->unicodeCharNo($c);

        /* see http://www.w3.org/TR/rdf-testcases/#ntrip_strings */
        if ($no < 9) {
            return "\\u" . sprintf('%04X', $no);  /* #x0-#x8 (0-8) */
        } elseif ($no == 9) {
            return '\t';                          /* #x9 (9) */
        } elseif ($no == 10) {
            return '\n';                          /* #xA (10) */
        } elseif ($no < 13) {
            return "\\u" . sprintf('%04X', $no);  /* #xB-#xC (11-12) */
        } elseif ($no == 13) {
            return '\r';                          /* #xD (13) */
        } elseif ($no < 32) {
            return "\\u" . sprintf('%04X', $no);  /* #xE-#x1F (14-31) */
        } elseif ($no < 34) {
            return $c;                            /* #x20-#x21 (32-33) */
        } elseif ($no == 34) {
            return '\"';                          /* #x22 (34) */
        } elseif ($no < 92) {
            return $c;                            /* #x23-#x5B (35-91) */
        } elseif ($no == 92) {
            return '\\';                          /* #x5C (92) */
        } elseif ($no < 127) {
            return $c;                            /* #x5D-#x7E (93-126) */
        } elseif ($no < 65536) {
            return "\\u" . sprintf('%04X', $no);  /* #x7F-#xFFFF (128-65535) */
        } elseif ($no < 1114112) {
            return "\\U" . sprintf('%08X', $no);  /* #x10000-#x10FFFF (65536-1114111) */
        } else {
            return '';                            /* not defined => ignore */
        }
    }

    /**
     * @ignore
     */
    protected function serialiseResource($res)
    {
        $escaped = $this->escapeString($res);
        if (substr($res, 0, 2) == '_:') {
            return $escaped;
        } else {
            return "<$escaped>";
        }
    }

    /**
     * Serialise an RDF value into N-Triples
     *
     * The value can either be an array in RDF/PHP form, or
     * an EasyRdf_Literal or EasyRdf_Resource object.
     *
     * @param array|object  $value   An associative array or an object
     * @throws EasyRdf_Exception
     * @return string The RDF value serialised to N-Triples
     */
    public function serialiseValue($value)
    {
        if (is_object($value)) {
            $value = $value->toRdfPhp();
        }

        if ($value['type'] == 'uri' or $value['type'] == 'bnode') {
            return $this->serialiseResource($value['value']);
        } elseif ($value['type'] == 'literal') {
            $escaped = $this->escapeString($value['value']);
            if (isset($value['lang'])) {
                $lang = $this->escapeString($value['lang']);
                return '"' . $escaped . '"' . '@' . $lang;
            } elseif (isset($value['datatype'])) {
                $datatype = $this->escapeString($value['datatype']);
                return '"' . $escaped . '"' . "^^<$datatype>";
            } else {
                return '"' . $escaped . '"';
            }
        } else {
            throw new EasyRdf_Exception(
                "Unable to serialise object of type '".$value['type']."' to ntriples: "
            );
        }
    }

    /**
     * Serialise an EasyRdf_Graph into N-Triples
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

        if ($format == 'ntriples') {
            $nt = '';
            foreach ($graph->toRdfPhp() as $resource => $properties) {
                foreach ($properties as $property => $values) {
                    foreach ($values as $value) {
                        $nt .= $this->serialiseResource($resource)." ";
                        $nt .= "<" . $this->escapeString($property) . "> ";
                        $nt .= $this->serialiseValue($value)." .\n";
                    }
                }
            }
            return $nt;
        } else {
            throw new EasyRdf_Exception(
                "EasyRdf_Serialiser_Ntriples does not support: $format"
            );
        }
    }
}
