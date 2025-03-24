<?php

/**
 * @file
 */

use Drupal\Core\Logger\RfcLogLevel;

/**
 * Creates a list of database log administration filters that can be applied.
 *
 * @return array
 *   Associative array of filters. The top-level keys are used as the form
 *   element names for the filters, and the values are arrays with the following
 *   elements:
 *   - title: Title of the filter.
 *   - where: The filter condition.
 *   - options: Array of options for the select list for the filter.
 */
function dblog_filters(): array {
  $filters = [];

  foreach (_dblog_get_message_types() as $type) {
    // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
    $types[$type] = t($type);
  }

  if (!empty($types)) {
    $filters['type'] = [
      'title' => t('Type'),
      'field' => 'w.type',
      'options' => $types,
    ];
  }

  $filters['severity'] = [
    'title' => t('Severity'),
    'field' => 'w.severity',
    'options' => RfcLogLevel::getLevels(),
  ];

  return $filters;
}
