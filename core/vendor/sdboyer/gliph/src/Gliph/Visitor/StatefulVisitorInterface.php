<?php

namespace Gliph\Visitor;

/**
 * Interface for stateful algorithm visitors.
 */
interface StatefulVisitorInterface {
    const NOT_STARTED = 0;
    const IN_PROGRESS = 1;
    const COMPLETE = 2;

    /**
     * Returns an integer indicating the current state of the visitor.
     *
     * @return int
     *   State should be one of the three StatefulVisitorInterface constants:
     *    - 0: indicates the algorithm has not yet started.
     *    - 1: indicates the algorithm is in progress.
     *    - 2: indicates the algorithm is complete.
     */
    public function getState();
}