<?php

namespace Drupal\d6_comment_test\Plugin\migrate\source\d6;

use Drupal\comment\Plugin\migrate\source\d6\Comment as CoreComment;
use Drupal\migrate\Row;

/**
 * Test source plugin for deprecation testing.
 *
 * @MigrateSource(
 *   id = "d6_comment_test",
 *   source_module = "comment"
 * )
 */
class Comment extends CoreComment {

  /**
   * Allow access to protected method.
   */
  public function prepareComment(Row $row) {
    return parent::prepareComment($row);
  }

}
