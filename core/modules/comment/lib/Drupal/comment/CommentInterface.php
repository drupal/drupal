<?php

/**
 * @file
 * Contains \Drupal\comment\CommentInterface.
 */

namespace Drupal\comment;

use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityChangedInterface;

/**
 * Provides an interface defining a comment entity.
 */
interface CommentInterface extends ContentEntityInterface, EntityChangedInterface {

  /**
   * Comment is awaiting approval.
   */
  const NOT_PUBLISHED = 0;

  /**
   * Comment is published.
   */
  const PUBLISHED = 1;

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
