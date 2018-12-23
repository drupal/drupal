<?php

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Url;
use Drupal\views\Plugin\views\field\LinkBase;
use Drupal\views\ResultRow;

/**
 * Field handler to present a link to a node revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_revision_link")
 */
class RevisionLink extends LinkBase {

  /**
   * {@inheritdoc}
   */
  protected function getUrlInfo(ResultRow $row) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getEntity($row);
    // Current revision uses the node view path.
    return !$node->isDefaultRevision() ?
      Url::fromRoute('entity.node.revision', ['node' => $node->id(), 'node_revision' => $node->getRevisionId()]) :
      $node->toUrl();
  }

  /**
   * {@inheritdoc}
   */
  protected function renderLink(ResultRow $row) {
    /** @var \Drupal\node\NodeInterface $node */
    $node = $this->getEntity($row);
    if (!$node->getRevisionid()) {
      return '';
    }
    $text = parent::renderLink($row);
    $this->options['alter']['query'] = $this->getDestinationArray();
    return $text;
  }

  /**
   * {@inheritdoc}
   */
  protected function getDefaultLabel() {
    return $this->t('View');
  }

}
