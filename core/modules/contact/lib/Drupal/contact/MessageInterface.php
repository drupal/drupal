<?php

/**
 * @file
 * Contains \Drupal\contact\Plugin\Core\Entity\MessageInterface.
 */

namespace Drupal\contact;

use Drupal\Core\Entity\EntityInterface;

/**
 * Provides an interface defining a contant message entity
 */
interface MessageInterface extends EntityInterface {

  /**
   * Return TRUE if this is the personal contact form.
   *
   * @return bool
   *   TRUE if the message bundle is personal.
   */
  public function isPersonal();

}
