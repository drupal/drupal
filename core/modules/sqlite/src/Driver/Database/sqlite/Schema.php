<?php

namespace Drupal\sqlite\Driver\Database\sqlite;

use Drupal\Core\Database\SchemaObjectExistsException;
use Drupal\Core\Database\SchemaObjectDoesNotExistException;
use Drupal\Core\Database\Schema as DatabaseSchema;

// cspell:ignore autoincrement autoindex

/**
 * @ingroup schemaapi
 * @{
 */

/**
 * SQLite implementation of \Drupal\Core\Database\Schema.
 */
class Schema extends DatabaseSchema {

  /**
   * Override DatabaseSchema::$defaultSchema.
   *
   * @var string
   */
  protected $defaultSchema = 'main';

  /**
   * {@inheritdoc}
   */
  public function tableExists($table, $add_prefix = TRUE) {
    $info = $this->getPrefixInfo($table, $add_prefix);

    // Don't use {} around sqlite_master table.
    return (bool) $this->connection->query('SELECT 1 FROM [' . $info['schema'] . '].sqlite_master WHERE type = :type AND name = :name', [':type' => 'table', ':name' => $info['table']])->fetchField();
  }

  /**
   * {@inheritdoc}
   */
  public function fieldExists($table, $column) {
    $schema = $this->introspectSchema($table);
    return !empty($schema['fields'][$column]);
  }

  /**
   * {@inheritdoc}
   */
  public function createTableSql($name, $table) {
    if (!empty($table['primary key']) && is_array($table['primary key'])) {
      $this->ensureNotNullPrimaryKey($table['primary key'], $table['fields']);
    }

    $sql = [];
    $sql[] = "CREATE TABLE {" . $name . "} (\n" . $this->createColumnsSql($name, $table) . "\n)\n";
    return array_merge($sql, $this->createIndexSql($name, $table));
  }

  /**
   * Build the SQL expression for indexes.
   */
  protected function createIndexSql($tablename, $schema) {
    $sql = [];
    $info = $this->getPrefixInfo($tablename);
    if (!empty($schema['unique keys'])) {
      foreach ($schema['unique keys'] as $key => $fields) {
        $sql[] = 'CREATE UNIQUE INDEX [' . $info['schema'] . '].[' . $info['table'] . '_' . $key . '] ON [' . $info['table'] . '] (' . $this->createKeySql($fields) . ")\n";
      }
    }
    if (!empty($schema['indexes'])) {
      foreach ($schema['indexes'] as $key => $fields) {
        $sql[] = 'CREATE INDEX [' . $info['schema'] . '].[' . $info['table'] . '_' . $key . '] ON [' . $info['table'] . '] (' . $this->createKeySql($fields) . ")\n";
      }
    }
    return $sql;
  }

  /**
   * Build the SQL expression for creating columns.
   */
  protected function createColumnsSql($tablename, $schema) {
    $sql_array = [];

    // Add the SQL statement for each field.
    foreach ($schema['fields'] as $name => $field) {
      if (isset($field['type']) && $field['type'] == 'serial') {
        if (isset($schema['primary key']) && ($key = array_search($name, $schema['primary key'])) !== FALSE) {
          unset($schema['primary key'][$key]);
        }
      }
      $sql_array[] = $this->createFieldSql($name, $this->processField($field));
    }

    // Process keys.
    if (!empty($schema['primary key'])) {
      $sql_array[] = " PRIMARY KEY (" . $this->createKeySql($schema['primary key']) . ")";
    }

    return implode(", \n", $sql_array);
  }

  /**
   * Build the SQL expression for keys.
   */
  protected function createKeySql($fields) {
    $return = [];
    foreach ($fields as $field) {
      if (is_array($field)) {
        $return[] = '[' . $field[0] . ']';
      }
      else {
        $return[] = '[' . $field . ']';
      }
    }
    return implode(', ', $return);
  }

  /**
   * Set database-engine specific properties for a field.
   *
   * @param $field
   *   A field description array, as specified in the schema documentation.
   */
  protected function processField($field) {
    if (!isset($field['size'])) {
      $field['size'] = 'normal';
    }

    // Set the correct database-engine specific datatype.
    // In case one is already provided, force it to uppercase.
    if (isset($field['sqlite_type'])) {
      $field['sqlite_type'] = mb_strtoupper($field['sqlite_type']);
    }
    else {
      $map = $this->getFieldTypeMap();
      $field['sqlite_type'] = $map[$field['type'] . ':' . $field['size']];

      // Numeric fields with a specified scale have to be stored as floats.
      if ($field['sqlite_type'] === 'NUMERIC' && isset($field['scale'])) {
        $field['sqlite_type'] = 'FLOAT';
      }
    }

    if (isset($field['type']) && $field['type'] == 'serial') {
      $field['auto_increment'] = TRUE;
    }

    return $field;
  }

  /**
   * Create an SQL string for a field to be used in table creation or alteration.
   *
   * Before passing a field out of a schema definition into this function it has
   * to be processed by self::processField().
   *
   * @param $name
   *   Name of the field.
   * @param $spec
   *   The field specification, as per the schema data structure format.
   */
  protected function createFieldSql($name, $spec) {
    $name = $this->connection->escapeField($name);
    if (!empty($spec['auto_increment'])) {
      $sql = $name . " INTEGER PRIMARY KEY AUTOINCREMENT";
      if (!empty($spec['unsigned'])) {
        $sql .= ' CHECK (' . $name . '>= 0)';
      }
    }
    else {
      $sql = $name . ' ' . $spec['sqlite_type'];

      if (in_array($spec['sqlite_type'], ['VARCHAR', 'TEXT'])) {
        if (isset($spec['length'])) {
          $sql .= '(' . $spec['length'] . ')';
        }

        if (isset($spec['binary']) && $spec['binary'] === FALSE) {
          $sql .= ' COLLATE NOCASE_UTF8';
        }
      }

      if (isset($spec['not null'])) {
        if ($spec['not null']) {
          $sql .= ' NOT NULL';
        }
        else {
          $sql .= ' NULL';
        }
      }

      if (!empty($spec['unsigned'])) {
        $sql .= ' CHECK (' . $name . '>= 0)';
      }

      if (isset($spec['default'])) {
        if (is_string($spec['default'])) {
          $spec['default'] = $this->connection->quote($spec['default']);
        }
        $sql .= ' DEFAULT ' . $spec['default'];
      }

      if (empty($spec['not null']) && !isset($spec['default'])) {
        $sql .= ' DEFAULT NULL';
      }
    }
    return $sql;
  }

  /**
   * {@inheritdoc}
   */
  public function getFieldTypeMap() {
    // Put :normal last so it gets preserved by array_flip. This makes
    // it much easier for modules (such as schema.module) to map
    // database types back into schema types.
    // $map does not use drupal_static as its value never changes.
    static $map = [
      'varchar_ascii:normal' => 'VARCHAR',

      'varchar:normal'  => 'VARCHAR',
      'char:normal'     => 'CHAR',

      'text:tiny'       => 'TEXT',
      'text:small'      => 'TEXT',
      'text:medium'     => 'TEXT',
      'text:big'        => 'TEXT',
      'text:normal'     => 'TEXT',

      'serial:tiny'     => 'INTEGER',
      'serial:small'    => 'INTEGER',
      'serial:medium'   => 'INTEGER',
      'serial:big'      => 'INTEGER',
      'serial:normal'   => 'INTEGER',

      'int:tiny'        => 'INTEGER',
      'int:small'       => 'INTEGER',
      'int:medium'      => 'INTEGER',
      'int:big'         => 'INTEGER',
      'int:normal'      => 'INTEGER',

      'float:tiny'      => 'FLOAT',
      'float:small'     => 'FLOAT',
      'float:medium'    => 'FLOAT',
      'float:big'       => 'FLOAT',
      'float:normal'    => 'FLOAT',

      'numeric:normal'  => 'NUMERIC',

      'blob:big'        => 'BLOB',
      'blob:normal'     => 'BLOB',
    ];
    return $map;
  }

  /**
   * {@inheritdoc}
   */
  public function renameTable($table, $new_name) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot rename '$table' to '$new_name': table '$table' doesn't exist.");
    }
    if ($this->tableExists($new_name)) {
      throw new SchemaObjectExistsException("Cannot rename '$table' to '$new_name': table '$new_name' already exists.");
    }

    $schema = $this->introspectSchema($table);

    // SQLite doesn't allow you to rename tables outside of the current
    // database. So the syntax '... RENAME TO database.table' would fail.
    // So we must determine the full table name here rather than surrounding
    // the table with curly braces in case the db_prefix contains a reference
    // to a database outside of our existing database.
    $info = $this->getPrefixInfo($new_name);
    $this->connection->query('ALTER TABLE {' . $table . '} RENAME TO [' . $info['table'] . ']');

    // Drop the indexes, there is no RENAME INDEX command in SQLite.
    if (!empty($schema['unique keys'])) {
      foreach ($schema['unique keys'] as $key => $fields) {
        $this->dropIndex($table, $key);
      }
    }
    if (!empty($schema['indexes'])) {
      foreach ($schema['indexes'] as $index => $fields) {
        $this->dropIndex($table, $index);
      }
    }

    // Recreate the indexes.
    $statements = $this->createIndexSql($new_name, $schema);
    foreach ($statements as $statement) {
      $this->connection->query($statement);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dropTable($table) {
    if (!$this->tableExists($table)) {
      return FALSE;
    }
    $this->connection->tableDropped = TRUE;
    $this->connection->query('DROP TABLE {' . $table . '}');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addField($table, $field, $specification, $keys_new = []) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add field '$table.$field': table doesn't exist.");
    }
    if ($this->fieldExists($table, $field)) {
      throw new SchemaObjectExistsException("Cannot add field '$table.$field': field already exists.");
    }
    if (isset($keys_new['primary key']) && in_array($field, $keys_new['primary key'], TRUE)) {
      $this->ensureNotNullPrimaryKey($keys_new['primary key'], [$field => $specification]);
    }

    // SQLite doesn't have a full-featured ALTER TABLE statement. It only
    // supports adding new fields to a table, in some simple cases. In most
    // cases, we have to create a new table and copy the data over.
    if (empty($keys_new) && (empty($specification['not null']) || isset($specification['default']))) {
      // When we don't have to create new keys and we are not creating a
      // NOT NULL column without a default value, we can use the quicker version.
      $query = 'ALTER TABLE {' . $table . '} ADD ' . $this->createFieldSql($field, $this->processField($specification));
      $this->connection->query($query);

      // Apply the initial value if set.
      if (isset($specification['initial_from_field'])) {
        if (isset($specification['initial'])) {
          $expression = 'COALESCE(' . $specification['initial_from_field'] . ', :default_initial_value)';
          $arguments = [':default_initial_value' => $specification['initial']];
        }
        else {
          $expression = $specification['initial_from_field'];
          $arguments = [];
        }
        $this->connection->update($table)
          ->expression($field, $expression, $arguments)
          ->execute();
      }
      elseif (isset($specification['initial'])) {
        $this->connection->update($table)
          ->fields([$field => $specification['initial']])
          ->execute();
      }
    }
    else {
      // We cannot add the field directly. Use the slower table alteration
      // method, starting from the old schema.
      $old_schema = $this->introspectSchema($table);
      $new_schema = $old_schema;

      // Add the new field.
      $new_schema['fields'][$field] = $specification;

      // Build the mapping between the old fields and the new fields.
      $mapping = [];
      if (isset($specification['initial_from_field'])) {
        // If we have an initial value, copy it over.
        if (isset($specification['initial'])) {
          $expression = 'COALESCE(' . $specification['initial_from_field'] . ', :default_initial_value)';
          $arguments = [':default_initial_value' => $specification['initial']];
        }
        else {
          $expression = $specification['initial_from_field'];
          $arguments = [];
        }
        $mapping[$field] = [
          'expression' => $expression,
          'arguments' => $arguments,
        ];
      }
      elseif (isset($specification['initial'])) {
        // If we have an initial value, copy it over.
        $mapping[$field] = [
          'expression' => ':newfieldinitial',
          'arguments' => [':newfieldinitial' => $specification['initial']],
        ];
      }
      else {
        // Else use the default of the field.
        $mapping[$field] = NULL;
      }

      // Add the new indexes.
      $new_schema = array_merge($new_schema, $keys_new);

      $this->alterTable($table, $old_schema, $new_schema, $mapping);
    }
  }

  /**
   * Create a table with a new schema containing the old content.
   *
   * As SQLite does not support ALTER TABLE (with a few exceptions) it is
   * necessary to create a new table and copy over the old content.
   *
   * @param $table
   *   Name of the table to be altered.
   * @param $old_schema
   *   The old schema array for the table.
   * @param $new_schema
   *   The new schema array for the table.
   * @param $mapping
   *   An optional mapping between the fields of the old specification and the
   *   fields of the new specification. An associative array, whose keys are
   *   the fields of the new table, and values can take two possible forms:
   *     - a simple string, which is interpreted as the name of a field of the
   *       old table,
   *     - an associative array with two keys 'expression' and 'arguments',
   *       that will be used as an expression field.
   */
  protected function alterTable($table, $old_schema, $new_schema, array $mapping = []) {
    $i = 0;
    do {
      $new_table = $table . '_' . $i++;
    } while ($this->tableExists($new_table));

    $this->createTable($new_table, $new_schema);

    // Build a SQL query to migrate the data from the old table to the new.
    $select = $this->connection->select($table);

    // Complete the mapping.
    $possible_keys = array_keys($new_schema['fields']);
    $mapping += array_combine($possible_keys, $possible_keys);

    // Now add the fields.
    foreach ($mapping as $field_alias => $field_source) {
      // Just ignore this field (ie. use its default value).
      if (!isset($field_source)) {
        continue;
      }

      if (is_array($field_source)) {
        $select->addExpression($field_source['expression'], $field_alias, $field_source['arguments']);
      }
      else {
        $select->addField($table, $field_source, $field_alias);
      }
    }

    // Execute the data migration query.
    $this->connection->insert($new_table)
      ->from($select)
      ->execute();

    $old_count = $this->connection->query('SELECT COUNT(*) FROM {' . $table . '}')->fetchField();
    $new_count = $this->connection->query('SELECT COUNT(*) FROM {' . $new_table . '}')->fetchField();
    if ($old_count == $new_count) {
      $this->dropTable($table);
      $this->renameTable($new_table, $table);
    }
  }

  /**
   * Find out the schema of a table.
   *
   * This function uses introspection methods provided by the database to
   * create a schema array. This is useful, for example, during update when
   * the old schema is not available.
   *
   * @param $table
   *   Name of the table.
   *
   * @return array
   *   An array representing the schema.
   *
   * @throws \Exception
   *   If a column of the table could not be parsed.
   */
  protected function introspectSchema($table) {
    $mapped_fields = array_flip($this->getFieldTypeMap());
    $schema = [
      'fields' => [],
      'primary key' => [],
      'unique keys' => [],
      'indexes' => [],
    ];

    $info = $this->getPrefixInfo($table);
    $result = $this->connection->query('PRAGMA [' . $info['schema'] . '].table_info([' . $info['table'] . '])');
    foreach ($result as $row) {
      if (preg_match('/^([^(]+)\((.*)\)$/', $row->type, $matches)) {
        $type = $matches[1];
        $length = $matches[2];
      }
      else {
        $type = $row->type;
        $length = NULL;
      }
      if (isset($mapped_fields[$type])) {
        [$type, $size] = explode(':', $mapped_fields[$type]);
        $schema['fields'][$row->name] = [
          'type' => $type,
          'size' => $size,
          'not null' => !empty($row->notnull) || $row->pk !== "0",
        ];
        if ($length) {
          $schema['fields'][$row->name]['length'] = $length;
        }

        // Convert the default into a properly typed value.
        if ($row->dflt_value === 'NULL') {
          $schema['fields'][$row->name]['default'] = NULL;
        }
        elseif (is_string($row->dflt_value) && $row->dflt_value[0] === '\'') {
          // Remove the wrapping single quotes. And replace duplicate single
          // quotes with a single quote.
          $schema['fields'][$row->name]['default'] = str_replace("''", "'", substr($row->dflt_value, 1, -1));
        }
        elseif (is_numeric($row->dflt_value)) {
          // Adding 0 to a string will cause PHP to convert it to a float or
          // an integer depending on what the string is. For example:
          // - '1' + 0 = 1
          // - '1.0' + 0 = 1.0
          $schema['fields'][$row->name]['default'] = $row->dflt_value + 0;
        }
        else {
          $schema['fields'][$row->name]['default'] = $row->dflt_value;
        }
        // $row->pk contains a number that reflects the primary key order. We
        // use that as the key and sort (by key) below to return the primary key
        // in the same order that it is stored in.
        if ($row->pk) {
          $schema['primary key'][$row->pk] = $row->name;
        }
      }
      else {
        throw new \Exception("Unable to parse the column type " . $row->type);
      }
    }
    ksort($schema['primary key']);
    // Re-key the array because $row->pk starts counting at 1.
    $schema['primary key'] = array_values($schema['primary key']);

    $indexes = [];
    $result = $this->connection->query('PRAGMA [' . $info['schema'] . '].index_list([' . $info['table'] . '])');
    foreach ($result as $row) {
      if (!str_starts_with($row->name, 'sqlite_autoindex_')) {
        $indexes[] = [
          'schema_key' => $row->unique ? 'unique keys' : 'indexes',
          'name' => $row->name,
        ];
      }
    }
    foreach ($indexes as $index) {
      $name = $index['name'];
      // Get index name without prefix.
      $index_name = substr($name, strlen($info['table']) + 1);
      $result = $this->connection->query('PRAGMA [' . $info['schema'] . '].index_info([' . $name . '])');
      foreach ($result as $row) {
        $schema[$index['schema_key']][$index_name][] = $row->name;
      }
    }
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function dropField($table, $field) {
    if (!$this->fieldExists($table, $field)) {
      return FALSE;
    }

    $old_schema = $this->introspectSchema($table);
    $new_schema = $old_schema;

    unset($new_schema['fields'][$field]);

    // Drop the primary key if the field to drop is part of it. This is
    // consistent with the behavior on PostgreSQL.
    // @see \Drupal\mysql\Driver\Database\mysql\Schema::dropField()
    if (isset($new_schema['primary key']) && in_array($field, $new_schema['primary key'], TRUE)) {
      unset($new_schema['primary key']);
    }

    // Handle possible index changes.
    foreach ($new_schema['indexes'] as $index => $fields) {
      foreach ($fields as $key => $field_name) {
        if ($field_name == $field) {
          unset($new_schema['indexes'][$index][$key]);
        }
      }
      // If this index has no more fields then remove it.
      if (empty($new_schema['indexes'][$index])) {
        unset($new_schema['indexes'][$index]);
      }
    }
    $this->alterTable($table, $old_schema, $new_schema);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function changeField($table, $field, $field_new, $spec, $keys_new = []) {
    if (!$this->fieldExists($table, $field)) {
      throw new SchemaObjectDoesNotExistException("Cannot change the definition of field '$table.$field': field doesn't exist.");
    }
    if (($field != $field_new) && $this->fieldExists($table, $field_new)) {
      throw new SchemaObjectExistsException("Cannot rename field '$table.$field' to '$field_new': target field already exists.");
    }
    if (isset($keys_new['primary key']) && in_array($field_new, $keys_new['primary key'], TRUE)) {
      $this->ensureNotNullPrimaryKey($keys_new['primary key'], [$field_new => $spec]);
    }

    $old_schema = $this->introspectSchema($table);
    $new_schema = $old_schema;

    // Map the old field to the new field.
    if ($field != $field_new) {
      $mapping[$field_new] = $field;
    }
    else {
      $mapping = [];
    }

    // Remove the previous definition and swap in the new one.
    unset($new_schema['fields'][$field]);
    $new_schema['fields'][$field_new] = $spec;

    // Map the former indexes to the new column name.
    $new_schema['primary key'] = $this->mapKeyDefinition($new_schema['primary key'], $mapping);
    foreach (['unique keys', 'indexes'] as $k) {
      foreach ($new_schema[$k] as &$key_definition) {
        $key_definition = $this->mapKeyDefinition($key_definition, $mapping);
      }
    }

    // Add in the keys from $keys_new.
    if (isset($keys_new['primary key'])) {
      $new_schema['primary key'] = $keys_new['primary key'];
    }
    foreach (['unique keys', 'indexes'] as $k) {
      if (!empty($keys_new[$k])) {
        $new_schema[$k] = $keys_new[$k] + $new_schema[$k];
      }
    }

    $this->alterTable($table, $old_schema, $new_schema, $mapping);
  }

  /**
   * Utility method: rename columns in an index definition according to a new mapping.
   *
   * @param $key_definition
   *   The key definition.
   * @param $mapping
   *   The new mapping.
   */
  protected function mapKeyDefinition(array $key_definition, array $mapping) {
    foreach ($key_definition as &$field) {
      // The key definition can be an array($field, $length).
      if (is_array($field)) {
        $field = &$field[0];
      }

      $mapped_field = array_search($field, $mapping, TRUE);
      if ($mapped_field !== FALSE) {
        $field = $mapped_field;
      }
    }
    return $key_definition;
  }

  /**
   * {@inheritdoc}
   */
  public function addIndex($table, $name, $fields, array $spec) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add index '$name' to table '$table': table doesn't exist.");
    }
    if ($this->indexExists($table, $name)) {
      throw new SchemaObjectExistsException("Cannot add index '$name' to table '$table': index already exists.");
    }

    $schema['indexes'][$name] = $fields;
    $statements = $this->createIndexSql($table, $schema);
    foreach ($statements as $statement) {
      $this->connection->query($statement);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function indexExists($table, $name) {
    $info = $this->getPrefixInfo($table);

    return $this->connection->query('PRAGMA [' . $info['schema'] . '].index_info([' . $info['table'] . '_' . $name . '])')->fetchField() != '';
  }

  /**
   * {@inheritdoc}
   */
  public function dropIndex($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $info = $this->getPrefixInfo($table);

    $this->connection->query('DROP INDEX [' . $info['schema'] . '].[' . $info['table'] . '_' . $name . ']');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addUniqueKey($table, $name, $fields) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add unique key '$name' to table '$table': table doesn't exist.");
    }
    if ($this->indexExists($table, $name)) {
      throw new SchemaObjectExistsException("Cannot add unique key '$name' to table '$table': unique key already exists.");
    }

    $schema['unique keys'][$name] = $fields;
    $statements = $this->createIndexSql($table, $schema);
    foreach ($statements as $statement) {
      $this->connection->query($statement);
    }
  }

  /**
   * {@inheritdoc}
   */
  public function dropUniqueKey($table, $name) {
    if (!$this->indexExists($table, $name)) {
      return FALSE;
    }

    $info = $this->getPrefixInfo($table);

    $this->connection->query('DROP INDEX [' . $info['schema'] . '].[' . $info['table'] . '_' . $name . ']');
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  public function addPrimaryKey($table, $fields) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("Cannot add primary key to table '$table': table doesn't exist.");
    }

    $old_schema = $this->introspectSchema($table);
    $new_schema = $old_schema;

    if (!empty($new_schema['primary key'])) {
      throw new SchemaObjectExistsException("Cannot add primary key to table '$table': primary key already exists.");
    }

    $new_schema['primary key'] = $fields;
    $this->ensureNotNullPrimaryKey($new_schema['primary key'], $new_schema['fields']);
    $this->alterTable($table, $old_schema, $new_schema);
  }

  /**
   * {@inheritdoc}
   */
  public function dropPrimaryKey($table) {
    $old_schema = $this->introspectSchema($table);
    $new_schema = $old_schema;

    if (empty($new_schema['primary key'])) {
      return FALSE;
    }

    unset($new_schema['primary key']);
    $this->alterTable($table, $old_schema, $new_schema);
    return TRUE;
  }

  /**
   * {@inheritdoc}
   */
  protected function findPrimaryKeyColumns($table) {
    if (!$this->tableExists($table)) {
      return FALSE;
    }
    $schema = $this->introspectSchema($table);
    return $schema['primary key'];
  }

  /**
   * {@inheritdoc}
   */
  protected function introspectIndexSchema($table) {
    if (!$this->tableExists($table)) {
      throw new SchemaObjectDoesNotExistException("The table $table doesn't exist.");
    }
    $schema = $this->introspectSchema($table);
    unset($schema['fields']);
    return $schema;
  }

  /**
   * {@inheritdoc}
   */
  public function findTables($table_expression) {
    $tables = [];

    // The SQLite implementation doesn't need to use the same filtering strategy
    // as the parent one because individually prefixed tables live in their own
    // schema (database), which means that neither the main database nor any
    // attached one will contain a prefixed table name, so we just need to loop
    // over all known schemas and filter by the user-supplied table expression.
    $attached_dbs = $this->connection->getAttachedDatabases();
    foreach ($attached_dbs as $schema) {
      // Can't use query placeholders for the schema because the query would
      // have to be :prefixsqlite_master, which does not work. We also need to
      // ignore the internal SQLite tables.
      $result = $this->connection->query("SELECT name FROM [" . $schema . "].sqlite_master WHERE type = :type AND name LIKE :table_name AND name NOT LIKE :pattern", [
        ':type' => 'table',
        ':table_name' => $table_expression,
        ':pattern' => 'sqlite_%',
      ]);
      $tables += $result->fetchAllKeyed(0, 0);
    }

    return $tables;
  }

}
