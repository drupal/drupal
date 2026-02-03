<?php

namespace Drupal\dblog;

use Drupal\Core\Database\Connection;
use Drupal\Core\Logger\RfcLogLevel;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Filter methods for the dblog module.
 */
class DbLogFilters {

  use StringTranslationTrait;

  public function __construct(
    protected readonly Connection $connection,
  ) {}

  /**
   * Gathers a list of uniquely defined database log message types.
   *
   * @return array
   *   List of uniquely defined database log message types.
   */
  public function getMessageTypes(): array {
    return $this->connection->query('SELECT DISTINCT([type]) FROM {watchdog} ORDER BY [type]')
      ->fetchAllKeyed(0, 0);
  }

  /**
   * Creates a list of database log administration filters that can be applied.
   *
   * @return array
   *   Associative array of filters. The top-level keys are used as the form
   *   element names for the filters, and the values are arrays with the
   *   following elements:
   *   - title: Title of the filter.
   *   - where: The filter condition.
   *   - options: Array of options for the select list for the filter.
   */
  public function filters(): array {
    $filters = [];

    foreach ($this->getMessageTypes() as $type) {
      // phpcs:ignore Drupal.Semantics.FunctionT.NotLiteralString
      $types[$type] = $this->t($type);
    }

    if (!empty($types)) {
      $filters['type'] = [
        'title' => $this->t('Type'),
        'field' => 'w.type',
        'options' => $types,
      ];
    }

    $filters['severity'] = [
      'title' => $this->t('Severity'),
      'field' => 'w.severity',
      'options' => RfcLogLevel::getLevels(),
    ];

    return $filters;
  }

}
