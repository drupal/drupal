<?php

namespace Drupal\Component\Plugin\Exception;

/**
 * Exception thrown when a decorator's _call() method is triggered, but the
 * decorated object does not contain the requested method.
 */
class InvalidDecoratedMethod extends \BadMethodCallException implements ExceptionInterface {}
