<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuTreeStorage.
 */

namespace Drupal\Core\Menu;

use Drupal\Core\Database\Connection;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Database\Database;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;

/**
 * Provides a tree storage using the database.
 */
class MenuTreeStorage implements MenuTreeStorageInterface {

  /**
   * The maximum depth of a menu links tree.
   */
  const MAX_DEPTH = 9;

  /**
   * The database connection.
   *
   * @var \Drupal\Core\Database\Connection
   */
  protected $connection;

  /**
   * Cache backend instance for the extracted tree data.
   *
   * @var \Drupal\Core\Cache\CacheBackendInterface
   */
  protected $treeCacheBackend;

  /**
   * The database table name.
   *
   * @var string
   */
  protected $table;

  /**
   * Additional database connection options to use in queries.
   *
   * @var array
   */
  protected $options = array();

  /**
   * Stores the definitions that have already been loaded for better performance.
   */
  protected $definitions = array();

  /**
   * List of serialized fields.
   *
   * @var array
   */
  protected $serializedFields;

  /**
   * List of plugin definition fields.
   *
   * @todo - inject this from the plugin manager?
   *
   * @var array
   */
  protected $definitionFields = array(
    'menu_name',
    'route_name',
    'route_parameters',
    'url',
    'title',
    'title_arguments',
    'title_context',
    'description',
    'parent',
    'weight',
    'options',
    'expanded',
    'hidden',
    'discovered',
    'provider',
    'metadata',
    'class',
    'form_class',
    'id',
  );

  /**
   * Constructs a new \Drupal\Core\Menu\MenuTreeStorage.
   *
   * @param \Drupal\Core\Database\Connection $connection
   *   A Database connection to use for reading and writing configuration data.
   * @param \Drupal\Core\Cache\CacheBackendInterface $tree_cache_backend
   *   Cache backend instance for the extracted tree data.
   * @param string $table
   *   A database table name to store configuration data in.
   * @param array $options
   *   (optional) Any additional database connection options to use in queries.
   */
  public function __construct(Connection $connection, CacheBackendInterface $tree_cache_backend, $table, array $options = array()) {
    $this->connection = $connection;
    $this->treeCacheBackend = $tree_cache_backend;
    $this->table = $table;
    $this->options = $options;
  }

  /**
   * {@inheritdoc}
   */
  public function maxDepth() {
    return static::MAX_DEPTH;
  }

  /**
   * {@inheritdoc}
   */
  public function resetDefinitions() {
    $this->definitions = array();
  }

  /**
   * {@inheritdoc}
   */
  public function rebuild(array $definitions) {
    $links = array();
    $children = array();
    $top_links = array();
    // Fetch the list of existing menus, in case some are not longer populated
    // after the rebuild.
    $before_menus = $this->getMenuNames();
    if ($definitions) {
      foreach ($definitions as $id => $link) {
        if (!empty($link['parent'])) {
          $children[$link['parent']][$id] = $id;
        }
        else {
          // A top level link - we need them to root our tree.
          $top_links[$id] = $id;
          $link['parent'] = '';
        }
        $links[$id] = $link;
      }
    }
    foreach ($top_links as $id) {
      $this->saveRecursive($id, $children, $links);
    }
    // Handle any children we didn't find starting from top-level links.
    foreach ($children as $orphan_links) {
      foreach ($orphan_links as $id) {
        // Force it to the top level.
        $links[$id]['parent'] = '';
        $this->saveRecursive($id, $children, $links);
      }
    }
    // Find any previously discovered menu links that no longer exist.
    if ($definitions) {
      $query = $this->connection->select($this->table, NULL, $this->options);
      $query->addField($this->table, 'id');
      $query->condition('discovered', 1);
      $query->condition('id', array_keys($definitions), 'NOT IN');
      $query->orderBy('depth', 'DESC');
      $result = $query->execute()->fetchCol();
    }
    else {
      $result = array();
    }

    // Remove all such items. Starting from those with the greatest depth will
    // minimize the amount of re-parenting done by the menu link controller.
    if ($result) {
      $this->purgeMultiple($result);
    }
    $this->resetDefinitions();
    $affected_menus = $this->getMenuNames() + $before_menus;
    Cache::invalidateTags(array('menu' => $affected_menus));
  }

  /**
   * Purges multiple menu links that no longer exist.
   *
   * @param array $ids
   *   An array of menu link IDs.
   * @param bool $prevent_reparenting
   *   (optional) Disables the re-parenting logic from the deletion process.
   *   Defaults to FALSE.
   */
  protected function purgeMultiple(array $ids, $prevent_reparenting = FALSE) {
    if (!$prevent_reparenting) {
      $loaded = $this->loadFullMultiple($ids);
      foreach ($loaded as $id => $link) {
        if ($link['has_children']) {
          $children = $this->loadByProperties(array('parent' => $id));
          foreach ($children as $child) {
            $child['parent'] = $link['parent'];
            $this->save($child);
          }
        }
      }
    }
    $query = $this->connection->delete($this->table, $this->options);
    $query->condition('id', $ids, 'IN');
    $query->execute();
  }

  /**
   * Executes a select query while making sure the database table exists.
   *
   * @param \Drupal\Core\Database\Query\SelectInterface $query
   *   The select object to be executed.
   *
   * @return \Drupal\Core\Database\StatementInterface|null
   *   A prepared statement, or NULL if the query is not valid.
   *
   * @throws \Exception
   *   If the table could not be created or the database connection failed.
   */
  protected function safeExecuteSelect(SelectInterface $query) {
    try {
      return $query->execute();
    }
    catch (\Exception $e) {
      // If there was an exception, try to create the table.
      if ($this->ensureTableExists()) {
        return $query->execute();
      }
      // Some other failure that we can not recover from.
      throw $e;
    }
  }

  /**
   * {@inheritdoc}
   */
  public function save(array $link) {
    $original = $this->loadFull($link['id']);
    // @todo - should we just return here if the links values match the original
    //   values completely?.
    $affected_menus = array();

    $transaction = $this->connection->startTransaction();
    try {
      if ($original) {
        $link['mlid'] = $original['mlid'];
        $link['has_children'] = $original['has_children'];
        $affected_menus[$original['menu_name']] = $original['menu_name'];
      }
      else {
        // Generate a new mlid.
        $options = array('return' => Database::RETURN_INSERT_ID) + $this->options;
        $link['mlid'] = $this->connection->insert($this->table, $options)
          ->fields(array('id' => $link['id'], 'menu_name' => $link['menu_name']))
          ->execute();
      }
      $fields = $this->preSave($link, $original);
      // We may be moving the link to a new menu.
      $affected_menus[$fields['menu_name']] = $fields['menu_name'];
      $query = $this->connection->update($this->table, $this->options);
      $query->condition('mlid', $link['mlid']);
      $query->fields($fields)
        ->execute();
      if ($original) {
        $this->updateParentalStatus($original);
      }
      $this->updateParentalStatus($link);
    }
    catch (\Exception $e) {
      $transaction->rollback();
      throw $e;
    }
    $this->resetDefinitions();
    Cache::invalidateTags(array('menu' => $affected_menus));
    return $affected_menus;
  }

  /**
   * Using the link definition, but up all the fields needed for database save.
   *
   * @param array $link
   *   The link definition to be updated.
   * @param array $original
   *   The link definition before the changes. May be empty if not found.
   *
   * @return array
   *   The values which will be stored.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown when the specific depth exceeds the maximum.
   */
  protected function preSave(array &$link, array $original) {
    static $schema_fields, $schema_defaults;
    if (empty($schema_fields)) {
      $schema = static::schemaDefinition();
      $schema_fields = $schema['fields'];
      foreach ($schema_fields as $name => $spec) {
        if (isset($spec['default'])) {
          $schema_defaults[$name] = $spec['default'];
        }
      }
    }

    // Try to find a parent link. If found, assign it and derive its menu.
    $parent = $this->findParent($link, $original);
    if ($parent) {
      $link['parent'] = $parent['id'];
      $link['menu_name'] = $parent['menu_name'];
    }
    else {
      $link['parent'] = '';
    }

    // If no corresponding parent link was found, move the link to the
    // top-level.
    foreach ($schema_defaults as $name => $default) {
      if (!isset($link[$name])) {
        $link[$name] = $default;
      }
    }
    $fields = array_intersect_key($link, $schema_fields);
    asort($fields['route_parameters']);
    // Since this will be urlencoded, it's safe to store and match against a
    // text field.
    $fields['route_param_key'] = $fields['route_parameters'] ? UrlHelper::buildQuery($fields['route_parameters']) : '';

    foreach ($this->serializedFields() as $name) {
      $fields[$name] = serialize($fields[$name]);
    }

    // Directly fill parents for top-level links.
    if (empty($link['parent'])) {
      $fields['p1'] = $link['mlid'];
      for ($i = 2; $i <= $this->maxDepth(); $i++) {
        $fields["p$i"] = 0;
      }
      $fields['depth'] = 1;
    }
    // Otherwise, ensure that this link's depth is not beyond the maximum depth
    // and fill parents based on the parent link.
    else {
      // @todo - we want to also check $original['has_children'] here, but that
      //   will be 0 even if there are children if those are hidden.
      //   has_children is really just the rendering hint. So, we either need
      //   to define another column (has_any_children), or do the extra query.
      if ($original) {
        $limit = $this->maxDepth() - $this->doFindChildrenRelativeDepth($original) - 1;
      }
      else {
        $limit = $this->maxDepth() - 1;
      }
      if ($parent['depth'] > $limit) {
        throw new PluginException(sprintf('The link with ID %s or its children exceeded the maximum depth of %d', $link['id'], $this->maxDepth()));
      }
      $this->setParents($fields, $parent);
    }

    // Need to check both parent and menu_name, since parent can be empty in any
    // menu.
    if ($original && ($link['parent'] != $original['parent'] || $link['menu_name'] != $original['menu_name'])) {
      $this->moveChildren($fields, $original);
    }
    // We needed the mlid above, but not in the update query.
    unset($fields['mlid']);

    // Cast booleans to int, if needed.
    $fields['hidden'] = (int) $fields['hidden'];
    $fields['expanded'] = (int) $fields['expanded'];
    return $fields;
  }

  /**
   * {@inheritdoc}
   */
  public function delete($id) {
    // Children get re-attached to the menu link's parent.
    $item = $this->loadFull($id);
    // It's possible the link is already deleted.
    if ($item) {
      $parent = $item['parent'];
      $children = $this->loadByProperties(array('parent' => $id));
      foreach ($children as $child) {
        $child['parent'] = $parent;
        $this->save($child);
      }

      $this->connection->delete($this->table, $this->options)
        ->condition('id', $id)
        ->execute();

      $this->updateParentalStatus($item);
      // Many children may have moved.
      $this->resetDefinitions();
      Cache::invalidateTags(array('menu' => $item['menu_name']));
    }
  }

  /**
   * {@inheritdoc}
   */
  public function getSubtreeHeight($id) {
    $original = $this->loadFull($id);
    return $original ? $this->doFindChildrenRelativeDepth($original) + 1 : 0;
  }

  /**
   * Finds the relative depth of this link's deepest child.
   *
   * @param array $original
   *   The parent definition used to find the depth.
   *
   * @return int
   *   Returns the relative depth.
   */
  protected function doFindChildrenRelativeDepth(array $original) {
    $query = $this->connection->select($this->table, $this->options);
    $query->addField($this->table, 'depth');
    $query->condition('menu_name', $original['menu_name']);
    $query->orderBy('depth', 'DESC');
    $query->range(0, 1);

    for ($i = 1; $i <= static::MAX_DEPTH && $original["p$i"]; $i++) {
      $query->condition("p$i", $original["p$i"]);
    }

    $max_depth = $this->safeExecuteSelect($query)->fetchField();

    return ($max_depth > $original['depth']) ? $max_depth - $original['depth'] : 0;
  }

  /**
   * Sets the materialized path field values based on the parent.
   *
   * @param array $fields
   *   The menu link.
   * @param array $parent
   *   The parent menu link.
   */
  protected function setParents(array &$fields, array $parent) {
    $fields['depth'] = $parent['depth'] + 1;
    $i = 1;
    while ($i < $fields['depth']) {
      $p = 'p' . $i++;
      $fields[$p] = $parent[$p];
    }
    $p = 'p' . $i++;
    // The parent (p1 - p9) corresponding to the depth always equals the mlid.
    $fields[$p] = $fields['mlid'];
    while ($i <= static::MAX_DEPTH) {
      $p = 'p' . $i++;
      $fields[$p] = 0;
    }
  }

  /**
   * Moves the link's children using the query fields value and original values.
   *
   * @param array $fields
   *   The changed menu link.
   * @param array $original
   *   The original menu link.
   */
  protected function moveChildren($fields, $original) {
    $query = $this->connection->update($this->table, $this->options);

    $query->fields(array('menu_name' => $fields['menu_name']));

    $expressions = array();
    for ($i = 1; $i <= $fields['depth']; $i++) {
      $expressions[] = array("p$i", ":p_$i", array(":p_$i" => $fields["p$i"]));
    }
    $j = $original['depth'] + 1;
    while ($i <= $this->maxDepth() && $j <= $this->maxDepth()) {
      $expressions[] = array('p' . $i++, 'p' . $j++, array());
    }
    while ($i <= $this->maxDepth()) {
      $expressions[] = array('p' . $i++, 0, array());
    }

    $shift = $fields['depth'] - $original['depth'];
    if ($shift > 0) {
      // The order of expressions must be reversed so the new values don't
      // overwrite the old ones before they can be used because "Single-table
      // UPDATE assignments are generally evaluated from left to right"
      // @see http://dev.mysql.com/doc/refman/5.0/en/update.html
      $expressions = array_reverse($expressions);
    }
    foreach ($expressions as $expression) {
      $query->expression($expression[0], $expression[1], $expression[2]);
    }

    $query->expression('depth', 'depth + :depth', array(':depth' => $shift));
    $query->condition('menu_name', $original['menu_name']);

    for ($i = 1; $i <= $this->maxDepth() && $original["p$i"]; $i++) {
      $query->condition("p$i", $original["p$i"]);
    }

    $query->execute();
  }

  /**
   * Loads the parent definition if it exists.
   *
   * @param array $link
   *   The link definition to check.
   * @param array|FALSE $original
   *   The original link, or FALSE.
   *
   * @return array|FALSE
   *   Returns a definition array, or FALSE if no parent was found.
   */
  protected function findParent($link, $original) {
    $parent = FALSE;

    // This item is explicitly top-level, skip the rest of the parenting.
    if (isset($link['parent']) && empty($link['parent'])) {
      return $parent;
    }

    // If we have a parent link ID, try to use that.
    $candidates = array();
    if (isset($link['parent'])) {
      $candidates[] = $link['parent'];
    }
    elseif (!empty($original['parent']) && $link['menu_name'] == $original['menu_name']) {
      // Otherwise, fall back to the original parent.
      $candidates[] = $original['parent'];
    }

    foreach ($candidates as $id) {
      $parent = $this->loadFull($id);
      if ($parent) {
        break;
      }
    }
    return $parent;
  }

  /**
   * Set the has_children flag for the link's parent if it has visible children.
   *
   * @param array $link
   *   The link to get a parent ID from.
   */
  protected function updateParentalStatus(array $link) {
    // If parent is empty, there is nothing to update.
    if (!empty($link['parent'])) {
      // Check if at least one visible child exists in the table.
      $query = $this->connection->select($this->table, $this->options);
      $query->addExpression('1');
      $query->range(0, 1);
      $query
        ->condition('menu_name', $link['menu_name'])
        ->condition('parent', $link['parent'])
        ->condition('hidden', 0);

      $parent_has_children = ((bool) $query->execute()->fetchField()) ? 1 : 0;
      $this->connection->update($this->table, $this->options)
        ->fields(array('has_children' => $parent_has_children))
        ->condition('id', $link['parent'])
        ->execute();
    }
  }

  protected function prepareLink($link, $intersect = FALSE) {
    foreach ($this->serializedFields() as $name) {
      $link[$name] = unserialize($link[$name]);
    }
    if ($intersect) {
      $link = array_intersect_key($link, array_flip($this->definitionFields()));
    }
    $this->definitions[$link['id']] = $link;
    return $link;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByProperties(array $properties) {
    // @todo - only allow loading by plugin definition properties.
    $query = $this->connection->select($this->table, $this->options);
    $query->fields($this->table, $this->definitionFields());
    foreach ($properties as $name => $value) {
      $query->condition($name, $value);
    }
    $loaded = $this->safeExecuteSelect($query)->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
    foreach ($loaded as $id => $link) {
      $loaded[$id] = $this->prepareLink($link);
    }
    return $loaded;
  }

  /**
   * {@inheritdoc}
   */
  public function loadByRoute($route_name, array $route_parameters = array(), $menu_name = NULL) {
    asort($route_parameters);
    // Since this will be urlencoded, it's safe to store and match against a
    // text field.
    // @todo - does this make more sense than using the system path?
    $param_key = $route_parameters ? UrlHelper::buildQuery($route_parameters) : '';
    $query = $this->connection->select($this->table, $this->options);
    $query->fields($this->table, $this->definitionFields());
    $query->condition('route_name', $route_name);
    $query->condition('route_param_key', $param_key);
    if ($menu_name) {
      $query->condition('menu_name', $menu_name);
    }
    $loaded = $this->safeExecuteSelect($query)->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
    foreach ($loaded as $id => $link) {
      $loaded[$id] = $this->prepareLink($link);
    }
    return $loaded;
  }

  /**
   * {@inheritdoc}
   */
  public function loadMultiple(array $ids) {
    $query = $this->connection->select($this->table, $this->options);
    $query->fields($this->table, $this->definitionFields());
    $query->condition('id', $ids, 'IN');
    $loaded = $this->safeExecuteSelect($query)->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
    foreach ($loaded as $id => $link) {
      $loaded[$id] = $this->prepareLink($link);
    }
    return $loaded;
  }

  /**
   * {@inheritdoc}
   */
  public function load($id) {
    if (isset($this->definitions[$id])) {
      return $this->definitions[$id];
    }
    $loaded = $this->loadMultiple(array($id));
    return isset($loaded[$id]) ? $loaded[$id] : FALSE;
  }

  /**
   * Loads all table fields, not just those that are in the plugin definition.
   *
   * @param string $id
   *   The menu link ID.
   *
   * @return array
   *   The loaded menu link definition or an empty array if not be found.
   */
  protected function loadFull($id) {
    $loaded = $this->loadFullMultiple(array($id));
    return isset($loaded[$id]) ? $loaded[$id] : array();
  }

  /**
   * Loads multiple menu link definitions by ID.
   *
   * @param array $ids
   *   The IDs to load.
   *
   * @return array
   *   The loaded menu link definitions.
   */
  protected function loadFullMultiple(array $ids) {
    $query = $this->connection->select($this->table, $this->options);
    $query->fields($this->table);
    $query->condition('id', $ids, 'IN');
    $loaded = $this->safeExecuteSelect($query)->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
    foreach ($loaded as &$link) {
      foreach ($this->serializedFields() as $name) {
        $link[$name] = unserialize($link[$name]);
      }
    }
    return $loaded;
  }

  /**
   * {@inheritdoc}
   */
  public function getRootPathIds($id) {
    $subquery = $this->connection->select($this->table, $this->options);
    // @todo - consider making this dynamic based on static::MAX_DEPTH
    //   or from the schema if that is generated using static::MAX_DEPTH.
    $subquery->fields($this->table, array('p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8', 'p9'));
    $subquery->condition('id', $id);
    $result = current($subquery->execute()->fetchAll(\PDO::FETCH_ASSOC));
    $ids = array_filter($result);
    if ($ids) {
      $query = $this->connection->select($this->table, $this->options);
      $query->fields($this->table, array('id'));
      $query->orderBy('depth', 'DESC');
      $query->condition('mlid', $ids, 'IN');
      // @todo - cache this result in memory if we find it's being used more
      //   than once per page load.
      return $this->safeExecuteSelect($query)->fetchAllKeyed(0, 0);
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getExpanded($menu_name, array $parents) {
    // @todo - go back to tracking in state or some other way
    //   which menus have expanded links?
    do {
      $query = $this->connection->select($this->table, $this->options);
      $query->fields($this->table, array('id'));
      $query->condition('menu_name', $menu_name);
      $query->condition('expanded', 1);
      $query->condition('has_children', 1);
      $query->condition('hidden', 0);
      $query->condition('parent', $parents, 'IN');
      $query->condition('id', $parents, 'NOT IN');
      $result = $this->safeExecuteSelect($query)->fetchAllKeyed(0, 0);
      $parents += $result;
    } while (!empty($result));
    return $parents;
  }

  /**
   * Saves menu links recursively.
   */
  protected function saveRecursive($id, &$children, &$links) {

    if (!empty($links[$id]['parent']) && empty($links[$links[$id]['parent']])) {
      // Invalid parent ID, so remove it.
      $links[$id]['parent'] = '';
    }
    $this->save($links[$id]);

    if (!empty($children[$id])) {
      foreach ($children[$id] as $next_id) {
        $this->saveRecursive($next_id, $children, $links);
      }
    }
    // Remove processed link names so we can find stragglers.
    unset($children[$id]);
  }

  /**
   * {@inheritdoc}
   */
  public function loadTreeData($menu_name, MenuLinkTreeParameters $parameters) {
    // Build the cache id; sort 'expanded' and 'conditions' to prevent duplicate
    // cache items.
    sort($parameters->expanded);
    sort($parameters->conditions);
    // @todo - may be able to skip hashing after https://drupal.org/node/2224847
    $tree_cid = "tree-data:$menu_name:" . hash('sha256', serialize($parameters));
    $cache = $this->treeCacheBackend->get($tree_cid);
    if ($cache && isset($cache->data)) {
      $data = $cache->data;
      // Cache the definitions in memory so they don't need to be loaded again.
      $this->definitions += $data['definitions'];
      unset($data['definitions']);
    }
    else {
      $links = $this->loadLinks($menu_name, $parameters);
      $data['tree'] = $this->doBuildTreeData($links, $parameters->activeTrail, $parameters->minDepth);
      $data['definitions'] = array();
      $data['route_names'] = $this->collectRoutesAndDefinitions($data['tree'], $data['definitions']);
      $this->treeCacheBackend->set($tree_cid, $data, Cache::PERMANENT, array('menu' => $menu_name));
      // The definitions were already added to $this->definitions in
      // $this->doBuildTreeData()
      unset($data['definitions']);
    }
    return $data;
  }

  /**
   * Loads links in the given menu, according to the given tree parameters.
   *
   * @param string $menu_name
   *   A menu name.
   * @param MenuLinkTreeParameters &$parameters
   *   The parameters to determine which menu links to be loaded into a tree.
   *   Passed by reference, so that ::loadLinks() can set the absolute minimum
   *   depth, which is used by ::doBuildTreeData().
   * @return array
   *   A flat array of menu links that are part of the menu. Each array element
   *   is an associative array of information about the menu link, containing
   *   the fields from the {menu_tree} table. This array must be ordered
   *   depth-first.
   */
  protected function loadLinks($menu_name, MenuLinkTreeParameters &$parameters) {
    $query = $this->connection->select($this->table, $this->options);
    $query->fields($this->table);

    // Allow a custom root to be specified for loading a menu link tree. If
    // ommitted, the default root (i.e. the actual root, '') is used.
    if ($parameters->root !== '') {
      $root = $this->loadFull($parameters->root);

      // If the custom root does not exist, we cannot load the links below it.
      if (!$root) {
        return array();
      }

      // When specifying a custom root, we only want to find links whose
      // parent IDs match that of the root; that's how ignore the rest of the
      // tree. In other words: we exclude everything unreachable from the
      // custom root.
      for ($i = 1; $i <= $root['depth']; $i++) {
        $query->condition("p$i", $root["p$i"]);
      }

      // When specifying a custom root, the menu is determined by that root.
      $menu_name = $root['menu_name'];

      // If the custom root exists, then we must rewrite some of our
      // parameters; parameters are relative to the root (default or custom),
      // but the queries require absolute numbers, so adjust correspondingly.
      if (isset($parameters->minDepth)) {
        $parameters->minDepth += $root['depth'];
      }
      else {
        $parameters->minDepth = $root['depth'];
      }
      if (isset($parameters->maxDepth)) {
        $parameters->maxDepth += $root['depth'];
      }
    }

    // If no minimum depth is specified, then set the actual minimum depth,
    // depending on the root.
    if (!isset($parameters->minDepth)) {
      if ($parameters->root !== '' && $root) {
        $parameters->minDepth = $root['depth'];
      }
      else {
        $parameters->minDepth = 1;
      }
    }

    for ($i = 1; $i <= $this->maxDepth(); $i++) {
      $query->orderBy('p' . $i, 'ASC');
    }

    $query->condition('menu_name', $menu_name);

    if (!empty($parameters->expanded)) {
      $query->condition('parent', $parameters->expanded, 'IN');
    }
    if (isset($parameters->minDepth) && $parameters->minDepth > 1) {
      $query->condition('depth', $parameters->minDepth, '>=');
    }
    if (isset($parameters->maxDepth)) {
      $query->condition('depth', $parameters->maxDepth, '<=');
    }
    // Add custom query conditions, if any were passed.
    if (!empty($parameters->conditions)) {
      // Only allow conditions that are testing definition fields.
      $parameters->conditions = array_intersect_key($parameters->conditions, array_flip($this->definitionFields()));
      foreach ($parameters->conditions as $column => $value) {
        if (!is_array($value)) {
          $query->condition($column, $value);
        }
        else {
          $operator = $value[1];
          $value = $value[0];
          $query->condition($column, $value, $operator);
        }
      }
    }

    $links = $this->safeExecuteSelect($query)->fetchAllAssoc('id', \PDO::FETCH_ASSOC);

    return $links;
  }

  /**
   * Traverses the menu tree and collects all the route names and definitions.
   *
   * @param array $tree
   *   The menu tree you wish to operate on.
   * @param array $definitions
   *   An array to accumulate definitions.
   *
   * @return array
   *   Array of route names, with all values being unique.
   */
  protected function collectRoutesAndDefinitions(array $tree, array &$definitions) {
    return array_values($this->doCollectRoutesAndDefinitions($tree, $definitions));
  }

  /**
   * Recursive helper function to collect all the route names and definitions.
   *
   * @param array $tree
   *   The menu link tree.
   * @param array &$definitions
   *   The collected definitions.
   *
   * @return array
   *   The collected route names.
   */
  protected function doCollectRoutesAndDefinitions(array $tree, array &$definitions) {
    $route_names = array();
    foreach (array_keys($tree) as $id) {
      $definition = $this->definitions[$id];
      $definitions[$id] = $definition;
      if (!empty($definition['route_name'])) {
        $route_names[$definition['route_name']] = $definition['route_name'];
      }
      if (!empty($tree[$id]->subtree)) {
        $route_names += $this->doCollectRoutesAndDefinitions($tree[$id]->subtree, $definitions);
      }
    }
    return $route_names;
  }

  /**
   * {@inheritdoc}
   */
  public function loadSubtreeData($id, $max_relative_depth = NULL) {
    $tree = array();
    $root = $this->loadFull($id);
    if (!$root) {
      return $tree;
    }
    $parameters = new MenuLinkTreeParameters();
    $parameters->setRoot($id)->excludeHiddenLinks();
    return $this->loadTreeData($root['menu_name'], $parameters);
  }

  /**
   * {@inheritdoc}
   */
  public function menuNameInUse($menu_name) {
    $query = $this->connection->select($this->table, $this->options);
    $query->addField($this->table, 'mlid');
    $query->condition('menu_name', $menu_name);
    $query->range(0, 1);
    return (bool) $this->safeExecuteSelect($query);
  }

  /**
   * {@inheritdoc}
   */
  public function getMenuNames() {
    $query = $this->connection->select($this->table, $this->options);
    $query->addField($this->table, 'menu_name');
    $query->distinct();
    return $this->safeExecuteSelect($query)->fetchAllKeyed(0, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function countMenuLinks($menu_name = NULL) {
    $query = $this->connection->select($this->table, $this->options);
    if ($menu_name) {
      $query->condition('menu_name', $menu_name);
    }
    return $this->safeExecuteSelect($query->countQuery())->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function getAllChildIds($id) {
    $root = $this->loadFull($id);
    if (!$root) {
      return array();
    }
    $query = $this->connection->select($this->table, $this->options);
    $query->fields($this->table, array('id'));
    $query->condition('menu_name', $root['menu_name']);
    for ($i = 1; $i <= $root['depth']; $i++) {
      $query->condition("p$i", $root["p$i"]);
    }
    // The next p column should not be empty. This excludes the root link.
    $query->condition("p$i", 0, '>');
    return $this->safeExecuteSelect($query)->fetchAllKeyed(0, 0);
  }

  /**
   * {@inheritdoc}
   */
  public function loadAllChildLinks($id, $max_relative_depth = NULL) {
    $parameters = new MenuLinkTreeParameters();
    $parameters->setRoot($id)->excludeRoot()->setMaxDepth($max_relative_depth)->excludeHiddenLinks();
    $links = $this->loadLinks(NULL, $parameters);
    foreach ($links as $id => $link) {
      $links[$id] = $this->prepareLink($link);
    }
    return $links;
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
   *   the fields from the {menu_tree} table. This array must be ordered
   *   depth-first.
   *   See ::loadTreeData() for a sample query.
   * @param array $parents
   *   An array of the menu link ID values that are in the path from the current
   *   page to the root of the menu tree.
   * @param int $depth
   *   The minimum depth to include in the returned menu tree.
   *
   * @return array
   *   The fully built tree.
   */
  protected function treeDataRecursive(array &$links, array $parents, $depth) {
    $tree = array();
    while ($tree_link_definition = array_pop($links)) {
      // Build a MenuTreeElement out of the menu link tree definition:
      // transform the menu link tree definition into a menu link definition and
      // store tree metadata in MenuTreeElement.
      $tree[$tree_link_definition['id']] = new MenuTreeElement(
        $this->prepareLink($tree_link_definition, TRUE),
        (bool) $tree_link_definition['has_children'],
        (int) $tree_link_definition['depth'],
        // We need to determine if we're on the path to root so we can later build
        // the correct active trail.
        in_array($tree_link_definition['id'], $parents),
        array()
      );

      // Look ahead to the next link, but leave it on the array so it's
      // available to other recursive function calls if we return or build a
      // sub-tree.
      $next = end($links);
      // Check whether the next link is the first in a new sub-tree.
      if ($next && $next['depth'] > $depth) {
        // Recursively call doBuildTreeData to build the sub-tree.
        $tree[$tree_link_definition['id']]->subtree = $this->treeDataRecursive($links, $parents, $next['depth']);
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
   * Checks if the tree table exists and create it if not.
   *
   * @return bool
   *   TRUE if the table was created, FALSE otherwise.
   *
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   If a database error occurs.
   */
  protected function ensureTableExists() {
    try {
      if (!$this->connection->schema()->tableExists($this->table)) {
        $this->connection->schema()->createTable($this->table, static::schemaDefinition());
        return TRUE;
      }
    }
    catch (SchemaObjectExistsException $e) {
      // If another process has already created the config table, attempting to
      // recreate it will throw an exception. In this case just catch the
      // exception and do nothing.
      return TRUE;
    }
    catch (\Exception $e) {
      throw new PluginException($e->getMessage(), NULL, $e);
    }
    return FALSE;
  }

  /**
   * Helper function to determine serialized fields.
   */
  protected function serializedFields() {
    // For now, build the list from the schema since it's in active development.
    if (empty($this->serializedFields)) {
      $schema = static::schemaDefinition();
      foreach ($schema['fields'] as $name => $field) {
        if (!empty($field['serialize'])) {
          $this->serializedFields[] = $name;
        }
      }
    }
    return $this->serializedFields;
  }

  /**
   * Helper function to determine fields that are part of the plugin definition.
   */
  protected function definitionFields() {
    return $this->definitionFields;
  }

  /**
   * Defines the schema for the tree table.
   */
  protected static function schemaDefinition() {
    $schema = array(
      'description' => 'Contains the menu tree hierarchy.',
      'fields' => array(
        'menu_name' => array(
          'description' => "The menu name. All links with the same menu name (such as 'tools') are part of the same menu.",
          'type' => 'varchar',
          'length' => 32,
          'not null' => TRUE,
          'default' => '',
        ),
        'mlid' => array(
          'description' => 'The menu link ID (mlid) is the integer primary key.',
          'type' => 'serial',
          'unsigned' => TRUE,
          'not null' => TRUE,
        ),
        'id' => array(
          'description' => 'Unique machine name: the plugin ID.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
        ),
        'parent' => array(
          'description' => 'The plugin ID for the parent of this link.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'route_name' => array(
          'description' => 'The machine name of a defined Symfony Route this menu item represents.',
          'type' => 'varchar',
          'length' => 255,
        ),
        'route_param_key' => array(
          'description' => 'An encoded string of route parameters for loading by route.',
          'type' => 'varchar',
          'length' => 255,
        ),
        'route_parameters' => array(
          'description' => 'Serialized array of route parameters of this menu link.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ),
        'url' => array(
          'description' => 'The external path this link points to (when not using a route).',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'title' => array(
          'description' => 'The text displayed for the link.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'title_arguments' => array(
          'description' => 'A serialized array of arguments to be passed to t() (if this plugin uses it).',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ),
        'title_context' => array(
          'description' => 'The translation context for the link title.',
          'type' => 'varchar',
          'length' => 255,
          'not null' => TRUE,
          'default' => '',
        ),
        'description' => array(
          'description' => 'The description of this link - used for admin pages and title attribute.',
          'type' => 'text',
          'not null' => FALSE,
        ),
        'class' => array(
          'description' => 'The class for this link plugin.',
          'type' => 'text',
          'not null' => FALSE,
        ),
        'options' => array(
          'description' => 'A serialized array of options to be passed to the url() or l() function, such as a query string or HTML attributes.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ),
        'provider' => array(
          'description' => 'The name of the module that generated this link.',
          'type' => 'varchar',
          'length' => DRUPAL_EXTENSION_NAME_MAX_LENGTH,
          'not null' => TRUE,
          'default' => 'system',
        ),
        'hidden' => array(
          'description' => 'A flag for whether the link should be rendered in menus. (1 = a disabled menu item that may be shown on admin screens, 0 = a normal, visible link)',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'discovered' => array(
          'description' => 'A flag for whether the link was discovered, so can be purged on rebuild',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'expanded' => array(
          'description' => 'Flag for whether this link should be rendered as expanded in menus - expanded links always have their child links displayed, instead of only when the link is in the active trail (1 = expanded, 0 = not expanded)',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'weight' => array(
          'description' => 'Link weight among links in the same menu at the same depth.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
        ),
        'metadata' => array(
          'description' => 'A serialized array of data that may be used by the plugin instance.',
          'type' => 'blob',
          'size' => 'big',
          'not null' => FALSE,
          'serialize' => TRUE,
        ),
        'has_children' => array(
          'description' => 'Flag indicating whether any non-hidden links have this link as a parent (1 = children exist, 0 = no children).',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'depth' => array(
          'description' => 'The depth relative to the top level. A link with empty parent will have depth == 1.',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 0,
          'size' => 'small',
        ),
        'p1' => array(
          'description' => 'The first mlid in the materialized path. If N = depth, then pN must equal the mlid. If depth > 1 then p(N-1) must equal the parent link mlid. All pX where X > depth must equal zero. The columns p1 .. p9 are also called the parents.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p2' => array(
          'description' => 'The second mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p3' => array(
          'description' => 'The third mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p4' => array(
          'description' => 'The fourth mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p5' => array(
          'description' => 'The fifth mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p6' => array(
          'description' => 'The sixth mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p7' => array(
          'description' => 'The seventh mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p8' => array(
          'description' => 'The eighth mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'p9' => array(
          'description' => 'The ninth mlid in the materialized path. See p1.',
          'type' => 'int',
          'unsigned' => TRUE,
          'not null' => TRUE,
          'default' => 0,
        ),
        'form_class' => array(
          'description' => 'meh',
          'type' => 'varchar',
          'length' => 255,
        ),
      ),
      'indexes' => array(
        'menu_parents' => array(
          'menu_name',
          'p1',
          'p2',
          'p3',
          'p4',
          'p5',
          'p6',
          'p7',
          'p8',
          'p9',
        ),
        // @todo - test this index for effectiveness.
        'menu_parent_expand_child' => array(
          'menu_name', 'expanded',
          'has_children',
          array('parent', 16),
        ),
        'route_values' => array(
          array('route_name', 32),
          array('route_param_key', 16),
        ),
      ),
      'primary key' => array('mlid'),
      'unique keys' => array(
        'id' => array('id'),
      ),
    );

    return $schema;
  }

}
