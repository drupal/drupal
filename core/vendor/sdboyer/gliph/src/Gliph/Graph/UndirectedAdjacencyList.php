<?php

namespace Gliph\Graph;

use Gliph\Exception\NonexistentVertexException;

class UndirectedAdjacencyList extends AdjacencyList implements MutableUndirectedGraph {

    /**
     * {@inheritdoc}
     */
    public function addEdge($from, $to) {
        $this->addVertex($from)->addVertex($to);
        if (!$this->vertices[$from]->contains($to)) {
            $this->size++;
        }

        $this->vertices[$from]->attach($to);
        $this->vertices[$to]->attach($from);
    }

    /**
     * {@inheritdoc}
     */
    public function removeVertex($vertex) {
        if (!$this->hasVertex($vertex)) {
            throw new NonexistentVertexException('Vertex is not in the graph, it cannot be removed.', E_WARNING);
        }

        foreach ($this->vertices[$vertex] as $adjacent) {
            $this->vertices[$adjacent]->detach($vertex);
        }
        unset($this->vertices[$vertex]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeEdge($from, $to) {
        $this->vertices[$from]->detach($to);
        $this->vertices[$to]->detach($from);
    }

    /**
     * {@inheritdoc}
     */
    public function eachEdge($callback) {
        $edges = array();
        $complete = new \SplObjectStorage();
        $that = $this;
        $this->fev(function ($a, $adjacent) use (&$edges, &$complete, $that) {
            $set = $that->_getTraversableSplos($adjacent);
            foreach ($set as $b) {
                if (!$complete->contains($b)) {
                    $edges[] = array($a, $b);
                }
            }
            $that->_cleanupSplosTraversal($set);
            $complete->attach($a);
        });

        foreach ($edges as $edge) {
            call_user_func($callback, $edge);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function inDegree($vertex) {
        if (!$this->hasVertex($vertex)) {
            throw new NonexistentVertexException('Vertex is not in the graph, in-degree information cannot be provided', E_WARNING);
        }

        return $this->vertices[$vertex]->count();
    }

    /**
     * {@inheritdoc}
     */
    public function outDegree($vertex) {
        if (!$this->hasVertex($vertex)) {
            throw new NonexistentVertexException('Vertex is not in the graph, out-degree information cannot be provided', E_WARNING);
        }

        return $this->vertices[$vertex]->count();
    }
}