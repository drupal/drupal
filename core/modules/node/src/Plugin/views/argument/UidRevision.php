<?php

namespace Drupal\node\Plugin\views\argument;

use Drupal\node\Plugin\views\UidRevisionTrait;
use Drupal\user\Plugin\views\argument\Uid;
use Drupal\views\Attribute\ViewsArgument;

/**
 * Filter handler, accepts a user ID.
 *
 * Checks for nodes that a user posted or created a revision on.
 */
#[ViewsArgument(
  id: 'node_uid_revision',
)]
class UidRevision extends Uid {

  use UidRevisionTrait;

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->uidRevisionQuery([$this->argument]);
  }

}
