<?php
// $Id: views_plugin_query_default.inc,v 1.1.4.39 2011/01/06 00:37:05 dereine Exp $
/**
 * @file views_plugin_query_default.inc
 * Defines the default query object which builds SQL to execute using the
 * Drupal database API.
 */

/**
 * Object used to create a SELECT query.
 */
class views_plugin_query_default extends views_plugin_query {

  /**
   * A list of tables in the order they should be added, keyed by alias.
   */
  var $table_queue = array();

  /**
   * Holds an array of tables and counts added so that we can create aliases
   */
  var $tables = array();

  /**
   * Holds an array of relationships, which are aliases of the primary
   * table that represent different ways to join the same table in.
   */
  var $relationships = array();

  /**
   * An array of sections of the WHERE query. Each section is in itself
   * an array of pieces and a flag as to whether or not it should be AND
   * or OR.
   */
  var $where = array();
  /**
   * An array of sections of the HAVING query. Each section is in itself
   * an array of pieces and a flag as to whether or not it should be AND
   * or OR.
   */
  var $having = array();
  /**
   * The default operator to use when connecting the WHERE groups. May be
   * AND or OR.
   */
  var $group_operator = 'AND';

  /**
   * A simple array of order by clauses.
   */
  var $orderby = array();

  /**
   * A simple array of group by clauses.
   */
  var $groupby = array();

  /**
   * The table header to use for tablesort. This matters because tablesort
   * needs to modify the query and needs the header.
   */
  var $header = array();

  /**
   * A flag as to whether or not to make the primary field distinct.
   */
  var $distinct = FALSE;

  var $has_aggregate = FALSE;

  /**
   * Should this query be optimized for counts, for example no sorts.
   */
  var $get_count_optimized = NULL;

  /**
   * Constructor; Create the basic query object and fill with default values.
   */
  function init($base_table = 'node', $base_field = 'nid', $options) {
    parent::init($base_table, $base_field, $options);
    $this->base_table = $base_table;  // Predefine these above, for clarity.
    $this->base_field = $base_field;
    $this->relationships[$base_table] = array(
      'link' => NULL,
      'table' => $base_table,
      'alias' => $base_table,
      'base' => $base_table
    );

    // init the table queue with our primary table.
    $this->table_queue[$base_table] = array(
      'alias' => $base_table,
      'table' => $base_table,
      'relationship' => $base_table,
      'join' => NULL,
    );

    // init the tables with our primary table
    $this->tables[$base_table][$base_table] = array(
      'count' => 1,
      'alias' => $base_table,
    );

/**
 * -- we no longer want the base field to appear automatigically.
    if ($base_field) {
      $this->fields[$base_field] = array(
        'table' => $base_table,
        'field' => $base_field,
        'alias' => $base_field,
      );
    }
 */

    $this->count_field = array(
      'table' => $base_table,
      'field' => $base_field,
      'alias' => $base_field,
      'count' => TRUE,
    );
  }

  // ----------------------------------------------------------------
  // Utility methods to set flags and data.

  /**
   * Set the base field to be distinct.
   */
  function set_distinct($value = TRUE) {
    if (!(isset($this->no_distinct) && $value)) {
      $this->distinct = $value;
    }
  }

  /**
   * Set what field the query will count() on for paging.
   */
  function set_count_field($table, $field, $alias = NULL) {
    if (empty($alias)) {
      $alias = $table . '_' . $field;
    }
    $this->count_field = array(
      'table' => $table,
      'field' => $field,
      'alias' => $alias,
      'count' => TRUE,
    );
  }

  /**
   * Set the table header; used for click-sorting because it's needed
   * info to modify the ORDER BY clause.
   */
  function set_header($header) {
    $this->header = $header;
  }

  function option_definition() {
    $options = parent::option_definition();
    $options['disable_sql_rewrite'] = array(
      'default' => FALSE,
      'translatable' => FALSE,
      'bool' => TRUE,
    );
    $options['distinct'] = array(
      'default' => FALSE,
      'bool' => TRUE,
    );
    $options['slave'] = array(
      'default' => FALSE,
      'bool' => TRUE,
    );
    $options['field_language'] = array(
      'default' => '***CURRENT_LANGUAGE***',
    );

    return $options;
  }

  /**
   * Add settings for the ui.
   */
  function options_form(&$form, &$form_state) {
    $form['disable_sql_rewrite'] = array(
      '#title' => t('Disable SQL rewriting'),
      '#description' => t('Disabling SQL rewriting will disable node_access checks as well as other modules that implement hook_db_rewrite_sql().'),
      '#type' => 'checkbox',
      '#default_value' => !empty($this->options['disable_sql_rewrite']),
      '#prefix' =>  '<div class="messages warning">' . t('WARNING: Disabling SQL rewriting means that node access security is disabled. This may allow users to see data they should not be able to see if your view is misconfigured. Please use this option only if you understand and accept this security risk.') . '</div>',
    );
    $form['distinct'] = array(
      '#type' => 'checkbox',
      '#title' => t('Distinct'),
      '#description' => t('This will make the view display only distinct items. If there are multiple identical items, each will be displayed only once. You can use this to try and remove duplicates from a view, though it does not always work. Note that this can slow queries down, so use it with caution.'),
      '#default_value' => !empty($this->options['distinct']),
    );
    $form['slave'] = array(
      '#type' => 'checkbox',
      '#title' => t('Use Slave Server'),
      '#description' => t('This will make the query attempt to connect to a slave server if available.  If no slave server is defined or available, it will fall back to the default server.'),
      '#default_value' => !empty($this->options['slave']),
    );

    $entities = entity_get_info();
    $entity_tables = array();
    $has_translation_handlers = FALSE;
    foreach ($entities as $type => $entity_info) {
      $entity_tables[] = $entity_info['base table'];

      if (!empty($entity_info['translation'])) {
        $has_translation_handlers = TRUE;
      }
    }

    // Doesn't make sense to show a field setting here if we aren't querying
    // an entity base table. Also, we make sure that there's at least one
    // entity type with a translation handler attached.
    if (in_array($this->base_table, $entity_tables) && $has_translation_handlers) {
      $languages = array(
        '***CURRENT_LANGUAGE***' => t("Current user's language"),
        '***DEFAULT_LANGUAGE***' => t("Default site language"),
        LANGUAGE_NONE => t('No language')
      );
      $languages = array_merge($languages, locale_language_list());

      $form['field_language'] = array(
        '#type' => 'select',
        '#title' => t('Field Language'),
        '#description' => t('All fields which support translations will be displayed in the selected language.'),
        '#options' => $languages,
        '#default_value' => $this->options['field_language'],
      );
    }
  }

  // ----------------------------------------------------------------
  // Table/join adding

  /**
   * A relationship is an alternative endpoint to a series of table
   * joins. Relationships must be aliases of the primary table and
   * they must join either to the primary table or to a pre-existing
   * relationship.
   *
   * An example of a relationship would be a nodereference table.
   * If you have a nodereference named 'book_parent' which links to a
   * parent node, you could set up a relationship 'node_book_parent'
   * to 'node'. Then, anything that links to 'node' can link to
   * 'node_book_parent' instead, thus allowing all properties of
   * both nodes to be available in the query.
   *
   * @param $alias
   *   What this relationship will be called, and is also the alias
   *   for the table.
   * @param $join
   *   A views_join object (or derived object) to join the alias in.
   * @param $base
   *   The name of the 'base' table this relationship represents; this
   *   tells the join search which path to attempt to use when finding
   *   the path to this relationship.
   * @param $link_point
   *   If this relationship links to something other than the primary
   *   table, specify that table here. For example, a 'track' node
   *   might have a relationship to an 'album' node, which might
   *   have a relationship to an 'artist' node.
   */
  function add_relationship($alias, $join, $base, $link_point = NULL) {
    if (empty($link_point)) {
      $link_point = $this->base_table;
    }
    elseif (!array_key_exists($link_point, $this->relationships)) {
      return FALSE;
    }

    // Make sure $alias isn't already used; if it, start adding stuff.
    $alias_base = $alias;
    $count = 1;
    while (!empty($this->relationships[$alias])) {
      $alias = $alias_base . '_' . $count++;
    }

    // Make sure this join is adjusted for our relationship.
    if ($link_point && isset($this->relationships[$link_point])) {
      $join = $this->adjust_join($join, $link_point);
    }

    // Add the table directly to the queue to avoid accidentally marking
    // it.
    $this->table_queue[$alias] = array(
      'table' => $join->table,
      'num' => 1,
      'alias' => $alias,
      'join' => $join,
      'relationship' => $link_point,
    );

    $this->relationships[$alias] = array(
      'link' => $link_point,
      'table' => $join->table,
      'base' => $base,
    );

    $this->tables[$this->base_table][$alias] = array(
      'count' => 1,
      'alias' => $alias,
    );

    return $alias;
  }

  /**
   * Add a table to the query, ensuring the path exists.
   *
   * This function will test to ensure that the path back to the primary
   * table is valid and exists; if you do not wish for this testing to
   * occur, use $query->queue_table() instead.
   *
   * @param $table
   *   The name of the table to add. It needs to exist in the global table
   *   array.
   * @param $relationship
   *   An alias of a table; if this is set, the path back to this table will
   *   be tested prior to adding the table, making sure that all intermediary
   *   tables exist and are properly aliased. If set to NULL the path to
   *   the primary table will be ensured. If the path cannot be made, the
   *   table will NOT be added.
   * @param $join
   *   In some join configurations this table may actually join back through
   *   a different method; this is most likely to be used when tracing
   *   a hierarchy path. (node->parent->parent2->parent3). This parameter
   *   will specify how this table joins if it is not the default.
   * @param $alias
   *   A specific alias to use, rather than the default alias.
   *
   * @return $alias
   *   The alias of the table; this alias can be used to access information
   *   about the table and should always be used to refer to the table when
   *   adding parts to the query. Or FALSE if the table was not able to be
   *   added.
   */
  function add_table($table, $relationship = NULL, $join = NULL, $alias = NULL) {
    if (!$this->ensure_path($table, $relationship, $join)) {
      return FALSE;
    }

    return $this->queue_table($table, $relationship, $this->adjust_join($join, $relationship), $alias);
  }

  /**
   * Add a table to the query, without ensuring the path.
   *
   * This function will test to ensure that the path back to the primary
   * table is valid and exists; if you do not wish for this testing to
   * occur, use $query->queue_table() instead.
   *
   * @param $table
   *   The name of the table to add. It needs to exist in the global table
   *   array.
   * @param $relationship
   *   The primary table alias this table is related to. If not set, the
   *   primary table will be used.
   * @param $join
   *   In some join configurations this table may actually join back through
   *   a different method; this is most likely to be used when tracing
   *   a hierarchy path. (node->parent->parent2->parent3). This parameter
   *   will specify how this table joins if it is not the default.
   * @param $alias
   *   A specific alias to use, rather than the default alias.
   *
   * @return $alias
   *   The alias of the table; this alias can be used to access information
   *   about the table and should always be used to refer to the table when
   *   adding parts to the query. Or FALSE if the table was not able to be
   *   added.
   */
  function queue_table($table, $relationship = NULL, $join = NULL, $alias = NULL) {
    // If the alias is set, make sure it doesn't already exist.
    if (isset($this->table_queue[$alias])) {
      return $alias;
    }

    if (empty($relationship)) {
      $relationship = $this->base_table;
    }

    if (!array_key_exists($relationship, $this->relationships)) {
      return FALSE;
    }

    if (!$alias && $join && $relationship && !empty($join->adjusted) && $table != $join->table) {
      if ($relationship == $this->base_table) {
        $alias = $table;
      }
      else {
        $alias = $relationship . '_' . $table;
      }
    }

    // Check this again to make sure we don't blow up existing aliases for already
    // adjusted joins.
    if (isset($this->table_queue[$alias])) {
      return $alias;
    }

    $alias = $this->mark_table($table, $relationship, $alias);

    // If no alias is specified, give it the default.
    if (!isset($alias)) {
      $alias = $this->tables[$relationship][$table]['alias'] . $this->tables[$relationship][$table]['count'];
    }

    // If this is a relationship based table, add a marker with
    // the relationship as a primary table for the alias.
    if ($table != $alias) {
      $this->mark_table($alias, $this->base_table, $alias);
    }

    // If no join is specified, pull it from the table data.
    if (!isset($join)) {
      $join = $this->get_join_data($table, $this->relationships[$relationship]['base']);
      if (empty($join)) {
        return FALSE;
      }

      $join = $this->adjust_join($join, $relationship);
    }

    $this->table_queue[$alias] = array(
      'table' => $table,
      'num' => $this->tables[$relationship][$table]['count'],
      'alias' => $alias,
      'join' => $join,
      'relationship' => $relationship,
    );

    return $alias;
  }

  function mark_table($table, $relationship, $alias) {
    // Mark that this table has been added.
    if (empty($this->tables[$relationship][$table])) {
      if (!isset($alias)) {
        $alias = '';
        if ($relationship != $this->base_table) {
          // double underscore will help prevent accidental name
          // space collisions.
          $alias = $relationship . '__';
        }
        $alias .= $table;
      }
      $this->tables[$relationship][$table] = array(
        'count' => 1,
        'alias' => $alias,
      );
    }
    else {
      $this->tables[$relationship][$table]['count']++;
    }

    return $alias;
  }

  /**
   * Ensure a table exists in the queue; if it already exists it won't
   * do anything, but if it doesn't it will add the table queue. It will ensure
   * a path leads back to the relationship table.
   *
   * @param $table
   *   The unaliased name of the table to ensure.
   * @param $relationship
   *   The relationship to ensure the table links to. Each relationship will
   *   get a unique instance of the table being added. If not specified,
   *   will be the primary table.
   * @param $join
   *   A views_join object (or derived object) to join the alias in.
   *
   * @return
   *   The alias used to refer to this specific table, or NULL if the table
   *   cannot be ensured.
   */
  function ensure_table($table, $relationship = NULL, $join = NULL) {
    // ensure a relationship
    if (empty($relationship)) {
      $relationship = $this->base_table;
    }

    // If the relationship is the primary table, this actually be a relationship
    // link back from an alias. We store all aliases along with the primary table
    // to detect this state, because eventually it'll hit a table we already
    // have and that's when we want to stop.
    if ($relationship == $this->base_table && !empty($this->tables[$relationship][$table])) {
      return $this->tables[$relationship][$table]['alias'];
    }

    if (!array_key_exists($relationship, $this->relationships)) {
      return FALSE;
    }

    if ($table == $this->relationships[$relationship]['base']) {
      return $relationship;
    }

    // If we do not have join info, fetch it.
    if (!isset($join)) {
      $join = $this->get_join_data($table, $this->relationships[$relationship]['base']);
    }

    // If it can't be fetched, this won't work.
    if (empty($join)) {
      return;
    }

    // Adjust this join for the relationship, which will ensure that the 'base'
    // table it links to is correct. Tables adjoined to a relationship
    // join to a link point, not the base table.
    $join = $this->adjust_join($join, $relationship);

    if ($this->ensure_path($table, $relationship, $join)) {
      // Attempt to eliminate redundant joins.  If this table's
      // relationship and join exactly matches an existing table's
      // relationship and join, we do not have to join to it again;
      // just return the existing table's alias.  See
      // http://groups.drupal.org/node/11288 for details.
      //
      // This can be done safely here but not lower down in
      // queue_table(), because queue_table() is also used by
      // add_table() which requires the ability to intentionally add
      // the same table with the same join multiple times.  For
      // example, a view that filters on 3 taxonomy terms using AND
      // needs to join taxonomy_term_data 3 times with the same join.

      // scan through the table queue to see if a matching join and
      // relationship exists.  If so, use it instead of this join.

      // TODO: Scanning through $this->table_queue results in an
      // O(N^2) algorithm, and this code runs every time the view is
      // instantiated (Views 2 does not currently cache queries).
      // There are a couple possible "improvements" but we should do
      // some performance testing before picking one.
      foreach ($this->table_queue as $queued_table) {
        // In PHP 4 and 5, the == operation returns TRUE for two objects
        // if they are instances of the same class and have the same
        // attributes and values.
        if ($queued_table['relationship'] == $relationship && $queued_table['join'] == $join) {
          return $queued_table['alias'];
        }
      }

      return $this->queue_table($table, $relationship, $join);
    }
  }

  /**
   * Make sure that the specified table can be properly linked to the primary
   * table in the JOINs. This function uses recursion. If the tables
   * needed to complete the path back to the primary table are not in the
   * query they will be added, but additional copies will NOT be added
   * if the table is already there.
   */
  function ensure_path($table, $relationship = NULL, $join = NULL, $traced = array(), $add = array()) {
    if (!isset($relationship)) {
      $relationship = $this->base_table;
    }

    if (!array_key_exists($relationship, $this->relationships)) {
      return FALSE;
    }

    // If we do not have join info, fetch it.
    if (!isset($join)) {
      $join = $this->get_join_data($table, $this->relationships[$relationship]['base']);
    }

    // If it can't be fetched, this won't work.
    if (empty($join)) {
      return FALSE;
    }

    // Does a table along this path exist?
    if (isset($this->tables[$relationship][$table]) ||
      ($join && $join->left_table == $relationship) ||
      ($join && $join->left_table == $this->relationships[$relationship]['table'])) {

      // Make sure that we're linking to the correct table for our relationship.
      foreach (array_reverse($add) as $table => $path_join) {
        $this->queue_table($table, $relationship, $this->adjust_join($path_join, $relationship));
      }
      return TRUE;
    }

    // Have we been this way?
    if (isset($traced[$join->left_table])) {
      // we looped. Broked.
      return FALSE;
    }

    // Do we have to add this table?
    $left_join = $this->get_join_data($join->left_table, $this->relationships[$relationship]['base']);
    if (!isset($this->tables[$relationship][$join->left_table])) {
      $add[$join->left_table] = $left_join;
    }

    // Keep looking.
    $traced[$join->left_table] = TRUE;
    return $this->ensure_path($join->left_table, $relationship, $left_join, $traced, $add);
  }

  /**
   * Fix a join to adhere to the proper relationship; the left table can vary
   * based upon what relationship items are joined in on.
   */
  function adjust_join($join, $relationship) {
    if (!empty($join->adjusted)) {
      return $join;
    }

    if (empty($relationship) || empty($this->relationships[$relationship])) {
      return $join;
    }

    // Adjusts the left table for our relationship.
    if ($relationship != $this->base_table) {
      // If we're linking to the primary table, the relationship to use will
      // be the prior relationship. Unless it's a direct link.

      // Safety! Don't modify an original here.
      $join = clone $join;

      // Do we need to try to ensure a path?
      if ($join->left_table != $this->relationships[$relationship]['table'] &&
          $join->left_table != $this->relationships[$relationship]['base'] &&
          !isset($this->tables[$relationship][$join->left_table]['alias'])) {
        $this->ensure_table($join->left_table, $relationship);
      }

      // First, if this is our link point/anchor table, just use the relationship
      if ($join->left_table == $this->relationships[$relationship]['table']) {
        $join->left_table = $relationship;
      }
      // then, try the base alias.
      elseif (isset($this->tables[$relationship][$join->left_table]['alias'])) {
        $join->left_table = $this->tables[$relationship][$join->left_table]['alias'];
      }
      // But if we're already looking at an alias, use that instead.
      elseif (isset($this->table_queue[$relationship]['alias'])) {
        $join->left_table = $this->table_queue[$relationship]['alias'];
      }
    }

    $join->adjusted = TRUE;
    return $join;
  }

  /**
   * Retrieve join data from the larger join data cache.
   *
   * @param $table
   *   The table to get the join information for.
   * @param $base_table
   *   The path we're following to get this join.
   *
   * @return
   *   A views_join object or child object, if one exists.
   */
  function get_join_data($table, $base_table) {
    // Check to see if we're linking to a known alias. If so, get the real
    // table's data instead.
    if (!empty($this->table_queue[$table])) {
      $table = $this->table_queue[$table]['table'];
    }
    return views_get_table_join($table, $base_table);
  }

  /**
   * Get the information associated with a table.
   *
   * If you need the alias of a table with a particular relationship, use
   * ensure_table().
   */
  function get_table_info($table) {
    if (!empty($this->table_queue[$table])) {
      return $this->table_queue[$table];
    }

    // In rare cases we might *only* have aliased versions of the table.
    if (!empty($this->tables[$this->base_table][$table])) {
      $alias = $this->tables[$this->base_table][$table]['alias'];
      if (!empty($this->table_queue[$alias])) {
        return $this->table_queue[$alias];
      }
    }
  }

  /**
   * Add a field to the query table, possibly with an alias. This will
   * automatically call ensure_table to make sure the required table
   * exists, *unless* $table is unset.
   *
   * @param $table
   *   The table this field is attached to. If NULL, it is assumed this will
   *   be a formula; otherwise, ensure_table is used to make sure the
   *   table exists.
   * @param $field
   *   The name of the field to add. This may be a real field or a formula.
   * @param $alias
   *   The alias to create. If not specified, the alias will be $table_$field
   *   unless $table is NULL. When adding formulae, it is recommended that an
   *   alias be used.
   * @param $params
   *   An array of parameters additional to the field that will control items
   *   such as aggregation functions and DISTINCT.
   *
   * @return $name
   *   The name that this field can be referred to as. Usually this is the alias.
   */
  function add_field($table, $field, $alias = '', $params = array()) {
    // We check for this specifically because it gets a special alias.
    if ($table == $this->base_table && $field == $this->base_field && empty($alias)) {
      $alias = $this->base_field;
    }

    if ($table && empty($this->table_queue[$table])) {
      $this->ensure_table($table);
    }

    if (!$alias && $table) {
      $alias = $table . '_' . $field;
    }

    // Make sure an alias is assigned
    $alias = $alias ? $alias : $field;

    // PostgreSQL truncates aliases to 63 characters: http://drupal.org/node/571548

    // We limit the length of the original alias up to 60 characters
    // to get a unique alias later if its have duplicates
    $alias = substr($alias, 0, 60);

    // Create a field info array.
    $field_info = array(
      'field' => $field,
      'table' => $table,
      'alias' => $alias,
    ) + $params;

    // Test to see if the field is actually the same or not. Due to
    // differing parameters changing the aggregation function, we need
    // to do some automatic alias collision detection:
    $base = $alias;
    $counter = 0;
    while (!empty($this->fields[$alias]) && $this->fields[$alias] != $field_info) {
      $field_info['alias'] = $alias = $base . '_' . ++$counter;
    }

    if (empty($this->fields[$alias])) {
      $this->fields[$alias] = $field_info;
    }

    return $alias;
  }

  /**
   * Remove all fields that may've been added; primarily used for summary
   * mode where we're changing the query because we didn't get data we needed.
   */
  function clear_fields() {
    $this->fields = array();
  }

  /**
   * Add a simple WHERE clause to the query. The caller is responsible for
   * ensuring that all fields are fully qualified (TABLE.FIELD) and that
   * the table already exists in the query.
   *
   * @param $group
   *   The WHERE group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $field
   *   The name of the field to check.
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar. For more
   *   complex options, it is an array. The meaning of each element in the array is
   *   dependent on the $operator.
   * @param $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more complex
   *   options such as IN, LIKE, or BETWEEN. Defaults to IN if $value is an array
   *   = otherwise. If $field is a string you have to use 'formula' here.
   *
   * @see QueryConditionInterface::condition()
   */
  function add_where($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->set_where_group('AND', $group);
    }

    $this->where[$group]['conditions'][] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    );
  }

  /**
   * Add a complex WHERE clause to the query.
   *
   * The caller is reponsible for ensuring that all fields are fully qualified
   * (TABLE.FIELD) and that the table already exists in the query.
   * Internally the dbtng method "where" is used.
   *
   * @param $group
   *   The WHERE group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $snippet
   *   The snippet to check. This can be either a column or
   *   a complex expression like "UPPER(table.field) = 'value'"
   * @param $args
   *   An associative array of arguments.
   *
   * @see QueryConditionInterface::where()
   */
  function add_where_expression($group, $snippet, $args = array()) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->where[$group])) {
      $this->set_where_group('AND', $group);
    }

    $this->where[$group]['conditions'][] = array(
      'field' => $snippet,
      'value' => $args,
      'operator' => 'formula',
    );
  }

  /**
   * Add a simple HAVING clause to the query.
   *
   * The caller is responsible for ensuring that all fields are fully qualified
   * (TABLE.FIELD) and that the table and an appropriate GROUP BY already exist in the query.
   * Internally the dbtng method "havingCondition" is used.
   *
   * @param $group
   *   The HAVING group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $field
   *   The name of the field to check.
   * @param $value
   *   The value to test the field against. In most cases, this is a scalar. For more
   *   complex options, it is an array. The meaning of each element in the array is
   *   dependent on the $operator.
   * @param $operator
   *   The comparison operator, such as =, <, or >=. It also accepts more complex
   *   options such as IN, LIKE, or BETWEEN. Defaults to IN if $value is an array
   *   = otherwise.  If $field is a string you have to use 'formula' here.
   *
   * @see SelectQueryInterface::havingCondition()
   */
  function add_having($group, $field, $value = NULL, $operator = NULL) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->having[$group])) {
      $this->set_where_group('AND', $group, 'having');
    }

    // Add the clause and the args.
    $this->having[$group]['conditions'][] = array(
      'field' => $field,
      'value' => $value,
      'operator' => $operator,
    );
  }

  /**
   * Add a complex HAVING clause to the query.
   * The caller is responsible for ensuring that all fields are fully qualified
   * (TABLE.FIELD) and that the table and an appropriate GROUP BY already exist in the query.
   * Internally the dbtng method "having" is used.
   *
   * @param $group
   *   The HAVING group to add these to; groups are used to create AND/OR
   *   sections. Groups cannot be nested. Use 0 as the default group.
   *   If the group does not yet exist it will be created as an AND group.
   * @param $snippet
   *   The snippet to check. This can be either a column or
   *   a complex expression like "COUNT(table.field) > 3"
   * @param $args
   *   An associative array of arguments.
   *
   * @see QueryConditionInterface::having()
   */
  function add_having_expression($group, $snippet, $args = array()) {
    // Ensure all variants of 0 are actually 0. Thus '', 0 and NULL are all
    // the default group.
    if (empty($group)) {
      $group = 0;
    }

    // Check for a group.
    if (!isset($this->having[$group])) {
      $this->set_where_group('AND', $group, 'having');
    }

    // Add the clause and the args.
    $this->having[$group]['conditions'][] = array(
      'field' => $snippet,
      'value' => $args,
      'operator' => 'formula',
    );
  }

  /**
   * Add an ORDER BY clause to the query.
   *
   * @param $table
   *   The table this field is part of. If a formula, enter NULL.
   * @param $field
   *   The field or formula to sort on. If already a field, enter NULL
   *   and put in the alias.
   * @param $order
   *   Either ASC or DESC.
   * @param $alias
   *   The alias to add the field as. In SQL, all fields in the order by
   *   must also be in the SELECT portion. If an $alias isn't specified
   *   one will be generated for from the $field; however, if the
   *   $field is a formula, this alias will likely fail.
   * @param $params
   *   Any params that should be passed through to the add_field.
   */
  function add_orderby($table, $field, $order, $alias = '', $params = array()) {
    if ($table) {
      $this->ensure_table($table);
    }

    // Only fill out this aliasing if there is a table;
    // otherwise we assume it is a formula.
    if (!$alias && $table) {
      $as = $table . '_' . $field;
    }
    else {
      $as = $alias;
    }

    if ($field) {
      $as = $this->add_field($table, $field, $as, $params);
    }

    $this->orderby[] = array(
      'field' => $as,
      'direction' => strtoupper($order)
    );

	/**
 	 * -- removing, this should be taken care of by field adding now.
     * -- leaving commented because I am unsure.
      // If grouping, all items in the order by must also be in the
      // group by clause. Check $table to ensure that this is not a
      // formula.
      if ($this->groupby && $table) {
        $this->add_groupby($as);
      }
    */
  }

  /**
   * Add a simple GROUP BY clause to the query. The caller is responsible
   * for ensuring that the fields are fully qualified and the table is properly
   * added.
   */
  function add_groupby($clause) {
    // Only add it if it's not already in there.
    if (!in_array($clause, $this->groupby)) {
      $this->groupby[] = $clause;
    }
  }

  /**
   * Generates a unique placeholder used in the db query.
   */
  function placeholder($base = 'views') {
    static $placeholders = array();
    if (!isset($placeholders[$base])) {
      $placeholders[$base] = 0;
      return ':' . $base;
    }
    else {
      return ':' . $base . ++$placeholders[$base];
    }
  }

  /**
   * Construct the "WHERE" or "HAVING" part of the query.
   *
   * @param $where
   *   'where' or 'having'.
   */
  function build_condition($where = 'where') {
    $has_condition = FALSE;
    $main_group = $this->group_operator == 'OR' ? db_or() : db_and();
    foreach ($this->$where as $group => $info) {
      $sub_group = $info['type'] == 'OR' ? db_or() : db_and();
      foreach ($info['conditions'] as $key => $clause) {
        if ($clause['operator'] == 'formula') {
          $has_condition = TRUE;
          $sub_group->where($clause['field'], $clause['value']);
        }
        else {
          $has_condition = TRUE;
          $sub_group->condition($clause['field'], $clause['value'], $clause['operator']);
        }
      }
      $main_group->condition($sub_group);
    }
    if ($has_condition) {
      return $main_group;
    }
  }

  /**
   * Build fields array.
   */
  function compile_fields($fields_array, $query) {
    $non_aggregates = array();
    foreach ($fields_array as $field) {
      $string = '';
      if (!empty($field['table'])) {
        $string .= $field['table'] . '.';
      }
      $string .= $field['field'];
      $fieldname = (!empty($field['alias']) ? $field['alias'] : $string);

      if (!empty($field['distinct'])) {
        throw new Exception("Column-level distinct is not supported anymore.");
      }

      if (!empty($field['count'])) {
        // Retained for compatibility
        $field['function'] = 'count';
        // It seems there's no way to abstract the table+column reference
        // without adding a field, aliasing, and then using the alias.
      }

      if (!empty($field['function'])) {
        $info = $this->get_aggregation_info();
        if (!empty($info[$field['function']]['method']) && function_exists($info[$field['function']]['method'])) {
          $string = $info[$field['function']]['method']($field['function'], $string);
          $placeholders = !empty($field['placeholders']) ? $field['placeholders'] : array();
          $query->addExpression($string, $fieldname, $placeholders);
        }

        $this->has_aggregate = TRUE;
      }
      // This is a formula, using no tables.
      elseif (empty($field['table'])) {
        $placeholders = !empty($field['placeholders']) ? $field['placeholders'] : array();
        $query->addExpression($string, $fieldname, $placeholders);
      }

      elseif ($this->distinct && !in_array($fieldname, $this->groupby)) {
        // d7cx: This code was there, apparently needed for PostgreSQL
        // $string = db_driver() == 'pgsql' ? "FIRST($string)" : $string;
        $query->addField(!empty($field['table']) ? $field['table'] : $this->base_table, $field['field'], $fieldname);
      }
      elseif (empty($field['aggregate'])) {
        $non_aggregates[] = $fieldname;
        $query->addField(!empty($field['table']) ? $field['table'] : $this->base_table, $field['field'], $fieldname);
      }

      // @TODO Remove this old code.
      if (!empty($field['distinct']) && empty($field['function'])) {
        $distinct[] = $string;
      }
      else {
        $fields[] = $string;
      }

      if ($this->get_count_optimized) {
        // We only want the first field in this case.
        break;
      }
    }
    return array(
      $non_aggregates,
    );
  }

  /**
   * Generate a query and a countquery from all of the information supplied
   * to the object.
   *
   * @param $get_count
   *   Provide a countquery if this is true, otherwise provide a normal query.
   */
  function query($get_count = FALSE) {
    // Check query distinct value.
    if (empty($this->no_distinct) && $this->distinct && !empty($this->fields)) {
      $base_field_alias = $this->add_field($this->base_table, $this->base_field);
      $this->add_groupby($base_field_alias);
    }

    /**
     * An optimized count query includes just the base field instead of all the fields.
     * Determine of this query qualifies by checking for a groupby or distinct.
     */
    $fields_array = $this->fields;
    if ($get_count && !$this->groupby) {
      foreach ($fields_array as $field) {
        if (!empty($field['distinct']) || !empty($field['function'])) {
          $this->get_count_optimized = FALSE;
          break;
        }
      }
    }
    else {
      $this->get_count_optimized = FALSE;
    }
    if (!isset($this->get_count_optimized)) {
      $this->get_count_optimized = TRUE;
    }

    $options = array();
    // Detect an external database.
    if (isset($this->view->base_database)) {
      $options['target'] = $this->view->base_database;
    }

    // Set the slave target if the slave option is set
    if (!empty($this->options['slave'])) {
      $options['target'] = 'slave';
    }

    // Go ahead and build the query.
    $query = db_select($this->base_table, $this->base_table, $options)->addTag('views');

    $joins = $where = $having = $orderby = $groupby = '';
    $fields = $distinct = array();

    // Add all the tables to the query via joins. We assume all LEFT joins.
    foreach ($this->table_queue as $table) {
      if (is_object($table['join'])) {
        $table['join']->build_join($query, $table, $this);
      }
    }

    $this->has_aggregate = FALSE;
    $non_aggregates = array();

    list($non_aggregates) = $this->compile_fields($fields_array, $query);

    if (count($this->having)) {
      $this->has_aggregate = TRUE;
    }
    if ($this->has_aggregate || $this->groupby) {
      $groupby = array_unique(array_merge($this->groupby, $non_aggregates));
      foreach ($groupby as $field) {
        $query->groupBy($field);
      }
      if (!empty($this->having) && $condition = $this->build_condition('having')) {
        $query->havingCondition($condition);
      }
    }

    if (!$this->get_count_optimized) {
      // we only add the orderby if we're not counting.
      if ($this->orderby) {
        foreach ($this->orderby as $order) {
          $query->orderBy($order['field'], $order['direction']);
        }
      }
    }

    if (!empty($this->where) && $condition = $this->build_condition('where')) {
      $query->condition($condition);
    }

    // Add all query substitutions as metadata.
    $query->addMetaData('views_substitutions', module_invoke_all('views_query_substitutions', $this));

    return $query;
  }

  /**
   * Get the arguments attached to the WHERE and HAVING clauses of this query.
   */
  function get_where_args() {
    $args = array();
    foreach ($this->where as $group => $where) {
      $args = array_merge($args, $where['args']);
    }
    foreach ($this->having as $group => $having) {
      $args = array_merge($args, $having['args']);
    }
    return $args;
  }

  /**
   * Let modules modify the query just prior to finalizing it.
   */
  function alter(&$view) {
    foreach (module_implements('views_query_alter') as $module) {
      $function = $module . '_views_query_alter';
      $function($view, $this);
    }
  }

  /**
   * Builds the necessary info to execute the query.
   */
  function build(&$view) {
    // Make the query distinct if the option was set.
    if (!empty($this->options['distinct'])) {
      $this->set_distinct();
    }

    $view->init_pager();

    // Let the pager modify the query to add limits.
    $this->pager->query();

    $view->build_info['query'] = $this->query();
    $view->build_info['count_query'] = $this->query(TRUE);
  }

  /**
   * Executes the query and fills the associated view object with according
   * values.
   *
   * Values to set: $view->result, $view->total_rows, $view->execute_time,
   * $view->current_page.
   */
  function execute(&$view) {
    $external = FALSE; // Whether this query will run against an external database.
    $query = $view->build_info['query'];
    $count_query = $view->build_info['count_query'];

    $query->addMetaData('view', $view);
    $count_query->addMetaData('view', $view);

    if (empty($this->options['disable_sql_rewrite'])) {
      $base_table_data = views_fetch_data($this->base_table);
      if (isset($base_table_data['table']['base']['access query tag'])) {
        $access_tag = $base_table_data['table']['base']['access query tag'];
        $query->addTag($access_tag);
        $count_query->addTag($access_tag);
      }
    }

    $items = array();
    if ($query) {
      $additional_arguments = module_invoke_all('views_query_substitutions', $view);
      // Build the count query.
      $count_query = $count_query->countQuery();
      //  $count_query->distinct = FALSE;

      // Add additional arguments as a fake condition.
      // XXX: this doesn't work... because PDO mandates that all bound arguments
      // are used on the query. TODO: Find a better way to do this.
      if (!empty($additional_arguments)) {
        // $query->where('1 = 1', $additional_arguments);
        // $count_query->where('1 = 1', $additional_arguments);
      }

      $start = microtime(TRUE);

      if ($this->pager->use_count_query() || !empty($view->get_total_rows)) {
        $this->pager->execute_count_query($count_query);
      }

      // Let the pager modify the query to add limits.
      $this->pager->pre_execute($query);

      if (!empty($this->limit) || !empty($this->offset)) {
        // We can't have an offset without a limit, so provide a very large limit instead.
        $limit  = intval(!empty($this->limit) ? $this->limit : 999999);
        $offset = intval(!empty($this->offset) ? $this->offset : 0);
        $query->range($offset, $limit);
      }

      try {
        $result = $query->execute();

        $view->result = array();
        foreach ($result as $item) {
          $view->result[] = $item;
        }

        $this->pager->post_execute($view->result);

        if ($this->pager->use_pager()) {
          $view->total_rows = $this->pager->get_total_items();
        }
      }
      catch (Exception $e) {
        $view->result = array();
        debug('Exception: ' . $e->getMessage());
      }

    }
    $view->execute_time = microtime(TRUE) - $start;
  }

  function add_signature(&$view) {
    $view->query->add_field(NULL, "'" . $view->name . ':' . $view->current_display . "'", 'view_name');
  }

  function get_aggregation_info() {
    // @todo -- need a way to get database specific and customized aggregation
    // functions into here.
    return array(
      'group' => array(
        'title' => t('Group results together'),
        'is aggregate' => FALSE,
      ),
      'count' => array(
        'title' => t('Count'),
        'method' => 'views_query_default_aggregation_method_simple',
        'handler' => array(
          'argument' => 'views_handler_argument_group_by_numeric',
          'field' => 'views_handler_field_group_by_numeric',
          'filter' => 'views_handler_filter_group_by_numeric',
          'sort' => 'views_handler_sort_group_by_numeric',
        ),
      ),
      'sum' => array(
        'title' => t('Sum'),
        'method' => 'views_query_default_aggregation_method_simple',
        'handler' => array(
          'argument' => 'views_handler_argument_group_by_numeric',
          'field' => 'views_handler_field_group_by_numeric',
          'filter' => 'views_handler_filter_group_by_numeric',
          'sort' => 'views_handler_sort_group_by_numeric',
        ),
      ),
      'avg' => array(
        'title' => t('Average'),
        'method' => 'views_query_default_aggregation_method_simple',
        'handler' => array(
          'argument' => 'views_handler_argument_group_by_numeric',
          'field' => 'views_handler_field_group_by_numeric',
          'filter' => 'views_handler_filter_group_by_numeric',
          'sort' => 'views_handler_sort_group_by_numeric',
        ),
      ),
      'min' => array(
        'title' => t('Minimum'),
        'method' => 'views_query_default_aggregation_method_simple',
        'handler' => array(
          'argument' => 'views_handler_argument_group_by_numeric',
          'field' => 'views_handler_field_group_by_numeric',
          'filter' => 'views_handler_filter_group_by_numeric',
          'sort' => 'views_handler_sort_group_by_numeric',
        ),
      ),
      'max' => array(
        'title' => t('Maximum'),
        'method' => 'views_query_default_aggregation_method_simple',
        'handler' => array(
          'argument' => 'views_handler_argument_group_by_numeric',
          'field' => 'views_handler_field_group_by_numeric',
          'filter' => 'views_handler_filter_group_by_numeric',
          'sort' => 'views_handler_sort_group_by_numeric',
        ),
      ),
    );
  }
}

function views_query_default_aggregation_method_simple($group_type, $field) {
  return strtoupper($group_type) . '(' . $field . ')';
}
