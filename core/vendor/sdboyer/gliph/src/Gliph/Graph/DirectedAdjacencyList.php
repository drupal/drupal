<?php

namespace Gliph\Graph;

use Gliph\Algorithm\ConnectedComponent;
use Gliph\Exception\NonexistentVertexException;
use Gliph\Exception\RuntimeException;
use Gliph\Traversal\DepthFirst;
use Gliph\Visitor\DepthFirstToposortVisitor;

class DirectedAdjacencyList extends AdjacencyList implements DirectedGraph {

    /**
     * {@inheritdoc}
     */
    public function addDirectedEdge($tail, $head) {
        if (!$this->hasVertex($tail)) {
            $this->addVertex(($tail));
        }

        if (!$this->hasVertex($head)) {
            $this->addVertex($head);
        }

        $this->vertices[$tail]->attach($head);
    }

    /**
     * {@inheritdoc}
     */
    public function removeVertex($vertex) {
        if (!$this->hasVertex($vertex)) {
            throw new NonexistentVertexException('Vertex is not in the graph, it cannot be removed.', E_WARNING);
        }

        $this->eachVertex(function($v, $outgoing) use ($vertex) {
            if ($outgoing->contains($vertex)) {
                $outgoing->detach($vertex);
            }
        });
        unset($this->vertices[$vertex]);
    }

    /**
     * {@inheritdoc}
     */
    public function removeEdge($tail, $head) {
        $this->vertices[$tail]->detach($head);
    }

    /**
     * {@inheritdoc}
     */
    public function eachEdge($callback) {
        $edges = array();
        $this->fev(function ($from, $outgoing) use (&$edges) {
            foreach ($outgoing as $to) {
                $edges[] = array($from, $to);
            }
        });

        foreach ($edges as $edge) {
            call_user_func($callback, $edge);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function transpose() {
        $graph = new self();
        $this->eachEdge(function($edge) use (&$graph) {
            $graph->addDirectedEdge($edge[1], $edge[0]);
        });

        return $graph;
    }

    /**
     * {@inheritdoc}
     */
    public function isAcyclic() {
        // The DepthFirstToposortVisitor throws an exception on cycles.
        try {
            DepthFirst::traverse($this, new DepthFirstToposortVisitor());
            return TRUE;
        }
        catch (RuntimeException $e) {
            return FALSE;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getCycles() {
        $scc = ConnectedComponent::tarjan_scc($this);
        return $scc->getConnectedComponents();
    }
}

