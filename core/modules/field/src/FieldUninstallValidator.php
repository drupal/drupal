<?php

/**
 * @file
 * Contains \Drupal\field\FieldUninstallValidator.
 */

namespace Drupal\field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
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
   * Constructs a new FieldUninstallValidator.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->fieldStorageConfigStorage = $entity_manager->getStorage('field_storage_config');
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module) {
    $reasons = [];
    if ($field_storages = $this->getFieldStoragesByModule($module)) {
      // Provide an explanation message (only mention pending deletions if there
      // remain no actual, non-deleted fields.)
      $non_deleted = FALSE;
      foreach ($field_storages as $field_storage) {
        if (!$field_storage->isDeleted()) {
          $non_deleted = TRUE;
          break;
        }
      }
      if ($non_deleted) {
        $reasons[] = $this->t('Fields type(s) in use');
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

}
