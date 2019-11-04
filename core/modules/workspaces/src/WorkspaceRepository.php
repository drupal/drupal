<?php

namespace Drupal\workspaces;

use Drupal\Component\Graph\Graph;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Entity\EntityTypeManagerInterface;

/**
 * Provides the default workspace tree lookup operations.
 */
class WorkspaceRepository implements WorkspaceRepositoryInterface {

  /**
   * The entity type manager service.
   *
   * @var \Drupal\Core\Entity\EntityTypeManagerInterface
   */
  protected $entityTypeManager;

  /**
   * The cache backend used to store the workspace tree.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * An array of tree items, keyed by workspace IDs and sorted in tree order.
   *
   * @var array|null
   */
  protected $tree;

  /**
   * Constructs a new WorkspaceRepository instance.
   *
   * @param \Drupal\Core\Entity\EntityTypeManagerInterface $entity_type_manager
   *   The entity type manager service.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   */
  public function __construct(EntityTypeManagerInterface $entity_type_manager, CacheBackendInterface $cache_backend) {
    $this->entityTypeManager = $entity_type_manager;
    $this->cache = $cache_backend;
  }

  /**
   * {@inheritdoc}
   */
  public function loadTree() {
    if (!isset($this->tree)) {
      $cache = $this->cache->get('workspace_tree');
      if ($cache) {
        $this->tree = $cache->data;
        return $this->tree;
      }

      /** @var \Drupal\workspaces\WorkspaceInterface[] $workspaces */
      $workspaces = $this->entityTypeManager->getStorage('workspace')->loadMultiple();

      // First, sort everything alphabetically.
      uasort($workspaces, function (WorkspaceInterface $a, WorkspaceInterface $b) {
        return strnatcasecmp($a->label(), $b->label());
      });

      $tree_children = [];
      foreach ($workspaces as $workspace_id => $workspace) {
        $tree_children[$workspace->parent->target_id][] = $workspace_id;
      }

      // Keeps track of the parents we have to process, the last entry is used
      // for the next processing step. Top-level (root) workspace use NULL as
      // the parent, so we need to initialize the list with that value.
      $process_parents[] = NULL;

      // Loops over the parent entities and adds its children to the tree array.
      // Uses a loop instead of a recursion, because it's more efficient.
      $tree = [];
      while (count($process_parents)) {
        $parent = array_pop($process_parents);

        if (!empty($tree_children[$parent])) {
          $child_id = current($tree_children[$parent]);
          do {
            if (empty($child_id)) {
              break;
            }
            $tree[$child_id] = $workspaces[$child_id];

            if (!empty($tree_children[$child_id])) {
              // We have to continue with this parent later.
              $process_parents[] = $parent;
              // Use the current entity as parent for the next iteration.
              $process_parents[] = $child_id;

              // Move pointer so that we get the correct entity the next time.
              next($tree_children[$parent]);
              break;
            }
          } while ($child_id = next($tree_children[$parent]));
        }
      }

      // Generate a graph object in order to populate the `_depth`, `_ancestors`
      // and '_descendants' properties for all the entities.
      $graph = [];
      foreach ($workspaces as $workspace_id => $workspace) {
        $graph[$workspace_id]['edges'] = [];
        if (!$workspace->parent->isEmpty()) {
          $graph[$workspace_id]['edges'][$workspace->parent->target_id] = TRUE;
        }
      }
      $graph = (new Graph($graph))->searchAndSort();

      foreach (array_keys($tree) as $workspace_id) {
        $this->tree[$workspace_id] = [
          'depth' => count($graph[$workspace_id]['paths']),
          'ancestors' => array_keys($graph[$workspace_id]['paths']),
          'descendants' => isset($graph[$workspace_id]['reverse_paths']) ? array_keys($graph[$workspace_id]['reverse_paths']) : [],
        ];
      }

      // Use the 'workspace_list' entity type cache tag because it will be
      // invalidated automatically when a workspace is added, updated or
      // deleted.
      $this->cache->set('workspace_tree', $this->tree, Cache::PERMANENT, $this->entityTypeManager->getDefinition('workspace')->getListCacheTags());
    }

    return $this->tree;
  }

  /**
   * {@inheritdoc}
   */
  public function getDescendantsAndSelf($workspace_id) {
    return array_merge([$workspace_id], $this->loadTree()[$workspace_id]['descendants']);
  }

  /**
   * {@inheritdoc}
   */
  public function resetCache() {
    $this->tree = NULL;

    return $this;
  }

}
