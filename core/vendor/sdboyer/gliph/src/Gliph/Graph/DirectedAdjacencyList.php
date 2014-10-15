<?php

namespace Gliph\Graph;

use Gliph\Algorithm\ConnectedComponent;
use Gliph\Exception\NonexistentVertexException;
use Gliph\Exception\RuntimeException;
use Gliph\Traversal\DepthFirst;
use Gliph\Visitor\DepthFirstToposortVisitor;

class DirectedAdjacencyList extends AdjacencyList implements MutableDirectedGraph {

    /**
     * {@inheritdoc}
     */
    public function addDirectedEdge($tail, $head) {
        $this->addVertex($tail)->addVertex($head);
        if (!$this->vertices[$tail]->contains($head)) {
            $this->size++;
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
        $that = $this;
        $this->fev(function ($from, $outgoing) use (&$edges, $that) {
            $set = $that->_getTraversableSplos($outgoing);
            foreach ($set as $to) {
                $edges[] = array($from, $to);
            }
            $that->_cleanupSplosTraversal($set);
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

    /**
     * {@inheritdoc}
     */
    public function inDegree($vertex) {
        if (!$this->hasVertex($vertex)) {
            throw new NonexistentVertexException('Vertex is not in the graph, in-degree information cannot be provided', E_WARNING);
        }

        $count = 0;
        $this->fev(function ($from, $outgoing) use (&$count, $vertex) {
            if ($outgoing->contains($vertex)) {
                $count++;
            }
        });

        return $count;
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

