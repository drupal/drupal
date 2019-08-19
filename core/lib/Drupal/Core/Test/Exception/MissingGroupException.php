<?php

namespace Drupal\Core\Test\Exception;

/**
 * Exception thrown when a test class is missing an @group annotation.
 *
 * @see \Drupal\Core\Test\TestDiscovery::getTestClasses()
 */
class MissingGroupException extends \LogicException {
}
