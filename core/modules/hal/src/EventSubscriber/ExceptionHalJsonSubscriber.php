<?php

/**
 * @file
 * Contains \Drupal\hal\EventSubscriber\ExceptionHalJsonSubscriber.
 */

namespace Drupal\hal\EventSubscriber;

use Drupal\Core\EventSubscriber\ExceptionJsonSubscriber;

/**
 * Handle HAL JSON exceptions the same as JSON exceptions.
 */
class ExceptionHalJsonSubscriber extends ExceptionJsonSubscriber {

  /**
   * {@inheritdoc}
   */
  protected function getHandledFormats() {
    return ['hal_json'];
  }

}
