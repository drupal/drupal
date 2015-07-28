<?php

/**
 * @file
 * Contains \Drupal\system\Tests\Bootstrap\ExceptionContainer.
 */

namespace Drupal\system\Tests\Bootstrap;

use Drupal\Core\DependencyInjection\Container;

/**
 * Base container which throws an exception.
 */
class ExceptionContainer extends Container {

  /**
   * {@inheritdoc}
   */
  public function get($id, $invalidBehavior = self::EXCEPTION_ON_INVALID_REFERENCE) {
    if ($id === 'http_kernel') {
      throw new \Exception('Thrown exception during Container::get');
    }
    else {
      return parent::get($id, $invalidBehavior);
    }
  }

}
