<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\ErrorContainerRebuildKernel.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\DrupalKernel;

/**
 * A kernel which produces a container which triggers an error.
 */
class ErrorContainerRebuildKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  const CONTAINER_BASE_CLASS = '\Drupal\system\Tests\Bootstrap\ErrorContainer';

}
