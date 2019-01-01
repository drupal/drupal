<?php

/**
 * @file
 * Builds placeholder replacement tokens for node visitor statistics.
 */

use Drupal\Core\Render\BubbleableMetadata;

/**
 * Implements hook_token_info().
 */
function statistics_token_info() {
  $node['total-count'] = [
    'name' => t("Number of views"),
    'description' => t("The number of visitors who have read the node."),
  ];
  $node['day-count'] = [
    'name' => t("Views today"),
    'description' => t("The number of visitors who have read the node today."),
  ];
  $node['last-view'] = [
    'name' => t("Last view"),
    'description' => t("The date on which a visitor last read the node."),
    'type' => 'date',
  ];

  return [
    'tokens' => ['node' => $node],
  ];
}

/**
 * Implements hook_tokens().
 */
function statistics_tokens($type, $tokens, array $data, array $options, BubbleableMetadata $bubbleable_metadata) {
  $token_service = \Drupal::token();

  $replacements = [];

  if ($type == 'node' & !empty($data['node'])) {
    $node = $data['node'];

    foreach ($tokens as $name => $original) {
      if ($name == 'total-count') {
        $statistics = statistics_get($node->id());
        $replacements[$original] = $statistics['totalcount'];
      }
      elseif ($name == 'day-count') {
        $statistics = statistics_get($node->id());
        $replacements[$original] = $statistics['daycount'];
      }
      elseif ($name == 'last-view') {
        $statistics = statistics_get($node->id());
        $replacements[$original] = \Drupal::service('date.formatter')->format($statistics['timestamp']);
      }
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'last-view')) {
      $statistics = statistics_get($node->id());
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $statistics['timestamp']], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
