<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\RevisionLink.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Url;
use Drupal\node\Plugin\views\field\Link;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ResultRow;
use Drupal\views\ViewExecutable;

/**
 * Field handler to present a link to a node revision.
 *
 * @ingroup views_field_handlers
 *
 * @ViewsField("node_revision_link")
 */
class RevisionLink extends Link {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['node_vid'] = array('table' => 'node_field_revision', 'field' => 'vid');
  }

  /**
   * {@inheritdoc}
   */
  public function access(AccountInterface $account) {
    return $account->hasPermission('view revisions') || $account->hasPermission('administer nodes');
  }

  /**
   * Prepares the link to point to a node revision.
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
    list($node, $vid) = $this->get_revision_entity($values, 'view');
    if (!isset($vid)) {
      return;
    }

    // Current revision uses the node view path.
    if (!$node->isDefaultRevision()) {
      $url = Url::fromRoute('node.revision_show', ['node' => $node->nid, 'node_revision' => $vid]);
    }
    else {
      $url = $node->urlInfo();
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['url'] = $url;
    $this->options['alter']['query'] = $this->getDestinationArray();

    return !empty($this->options['text']) ? $this->options['text'] : $this->t('View');
  }

  /**
   * Returns the revision values of a node.
   *
   * @param object $values
   *   An object containing all retrieved values.
   * @param string $op
   *   The operation being performed.
   *
   * @return array
   *   A numerically indexed array containing the current node object and the
   *   revision ID for this row.
   */
  function get_revision_entity($values, $op) {
    $vid = $this->getValue($values, 'node_vid');
    $node = $this->getEntity($values);
    // Unpublished nodes ignore access control.
    $node->setPublished(TRUE);
    // Ensure user has access to perform the operation on this node.
    if (!$node->access($op)) {
      return array($node, NULL);
    }
    return array($node, $vid);
  }

}
