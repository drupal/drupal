<?php

namespace Gliph\Graph;

use Gliph\Exception\InvalidVertexTypeException;
use Gliph\Exception\NonexistentVertexException;

/**
 * A graph, represented as an adjacency list.
 *
 * Adjacency lists store vertices directly, and edges relative to the vertices
 * they connect. That means there is no overall list of edges in the graph; only
 * a list of the graph's vertices. In this implementation, that list is keyed by
 * vertex, with the value being a list of all the vertices to which that vertex
 * is adjacent - hence, "adjacency list."
 *
 * Consequently, this structure offers highly efficient access to vertices, but
 * less efficient access to edges.
 *
 * In an undirected graph, the edges are stored in both vertices' adjacency
 * lists. In a directed graph, only the out-edges are stored in each vertex's
 * adjacency list. This makes accessing in-edge information in a directed graph
 * highly inefficient.
 */
abstract class AdjacencyList implements MutableGraph {

    /**
     * Contains the adjacency list of vertices.
     *
     * @var \SplObjectStorage
     */
    protected $vertices;

    /**
     * Bookkeeper for nested iteration.
     *
     * @var \SplObjectStorage
     */
    protected $walking;

    /**
     * Count of the number of edges in the graph.
     *
     * We keep track because calculating it on demand is expensive.
     *
     * @var int
     */
    protected $size = 0;

    public function __construct() {
        $this->vertices = new \SplObjectStorage();
        $this->walking = new \SplObjectStorage();
    }

    /**
     * {@inheritdoc}
     */
    public function addVertex($vertex) {
        if (!is_object($vertex)) {
            throw new InvalidVertexTypeException('Vertices must be objects; non-object provided.');
        }

        if (!$this->hasVertex($vertex)) {
            $this->vertices[$vertex] = new \SplObjectStorage();
        }

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function eachAdjacent($vertex, $callback) {
        if (!$this->hasVertex($vertex)) {
            throw new NonexistentVertexException('Vertex is not in graph; cannot iterate over its adjacent vertices.');
        }

        $set = $this->_getTraversableSplos($this->vertices[$vertex]);
        foreach ($set as $adjacent_vertex) {
            call_user_func($callback, $adjacent_vertex);
        }
        $this->walking->detach($set);
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function eachVertex($callback) {
        $this->fev(function ($v, $adjacent) use ($callback) {
            call_user_func($callback, $v, $adjacent);
        });

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function hasVertex($vertex) {
        return $this->vertices->contains($vertex);
    }

    /**
     * {@inheritdoc}
     */
    public function order() {
        return $this->vertices->count();
    }

    /**
     * {@inheritdoc}
     */
    public function size() {
        return $this->size;
    }

    protected function fev($callback) {
        $set = $this->_getTraversableSplos($this->vertices);
        foreach ($set as $vertex) {
            $outgoing = $set->getInfo();
            $callback($vertex, $outgoing);
        }
        $this->walking->detach($set);

        return $this;
        }

    /**
     * Helper function to ensure SPLOS traversal pointer is not overridden.
     *
     * This would otherwise occur if nested calls are made that traverse the
     * same SPLOS. This keeps track of which SPLOSes are currently being
     * traversed, and if it's in use, it returns a clone.
     *
     * It is incumbent on the calling code to release the semaphore directly
     * by calling $this->_cleanupSplosTraversal() when the traversal in
     * question is complete. (This is very important!)
     *
     * Only public because it needs to be called from within closures.
     *
     * @param \SplObjectStorage $splos
     *   The SPLOS to traverse.
     *
     * @return \SplObjectStorage
     *   A SPLOS that is safe for traversal; may or may not be a clone of the
     *   original.
     */
    public function _getTraversableSplos(\SplObjectStorage $splos) {
        if ($this->walking->contains($splos)) {
            return clone $splos;
        }
        else {
            $this->walking->attach($splos);
            return $splos;
        }
    }

    /**
     * Helper function to clean up SPLOSes after finishing traversal.
     *
     * @param \SplObjectStorage $splos
     *   The SPLOS to mark as safe for traversal again.
     */
    public function _cleanupSplosTraversal(\SplObjectStorage $splos) {
        $this->walking->detach($splos);
    }
}