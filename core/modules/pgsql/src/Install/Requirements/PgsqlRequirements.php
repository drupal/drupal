<?php

declare(strict_types=1);

namespace Drupal\pgsql\Install\Requirements;

use Drupal\Core\Database\Database;
use Drupal\Core\Extension\InstallRequirementsInterface;
use Drupal\Core\Extension\Requirement\RequirementSeverity;

/**
 * Install time requirements for the pgsql module.
 */
class PgsqlRequirements implements InstallRequirementsInterface {

  /**
   * {@inheritdoc}
   */
  public static function getRequirements(): array {
    $requirements = [];
    // Test with PostgreSQL databases for the status of the pg_trgm extension.
    if (Database::isActiveConnection()) {
      $connection = Database::getConnection();

      // Set the requirement just for postgres.
      if ($connection->driver() == 'pgsql') {
        $requirements['pgsql_extension_pg_trgm'] = [
          'severity' => RequirementSeverity::OK,
          'title' => t('PostgreSQL pg_trgm extension'),
          'value' => t('Available'),
          'description' => t('The pg_trgm PostgreSQL extension is present.'),
        ];

        // If the extension is not available, set the requirement error.
        if (!$connection->schema()->extensionExists('pg_trgm')) {
          $requirements['pgsql_extension_pg_trgm']['severity'] = RequirementSeverity::Error;
          $requirements['pgsql_extension_pg_trgm']['value'] = t('Not created');
          $requirements['pgsql_extension_pg_trgm']['description'] = t('The <a href=":pg_trgm">pg_trgm</a> PostgreSQL extension is not present. The extension is required by Drupal to improve performance when using PostgreSQL. See <a href=":requirements">Drupal database server requirements</a> for more information.', [
            ':pg_trgm' => 'https://www.postgresql.org/docs/current/pgtrgm.html',
            ':requirements' => 'https://www.drupal.org/docs/system-requirements/database-server-requirements',
          ]);
        }

      }
    }

    return $requirements;
  }

}
