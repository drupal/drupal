<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Extension\ModuleUninstallValidatorInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\Core\StringTranslation\TranslationInterface;
use Drupal\Core\Url;

/**
 * Validates module uninstall readiness based on existing content entities.
 */
class ContentUninstallValidator implements ModuleUninstallValidatorInterface {
  use StringTranslationTrait;

  /**
   * The entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * Constructs a new ContentUninstallValidator.
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
  public function validate($module) {
    $entity_types = $this->entityManager->getDefinitions();
    $reasons = [];
    foreach ($entity_types as $entity_type) {
      if ($module == $entity_type->getProvider() && $entity_type instanceof ContentEntityTypeInterface && $this->entityManager->getStorage($entity_type->id())->hasData()) {
        $reasons[] = $this->t('There is content for the entity type: @entity_type. <a href=":url">Remove @entity_type_plural</a>.', [
          '@entity_type' => $entity_type->getLabel(),
          '@entity_type_plural' => $entity_type->getPluralLabel(),
          ':url' => Url::fromRoute('system.prepare_modules_entity_uninstall', ['entity_type_id' => $entity_type->id()])->toString(),
        ]);
      }
    }
    return $reasons;
  }

}
