<?php

declare(strict_types=1);

namespace Drupal\workspaces\Hook;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Entity\EntityDefinitionUpdateManagerInterface;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Hook\Attribute\Hook;
use Drupal\Core\Routing\RouteMatchInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\workspaces\WorkspaceInformationInterface;
use Drupal\workspaces\WorkspaceManagerInterface;

/**
 * Hook implementations for workspaces.
 */
class WorkspacesHooks {

  use StringTranslationTrait;

  public function __construct(
    protected WorkspaceManagerInterface $workspaceManager,
    protected WorkspaceInformationInterface $workspaceInfo,
    protected EntityDefinitionUpdateManagerInterface $entityDefinitionUpdateManager,
    protected CacheTagsInvalidatorInterface $cacheTagsInvalidator,
  ) {}

  /**
   * Implements hook_help().
   */
  #[Hook('help')]
  public function help(string $route_name, RouteMatchInterface $route_match): string {
    $output = '';
    switch ($route_name) {
      // Main module help for the Workspaces module.
      case 'help.page.workspaces':
        $output = '';
        $output .= '<h2>' . $this->t('About') . '</h2>';
        $output .= '<p>' . $this->t('The Workspaces module allows workspaces to be defined and switched between. Content is then assigned to the active workspace when created. For more information, see the <a href=":workspaces">online documentation for the Workspaces module</a>.', [':workspaces' => 'https://www.drupal.org/docs/8/core/modules/workspace/overview']) . '</p>';
        break;
    }
    return $output;
  }

  /**
   * Implements hook_module_preinstall().
   */
  #[Hook('module_preinstall')]
  public function modulePreinstall(string $module): void {
    if ($module !== 'workspaces') {
      return;
    }

    foreach ($this->entityDefinitionUpdateManager->getEntityTypes() as $entity_type) {
      if ($this->workspaceInfo->isEntityTypeSupported($entity_type)) {
        $entity_type->setRevisionMetadataKey('workspace', 'workspace');
        $this->entityDefinitionUpdateManager->updateEntityType($entity_type);
      }
    }
  }

  /**
   * Implements hook_ENTITY_TYPE_update() for 'menu_link_content' entities.
   */
  #[Hook('menu_link_content_update')]
  public function menuLinkContentUpdate(EntityInterface $entity): void {
    /** @var \Drupal\menu_link_content\MenuLinkContentInterface $entity */
    if ($entity->getLoadedRevisionId() != $entity->getRevisionId()) {
      // We are not updating the menu tree definitions when a custom menu link
      // entity is saved as a pending revision (because the parent can not be
      // changed), so we need to clear the system menu cache manually. However,
      // inserting or deleting a custom menu link updates the menu tree
      // definitions, so we don't have to do anything in those cases.
      $cache_tags = Cache::buildTags('config:system.menu', [$entity->getMenuName()], '.');
      $this->cacheTagsInvalidator->invalidateTags($cache_tags);
    }
  }

  /**
   * Implements hook_cron().
   */
  #[Hook('cron')]
  public function cron(): void {
    $this->workspaceManager->purgeDeletedWorkspacesBatch();
  }

}
