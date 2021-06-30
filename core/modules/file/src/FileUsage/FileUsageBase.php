<?php

namespace Drupal\file\FileUsage;

use Drupal\Core\Config\ConfigFactoryInterface;
use Drupal\file\FileInterface;
use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityStorageInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Field\FieldDefinitionInterface;

/**
 * Defines the base class for database file usage backend.
 */
abstract class FileUsageBase implements FileUsageInterface {

  /**
   * The config factory.
   *
   * @var \Drupal\Core\Config\ConfigFactoryInterface
   */
  protected $configFactory;

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The entity field manager service.
   *
   * @var \Drupal\Core\Entity\EntityFieldManagerInterface
   */
  protected $entityFieldManager;

  /**
   * Memory cached results of ::getReferences().
   *
   * @var array
   */
  protected $references = [];

  /**
   * Memory cached results of ::findReferenceColumn().
   *
   * @var mixed[]
   */
  protected $fieldColumns = [];

  /**
   * Creates a FileUsageBase object.
   *
   * @param \Drupal\Core\Config\ConfigFactoryInterface $config_factory
   *   The config factory service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Entity\EntityFieldManagerInterface $entity_field_manager
   *   The entity field manager service.
   */
  public function __construct(ConfigFactoryInterface $config_factory, EntityTypeManagerInterface $entity_type_manager = NULL, EntityFieldManagerInterface $entity_field_manager = NULL) {
    $this->configFactory = $config_factory;

    if (!$entity_type_manager) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entity_type_manager argument is deprecated in drupal:9.3.0 and the $entity_type_manager argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3035357.', E_USER_DEPRECATED);
      $entity_type_manager = \Drupal::entityTypeManager();
    }
    $this->entityTypeManager = $entity_type_manager;

    if (!$entity_field_manager) {
      @trigger_error('Calling ' . __METHOD__ . '() without the $entity_field_manager argument is deprecated in drupal:9.3.0 and the $entity_field_manager argument will be required in drupal:10.0.0. See https://www.drupal.org/node/3035357.', E_USER_DEPRECATED);
      $this->entityFieldManager = \Drupal::service('entity_field.manager');
    }
    $this->entityFieldManager = $entity_field_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function add(FileInterface $file, $module, $type, $id, $count = 1) {
    // Make sure that a used file is permanent.
    if (!$file->isPermanent()) {
      $file->setPermanent();
      $file->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function delete(FileInterface $file, $module, $type = NULL, $id = NULL, $count = 1) {
    // Do not actually mark files as temporary when the behavior is disabled.
    if (!$this->configFactory->get('file.settings')->get('make_unused_managed_files_temporary')) {
      return;
    }
    // If there are no more remaining usages of this file, mark it as temporary,
    // which result in a delete through system_cron().
    $usage = \Drupal::service('file.usage')->listUsage($file);
    if (empty($usage)) {
      $file->setTemporary();
      $file->save();
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getReferences(FileInterface $file, FieldDefinitionInterface $field = NULL, string $age = EntityStorageInterface::FIELD_LOAD_REVISION, string $field_type = 'file'): array {
    // Fill the static cache, disregard $field and $field_type for now.
    if (!isset($this->references[$file->id()][$age])) {
      $this->references[$file->id()][$age] = [];
      $usage_list = $this->listUsage($file);
      $file_usage_list = isset($usage_list['file']) ? $usage_list['file'] : [];
      foreach ($file_usage_list as $entity_type_id => $entity_ids) {
        $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadMultiple(array_keys($entity_ids));
        /** @var \Drupal\Core\Entity\FieldableEntityInterface $entity */
        foreach ($entities as $entity) {
          $bundle = $entity->bundle();
          // We need to find file fields for this entity type and bundle.
          if (!isset($file_fields[$entity_type_id][$bundle])) {
            $file_fields[$entity_type_id][$bundle] = [];
            // This contains the possible field names.
            foreach ($entity->getFieldDefinitions() as $field_name => $field_definition) {
              // If this is the first time this field type is seen, check
              // whether it references files.
              if (!isset($this->fieldColumns[$field_definition->getType()])) {
                $this->fieldColumns[$field_definition->getType()] = $this->findReferenceColumn($field_definition);
              }
              // If the field type does reference files then record it.
              if ($this->fieldColumns[$field_definition->getType()]) {
                $file_fields[$entity_type_id][$bundle][$field_name] = $this->fieldColumns[$field_definition->getType()];
              }
            }
          }
          foreach ($file_fields[$entity_type_id][$bundle] as $field_name => $field_column) {
            // Iterate over the field items to find the referenced file and
            // field name. This will fail if the usage checked is in a
            // non-current revision because field items are from the current
            // revision. We also iterate over all translations because a file
            // can be linked to a language other than the default.
            foreach ($entity->getTranslationLanguages() as $langcode => $language) {
              foreach ($entity->getTranslation($langcode)->get($field_name) as $item) {
                if ($file->id() == $item->{$field_column}) {
                  $this->references[$file->id()][$age][$field_name][$entity_type_id][$entity->id()] = $entity;
                  break;
                }
              }
            }
          }
        }
      }
    }
    $return = $this->references[$file->id()][$age];
    // Filter the static cache down to the requested entries. The usual static
    // cache is very small so this will be very fast.
    if ($field || $field_type) {
      foreach ($return as $field_name => $data) {
        foreach (array_keys($data) as $entity_type_id) {
          $field_storage_definitions = $this->entityFieldManager->getFieldStorageDefinitions($entity_type_id);
          $current_field = $field_storage_definitions[$field_name];
          if (($field_type && $current_field->getType() != $field_type) || ($field && $field->uuid() != $current_field->uuid())) {
            unset($return[$field_name][$entity_type_id]);
          }
        }
      }
    }
    return $return;
  }

  /**
   * Determines whether a field references files stored in {file_managed}.
   *
   * @param \Drupal\Core\Field\FieldDefinitionInterface $field
   *   A field definition.
   *
   * @return string|bool
   *   The field column if the field references {file_managed}.fid, typically
   *   fid, FALSE if it does not.
   */
  protected function findReferenceColumn(FieldDefinitionInterface $field) {
    $schema = $field->getFieldStorageDefinition()->getSchema();
    foreach ($schema['foreign keys'] as $data) {
      if ($data['table'] == 'file_managed') {
        foreach ($data['columns'] as $field_column => $column) {
          if ($column == 'fid') {
            return $field_column;
          }
        }
      }
    }
    return FALSE;
  }

  /**
   * Resets the internal memory cache.
   */
  public function resetCache() {
    $this->references = [];
    $this->fieldColumns = [];
  }

}
