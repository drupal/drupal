<?php

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to revert a node to a revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_revision_link_revert")
 */
class RevisionLinkRevert extends RevisionLink {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getEntity($row);
    if (!$node) {
      return NULL;
    }
    return Url::fromRoute('node.revision_revert_confirm', ['node' => $node->id(), 'node_revision' => $node->getRevisionId()]);
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('Revert');
  }

}
