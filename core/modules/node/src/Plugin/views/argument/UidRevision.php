<?php

/**
 * @file
 * Contains \Drupal\node\Plugin\views\argument\UidRevision.
 */

namespace Drupal\node\Plugin\views\argument;

use Drupal\user\Plugin\views\argument\Uid;

/**
 * Filter handler to accept a user id to check for nodes that
 * user posted or created a revision on.
 *
 * @ViewsArgument("node_uid_revision")
 */
class UidRevision extends Uid {

  public function query($group_by = FALSE) {
    $this->ensureMyTable();
    $placeholder = $this->placeholder();
    $this->query->addWhereExpression(0, "$this->tableAlias.revision_uid = $placeholder OR ((SELECT COUNT(DISTINCT vid) FROM {node_revision} nr WHERE nfr.revision_uid = $placeholder AND nr.nid = $this->tableAlias.nid) > 0)", array($placeholder => $this->argument));
  }

}
