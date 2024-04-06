<?php

namespace Drupal\migrate_drupal\Plugin\migrate\field\d7;

use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate_drupal\Attribute\MigrateField;
use Drupal\migrate_drupal\Plugin\migrate\field\ReferenceBase;

/**
 * MigrateField plugin for Drupal 7 user_reference fields.
 */
#[MigrateField(
  id: 'user_reference',
  core: [7],
  type_map: [
    'user_reference' => 'entity_reference',
  ],
  source_module: 'user_reference',
  destination_module: 'core',
)]
class UserReference extends ReferenceBase {

  /**
   * The plugin ID for the reference type migration.
   *
   * @var string
   */
  protected $userTypeMigration = 'd7_user_role';

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
  public function getFieldFormatterMap() {
    return [
      'user_reference_default' => 'entity_reference_label',
      'user_reference_plain' => 'entity_reference_label',
      'user_reference_uid' => 'entity_reference_entity_id',
      'user_reference_user' => 'entity_reference_entity_view',
      'user_reference_path' => 'entity_reference_label',
    ];
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
          'migration' => 'd7_user',
          'source' => 'uid',
        ],
      ],
    ];
    $migration->setProcessOfProperty($field_name, $process);

  }

}
