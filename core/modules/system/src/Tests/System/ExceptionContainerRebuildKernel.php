<?php

/**
 * @file
 * Contains \Drupal\system\Tests\System\ExceptionContainerRebuildKernel.
 */

namespace Drupal\system\Tests\System;

use Drupal\Core\DrupalKernel;

/**
 * A kernel which produces a container which triggers an exception.
 */
class ExceptionContainerRebuildKernel extends DrupalKernel {

  /**
   * {@inheritdoc}
   */
  const CONTAINER_BASE_CLASS = '\Drupal\system\Tests\Bootstrap\ExceptionContainer';

}
