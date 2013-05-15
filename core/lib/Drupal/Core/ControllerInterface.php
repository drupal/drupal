<?php

/**
 * @file
 * Contains \Drupal\Core\ControllerInterface;
 */

namespace Drupal\Core;

use Drupal\Core\Controller\ControllerInterface as BaseInterface;

/**
 * BC shiv for controllers using the old interface name.
 *
 * @deprecated Use \Drupal\Core\Controller\ControllerInterface instead.
 */
interface ControllerInterface extends BaseInterface {
}
