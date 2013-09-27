<?php

/**
 * @file
 * Contains \Gliph\Visitor\StatefulDepthFirstVisitor.
 */

namespace Gliph\Visitor;

use Gliph\Exception\WrongVisitorStateException;

/**
 * A base class for stateful depth first visitors.
 *
 * This visitor tracks state as indicated to it by the depth first traversal
 * algorithm, throwing exceptions if certain methods are accessed from an
 * incorrect state.
 */
abstract class StatefulDepthFirstVisitor implements DepthFirstVisitorInterface, StatefulVisitorInterface {

    /**
     * Represents the current state of the visitor.
     *
     * @var int
     */
    protected $state = self::NOT_STARTED;

    public function onInitializeVertex($vertex, $source, \SplQueue $queue) {
        if ($this->state != self::NOT_STARTED) {
            throw new WrongVisitorStateException('Vertex initialization should only happen before traversal has begun.');
        }
    }

    public function beginTraversal() {
        if ($this->state != self::NOT_STARTED) {
            throw new WrongVisitorStateException('Traversal has already begun; cannot begin twice.');
        }
        $this->state = self::IN_PROGRESS;
    }

    public function onBackEdge($vertex, \Closure $visit) {
        if ($this->state != self::IN_PROGRESS) {
            throw new WrongVisitorStateException('onBackEdge should only be called while traversal is in progress.');
        }
    }

    public function onStartVertex($vertex, \Closure $visit) {
        if ($this->state != self::IN_PROGRESS) {
            throw new WrongVisitorStateException('onStartVertex should only be called while traversal is in progress.');
        }
    }

    public function onExamineEdge($from, $to, \Closure $visit) {
        if ($this->state != self::IN_PROGRESS) {
            throw new WrongVisitorStateException('onExamineEdge should only be called while traversal is in progress.');
        }
    }

    public function onFinishVertex($vertex, \Closure $visit) {
        if ($this->state != self::IN_PROGRESS) {
            throw new WrongVisitorStateException('onFinishVertex should only be called while traversal is in progress.');
        }
    }

    public function endTraversal() {
        if ($this->state != self::IN_PROGRESS) {
            throw new WrongVisitorStateException('Cannot end traversal; no traversal is currently in progress.');
        }
        $this->state = self::COMPLETE;
    }

    /**
     * {@inheritdoc}
     */
    public function getState() {
        return $this->state;
    }
}