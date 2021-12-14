<?php

namespace Drupal\FunctionalTests\Bootstrap;

use Drupal\Core\DependencyInjection\Container;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base container which throws an exception.
 */
class ExceptionContainer extends Container {

  /**
   * {@inheritdoc}
   */
  public function get($id, $invalidBehavior = ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE): ?object {
    if ($id === 'http_kernel') {
      throw new \Exception('Thrown exception during Container::get');
    }
    else {
      return parent::get($id, $invalidBehavior);
    }
  }

}
