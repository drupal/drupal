<?php

declare(strict_types=1);

namespace Drupal\mysql\Hook;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Render\Markup;
use Drupal\Core\StringTranslation\StringTranslationTrait;

/**
 * Requirements for the MySQL module.
 */
class MysqlRequirements {

  use StringTranslationTrait;

  /**
   * Implements hook_runtime_requirements().
   */
  #[Hook('runtime_requirements')]
  public function runtime(): array {
    $requirements = [];
    // Test with MySql databases.
    if (Database::isActiveConnection()) {
      $connection = Database::getConnection();
      // Only show requirements when MySQL is the default database connection.
      if (!($connection->driver() === 'mysql' && $connection->getProvider() === 'mysql')) {
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
