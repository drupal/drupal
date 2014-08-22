<?php

/**
 * @file
 * Contains Drupal\content_translation\Access\ContentTranslationManageAccessCheck.
 */

namespace Drupal\content_translation\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Route;

/**
 * Access check for entity translation CRUD operation.
 */
class ContentTranslationManageAccessCheck implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface
   */
  protected $entityManager;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * Constructs a ContentTranslationManageAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   */
  public function __construct(EntityManagerInterface $manager, LanguageManagerInterface $language_manager) {
    $this->entityManager = $manager;
    $this->languageManager = $language_manager;
  }

  /**
   * Checks translation access for the entity and operation on the given route.
   *
   * @param \Symfony\Component\Routing\Route $route
   *   The route to check against.
   * @param \Symfony\Component\HttpFoundation\Request $request
   *   The request object.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   * @param string $source
   *   (optional) For a create operation, the language code of the source.
   * @param string $target
   *   (optional) For a create operation, the language code of the translation.
   * @param string $language
   *   (optional) For an update or delete operation, the language code of the
   *   translation being updated or deleted.
   * @param string $entity_type_id
   *   (optional) The entity type ID.
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, Request $request, AccountInterface $account, $source = NULL, $target = NULL, $language = NULL, $entity_type_id = NULL) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if ($entity = $request->attributes->get($entity_type_id)) {

      $operation = $route->getRequirement('_access_content_translation_manage');

      /* @var \Drupal\content_translation\ContentTranslationHandlerInterface $handler */
      $handler = $this->entityManager->getHandler($entity->getEntityTypeId(), 'translation');

      // Load translation.
      $translations = $entity->getTranslationLanguages();
      $languages = $this->languageManager->getLanguages();

      switch ($operation) {
        case 'create':
          $source_language = $this->languageManager->getLanguage($source) ?: $entity->language();
          $target_language = $this->languageManager->getLanguage($target) ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
          return ($source_language->getId() != $target_language->getId()
            && isset($languages[$source_language->getId()])
            && isset($languages[$target_language->getId()])
            && !isset($translations[$target_language->getId()])
            && $handler->getTranslationAccess($entity, $operation))
            ? static::ALLOW : static::DENY;

        case 'update':
        case 'delete':
          $language = $this->languageManager->getLanguage($language) ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
          return isset($languages[$language->getId()])
            && $language->getId() != $entity->getUntranslated()->language()->getId()
            && isset($translations[$language->getId()])
            && $handler->getTranslationAccess($entity, $operation)
            ? static::ALLOW : static::DENY;
      }
    }
    return static::DENY;
  }

}
