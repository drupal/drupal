<?php

namespace Drupal\migrate\Plugin\migrate\destination;

use Drupal\Component\Utility\NestedArray;
use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\language\ConfigurableLanguageManager;
use Drupal\migrate\Plugin\MigrationInterface;
use Drupal\migrate\MigrateException;
use Drupal\migrate\Plugin\MigrateIdMapInterface;
use Drupal\migrate\Row;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Base destination class for importing configuration entities.
 *
 * Available configuration keys:
 * - translations: (optional) Boolean, if TRUE, the destination will be
 *   associated with the langcode provided by the source plugin. Defaults to
 *   FALSE.
 *
 * Examples:
 *
 * @code
 * source:
 *   plugin: d7_block_custom
 * process:
 *   id: bid
 *   info: info
 *   langcode: language
 *   body: body
 * destination:
 *   plugin: entity:block
 * @endcode
 *
 * This will save the migrated, processed row as a block config entity.
 *
 * @code
 * source:
 *   plugin: d6_profile_field_translation
 *   constants:
 *     entity_type: user
 *     bundle: user
 * process:
 *   langcode: language
 *   entity_type: 'constants/entity_type'
 *   bundle: 'constants/bundle'
 *   field_name: name
 *   ...
 *   property: property
 *   translation: translation
 * destination:
 *   plugin: entity:field_config
 *   translations: true
 * @endcode
 *
 * Because the translations configuration is set to "true", this will save the
 * migrated, processed row to a "field_config" entity associated with the
 * designated langcode. Note that the this makes the "translation" and
 * "property" properties required.
 */
class EntityConfigBase extends Entity {

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The configuration factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * Construct a new entity.
   *
   * @param array $configuration
   *   A configuration array containing information about the plugin instance.
   * @param string $plugin_id
   *   The plugin ID for the plugin instance.
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
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityStorageInterface $storage, array $bundles, LanguageManagerInterface $language_manager, ConfigFactoryInterface $config_factory) {
    parent::__construct($configuration, $plugin_id, $plugin_definition, $migration, $storage, $bundles);
    $this->languageManager = $language_manager;
    $this->configFactory = $config_factory;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    $entity_type_id = static::getEntityTypeId($plugin_id);
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')->getStorage($entity_type_id),
      array_keys($container->get('entity_type.bundle.info')->getBundleInfo($entity_type_id)),
      $container->get('language_manager'),
      $container->get('config.factory')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function import(Row $row, array $old_destination_id_values = []) {
    if ($row->isStub()) {
      throw new MigrateException('Config entities can not be stubbed.');
    }
    $this->rollbackAction = MigrateIdMapInterface::ROLLBACK_DELETE;
    $ids = $this->getIds();
    $id_key = $this->getKey('id');
    if (count($ids) > 1) {
      // Ids is keyed by the key name so grab the keys.
      $id_keys = array_keys($ids);
      if (!$row->getDestinationProperty($id_key)) {
        // Set the ID into the destination in for form "val1.val2.val3".
        $row->setDestinationProperty($id_key, $this->generateId($row, $id_keys));
      }
    }
    $entity = $this->getEntity($row, $old_destination_id_values);
    // Translations are already saved in updateEntity by configuration override.
    if (!$this->isTranslationDestination()) {
      $entity->save();
    }
    if (count($ids) > 1) {
      // This can only be a config entity, content entities have their ID key
      // and that's it.
      $return = [];
      foreach ($id_keys as $id_key) {
        if (($this->isTranslationDestination()) && ($id_key == 'langcode')) {
          // Config entities do not have a language property, get the language
          // code from the destination.
          $return[] = $row->getDestinationProperty($id_key);
        }
        else {
          $return[] = $entity->get($id_key);
        }
      }
      return $return;
    }
    return [$entity->id()];
  }

  /**
   * Get whether this destination is for translations.
   *
   * @return bool
   *   Whether this destination is for translations.
   */
  protected function isTranslationDestination() {
    return !empty($this->configuration['translations']);
  }

  /**
   * {@inheritdoc}
   */
  public function getIds() {
    $id_key = $this->getKey('id');
    $ids[$id_key]['type'] = 'string';
    if ($this->isTranslationDestination()) {
      $ids['langcode']['type'] = 'string';
    }
    return $ids;
  }

  /**
   * Updates an entity with the contents of a row.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The entity to update.
   * @param \Drupal\migrate\Row $row
   *   The row object to update from.
   *
   * @return \Drupal\Core\Entity\EntityInterface
   *   An updated entity from row values.
   *
   * @throws \LogicException
   *   Thrown if the destination is for translations and either the "property"
   *   or "translation" property does not exist.
   */
  protected function updateEntity(EntityInterface $entity, Row $row) {
    // This is a translation if the language in the active config does not
    // match the language of this row.
    $translation = FALSE;
    if ($this->isTranslationDestination() && $row->hasDestinationProperty('langcode') && $this->languageManager instanceof ConfigurableLanguageManager) {
      $config = $entity->getConfigDependencyName();
      $langcode = $this->configFactory->get('langcode');
      if ($langcode != $row->getDestinationProperty('langcode')) {
        $translation = TRUE;
      }
    }

    if ($translation) {
      if (!$row->hasDestinationProperty('property')) {
        throw new \LogicException('The "property" property is required');
      }
      if (!$row->hasDestinationProperty('translation')) {
        throw new \LogicException('The "translation" property is required');
      }
      $config_override = $this->languageManager->getLanguageConfigOverride($row->getDestinationProperty('langcode'), $config);
      $config_override->set(str_replace(Row::PROPERTY_SEPARATOR, '.', $row->getDestinationProperty('property')), $row->getDestinationProperty('translation'));
      $config_override->save();
    }
    else {
      foreach ($row->getRawDestination() as $property => $value) {
        $this->updateEntityProperty($entity, explode(Row::PROPERTY_SEPARATOR, $property), $value);
      }
      $this->setRollbackAction($row->getIdMap());
    }

    return $entity;
  }

  /**
   * Updates a (possible nested) entity property with a value.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The config entity.
   * @param array $parents
   *   The array of parents.
   * @param string|object $value
   *   The value to update to.
   */
  protected function updateEntityProperty(EntityInterface $entity, array $parents, $value) {
    $top_key = array_shift($parents);
    $entity_value = $entity->get($top_key);
    if (is_array($entity_value)) {
      NestedArray::setValue($entity_value, $parents, $value);
    }
    else {
      $entity_value = $value;
    }
    $entity->set($top_key, $entity_value);
  }

  /**
   * Generates an entity ID.
   *
   * @param \Drupal\migrate\Row $row
   *   The current row.
   * @param array $ids
   *   The destination IDs.
   *
   * @return string
   *   The generated entity ID.
   */
  protected function generateId(Row $row, array $ids) {
    $id_values = [];
    foreach ($ids as $id) {
      if ($this->isTranslationDestination() && $id == 'langcode') {
        continue;
      }
      $id_values[] = $row->getDestinationProperty($id);
    }
    return implode('.', $id_values);
  }

  /**
   * {@inheritdoc}
   */
  public function rollback(array $destination_identifier) {
    if ($this->isTranslationDestination()) {
      // The entity id does not include the langcode.
      $id_values = [];
      foreach ($destination_identifier as $key => $value) {
        if ($this->isTranslationDestination() && $key === 'langcode') {
          continue;
        }
        $id_values[] = $value;
      }
      $entity_id = implode('.', $id_values);
      $language = $destination_identifier['langcode'];

      $config = $this->storage->load($entity_id)->getConfigDependencyName();
      $config_override = $this->languageManager->getLanguageConfigOverride($language, $config);
      // Rollback the translation.
      $config_override->delete();
    }
    else {
      $destination_identifier = implode('.', $destination_identifier);
      parent::rollback([$destination_identifier]);
    }
  }

}
