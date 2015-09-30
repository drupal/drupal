<?php

/**
 * EasyRdf
 *
 * Use this file to load the core of EasyRdf, if you don't have an autoloader.
 *
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
 * @copyright  Copyright (c) 2011-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */

/**
 * @see EasyRdf_Exception
 */
require_once "EasyRdf/Exception.php";

/**
 * @see EasyRdf_Format
 */
require_once "EasyRdf/Format.php";

/**
 * @see EasyRdf_Graph
 */
require_once "EasyRdf/Graph.php";

/**
 * @see EasyRdf_GraphStore
 */
require_once "EasyRdf/GraphStore.php";

/**
 * @see EasyRdf_Http
 */
require_once "EasyRdf/Http.php";

/**
 * @see EasyRdf_Http_Client
 */
require_once "EasyRdf/Http/Client.php";

/**
 * @see EasyRdf_Http_Response
 */
require_once "EasyRdf/Http/Response.php";

/**
 * @see EasyRdf_Isomorphic
 */
require_once "EasyRdf/Isomorphic.php";

/**
 * @see EasyRdf_Namespace
 */
require_once "EasyRdf/Namespace.php";

/**
 * @see EasyRdf_Literal
 */
require_once "EasyRdf/Literal.php";

/**
 * @see EasyRdf_Literal_Boolean
 */
require_once "EasyRdf/Literal/Boolean.php";

/**
 * @see EasyRdf_Literal_Date
 */
require_once "EasyRdf/Literal/Date.php";

/**
 * @see EasyRdf_Literal_DateTime
 */
require_once "EasyRdf/Literal/DateTime.php";

/**
 * @see EasyRdf_Literal_Decimal
 */
require_once "EasyRdf/Literal/Decimal.php";

/**
 * @see EasyRdf_Literal_HexBinary
 */
require_once "EasyRdf/Literal/HexBinary.php";

/**
 * @see EasyRdf_Literal_HTML
 */
require_once "EasyRdf/Literal/HTML.php";

/**
 * @see EasyRdf_Literal_Integer
 */
require_once "EasyRdf/Literal/Integer.php";

/**
 * @see EasyRdf_Literal_XML
 */
require_once "EasyRdf/Literal/XML.php";

/**
 * @see EasyRdf_ParsedUri
 */
require_once "EasyRdf/ParsedUri.php";

/**
 * @see EasyRdf_Parser
 */
require_once "EasyRdf/Parser.php";

/**
 * @see EasyRdf_Parser_Exception
 */
require_once "EasyRdf/Parser/Exception.php";

/**
 * @see EasyRdf_Parser_RdfPhp
 */
require_once "EasyRdf/Parser/RdfPhp.php";

/**
 * @see EasyRdf_Parser_Ntriples
 */
require_once "EasyRdf/Parser/Ntriples.php";

/**
 * @see EasyRdf_Parser_Json
 */
require_once "EasyRdf/Parser/Json.php";

/**
 * @see EasyRdf_Parser_Rdfa
 */
require_once "EasyRdf/Parser/Rdfa.php";

/**
 * @see EasyRdf_Parser_RdfXml
 */
require_once "EasyRdf/Parser/RdfXml.php";

/**
 * @see EasyRdf_Parser_Turtle
 */
require_once "EasyRdf/Parser/Turtle.php";

/**
 * @see EasyRdf_Resource
 */
require_once "EasyRdf/Resource.php";

/**
 * @see EasyRdf_Collection
 */
require_once "EasyRdf/Collection.php";

/**
 * @see EasyRdf_Container
 */
require_once "EasyRdf/Container.php";

/**
 * @see EasyRdf_Serialiser
 */
require_once "EasyRdf/Serialiser.php";

/**
 * @see EasyRdf_Serialiser_GraphViz
 */
require_once "EasyRdf/Serialiser/GraphViz.php";

/**
 * @see EasyRdf_Serialiser_RdfPhp
 */
require_once "EasyRdf/Serialiser/RdfPhp.php";

/**
 * @see EasyRdf_Serialiser_Ntriples
 */
require_once "EasyRdf/Serialiser/Ntriples.php";

/**
 * @see EasyRdf_Serialiser_Json
 */
require_once "EasyRdf/Serialiser/Json.php";

/**
 * @see EasyRdf_Serialiser_RdfXml
 */
require_once "EasyRdf/Serialiser/RdfXml.php";

/**
 * @see EasyRdf_Serialiser_Turtle
 */
require_once "EasyRdf/Serialiser/Turtle.php";

/**
 * @see EasyRdf_Sparql_Client
 */
require_once "EasyRdf/Sparql/Client.php";

/**
 * @see EasyRdf_Sparql_Result
 */
require_once "EasyRdf/Sparql/Result.php";

/**
 * @see EasyRdf_TypeMapper
 */
require_once "EasyRdf/TypeMapper.php";

/**
 * @see EasyRdf_Utils
 */
require_once "EasyRdf/Utils.php";
