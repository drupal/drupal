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
 * A class for fetching, saving and deleting graphs to a Graph Store.
 * Implementation of the SPARQL 1.1 Graph Store HTTP Protocol.
 *
 * @package    EasyRdf
 * @copyright  Copyright (c) 2009-2013 Nicholas J Humfrey
 * @license    http://www.opensource.org/licenses/bsd-license.php
 */
class EasyRdf_GraphStore
{
    /** The address of the GraphStore endpoint */
    private $uri = null;
    private $parsedUri = null;


    /** Create a new SPARQL Graph Store client
     *
     * @param string $uri The address of the graph store endpoint
     */
    public function __construct($uri)
    {
        $this->uri = $uri;
        $this->parsedUri = new EasyRdf_ParsedUri($uri);
    }

    /** Get the URI of the graph store
     *
     * @return string The URI of the graph store
     */
    public function getUri()
    {
        return $this->uri;
    }

    /** Fetch a named graph from the graph store
     *
     * The URI can either be a full absolute URI or
     * a URI relative to the URI of the graph store.
     *
     * @param string $uriRef The URI of graph desired
     * @return object EasyRdf_Graph The graph requested
     */
    public function get($uriRef)
    {
        $graphUri = $this->parsedUri->resolve($uriRef)->toString();
        $dataUrl = $this->urlForGraph($graphUri);
        $graph = new EasyRdf_Graph($graphUri);
        $graph->load($dataUrl);
        return $graph;
    }

    /** Send some graph data to the graph store
     *
     * This method is used by insert() and replace()
     *
     * @ignore
     */
    protected function sendGraph($method, $graph, $uriRef, $format)
    {
        if (is_object($graph) and $graph instanceof EasyRdf_Graph) {
            if ($uriRef == null) {
                $uriRef = $graph->getUri();
            }
            $data = $graph->serialise($format);
        } else {
            $data = $graph;
        }

        $formatObj = EasyRdf_Format::getFormat($format);
        $mimeType = $formatObj->getDefaultMimeType();

        $graphUri = $this->parsedUri->resolve($uriRef)->toString();
        $dataUrl = $this->urlForGraph($graphUri);

        $client = EasyRdf_Http::getDefaultHttpClient();
        $client->resetParameters(true);
        $client->setUri($dataUrl);
        $client->setMethod($method);
        $client->setRawData($data);
        $client->setHeaders('Content-Type', $mimeType);
        $response = $client->request();
        if (!$response->isSuccessful()) {
            throw new EasyRdf_Exception(
                "HTTP request for $dataUrl failed: ".$response->getMessage()
            );
        }
        return $response;
    }

    /** Replace the contents of a graph in the graph store with new data
     *
     * The $graph parameter is the EasyRdf_Graph object to be sent to the
     * graph store. Alternatively it can be a string, already serialised.
     *
     * The URI can either be a full absolute URI or
     * a URI relative to the URI of the graph store.
     *
     * The $format parameter can be given to specify the serialisation
     * used to send the graph data to the graph store.
     *
     * @param object EasyRdfGraph $graph The URI of graph desired
     * @param string $uriRef The URI of graph to be replaced
     * @param string $format The format of the data to send to the graph store
     * @return object EasyRdf_Http_Response The response from the graph store
     */
    public function replace($graph, $uriRef = null, $format = 'ntriples')
    {
        return $this->sendGraph('PUT', $graph, $uriRef, $format);
    }

    /** Add data to a graph in the graph store
     *
     * The $graph parameter is the EasyRdf_Graph object to be sent to the
     * graph store. Alternatively it can be a string, already serialised.
     *
     * The URI can either be a full absolute URI or
     * a URI relative to the URI of the graph store.
     *
     * The $format parameter can be given to specify the serialisation
     * used to send the graph data to the graph store.
     *
     * @param object EasyRdfGraph $graph The URI of graph desired
     * @param string $uriRef The URI of graph to be added to
     * @param string $format The format of the data to send to the graph store
     * @return object EasyRdf_Http_Response The response from the graph store
     */
    public function insert($graph, $uriRef = null, $format = 'ntriples')
    {
        return $this->sendGraph('POST', $graph, $uriRef, $format);
    }

    /** Delete a graph from the graph store
     *
     * The URI can either be a full absolute URI or
     * a URI relative to the URI of the graph store.
     *
     * @param string $uriRef The URI of graph to be added to
     * @return object EasyRdf_Http_Response The response from the graph store
     */
    public function delete($uriRef)
    {
        $graphUri = $this->parsedUri->resolve($uriRef)->toString();
        $dataUrl = $this->urlForGraph($graphUri);

        $client = EasyRdf_Http::getDefaultHttpClient();
        $client->resetParameters(true);
        $client->setUri($dataUrl);
        $client->setMethod('DELETE');
        $response = $client->request();
        if (!$response->isSuccessful()) {
            throw new EasyRdf_Exception(
                "HTTP request to delete $dataUrl failed: ".$response->getMessage()
            );
        }
        return $response;
    }

    /** Work out the full URL for a graph store request.
     *  by checking if if it is a direct or indirect request.
     *  @ignore
     */
    protected function urlForGraph($url)
    {
        if (strpos($url, $this->uri) === false) {
            $url = $this->uri."?graph=".urlencode($url);
        }
        return $url;
    }

    /** Magic method to return URI of the graph store when casted to string
     *
     * @return string The URI of the graph store
     */
    public function __toString()
    {
        return empty($this->uri) ? '' : $this->uri;
    }
}
