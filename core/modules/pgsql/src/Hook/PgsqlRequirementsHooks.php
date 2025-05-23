<?php

namespace Drupal\pgsql\Hook;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\Requirement\RequirementSeverity;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for pgsql module.
 */
class PgsqlRequirementsHooks {

  use StringTranslationTrait;

  /**
   * Implements hook_update_requirements().
   *
   * Implements hook_runtime_requirements().
   */
  #[Hook('update_requirements')]
  #[Hook('runtime_requirements')]
  public function checkRequirements(): array {
    $requirements = [];
    // Test with PostgreSQL databases for the status of the pg_trgm extension.
    if (Database::isActiveConnection()) {
      $connection = Database::getConnection();

      // Set the requirement just for postgres.
      if ($connection->driver() == 'pgsql') {
        $requirements['pgsql_extension_pg_trgm'] = [
          'severity' => RequirementSeverity::OK,
          'title' => $this->t('PostgreSQL pg_trgm extension'),
          'value' => $this->t('Available'),
          'description' => $this->t('The pg_trgm PostgreSQL extension is present.'),
        ];

        // If the extension is not available, set the requirement error.
        if (!$connection->schema()->extensionExists('pg_trgm')) {
          $requirements['pgsql_extension_pg_trgm']['severity'] = RequirementSeverity::Error;
          $requirements['pgsql_extension_pg_trgm']['value'] = $this->t('Not created');
          $requirements['pgsql_extension_pg_trgm']['description'] = $this->t('The <a href=":pg_trgm">pg_trgm</a> PostgreSQL extension is not present. The extension is required by Drupal to improve performance when using PostgreSQL. See <a href=":requirements">Drupal database server requirements</a> for more information.', [
            ':pg_trgm' => 'https://www.postgresql.org/docs/current/pgtrgm.html',
            ':requirements' => 'https://www.drupal.org/docs/system-requirements/database-server-requirements',
          ]);
        }

      }
    }

    return $requirements;
  }

}
