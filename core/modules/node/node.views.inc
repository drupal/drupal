<?php

/**
 * @file
 * Provide views data for node.module.
 */

/**
 * Implements hook_views_wizard().
 */
function node_views_wizard() {
  // @todo: figure this piece out.
  if (\Drupal::moduleHandler()->moduleExists('statistics')) {
    $plugins['node']['available_sorts']['node_counter-totalcount:DESC'] = t('Number of hits');
  }

}
