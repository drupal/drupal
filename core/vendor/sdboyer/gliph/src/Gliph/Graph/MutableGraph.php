<?php
namespace Gliph\Graph;

/**
 * Describes a graph that can be modified after initial creation.
 */
interface MutableGraph extends Graph {

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
}