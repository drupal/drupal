<?php

/**
 * @file
 * Contains \Drupal\tracker\Plugin\views\filter\UserUid.
 */

namespace Drupal\tracker\Plugin\views\filter;

use Drupal\user\Plugin\views\filter\Name;

/**
 * UID filter to check for nodes that a user posted or commented on.
 *
 * @ingroup views_filter_handlers
 *
 * @ViewsFilter("tracker_user_uid")
 */
class UserUid extends Name {

  /**
   * {@inheritdoc}
   */
  public function query() {
    // Because this handler thinks it's an argument for a field on the {node}
    // table, we need to make sure {tracker_user} is JOINed and use its alias
    // for the WHERE clause.
    $tracker_user_alias = $this->query->ensureTable('tracker_user');
    // Cast scalars to array so we can consistently use an IN condition.
    $this->query->addWhere(0, "$tracker_user_alias.uid", (array) $this->value, 'IN');
  }

}
