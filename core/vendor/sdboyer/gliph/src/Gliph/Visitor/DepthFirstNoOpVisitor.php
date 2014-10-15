<?php

namespace Gliph\Visitor;

/**
 * A no-op visitor for depth first traversal algorithms.
 *
 * @codeCoverageIgnore
 */
class DepthFirstNoOpVisitor implements DepthFirstVisitorInterface {
    public function onInitializeVertex($vertex, $source, \SplQueue $queue) {}
    public function beginTraversal() {}
    public function onBackEdge($vertex, \Closure $visit) {}
    public function onStartVertex($vertex, \Closure $visit) {}
    public function onExamineEdge($from, $to, \Closure $visit) {}
    public function onFinishVertex($vertex, \Closure $visit) {}
    public function endTraversal() {}
}