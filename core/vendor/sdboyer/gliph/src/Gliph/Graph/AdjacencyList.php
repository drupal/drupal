<?php

namespace Gliph\Graph;

use Gliph\Exception\InvalidVertexTypeException;
use Gliph\Exception\NonexistentVertexException;

abstract class AdjacencyList implements Graph {

    protected $vertices;

    public function __construct() {
        $this->vertices = new \SplObjectStorage();
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

        foreach ($this->vertices[$vertex] as $adjacent_vertex) {
            call_user_func($callback, $adjacent_vertex);
        }

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

    protected function fev($callback) {
        foreach ($this->vertices as $vertex) {
            $outgoing = $this->vertices->getInfo();
            $callback($vertex, $outgoing);
        }

        return $this;
    }
}