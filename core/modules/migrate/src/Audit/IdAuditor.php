<?php

namespace Drupal\migrate\Audit;

use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\migrate\Plugin\migrate\destination\EntityContentComplete;
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

    if ($destination->getHighestId() > $id_map->getHighestId() || ($destination instanceof EntityContentComplete && !$this->auditEntityComplete($migration))) {
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

  /**
   * Audits an EntityComplete migration.
   *
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration to audit.
   *
   * @return bool
   *   TRUE if the audit passes and FALSE if not.
   *
   * @todo Refactor in https://www.drupal.org/project/drupal/issues/3061676 or
   *   https://www.drupal.org/project/drupal/issues/3091004
   */
  private function auditEntityComplete(MigrationInterface $migration) {
    $map_table = $migration->getIdMap()->mapTableName();

    $database = \Drupal::database();
    if (!$database->schema()->tableExists($map_table)) {
      throw new \InvalidArgumentException();
    }

    $query = $database->select($map_table, 'map')
      ->fields('map', ['destid2'])
      ->range(0, 1)
      ->orderBy('destid2', 'DESC');
    $max = (int) $query->execute()->fetchField();

    // Make a migration based on node_complete but with an entity_revision
    // destination.
    $revision_migration = $migration->getPluginDefinition();
    $revision_migration['id'] = $migration->getPluginId() . '-revision';
    $revision_migration['destination']['plugin'] = 'entity_revision:node';
    $revision_migration = \Drupal::service('plugin.manager.migration')->createStubMigration($revision_migration);

    // Get the highest node revision ID.
    $destination = $revision_migration->getDestinationPlugin();
    $highest = $destination->getHighestId();

    return $max <= $highest;
  }

}
