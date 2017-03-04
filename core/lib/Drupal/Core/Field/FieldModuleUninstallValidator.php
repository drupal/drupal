<?php

namespace Drupal\Core\Field;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\FieldableEntityInterface;
use Drupal\Core\Entity\FieldableEntityStorageInterface;
use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;

/**
 * Validates module uninstall readiness based on defined storage definitions.
 *
 * @todo Remove this once we support field purging for base fields. See
 *   https://www.drupal.org/node/2282119.
 */
class FieldModuleUninstallValidator implements ModuleUninstallValidatorInterface {
  use StringTranslationTrait;

  /**
   * Constructs the object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\StringTranslation\TranslationInterface $string_translation
   *   The string translation service.
   */
  public function __construct(EntityManagerInterface $entity_manager, TranslationInterface $string_translation) {
    $this->entityManager = $entity_manager;
    $this->stringTranslation = $string_translation;
  }

  /**
   * {@inheritdoc}
   */
  public function validate($module_name) {
    $reasons = [];

    // We skip fields provided by the Field module as it implements field
    // purging.
    if ($module_name != 'field') {
      foreach ($this->entityManager->getDefinitions() as $entity_type_id => $entity_type) {
        // We skip entity types defined by the module as there must be no
        // content to be able to uninstall them anyway.
        // See \Drupal\Core\Entity\ContentUninstallValidator.
        if ($entity_type->getProvider() != $module_name && $entity_type->entityClassImplements(FieldableEntityInterface::class)) {
          foreach ($this->entityManager->getFieldStorageDefinitions($entity_type_id) as $storage_definition) {
            if ($storage_definition->getProvider() == $module_name) {
              $storage = $this->entityManager->getStorage($entity_type_id);
              if ($storage instanceof FieldableEntityStorageInterface && $storage->countFieldData($storage_definition, TRUE)) {
                $reasons[] = $this->t('There is data for the field @field-name on entity type @entity_type', [
                  '@field-name' => $storage_definition->getName(),
                  '@entity_type' => $entity_type->getLabel(),
                ]);
              }
            }
          }
        }
      }
    }

    return $reasons;
  }

}
