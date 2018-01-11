<?php

namespace Drupal\migrate\Audit;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\MigrationInterface;

/**
 * Audits migrations that create content entities in the destination system.
 */
class IdAuditor implements AuditorInterface {

  use StringTranslationTrait;

  /**
   * {@inheritdoc}
   */
  public function audit(MigrationInterface $migration) {
    // If the migration does not opt into auditing, it passes.
    if (!$migration->isAuditable()) {
      return AuditResult::pass($migration);
    }

    $interface = HighestIdInterface::class;

    $destination = $migration->getDestinationPlugin();
    if (!$destination instanceof HighestIdInterface) {
      throw new AuditException($migration, "Destination does not implement $interface");
    }

    $id_map = $migration->getIdMap();
    if (!$id_map instanceof HighestIdInterface) {
      throw new AuditException($migration, "ID map does not implement $interface");
    }

    if ($destination->getHighestId() > $id_map->getHighestId()) {
      return AuditResult::fail($migration, [
        $this->t('The destination system contains data which was not created by a migration.'),
      ]);
    }
    return AuditResult::pass($migration);
  }

  /**
   * {@inheritdoc}
   */
  public function auditMultiple(array $migrations) {
    $conflicts = [];

    foreach ($migrations as $migration) {
      $migration_id = $migration->getPluginId();
      $conflicts[$migration_id] = $this->audit($migration);
    }
    ksort($conflicts);
    return $conflicts;
  }

}
