<?php

/**
 * @file
 * Defines the various handler objects to help build and display views.
 */

use Drupal\Core\Database\Database;
use Drupal\views\View;
use Drupal\views\Plugin\views\join\JoinPluginBase;
use Drupal\views\Plugin\Type\ViewsPluginManager;
use Drupal\Component\Plugin\Exception\PluginException;

/**
 * Instantiate and construct a new plugin.
 */
function _views_create_plugin($type, $definition) {
  $manager = new ViewsPluginManager($type);
  $instance = $manager->createInstance($definition['id']);

  $instance->is_plugin = TRUE;
  $instance->plugin_type = $type;
  $instance->setDefinition($definition);

  // Let the handler have something like a constructor.
  $instance->construct();

  return $instance;
}

/**
 * Instantiate and construct a new handler
 */
function _views_create_handler($type, $definition) {
  $manager = new ViewsPluginManager($type);
  // @todo This is crazy. Find a way to remove the override functionality.
  $id = !empty($definition['override handler']) ? $definition['override handler'] : $definition['id'];
  try {
    $instance = $manager->createInstance($id);
  }
  catch (PluginException $e) {
    $instance = $manager->createInstance($definition['id']);
  }

  $instance->is_handler = TRUE;
  $instance->plugin_type = $type;

  $instance->setDefinition($definition);

  // let the handler have something like a constructor.
  $instance->construct();

  return $instance;
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

  return _views_create_handler($type, $definition);
}

/**
 * Fetch a handler to join one table to a primary table from the data cache
 */
function views_get_table_join($table, $base_table) {
  $data = views_fetch_data($table);
  if (isset($data['table']['join'][$base_table])) {
    $h = $data['table']['join'][$base_table];
    if (!empty($h['join_id']) && class_exists($h['handler'])) {
      $id = $h['join_id'];
    }
    else {
      $id = 'standard';
    }
    $handler = views_get_join($id);

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
