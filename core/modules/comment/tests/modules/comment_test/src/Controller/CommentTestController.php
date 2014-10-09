<?php

/**
 * @file
 * Contains \Drupal\comment_test\Controller\CommentTestController.
 */

namespace Drupal\comment_test\Controller;

use Drupal\comment\CommentInterface;
use Drupal\Core\Controller\ControllerBase;

/**
 * Controller for the comment_test.module.
 */
class CommentTestController extends ControllerBase {

  /**
   * Provides a comment report.
   */
  public function commentReport(CommentInterface $comment) {
    return ['#markup' => $this->t('Report for a comment')];
  }

}
