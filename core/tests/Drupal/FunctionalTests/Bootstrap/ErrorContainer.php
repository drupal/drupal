<?php

namespace Drupal\FunctionalTests\Bootstrap;

use Drupal\Core\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Container base class which triggers an error.
 */
class ErrorContainer extends Container {

  /**
   * {@inheritdoc}
   */
  public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): ?object {
    if ($id === 'http_kernel') {
      // Enforce a recoverable error.
      $callable = function (ErrorContainer $container) {
      };
      return $callable(1);
    }
    return parent::get($id, $invalidBehavior);
  }

}
