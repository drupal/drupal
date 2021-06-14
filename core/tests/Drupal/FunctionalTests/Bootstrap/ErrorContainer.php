<?php

namespace Drupal\FunctionalTests\Bootstrap;

use Drupal\Core\DependencyInjection\Container;

/**
 * Container base class which triggers an error.
 */
class ErrorContainer extends Container {

  /**
   * {@inheritdoc}
   */
  public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE) {
    if ($id === 'http_kernel') {
      // Enforce a recoverable error.
      $callable = function (ErrorContainer $container) {
      };
      $callable(1);
    }
    else {
      return parent::get($id, $invalidBehavior);
    }
  }

}
