<?php

/**
 * @file
 * Definition of Drupal\node\Plugin\views\field\RevisionLink.
 */

namespace Drupal\node\Plugin\views\field;

use Drupal\node\Plugin\views\field\Link;
use Drupal\views\Plugin\views\display\DisplayPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Component\Annotation\PluginID;

/**
 * Field handler to present a link to a node revision.
 *
 * @ingroup views_field_handlers
 *
 * @PluginID("node_revision_link")
 */
class RevisionLink extends Link {

  /**
   * Overrides Drupal\views\Plugin\views\field\FieldPluginBase::init().
   */
  public function init(ViewExecutable $view, DisplayPluginBase $display, array &$options = NULL) {
    parent::init($view, $display, $options);

    $this->additional_fields['node_vid'] = array('table' => 'node_field_revision', 'field' => 'vid');
  }

  public function access() {
    return user_access('view revisions') || user_access('administer nodes');
  }

  function render_link($data, $values) {
    list($node, $vid) = $this->get_revision_entity($values, 'view');
    if (!isset($vid)) {
      return;
    }

    // Current revision uses the node view path.
    $path = 'node/' . $node->nid;
    if (!$node->isDefaultRevision()) {
      $path .= "/revisions/$vid/view";
    }

    $this->options['alter']['make_link'] = TRUE;
    $this->options['alter']['path'] = $path;
    $this->options['alter']['query'] = drupal_get_destination();

    return !empty($this->options['text']) ? $this->options['text'] : t('View');
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
    $vid = $this->get_value($values, 'node_vid');
    $node = $this->get_entity($values);
    // Unpublished nodes ignore access control.
    $node->status = 1;
    // Ensure user has access to perform the operation on this node.
    if (!node_access($op, $node)) {
      return array($node, NULL);
    }
    return array($node, $vid);
  }

}
