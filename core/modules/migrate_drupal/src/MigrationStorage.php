<?php

/**
 * @file
 * Contains \Drupal\migrate_drupal\MigrateStorage.
 */

namespace Drupal\migrate_drupal;

use Drupal\Component\Utility\SafeMarkup;
use Drupal\Component\Uuid\UuidInterface;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageException;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\migrate\Plugin\migrate\source\SourcePluginBase;
use Drupal\migrate_drupal\Plugin\CckFieldMigrateSourceInterface;
use Drupal\migrate\MigrationStorage as BaseMigrationStorage;
use Drupal\migrate_drupal\Plugin\MigratePluginManager;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Storage for migration entities.
 */
class MigrationStorage extends BaseMigrationStorage {

  /**
   * A cached array of cck field plugins.
   *
   * @var array
   */
  protected $cckFieldPlugins;

  /**
   * @var \Drupal\migrate_drupal\Plugin\MigratePluginManager
   */
  protected $cckPluginManager;

  /**
   * Constructs a MigrationStorage object.
   *
   * @param \Drupal\Core\Entity\EntityTypeInterface $entity_type
   *   The entity type definition.
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Component\Uuid\UuidInterface $uuid_service
   *   The UUID service.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\migrate_drupal\Plugin\MigratePluginManager
   *  The cckfield plugin manager.
   */
  public function __construct(EntityTypeInterface $entity_type, ConfigFactoryInterface $config_factory, UuidInterface $uuid_service, LanguageManagerInterface $language_manager, MigratePluginManager $cck_plugin_manager) {
    parent::__construct($entity_type, $config_factory, $uuid_service, $language_manager);
    $this->cckPluginManager = $cck_plugin_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function createInstance(ContainerInterface $container, EntityTypeInterface $entity_type) {
    return new static(
      $entity_type,
      $container->get('config.factory'),
      $container->get('uuid'),
      $container->get('language_manager'),
      $container->get('plugin.manager.migrate.cckfield')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids = NULL) {
    $ids_to_load = array();
    $dynamic_ids = array();
    if (isset($ids)) {
      foreach ($ids as $id) {
        // Evaluate whether or not this migration is dynamic in the form of
        // migration_id:* to load all the additional migrations.
        if (($n = strpos($id, ':')) !== FALSE) {
          $base_id = substr($id, 0, $n);
          $ids_to_load[] = $base_id;
          // Get the ids of the additional migrations.
          $sub_id = substr($id, $n + 1);
          if ($sub_id == '*') {
            // If the id of the additional migration is '*', get all of them.
            $dynamic_ids[$base_id] = NULL;
          }
          elseif (!isset($dynamic_ids[$base_id]) || is_array($dynamic_ids[$base_id])) {
            $dynamic_ids[$base_id][] = $sub_id;
          }
        }
        else {
          $ids_to_load[] = $id;
        }
      }
      $ids = array_flip($ids);
    }
    else {
      $ids_to_load = NULL;
    }

    /** @var \Drupal\migrate_drupal\Entity\MigrationInterface[] $entities */
    $entities = parent::loadMultiple($ids_to_load);
    if (!isset($ids)) {
      // Changing the array being foreach()'d is not a good idea.
      $return = array();
      foreach ($entities as $entity_id => $entity) {
        if ($plugin = $entity->getLoadPlugin()) {
          $new_entities = $plugin->loadMultiple($this);
          $this->postLoad($new_entities);
          $this->getDynamicIds($dynamic_ids, $new_entities);
          $return += $new_entities;
        }
        else {
          $return[$entity_id] = $entity;
        }
      }
      $entities = $return;
    }
    else {
      foreach ($dynamic_ids as $base_id => $sub_ids) {
        $entity = $entities[$base_id];
        if ($plugin = $entity->getLoadPlugin()) {
          unset($entities[$base_id]);
          $new_entities = $plugin->loadMultiple($this, $sub_ids);
          $this->postLoad($new_entities);
          if (!isset($sub_ids)) {
            unset($dynamic_ids[$base_id]);
            $this->getDynamicIds($dynamic_ids, $new_entities);
          }
          $entities += $new_entities;
        }
      }
    }

    // Allow modules providing cck field plugins to alter the required
    // migrations to assist with the migration a custom field type.
    $this->applyCckFieldProcessors($entities);

    // Build an array of dependencies and set the order of the migrations.
    return $this->buildDependencyMigration($entities, $dynamic_ids);
  }

  /**
   * Extract the dynamic id mapping from entities loaded by plugin.
   *
   * @param array $dynamic_ids
   *   Get the dynamic migration ids.
   * @param array $entities
   *   An array of entities.
   */
  protected function getDynamicIds(array &$dynamic_ids, array $entities) {
    foreach (array_keys($entities) as $new_id) {
      list($base_id, $sub_id) = explode(':', $new_id, 2);
      $dynamic_ids[$base_id][] = $sub_id;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(EntityInterface $entity) {
    if (strpos($entity->id(), ':') !== FALSE) {
      throw new EntityStorageException(SafeMarkup::format("Dynamic migration %id can't be saved", array('$%id' => $entity->id())));
    }
    return parent::save($entity);
  }

  /**
   * Allow any field type plugins to adjust the migrations as required.
   *
   * @param \Drupal\migrate\Entity\Migration[] $entities
   *   An array of migration entities.
   */
  protected function applyCckFieldProcessors(array $entities) {
    $method_map = $this->getMigrationPluginMethodMap();

    foreach ($entities as $entity_id => $migration) {
      // Allow field plugins to process the required migrations.
      if (isset($method_map[$entity_id])) {
        $method = $method_map[$entity_id];
        $cck_plugins = $this->getCckFieldPlugins();

        array_walk($cck_plugins, function ($plugin) use ($method, $migration) {
          $plugin->$method($migration);
        });
      }

      // If this is a CCK bundle migration, allow the cck field plugins to add
      // any field type processing.
      $source_plugin = $migration->getSourcePlugin();
      if ($source_plugin instanceof CckFieldMigrateSourceInterface && strpos($entity_id, SourcePluginBase::DERIVATIVE_SEPARATOR)) {
        $plugins = $this->getCckFieldPlugins();
        foreach ($source_plugin->fieldData() as $field_name => $data) {
          if (isset($plugins[$data['type']])) {
            $plugins[$data['type']]->processCckFieldValues($migration, $field_name, $data);
          }
        }
      }
    }
  }

  /**
   * Get an array of loaded cck field plugins.
   *
   * @return \Drupal\migrate_drupal\Plugin\MigrateCckFieldInterface[]
   *   An array of cck field process plugins.
   */
  protected function getCckFieldPlugins() {
    if (!isset($this->cckFieldPlugins)) {
      $this->cckFieldPlugins = [];
      foreach ($this->cckPluginManager->getDefinitions() as $definition) {
        $this->cckFieldPlugins[$definition['id']] = $this->cckPluginManager->createInstance($definition['id']);
      }
    }
    return $this->cckFieldPlugins;
  }

  /**
   * Provides a map between migration ids and the cck field plugin method.
   *
   * @return array
   *   The map between migrations and cck field plugin processing methods.
   */
  protected function getMigrationPluginMethodMap() {
    return [
      'd6_field' => 'processField',
      'd6_field_instance' => 'processFieldInstance',
      'd6_field_instance_widget_settings' => 'processFieldWidget',
      'd6_field_formatter_settings' => 'processFieldFormatter',
    ];
  }

}
