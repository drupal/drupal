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
 * Class for making SPARQL queries using the SPARQL 1.1 Protocol
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_Sparql_Client
{
    /** The query/read address of the SPARQL Endpoint */
    private $queryUri = null;

    /** The update/write address of the SPARQL Endpoint */
    private $updateUri = null;

    /** Configuration settings */
    private $config = array();


    /** Create a new SPARQL endpoint client
     *
     * If the query and update endpoints are the same, then you
     * only need to give a single URI.
     *
     * @param string $queryUri The address of the SPARQL Query Endpoint
     * @param string $updateUri Optional address of the SPARQL Update Endpoint
     */
    public function __construct($queryUri, $updateUri = null)
    {
        $this->queryUri = $queryUri;
        if ($updateUri) {
            $this->updateUri = $updateUri;
        } else {
            $this->updateUri = $queryUri;
        }
    }

    /** Get the URI of the SPARQL query endpoint
     *
     * @return string The query URI of the SPARQL endpoint
     */
    public function getQueryUri()
    {
        return $this->queryUri;
    }

    /** Get the URI of the SPARQL update endpoint
     *
     * @return string The query URI of the SPARQL endpoint
     */
    public function getUpdateUri()
    {
        return $this->updateUri;
    }

    /**
     * @depredated
     * @ignore
     */
    public function getUri()
    {
        return $this->queryUri;
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
        return $this->request('query', $query);
    }

    /** Count the number of triples in a SPARQL 1.1 endpoint
     *
     * Performs a SELECT query to estriblish the total number of triples.
     *
     * Counts total number of triples by default but a conditional triple pattern
     * can be given to count of a subset of all triples.
     *
     * @param string $condition Triple-pattern condition for the count query
     * @return integer The number of triples
     */
    public function countTriples($condition = '?s ?p ?o')
    {
        // SELECT (COUNT(*) AS ?count)
        // WHERE {
        //   {?s ?p ?o}
        //   UNION
        //   {GRAPH ?g {?s ?p ?o}}
        // }
        $result = $this->query('SELECT (COUNT(*) AS ?count) {'.$condition.'}');
        return $result[0]->count->getValue();
    }

    /** Get a list of named graphs from a SPARQL 1.1 endpoint
     *
     * Performs a SELECT query to get a list of the named graphs
     *
     * @param string $limit Optional limit to the number of results
     * @return array Array of EasyRdf_Resource objects for each named graph
     */
    public function listNamedGraphs($limit = null)
    {
        $query = "SELECT DISTINCT ?g WHERE {GRAPH ?g {?s ?p ?o}}";
        if (!is_null($limit)) {
            $query .= " LIMIT ".(int)$limit;
        }
        $result = $this->query($query);

        // Convert the result object into an array of resources
        $graphs = array();
        foreach ($result as $row) {
            array_push($graphs, $row->g);
        }
        return $graphs;
    }

    /** Make an update request to the SPARQL endpoint
     *
     * Successful responses will return the HTTP response object
     *
     * Unsuccessful responses will throw an exception
     *
     * @param string $query The update query string to be executed
     * @return object EasyRdf_Http_Response HTTP response
     */
    public function update($query)
    {
        return $this->request('update', $query);
    }

    public function insert($data, $graphUri = null)
    {
        #$this->updateData('INSET',
        $query = 'INSERT DATA {';
        if ($graphUri) {
            $query .= "GRAPH <$graphUri> {";
        }
        $query .= $this->convertToTriples($data);
        if ($graphUri) {
            $query .= "}";
        }
        $query .= '}';
        return $this->update($query);
    }

    protected function updateData($operation, $data, $graphUri = null)
    {
        $query = "$operation DATA {";
        if ($graphUri) {
            $query .= "GRAPH <$graphUri> {";
        }
        $query .= $this->convertToTriples($data);
        if ($graphUri) {
            $query .= "}";
        }
        $query .= '}';
        return $this->update($query);
    }

    public function clear($graphUri, $silent = false)
    {
        $query = "CLEAR";
        if ($silent) {
            $query .= " SILENT";
        }
        if (preg_match("/^all|named|default$/i", $graphUri)) {
            $query .= " $graphUri";
        } else {
            $query .= " GRAPH <$graphUri>";
        }
        return $this->update($query);
    }

    /*
     * Internal function to make an HTTP request to SPARQL endpoint
     *
     * @ignore
     */
    protected function request($type, $query)
    {
        // Check for undefined prefixes
        $prefixes = '';
        foreach (EasyRdf_Namespace::namespaces() as $prefix => $uri) {
            if (strpos($query, "$prefix:") !== false and
                strpos($query, "PREFIX $prefix:") === false) {
                $prefixes .=  "PREFIX $prefix: <$uri>\n";
            }
        }

        $client = EasyRdf_Http::getDefaultHttpClient();
        $client->resetParameters();

        // Tell the server which response formats we can parse
        $accept = EasyRdf_Format::getHttpAcceptHeader(
            array(
              'application/sparql-results+json' => 1.0,
              'application/sparql-results+xml' => 0.8
            )
        );
        $client->setHeaders('Accept', $accept);

        if ($type == 'update') {
            $client->setMethod('POST');
            $client->setUri($this->updateUri);
            $client->setRawData($prefixes . $query);
            $client->setHeaders('Content-Type', 'application/sparql-update');
        } elseif ($type == 'query') {
            // Use GET if the query is less than 2kB
            // 2046 = 2kB minus 1 for '?' and 1 for NULL-terminated string on server
            $encodedQuery = 'query='.urlencode($prefixes . $query);
            if (strlen($encodedQuery) + strlen($this->queryUri) <= 2046) {
                $client->setMethod('GET');
                $client->setUri($this->queryUri.'?'.$encodedQuery);
            } else {
                // Fall back to POST instead (which is un-cacheable)
                $client->setMethod('POST');
                $client->setUri($this->queryUri);
                $client->setRawData($encodedQuery);
                $client->setHeaders('Content-Type', 'application/x-www-form-urlencoded');
            }
        }

        $response = $client->request();
        if ($response->getStatus() == 204) {
            // No content
            return $response;
        } elseif ($response->isSuccessful()) {
            list($type, $params) = EasyRdf_Utils::parseMimeType(
                $response->getHeader('Content-Type')
            );
            if (strpos($type, 'application/sparql-results') === 0) {
                return new EasyRdf_Sparql_Result($response->getBody(), $type);
            } else {
                return new EasyRdf_Graph($this->queryUri, $response->getBody(), $type);
            }
        } else {
            throw new EasyRdf_Exception(
                "HTTP request for SPARQL query failed: ".$response->getBody()
            );
        }
    }

    protected function convertToTriples($data)
    {
        if (is_string($data)) {
            return $data;
        } elseif (is_object($data) and $data instanceof EasyRdf_Graph) {
            # FIXME: insert Turtle when there is a way of seperateing out the prefixes
            return $data->serialise('ntriples');
        } else {
            throw new EasyRdf_Exception(
                "Don't know how to convert to triples for SPARQL query: ".$response->getBody()
            );
        }
    }
}
