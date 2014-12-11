<?php

/**
 * @file
 * Contains \Drupal\Core\Menu\MenuTreeStorage.
 */

namespace Drupal\Core\Menu;

use Drupal\Component\Plugin\Exception\PluginException;
use Drupal\Component\Utility\String;
use Drupal\Component\Utility\UrlHelper;
use Drupal\Core\Cache\Cache;
use Drupal\Core\Cache\CacheBackendInterface;
use Drupal\Core\Database\Connection;
use Drupal\Core\Database\Database;
use Drupal\Core\Database\Query\SelectInterface;
use Drupal\Core\Database\SchemaObjectExistsException;

/**
 * Provides a menu tree storage using the database.
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
  protected $menuCacheBackend;

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
   * Stores definitions that have already been loaded for better performance.
   *
   * An array of plugin definition arrays, keyed by plugin ID.
   *
   * @var array
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
   * @todo Decide how to keep these field definitions in sync.
   *   https://www.drupal.org/node/2302085
   *
   * @see \Drupal\Core\Menu\MenuLinkManager::$defaults
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
    'enabled',
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
   * @param \Drupal\Core\Cache\CacheBackendInterface $menu_cache_backend
   *   Cache backend instance for the extracted tree data.
   * @param string $table
   *   A database table name to store configuration data in.
   * @param array $options
   *   (optional) Any additional database connection options to use in queries.
   */
  public function __construct(Connection $connection, CacheBackendInterface $menu_cache_backend, $table, array $options = array()) {
    $this->connection = $connection;
    $this->menuCacheBackend = $menu_cache_backend;
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
        // Flag this link as discovered, i.e. saved via rebuild().
        $link['discovered'] = 1;
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
    $result = $this->findNoLongerExistingLinks($definitions);

    // Remove all such items.
    if ($result) {
      $this->purgeMultiple($result);
    }
    $this->resetDefinitions();
    $affected_menus = $this->getMenuNames() + $before_menus;
    // Invalidate any cache tagged with any menu name.
    $cache_tags = Cache::buildTags('menu', $affected_menus);
    Cache::invalidateTags($cache_tags);
    $this->resetDefinitions();
    // Every item in the cache bin should have one of the menu cache tags but it
    // is not guaranteed, so invalidate everything in the bin.
    $this->menuCacheBackend->invalidateAll();
  }

  /**
   * Purges multiple menu links that no longer exist.
   *
   * @param array $ids
   *   An array of menu link IDs.
   */
  protected function purgeMultiple(array $ids) {
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
    $this->doDeleteMultiple($ids);
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
   *   Thrown if the table could not be created or the database connection
   *   failed.
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
    $affected_menus = $this->doSave($link);
    $this->resetDefinitions();
    $cache_tags = Cache::buildTags('menu', $affected_menus);
    Cache::invalidateTags($cache_tags);
    return $affected_menus;
  }

  /**
   * Saves a link without clearing caches.
   *
   * @param array $link
   *   A definition, according to $definitionFields, for a
   *   \Drupal\Core\Menu\MenuLinkInterface plugin.
   *
   * @return array
   *   The menu names affected by the save operation. This will be one menu
   *   name if the link is saved to the sane menu, or two if it is saved to a
   *   new menu.
   *
   * @throws \Exception
   *   Thrown if the storage back-end does not exist and could not be created.
   * @throws \Drupal\Component\Plugin\Exception\PluginException
   *   Thrown if the definition is invalid, for example, if the specified parent
   *   would cause the links children to be moved to greater than the maximum
   *   depth.
   */
  protected function doSave(array $link) {
    $original = $this->loadFull($link['id']);
    // @todo Should we just return here if the link values match the original
    //   values completely?
    //   https://www.drupal.org/node/2302137
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
    return $affected_menus;
  }

  /**
   * Fills in all the fields the database save needs, using the link definition.
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
    // Sort the route parameters so that the query string will be the same.
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
      // @todo We want to also check $original['has_children'] here, but that
      //   will be 0 even if there are children if those are not enabled.
      //   has_children is really just the rendering hint. So, we either need
      //   to define another column (has_any_children), or do the extra query.
      //   https://www.drupal.org/node/2302149
      if ($original) {
        $limit = $this->maxDepth() - $this->doFindChildrenRelativeDepth($original) - 1;
      }
      else {
        $limit = $this->maxDepth() - 1;
      }
      if ($parent['depth'] > $limit) {
        throw new PluginException(String::format('The link with ID @id or its children exceeded the maximum depth of @depth', array('@id' => $link['id'], '@depth' => $this->maxDepth())));
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

    // Cast Booleans to int, if needed.
    $fields['enabled'] = (int) $fields['enabled'];
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

      $this->doDeleteMultiple([$id]);

      $this->updateParentalStatus($item);
      // Many children may have moved.
      $this->resetDefinitions();
      Cache::invalidateTags(array('menu:' . $item['menu_name']));
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
   * Re-parents a link's children when the link itself is moved.
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
      // UPDATE assignments are generally evaluated from left to right".
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
   *   The link definition to find the parent of.
   * @param array|false $original
   *   The original link that might be used to find the parent if the parent
   *   is not set on the $link, or FALSE if the original could not be loaded.
   *
   * @return array|false
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
   * Sets has_children for the link's parent if it has visible children.
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
        ->condition('enabled', 1);

      $parent_has_children = ((bool) $query->execute()->fetchField()) ? 1 : 0;
      $this->connection->update($this->table, $this->options)
        ->fields(array('has_children' => $parent_has_children))
        ->condition('id', $link['parent'])
        ->execute();
    }
  }

  /**
   * Prepares a link by unserializing values and saving the definition.
   *
   * @param array $link
   *   The data loaded in the query.
   * @param bool $intersect
   *   If TRUE, filter out values that are not part of the actual definition.
   *
   * @return array
   *   The prepared link data.
   */
  protected function prepareLink(array $link, $intersect = FALSE) {
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
    $query = $this->connection->select($this->table, $this->options);
    $query->fields($this->table, $this->definitionFields());
    foreach ($properties as $name => $value) {
      if (!in_array($name, $this->definitionFields(), TRUE)) {
        $fields = implode(', ', $this->definitionFields());
        throw new \InvalidArgumentException(String::format('An invalid property name, @name was specified. Allowed property names are: @fields.', array('@name' => $name, '@fields' => $fields)));
      }
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
    // Sort the route parameters so that the query string will be the same.
    asort($route_parameters);
    // Since this will be urlencoded, it's safe to store and match against a
    // text field.
    // @todo Standardize an efficient way to load by route name and parameters
    //   in place of system path. https://www.drupal.org/node/2302139
    $param_key = $route_parameters ? UrlHelper::buildQuery($route_parameters) : '';
    $query = $this->connection->select($this->table, $this->options);
    $query->fields($this->table, $this->definitionFields());
    $query->condition('route_name', $route_name);
    $query->condition('route_param_key', $param_key);
    if ($menu_name) {
      $query->condition('menu_name', $menu_name);
    }
    // Make the ordering deterministic.
    $query->orderBy('depth');
    $query->orderBy('weight');
    $query->orderBy('id');
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
    $missing_ids = array_diff($ids, array_keys($this->definitions));

    if ($missing_ids) {
      $query = $this->connection->select($this->table, $this->options);
      $query->fields($this->table, $this->definitionFields());
      $query->condition('id', $missing_ids, 'IN');
      $loaded = $this->safeExecuteSelect($query)->fetchAllAssoc('id', \PDO::FETCH_ASSOC);
      foreach ($loaded as $id => $link) {
        $this->definitions[$id] = $this->prepareLink($link);
      }
    }
    return array_intersect_key($this->definitions, array_flip($ids));
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
   * Loads all table fields for multiple menu link definitions by ID.
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
    // @todo Consider making this dynamic based on static::MAX_DEPTH or from the
    //   schema if that is generated using static::MAX_DEPTH.
    //   https://www.drupal.org/node/2302043
    $subquery->fields($this->table, array('p1', 'p2', 'p3', 'p4', 'p5', 'p6', 'p7', 'p8', 'p9'));
    $subquery->condition('id', $id);
    $result = current($subquery->execute()->fetchAll(\PDO::FETCH_ASSOC));
    $ids = array_filter($result);
    if ($ids) {
      $query = $this->connection->select($this->table, $this->options);
      $query->fields($this->table, array('id'));
      $query->orderBy('depth', 'DESC');
      $query->condition('mlid', $ids, 'IN');
      // @todo Cache this result in memory if we find it is being used more
      //   than once per page load. https://www.drupal.org/node/2302185
      return $this->safeExecuteSelect($query)->fetchAllKeyed(0, 0);
    }
    return array();
  }

  /**
   * {@inheritdoc}
   */
  public function getExpanded($menu_name, array $parents) {
    // @todo Go back to tracking in state or some other way which menus have
    //   expanded links? https://www.drupal.org/node/2302187
    do {
      $query = $this->connection->select($this->table, $this->options);
      $query->fields($this->table, array('id'));
      $query->condition('menu_name', $menu_name);
      $query->condition('expanded', 1);
      $query->condition('has_children', 1);
      $query->condition('enabled', 1);
      $query->condition('parent', $parents, 'IN');
      $query->condition('id', $parents, 'NOT IN');
      $result = $this->safeExecuteSelect($query)->fetchAllKeyed(0, 0);
      $parents += $result;
    } while (!empty($result));
    return $parents;
  }

  /**
   * Saves menu links recursively.
   *
   * @param string $id
   *   The definition ID.
   * @param array $children
   *   An array of IDs of child links collected by parent ID.
   * @param array $links
   *   An array of all definitions keyed by ID.
   */
  protected function saveRecursive($id, &$children, &$links) {
    if (!empty($links[$id]['parent']) && empty($links[$links[$id]['parent']])) {
      // Invalid parent ID, so remove it.
      $links[$id]['parent'] = '';
    }
    $this->doSave($links[$id]);

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
  public function loadTreeData($menu_name, MenuTreeParameters $parameters) {
    // Build the cache ID; sort 'expanded' and 'conditions' to prevent duplicate
    // cache items.
    sort($parameters->expandedParents);
    sort($parameters->conditions);
    $tree_cid = "tree-data:$menu_name:" . serialize($parameters);
    $cache = $this->menuCacheBackend->get($tree_cid);
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
      $this->menuCacheBackend->set($tree_cid, $data, Cache::PERMANENT, array('menu:' . $menu_name));
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
   * @param \Drupal\Core\Menu\MenuTreeParameters $parameters
   *   The parameters to determine which menu links to be loaded into a tree.
   *   This method will set the absolute minimum depth, which is used in
   *   MenuTreeStorage::doBuildTreeData().
   *
   * @return array
   *   A flat array of menu links that are part of the menu. Each array element
   *   is an associative array of information about the menu link, containing
   *   the fields from the {menu_tree} table. This array must be ordered
   *   depth-first.
   */
  protected function loadLinks($menu_name, MenuTreeParameters $parameters) {
    $query = $this->connection->select($this->table, $this->options);
    $query->fields($this->table);

    // Allow a custom root to be specified for loading a menu link tree. If
    // omitted, the default root (i.e. the actual root, '') is used.
    if ($parameters->root !== '') {
      $root = $this->loadFull($parameters->root);

      // If the custom root does not exist, we cannot load the links below it.
      if (!$root) {
        return array();
      }

      // When specifying a custom root, we only want to find links whose
      // parent IDs match that of the root; that's how we ignore the rest of the
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

    if (!empty($parameters->expandedParents)) {
      $query->condition('parent', $parameters->expandedParents, 'IN');
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
   *   An array to accumulate definitions by reference.
   *
   * @return array
   *   Array of route names, with all values being unique.
   */
  protected function collectRoutesAndDefinitions(array $tree, array &$definitions) {
    return array_values($this->doCollectRoutesAndDefinitions($tree, $definitions));
  }

  /**
   * Collects all the route names and definitions.
   *
   * @param array $tree
   *   A menu link tree from MenuTreeStorage::doBuildTreeData()
   * @param array $definitions
   *   The collected definitions which are populated by reference.
   *
   * @return array
   *   The collected route names.
   */
  protected function doCollectRoutesAndDefinitions(array $tree, array &$definitions) {
    $route_names = array();
    foreach (array_keys($tree) as $id) {
      $definitions[$id] = $this->definitions[$id];
      if (!empty($definition['route_name'])) {
        $route_names[$definition['route_name']] = $definition['route_name'];
      }
      if ($tree[$id]['subtree']) {
        $route_names += $this->doCollectRoutesAndDefinitions($tree[$id]['subtree'], $definitions);
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
    $parameters = new MenuTreeParameters();
    $parameters->setRoot($id)->onlyEnabledLinks();
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
  public function loadAllChildren($id, $max_relative_depth = NULL) {
    $parameters = new MenuTreeParameters();
    $parameters->setRoot($id)->excludeRoot()->setMaxDepth($max_relative_depth)->onlyEnabledLinks();
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
   *   the fields from the $this->table. This array must be ordered
   *   depth-first. MenuTreeStorage::loadTreeData() includes a sample query.
   *
   * @param array $parents
   *   An array of the menu link ID values that are in the path from the current
   *   page to the root of the menu tree.
   * @param int $depth
   *   The minimum depth to include in the returned menu tree.
   *
   * @return array
   *   The fully built tree.
   *
   * @see \Drupal\Core\Menu\MenuTreeStorage::loadTreeData()
   */
  protected function treeDataRecursive(array &$links, array $parents, $depth) {
    $tree = array();
    while ($tree_link_definition = array_pop($links)) {
      $tree[$tree_link_definition['id']] = array(
        'definition' => $this->prepareLink($tree_link_definition, TRUE),
        'has_children' => $tree_link_definition['has_children'],
        // We need to determine if we're on the path to root so we can later
        // build the correct active trail.
        'in_active_trail' => in_array($tree_link_definition['id'], $parents),
        'subtree' => array(),
        'depth' => $tree_link_definition['depth'],
      );
      // Look ahead to the next link, but leave it on the array so it's
      // available to other recursive function calls if we return or build a
      // sub-tree.
      $next = end($links);
      // Check whether the next link is the first in a new sub-tree.
      if ($next && $next['depth'] > $depth) {
        // Recursively call doBuildTreeData to build the sub-tree.
        $tree[$tree_link_definition['id']]['subtree'] = $this->treeDataRecursive($links, $parents, $next['depth']);
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
   * Determines serialized fields in the storage.
   *
   * @return array
   *   A list of fields that are serialized in the database.
   */
  protected function serializedFields() {
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
   * Determines fields that are part of the plugin definition.
   *
   * @return array
   *   The list of the subset of fields that are part of the plugin definition.
   */
  protected function definitionFields() {
    return $this->definitionFields;
  }

  /**
   * Defines the schema for the tree table.
   *
   * @return array
   *   The schema API definition for the SQL storage table.
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
          'description' => 'A serialized array of options to be passed to the _url() or _l() function, such as a query string or HTML attributes.',
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
        'enabled' => array(
          'description' => 'A flag for whether the link should be rendered in menus. (0 = a disabled menu item that may be shown on admin screens, 1 = a normal, visible link)',
          'type' => 'int',
          'not null' => TRUE,
          'default' => 1,
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
          'description' => 'Flag indicating whether any enabled links have this link as a parent (1 = enabled children exist, 0 = no enabled children).',
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
        // @todo Test this index for effectiveness.
        //   https://www.drupal.org/node/2302197
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

  /**
   * Find any previously discovered menu links that no longer exist.
   *
   * @param array $definitions
   *   The new menu link definitions.
   * @return array
   *   A list of menu link IDs that no longer exist.
   */
  protected function findNoLongerExistingLinks(array $definitions) {
    if ($definitions) {
      $query = $this->connection->select($this->table, NULL, $this->options);
      $query->addField($this->table, 'id');
      $query->condition('discovered', 1);
      $query->condition('id', array_keys($definitions), 'NOT IN');
      // Starting from links with the greatest depth will minimize the amount
      // of re-parenting done by the menu storage.
      $query->orderBy('depth', 'DESC');
      $result = $query->execute()->fetchCol();
    }
    else {
      $result = array();
    }
    return $result;
  }

  /**
   * Purge menu links from the database.
   *
   * @param array $ids
   *   A list of menu link IDs to be purged.
   */
  protected function doDeleteMultiple(array $ids) {
    $this->connection->delete($this->table, $this->options)
      ->condition('id', $ids, 'IN')
      ->execute();
  }

}
