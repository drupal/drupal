<?php

namespace Gliph\Graph;

use Gliph\Exception\InvalidVertexTypeException;
use Gliph\Exception\NonexistentVertexException;

/**
 * The most basic interface for graph datastructures.
 */
interface Graph {

    /**
     * Adds a vertex to the graph.
     *
     * Gliph requires that its graph vertices be objects; beyond that, it does
     * not care about vertex type.
     *
     * @param object $vertex
     *   An object to use as a vertex in the graph.
     *
     * @return Graph
     *   The current graph instance.
     *
     * @throws InvalidVertexTypeException
     *   Thrown if an invalid type of data is provided as a vertex.
     */
    public function addVertex($vertex);

    /**
     * Remove a vertex from the graph.
     *
     * This will also remove any edges that include the vertex.
     *
     * @param object $vertex
     *   A vertex object to remove from the graph.
     *
     * @return Graph
     *   The current graph instance.
     *
     * @throws NonexistentVertexException
     *   Thrown if the provided vertex is not present in the graph.
     */
    public function removeVertex($vertex);

    /**
     * Removes an edge from the graph.
     *
     * @param $a
     *   The first vertex in the edge pair to remove. In a directed graph, this
     *   is the tail vertex.
     * @param $b
     *   The second vertex in the edge pair to remove. In a directed graph, this
     *   is the head vertex.
     *
     * @return Graph
     *   The current graph instance.
     */
    public function removeEdge($a, $b);

    /**
     * Calls the callback with each vertex adjacent to the provided vertex.
     *
     * The meaning of "adjacency" depends on the type of graph. In a directed
     * graph, it refers to all the out-edges of the provided vertex. In an
     * undirected graph, in-edges and out-edges are the same, so this method
     * will iterate over both.
     *
     * @param object $vertex
     *   The vertex whose out-edges should be visited.
     * @param callback $callback
     *   The callback to fire. For each vertex found along an out-edge, this
     *   callback will be called with that vertex as the sole parameter.
     *
     * @return Graph
     *   The current graph instance.
     *
     * @throws NonexistentVertexException
     *   Thrown if the vertex provided in the first parameter is not present in
     *   the graph.
     */
    public function eachAdjacent($vertex, $callback);

    /**
     * Calls the provided callback for each vertex in the graph.
     *
     * @param $callback
     *   The callback is called once for each vertex in the graph. Two
     *   parameters are provided:
     *    - The vertex being inspected.
     *    - An SplObjectStorage containing a list of all the vertices adjacent
     *      to the vertex being inspected.
     *
     * @return Graph
     *   The current graph instance.
     */
    public function eachVertex($callback);

    /**
     * Calls the provided callback for each edge in the graph.
     *
     * @param $callback
     *   The callback is called once for each unique edge in the graph. A single
     *   parameter is provided: a 2-tuple (indexed array with two elements),
     *   where the first element is the first vertex (in a directed graph, the
     *   tail) and the second element is the second vertex (in a directed graph,
     *   the head).
     *
     * @return Graph
     *   The current graph instance.
     */
    public function eachEdge($callback);

    /**
     * Indicates whether or not the provided vertex is present in the graph.
     *
     * @param object $vertex
     *   The vertex object to check for membership in the graph.
     *
     * @return bool
     *   TRUE if the vertex is present, FALSE otherwise.
     */
    public function hasVertex($vertex);
}