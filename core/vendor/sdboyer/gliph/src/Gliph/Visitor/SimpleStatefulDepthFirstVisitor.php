<?php

/**
 * @file
 * Contains \Gliph\Visitor\SimpleStatefulDepthFirstVisitor.
 */

namespace Gliph\Visitor;

use Gliph\Exception\WrongVisitorStateException;

/**
 * Simplified stateful depth-first visitor with less complex state.
 *
 * Rather than a three-way distinction (NOT_STARTED/IN_PROGRESS/COMPLETE), the
 * simplified visitor only cares about COMPLETE.
 */
abstract class SimpleStatefulDepthFirstVisitor implements StatefulVisitorInterface, DepthFirstVisitorInterface {

    /**
     * Represents the current state of the visitor.
     *
     * This visitor disregards the NOT_STARTED phase as irrelevant, and so is
     * considered IN_PROGRESS from initial construction.
     *
     * @var int
     */
    protected $state = self::IN_PROGRESS;

    /**
     * @codeCoverageIgnore
     */
    public function beginTraversal() {}

    /**
     * {@inheritdoc}
     */
    public function endTraversal() {
        if ($this->getState() != self::IN_PROGRESS) {
            throw new WrongVisitorStateException('Cannot end traversal, visitor is not marked as currently being in progress.');
        }
        $this->state = self::COMPLETE;
    }

    public function getState() {
        return $this->state;
    }
}