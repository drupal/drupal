<?php

namespace Gliph\Traversal;

use Gliph\Exception\RuntimeException;
use Gliph\Graph\DirectedGraph;
use Gliph\Visitor\DepthFirstToposortVisitor;
use Gliph\Visitor\DepthFirstVisitorInterface;

class DepthFirst {

    /**
     * Perform a depth-first traversal on the provided graph.
     *
     * @param DirectedGraph $graph
     *   The graph on which to perform the depth-first search.
     * @param DepthFirstVisitorInterface $visitor
     *   The visitor object to use during the traversal.
     * @param object|\SplDoublyLinkedList $start
     *   A vertex, or vertices, to use as start points for the traversal. There
     *   are a few sub-behaviors here:
     *     - If an SplDoublyLinkedList, SplQueue, or SplStack is provided, the
     *       traversal will deque and visit vertices contained therein.
     *     - If a single vertex object is provided, it will be the sole
     *       originating point for the traversal.
     *     - If no value is provided, DepthFirst::find_sources() is called to
     *       search the graph for source vertices. These are place into an
     *       SplQueue in the order in which they are discovered, and traversal
     *       is then run over that queue in the same manner as if calling code
     *       had provided a queue directly. This method *guarantees* that all
     *       vertices in the graph will be visited.
     *
     * @throws RuntimeException
     *   Thrown if an invalid $start parameter is provided.
     */
    public static function traverse(DirectedGraph $graph, DepthFirstVisitorInterface $visitor, $start = NULL) {
        if ($start === NULL) {
            $queue = self::find_sources($graph, $visitor);
        }
        else if ($start instanceof \SplDoublyLinkedList) {
            $queue = $start;
        }
        else if (is_object($start)) {
            $queue = new \SplDoublyLinkedList();
            $queue->push($start);
        }

        if ($queue->isEmpty()) {
            throw new RuntimeException('No start vertex or vertices were provided, and no source vertices could be found in the provided graph.', E_WARNING);
        }

        $visiting = new \SplObjectStorage();
        $visited = new \SplObjectStorage();

        $visitor->beginTraversal();

        $visit = function($vertex) use ($graph, $visitor, &$visit, $visiting, $visited) {
            if ($visiting->contains($vertex)) {
                $visitor->onBackEdge($vertex, $visit);
            }
            else if (!$visited->contains($vertex)) {
                $visiting->attach($vertex);

                $visitor->onStartVertex($vertex, $visit);

                $graph->eachAdjacent($vertex, function($to) use ($vertex, &$visit, $visitor) {
                    $visitor->onExamineEdge($vertex, $to, $visit);
                    $visit($to);
                });

                $visitor->onFinishVertex($vertex, $visit);

                $visiting->detach($vertex);
                $visited->attach($vertex);
            }
        };

        while (!$queue->isEmpty()) {
            $vertex = $queue->shift();
            $visit($vertex);
        }

        $visitor->endTraversal();
    }

    /**
     * Finds source vertices in a DirectedGraph, then enqueues them.
     *
     * @param DirectedGraph $graph
     * @param DepthFirstVisitorInterface $visitor
     *
     * @return \SplQueue
     */
    public static function find_sources(DirectedGraph $graph, DepthFirstVisitorInterface $visitor) {
        $incomings = new \SplObjectStorage();
        $queue = new \SplQueue();

        $graph->eachEdge(function ($edge) use (&$incomings) {
            if (!isset($incomings[$edge[1]])) {
                $incomings[$edge[1]] = new \SplObjectStorage();
            }
            $incomings[$edge[1]]->attach($edge[0]);
        });

        // Prime the queue with vertices that have no incoming edges.
        $graph->eachVertex(function($vertex) use ($queue, $incomings, $visitor) {
            if (!$incomings->contains($vertex)) {
                $queue->push($vertex);
                // TRUE second param indicates source vertex
                $visitor->onInitializeVertex($vertex, TRUE, $queue);
            }
            else {
                $visitor->onInitializeVertex($vertex, FALSE, $queue);
            }
        });

        return $queue;
    }

    /**
     * Performs a topological sort on the provided graph.
     *
     * @param DirectedGraph $graph
     * @param object|\SplDoublyLinkedList $start
     *   The starting point(s) for the toposort. @see DepthFirst::traverse()
     *
     * @return array
     *   A valid topologically sorted list for the provided graph.
     */
    public static function toposort(DirectedGraph $graph, $start = NULL) {
        $visitor = new DepthFirstToposortVisitor();
        self::traverse($graph, $visitor, $start);

        return $visitor->getTsl();
    }
}