<?php

namespace Drupal\migrate_drupal\Plugin\migrate;

use Drupal\Component\Plugin\Derivative\DeriverBase;
use Drupal\Component\Plugin\PluginBase;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Plugin\Discovery\ContainerDeriverInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Symfony\Component\DependencyInjection\ContainerInterface;

/**
 * Deriver for entity reference field translations.
 *
 * A migration will be created for every bundle with at least one entity
 * reference field that is configured to point to one of the supported target
 * entity types. The migrations will update the entity reference fields with
 * values found in the mapping tables of the migrations associated with the
 * target types.
 *
 * Example:
 *
 * @code
 * id: d7_entity_reference_translation
 * label: Entity reference translations
 * migration_tags:
 *   - Drupal 7
 *   - Follow-up migration
 * deriver: Drupal\migrate_drupal\Plugin\migrate\EntityReferenceTranslationDeriver
 * target_types:
 *   node:
 *     - d7_node_translation
 * source:
 *   plugin: empty
 *   key: default
 *   target: default
 * process: []
 * destination:
 *   plugin: null
 * @endcode
 *
 * In this example, the only supported target type is 'node' and the associated
 * migration for the mapping table lookup is 'd7_node_translation'.
 */
class EntityReferenceTranslationDeriver extends DeriverBase implements ContainerDeriverInterface {

  use StringTranslationTrait;

  /**
   * The entity field manager.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * EntityReferenceTranslationDeriver constructor.
   *
   * @param string $base_plugin_id
   *   The base plugin ID.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   */
  public function __construct($base_plugin_id, EntityFieldManagerInterface $entity_field_manager, EntityTypeManagerInterface $entity_type_manager) {
    $this->entityFieldManager = $entity_field_manager;
    $this->entityTypeManager = $entity_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public static function create(ContainerInterface $container, $base_plugin_id) {
    return new static(
      $base_plugin_id,
      $container->get('entity_field.manager'),
      $container->get('entity_type.manager')
    );
  }

  /**
   * {@inheritdoc}
   */
  public function getDerivativeDefinitions($base_plugin_definition) {
    // Get all entity reference fields.
    $field_map = $this->entityFieldManager->getFieldMapByFieldType('entity_reference');

    foreach ($field_map as $entity_type => $fields) {
      foreach ($fields as $field_name => $field) {
        foreach ($field['bundles'] as $bundle) {
          $field_definitions = $this->entityFieldManager->getFieldDefinitions($entity_type, $bundle);
          $target_type = $field_definitions[$field_name]->getSetting('target_type');

          // If the field's target type is not supported, skip it.
          if (!array_key_exists($target_type, $base_plugin_definition['target_types'])) {
            continue;
          }

          // Key derivatives by entity types and bundles.
          $derivative_key = $entity_type . '__' . $bundle;

          $derivative = $base_plugin_definition;
          $entity_type_definition = $this->entityTypeManager->getDefinition($entity_type);

          // Set the migration label.
          $derivative['label'] = $this->t('@label (@derivative)', [
            '@label' => $base_plugin_definition['label'],
            '@derivative' => $derivative_key,
          ]);

          // Set the source plugin.
          $derivative['source']['plugin'] = 'content_entity' . PluginBase::DERIVATIVE_SEPARATOR . $entity_type;
          if ($entity_type_definition->hasKey('bundle')) {
            $derivative['source']['bundle'] = $bundle;
          }

          // Set the process pipeline.
          $id_key = $entity_type_definition->getKey('id');
          $derivative['process'][$id_key] = $id_key;
          if ($entity_type_definition->isRevisionable()) {
            $revision_key = $entity_type_definition->getKey('revision');
            $derivative['process'][$revision_key] = $revision_key;
          }
          if ($entity_type_definition->isTranslatable()) {
            $langcode_key = $entity_type_definition->getKey('langcode');
            $derivative['process'][$langcode_key] = $langcode_key;
          }

          // Set the destination plugin.
          $derivative['destination']['plugin'] = 'entity' . PluginBase::DERIVATIVE_SEPARATOR . $entity_type;
          if ($entity_type_definition->hasKey('bundle')) {
            $derivative['destination']['default_bundle'] = $bundle;
          }
          if ($entity_type_definition->isTranslatable()) {
            $derivative['destination']['translations'] = TRUE;
          }

          // Allow overwriting the entity reference field so we can update its
          // values with the ones found in the mapping table.
          $derivative['destination']['overwrite_properties'][$field_name] = $field_name;

          // Add the entity reference field to the process pipeline.
          $derivative['process'][$field_name] = [
            'plugin' => 'sub_process',
            'source' => $field_name,
            'process' => [
              'translation_target_id' => [
                [
                  'plugin' => 'migration_lookup',
                  'source' => 'target_id',
                  'migration' => $base_plugin_definition['target_types'][$target_type],
                  'no_stub' => TRUE,
                ],
                [
                  'plugin' => 'skip_on_empty',
                  'method' => 'process',
                ],
                [
                  'plugin' => 'extract',
                  'index' => [0],
                ],
              ],
              'target_id' => [
                [
                  'plugin' => 'null_coalesce',
                  'source' => [
                    '@translation_target_id',
                    'target_id',
                  ],
                ],
              ],
            ],
          ];

          if (!isset($this->derivatives[$derivative_key])) {
            // If this is a new derivative, add it to the returned derivatives.
            $this->derivatives[$derivative_key] = $derivative;
          }
          else {
            // If this is an existing derivative, it means this bundle has more
            // than one entity reference field. In that case, we only want to add
            // the field to the process pipeline and make it overwritable.
            $this->derivatives[$derivative_key]['process'] += $derivative['process'];
            $this->derivatives[$derivative_key]['destination']['overwrite_properties'] += $derivative['destination']['overwrite_properties'];
          }
        }
      }
    }

    return $this->derivatives;
  }

}
