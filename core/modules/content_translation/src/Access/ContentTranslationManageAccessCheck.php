<?php

/**
 * @file
 * Contains Drupal\content_translation\Access\ContentTranslationManageAccessCheck.
 */

namespace Drupal\content_translation\Access;

use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
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
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parametrized route.
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
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(Route $route, RouteMatchInterface $route_match, AccountInterface $account, $source = NULL, $target = NULL, $language = NULL, $entity_type_id = NULL) {
    /* @var \Drupal\Core\Entity\ContentEntityInterface $entity */
    if ($entity = $route_match->getParameter($entity_type_id)) {
      $operation = $route->getRequirement('_access_content_translation_manage');
      $language = $this->languageManager->getLanguage($language) ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
      $entity_type = $this->entityManager->getDefinition($entity_type_id);

      if (in_array($operation, ['update', 'delete'])) {
        // Translation operations cannot be performed on the default
        // translation.
        if ($language->getId() == $entity->getUntranslated()->language()->getId()) {
          return AccessResult::forbidden()->cacheUntilEntityChanges($entity);
        }
        // Editors have no access to the translation operations, as entity
        // access already grants them an equal or greater access level.
        $templates = ['update' => 'edit-form', 'delete' => 'delete-form'];
        if ($entity->access($operation) && $entity_type->hasLinkTemplate($templates[$operation])) {
          return AccessResult::forbidden()->cachePerPermissions();
        }
      }

      if ($account->hasPermission('translate any entity')) {
        return AccessResult::allowed()->cachePerPermissions();
      }

      /* @var \Drupal\content_translation\ContentTranslationHandlerInterface $handler */
      $handler = $this->entityManager->getHandler($entity->getEntityTypeId(), 'translation');

      // Load translation.
      $translations = $entity->getTranslationLanguages();
      $languages = $this->languageManager->getLanguages();

      switch ($operation) {
        case 'create':
          $source_language = $this->languageManager->getLanguage($source) ?: $entity->language();
          $target_language = $this->languageManager->getLanguage($target) ?: $this->languageManager->getCurrentLanguage(LanguageInterface::TYPE_CONTENT);
          $is_new_translation = ($source_language->getId() != $target_language->getId()
            && isset($languages[$source_language->getId()])
            && isset($languages[$target_language->getId()])
            && !isset($translations[$target_language->getId()]));
          return AccessResult::allowedIf($is_new_translation)->cachePerPermissions()->cacheUntilEntityChanges($entity)
            ->andIf($handler->getTranslationAccess($entity, $operation));

        case 'delete':
        case 'update':
          $has_translation = isset($languages[$language->getId()])
            && $language->getId() != $entity->getUntranslated()->language()->getId()
            && isset($translations[$language->getId()]);
          return AccessResult::allowedIf($has_translation)->cachePerPermissions()->cacheUntilEntityChanges($entity)
            ->andIf($handler->getTranslationAccess($entity, $operation));
      }
    }

    // No opinion.
    return AccessResult::neutral();
  }

}
