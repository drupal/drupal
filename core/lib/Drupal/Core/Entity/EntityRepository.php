<?php

namespace Drupal\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityTypeInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Plugin\Context\ContextRepositoryInterface;
use Drupal\Core\TypedData\TranslatableInterface as TranslatableDataInterface;

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
   * The context repository service.
   *
   * @var \Drupal\Core\Plugin\Context\ContextRepositoryInterface
   */
  protected $contextRepository;

  /**
   * Constructs a new EntityRepository.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Drupal\Core\Plugin\Context\ContextRepositoryInterface $context_repository
   *   The context repository service.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, LanguageManagerInterface $language_manager, ContextRepositoryInterface $context_repository) {
    $this->entityTypeManager = $entity_type_manager;
    $this->languageManager = $language_manager;
    $this->contextRepository = $context_repository;
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

    if ($entity instanceof TranslatableDataInterface && count($entity->getTranslationLanguages()) > 1) {
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

  /**
   * {@inheritdoc}
   */
  public function getActive($entity_type_id, $entity_id, array $contexts = NULL) {
    return current($this->getActiveMultiple($entity_type_id, [$entity_id], $contexts)) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveMultiple($entity_type_id, array $entity_ids, array $contexts = NULL) {
    $active = [];

    if (!isset($contexts)) {
      $contexts = $this->contextRepository->getAvailableContexts();
    }

    // @todo Consider implementing a more performant version of this logic fully
    //   supporting multiple entities in https://www.drupal.org/node/3031082.
    $langcode = $this->languageManager->isMultilingual()
      ? $this->getContentLanguageFromContexts($contexts)
      : $this->languageManager->getDefaultLanguage()->getId();

    $entities = $this->entityTypeManager
      ->getStorage($entity_type_id)
      ->loadMultiple($entity_ids);

    foreach ($entities as $id => $entity) {
      // Retrieve the fittest revision, if needed.
      if ($entity instanceof RevisionableInterface && $entity->getEntityType()->isRevisionable()) {
        $entity = $this->getLatestTranslationAffectedRevision($entity, $langcode);
      }

      // Retrieve the fittest translation, if needed.
      if ($entity instanceof TranslatableInterface) {
        $entity = $this->getTranslationFromContext($entity, $langcode);
      }

      $active[$id] = $entity;
    }

    return $active;
  }

  /**
   * {@inheritdoc}
   */
  public function getCanonical($entity_type_id, $entity_id, array $contexts = NULL) {
    return current($this->getCanonicalMultiple($entity_type_id, [$entity_id], $contexts)) ?: NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function getCanonicalMultiple($entity_type_id, array $entity_ids, array $contexts = NULL) {
    $entities = $this->entityTypeManager->getStorage($entity_type_id)
      ->loadMultiple($entity_ids);

    if (!$entities || !$this->languageManager->isMultilingual()) {
      return $entities;
    }

    if (!isset($contexts)) {
      $contexts = $this->contextRepository->getAvailableContexts();
    }

    // @todo Consider deprecating the legacy context operation altogether in
    //   https://www.drupal.org/node/3031124.
    $legacy_context = [];
    $key = static::CONTEXT_ID_LEGACY_CONTEXT_OPERATION;
    if (isset($contexts[$key])) {
      $legacy_context['operation'] = $contexts[$key]->getContextValue();
    }

    $canonical = [];
    $langcode = $this->getContentLanguageFromContexts($contexts);
    foreach ($entities as $id => $entity) {
      $canonical[$id] = $this->getTranslationFromContext($entity, $langcode, $legacy_context);
    }

    return $canonical;
  }

  /**
   * Retrieves the current content language from the specified contexts.
   *
   * @param \Drupal\Core\Plugin\Context\ContextInterface[] $contexts
   *   An array of context items.
   *
   * @return string|null
   *   A language code or NULL if no language context was provided.
   */
  protected function getContentLanguageFromContexts(array $contexts) {
    // Content language might not be configurable, in which case we need to fall
    // back to a configurable language type.
    foreach ([LanguageInterface::TYPE_CONTENT, LanguageInterface::TYPE_INTERFACE] as $language_type) {
      $context_id = '@language.current_language_context:' . $language_type;
      if (isset($contexts[$context_id])) {
        return $contexts[$context_id]->getContextValue()->getId();
      }
    }
    return $this->languageManager->getDefaultLanguage()->getId();
  }

  /**
   * Returns the latest revision translation of the specified entity.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   The default revision of the entity being converted.
   * @param string $langcode
   *   The language of the revision translation to be loaded.
   *
   * @return \Drupal\Core\Entity\RevisionableInterface
   *   The latest translation-affecting revision for the specified entity, or
   *   just the latest revision, if the specified entity is not translatable or
   *   does not have a matching translation yet.
   */
  protected function getLatestTranslationAffectedRevision(RevisionableInterface $entity, $langcode) {
    $revision = NULL;
    $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());

    if ($entity instanceof TranslatableRevisionableInterface && $entity->isTranslatable()) {
      /** @var \Drupal\Core\Entity\TranslatableRevisionableStorageInterface $storage */
      $revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $langcode);

      // If the latest translation-affecting revision was a default revision, it
      // is fine to load the latest revision instead, because in this case the
      // latest revision, regardless of it being default or pending, will always
      // contain the most up-to-date values for the specified translation. This
      // provides a BC behavior when the route is defined by a module always
      // expecting the latest revision to be loaded and to be the default
      // revision. In this particular case the latest revision is always going
      // to be the default revision, since pending revisions would not be
      // supported.
      $revision = $revision_id ? $this->loadRevision($entity, $revision_id) : NULL;
      if (!$revision || ($revision->wasDefaultRevision() && !$revision->isDefaultRevision())) {
        $revision = NULL;
      }
    }

    // Fall back to the latest revisions if no affected revision for the current
    // content language could be found. This is acceptable as it means the
    // entity is not translated. This is the correct logic also on monolingual
    // sites.
    if (!isset($revision)) {
      $revision_id = $storage->getLatestRevisionId($entity->id());
      $revision = $this->loadRevision($entity, $revision_id);
    }

    return $revision;
  }

  /**
   * Loads the specified entity revision.
   *
   * @param \Drupal\Core\Entity\RevisionableInterface $entity
   *   The default revision of the entity being converted.
   * @param string $revision_id
   *   The identifier of the revision to be loaded.
   *
   * @return \Drupal\Core\Entity\RevisionableInterface
   *   An entity revision object.
   */
  protected function loadRevision(RevisionableInterface $entity, $revision_id) {
    // We explicitly perform a loose equality check, since a revision ID may be
    // returned as an integer or a string.
    if ($entity->getLoadedRevisionId() != $revision_id) {
      /** @var \Drupal\Core\Entity\RevisionableStorageInterface $storage */
      $storage = $this->entityTypeManager->getStorage($entity->getEntityTypeId());
      return $storage->loadRevision($revision_id);
    }
    return $entity;
  }

}
