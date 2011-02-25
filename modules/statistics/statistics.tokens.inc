<?php

/**
 * @file
 * Builds placeholder replacement tokens for node visitor statistics.
 */

/**
 * Implements hook_token_info().
 */
function statistics_token_info() {
  $node['total-count'] = array(
    'name' => t("Number of views"),
    'description' => t("The number of visitors who have read the node."),
  );
  $node['day-count'] = array(
    'name' => t("Views today"),
    'description' => t("The number of visitors who have read the node today."),
  );
  $node['last-view'] = array(
    'name' => t("Last view"),
    'description' => t("The date on which a visitor last read the node."),
    'type' => 'date',
  );

  return array(
    'tokens' => array('node' => $node),
  );
}

/**
 * Implements hook_tokens().
 */
function statistics_tokens($type, $tokens, array $data = array(), array $options = array()) {
  $url_options = array('absolute' => TRUE);
  $replacements = array();

  if ($type == 'node' & !empty($data['node'])) {
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      if ($name == 'total-count') {
        $statistics = statistics_get($node->nid);
        $replacements[$original] = $statistics['totalcount'];
      }
      elseif ($name == 'day-count') {
        $statistics = statistics_get($node->nid);
        $replacements[$original] = $statistics['daycount'];
      }
      elseif ($name == 'last-view') {
        $statistics = statistics_get($node->nid);
        $replacements[$original] = format_date($statistics['timestamp']);
      }
    }

    if ($created_tokens = token_find_with_prefix($tokens, 'last-view')) {
      $statistics = statistics_get($node->nid);
      $replacements += token_generate('date', $created_tokens, array('date' => $statistics['timestamp']), $options);
    }
  }

  return $replacements;
}
