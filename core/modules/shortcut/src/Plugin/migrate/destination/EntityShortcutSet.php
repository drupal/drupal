<?php

namespace Drupal\shortcut\Plugin\migrate\destination;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\migrate\Attribute\MigrateDestination;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;

/**
 * Migration destination for shortcut set entity.
 *
 * @deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no
 *   replacement.
 *
 * @see https://www.drupal.org/node/3533565
 */
#[MigrateDestination('entity:shortcut_set')]
class EntityShortcutSet extends EntityConfigBase {

  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    @trigger_error(__CLASS__ . '() is deprecated in drupal:11.3.0 and is removed from drupal:12.0.0. There is no replacement. See https://www.drupal.org/node/3533565', E_USER_DEPRECATED);
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $language_manager, $config_factory);
  }

  /**
   * {@inheritdoc}
   */
  protected function getEntity(Row $row, array $old_destination_id_values) {
    $entity = parent::getEntity($row, $old_destination_id_values);
    // Set the "syncing" flag to TRUE, to avoid duplication of default
    // shortcut links.
    $entity->setSyncing(TRUE);
    return $entity;
  }

}
