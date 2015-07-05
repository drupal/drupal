<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Bootstrap\ErrorContainer.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\Core\DependencyInjection\Container;

/**
 * Container base class which triggers an error.
 */
class ErrorContainer extends Container {

  /**
   * {@inheritdoc}
   */
  public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE) {
    // Enforce a recoverable error.
    $callable = function(ErrorContainer $container) {
    };
    $callable(1);
  }

}
