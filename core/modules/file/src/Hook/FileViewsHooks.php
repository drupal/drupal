<?php

declare(strict_types=1);

namespace Drupal\file\Hook;

use Drupal\Core\Entity\EntityFieldManagerInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\views\FieldViewsDataProvider;

/**
 * Hook implementations for file.
 */
class FileViewsHooks {

  use StringTranslationTrait;

  public function __construct(
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    protected readonly EntityFieldManagerInterface $entityFieldManager,
    protected readonly ?FieldViewsDataProvider $fieldViewsDataProvider,
  ) {}

  /**
   * Implements hook_field_views_data().
   *
   * Views integration for file fields. Adds a file relationship to the default
   * field data.
   *
   * @see FieldViewsDataProvider::defaultFieldImplementation()
   */
  #[Hook('field_views_data')]
  public function fieldViewsData(FieldStorageConfigInterface $field_storage): array {
    $data = $this->fieldViewsDataProvider->defaultFieldImplementation($field_storage);
    foreach ($data as $table_name => $table_data) {
      // Add the relationship only on the fid field.
      $data[$table_name][$field_storage->getName() . '_target_id']['relationship'] = [
        'id' => 'standard',
        'base' => 'file_managed',
        'entity type' => 'file',
        'base field' => 'fid',
        'label' => $this->t('file from @field_name', [
          '@field_name' => $field_storage->getName(),
        ]),
      ];
    }
    return $data;
  }

  /**
   * Implements hook_field_views_data_views_data_alter().
   *
   * Views integration to provide reverse relationships on file fields.
   */
  #[Hook('field_views_data_views_data_alter')]
  public function fieldViewsDataViewsDataAlter(array &$data, FieldStorageConfigInterface $field_storage): void {
    $entity_type_id = $field_storage->getTargetEntityTypeId();
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);
    $field_name = $field_storage->getName();
    $pseudo_field_name = 'reverse_' . $field_name . '_' . $entity_type_id;
    /** @var \Drupal\Core\Entity\Sql\DefaultTableMapping $table_mapping */
    $table_mapping = $this->entityTypeManager->getStorage($entity_type_id)->getTableMapping();
    [$label] = $this->entityFieldManager->getFieldLabels($entity_type_id, $field_name);
    $data['file_managed'][$pseudo_field_name]['relationship'] = [
      'title' => $this->t('@entity using @field', [
        '@entity' => $entity_type->getLabel(),
        '@field' => $label,
      ]),
      'label' => $this->t('@field_name', [
        '@field_name' => $field_name,
      ]),
      'group' => $entity_type->getLabel(),
      'help' => $this->t('Relate each @entity with a @field set to the file.', [
        '@entity' => $entity_type->getLabel(),
        '@field' => $label,
      ]),
      'id' => 'entity_reverse',
      'base' => $entity_type->getDataTable() ?: $entity_type->getBaseTable(),
      'entity_type' => $entity_type_id,
      'base field' => $entity_type->getKey('id'),
      'field_name' => $field_name,
      'field table' => $table_mapping->getDedicatedDataTableName($field_storage),
      'field field' => $field_name . '_target_id',
      'join_extra' => [
        0 => [
          'field' => 'deleted',
          'value' => 0,
          'numeric' => TRUE,
        ],
      ],
    ];
  }

}
