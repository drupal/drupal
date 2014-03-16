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
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request, AccountInterface $account) {
    $entity_type = $request->attributes->get('_entity_type_id');
    /** @var $entity \Drupal\Core\Entity\EntityInterface */
    if ($entity = $request->attributes->get($entity_type)) {
      $route_requirements = $route->getRequirements();
      $operation = $route_requirements['_access_content_translation_manage'];
      $controller = content_translation_controller($entity_type, $account);

      // Load translation.
      $translations = $entity->getTranslationLanguages();
      $languages = language_list();

      switch ($operation) {
        case 'create':
          $source = language_load($request->attributes->get('source'));
          $target = language_load($request->attributes->get('target'));
          $source = !empty($source) ? $source : $entity->language();
          $target = !empty($target) ? $target : \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_CONTENT);
          return ($source->id != $target->id
            && isset($languages[$source->id])
            && isset($languages[$target->id])
            && !isset($translations[$target->id])
            && $controller->getTranslationAccess($entity, $operation))
            ? static::ALLOW : static::DENY;

        case 'update':
        case 'delete':
          $language = language_load($request->attributes->get('language'));
          $language = !empty($language) ? $language : \Drupal::languageManager()->getCurrentLanguage(Language::TYPE_CONTENT);
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
