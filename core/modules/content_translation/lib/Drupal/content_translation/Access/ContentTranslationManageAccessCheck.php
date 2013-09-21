<?php

/**
 * @file
 * Contains Drupal\content_translation\Access\ContentTranslationManageAccessCheck.
 */

namespace Drupal\content_translation\Access;

use Drupal\Core\Entity\EntityManager;
use Drupal\Core\Access\StaticAccessCheckInterface;
use Drupal\Core\Language\Language;
use Symfony\Component\Routing\Route;
use Symfony\Component\HttpFoundation\Request;

/**
 * Access check for entity translation CRUD operation.
 */
class ContentTranslationManageAccessCheck implements StaticAccessCheckInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityManager
   */
  protected $entityManager;

  /**
   * Constructs a ContentTranslationManageAccessCheck object.
   *
   * @param \Drupal\Core\Entity\EntityManager $manager
   *   The entity type manager.
   */
  public function __construct(EntityManager $manager) {
    $this->entityManager = $manager;
  }

  /**
   * {@inheritdoc}
   */
  public function appliesTo() {
    return array('_access_content_translation_manage');
  }

  /**
   * {@inheritdoc}
   */
  public function access(Route $route, Request $request) {
    if ($entity = $request->attributes->get('entity')) {
      $route_requirements = $route->getRequirements();
      $operation = $route_requirements['_access_content_translation_manage'];
      $entity_type = $entity->entityType();
      $controller_class = $this->entityManager->getControllerClass($entity_type, 'translation');
      $controller = new $controller_class($entity_type, $entity->entityInfo());

      // Load translation.
      $translations = $entity->getTranslationLanguages();
      $languages = language_list();

      switch ($operation) {
        case 'create':
          $source = language_load($request->attributes->get('source'));
          $target = language_load($request->attributes->get('target'));
          $source = !empty($source) ? $source : $entity->language();
          $target = !empty($target) ? $target : language(Language::TYPE_CONTENT);
          return ($source->id != $target->id
            && isset($languages[$source->id])
            && isset($languages[$target->id])
            && !isset($translations[$target->id])
            && $controller->getTranslationAccess($entity, $operation))
            ? static::ALLOW : static::DENY;

        case 'update':
        case 'delete':
          $language = language_load($request->attributes->get('language'));
          $language = !empty($language) ? $language : language(Language::TYPE_CONTENT);
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
