<?php

/**
 * @file
 * Contains \Drupal\menu_link\MenuTree.
 */

namespace Drupal\menu_link;

use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Entity\EntityManagerInterface;
use Drupal\Core\Entity\Query\QueryFactory;
use Drupal\Core\State\StateInterface;
use Drupal\Core\Language\LanguageManagerInterface;
use Symfony\Cmf\Component\Routing\RouteObjectInterface;
use Symfony\Component\HttpFoundation\RequestStack;

/**
 * Provides the default implementation of a menu tree.
 */
class MenuTree implements MenuTreeInterface {

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   *   The database connection.
   */
  protected $database;

  /**
   * The cache backend.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $cache;

  /**
   * The language manager.
   *
   * @var \Drupal\Core\Language\LanguageManagerInterface
   */
  protected $languageManager;

  /**
   * The request stack.
   *
   * @var \Symfony\Component\HttpFoundation\RequestStack
   */
  protected $requestStack;

  /**
   * The menu link storage.
   *
   * @var \Drupal\menu_link\MenuLinkStorageInterface
   */
  protected $menuLinkStorage;

  /**
   * The entity query factory.
   *
   * @var \Drupal\Core\Entity\Query\QueryFactory
   */
  protected $queryFactory;

  /**
   * The state.
   *
   * @var \Drupal\Core\State\StateInterface
   */
  protected $state;

  /**
   * A list of active trail paths keyed by $menu_name.
   *
   * @var array
   */
  protected $trailPaths;

  /**
   * Stores the rendered menu output keyed by $menu_name.
   *
   * @var array
   */
  protected $menuOutput;

  /**
   * Stores the menu tree used by the doBuildTree method, keyed by a cache ID.
   *
   * This cache ID is built using the $menu_name, the current language and
   * some parameters passed into an entity query.
   */
  protected $menuTree;

  /**
   * Stores the full menu tree data keyed by a cache ID.
   *
   * This variable distinct from static::$menuTree by having also items without
   * access by the current user.
   *
   * This cache ID is built with the menu name, a passed in root link ID, the
   * current language as well as the maximum depth.
   *
   * @var array
   */
  protected $menuFullTrees;

  /**
   * Stores the menu tree data on the current page keyed by a cache ID.
   *
   * This contains less information than a tree built with buildAllData.
   *
   * @var array
   */
  protected $menuPageTrees;

  /**
   * Constructs a new MenuTree.
   *
   * @param \Drupal\Core\Database\Connection $database
   *   The database connection.
   * @param \Drupal\Core\Cache\CacheBackendInterface $cache_backend
   *   The cache backend.
   * @param \Drupal\Core\Language\LanguageManagerInterface $language_manager
   *   The language manager.
   * @param \Symfony\Component\HttpFoundation\RequestStack $request_stack
   *   The request stack.
   * @param \Drupal\Core\Entity\EntityManagerInterface $entity_manager
   *   The entity manager.
   * @param \Drupal\Core\Entity\Query\QueryFactory $entity_query_factory
   *   The entity query factory.
   * @param \Drupal\Core\State\StateInterface $state
   *   The state.
   */
  public function __construct(Connection $database, CacheBackendInterface $cache_backend, LanguageManagerInterface $language_manager, RequestStack $request_stack, EntityManagerInterface $entity_manager, QueryFactory $entity_query_factory, StateInterface $state) {
    $this->database = $database;
    $this->cache = $cache_backend;
    $this->languageManager = $language_manager;
    $this->requestStack = $request_stack;
    $this->menuLinkStorage = $entity_manager->getStorage('menu_link');
    $this->queryFactory = $entity_query_factory;
    $this->state = $state;
  }

  /**
   * {@inheritdoc}
   */
  public function buildAllData($menu_name, $link = NULL, $max_depth = NULL) {
    $language_interface = $this->languageManager->getCurrentLanguage();

    // Use $mlid as a flag for whether the data being loaded is for the whole
    // tree.
    $mlid = isset($link['mlid']) ? $link['mlid'] : 0;
    // Generate a cache ID (cid) specific for this $menu_name, $link, $language,
    // and depth.
    $cid = 'links:' . $menu_name . ':all:' . $mlid . ':' . $language_interface->id . ':' . (int) $max_depth;

    if (!isset($this->menuFullTrees[$cid])) {
      // If the static variable doesn't have the data, check {cache_menu}.
      $cache = $this->cache->get($cid);
      if ($cache && $cache->data) {
        // If the cache entry exists, it contains the parameters for
        // menu_build_tree().
        $tree_parameters = $cache->data;
      }
      // If the tree data was not in the cache, build $tree_parameters.
      if (!isset($tree_parameters)) {
        $tree_parameters = array(
          'min_depth' => 1,
          'max_depth' => $max_depth,
        );
        if ($mlid) {
          // The tree is for a single item, so we need to match the values in
          // its p columns and 0 (the top level) with the plid values of other
          // links.
          $parents = array(0);
          for ($i = 1; $i < MENU_MAX_DEPTH; $i++) {
            if (!empty($link["p$i"])) {
              $parents[] = $link["p$i"];
            }
          }
          $tree_parameters['expanded'] = $parents;
          $tree_parameters['active_trail'] = $parents;
          $tree_parameters['active_trail'][] = $mlid;
        }

        // Cache the tree building parameters using the page-specific cid.
        $this->cache->set($cid, $tree_parameters, Cache::PERMANENT, array('menu' => $menu_name));
      }

      // Build the tree using the parameters; the resulting tree will be cached
      // by $this->doBuildTree()).
      $this->menuFullTrees[$cid] = $this->buildTree($menu_name, $tree_parameters);
    }

    return $this->menuFullTrees[$cid];
  }

  /**
   * {@inheritdoc}
   */
  public function buildPageData($menu_name, $max_depth = NULL, $only_active_trail = FALSE) {
    $language_interface = $this->languageManager->getCurrentLanguage();

    // Load the request corresponding to the current page.
    $request = $this->requestStack->getCurrentRequest();
    $system_path = NULL;
    if ($route_name = $request->attributes->get(RouteObjectInterface::ROUTE_NAME)) {
      // @todo https://drupal.org/node/2068471 is adding support so we can tell
      // if this is called on a 404/403 page.
      $system_path = $request->attributes->get('_system_path');
      $page_not_403 = 1;
    }
    if (isset($system_path)) {
      if (isset($max_depth)) {
        $max_depth = min($max_depth, MENU_MAX_DEPTH);
      }
      // Generate a cache ID (cid) specific for this page.
      $cid = 'links:' . $menu_name . ':page:' . $system_path . ':' . $language_interface->id . ':' . $page_not_403 . ':' . (int) $max_depth;
      // If we are asked for the active trail only, and $menu_name has not been
      // built and cached for this page yet, then this likely means that it
      // won't be built anymore, as this function is invoked from
      // template_preprocess_page(). So in order to not build a giant menu tree
      // that needs to be checked for access on all levels, we simply check
      // whether we have the menu already in cache, or otherwise, build a
      // minimum tree containing the active trail only.
      if (!isset($this->menuPageTrees[$cid]) && $only_active_trail) {
        $cid .= ':trail';
      }

      if (!isset($this->menuPageTrees[$cid])) {
        // If the static variable doesn't have the data, check {cache_menu}.
        $cache = $this->cache->get($cid);
        if ($cache && $cache->data) {
          // If the cache entry exists, it contains the parameters for
          // menu_build_tree().
          $tree_parameters = $cache->data;
        }
        // If the tree data was not in the cache, build $tree_parameters.
        if (!isset($tree_parameters)) {
          $tree_parameters = array(
            'min_depth' => 1,
            'max_depth' => $max_depth,
          );
          $active_trail = $this->getActiveTrailIds($menu_name);

          // If this page is accessible to the current user, build the tree
          // parameters accordingly.
          if ($page_not_403) {
            // The active trail contains more than only array(0 => 0).
            if (count($active_trail) > 1) {
              // If we are asked to build links for the active trail only,skip
              // the entire 'expanded' handling.
              if ($only_active_trail) {
                $tree_parameters['only_active_trail'] = TRUE;
              }
            }
            $parents = $active_trail;

            $expanded = $this->state->get('menu_expanded');
            // Check whether the current menu has any links set to be expanded.
            if (!$only_active_trail && $expanded && in_array($menu_name, $expanded)) {
              // Collect all the links set to be expanded, and then add all of
              // their children to the list as well.
              do {
                $query = $this->queryFactory->get('menu_link')
                  ->condition('menu_name', $menu_name)
                  ->condition('expanded', 1)
                  ->condition('has_children', 1)
                  ->condition('plid', $parents, 'IN')
                  ->condition('mlid', $parents, 'NOT IN');
                $result = $query->execute();
                $parents += $result;
              } while (!empty($result));
            }
            $tree_parameters['expanded'] = $parents;
            $tree_parameters['active_trail'] = $active_trail;
          }
          // If access is denied, we only show top-level links in menus.
          else {
            $tree_parameters['expanded'] = $active_trail;
            $tree_parameters['active_trail'] = $active_trail;
          }
          // Cache the tree building parameters using the page-specific cid.
          $this->cache->set($cid, $tree_parameters, Cache::PERMANENT, array('menu' => $menu_name));
        }

        // Build the tree using the parameters; the resulting tree will be
        // cached by $tihs->buildTree().
        $this->menuPageTrees[$cid] = $this->buildTree($menu_name, $tree_parameters);
      }
      return $this->menuPageTrees[$cid];
    }

    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getActiveTrailIds($menu_name) {
    // Parent mlids; used both as key and value to ensure uniqueness.
    // We always want all the top-level links with plid == 0.
    $active_trail = array(0 => 0);

    $request = $this->requestStack->getCurrentRequest();

    if ($route_name = $request->attributes->get(RouteObjectInterface::ROUTE_NAME)) {
      // @todo https://drupal.org/node/2068471 is adding support so we can tell
      // if this is called on a 404/403 page.
      // Check if the active trail has been overridden for this menu tree.
      $active_path = $this->getPath($menu_name);
      // Find a menu link corresponding to the current path. If
      // $active_path is NULL, let menu_link_get_preferred() determine
      // the path.
      if ($active_link = $this->menuLinkGetPreferred($menu_name, $active_path)) {
        if ($active_link['menu_name'] == $menu_name) {
          // Use all the coordinates, except the last one because
          // there can be no child beyond the last column.
          for ($i = 1; $i < MENU_MAX_DEPTH; $i++) {
            if ($active_link['p' . $i]) {
              $active_trail[$active_link['p' . $i]] = $active_link['p' . $i];
            }
          }
        }
      }
    }
    return $active_trail;
  }

  /**
   * {@inheritdoc}
   */
  public function setPath($menu_name, $path = NULL) {
    if (isset($path)) {
      $this->trailPaths[$menu_name] = $path;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getPath($menu_name) {
    return isset($this->trailPaths[$menu_name]) ? $this->trailPaths[$menu_name] : NULL;
  }

  /**
   * {@inheritdoc}
   */
  public function renderMenu($menu_name) {

    if (!isset($this->menuOutput[$menu_name])) {
      $tree = $this->buildPageData($menu_name);
      $this->menuOutput[$menu_name] = $this->renderTree($tree);
    }
    return $this->menuOutput[$menu_name];
  }

  /**
   * {@inheritdoc}
   */
  public function renderTree($tree) {
    $build = array();
    $items = array();
    $menu_name = $tree ? end($tree)['link']['menu_name'] : '';

    // Pull out just the menu links we are going to render so that we
    // get an accurate count for the first/last classes.
    foreach ($tree as $data) {
      if ($data['link']['access'] && !$data['link']['hidden']) {
        $items[] = $data;
      }
    }

    foreach ($items as $data) {
      $class = array();
      // Set a class for the <li>-tag. Since $data['below'] may contain local
      // tasks, only set 'expanded' class if the link also has children within
      // the current menu.
      if ($data['link']['has_children'] && $data['below']) {
        $class[] = 'expanded';
      }
      elseif ($data['link']['has_children']) {
        $class[] = 'collapsed';
      }
      else {
        $class[] = 'leaf';
      }
      // Set a class if the link is in the active trail.
      if ($data['link']['in_active_trail']) {
        $class[] = 'active-trail';
        $data['link']['localized_options']['attributes']['class'][] = 'active-trail';
      }

      // Allow menu-specific theme overrides.
      $element['#theme'] = 'menu_link__' . strtr($data['link']['menu_name'], '-', '_');
      $element['#attributes']['class'] = $class;
      $element['#title'] = $data['link']['title'];
      // @todo Use route name and parameters to generate the link path, unless
      //    it is external.
      $element['#href'] = $data['link']['link_path'];
      $element['#localized_options'] = !empty($data['link']['localized_options']) ? $data['link']['localized_options'] : array();
      $element['#below'] = $data['below'] ? $this->renderTree($data['below']) : $data['below'];
      $element['#original_link'] = $data['link'];
      // Index using the link's unique mlid.
      $build[$data['link']['mlid']] = $element;
    }
    if ($build) {
      // Make sure drupal_render() does not re-order the links.
      $build['#sorted'] = TRUE;
      // Add the theme wrapper for outer markup.
      // Allow menu-specific theme overrides.
      $build['#theme_wrappers'][] = 'menu_tree__' . strtr($menu_name, '-', '_');
      // Set cache tag.
      $menu_name = $data['link']['menu_name'];
      $build['#cache']['tags']['menu'][$menu_name] = $menu_name;
    }

    return $build;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTree($menu_name, array $parameters = array()) {
    // Build the menu tree.
    $tree = $this->doBuildTree($menu_name, $parameters);
    // Check access for the current user to each item in the tree.
    $this->checkAccess($tree);
    return $tree;
  }

  /**
   * Builds a menu tree.
   *
   * This function may be used build the data for a menu tree only, for example
   * to further massage the data manually before further processing happens.
   * MenuTree::checkAccess() needs to be invoked afterwards.
   *
   * @param string $menu_name
   *   The name of the menu.
   * @param array $parameters
   *   The parameters passed into static::buildTree()
   *
   * @see static::buildTree()
   */
  protected function doBuildTree($menu_name, array $parameters = array()) {
    $language_interface = $this->languageManager->getCurrentLanguage();

    // Build the cache id; sort parents to prevent duplicate storage and remove
    // default parameter values.
    if (isset($parameters['expanded'])) {
      sort($parameters['expanded']);
    }
    $tree_cid = 'links:' . $menu_name . ':tree-data:' . $language_interface->id . ':' . hash('sha256', serialize($parameters));

    // If we do not have this tree in the static cache, check {cache_menu}.
    if (!isset($this->menuTree[$tree_cid])) {
      $cache = $this->cache->get($tree_cid);
      if ($cache && $cache->data) {
        $this->menuTree[$tree_cid] = $cache->data;
      }
    }

    if (!isset($this->menuTree[$tree_cid])) {
      $query = $this->queryFactory->get('menu_link');
      for ($i = 1; $i <= MENU_MAX_DEPTH; $i++) {
        $query->sort('p' . $i, 'ASC');
      }
      $query->condition('menu_name', $menu_name);
      if (!empty($parameters['expanded'])) {
        $query->condition('plid', $parameters['expanded'], 'IN');
      }
      elseif (!empty($parameters['only_active_trail'])) {
        $query->condition('mlid', $parameters['active_trail'], 'IN');
      }
      $min_depth = (isset($parameters['min_depth']) ? $parameters['min_depth'] : 1);
      if ($min_depth != 1) {
        $query->condition('depth', $min_depth, '>=');
      }
      if (isset($parameters['max_depth'])) {
        $query->condition('depth', $parameters['max_depth'], '<=');
      }
      // Add custom query conditions, if any were passed.
      if (isset($parameters['conditions'])) {
        foreach ($parameters['conditions'] as $column => $value) {
          $query->condition($column, $value);
        }
      }

      // Build an ordered array of links using the query result object.
      $links = array();
      if ($result = $query->execute()) {
        $links = $this->menuLinkStorage->loadMultiple($result);
      }
      $active_trail = (isset($parameters['active_trail']) ? $parameters['active_trail'] : array());
      $tree = $this->doBuildTreeData($links, $active_trail, $min_depth);

      // Cache the data, if it is not already in the cache.
      $this->cache->set($tree_cid, $tree, Cache::PERMANENT, array('menu' => $menu_name));
      $this->menuTree[$tree_cid] = $tree;
    }

    return $this->menuTree[$tree_cid];
  }

  /**
   * Sorts the menu tree and recursively checks access for each item.
   *
   * @param array $tree
   *   The menu tree you wish to operate on.
   */
  protected function checkAccess(&$tree) {
    $new_tree = array();
    foreach ($tree as $key => $v) {
      $item = &$tree[$key]['link'];
      $this->menuLinkTranslate($item);
      if ($item['access'] || ($item['in_active_trail'] && strpos($item['href'], '%') !== FALSE)) {
        if ($tree[$key]['below']) {
          $this->checkAccess($tree[$key]['below']);
        }
        // The weights are made a uniform 5 digits by adding 50000 as an offset.
        // After _menu_link_translate(), $item['title'] has the localized link
        // title. Adding the mlid to the end of the index insures that it is
        // unique.
        $new_tree[(50000 + $item['weight']) . ' ' . $item['title'] . ' ' . $item['mlid']] = $tree[$key];
      }
    }
    // Sort siblings in the tree based on the weights and localized titles.
    ksort($new_tree);
    $tree = $new_tree;
  }

  /**
   * {@inheritdoc}
   */
  public function buildTreeData(array $links, array $parents = array(), $depth = 1) {
    $tree = $this->doBuildTreeData($links, $parents, $depth);
    $this->checkAccess($tree);
    return $tree;
  }

  /**
   * Prepares the data for calling $this->treeDataRecursive().
   */
  protected function doBuildTreeData(array $links, array $parents = array(), $depth = 1) {
    // Reverse the array so we can use the more efficient array_pop() function.
    $links = array_reverse($links);
    return $this->treeDataRecursive($links, $parents, $depth);
  }

  /**
   * Builds the data representing a menu tree.
   *
   * The function is a bit complex because the rendering of a link depends on
   * the next menu link.
   *
   * @param array $links
   *   A flat array of menu links that are part of the menu. Each array element
   *   is an associative array of information about the menu link, containing
   *   the fields from the {menu_links} table, and optionally additional
   *   information from the {menu_router} table, if the menu item appears in
   *   both tables. This array must be ordered depth-first.
   *   See _menu_build_tree() for a sample query.
   * @param array $parents
   *   An array of the menu link ID values that are in the path from the current
   *   page to the root of the menu tree.
   * @param int $depth
   *   The minimum depth to include in the returned menu tree.
   *
   * @return array
   */
  protected function treeDataRecursive(&$links, $parents, $depth) {
    $tree = array();
    while ($item = array_pop($links)) {
      // We need to determine if we're on the path to root so we can later build
      // the correct active trail.
      $item['in_active_trail'] = in_array($item['mlid'], $parents);
      // Add the current link to the tree.
      $tree[$item['mlid']] = array(
        'link' => $item,
        'below' => array(),
      );
      // Look ahead to the next link, but leave it on the array so it's
      // available to other recursive function calls if we return or build a
      // sub-tree.
      $next = end($links);
      // Check whether the next link is the first in a new sub-tree.
      if ($next && $next['depth'] > $depth) {
        // Recursively call doBuildTreeData to build the sub-tree.
        $tree[$item['mlid']]['below'] = $this->treeDataRecursive($links, $parents, $next['depth']);
        // Fetch next link after filling the sub-tree.
        $next = end($links);
      }
      // Determine if we should exit the loop and return.
      if (!$next || $next['depth'] < $depth) {
        break;
      }
    }
    return $tree;
  }

  /**
   * Wraps menu_link_get_preferred().
   */
  protected function menuLinkGetPreferred($menu_name, $active_path) {
    return menu_link_get_preferred($active_path, $menu_name);
  }

  /**
   * Wraps _menu_link_translate().
   */
  protected function menuLinkTranslate(&$item) {
    _menu_link_translate($item);
  }

}
