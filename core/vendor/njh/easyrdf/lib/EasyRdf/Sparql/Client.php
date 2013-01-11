<?php

/**
 * EasyRdf
 *
 * LICENSE
 *
 * Copyright (c) 2009-2012 Nicholas J Humfrey.  All rights reserved.
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
 * @license    http://www.opensource.org/licenses/bsd-license.php
 * @version    $Id$
 */

/**
 * Class for making SPARQL queries using the SPARQL 1.1 Protocol
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2012 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Sparql_Client
{
    /** The address of the SPARQL Endpoint */
    private $uri = null;

    /** Configuration settings */
    private $config = array();


    /** Create a new SPARQL endpoint client
     *
     * @param string $uri The address of the SPARQL Endpoint
     */
    public function __construct($uri)
    {
        $this->uri = $uri;
    }

    /** Get the URI of the SPARQL endpoint
     *
     * @return string The URI of the SPARQL endpoint
     */
    public function getUri()
    {
        return $this->uri;
    }

    /** Make a query to the SPARQL endpoint
     *
     * SELECT and ASK queries will return an object of type
     * EasyRdf_Sparql_Result.
     *
     * CONSTRUCT and DESCRIBE queries will return an object
     * of type EasyRdf_Graph.
     *
     * @param string $query The query string to be executed
     * @return object EasyRdf_Sparql_Result|EasyRdf_Graph Result of the query.
     */
    public function query($query)
    {
        # Add namespaces to the queryString
        $prefixes = '';
        foreach (EasyRdf_Namespace::namespaces() as $prefix => $uri) {
            if (strpos($query, "$prefix:") !== false and
                strpos($query, "PREFIX $prefix:") === false) {
                $prefixes .=  "PREFIX $prefix: <$uri>\n";
            }
        }

        $client = EasyRdf_Http::getDefaultHttpClient();
        $client->resetParameters();
        $client->setUri($this->uri);
        $client->setMethod('GET');

        $accept = EasyRdf_Format::getHttpAcceptHeader(
            array(
              'application/sparql-results+json' => 1.0,
              'application/sparql-results+xml' => 0.8
            )
        );
        $client->setHeaders('Accept', $accept);
        $client->setParameterGet('query', $prefixes . $query);

        $response = $client->request();
        if ($response->isSuccessful()) {
            list($type, $params) = EasyRdf_Utils::parseMimeType(
                $response->getHeader('Content-Type')
            );
            if (strpos($type, 'application/sparql-results') === 0) {
                return new EasyRdf_Sparql_Result($response->getBody(), $type);
            } else {
                return new EasyRdf_Graph($this->uri, $response->getBody(), $type);
            }
        } else {
            throw new EasyRdf_Exception(
                "HTTP request for SPARQL query failed: ".$response->getBody()
            );
        }
    }

    /** Magic method to return URI of the SPARQL endpoint when casted to string
     *
     * @return string The URI of the SPARQL endpoint
     */
    public function __toString()
    {
        return $this->uri == null ? '' : $this->uri;
    }
}
