<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\RevisionLinkDelete.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present link to delete a node revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_revision_link_delete")
 */
class RevisionLinkDelete extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getEntity($row);
    return Url::fromRoute('node.revision_delete_confirm', ['node' => $node->id(), 'node_revision' => $node->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Delete');
  }

}
