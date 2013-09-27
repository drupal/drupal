<?php

namespace Gliph\Exception;

/**
 * An exception thrown when a method is called on a visitor that it does not
 * expect in its current state.
 *
 * For example, this exception should be thrown by a visitor if it has a method
 * that returns data produced by a full traversal algorithm, but the algorithm
 * has not yet informed the visitor that it is done running.
 */
class WrongVisitorStateException extends \LogicException {}