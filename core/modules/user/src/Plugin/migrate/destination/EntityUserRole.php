<?php

namespace Drupal\user\Plugin\migrate\destination;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\migrate\Plugin\migrate\destination\EntityConfigBase;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Provides a destination plugin for migrating user role entities.
 *
 * @MigrateDestination(
 *   id = "entity:user_role"
 * )
 */
class EntityUserRole extends EntityConfigBase {

  /**
   * All permissions on the destination site.
   *
   * @var string[]
   */
  protected $destinationPermissions = [];

  /**
   * Builds a user role entity destination.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin_id for the plugin instance.
   * @param mixed $plugin_definition
   *   The plugin implementation definition.
   * @param \Drupal\migrate\Plugin\MigrationInterface $migration
   *   The migration.
   * @param \Drupal\Core\Entity\EntityStorageInterface $storage
   *   The storage for this entity type.
   * @param array $bundles
   *   The list of bundles this entity type has.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The configuration factory.
   * @param array $destination_permissions
   *   All available permissions.
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory, array $destination_permissions) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles, $language_manager, $config_factory);
    $this->destinationPermissions = $destination_permissions;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration = NULL) {
    $entity_type_id = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')->getStorage($entity_type_id),
      array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type_id)),
      $container->get('language_manager'),
      $container->get('config.factory'),
      array_keys($container->get('user.permissions')->getPermissions()),
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []): array|bool {
    $permissions = $row->getDestinationProperty('permissions') ?? [];

    // Get permissions that do not exist on the destination.
    $invalid_permissions = array_diff($permissions, $this->destinationPermissions);
    if ($invalid_permissions) {
      sort($invalid_permissions);
      // Log the message in the migration message table.
      $message = "Permission(s) '" . implode("', '", $invalid_permissions) . "' not found.";
      $this->migration->getIdMap()
        ->saveMessage($row->getSourceIdValues(), $message, MigrationInterface::MESSAGE_WARNING);
    }

    $valid_permissions = array_intersect($permissions, $this->destinationPermissions);
    $row->setDestinationProperty('permissions', $valid_permissions);
    return parent::import($row, $old_destination_id_values);
  }

}
