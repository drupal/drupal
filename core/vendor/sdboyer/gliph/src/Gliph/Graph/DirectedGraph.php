<?php

namespace Gliph\Graph;

/**
 * Interface for directed graph datastructures.
 */
interface DirectedGraph extends Graph {

    /**
     * Adds a directed edge to this graph.
     *
     * Directed edges are also often referred to as 'arcs'.
     *
     * @param object $tail
     *   An object vertex from which the edge originates. The vertex will be
     *   added to the graph if it is not already present.
     * @param object $head
     *   An object vertex to which the edge points. The vertex will be added to
     *   the graph if it is not already present.
     *
     * @return DirectedGraph
     *   The current graph instance.
     */
    public function addDirectedEdge($tail, $head);

    /**
     * Returns the transpose of this graph.
     *
     * A transpose is identical to the current graph, except that its edges
     * have had their directionality reversed.
     *
     * Transposed graphs are sometimes called the 'reverse' or 'converse'.
     *
     * @return DirectedGraph
     */
    public function transpose();

    /**
     * Indicates whether or not this graph is acyclic.
     *
     * @return bool
     */
    public function isAcyclic();

    /**
     * Returns the cycles in this graph, if any.
     *
     * @return array
     *   An array of arrays, each subarray representing a full cycle in the
     *   graph. If the array is empty, the graph is acyclic.
     */
    public function getCycles();
}