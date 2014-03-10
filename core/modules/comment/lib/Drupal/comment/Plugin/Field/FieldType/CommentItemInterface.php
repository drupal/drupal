<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Field\FieldType\CommentItemInterface.
 */

namespace Drupal\comment\Plugin\Field\FieldType;

use Drupal\Core\Field\ConfigFieldItemInterface;

/**
 * Interface definition for Comment items.
 */
interface CommentItemInterface extends ConfigFieldItemInterface {

  /**
   * Comments for this entity are hidden.
   */
  const HIDDEN = 0;

  /**
   * Comments for this entity are closed.
   */
  const CLOSED = 1;

  /**
   * Comments for this entity are open.
   */
  const OPEN = 2;

}
