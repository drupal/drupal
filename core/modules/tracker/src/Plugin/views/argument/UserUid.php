<?php

namespace Drupal\tracker\Plugin\views\argument;

use Drupal\comment\Plugin\views\argument\UserUid as CommentUserUid;

/**
 * UID argument to check for nodes that user posted or commented on.
 *
 * @ingroup views_argument_handlers
 *
 * @ViewsArgument("tracker_user_uid")
 */
class UserUid extends CommentUserUid {

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    // Because this handler thinks it's an argument for a field on the {node}
    // table, we need to make sure {tracker_user} is JOINed and use its alias
    // for the WHERE clause.
    $tracker_user_alias = $this->query->ensureTable('tracker_user');
    $this->query->addWhere(0, "$tracker_user_alias.uid", $this->argument);
  }

}
