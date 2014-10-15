<?php

namespace Gliph\Graph;

/**
 * Describes a undirected graph that can be modified after initial creation.
 */
interface MutableUndirectedGraph extends Graph {

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
     * @return MutableUndirectedGraph
     *   The current graph instance.
     */
    public function addEdge($a, $b);
}