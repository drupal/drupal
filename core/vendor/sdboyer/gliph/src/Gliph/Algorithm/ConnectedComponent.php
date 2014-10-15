<?php

namespace Gliph\Algorithm;

use Gliph\Graph\DirectedGraph;
use Gliph\Visitor\TarjanSCCVisitor;

/**
 * Contains algorithms for discovering connected components.
 */
class ConnectedComponent {

    /**
     * Finds connected components in the provided directed graph.
     *
     * @param DirectedGraph $graph
     *   The DirectedGraph to search for connected components.
     * @param TarjanSCCVisitor $visitor
     *   The visitor that will collect and store the connected components. One
     *   will be created if not provided.
     *
     * @return TarjanSCCVisitor
     *   The finalized visitor.
     */
    public static function tarjan_scc(DirectedGraph $graph, TarjanSCCVisitor $visitor = NULL) {
        $visitor = $visitor ?: new TarjanSCCVisitor();
        $counter = 0;
        $stack = array();
        $indices = new \SplObjectStorage();
        $lowlimits = new \SplObjectStorage();

        $visit = function($vertex) use (&$visit, &$counter, $graph, &$stack, $indices, $lowlimits, $visitor) {
            $indices->attach($vertex, $counter);
            $lowlimits->attach($vertex, $counter);
            $stack[] = $vertex;
            $counter++;

            $graph->eachAdjacent($vertex, function ($to) use (&$visit, $vertex, $indices, $lowlimits, &$stack) {
                if (!$indices->contains($to)) {
                    $visit($to);
                    $lowlimits[$vertex] = min($lowlimits[$vertex], $lowlimits[$to]);
                }
                else if (in_array($to, $stack, TRUE)) {
                    $lowlimits[$vertex] = min($lowlimits[$vertex], $indices[$to]);
                }
            });

            if ($lowlimits[$vertex] === $indices[$vertex]) {
                $visitor->newComponent();
                do {
                    $other = array_pop($stack);
                    $visitor->addToCurrentComponent($other);
                } while ($other != $vertex);
            }
        };

        $graph->eachVertex(function($vertex) use (&$visit, $indices) {
            if (!$indices->contains($vertex)) {
                $visit($vertex);
            }
        });

        return $visitor;
    }
}