<?php

/**
 * @file
 * Provide views data for node.module.
 */

use Drupal\views\Analyzer;
use Drupal\views\ViewExecutable;

/**
 * Implements hook_views_data_alter().
 */
function node_views_data_alter(&$data) {
  $data['node']['node_bulk_form'] = array(
    'title' => t('Node operations bulk form'),
    'help' => t('Add a form element that lets you run operations on multiple nodes.'),
    'field' => array(
      'id' => 'node_bulk_form',
    ),
  );
}

/**
 * Implements hook_preprocess_node().
 */
function node_row_node_view_preprocess_node(&$variables) {
  $options = $variables['view']->rowPlugin->options;

  if (!$options['links']) {
    unset($variables['content']['links']);
  }
}

/**
 * Implements hook_views_wizard().
 */
function node_views_wizard() {
  // @todo: figure this piece out.
  if (\Drupal::moduleHandler()->moduleExists('statistics')) {
    $plugins['node']['available_sorts']['node_counter-totalcount:DESC'] = t('Number of hits');
  }

}
