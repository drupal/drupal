<?php

/**
 * @file
 * Definition of Drupal\views\ViewStorageInterface.
 */

namespace Drupal\views;

use Drupal\Core\Config\Entity\ConfigEntityInterface;

/**
 * Defines an interface for View storage classes.
 */
interface ViewStorageInterface extends \IteratorAggregate, ConfigEntityInterface {
}
