<?php

/**
 * @file
 * Contains \Drupal\comment\Entity\CommentInterface.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a comment entity.
 */
interface CommentInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Returns the permalink URL for this comment.
   *
   * @return array
   *   An array containing the 'path' and 'options' keys used to build the URI
   *   of the comment, and matching the signature of
   *   UrlGenerator::generateFromPath().
   */
  public function permalink();
}
