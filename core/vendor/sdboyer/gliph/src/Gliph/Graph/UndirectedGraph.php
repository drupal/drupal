<?php

namespace Gliph\Graph;

/**
 * Interface for undirected graph datastructures.
 */
interface UndirectedGraph extends Graph {

    /**
     * Adds an undirected edge to this graph.
     *
     * @param object $a
     *   The first object vertex in the edge pair. The vertex will be added to
     *   the graph if it is not already present.
     * @param object $b
     *   The second object vertex in the edge pair. The vertex will be added to
     *   the graph if it is not already present.
     *
     * @return UndirectedGraph
     *   The current graph instance.
     */
    public function addEdge($a, $b);

}