<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\filter\UidRevision.
 */

namespace Drupal\node\Plugin\views\filter;

use Drupal\user\Plugin\views\filter\Name;
use Drupal\Component\Annotation\PluginID;

/**
 * Filter handler to check for revisions a certain user has created.
 *
 * @ingroup views_filter_handlers
 *
 * @PluginID("node_uid_revision")
 */
class UidRevision extends Name {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();

    $placeholder = $this->placeholder();

    $args = array_values($this->value);

    $this->query->add_where_expression($this->options['group'], "$this->tableAlias.uid IN($placeholder) OR
      ((SELECT COUNT(DISTINCT vid) FROM {node_field_revision} nfr WHERE nfr.revision_uid IN ($placeholder) AND nfr.nid = $this->tableAlias.nid) > 0)", array($placeholder => $args),
      $args);
  }

}
