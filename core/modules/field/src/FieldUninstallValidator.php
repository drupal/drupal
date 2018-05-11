<?php

namespace Drupal\field;

use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Prevents uninstallation of modules providing active field storage.
 */
class FieldUninstallValidator implements ModuleUninstallValidatorInterface {

  use StringTranslationTrait;

  /**
   * The field storage config storage.
   *
   * @var \Drupal\Core\Config\Entity\ConfigEntityStorageInterface
   */
  protected $fieldStorageConfigStorage;

  /**
   * The field type plugin manager.
   *
   * @var \Drupal\Core\Field\FieldTypePluginManagerInterface
   */
  protected $fieldTypeManager;

  /**
   * Constructs a new FieldUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, TranslationInterface $string_translation, FieldTypePluginManagerInterface $field_type_manager) {
    $this->fieldStorageConfigStorage = $entity_type_manager->getStorage('field_storage_config');
    $this->stringTranslation = $string_translation;
    $this->fieldTypeManager = $field_type_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($field_storages = $this->getFieldStoragesByModule($module)) {
      // Provide an explanation message (only mention pending deletions if there
      // remain no actual, non-deleted fields.)
      $fields_in_use = [];
      foreach ($field_storages as $field_storage) {
        if (!$field_storage->isDeleted()) {
          $fields_in_use[$field_storage->getType()][] = $field_storage->getLabel();
        }
      }
      if (!empty($fields_in_use)) {
        foreach ($fields_in_use as $field_type => $field_storages) {
          $field_type_label = $this->getFieldTypeLabel($field_type);
          $reasons[] = $this->formatPlural(count($fields_in_use[$field_type]), 'The %field_type_label field type is used in the following field: @fields', 'The %field_type_label field type is used in the following fields: @fields', ['%field_type_label' => $field_type_label, '@fields' => implode(', ', $field_storages)]);
        }
      }
      else {
        $reasons[] = $this->t('Fields pending deletion');
      }
    }
    return $reasons;
  }

  /**
   * Returns all field storages for a specified module.
   *
   * @param string $module
   *   The module to filter field storages by.
   *
   * @return \Drupal\field\FieldStorageConfigInterface[]
   *   An array of field storages for a specified module.
   */
  protected function getFieldStoragesByModule($module) {
    return $this->fieldStorageConfigStorage->loadByProperties(['module' => $module, 'include_deleted' => TRUE]);
  }

  /**
   * Returns the label for a specified field type.
   *
   * @param string $field_type
   *   The field type.
   *
   * @return string
   *   The field type label.
   */
  protected function getFieldTypeLabel($field_type) {
    return $this->fieldTypeManager->getDefinitions()[$field_type]['label'];
  }

}
