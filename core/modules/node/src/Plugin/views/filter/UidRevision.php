<?php

namespace Drupal\node\Plugin\views\filter;

use Drupal\node\Plugin\views\UidRevisionTrait;
use Drupal\user\Plugin\views\filter\Name;
use Drupal\views\Attribute\ViewsFilter;

/**
 * Filter handler to check for revisions a certain user has created.
 *
 * @ingroup views_filter_handlers
 */
#[ViewsFilter("node_uid_revision")]
class UidRevision extends Name {

  use UidRevisionTrait;

  /**
   * {@inheritdoc}
   */
  public function query($group_by = FALSE) {
    $this->uidRevisionQuery($this->value, $this->options['group']);
  }

}
