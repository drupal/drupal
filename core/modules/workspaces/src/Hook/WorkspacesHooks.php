<?php

namespace Drupal\workspaces\Hook;

use Drupal\workspaces\ViewsQueryAlter;
use Drupal\views\Plugin\views\query\QueryPluginBase;
use Drupal\views\ViewExecutable;
use Drupal\Core\Cache\Cache;
use Drupal\workspaces\EntityAccess;
use Drupal\Core\Session\AccountInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Entity\EntityTypeInterface;
use Drupal\workspaces\FormOperations;
use Drupal\workspaces\EntityOperations;
use Drupal\Core\Entity\EntityFormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\workspaces\EntityTypeInfo;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\Hook\Attribute\Hook;

/**
 * Hook implementations for workspaces.
 */
class WorkspacesHooks {

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help($route_name, RouteMatchInterface $route_match) {
    switch ($route_name) {
      // Main module help for the Workspaces module.
      case 'help.page.workspaces':
        $output = '';
        $output .= '<h2>' . t('About') . '</h2>';
        $output .= '<p>' . t('The Workspaces module allows workspaces to be defined and switched between. Content is then assigned to the active workspace when created. For more information, see the <a href=":workspaces">online documentation for the Workspaces module</a>.', [':workspaces' => 'https://www.drupal.org/docs/8/core/modules/workspace/overview']) . '</p>';
        return $output;
    }
  }

  /**
   * Implements hook_module_preinstall().
   */
  #[Hook('module_preinstall')]
  public function modulePreinstall($module) {
    if ($module !== 'workspaces') {
      return;
    }

    /** @var \Drupal\workspaces\WorkspaceInformationInterface $workspace_info */
    $workspace_info = \Drupal::service('workspaces.information');
    $entity_definition_update_manager = \Drupal::entityDefinitionUpdateManager();
    foreach ($entity_definition_update_manager->getEntityTypes() as $entity_type) {
      if ($workspace_info->isEntityTypeSupported($entity_type)) {
        $entity_type->setRevisionMetadataKey('workspace', 'workspace');
        $entity_definition_update_manager->updateEntityType($entity_type);
      }
    }
  }

  /**
   * Implements hook_entity_type_build().
   */
  #[Hook('entity_type_build')]
  public function entityTypeBuild(array &$entity_types) {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityTypeInfo::class)->entityTypeBuild($entity_types);
  }

  /**
   * Implements hook_entity_type_alter().
   */
  #[Hook('entity_type_alter')]
  public function entityTypeAlter(array &$entity_types) : void {
    \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityTypeInfo::class)->entityTypeAlter($entity_types);
  }

  /**
   * Implements hook_form_alter().
   */
  #[Hook('form_alter')]
  public function formAlter(&$form, FormStateInterface $form_state, $form_id) : void {
    if ($form_state->getFormObject() instanceof EntityFormInterface) {
      \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityOperations::class)->entityFormAlter($form, $form_state, $form_id);
    }
    \Drupal::service('class_resolver')->getInstanceFromDefinition(FormOperations::class)->formAlter($form, $form_state, $form_id);
  }

  /**
   * Implements hook_field_info_alter().
   */
  #[Hook('field_info_alter')]
  public function fieldInfoAlter(&$definitions): void {
    \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityTypeInfo::class)->fieldInfoAlter($definitions);
  }

  /**
   * Implements hook_entity_base_field_info().
   */
  #[Hook('entity_base_field_info')]
  public function entityBaseFieldInfo(EntityTypeInterface $entity_type) {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityTypeInfo::class)->entityBaseFieldInfo($entity_type);
  }

  /**
   * Implements hook_entity_preload().
   */
  #[Hook('entity_preload')]
  public function entityPreload(array $ids, $entity_type_id) {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityOperations::class)->entityPreload($ids, $entity_type_id);
  }

  /**
   * Implements hook_entity_presave().
   */
  #[Hook('entity_presave')]
  public function entityPresave(EntityInterface $entity) {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityOperations::class)->entityPresave($entity);
  }

  /**
   * Implements hook_entity_insert().
   */
  #[Hook('entity_insert')]
  public function entityInsert(EntityInterface $entity) {
    if ($entity->getEntityTypeId() === 'workspace') {
      \Drupal::service('workspaces.association')->workspaceInsert($entity);
      \Drupal::service('workspaces.repository')->resetCache();
    }
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityOperations::class)->entityInsert($entity);
  }

  /**
   * Implements hook_entity_update().
   */
  #[Hook('entity_update')]
  public function entityUpdate(EntityInterface $entity) {
    if ($entity->getEntityTypeId() === 'workspace') {
      \Drupal::service('workspaces.repository')->resetCache();
    }
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityOperations::class)->entityUpdate($entity);
  }

  /**
   * Implements hook_entity_translation_insert().
   */
  #[Hook('entity_translation_insert')]
  public function entityTranslationInsert(EntityInterface $translation) : void {
    \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityOperations::class)->entityTranslationInsert($translation);
  }

  /**
   * Implements hook_entity_predelete().
   */
  #[Hook('entity_predelete')]
  public function entityPredelete(EntityInterface $entity) {
    if ($entity->getEntityTypeId() === 'workspace') {
      \Drupal::service('workspaces.repository')->resetCache();
    }
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityOperations::class)->entityPredelete($entity);
  }

  /**
   * Implements hook_entity_delete().
   */
  #[Hook('entity_delete')]
  public function entityDelete(EntityInterface $entity) {
    if (\Drupal::service('workspaces.information')->isEntityTypeSupported($entity->getEntityType())) {
      \Drupal::service('workspaces.association')->deleteAssociations(NULL, $entity->getEntityTypeId(), [$entity->id()]);
    }
  }

  /**
   * Implements hook_entity_revision_delete().
   */
  #[Hook('entity_revision_delete')]
  public function entityRevisionDelete(EntityInterface $entity) {
    if (\Drupal::service('workspaces.information')->isEntityTypeSupported($entity->getEntityType())) {
      \Drupal::service('workspaces.association')->deleteAssociations(NULL, $entity->getEntityTypeId(), [$entity->id()], [$entity->getRevisionId()]);
    }
  }

  /**
   * Implements hook_entity_access().
   *
   * @see \Drupal\workspaces\EntityAccess
   */
  #[Hook('entity_access')]
  public function entityAccess(EntityInterface $entity, $operation, AccountInterface $account) {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityAccess::class)->entityOperationAccess($entity, $operation, $account);
  }

  /**
   * Implements hook_entity_create_access().
   *
   * @see \Drupal\workspaces\EntityAccess
   */
  #[Hook('entity_create_access')]
  public function entityCreateAccess(AccountInterface $account, array $context, $entity_bundle) {
    return \Drupal::service('class_resolver')->getInstanceFromDefinition(EntityAccess::class)->entityCreateAccess($account, $context, $entity_bundle);
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for the 'menu_link_content' entity type.
   */
  #[Hook('menu_link_content_update')]
  public function menuLinkContentUpdate(EntityInterface $entity) {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
    if ($entity->getLoadedRevisionId() != $entity->getRevisionId()) {
      // We are not updating the menu tree definitions when a custom menu link
      // entity is saved as a pending revision (because the parent can not be
      // changed), so we need to clear the system menu cache manually. However,
      // inserting or deleting a custom menu link updates the menu tree
      // definitions, so we don't have to do anything in those cases.
      $cache_tags = Cache::buildTags('config:system.menu', [$entity->getMenuName()], '.');
      \Drupal::service('cache_tags.invalidator')->invalidateTags($cache_tags);
    }
  }

  /**
   * Implements hook_views_query_alter().
   */
  #[Hook('views_query_alter')]
  public function viewsQueryAlter(ViewExecutable $view, QueryPluginBase $query): void {
    \Drupal::service('class_resolver')->getInstanceFromDefinition(ViewsQueryAlter::class)->alterQuery($view, $query);
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    \Drupal::service('workspaces.manager')->purgeDeletedWorkspacesBatch();
  }

}
