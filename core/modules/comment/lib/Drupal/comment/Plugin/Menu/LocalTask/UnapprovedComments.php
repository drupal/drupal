<?php

/**
 * @file
 * Contains \Drupal\comment\Plugin\Menu\LocalTask\UnapprovedComments.
 */

namespace Drupal\comment\Plugin\Menu\LocalTask;

use Drupal\Core\Menu\LocalTaskDefault;

/**
 * Provides a local task that shows the amount of unapproved comments.
 */
class UnapprovedComments extends LocalTaskDefault {

  /**
   * {@inheritdoc}
   */
  public function getTitle() {
    return comment_count_unpublished();
  }

}
