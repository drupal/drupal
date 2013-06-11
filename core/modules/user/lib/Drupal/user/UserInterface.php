<?php

/**
 * @file
 * Contains \Drupal\user\UserInterface.
 */

namespace Drupal\user;

use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Session\AccountInterface;

/**
 * Provides an interface defining a user entity.
 */
interface UserInterface extends EntityInterface, AccountInterface {

}
