<?php

namespace Drupal\migrate\Plugin\migrate\process;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\ContainerFactoryPluginInterface;
use Drupal\migrate\Attribute\MigrateProcess;
use Drupal\migrate\Plugin\MigrationInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Ensures the source value is made unique against an entity field.
 *
 * The make_unique process plugin is typically used to make the entity id
 * unique, ensuring that migrated entity data is preserved.
 *
 * The make_unique process plugin has two required configuration keys,
 * entity_type and field. It's typically used with an entity destination, making
 * sure that after saving the entity, the field value is unique. For example,
 * if the value is foo and there is already an entity where the field value is
 * foo, then the plugin will return foo1.
 *
 * The optional configuration key postfix which will be added between the number
 * and the original value, for example, foo_1 for postfix: _. Note that the
 * value of postfix is ignored if the value is not changed, if it was already
 * unique.
 *
 * The optional configuration key migrated, if true, indicates that an entity
 * will only be considered a duplicate if it was migrated by the current
 * migration.
 *
 * Available configuration keys
 *   - entity_type: The entity type.
 *   - field: The entity field for the given value.
 *   - migrated: (optional) A boolean to indicate that making the field unique
 *     only occurs for migrated entities.
 *   - start: (optional) The position at which to start reading.
 *   - length: (optional) The number of characters to read.
 *   - postfix: (optional) A string to insert before the numeric postfix.
 *
 * Examples:
 *
 * @code
 * process:
 *   format:
 *   -
 *     plugin: machine_name
 *     source: name
 *   -
 *     plugin: make_unique_entity_field
 *     entity_type: filter_format
 *     field: format
 *
 * @endcode
 *
 * This will create a format machine name out the human readable name and make
 * sure it's unique.
 *
 * @code
 * process:
 *   format:
 *   -
 *     plugin: machine_name
 *     source: name
 *   -
 *     plugin: make_unique_entity_field
 *     entity_type: filter_format
 *     field: format
 *     postfix: _
 *     migrated: true
 *
 * @endcode
 *
 * This will create a format machine name out the human readable name and make
 * sure it's unique if the entity was migrated. The postfix character is
 * inserted between the added number and the original value.
 *
 * @see \Drupal\migrate\Plugin\migrate\process\MakeUniqueBase
 * @see \Drupal\migrate\Plugin\MigrateProcessInterface
 */
#[MigrateProcess('make_unique_entity_field')]
class MakeUniqueEntityField extends MakeUniqueBase implements ContainerFactoryPluginInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The current migration.
   *
   * @var \Drupal\migrate\Plugin\MigrationInterface
   */
  protected $migration;

  /**
   * {@inheritdoc}
   */
  public function __construct(array $configuration, $plugin_id, $plugin_definition, MigrationInterface $migration, EntityTypeManagerInterface $entity_type_manager) {
    parent::__construct($configuration, $plugin_id, $plugin_definition);
    $this->migration = $migration;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, array $configuration, $plugin_id, $plugin_definition, ?MigrationInterface $migration = NULL) {
    return new static(
      $configuration,
      $plugin_id,
      $plugin_definition,
      $migration,
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  protected function exists($value) {
    // Plugins are cached so for every run we need a new query object.
    $query = $this
      ->entityTypeManager
      ->getStorage($this->configuration['entity_type'])
      ->getQuery()
      ->accessCheck(FALSE)
      ->condition($this->configuration['field'], $value);
    if (!empty($this->configuration['migrated'])) {
      // Check if each entity is in the ID map.
      $idMap = $this->migration->getIdMap();
      foreach ($query->execute() as $id) {
        $dest_id_values[$this->configuration['field']] = $id;
        if ($idMap->lookupSourceId($dest_id_values)) {
          return TRUE;
        }
      }
      return FALSE;
    }
    else {
      // Just check if any such entity exists.
      return $query->count()->execute();
    }
  }

}
