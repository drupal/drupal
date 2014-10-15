<?php

namespace Gliph\Graph;

/**
 * Interface for directed graph datastructures.
 */
interface DirectedGraph extends Graph {

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