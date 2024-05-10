<?php

namespace Drupal\workspaces;

use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Cache\CacheTagsInvalidatorInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityTypeManagerInterface;
use Drupal\Core\Menu\MenuTreeParameters;
use Drupal\Core\Menu\MenuTreeStorage as CoreMenuTreeStorage;

/**
 * Overrides the default menu storage to provide workspace-specific menu links.
 *
 * @internal
 */
class WorkspacesMenuTreeStorage extends CoreMenuTreeStorage {

  /**
   * WorkspacesMenuTreeStorage constructor.
   *
   * @param \Drupal\workspaces\WorkspaceManagerInterface $workspaceManager
   *   The workspace manager service.
   * @param \Drupal\workspaces\WorkspaceAssociationInterface $workspaceAssociation
   *   The workspace association service.
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entityTypeManager
   *   The entity type manager.
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection to use for reading and writing configuration data.
   * @param \Drupal\Core\Cache\CacheBackendInterface $menu_cache_backend
   *   Cache backend instance for the extracted tree data.
   * @param \Drupal\Core\Cache\CacheTagsInvalidatorInterface $cache_tags_invalidator
   *   The cache tags invalidator.
   * @param string $table
   *   A database table name to store configuration data in.
   * @param array $options
   *   (optional) Any additional database connection options to use in queries.
   */
  public function __construct(
    protected readonly WorkspaceManagerInterface $workspaceManager,
    protected readonly WorkspaceAssociationInterface $workspaceAssociation,
    protected readonly EntityTypeManagerInterface $entityTypeManager,
    Connection $connection,
    CacheBackendInterface $menu_cache_backend,
    CacheTagsInvalidatorInterface $cache_tags_invalidator,
    string $table,
    array $options = [],
  ) {
    parent::__construct($connection, $menu_cache_backend, $cache_tags_invalidator, $table, $options);
  }

  /**
   * {@inheritdoc}
   */
  public function loadTreeData($menu_name, MenuTreeParameters $parameters) {
    // Add the active workspace as a menu tree condition parameter in order to
    // include it in the cache ID.
    if ($active_workspace = $this->workspaceManager->getActiveWorkspace()) {
      $parameters->conditions['workspace'] = $active_workspace->id();
    }
    return parent::loadTreeData($menu_name, $parameters);
  }

  /**
   * {@inheritdoc}
   */
  protected function loadLinks($menu_name, MenuTreeParameters $parameters) {
    $links = parent::loadLinks($menu_name, $parameters);

    // Replace the menu link plugin definitions with workspace-specific ones.
    if ($active_workspace = $this->workspaceManager->getActiveWorkspace()) {
      $tracked_revisions = $this->workspaceAssociation->getTrackedEntities($active_workspace->id());
      if (isset($tracked_revisions['menu_link_content'])) {
        /** @var \Drupal\menu_link_content\MenuLinkContentInterface[] $workspace_revisions */
        $workspace_revisions = $this->entityTypeManager->getStorage('menu_link_content')->loadMultipleRevisions(array_keys($tracked_revisions['menu_link_content']));
        foreach ($workspace_revisions as $workspace_revision) {
          if (isset($links[$workspace_revision->getPluginId()])) {
            $pending_plugin_definition = $workspace_revision->getPluginDefinition();
            $links[$workspace_revision->getPluginId()] = [
              'title' => serialize($pending_plugin_definition['title']),
              'description' => serialize($pending_plugin_definition['description']),
              'enabled' => (string) $pending_plugin_definition['enabled'],
              'url' => $pending_plugin_definition['url'],
              'route_name' => $pending_plugin_definition['route_name'],
              'route_parameters' => serialize($pending_plugin_definition['route_parameters']),
              'options' => serialize($pending_plugin_definition['options']),
            ] + $links[$workspace_revision->getPluginId()];
          }
        }
      }
    }

    return $links;
  }

}
