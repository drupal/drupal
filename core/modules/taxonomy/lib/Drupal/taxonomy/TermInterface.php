<?php

/**
 * @file
 * Contains \Drupal\taxonomy\TermInterface.
 */

namespace Drupal\taxonomy;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a taxonomy term entity.
 */
interface TermInterface extends ContentEntityInterface, EntityChangedInterface {

}
