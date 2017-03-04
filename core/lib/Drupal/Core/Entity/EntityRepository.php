<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\TypedData\TranslatableInterface;

/**
 * Provides several mechanisms for retrieving entities.
 */
class EntityRepository implements EntityRepositoryInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a new EntityRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityByUuid($entity_type_id, $uuid) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    if (!$uuid_key = $entity_type->getKey('uuid')) {
      throw new EntityStorageException("Entity type $entity_type_id does not support UUIDs.");
    }

    $entities = $this->entityTypeManager->getStorage($entity_type_id)->loadByProperties([$uuid_key => $uuid]);

    return ($entities) ? reset($entities) : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function loadEntityByConfigTarget($entity_type_id, $target) {
    $entity_type = $this->entityTypeManager->getDefinition($entity_type_id);

    // For configuration entities, the config target is given by the entity ID.
    // @todo Consider adding a method to allow entity types to indicate the
    //   target identifier key rather than hard-coding this check. Issue:
    //   https://www.drupal.org/node/2412983.
    if ($entity_type instanceof ConfigEntityTypeInterface) {
      $entity = $this->entityTypeManager->getStorage($entity_type_id)->load($target);
    }

    // For content entities, the config target is given by the UUID.
    else {
      $entity = $this->loadEntityByUuid($entity_type_id, $target);
    }

    return $entity;
  }

  /**
   * {@inheritdoc}
   */
  public function getTranslationFromContext(EntityInterface $entity, $langcode = NULL, $context = []) {
    $translation = $entity;

    if ($entity instanceof TranslatableInterface && count($entity->getTranslationLanguages()) > 1) {
      if (empty($langcode)) {
        $langcode = $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT)->getId();
        $entity->addCacheContexts(['languages:' . LanguageInterface::TYPE_CONTENT]);
      }

      // Retrieve language fallback candidates to perform the entity language
      // negotiation, unless the current translation is already the desired one.
      if ($entity->language()->getId() != $langcode) {
        $context['data'] = $entity;
        $context += ['operation' => 'entity_view', 'langcode' => $langcode];
        $candidates = $this->languageManager->getFallbackCandidates($context);

        // Ensure the default language has the proper language code.
        $default_language = $entity->getUntranslated()->language();
        $candidates[$default_language->getId()] = LanguageInterface::LANGCODE_DEFAULT;

        // Return the most fitting entity translation.
        foreach ($candidates as $candidate) {
          if ($entity->hasTranslation($candidate)) {
            $translation = $entity->getTranslation($candidate);
            break;
          }
        }
      }
    }

    return $translation;
  }

}
