<?php

namespace Gliph\Graph;

use Gliph\Exception\NonexistentVertexException;

class UndirectedAdjacencyList extends AdjacencyList implements UndirectedGraph {

    /**
     * {@inheritdoc}
     */
    public function addEdge($from, $to) {
        if (!$this->hasVertex($from)) {
            $this->addVertex(($from));
        }

        if (!$this->hasVertex($to)) {
            $this->addVertex($to);
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
        $this->fev(function ($a, $adjacent) use (&$edges, &$complete) {
            foreach ($adjacent as $b) {
                if (!$complete->contains($b)) {
                    $edges[] = array($a, $b);
                }
            }
            $complete->attach($a);
        });

        foreach ($edges as $edge) {
            call_user_func($callback, $edge);
        }
    }
}