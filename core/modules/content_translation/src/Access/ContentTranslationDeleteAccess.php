<?php

namespace Drupal\content_translation\Access;

use Drupal\content_translation\ContentTranslationManager;
use Drupal\content_translation\ContentTranslationManagerInterface;
use Drupal\Core\Access\AccessResult;
use Drupal\Core\Entity\ContentEntityInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Routing\Access\AccessInterface;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Session\AccountInterface;
use Drupal\language\Entity\ContentLanguageSettings;
use Drupal\workflows\Entity\Workflow;

/**
 * Access check for entity translation deletion.
 *
 * @internal This additional access checker only aims to prevent deletions in
 *   pending revisions until we are able to flag revision translations as
 *   deleted.
 *
 * @todo Remove this in https://www.drupal.org/node/2945956.
 */
class ContentTranslationDeleteAccess implements AccessInterface {

  /**
   * The entity type manager.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The content translation manager.
   *
   * @var \Drupal\content_translation\ContentTranslationManagerInterface
   */
  protected $contentTranslationManager;

  /**
   * Constructs a ContentTranslationDeleteAccess object.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $manager
   *   The entity type manager.
   * @param \Drupal\content_translation\ContentTranslationManagerInterface $content_translation_manager
   *   The content translation manager.
   */
  public function __construct(EntityTypeManagerInterface $manager, ContentTranslationManagerInterface $content_translation_manager) {
    $this->entityTypeManager = $manager;
    $this->contentTranslationManager = $content_translation_manager;
  }

  /**
   * Checks access to translation deletion for the specified route match.
   *
   * @param \Drupal\Core\Routing\RouteMatchInterface $route_match
   *   The parameterized route.
   * @param \Drupal\Core\Session\AccountInterface $account
   *   The currently logged in account.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function access(RouteMatchInterface $route_match, AccountInterface $account) {
    $requirement = $route_match->getRouteObject()->getRequirement('_access_content_translation_delete');
    $entity_type_id = current(explode('.', $requirement));
    $entity = $route_match->getParameter($entity_type_id);
    return $this->checkAccess($entity);
  }

  /**
   * Checks access to translation deletion for the specified entity.
   *
   * @param \Drupal\Core\Entity\ContentEntityInterface $entity
   *   The entity translation to be deleted.
   *
   * @return \Drupal\Core\Access\AccessResultInterface
   *   The access result.
   */
  public function checkAccess(ContentEntityInterface $entity) {
    $result = AccessResult::allowed();

    $entity_type_id = $entity->getEntityTypeId();
    $result->addCacheableDependency($entity);
    // Add the cache dependencies used by
    // ContentTranslationManager::isPendingRevisionSupportEnabled().
    if (\Drupal::moduleHandler()->moduleExists('content_moderation')) {
      foreach (Workflow::loadMultipleByType('content_moderation') as $workflow) {
        $result->addCacheableDependency($workflow);
      }
    }
    if (!ContentTranslationManager::isPendingRevisionSupportEnabled($entity_type_id, $entity->bundle())) {
      return $result;
    }

    if ($entity->isDefaultTranslation()) {
      return $result;
    }

    $config = ContentLanguageSettings::load($entity_type_id . '.' . $entity->bundle());
    $result->addCacheableDependency($config);
    if (!$this->contentTranslationManager->isEnabled($entity_type_id, $entity->bundle())) {
      return $result;
    }

    /** @var \Drupal\Core\Entity\ContentEntityStorageInterface $storage */
    $storage = $this->entityTypeManager->getStorage($entity_type_id);
    $revision_id = $storage->getLatestTranslationAffectedRevisionId($entity->id(), $entity->language()->getId());
    if (!$revision_id) {
      return $result;
    }

    /** @var \Drupal\Core\Entity\ContentEntityInterface $revision */
    $revision = $storage->loadRevision($revision_id);
    if ($revision->wasDefaultRevision()) {
      return $result;
    }

    $result = $result->andIf(AccessResult::forbidden());
    return $result;
  }

}
