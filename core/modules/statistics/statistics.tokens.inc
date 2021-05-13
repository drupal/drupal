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

    /** @var \Drupal\statistics\StatisticsStorageInterface $stats_storage */
    $stats_storage = \Drupal::service('statistics.storage.node');
    $node_view = NULL;

    foreach ($tokens as $name => $original) {
      if ($name == 'total-count') {
        $node_view = $node_view ?? $stats_storage->fetchView($node->id());
        $replacements[$original] = $node_view ? $node_view->getTotalCount() : 0;
      }
      elseif ($name == 'day-count') {
        $node_view = $node_view ?? $stats_storage->fetchView($node->id());
        $replacements[$original] = $node_view ? $node_view->getDayCount() : 0;
      }
      elseif ($name == 'last-view') {
        $node_view = $node_view ?? $stats_storage->fetchView($node->id());
        $replacements[$original] = $node_view ? \Drupal::service('date.formatter')->format($node_view->getTimestamp()) : t('never');
      }
    }

    if ($created_tokens = $token_service->findWithPrefix($tokens, 'last-view')) {
      $node_view = $node_view ?? $stats_storage->fetchView($node->id());
      $replacements += $token_service->generate('date', $created_tokens, ['date' => $node_view ? $node_view->getTimestamp() : 0], $options, $bubbleable_metadata);
    }
  }

  return $replacements;
}
