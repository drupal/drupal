<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field\d6;

// cspell:ignore userreference

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\ReferenceBase;

/**
 * MigrateField Plugin for Drupal 6 user reference fields.
 * @internal
 */
#[MigrateField(
  id: 'userreference',
  core: [6],
  type_map: [
    'userreference' => 'entity_reference',
  ],
  source_module: 'userreference',
  destination_module: 'core',
)]
class UserReference extends ReferenceBase {

  /**
   * The plugin ID for the reference type migration.
   *
   * @var string
   */
  protected $userTypeMigration = 'd6_user_role';

  /**
   * {@inheritdoc}
   */
  protected function getEntityTypeMigrationId() {
    return $this->userTypeMigration;
  }

  /**
   * {@inheritdoc}
   */
  protected function entityId() {
    return 'uid';
  }

  /**
   * {@inheritdoc}
   */
  public function defineValueProcessPipeline(MigrationInterface $migration, $field_name, $data) {
    $process = [
      'plugin' => 'sub_process',
      'source' => $field_name,
      'process' => [
        'target_id' => [
          'plugin' => 'migration_lookup',
          'migration' => 'd6_user',
          'source' => 'uid',
        ],
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);
  }

}
