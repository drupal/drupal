<?php

/**
 * @file
 * Contains \Gliph\Visitor\DepthFirstToposortVisitor.
 */

namespace Gliph\Visitor;

use Gliph\Exception\RuntimeException;
use Gliph\Exception\WrongVisitorStateException;

/**
 * Visitor that produces a topologically sorted list on a depth first traversal.
 */
class DepthFirstToposortVisitor extends StatefulDepthFirstVisitor implements DepthFirstVisitorInterface {

    /**
     * @var array
     */
    protected $tsl = array();

    public function onBackEdge($vertex, \Closure $visit) {
        parent::onBackEdge($vertex, $visit);
        throw new RuntimeException(sprintf('Cycle detected in provided graph; toposort is not possible.'));
    }

    public function beginTraversal() {
        parent::beginTraversal();
        $this->tsl = array();
    }

    public function onFinishVertex($vertex, \Closure $visit) {
        parent::onFinishVertex($vertex, $visit);
        $this->tsl[] = $vertex;
    }

    /**
     * Returns a valid topological sort of the visited graph as an array.
     *
     * @return array
     *
     * @throws WrongVisitorStateException
     *   Thrown if called before traversal is complete.
     */
    public function getTsl() {
        if ($this->getState() !== self::COMPLETE) {
            throw new WrongVisitorStateException('Topologically sorted list cannot be retrieved until traversal is complete.');
        }

        return $this->tsl;
    }
}