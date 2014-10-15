<?php
namespace Gliph\Graph;

/**
 * Describes a directed graph that can be modified after initial creation.
 */
interface MutableDirectedGraph extends MutableGraph, DirectedGraph {

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
}