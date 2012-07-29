<?php

/**
 * @file
 * Defines the various handler objects to help build and display views.
 */

use Drupal\Core\Database\Database;
use Drupal\views\View;
use Drupal\views\Join;
use Drupal\views\ViewsObject;
use Drupal\views\Plugin\Type\ViewsPluginManager;

/**
 * Instantiate and construct a new plugin.
 *
 * @todo
 * Figure out what to keep from _views_create_handler.
 */
function _views_create_plugin($type, $plugin_id, $definition) {
  $manager = new ViewsPluginManager($type);

  $instance = $manager->createInstance($plugin_id);

  $instance->is_plugin = TRUE;
  $instance->plugin_type = $type;
  $instance->plugin_name = $plugin_id;

  $instance->set_definition($definition);

  // Let the handler have something like a constructor.
  $instance->construct();


  return $instance;
}

/**
 * Instantiate and construct a new handler
 */
function _views_create_handler($definition, $type = 'handler', $handler_type = NULL) {
  if (!empty($definition['plugin_id'])) {
    $manager = new ViewsPluginManager($handler_type);
    $handler = $manager->createInstance($definition['plugin_id']);
  }
  else {
    // @todo: remove this else, once all instances got converted.
    $handler = new $definition['handler']($definition, $definition['handler']);
  }

  $handler->set_definition($definition);

  $handler->is_handler = TRUE;
  $handler->handler_type = $handler_type;

  // let the handler have something like a constructor.
  $handler->construct();

  return $handler;
}

/**
 * Prepare a handler's data by checking defaults and such.
 */
function _views_prepare_handler($definition, $data, $field, $type) {
  foreach (array('group', 'title', 'title short', 'help', 'real field') as $key) {
    if (!isset($definition[$key])) {
      // First check the field level
      if (!empty($data[$field][$key])) {
        $definition[$key] = $data[$field][$key];
      }
      // Then if that doesn't work, check the table level
      elseif (!empty($data['table'][$key])) {
        $definition[$key] = $data['table'][$key];
      }
    }
  }

  return _views_create_handler($definition, 'handler', $type);
}

/**
 * Fetch a handler to join one table to a primary table from the data cache
 */
function views_get_table_join($table, $base_table) {
  $data = views_fetch_data($table);
  if (isset($data['table']['join'][$base_table])) {
    $h = $data['table']['join'][$base_table];
    if (!empty($h['handler']) && class_exists($h['handler'])) {
      $handler = new $h['handler'];
    }
    else {
      $handler = new Join();
    }

    // Fill in some easy defaults
    $handler->definition = $h;
    if (empty($handler->definition['table'])) {
      $handler->definition['table'] = $table;
    }
    // If this is empty, it's a direct link.
    if (empty($handler->definition['left_table'])) {
      $handler->definition['left_table'] = $base_table;
    }

    if (isset($h['arguments'])) {
      call_user_func_array(array(&$handler, 'construct'), $h['arguments']);
    }
    else {
      $handler->construct();
    }

    return $handler;
  }

  // DEBUG -- identify missing handlers
  vpr("Missing join: @table @base_table", array('@table' => $table, '@base_table' => $base_table));
}

/**
 * This many to one helper object is used on both arguments and filters.
 *
 * @todo This requires extensive documentation on how this class is to
 * be used. For now, look at the arguments and filters that use it. Lots
 * of stuff is just pass-through but there are definitely some interesting
 * areas where they interact.
 *
 * Any handler that uses this can have the following possibly additional
 * definition terms:
 * - numeric: If true, treat this field as numeric, using %d instead of %s in
 *            queries.
 *
 */
class views_many_to_one_helper {
  function views_many_to_one_helper(&$handler) {
    $this->handler = &$handler;
  }

  static function option_definition(&$options) {
    $options['reduce_duplicates'] = array('default' => FALSE, 'bool' => TRUE);
  }

  function options_form(&$form, &$form_state) {
    $form['reduce_duplicates'] = array(
      '#type' => 'checkbox',
      '#title' => t('Reduce duplicates'),
      '#description' => t('This filter can cause items that have more than one of the selected options to appear as duplicate results. If this filter causes duplicate results to occur, this checkbox can reduce those duplicates; however, the more terms it has to search for, the less performant the query will be, so use this with caution. Shouldn\'t be set on single-value fields, as it may cause values to disappear from display, if used on an incompatible field.'),
      '#default_value' => !empty($this->handler->options['reduce_duplicates']),
      '#weight' => 4,
    );
  }

  /**
   * Sometimes the handler might want us to use some kind of formula, so give
   * it that option. If it wants us to do this, it must set $helper->formula = TRUE
   * and implement handler->get_formula();
   */
  function get_field() {
    if (!empty($this->formula)) {
      return $this->handler->get_formula();
    }
    else {
      return $this->handler->table_alias . '.' . $this->handler->real_field;
    }
  }

  /**
   * Add a table to the query.
   *
   * This is an advanced concept; not only does it add a new instance of the table,
   * but it follows the relationship path all the way down to the relationship
   * link point and adds *that* as a new relationship and then adds the table to
   * the relationship, if necessary.
   */
  function add_table($join = NULL, $alias = NULL) {
    // This is used for lookups in the many_to_one table.
    $field = $this->handler->relationship . '_' . $this->handler->table . '.' . $this->handler->field;

    if (empty($join)) {
      $join = $this->get_join();
    }

    // See if there's a chain between us and the base relationship. If so, we need
    // to create a new relationship to use.
    $relationship = $this->handler->relationship;

    // Determine the primary table to seek
    if (empty($this->handler->query->relationships[$relationship])) {
      $base_table = $this->handler->query->base_table;
    }
    else {
      $base_table = $this->handler->query->relationships[$relationship]['base'];
    }

    // Cycle through the joins. This isn't as error-safe as the normal
    // ensure_path logic. Perhaps it should be.
    $r_join = clone $join;
    while ($r_join->left_table != $base_table) {
      $r_join = views_get_table_join($r_join->left_table, $base_table);
    }
    // If we found that there are tables in between, add the relationship.
    if ($r_join->table != $join->table) {
      $relationship = $this->handler->query->add_relationship($this->handler->table . '_' . $r_join->table, $r_join, $r_join->table, $this->handler->relationship);
    }

    // And now add our table, using the new relationship if one was used.
    $alias = $this->handler->query->add_table($this->handler->table, $relationship, $join, $alias);

    // Store what values are used by this table chain so that other chains can
    // automatically discard those values.
    if (empty($this->handler->view->many_to_one_tables[$field])) {
      $this->handler->view->many_to_one_tables[$field] = $this->handler->value;
    }
    else {
      $this->handler->view->many_to_one_tables[$field] = array_merge($this->handler->view->many_to_one_tables[$field], $this->handler->value);
    }

    return $alias;
  }

  function get_join() {
    return $this->handler->get_join();
  }

  /**
   * Provide the proper join for summary queries. This is important in part because
   * it will cooperate with other arguments if possible.
   */
  function summary_join() {
    $field = $this->handler->relationship . '_' . $this->handler->table . '.' . $this->handler->field;
    $join = $this->get_join();

    // shortcuts
    $options = $this->handler->options;
    $view = &$this->handler->view;
    $query = &$this->handler->query;

    if (!empty($options['require_value'])) {
      $join->type = 'INNER';
    }

    if (empty($options['add_table']) || empty($view->many_to_one_tables[$field])) {
      return $query->ensure_table($this->handler->table, $this->handler->relationship, $join);
    }
    else {
      if (!empty($view->many_to_one_tables[$field])) {
        foreach ($view->many_to_one_tables[$field] as $value) {
          $join->extra = array(
            array(
              'field' => $this->handler->real_field,
              'operator' => '!=',
              'value' => $value,
              'numeric' => !empty($this->definition['numeric']),
            ),
          );
        }
      }
      return $this->add_table($join);
    }
  }

  /**
   * Override ensure_my_table so we can control how this joins in.
   * The operator actually has influence over joining.
   */
  function ensure_my_table() {
    if (!isset($this->handler->table_alias)) {
      // Case 1: Operator is an 'or' and we're not reducing duplicates.
      // We hence get the absolute simplest:
      $field = $this->handler->relationship . '_' . $this->handler->table . '.' . $this->handler->field;
      if ($this->handler->operator == 'or' && empty($this->handler->options['reduce_duplicates'])) {
        if (empty($this->handler->options['add_table']) && empty($this->handler->view->many_to_one_tables[$field])) {
          // query optimization, INNER joins are slightly faster, so use them
          // when we know we can.
          $join = $this->get_join();
          if (isset($join)) {
            $join->type = 'INNER';
          }
          $this->handler->table_alias = $this->handler->query->ensure_table($this->handler->table, $this->handler->relationship, $join);
          $this->handler->view->many_to_one_tables[$field] = $this->handler->value;
        }
        else {
          $join = $this->get_join();
          $join->type = 'LEFT';
          if (!empty($this->handler->view->many_to_one_tables[$field])) {
            foreach ($this->handler->view->many_to_one_tables[$field] as $value) {
              $join->extra = array(
                array(
                  'field' => $this->handler->real_field,
                  'operator' => '!=',
                  'value' => $value,
                  'numeric' => !empty($this->handler->definition['numeric']),
                ),
              );
            }
          }

          $this->handler->table_alias = $this->add_table($join);
        }

        return $this->handler->table_alias;
      }

      // Case 2: it's an 'and' or an 'or'.
      // We do one join per selected value.
      if ($this->handler->operator != 'not') {
        // Clone the join for each table:
        $this->handler->table_aliases = array();
        foreach ($this->handler->value as $value) {
          $join = $this->get_join();
          if ($this->handler->operator == 'and') {
            $join->type = 'INNER';
          }
          $join->extra = array(
            array(
              'field' => $this->handler->real_field,
              'value' => $value,
              'numeric' => !empty($this->handler->definition['numeric']),
            ),
          );

          // The table alias needs to be unique to this value across the
          // multiple times the filter or argument is called by the view.
          if (!isset($this->handler->view->many_to_one_aliases[$field][$value])) {
            if (!isset($this->handler->view->many_to_one_count[$this->handler->table])) {
              $this->handler->view->many_to_one_count[$this->handler->table] = 0;
            }
            $this->handler->view->many_to_one_aliases[$field][$value] = $this->handler->table . '_value_' . ($this->handler->view->many_to_one_count[$this->handler->table]++);
          }
          $alias = $this->handler->table_aliases[$value] = $this->add_table($join, $this->handler->view->many_to_one_aliases[$field][$value]);

          // and set table_alias to the first of these.
          if (empty($this->handler->table_alias)) {
            $this->handler->table_alias = $alias;
          }
        }
      }
      // Case 3: it's a 'not'.
      // We just do one join. We'll add a where clause during
      // the query phase to ensure that $table.$field IS NULL.
      else {
        $join = $this->get_join();
        $join->type = 'LEFT';
        $join->extra = array();
        $join->extra_type = 'OR';
        foreach ($this->handler->value as $value) {
          $join->extra[] = array(
            'field' => $this->handler->real_field,
            'value' => $value,
            'numeric' => !empty($this->handler->definition['numeric']),
          );
        }

        $this->handler->table_alias = $this->add_table($join);
      }
    }
    return $this->handler->table_alias;
  }

  /**
   * Provides a unique placeholders for handlers.
   */
  function placeholder() {
    return $this->handler->query->placeholder($this->handler->options['table'] . '_' . $this->handler->options['field']);
  }

  function add_filter() {
    if (empty($this->handler->value)) {
      return;
    }
    $this->handler->ensure_my_table();

    // Shorten some variables:
    $field = $this->get_field();
    $options = $this->handler->options;
    $operator = $this->handler->operator;
    $formula = !empty($this->formula);
    $value = $this->handler->value;
    if (empty($options['group'])) {
      $options['group'] = 0;
    }

    // add_condition determines whether a single expression is enough(FALSE) or the
    // conditions should be added via an db_or()/db_and() (TRUE).
    $add_condition = TRUE;
    if ($operator == 'not') {
      $value = NULL;
      $operator = 'IS NULL';
      $add_condition = FALSE;
    }
    elseif ($operator == 'or' && empty($options['reduce_duplicates'])) {
      if (count($value) > 1) {
        $operator = 'IN';
      }
      else {
        $value = is_array($value) ? array_pop($value) : $value;
        $operator = '=';
      }
      $add_condition = FALSE;
    }

    if (!$add_condition) {
      if ($formula) {
        $placeholder = $this->placeholder();
        if ($operator == 'IN') {
          $operator = "$operator IN($placeholder)";
        }
        else {
          $operator = "$operator $placeholder";
        }
        $placeholders = array(
          $placeholder => $value,
        ) + $this->placeholders;
        $this->handler->query->add_where_expression($options['group'], "$field $operator", $placeholders);
      }
      else {
        $this->handler->query->add_where($options['group'], $field, $value, $operator);
      }
    }

    if ($add_condition) {
      $field = $this->handler->real_field;
      $clause = $operator == 'or' ? db_or() : db_and();
      foreach ($this->handler->table_aliases as $value => $alias) {
        $clause->condition("$alias.$field", $value);
      }

      // implode on either AND or OR.
      $this->handler->query->add_where($options['group'], $clause);
    }
  }
}

/**
 * Break x,y,z and x+y+z into an array. Works for strings.
 *
 * @param $str
 *   The string to parse.
 * @param $object
 *   The object to use as a base. If not specified one will
 *   be created.
 *
 * @return $object
 *   An object containing
 *   - operator: Either 'and' or 'or'
 *   - value: An array of numeric values.
 */
function views_break_phrase_string($str, &$handler = NULL) {
  if (!$handler) {
    $handler = new stdClass();
  }

  // Set up defaults:
  if (!isset($handler->value)) {
    $handler->value = array();
  }

  if (!isset($handler->operator)) {
    $handler->operator = 'or';
  }

  if ($str == '') {
    return $handler;
  }

  // Determine if the string has 'or' operators (plus signs) or 'and' operators
  // (commas) and split the string accordingly. If we have an 'and' operator,
  // spaces are treated as part of the word being split, but otherwise they are
  // treated the same as a plus sign.
  $or_wildcard = '[^\s+,]';
  $and_wildcard = '[^+,]';
  if (preg_match("/^({$or_wildcard}+[+ ])+{$or_wildcard}+$/", $str)) {
    $handler->operator = 'or';
    $handler->value = preg_split('/[+ ]/', $str);
  }
  elseif (preg_match("/^({$and_wildcard}+,)*{$and_wildcard}+$/", $str)) {
    $handler->operator = 'and';
    $handler->value = explode(',', $str);
  }

  // Keep an 'error' value if invalid strings were given.
  if (!empty($str) && (empty($handler->value) || !is_array($handler->value))) {
    $handler->value = array(-1);
    return $handler;
  }

  // Doubly ensure that all values are strings only.
  foreach ($handler->value as $id => $value) {
    $handler->value[$id] = (string) $value;
  }

  return $handler;
}

/**
 * Break x,y,z and x+y+z into an array. Numeric only.
 *
 * @param $str
 *   The string to parse.
 * @param $handler
 *   The handler object to use as a base. If not specified one will
 *   be created.
 *
 * @return $handler
 *   The new handler object.
 */
function views_break_phrase($str, &$handler = NULL) {
  if (!$handler) {
    $handler = new stdClass();
  }

  // Set up defaults:

  if (!isset($handler->value)) {
    $handler->value = array();
  }

  if (!isset($handler->operator)) {
    $handler->operator = 'or';
  }

  if (empty($str)) {
    return $handler;
  }

  if (preg_match('/^([0-9]+[+ ])+[0-9]+$/', $str)) {
    // The '+' character in a query string may be parsed as ' '.
    $handler->operator = 'or';
    $handler->value = preg_split('/[+ ]/', $str);
  }
  elseif (preg_match('/^([0-9]+,)*[0-9]+$/', $str)) {
    $handler->operator = 'and';
    $handler->value = explode(',', $str);
  }

  // Keep an 'error' value if invalid strings were given.
  if (!empty($str) && (empty($handler->value) || !is_array($handler->value))) {
    $handler->value = array(-1);
    return $handler;
  }

  // Doubly ensure that all values are numeric only.
  foreach ($handler->value as $id => $value) {
    $handler->value[$id] = intval($value);
  }

  return $handler;
}

// --------------------------------------------------------------------------
// Date helper functions

/**
 * Figure out what timezone we're in; needed for some date manipulations.
 */
function views_get_timezone() {
  global $user;
  if (variable_get('configurable_timezones', 1) && $user->uid && strlen($user->timezone)) {
    $timezone = $user->timezone;
  }
  else {
    $timezone = variable_get('date_default_timezone', 0);
  }

  // set up the database timezone
  $db_type = Database::getConnection()->databaseType();
  if (in_array($db_type, array('mysql', 'pgsql'))) {
    $offset = '+00:00';
    static $already_set = FALSE;
    if (!$already_set) {
      if ($db_type == 'pgsql') {
        db_query("SET TIME ZONE INTERVAL '$offset' HOUR TO MINUTE");
      }
      elseif ($db_type == 'mysql') {
        db_query("SET @@session.time_zone = '$offset'");
      }

      $already_set = TRUE;
    }
  }

  return $timezone;
}

/**
 * Helper function to create cross-database SQL dates.
 *
 * @param $field
 *   The real table and field name, like 'tablename.fieldname'.
 * @param $field_type
 *  The type of date field, 'int' or 'datetime'.
 * @param $set_offset
 *   The name of a field that holds the timezone offset or a fixed timezone
 *   offset value. If not provided, the normal Drupal timezone handling
 *   will be used, i.e. $set_offset = 0 will make no timezone adjustment.
 * @return
 *   An appropriate SQL string for the db type and field type.
 */
function views_date_sql_field($field, $field_type = 'int', $set_offset = NULL) {
  $db_type = Database::getConnection()->databaseType();
  $offset = $set_offset !== NULL ? $set_offset : views_get_timezone();
  if (isset($offset) && !is_numeric($offset)) {
    $dtz = new DateTimeZone($offset);
    $dt = new DateTime("now", $dtz);
    $offset_seconds = $dtz->getOffset($dt);
  }

  switch ($db_type) {
    case 'mysql':
      switch ($field_type) {
        case 'int':
          $field = "DATE_ADD('19700101', INTERVAL $field SECOND)";
          break;
        case 'datetime':
          break;
      }
      if (!empty($offset)) {
        $field = "($field + INTERVAL $offset_seconds SECOND)";
      }
      return $field;
    case 'pgsql':
      switch ($field_type) {
        case 'int':
          $field = "TO_TIMESTAMP($field)";
          break;
        case 'datetime':
          break;
      }
      if (!empty($offset)) {
        $field = "($field + INTERVAL '$offset_seconds SECONDS')";
      }
      return $field;
    case 'sqlite':
      if (!empty($offset)) {
        $field = "($field + '$offset_seconds')";
      }
      return $field;
  }
}

/**
 * Helper function to create cross-database SQL date formatting.
 *
 * @param $format
 *   A format string for the result, like 'Y-m-d H:i:s'.
 * @param $field
 *   The real table and field name, like 'tablename.fieldname'.
 * @param $field_type
 *   The type of date field, 'int' or 'datetime'.
 * @param $set_offset
 *   The name of a field that holds the timezone offset or a fixed timezone
 *   offset value. If not provided, the normal Drupal timezone handling
 *   will be used, i.e. $set_offset = 0 will make no timezone adjustment.
 * @return
 *   An appropriate SQL string for the db type and field type.
 */
function views_date_sql_format($format, $field, $field_type = 'int', $set_offset = NULL) {
  $db_type = Database::getConnection()->databaseType();
  $field = views_date_sql_field($field, $field_type, $set_offset);
  switch ($db_type) {
    case 'mysql':
      $replace = array(
        'Y' => '%Y',
        'y' => '%y',
        'M' => '%b',
        'm' => '%m',
        'n' => '%c',
        'F' => '%M',
        'D' => '%a',
        'd' => '%d',
        'l' => '%W',
        'j' => '%e',
        'W' => '%v',
        'H' => '%H',
        'h' => '%h',
        'i' => '%i',
        's' => '%s',
        'A' => '%p',
        );
      $format = strtr($format, $replace);
      return "DATE_FORMAT($field, '$format')";
    case 'pgsql':
      $replace = array(
        'Y' => 'YYYY',
        'y' => 'YY',
        'M' => 'Mon',
        'm' => 'MM',
        'n' => 'MM', // no format for Numeric representation of a month, without leading zeros
        'F' => 'Month',
        'D' => 'Dy',
        'd' => 'DD',
        'l' => 'Day',
        'j' => 'DD', // no format for Day of the month without leading zeros
        'W' => 'WW',
        'H' => 'HH24',
        'h' => 'HH12',
        'i' => 'MI',
        's' => 'SS',
        'A' => 'AM',
        );
      $format = strtr($format, $replace);
      return "TO_CHAR($field, '$format')";
    case 'sqlite':
      $replace = array(
        'Y' => '%Y', // 4 digit year number
        'y' => '%Y', // no format for 2 digit year number
        'M' => '%m', // no format for 3 letter month name
        'm' => '%m', // month number with leading zeros
        'n' => '%m', // no format for month number without leading zeros
        'F' => '%m', // no format for full month name
        'D' => '%d', // no format for 3 letter day name
        'd' => '%d', // day of month number with leading zeros
        'l' => '%d', // no format for full day name
        'j' => '%d', // no format for day of month number without leading zeros
        'W' => '%W', // ISO week number
        'H' => '%H', // 24 hour hour with leading zeros
        'h' => '%H', // no format for 12 hour hour with leading zeros
        'i' => '%M', // minutes with leading zeros
        's' => '%S', // seconds with leading zeros
        'A' => '', // no format for  AM/PM
      );
      $format = strtr($format, $replace);
      return "strftime('$format', $field, 'unixepoch')";
  }
}

/**
 * Helper function to create cross-database SQL date extraction.
 *
 * @param $extract_type
 *   The type of value to extract from the date, like 'MONTH'.
 * @param $field
 *   The real table and field name, like 'tablename.fieldname'.
 * @param $field_type
 *   The type of date field, 'int' or 'datetime'.
 * @param $set_offset
 *   The name of a field that holds the timezone offset or a fixed timezone
 *   offset value. If not provided, the normal Drupal timezone handling
 *   will be used, i.e. $set_offset = 0 will make no timezone adjustment.
 * @return
 *   An appropriate SQL string for the db type and field type.
 */
function views_date_sql_extract($extract_type, $field, $field_type = 'int', $set_offset = NULL) {
  $db_type = Database::getConnection()->databaseType();
  $field = views_date_sql_field($field, $field_type, $set_offset);

  // Note there is no space after FROM to avoid db_rewrite problems
  // see http://drupal.org/node/79904.
  switch ($extract_type) {
    case 'DATE':
      return $field;
    case 'YEAR':
      return "EXTRACT(YEAR FROM($field))";
    case 'MONTH':
      return "EXTRACT(MONTH FROM($field))";
    case 'DAY':
      return "EXTRACT(DAY FROM($field))";
    case 'HOUR':
      return "EXTRACT(HOUR FROM($field))";
    case 'MINUTE':
      return "EXTRACT(MINUTE FROM($field))";
    case 'SECOND':
      return "EXTRACT(SECOND FROM($field))";
    // ISO week number for date
    case 'WEEK':
      switch ($db_type) {
        case 'mysql':
          // WEEK using arg 3 in mysql should return the same value as postgres
          // EXTRACT.
          return "WEEK($field, 3)";
        case 'pgsql':
          return "EXTRACT(WEEK FROM($field))";
      }
    case 'DOW':
      switch ($db_type) {
        case 'mysql':
          // mysql returns 1 for Sunday through 7 for Saturday php date
          // functions and postgres use 0 for Sunday and 6 for Saturday.
          return "INTEGER(DAYOFWEEK($field) - 1)";
        case 'pgsql':
          return "EXTRACT(DOW FROM($field))";
      }
    case 'DOY':
      switch ($db_type) {
        case 'mysql':
          return "DAYOFYEAR($field)";
        case 'pgsql':
          return "EXTRACT(DOY FROM($field))";
      }
  }
}

/**
 * @}
 */
