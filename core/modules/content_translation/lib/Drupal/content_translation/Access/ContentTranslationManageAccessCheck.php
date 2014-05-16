<?php

/**
 * @file
 * Contains Drupal\content_translation\Access\ContentTranslationManageAccessCheck.
 */

namespace Drupal\content_translation\Access;

use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Session\AccountInterface;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

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
   * Constructs a ContentTranslationManageAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManagerInterface $manager
   *   The entity type manager.
   */
  public function __construct(EntityManagerInterface $manager) {
    $this->entityManager = $manager;
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
   *
   * @return string
   *   A \Drupal\Core\Access\AccessInterface constant value.
   */
  public function access(Route $route, Request $request, AccountInterface $account, $source = NULL, $target = NULL, $language = NULL) {
    $entity_type = $request->attributes->get('_entity_type_id');
    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    if ($entity = $request->attributes->get($entity_type)) {
      $operation = $route->getRequirement('_access_content_translation_manage');
      $controller = content_translation_controller($entity_type, $account);

      // Load translation.
      $translations = $entity->getTranslationLanguages();
      $languages = language_list();

      switch ($operation) {
        case 'create':
          $source = language_load($source) ?: $entity->language();
          $target = language_load($target) ?: \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_CONTENT);
          return ($source->id != $target->id
            && isset($languages[$source->id])
            && isset($languages[$target->id])
            && !isset($translations[$target->id])
            && $controller->getTranslationAccess($entity, $operation))
            ? static::ALLOW : static::DENY;

        case 'update':
        case 'delete':
          $language = language_load($language) ?: \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_CONTENT);
          return isset($languages[$language->id])
            && $language->id != $entity->getUntranslated()->language()->id
            && isset($translations[$language->id])
            && $controller->getTranslationAccess($entity, $operation)
            ? static::ALLOW : static::DENY;
      }
    }
    return static::DENY;
  }

}
