<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\RevisionLinkRevert.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Session\AccountInterface;
use Drupal\node\Plugin\views\field\RevisionLink;
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
  public function access(AccountInterface $account) {
    return $account->hasPermission('revert revisions') || $account->hasPermission('administer nodes');
  }

  /**
   * Prepares the link to revert node to a revision.
   *
   * @param \Drupal\Core\Entity\EntityInterface $data
   *   The node revision entity this field belongs to.
   * @param \Drupal\views\ResultRow $values
   *   The values retrieved from the view's result set.
   *
   * @return string
   *   Returns a string for the link text.
   */
  protected function renderLink($data, ResultRow $values) {
    list($node, $vid) = $this->get_revision_entity($values, 'update');
    if (!isset($vid)) {
      return;
    }

    // Current revision cannot be reverted.
    if ($node->isDefaultRevision()) {
      return;
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = 'node/' . $node->id() . "/revisions/$vid/revert";
    $this->options['alter']['query'] = drupal_get_destination();

    return !empty($this->options['text']) ? $this->options['text'] : t('Revert');
  }

}
