<?php

namespace Drupal\mysqli\Hook;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Markup;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Hook implementations for mysqli.
 */
class MysqliHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match): ?string {
    switch ($route_name) {
      case 'help.page.mysqli':
        $output = '';
        $output .= '<h3>' . $this->t('About') . '</h3>';
        $output .= '<p>' . $this->t('The MySQLi module provides the connection between Drupal and a MySQL, MariaDB or equivalent database using the mysqli PHP extension. For more information, see the <a href=":mysqli">online documentation for the MySQLi module</a>.', [':mysqli' => 'https://www.drupal.org/documentation/modules/mysqli']) . '</p>';
        return $output;

    }
    return NULL;
  }

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtimeRequirements(): array {
    $requirements = [];

    // Test with MySql databases.
    if (Database::isActiveConnection()) {
      $connection = Database::getConnection();
      // Only show requirements when MySQLi is the default database connection.
      if (!($connection->driver() === 'mysqli' && $connection->getProvider() === 'mysqli')) {
        return [];
      }

      $query = $connection->isMariaDb() ? 'SELECT @@SESSION.tx_isolation' : 'SELECT @@SESSION.transaction_isolation';

      $isolation_level = $connection->query($query)->fetchField();

      $tables_missing_primary_key = [];
      $tables = $connection->schema()->findTables('%');
      foreach ($tables as $table) {
        $primary_key_column = Database::getConnection()->query("SHOW KEYS FROM {" . $table . "} WHERE Key_name = 'PRIMARY'")->fetchAllAssoc('Column_name');
        if (empty($primary_key_column)) {
          $tables_missing_primary_key[] = $table;
        }
      }

      $description = [];
      if ($isolation_level == 'READ-COMMITTED') {
        if (empty($tables_missing_primary_key)) {
          $severity_level = RequirementSeverity::OK;
        }
        else {
          $severity_level = RequirementSeverity::Error;
        }
      }
      else {
        if ($isolation_level == 'REPEATABLE-READ') {
          $severity_level = RequirementSeverity::Warning;
        }
        else {
          $severity_level = RequirementSeverity::Error;
          $description[] = $this->t('This is not supported by Drupal.');
        }
        $description[] = $this->t('The recommended level for Drupal is "READ COMMITTED".');
      }

      if (!empty($tables_missing_primary_key)) {
        $description[] = $this->t('For this to work correctly, all tables must have a primary key. The following table(s) do not have a primary key: @tables.', ['@tables' => implode(', ', $tables_missing_primary_key)]);
      }

      $description[] = $this->t('See the <a href=":performance_doc">setting MySQL transaction isolation level</a> page for more information.', [
        ':performance_doc' => 'https://www.drupal.org/docs/system-requirements/setting-the-mysql-transaction-isolation-level',
      ]);

      $requirements['mysql_transaction_level'] = [
        'title' => $this->t('Transaction isolation level'),
        'severity' => $severity_level,
        'value' => $isolation_level,
        'description' => Markup::create(implode(' ', $description)),
      ];
    }

    return $requirements;
  }

}
